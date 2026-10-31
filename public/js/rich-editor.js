/*
 * Etude World – the ONE text editor used for blogs, language lessons, info pages, SEO page text and author bios.
 * (The test pages – IELTS, CELPIP, TOEFL, English level – keep their own editors.)
 *
 * Built on TinyMCE (free, open-source, stored in public/vendor/tinymce). Features:
 *   headings, bold / italic / underline, colours, alignment, lists, links, tables, quotes, code, full screen,
 *   pictures (with ALT text), audio, video, documents (PDF, Word …) uploaded from the computer with a description,
 *   YouTube-style video embeds, call-to-action buttons and FAQ blocks (the website adds FAQ search-engine data by itself).
 *
 * Use:  EtudeRichEditor.init({ selector: '#body', height: 520 })        (profile: 'full' | 'simple')
 * Load after tinymce.min.js, editor-paste-clean.js and editor-blocks.js.
 */
window.EtudeRichEditor = (function () {
    const meta = n => (document.querySelector('meta[name="' + n + '"]') || {}).content || '';
    const KINDS = {
        image: ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        audio: ['mp3', 'wav', 'ogg', 'm4a', 'aac'],
        video: ['mp4', 'webm', 'mov'],
    };
    const kindOf = name => {
        const ext = String(name).split('.').pop().toLowerCase();
        return Object.keys(KINDS).find(k => KINDS[k].includes(ext)) || 'document';
    };
    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

    /** Send one file to the website; resolves with {location, kind, ext, name}. */
    function upload(file) {
        const data = new FormData();
        data.append('file', file, file.name);
        return fetch(meta('editor-upload-url'), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': meta('csrf-token'), 'Accept': 'application/json' },
            body: data,
        }).then(r => r.json().catch(() => ({})).then(j => {
            if (r.ok && j.location) return j;
            const first = j.errors ? Object.values(j.errors)[0][0] : null;
            throw new Error(first || j.message || 'The file could not be uploaded (it may be too big for the server).');
        }));
    }

    /** What goes into the text for an uploaded file. */
    function htmlFor(res, d) {
        const alt = (d.alt || '').trim();
        const url = esc(res.location);
        switch (res.kind) {
            case 'image':
                return `<p><img src="${url}" alt="${esc(alt)}"></p>`;
            case 'audio':
                return `<p><audio controls preload="none" src="${url}" title="${esc(alt)}" aria-label="${esc(alt)}"></audio></p>`;
            case 'video':
                return `<p><video controls preload="metadata" src="${url}" title="${esc(alt)}" aria-label="${esc(alt)}"></video></p>`;
            default: {
                const text = (d.text || '').trim() || (res.name + ' (' + res.ext.toUpperCase() + ')');
                return `<p><a class="ew-file" href="${url}" target="_blank" rel="noopener"${alt ? ` title="${esc(alt)}"` : ''}>${esc(text)}</a></p>`;
            }
        }
    }

    /** "Attach file" window: choose a file, describe it (alt text), insert it. */
    function openAttach(editor) {
        editor.windowManager.open({
            title: 'Attach a file – picture, audio, video or document',
            size: 'medium',
            body: {
                type: 'panel',
                items: [
                    { type: 'dropzone', name: 'files', label: 'File' },
                    { type: 'input', name: 'alt', label: 'Alt text – a short description of the file (needed for pictures, audio and video)', placeholder: 'e.g. Student writing an IELTS Task 2 essay' },
                    { type: 'input', name: 'text', label: 'Link text (documents only)', placeholder: 'e.g. Download the free worksheet' },
                    { type: 'htmlpanel', html: '<p style="font-size:12px;color:#5d6679;margin:0">Pictures JPG, PNG, WebP, GIF · Audio MP3, WAV, OGG, M4A · Video MP4, WebM, MOV · Documents PDF, Word, Excel, PowerPoint, ZIP. Up to 50 MB.</p>' },
                ],
            },
            initialData: { files: [], alt: '', text: '' },
            buttons: [{ type: 'cancel', text: 'Cancel' }, { type: 'submit', text: 'Upload & insert', primary: true }],
            onSubmit: api => {
                const d = api.getData();
                const file = d.files && d.files[0];
                if (!file) return editor.windowManager.alert('Please choose a file first.');
                if (kindOf(file.name) !== 'document' && !(d.alt || '').trim()) {
                    return editor.windowManager.alert('Please add the alt text (a short description). It helps people who cannot see or hear the file, and search engines.');
                }
                api.block('Uploading…');
                upload(file).then(res => {
                    editor.undoManager.transact(() => editor.insertContent(htmlFor(res, d)));
                    api.close();
                }).catch(err => {
                    api.unblock();
                    editor.windowManager.alert(err.message);
                });
            },
        });
    }

    /** The "Browse" buttons inside the Image / Link / Media windows: pick a file from the computer and upload it. */
    function filePicker(callback, value, m) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = m.filetype === 'image' ? 'image/*' : m.filetype === 'media' ? 'audio/*,video/*' : '*/*';
        input.onchange = () => {
            const f = input.files[0];
            if (!f) return;
            upload(f).then(res => {
                const base = f.name.replace(/\.[^.]+$/, '');
                callback(res.location, m.filetype === 'image' ? { alt: '' } : { text: base, title: base });
            }).catch(err => alert(err.message));
        };
        input.click();
    }

    const CONTENT_STYLE =
        'body{font-family:"Plus Jakarta Sans",system-ui,sans-serif;font-size:16px;line-height:1.7;color:#1c2333;max-width:760px;margin:16px auto;padding:0 12px}' +
        'h1,h2,h3{color:#13234a;line-height:1.25} img,video{max-width:100%;height:auto} audio{width:100%}' +
        'blockquote{border-left:4px solid #2f6bff;margin:1em 0;padding:.4em 1em;color:#5d6679;background:#f4f7fc}' +
        'table{border-collapse:collapse}td,th{border:1px solid #cfd5e2;padding:6px 10px}' +
        // pictures with no alt text get an orange dashed frame so they are easy to spot (decorative ones are fine)
        'img:not([alt]):not([role=presentation]),img[alt=""]:not([role=presentation]){outline:2px dashed #ff8a3d;outline-offset:2px}';

    const PROFILES = {
        full: {
            menubar: 'edit view insert format table tools',
            menu: { insert: { title: 'Insert', items: 'ewattach ewbutton ewfaq | image link media table | charmap hr anchor insertdatetime' } },
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table wordcount help autoresize quickbars',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright | ' +
                     'bullist numlist outdent indent | link image media ewattach table blockquote | ewbutton ewfaq | removeformat code fullscreen',
            statusbar: true,
        },
        simple: {   // short texts such as an author's bio
            menubar: false,
            plugins: 'lists link autolink',
            toolbar: 'bold italic underline | bullist numlist | link | removeformat',
            statusbar: false,
        },
    };

    function init(opts) {
        const p = PROFILES[opts.profile || 'full'];
        const full = (opts.profile || 'full') === 'full';
        const cfg = Object.assign({
            license_key: 'gpl', promotion: false, branding: false,
            height: opts.height || 560,
            menubar: p.menubar, menu: p.menu, plugins: p.plugins, toolbar: p.toolbar, statusbar: p.statusbar,
            toolbar_mode: 'wrap',
            content_css: (document.querySelector('meta[name="editor-content-css"]') || {}).content || '/css/content-blocks.css?v=3',
            content_style: CONTENT_STYLE,
            noneditable_class: 'ew-block',
            extended_valid_elements: 'details[class|open|data-ew],summary[class],a[href|target|rel|class|data-ew|data-style|data-align|title|download],' +
                                     'audio[controls|src|preload|title|aria-label|class],video[controls|src|poster|width|height|preload|title|aria-label|class|playsinline],source[src|type]',
            block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6; Preformatted=pre',
            contextmenu: full ? 'ewformat link image table' : false,
            contextmenu_never_use_native: full,
            quickbars_insert_toolbar: false,
            quickbars_selection_toolbar: 'bold italic | h2 h3 | link blockquote',
            autoresize_bottom_margin: 40,
            min_height: opts.minHeight || (full ? 480 : 220),
            link_default_target: '_self', link_title: false,
            image_caption: true, image_title: true, image_advtab: true, image_description: true, image_uploadtab: true,
            media_alt_source: false, media_poster: false,
            convert_urls: false,
            automatic_uploads: true,
            file_picker_types: 'file image media',
            file_picker_callback: filePicker,
            images_upload_handler: blob => upload(new File([blob.blob()], blob.filename(), { type: blob.blob().type })).then(r => r.location),
            setup: editor => {
                if (window.EtudeEditorBlocks) { if (full) EtudeEditorBlocks.register(editor); else EtudeEditorBlocks.basics(editor); }
                editor.ui.registry.addButton('ewattach', { icon: 'upload', text: 'Attach file', tooltip: 'Attach a picture, audio, video or document (with alt text)', onAction: () => openAttach(editor) });
                editor.ui.registry.addMenuItem('ewattach', { icon: 'upload', text: 'Attach file…', onAction: () => openAttach(editor) });
                if (opts.seo !== false) {
                    const update = () => window.EtudeSeo && window.EtudeSeo.refresh(editor.getContent());
                    editor.on('init keyup change SetContent NodeChange', update);
                }
                if (opts.setup) opts.setup(editor);
            },
        }, opts.selector ? { selector: opts.selector } : { target: opts.target });
        if (opts.profile === 'simple') cfg.content_style = CONTENT_STYLE.replace('font-size:16px', 'font-size:15px');
        return tinymce.init(cfg);
    }

    return { init, upload };
})();
