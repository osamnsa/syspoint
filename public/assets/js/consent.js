// Cookie consent: banner, preferences panel, revisit button, and loading
// Google Analytics / Meta Pixel only for the categories the visitor allows.
(function () {
    var root = document.querySelector('[data-cookie-consent]');
    if (!root) return;
    var cfg;
    try { cfg = JSON.parse(root.getAttribute('data-cookie-consent')); } catch (e) { return; }

    var banner = root.querySelector('.cc-banner');
    var modal = root.querySelector('.cc-modal');
    var revisit = root.querySelector('.cc-revisit');
    var switches = root.querySelectorAll('[data-cc-cat]');
    var CATS = ['functional', 'analytics', 'marketing'];

    function read() {
        var m = document.cookie.match(new RegExp('(?:^|; )' + cfg.cookie + '=([^;]*)'));
        if (!m) return null;
        try {
            var d = JSON.parse(decodeURIComponent(m[1]));
            return d && d.v === cfg.version ? d : null;
        } catch (e) { return null; }
    }

    function uuid() {
        if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 3 | 8)).toString(16);
        });
    }

    var current = read();

    function write(choices, action) {
        var prev = current;
        current = { id: (prev && prev.id) || uuid(), v: cfg.version, t: Math.floor(Date.now() / 1000), c: choices };
        document.cookie = cfg.cookie + '=' + encodeURIComponent(JSON.stringify(current)) +
            '; Max-Age=' + (cfg.days * 86400) + '; Path=/; SameSite=Lax' + (cfg.secure ? '; Secure' : '');
        try {
            fetch(cfg.log, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', keepalive: true,
                body: JSON.stringify({ id: current.id, action: action, c: choices }) });
        } catch (e) { /* logging is best effort */ }

        // Something switched off that was on: remove its cookies and reload so its script stops.
        var revoked = prev && CATS.some(function (k) { return prev.c[k] && !choices[k]; });
        if (revoked) { clearOptional(choices); location.reload(); return; }
        apply();
        document.dispatchEvent(new CustomEvent('cookieconsent', { detail: choices }));
    }

    function clearOptional(choices) {
        var names = [];
        if (!choices.analytics) names.push(/^_ga/, /^_gid$/, /^_gat/);
        if (!choices.marketing) names.push(/^_fbp$/, /^_fbc$/);
        var host = location.hostname.split('.');
        document.cookie.split('; ').forEach(function (pair) {
            var name = pair.split('=')[0];
            if (!names.some(function (re) { return re.test(name); })) return;
            for (var i = 0; i < host.length - 1; i++) {
                var domain = host.slice(i).join('.');
                document.cookie = name + '=; Max-Age=0; Path=/; Domain=.' + domain;
            }
            document.cookie = name + '=; Max-Age=0; Path=/';
        });
    }

    var loaded = {};
    function addScript(src) {
        var s = document.createElement('script');
        s.async = true; s.src = src;
        document.head.appendChild(s);
    }

    function apply() {
        var c = current ? current.c : {};
        if (c.analytics && cfg.ga && !loaded.ga) {
            loaded.ga = true;
            window.dataLayer = window.dataLayer || [];
            window.gtag = function () { dataLayer.push(arguments); };
            gtag('consent', 'default', { ad_storage: c.marketing ? 'granted' : 'denied', ad_user_data: c.marketing ? 'granted' : 'denied',
                ad_personalization: c.marketing ? 'granted' : 'denied', analytics_storage: 'granted' });
            gtag('js', new Date());
            gtag('config', cfg.ga, { anonymize_ip: true });
            addScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(cfg.ga));
        }
        if (c.marketing && cfg.pixel && !loaded.pixel) {
            loaded.pixel = true;
            /* Meta Pixel base code */
            !function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
                if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0;
                t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', cfg.pixel);
            fbq('track', 'PageView');
        }
    }

    function all(on) { var c = {}; CATS.forEach(function (k) { c[k] = on ? 1 : 0; }); return c; }

    function showBanner(show) {
        banner.hidden = !show;
        revisit.hidden = show;
        document.documentElement.classList.toggle('cc-banner-open', show);
    }

    function openPanel() {
        var c = current ? current.c : {};
        switches.forEach(function (s) { s.checked = !!c[s.getAttribute('data-cc-cat')]; });
        if (modal.showModal) modal.showModal(); else modal.setAttribute('open', '');
    }
    function closePanel() { if (modal.close) modal.close(); else modal.removeAttribute('open'); }

    function decide(action) {
        var choices = action === 'accept' ? all(true) : action === 'reject' ? all(false) : (function () {
            var c = all(false);
            switches.forEach(function (s) { c[s.getAttribute('data-cc-cat')] = s.checked ? 1 : 0; });
            return c;
        })();
        var logged = action === 'accept' ? 'accept_all' : action === 'reject' ? 'reject_all' : 'custom';
        closePanel();
        showBanner(false);
        write(choices, logged);
    }

    root.addEventListener('click', function (e) {
        var b = e.target.closest('[data-cc]');
        if (b) {
            var act = b.getAttribute('data-cc');
            if (act === 'customise') openPanel();
            else if (act === 'close') closePanel();
            else decide(act === 'save' ? 'save' : act);
            return;
        }
        var t = e.target.closest('.cc-cat-toggle');
        if (t) {
            var open = t.getAttribute('aria-expanded') !== 'true';
            t.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.getElementById(t.getAttribute('aria-controls')).hidden = !open;
        }
    });
    // Click on the dimmed backdrop closes the panel.
    modal.addEventListener('click', function (e) { if (e.target === modal) closePanel(); });

    // Any "cookie settings" link or button on the page (footer, Cookie Policy) opens the panel.
    document.addEventListener('click', function (e) {
        var s = e.target.closest('[data-cookie-settings]');
        if (!s) return;
        e.preventDefault();
        openPanel();
    });

    if (current) { showBanner(false); apply(); }
    else showBanner(true);
})();
