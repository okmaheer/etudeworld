/*
 * Etude World – CELPIP test screen.
 * Shows one [data-step] at a time, runs the timer for each task, counts words,
 * handles the Task 2 option choice, saves answers in this browser while typing,
 * and builds the "Your answers" review at the end.
 */
(function () {
    const exam = document.querySelector('[data-exam]');
    if (!exam) return;

    const $  = (s, el = exam) => el.querySelector(s);
    const $$ = (s, el = exam) => Array.from(el.querySelectorAll(s));

    const steps      = $$('[data-step]');
    const timerBox   = $('[data-timer]');
    const timerText  = $('[data-timer-text]');
    const stepLabel  = $('[data-step-label]');
    const storageKey = exam.dataset.storageKey;
    const mock = exam.dataset.mode === 'mock';

    let current = -1;
    let ticking = null;
    let started = false;
    let finished = false;

    /* ---------- saving in this browser ---------- */
    const store = {
        read() { try { return JSON.parse(localStorage.getItem(storageKey) || 'null'); } catch (_) { return null; } },
        write(data) { try { localStorage.setItem(storageKey, JSON.stringify(data)); } catch (_) {} },
        clear() { try { localStorage.removeItem(storageKey); } catch (_) {} },
    };

    function collect() {
        const data = { answers: {}, choices: {}, saved: Date.now() };
        $$('[data-answer]').forEach(t => { data.answers[t.dataset.answer] = t.value; });
        $$('[data-choice]:checked').forEach(r => { data.choices[r.name.replace('_choice', '')] = r.value; });
        return data;
    }
    const save = () => store.write(collect());

    function restore() {
        store.clear();   // a reload starts the whole test again
        const data = store.read();
        if (!data) return;
        let any = false;
        Object.entries(data.choices || {}).forEach(([task, value]) => {
            const radio = $(`[data-choice][name="${task}_choice"][value="${value}"]`);
            if (radio) { radio.checked = true; unlockAnswer(radio); any = true; }
        });
        Object.entries(data.answers || {}).forEach(([task, text]) => {
            const box = $(`[data-answer="${task}"]`);
            if (box && text) { box.value = text; updateCount(box); any = true; }
        });
        if (any) $('[data-restored]').hidden = false;
    }

    /* ---------- word counting ---------- */
    const words = text => (text.trim().match(/\S+/g) || []).length;

    function updateCount(box) {
        const step = box.closest('[data-step]');
        const n = words(box.value);
        $('[data-word-count]', step).textContent = n;
        const hint = $('[data-word-hint]', step);
        hint.classList.remove('is-low', 'is-good', 'is-high');
        if (n === 0) { hint.textContent = 'Aim for 150–200 words'; }
        else if (n < 150) { hint.classList.add('is-low'); hint.textContent = `${150 - n} more to reach 150`; }
        else if (n <= 200) { hint.classList.add('is-good'); hint.textContent = 'Great length (150–200)'; }
        else { hint.classList.add('is-high'); hint.textContent = `${n - 200} over 200 – try to trim`; }
    }

    $$('[data-answer]').forEach(box => {
        box.addEventListener('input', () => { updateCount(box); save(); });
        // Don't allow dragging text in from other windows.
        box.addEventListener('drop', e => e.preventDefault());
    });

    /* ---------- Task 2: choose an option first ---------- */
    function unlockAnswer(radio) {
        const step = radio.closest('[data-step]');
        const box = $('[data-answer]', step);
        if (step.classList.contains('is-time-up')) return;
        box.disabled = false;
        box.placeholder = `You chose Option ${radio.value === '1' ? 'A' : 'B'}. Start typing your response here…`;
    }
    $$('[data-choice]').forEach(r => r.addEventListener('change', () => {
        unlockAnswer(r);
        save();
        $('[data-answer]', r.closest('[data-step]')).focus();
    }));

    /* ---------- timer ---------- */
    const fmt = secs => `${String(Math.floor(secs / 60)).padStart(2, '0')}:${String(secs % 60).padStart(2, '0')}`;

    function stopTimer() { clearInterval(ticking); ticking = null; }

    function startTimer(step) {
        stopTimer();
        const minutes = parseInt(step.dataset.minutes || '0', 10);
        const own = $('[data-step-timer]', step);          // writing tasks show their timer next to Next
        const box = own || timerBox;
        const text = own ? $('[data-step-timer-text]', step) : timerText;
        if (own) timerBox.hidden = true;
        if (!minutes) { timerBox.hidden = true; return; }
        if (step.dataset.left === undefined) step.dataset.left = String(minutes * 60);
        box.hidden = false;
        const render = () => {
            const left = parseInt(step.dataset.left, 10);
            text.textContent = own ? `${Math.floor(left / 60)}:${String(left % 60).padStart(2, '0')}` : fmt(left);
            if (own) {
                box.classList.toggle('is-warning', left <= 60);      // red in the last minute
            } else {
                box.classList.toggle('is-warning', left <= 300 && left > 60);
                box.classList.toggle('is-danger', left <= 60);
            }
        };
        render();
        if (step.classList.contains('is-time-up')) return;
        ticking = setInterval(() => {
            const left = Math.max(0, parseInt(step.dataset.left, 10) - 1);
            step.dataset.left = String(left);
            render();
            if (left === 0) { stopTimer(); timeUp(step); }
        }, 1000);
    }

    // Timer reached 00:00: lock the answer, save it, and go straight to the next screen.
    function lockTask(step) {
        step.classList.add('is-time-up');
        const box = $('[data-answer]', step);
        if (box) { box.readOnly = true; box.disabled = false; box.blur(); }
        $$('[data-choice]', step).forEach(r => { r.disabled = true; });
        $$('[data-tool]', step).forEach(b => { b.disabled = true; });
    }

    function timeUp(step) {
        step.classList.add('is-time-up');
        const box = $('[data-answer]', step);
        if (box) { box.readOnly = true; box.disabled = false; box.blur(); }
        $$('[data-choice]', step).forEach(r => { r.disabled = true; });
        $$('[data-tool]', step).forEach(b => { b.disabled = true; });
        const banner = $('[data-time-up]', step);
        if (banner) banner.hidden = false;
        // Task 2's time running out submits the whole Writing section.
        if (step.dataset.task === 'task2') { $$('[data-step][data-task]').forEach(lockTask); submitWriting(); }
        save();
        started = true;
        go(current + 1);
        toast(`Time ran out for ${step.dataset.taskTitle || 'the last task'}. Your answer was saved automatically.`);
    }

    const toastBox = $('[data-toast]');
    let toastTimer = null;
    function toast(msg) {
        if (!toastBox) return;
        toastBox.textContent = msg;
        toastBox.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toastBox.hidden = true; }, 7000);
    }

    /* ---------- Cut / Copy / Paste / Undo / Redo buttons ---------- */
    // Uses the browser's own editing commands, so Ctrl+Z / Ctrl+Y undo and redo these actions too.
    let clipboard = '';
    function insertText(box, text) {
        box.focus();
        if (!document.execCommand('insertText', false, text)) {
            const s = box.selectionStart, e = box.selectionEnd;
            box.setRangeText(text, s, e, 'end');
        }
        box.dispatchEvent(new Event('input', { bubbles: true }));
    }
    exam.addEventListener('mousedown', e => {
        // Keep the text selection when a tool button is pressed.
        if (e.target.closest('[data-tool]')) e.preventDefault();
    });
    exam.addEventListener('click', async e => {
        const btn = e.target.closest('[data-tool]');
        if (!btn) return;
        const box = $('[data-answer]', btn.closest('[data-step]'));
        if (!box || box.disabled || box.readOnly) return;
        const selected = box.value.substring(box.selectionStart, box.selectionEnd);
        switch (btn.dataset.tool) {
            case 'copy':
                if (selected) { clipboard = selected; try { await navigator.clipboard.writeText(selected); } catch (_) {} }
                box.focus();
                break;
            case 'cut':
                if (selected) { clipboard = selected; try { await navigator.clipboard.writeText(selected); } catch (_) {} insertText(box, ''); }
                break;
            case 'paste': {
                let text = clipboard;
                try { const sys = await navigator.clipboard.readText(); if (sys) text = sys; } catch (_) {}
                if (text) insertText(box, text);
                else box.focus();
                break;
            }
            case 'undo': box.focus(); document.execCommand('undo'); box.dispatchEvent(new Event('input', { bubbles: true })); break;
            case 'redo': box.focus(); document.execCommand('redo'); box.dispatchEvent(new Event('input', { bubbles: true })); break;
        }
    });

    /* ---------- moving between screens ---------- */
    function go(index) {
        if (index < 0 || index >= steps.length) return;
        stopTimer();
        steps.forEach((s, i) => { s.hidden = i !== index; });
        current = index;
        const step = steps[index];
        stepLabel.textContent = step.dataset.label || '';

        // Section progress (mock test)
        const mod = step.dataset.module;
        const order = $$('[data-progress]').map(li => li.dataset.progress);
        $$('[data-progress]').forEach(li => {
            const pos = order.indexOf(li.dataset.progress);
            li.classList.toggle('is-current', li.dataset.progress === mod);
            li.classList.toggle('is-done', step.hasAttribute('data-finish') || (mod && pos < order.indexOf(mod)));
        });

        if (step.hasAttribute('data-finish')) {
            finished = true;
            timerBox.hidden = true;
            buildReview();
            if (window.EWResultsPage) window.EWResultsPage();
            if (window.EWResultsActions) window.EWResultsActions(step.querySelector('.exam-done'));
        } else {
            startTimer(step);
        }
        window.scrollTo({ top: 0 });
        const box = $('[data-answer]:not([disabled])', step);
        if (box && !box.readOnly) setTimeout(() => box.focus(), 60);
    }

    exam.addEventListener('click', e => {
        const next = e.target.closest('[data-next]');
        if (!next) return;
        const step = steps[current];
        if (step && step.querySelector('[data-student]') && !checkStudent(step)) return;
        const box = $('[data-answer]', step);
        if (box && mock && !step.classList.contains('is-time-up')) {
            const n = words(box.value);
            const msg = n === 0
                ? "You haven't written anything for this task yet. Move on anyway? (You can't come back to it.)"
                : 'You still have time left. Move on to the next part? (You can\'t come back to this task.)';
            if (!window.confirm(msg)) return;
        }
        started = true;
        go(current + 1);
    });


    /* ---------- the student's name & email (needed to send Writing/Speaking to the teacher) ---------- */
    const STUDENT_KEY = 'ew-student';
    window.EW_STUDENT = (() => { try { return JSON.parse(localStorage.getItem(STUDENT_KEY) || 'null'); } catch (_) { return null; } })();
    (function prefill() {
        const n = $('[data-student-name]'), m = $('[data-student-email]');
        if (n && window.EW_STUDENT) { n.value = window.EW_STUDENT.name || ''; m.value = window.EW_STUDENT.email || ''; }
    })();
    function checkStudent(step) {
        const n = $('[data-student-name]', step), m = $('[data-student-email]', step), err = $('[data-student-error]', step);
        const name = n.value.trim(), email = m.value.trim();
        let msg = '';
        if (name.length < 2) msg = 'Please enter your name.';
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) msg = 'Please enter a valid email address.';
        err.hidden = !msg; err.textContent = msg;
        if (msg) { (name.length < 2 ? n : m).focus(); return false; }
        window.EW_STUDENT = { name, email };
        try { localStorage.setItem(STUDENT_KEY, JSON.stringify(window.EW_STUDENT)); } catch (_) {}
        return true;
    }

    /* ---------- send Writing answers / Speaking recordings to the teacher ---------- */
    const attempt = Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
    window.EWSubmit = function (module, answers, recordings) {
        const data = new FormData();
        data.append('test_id', exam.dataset.testId);
        data.append('module', module);
        data.append('mode', mock ? 'mock' : 'practice');
        data.append('attempt', attempt);
        data.append('student_name', (window.EW_STUDENT || {}).name || '');
        data.append('student_email', (window.EW_STUDENT || {}).email || '');
        data.append('answers', JSON.stringify(answers || {}));
        Object.entries(recordings || {}).forEach(([task, file]) => { if (file) data.append(`recordings[${task}]`, file, file.name || `task-${task}.webm`); });
        return fetch(exam.dataset.submitUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: data,
        }).then(async r => {
            if (!r.ok) {
                let j = {}; try { j = await r.json(); } catch (_) {}
                const first = j.errors ? Object.values(j.errors)[0][0] : j.message;
                throw new Error(first || 'Sending failed');
            }
            return r.json();
        });
    };

    /* Listening / Reading scores go to the student's portal (only when they are logged in). */
    window.EWSaveResult = function (module, r) {
        if (exam.dataset.signedIn !== '1' || !exam.dataset.resultsUrl) return;
        const data = new FormData();
        data.append('exam', 'celpip');
        data.append('test_id', exam.dataset.testId);
        data.append('module', module);
        data.append('mode', mock ? 'mock' : 'practice');
        data.append('score', r.score);
        data.append('total', r.total);
        if (r.level) data.append('level', r.level);
        fetch(exam.dataset.resultsUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: data,
        }).catch(() => {});
    };

    let writingSent = false;
    function submitWriting(tries = 0) {
        if (writingSent) return;
        const answers = {};
        $$('[data-step][data-task]').forEach(step => {
            const box = $('[data-answer]', step);
            const choice = $('[data-choice]:checked', step);
            answers[step.dataset.task] = { answer: box.value, choice: choice ? choice.value : null };
        });
        writingSent = true;
        window.EWSubmit('writing', answers).then(() => {
            toast('Your writing was sent to your teacher for evaluation.');
        }).catch(err => {
            writingSent = false;
            if (tries < 2) setTimeout(() => submitWriting(tries + 1), 3000);
            else toast('Your writing could not be sent (' + err.message + '). Your answers are still saved in this browser.');
        });
    }

    /* ---------- Writing: Back (practice only) and Submit (Task 2) ---------- */
    exam.addEventListener('click', e => {
        if (e.target.closest('[data-back]') && !mock) {
            started = true;
            go(current - 1);
            return;
        }
        if (e.target.closest('[data-writing-submit]')) {
            const tasks = $$('[data-step][data-task]');
            const empty = tasks.filter(t => words($('[data-answer]', t).value) === 0).length;
            const msg = empty
                ? `You haven't written anything for ${empty === 1 ? 'one task' : 'either task'}. Submit your writing anyway? You can't change it after this.`
                : "Submit your writing? You can't change it after this.";
            if (!window.confirm(msg)) return;
            tasks.forEach(lockTask);
            save();
            submitWriting();
            started = true;
            go(current + 1);
        }
    });

    /* ---------- results ---------- */
    const esc = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    function buildReview() {
        const review = $('[data-review]');
        const tasks = $$('[data-step][data-task]');
        const scored = [['Listening', window.EW_LISTENING_RESULT], ['Reading', window.EW_READING_RESULT]].filter(x => x[1]);
        const lsHtml = scored.map(([name, r]) => `<article class="review-item"><header><h3>${name}</h3>
                <span class="review-count is-good">${r.score} / ${r.total} correct${r.level ? ' · ' + r.level : ''}</span></header></article>`).join('');
        if (!tasks.length && scored.length) { review.innerHTML = lsHtml; return; }
        if (!tasks.length) {
            review.innerHTML = '<p class="review-empty">This test didn\'t include any writing tasks yet, so there are no answers to show.</p>';
            return;
        }
        review.innerHTML = lsHtml + tasks.map(step => {
            const box = $('[data-answer]', step);
            const n = words(box.value);
            const choice = $('[data-choice]:checked', step);
            const cls = n === 0 ? 'is-empty' : n < 150 ? 'is-low' : n <= 200 ? 'is-good' : 'is-high';
            return `<article class="review-item">
                <header>
                    <h3>${esc(step.dataset.taskTitle)}</h3>
                    <span class="review-count ${cls}">${n} words</span>
                </header>
                ${choice ? `<p class="review-choice">You chose <strong>Option ${choice.value === '1' ? 'A' : 'B'}</strong></p>` : ''}
                <div class="review-text">${n ? esc(box.value).replace(/\n/g, '<br>') : '<em>No answer written.</em>'}</div>
            </article>`;
        }).join('');
    }

    function asText() {
        return $$('[data-step][data-task]').map(step => {
            const box = $('[data-answer]', step);
            const choice = $('[data-choice]:checked', step);
            return `${step.dataset.taskTitle}${choice ? ` (Option ${choice.value === '1' ? 'A' : 'B'})` : ''}\n` +
                `Words: ${words(box.value)}\n\n${box.value.trim() || '(no answer)'}\n`;
        }).join('\n----------------------------------------\n\n');
    }

    exam.addEventListener('click', e => {
        if (e.target.closest('[data-copy]')) {
            const btn = e.target.closest('[data-copy]');
            navigator.clipboard.writeText(asText()).then(() => {
                btn.textContent = 'Copied!';
                setTimeout(() => { btn.textContent = 'Copy my answers'; }, 2000);
            });
        }
        if (e.target.closest('[data-download]')) {
            const title = document.title.replace(/[^\w\- ]+/g, '').trim() || 'celpip-answers';
            const blob = new Blob([asText()], { type: 'text/plain' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = title + '.txt';
            a.click();
            setTimeout(() => URL.revokeObjectURL(a.href), 1000);
        }
        if (e.target.closest('[data-reset]')) {
            if (finished || window.confirm('Clear your answers and start this test again?')) {
                store.clear();
                finished = false; started = false;
                window.location.reload();
            }
        }
    });

    // A Listening/Reading box that is the last section shows its own results – the test is then finished.
    exam.addEventListener('ew:finished', () => { finished = true; });

    /* ---------- don't lose work by accident ---------- */
    window.addEventListener('beforeunload', e => {
        if (started && !finished) { e.preventDefault(); e.returnValue = ''; }
    });
    $$('[data-exit]').forEach(a => a.addEventListener('click', e => {
        if (started && !finished && !window.confirm('Leave the test? If you leave or reload this page, your answers are lost and the test starts again.')) {
            e.preventDefault();
        } else {
            started = false;
        }
    }));

    /* ---------- Spell check (underlines mistakes; never changes your text) ---------- */
    const spell = (function () {
        let worker = null;
        try { worker = new Worker(window.EW_SPELL_WORKER || '/js/spellcheck-worker.js'); } catch (_) { return { check() {} }; }
        const pending = new Map();
        let seq = 0;
        worker.onmessage = e => {
            const job = pending.get(e.data.id);
            if (!job) return;
            pending.delete(e.data.id);
            job(e.data.wrong);
        };
        const escapeHtml = t => t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

        function paint(box, wrong) {
            const backdrop = box.parentNode.querySelector('[data-spell-backdrop]');
            if (!backdrop) return;
            const text = box.value;
            // Don't flag the word you are still typing (cursor right at its end).
            const typingAt = document.activeElement === box ? box.selectionStart : -1;
            let html = '', last = 0;
            wrong.forEach(([a, b]) => {
                if (b === typingAt && /\S/.test(text.slice(a, b))) return;
                html += escapeHtml(text.slice(last, a)) + '<mark>' + escapeHtml(text.slice(a, b)) + '</mark>';
                last = b;
            });
            html += escapeHtml(text.slice(last)) + '\n';
            backdrop.innerHTML = html;
            backdrop.scrollTop = box.scrollTop;
        }

        const timers = new WeakMap();
        function check(box, delay = 350) {
            clearTimeout(timers.get(box));
            timers.set(box, setTimeout(() => {
                const id = ++seq;
                pending.set(id, wrong => paint(box, wrong));
                worker.postMessage({ id, text: box.value });
            }, delay));
        }
        return { check };
    })();

    $$('[data-answer]').forEach(box => {
        const backdrop = box.parentNode.querySelector('[data-spell-backdrop]');
        box.addEventListener('input', () => spell.check(box));
        box.addEventListener('blur', () => spell.check(box, 0));
        box.addEventListener('scroll', () => { if (backdrop) backdrop.scrollTop = box.scrollTop; });
        if (box.value) spell.check(box, 0);
    });

    restore();
    $$('[data-answer]').forEach(box => { if (box.value) spell.check(box, 0); });
    go(0);
})();
