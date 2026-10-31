/*
 * Etude World – "Download the results" button on the Listening / Reading results screen.
 * Makes a PDF of the results (score, CELPIP level table and every answer) and downloads it.
 * Uses html2pdf (public/vendor/html2pdf), which is loaded only when the button is first clicked.
 */
(function () {
    let loading = null;
    function loadLibrary() {
        if (window.html2pdf) return Promise.resolve();
        if (!loading) {
            loading = new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = window.EW_HTML2PDF;
                s.onload = resolve;
                s.onerror = () => { loading = null; reject(new Error('load')); };
                document.head.appendChild(s);
            });
        }
        return loading;
    }

    const clean = s => String(s || '').replace(/[\\/:*?"<>|]+/g, '').replace(/\s+/g, ' ').trim();
    const esc = s => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    document.addEventListener('click', async e => {
        const btn = e.target.closest('[data-results-download]');
        if (!btn) return;
        const step = btn.closest('[data-step]');
        const results = step && step.querySelector('.lsx-end');
        if (!results) return;

        const module = btn.dataset.resultsDownload || 'Test';
        const testName = (document.querySelector('.exam-bar__title strong') || {}).textContent || 'CELPIP Practice Test';
        const date = new Date().toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
        const label = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Preparing PDF…';

        try {
            await loadLibrary();
            const sheet = document.createElement('div');
            sheet.className = 'ew-pdf';
            sheet.innerHTML = `
                <header class="ew-pdf__head">
                    <div class="ew-pdf__brand">Etude <strong>World</strong></div>
                    <div class="ew-pdf__meta"><strong>${esc(testName)}</strong><span>${esc(module)} results · ${esc(date)}</span></div>
                </header>`;
            const copy = results.cloneNode(true);
            copy.hidden = false;
            sheet.appendChild(copy);

            const y = window.scrollY;
            window.scrollTo(0, 0);
            await window.html2pdf().set({
                margin: [12, 12, 14, 12],
                filename: clean(`${testName} - ${module} results`) + '.pdf',
                image: { type: 'jpeg', quality: 0.95 },
                html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', scrollX: 0, scrollY: 0 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['css', 'legacy'], avoid: ['li', 'tr', '.rdx-result', '.ew-pdf__head', 'h3'] },
            }).from(sheet).save();
            window.scrollTo(0, y);
        } catch (_) {
            window.alert('Sorry, the PDF could not be made. Please try again.');
        } finally {
            btn.disabled = false;
            btn.textContent = label;
        }
    });
})();
