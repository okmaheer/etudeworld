/*
 * Etude World – CELPIP Listening test (student side).
 * All parts run inside ONE box on one page. The list of screens comes from the server
 * (App\Support\CelpipListening::screens), so new parts appear here automatically.
 *
 *  info       text (+ picture); its audio plays in the background; Next is available straight away
 *  recording  text above, audio in the middle; Next appears when the recording ends
 *  question   audio plays; Next appears when it ends, then a 30-second answer timer starts
 *  pause      10-second gap between divisions, then it moves on by itself
 *  group      Part 4: all questions on one screen (drop-down menus), one timer for all of them
 *  end        score + review (added here), then the button to continue the test
 */
(function () {
    const root = document.querySelector('[data-listening]');
    if (!root) return;
    const $  = (s, el = root) => el.querySelector(s);
    const $$ = (s, el = root) => Array.from(el.querySelectorAll(s));

    let screens = [];
    try { screens = JSON.parse($('[data-ls-data]').textContent) || []; } catch (_) {}
    screens.push({ type: 'end', title: 'Listening test complete – your results' });

    const stage   = $('[data-ls-stage]');
    const titleEl = $('[data-ls-title]');
    const timerEl = $('[data-ls-timer]');
    const timeEl  = $('[data-ls-time]');

    const answers = {};          // screen index → chosen option (0–3)
    const visited = new Set();
    let cur = -1, tick = null, audio = null;

    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const fmt = s => `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
    const LETTERS = ['A', 'B', 'C', 'D'];

    function stopAll() {
        clearInterval(tick); tick = null;
        if (audio) { audio.pause(); audio = null; }
        $$('audio, video', stage).forEach(a => a.pause());
        timerEl.hidden = true;
    }

    function nav(back, next) {
        $$('[data-ls-back]').forEach(b => { b.hidden = !back; });
        $$('[data-ls-next]').forEach(b => { b.hidden = !next; });
    }

    function countdown(seconds, onTick, onDone) {
        let left = seconds;
        onTick(left);
        tick = setInterval(() => {
            left -= 1;
            onTick(left);
            if (left <= 0) { clearInterval(tick); tick = null; onDone(); }
        }, 1000);
    }

    /* ---------- drawing each screen ---------- */
    function audioPlayer(src) {
        // A video file (e.g. the Part 5 discussion) is shown as a video; anything else as an audio bar.
        if (/\.(mp4|webm|mov|m4v)(\?|$)/i.test(src)) {
            return `<div class="lsx-audio lsx-video"><video controls preload="auto" playsinline src="${esc(src)}" controlsList="nodownload noplaybackrate"></video></div>`;
        }
        return `<div class="lsx-audio"><audio controls preload="auto" src="${esc(src)}" controlsList="nodownload noplaybackrate"></audio></div>`;
    }

    function render(s, i) {
        if (s.type === 'info') {
            return `<div class="lsx-info ${s.image ? 'has-image' : ''}">
                <div class="lsx-text">${s.text || ''}</div>
                ${s.image ? `<img class="lsx-img" src="${esc(s.image)}" alt="">` : ''}
            </div>
            ${s.audio ? '<p class="lsx-playing" data-ls-playing><span class="lsx-bars"><i></i><i></i><i></i></span> Audio is playing…</p>' : ''}`;
        }
        if (s.type === 'recording') {
            return `<div class="lsx-recording">
                ${s.text ? `<div class="lsx-text">${s.text}</div>` : ''}
                ${s.audio ? audioPlayer(s.audio) : ''}
                <p class="lsx-hint" data-ls-hint>${s.audio ? 'Listen carefully. The Next button appears when the recording ends.' : ''}</p>
            </div>`;
        }
        if (s.type === 'question') {
            const pictures = s.options.some(o => o.image);
            return `<div class="lsx-question lsx-split">
                <div class="lsx-split__left">
                    ${s.audio ? audioPlayer(s.audio) : ''}
                    ${s.image ? `<img class="lsx-img" src="${esc(s.image)}" alt="">` : ''}
                </div>
                <div class="lsx-split__right">
                    ${s.text ? `<div class="lsx-text">${s.text}</div>` : ''}
                    ${s.statement ? `<p class="lsx-statement">${esc(s.statement)}</p>` : ''}
                    <div class="lsx-options ${pictures ? 'is-pictures' : ''}" role="radiogroup">
                        ${s.options.map((o, k) => (o.text || o.image) ? `
                            <label class="lsx-opt">
                                <input type="radio" name="lsx-q-${i}" value="${k}" ${answers[i] === k ? 'checked' : ''}>
                                <span class="lsx-opt__letter">${LETTERS[k]}</span>
                                <span class="lsx-opt__body">
                                    ${o.image ? `<img src="${esc(o.image)}" alt="Option ${LETTERS[k]}">` : ''}
                                    ${o.text ? `<span>${esc(o.text)}</span>` : ''}
                                </span>
                            </label>` : '').join('')}
                    </div>
                </div>
            </div>`;
        }
        if (s.type === 'group') {
            return `<div class="lsx-group">
                <div class="lsx-group__intro"><span class="rdx-info" aria-hidden="true">i</span><div>${s.intro || ''}</div></div>
                <p class="rdx-timeup" data-group-timeup ${s.locked ? '' : 'hidden'}>Time is up. Your answers are locked.</p>
                <div class="lsx-group__qs">${s.questions.map((q, k) => `<div class="lsx-gq">
                    <span class="lsx-gq__stmt">${q.number}. ${esc(q.statement)}</span> <span data-gdd="${k}"></span></div>`).join('')}</div>
            </div>`;
        }
        if (s.type === 'pause') {
            return `<div class="lsx-pause"><p>The next section of the conversation will begin in</p>
                <strong data-ls-pause>${s.seconds}</strong><p>seconds</p></div>`;
        }
        if (root.querySelector('[data-results-download]')) {
            const exam = document.querySelector('[data-exam]'); if (exam) exam.dispatchEvent(new CustomEvent('ew:finished'));
        }
        return renderEnd();
    }

    /* Drop-down for Part 4 (fixed width; a chosen answer shows as bold green text, like the official test). */
    function dropdown(options, value, disabled, onChange) {
        const wrap = document.createElement('span');
        wrap.className = 'rdx-dd lsx-dd';
        wrap.innerHTML = `<button type="button" class="rdx-dd__btn" aria-haspopup="listbox" aria-expanded="false" ${disabled ? 'disabled' : ''}>
                <span class="rdx-dd__val"></span><span class="rdx-dd__arrow" aria-hidden="true">▾</span></button>
            <ul class="rdx-dd__list" role="listbox" hidden>${options.map((o, k) => `<li role="option" tabindex="-1" data-value="${k}">${esc(o)}</li>`).join('')}</ul>`;
        const btn = $('.rdx-dd__btn', wrap), list = $('.rdx-dd__list', wrap), items = $$('li', list);
        const sync = () => {
            $('.rdx-dd__val', wrap).textContent = value === undefined ? '' : options[value];
            wrap.classList.toggle('has-value', value !== undefined);
            items.forEach(li => li.setAttribute('aria-selected', String(Number(li.dataset.value) === value)));
        };
        const close = () => { list.hidden = true; btn.setAttribute('aria-expanded', 'false'); };
        const open = () => {
            $$('.lsx-dd .rdx-dd__list').forEach(l => { l.hidden = true; });
            list.hidden = false; btn.setAttribute('aria-expanded', 'true');
            wrap.classList.toggle('is-up', list.getBoundingClientRect().bottom > window.innerHeight - 10);
            (items.find(li => li.getAttribute('aria-selected') === 'true') || items[0])?.focus();
        };
        const choose = li => { value = Number(li.dataset.value); onChange(value); sync(); close(); btn.focus(); };
        btn.addEventListener('click', () => (list.hidden ? open() : close()));
        list.addEventListener('click', e => { const li = e.target.closest('li'); if (li) choose(li); });
        list.addEventListener('keydown', e => {
            const i = items.indexOf(document.activeElement);
            if (e.key === 'ArrowDown') { e.preventDefault(); items[Math.min(items.length - 1, i + 1)].focus(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); items[Math.max(0, i - 1)].focus(); }
            else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); if (i >= 0) choose(items[i]); }
            else if (e.key === 'Escape' || e.key === 'Tab') { close(); btn.focus(); }
        });
        sync();
        return wrap;
    }
    document.addEventListener('click', e => { if (!e.target.closest('.lsx-dd')) $$('.lsx-dd .rdx-dd__list').forEach(l => { l.hidden = true; }); });

    function result() {
        const items = [];
        screens.forEach((s, i) => {
            if (s.type === 'group') {
                s.questions.forEach((q, k) => {
                    const chosen = answers[`${i}-${k}`];
                    items.push({ s: { ...q, part: s.part, options: q.options.map(t => ({ text: t })) }, chosen, ok: q.correct !== null && chosen === q.correct });
                });
                return;
            }
            if (s.type !== 'question') return;
            items.push({ s, chosen: answers[i], ok: s.correct !== null && answers[i] === s.correct });
        });
        return { items, score: items.filter(x => x.ok).length, total: items.length };
    }

    /*
     * CELPIP Listening level from the number of correct answers out of 38.
     * If a test has a different number of questions, the score is first scaled to 38.
     * Where the ranges in the table overlap, the higher level wins (e.g. 7 → level 3, 35 → 10–12).
     */
    const BANDS = [
        { min: 35, max: 38, level: '10–12',       clb: '10–12' },
        { min: 33, max: 34, level: '9',           clb: '9' },
        { min: 30, max: 32, level: '8',           clb: '8' },
        { min: 27, max: 29, level: '7',           clb: '7' },
        { min: 22, max: 26, level: '6',           clb: '6' },
        { min: 17, max: 21, level: '5',           clb: '5' },
        { min: 11, max: 16, level: '4',           clb: '4' },
        { min: 7,  max: 10, level: '3',           clb: '3' },
        { min: 0,  max: 6,  level: 'M (Minimal)', clb: 'Below 3' },
    ];

    let resultSaved = false;
    function renderEnd() {
        const r = result();
        const out38 = r.total ? Math.round(r.score / r.total * 38) : 0;
        const band = BANDS.find(b => out38 >= b.min);
        const clb = band.clb.startsWith('Below') ? 'Below CLB 3' : 'CLB ' + band.clb;
        window.EW_LISTENING_RESULT = { score: r.score, total: r.total, level: `CELPIP level ${band.level} · ${clb}` };
        if (!resultSaved && window.EWSaveResult) { resultSaved = true; window.EWSaveResult('listening', window.EW_LISTENING_RESULT); }
        try {
            localStorage.setItem('ew-listening-result-' + (root.dataset.testId || ''), JSON.stringify({
                saved: Date.now(), score: r.score, total: r.total, level: band.level, clb,
            }));
        } catch (_) {}
        const optText = (s, k) => k === undefined || k === null ? '<em>No answer</em>'
            : `${LETTERS[k]}${s.options[k] && s.options[k].text ? ' – ' + esc(s.options[k].text) : ''}`;
        return `<div class="lsx-end">
            <div class="rdx-result">
                <div class="rdx-result__score"><strong>${r.score}</strong><span>out of ${r.total}</span></div>
                <div class="rdx-result__band">
                    <p class="rdx-result__label">Your CELPIP Listening level</p>
                    <p class="rdx-result__level">${band.level} <span>${clb}</span></p>
                    ${r.total !== 38 ? `<p class="rdx-result__small">Your score is about ${out38} out of 38 on the official scale.</p>` : ''}
                </div>
            </div>
            ${window.EWVocabCard ? window.EWVocabCard.html(root.dataset.vocab) : ''}
            <h3>Your answers</h3>
            <ol class="lsx-review">
                ${r.items.map(({ s, chosen, ok }) => `<li class="${ok ? 'is-right' : 'is-wrong'}">
                    <span class="lsx-review__mark">${ok ? '✓' : '✗'}</span>
                    <div><strong>Part ${s.part} · Question ${s.number}</strong>${s.statement ? ` – ${esc(s.statement)}` : ''}<br>
                    <small>Your answer: ${optText(s, chosen)}${ok ? '' : ` · Correct answer: ${optText(s, s.correct)}`}</small></div>
                </li>`).join('')}
            </ol>
        </div>`;
    }

    /* ---------- moving between screens ---------- */
    const mock = root.dataset.mode === 'mock';

    function show(i) {
        if (i < 0 || i >= screens.length) return;
        stopAll();
        cur = i;
        const s = screens[i];
        const firstVisit = !visited.has(i);
        visited.add(i);
        titleEl.textContent = s.title;
        stage.innerHTML = render(s, i);
        root.scrollIntoView({ block: 'start' });
        $('[data-ls-continue]').hidden = s.type !== 'end';
        if (s.type === 'end' && !mock && window.EWResultsPage) { window.EWResultsPage(); window.EWResultsActions($('.lsx-end', stage)); if (window.EWVocabCard) window.EWVocabCard.bind($('.lsx-end', stage)); }

        // Back: only when practising Listening on its own – never in the full mock test.
        const canBack = !mock && i > 0 && s.type !== 'pause' && s.type !== 'end';
        const player = $('audio, video', stage);

        if (s.type === 'info') {
            nav(canBack, true);
            if (s.audio) {
                audio = new Audio(s.audio);
                audio.addEventListener('ended', () => { const p = $('[data-ls-playing]', stage); if (p) p.hidden = true; });
                audio.play().catch(() => { const p = $('[data-ls-playing]', stage); if (p) p.hidden = true; });
            }
        } else if (s.type === 'recording') {
            if (!player || !firstVisit) { nav(canBack, true); return; }
            nav(canBack, false);
            player.addEventListener('ended', () => { nav(canBack, true); const h = $('[data-ls-hint]', stage); if (h) h.textContent = ''; }, { once: true });
            player.play().catch(() => nav(canBack, true));
        } else if (s.type === 'question') {
            $$('input[type=radio]', stage).forEach(r => r.addEventListener('change', () => { answers[i] = Number(r.value); }));
            if (!firstVisit) { nav(canBack, true); return; }
            nav(canBack, false);
            const startAnswerTimer = () => {
                nav(canBack, true);
                timerEl.hidden = false;
                countdown(s.seconds || 30, left => {
                    timeEl.textContent = fmt(Math.max(0, left));
                    timerEl.classList.toggle('is-warning', left <= 10);
                }, () => show(cur + 1));
            };
            if (player) {
                player.addEventListener('ended', startAnswerTimer, { once: true });
                player.play().catch(startAnswerTimer);
            } else {
                startAnswerTimer();
            }
        } else if (s.type === 'group') {
            $$('[data-gdd]', stage).forEach(slot => {
                const k = Number(slot.dataset.gdd);
                slot.replaceWith(dropdown(s.questions[k].options, answers[`${i}-${k}`], !!s.locked, v => { answers[`${i}-${k}`] = v; }));
            });
            nav(canBack, true);
            if (s.left === undefined) s.left = s.seconds || 180;
            // "3 minutes … 2 minutes"; in the last minute it turns red and shows seconds
            const draw = () => {
                timeEl.textContent = s.left > 60 ? `${Math.ceil(s.left / 60)} minutes` : fmt(Math.max(0, s.left));
                timerEl.classList.toggle('is-warning', s.left <= 60);
            };
            timerEl.hidden = false;
            draw();
            if (s.locked) return;
            tick = setInterval(() => {
                s.left -= 1;
                draw();
                if (s.left <= 0) { clearInterval(tick); tick = null; s.locked = true; show(cur + 1); }
            }, 1000);
        } else if (s.type === 'pause') {
            nav(false, false);
            countdown(s.seconds || 10, left => { const el = $('[data-ls-pause]', stage); if (el) el.textContent = Math.max(0, left); }, () => show(cur + 1));
        } else {
            nav(false, false);
        }
    }

    $$('[data-ls-next]').forEach(b => b.addEventListener('click', () => {
        if (mock && screens[cur] && screens[cur].type === 'group') screens[cur].locked = true;
        show(cur + 1);
    }));
    $$('[data-ls-back]').forEach(b => b.addEventListener('click', () => {
        let j = cur - 1;
        while (j > 0 && screens[j].type === 'pause') j--;
        show(j);
    }));
    $('[data-ls-continue]').addEventListener('click', stopAll);

    // Start the first screen when the Listening section opens (after the student clicks Start/Begin).
    const startIfVisible = () => { if (!root.hidden && cur === -1) show(0); };
    new MutationObserver(startIfVisible).observe(root, { attributes: true, attributeFilter: ['hidden'] });
    startIfVisible();
    // Leaving the section (e.g. Exit) stops any sound.
    new MutationObserver(() => { if (root.hidden) stopAll(); }).observe(root, { attributes: true, attributeFilter: ['hidden'] });
})();
