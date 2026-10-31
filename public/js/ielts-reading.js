/*
 * Etude World – IELTS Reading and Listening test screen (Listening: no passage pane, 40 minutes, one recording).
 *  • Timer (1 hour; red in the last minute). Submit, or the timer reaching zero, sends the answers
 *    to the server, which marks them; the score and every answer are then shown.
 *  • Parts: ← → buttons and the "Part N" labels. Footer numbers jump to a question; they turn
 *    light blue once answered.
 *  • Split screen: drag the divider to resize the passage and the questions.
 *  • Settings: text size 12 / 14 / 16 and white / yellow background.
 *  • Highlighting in the passage: select text → Highlight / Remove / Remove all.
 *  • Headings: drag a heading into the box above a paragraph (or click it, then click the box).
 *    Click a placed heading to send it back to the list.
 *  • A reload starts the whole test again (answers, timer and recording are reset).
 */
(function () {
    const app = document.querySelector('[data-reading]');
    if (!app) return;
    const $  = (s, el = app) => el.querySelector(s);
    const $$ = (s, el = app) => Array.from(el.querySelectorAll(s));
    const key = app.dataset.storageKey;
    const TOTAL = Number(app.dataset.minutes || 60) * 60;

    const parts = $$('[data-questions]').map(el => el.dataset.questions);
    const audio = $('[data-audio]');       // Listening only: the one recording of the test
    let current = 0;
    let answers = {};            // question number → string | string[]
    let finished = false;

    /* ---------- saved in this browser ---------- */
    const store = {
        read() { try { return JSON.parse(localStorage.getItem(key) || 'null') || {}; } catch (_) { return {}; } },
        write(d) { try { localStorage.setItem(key, JSON.stringify(d)); } catch (_) {} },
        clear() { try { localStorage.removeItem(key); } catch (_) {} },
    };
    store.clear();            // a reload starts the whole test again (answers, timer and recording)
    const saved = store.read();
    // The hour starts when the student clicks "Start test" on the instructions page (a reload keeps the running test).
    const resumed = !!(saved.startedAt && Date.now() - saved.startedAt < TOTAL * 1000);
    let startedAt = resumed ? saved.startedAt : 0;
    if (!resumed) { saved.answers = {}; }
    const save = () => store.write({ startedAt, answers, prefs, audioAt: audio && audio.currentTime > 0 ? audio.currentTime : (saved.audioAt || 0) });

    /* ---------- answered? (footer colours) ---------- */
    const filled = v => Array.isArray(v) ? v.length > 0 && v.every(x => String(x || '').trim() !== '') : String(v || '').trim() !== '';
    function paintNav() {
        $$('[data-nav-q]').forEach(b => {
            const q = Number(b.dataset.navQ);
            let on = filled(answers[q]);
            // "choose TWO" questions: the first number is answered with one choice, the second with two
            const two = $(`input[data-two][data-q="${q - 1}"]`);
            if (two) on = Array.isArray(answers[q - 1]) && answers[q - 1].length >= 2;
            else if ($(`input[data-two][data-q="${q}"]`)) on = Array.isArray(answers[q]) && answers[q].length >= 1;
            b.classList.toggle('is-answered', on);
        });
    }
    const changed = () => { paintNav(); save(); };

    /* ---------- parts ---------- */
    function showPart(i, scrollTop = true) {
        current = Math.max(0, Math.min(parts.length - 1, i));
        const p = parts[current];
        $$('[data-passage]').forEach(el => { el.hidden = el.dataset.passage !== p; });
        $$('[data-questions]').forEach(el => { el.hidden = el.dataset.questions !== p; });
        $$('[data-part-bar]').forEach(el => { el.hidden = el.dataset.partBar !== p; });
        $$('[data-nav-part]').forEach(el => el.classList.toggle('is-current', el.dataset.navPart === p));
        $('[data-prev]').disabled = current === 0;
        $('[data-next]').disabled = current === parts.length - 1;
        if (scrollTop) { $('[data-left]').scrollTop = 0; $('[data-right]').scrollTop = 0; }
        hideMenu();
    }
    $('[data-prev]').addEventListener('click', () => showPart(current - 1));
    $('[data-next]').addEventListener('click', () => showPart(current + 1));
    $$('[data-go-part]').forEach(el => el.addEventListener('click', () => showPart(parts.indexOf(el.dataset.goPart))));

    // Footer number → its part, then scroll to and focus the question.
    $$('[data-nav-q]').forEach(b => b.addEventListener('click', () => {
        const q = Number(b.dataset.navQ);
        showPart(parts.indexOf(b.dataset.part), false);
        let target = $(`[data-qbox="${q}"]`) || $(`[data-q="${q}"]`) || $(`[data-qbox="${q - 1}"]`);
        if (!target) return;
        target.scrollIntoView({ block: 'center', behavior: 'smooth' });
        target.classList.remove('rd-focus'); void target.offsetWidth; target.classList.add('rd-focus');
        const input = target.matches('input') ? target : $('input:not([type=hidden])', target);
        if (input && input.classList.contains('rd-blank')) setTimeout(() => input.focus({ preventScroll: true }), 300);
        $$('[data-nav-q]').forEach(x => x.classList.toggle('is-here', x === b));
    }));

    /* ---------- blanks ---------- */
    $$('.rd-blank').forEach(inp => inp.addEventListener('input', () => {
        if (inp.classList.contains('rd-blank--letter')) inp.value = inp.value.replace(/[^a-z]/gi, '').slice(0, 1).toUpperCase();   // synonyms: one capital letter
        const q = inp.dataset.q, b = Number(inp.dataset.b);
        const count = $$(`.rd-blank[data-q="${q}"]`).length;
        const v = Array.isArray(answers[q]) ? answers[q] : Array(count).fill('');
        v[b] = inp.value;
        answers[q] = v;
        changed();
    }));

    /* ---------- TRUE / FALSE / NOT GIVEN (options open on click) and MCQ ---------- */
    app.addEventListener('click', e => {
        const t = e.target.closest('[data-toggle]');
        if (!t) return;
        const box = t.closest('.rd-tf');
        const open = t.getAttribute('aria-expanded') !== 'true';
        $$('.rd-tf', box.parentElement).forEach(other => {
            $('[data-toggle]', other).setAttribute('aria-expanded', 'false');
            $('.rd-tf__opts', other).hidden = true;
        });
        t.setAttribute('aria-expanded', String(open));
        $('.rd-tf__opts', box).hidden = !open;
    });
    app.addEventListener('change', e => {
        const inp = e.target;
        if (!inp.dataset || inp.dataset.q === undefined || inp.classList.contains('rd-blank')) return;
        const q = inp.dataset.q;
        if (inp.type === 'radio') {
            answers[q] = inp.value;
            const chosen = $(`[data-chosen="${q}"]`);
            if (chosen) chosen.textContent = inp.value;
        } else if (inp.dataset.two !== undefined) {
            const on = $$(`input[data-two][data-q="${q}"]:checked`);
            if (on.length > 2) { inp.checked = false; return; }
            answers[q] = on.map(x => x.value);
        }
        changed();
    });

    /* ---------- matching statements: click a cell ---------- */
    function pickCell(cell) {
        const q = cell.dataset.q;
        const same = answers[q] === cell.dataset.value;
        $$(`[data-cell][data-q="${q}"]`).forEach(c => c.classList.remove('is-chosen'));
        if (same) delete answers[q];
        else { answers[q] = cell.dataset.value; cell.classList.add('is-chosen'); }
        changed();
    }
    app.addEventListener('click', e => { const c = e.target.closest('[data-cell]'); if (c) pickCell(c); });
    app.addEventListener('keydown', e => {
        const c = e.target.closest && e.target.closest('[data-cell]');
        if (c && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); pickCell(c); }
    });

    /* ---------- headings: drag & drop (or click, then click a box) ----------
     * The numbered boxes above the paragraphs are the questions; the headings (no numbers) are the choices.
     * There can be more headings than boxes – the extra ones simply stay in the list.
     * answers[box number] = the heading's position in the list (0, 1, 2 …). */
    let dragged = null, picked = null;
    const partOf = q => (($(`[data-nav-q="${q}"]`) || {}).dataset || {}).part;
    const listOf = h => $(`[data-headings][data-hg="${h.dataset.hg}"]`);
    const headingFor = (hg, i) => $(`.rd-heading[data-hg="${hg}"][data-heading="${i}"]`);

    function sendHome(h) {
        const box = h.closest('.rd-drop');
        if (box) { delete answers[box.dataset.dropQ]; box.classList.remove('is-filled'); }
        const list = listOf(h);
        // keep the list in its original order
        const after = $$('[data-heading]', list).find(x => Number(x.dataset.heading) > Number(h.dataset.heading));
        list.insertBefore(h, after || null);
    }
    function place(h, drop) {
        if (h.dataset.hg !== drop.dataset.hg) return;                // only into this passage's boxes
        const from = h.closest('.rd-drop');
        if (from === drop) return;
        if (from) { delete answers[from.dataset.dropQ]; from.classList.remove('is-filled'); }
        const old = $('[data-heading]', drop);
        if (old) sendHome(old);
        drop.appendChild(h);
        drop.classList.add('is-filled');
        answers[drop.dataset.dropQ] = h.dataset.heading;
        h.classList.remove('is-picked');
        picked = null;
        changed();
    }
    $$('.rd-heading').forEach(h => {
        h.addEventListener('dragstart', e => { dragged = h; h.classList.add('is-dragging'); e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', h.dataset.heading); });
        h.addEventListener('dragend', () => { h.classList.remove('is-dragging'); dragged = null; $$('.rd-drop.is-over').forEach(d => d.classList.remove('is-over')); });
        h.addEventListener('click', () => {
            if (h.closest('.rd-drop')) { sendHome(h); changed(); return; }          // placed → back to the list
            const was = picked === h;
            $$('.rd-heading.is-picked').forEach(x => x.classList.remove('is-picked'));
            picked = was ? null : h;
            if (picked) h.classList.add('is-picked');
        });
    });
    // Dropping a placed heading back onto the list returns it.
    $$('[data-headings]').forEach(list => {
        list.addEventListener('dragover', e => { if (dragged && dragged.closest('.rd-drop')) e.preventDefault(); });
        list.addEventListener('drop', e => { e.preventDefault(); if (dragged && dragged.closest('.rd-drop')) { sendHome(dragged); changed(); } });
    });
    $$('.rd-drop').forEach(drop => {
        drop.addEventListener('dragover', e => { if (dragged && dragged.dataset.hg === drop.dataset.hg) { e.preventDefault(); drop.classList.add('is-over'); } });
        drop.addEventListener('dragleave', () => drop.classList.remove('is-over'));
        drop.addEventListener('drop', e => { e.preventDefault(); drop.classList.remove('is-over'); if (dragged) place(dragged, drop); });
        drop.addEventListener('click', e => { if (picked && !e.target.closest('[data-heading]')) place(picked, drop); });
    });

    /* ---------- resizable split screen ---------- */
    const split = $('[data-split]'), divider = $('[data-divider]');
    const setLeft = pct => { pct = Math.max(20, Math.min(80, pct)); split.style.setProperty('--left', pct + '%'); prefs.left = pct; };
    let resizing = false;
    divider.addEventListener('mousedown', e => e.preventDefault());      // no accidental text selection
    divider.addEventListener('pointerdown', e => {
        e.preventDefault();
        resizing = true;
        hideMenu();
        divider.setPointerCapture(e.pointerId);
        divider.classList.add('is-dragging');
        document.body.classList.add('is-resizing');
        const box = split.getBoundingClientRect();
        const move = ev => setLeft((ev.clientX - box.left) / box.width * 100);
        const up = () => {
            divider.classList.remove('is-dragging'); document.body.classList.remove('is-resizing');
            divider.removeEventListener('pointermove', move); divider.removeEventListener('pointerup', up);
            setTimeout(() => { resizing = false; }, 50);
            save();
        };
        divider.addEventListener('pointermove', move);
        divider.addEventListener('pointerup', up);
    });
    divider.addEventListener('keydown', e => {
        if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
        e.preventDefault();
        setLeft((prefs.left || 50) + (e.key === 'ArrowLeft' ? -3 : 3));
        save();
    });

    /* ---------- settings: text size + background ---------- */
    const prefs = Object.assign({ font: 14, bg: 'white', left: 50 }, saved.prefs || {});
    function applyPrefs() {
        app.style.setProperty('--rd-fs', prefs.font + 'px');
        app.classList.toggle('is-yellow', prefs.bg === 'yellow');
        $$('[data-font]').forEach(b => b.classList.toggle('is-on', Number(b.dataset.font) === prefs.font));
        $$('[data-bg]').forEach(b => b.classList.toggle('is-on', b.dataset.bg === prefs.bg));
        split.style.setProperty('--left', (prefs.left || 50) + '%');
    }
    const settings = $('[data-settings]'), settingsBtn = $('[data-settings-btn]');
    settingsBtn.addEventListener('click', e => {
        e.stopPropagation();
        settings.hidden = !settings.hidden;
        settingsBtn.setAttribute('aria-expanded', String(!settings.hidden));
    });
    settings.addEventListener('click', e => {
        e.stopPropagation();
        const f = e.target.closest('[data-font]'), b = e.target.closest('[data-bg]');
        if (f) prefs.font = Number(f.dataset.font);
        if (b) prefs.bg = b.dataset.bg;
        if (f || b) { applyPrefs(); save(); }
    });
    document.addEventListener('click', () => { if (!settings.hidden) { settings.hidden = true; settingsBtn.setAttribute('aria-expanded', 'false'); } });

    /* ---------- highlighting in the passage ---------- */
    const menu = $('[data-menu]');
    const left = $('[data-left]');
    let menuRange = null, menuMark = null;
    function hideMenu() { menu.hidden = true; menuRange = null; menuMark = null; }
    const passageNow = () => $(`[data-passage="${parts[current]}"]`);

    // The text pieces inside a selection (split at its edges), skipping the heading boxes.
    function segments(range) {
        const root = passageNow();
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode: n => !n.parentElement.closest('.rd-drop') && range.intersectsNode(n) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT,
        });
        const list = [];
        while (walker.nextNode()) list.push(walker.currentNode);
        const { startContainer: sc, startOffset: so, endContainer: ec, endOffset: eo } = range;
        return list.map(n => {
            let s = n === sc ? so : 0, e = n === ec ? eo : n.length;
            if (s >= e) return null;
            if (e < n.length) n.splitText(e);
            if (s > 0) n = n.splitText(s);
            return n;
        }).filter(n => n && n.nodeValue.length);
    }
    function unwrapPiece(node) {
        const mark = node.parentElement;
        if (!mark || !mark.classList.contains('rd-hl')) return;
        const before = mark.cloneNode(false), after = mark.cloneNode(false);
        let seen = false;
        Array.from(mark.childNodes).forEach(c => { if (c === node) seen = true; else (seen ? after : before).appendChild(c); });
        if (before.childNodes.length) mark.parentNode.insertBefore(before, mark);
        mark.parentNode.insertBefore(node, mark);
        if (after.childNodes.length) mark.parentNode.insertBefore(after, mark);
        mark.remove();
    }
    function highlight(range) {
        segments(range).forEach(n => {
            if (n.parentElement.classList.contains('rd-hl') || !n.nodeValue.trim()) return;
            const m = document.createElement('mark');
            m.className = 'rd-hl';
            n.parentNode.insertBefore(m, n);
            m.appendChild(n);
        });
    }
    const unhighlight = range => { segments(range).forEach(unwrapPiece); passageNow().normalize(); };
    function clearAll() {
        $$('.rd-hl', passageNow()).forEach(m => { while (m.firstChild) m.parentNode.insertBefore(m.firstChild, m); m.remove(); });
        passageNow().normalize();
    }
    function openMenu(x, y, canAdd, canRemove) {
        $('[data-hl="add"]', menu).disabled = !canAdd;
        $('[data-hl="remove"]', menu).disabled = !canRemove;
        $('[data-hl="clear"]', menu).disabled = !$('.rd-hl', passageNow());
        menu.hidden = false;
        const w = menu.offsetWidth, h = menu.offsetHeight;
        menu.style.left = Math.min(x + 6, window.innerWidth - w - 8) + 'px';
        menu.style.top = Math.min(y + 8, window.innerHeight - h - 8) + 'px';
    }
    left.addEventListener('mouseup', e => {
        if (e.button !== 0 || resizing || e.target.closest('.rd-drop')) return;
        setTimeout(() => {
            if (resizing) return;
            const sel = window.getSelection();
            if (sel && sel.rangeCount && !sel.isCollapsed && passageNow().contains(sel.getRangeAt(0).commonAncestorContainer)) {
                menuRange = sel.getRangeAt(0).cloneRange();
                const hasHl = $$('.rd-hl', passageNow()).some(m => menuRange.intersectsNode(m));
                openMenu(e.clientX, e.clientY, true, hasHl);
                return;
            }
            const mark = e.target.closest('.rd-hl');
            if (mark) { menuMark = mark; openMenu(e.clientX, e.clientY, false, true); return; }
            hideMenu();
        }, 0);
    });
    menu.addEventListener('mousedown', e => e.preventDefault());      // keep the selection
    menu.addEventListener('click', e => {
        const b = e.target.closest('[data-hl]');
        if (!b || b.disabled) return;
        if (b.dataset.hl === 'add' && menuRange) highlight(menuRange);
        if (b.dataset.hl === 'remove') {
            if (menuRange) unhighlight(menuRange);
            else if (menuMark) { while (menuMark.firstChild) menuMark.parentNode.insertBefore(menuMark.firstChild, menuMark); menuMark.remove(); passageNow().normalize(); }
        }
        if (b.dataset.hl === 'clear') clearAll();
        window.getSelection().removeAllRanges();
        hideMenu();
    });
    document.addEventListener('mousedown', e => { if (!menu.hidden && !menu.contains(e.target)) hideMenu(); });
    left.addEventListener('scroll', hideMenu);

    /* ---------- timer ---------- */
    const timerBox = $('[data-timer]'), timerText = $('[data-timer-text]');
    let ticking = null;
    function tick() {
        const secs = Math.max(0, TOTAL - Math.floor((Date.now() - startedAt) / 1000));
        if (secs > 60) { timerText.textContent = Math.ceil(secs / 60); $('small', timerBox).textContent = 'minutes left'; }
        else { timerText.textContent = secs; $('small', timerBox).textContent = secs === 1 ? 'second left' : 'seconds left'; }
        timerBox.classList.toggle('is-warning', secs <= 60);
        if (secs === 0) { clearInterval(ticking); submit(true); }
    }

    /* ---------- submit + results ---------- */
    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    let sending = false;
    function submit(timeUp) {
        if (finished || sending) return;
        if (!timeUp) {
            const open = $$('[data-nav-q]').filter(b => !b.classList.contains('is-answered')).length;
            const msg = open ? `You have ${open} unanswered question${open === 1 ? '' : 's'}. Submit your answers anyway?` : 'Submit your answers? You can’t change them after this.';
            if (!window.confirm(msg)) return;
        }
        sending = true;
        const form = new FormData();
        form.append('answers', JSON.stringify(answers));
        fetch(app.dataset.scoreUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: form,
        }).then(r => r.ok ? r.json() : Promise.reject(new Error('Error ' + r.status)))
          .then(res => { finished = true; clearInterval(ticking); if (audio) audio.pause(); store.clear(); showResults(res, timeUp); })
          .catch(err => {
              sending = false;
              if (window.confirm('Your answers could not be sent (' + err.message + '). Check your internet connection. Try again?')) submit(true);
          });
    }
    $('[data-submit]').addEventListener('click', () => submit(false));

    // "Words from this test" card on the results page.
    function vocabCard(v) {
        if (!v) return '';
        let lang = ''; try { lang = localStorage.getItem('ew-vocab-lang') || ''; } catch (_) {}
        const opts = Object.entries(v.langs).map(([c, n]) => `<option value="${c}"${c === lang ? ' selected' : ''}>${esc(n)}</option>`).join('');
        return `<div class="rd-vocab" data-vocab data-pdf="${esc(v.pdf)}" data-data="${esc(v.data)}" data-script="${esc(v.script)}" data-practise="${esc(v.practise)}">
            <h2>Words from this test (${v.count} words)</h2>
            <p class="rd-vocab__samples">${v.samples.map(w => `<span>${esc(w)}</span>`).join('')}<em>and more…</em></p>
            <label class="rd-vocab__lang">My language: <select data-vocab-lang><option value="">English only</option>${opts}</select></label>
            <div class="rd-vocab__btns">
                <a href="#" class="is-primary" data-vocab-pdf>Download vocabulary PDF</a>
                <a href="#" data-vocab-go="practise" target="_blank" rel="noopener">Learn these words</a>
            </div>
        </div>`;
    }

    const word = app.hasAttribute('data-listening') ? 'Listening' : 'Reading';
    function showResults(res, timeUp) {
        const wrap = document.createElement('div');
        wrap.className = 'rd-results';
        wrap.innerHTML = `<div class="rd-results__inner">
            ${timeUp ? '<p class="rdx-timeup">Time is up – your answers were submitted automatically.</p>' : ''}
            <div class="rdx-result">
                <div class="rdx-result__score"><strong>${res.score}</strong><span>out of ${res.total}</span></div>
                <div class="rdx-result__band">
                    <p class="rdx-result__label">Your IELTS ${word} band</p>
                    <p class="rdx-result__level">${esc(res.band)} <span>estimated band score</span></p>
                    <p class="rdx-result__small">${res.saved ? 'This score has been saved to your portal.' : (app.dataset.signedIn === '1' ? '' : 'Log in before your next test to keep your scores in your portal.')}</p>
                </div>
            </div>
            ${vocabCard(res.vocab)}
            <h3>Your answers</h3>
            <ol class="lsx-review">${res.rows.map(r => `<li class="${r.ok ? 'is-right' : 'is-wrong'}">
                <span class="lsx-review__mark">${r.ok ? '✓' : '✗'}</span>
                <div><strong>Question ${r.q}</strong><br>
                <small>Your answer: ${esc(r.your) || '<em>No answer</em>'}${r.ok ? '' : ` · Correct answer: ${esc(r.correct)}`}</small></div>
            </li>`).join('')}</ol>
            <div class="rd-results__actions">
                <a href="${esc(app.dataset.backUrl)}" class="is-primary">More ${word.toLowerCase()} tests</a>
                ${res.saved ? `<a href="${esc(app.dataset.portalUrl)}">My portal</a>` : ''}
                <button type="button" data-again>Take this test again</button>
                <a href="${esc(window.EWPlanUrl ? window.EWPlanUrl('IELTS ' + word + ' band ' + res.band) : '/tools/study-planner')}">Make my study plan</a>
            </div>
        </div>`;
        document.body.innerHTML = '';
        document.body.appendChild(wrap);
        if (window.EWResultsPage) window.EWResultsPage();
        const vc = wrap.querySelector('[data-vocab]');
        if (vc) {
            const sel = vc.querySelector('[data-vocab-lang]');
            const setLinks = () => vc.querySelectorAll('[data-vocab-go]').forEach(a => { a.href = vc.dataset[a.dataset.vocabGo] + (sel.value ? '?lang=' + sel.value : ''); });
            sel.addEventListener('change', () => { try { localStorage.setItem('ew-vocab-lang', sel.value); } catch (_) {} setLinks(); });
            setLinks();
            // The PDF is made in the browser and downloaded straight away (no new page).
            const pdfBtn = vc.querySelector('[data-vocab-pdf]');
            pdfBtn.addEventListener('click', async e => {
                e.preventDefault();
                if (pdfBtn.dataset.busy) return;
                const label = pdfBtn.textContent; pdfBtn.dataset.busy = '1'; pdfBtn.textContent = 'Preparing your PDF…';
                try {
                    if (!window.EWVocabPdf) await new Promise((ok, no) => { const sc = document.createElement('script'); sc.src = vc.dataset.script; sc.onload = ok; sc.onerror = no; document.head.appendChild(sc); });
                    const o = await fetch(vc.dataset.data + (sel.value ? '?lang=' + sel.value : ''), { headers: { Accept: 'application/json' } }).then(r => r.ok ? r.json() : Promise.reject(new Error('Error ' + r.status)));
                    await window.EWVocabPdf.download(o);
                } catch (err) { window.alert('Sorry, the PDF could not be made. Please try again.'); }
                pdfBtn.textContent = label; delete pdfBtn.dataset.busy;
            });
        }
        wrap.querySelector('[data-again]').addEventListener('click', () => window.location.reload());
    }

    // No "find on page" during the test (Ctrl+F / Cmd+F, F3, Ctrl+G).
    window.addEventListener('keydown', e => {
        const k = (e.key || '').toLowerCase();
        if (((e.ctrlKey || e.metaKey) && (k === 'f' || k === 'g')) || e.key === 'F3') e.preventDefault();
    }, true);

    window.addEventListener('beforeunload', e => { if (!finished && Object.keys(answers).length) { e.preventDefault(); e.returnValue = ''; } });

    /* ---------- restore + start ---------- */
    function restore(a) {
        Object.entries(a || {}).forEach(([q, v]) => {
            if (Array.isArray(v) && $(`.rd-blank[data-q="${q}"]`)) {
                v.forEach((val, b) => { const inp = $(`.rd-blank[data-q="${q}"][data-b="${b}"]`); if (inp) inp.value = val || ''; });
                answers[q] = v;
            } else if (Array.isArray(v)) {
                v.forEach(l => { const c = $(`input[data-two][data-q="${q}"][value="${l}"]`); if (c) c.checked = true; });
                answers[q] = v;
            } else if (typeof v === 'string') {
                const radio = $(`input[type=radio][data-q="${q}"][value="${CSS.escape(v)}"]`);
                const cell = $(`[data-cell][data-q="${q}"][data-value="${CSS.escape(v)}"]`);
                const drop = $(`.rd-drop[data-drop-q="${q}"]`), heading = drop && headingFor(drop.dataset.hg, v);
                if (radio) { radio.checked = true; const ch = $(`[data-chosen="${q}"]`); if (ch) ch.textContent = v; answers[q] = v; }
                else if (cell) { cell.classList.add('is-chosen'); answers[q] = v; }
                else if (heading && drop) { place(heading, drop); }
            }
        });
    }
    restore(saved.answers);
    applyPrefs();
    showPart(0);
    paintNav();
    save();
    const intro = $('[data-intro]');

    /* ---------- Listening: the recording (starts with "Start test", plays once, volume slider) ---------- */
    const resume = $('[data-resume]');
    if (audio) {
        const vol = $('[data-volume]');
        audio.volume = typeof prefs.volume === 'number' ? prefs.volume : 1;
        if (vol) {
            vol.value = Math.round(audio.volume * 100);
            vol.addEventListener('input', () => { audio.volume = vol.value / 100; prefs.volume = audio.volume; save(); });
        }
        let lastSave = 0;
        audio.addEventListener('timeupdate', () => { if (Date.now() - lastSave > 3000) { lastSave = Date.now(); save(); } });
        audio.addEventListener('ended', save);
    }
    // The box over the test: "Continue" (the browser needs a click before it plays sound) or, when the
    // recording itself cannot be loaded, "Try again" plus a way to answer without it – never an endless loop.
    function showResume(broken) {
        if (!resume) return;
        $('h1', resume).textContent = broken ? 'The recording could not be played' : 'Your test is in progress';
        $('.rd-intro__lead', resume).textContent = broken
            ? 'Please check your internet connection and click Try again. If it still does not play, you can close this box and answer the questions without the recording.'
            : 'Click Continue to carry on with the recording.';
        $('[data-continue]', resume).textContent = broken ? 'Try again' : 'Continue →';
        let skip = $('[data-skip-audio]', resume);
        if (broken && !skip) {
            skip = document.createElement('button');
            skip.type = 'button'; skip.className = 'rd-intro__skip'; skip.setAttribute('data-skip-audio', '');
            skip.textContent = 'Answer without the recording';
            skip.style.cssText = 'display:block;margin:14px auto 0;background:none;border:0;color:#5d6679;text-decoration:underline;cursor:pointer;font:inherit';
            skip.addEventListener('click', () => { resume.hidden = true; });
            $('[data-continue]', resume).after(skip);
        }
        if (skip) skip.hidden = !broken;
        resume.hidden = false;
    }
    function playAudio() {
        if (!audio || audio.ended) return;
        if (audio.error) { audio.load(); }            // try to fetch the file again after a failure
        if (resumed && saved.audioAt) { try { audio.currentTime = saved.audioAt; } catch (_) {} }
        const p = audio.play();
        if (p && p.catch) p.catch(err => showResume(!(err && err.name === 'NotAllowedError')));
    }
    if (resume) $('[data-continue]', resume).addEventListener('click', () => { resume.hidden = true; playAudio(); });

    function begin() {
        if (!startedAt) startedAt = Date.now();
        if (intro) intro.hidden = true;
        if (audio) { if (resumed) { if (resume) resume.hidden = false; } else playAudio(); }
        save();
        tick();
        clearInterval(ticking);
        ticking = setInterval(tick, 1000);
    }
    if (intro && !resumed) {
        $('[data-start]', intro).addEventListener('click', begin);
    } else {
        begin();
    }
})();
