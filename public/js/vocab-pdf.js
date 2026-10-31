/*
 * Vocabulary PDF – builds the word list as a PDF inside the student's browser and downloads it straight away.
 * The browser draws the text itself, so every language (Urdu, Arabic, Chinese, Punjabi …) comes out correctly.
 * Usage: EWVocabPdf.download({ title, subtitle, langName, rtl, words: [{word, type, meaning, synonyms, example, tr}], filename })
 * Any table: pass cols: [{t:'Heading', w:'20%'}] and words: [{cells:['<b>html</b>', ...]}] (cells are HTML, escape text yourself).
 */
(function () {
    const W = 794, H = 1123, M = 40, SCALE = 2;          // A4 at 96 dpi, margin, sharpness
    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const CSS = `*{box-sizing:border-box}div.pg{width:${W}px;height:${H}px;padding:${M}px;background:#fff;color:#17202e;font:14px/1.45 Arial,'Noto Sans','Segoe UI',sans-serif}
        h1{font-size:22px;margin:0 0 2px}p.s{color:#5b6577;margin:0 0 14px}table{width:100%;border-collapse:collapse;table-layout:fixed}
        th,td{border:1px solid #c9d1de;padding:6px 8px;vertical-align:top;text-align:left;word-wrap:break-word}th{background:#f1f4f9}
        .w{font-weight:700}.t{color:#5b6577;font-size:12px}.e{color:#3b4658;font-style:italic}.r{font-size:16px}.rtl{direction:rtl;text-align:right}
        .f{position:absolute;bottom:18px;left:${M}px;color:#8a93a5;font-size:11px}`;

    function rowHtml(w, i, o) {
        if (o.cols) return `<tr>${w.cells.map((c, k) => `<td style="width:${o.cols[k].w || 'auto'}">${c}</td>`).join('')}</tr>`;
        return `<tr><td style="width:5%">${i + 1}</td><td style="width:24%"><span class="w">${esc(w.word)}</span><br/><span class="t">${esc(w.type)}</span></td>
            <td>${esc(w.meaning)}${w.synonyms ? `<br/><span class="t">Synonyms: ${esc(w.synonyms)}</span>` : ''}${w.example ? `<br/><span class="e">${esc(w.example)}</span>` : ''}</td>
            ${o.langName ? `<td style="width:22%" class="r${o.rtl ? ' rtl' : ''}">${esc(w.tr)}</td>` : ''}</tr>`;
    }
    const headHtml = o => o.cols ? `<tr>${o.cols.map(c => `<th style="width:${c.w || 'auto'}">${esc(c.t)}</th>`).join('')}</tr>` : `<tr><th style="width:5%">#</th><th style="width:24%">Word / phrase</th><th>Meaning</th>${o.langName ? `<th style="width:22%">${esc(o.langName)}</th>` : ''}</tr>`;
    const titleHtml = o => `<h1>${esc(o.title)}</h1><p class="s">${esc(o.subtitle)}</p>`;

    // Measure every row once, then fill the pages.
    function paginate(o) {
        const box = document.createElement('div');
        box.style.cssText = `position:fixed;left:-10000px;top:0;width:${W}px;`;
        box.innerHTML = `<style>${CSS}</style><div class="pg" style="height:auto"><div id="m-t">${titleHtml(o)}</div><table><thead id="m-h">${headHtml(o)}</thead><tbody>${o.words.map((w, i) => rowHtml(w, i, o)).join('')}</tbody></table></div>`;
        document.body.appendChild(box);
        const tH = box.querySelector('#m-t').offsetHeight, hH = box.querySelector('#m-h').offsetHeight;
        const heights = [...box.querySelectorAll('tbody tr')].map(r => r.offsetHeight);
        document.body.removeChild(box);
        const room = H - 2 * M - 24, pages = [];
        let cur = [], used = tH + hH;
        heights.forEach((h, i) => {
            if (used + h > room && cur.length) { pages.push(cur); cur = []; used = hH; }
            cur.push(i); used += h;
        });
        if (cur.length) pages.push(cur);
        return pages;
    }

    function drawPage(o, idx, first, n, total) {
        const html = `<div xmlns="http://www.w3.org/1999/xhtml" class="pg" style="position:relative"><style>${CSS}</style>${first ? titleHtml(o) : ''}<table><thead>${headHtml(o)}</thead><tbody>${idx.map(i => rowHtml(o.words[i], i, o)).join('')}</tbody></table><div class="f">Etude World · page ${n} of ${total}</div></div>`;
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}"><foreignObject width="100%" height="100%">${html}</foreignObject></svg>`;
        return new Promise((res, rej) => {
            const img = new Image();
            img.onload = () => {
                const c = document.createElement('canvas'); c.width = W * SCALE; c.height = H * SCALE;
                const x = c.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, c.width, c.height); x.drawImage(img, 0, 0, c.width, c.height);
                try { res(c.toDataURL('image/jpeg', 0.92)); } catch (e) { rej(e); }
            };
            img.onerror = () => rej(new Error('draw failed'));
            img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
        });
    }

    // A very small PDF writer: one JPEG per A4 page.
    function buildPdf(jpegs) {
        const enc = new TextEncoder(), parts = [], offs = [];
        let len = 0;
        const add = d => { const b = typeof d === 'string' ? enc.encode(d) : d; parts.push(b); len += b.length; };
        const obj = (n, body) => { offs[n] = len; add(`${n} 0 obj\n`); body(); add('\nendobj\n'); };
        const N = jpegs.length;
        add('%PDF-1.4\n');
        obj(1, () => add('<< /Type /Catalog /Pages 2 0 R >>'));
        obj(2, () => add(`<< /Type /Pages /Count ${N} /Kids [${jpegs.map((_, i) => `${3 + i * 3} 0 R`).join(' ')}] >>`));
        jpegs.forEach((j, i) => {
            const p = 3 + i * 3, bin = Uint8Array.from(atob(j.split(',')[1]), c => c.charCodeAt(0));
            obj(p, () => add(`<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /XObject << /Im0 ${p + 2} 0 R >> >> /Contents ${p + 1} 0 R >>`));
            obj(p + 1, () => { const s = 'q 595.28 0 0 841.89 0 0 cm /Im0 Do Q'; add(`<< /Length ${s.length} >>\nstream\n${s}\nendstream`); });
            obj(p + 2, () => { add(`<< /Type /XObject /Subtype /Image /Width ${W * SCALE} /Height ${H * SCALE} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${bin.length} >>\nstream\n`); add(bin); add('\nendstream'); });
        });
        const total = 3 + N * 3, xref = len;
        add(`xref\n0 ${total}\n0000000000 65535 f \n` + offs.slice(1).map(o => String(o).padStart(10, '0') + ' 00000 n \n').join(''));
        add(`trailer\n<< /Size ${total} /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF`);
        return new Blob(parts, { type: 'application/pdf' });
    }

    async function download(o) {
        if (document.fonts && document.fonts.ready) await document.fonts.ready;
        const pages = paginate(o);
        const jpegs = [];
        for (let i = 0; i < pages.length; i++) jpegs.push(await drawPage(o, pages[i], i === 0, i + 1, pages.length));
        const blob = buildPdf(jpegs), a = document.createElement('a');
        a.href = URL.createObjectURL(blob); a.download = o.filename || 'vocabulary.pdf';
        document.body.appendChild(a); a.click();
        setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 4000);
    }
    window.EWVocabPdf = { download };
})();
