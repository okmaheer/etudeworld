(function () {
    var box = document.querySelector('[data-tool]');
    if (!box) { return; }
    var tool = box.getAttribute('data-tool');
    var out = document.getElementById('out');
    var f1 = function (n) { return Number(n).toFixed(1); };
    var $ = function (id) { return document.getElementById(id); };
    var table = function (head, rows) {
        return '<table class="tool-table"><thead><tr>' + head.map(function (h) { return '<th>' + h + '</th>'; }).join('') +
            '</tr></thead><tbody>' + rows.map(function (r) {
                return '<tr>' + r.map(function (c, i) { return i ? '<td>' + c + '</td>' : '<th scope="row">' + c + '</th>'; }).join('') + '</tr>';
            }).join('') + '</tbody></table>';
    };

    /* ---- CEFR (IELTS guide) ---- */
    var CEFR = [
        [8.5, 'C2', 'Proficient: understands virtually everything and expresses ideas fluently and precisely.'],
        [7, 'C1', 'Advanced: handles demanding texts and speaks fluently and flexibly.'],
        [5.5, 'B2', 'Upper intermediate: deals with complex topics and speaks without much searching for words.'],
        [4, 'B1', 'Intermediate: handles everyday situations and writes simple connected text.'],
        [3, 'A2', 'Elementary: understands common phrases and handles simple, routine tasks.'],
        [0, 'A1', 'Beginner: uses very basic phrases for immediate needs.']
    ];
    var cefr = function (b) { for (var i = 0; i < CEFR.length; i++) { if (b >= CEFR[i][0]) { return CEFR[i]; } } return CEFR[CEFR.length - 1]; };

    /* ---- TOEFL: IELTS -> [R, L, W, S, Total] on the 1-6 scale;  ---- */
    var TOEFL_NEW = {
        9: [6, 6, 6, 6, 6], 8.5: [5.5, 6, 6, 6, 6], 8: [5.5, 5.5, 6, 6, 6], 7.5: [5, 5, 5.5, 5, 5.5], 7: [4.5, 5, 5, 4.5, 5],
        6.5: [4, 4.5, 4.5, 4, 4.5], 6: [3.5, 3.5, 4, 3.5, 4], 5.5: [3.5, 3, 3, 3, 3.5], 5: [3, 2.5, 2, 2.5, 2.5],
        4.5: [2.5, 1.5, 1.5, 2, 2], 4: [1, 1, 1, 1.5, 1.5]
    };
    /* ---- PTE: IELTS -> [Overall, L, R, S, W] ---- */
    var PTE = {
        5: [40.8, 40.2, 43.0, 40.2, 43.1], 5.5: [45.4, 42.7, 47.9, 42.2, 51.0], 6: [51.6, 48.1, 53.5, 46.2, 62.2],
        6.5: [58.5, 56.8, 60.6, 53.5, 74.1], 7: [66.3, 66.2, 67.8, 65.3, 82.3], 7.5: [74.6, 73.9, 73.7, 75.3, 87.5],
        8: [82.3, 79.4, 78.4, 80.9, 89.4], 8.5: [88.1, 84.7, 83.7, 85.5, 89.5]
    };
    /* ---- CLB: minimum IELTS General Training band per skill (IRCC) ---- */
    var CLB = { // [clb, L, R, W, S]
        rows: [[10, 8.5, 8, 7.5, 7.5], [9, 8, 7, 7, 7], [8, 7.5, 6.5, 6.5, 6.5], [7, 6, 6, 6, 6], [6, 5.5, 5, 5.5, 5.5], [5, 5, 4, 5, 5], [4, 4.5, 3.5, 4, 4]]
    };
    var clbFor = function (skillIndex, band) {
        for (var i = 0; i < CLB.rows.length; i++) { if (band >= CLB.rows[i][skillIndex]) { return CLB.rows[i][0]; } }
        return 0;
    };

    var empty = function (t) { out.innerHTML = '<p class="tool-empty">' + t + '</p>'; };
    // Reads typed bands. Returns an array of numbers, or null after showing a message.
    var read = function (ids, lo, hi, askText) {
        var vals = [], blank = false;
        for (var i = 0; i < ids.length; i++) {
            var raw = $(ids[i]).value.trim().replace(',', '.');
            if (raw === '') { blank = true; continue; }
            var n = Number(raw);
            if (isNaN(n) || n * 2 !== Math.round(n * 2)) { empty('Type a band score in steps of 0.5, for example 6 or 6.5.'); return null; }
            if (n < lo || n > hi) { empty('This tool covers IELTS bands ' + f1(lo) + ' to ' + f1(hi) + '.'); return null; }
            vals.push(n);
        }
        if (blank) { empty(askText); return null; }
        return vals;
    };

    if (tool === 'ielts-calculator') {
        var run = function () {
            var v = read(['l', 'r', 'w', 's'], 0, 9, 'Type all four bands to see your overall score.');
            if (!v) { return; }
            var avg = v.reduce(function (a, b) { return a + b; }, 0) / 4, fl = Math.floor(avg), fr = avg - fl;
            var overall = fr < 0.25 ? fl : (fr < 0.75 ? fl + 0.5 : fl + 1);
            var c = cefr(overall);
            out.innerHTML = '<div class="tool-big"><b>' + f1(overall) + '</b><span>Overall band (average ' + avg.toFixed(2) + ')</span></div>' +
                '<p>Approximate CEFR level: <strong>' + c[1] + '</strong>. ' + c[2] + '</p>';
        };
    } else if (tool === 'ielts-to-toefl') {
        var run = function () {
            var r = read(['band'], 4, 9, 'Type your IELTS band.'); if (!r) { return; }
            var b = r[0], n = TOEFL_NEW[b];
            out.innerHTML = '<div class="tool-big"><b>' + n[4].toFixed(1) + '</b><span>TOEFL iBT total (1–6 scale)</span></div>' +
                table(['IELTS ' + f1(b), 'Reading', 'Listening', 'Writing', 'Speaking', 'Total'], [['TOEFL'].concat(n.map(function (x) { return x.toFixed(1); }))]);
        };
    } else if (tool === 'ielts-to-pte') {
        var run = function () {
            var r = read(['band'], 5, 8.5, 'Type your IELTS band.'); if (!r) { return; }
            var b = r[0], p = PTE[b];
            out.innerHTML = '<div class="tool-big"><b>' + p[0].toFixed(0) + '</b><span>PTE Academic overall (about ' + p[0].toFixed(1) + ')</span></div>' +
                table(['IELTS ' + f1(b), 'Listening', 'Reading', 'Speaking', 'Writing'], [['PTE Academic', p[1].toFixed(0), p[2].toFixed(0), p[3].toFixed(0), p[4].toFixed(0)]]);
        };
    } else if (tool === 'ielts-to-celpip') {
        var run = function () {
            var ids = ['l', 'r', 'w', 's'], names = ['Listening', 'Reading', 'Writing', 'Speaking'];
            var v = read(ids, 0, 9, 'Type all four bands to see your CLB and CELPIP levels.');
            if (!v) { return; }
            var rows = v.map(function (b, i) {
                var c = clbFor(i + 1, b), txt = c === 0 ? 'Below CLB 4' : (c === 10 ? 'CLB 10 or higher' : 'CLB ' + c);
                return [names[i], f1(b), txt, c === 0 ? '–' : (c === 10 ? '10+' : String(c))];
            });
            out.innerHTML = table(['Skill', 'IELTS band', 'CLB level', 'CELPIP level'], rows);
        };
    } else if (tool === 'ielts-to-cefr') {
        var run = function () {
            var r = read(['band'], 0, 9, 'Type your IELTS band.'); if (!r) { return; }
            var b = r[0], c = cefr(b);
            out.innerHTML = '<div class="tool-big"><b>' + c[1] + '</b><span>IELTS ' + f1(b) + '</span></div><p>' + c[2] + '</p>' +
                table(['CEFR', 'IELTS bands'], [['C2', '8.5 – 9.0'], ['C1', '7.0 – 8.0'], ['B2', '5.5 – 6.5'], ['B1', '4.0 – 5.0'], ['A2', '3.0 – 3.5'], ['A1', 'Below 3.0']]);
        };
    }
    if (run) {
        box.addEventListener('input', run);
        run();
    }
})();
