/*
 * Etude World – CELPIP Speaking test (student side).
 * One box: instructions → Tasks 1–8 → "your results will be in your portal" screen.
 *
 * Each task stays on ONE screen:   question (+ picture) → preparation countdown →
 *   "Start speaking now" (audio) → microphone recording countdown → done.
 * Task 5 first shows its own instructions screen, then one screen with:
 *   choose one of two options (60 s; the computer picks if time runs out) → the other option
 *   disappears → preparation → "Start speaking now" → recording.
 *
 * Speaking on its own (practice): Next and Back. Full mock test: no Back.
 * Each task is recorded only once; going Back shows (and plays) what was recorded.
 * Recordings are sent to the teacher (Admin → Evaluation Requests).
 */
(function () {
    const root = document.querySelector('[data-speaking]');
    if (!root) return;
    const $  = (s, el = root) => el.querySelector(s);
    const $$ = (s, el = root) => Array.from(el.querySelectorAll(s));

    let data = { intro: {}, tasks: [] };
    try { data = JSON.parse($('[data-sp-data]').textContent); } catch (_) {}
    const mock = root.dataset.mode === 'mock';

    const stage = $('[data-sp-stage]'), titleEl = $('[data-sp-title]'), timesEl = $('[data-sp-times]');
    const nextBtn = $('[data-sp-next]'), backBtn = $('[data-sp-back]'), contBtn = $('[data-sp-continue]');
    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const fmt = s => s >= 60 ? `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}` : String(s);

    // screens: intro, (Task 5 instructions), one per task, end
    const screens = [{ type: 'intro', title: 'CELPIP Speaking Test' }];
    data.tasks.forEach(t => {
        if (t.intro) screens.push({ type: 'taskintro', title: t.title, text: t.intro });
        screens.push({ type: 'task', ...t, state: 'new', choice: null });
    });
    screens.push({ type: 'end', title: 'Speaking test complete' });

    let cur = -1, tick = null, introAudio = null, cueAudio = null, stream = null, micError = '', recorder = null, cueToken = 0;
    const recordings = {};      // task number → File

    /* ---------- microphone ---------- */
    async function getMic() {
        if (stream || micError) return stream;
        if (!navigator.mediaDevices || !window.MediaRecorder) {
            micError = 'Your browser cannot record audio. Please use a recent version of Chrome, Edge, Firefox or Safari.';
            return null;
        }
        try {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch (e) {
            micError = 'We could not use your microphone. Please allow microphone access in your browser (the icon in the address bar) and reload the page.';
        }
        return stream;
    }
    const mimeType = () => ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4']
        .find(t => window.MediaRecorder && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(t)) || '';

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
    let stopWave = () => {};
    function startRecording(s) {
        if (!stream) return;
        const chunks = [];
        const type = mimeType();
        recorder = new MediaRecorder(stream, type ? { mimeType: type } : undefined);
        recorder.ondataavailable = e => { if (e.data && e.data.size) chunks.push(e.data); };
        recorder.onstop = () => {
            stopWave();
            const t = (recorder && recorder.mimeType) || type || 'audio/webm';
            const ext = t.includes('mp4') ? 'm4a' : t.includes('ogg') ? 'ogg' : 'webm';
            if (chunks.length) {
                recordings[s.task] = new File(chunks, `task-${s.task}.${ext}`, { type: t.split(';')[0] });
                s.url = URL.createObjectURL(recordings[s.task]);
            }
            recorder = null;
            if (screens[cur] === s) draw(s);
        };
        recorder.start(1000);
    }
    // Stopping is not instant: the last piece of sound arrives a moment later. pendingStop lets the end
    // screen wait for it, so a task stopped with "Next" on the last task is still sent to the teacher.
    let pendingStop = null;
    function stopRecording() {
        if (recorder && recorder.state !== 'inactive') {
            pendingStop = new Promise(res => recorder.addEventListener('stop', () => setTimeout(res, 0), { once: true }));
            recorder.stop();
        }
        return pendingStop || Promise.resolve();
    }

    /* ---------- "Start speaking now": uploaded audio, or the browser's own voice, then a short beep ---------- */
    function beep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const o = ctx.createOscillator(), g = ctx.createGain();
            o.frequency.value = 880; g.gain.value = 0.15;
            o.connect(g); g.connect(ctx.destination);
            o.start(); o.stop(ctx.currentTime + 0.35);
            return new Promise(r => setTimeout(() => { ctx.close(); r(); }, 450));
        } catch (_) { return Promise.resolve(); }
    }
    function playCue() {
        const token = ++cueToken;
        const done = new Promise(resolve => {
            let finished = false;
            const end = () => { if (!finished) { finished = true; resolve(); } };
            setTimeout(end, 6000);                                   // never wait longer than 6 s
            if (data.intro.cue) {
                cueAudio = new Audio(data.intro.cue);
                cueAudio.onended = end; cueAudio.onerror = end;
                cueAudio.play().catch(end);
            } else if (window.speechSynthesis && window.SpeechSynthesisUtterance) {
                const u = new SpeechSynthesisUtterance('Start speaking now.');
                u.lang = 'en-US'; u.rate = 1;
                u.onend = end; u.onerror = end;
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(u);
            } else {
                end();
            }
        });
        return done.then(() => (token === cueToken ? beep() : null)).then(() => token === cueToken);
    }
    function stopCue() {
        cueToken++;
        if (cueAudio) { cueAudio.pause(); cueAudio = null; }
        if (window.speechSynthesis) window.speechSynthesis.cancel();
    }

    /* ---------- drawing ---------- */
    const ICONS = {
        clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        mic:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/></svg>',
        check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m5 12 5 5 9-10"/></svg>',
        hand:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11V5a1.5 1.5 0 0 1 3 0v6M12 10V4a1.5 1.5 0 0 1 3 0v6M15 10V6a1.5 1.5 0 0 1 3 0v7a6 6 0 0 1-6 6h-1a6 6 0 0 1-5-2.7L3.5 13a1.5 1.5 0 0 1 2.4-1.8L9 14"/></svg>',
    };

    function panel(s) {
        const box = {
            new:    ['clock', 'Preparation Time', s.prep],
            choose: ['hand', 'Choose an option', s.left],
            prep:   ['clock', 'Preparation Time', s.left],
            cue:    ['mic', 'Start speaking now', ''],
            rec:    ['mic', 'Recording…', s.left],
            done:   ['check', 'Recording complete', ''],
        }[s.state];
        return `<div class="spx-panel is-${s.state}">
                <div class="spx-icon" aria-hidden="true">${ICONS[box[0]]}</div>
                <div class="spx-panel__body">
                    <p class="spx-panel__label">${box[1]}</p>
                    <p class="spx-panel__count" data-sp-count>${box[2] === '' ? '' : fmt(box[2])}</p>
                    ${s.state === 'done' && s.url ? `<audio controls src="${s.url}"></audio>` : ''}
                    ${s.state === 'done' && !s.url ? '<p class="spx-small">No recording was made for this task.</p>' : ''}
                </div>
            </div>`;
    }

    function optionCard(s, k) {
        const o = s.options[k];
        const chosen = s.choice === k;
        return `<button type="button" class="spx-option ${chosen ? 'is-chosen' : ''}" data-sp-choose="${k}" ${s.state !== 'choose' ? 'disabled' : ''}
                    aria-pressed="${chosen}">
                ${o.image ? `<img src="${esc(o.image)}" alt="Option ${k + 1}">` : ''}
                ${o.caption ? `<span class="spx-option__text">${o.caption}</span>` : ''}
                ${s.state === 'choose' ? `<span class="spx-option__pick">${chosen ? '✓ Selected' : 'Click to choose this option'}</span>` : ''}
            </button>`;
    }

    function draw(s) {
        if (s.type === 'intro' || s.type === 'taskintro') {
            stage.innerHTML = `<div class="lsx-text spx-instructions">${s.type === 'intro' ? (data.intro.text || '') : s.text}</div>`;
            return;
        }
        if (s.type === 'end') { drawEnd(); return; }

        let media = '';
        if (s.options) {
            if (s.state === 'choose' || s.state === 'new') {
                media = `<div class="spx-options">${optionCard(s, 0)}${optionCard(s, 1)}</div>`;
            } else {
                media = `<div class="spx-options is-single">${optionCard(s, s.choice ?? 0)}</div>`;
            }
        } else if (s.image) {
            media = `<img class="spx-image" src="${esc(s.image)}" alt="">`;
        }
        const choosing = s.options && (s.state === 'choose' || s.state === 'new');
        stage.innerHTML = `
            <div class="spx-question"><span class="rdx-info" aria-hidden="true">i</span><div>${s.text || ''}
                ${s.options && choosing ? '<p class="spx-sub">If you do not choose an option, the computer will choose one for you. You do not need to speak for this part.</p>' : ''}</div></div>
            <hr class="spx-rule">
            ${choosing
                ? `${media}<div class="spx-below">${panel(s)}</div>`
                : media
                    ? `<div class="spx-split"><div class="spx-split__media">${media}</div><div class="spx-split__panel">${panel(s)}</div></div>`
                    : panel(s)}
            ${micError ? `<p class="spx-error">${esc(micError)}</p>` : ''}
            <p class="spx-note"><strong>*NOTE:</strong> Your answer is recorded and sent to your teacher for evaluation.</p>`;
        setTimes(s);
    }

    function setTimes(s) {
        if (s.type !== 'task') { timesEl.hidden = true; return; }
        timesEl.hidden = false;
        timesEl.innerHTML = s.options && (s.state === 'choose' || s.state === 'new')
            ? `Choose an option: <strong>${s.choose} seconds</strong>`
            : `Preparation: <strong>${s.prep} seconds</strong> Recording: <strong>${s.record} seconds</strong>`;
    }

    let sending = false, sent = false, sendError = '';
    function drawEnd() {
        const n = Object.keys(recordings).length;
        stage.innerHTML = `<div class="spx-end">
            <div class="exam-intro__icon ${sent ? 'exam-intro__icon--done' : ''}" aria-hidden="true">${sent ? '✓' : '…'}</div>
            <h3>${sent ? 'Your Speaking test has been submitted.' : sending ? 'Sending your recordings…' : sendError ? 'Your recordings could not be sent.' : 'Speaking test complete'}</h3>
            ${sent ? `<p>${n} recording${n === 1 ? '' : 's'} received. Your teacher will evaluate your speaking.</p>
                      <p class="spx-portal">Please check your results in <a href="${esc(root.dataset.portalUrl)}">your portal</a> after <strong>24 hours</strong>.</p>`
                : `<p class="muted-inline">${sendError ? esc(sendError) : 'Please wait a moment…'}</p>`}
            ${sendError ? '<button type="button" class="btn btn-primary" data-sp-retry>Try again</button>' : ''}
        </div>`;
        contBtn.hidden = !(sent || sendError);
        if (sent && !mock && window.EWResultsActions) window.EWResultsActions($('.spx-end', stage));
    }
    function send() {
        if (sending || sent) return;
        sending = true; sendError = ''; drawEnd();
        const answers = {};
        data.tasks.forEach(t => {
            const s = screens.find(x => x.type === 'task' && x.task === t.task);
            answers[t.task] = { recorded: !!recordings[t.task] };
            if (s && s.options) { answers[t.task].choice = (s.choice ?? 0) + 1; answers[t.task].auto = !!s.autoChosen; }
        });
        window.EWSubmit('speaking', answers, recordings).then(() => {
            sent = true;
            if (!mock && window.EWResultsPage) window.EWResultsPage();
        }).catch(err => {
            sendError = err.message + ' – check your internet connection and try again.';
        }).finally(() => {
            sending = false;
            if (screens[cur] && screens[cur].type === 'end') drawEnd();
            if (sent && contBtn.tagName === 'A') {
                const exam = document.querySelector('[data-exam]'); if (exam) exam.dispatchEvent(new CustomEvent('ew:finished'));
            }
        });
    }
    stage.addEventListener('click', e => {
        if (e.target.closest('[data-sp-retry]')) { send(); return; }
        const opt = e.target.closest('[data-sp-choose]');
        const s = screens[cur];
        if (opt && s && s.state === 'choose') {
            s.choice = Number(opt.dataset.spChoose);
            $$('[data-sp-choose]', stage).forEach(b => {
                const on = Number(b.dataset.spChoose) === s.choice;
                b.classList.toggle('is-chosen', on);
                b.setAttribute('aria-pressed', on);
                const pick = $('.spx-option__pick', b); if (pick) pick.textContent = on ? '✓ Selected' : 'Click to choose this option';
            });
        }
    });

    /* ---------- one task: (choose →) preparation → "start speaking now" → recording ---------- */
    function stop() { clearInterval(tick); tick = null; }

    function countdown(s, onDone) {
        const count = () => { const el = $('[data-sp-count]', stage); if (el) el.textContent = fmt(Math.max(0, s.left)); };
        tick = setInterval(() => {
            s.left -= 1;
            count();
            if (s.left <= 0) { stop(); onDone(); }
        }, 1000);
    }

    function phase(s, state) {
        if (screens[cur] !== s) return;
        s.state = state;
        if (state === 'choose') { s.left = s.choose; draw(s); countdown(s, () => {
            if (s.choice === null) { s.choice = Math.random() < 0.5 ? 0 : 1; s.autoChosen = true; }
            phase(s, 'prep');
        }); }
        else if (state === 'prep') { s.left = s.prep; draw(s); countdown(s, () => phase(s, 'cue')); }
        else if (state === 'cue') {
            draw(s);
            playCue().then(ok => { if (ok && screens[cur] === s && s.state === 'cue') phase(s, 'rec'); });
        }
        else if (state === 'rec') { s.left = s.record; startRecording(s); draw(s); stopWave = startWave(stream, $('.spx-panel.is-rec .spx-icon', stage)); countdown(s, () => { s.state = 'done'; stopRecording(); draw(s); }); }
        else draw(s);
    }

    async function show(i) {
        if (i < 0 || i >= screens.length) return;
        stop(); stopCue();
        cur = i;
        const s = screens[i];
        titleEl.textContent = s.title;
        nextBtn.hidden = s.type === 'end';
        backBtn.hidden = mock || i === 0 || s.type === 'end';
        contBtn.hidden = true;
        timesEl.hidden = true;
        window.scrollTo({ top: 0 });

        if (s.type === 'intro' || s.type === 'taskintro') {
            draw(s);
            if (s.type === 'intro' && data.intro.audio && !introAudio) { introAudio = new Audio(data.intro.audio); introAudio.play().catch(() => {}); }
            return;
        }
        if (introAudio) introAudio.pause();
        if (s.type === 'end') {
            draw(s);
            if (pendingStop) { await pendingStop; pendingStop = null; }      // wait for the last recording to be ready
            if (stream) stream.getTracks().forEach(t => t.stop());
            send();
            return;
        }
        await getMic();
        if (s.state === 'done') { draw(s); return; }
        phase(s, s.options ? 'choose' : 'prep');
    }

    // Leaving a task early: it stops for good (a task is recorded only once).
    function leaveTask() {
        const s = screens[cur];
        if (!s || s.type !== 'task' || s.state === 'done') return true;
        const msg = s.state === 'rec' ? 'Stop recording and move on? You can\'t record this task again.'
            : 'Leave this task? You won\'t be able to record it later.';
        if (s.state !== 'new' && !window.confirm(msg)) return false;
        stop(); stopCue();
        if (s.state === 'rec') stopRecording();
        if (s.options && s.choice === null) { s.choice = 0; s.autoChosen = true; }
        s.state = 'done';
        return true;
    }
    nextBtn.addEventListener('click', () => { if (leaveTask()) show(cur + 1); });
    backBtn.addEventListener('click', () => { if (leaveTask()) show(cur - 1); });

    // Start when the Speaking section opens; stop everything if it is left.
    const watch = () => {
        if (!root.hidden && cur === -1) show(0);
        if (root.hidden) { stop(); stopCue(); stopRecording(); if (introAudio) introAudio.pause(); }
    };
    new MutationObserver(watch).observe(root, { attributes: true, attributeFilter: ['hidden'] });
    watch();
})();
