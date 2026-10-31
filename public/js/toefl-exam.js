/*
 * Etude World – TOEFL iBT (2026 format) test screen.
 * Builds every screen from the JSON in #tfx-data and runs the sections one after another:
 *   Reading (modules, can go back in practice) · Listening (audio first, then questions)
 *   Writing (build a sentence, email, academic discussion) · Speaking (listen and repeat, interview)
 * Reading, Listening and Build a Sentence are marked on the server; Writing and Speaking are saved for the teacher.
 */
(function () {
    const cfg = JSON.parse(document.getElementById('tfx-data').textContent);
    const U = window.TFX_URLS;
    const root = document.getElementById('tfx'), main = document.getElementById('tfx-main');
    const timerBox = root.querySelector('[data-timer]'), timerText = root.querySelector('[data-timer-text]'), stepLabel = root.querySelector('[data-step-label]');
    const sid = root.querySelector('[data-sid]');
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const mock = cfg.mode === 'mock';
    const LABEL = { reading: 'Reading', listening: 'Listening', writing: 'Writing', speaking: 'Speaking' };

    /* ───────── small helpers ───────── */
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const h = (html, cls, tag) => { const e = document.createElement(tag || 'div'); if (cls) e.className = cls; if (html != null) e.innerHTML = html; return e; };
    const $ = (s, e) => (e || main).querySelector(s);
    const $$ = (s, e) => Array.from((e || main).querySelectorAll(s));
    const wait = ms => new Promise(r => setTimeout(r, ms));
    const shuffle = a => { a = a.slice(); for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; } return a; };
    const fmt = s => { s = Math.max(0, Math.ceil(s)); return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); };
    const letter = i => String.fromCharCode(65 + i);
    const words = t => (t.trim().match(/\S+/g) || []).length;
    let running = true;
    window.addEventListener('beforeunload', e => { if (running) { e.preventDefault(); e.returnValue = ''; } });
    $$('[data-exit]', root).forEach(a => a.addEventListener('click', e => { if (running && !confirm('Leave the test? Your progress will be lost.')) e.preventDefault(); else running = false; }));

    function show(node, label) {
        main.innerHTML = ''; main.appendChild(node); window.scrollTo(0, 0);
        if (label) stepLabel.textContent = label;
        if (typeof timerVis === 'function') timerVis();
    }
    /** A screen with one button; resolves when it is clicked. */
    function gate(html, btnText, label) {
        return new Promise(res => {
            const box = h(html, 'exam-step exam-step--center'); const inner = h(null, 'exam-intro');
            inner.innerHTML = html; const b = h(btnText, 'btn btn-primary btn-lg', 'button'); b.type = 'button'; inner.appendChild(b);
            box.innerHTML = ''; box.appendChild(inner); show(box, label); b.addEventListener('click', () => res(), { once: true }); b.focus();
        });
    }

    /* ───────── countdowns ───────── */
    function Countdown(onTick) {
        let id = null, end = 0, done = null;
        const api = {
            start(sec, cb) { api.stop(); end = Date.now() + sec * 1000; done = cb; const tick = () => { const left = (end - Date.now()) / 1000; onTick(Math.max(0, left)); if (left <= 0) { api.stop(); cb && cb(); } }; tick(); id = setInterval(tick, 250); },
            stop() { if (id) clearInterval(id); id = null; },
            left() { return Math.max(0, (end - Date.now()) / 1000); },
        };
        return api;
    }
    /* ───────── preferences (kept in this browser): background, timer, volume ───────── */
    const prefs = Object.assign({ bg: 'white', hideTimer: false, volume: 100 }, (() => { try { return JSON.parse(localStorage.getItem('ew-tfx-prefs') || '{}'); } catch (_) { return {}; } })());
    const savePrefs = () => { try { localStorage.setItem('ew-tfx-prefs', JSON.stringify(prefs)); } catch (_) {} };
    let lastLeft = 0, clockOn = false, currentAudio = null;
    /** The timer can be hidden, but it comes back when 5 minutes are left. */
    function timerVis() {
        const vis = clockOn && (!prefs.hideTimer || lastLeft <= 300);
        timerBox.hidden = !vis;
        $$('.tfx-time').forEach(t => { t.hidden = !vis; });
    }
    const clock = Countdown(left => {
        lastLeft = left; timerVis();
        timerText.textContent = fmt(left);
        timerBox.classList.toggle('is-danger', left <= 30); timerBox.classList.toggle('is-warning', left > 30 && left <= 120);
        $$('.tfx-clock').forEach(c => { c.textContent = fmt(left); });
    });
    const startClock = (sec, cb) => { clockOn = true; lastLeft = sec; clock.start(sec, cb); timerVis(); };
    const stopClock = () => { clock.stop(); clockOn = false; timerVis(); };

    /* menu: hide timer · background · volume */
    const menuWrap = h(`<button type="button" class="exam-bar__exit tfx-menu-btn" aria-expanded="false" aria-haspopup="true">☰ Menu</button>
        <div class="tfx-menu" hidden>
            <h4>Timer</h4><button type="button" class="tfx-opt-btn" data-hide-timer></button>
            <p class="tfx-menu__note">A hidden timer comes back when 5 minutes are left.</p>
            <h4>Background</h4><div class="tfx-menu__row"><button type="button" class="tfx-opt-btn" data-bg="white">White</button><button type="button" class="tfx-opt-btn tfx-opt-btn--yellow" data-bg="yellow">Yellow</button><button type="button" class="tfx-opt-btn tfx-opt-btn--dark" data-bg="dark">Dark</button></div>
            <h4>Volume</h4><label class="tfx-vol-row"><input type="range" min="0" max="100" data-volume value="${prefs.volume}"></label><button type="button" class="tfx-opt-btn" data-testsound>Test sound</button>
        </div>`, 'tfx-menuwrap');
    root.querySelector('.exam-bar__exit').before(menuWrap);
    const menuBox = menuWrap.querySelector('.tfx-menu'), menuBtn = menuWrap.querySelector('.tfx-menu-btn');
    function applyPrefs() {
        root.dataset.bg = prefs.bg;
        root.querySelectorAll('[data-bg]').forEach(b => b.classList.toggle('is-on', b.dataset.bg === prefs.bg));
        root.querySelector('[data-hide-timer]').textContent = prefs.hideTimer ? 'Show timer' : 'Hide timer';
        timerVis();
    }
    function setVolume(v) { prefs.volume = v; savePrefs(); root.querySelectorAll('[data-volume]').forEach(i => { i.value = v; }); if (currentAudio) currentAudio.volume = v / 100; }
    function testSound() {
        try { const ac = new (window.AudioContext || window.webkitAudioContext)(), o = ac.createOscillator(), g = ac.createGain(); g.gain.value = prefs.volume / 100 * 0.4; o.frequency.value = 440; o.connect(g); g.connect(ac.destination); o.start(); setTimeout(() => { o.stop(); ac.close(); }, 700); } catch (_) {}
    }
    const volumeHtml = () => `<div class="tfx-vol"><label>🔊 Volume <input type="range" min="0" max="100" value="${prefs.volume}" data-volume></label><button type="button" class="btn btn-ghost btn-sm" data-testsound>Test sound</button></div>`;
    root.addEventListener('input', e => { if (e.target.matches('[data-volume]')) setVolume(+e.target.value); });
    root.addEventListener('click', e => {
        if (e.target.closest('.tfx-menu-btn')) { menuBox.hidden = !menuBox.hidden; menuBtn.setAttribute('aria-expanded', String(!menuBox.hidden)); return; }
        if (e.target.closest('[data-testsound]')) testSound();
        const bg = e.target.closest('[data-bg]'); if (bg) { prefs.bg = bg.dataset.bg; savePrefs(); applyPrefs(); }
        if (e.target.closest('[data-hide-timer]')) { prefs.hideTimer = !prefs.hideTimer; savePrefs(); applyPrefs(); }
        if (!e.target.closest('.tfx-menuwrap')) menuBox.hidden = true;
    });
    applyPrefs();

    /* ───────── question panel (footer) and flags ───────── */
    const flags = new Set();
    const foot = h(null, 'tfx-foot'); foot.hidden = true; root.appendChild(foot);
    const panel = {
        qs: [], opts: {}, cur: [], label: '',
        show(label, qs, opts) { this.qs = qs; this.opts = opts || {}; this.cur = []; this.label = label; foot.hidden = false; root.classList.add('has-foot'); this.draw(); },
        hide() { foot.hidden = true; root.classList.remove('has-foot'); this.qs = []; },
        cls(q) { return flags.has(q.key) ? 'is-flag' : q.done() ? 'is-done' : ''; },
        refresh(cur) { if (cur) this.cur = cur; if (this.qs.length) this.draw(); },
        draw() {
            foot.innerHTML = `<span class="tfx-foot__label">${esc(this.label)}</span><div class="tfx-foot__nums">${this.qs.map(q => `<button type="button" class="tfx-qb ${this.cls(q)} ${this.cur.includes(q.n) ? 'is-cur' : ''}" data-fq="${q.n}" ${this.opts.go ? '' : 'disabled'} aria-label="Question ${q.n}">${q.n}</button>`).join('')}</div>
                ${this.opts.review ? '<button type="button" class="btn btn-ghost btn-sm" data-review>Review</button>' : ''}`;
        },
    };
    foot.addEventListener('click', e => {
        const b = e.target.closest('[data-fq]'); if (b && panel.opts.go) panel.opts.go(panel.qs.find(q => q.n === +b.dataset.fq));
        if (e.target.closest('[data-review]') && panel.opts.review) panel.opts.review();
    });
    const touch = () => panel.refresh();
    /** The "Flag" button: marks questions to come back to. */
    function flagBtn(keys) {
        const b = h('', 'tfx-flag', 'button'); b.type = 'button';
        const paint = () => { const on = keys.every(k => flags.has(k)); b.classList.toggle('is-on', on); b.textContent = on ? '⚑ Flagged' : '⚑ Flag'; b.setAttribute('aria-pressed', String(on)); };
        b.addEventListener('click', () => { const on = !keys.every(k => flags.has(k)); keys.forEach(k => on ? flags.add(k) : flags.delete(k)); paint(); touch(); });
        paint(); return b;
    }

    /* ───────── pictures (uploaded, or a random photo of a man / woman) ───────── */
    const AVATAR = "data:image/svg+xml;utf8," + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#dbe3f0"/><circle cx="50" cy="38" r="18" fill="#9aa8c4"/><path d="M14 92c4-24 20-34 36-34s32 10 36 34z" fill="#9aa8c4"/></svg>');
    const rnd = () => Math.floor(Math.random() * 100);
    const portrait = (gender, n) => `https://randomuser.me/api/portraits/${gender === 'female' ? 'women' : 'men'}/${n}.jpg`;
    function photo(mode, upload, seed) {
        const img = src => `<img class="tfx-photo" src="${esc(src)}" alt="" onerror="this.onerror=null;this.src='${AVATAR}'">`;
        seed = seed || {};
        if (mode === 'pair') { seed.a = seed.a ?? rnd(); seed.b = seed.b ?? rnd(); return `<div class="tfx-pair">${img(portrait('male', seed.a))}${img(portrait('female', seed.b))}</div>`; }
        if (mode === 'male' || mode === 'female') { seed.a = seed.a ?? rnd(); return img(portrait(mode, seed.a)); }
        return upload ? img('/' + upload) : '';
    }

    /* ───────── answers ───────── */
    let answers = {};                 // for the section that is running
    const sectionAnswers = {};        // finished sections
    const results = {};               // section → server result / saved submission

    /* ───────── instructions typed by the admin ("Question sequence management") ───────── */
    function instrBanner(mod, from, to) {
        const list = (mod.instructions || []).filter(b => b.from <= to && b.to >= from);
        return list.map(b => `<div class="tfx-instr">${b.text}</div>`).join('');
    }

    /* ═════════════ READING ═════════════ */
    const KIND = { daily: 'Read in Daily Life', academic: 'Read an Academic Passage' };

    function passageHtml(it) {
        const t = it.title ? `<h3 class="tfx-ptitle">${esc(it.title)}</h3>` : '';
        const body = `<div class="tfx-body">${it.passage || ''}</div>`;
        switch (it.style) {
            case 'notice': return `<div class="tfx-notice"><div class="tfx-notice__in">${it.title ? `<h3>${esc(it.title)}</h3>` : ''}${it.subtitle ? `<p class="tfx-notice__sub">${esc(it.subtitle)}</p>` : ''}${body}</div></div>`;
            case 'post': return `<div class="tfx-phone"><div class="tfx-phone__bar"><i></i><b></b><i></i></div><div class="tfx-phone__screen"><div class="tfx-post__who"><span class="tfx-avatar"></span><strong>${esc(it.author)}</strong></div>${body}<div class="tfx-post__acts"><span>👍 Like</span><span>💬 Comment</span></div></div></div>`;
            case 'email': return `<div class="tfx-mail tfx-mail--${it.color === 'brown' ? 'brown' : 'teal'}">${[['To', it.to], ['From', it.from], ['Date', it.date], ['Subject', it.subject]].filter(r => r[1]).map(r => `<div class="tfx-mail__row"><span>${r[0]}:</span><div>${esc(r[1])}</div></div>`).join('')}<div class="tfx-mail__body">${body}</div></div>`;
            case 'picture': return `${t}${it.image ? `<img class="tfx-pimg" src="/${esc(it.image)}" alt="">` : ''}`;
            default: return `${t}${body}`;
        }
    }
    /** Mark the vocabulary word of a question inside the passage. */
    function highlight(root, word) {
        if (!word) return;
        const w = document.createTreeWalker(root, NodeFilter.SHOW_TEXT); let n;
        const re = new RegExp('\\b' + word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'i');
        while ((n = w.nextNode())) {
            const m = re.exec(n.nodeValue); if (!m) continue;
            const r = n.splitText(m.index); r.splitText(m[0].length);
            const mk = document.createElement('mark'); mk.className = 'tfx-hl'; r.parentNode.replaceChild(mk, r); mk.appendChild(r); return;
        }
    }
    function optionsHtml(q) {
        return q.options.map((o, i) => `<label class="tfx-opt"><input type="radio" name="q${q.n}" value="${i}" ${answers[q.key] === String(i) ? 'checked' : ''}><span class="tfx-opt__l">${letter(i)}</span><span>${esc(o)}</span></label>`).join('');
    }
    function bindOptions(box, q) { $$('input[type=radio]', box).forEach(r => r.addEventListener('change', () => { answers[q.key] = r.value; touch(); })); }

    function readingScreen(mod, it) {
        const wrap = h(null, 'tfx-screen');
        if (it.type === 'words') {
            const last = it.n + it.count - 1;
            wrap.innerHTML = `${instrBanner(mod, it.n, last)}<div class="tfx-card tfx-card--words"><p class="tfx-kind">Complete the Words${it.count ? ` · Questions ${it.n}–${last}` : ''}</p><h3 class="tfx-ptitle">${esc(it.title || 'Fill in the missing letters in the paragraph.')}</h3><div class="tfx-para">${it.html}</div></div>`;
            $$('.tf-blank', wrap).forEach(sp => {
                const idx = +sp.dataset.b, len = +sp.dataset.len, key = it.id + ':' + (idx + 1), saved = (answers[key] || '').padEnd(len, ' ').split('');
                sp.innerHTML = `<span class="tfx-pre">${esc(sp.dataset.pre)}</span>`;
                const boxes = [];
                for (let i = 0; i < len; i++) {
                    const inp = h(null, 'tfx-letter', 'input'); inp.maxLength = 1; inp.autocomplete = 'off'; inp.spellcheck = false; inp.value = saved[i].trim(); inp.setAttribute('aria-label', 'Missing letter ' + (i + 1));
                    inp.addEventListener('input', () => { if (inp.value) { const all = $$('.tfx-letter', wrap), at = all.indexOf(inp); if (all[at + 1]) all[at + 1].focus(); } answers[key] = boxes.map(b => b.value || ' ').join('').replace(/ +$/, ''); touch(); });
                    inp.addEventListener('keydown', e => { if (e.key === 'Backspace' && !inp.value) { const all = $$('.tfx-letter', wrap), at = all.indexOf(inp); if (all[at - 1]) all[at - 1].focus(); } });
                    boxes.push(inp); sp.appendChild(inp);
                }
                sp.insertAdjacentHTML('beforeend', `<span class="tfx-suf">${esc(sp.dataset.suf)}</span>`);
            });
            $('.tfx-card--words', wrap).appendChild(flagBtn(Array.from({ length: it.count }, (_, b) => it.id + ':' + (b + 1))));
            return { node: wrap, range: [it.n, last] };
        }
        const from = it.questions[0] ? it.questions[0].n : 0, to = it.questions.length ? it.questions[it.questions.length - 1].n : 0;
        wrap.innerHTML = `${instrBanner(mod, from, to)}<div class="tfx-split"><div class="tfx-left"><p class="tfx-kind">${KIND[it.kind] || ''}</p><div class="tfx-passage">${passageHtml(it)}</div></div>
            <div class="tfx-right">${it.questions.map(q => `<div class="tfx-q" data-q="${q.n}"><p class="tfx-qn">Question ${q.n}</p><p class="tfx-qs">${esc(q.statement)}</p><div class="tfx-opts">${optionsHtml(q)}</div></div>`).join('')}</div></div>`;
        it.questions.forEach(q => { const qb = $(`[data-q="${q.n}"]`, wrap); bindOptions(qb, q); qb.appendChild(flagBtn([q.key])); highlight($('.tfx-passage', wrap), q.highlight); });
        return { node: wrap, range: [from, to] };
    }

    function navBar(parts) {
        const bar = h(null, 'tfx-nav');
        parts.forEach(p => { if (!p) return; const b = h(p.label, 'btn ' + (p.cls || 'btn-primary'), 'button'); b.type = 'button'; b.addEventListener('click', p.fn); bar.appendChild(b); });
        return bar;
    }

    function readingModule(mod, sec) {
        return new Promise(resolve => {
            let i = 0, ended = false;
            const qs = [];
            mod.items.forEach((it, idx) => {
                if (it.type === 'words') {
                    $$('.tf-blank', h(it.html)).map(sp => +sp.dataset.len).forEach((len, b) => { const key = it.id + ':' + (b + 1); qs.push({ n: it.n + b, key, screen: idx, done: () => (answers[key] || '').replace(/ /g, '').length >= len }); });
                } else it.questions.forEach(q => qs.push({ n: q.n, key: q.key, screen: idx, done: () => answers[q.key] != null }));
            });
            const finish = () => { if (ended) return; ended = true; stopClock(); panel.hide(); resolve(); };
            const go = q => { i = q.screen; draw(); };
            const review = () => {
                const done = qs.filter(q => q.done()).length, fl = qs.filter(q => flags.has(q.key)).length;
                const page = h(null, 'tfx-page tfx-page--narrow');
                page.innerHTML = `<div class="tfx-head"><span>${LABEL.reading} · Module ${mod.module}</span><span>Review</span></div>
                    <div class="tfx-card tfx-review"><h2>Review your answers</h2><p><strong>${done}</strong> answered · <strong>${qs.length - done}</strong> not answered · <strong>${fl}</strong> flagged</p>
                    <div class="tfx-foot__nums">${qs.map(q => `<button type="button" class="tfx-qb ${panel.cls(q)}" data-rq="${q.n}">${q.n}</button>`).join('')}</div>
                    <p class="tfx-hint">Click a number to go back to that question. You can only go back inside this module.</p></div>`;
                $$('[data-rq]', page).forEach(b => b.addEventListener('click', () => go(qs.find(q => q.n === +b.dataset.rq))));
                page.appendChild(navBar([{ label: '← Return to questions', cls: 'btn-ghost', fn: () => draw() }, { label: 'Finish module', fn: () => { if (confirm('Finish this module? You cannot change your answers afterwards.')) finish(); } }]));
                panel.refresh([]); show(page);
            };
            panel.show('Module ' + mod.module, qs, { go, review });
            if (mod.minutes > 0) startClock(mod.minutes * 60, finish);
            const draw = () => {
                const it = mod.items[i], sc = readingScreen(mod, it), page = h(null, 'tfx-page');
                page.appendChild(h(`<span>${LABEL.reading} · Module ${mod.module}</span><span>${sc.range[0] ? `Questions ${sc.range[0]}–${sc.range[1]} of ${mod.total}` : ''}</span>`, 'tfx-head'));
                page.appendChild(sc.node);
                const last = i === mod.items.length - 1;
                page.appendChild(navBar([
                    i > 0 ? { label: '← Back', cls: 'btn-ghost', fn: () => { i--; draw(); } } : null,
                    { label: last ? 'Review & finish' : 'Next →', fn: () => { if (!last) { i++; draw(); } else review(); } },
                ]));
                panel.refresh(Array.from({ length: Math.max(0, sc.range[1] - sc.range[0] + 1) }, (_, k) => sc.range[0] + k)); show(page);
            };
            draw();
        });
    }

    async function runReading(sec) {
        for (const mod of sec.modules) {
            if (!mod.items.length) continue;
            await gate(`<span class="pill pill--free">Module ${mod.module}</span><h2>Reading – Module ${mod.module}</h2><p>${mod.total} questions${mod.minutes ? ' · ' + mod.minutes + ' minutes' : ''}.</p>
                <ul class="exam-tips"><li>Read each text and answer the questions.</li><li>Use Back, Next or the question numbers at the bottom to move around this module. Click <strong>Flag</strong> on a question to come back to it from the Review screen.</li><li>You cannot return to a module once you finish it.</li><li>When the time ends, the module closes on its own.</li></ul>`, 'Start module ' + mod.module, 'Reading – Module ' + mod.module);
            await readingModule(mod, sec);
        }
    }

    /* ═════════════ LISTENING ═════════════ */
    function playAudio(src, area) {
        return new Promise(res => {
            if (!src) { res(); return; }
            const a = new Audio('/' + src); a.volume = prefs.volume / 100; currentAudio = a; const status = area.querySelector('.tfx-status');
            let over = false; const done = () => { if (!over) { over = true; if (currentAudio === a) currentAudio = null; res(); } };
            a.addEventListener('ended', done); a.addEventListener('error', () => { if (status) status.textContent = 'The recording could not be played.'; setTimeout(done, 1500); });
            if (status) status.textContent = '🔊 Listen carefully…';
            a.play().catch(() => {
                if (status) status.innerHTML = '<button type="button" class="btn btn-primary">▶ Play recording</button>';
                status.querySelector('button').addEventListener('click', () => { status.textContent = '🔊 Listen carefully…'; a.play().catch(done); });
            });
        });
    }
    const qBar = h(null);
    function askQuestion(q, pic, mod, state) {
        return new Promise(res => {
            if (state.ended) { res(); return; }
            const page = h(null, 'tfx-page tfx-page--narrow');
            page.innerHTML = `<div class="tfx-head"><span>${LABEL.listening} · Module ${mod.module}</span><span>Question ${q.n} of ${mod.total}</span></div>${instrBanner(mod, q.n, q.n)}
                <div class="tfx-card tfx-ask">${pic ? `<div class="tfx-askpic">${pic}</div>` : ''}<div><p class="tfx-qs tfx-qs--big">${esc(q.statement)}</p><div class="tfx-opts">${optionsHtml(q)}</div></div></div>`;
            $('.tfx-ask', page).appendChild(flagBtn([q.key]));
            panel.refresh([q.n]);
            const next = () => { state.cancel = null; res(); };
            const nb = navBar([{ label: 'Next →', fn: next }]); nb.querySelector('button').disabled = true; page.appendChild(nb);
            bindOptions(page, q); $$('input[type=radio]', page).forEach(r => r.addEventListener('change', () => { nb.querySelector('button').disabled = false; }));
            if ($('input:checked', page)) nb.querySelector('button').disabled = false;
            state.cancel = next; show(page);
        });
    }
    async function listenBlock(mod, state, media, questions, label) {
        const seed = {};
        const picHtml = photo(media.image_mode, media.image, seed);
        const page = h(`<div class="tfx-head"><span>${LABEL.listening} · Module ${mod.module}</span><span>${esc(label)}</span></div>
            <div class="tfx-card tfx-listen">${picHtml ? `<div class="tfx-bigpic">${picHtml}</div>` : ''}<p class="tfx-status"></p></div>`, 'tfx-page tfx-page--narrow');
        if (state.ended) return;
        show(page);
        await Promise.race([playAudio(media.audio, page), new Promise(r => { state.cancel = r; })]);
        const small = picHtml ? `<div class="tfx-thumb">${picHtml}</div>` : '';
        for (const q of questions) await askQuestion(q, small, mod, state);
    }
    function listeningModule(mod) {
        return new Promise(async resolve => {
            const state = { ended: false, cancel: null };
            const qs = [];
            mod.items.forEach(it => it.questions.forEach(q => qs.push({ n: q.n, key: q.key, done: () => answers[q.key] != null })));
            panel.show('Module ' + mod.module, qs, {});
            const finish = () => { if (state.ended) return; state.ended = true; stopClock(); panel.hide(); if (state.cancel) state.cancel(); resolve(); };
            if (mod.minutes > 0) startClock(mod.minutes * 60, finish);
            for (const it of mod.items) {
                if (state.ended) break;
                if (it.type === 'response') {
                    for (const q of it.questions) { if (state.ended) break; await listenBlock(mod, state, { audio: q.audio, image: q.image, image_mode: q.image_mode }, [q], 'Listen and Choose a Response'); }
                } else {
                    await listenBlock(mod, state, it, it.questions, it.title || { conversation: 'Listen to a conversation', announcement: 'Listen to an announcement', talk: 'Listen to an academic talk' }[it.type]);
                }
            }
            finish();
        });
    }
    async function runListening(sec) {
        for (const mod of sec.modules) {
            if (!mod.items.length) continue;
            await gate(`<span class="pill pill--free">Module ${mod.module}</span><h2>Listening – Module ${mod.module}</h2><p>${mod.total} questions${mod.minutes ? ' · ' + mod.minutes + ' minutes' : ''}.</p>
                <ul class="exam-tips"><li>Use headphones or speakers. Each recording plays once.</li><li>The picture and recording come first. The questions appear when the recording ends.</li><li>You cannot go back to earlier questions. The numbers at the bottom show your progress.</li></ul>${volumeHtml()}`, 'Start module ' + mod.module, 'Listening – Module ' + mod.module);
            await listeningModule(mod);
        }
    }

    /* ═════════════ WRITING ═════════════ */
    /** Build a Sentence: drag (or click) the words into the blanks. */
    function buildScreen(it) {
        const parts = it.answer.split('#'), slots = Math.max(0, parts.length - 1);
        it._order = it._order || shuffle(it.options.map((o, i) => i));
        const saved = Array.isArray(answers[it.key]) ? answers[it.key] : [];
        const used = [], placed = Array.from({ length: slots }, (_, k) => { const w = saved[k]; if (!w) return null; const idx = it.options.findIndex((o, i) => o === w && !used.includes(i)); if (idx >= 0) used.push(idx); return idx >= 0 ? idx : null; });
        const wrap = h(null, 'tfx-card tfx-build');
        const save = () => { answers[it.key] = placed.map(p => p == null ? '' : it.options[p]); };
        const render = () => {
            let s = '';
            parts.forEach((p, k) => { s += `<span>${esc(p)}</span>`; if (k < slots) s += `<span class="tfx-slot ${placed[k] != null ? 'is-full' : ''}" data-slot="${k}" ${placed[k] != null ? 'draggable="true"' : ''}>${placed[k] != null ? esc(it.options[placed[k]]) : ''}</span>`; });
            wrap.innerHTML = `<p class="tfx-qs tfx-qs--big">${esc(it.statement)}</p><div class="tfx-sentence">${s}</div><p class="tfx-hint">Drag the words into the blanks (or click a word). Click a word in a blank to remove it.</p>
                <div class="tfx-bank" data-bank>${it._order.map(i => placed.includes(i) ? `<span class="tfx-chip is-used">${esc(it.options[i])}</span>` : `<span class="tfx-chip" draggable="true" data-chip="${i}">${esc(it.options[i])}</span>`).join('')}</div>`;
            $$('[data-chip]', wrap).forEach(c => {
                c.addEventListener('click', () => { const k = placed.indexOf(null); if (k >= 0) { placed[k] = +c.dataset.chip; save(); render(); } });
                c.addEventListener('dragstart', e => { e.dataTransfer.setData('text/plain', 'c:' + c.dataset.chip); });
            });
            $$('[data-slot]', wrap).forEach(sl => {
                const k = +sl.dataset.slot;
                sl.addEventListener('click', () => { if (placed[k] != null) { placed[k] = null; save(); render(); } });
                sl.addEventListener('dragstart', e => { e.dataTransfer.setData('text/plain', 's:' + k); });
                sl.addEventListener('dragover', e => { e.preventDefault(); sl.classList.add('is-over'); });
                sl.addEventListener('dragleave', () => sl.classList.remove('is-over'));
                sl.addEventListener('drop', e => {
                    e.preventDefault(); const [t, v] = (e.dataTransfer.getData('text/plain') || '').split(':'); const val = +v;
                    if (t === 'c') { placed[k] = val; } else if (t === 's' && val !== k) { const tmp = placed[k]; placed[k] = placed[val]; placed[val] = tmp; }
                    save(); render();
                });
            });
            const bank = $('[data-bank]', wrap);
            bank.addEventListener('dragover', e => e.preventDefault());
            bank.addEventListener('drop', e => { e.preventDefault(); const [t, v] = (e.dataTransfer.getData('text/plain') || '').split(':'); if (t === 's') { placed[+v] = null; save(); render(); } });
        };
        render(); save();
        return wrap;
    }

    /** Text box with a word count and the time. */
    function editorBox(key, ph) {
        const box = h(`<div class="tfx-tools">            <span class="tfx-grow"></span><span>Words: <b data-words>0</b></span><span class="tfx-time">⏱ <b class="tfx-clock">${timerText.textContent}</b></span></div>
            <textarea class="tfx-text" spellcheck="false" autocomplete="off" autocorrect="off" placeholder="${esc(ph || 'Type your response here…')}"></textarea>`, 'tfx-editor');
        const ta = $('textarea', box), wc = $('[data-words]', box);
        ta.value = answers[key] || ''; wc.textContent = words(ta.value);
        ta.addEventListener('input', () => { answers[key] = ta.value; wc.textContent = words(ta.value); });
        return box;
    }
    function emailScreen(it) {
        const wrap = h(`<div class="tfx-split"><div class="tfx-left"><div class="tfx-card"><p class="tfx-kind">Write an Email</p>
            ${it.to ? `<p class="tfx-meta"><b>To:</b> ${esc(it.to)}</p>` : ''}${it.subject ? `<p class="tfx-meta"><b>Subject:</b> ${esc(it.subject)}</p>` : ''}<div class="tfx-body">${it.statement || ''}</div></div></div>
            <div class="tfx-right"><div class="tfx-instr tfx-instr--plain">${it.instructions || ''}</div></div></div>`);
        $('.tfx-right', wrap).appendChild(editorBox(it.id));
        return wrap;
    }
    function discussionScreen(it) {
        const box = (cls, who, mode, img, post) => `<div class="tfx-disc tfx-disc--${cls}"><div class="tfx-disc__pic">${photo(mode, img, {})}</div><div class="tfx-disc__post"><p class="tfx-disc__who">${who}</p>${post}</div></div>`;
        const wrap = h(`<div class="tfx-split"><div class="tfx-left"><p class="tfx-kind">Write for an Academic Discussion</p>
            ${box('white', 'Professor', it.professor_image_mode, it.professor_image, it.professor_post || '')}${box('blue', 'Student', it.student1_image_mode, it.student1_image, it.student1_post || '')}${box('green', 'Student', it.student2_image_mode, it.student2_image, it.student2_post || '')}</div>
            <div class="tfx-right"><div class="tfx-instr tfx-instr--plain">${it.instructions || ''}</div></div></div>`);
        $('.tfx-right', wrap).appendChild(editorBox(it.id));
        return wrap;
    }
    const WTYPE = { build: 'Build a Sentence', email: 'Write an Email', discussion: 'Write for an Academic Discussion' };
    function writingGroup(g, minutes, mod) {
        return new Promise(resolve => {
            let i = 0, ended = false;
            const finish = () => { if (ended) return; ended = true; stopClock(); resolve(); };
            const draw = () => {
                const it = g.items[i], page = h(null, 'tfx-page');
                const node = it.type === 'build' ? buildScreen(it) : it.type === 'email' ? emailScreen(it) : discussionScreen(it);
                page.appendChild(h(`<span>${LABEL.writing} · ${WTYPE[it.type]}</span><span>${it.type === 'build' ? `Question ${i + 1} of ${g.items.length}` : ''}</span>`, 'tfx-head'));
                page.appendChild(instrBanner(mod, it.n, it.n) ? h(instrBanner(mod, it.n, it.n)) : h(''));
                page.appendChild(node);
                const last = i === g.items.length - 1;
                page.appendChild(navBar([{ label: last ? (g.type === 'build' ? 'Finish' : 'Submit') : 'Next →', fn: () => { if (!last) { i++; draw(); } else finish(); } }]));
                show(page);
            };
            if (minutes > 0) startClock(minutes * 60, finish);
            draw();
        });
    }
    async function runWriting(sec) {
        panel.hide();
        const mod = sec.modules[0], groups = [];
        mod.items.forEach(it => { const g = groups[groups.length - 1]; if (it.type === 'build' && g && g.type === 'build') g.items.push(it); else groups.push({ type: it.type, items: [it] }); });
        for (const g of groups) {
            const mins = (sec.minutes || {})[g.type] || 0;
            await gate(`<span class="pill pill--free">${WTYPE[g.type]}</span><h2>${WTYPE[g.type]}</h2><p>${g.type === 'build' ? g.items.length + ' questions' : '1 task'}${mins ? ' · ' + mins + ' minutes' : ''}.</p>
                <ul class="exam-tips">${g.type === 'build' ? '<li>Put the words in the correct order. You get the mark only if the whole sentence is correct.</li>' : '<li>Spelling is not corrected for you.</li>'}<li>When the time ends, your answer is saved and the next screen opens.</li></ul>`, 'Start', 'Writing – ' + WTYPE[g.type]);
            await writingGroup(g, mins, mod);
        }
    }

    /* ═════════════ SPEAKING ═════════════ */
    let mic = null;
    const recordings = {};
    async function getMic() {
        while (!mic) {
            try { mic = await navigator.mediaDevices.getUserMedia({ audio: true }); }
            catch (_) { await gate('<h2>Microphone needed</h2><p>Please allow your browser to use the microphone (click <strong>Allow</strong>), then try again. Speaking cannot be recorded without it.</p>', 'Try again', 'Speaking'); }
        }
    }

    /* Live sound waves: bars that move with the student's voice while recording */
    function startWave(mediaStream, host) {
        if (!host || !mediaStream) return () => {};
        try {
            if (!document.getElementById('sw-style')) {
                const st = document.createElement('style'); st.id = 'sw-style';
                st.textContent = '.sw-bars{display:flex;align-items:center;justify-content:center;gap:4px;height:100%;width:100%}.sw-bars i{display:block;width:6px;min-height:6px;height:6px;border-radius:3px;background:currentColor;transition:height .08s linear}';
                document.head.appendChild(st);
            }
            const ctx = new (window.AudioContext || window.webkitAudioContext)(), an = ctx.createAnalyser();
            an.fftSize = 128; ctx.createMediaStreamSource(mediaStream).connect(an);
            host.innerHTML = '<span class="sw-bars">' + '<i></i>'.repeat(7) + '</span>';
            host.style.animation = 'none';
            const bars = host.querySelectorAll('.sw-bars i'), data = new Uint8Array(an.frequencyBinCount);
            let alive = true;
            const loop = () => {
                if (!alive) return;
                an.getByteFrequencyData(data);
                bars.forEach((b, k) => { const v = data[2 + k * 3] / 255; b.style.height = Math.max(6, Math.round(v * 56)) + 'px'; });
                requestAnimationFrame(loop);
            };
            loop();
            return () => { alive = false; try { ctx.close(); } catch (_) {} };
        } catch (_) { return () => {}; }
    }
    function speakItem(it, shared, idx, total) {
        return new Promise(async resolve => {
            const src = it.type === 'interview' ? shared : it;
            const pic = photo(src.image_mode || 'upload', src.image, src.seed);
            const page = h(`<div class="tfx-head"><span>${LABEL.speaking} · ${it.type === 'repeat' ? 'Listen and Repeat' : 'Take an Interview'}</span><span>Question ${idx} of ${total}</span></div>
                <div class="tfx-card tfx-listen">${pic ? `<div class="tfx-bigpic">${pic}</div>` : ''}${it.statement ? `<p class="tfx-qs tfx-qs--big">${esc(it.statement)}</p>` : ''}<p class="tfx-status"></p>
                </div>`, 'tfx-page tfx-page--narrow');
            show(page);
            await playAudio(it.audio, page);
            // Same as IELTS Speaking: "Start speaking now" + beep, then "Recording…" with a Stop button
            $('.tfx-status', page).innerHTML = '<span class="tfx-cue">Start speaking now</span>';
            await new Promise(r => {
                let done = false; const end = () => { if (!done) { done = true; r(); } };
                setTimeout(end, 4000);
                try {
                    const u = new SpeechSynthesisUtterance('Start speaking now.'); u.lang = 'en-GB'; u.rate = 0.85; u.onend = end; u.onerror = end;
                    speechSynthesis.cancel(); speechSynthesis.speak(u);
                } catch (_) { end(); }
            });
            await new Promise(r => {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)(), o = ctx.createOscillator(), g = ctx.createGain();
                    o.frequency.value = 880; g.gain.value = 0.15; o.connect(g); g.connect(ctx.destination); o.start(); o.stop(ctx.currentTime + 0.35);
                    setTimeout(() => { ctx.close(); r(); }, 450);
                } catch (_) { r(); }
            });
            const mime = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg'].find(m => window.MediaRecorder && MediaRecorder.isTypeSupported(m)) || '';
            const rec = new MediaRecorder(mic, mime ? { mimeType: mime } : undefined), chunks = [];
            rec.ondataavailable = e => { if (e.data.size) chunks.push(e.data); };
            let stopped = false, limit = null;
            const stop = () => { if (stopped) return; stopped = true; clearTimeout(limit); if (typeof stopWave === 'function') stopWave(); rec.onstop = () => { recordings[it.id] = new Blob(chunks, { type: rec.mimeType || 'audio/webm' }); resolve(); }; rec.state !== 'inactive' ? rec.stop() : rec.onstop(); };
            $('.tfx-status', page).innerHTML = '<span class="tfx-rec">● Recording…</span><span class="tfx-wave" style="display:block;width:150px;height:64px;margin:12px auto 0;color:#d7263d"></span>';
            const stopWave = startWave(mic, $('.tfx-wave', page));
            const sb = h('■ Stop', 'btn btn-primary', 'button'); sb.type = 'button'; sb.addEventListener('click', () => { sb.disabled = true; stop(); });
            page.appendChild(h(null, 'tfx-nav')).appendChild(sb);
            rec.start(); limit = setTimeout(stop, (it.seconds || 120) * 1000);   // maximum time, no timer shown
        });
    }
    async function runSpeaking(sec) {
        panel.hide();
        const mod = sec.modules[0], items = mod.items;
        await gate(`<h2>Speaking</h2><p>${items.length} questions. Your browser will ask to use the microphone – click <strong>Allow</strong>.</p><ul class="exam-tips"><li>Listen to each question. After you hear “Start speaking now”, your answer is recorded. Click <strong>Stop</strong> when you finish.</li><li>The recording also stops by itself when the time for the question is over.</li><li>Your recordings are saved to your account, and you can ask a teacher for feedback.</li></ul>${volumeHtml()}`, 'Allow microphone and start', 'Speaking');
        await getMic();
        const first = items.find(i => i.type === 'interview');
        const shared = first ? { image_mode: first.image_mode, image: first.image, seed: {} } : null;
        for (let i = 0; i < items.length; i++) await speakItem(items[i], shared, i + 1, items.length);
        if (mic) { mic.getTracks().forEach(t => t.stop()); mic = null; }
    }

    /* ═════════════ marking, saving and results ═════════════ */
    const post = (url, body) => fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', ...(body instanceof FormData ? {} : { 'Content-Type': 'application/json' }) }, body: body instanceof FormData ? body : JSON.stringify(body) })
        .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); });

    async function save(m) {
        const a = JSON.stringify(answers);
        const attempt = async fn => { for (;;) { try { return await fn(); } catch (e) { await gate('<h2>We could not save your answers</h2><p>Please check your internet connection and try again. Your answers are still on this screen.</p>', 'Try again', 'Saving'); } } };
        show(h('<div class="exam-intro"><h2>Saving your answers…</h2><p>Please wait a moment.</p></div>', 'exam-step exam-step--center'), 'Saving');
        const res = {};
        if (m !== 'speaking') { try { Object.assign(res, await post(U.score, { test_id: cfg.testId, section: m, mode: cfg.mode, answers: a })); } catch (_) { res.error = true; } }
        if (m === 'writing' || m === 'speaking') {
            res.sub = await attempt(() => {
                const fd = new FormData(); fd.append('test_id', cfg.testId); fd.append('module', m); fd.append('mode', cfg.mode); fd.append('answers', a);
                Object.entries(recordings).forEach(([id, blob]) => fd.append('recordings[' + id + ']', blob, id + '.webm'));
                return post(U.submit, fd);
            });
        }
        results[m] = res;
    }

    function resultBlock(m) {
        const r = results[m] || {}, box = h(null, 'tfx-result');
        let html = `<h2>${LABEL[m]}</h2>`;
        if (r.error) html += '<p class="tfx-warn">Your answers could not be marked right now. Please try again later.</p>';
        else if (m !== 'speaking' && r.rows) {
            if (m !== 'writing') html += `<p class="tfx-score"><strong>${r.score}</strong> / ${r.total} <span>correct</span></p><p class="tfx-note">This is your raw score. The conversion to the TOEFL score scale will be added soon.</p>`;
            else if (r.rows.length) html += `<p class="tfx-score"><strong>${r.score}</strong> / ${r.total} <span>sentences built correctly</span></p>`;
            if (r.rows.length) {
                let cur = null, rows = '';
                r.rows.forEach(x => {
                    if (x.module && x.module !== cur) { cur = x.module; rows += `<tr class="tfx-mod"><td colspan="5">Module ${cur}</td></tr>`; }
                    rows += `<tr class="${x.graded ? (x.ok ? 'is-ok' : 'is-bad') : 'is-skip'}"><td>${x.n}</td><td>${esc(x.label)}</td><td>${esc(x.yours)}</td><td>${x.ok && x.graded ? '' : esc(x.correct)}</td><td>${x.graded ? (x.ok ? '✓' : '✗') : '<small>not scored</small>'}</td></tr>`;
                });
                html += `<div class="tfx-table"><table><thead><tr><th>#</th><th>Question</th><th>Your answer</th><th>Correct answer</th><th></th></tr></thead><tbody>${rows}</tbody></table></div>`;
            }
        }
        if (r.sub) {
            html += `<div class="tfx-saved"><p>${m === 'writing' ? 'Your email and discussion answers are saved.' : 'Your recordings are saved.'} Ask a teacher to score them and give you feedback.</p>
                <button type="button" class="btn btn-primary" data-req="${esc(r.sub.request)}">Request feedback</button></div>`;
        }
        box.innerHTML = html;
        const rb = $('[data-req]', box);
        if (rb) rb.addEventListener('click', async () => { rb.disabled = true; try { await post(rb.dataset.req, {}); rb.parentNode.innerHTML = `<p class="tfx-ok">✓ Feedback requested. The teacher’s score and comments will appear in <a href="${U.portal}" target="_blank">your portal</a>.</p>`; } catch (_) { rb.disabled = false; alert('Sorry, the request could not be sent. Please try again.'); } });
        return box;
    }
    function showResults(mods) {
        const page = h(`<div class="tfx-head"><span>Results</span><span>${esc(cfg.name)}</span></div>`, 'tfx-page tfx-page--narrow');
        mods.forEach(m => page.appendChild(resultBlock(m)));
        if (!cfg.student) page.appendChild(h('<p class="tfx-note">Log in to save your results to your portal.</p>'));
        page.appendChild(navBar([{ label: 'Back to the tests', fn: () => { running = false; location.href = U.back; } }, { label: 'Try again', cls: 'btn-ghost', fn: () => { running = false; location.reload(); } }]));
        show(page, 'Results'); if (sid) sid.hidden = true; stopClock();
    }

    /* ═════════════ main flow ═════════════ */
    const RUN = { reading: runReading, listening: runListening, writing: runWriting, speaking: runSpeaking };
    async function runTest() {
        const secs = cfg.sections;
        await gate(`<span class="pill pill--free">${mock ? 'Full mock test' : 'Practice'}</span><h1>${esc(cfg.name)}</h1>
            <ol class="exam-plan">${secs.map(s => `<li><div><strong>${LABEL[s.section]}</strong><small>${esc(U.labels[s.section].parts)} · ${esc(U.labels[s.section].time)}</small></div></li>`).join('')}</ol>
            ${mock ? '<p>The sections run one after another. You cannot go back to an earlier section.</p>' : ''}
            ${volumeHtml()}<p class="tfx-hint">Put on your headphones and use <strong>Test sound</strong> to set a comfortable volume. You can change it later in the Menu.</p>
            ${cfg.student ? `<p class="exam-student">Logged in as <strong>${esc(cfg.student.name)}</strong> · Student ID <strong>${cfg.student.id}</strong>. Your Writing and Speaking answers are saved to your account.</p>` : ''}`, mock ? 'Start the test' : 'Start practice', mock ? 'Full mock test' : 'Practice');
        for (let i = 0; i < secs.length; i++) {
            const s = secs[i]; answers = {}; Object.keys(recordings).forEach(k => delete recordings[k]);
            if (i > 0) await gate(`<h2>Next: ${LABEL[s.section]}</h2><p>${esc(U.labels[s.section].parts)} · ${esc(U.labels[s.section].time)}</p>`, 'Start ' + LABEL[s.section], LABEL[s.section]);
            if (sid) sid.hidden = !['writing', 'speaking'].includes(s.section);
            await RUN[s.section](s);
            stopClock();
            await save(s.section);
        }
        running = false;
        showResults(secs.map(s => s.section));
    }
    runTest();
})();
