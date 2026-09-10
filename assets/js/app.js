/**
 * Shared front-end helpers: CSRF-aware fetch, toast notifications, modal control.
 */
window.PPB = (function () {
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function apiFetch(url, options) {
        options = options || {};
        options.headers = Object.assign({}, options.headers, {
            'X-CSRF-Token': getCsrfToken(),
        });
        if (options.body && !(options.body instanceof FormData)) {
            options.headers['Content-Type'] = 'application/json';
        }
        options.credentials = 'same-origin';
        return fetch(url, options).then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, status: res.status, data: data };
            });
        });
    }

    function toast(message, type) {
        var el = document.createElement('div');
        el.className = 'ppb-toast ppb-toast-' + (type || 'info');
        el.textContent = message;
        el.style.cssText = 'position:fixed;bottom:20px;right:20px;background:' +
            (type === 'error' ? '#dc2626' : '#16a34a') +
            ';color:#fff;padding:10px 16px;border-radius:8px;font-size:13px;z-index:2000;box-shadow:0 4px 12px rgba(0,0,0,.2);';
        document.body.appendChild(el);
        setTimeout(function () {
            el.remove();
        }, 3000);
    }

    function openModal(id) {
        var el = document.getElementById(id);
        if (el) el.classList.remove('hidden');
    }

    function closeModal(id) {
        var el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    }

    return { apiFetch: apiFetch, toast: toast, openModal: openModal, closeModal: closeModal, csrfToken: getCsrfToken };
})();
