/*
 * Etude World admin – TOEFL section pages (Reading, Listening, Writing, Speaking).
 * Every question block is a "card". Cards are kept in one ordered list; the "Question sequence management" tab
 * changes that order and adds instruction texts. Everything is collected into one JSON value when Save is clicked.
 */
(function () {
    const form = document.getElementById('tf-form');
    if (!form) return;
    const $ = (s, el = document) => el.querySelector(s);
    const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
    const D = JSON.parse(document.getElementById('tf-data').textContent);
    const section = D.section, csrf = $('meta[name="csrf-token"]').content;
    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const L = 'ABCDEFGH';
    let uid = 0;
    const cards = new Map();            // id -> { el, type, read(), summary() }
    let order = [];                     // card ids in test order
    let instructions = (D.content.instructions || []).map(b => ({ ...b }));

    /* ───────── rich text editors ───────── */
    function startEditors(root) {
        if (!window.tinymce) return;
        $$('textarea.tf-editor', root).forEach(el => {
            if (el.dataset.started || el.closest('[hidden]')) return;
            el.dataset.started = '1';
            const full = el.dataset.full === '1';
            tinymce.init({
                target: el, license_key: 'gpl', promotion: false, branding: false, menubar: false,
                plugins: full ? 'lists link autolink autoresize image table code' : 'lists link autolink autoresize code',
                toolbar: full ? 'blocks | bold italic underline | forecolor | bullist numlist | image table | link | removeformat code' : 'bold italic underline | bullist numlist | link | removeformat',
                block_formats: 'Paragraph=p; Heading 3=h3; Heading 4=h4',
                min_height: full ? 240 : 110, autoresize_bottom_margin: 12, convert_urls: false, image_caption: false,
                images_upload_handler: blob => upload(blob.blob(), 'image').then(r => '/' + r.path),
                automatic_uploads: true, file_picker_types: 'image',
                content_style: 'body{font-family:"Plus Jakarta Sans",system-ui,sans-serif;font-size:15px;line-height:1.6;margin:10px 14px} img{max-width:100%;height:auto}',
                setup: ed => ed.on('input change undo redo keyup', () => { ed.save(); el.dispatchEvent(new Event('input', { bubbles: true })); }),
            });
        });
    }
    const edVal = el => (window.tinymce && tinymce.get(el.id)) ? tinymce.get(el.id).getContent() : el.value;
    const edText = el => { const ed = window.tinymce && tinymce.get(el.id); if (ed) return ed.getContent({ format: 'text' }); const d = document.createElement('div'); d.innerHTML = el.value; return d.textContent; };
    const editor = (label, value, full, help, key) => `<label class="tf-lab">${label}</label>${help ? `<p class="tf-help">${help}</p>` : ''}<textarea class="tf-editor" id="ed-${++uid}" data-full="${full ? 1 : 0}" data-k="${key}">${esc(value)}</textarea>`;
    const text = (label, value, key, ph, help) => `<label class="tf-lab">${label}</label>${help ? `<p class="tf-help">${help}</p>` : ''}<input type="text" data-k="${key}" value="${esc(value)}" placeholder="${esc(ph || '')}">`;
    const val = (el, k) => { const f = $(`[data-k="${k}"]`, el); return !f ? '' : f.classList.contains('tf-editor') ? edVal(f) : f.value; };

    /* ───────── uploads ───────── */
    function upload(file, kind, onProgress) {
        return new Promise((resolve, reject) => {
            const fd = new FormData(); fd.append('file', file, file.name || 'image.png'); fd.append('kind', kind); fd.append('folder', section);
            const xhr = new XMLHttpRequest(); xhr.open('POST', form.dataset.uploadUrl);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf); xhr.setRequestHeader('Accept', 'application/json');
            if (onProgress) xhr.upload.onprogress = e => e.lengthComputable && onProgress(Math.round(e.loaded / e.total * 100));
            xhr.onload = () => { let r = {}; try { r = JSON.parse(xhr.responseText); } catch (_) { } if (xhr.status < 300 && r.path) resolve(r); else reject({ message: (r.errors && r.errors.file && r.errors.file[0]) || 'Upload failed' }); };
            xhr.onerror = () => reject({ message: 'Upload failed. Check your connection.' });
            xhr.send(fd);
        });
    }
    const preview = (kind, path) => !path ? '<small>No file yet</small>' : kind === 'audio' ? `<audio controls preload="none" src="/${esc(path)}"></audio>` : kind === 'video' ? `<video controls preload="none" src="/${esc(path)}"></video>` : `<img src="/${esc(path)}" alt="">`;
    const media = (kind, label, path, key) => `<div class="tf-media" data-media data-kind="${kind}"><strong>${label}</strong>
        <input type="hidden" data-k="${key}" value="${esc(path)}"><span class="tf-media__preview" data-prev>${preview(kind, path)}</span>
        <label class="btn btn-sm btn-ghost"><input type="file" hidden accept="${kind === 'audio' ? 'audio/*' : kind === 'video' ? 'video/*' : 'image/*'}" data-file><span data-pick>${path ? 'Replace' : 'Upload'}</span></label>
        <button type="button" class="tf-danger" data-clear ${path ? '' : 'hidden'}>Remove</button><small data-status></small></div>`;
    form.addEventListener('change', e => {
        const f = e.target.closest('[data-file]'); if (!f || !f.files[0]) return;
        const box = f.closest('[data-media]'), st = $('[data-status]', box);
        upload(f.files[0], box.dataset.kind, p => st.textContent = p + '%').then(r => {
            $('input[type=hidden]', box).value = r.path; $('[data-prev]', box).innerHTML = preview(box.dataset.kind, r.path);
            $('[data-pick]', box).textContent = 'Replace'; $('[data-clear]', box).hidden = false; st.textContent = 'Uploaded';
        }).catch(err => st.textContent = err.message);
        f.value = '';
    });
    form.addEventListener('click', e => {
        const c = e.target.closest('[data-clear]'); if (!c) return;
        const box = c.closest('[data-media]'); $('input[type=hidden]', box).value = ''; $('[data-prev]', box).innerHTML = preview(box.dataset.kind, '');
        $('[data-pick]', box).textContent = 'Upload'; c.hidden = true;
    });
    const mediaVal = (el, key) => { const f = $(`[data-media] [data-k="${key}"]`, el); return f ? f.value : ''; };

    /* ───────── multiple-choice questions (shared) ───────── */
    function optRow(g, t, k, ok) {
        return `<div class="tf-opt ${ok ? 'is-ok' : ''}" data-opt><label class="tf-opt__ok" title="Tick the correct answer"><input type="radio" name="ok-${g}" ${ok ? 'checked' : ''}><span>${L[k]}</span></label>
            <input type="text" value="${esc(t)}" placeholder="Option ${L[k]}" data-opt-text><button type="button" class="tf-x" data-opt-remove title="Remove option">×</button></div>`;
    }
    function questionEl(q, o) {
        const g = ++uid, opts = q.options && q.options.length ? q.options : ['', '', '', ''];
        const div = document.createElement('div'); div.className = 'tf-q'; div.dataset.q = '';
        div.innerHTML = `<div class="tf-q__head"><strong data-qn>Question</strong><span class="tf-grow" style="flex:1"></span>
            <label><input type="checkbox" data-graded ${q.graded === false ? '' : 'checked'}> Graded</label>${o.seconds ? `<label>Seconds <input type="number" min="0" max="600" data-seconds value="${q.seconds || o.seconds}"></label>` : ''}
            <button type="button" class="tf-danger" data-q-remove>Remove</button></div>
            ${o.media ? media('audio', 'Recording', q.audio || '', 'audio') + `<div class="tf-row"><label>Picture <select data-imode><option value="upload">My own picture</option><option value="male">Random photo – man</option><option value="female">Random photo – woman</option></select></label></div>${media('image', 'Picture', q.image || '', 'image')}` : ''}
            <input type="text" data-statement value="${esc(q.statement || o.statement || '')}" placeholder="Question statement">
            <div data-opts>${opts.map((t, k) => optRow(g, t, k, q.correct === k)).join('')}</div>
            <button type="button" class="tf-link" data-opt-add>+ Add option</button>
            ${o.highlight ? `<label class="tf-lab">Word to highlight in the passage (optional)</label><input type="text" data-highlight value="${esc(q.highlight || '')}" placeholder="e.g. milestone">` : ''}`;
        div.dataset.g = g;
        if (o.media) { $('[data-imode]', div).value = q.image_mode || 'upload'; syncImode(div); }
        return div;
    }
    function syncImode(div) { const m = $('[data-imode]', div); if (m) { const box = $('[data-media][data-kind=image]', div); if (box) box.hidden = m.value !== 'upload'; } }
    function readQ(div, o) {
        const opts = []; let correct = -1;
        $$('[data-opt]', div).forEach(r => { const t = $('[data-opt-text]', r).value.trim(); if (!t) return; if ($('input[type=radio]', r).checked) correct = opts.length; opts.push(t); });
        const q = { statement: $('[data-statement]', div).value.trim(), options: opts, correct, graded: $('[data-graded]', div).checked };
        if (o.seconds) q.seconds = +($('[data-seconds]', div).value || 0);
        if (o.highlight) q.highlight = $('[data-highlight]', div).value.trim();
        if (o.media) { q.audio = mediaVal(div, 'audio'); q.image = mediaVal(div, 'image'); q.image_mode = $('[data-imode]', div).value; }
        return q;
    }
    function relabel(box) {
        $$('[data-opt]', box).forEach((r, k) => { $('.tf-opt__ok span', r).textContent = L[k]; $('[data-opt-text]', r).placeholder = 'Option ' + L[k]; });
        $$('[data-qn]', box).forEach((n, i) => n.textContent = 'Question ' + (i + 1));
    }
    form.addEventListener('click', e => {
        const q = e.target.closest('[data-q]');
        if (e.target.closest('[data-opt-add]') && q) {
            const opts = $('[data-opts]', q); if ($$('[data-opt]', opts).length >= 8) return;
            opts.insertAdjacentHTML('beforeend', optRow(q.dataset.g, '', $$('[data-opt]', opts).length, false)); relabel(q);
        }
        if (e.target.closest('[data-opt-remove]') && q) { const r = e.target.closest('[data-opt]'); if ($$('[data-opt]', q).length > 2) { r.remove(); relabel(q); } }
        if (e.target.closest('[data-q-remove]') && q) { const card = q.closest('.tf-card'); q.remove(); relabel(card); card.dispatchEvent(new Event('input', { bubbles: true })); }
    });
    form.addEventListener('change', e => {
        if (e.target.matches('[data-imode]')) syncImode(e.target.closest('[data-q]'));
        const r = e.target.closest('.tf-opt'); if (r && e.target.type === 'radio') $$('.tf-opt', r.parentElement).forEach(x => x.classList.toggle('is-ok', $('input[type=radio]', x).checked));
    });
    const questionsBlock = (list, o, addLabel) => `<div data-qs></div><button type="button" class="tf-link" data-q-add>${addLabel || '+ Add a question'}</button>`;
    function fillQuestions(card, list, o) {
        const wrap = $('[data-qs]', card); (list && list.length ? list : [{}]).forEach(q => wrap.appendChild(questionEl(q, o))); relabel(wrap);
        $('[data-q-add]', card).addEventListener('click', () => { const el = questionEl({}, o); wrap.appendChild(el); relabel(wrap); startEditors(el); card.dispatchEvent(new Event('input', { bubbles: true })); });
    }
    const readQs = (card, o) => $$('[data-q]', card).map(q => readQ(q, o));

    /* ───────── card builders ───────── */
    const moduleSel = it => D.modules ? `<label>Module <select data-k="module"><option value="1">1</option><option value="2">2</option></select></label>` : '';
    const gradedBox = it => `<label><input type="checkbox" data-k="graded" ${it.graded === false ? '' : 'checked'}> Graded (counts for the score)</label>`;

    const B = {};
    B.words = it => ({
        html: `${text('Heading shown to the student', it.title || 'Fill in the missing letters in the paragraph.', 'title')}
            ${editor('Paragraph', it.paragraph || '', false, 'Write # for each missing letter. Example: <b>ed####</b> is the word “editor” with 4 letters missing. Every incomplete word is one question (1 mark).', 'paragraph')}
            <label class="tf-lab">Answers</label><div data-blanks></div>`,
        grade: true,
        init(card) {
            const answers = (it.answers || []).slice();
            const draw = () => {
                const t = edText($('[data-k=paragraph]', card)), re = /([\p{L}'’-]*)(#+)([\p{L}'’-]*)/gu, rows = [];
                $$('[data-ans]', card).forEach((i, k) => answers[k] = i.value);
                let m, k = 0; while ((m = re.exec(t))) { rows.push(`<div class="tf-blank"><b>${++k}.</b> <code>${esc(m[1])}${'_'.repeat(m[2].length)}${esc(m[3])}</code><input type="text" data-ans value="${esc(answers[k - 1] || '')}" placeholder="${m[2].length} letters"><small class="muted"></small></div>`); }
                $('[data-blanks]', card).innerHTML = rows.join('') || '<p class="tf-warn">No # found yet. Type # where letters are missing.</p>';
                card.dataset.n = k; card.dispatchEvent(new Event('qcount', { bubbles: true }));
            };
            const check = i => { const n = $$('[data-ans]', card).indexOf(i), need = ((edText($('[data-k=paragraph]', card)).match(/#+/g) || [])[n]) || ''; $('small', i.parentElement).textContent = i.value && need && i.value.trim().length !== need.length ? `⚠ ${need.length} letters expected` : ''; };
            card.addEventListener('focusout', e => { if (e.target.matches && e.target.matches('[data-ans]')) check(e.target); });
            card.addEventListener('input', e => { if (e.target.matches && e.target.matches('[data-ans]')) { $('small', e.target.parentElement).textContent = ''; return; } if (e.target.closest('[data-blanks]') || e.target.dataset.k === 'paragraph' || e.target.classList.contains('tf-editor')) draw(); });
            card._draw = draw; setTimeout(draw, 0);
        },
        read: c => ({ title: val(c, 'title'), paragraph: val(c, 'paragraph'), graded: $('[data-k=graded]', c).checked, answers: $$('[data-ans]', c).map(i => i.value.trim()) }),
        count: c => +c.dataset.n || 0,
    });
    B.mc = it => ({
        html: `<div class="tf-row"><label>Type <select data-k="kind"><option value="daily">Read in Daily Life</option><option value="academic">Read an Academic Passage</option></select></label>
            <label>Passage looks like <select data-k="style"><option value="plain">Simple text (academic passage)</option><option value="notice">Notice (box with title)</option><option value="post">Social media post</option><option value="email">Email</option><option value="picture">Picture only</option></select></label>
            <label data-for="email">Email colour <select data-k="color"><option value="teal">Teal</option><option value="brown">Brown</option></select></label></div>
            ${text('Passage title (optional)', it.title || '', 'title')}
            <div data-for="notice">${text('Notice sub-heading (bold line under the title)', it.subtitle || '', 'subtitle', 'e.g. Sign up for paperless billing statements today.')}</div>
            <div data-for="post">${text('Name of the person who posted', it.author || '', 'author', 'e.g. Sofia Baker')}</div>
            <div data-for="email"><div class="tf-row tf-cols"><div>${text('To', it.to || '', 'to', 'edward56L@dmail.com')}</div><div>${text('From', it.from || '', 'from', 'artforeveryone@dmail.com')}</div><div>${text('Date (optional)', it.date || '', 'date', '10/09/2025')}</div></div>${text('Subject', it.subject || '', 'subject', 'Art Workshop Reservation Confirmation')}</div>
            <div data-for="plain notice post email">${editor('Passage text', it.passage || '', true, 'Shown on the left of the questions. For a notice, post or email this is the main text; the box, phone or email frame is drawn for you.', 'passage')}</div>
            <div data-for="picture">${media('image', 'Passage picture', it.image || '', 'image')}</div>
            <label class="tf-lab">Questions about this passage</label>${questionsBlock()}`,
        init(card) {
            $('[data-k=kind]', card).value = it.kind || 'daily';
            const st = $('[data-k=style]', card); st.value = it.style || (it.kind === 'academic' ? 'plain' : (it.image && !it.passage ? 'picture' : 'plain'));
            $('[data-k=color]', card).value = it.color || 'teal';
            const kind = $('[data-k=kind]', card);
            const sync = () => $$('[data-for]', card).forEach(e => { e.hidden = !e.dataset.for.split(' ').includes(st.value); });
            st.addEventListener('change', () => { sync(); startEditors(card); }); sync();
            kind.addEventListener('change', () => { if (kind.value === 'academic') { st.value = 'plain'; sync(); } });
            fillQuestions(card, it.questions, { highlight: true });
        },
        read: c => ({ kind: val(c, 'kind'), style: val(c, 'style'), color: val(c, 'color'), title: val(c, 'title'), subtitle: val(c, 'subtitle'), author: val(c, 'author'), to: val(c, 'to'), from: val(c, 'from'), date: val(c, 'date'), subject: val(c, 'subject'), passage: val(c, 'passage'), image: mediaVal(c, 'image'), questions: readQs(c, { highlight: true }) }),
        count: c => $$('[data-q]', c).length,
    });
    B.response = it => ({
        html: `<p class="tf-help">Each question has its own recording and picture. The question statement is added for you.</p>${questionsBlock(null, null, '+ Add a question')}`,
        init(card) { fillQuestions(card, it.questions, { media: true, statement: D.defaults.response }); },
        read: c => ({ questions: readQs(c, { media: true }) }),
        count: c => $$('[data-q]', c).length,
    });
    ['conversation', 'announcement', 'talk'].forEach(t => B[t] = it => ({
        html: `${text('Heading shown to the student', it.title || { conversation: 'Listen to a conversation.', announcement: 'Listen to an announcement.', talk: 'Listen to a talk.' }[t], 'title')}
            ${media('audio', 'Recording', it.audio || '', 'audio')}
            <div class="tf-row"><label>Picture <select data-k="image_mode"><option value="upload">My own picture</option>${t === 'conversation' ? '<option value="pair">Random photo – man and woman</option>' : ''}<option value="male">Random photo – man</option><option value="female">Random photo – woman</option></select></label></div>
            ${media('image', 'Picture', it.image || '', 'image')}
            <p class="tf-help">The picture and recording play first. The questions appear when the recording ends.</p>
            <label class="tf-lab">Questions</label>${questionsBlock()}`,
        init(card) {
            const s = $('[data-k=image_mode]', card); s.value = it.image_mode || 'upload';
            const sync = () => { $('[data-media][data-kind=image]', card).hidden = s.value !== 'upload'; }; s.addEventListener('change', sync); sync();
            fillQuestions(card, it.questions, {});
        },
        read: c => ({ title: val(c, 'title'), audio: mediaVal(c, 'audio'), image: mediaVal(c, 'image'), image_mode: val(c, 'image_mode'), questions: readQs(c, {}) }),
        count: c => $$('[data-q]', c).length,
    }));
    B.build = it => ({
        html: `${text('Main question statement', it.statement || '', 'statement', 'e.g. What was the highlight of your trip?')}
            ${text('Answer statement', it.answer || '', 'answer', 'e.g. The # # # # # # fantastic.', 'Write # for every blank the student must fill.')}
            ${text('Options (separated by /)', it.options || '', 'options', 'e.g. were / the / was / old city / showed us around / who / tour guides', 'All the words and groups of words the student can drag, including any extra ones.')}
            ${text('Correct words in order (separated by /)', it.correct || '', 'correct', 'e.g. tour guides / who / showed us around / the / old city / were', 'One per blank, from the first blank to the last. The student gets the mark only if every word is in the right place.')}
            <div data-check></div>`,
        init(card) {
            const chk = () => {
                const slots = (val(card, 'answer').match(/#/g) || []).length, split = s => s.split('/').map(x => x.trim()).filter(Boolean);
                const cor = split(val(card, 'correct')), opt = split(val(card, 'options')); let msg = '';
                if (slots && cor.length !== slots) msg = `${slots} blanks but ${cor.length} correct words.`; else if (cor.some(x => !opt.includes(x))) msg = 'A correct word is missing from the options.';
                $('[data-check]', card).innerHTML = !slots ? '' : msg ? `<p class="tf-warn">⚠ ${msg}</p>` : '<p class="tf-ok">✓ Looks good</p>';
            };
            card.addEventListener('input', chk); chk();
        },
        read: c => ({ statement: val(c, 'statement'), answer: val(c, 'answer'), options: val(c, 'options'), correct: val(c, 'correct') }),
        count: () => 1,
    });
    B.email = it => ({
        html: `${editor('Main question statement (shown on the left)', it.statement || '', false, '', 'statement')}
            ${editor('Question instructions (shown on the right, above the answer box)', it.instructions || '', false, '', 'instructions')}
            <div class="tf-row"><div style="flex:1">${text('To', it.to || '', 'to', 'editor@example.com')}</div><div style="flex:1">${text('Subject', it.subject || '', 'subject')}</div></div>`,
        read: c => ({ statement: val(c, 'statement'), instructions: val(c, 'instructions'), to: val(c, 'to'), subject: val(c, 'subject') }),
        count: () => 1,
    });
    B.discussion = it => {
        const box = (p, label, cls) => `<div class="tf-box tf-box--${cls}" data-person="${p}"><strong>${label}</strong>
            <div class="tf-row"><label>Picture <select data-k="${p}_image_mode"><option value="male">Random photo – man</option><option value="female">Random photo – woman</option><option value="upload">My own picture</option></select></label></div>
            ${media('image', 'Picture', it[p + '_image'] || '', p + '_image')}${editor('Post', it[p + '_post'] || '', false, '', p + '_post')}</div>`;
        return {
            html: `<p class="tf-help">On the website the three boxes have different colours: professor (white), first student (blue), second student (light green).</p>
                ${box('professor', 'Professor’s question', 'white')}${box('student1', 'First student’s reply', 'blue')}${box('student2', 'Second student’s reply', 'green')}
                ${editor('Question instructions (shown above the answer box)', it.instructions || '', false, 'e.g. “Write a post responding to the professor’s question. An effective response will contain at least 100 words.”', 'instructions')}`,
            init(card) {
                [['professor', 'male'], ['student1', 'female'], ['student2', 'male']].forEach(([p, def]) => {
                    const sel = $(`[data-k=${p}_image_mode]`, card); sel.value = it[p + '_image_mode'] || (it[p + '_image'] ? 'upload' : def);
                    const sync = () => { $('[data-media][data-kind=image]', sel.closest('[data-person]')).hidden = sel.value !== 'upload'; }; sel.addEventListener('change', sync); sync();
                });
            },
            read: c => { const o = { instructions: val(c, 'instructions') }; ['professor', 'student1', 'student2'].forEach(p => { o[p + '_image_mode'] = val(c, p + '_image_mode'); o[p + '_image'] = mediaVal(c, p + '_image'); o[p + '_post'] = val(c, p + '_post'); }); return o; },
            count: () => 1,
        };
    };
    B.repeat = it => ({
        html: `${text('Main question statement', it.statement || '', 'statement', 'e.g. You are learning to welcome visitors to the zoo. Listen to your manager and repeat what she says.')}
            ${media('audio', 'Recording', it.audio || '', 'audio')}${media('image', 'Picture', it.image || '', 'image')}
            <label class="tf-lab">Recording time for the student (seconds)</label><input type="number" min="5" max="300" data-k="seconds" value="${it.seconds || D.defaults.seconds.repeat}">`,
        read: c => ({ statement: val(c, 'statement'), audio: mediaVal(c, 'audio'), image: mediaVal(c, 'image'), seconds: +val(c, 'seconds') }),
        count: () => 1,
    });
    const interviewFirst = () => { for (const id of order) { const c = cards.get(id); if (c && c.type === 'interview') return c; } return null; };
    function syncInterviewPics() {
        const f = interviewFirst();
        order.forEach(id => { const c = cards.get(id); if (!c || c.type !== 'interview') return; const first = c === f; $('[data-pic]', c.el).hidden = !first; $('[data-pic-note]', c.el).hidden = first; });
    }
    B.interview = it => ({
        html: `${text('Main question statement (optional)', it.statement || '', 'statement', 'e.g. You will have a short online interview with a researcher.')}
            ${media('audio', 'Interview question recording', it.audio || '', 'audio')}
            <div data-pic><div class="tf-row"><label>Picture for the whole interview <select data-k="image_mode"><option value="male">Random photo – man</option><option value="female">Random photo – woman</option><option value="upload">My own picture</option></select></label></div>
            ${media('image', 'Picture', it.image || '', 'image')}</div>
            <p class="tf-help" data-pic-note hidden>This question uses the same picture as the first interview question.</p>
            <label class="tf-lab">Recording time for the student (seconds)</label><input type="number" min="5" max="600" data-k="seconds" value="${it.seconds || D.defaults.seconds.interview}">`,
        init(card) {
            const sel = $('[data-k=image_mode]', card); sel.value = it.image_mode || (it.image ? 'upload' : 'female');
            const sync = () => { $('[data-media][data-kind=image]', card).hidden = sel.value !== 'upload'; }; sel.addEventListener('change', sync); sync();
        },
        read: c => { const f = interviewFirst(), src = f && f.el !== c ? f.el : c; return ({ statement: val(c, 'statement'), audio: mediaVal(c, 'audio'), image: mediaVal(src, 'image'), image_mode: val(src, 'image_mode'), seconds: +val(c, 'seconds') }); },
        count: () => 1,
    });

    function makeCard(type, it) {
        const id = it.id || ('n' + (++uid) + Math.random().toString(36).slice(2, 6));
        const b = B[type](it), el = document.createElement('section');
        el.className = 'tf-card'; el.dataset.id = id;
        el.innerHTML = `<div class="tf-card__head"><span class="tf-tag">${esc(D.types[type])}</span><span class="tf-grow"></span>
            ${moduleSel(it)}${b.grade ? gradedBox(it) : ''}<button type="button" class="tf-danger" data-remove>Remove</button></div>${b.html}`;
        const mod = $('[data-k=module]', el); if (mod) mod.value = it.module || 1;
        const card = {
            el, type, id,
            read() { const o = b.read(el); o.id = id; o.type = type; if (mod) o.module = +mod.value; if (b.grade) o.graded = $('[data-k=graded]', el).checked; return o; },
            n() { return b.count(el); },
            module() { return mod ? +mod.value : 0; },
            summary() { return `${D.types[type]} · ${card.n()} question${card.n() === 1 ? '' : 's'}`; },
        };
        cards.set(id, card);
        if (b.init) b.init(el);
        el.addEventListener('input', () => { if (seqOpen()) drawSeq(); counts(); });
        mod && mod.addEventListener('change', () => { drawSeq(); counts(); });
        el.addEventListener('qcount', () => { counts(); });
        $('[data-remove]', el).addEventListener('click', () => {
            if (!confirm('Remove this block and all its questions?')) return;
            order = order.filter(x => x !== id); cards.delete(id);
            if (window.tinymce) $$('textarea.tf-editor', el).forEach(t => tinymce.get(t.id) && tinymce.get(t.id).remove());
            el.remove(); drawSeq(); counts(); syncInterviewPics();
        });
        return card;
    }
    function paneFor(type) { const t = D.tabs.find(x => x.types.includes(type)); return $(`[data-cards="${t.k}"]`); }
    function addCard(type, it) {
        const card = makeCard(type, it || {}); order.push(card.id); paneFor(type).appendChild(card.el);
        startEditors(card.el); drawSeq(); counts(); syncInterviewPics(); return card;
    }

    /* ───────── numbering, counts, sequence tab ───────── */
    function groups() {
        const g = {};
        order.forEach(id => { const c = cards.get(id); if (!c) return; (g[c.module()] ||= []).push(c); });
        return g;
    }
    function counts() {
        const g = groups(), parts = Object.keys(g).sort().map(m => {
            const n = g[m].reduce((s, c) => s + c.n(), 0);
            return `${+m ? 'Module ' + m : 'All'}: ${n} questions`;
        });
        $('#tf-counts').textContent = parts.join(' · ') || 'No questions yet.';
    }
    const seqOpen = () => !$('[data-pane=seq]').hidden;
    function drawSeq() {
        const wrap = $('#tf-seq'); if (!wrap) return;
        const g = groups(), keys = D.modules ? ['1', '2'] : ['0'];
        wrap.innerHTML = keys.map(m => {
            const list = g[m] || []; let n = 0;
            const rows = list.map((c, i) => { const from = n + 1; n += c.n(); return `<div class="tf-seq__row"><b>${c.n() ? (c.n() === 1 ? 'Q' + from : 'Q' + from + '–' + n) : '–'}</b><span class="tf-grow">${esc(c.summary())}</span>
                <button type="button" data-up="${c.id}" ${i === 0 ? 'disabled' : ''} title="Move up">↑</button><button type="button" data-down="${c.id}" ${i === list.length - 1 ? 'disabled' : ''} title="Move down">↓</button></div>`; }).join('') || '<p class="muted">Nothing added yet.</p>';
            const ins = instructions.map((b, i) => ({ b, i })).filter(x => String(x.b.module || 0) === m || (!D.modules && m === '0'));
            const insHtml = ins.map(({ b, i }) => `<div class="tf-ins" data-ins="${i}"><label class="tf-lab" style="margin-top:0">Instructions</label><textarea rows="2" data-ins-text style="width:100%;padding:9px;border:1px solid var(--line);border-radius:8px;font:inherit">${esc(b.text)}</textarea>
                <div class="tf-row" style="margin-top:8px">Applies to questions <input type="number" min="1" data-ins-from value="${b.from}" style="width:80px;padding:7px;border:1px solid var(--line);border-radius:8px"> to <input type="number" min="1" data-ins-to value="${b.to}" style="width:80px;padding:7px;border:1px solid var(--line);border-radius:8px">
                <button type="button" class="tf-danger" data-ins-remove>Remove</button></div></div>`).join('');
            return `<div class="tf-seq__group"><h3 class="tf-h">${D.modules ? 'Module ' + m : 'All questions'} · ${n} questions</h3><p class="muted">Use the arrows to change the order. Questions are numbered in this order.</p>${rows}${insHtml}
                <button type="button" class="btn btn-sm btn-ghost" data-ins-add="${m}">+ Add instructions for some questions</button></div>`;
        }).join('');
    }
    $('#tf-seq').addEventListener('click', e => {
        const mv = e.target.closest('[data-up],[data-down]');
        if (mv) {
            const id = mv.dataset.up || mv.dataset.down, c = cards.get(id), same = order.filter(x => cards.get(x).module() === c.module());
            const j = same.indexOf(id) + (mv.dataset.up ? -1 : 1), other = same[j]; if (!other) return;
            const a = order.indexOf(id), b = order.indexOf(other); [order[a], order[b]] = [order[b], order[a]];
            const pane = paneFor(c.type); if (paneFor(cards.get(other).type) === pane) { const ea = c.el, eb = cards.get(other).el; j < same.indexOf(id) + 1 && mv.dataset.up ? eb.before(ea) : eb.after(ea); }
            drawSeq(); return;
        }
        const add = e.target.closest('[data-ins-add]');
        if (add) { saveIns(); instructions.push({ text: '', module: +add.dataset.insAdd || 1, from: 1, to: 1 }); if (!D.modules) instructions[instructions.length - 1].module = 1; drawSeq(); return; }
        const rm = e.target.closest('[data-ins-remove]');
        if (rm) { saveIns(); instructions.splice(+rm.closest('[data-ins]').dataset.ins, 1); drawSeq(); }
    });
    function saveIns() {
        $$('[data-ins]').forEach(d => { const b = instructions[+d.dataset.ins]; if (!b) return; b.text = $('[data-ins-text]', d).value; b.from = +$('[data-ins-from]', d).value || 1; b.to = +$('[data-ins-to]', d).value || 1; });
    }

    /* ───────── tabs and saving ───────── */
    $$('.tf-tab').forEach(t => t.addEventListener('click', () => {
        $$('.tf-tab').forEach(x => x.classList.toggle('is-active', x === t));
        $$('.tf-pane').forEach(p => { p.hidden = p.dataset.pane !== t.dataset.tab; });
        const pane = $(`[data-pane="${t.dataset.tab}"]`);
        if (t.dataset.tab === 'seq') { saveIns(); drawSeq(); } else startEditors(pane);
    }));
    $$('[data-add]').forEach(b => b.addEventListener('click', () => { const c = addCard(b.dataset.add); c.el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }));

    form.addEventListener('submit', () => {
        saveIns();
        const minutes = {}; $$('[data-minutes]').forEach(i => minutes[i.dataset.minutes] = +i.value || 0);
        $('#tf-json').value = JSON.stringify({ minutes, items: order.map(id => cards.get(id).read()), instructions: instructions.filter(b => (b.text || '').trim()) });
    });

    // meta counters (same helper the other admin forms use, if present)
    (D.content.items || []).forEach(it => addCard(it.type, it));
    startEditors($('.tf-pane:not([hidden])'));
    drawSeq(); counts();
})();
