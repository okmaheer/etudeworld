(function () {
    var cfg = window.SP; if (!cfg) { return; }
    var $ = function (id) { return document.getElementById(id); };
    var out = $('out'), plan = null;
    var e = function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };

    // Exam date: from tomorrow, and fill the form from the address (?exam=ielts-gt&target=7&weak=Writing,Speaking)
    var min = new Date(Date.now() + 864e5); $('sp-date').min = min.toISOString().slice(0, 10);
    var q = new URLSearchParams(location.search);
    if (q.get('exam') && $('sp-exam').querySelector('[value="' + q.get('exam') + '"]')) { $('sp-exam').value = q.get('exam'); }
    if (q.get('target')) { $('sp-target').value = q.get('target').slice(0, 40); }
    if (q.get('current')) { $('sp-current').value = q.get('current').slice(0, 60); }
    (q.get('weak') || '').split(',').forEach(function (w) { var c = document.querySelector('input[name=weak][value="' + w.trim() + '"]'); if (c) { c.checked = true; } });

    function render(p) {
        plan = p;
        out.innerHTML = '<div class="sp-summary"><strong>' + e(p.exam) + ' · target ' + e(p.target) + ' · ' + p.hours + ' h/day</strong><p style="margin:6px 0 0">' + e(p.summary) + '</p></div>' +
            (p.steps || []).map(function (w) { return '<div class="sp-week"><h3>' + (p.unit === 'day' ? 'Day ' : 'Week ') + w.n + ': ' + e(w.focus) + '</h3><ul>' + w.tasks.map(function (t) { return '<li>' + e(t) + '</li>'; }).join('') + '</ul></div>'; }).join('') +
            (p.tips.length ? '<ul class="sp-tips">' + p.tips.map(function (t) { return '<li>' + e(t) + '</li>'; }).join('') + '</ul>' : '') +
            '<div class="sp-actions"><button type="button" class="vb-btn is-primary" id="sp-pdf">Download PDF</button></div><p class="tool-note">This is your study plan. Each visitor can make only one plan, so please download the PDF to keep it.</p>';
        $('sp-form').style.display = 'none';
        $('sp-pdf').addEventListener('click', downloadPdf);
        out.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function downloadPdf() {
        var btn = this, label = btn.textContent; btn.textContent = 'Preparing your PDF…'; btn.disabled = true;
        try {
            if (!window.EWVocabPdf) { await new Promise(function (ok, no) { var s = document.createElement('script'); s.src = cfg.pdf; s.onload = ok; s.onerror = no; document.head.appendChild(s); }); }
            var rows = plan.steps.map(function (w) { return { cells: ['<b>' + w.n + '</b>', '<b>' + e(w.focus) + '</b>', w.tasks.map(function (t) { return '• ' + e(t); }).join('<br/>')] }; });
            if (plan.tips.length) { rows.push({ cells: ['', '<b>Tips</b>', plan.tips.map(function (t) { return '• ' + e(t); }).join('<br/>')] }); }
            await window.EWVocabPdf.download({ title: plan.exam + ' study plan', subtitle: 'Target ' + plan.target + ' · exam on ' + plan.date + ' · ' + plan.hours + ' hours a day · Etude World',
                cols: [{ t: plan.unit === 'day' ? 'Day' : 'Week', w: '9%' }, { t: 'Focus', w: '26%' }, { t: 'What to do' }], words: rows, filename: 'study-plan.pdf' });
        } catch (err) { window.alert('Sorry, the PDF could not be made. Please try again.'); }
        btn.textContent = label; btn.disabled = false;
    }

    // A visitor who already made a plan sees it again (and the form stays hidden).
    if (cfg.mine) { fetch(cfg.mine, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) { if (d && d.plan) { render(d.plan); } }).catch(function () { }); }

    $('sp-form').addEventListener('submit', async function (ev) {
        ev.preventDefault();
        var btn = $('sp-go');
        if (!$('sp-target').value.trim() || !$('sp-date').value) { out.innerHTML = '<p class="tool-empty">Please fill in your target score and exam date.</p>'; return; }
        btn.disabled = true; btn.textContent = 'Making your plan… (about 20 seconds)';
        out.innerHTML = '';
        var token = document.querySelector('meta[name="csrf-token"]');
        try {
            var res = await fetch(cfg.url, {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token ? token.content : '' },
                body: JSON.stringify({
                    exam: $('sp-exam').value, target: $('sp-target').value.trim(), current: $('sp-current').value.trim(), date: $('sp-date').value, hours: $('sp-hours').value,
                    weak: Array.prototype.map.call(document.querySelectorAll('input[name=weak]:checked'), function (c) { return c.value; })
                })
            });
            var data = await res.json().catch(function () { return {}; });
            if (res.status === 429 && !data.error) { data.error = 'You have made several plans already. Please try again in a little while.'; }
            if (res.status === 409 && data.plan) { render(data.plan); }
            else if (!res.ok) { out.innerHTML = '<p class="tool-empty">' + e(data.error || (data.errors ? Object.values(data.errors)[0][0] : 'Something went wrong. Please try again.')) + '</p>'; }
            else { render(data); }
        } catch (_) { out.innerHTML = '<p class="tool-empty">Something went wrong. Please check your connection and try again.</p>'; }
        btn.disabled = false; btn.textContent = 'Make my study plan';
    });
})();
