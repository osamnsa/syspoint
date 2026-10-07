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

/* Repeater rows (purchase order lines) + live line totals. */
(function () {
    'use strict';
    var naira = function (n) { return '₦' + Math.round(n).toLocaleString('en-NG'); };
    document.querySelectorAll('[data-repeater]').forEach(function (table) {
        var body = table.querySelector('tbody');
        function totals() {
            var grand = 0;
            body.querySelectorAll('[data-repeater-row]').forEach(function (row) {
                var q = parseFloat(row.querySelector('[data-po-qty]').value) || 0;
                var c = parseFloat(row.querySelector('[data-po-cost]').value) || 0;
                row.querySelector('[data-po-total]').textContent = q && c ? naira(q * c) : '—';
                grand += q * c;
            });
            var g = table.querySelector('[data-po-grand]');
            if (g) g.textContent = grand ? naira(grand) : '—';
        }
        table.addEventListener('input', totals);
        table.addEventListener('change', function (e) {
            if (e.target.matches('[data-po-product]')) {
                var cost = e.target.selectedOptions[0] && e.target.selectedOptions[0].getAttribute('data-cost');
                var input = e.target.closest('tr').querySelector('[data-po-cost]');
                if (cost && !input.value) input.value = cost;
            }
            totals();
        });
        table.addEventListener('click', function (e) {
            if (e.target.matches('[data-repeater-add]')) {
                var first = body.querySelector('[data-repeater-row]');
                var row = first.cloneNode(true);
                row.querySelectorAll('input').forEach(function (i) { i.value = i.matches('[data-po-qty]') ? '1' : ''; });
                row.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
                body.appendChild(row);
                totals();
            }
            if (e.target.matches('[data-repeater-remove]')) {
                var rows = body.querySelectorAll('[data-repeater-row]');
                if (rows.length > 1) e.target.closest('tr').remove();
                totals();
            }
        });
        totals();
    });
})();

