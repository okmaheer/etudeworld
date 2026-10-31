(function () {
    var root = document.getElementById('vb'); if (!root || !window.VB) { return; }
    var words = window.VB.words, key = 'ew-vocab-known-' + root.dataset.key, rtl = ['ar', 'ur'];
    var $ = function (id) { return document.getElementById(id); };
    var e = function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
    var known = {}, lang = '', mode = 'list', idx = 0, flipped = false, shown = words;
    known = window.VB.known || {};
    try { lang = localStorage.getItem('ew-vocab-lang') || ''; } catch (_) { }
    $('vb-lang').value = lang; if ($('vb-lang').value !== lang) { lang = ''; }

    var saveTimer;
    function save() {
        if (!window.VB.save) { return; }   // not logged in: nothing is saved
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function () {
            var t = document.querySelector('meta[name="csrf-token"]');
            fetch(window.VB.save, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': t ? t.content : '' }, body: JSON.stringify({ known: Object.keys(known) }) }).catch(function () { });
        }, 400);
    }
    function isPhrase(w) { return /\s/.test(w.word.trim()); }
    function filter() {
        var q = $('vb-search').value.trim().toLowerCase(), ph = $('vb-phrases').checked;
        shown = words.filter(function (w) {
            return (!ph || isPhrase(w)) && (!q || (w.word + ' ' + w.meaning + ' ' + (w.tr[lang] || '')).toLowerCase().indexOf(q) !== -1);
        });
        if (idx >= shown.length) { idx = 0; }
    }
    function progress() {
        var n = words.filter(function (w) { return known[w.id]; }).length;
        $('vb-summary').textContent = words.length + ' words and phrases · ' + n + ' learned';
        $('vb-bar').style.width = (words.length ? n * 100 / words.length : 0) + '%';
    }
    function trHtml(w) {
        var t = lang && w.tr[lang]; return t ? '<p class="tr"' + (rtl.indexOf(lang) > -1 ? ' dir="rtl"' : '') + '>' + e(t) + '</p>' : '';
    }
    function drawList() {
        $('vb-list').innerHTML = shown.length ? shown.map(function (w) {
            return '<article class="vb-word' + (known[w.id] ? ' is-known' : '') + '"><div class="vb-word__head"><span class="vb-word__w">' + e(w.word) + '</span><span class="vb-word__t">' + e(w.type) + '</span>' +
                '<button type="button" class="vb-known" data-known="' + w.id + '" aria-pressed="' + (known[w.id] ? 'true' : 'false') + '">' + (known[w.id] ? 'I know this ✓' : 'Mark as learned') + '</button></div>' +
                '<p>' + e(w.meaning) + '</p>' + trHtml(w) + (w.synonyms ? '<p class="syn">Similar: ' + e(w.synonyms) + '</p>' : '') + (w.example ? '<p class="ex">' + e(w.example) + '</p>' : '') + '</article>';
        }).join('') : '<p class="vb-empty">No words match your search.</p>';
    }
    function drawCard() {
        var w = shown[idx]; if (!w) { $('vb-card').innerHTML = '<p class="vb-empty">No words match your search.</p>'; $('vb-pos').textContent = ''; return; }
        $('vb-card').innerHTML = flipped ? '<div>' + e(w.meaning) + '</div>' + trHtml(w) + (w.synonyms ? '<div class="t">Similar: ' + e(w.synonyms) + '</div>' : '') + (w.example ? '<div class="ex">' + e(w.example) + '</div>' : '')
            : '<div class="w">' + e(w.word) + '</div><div class="t">' + e(w.type) + '</div>';
        $('vb-pos').textContent = (idx + 1) + ' / ' + shown.length;
    }
    function draw() { filter(); progress(); drawList(); drawCard(); }

    $('vb-list').addEventListener('click', function (ev) {
        var b = ev.target.closest('[data-known]'); if (!b) { return; }
        var id = b.dataset.known; if (known[id]) { delete known[id]; } else { known[id] = 1; }
        save(); draw();
    });
    $('vb-lang').addEventListener('change', function () { lang = this.value; try { localStorage.setItem('ew-vocab-lang', lang); } catch (_) { } draw(); });
    $('vb-search').addEventListener('input', function () { idx = 0; draw(); });
    $('vb-phrases').addEventListener('change', function () { idx = 0; draw(); });
    $('vb-mode').addEventListener('click', function () {
        mode = mode === 'list' ? 'cards' : 'list';
        $('vb-list').hidden = mode !== 'list'; $('vb-cards').hidden = mode !== 'cards';
        this.textContent = mode === 'list' ? 'Flashcards' : 'Word list'; idx = 0; flipped = false; draw();
    });
    $('vb-card').addEventListener('click', function () { flipped = !flipped; drawCard(); });
    $('vb-card').addEventListener('keydown', function (ev) { if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); flipped = !flipped; drawCard(); } });
    function go(d) { if (!shown.length) { return; } idx = (idx + d + shown.length) % shown.length; flipped = false; drawCard(); }
    $('vb-next').addEventListener('click', function () { go(1); });
    $('vb-prev').addEventListener('click', function () { go(-1); });
    document.addEventListener('keydown', function (ev) { if (mode !== 'cards' || ev.target.matches('input,select')) { return; } if (ev.key === 'ArrowRight') { go(1); } if (ev.key === 'ArrowLeft') { go(-1); } });

    // PDF made in the browser, downloaded straight away
    $('vb-pdf').addEventListener('click', async function () {
        var btn = this; if (btn.dataset.busy) { return; }
        var label = btn.textContent; btn.dataset.busy = '1'; btn.textContent = 'Preparing your PDF…';
        try {
            if (!window.EWVocabPdf) { await new Promise(function (ok, no) { var sc = document.createElement('script'); sc.src = root.dataset.script; sc.onload = ok; sc.onerror = no; document.head.appendChild(sc); }); }
            var o = await fetch(root.dataset.data + (lang ? '?lang=' + lang : ''), { headers: { Accept: 'application/json' } }).then(function (r) { return r.ok ? r.json() : Promise.reject(); });
            await window.EWVocabPdf.download(o);
        } catch (_) { window.alert('Sorry, the PDF could not be made. Please try again.'); }
        btn.textContent = label; delete btn.dataset.busy;
    });
    draw();
})();
