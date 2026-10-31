(function () {
    var cfg = window.LEVEL_TEST; if (!cfg) { return; }
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
    var esc = function (t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; };
    var answers = {};

    $('#lt-start').addEventListener('click', function () {
        $('#lt-intro').hidden = true; $('#lt').hidden = false; window.scrollTo({ top: 0 });
    });

    function show(n) {
        $$('[data-section-panel]').forEach(function (p) { p.hidden = String(p.dataset.sectionPanel) !== String(n); });
        $$('[data-tab]').forEach(function (t) { t.classList.toggle('is-active', t.dataset.tab === String(n)); });
        window.scrollTo({ top: 0 });
    }
    function mark(id, has) {
        var b = $('[data-box="' + id + '"]'); if (b) { b.classList.toggle('is-done', has); }
    }
    function onInput(e) {
        var el = e.target, id = el.getAttribute('data-qid'); if (!id) { return; }
        var v = el.type === 'radio' ? el.value : el.value.trim();
        if (v === '') { delete answers[id]; } else { answers[id] = v; }
        mark(id, v !== '');
        warn(true);
    }
    document.addEventListener('input', onInput);
    document.addEventListener('change', onInput);

    $$('[data-tab]').forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.tab); }); });
    $$('[data-box]').forEach(function (b) {
        b.addEventListener('click', function () {
            show(b.dataset.section);
            var q = document.getElementById('q-' + b.dataset.box);
            if (q) {
                q.scrollIntoView({ behavior: 'smooth', block: 'center' });
                q.classList.add('is-flash'); setTimeout(function () { q.classList.remove('is-flash'); }, 1200);
                var f = $('input', q); if (f && f.type === 'text') { f.focus({ preventScroll: true }); }
            }
        });
    });

    var warnEl = $('#lt-warn'), confirming = false;
    function warn(hide) {
        if (hide) { confirming = false; warnEl.hidden = true; $('#lt-finish').textContent = 'Finish Test'; }
    }

    $('#lt-finish').addEventListener('click', function () {
        var left = cfg.total - Object.keys(answers).length;
        if (left > 0 && !confirming) {
            confirming = true;
            warnEl.textContent = left + (left === 1 ? ' question is' : ' questions are') + ' unanswered.';
            warnEl.hidden = false;
            this.textContent = 'Finish anyway';
            return;
        }
        var btn = this; btn.disabled = true; btn.textContent = 'Marking…';
        var token = document.querySelector('meta[name="csrf-token"]');
        fetch(cfg.scoreUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token ? token.content : '' },
            body: JSON.stringify({ answers: answers })
        }).then(function (r) { if (!r.ok) { throw new Error(); } return r.json(); }).then(render).catch(function () {
            btn.disabled = false; btn.textContent = 'Finish Test';
            warnEl.textContent = 'Something went wrong. Please try again.'; warnEl.hidden = false;
        });
    });

    function render(res) {
        $('#lt-questions').hidden = true; $('#lt-bottom').hidden = true; $('.lt-finish-wrap').hidden = true;
        var rows = res.levels.map(function (l) {
            return '<tr class="' + (l.level === res.level ? 'is-you' : '') + '"><td>' + l.level + '</td><td>' + l.from + (l.to > l.from ? ' – ' + l.to : '') + '</td><td>' + esc(l.text) + '</td></tr>';
        }).join('');
        var review = res.review.map(function (r) {
            return '<div class="lt-row ' + (r.ok ? 'ok' : 'no') + '"><div class="lt-row__n">' + r.n + '</div><div><p>' + esc(r.prompt) + '</p>' +
                (r.ok ? '<small class="good">Correct: ' + esc(r.answer) + '</small>' :
                    '<small class="bad">Your answer: ' + (r.yours ? esc(r.yours) : 'no answer') + '</small><small class="good">Correct answer: ' + esc(r.answer) + '</small>') + '</div></div>';
        }).join('');
        var box = $('#lt-result');
        box.innerHTML =
            '<div class="lt-card"><div class="lt-level"><div class="lt-level__badge">' + res.level + '</div><div><h2>Your level: ' + res.level + '</h2>' +
            '<p>You scored <strong>' + res.score + ' out of ' + res.total + '</strong>. ' + esc(res.description) + '</p></div></div>' +
            '<table class="lt-table"><thead><tr><th>Level</th><th>Score</th><th>What it means</th></tr></thead><tbody>' + rows + '</tbody></table>' +
            '<div class="lt-actions"><a class="primary" href="/ielts">Practise IELTS</a><a href="/celpip">Practise CELPIP</a><a href="/tools/ielts-to-cefr-converter">IELTS to CEFR converter</a><button type="button" id="lt-again">Take the test again</button></div></div>' +
            '<div class="lt-card lt-review"><h2>Your answers</h2>' + review + '</div>';
        box.hidden = false; window.scrollTo({ top: 0 });
        $('#lt-again').addEventListener('click', function () { location.reload(); });
    }
})();
