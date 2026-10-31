/*
 * Etude World admin – CELPIP Reading page.
 * Each part has a passage (left side on the website) and any number of question sections (right side):
 *   Multiple Choice  (#id-31)  instructions + questions, each with a statement and any number of options
 *   Fill in Blanks   (#id-33)  instructions + a text where every "#" becomes a question with its own options
 *   Matching         (#id-37)  like Multiple Choice, but on the website the options come before the statement
 * Everything is collected into one JSON value when Save is clicked.
 */
(function () {
    const form = document.getElementById('reading-form');
    if (!form) return;
    const $  = (s, el = document) => el.querySelector(s);
    const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
    const { content, types, defaults } = JSON.parse(document.getElementById('reading-data').textContent);
    const csrf = $('meta[name="csrf-token"]').content;
    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const dirty = () => form.dispatchEvent(new Event('input'));
    let uid = 0;

    /* ---------- text editors ---------- */
    function uploadImage(blobInfo, progress) {
        return new Promise((resolve, reject) => {
            const data = new FormData();
            data.append('file', blobInfo.blob(), blobInfo.filename());
            data.append('kind', 'image');
            data.append('folder', 'reading');
            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.dataset.uploadUrl);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.onprogress = e => e.lengthComputable && progress(e.loaded / e.total * 100);
            xhr.onload = () => {
                let res = {};
                try { res = JSON.parse(xhr.responseText); } catch (_) {}
                if (xhr.status < 300 && res.path) resolve('/' + res.path);
                else reject({ message: (res.errors && res.errors.file && res.errors.file[0]) || 'Upload failed', remove: true });
            };
            xhr.onerror = () => reject({ message: 'Upload failed. Check your connection.', remove: true });
            xhr.send(data);
        });
    }

    function startEditor(el) {
        if (!window.tinymce || el.dataset.started) return;
        if (el.closest('details:not([open])')) return;          // started when its part is opened
        el.dataset.started = '1';
        const passage = el.classList.contains('rd-editor--passage');
        const blanks = el.classList.contains('rd-editor--blanks');
        tinymce.init({
            target: el,
            license_key: 'gpl', promotion: false, branding: false, menubar: false,
            plugins: passage ? 'lists link autolink autoresize image table code' : 'lists link autolink autoresize code',
            toolbar: passage
                ? 'blocks | bold italic underline | forecolor | bullist numlist | image table | link | removeformat code'
                : 'bold italic underline | bullist numlist | link | removeformat',
            block_formats: 'Paragraph=p; Heading 3=h3; Heading 4=h4',
            contextmenu: passage ? 'bold italic underline | link image table' : 'bold italic underline | link',
            contextmenu_never_use_native: true,
            min_height: passage ? 260 : blanks ? 200 : 110,
            autoresize_bottom_margin: 16,
            convert_urls: false,
            image_caption: false, image_dimensions: true,
            images_upload_handler: uploadImage,
            automatic_uploads: true,
            file_picker_types: 'image',
            placeholder: el.getAttribute('placeholder') || '',
            content_style: 'body{font-family:"Plus Jakarta Sans",system-ui,sans-serif;font-size:15px;line-height:1.6;color:#1c2333;margin:10px 14px} img{max-width:100%;height:auto} table{border-collapse:collapse} td,th{border:1px solid #ccd3e0;padding:6px 8px}',
            setup: editor => {
                if (window.EtudeEditorBlocks) EtudeEditorBlocks.basics(editor);
                editor.on('input change undo redo keyup', () => {
                    editor.save();
                    dirty();
                    if (blanks) syncBlanks(el.closest('[data-section]'));
                });
            },
        });
    }
    const startEditors = root => $$('textarea.rd-editor', root).forEach(startEditor);
    const editorValue = el => (window.tinymce && tinymce.get(el.id)) ? tinymce.get(el.id).getContent() : el.value;
    const editorText = el => {
        const ed = window.tinymce && tinymce.get(el.id);
        if (ed) return ed.getContent({ format: 'text' });
        const d = document.createElement('div'); d.innerHTML = el.value; return d.textContent;
    };

    /* ---------- drawing ---------- */
    function optionRow(q, text, k, correct) {
        return `<div class="rd-opt ${correct ? 'is-correct' : ''}" data-opt>
            <label class="rd-opt__correct" title="Mark as the correct answer">
                <input type="radio" name="rd-correct-${q}" ${correct ? 'checked' : ''} data-opt-correct>
                <span class="rd-opt__num">${k + 1}</span>
            </label>
            <input type="text" value="${esc(text)}" placeholder="Option ${k + 1}" data-opt-text>
            <button type="button" class="rd-x" title="Remove this option" data-opt-remove>×</button>
        </div>`;
    }

    function questionEl(type, q) {
        const id = ++uid;
        const div = document.createElement('div');
        div.className = 'rd-q';
        div.dataset.q = '';
        const opts = q.options && q.options.length ? q.options : ['', '', '', ''];
        div.innerHTML = `
            <div class="rd-q__head">
                <span class="rd-q__num" data-q-num>Q</span>
                <span class="ls-status" data-q-status>Incomplete</span>
                ${type === 'blanks' ? '' : '<button type="button" class="rd-link-danger" data-q-remove>Remove question</button>'}
            </div>
            ${type === 'blanks' ? '' : `<input type="text" class="rd-q__statement" value="${esc(q.statement)}" data-q-statement
                placeholder="${type === 'matching' ? 'Statement, e.g. There are physical differences between males and females.' : 'Question statement, e.g. Maria’s mother is now'}">`}
            <div class="rd-opts" data-opts>${opts.map((o, k) => optionRow(id, o, k, q.correct === k)).join('')}</div>
            <button type="button" class="rd-link" data-opt-add>+ Add option</button>`;
        div.dataset.uid = id;
        return div;
    }

    function sectionEl(part, s) {
        const t = types[s.type];
        const sec = document.createElement('section');
        sec.className = 'ls-card ls-card--inner rd-section rd-section--' + s.type;
        sec.dataset.section = '';
        sec.dataset.type = s.type;
        const n = ++uid;
        sec.innerHTML = `
            <div class="rd-section__head">
                <span class="ls-tag">${esc(t.label)}</span>
                <span class="muted-inline">${s.type === 'matching' ? 'On the website the options come before the statement.' : s.type === 'blanks' ? 'Type # in the text wherever a drop-down should appear.' : 'The statement appears above its options.'}</span>
                <button type="button" class="rd-link-danger" data-section-remove>Remove section</button>
            </div>
            <div class="field">
                <label for="rd-ins-${n}">Instructions <span class="muted-inline">– shown above all questions in this section</span></label>
                <textarea id="rd-ins-${n}" class="rd-editor" rows="3">${esc(s.instructions)}</textarea>
            </div>
            ${s.type === 'blanks' ? `
            <div class="field">
                <label for="rd-blank-${n}">Text with blanks <span class="muted-inline">– every <strong>#</strong> becomes a question</span></label>
                <textarea id="rd-blank-${n}" class="rd-editor rd-editor--blanks" rows="6" data-blanks-text
                    placeholder="e.g. Count us in, we would hate to miss #. We'll leave # at 6am…">${esc(s.passage)}</textarea>
                <p class="hint" data-blank-count></p>
            </div>` : ''}
            <div class="rd-questions" data-questions></div>
            ${s.type === 'blanks' ? '' : '<button type="button" class="btn btn-sm btn-ghost" data-q-add>+ Add question</button>'}`;
        const list = $('[data-questions]', sec);
        (s.questions || []).forEach(q => list.appendChild(questionEl(s.type, q)));
        sec._stash = [];
        return sec;
    }

    /* ---------- numbering, status and the part progress ---------- */
    function refresh(partEl) {
        let num = 0, done = 0;
        $$('[data-section]', partEl).forEach((sec, si) => {
            const t = types[sec.dataset.type];
            sec.id = `id-${t.id}-part-${partEl.dataset.rdPart}-${si + 1}`;
            $$('[data-q]', sec).forEach(q => {
                num++;
                $('[data-q-num]', q).textContent = sec.dataset.type === 'blanks' ? `Blank ${num}` : `Q${num}`;
                const opts = $$('[data-opt]', q);
                opts.forEach((o, k) => {
                    $('.rd-opt__num', o).textContent = sec.dataset.type === 'matching' ? String.fromCharCode(65 + k) : k + 1;
                    $('[data-opt-text]', o).placeholder = 'Option ' + (sec.dataset.type === 'matching' ? String.fromCharCode(65 + k) : k + 1);
                    o.classList.toggle('is-correct', $('[data-opt-correct]', o).checked);
                });
                const filled = opts.filter(o => $('[data-opt-text]', o).value.trim()).length;
                const right = opts.find(o => $('[data-opt-correct]', o).checked);
                const st = $('[data-q-statement]', q);
                const ok = (!st || st.value.trim()) && filled >= 2 && right && $('[data-opt-text]', right).value.trim();
                q.classList.toggle('is-complete', !!ok);
                $('[data-q-status]', q).textContent = ok ? 'Complete' : 'Incomplete';
                if (ok) done++;
            });
        });
        const chip = $('[data-rd-progress]', partEl);
        chip.textContent = `${done} / ${num} questions complete`;
        chip.classList.toggle('is-done', num > 0 && done === num);
    }

    /* Fill in the Blanks: one question per "#" in the text. Removed blanks are kept aside in case the # comes back. */
    function syncBlanks(sec) {
        const count = (editorText($('[data-blanks-text]', sec)).match(/#/g) || []).length;
        const list = $('[data-questions]', sec);
        const qs = $$('[data-q]', list);
        while (qs.length > count) { const q = qs.pop(); q.remove(); sec._stash.unshift(q); }
        while (qs.length < count) { const q = sec._stash.shift() || questionEl('blanks', { options: ['', '', ''] }); list.appendChild(q); qs.push(q); }
        $('[data-blank-count]', sec).textContent = count
            ? `${count} blank${count > 1 ? 's' : ''} found – add the options for each one below.`
            : 'No # found yet. Type # where each blank should be.';
        refresh(sec.closest('[data-rd-part]'));
    }

    /* ---------- build the page ---------- */
    $$('[data-rd-part]').forEach(partEl => {
        const p = partEl.dataset.rdPart;
        const box = $('[data-rd-sections]', partEl);
        (content.parts[p].sections || []).forEach(s => box.appendChild(sectionEl(p, s)));
        $$('[data-section][data-type="blanks"]', box).forEach(sec => {
            $('[data-blank-count]', sec).textContent = `${$$('[data-q]', sec).length} blank(s) – add the options for each one below.`;
        });
        refresh(partEl);
        partEl.addEventListener('toggle', () => { if (partEl.open) startEditors(partEl); });
    });
    startEditors(document);

    /* ---------- buttons ---------- */
    form.addEventListener('click', e => {
        const b = e.target.closest('button');
        if (!b) return;
        const partEl = b.closest('[data-rd-part]');
        if (b.dataset.rdAdd) {
            const type = b.dataset.rdAdd;
            const sec = sectionEl(partEl.dataset.rdPart, {
                type, instructions: defaults[type], passage: '',
                questions: type === 'blanks' ? [] : [{ statement: '', options: type === 'matching' ? ['A', 'B', 'C', 'D', 'E'] : ['', '', '', ''], correct: null }],
            });
            $('[data-rd-sections]', partEl).appendChild(sec);
            if (type === 'blanks') $('[data-blank-count]', sec).textContent = 'No # found yet. Type # where each blank should be.';
            startEditors(sec);
            sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else if (b.hasAttribute('data-section-remove')) {
            if (!window.confirm('Remove this whole section and its questions?')) return;
            const sec = b.closest('[data-section]');
            $$('textarea.rd-editor', sec).forEach(t => { const ed = window.tinymce && tinymce.get(t.id); if (ed) ed.remove(); });
            sec.remove();
        } else if (b.hasAttribute('data-q-add')) {
            const sec = b.closest('[data-section]');
            const last = $$('[data-q]', sec).pop();
            // Matching: copy the previous question's options (usually the same A–E list)
            const options = sec.dataset.type === 'matching' && last ? $$('[data-opt-text]', last).map(i => i.value) : sec.dataset.type === 'matching' ? ['A', 'B', 'C', 'D', 'E'] : ['', '', '', ''];
            const q = questionEl(sec.dataset.type, { statement: '', options, correct: null });
            $('[data-questions]', sec).appendChild(q);
            const st = $('[data-q-statement]', q); if (st) st.focus();
        } else if (b.hasAttribute('data-q-remove')) {
            b.closest('[data-q]').remove();
        } else if (b.hasAttribute('data-opt-add')) {
            const q = b.closest('[data-q]');
            const k = $$('[data-opt]', q).length;
            $('[data-opts]', q).insertAdjacentHTML('beforeend', optionRow(q.dataset.uid, '', k, false));
            $$('[data-opt-text]', q).pop().focus();
        } else if (b.hasAttribute('data-opt-remove')) {
            const q = b.closest('[data-q]');
            if ($$('[data-opt]', q).length <= 1) return;
            b.closest('[data-opt]').remove();
        } else {
            return;
        }
        if (partEl) refresh(partEl);
        dirty();
    });
    form.addEventListener('input', e => { const partEl = e.target.closest && e.target.closest('[data-rd-part]'); if (partEl) refresh(partEl); });
    form.addEventListener('change', e => { const partEl = e.target.closest && e.target.closest('[data-rd-part]'); if (partEl) refresh(partEl); });

    /* ---------- save: collect everything into one JSON value ---------- */
    function collect() {
        const out = {
            intro: { text: editorValue($('#reading-intro')), audio: $('#reading-intro-audio [data-media-path]').value },
            parts: {},
        };
        $$('[data-rd-part]').forEach(partEl => {
            out.parts[partEl.dataset.rdPart] = {
                passage: editorValue($('.rd-editor--passage', partEl)),
                sections: $$('[data-section]', partEl).map(sec => ({
                    type: sec.dataset.type,
                    instructions: editorValue($('textarea.rd-editor:not(.rd-editor--blanks)', sec)),
                    passage: sec.dataset.type === 'blanks' ? editorValue($('[data-blanks-text]', sec)) : '',
                    questions: $$('[data-q]', sec).map(q => {
                        const opts = $$('[data-opt]', q);
                        const st = $('[data-q-statement]', q);
                        const correct = opts.findIndex(o => $('[data-opt-correct]', o).checked);
                        return {
                            statement: st ? st.value : '',
                            options: opts.map(o => $('[data-opt-text]', o).value),
                            correct: correct >= 0 ? correct : null,
                        };
                    }),
                })),
            };
        });
        return out;
    }
    form.addEventListener('submit', () => { $('[data-reading-json]').value = JSON.stringify(collect()); });
})();
