/*
 * Etude World admin – IELTS Reading.
 *  • Passages page: three big text editors.
 *  • Question form (one page for every type): add / remove questions with "+", count the # blanks,
 *    build the answers panel, show the question numbers, and send everything as one JSON value.
 */
(function () {
    const $  = (s, el = document) => el.querySelector(s);
    const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    let uid = Date.now();

    /* ---------- unsaved changes ---------- */
    let dirty = false;
    const status = $('[data-save-status]');
    const markDirty = () => {
        if (dirty || !status) return;
        dirty = true;
        status.textContent = 'You have unsaved changes.';
        status.classList.add('is-dirty');
    };
    window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

    /* ---------- text editors ---------- */
    const imageUpload = blobInfo => new Promise((resolve, reject) => {
        const data = new FormData();
        data.append('file', blobInfo.blob(), blobInfo.filename());
        fetch(window.EW_EDITOR_IMAGE_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: data })
            .then(r => r.ok ? r.json() : r.json().then(j => Promise.reject(j.message || 'Upload failed')))
            .then(j => resolve(j.location))
            .catch(err => reject({ message: String(err), remove: true }));
    });

    function editor(el, kind, onChange) {
        if (!window.tinymce) return Promise.resolve(null);
        const big = kind === 'big' || kind === 'table';
        return tinymce.init({
            target: el,
            license_key: 'gpl',
            promotion: false,
            branding: false,
            menubar: big ? 'edit insert format table' : false,
            plugins: big ? 'advlist autolink lists link image charmap table autoresize code' : 'lists link autolink autoresize',
            toolbar: kind === 'table'
                ? 'undo redo | bold italic underline | bullist numlist | table tableinsertrowafter tableinsertcolafter tabledeleterow tabledeletecol | alignleft aligncenter | removeformat code'
                : big ? 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | link image table | removeformat code'
                    : 'bold italic underline | bullist numlist | removeformat',
            toolbar_mode: 'wrap',
            block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
            contextmenu: false,
            min_height: big ? 260 : kind === 'inst' ? 120 : 90,
            autoresize_bottom_margin: 14,
            convert_urls: false,
            entity_encoding: 'raw',
            table_default_attributes: { border: '1' },
            images_upload_handler: big ? imageUpload : undefined,
            content_style: 'body{font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1c2333;margin:10px 14px} p{margin:0 0 .6em} img{max-width:100%;height:auto} table{border-collapse:collapse;width:100%} td,th{border:1px solid #9aa3b5;padding:6px 8px;vertical-align:top}',
            setup: ed => {
                if (window.EtudeEditorBlocks) EtudeEditorBlocks.basics(ed);
                ed.on('input change undo redo keyup SetContent', () => { ed.save(); markDirty(); if (onChange) onChange(ed); });
            },
        }).then(list => list[0]);
    }

    /* ═══════════════ Passages page ═══════════════ */
    const passagesForm = $('#passages-form');
    if (passagesForm) {
        $$('.rda-editor', passagesForm).forEach(el => editor(el, 'big'));
        passagesForm.addEventListener('submit', () => { if (window.tinymce) tinymce.triggerSave(); dirty = false; });
        return;
    }

    /* ═══════════════ Question form ═══════════════ */
    const form = $('#rda-form');
    if (!form) return;
    const type = form.dataset.type;
    const data = JSON.parse($('[data-rda-data]').textContent);
    const g = data.group;
    const BLANKS = ['fitb', 'fitb_flow', 'fitb_para', 'fitb_table'].includes(type);
    const itemsBox = $('[data-items]'), answersBox = $('[data-answers]');
    const L = n => String.fromCharCode(65 + n);
    const MCQ_N = data.mcqOptions || 4;
    const NAMES = type === 'matching_names';   // statements answered with a letter from a list of names
    const SYN = type === 'fitb_syn';      // blanks answered with a letter from a list of words
    const MATCH = type === 'matching' || type === 'matching_items' || type === 'map' || type === 'drag';
    let opts = (g.options || []).map(t => ({ key: 'o' + (uid++), text: t }));

    // # blanks in the TEXT of some HTML (not in tags, not in &#39;-style entities) – same rule as the website.
    const countBlanks = html => {
        let n = 0;
        String(html || '').split(/(<[^>]*>)/).forEach(part => {
            if (!part || part[0] === '<') return;
            (part.match(/&#?\w+;|#/g) || []).forEach(m => { if (m === '#') n++; });
        });
        return n;
    };
    const passage = () => Number(($('[data-passage]:checked') || {}).value || g.passage || 1);
    const start = () => data.starts[passage()] || 1;

    /* ---------- the questions (state) ---------- */
    let items = (g.items || []).map(i => ({ ...i, key: 'i' + (uid++) }));
    let table = { text: g.table || '', answers: g.answers || [] };
    if (SYN) items = [{ key: 'syn', text: g.text || '', answers: (g.answers || []).slice() }];

    const letterChoices = () => {
        if (type === 'matching_items' || type === 'drag') return opts.map((_, k) => L(k));
        if (type === 'matching' || type === 'map') return Array.from({ length: Number(($('[data-letters-count]') || {}).value || 5) }, (_, k) => L(k));
        const found = (data.letters[passage()] || []);
        const warn = $('[data-letters-warning]');
        if (warn) warn.hidden = type !== 'headings' || found.length > 0;
        if (type === 'headings') return found;
        return found.length ? found : Array.from({ length: 10 }, (_, k) => L(k));
    };

    // Headings: "Not used" for extra headings; other types: "–" until an answer is chosen.
    const choiceOptions = (choices, v) => `<option value="">${type === 'headings' ? 'Not used (extra heading)' : '–'}</option>`
        + choices.map(l => `<option ${v === l ? 'selected' : ''}>${l}</option>`).join('');

    function itemHtml(it) {
        const ta = `<textarea id="${it.key}-text" class="rda-editor" rows="3">${esc(it.text || '')}</textarea>`;
        let extra = '';
        if (type === 'fitb_flow') {
            extra = `<label class="rda-check"><input type="checkbox" data-arrow ${it.arrow ? 'checked' : ''}> Add ↓ arrow below this box</label>`;
        } else if (type === 'mcq' || type === 'mcq5') {
            const n = type === 'mcq' ? MCQ_N : 5;
            const opts = it.options || [];
            extra = '<div class="rda-opts">' + Array.from({ length: n }, (_, k) => {
                const l = L(k);
                const on = type === 'mcq' ? it.answer === l : (it.answers || []).includes(l);
                return `<label class="rda-opt ${on ? 'is-correct' : ''}">
                    <input type="${type === 'mcq' ? 'radio' : 'checkbox'}" name="${it.key}-correct" value="${l}" data-correct ${on ? 'checked' : ''} title="Correct answer">
                    <b>${l}</b><input type="text" data-option="${k}" value="${esc(opts[k] || '')}" placeholder="Option ${l}"></label>`;
            }).join('') + `<p class="muted rda-small">${type === 'mcq' ? 'Click the circle next to the correct option.' : 'Tick the TWO correct options.'}</p></div>`;
        } else if (type === 'headings' || MATCH) {
            const choices = letterChoices();
            extra = `<label class="rda-answer-pick">${type === 'headings' ? 'Goes above paragraph' : 'Answer'}
                <select data-answer>${choiceOptions(choices, it.answer)}</select></label>`;
        }
        return `<div class="rda-item" data-item="${it.key}">
            <div class="rda-item__head"><span class="rda-item__num" data-num></span>
                <button type="button" class="media-remove" data-remove>Remove</button></div>
            ${ta}${extra}</div>`;
    }

    function addItem(it, focus) {
        itemsBox.insertAdjacentHTML('beforeend', itemHtml(it));
        const box = itemsBox.lastElementChild;
        editor($('textarea', box), 'small', ed => { it.text = ed.getContent(); refresh(); }).then(ed => { if (focus && ed) ed.focus(); });
    }

    /* ---------- numbers + answers panel ---------- */
    function refresh() {
        let q = start();
        const range = { first: null, last: null };
        const take = n => { if (range.first === null) range.first = q; q += n; range.last = q - 1; return q - n; };

        if (type === 'fitb_table') {
            const n = countBlanks(table.text);
            if (n) take(n);
        } else if (SYN) {
            const n = countBlanks(items[0].text);
            if (n) take(n);
        } else if (type === 'headings') {
            const paras = data.letters[passage()] || [];
            if (paras.length) take(paras.length);
            items.forEach((it, i) => { const el = $(`[data-item="${it.key}"] [data-num]`, itemsBox); if (el) el.textContent = `Heading ${i + 1}`; });
            headingSummary(paras, range.first);
        } else {
            items.forEach(it => {
                const el = $(`[data-item="${it.key}"] [data-num]`, itemsBox);
                if (!el) return;
                if (type === 'fitb_para') {
                    const n = countBlanks(it.text);
                    if (n) { const s = take(n); el.textContent = n > 1 ? `Questions ${s}–${s + n - 1}` : `Question ${s}`; } else el.textContent = 'No # yet';
                    el.classList.toggle('is-muted', !n);
                } else if (BLANKS) {
                    const n = countBlanks(it.text);
                    el.textContent = n ? `Question ${take(1)}` + (n > 1 ? ` (${n} blanks)` : '') : (type === 'fitb_flow' ? 'Box without a question' : 'No # yet');
                    el.classList.toggle('is-muted', !n);
                } else if (type === 'mcq5') {
                    const s = take(2); el.textContent = `Questions ${s}–${s + 1}`;
                } else {
                    el.textContent = `${type === 'headings' ? 'Heading' : 'Question'} ${take(1)}`;
                }
            });
        }
        $$('[data-range]').forEach(r => { r.textContent = range.first === null ? '…' : range.first === range.last ? range.first : `${range.first}–${range.last}`; });
        drawAnswers();
    }

    // Headings: which question (box) each paragraph is, and any paragraph without – or with two – headings.
    function headingSummary(paras, first) {
        const box = $('[data-heading-summary]');
        if (!box) return;
        box.hidden = !paras.length;
        if (!paras.length) return;
        const used = items.map(it => it.answer).filter(Boolean);
        const extra = items.length - used.length;
        const rows = paras.map((l, i) => {
            const n = used.filter(a => a === l).length;
            const state = n === 1 ? 'is-ok' : 'is-bad';
            return `<li class="${state}"><b>Question ${first + i}</b> – box above paragraph ${l}: ${n === 1 ? '✓ heading chosen' : n === 0 ? 'no heading chosen yet' : n + ' headings chosen – choose only one'}</li>`;
        }).join('');
        box.innerHTML = `<p><strong>${paras.length} paragraphs → ${paras.length} questions.</strong> ${items.length} heading${items.length === 1 ? '' : 's'}`
            + (extra > 0 ? `, ${extra} extra (not used).` : '.') + `</p><ul>${rows}</ul>`;
    }

    let answersKey = '';
    function drawAnswers() {
        if (!answersBox) return;
        let q = start();
        let rows = [];
        if (type === 'fitb_table') {
            const n = countBlanks(table.text);
            for (let b = 0; b < n; b++) rows.push({ label: `Question ${q++}`, it: table, b });
        } else if (NAMES) {
            items.forEach(it => rows.push({ label: `Question ${q++}`, it, nm: true }));
        } else if (SYN) {
            const n = countBlanks(items[0].text);
            for (let b = 0; b < n; b++) rows.push({ label: `Question ${q++}`, it: items[0], b, syn: true });
        } else if (type === 'tfng') {
            items.forEach(it => rows.push({ label: `Question ${q++}`, it, tf: true }));
        } else {
            items.forEach(it => {
                const n = countBlanks(it.text);
                if (!n) return;
                const num = q;
                if (type === 'fitb_para') { for (let b = 0; b < n; b++) rows.push({ label: `Question ${q++}`, it, b }); return; }
                q++;
                for (let b = 0; b < n; b++) rows.push({ label: n > 1 ? `Question ${num} – blank ${b + 1}` : `Question ${num}`, it, b });
            });
        }
        // Only rebuild when the shape changed, so typing in an answer box isn't interrupted.
        const set = ($('[data-set]') || {}).value || 'tf';
        const shape = rows.map(r => r.label + (r.it.key || 't') + (r.b ?? '')).join('|') + set + (SYN || NAMES ? '#' + opts.map(o => o.text).join('~') : '');
        if (shape === answersKey) return;
        answersKey = shape;
        if (!rows.length) {
            answersBox.innerHTML = `<p class="muted">${type === 'tfng' ? 'Add a statement above.' : 'Put # in the text above to make blanks – an answer box appears here for each one.'}</p>`;
            return;
        }
        const choices = set === 'yn' ? ['YES', 'NO', 'NOT GIVEN'] : ['TRUE', 'FALSE', 'NOT GIVEN'];
        const plain = h => String(h || '').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
        answersBox.innerHTML = rows.map((r, i) => r.nm
            ? `<label class="rda-ans-row"><span>${r.label}</span><select data-nm="${i}"><option value="">– choose the correct letter –</option>${opts.map((o, k) =>
                `<option value="${L(k)}" ${r.it.answer === L(k) ? 'selected' : ''}>${L(k)} – ${esc(plain(o.text)) || '(empty)'}</option>`).join('')}</select></label>`
            : r.syn
            ? `<label class="rda-ans-row"><span>${r.label}</span><select data-syn="${i}"><option value="">– choose the correct word –</option>${opts.map((o, k) =>
                `<option value="${L(k)}" ${(r.it.answers || [])[r.b] === L(k) ? 'selected' : ''}>${L(k)} – ${esc(plain(o.text)) || '(empty)'}</option>`).join('')}</select></label>`
            : r.tf
            ? `<div class="rda-ans-row"><span>${r.label}</span><div class="rda-tf">${choices.map(c =>
                `<label class="rda-tf__opt ${r.it.answer === c ? 'is-on' : ''}"><input type="radio" name="tf-${r.it.key}" value="${c}" data-tf="${i}" ${r.it.answer === c ? 'checked' : ''}> ${c}</label>`).join('')}</div></div>`
            : `<label class="rda-ans-row"><span>${r.label}</span><input type="text" data-blank="${i}" value="${esc((r.it.answers || [])[r.b] || '')}" placeholder="Correct answer"></label>`
        ).join('');
        answersBox._rows = rows;
    }

    if (answersBox) {
        answersBox.addEventListener('input', e => {
            const i = e.target.dataset.blank;
            if (i === undefined) return;
            const r = answersBox._rows[Number(i)];
            r.it.answers = r.it.answers || [];
            r.it.answers[r.b] = e.target.value;
            markDirty();
        });
        answersBox.addEventListener('change', e => {
            if (e.target.dataset.nm !== undefined) {
                answersBox._rows[Number(e.target.dataset.nm)].it.answer = e.target.value;
                markDirty();
                return;
            }
            if (e.target.dataset.syn !== undefined) {
                const r = answersBox._rows[Number(e.target.dataset.syn)];
                r.it.answers = r.it.answers || [];
                r.it.answers[r.b] = e.target.value;
                markDirty();
                return;
            }
            const i = e.target.dataset.tf;
            if (i === undefined) return;
            const r = answersBox._rows[Number(i)];
            r.it.answer = e.target.value;
            $$(`input[name="tf-${r.it.key}"]`, answersBox).forEach(x => x.closest('label').classList.toggle('is-on', x.checked));
            markDirty();
        });
    }

    /* ---------- events ---------- */
    if (itemsBox) {
        items.forEach(it => addItem(it));
        if (!items.length) { const it = { key: 'i' + (uid++), text: '' }; items.push(it); addItem(it); }

        $('[data-add]').addEventListener('click', () => {
            const it = { key: 'i' + (uid++), text: '' };
            items.push(it);
            addItem(it, true);
            refresh();
            markDirty();
        });
        itemsBox.addEventListener('click', e => {
            const rm = e.target.closest('[data-remove]');
            if (!rm) return;
            const box = rm.closest('[data-item]');
            const it = items.find(x => x.key === box.dataset.item);
            if ((it.text || '').replace(/<[^>]*>/g, '').trim() && !confirm('Remove this ' + (type === 'headings' ? 'heading' : 'question') + '?')) return;
            const ed = window.tinymce && tinymce.get(`${it.key}-text`);
            if (ed) ed.remove();
            box.remove();
            items = items.filter(x => x !== it);
            refresh();
            markDirty();
        });
        itemsBox.addEventListener('input', e => {
            const box = e.target.closest('[data-item]');
            if (!box) return;
            const it = items.find(x => x.key === box.dataset.item);
            if (e.target.dataset.option !== undefined) { it.options = it.options || []; it.options[Number(e.target.dataset.option)] = e.target.value; }
            markDirty();
        });
        itemsBox.addEventListener('change', e => {
            const box = e.target.closest('[data-item]');
            if (!box) return;
            const it = items.find(x => x.key === box.dataset.item);
            if (e.target.matches('[data-arrow]')) it.arrow = e.target.checked;
            if (e.target.matches('[data-answer]')) { it.answer = e.target.value; if (type === 'headings') refresh(); }
            if (e.target.matches('[data-correct]')) {
                if (type === 'mcq') it.answer = e.target.value;
                else {
                    const on = $$('[data-correct]:checked', box);
                    if (on.length > 2) { e.target.checked = false; alert('Choose only TWO correct options.'); return; }
                    it.answers = on.map(x => x.value);
                }
                $$('[data-correct]', box).forEach(x => x.closest('.rda-opt').classList.toggle('is-correct', x.checked));
            }
            markDirty();
        });
    }

    if (type === 'fitb_table') {
        const ta = $('#rda-table');
        editor(ta, 'table', ed => { table.text = ed.getContent(); refresh(); });
        $('[data-generate]').addEventListener('click', () => {
            const ed = window.tinymce && tinymce.get('rda-table');
            if (!ed) return;
            const rows = Math.max(1, Math.min(20, Number($('[data-rows]').value) || 1));
            const cols = Math.max(1, Math.min(8, Number($('[data-cols]').value) || 1));
            if (ed.getContent().trim() && !confirm('Replace the current table with a new empty one?')) return;
            const head = '<tr>' + Array.from({ length: cols }, () => '<th>&nbsp;</th>').join('') + '</tr>';
            const row = '<tr>' + Array.from({ length: cols }, () => '<td>&nbsp;</td>').join('') + '</tr>';
            ed.setContent('<table border="1" style="width:100%;border-collapse:collapse"><thead>' + head + '</thead><tbody>' + row.repeat(Math.max(0, rows - 1)) + '</tbody></table><p></p>');
            table.text = ed.getContent();
            refresh();
            markDirty();
        });
    }

    editor($('#rda-instructions'), 'inst');
    if (SYN) editor($('#rda-syn-text'), 'inst', ed => { items[0].text = ed.getContent(); refresh(); });
    $$('[data-passage]').forEach(r => r.addEventListener('change', () => {
        markDirty();
        if (type === 'headings') {        // paragraph letters depend on the passage
            const choices = letterChoices();
            $$('[data-answer]', itemsBox).forEach(sel => {
                const v = sel.value;
                sel.innerHTML = choiceOptions(choices, v);
                if (!choices.includes(v)) { const it = items.find(x => x.key === sel.closest('[data-item]').dataset.item); it.answer = ''; }
            });
        }
        answersKey = '';
        refresh();
    }));
    const letters = $('[data-letters-count]');
    function resetChoices() {
        const choices = letterChoices();
        $$('[data-answer]', itemsBox).forEach(sel => {
            const v = sel.value;
            sel.innerHTML = choiceOptions(choices, v);
            if (!choices.includes(v)) { const it = items.find(x => x.key === sel.closest('[data-item]').dataset.item); it.answer = ''; }
        });
        markDirty();
    }
    if (letters) letters.addEventListener('change', resetChoices);

    /* ---------- matching items: the columns of the table ---------- */
    const optsBox = $('[data-options]');
    if (optsBox) {
        const label = () => $$('[data-onum]', optsBox).forEach((el, i) => { el.textContent = (optsBox.dataset.word || 'Item') + ' ' + L(i); });
        const addOpt = (o, focus) => {
            optsBox.insertAdjacentHTML('beforeend', `<div class="rda-item" data-opt="${o.key}"><div class="rda-item__head"><span class="rda-item__num" data-onum></span>
                <button type="button" class="media-remove" data-orem>Remove</button></div><textarea id="${o.key}-text" class="rda-editor" rows="2">${esc(o.text || '')}</textarea></div>`);
            editor($(`#${o.key}-text`), 'small', ed => { o.text = ed.getContent(); if (SYN || NAMES) refresh(); }).then(ed => { if (focus && ed) ed.focus(); });
            label();
        };
        if (!opts.length) opts.push({ key: 'o' + (uid++), text: '' }, { key: 'o' + (uid++), text: '' });
        opts.forEach(o => addOpt(o));
        $('[data-add-option]').addEventListener('click', () => { const o = { key: 'o' + (uid++), text: '' }; opts.push(o); addOpt(o, true); if (SYN || NAMES) { answersKey = ''; refresh(); markDirty(); } else resetChoices(); });
        optsBox.addEventListener('click', e => {
            const rm = e.target.closest('[data-orem]');
            if (!rm) return;
            const box = rm.closest('[data-opt]'), o = opts.find(x => x.key === box.dataset.opt);
            if ((o.text || '').replace(/<[^>]*>/g, '').trim() && !confirm('Remove this option? Answers that use later items will shift.')) return;
            const ed = window.tinymce && tinymce.get(`${o.key}-text`);
            if (ed) ed.remove();
            // keep answers pointing at the same items after the letters shift
            const gone = opts.indexOf(o);
            items.forEach(it => { if (it.answer) { const i = it.answer.charCodeAt(0) - 65; it.answer = i === gone ? '' : i > gone ? L(i - 1) : it.answer; } });
            if (SYN) items[0].answers = (items[0].answers || []).map(a => { if (!a) return ''; const i = a.charCodeAt(0) - 65; return i === gone ? '' : i > gone ? L(i - 1) : a; });
            box.remove();
            opts = opts.filter(x => x !== o);
            label();
            if (SYN || NAMES) { answersKey = ''; refresh(); }
            $$('[data-answer]', itemsBox).forEach(sel => { const it = items.find(x => x.key === sel.closest('[data-item]').dataset.item); sel.innerHTML = choiceOptions(letterChoices(), it.answer); });
            markDirty();
        });
        editor($('#rda-corner'), 'small');
    }
    /* ---------- map labelling: the picture ---------- */
    let mapImage = g.image || '';
    const mapBox = $('[data-map-box]');
    if (mapBox) {
        const img = $('[data-map-img]', mapBox), rm = $('[data-map-remove]', mapBox), st = $('[data-map-status]', mapBox);
        $('[data-map-file]', mapBox).addEventListener('change', e => {
            const f = e.target.files[0];
            if (!f) return;
            const fd = new FormData(); fd.append('image', f);
            st.textContent = 'Uploading…';
            fetch(data.imageUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd })
                .then(r => r.ok ? r.json() : r.json().then(j => Promise.reject((j.errors && j.errors.image && j.errors.image[0]) || j.message || 'Upload failed')))
                .then(j => { mapImage = j.path; img.src = j.url; img.hidden = false; rm.hidden = false; st.textContent = ''; markDirty(); })
                .catch(err => { st.textContent = String(err); });
            e.target.value = '';
        });
        rm.addEventListener('click', () => { mapImage = ''; img.hidden = true; rm.hidden = true; markDirty(); });
    }
    const set = $('[data-set]');
    if (set) set.addEventListener('change', () => { items.forEach(it => { it.answer = ''; }); answersKey = ''; refresh(); markDirty(); });
    letterChoices();
    refresh();

    /* ---------- save ---------- */
    form.addEventListener('submit', e => {
        if (window.tinymce) tinymce.triggerSave();
        const out = {
            id: g.id, type, passage: passage(),
            instructions: (tinymce.get('rda-instructions') || { getContent: () => $('#rda-instructions').value }).getContent(),
        };
        if (SYN) {
            const txt = key => { const ed = window.tinymce && tinymce.get(key); return ed ? ed.getContent() : ''; };
            const text = txt('rda-syn-text');
            const all = opts.map(o => txt(`${o.key}-text`));
            const keep = all.map((t, i) => t.replace(/<[^>]*>|&nbsp;/g, '').trim() ? i : -1).filter(i => i >= 0);
            out.text = text;
            out.options = keep.map(i => all[i]);
            const n = countBlanks(text);
            out.answers = Array.from({ length: n }, (_, b) => { const a = (items[0].answers || [])[b]; if (!a) return ''; const i = keep.indexOf(a.charCodeAt(0) - 65); return i < 0 ? '' : L(i); });
            const problems = [];
            if (!n) problems.push('There is no # in the text yet, so there are no questions.');
            if (out.options.length < 2) problems.push('Add at least two options.');
            const missing = out.answers.filter(a => !a).length;
            if (n && missing) problems.push(`${missing} question${missing === 1 ? ' has' : 's have'} no answer chosen.`);
            if (problems.length && !confirm(problems.join('\n') + '\n\nSave anyway?')) { e.preventDefault(); return; }
            $('[data-group-json]').value = JSON.stringify(out);
            dirty = false;
            return;
        }
        const textOf = it => { const ed = window.tinymce && tinymce.get(`${it.key}-text`); return ed ? ed.getContent() : (it.text || ''); };
        if (type === 'fitb_table') {
            const ed = window.tinymce && tinymce.get('rda-table');
            out.table = ed ? ed.getContent() : table.text;
            out.answers = table.answers.slice(0, countBlanks(out.table));
        } else {
            out.items = items.map(it => {
                const text = textOf(it);
                const row = { text };
                if (BLANKS) { row.answers = (it.answers || []).slice(0, countBlanks(text)); if (type === 'fitb_flow') row.arrow = !!it.arrow; }
                if (['tfng', 'mcq', 'headings', 'matching', 'matching_items', 'matching_names', 'map', 'drag'].includes(type)) row.answer = it.answer || '';
                if (type === 'mcq' || type === 'mcq5') row.options = Array.from({ length: type === 'mcq' ? MCQ_N : 5 }, (_, k) => (it.options || [])[k] || '');
                if (type === 'mcq5') row.answers = it.answers || [];
                return row;
            }).filter(row => row.text.replace(/<[^>]*>|&nbsp;/g, '').trim() !== '');
        }
        if (type === 'tfng') out.set = set.value;
        if (type === 'matching' || type === 'map') out.letters = Number(letters.value);
        if (type === 'map') out.image = mapImage;
        if (type === 'matching_items' || type === 'drag' || NAMES) {
            const txt = key => { const ed = window.tinymce && tinymce.get(key); return ed ? ed.getContent() : ''; };
            const all = opts.map(o => txt(`${o.key}-text`));
            const keep = all.map((t, i) => t.replace(/<[^>]*>|&nbsp;/g, '').trim() ? i : -1).filter(i => i >= 0);
            out.options = keep.map(i => all[i]);
            out.corner = txt('rda-corner');
            if (NAMES) out.ohead = ($('[data-ohead]') || {}).value || '';
            if (type === 'drag') { out.qhead = ($('[data-qhead]') || {}).value || ''; out.ohead = ($('[data-ohead]') || {}).value || ''; }
            out.items.forEach(r => { if (r.answer) { const i = keep.indexOf(r.answer.charCodeAt(0) - 65); r.answer = i < 0 ? '' : L(i); } });
        }

        // Gentle checks before saving.
        const problems = [];
        if (type !== 'fitb_table' && !out.items.length) problems.push('There are no questions yet.');
        if (type === 'map' && !mapImage) problems.push('No image added yet.');
        if ((type === 'matching_items' || type === 'drag' || NAMES) && out.options.length < 2) problems.push('Add at least two options.');
        if (BLANKS) {
            const list = type === 'fitb_table' ? [{ text: out.table, answers: out.answers }] : out.items;
            const missing = list.reduce((n, r) => n + Math.max(0, countBlanks(r.text) - (r.answers || []).filter(a => String(a || '').trim()).length), 0);
            if (missing) problems.push(`${missing} blank${missing === 1 ? ' has' : 's have'} no answer.`);
        } else if (type === 'mcq5') {
            if (out.items.some(r => (r.answers || []).length !== 2)) problems.push('Every question needs exactly TWO correct options.');
        } else if (type === 'headings') {
            const paras = data.letters[passage()] || [];
            if (!paras.length) problems.push('This passage has no lettered paragraphs ([A], [B]…), so there are no questions yet.');
            const bad = paras.filter(l => out.items.filter(r => r.answer === l).length !== 1);
            if (bad.length) problems.push('Paragraph' + (bad.length > 1 ? 's ' : ' ') + bad.join(', ') + ' should have exactly one heading.');
        } else if (type !== 'fitb_table' && out.items.some(r => !r.answer)) {
            problems.push('Some questions have no correct answer chosen.');
        }
        if (problems.length && !confirm(problems.join('\n') + '\n\nSave anyway?')) { e.preventDefault(); return; }
        $('[data-group-json]').value = JSON.stringify(out);
        dirty = false;
    });
})();
