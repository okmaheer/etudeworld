// Etude World – small interactive bits (menu + slideshow). No libraries needed.
document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Search bar (magnifier in the header) ---------- */
    var sBtn = document.querySelector('[data-search-toggle]');
    var sBar = document.getElementById('site-search');
    if (sBtn && sBar) {
        var sInput = sBar.querySelector('input');
        var sClose = function () { sBar.hidden = true; sBtn.setAttribute('aria-expanded', 'false'); };
        sBtn.addEventListener('click', function () {
            var open = sBar.hidden;
            sBar.hidden = !open;
            sBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open && sInput) sInput.focus();
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !sBar.hidden) { sClose(); sBtn.focus(); } });
        document.addEventListener('click', function (e) { if (!sBar.hidden && !e.target.closest('#site-search') && !e.target.closest('[data-search-toggle]')) sClose(); });
    }

    /* ---------- Mobile menu ---------- */
    var toggle = document.querySelector('.nav-toggle');
    var menu = document.getElementById('nav-menu');
    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            var open = menu.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open);
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        });
    }

    /* ---------- Dropdowns (click / tap) ---------- */
    var items = document.querySelectorAll('.nav-item.has-dropdown');
    items.forEach(function (item) {
        var btn = item.querySelector('.nav-link');
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var wasOpen = item.classList.contains('open');
            items.forEach(function (i) {
                i.classList.remove('open');
                i.querySelector('.nav-link').setAttribute('aria-expanded', 'false');
            });
            if (!wasOpen) {
                item.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });
    // On computers, pointing at another menu closes a menu that was opened by a click, so two are never open together.
    items.forEach(function (item) {
        item.addEventListener('mouseenter', function () {
            if (!window.matchMedia('(hover: hover) and (min-width: 901px)').matches) return;
            items.forEach(function (i) {
                if (i !== item && i.classList.contains('open')) {
                    i.classList.remove('open');
                    i.querySelector('.nav-link').setAttribute('aria-expanded', 'false');
                }
            });
        });
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.nav-item')) {
            items.forEach(function (i) {
                i.classList.remove('open');
                i.querySelector('.nav-link').setAttribute('aria-expanded', 'false');
            });
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') items.forEach(function (i) { i.classList.remove('open'); });
    });

    /* ---------- Student account menu (avatar in the top right) ---------- */
    var accBtn = document.querySelector('[data-account-toggle]');
    var accMenu = document.querySelector('[data-account-menu]');
    if (accBtn && accMenu) {
        accBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            accMenu.hidden = !accMenu.hidden;
            accBtn.setAttribute('aria-expanded', accMenu.hidden ? 'false' : 'true');
        });
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.nav-account')) { accMenu.hidden = true; accBtn.setAttribute('aria-expanded', 'false'); }
        });
    }

    /* ---------- Sub-menus (e.g. Sample Answers → IELTS → Speaking) ---------- */
    document.querySelectorAll('.sub-toggle').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            // On computers sub-menus open on hover only; the arrow does nothing when clicked.
            if (window.matchMedia('(hover: hover) and (min-width: 901px)').matches) return;
            var li = btn.closest('.has-sub');
            var open = li.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            // Close sibling sub-menus at the same level
            Array.prototype.forEach.call(li.parentNode.children, function (sib) {
                if (sib !== li && sib.classList.contains('has-sub')) {
                    sib.classList.remove('open');
                    var b = sib.querySelector(':scope > .sub-row .sub-toggle');
                    if (b) b.setAttribute('aria-expanded', 'false');
                }
            });
        });
    });

    // On computers sub-menus open to the right. If the whole chain (e.g. IELTS → Speaking → Part 1) would not fit
    // before the right edge of the screen, the WHOLE chain opens to the left instead, so the panels never cover each other.
    function chainWidth(li) {
        var sub = li.querySelector(':scope > .submenu');
        if (!sub) return 0;
        var deepest = 0;
        sub.querySelectorAll(':scope > .has-sub').forEach(function (child) { deepest = Math.max(deepest, chainWidth(child)); });
        return sub.offsetWidth + deepest;
    }
    document.querySelectorAll('.dropdown > .has-sub').forEach(function (top) {
        top.addEventListener('mouseenter', function () {
            if (window.innerWidth <= 900) return;
            var left = top.getBoundingClientRect().right + chainWidth(top) + 12 > window.innerWidth;
            top.classList.toggle('opens-left', left);
            top.querySelectorAll('.has-sub').forEach(function (d) { d.classList.toggle('opens-left', left); });
        });
    });

    // On phones, tapping the label itself also opens its sub-menu (it is not a link).
    document.querySelectorAll('.sub-row').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('.sub-toggle')) return;
            var btn = row.querySelector('.sub-toggle');
            if (btn) btn.click();
            e.stopPropagation();
        });
    });

    /* ---------- FAQ blocks in blogs: click a question to open its answer ---------- */
    document.querySelectorAll('.ew-faq').forEach(function (faq) {
        var items = faq.querySelectorAll('div.ew-faq__item');
        if (!items.length) return;
        faq.classList.add('ew-faq--js');
        items.forEach(function (item) {
            var q = item.querySelector('.ew-faq__q');
            if (!q) return;
            q.setAttribute('role', 'button');
            q.setAttribute('tabindex', '0');
            q.setAttribute('aria-expanded', 'false');   // every answer starts closed
            function toggle() {
                var open = item.classList.toggle('is-open');
                q.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
            q.addEventListener('click', toggle);
            q.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
            });
        });
    });

    /* ---------- Courses: swipeable row of cards ---------- */
    document.querySelectorAll('[data-course-slider]').forEach(function (box) {
        var track = box.querySelector('[data-cs-track]');
        var prev = box.querySelector('[data-cs-prev]');
        var next = box.querySelector('[data-cs-next]');
        if (!track) return;
        function step() {
            var card = track.querySelector('.course-card');
            return card ? card.getBoundingClientRect().width + 24 : track.clientWidth;
        }
        function update() {
            var max = track.scrollWidth - track.clientWidth - 2;
            prev.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft >= max;
            box.classList.toggle('cs-fits', max <= 0);
        }
        prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); });
        next.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); });
        track.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
        track.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') { e.preventDefault(); next.click(); }
            if (e.key === 'ArrowLeft') { e.preventDefault(); prev.click(); }
        });
        window.addEventListener('resize', update);
        // On a phone (one card at a time) open on the highlighted card
        var feat = track.querySelector('.course-card--featured');
        if (feat && window.matchMedia('(max-width: 640px)').matches) {
            track.style.scrollBehavior = 'auto';
            track.scrollLeft = feat.offsetLeft - track.offsetLeft - 4;
            track.style.scrollBehavior = '';
        }
        update();
    });

    /* ---------- Achievements slideshow ---------- */
    document.querySelectorAll('[data-slider]').forEach(function (slider) {
        var slides = slider.querySelectorAll('.slide');
        var dots = slider.querySelectorAll('[data-dot]');
        var delay = parseInt(slider.dataset.interval, 10) || 5000;
        var current = 0;
        var timer = null;

        function show(index) {
            current = (index + slides.length) % slides.length;
            slides.forEach(function (s, i) { s.classList.toggle('is-active', i === current); });
            dots.forEach(function (d, i) { d.classList.toggle('is-active', i === current); });
        }
        function start() { stop(); timer = setInterval(function () { show(current + 1); }, delay); }
        function stop() { if (timer) clearInterval(timer); }

        slider.querySelector('[data-next]').addEventListener('click', function () { show(current + 1); start(); });
        slider.querySelector('[data-prev]').addEventListener('click', function () { show(current - 1); start(); });
        dots.forEach(function (d) {
            d.addEventListener('click', function () { show(parseInt(d.dataset.dot, 10)); start(); });
        });

        // Pause while the visitor is hovering over it
        slider.addEventListener('mouseenter', stop);
        slider.addEventListener('mouseleave', start);

        // Swipe on phones
        var startX = null;
        slider.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
        slider.addEventListener('touchend', function (e) {
            if (startX === null) return;
            var dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 40) { show(current + (dx < 0 ? 1 : -1)); start(); }
            startX = null;
        });

        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) start();
    });
});
