/* Every text editor in the admin panel: when text is pasted (for example from Word), throw away its
   font size, font name, colours and spacing, but keep bold, italic, underline, lists, tables and links.
   The editor itself then shows everything in Calibri 12. Load this right after tinymce.min.js. */
(function () {
    if (!window.tinymce || tinymce.__pasteClean) return;
    tinymce.__pasteClean = true;

    const DROP = /^(font|color|background|line-height|margin|padding|text-indent|mso-|tab-stops|letter-spacing|word-spacing|width|height|border-collapse)/i;

    function clean(root) {
        root.querySelectorAll('o\\:p, style, meta, link, xml').forEach(n => n.remove());
        root.querySelectorAll('font').forEach(f => { while (f.firstChild) f.parentNode.insertBefore(f.firstChild, f); f.remove(); });
        root.querySelectorAll('*').forEach(el => {
            ['class', 'lang', 'face', 'size', 'color', 'bgcolor'].forEach(a => el.removeAttribute(a));
            const st = el.getAttribute('style');
            if (st === null) return;
            const keep = [];
            let bold = false, italic = false, under = false;
            st.split(';').forEach(rule => {
                const i = rule.indexOf(':'); if (i < 0) return;
                const k = rule.slice(0, i).trim().toLowerCase(), v = rule.slice(i + 1).trim();
                if (k === 'font-weight') { bold = bold || /bold|[6-9]00/.test(v); return; }
                if (k === 'font-style') { italic = italic || /italic|oblique/.test(v); return; }
                if (k === 'text-decoration' || k === 'text-decoration-line') { under = under || /underline/.test(v); return; }
                if (k === 'text-align') { keep.push(k + ':' + v); return; }
                if (DROP.test(k)) return;
            });
            if (keep.length) el.setAttribute('style', keep.join(';')); else el.removeAttribute('style');
            // Word writes bold / italic as styles on a span: turn them into real <strong> / <em> / <u>
            if (el.tagName === 'SPAN' && (bold || italic || under)) {
                let inner = el.innerHTML;
                if (under) inner = '<u>' + inner + '</u>';
                if (italic) inner = '<em>' + inner + '</em>';
                if (bold) inner = '<strong>' + inner + '</strong>';
                el.innerHTML = inner;
            }
        });
        // plain spans left over carry nothing: unwrap them
        root.querySelectorAll('span').forEach(s => { if (!s.attributes.length) { while (s.firstChild) s.parentNode.insertBefore(s.firstChild, s); s.remove(); } });
    }

    const init = tinymce.init.bind(tinymce);
    const base = 'body{font-family:Calibri,Carlito,"Segoe UI",Arial,sans-serif;font-size:12pt}';
    tinymce.init = function (cfg) {
        cfg = Object.assign({}, cfg);
        // Catch the paste ourselves, before TinyMCE's own Word filter drops bold / italic written as styles
        const userSetup = cfg.setup;
        cfg.setup = function (editor) {
            editor.on('paste', function (e) {
                const cd = e.clipboardData; if (!cd || (cd.files && cd.files.length)) return;
                const html = cd.getData('text/html'); if (!html) return;      // plain text: leave as it is
                try {
                    const d = new DOMParser().parseFromString(html.replace(/<!--[\s\S]*?-->/g, ''), 'text/html');
                    clean(d.body);
                    e.preventDefault();
                    editor.execCommand('mceInsertClipboardContent', false, { html: d.body.innerHTML });
                } catch (_) {}
            });
            if (userSetup) userSetup(editor);
        };
        const after = cfg.paste_postprocess;
        cfg.paste_postprocess = function (editor, args) { clean(args.node); if (after) after(editor, args); };
        cfg.paste_data_images = cfg.paste_data_images || false;
        // later rules win, so the Calibri 12 default sits at the end of the editor's own style
        cfg.content_style = (cfg.content_style || '') + ' ' + base + ' body *{font-family:inherit}';
        return init(cfg);
    };
})();
