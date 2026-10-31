/*
 * Etude World – extra tools for the blog text editor (TinyMCE):
 *   • Button  – a call-to-action button (text, link, colour, alignment)
 *   • FAQ     – a "Frequently Asked Questions" block. Questions and answers are typed
 *               straight into the editor (Enter in a question jumps to its answer;
 *               Enter on an empty line at the end of an answer starts a new question).
 *   • Right-click menu – Bold / Italic / Headings / FAQ / Button.
 *
 * The button is a locked block: double-click it to change it.
 */
window.EtudeEditorBlocks = (function () {
    const esc = s => String(s ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

    /* ───────────────────────── BUTTON ───────────────────────── */

    const BUTTON_STYLES = [
        { value: 'primary', text: 'Blue (main)' },
        { value: 'accent',  text: 'Orange (eye-catching)' },
        { value: 'outline', text: 'White with border (subtle)' },
    ];
    const ALIGNS = [
        { value: 'left',   text: 'Left' },
        { value: 'center', text: 'Centre' },
        { value: 'right',  text: 'Right' },
    ];

    function buttonHtml(d) {
        const url = d.url.trim();
        const newTab = d.newtab ? ' target="_blank" rel="noopener"' : '';
        return `<p class="ew-btn-wrap ew-align-${esc(d.align)}">` +
            `<a class="ew-block ew-btn ew-btn--${esc(d.style)}" data-ew="button" href="${esc(url)}"${newTab}>${esc(d.text.trim())}</a></p>`;
    }

    function openButtonDialog(editor, existing) {
        const wrap = existing ? existing.closest('.ew-btn-wrap') : null;
        const initial = existing ? {
            text: existing.textContent.trim(),
            url: existing.getAttribute('href') || '',
            style: (BUTTON_STYLES.find(s => existing.classList.contains('ew-btn--' + s.value)) || BUTTON_STYLES[0]).value,
            align: (ALIGNS.find(a => wrap && wrap.classList.contains('ew-align-' + a.value)) || ALIGNS[0]).value,
            newtab: existing.getAttribute('target') === '_blank',
        } : { text: '', url: '', style: 'primary', align: 'left', newtab: false };

        editor.windowManager.open({
            title: existing ? 'Edit button' : 'Insert button',
            size: 'normal',
            body: {
                type: 'panel',
                items: [
                    { type: 'input', name: 'text', label: 'Button text', placeholder: 'e.g. Take a free IELTS test' },
                    { type: 'input', name: 'url', label: 'Link (where the button goes)', placeholder: 'https://… or /free-test/ielts-academic' },
                    { type: 'grid', columns: 2, items: [
                        { type: 'selectbox', name: 'style', label: 'Colour', items: BUTTON_STYLES },
                        { type: 'selectbox', name: 'align', label: 'Position', items: ALIGNS },
                    ]},
                    { type: 'checkbox', name: 'newtab', label: 'Open the link in a new tab' },
                ],
            },
            initialData: initial,
            buttons: [
                ...(existing ? [{ type: 'custom', name: 'remove', text: 'Remove button', align: 'start' }] : []),
                { type: 'cancel', text: 'Cancel' },
                { type: 'submit', text: existing ? 'Save' : 'Insert', primary: true },
            ],
            onAction: (api, details) => {
                if (details.name === 'remove') {
                    editor.undoManager.transact(() => editor.dom.remove(wrap && wrap.children.length === 1 ? wrap : existing));
                    api.close();
                }
            },
            onSubmit: api => {
                const d = api.getData();
                if (!d.text.trim() || !d.url.trim()) {
                    editor.windowManager.alert('Please fill in both the button text and the link.');
                    return;
                }
                editor.undoManager.transact(() => {
                    if (existing) {
                        editor.selection.select(wrap && wrap.children.length === 1 ? wrap : existing);
                    }
                    editor.insertContent(buttonHtml(d));
                });
                api.close();
            },
        });
    }

    /* ───────────────────────── FAQ (typed directly in the editor) ───────────────────────── */

    const Q_PLACEHOLDER = 'Type your question here';
    const A_PLACEHOLDER = 'Type the answer here.';

    const faqItemHtml = () =>
        `<div class="ew-faq__item"><h3 class="ew-faq__q">${Q_PLACEHOLDER}</h3>` +
        `<div class="ew-faq__a"><p>${A_PLACEHOLDER}</p></div></div>`;

    const faqBlockHtml = () =>
        `<div class="ew-faq" data-ew="faq"><h2 class="ew-faq__title">Frequently Asked Questions</h2>` +
        faqItemHtml() + faqItemHtml() + `</div>`;

    // Older FAQ blocks (made with the pop-up version) are converted to the new editable layout.
    function upgradeFaqHtml(html) {
        if (!html || html.indexOf('ew-faq') === -1) return html;
        const doc = new DOMParser().parseFromString('<body>' + html + '</body>', 'text/html');
        doc.querySelectorAll('.ew-faq').forEach(faq => {
            faq.classList.remove('ew-block');
            faq.querySelectorAll('details.ew-faq__item').forEach(det => {
                const item = doc.createElement('div');
                item.className = 'ew-faq__item';
                const q = doc.createElement('h3');
                q.className = 'ew-faq__q';
                const summary = det.querySelector('summary');
                q.innerHTML = summary ? summary.innerHTML : '';
                item.appendChild(q);
                const a = det.querySelector('.ew-faq__a') || doc.createElement('div');
                a.className = 'ew-faq__a';
                if (!a.innerHTML.trim()) a.innerHTML = '<p></p>';
                item.appendChild(a);
                det.replaceWith(item);
            });
        });
        return doc.body.innerHTML;
    }

    function selectText(editor, el) {
        if (!el) return;
        editor.selection.select(el, true);
        editor.focus();
    }

    function caretTo(editor, el, atEnd) {
        if (!el) return;
        // If it still shows the example text, select it so typing replaces it.
        const t = el.textContent.trim();
        if (t === Q_PLACEHOLDER || t === A_PLACEHOLDER) return selectText(editor, el);
        editor.selection.select(el, true);
        editor.selection.collapse(!atEnd);
        editor.focus();
    }

    function insertFaq(editor) {
        const inside = editor.selection.getNode().closest('.ew-faq');
        if (inside) return addQuestion(editor);
        editor.undoManager.transact(() => {
            editor.insertContent(faqBlockHtml() + '<p>&nbsp;</p>');
        });
        // Select the first question so you can start typing straight away.
        const blocks = editor.getBody().querySelectorAll('.ew-faq');
        const block = blocks[blocks.length - 1];
        const all = Array.from(editor.getBody().querySelectorAll('.ew-faq__q')).filter(q => q.textContent === Q_PLACEHOLDER);
        selectText(editor, all[0] || (block && block.querySelector('.ew-faq__q')));
    }

    function addQuestion(editor) {
        const node = editor.selection.getNode();
        const faq = node.closest('.ew-faq');
        if (!faq) return;
        const current = node.closest('.ew-faq__item');
        const wrapper = editor.dom.create('div');
        wrapper.innerHTML = faqItemHtml();
        const item = wrapper.firstChild;
        editor.undoManager.transact(() => {
            if (current) current.after(item); else faq.appendChild(item);
        });
        selectText(editor, item.querySelector('.ew-faq__q'));
        editor.nodeChanged();
    }

    function deleteQuestion(editor) {
        const node = editor.selection.getNode();
        const item = node.closest('.ew-faq__item');
        const faq = node.closest('.ew-faq');
        if (!item || !faq) return;
        const next = item.nextElementSibling || item.previousElementSibling;
        editor.undoManager.transact(() => {
            item.remove();
            if (!faq.querySelector('.ew-faq__item')) faq.remove();
        });
        if (next && next.classList.contains('ew-faq__item')) caretTo(editor, next.querySelector('.ew-faq__q'), true);
        editor.nodeChanged();
    }

    function deleteFaq(editor) {
        const faq = editor.selection.getNode().closest('.ew-faq');
        if (!faq) return;
        if (!confirm('Delete the whole FAQ block?')) return;
        const after = faq.nextElementSibling;
        editor.undoManager.transact(() => faq.remove());
        if (after) caretTo(editor, after, false);
        editor.nodeChanged();
    }

    function writeBelow(editor) {
        const faq = editor.selection.getNode().closest('.ew-faq');
        if (!faq) return;
        let next = faq.nextElementSibling;
        if (!next || next.tagName !== 'P') {
            next = editor.dom.create('p', {}, '<br data-mce-bogus="1">');
            faq.after(next);
        }
        caretTo(editor, next, false);
        editor.nodeChanged();
    }

    // Keyboard behaviour inside a FAQ block.
    function faqKeys(editor, e) {
        const node = editor.selection.getNode();
        if (!node || !node.closest('.ew-faq')) return;
        const rng = editor.selection.getRng();
        const atStart = el => {
            if (!rng.collapsed) return false;
            const r = rng.cloneRange();
            r.selectNodeContents(el);
            r.setEnd(rng.startContainer, rng.startOffset);
            return r.toString().length === 0;
        };

        if (e.key === 'Enter' && !e.shiftKey) {
            const title = node.closest('.ew-faq__title');
            const q = node.closest('.ew-faq__q');
            if (title) {                                     // heading → first question
                e.preventDefault();
                return caretTo(editor, title.parentNode.querySelector('.ew-faq__q'), true);
            }
            if (q) {                                         // question → its answer
                e.preventDefault();
                const a = q.parentNode.querySelector('.ew-faq__a');
                return caretTo(editor, a.querySelector('p') || a, true);
            }
            const p = node.closest('.ew-faq__a p');
            if (p && !p.textContent.trim() && !p.nextElementSibling && p.previousElementSibling) {
                e.preventDefault();                          // Enter on an empty last line → new question
                editor.undoManager.transact(() => p.remove());
                return addQuestion(editor);
            }
        }

        if (e.key === 'Backspace') {
            // Stop Backspace from merging a question into the previous answer (which would break the block).
            const q = node.closest('.ew-faq__q');
            const firstP = node.closest('.ew-faq__a p');
            if ((q && atStart(q)) || (firstP && !firstP.previousElementSibling && atStart(firstP))) {
                e.preventDefault();
            }
        }
    }

    /* Fixes used by every editor on the admin pages. */
    function basics(editor) {
        // The editor normally jumps the page to the top of the text box the first time you click
        // into it after scrolling down. That jump is annoying in long blogs and also closed the
        // right-click menu, so it is switched off here.
        editor.on('init', () => {
            if (editor.iframeElement) editor.iframeElement.scrollIntoView = function () {};
        });

        // Right-click: put the cursor where you clicked, so formatting applies there.
        editor.on('mousedown', e => {
            if (e.button !== 2) return;
            // Move the cursor to where you right-clicked (unless you right-clicked inside selected text),
            // so the editor doesn't jump back to the old cursor position and close the menu.
            const sel = editor.selection.getRng();
            const insideSelection = !sel.collapsed && sel.intersectsNode && sel.intersectsNode(e.target);
            if (!insideSelection) {
                try {
                    const doc = editor.getDoc();
                    const pos = doc.caretPositionFromPoint ? doc.caretPositionFromPoint(e.clientX, e.clientY) : null;
                    const rng = pos ? doc.createRange() : (doc.caretRangeFromPoint ? doc.caretRangeFromPoint(e.clientX, e.clientY) : null);
                    if (pos) { rng.setStart(pos.offsetNode, pos.offset); rng.collapse(true); }
                    if (rng) editor.selection.setRng(rng);
                } catch (_) {}
            }
            if (!editor.hasFocus()) {
                try { editor.getBody().focus({ preventScroll: true }); } catch (_) {}
            }
        });

    }

    /* ───────────────────────── wiring ───────────────────────── */

    function register(editor) {
        const current = selector => {
            const node = editor.selection.getNode();
            return node ? node.closest(selector) : null;
        };

        editor.ui.registry.addIcon('ew-button', '<svg width="24" height="24" viewBox="0 0 24 24"><rect x="2.5" y="7" width="19" height="10" rx="5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 12h8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>');
        editor.ui.registry.addIcon('ew-faq', '<svg width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6v.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="17" r="1.1"/></svg>');

        editor.ui.registry.addButton('ewbutton', {
            icon: 'ew-button', text: 'Button', tooltip: 'Insert a button',
            onAction: () => openButtonDialog(editor, current('a[data-ew="button"]')),
        });
        editor.ui.registry.addButton('ewfaq', {
            icon: 'ew-faq', text: 'FAQ', tooltip: 'Insert a FAQ (questions & answers) block',
            onAction: () => insertFaq(editor),
        });
        editor.ui.registry.addMenuItem('ewbutton', {
            icon: 'ew-button', text: 'Button…',
            onAction: () => openButtonDialog(editor, current('a[data-ew="button"]')),
        });
        editor.ui.registry.addMenuItem('ewfaq', {
            icon: 'ew-faq', text: 'FAQ block',
            onAction: () => insertFaq(editor),
        });

        // FAQ helpers (toolbar that appears inside a FAQ block + right-click menu)
        const faqActions = {
            ewfaqadd:   { text: '+ Add question',   icon: 'plus',   run: () => addQuestion(editor) },
            ewfaqdelq:  { text: 'Delete question',  icon: 'remove', run: () => deleteQuestion(editor) },
            ewfaqbelow: { text: 'Write below FAQ',  icon: 'new-document', run: () => writeBelow(editor) },
            ewfaqdel:   { text: 'Delete FAQ block', icon: 'close',  run: () => deleteFaq(editor) },
        };
        Object.entries(faqActions).forEach(([name, a]) => {
            editor.ui.registry.addButton(name, { text: a.text, onAction: a.run });
            editor.ui.registry.addMenuItem(name, { text: a.text, icon: a.icon, onAction: a.run });
        });
        editor.ui.registry.addContextToolbar('ew-faq-toolbar', {
            // Anchored to the whole FAQ block (shown above it), so it never covers what you're typing.
            predicate: node => !!(node.classList && node.classList.contains('ew-faq')),
            items: 'ewfaqadd ewfaqdelq | ewfaqbelow | ewfaqdel',
            position: 'node',
            scope: 'node',
        });

        /* ---------- Right-click menu ---------- */
        const fmt = name => () => editor.execCommand('mceToggleFormat', false, name);
        const headingItem = (name, text) => editor.ui.registry.addToggleMenuItem(name, {
            text,
            onAction: fmt(name.replace('ew', '')),
            onSetup: api => {
                const tag = name.replace('ew', '');
                api.setActive(editor.formatter.match(tag));
                return editor.formatter.formatChanged(tag, state => api.setActive(state)).unbind;
            },
        });
        headingItem('ewh2', 'Heading 2');
        headingItem('ewh3', 'Heading 3');
        headingItem('ewh4', 'Heading 4');
        editor.ui.registry.addMenuItem('ewpara', { text: 'Normal text', onAction: fmt('p') });
        editor.ui.registry.addContextMenu('ewformat', {
            update: el => {
                if (el.closest && el.closest('.ew-faq')) {
                    return 'bold italic underline | ewfaqadd ewfaqdelq ewfaqbelow | ewfaqdel';
                }
                if (el.closest && el.closest('a[data-ew="button"]')) {
                    return 'ewedit';
                }
                return 'bold italic underline | ewh2 ewh3 ewh4 ewpara | ewfaq ewbutton';
            },
        });

        basics(editor);

        // Keyboard rules inside FAQ blocks, and convert old FAQ blocks when a blog is opened.
        editor.on('keydown', e => faqKeys(editor, e));
        editor.on('BeforeSetContent', e => { e.content = upgradeFaqHtml(e.content); });

        // Double-click a button or FAQ block to edit it.
        editor.on('dblclick', e => {
            const btn = e.target.closest && e.target.closest('a[data-ew="button"]');
            if (btn) { e.preventDefault(); editor.selection.select(btn); return openButtonDialog(editor, btn); }
        });

        // Clicking a button inside the editor must not follow its link.
        editor.on('click', e => {
            if (e.target.closest && e.target.closest('a[data-ew="button"]')) e.preventDefault();
        });

        // Small "Edit" bar that pops up when a block is selected.
        editor.ui.registry.addMenuItem('ewedit', {
            icon: 'edit-block', text: 'Edit button…',
            onAction: () => openButtonDialog(editor, current('a[data-ew="button"]')),
        });
        editor.ui.registry.addButton('ewedit', {
            icon: 'edit-block', text: 'Edit',
            onAction: () => {
                const btn = current('a[data-ew="button"]');
                if (btn) return openButtonDialog(editor, btn);
            },
        });
        editor.ui.registry.addContextToolbar('ew-block-toolbar', {
            predicate: node => !!(node.closest && node.closest('a[data-ew="button"]')),
            items: 'ewedit',
            position: 'node',
            scope: 'node',
        });
    }

    return { register, basics };
})();