/* Point of sale register. */
(function () {
    'use strict';
    var form = document.querySelector('[data-pos]');
    if (!form) return;
    var catalog = JSON.parse(form.querySelector('[data-pos-catalog]').textContent);
    var byId = {};
    catalog.forEach(function (p) { byId[p.id] = p; });
    var cart = []; // {id, qty, units: [unitId]}
    var grid = form.querySelector('[data-pos-grid]');
    var linesEl = form.querySelector('[data-pos-lines]');
    var naira = function (n) { return '₦' + Math.round(n).toLocaleString('en-NG'); };
    function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }

    function renderGrid(q) {
        q = (q || '').toLowerCase();
        grid.textContent = '';
        var shown = 0;
        catalog.forEach(function (p) {
            if (q && (p.name + ' ' + p.sku + ' ' + p.category).toLowerCase().indexOf(q) === -1) return;
            var available = p.serials ? Math.min(p.units.length, p.stock) : p.stock;
            var b = el('button', 'pos-tile' + (available < 1 ? ' is-out' : ''));
            b.type = 'button';
            b.disabled = available < 1;
            if (p.image) { var img = el('img'); img.src = p.image; img.alt = ''; img.loading = 'lazy'; b.appendChild(img); }
            b.appendChild(el('strong', null, p.name));
            b.appendChild(el('span', 'pos-tile-price', naira(p.price)));
            b.appendChild(el('small', null, available < 1 ? (p.serials && p.stock ? 'Register serials first' : 'Out of stock') : available + ' in stock' + (p.serials ? ' · serials' : '')));
            b.addEventListener('click', function () { add(p.id); });
            grid.appendChild(b);
            shown++;
        });
        form.querySelector('[data-pos-none]').hidden = shown > 0;
    }

    function add(id) {
        var p = byId[id];
        var line = cart.find(function (l) { return l.id === id; });
        var max = p.serials ? Math.min(p.units.length, p.stock) : p.stock;
        if (line) { if (line.qty < max) line.qty++; } else { cart.push({ id: id, qty: 1, units: [] }); }
        line = cart.find(function (l) { return l.id === id; });
        // Serial items: auto-pick the first free units; staff can change the pick.
        if (p.serials) {
            while (line.units.length < line.qty) {
                var free = p.units.find(function (u) { return line.units.indexOf(u.id) === -1; });
                if (!free) break;
                line.units.push(free.id);
            }
        }
        render();
    }

    function render() {
        linesEl.textContent = '';
        var subtotal = 0;
        cart.forEach(function (line, idx) {
            var p = byId[line.id];
            subtotal += p.price * line.qty;
            var row = el('div', 'pos-line');
            var head = el('div', 'pos-line-head');
            head.appendChild(el('strong', null, p.name));
            head.appendChild(el('span', null, naira(p.price * line.qty)));
            row.appendChild(head);
            var ctr = el('div', 'pos-qty');
            var minus = el('button', null, '−'); minus.type = 'button'; minus.setAttribute('aria-label', 'One less');
            var plus = el('button', null, '+'); plus.type = 'button'; plus.setAttribute('aria-label', 'One more');
            var qty = el('span', null, String(line.qty));
            var max = p.serials ? Math.min(p.units.length, p.stock) : p.stock;
            minus.addEventListener('click', function () {
                line.qty--; if (p.serials) line.units = line.units.slice(0, line.qty);
                if (line.qty < 1) cart.splice(idx, 1); render();
            });
            plus.addEventListener('click', function () { if (line.qty < max) add(p.id); });
            ctr.appendChild(minus); ctr.appendChild(qty); ctr.appendChild(plus);
            ctr.appendChild(el('small', null, naira(p.price) + ' each'));
            row.appendChild(ctr);
            if (p.serials) {
                var pick = el('div', 'pos-serials');
                pick.appendChild(el('small', null, 'Serials sold (' + line.units.length + ' of ' + line.qty + '):'));
                p.units.forEach(function (u) {
                    var lab = el('label');
                    var cb = el('input'); cb.type = 'checkbox'; cb.checked = line.units.indexOf(u.id) !== -1;
                    cb.addEventListener('change', function () {
                        if (cb.checked) { if (line.units.length >= line.qty) { cb.checked = false; return; } line.units.push(u.id); }
                        else line.units = line.units.filter(function (x) { return x !== u.id; });
                        render();
                    });
                    lab.appendChild(cb); lab.appendChild(document.createTextNode(' ' + u.serial));
                    pick.appendChild(lab);
                });
                row.appendChild(pick);
            }
            // Hidden inputs the server reads.
            var base = 'lines[' + idx + ']';
            [['product_id', p.id], ['qty', line.qty]].forEach(function (f) {
                var h = el('input'); h.type = 'hidden'; h.name = base + '[' + f[0] + ']'; h.value = f[1]; row.appendChild(h);
            });
            line.units.forEach(function (u) { var h = el('input'); h.type = 'hidden'; h.name = base + '[unit_ids][]'; h.value = u; row.appendChild(h); });
            linesEl.appendChild(row);
        });
        if (!cart.length) linesEl.appendChild(el('p', 'pos-empty', 'Tap a product to add it.'));
        var discount = Math.min(subtotal, Math.max(0, parseFloat(form.querySelector('[data-pos-discount]').value) || 0));
        var total = subtotal - discount;
        form.querySelector('[data-pos-subtotal]').textContent = naira(subtotal);
        form.querySelector('[data-pos-total]').textContent = naira(total);
        var paid = parseFloat(form.querySelector('[data-pos-paid]').value);
        form.querySelector('[data-pos-change]').textContent = isNaN(paid) ? '—' : (paid >= total ? naira(paid - total) : 'Short by ' + naira(total - paid));
        var serialsOk = cart.every(function (l) { return !byId[l.id].serials || l.units.length === l.qty; });
        form.querySelector('[data-pos-submit]').disabled = !cart.length || !serialsOk;
    }

    form.querySelector('[data-pos-search]').addEventListener('input', function (e) { renderGrid(e.target.value); });
    form.querySelector('[data-pos-search]').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); var first = grid.querySelector('.pos-tile:not([disabled])'); if (first) first.click(); }
    });
    form.addEventListener('input', function (e) { if (e.target.matches('[data-pos-discount],[data-pos-paid]')) render(); });
    form.addEventListener('submit', function () { form.querySelector('[data-pos-submit]').disabled = true; });
    renderGrid('');
    render();
})();

/* Clickable table rows: whole row opens data-href, but links/buttons inside keep their own behaviour. */
document.addEventListener('click', function (e) {
    var row = e.target.closest('tr[data-href]');
    if (!row || e.target.closest('a, button, input, select, textarea, label, form')) return;
    window.location.href = row.getAttribute('data-href');
});
