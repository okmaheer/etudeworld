/*
 * Etude World – CELPIP Reading test (student side).
 * One box: the instructions screen, then one screen per part, then the score.
 * Each part: passage on the LEFT, question sections on the RIGHT, its own countdown timer.
 * Questions are drop-down menus:
 *   mcq       "1. statement [▼]"
 *   blanks    every "#" in the text becomes "1. [▼]"
 *   matching  "[▼] – 1. statement"
 * When a part's time runs out, its answers are locked and the next screen opens.
 */
(function () {
    const root = document.querySelector('[data-reading]');
    if (!root) return;
    const $  = (s, el = root) => el.querySelector(s);
    const $$ = (s, el = root) => Array.from(el.querySelectorAll(s));

    let data = { intro: {}, parts: [] };
    try { data = JSON.parse($('[data-rd-data]').textContent); } catch (_) {}

    const stage   = $('[data-rd-stage]');
    const titleEl = $('[data-rd-title]');
    const timerEl = $('[data-rd-timer]');
    const timeEl  = $('[data-rd-time]');
    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const fmt = s => `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;

    const screens = [];   // { el, title, part?, left?, locked? }
    const questions = []; // { part, number, statement, options, correct, select }
    let cur = -1, tick = null, audio = null;

    /*
     * Each answer box is our own drop-down (fixed width, long options wrap onto more lines).
     * A hidden <select> inside it keeps the chosen value, so scoring and locking work as before.
     */
    function select(q) {
        const sel = document.createElement('select');
        sel.hidden = true;
        sel.innerHTML = '<option value=""></option>' + q.options.map((o, k) => `<option value="${k}">${esc(o)}</option>`).join('');
        q.select = sel;
        questions.push(q);

        const wrap = document.createElement('span');
        wrap.className = 'rdx-dd';
        wrap.innerHTML = `<button type="button" class="rdx-dd__btn" aria-haspopup="listbox" aria-expanded="false" aria-label="Question ${q.number}">
                <span class="rdx-dd__val"></span><span class="rdx-dd__arrow" aria-hidden="true">▾</span></button>
            <ul class="rdx-dd__list" role="listbox" hidden>${q.options.map((o, k) => `<li role="option" tabindex="-1" data-value="${k}">${esc(o)}</li>`).join('')}</ul>`;
        wrap.prepend(sel);
        const btn = $('.rdx-dd__btn', wrap), list = $('.rdx-dd__list', wrap), items = $$('li', list);

        const sync = () => {
            const o = sel.value === '' ? null : q.options[Number(sel.value)];
            $('.rdx-dd__val', wrap).textContent = o || '';
            btn.title = o || '';
            wrap.classList.toggle('has-value', !!o);
            items.forEach(li => li.setAttribute('aria-selected', li.dataset.value === sel.value ? 'true' : 'false'));
        };
        const open = () => {
            if (sel.disabled) return;
            closeAll();
            list.hidden = false;
            btn.setAttribute('aria-expanded', 'true');
            // open upwards if there's no room below inside the questions panel
            const panel = wrap.closest('.rdx-right');
            wrap.classList.remove('is-up');
            if (panel && list.getBoundingClientRect().bottom > panel.getBoundingClientRect().bottom) wrap.classList.add('is-up');
            (items.find(li => li.getAttribute('aria-selected') === 'true') || items[0])?.focus();
        };
        const close = () => { list.hidden = true; btn.setAttribute('aria-expanded', 'false'); };
        const choose = li => { sel.value = li.dataset.value; sel.dispatchEvent(new Event('change')); sync(); close(); btn.focus(); };

        btn.addEventListener('click', () => (list.hidden ? open() : close()));
        btn.addEventListener('keydown', e => { if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); open(); } });
        list.addEventListener('click', e => { const li = e.target.closest('li'); if (li) choose(li); });
        list.addEventListener('keydown', e => {
            const i = items.indexOf(document.activeElement);
            if (e.key === 'ArrowDown') { e.preventDefault(); items[Math.min(items.length - 1, i + 1)].focus(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); items[Math.max(0, i - 1)].focus(); }
            else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); if (i >= 0) choose(items[i]); }
            else if (e.key === 'Escape' || e.key === 'Tab') { close(); btn.focus(); }
        });
        wrap._close = close;
        sync();
        return wrap;
    }
    const closeAll = () => $$('.rdx-dd').forEach(w => w._close && w._close());
    document.addEventListener('click', e => { if (!e.target.closest('.rdx-dd')) closeAll(); });

    /* ---------- build every screen once (answers stay when moving Back/Next) ---------- */
    function introScreen() {
        const el = document.createElement('div');
        el.className = 'rdx-intro';
        el.innerHTML = `<div class="lsx-text">${data.intro.text || ''}</div>`;
        screens.push({ el, title: 'CELPIP Reading Test', intro: true });
    }

    function partScreen(p) {
        const el = document.createElement('div');
        el.className = 'rdx-split';
        el.innerHTML = `
            <div class="rdx-left"><div class="rdx-passage lsx-text">${p.passage || ''}</div></div>
            <div class="rdx-right"><p class="rdx-timeup" data-timeup hidden>Time is up for this part. Your answers are locked.</p></div>`;
        const right = $('.rdx-right', el);
        let n = 0;
        p.sections.forEach(s => {
            const sec = document.createElement('section');
            sec.className = 'rdx-section rdx-section--' + s.type;
            sec.innerHTML = `<div class="rdx-instructions"><span class="rdx-info" aria-hidden="true">i</span><div>${s.instructions || ''}</div></div>
                <div class="rdx-card"></div>`;
            const card = $('.rdx-card', sec);
            if (s.type === 'blanks') {
                const text = document.createElement('div');
                text.className = 'rdx-blanks';
                text.innerHTML = s.passage || '';
                let qi = 0;
                const walker = document.createTreeWalker(text, NodeFilter.SHOW_TEXT);
                const nodes = [];
                while (walker.nextNode()) if (walker.currentNode.nodeValue.includes('#')) nodes.push(walker.currentNode);
                nodes.forEach(node => {
                    const parts = node.nodeValue.split('#');
                    const frag = document.createDocumentFragment();
                    parts.forEach((t, i) => {
                        frag.appendChild(document.createTextNode(t));
                        if (i < parts.length - 1) {
                            const q = s.questions[qi++];
                            if (!q) return;
                            const wrap = document.createElement('span');
                            wrap.className = 'rdx-blank';
                            const num = ++n;
                            wrap.innerHTML = `<span class="rdx-num">${num}.</span>`;
                            wrap.appendChild(select({ ...q, part: p.part, number: num }));
                            frag.appendChild(wrap);
                        }
                    });
                    node.replaceWith(frag);
                });
                card.appendChild(text);
            } else {
                s.questions.forEach(q => {
                    const num = ++n;
                    const row = document.createElement('div');
                    row.className = 'rdx-q';
                    const sel = select({ ...q, part: p.part, number: num });
                    if (s.type === 'matching') {
                        row.appendChild(sel);
                        row.insertAdjacentHTML('beforeend', `<span class="rdx-stmt"> – ${num}. ${esc(q.statement)}</span>`);
                    } else {
                        row.innerHTML = `<span class="rdx-stmt">${num}. ${esc(q.statement)}</span> `;
                        row.appendChild(sel);
                    }
                    card.appendChild(row);
                });
            }
            right.appendChild(sec);
        });
        screens.push({ el, title: p.title, part: p.part, left: p.minutes * 60 });
    }

    function endScreen() {
        const el = document.createElement('div');
        el.className = 'lsx-end';
        screens.push({ el, title: 'Reading test complete', end: true });
    }

    /*
     * CELPIP Reading level from the number of correct answers out of 38.
     * If a test has a different number of questions, the score is first scaled to 38.
     * Where the ranges in the table overlap, the higher level wins (e.g. 33 → level 10).
     */
    const BANDS = [
        { min: 33, level: '10, 11, 12', clb: 'CLB 10+',      note: 'Advanced; maximum CRS language points' },
        { min: 31, level: '9',          clb: 'CLB 9',        note: 'Strong; competitive for most Express Entry draws' },
        { min: 28, level: '8',          clb: 'CLB 8',        note: 'Good comprehension of detailed passages' },
        { min: 24, level: '7',          clb: 'CLB 7',        note: 'Meets the common FSW / CEC minimum' },
        { min: 19, level: '6',          clb: 'CLB 6',        note: 'Below the FSW minimum' },
        { min: 15, level: '5',          clb: 'CLB 5',        note: 'Basic professional reading' },
        { min: 10, level: '4',          clb: 'CLB 4',        note: 'Emerging; limited ability' },
        { min: 8,  level: '3',          clb: 'CLB 3',        note: 'Below most immigration minimums' },
        { min: 0,  level: '0 to 2',     clb: 'Below CLB 3',  note: 'Beginner' },
    ];
    const bandFor = (score, total) => {
        const out38 = total ? Math.round(score / total * 38) : 0;
        return { out38, ...BANDS.find(b => out38 >= b.min) };
    };

    let resultSaved = false;
    function renderEnd(el) {
        const items = questions.map(q => {
            const chosen = q.select.value === '' ? null : Number(q.select.value);
            return { q, chosen, ok: q.correct !== null && chosen === q.correct };
        });
        const score = items.filter(x => x.ok).length;
        const band = bandFor(score, items.length);
        window.EW_READING_RESULT = { score, total: items.length, level: `CELPIP level ${band.level} · ${band.clb}` };
        if (!resultSaved && window.EWSaveResult) { resultSaved = true; window.EWSaveResult('reading', window.EW_READING_RESULT); }
        try {
            localStorage.setItem('ew-reading-result-' + root.dataset.testId, JSON.stringify({
                saved: Date.now(), score, total: items.length, level: band.level, clb: band.clb,
                answers: items.map(x => ({ part: x.q.part, number: x.q.number, chosen: x.chosen, correct: x.q.correct })),
            }));
        } catch (_) {}
        const opt = (q, k) => k === null || k === undefined ? '<em>No answer</em>' : esc(q.options[k]);
        el.innerHTML = `
            <div class="rdx-result">
                <div class="rdx-result__score"><strong>${score}</strong><span>out of ${items.length}</span></div>
                <div class="rdx-result__band">
                    <p class="rdx-result__label">Your CELPIP Reading level</p>
                    <p class="rdx-result__level">${band.level} <span>${band.clb}</span></p>
                    <p class="rdx-result__note">${band.note}</p>
                    ${items.length !== 38 ? `<p class="rdx-result__small">Your score is about ${band.out38} out of 38 on the official scale.</p>` : ''}
                </div>
            </div>
            ${window.EWVocabCard ? window.EWVocabCard.html(root.dataset.vocab) : ''}
            <h3>Your answers</h3>
            <ol class="lsx-review">${items.map(({ q, chosen, ok }) => `<li class="${ok ? 'is-right' : 'is-wrong'}">
                <span class="lsx-review__mark">${ok ? '✓' : '✗'}</span>
                <div><strong>Part ${q.part} · Question ${q.number}</strong>${q.statement ? ` – ${esc(q.statement)}` : ''}<br>
                <small>Your answer: ${opt(q, chosen)}${ok ? '' : ` · Correct answer: ${opt(q, q.correct)}`}</small></div>
            </li>`).join('')}</ol>`;
        if (window.EWVocabCard) window.EWVocabCard.bind(el);
    }

    introScreen();
    (data.parts || []).forEach(partScreen);
    endScreen();
    screens.forEach(s => { s.el.hidden = true; stage.appendChild(s.el); });

    /* ---------- moving between screens ---------- */
    function stop() { clearInterval(tick); tick = null; }

    function lock(s, timeUp) {
        s.locked = true;
        closeAll();
        $$('select', s.el).forEach(x => { x.disabled = true; });
        $$('.rdx-dd__btn', s.el).forEach(x => { x.disabled = true; });
        const t = $('[data-timeup]', s.el); if (t && timeUp) t.hidden = false;
    }

    const mock = root.dataset.mode === 'mock';
    let submitted = false;
    const lastPart = () => { for (let k = screens.length - 1; k >= 0; k--) if (screens[k].part) return k; return -1; };

    function show(i) {
        if (i < 0 || i >= screens.length) return;
        stop();
        closeAll();
        if (audio && i !== 0) { audio.pause(); }
        cur = i;
        const s = screens[i];
        const isLast = i === lastPart();
        screens.forEach((x, k) => { x.el.hidden = k !== i; });
        titleEl.textContent = s.title;
        const next = $('[data-rd-next]');
        next.hidden = !!s.end;
        next.disabled = isLast;                       // nothing after the last part – use Submit
        $('[data-rd-submit]').hidden = !isLast || submitted;
        // Full mock test: no going back. Single Reading practice: Back is allowed until the test is submitted.
        $('[data-rd-back]').hidden = mock || i === 0 || !!s.end || submitted;
        $('[data-rd-continue]').hidden = !s.end;
        timerEl.hidden = s.left === undefined;
        $$('.rdx-left, .rdx-right', s.el).forEach(x => { x.scrollTop = 0; });
        window.scrollTo({ top: 0 });

        if (s.intro && data.intro.audio && !audio) {
            audio = new Audio(data.intro.audio);
            audio.play().catch(() => {});
        }
        if (s.end) {
            renderEnd(s.el);
        if (document.querySelector('[data-results-download]') && root.querySelector('[data-results-download]')) {
            const exam = document.querySelector('[data-exam]'); if (exam) exam.dispatchEvent(new CustomEvent('ew:finished'));
        }
            return;
        }
        if (s.left !== undefined) {
            const draw = () => {
                timeEl.textContent = fmt(Math.max(0, s.left));
                timerEl.classList.toggle('is-warning', s.left <= 60);
            };
            draw();
            if (s.locked) return;
            tick = setInterval(() => {
                s.left -= 1;
                draw();
                if (s.left <= 0) {
                    stop(); lock(s, true);
                    if (isLast) submit(true); else show(cur + 1);
                }
            }, 1000);
        }
    }

    /* Submit: lock every part, mark the answers and show the results. Also happens when the last part's time runs out. */
    function submit(timeUp) {
        if (submitted) return;
        if (!timeUp) {
            const empty = questions.filter(q => q.select.value === '').length;
            const msg = empty
                ? `You have ${empty} unanswered question${empty > 1 ? 's' : ''}. Submit your answers anyway? You can't change them after this.`
                : "Submit your answers? You can't change them after this.";
            if (!window.confirm(msg)) return;
        }
        submitted = true;
        stop();
        screens.forEach(x => { if (x.part) lock(x); });
        show(screens.length - 1);
        if (!mock && window.EWResultsPage) window.EWResultsPage();
        if (!mock && window.EWResultsActions) window.EWResultsActions(screens[screens.length - 1].el);
    }

    $('[data-rd-submit]').addEventListener('click', () => submit(false));
    $('[data-rd-next]').addEventListener('click', () => {
        // Full mock test: a part you move on from is closed for good.
        if (mock && screens[cur] && screens[cur].part) { stop(); lock(screens[cur]); }
        show(cur + 1);
    });
    $('[data-rd-back]').addEventListener('click', () => show(cur - 1));
    $('[data-rd-continue]').addEventListener('click', () => { stop(); if (audio) audio.pause(); });

    // Start when the Reading section opens; stop the timer if the section is left.
    const watch = () => {
        if (!root.hidden && cur === -1) show(0);
        if (root.hidden) { stop(); if (audio) audio.pause(); }
    };
    new MutationObserver(watch).observe(root, { attributes: true, attributeFilter: ['hidden'] });
    watch();
})();
