/* Shared by every practice test (IELTS, CELPIP, TOEFL; Listening, Reading, Writing, Speaking).
   When the results screen is shown, the address changes to  …/{test}/results  and the page gets its
   own title and description. Going back or reloading returns to the test page. */
(function () {
    var done = false;
    // The test page's own address (without /results) and the list of tests of the same kind.
    var base = location.pathname.replace(/\/+$/, '').replace(/\/results$/, '');
    var parts = base.split('/');
    var listUrl = parts.slice(0, -1).join('/');
    var kind = parts[parts.length - 2] || 'practice';
    kind = kind === 'mock-tests' ? 'mock' : kind;

    /* Address of the Study Planner with the exam and the skill of this test filled in (and the score, when given). */
    window.EWPlanUrl = function (current) {
        var exam = '', weak = '';
        parts.forEach(function (x) {
            if (x === 'celpip') { exam = 'celpip'; }
            if (x === 'toefl') { exam = 'toefl'; }
            if (x === 'ielts' && !exam) { exam = 'ielts-academic'; }
            if (exam.indexOf('ielts') === 0 && (x === 'general-training' || x === 'gt' || x === 'general')) { exam = 'ielts-gt'; }
            if (/^(listening|reading|writing|speaking)$/.test(x)) { weak = x.charAt(0).toUpperCase() + x.slice(1); }
        });
        var q = [];
        if (exam) { q.push('exam=' + exam); }
        if (weak) { q.push('weak=' + weak); }
        if (current) { q.push('current=' + encodeURIComponent(current)); }
        return '/tools/study-planner' + (q.length ? '?' + q.join('&') : '');
    };

    /* "More … tests" and "Take this test again" buttons, added once inside the given element. */
    window.EWResultsActions = function (parent) {
        if (!parent || parent.querySelector('.ew-res-actions')) { return; }
        if (!document.getElementById('ew-res-actions-css')) {
            var st = document.createElement('style'); st.id = 'ew-res-actions-css';
            st.textContent = '.ew-res-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin:22px 0 4px}' +
                '.ew-res-actions a{font:700 14px Arial,Helvetica,sans-serif;padding:11px 20px;border-radius:8px;border:1px solid #c3c9d3;background:#fff;color:#13234a;text-decoration:none}' +
                '.ew-res-actions a.is-primary{background:#2f6bff;border-color:#2f6bff;color:#fff}';
            document.head.appendChild(st);
        }
        var d = document.createElement('div'); d.className = 'ew-res-actions';
        d.innerHTML = '<a class="is-primary" href="' + listUrl + '">More ' + kind + ' tests</a><a href="' + base + '">Take this test again</a><a href="' + window.EWPlanUrl(window.EWResultLevel || '') + '">Make my study plan</a>';
        parent.appendChild(d);
    };
    window.EWResultsPage = function () {
        if (done) { return; }
        var path = location.pathname.replace(/\/+$/, '');
        if (/\/results$/.test(path)) { return; }
        done = true;
        var name = (document.title || '').replace(/\s+[–|-]\s+[^–|-]*$/, '').trim() || 'your practice test';
        var site = (document.title.match(/[–|-]\s*([^–|-]+)$/) || [])[1];
        var title = 'Results – ' + name + (site ? ' – ' + site.trim() : '');
        var desc = 'Your score, correct answers and feedback for ' + name + '. Review your results and keep practising.';
        try { history.pushState({ ewResults: 1 }, '', path + '/results' + location.search); } catch (_) { }
        document.title = title;
        var m = document.querySelector('meta[name="description"]');
        if (!m) { m = document.createElement('meta'); m.name = 'description'; document.head.appendChild(m); }
        m.setAttribute('content', desc);
        window.addEventListener('popstate', function () { location.reload(); });
    };
})();
