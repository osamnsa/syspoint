/*
 * Syspoint alerts — SweetAlert2 everywhere (public site and admin).
 *
 * 1. Server-rendered messages (.alert-success / .alert-error / .alert-warning)
 *    become a toast (success) or a dialog (errors, with the list of problems).
 *    The original box stays in the HTML, so without JavaScript nothing is lost.
 *    Add .alert-static to a box that should stay on the page as-is.
 * 2. Any form or submit button with data-confirm="Question?" asks first
 *    (optional data-confirm-button="Delete", data-confirm-danger for red).
 *    Falls back to the browser's confirm() if SweetAlert didn't load.
 * 3. window.SP.toast / SP.alert / SP.confirm / SP.prompt for scripts.
 */
(function () {
    'use strict';
    var hasSwal = typeof window.Swal !== 'undefined';

    var classes = function (extra) {
        var c = {
            popup: 'sp-swal',
            title: 'sp-swal-title',
            htmlContainer: 'sp-swal-text',
            actions: 'sp-swal-actions',
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-outline sp-swal-cancel',
            input: 'sp-swal-input',
            validationMessage: 'sp-swal-validation'
        };
        for (var k in (extra || {})) c[k] = extra[k];
        return c;
    };
    var Modal = hasSwal ? Swal.mixin({
        buttonsStyling: false,
        customClass: classes(),
        showClass: { popup: 'sp-swal-in' },
        hideClass: { popup: 'sp-swal-out' }
    }) : null;

    var Toast = hasSwal ? Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        customClass: { popup: 'sp-swal sp-toast' },
        didOpen: function (t) {
            t.addEventListener('mouseenter', Swal.stopTimer);
            t.addEventListener('mouseleave', Swal.resumeTimer);
        }
    }) : null;

    function textNode(tag, text, cls) {
        var el = document.createElement(tag);
        if (cls) el.className = cls;
        el.textContent = text;
        return el;
    }

    var SP = {
        toast: function (text, icon) {
            if (!hasSwal) return Promise.resolve();
            return Toast.fire({ icon: icon || 'success', title: text });
        },
        alert: function (title, lines, icon) {
            if (!hasSwal) { window.alert(title + (lines && lines.length ? '\n\n' + lines.join('\n') : '')); return Promise.resolve(); }
            var body = null;
            if (lines && lines.length > 1) {
                body = document.createElement('ul');
                body.className = 'sp-swal-list';
                lines.forEach(function (l) { body.appendChild(textNode('li', l)); });
            } else if (lines && lines.length === 1) {
                body = textNode('p', lines[0]);
            }
            return Modal.fire({ icon: icon || 'error', title: title, html: body || undefined, confirmButtonText: 'OK' });
        },
        confirm: function (question, opts) {
            opts = opts || {};
            if (!hasSwal) return Promise.resolve(window.confirm(question));
            return Modal.fire({
                icon: opts.danger ? 'warning' : 'question',
                title: question,
                text: opts.text || undefined,
                showCancelButton: true,
                confirmButtonText: opts.button || 'Yes, continue',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: !!opts.danger,
                customClass: classes(opts.danger ? { confirmButton: 'btn sp-swal-danger' } : {})
            }).then(function (r) { return !!r.isConfirmed; });
        },
        prompt: function (title, opts) {
            opts = opts || {};
            if (!hasSwal) return Promise.resolve(window.prompt(title, opts.value || ''));
            return Modal.fire({
                title: title,
                input: opts.type || 'text',
                inputValue: opts.value || '',
                inputPlaceholder: opts.placeholder || '',
                showCancelButton: true,
                confirmButtonText: opts.button || 'OK',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                inputValidator: function (v) { return opts.required && !String(v).trim() ? (opts.requiredText || 'Please fill this in.') : undefined; }
            }).then(function (r) { return r.isConfirmed ? String(r.value).trim() : null; });
        }
    };
    window.SP = SP;

    // 1. Page messages → toast / dialog (errors first, then the success toast).
    function convertMessages() {
        if (!hasSwal) return;
        var queue = [];
        document.querySelectorAll('.alert').forEach(function (box) {
            if (box.classList.contains('alert-static') || box.hidden) return;
            var items = Array.prototype.map.call(box.querySelectorAll('li'), function (li) { return li.textContent.trim(); }).filter(Boolean);
            var text = box.textContent.replace(/\s+/g, ' ').trim();
            if (!text) return;
            if (box.classList.contains('alert-error') || box.classList.contains('alert-warning')) {
                var isWarn = box.classList.contains('alert-warning');
                var title = items.length > 1 ? 'Please check a few things' : (items[0] || text);
                queue.unshift(function () { return SP.alert(title, items.length > 1 ? items : [], isWarn ? 'warning' : 'error'); });
            } else {
                queue.push(function () { return SP.toast(text, 'success'); });
            }
            box.hidden = true;
            box.setAttribute('data-swal-shown', '');
        });
        queue.reduce(function (p, fn) { return p.then(fn); }, Promise.resolve());
    }

    // 2. data-confirm on forms and submit buttons.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        var btn = e.submitter || null;
        var source = btn && btn.hasAttribute('data-confirm') ? btn : (form.hasAttribute('data-confirm') ? form : null);
        if (!source || form.getAttribute('data-confirmed') === '1') return;
        e.preventDefault();
        SP.confirm(source.getAttribute('data-confirm'), {
            button: source.getAttribute('data-confirm-button') || undefined,
            danger: source.hasAttribute('data-confirm-danger')
        }).then(function (ok) {
            if (!ok) return;
            form.setAttribute('data-confirmed', '1');
            if (btn && form.requestSubmit) form.requestSubmit(btn); else form.submit();
            setTimeout(function () { form.removeAttribute('data-confirmed'); }, 1000);
        });
    }, true);

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', convertMessages);
    else convertMessages();
})();
