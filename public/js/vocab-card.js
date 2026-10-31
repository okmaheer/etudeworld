/* "Words from this test" card for results pages (CELPIP). Language choice, PDF download and a link to the word list. */
(function () {
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
    window.EWVocabCard = {
        html: function (json) {
            var v; try { v = JSON.parse(json || ''); } catch (_) { return ''; }
            if (!v || !v.count) { return ''; }
            var lang = ''; try { lang = localStorage.getItem('ew-vocab-lang') || ''; } catch (_) { }
            var opts = Object.keys(v.langs).map(function (c) { return '<option value="' + c + '"' + (c === lang ? ' selected' : '') + '>' + esc(v.langs[c]) + '</option>'; }).join('');
            return '<div class="ew-vc" data-vc data-data="' + esc(v.data) + '" data-script="' + esc(v.script) + '" data-practise="' + esc(v.practise) + '">' +
                '<h3>Words from this test (' + v.count + ' words)</h3>' +
                '<p class="ew-vc__samples">' + v.samples.map(function (w) { return '<span>' + esc(w) + '</span>'; }).join('') + '<em>and more…</em></p>' +
                '<label class="ew-vc__lang">My language: <select data-vc-lang><option value="">English only</option>' + opts + '</select></label>' +
                '<div class="ew-vc__btns"><a href="#" class="is-primary" data-vc-pdf>Download vocabulary PDF</a><a href="#" data-vc-go>Learn these words</a></div></div>';
        },
        bind: function (scope) {
            var vc = scope && scope.querySelector('[data-vc]'); if (!vc || vc.dataset.bound) { return; }
            vc.dataset.bound = '1';
            var sel = vc.querySelector('[data-vc-lang]'), go = vc.querySelector('[data-vc-go]'), pdf = vc.querySelector('[data-vc-pdf]');
            var setLinks = function () { go.href = vc.dataset.practise + (sel.value ? '?lang=' + sel.value : ''); };
            sel.addEventListener('change', function () { try { localStorage.setItem('ew-vocab-lang', sel.value); } catch (_) { } setLinks(); });
            setLinks();
            pdf.addEventListener('click', async function (e) {
                e.preventDefault(); if (pdf.dataset.busy) { return; }
                var label = pdf.textContent; pdf.dataset.busy = '1'; pdf.textContent = 'Preparing your PDF…';
                try {
                    if (!window.EWVocabPdf) { await new Promise(function (ok, no) { var s = document.createElement('script'); s.src = vc.dataset.script; s.onload = ok; s.onerror = no; document.head.appendChild(s); }); }
                    var o = await fetch(vc.dataset.data + (sel.value ? '?lang=' + sel.value : ''), { headers: { Accept: 'application/json' } }).then(function (r) { return r.ok ? r.json() : Promise.reject(new Error('Error ' + r.status)); });
                    await window.EWVocabPdf.download(o);
                } catch (err) { window.alert('Sorry, the PDF could not be made. Please try again.'); }
                pdf.textContent = label; delete pdf.dataset.busy;
            });
        }
    };
})();
