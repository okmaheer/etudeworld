/* Admin → Site Settings: add / remove footer columns and links. */
(function () {
    const wrap = document.querySelector('[data-columns]');
    if (!wrap) return;
    const tplCol = document.querySelector('[data-tpl-col]').innerHTML, tplLink = document.querySelector('[data-tpl-link]').innerHTML;
    const next = (parent, sel) => Math.max(-1, ...[...parent.querySelectorAll(':scope > ' + sel)].map(e => +e.dataset.index)) + 1;
    const build = (html, map) => { const t = document.createElement('div'); t.innerHTML = Object.keys(map).reduce((h, k) => h.split(k).join(map[k]), html).trim(); return t.firstElementChild; };

    document.addEventListener('click', e => {
        const t = e.target;
        if (t.closest('[data-add-col]')) {
            const el = build(tplCol, { __C__: next(wrap, '[data-col]') }); wrap.appendChild(el); el.querySelector('input').focus();
        } else if (t.closest('[data-add-link]')) {
            const col = t.closest('[data-col]'), box = col.querySelector('[data-links]');
            const el = build(tplLink, { __C__: col.dataset.index, __L__: next(box, '[data-link]') }); box.appendChild(el); el.querySelector('input').focus();
        } else if (t.closest('[data-del-link]')) {
            t.closest('[data-link]').remove();
        } else if (t.closest('[data-del-col]')) {
            if (confirm('Remove this whole column from the footer?')) t.closest('[data-col]').remove();
        }
    });
})();
