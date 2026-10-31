/*
 * Etude World – IELTS Speaking test (student side). Everything runs in one box on one page.
 *
 *  Main Section (instructions) → Next
 *  Part 1: instructions → "Start test" → for each question:
 *          "Question N" → the question's AUDIO plays (uploaded in the admin panel; the text is not shown) →
 *          "Start speaking now" + beep → recorder with a Stop button (max 2 minutes, no timer shown)
 *          → next question. After the last one, "Part 2 ›" appears at the top right.
 *  Part 2: instructions → "Start Part 2" → topic card + 1 minute preparation →
 *          "Start speaking now" + beep → recorder with a 2-minute timer (topic stays on screen).
 *          Then "Part 3 ›" appears.
 *  Part 3: same as Part 1. Then the recordings are sent to the teacher
 *          (Admin → Evaluation Requests → IELTS Evaluation) and the student is told to check
 *          their portal after 24 hours.
 * The student cannot go back.
 */
(function () {
    const root = document.querySelector('[data-ielts-speaking]');
    if (!root) return;
    const $  = (s, el = root) => el.querySelector(s);
    const $$ = (s, el = root) => Array.from(el.querySelectorAll(s));

    let data = { intro: '', prep: 60, record: 120, parts: [] };
    try { data = JSON.parse($('[data-ip-data]').textContent); } catch (_) {}

    const stage = $('[data-ip-stage]'), titleEl = $('[data-ip-title]');
    const nextBtn = $('[data-ip-next]'), partBtn = $('[data-ip-partnext]');
    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const fmt = s => `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
    const wait = ms => new Promise(r => setTimeout(r, ms));

    const recordings = {};          // "p1-q1" → File
    const answered = [];            // what was asked, in order (sent with the recordings)
    let stream = null, micError = '', recorder = null, tick = null, started = false, finished = false;
    let token = 0;                  // changes whenever the screen changes, so old timers stop quietly

    const ICONS = {
        ear:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5 6 9H3v6h3l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M19 5a10 10 0 0 1 0 14"/></svg>',
        clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        mic:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/></svg>',
        check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m5 12 5 5 9-10"/></svg>',
    };

    /* ---------- progress chips in the top bar ---------- */
    function progress(part, done) {
        $$('[data-ip-progress]').forEach(li => {
            const p = Number(li.dataset.ipProgress);
            li.classList.toggle('is-current', p === part && !done);
            li.classList.toggle('is-done', p < part || (p === part && done));
        });
    }

    /* ---------- microphone ---------- */
    async function getMic() {
        if (stream) return stream;
        micError = '';
        if (!navigator.mediaDevices || !window.MediaRecorder) {
            micError = 'Your browser cannot record audio. Please use a recent version of Chrome, Edge, Firefox or Safari.';
            return null;
        }
        try { stream = await navigator.mediaDevices.getUserMedia({ audio: true }); }
        catch (e) { micError = 'We could not use your microphone. Please allow microphone access (the icon in the address bar), then click the button again.'; }
        return stream;
    }
    const mimeType = () => ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4']
        .find(t => window.MediaRecorder && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(t)) || '';

    /** Record until stop() is called. Resolves with the File (or null). */

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
    function record(key) {
        return new Promise(resolve => {
            if (!stream) { resolve(null); return; }
            const chunks = [], type = mimeType();
            recorder = new MediaRecorder(stream, type ? { mimeType: type } : undefined);
            recorder.ondataavailable = e => { if (e.data && e.data.size) chunks.push(e.data); };
            recorder.onstop = () => {
                const t = (recorder && recorder.mimeType) || type || 'audio/webm';
                const ext = t.includes('mp4') ? 'm4a' : t.includes('ogg') ? 'ogg' : 'webm';
                recorder = null;
                if (!chunks.length) { resolve(null); return; }
                recordings[key] = new File(chunks, `${key}.${ext}`, { type: t.split(';')[0] });
                resolve(recordings[key]);
            };
            recorder.start(1000);
            const stopWave = startWave(stream, $('.spx-panel.is-rec .spx-icon', stage));
            recorder.addEventListener('stop', stopWave);
        });
    }
    function stopRecording() { if (recorder && recorder.state !== 'inactive') recorder.stop(); }

    /* ---------- sounds ---------- */
    let player = null;
    /** Play an audio file to the end. Resolves true when it finished, false if it could not play. */
    function play(url) {
        return new Promise(resolve => {
            if (player) player.pause();
            player = new Audio(url);
            player.onended = () => resolve(true);
            player.onerror = () => resolve(false);
            player.play().catch(() => resolve(false));
        });
    }
    function stopSound() {
        if (player) { player.pause(); player = null; }
        if (window.speechSynthesis) speechSynthesis.cancel();
    }

    /** "Start speaking now": the uploaded audio, otherwise the browser says it (slowly). */
    function cue() {
        if (data.cue) return play(data.cue);
        return new Promise(resolve => {
            if (!window.speechSynthesis || !window.SpeechSynthesisUtterance) { resolve(); return; }
            let done = false;
            const end = () => { if (!done) { done = true; resolve(); } };
            setTimeout(end, 5000);
            const u = new SpeechSynthesisUtterance('Start speaking now.');
            const v = speechSynthesis.getVoices().find(x => /^en/i.test(x.lang));
            if (v) u.voice = v;
            u.lang = v ? v.lang : 'en-GB';
            u.rate = 0.85;
            u.onend = end; u.onerror = end;
            speechSynthesis.cancel();
            speechSynthesis.speak(u);
        });
    }
    function beep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const o = ctx.createOscillator(), g = ctx.createGain();
            o.frequency.value = 880; g.gain.value = 0.15;
            o.connect(g); g.connect(ctx.destination);
            o.start(); o.stop(ctx.currentTime + 0.35);
            return wait(450).then(() => ctx.close());
        } catch (_) { return Promise.resolve(); }
    }

    /* ---------- drawing ---------- */
    function panel(state, label, count = '', extra = '') {
        const icon = { ask: 'ear', retry: 'ear', prep: 'clock', cue: 'mic', rec: 'mic', done: 'check' }[state];
        return `<div class="spx-panel is-${state}">
                <div class="spx-icon" aria-hidden="true">${ICONS[icon]}</div>
                <div class="spx-panel__body">
                    <p class="spx-panel__label">${label}</p>
                    <p class="spx-panel__count" data-ip-count>${count}</p>
                    ${extra}
                </div>
            </div>`;
    }
    const stopButton = '<button type="button" class="btn ipx-stop" data-ip-stop><span class="ipx-stop__sq" aria-hidden="true"></span> Stop</button>';

    function setHead(title, showNext) {
        titleEl.textContent = title;
        nextBtn.hidden = !showNext;
    }

    /* ---------- screens ---------- */
    function showMain() {
        token++;
        setHead('IELTS Speaking Test', true);
        partBtn.hidden = true;
        stage.innerHTML = `<div class="lsx-text spx-instructions">${data.intro || ''}</div>`;
    }

    function showPartIntro(i, error = '') {
        const t = ++token;
        const part = data.parts[i];
        progress(part.part, false);
        partBtn.hidden = true;
        setHead(part.title, false);
        stage.innerHTML = `<div class="lsx-text spx-instructions">${part.intro || ''}</div>
            ${error ? `<p class="spx-error">${esc(error)}</p>` : ''}
            <div class="ipx-start"><button type="button" class="btn btn-primary btn-lg" data-ip-start>${part.part === 1 ? 'Start test' : 'Start Part ' + part.part}</button></div>`;
        $('[data-ip-start]', stage).addEventListener('click', async e => {
            e.currentTarget.disabled = true;
            started = true;
            await getMic();
            if (t !== token) return;
            if (!stream) { showPartIntro(i, micError); return; }
            if (part.spoken) askQuestion(i, 0); else longTurn(i);
        });
        window.scrollTo({ top: 0 });
    }

    /* Parts 1 and 3: question audio → cue → record (Stop button, 2 minutes max, no timer shown) */
    async function askQuestion(i, n) {
        const t = ++token;
        const part = data.parts[i], audio = part.questions[n], key = `p${part.part}-q${n + 1}`;
        const head = `<p class="ipx-qnum">Question ${n + 1}</p>`;

        stage.innerHTML = head + panel('ask', 'Listen to the question…');
        await wait(600);
        if (t !== token) return;
        let ok = await play(audio);
        if (t !== token) return;
        if (!ok) {
            // The browser blocked or could not load the audio: let the student start it themselves.
            stage.innerHTML = head + panel('retry', 'The question could not play automatically.', '',
                '<button type="button" class="btn btn-primary" data-ip-play>▶ Play the question</button>');
            await new Promise(r => $('[data-ip-play]', stage).addEventListener('click', r, { once: true }));
            if (t !== token) return;
            stage.innerHTML = head + panel('ask', 'Listen to the question…');
            ok = await play(audio);
            if (t !== token) return;
        }

        stage.innerHTML = head + panel('cue', 'Start speaking now');
        await cue();
        if (t !== token) return;
        await beep();
        if (t !== token) return;

        stage.innerHTML = head + panel('rec', 'Recording…', '', stopButton);
        const result = record(key);
        const limit = setTimeout(stopRecording, data.record * 1000);      // 2 minutes at most
        $('[data-ip-stop]', stage).addEventListener('click', e => { e.currentTarget.disabled = true; stopRecording(); });
        await result;
        clearTimeout(limit);
        answered.push({ key, part: part.part, number: n + 1 });
        if (t !== token) return;

        stage.innerHTML = head + panel('done', 'Answer saved');
        await wait(1200);
        if (t !== token) return;
        if (n + 1 < part.questions.length) askQuestion(i, n + 1);
        else partDone(i);
    }

    /* Part 2: topic card + 1 minute preparation → cue → 2 minutes recording with timer */
    async function longTurn(i) {
        const t = ++token;
        const part = data.parts[i], key = `p${part.part}-q1`;
        const card = `<div class="ipx-card">${part.questions[0] || ''}</div>`;
        const show = (state, label, count, extra) => {
            stage.innerHTML = `<div class="ipx-split"><div class="ipx-split__card">${card}</div><div class="ipx-split__panel">${panel(state, label, count, extra)}</div></div>`;
        };
        const countdown = (secs, warnAt) => new Promise(resolve => {
            let left = secs;
            const el = () => $('[data-ip-count]', stage);
            tick = setInterval(() => {
                if (t !== token) { clearInterval(tick); resolve(false); return; }
                left -= 1;
                if (el()) { el().textContent = fmt(Math.max(0, left)); el().classList.toggle('is-warning', left <= warnAt); }
                if (left <= 0) { clearInterval(tick); resolve(true); }
            }, 1000);
        });

        show('prep', 'Preparation time: 1 minute', fmt(data.prep), '<p class="spx-small">You can make notes on paper.</p>');
        await countdown(data.prep, 10);
        if (t !== token) return;

        show('cue', 'Start speaking now', '');
        await cue();
        if (t !== token) return;
        await beep();
        if (t !== token) return;

        show('rec', 'Recording…', fmt(data.record), stopButton);
        const result = record(key);
        $('[data-ip-stop]', stage).addEventListener('click', e => { e.currentTarget.disabled = true; clearInterval(tick); stopRecording(); });
        countdown(data.record, 10).then(ok => { if (ok) stopRecording(); });
        await result;
        clearInterval(tick);
        answered.push({ key, part: part.part, number: 1 });
        if (t !== token) return;
        partDone(i);
    }

    /* A part is over: show "Part N ›" at the top right (or finish after Part 3) */
    function partDone(i) {
        token++;
        const part = data.parts[i], next = data.parts[i + 1];
        progress(part.part, true);
        if (!next) { finish(); return; }
        stage.innerHTML = `<div class="spx-end">
                <div class="exam-intro__icon exam-intro__icon--done" aria-hidden="true">✓</div>
                <h3>Part ${part.part} is complete.</h3>
                <p>Click <strong>Part ${next.part} &rsaquo;</strong> at the top right to continue.</p>
            </div>`;
        partBtn.innerHTML = `Part ${next.part} &rsaquo;`;
        partBtn.hidden = false;
        partBtn.onclick = () => { partBtn.hidden = true; showPartIntro(i + 1); };
        partBtn.focus();
    }

    /* ---------- the end: send the recordings ---------- */
    let sending = false, sent = false, sendError = '';
    function drawEnd() {
        const n = Object.keys(recordings).length;
        setHead('Speaking test complete', false);
        stage.innerHTML = `<div class="spx-end">
            <div class="exam-intro__icon ${sent ? 'exam-intro__icon--done' : ''}" aria-hidden="true">${sent ? '✓' : '…'}</div>
            <h3>${sent ? 'Your Speaking test has been submitted.' : sending ? 'Sending your recordings…' : 'Your recordings could not be sent.'}</h3>
            ${sent ? `<p>${n} recording${n === 1 ? '' : 's'} received. Your teacher will evaluate your speaking.</p>
                      <p class="spx-portal">Please check your results in <a href="${esc(root.dataset.portalUrl)}">your portal</a> after <strong>24 hours</strong>.</p>
                      <p><a href="${esc(root.dataset.portalUrl)}" class="btn btn-primary">Go to my portal</a></p>`
                : `<p class="muted-inline">${sendError ? esc(sendError) : 'Please wait a moment and keep this page open…'}</p>`}
            ${sendError ? '<button type="button" class="btn btn-primary" data-ip-retry>Try again</button>' : ''}
        </div>`;
        const retry = $('[data-ip-retry]', stage);
        if (retry) retry.addEventListener('click', send);
        if (sent && window.EWResultsActions) window.EWResultsActions($('.spx-end', stage));
    }
    function send() {
        if (sending || sent) return;
        sending = true; sendError = ''; drawEnd();
        const form = new FormData();
        form.append('test_id', root.dataset.testId);
        form.append('answers', JSON.stringify(answered.map(a => ({ key: a.key, part: a.part, number: a.number, recorded: !!recordings[a.key] }))));
        Object.entries(recordings).forEach(([key, file]) => form.append(`recordings[${key}]`, file, file.name));
        fetch(root.dataset.submitUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: form,
        }).then(async r => {
            if (!r.ok) {
                let j = {}; try { j = await r.json(); } catch (_) {}
                throw new Error((j.errors ? Object.values(j.errors)[0][0] : j.message) || 'Sending failed');
            }
            sent = true; finished = true;
            if (window.EWResultsPage) window.EWResultsPage();
        }).catch(err => {
            sendError = err.message + ' – check your internet connection and try again.';
        }).finally(() => { sending = false; drawEnd(); });
    }
    function finish() {
        partBtn.hidden = true;
        if (stream) stream.getTracks().forEach(tr => tr.stop());
        send();
    }

    /* ---------- start ---------- */
    nextBtn.addEventListener('click', () => { if (data.parts.length) showPartIntro(0); });
    window.addEventListener('beforeunload', e => { if (started && !finished) { e.preventDefault(); e.returnValue = ''; } });
    $$('[data-exit]').forEach(a => a.addEventListener('click', e => {
        if (started && !finished && !window.confirm('Leave the test? Your recordings so far will be lost.')) e.preventDefault();
        else { finished = true; stopSound(); }
    }));
    showMain();
})();
