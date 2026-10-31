/*
 * Admin – Listening "Map Labelling – Drop Ver".
 * Click the picture to place points (A, B, C …); each point has an answer box. Everything is sent as one JSON value.
 */
(function () {
    const form = document.getElementById('mapdrop-form');
    if (!form) return;
    const $  = (s, el = document) => el.querySelector(s);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const data = JSON.parse($('[data-rda-data]').textContent);
    const g = data.group;
    const L = n => String.fromCharCode(65 + n);
    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    let uid = Date.now(), dirty = false, image = g.image || '';
    let points = (g.items || []).map(p => ({ key: 'p' + (uid++), x: Number(p.x) || 0, y: Number(p.y) || 0, text: p.text || '' }));
    const pad = $('[data-pad]'), img = $('[data-map-img]'), list = $('[data-points]'), empty = $('[data-empty]'), pointer = $('[data-pointer]');
    const status = $('[data-save-status]');
    const markDirty = () => { dirty = true; if (status) status.textContent = 'You have unsaved changes.'; };
    window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

    function editor(el, inst) {
        if (!window.tinymce) return;
        tinymce.init({
            target: el, license_key: 'gpl', promotion: false, branding: false, menubar: false,
            plugins: 'lists link autolink autoresize', toolbar: 'bold italic underline | bullist numlist | removeformat', toolbar_mode: 'wrap',
            contextmenu: false, min_height: inst ? 120 : 70, autoresize_bottom_margin: 10, convert_urls: false, entity_encoding: 'raw',
            content_style: 'body{font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1c2333;margin:8px 12px} p{margin:0 0 .5em}',
            setup: ed => { if (window.EtudeEditorBlocks) EtudeEditorBlocks.basics(ed); ed.on('input change undo redo keyup SetContent', () => { ed.save(); markDirty(); }); },
        });
    }
    editor($('#rda-instructions'), true);

    /* ----- the picture ----- */
    $('[data-map-file]').addEventListener('change', e => {
        const f = e.target.files[0]; if (!f) return;
        const fd = new FormData(); fd.append('image', f);
        const st = $('[data-map-status]'); st.textContent = 'Uploading…';
        fetch(data.imageUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd })
            .then(r => r.ok ? r.json() : r.json().then(j => Promise.reject((j.errors && j.errors.image && j.errors.image[0]) || j.message || 'Upload failed')))
            .then(j => { image = j.path; img.src = j.url; pad.hidden = false; $('[data-map-remove]').hidden = false; st.textContent = ''; markDirty(); drawPins(); })
            .catch(err => { st.textContent = String(err); });
        e.target.value = '';
    });
    $('[data-map-remove]').addEventListener('click', () => { image = ''; pad.hidden = true; $('[data-map-remove]').hidden = true; markDirty(); });
    if (image) { img.src = data.imageSrc; pad.hidden = false; }

    /* ----- points on the picture ----- */
    const pct = (ev) => {
        const r = img.getBoundingClientRect();
        return { x: Math.max(0, Math.min(100, (ev.clientX - r.left) / r.width * 100)), y: Math.max(0, Math.min(100, (ev.clientY - r.top) / r.height * 100)) };
    };
    const round = n => Math.round(n * 100) / 100;
    function drawPins() {
        pad.querySelectorAll('.rda-pin').forEach(n => n.remove());
        points.forEach((p, i) => {
            const pin = document.createElement('div');
            pin.className = 'rda-pin'; pin.textContent = L(i); pin.dataset.key = p.key;
            pin.style.left = p.x + '%'; pin.style.top = p.y + '%';
            pad.appendChild(pin);
        });
    }
    let drag = null;
    pad.addEventListener('pointerdown', ev => {
        const pin = ev.target.closest('.rda-pin');
        if (pin) { drag = { p: points.find(p => p.key === pin.dataset.key), moved: false }; pad.setPointerCapture(ev.pointerId); ev.preventDefault(); }
    });
    pad.addEventListener('pointermove', ev => {
        const c = pct(ev); pointer.textContent = `Pointer: x ${round(c.x)}%, y ${round(c.y)}%  (about ${Math.round(c.x / 100 * img.naturalWidth)} × ${Math.round(c.y / 100 * img.naturalHeight)} px of the original picture)`;
        if (!drag) return;
        drag.moved = true; drag.p.x = round(c.x); drag.p.y = round(c.y); drawPins(); drawList(false); markDirty();
    });
    pad.addEventListener('pointerup', ev => {
        if (drag) { drag = null; return; }
        if (ev.target.closest('.rda-pin') || points.length >= 26) return;
        const c = pct(ev);
        points.push({ key: 'p' + (uid++), x: round(c.x), y: round(c.y), text: '' });
        drawPins(); drawList(true); markDirty();
    });

    /* ----- one answer box per point ----- */
    function drawList(rebuild) {
        empty.hidden = points.length > 0;
        if (!rebuild) { points.forEach((p, i) => { const c = $(`[data-point="${p.key}"] [data-coords]`, list); if (c) c.textContent = `x ${p.x}%, y ${p.y}%`; }); return; }
                points.forEach(p => { const ed = window.tinymce && tinymce.get(p.key + '-text'); if (ed) { p.text = ed.getContent(); ed.remove(); } });
        list.innerHTML = points.map((p, i) => `<div class="rda-item" data-point="${p.key}">
            <div class="rda-item__head"><strong>${L(i)}</strong> <small class="muted" data-coords>x ${p.x}%, y ${p.y}%</small>
                <button type="button" class="btn btn-sm btn-ghost" data-del="${p.key}">Remove</button></div>
            <textarea id="${p.key}-text" rows="2">${esc(p.text)}</textarea></div>`).join('');
        points.forEach(p => editor(document.getElementById(p.key + '-text'), false));
    }
    list.addEventListener('click', e => {
        const b = e.target.closest('[data-del]'); if (!b) return;
        points = points.filter(p => p.key !== b.dataset.del); drawPins(); drawList(true); markDirty();
    });
    drawPins(); drawList(true);

    /* ----- save ----- */
    form.addEventListener('submit', e => {
        if (window.tinymce) tinymce.triggerSave();
        const txt = p => { const ed = window.tinymce && tinymce.get(p.key + '-text'); return ed ? ed.getContent() : p.text; };
        const out = {
            id: g.id, type: 'mapdrop', passage: Number((form.querySelector('[data-passage]:checked') || {}).value || 1),
            instructions: (tinymce.get('rda-instructions') || { getContent: () => $('#rda-instructions').value }).getContent(),
            image, items: points.map(p => ({ x: p.x, y: p.y, text: txt(p) })),
        };
        const blank = t => !t.replace(/<[^>]*>|&nbsp;/g, '').trim();
        const problems = [];
        if (!image) problems.push('No picture added yet.');
        if (!out.items.length) problems.push('Click the picture to add at least one point.');
        if (out.items.some(i => blank(i.text))) problems.push('Every point needs an answer.');
        if (problems.length && !window.confirm(problems.join('\n') + '\n\nSave anyway?')) { e.preventDefault(); return; }
        $('[data-group-json]').value = JSON.stringify(out);
        dirty = false;
    });
})();
