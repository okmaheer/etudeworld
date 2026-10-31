/*
 * Etude World – IELTS Writing test (student side).
 *  Welcome → Task 1 ⇄ Task 2 → finished.
 *  • ONE timer for both tasks: 1 hour (red in the last minute). When it runs out, everything is submitted.
 *  • The Task 1 / Task 2 buttons at the bottom switch tasks at any time.
 *  • Word limits per task: the student cannot type past the maximum; Submit needs both minimums.
 *  • Like the real IELTS test: no spell check, and no copy / cut / paste / undo / redo
 *    (not even with keyboard shortcuts, the right-click menu or drag and drop) – only inside the
 *    split screen, silently. Outside it, right-click / Inspect element work as normal.
 *  • Answers are kept in this browser while typing (in case the page is reloaded).
 *  • Answers go to the teacher (Admin → Evaluation Requests → IELTS Evaluation).
 */
(function () {
    const exam = document.querySelector('[data-ielts-writing]');
    if (!exam) return;
    const $  = (s, el = exam) => el.querySelector(s);
    const $$ = (s, el = exam) => Array.from(el.querySelectorAll(s));

    const TOTAL = 60 * 60;                       // 1 hour for both tasks
    const steps = $$('[data-step]');
    const tasks = $$('[data-step][data-task]');
    const finishStep = $('[data-finish]');
    const key = exam.dataset.storageKey;
    let current = 0, left = TOTAL, ticking = null, started = false, finished = false;

    const words = text => (text.trim().match(/\S+/g) || []).length;
    const boxOf = step => $('[data-answer]', step);
    const limits = step => ({ min: +step.dataset.min, max: +step.dataset.max });

    /* ---------- saved in this browser ---------- */
    const store = {
        read() { try { return JSON.parse(localStorage.getItem(key) || 'null'); } catch (_) { return null; } },
        write(d) { try { localStorage.setItem(key, JSON.stringify(d)); } catch (_) {} },
        clear() { try { localStorage.removeItem(key); } catch (_) {} },
    };
    const save = () => {
        const d = { answers: {} };
        tasks.forEach(t => { d.answers[t.dataset.task] = boxOf(t).value; });
        store.write(d);
    };

    /* ---------- messages ---------- */
    const toastBox = $('[data-toast]');
    let toastTimer = null;
    function toast(msg) {
        toastBox.textContent = msg;
        toastBox.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toastBox.hidden = true; }, 5000);
    }

    /* ---------- word count + maximum ---------- */
    function updateCount(step) {
        const n = words(boxOf(step).value), { min, max } = limits(step);
        $('[data-word-count]', step).textContent = n;
        const hint = $('[data-word-hint]', step);
        hint.classList.remove('is-low', 'is-good', 'is-high');
        if (n === 0) hint.textContent = `Write at least ${min} words`;
        else if (n < min) { hint.classList.add('is-low'); hint.textContent = `${min - n} more to reach the minimum (${min})`; }
        else if (n < max) { hint.classList.add('is-good'); hint.textContent = `Good length (${min}–${max} words)`; }
        else { hint.classList.add('is-high'); hint.textContent = `Maximum reached (${max} words)`; }
    }

    tasks.forEach(step => {
        const box = boxOf(step);
        let lastOk = box.value;
        box.addEventListener('input', () => {
            if (words(box.value) > limits(step).max) {
                // Too many words: undo this keystroke and keep the caret where it was.
                const pos = Math.max(0, box.selectionStart - Math.max(0, box.value.length - lastOk.length));
                box.value = lastOk;
                box.setSelectionRange(pos, pos);
                toast(`You have reached the maximum of ${limits(step).max} words for this task.`);
            }
            lastOk = box.value;
            updateCount(step);
            save();
        });
        step._sync = () => { lastOk = box.value; };
    });

    /* ---------- no copy / cut / paste / undo / redo (like the real test) ---------- */
    const BLOCKED_INPUT = ['insertFromPaste', 'insertFromPasteAsQuotation', 'insertFromDrop', 'insertFromYank',
        'deleteByCut', 'deleteByDrag', 'historyUndo', 'historyRedo', 'insertReplacementText'];
    // Only inside the split screen (question + answer box). Everywhere else right-click etc. work as normal.
    const writingArea = el => el && el.closest && el.closest('[data-step][data-task] .wrx-split');

    ['copy', 'cut', 'paste', 'drop', 'dragstart', 'contextmenu'].forEach(type => {
        exam.addEventListener(type, e => { if (writingArea(e.target)) e.preventDefault(); }, true);
    });
    exam.addEventListener('beforeinput', e => {
        if (writingArea(e.target) && BLOCKED_INPUT.includes(e.inputType)) e.preventDefault();
    }, true);
    document.addEventListener('keydown', e => {
        if (!writingArea(e.target)) return;
        const k = e.key.toLowerCase();
        const mod = e.ctrlKey || e.metaKey;
        if (mod && e.shiftKey && k === 'c') return;           // Ctrl+Shift+C = the browser's "Inspect element"
        const shortcut = (mod && ['c', 'x', 'v', 'z', 'y', 'insert'].includes(k))
            || (e.shiftKey && (k === 'insert' || k === 'delete'));
        if (shortcut) e.preventDefault();
    }, true);

    function restore() {
        store.clear();            // a reload starts the whole test again
        const d = store.read();
        if (!d || !d.answers) return;
        let any = false;
        tasks.forEach(t => {
            const v = d.answers[t.dataset.task];
            if (v) { boxOf(t).value = v; t._sync(); updateCount(t); any = true; }
        });
        if (any) $('[data-restored]').hidden = false;
    }

    /* ---------- one timer for both tasks ---------- */
    const fmt = s => `${Math.floor(s / 3600)}:${String(Math.floor(s % 3600 / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
    function renderTimer() {
        $$('[data-timer]').forEach(box => {
            $('[data-timer-text]', box).textContent = fmt(left);
            box.classList.toggle('is-warning', left <= 60);        // red in the last minute
        });
    }
    function startTimer() {
        if (ticking) return;
        renderTimer();
        ticking = setInterval(() => {
            left = Math.max(0, left - 1);
            renderTimer();
            if (left === 0) { clearInterval(ticking); ticking = null; timeUp(); }
        }, 1000);
    }

    function lockAll() {
        tasks.forEach(step => { step.classList.add('is-time-up'); boxOf(step).readOnly = true; boxOf(step).blur(); });
    }
    function timeUp() {
        lockAll();
        $$('[data-time-up]').forEach(b => { b.hidden = false; });
        save();
        finish();
        toast('Time is up. Your answers were saved and submitted automatically.');
    }

    /* ---------- moving between screens ---------- */
    function show(step) {
        steps.forEach(s => { s.hidden = s !== step; });
        current = steps.indexOf(step);
        if (step.dataset.task) {
            const box = boxOf(step);
            if (!box.readOnly) setTimeout(() => box.focus(), 60);
        }
        window.scrollTo({ top: 0 });
    }

    exam.addEventListener('click', e => {
        if (e.target.closest('[data-next]')) {        // "Start practice"
            started = true;
            show(tasks[0]);
            startTimer();
            return;
        }
        const goto = e.target.closest('[data-goto]');
        if (goto) { show(tasks.find(t => t.dataset.task === goto.dataset.goto)); return; }
        if (e.target.closest('[data-writing-submit]')) {
            const short = tasks.filter(t => words(boxOf(t).value) < limits(t).min);
            if (short.length) {
                const t = short[0];
                window.alert(`${t.dataset.taskTitle} needs at least ${limits(t).min} words before you can submit `
                    + `(you have ${words(boxOf(t).value)}).`);
                show(t);
                return;
            }
            if (!window.confirm("Submit your writing? You can't change it after this.")) return;
            lockAll();
            save();
            finish();
            return;
        }
        if (e.target.closest('[data-reset]')) {
            if (window.confirm('Clear your answers and start this test again?')) { store.clear(); window.location.reload(); }
        }
    });

    /* ---------- finishing: send to the teacher + show the answers ---------- */
    const esc = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    let sending = false, sent = false;

    function send() {
        if (sending || sent) return;
        sending = true;
        const status = $('[data-send-status]'), retry = $('[data-retry]');
        status.textContent = 'Sending your answers to your teacher…';
        retry.hidden = true;
        const answers = {};
        tasks.forEach(t => { answers[t.dataset.task] = { answer: boxOf(t).value }; });
        const form = new FormData();
        form.append('test_id', exam.dataset.testId);
        form.append('module', 'writing');
        form.append('answers', JSON.stringify(answers));
        fetch(exam.dataset.submitUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: form,
        }).then(async r => {
            if (!r.ok) {
                let j = {}; try { j = await r.json(); } catch (_) {}
                throw new Error((j.errors ? Object.values(j.errors)[0][0] : j.message) || 'Sending failed');
            }
            sent = true;
            if (window.EWResultsPage) window.EWResultsPage();
            store.clear();
            status.textContent = 'Here are your answers. Read them again and compare them with the tasks.';
            $('[data-portal]').hidden = false;
            if (window.EWResultsActions) window.EWResultsActions($('[data-portal]').parentElement);
        }).catch(err => {
            status.textContent = 'Your answers could not be sent (' + err.message + '). Check your internet connection and try again – your answers are still on this page.';
            retry.hidden = false;
        }).finally(() => { sending = false; });
    }
    $('[data-retry]').addEventListener('click', send);

    function finish() {
        finished = true;
        clearInterval(ticking); ticking = null;
        $('[data-review]').innerHTML = tasks.map(step => {
            const box = boxOf(step), n = words(box.value), { min, max } = limits(step);
            const cls = n === 0 ? 'is-empty' : n < min ? 'is-low' : n <= max ? 'is-good' : 'is-high';
            return `<article class="review-item">
                <header><h3>${esc(step.dataset.taskTitle)}</h3><span class="review-count ${cls}">${n} words</span></header>
                <div class="review-text">${n ? esc(box.value).replace(/\n/g, '<br>') : '<em>No answer written.</em>'}</div>
            </article>`;
        }).join('');
        show(finishStep);
        send();
    }

    const asText = () => tasks.map(step => `${step.dataset.taskTitle}\nWords: ${words(boxOf(step).value)}\n\n${boxOf(step).value.trim() || '(no answer)'}\n`)
        .join('\n----------------------------------------\n\n');
    exam.addEventListener('click', e => {
        const copy = e.target.closest('[data-copy]');
        if (copy) navigator.clipboard.writeText(asText()).then(() => { copy.textContent = 'Copied!'; setTimeout(() => { copy.textContent = 'Copy my answers'; }, 2000); });
        if (e.target.closest('[data-download]')) {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob([asText()], { type: 'text/plain' }));
            a.download = (document.title.replace(/[^\w\- ]+/g, '').trim() || 'ielts-writing') + '.txt';
            a.click();
            setTimeout(() => URL.revokeObjectURL(a.href), 1000);
        }
    });

    /* ---------- don't lose work by accident ---------- */
    window.addEventListener('beforeunload', e => { if (started && !finished) { e.preventDefault(); e.returnValue = ''; } });
    $$('[data-exit]').forEach(a => a.addEventListener('click', e => {
        if (started && !finished && !window.confirm('Leave the test? If you reload or leave this page, your answers are lost and the test starts again.')) e.preventDefault();
        else started = false;
    }));

    restore();
    tasks.forEach(updateCount);
    renderTimer();
})();
