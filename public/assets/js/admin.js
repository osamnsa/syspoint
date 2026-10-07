/* Admin shell: mobile sidebar toggle + chart hover tooltips (data-tip). */
(function () {
    'use strict';

    // Sidebar (phones/tablets): open with the menu button, close on scrim/Escape.
    var body = document.body;
    var toggle = document.querySelector('[data-admin-toggle]');
    function setOpen(open) {
        body.classList.toggle('admin-nav-open', open);
        if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    if (toggle) toggle.addEventListener('click', function () { setOpen(!body.classList.contains('admin-nav-open')); });
    document.querySelectorAll('[data-admin-close]').forEach(function (el) {
        el.addEventListener('click', function () { setOpen(false); });
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });

    // Chart tooltips: any element with data-tip; line charts also move a crosshair.
    var tip = document.querySelector('.chart-tooltip');
    if (!tip) return;
    function show(el, x, y) {
        tip.textContent = el.getAttribute('data-tip');
        tip.hidden = false;
        var r = tip.getBoundingClientRect();
        var left = Math.min(window.innerWidth - r.width - 8, Math.max(8, x - r.width / 2));
        var top = y - r.height - 14;
        if (top < 8) top = y + 18;
        tip.style.left = left + 'px';
        tip.style.top = top + 'px';
        var svg = el.ownerSVGElement;
        var cross = svg && svg.querySelector('.chart-crosshair');
        if (cross && el.hasAttribute('data-x')) {
            cross.setAttribute('x1', el.getAttribute('data-x'));
            cross.setAttribute('x2', el.getAttribute('data-x'));
            cross.classList.add('is-on');
        }
    }
    function hide(el) {
        tip.hidden = true;
        var svg = el && el.ownerSVGElement;
        var cross = svg && svg.querySelector('.chart-crosshair');
        if (cross) cross.classList.remove('is-on');
    }
    document.querySelectorAll('[data-tip]').forEach(function (el) {
        el.addEventListener('mousemove', function (e) { show(el, e.clientX, e.clientY); });
        el.addEventListener('mouseleave', function () { hide(el); });
        el.addEventListener('touchstart', function (e) {
            var t = e.touches[0]; show(el, t.clientX, t.clientY);
        }, { passive: true });
    });
    document.addEventListener('touchstart', function (e) {
        if (!e.target.closest('[data-tip]')) hide(null);
    }, { passive: true });
})();
