/*
 * Etude World admin – CELPIP Listening page.
 *  • Text editors start only when their bar/question is opened (keeps the page fast).
 *  • Audio/image files upload as soon as they're chosen, with a progress bar.
 *  • Each question shows "Complete" once it has a question, 4 options, a correct answer and audio.
 *  • Warns before leaving with unsaved changes.
 */
(function () {
    const form = document.getElementById('listening-form') || document.getElementById('reading-form');
    if (!form) return;
    const $  = (s, el = document) => el.querySelector(s);
    const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    /* ---------- unsaved changes ---------- */
    let dirty = false;
    const status = $('[data-save-status]');
    const markDirty = () => {
        if (dirty) return;
        dirty = true;
        status.textContent = 'You have unsaved changes.';
        status.classList.add('is-dirty');
    };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    form.addEventListener('submit', () => { if (window.tinymce) tinymce.triggerSave(); dirty = false; });

    /* ---------- text editors (started when visible) ---------- */
    function startEditors(root) {
        if (!window.tinymce) return;
        $$('textarea.ls-editor', root).forEach(el => {
            if (el.dataset.started) return;
            if (el.closest('details:not([open])')) return;      // still hidden – start later
            el.dataset.started = '1';
            const small = el.classList.contains('ls-editor--small');
            tinymce.init({
                target: el,
                license_key: 'gpl',
                promotion: false,
                branding: false,
                menubar: false,
                plugins: 'lists link autolink autoresize code',
                toolbar: small
                    ? 'bold italic underline | bullist numlist | link | removeformat'
                    : 'blocks | bold italic underline | forecolor | bullist numlist | link | removeformat code',
                block_formats: 'Paragraph=p; Heading 3=h3; Heading 4=h4',
                contextmenu: 'bold italic underline | link',
                contextmenu_never_use_native: true,
                min_height: small ? 120 : 200,
                autoresize_bottom_margin: 16,
                convert_urls: false,
                placeholder: el.getAttribute('placeholder') || '',
                content_style: 'body{font-family:"Plus Jakarta Sans",system-ui,sans-serif;font-size:15px;line-height:1.6;color:#1c2333;margin:10px 14px} h3{color:#13234a;margin:.2em 0 .5em} ul,ol{padding-left:1.4em} li{margin:.25em 0}',
                setup: editor => {
                    if (window.EtudeEditorBlocks) EtudeEditorBlocks.basics(editor);
                    editor.on('input change undo redo', () => {
                        editor.save();
                        markDirty();
                        const q = el.closest('[data-question]');
                        if (q) updateQuestion(q);
                    });
                },
            });
        });
    }
    startEditors(document);
    window.EWStartEditors = startEditors;     // used by pages that add question boxes (e.g. IELTS Speaking "+")
    window.EWMarkDirty = markDirty;
    $$('details').forEach(d => d.addEventListener('toggle', () => { if (d.open) startEditors(d); }));

    /* ---------- uploads ---------- */
    function setPreview(field, url) {
        const kind = field.dataset.kind;
        const preview = $('[data-media-preview]', field);
        preview.innerHTML = '';
        if (!url) {
            preview.innerHTML = `<span class="media-empty">No ${kind} yet</span>`;
        } else if (kind === 'audio') {
            const a = document.createElement('audio');
            a.controls = true; a.preload = 'metadata'; a.src = url;
            preview.appendChild(a);
        } else {
            const img = document.createElement('img');
            img.src = url; img.alt = '';
            preview.appendChild(img);
        }
        field.classList.toggle('has-file', !!url);
        $('[data-media-remove]', field).hidden = !url;
        $('[data-media-pick-label]', field).textContent = (url ? 'Replace ' : 'Upload ') + kind;
        const def = $('[data-media-default]', field);
        if (def) def.hidden = !(url && /\/audio\/celpip\//.test(url));
    }

    function upload(field, file) {
        const msg = $('[data-media-status]', field);
        const bar = $('[data-media-progress]', field);
        const data = new FormData();
        data.append('file', file);
        data.append('kind', field.dataset.kind);
        if (form.dataset.uploadFolder) data.append('folder', form.dataset.uploadFolder);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.dataset.uploadUrl);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.onprogress = e => {
            if (!e.lengthComputable) return;
            $('span', bar).style.width = Math.round(e.loaded / e.total * 100) + '%';
        };
        xhr.onloadstart = () => { bar.hidden = false; $('span', bar).style.width = '0%'; msg.textContent = 'Uploading…'; msg.className = 'media-status'; };
        xhr.onload = () => {
            bar.hidden = true;
            let res = {};
            try { res = JSON.parse(xhr.responseText); } catch (_) {}
            if (xhr.status >= 200 && xhr.status < 300 && res.path) {
                $('[data-media-path]', field).value = res.path;
                setPreview(field, res.url);
                msg.textContent = 'Uploaded ✓';
                msg.className = 'media-status is-ok';
                markDirty();
                const q = field.closest('[data-question]');
                if (q) updateQuestion(q);
                field.dispatchEvent(new Event('media-change', { bubbles: true }));
            } else {
                const err = (res.errors && res.errors.file && res.errors.file[0]) || res.message || 'Upload failed. Please try again.';
                msg.textContent = err;
                msg.className = 'media-status is-error';
            }
        };
        xhr.onerror = () => { bar.hidden = true; msg.textContent = 'Upload failed. Check your connection and try again.'; msg.className = 'media-status is-error'; };
        xhr.send(data);
    }

    function bindMedia(field) {
        if (field.dataset.bound) return;
        field.dataset.bound = '1';
        const input = $('[data-media-input]', field);
        input.addEventListener('change', () => { if (input.files[0]) upload(field, input.files[0]); input.value = ''; });
        $('[data-media-remove]', field).addEventListener('click', () => {
            $('[data-media-path]', field).value = '';
            setPreview(field, '');
            $('[data-media-status]', field).textContent = '';
            markDirty();
            const q = field.closest('[data-question]');
            if (q) updateQuestion(q);
            field.dispatchEvent(new Event('media-change', { bubbles: true }));
        });
    }
    $$('[data-media]').forEach(bindMedia);
    window.EWBindMedia = bindMedia;          // for upload boxes added later (IELTS Speaking "+ Add a question")

    /* ---------- question status ---------- */
    const plain = html => { const d = document.createElement('div'); d.innerHTML = html || ''; return d.textContent.trim(); };

    function updateQuestion(q) {
        const statement = $('[data-q-statement]', q).value.trim();
        const ed = $('textarea.ls-editor', q);
        const text = ed ? plain(ed.value) : '';
        const options = $$('[data-option]', q);
        const optionsOk = options.every(o => { const img = $('[data-media-path]', o); return $('[data-option-text]', o).value.trim() || (img && img.value); });
        const correct = $('[data-q-correct]:checked', q);
        const audioEl = $(':scope > .ls-q-body > .ls-media-row [data-media][data-kind="audio"] [data-media-path]', q);
        const audio = q.hasAttribute('data-no-audio') || (audioEl && audioEl.value);   // Part 4 questions have no audio
        const complete = !!((statement || text) && optionsOk && correct && audio);

        options.forEach(o => o.classList.toggle('is-correct', $('[data-q-correct]', o).checked));
        q.classList.toggle('is-complete', complete);
        $('[data-q-status]', q).textContent = complete ? 'Complete' : 'Incomplete';
        const preview = statement || text;
        $('[data-q-preview]', q).textContent = preview ? (preview.length > 70 ? preview.slice(0, 68) + '…' : preview) : 'Not written yet';
        updateProgress();
    }

    function updateProgress() {
        $$('[data-part]').forEach(part => {
            const all = $$('[data-question]', part);
            const done = all.filter(q => q.classList.contains('is-complete')).length;
            const el = $('[data-part-progress]', part);
            if (el) {
                el.textContent = `${done} / ${all.length} questions complete`;
                el.classList.toggle('is-done', done === all.length);
            }
        });
    }

    $$('[data-question]').forEach(q => {
        q.addEventListener('input', () => updateQuestion(q));
        q.addEventListener('change', () => updateQuestion(q));
    });
})();
