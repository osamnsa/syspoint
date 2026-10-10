(function () {
    var toggle = document.querySelector('.nav-toggle');
    var links = document.getElementById('nav-links');
    if (!toggle || !links) return;

    toggle.addEventListener('click', function () {
        var isOpen = links.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    links.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            links.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });
})();

// Shop search & filters: checkboxes apply at once, sort jumps to its URL,
// and on phones the filter panel opens from a button.
(function () {
    var form = document.querySelector('[data-shop-filters]');
    if (form) {
        form.addEventListener('change', function (e) {
            if (e.target.hasAttribute('data-auto')) { if (form.requestSubmit) form.requestSubmit(); else form.submit(); }
        });
        // don't send empty fields (keeps URLs tidy)
        form.addEventListener('submit', function () {
            form.querySelectorAll('input').forEach(function (i) {
                if (i.type !== 'checkbox' && (i.value === '' || (i.name === 'sort' && i.value === (form.q.value ? 'relevance' : 'newest')))) i.disabled = true;
            });
        });
    }
    var sort = document.querySelector('[data-shop-sort]');
    if (sort) sort.addEventListener('change', function () { window.location.href = sort.value; });
    var toggle = document.querySelector('[data-shop-filters-toggle]');
    if (toggle && form) toggle.addEventListener('click', function () {
        var open = form.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
})();
