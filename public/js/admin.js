// Etude World admin – small helpers. No libraries needed.
(function () {
    const $ = (s, el = document) => el.querySelector(s);
    const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));

    /* ---------- Sidebar (phones) ---------- */
    const sidebar = $('#sidebar');
    $$('[data-open-sidebar]').forEach(b => b.addEventListener('click', () => sidebar.classList.add('open')));
    $$('[data-close-sidebar]').forEach(b => b.addEventListener('click', () => sidebar.classList.remove('open')));

    /* ---------- Sidebar dropdown (Manage Paid Clients) ---------- */
    $$('.side-toggle').forEach(btn => btn.addEventListener('click', () => {
        const group = btn.closest('.side-group');
        const open = group.classList.toggle('open');
        btn.setAttribute('aria-expanded', open);
    }));

    /* ---------- Show / hide password ---------- */
    $$('[data-toggle-password]').forEach(btn => btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.togglePassword);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
    }));

    /* ---------- "Are you sure?" before deleting ---------- */
    $$('[data-confirm]').forEach(btn => btn.addEventListener('click', e => {
        if (!window.confirm(btn.dataset.confirm)) e.preventDefault();
    }));

    /* ---------- URL (slug) made automatically from the title ---------- */
    const slugify = s => s.toString().normalize('NFKD').replace(/[̀-ͯ]/g, '')
        .toLowerCase().trim().replace(/['’]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    const source = $('[data-slug-source]');
    const target = $('[data-slug-target]');
    if (source && target) {
        let locked = target.hasAttribute('data-slug-locked') || target.value !== '';
        source.addEventListener('input', () => { if (!locked) { target.value = slugify(source.value); refreshPreviews(); } });
        target.addEventListener('input', () => { locked = target.value !== ''; });
        target.addEventListener('blur', () => { target.value = slugify(target.value); refreshPreviews(); });
    }

    /* ---------- Category changes the URL prefix ---------- */
    const category = $('[data-category]');
    // Address of the chosen category, e.g. "blog/ielts" or "sample-answers/ielts/speaking/part-1"
    const categoryPath = () => {
        const opt = category && category.selectedOptions[0];
        return opt && opt.dataset.path ? opt.dataset.path : 'blog/category';
    };
    const prefix = $('[data-url-prefix]');
    if (category && prefix) {
        category.addEventListener('change', () => { prefix.textContent = '/' + categoryPath() + '/'; refreshPreviews(); });
    }

    /* ---------- Character counters for meta title / description ---------- */
    function updateCounter(input) {
        const ideal = parseInt(input.dataset.counter, 10);
        const len = input.value.trim().length;
        const min = ideal === 60 ? 30 : 120;
        const meter = $(`[data-meter-for="${input.id}"]`);
        const label = $(`[data-count-for="${input.id}"]`);
        if (meter) {
            meter.style.width = Math.min(100, (len / ideal) * 100) + '%';
            meter.className = len === 0 ? '' : len > ideal ? 'bad' : len < min ? 'warn' : '';
        }
        if (label) {
            const msg = len === 0 ? `Best length: ${min}–${ideal} characters.`
                : len > ideal ? `${len} characters — too long, Google will cut it off after about ${ideal}.`
                : len < min ? `${len} characters — a little short. Aim for ${min}–${ideal}.`
                : `${len} characters — great length.`;
            label.textContent = msg;
        }
    }
    $$('[data-counter]').forEach(input => {
        updateCounter(input);
        input.addEventListener('input', () => { updateCounter(input); refreshPreviews(); });
    });

    /* ---------- Blog image preview ---------- */
    const imageInput = $('[data-image-input]');
    if (imageInput) {
        imageInput.addEventListener('change', () => {
            const file = imageInput.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            $('[data-image-preview]').src = url;
            $('[data-dropzone]').classList.add('has-image');
            const social = $('[data-social-img]');
            if (social) social.style.backgroundImage = `url(${url})`;
            refreshPreviews();
        });
    }

    /* ---------- Google + social previews ---------- */
    function refreshPreviews() {
        const serp = $('[data-serp]');
        if (!serp) return;
        const title = ($('#meta_title').value.trim() || $('#title').value.trim() || 'Your blog title');
        const desc = ($('#meta_description').value.trim() || 'Your meta description will appear here.');
        const path = categoryPath().split('/').join(' › ');
        const slug = target && target.value ? target.value : '…';
        const host = serp.dataset.site.replace(/^https?:\/\//, '');
        $('[data-serp-title]').textContent = title.length > 60 ? title.slice(0, 58) + '…' : title;
        $('[data-serp-desc]').textContent = desc.length > 160 ? desc.slice(0, 158) + '…' : desc;
        $('[data-serp-url]').textContent = `${host} › ${path} › ${slug}`;
        $('[data-social-title]').textContent = title;
        $('[data-social-desc]').textContent = desc;
        runChecks();
    }
    if ($('#title') && $('[data-serp]')) $('#title').addEventListener('input', refreshPreviews);

    /* ---------- SEO checklist (reads the blog text) ---------- */
    let lastHtml = null;
    function runChecks(html) {
        const list = $('[data-seo-checklist]');
        if (!list) return;
        if (html !== undefined) lastHtml = html;
        if (lastHtml === null) lastHtml = ($('#content') || {}).value || '';
        const doc = new DOMParser().parseFromString(lastHtml, 'text/html');
        const text = doc.body.textContent.trim();
        const words = text ? text.split(/\s+/).length : 0;
        const headings = $$('h1,h2,h3,h4,h5,h6', doc);
        const imgs = $$('img', doc);
        const metaTitleLen = ($('#meta_title').value.trim() || $('#title').value.trim()).length;
        const metaDescLen = $('#meta_description').value.trim().length;
        const hasImage = $('[data-dropzone]').classList.contains('has-image');

        const results = {
            title: $('#title').value.trim().length > 0,
            metaTitle: metaTitleLen >= 30 && metaTitleLen <= 60,
            metaDesc: metaDescLen >= 120 && metaDescLen <= 160,
            words: words >= 300,
            h1: !headings.some(h => h.tagName === 'H1'),
            h2: headings.some(h => h.tagName === 'H2'),
            alt: imgs.every(i => (i.getAttribute('alt') || '').trim() !== ''),
            image: hasImage,
        };
        Object.entries(results).forEach(([key, ok]) => {
            const li = $(`[data-check="${key}"]`, list);
            if (li) { li.classList.toggle('ok', ok); li.classList.toggle('fail', !ok); }
        });
        const wc = $('[data-word-count]');
        if (wc) wc.textContent = `(${words} now)`;

        const outline = $('[data-outline]');
        if (outline) {
            outline.innerHTML = '';
            if (!headings.length) {
                outline.innerHTML = '<li class="muted">Headings you add will show here.</li>';
            } else {
                headings.forEach(h => {
                    const li = document.createElement('li');
                    li.className = h.tagName.toLowerCase();
                    li.innerHTML = `<span class="lvl">${h.tagName}</span>`;
                    li.appendChild(document.createTextNode(h.textContent.trim() || '(empty)'));
                    outline.appendChild(li);
                });
            }
        }
    }

    window.EtudeSeo = { refresh: html => runChecks(html) };
    refreshPreviews();
})();
