(function () {
    var root = document.getElementById('sp'); if (!root || !window.SPELL) { return; }
    var all = window.SPELL.words, letter = '', q = '';
    var $ = function (id) { return document.getElementById(id); };
    var e = function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
    var shuffle = function (a) { a = a.slice(); for (var i = a.length - 1; i > 0; i--) { var j = Math.floor(Math.random() * (i + 1)); var t = a[i]; a[i] = a[j]; a[j] = t; } return a; };
    var canSpeak = 'speechSynthesis' in window;

    function speak(text) {
        if (!canSpeak) { return; }
        try { window.speechSynthesis.cancel(); var u = new SpeechSynthesisUtterance(text); u.lang = 'en-GB'; u.rate = 0.85; window.speechSynthesis.speak(u); } catch (_) { }
    }

    // A–Z buttons (only letters that have words)
    var letters = {}; all.forEach(function (w) { letters[w.w.charAt(0).toUpperCase()] = 1; });
    $('sp-az').innerHTML = '<button type="button" class="is-active" data-l="">All</button>' + Object.keys(letters).sort().map(function (l) { return '<button type="button" data-l="' + l + '">' + l + '</button>'; }).join('');
    $('sp-az').addEventListener('click', function (ev) {
        var b = ev.target.closest('[data-l]'); if (!b) { return; }
        letter = b.dataset.l; Array.prototype.forEach.call($('sp-az').children, function (x) { x.classList.toggle('is-active', x === b); }); draw();
    });
    $('sp-search').addEventListener('input', function () { q = this.value.trim().toLowerCase(); draw(); });

    function shown() {
        return all.filter(function (w) { return (!letter || w.w.charAt(0).toUpperCase() === letter) && (!q || w.w.toLowerCase().indexOf(q) > -1 || w.alt.join(' ').toLowerCase().indexOf(q) > -1); });
    }
    function draw() {
        var list = shown(), groups = {};
        list.forEach(function (w) { (groups[w.cat] = groups[w.cat] || []).push(w); });
        $('sp-count').textContent = list.length + (list.length === 1 ? ' word' : ' words');
        $('sp-list').innerHTML = Object.keys(groups).sort(function (a, b) { var o = window.SPELL.order || [], x = o.indexOf(a), y = o.indexOf(b); return (x < 0 ? 99 : x) - (y < 0 ? 99 : y) || a.localeCompare(b); }).map(function (c) {
            return '<h2 class="vb-h2">' + e(c) + '</h2><div class="sp-words">' + groups[c].map(function (w) {
                return '<div class="sp-word"><button type="button" class="sp-say" data-say="' + e(w.w) + '" aria-label="Listen to ' + e(w.w) + '"' + (canSpeak ? '' : ' hidden') + '>🔊</button><strong>' + e(w.w) + '</strong>' +
                    (w.alt.length ? '<span class="sp-alt">also ' + e(w.alt.join(', ')) + '</span>' : '') + '<small>in ' + w.n + (w.n === 1 ? ' test' : ' tests') + '</small></div>';
            }).join('') + '</div>';
        }).join('') || '<p class="vb-empty">No words match.</p>';
    }
    $('sp-list').addEventListener('click', function (ev) { var b = ev.target.closest('[data-say]'); if (b) { speak(b.dataset.say); } });
    draw();

    // Practice: the browser says a word, the student types it
    $('sp-practise').addEventListener('click', function () {
        var pool = shuffle(shown().length >= 3 ? shown() : all).slice(0, 10), i = 0, score = 0, quiz = $('sp-quiz');
        $('sp-list').hidden = true; $('sp-az').hidden = true; quiz.hidden = false;
        function back() { quiz.hidden = true; $('sp-list').hidden = false; $('sp-az').hidden = false; window.scrollTo(0, 0); }
        var backBtn = '<p><button type="button" class="vb-btn" data-back>← Back to the word list</button></p>';
        quiz.onclick = function (ev) { if (ev.target.closest('[data-back]')) { back(); } };
        function ask() {
            if (i >= pool.length) {
                quiz.innerHTML = '<div class="vbx-done"><h2>You scored ' + score + ' out of ' + pool.length + '</h2><p><button type="button" class="vb-btn is-primary" id="sp-again">Try again</button> <button type="button" class="vb-btn" data-back>Back to the word list</button></p></div>';
                $('sp-again').onclick = function () { $('sp-practise').click(); };
                return;
            }
            var w = pool[i];
            quiz.innerHTML = '<div class="sp-q"><p class="vb-count">Word ' + (i + 1) + ' of ' + pool.length + '</p>' +
                (canSpeak ? '<button type="button" class="vb-btn is-primary" id="sp-play">🔊 Listen to the word</button>' : '<p class="vb-empty">Your browser cannot read words aloud. Use a different browser to practise.</p>') +
                '<form id="sp-form"><input id="sp-input" type="text" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Type the word you hear" aria-label="Type the word you hear"><button class="vb-btn is-primary">Check</button></form><p id="sp-msg" class="sp-msg"></p>' + backBtn + '</div>';
            if ($('sp-play')) { $('sp-play').onclick = function () { speak(w.w); }; speak(w.w); }
            $('sp-input').focus();
            $('sp-form').onsubmit = function (ev) {
                ev.preventDefault();
                var norm = function (s) { return s.trim().toLowerCase().replace(/\s+/g, ' '); }, v = norm($('sp-input').value);
                var ok = v === norm(w.w) || w.alt.some(function (a) { return v === norm(a); });
                if (ok) { score++; }
                $('sp-msg').innerHTML = ok ? '<span class="sp-ok">Correct ✓</span>' : '<span class="sp-bad">Not correct. The correct spelling is <strong>' + e(w.w) + '</strong></span>';
                if (canSpeak) { $('sp-msg').insertAdjacentHTML('beforeend', ' <button type="button" class="sp-say" id="sp-say2" aria-label="Listen again">🔊</button>'); $('sp-say2').onclick = function () { speak(w.w); }; }
                $('sp-form').innerHTML = '<button class="vb-btn is-primary" id="sp-next" autofocus>' + (i + 1 < pool.length ? 'Next word →' : 'See my score') + '</button>';
                $('sp-next').focus(); $('sp-next').onclick = function (e2) { e2.preventDefault(); i++; ask(); };
            };
        }
        ask();
    });
})();
