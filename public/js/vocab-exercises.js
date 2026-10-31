(function () {
    var root = document.getElementById('vbx'); if (!root || !window.VBX) { return; }
    var all = window.VBX.words, area = document.getElementById('vbx-area'), LEN = 10;
    var e = function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
    var shuffle = function (a) { a = a.slice(); for (var i = a.length - 1; i > 0; i--) { var j = Math.floor(Math.random() * (i + 1)); var t = a[i]; a[i] = a[j]; a[j] = t; } return a; };
    var esc = function (s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); };

    function pick(pool, correct, n, field) {
        var others = shuffle(pool.filter(function (w) { return w[field] !== correct[field]; })).slice(0, n - 1);
        return shuffle(others.concat([correct]));
    }
    function build(kind) {
        var pool = all.filter(function (w) { return w.meaning; }), qs = [];
        if (kind === 'blank') {
            pool = all.filter(function (w) { return w.example && new RegExp(esc(w.word), 'i').test(w.example); });
        }
        if (pool.length < 4) { return null; }
        shuffle(pool).slice(0, LEN).forEach(function (w) {
            if (kind === 'meaning') {
                qs.push({ title: w.word, prompt: 'Choose the correct meaning.', opts: pick(all.filter(function (x) { return x.meaning; }), w, 4, 'meaning').map(function (x) { return { t: x.meaning, ok: x === w }; }) });
            } else {
                qs.push({ title: w.example.replace(new RegExp(esc(w.word), 'i'), '_____'), prompt: 'Choose the word that fits the blank.', opts: pick(pool, w, 4, 'word').map(function (x) { return { t: x.word, ok: x === w }; }) });
            }
        });
        return qs;
    }

    function run(kind) {
        var qs = build(kind), i = 0, score = 0;
        if (!qs) { area.innerHTML = '<p class="vb-empty">There are not enough words for this exercise yet.</p>'; return; }
        function show() {
            if (i >= qs.length) {
                area.innerHTML = '<div class="vb-q vb-score"><b>' + score + ' / ' + qs.length + '</b><p>' + (score === qs.length ? 'Perfect score!' : score >= qs.length * 0.7 ? 'Well done!' : 'Keep practising – try again.') + '</p><button type="button" class="vb-btn is-primary" id="vbx-again">Try again</button></div>';
                document.getElementById('vbx-again').addEventListener('click', function () { run(kind); });
                return;
            }
            var q = qs[i];
            area.innerHTML = '<div class="vb-q"><p class="prompt">Question ' + (i + 1) + ' of ' + qs.length + ' · ' + q.prompt + '</p><h3>' + e(q.title) + '</h3><div class="vb-opts">' +
                q.opts.map(function (o, k) { return '<button type="button" class="vb-opt" data-k="' + k + '">' + e(o.t) + '</button>'; }).join('') + '</div></div>';
            area.querySelector('.vb-opts').addEventListener('click', function (ev) {
                var b = ev.target.closest('.vb-opt'); if (!b || b.disabled) { return; }
                var o = q.opts[b.dataset.k]; if (o.ok) { score++; }
                area.querySelectorAll('.vb-opt').forEach(function (x, k) { x.disabled = true; if (q.opts[k].ok) { x.classList.add('ok'); } else if (x === b) { x.classList.add('no'); } });
                setTimeout(function () { i++; show(); }, o.ok ? 700 : 1500);
            });
        }
        show();
    }
    root.querySelectorAll('[data-ex]').forEach(function (t) {
        t.addEventListener('click', function () {
            root.querySelectorAll('[data-ex]').forEach(function (x) { x.classList.toggle('is-active', x === t); });
            run(t.dataset.ex);
        });
    });
    run('meaning');
})();
