/**
 * Save / load page data against the PHP API. GrapesJS's built-in
 * StorageManager is disabled (see editor.js) in favor of these explicit
 * calls so we control exactly when saves happen and can show status text.
 */
var PPBStorage = (function () {
    var saveStatusEl = null;
    var currentPageId = window.PPB_PAGE && window.PPB_PAGE.id ? window.PPB_PAGE.id : null;

    function setStatus(text, cls) {
        if (!saveStatusEl) saveStatusEl = document.getElementById('saveStatus');
        if (!saveStatusEl) return;
        saveStatusEl.textContent = text;
        saveStatusEl.className = 'save-status' + (cls ? ' ' + cls : '');
    }

    function markDirty() {
        setStatus('Unsaved changes', 'dirty');
    }

    function savePage(editor, opts) {
        opts = opts || {};
        var titleInput = document.getElementById('pageTitleInput');
        var title = titleInput ? titleInput.value.trim() : '';

        if (!title) {
            if (!opts.silent) PPB.toast('Please enter a page title', 'error');
            return Promise.resolve(null);
        }

        setStatus('Saving...', 'dirty');

        var slugInput = document.getElementById('pageSlugInput');
        var slug = slugInput ? slugInput.value.trim() : '';

        var payload = {
            id: currentPageId,
            title: title,
            slug: slug,
            html: editor.getHtml(),
            css: editor.getCss(),
            status: opts.status || (window.PPB_PAGE ? window.PPB_PAGE.status : 'draft'),
        };

        return PPB.apiFetch(APP_API_BASE + '/page-save.php', {
            method: 'POST',
            body: JSON.stringify(payload),
        }).then(function (res) {
            if (res.data.success) {
                currentPageId = res.data.id;
                window.PPB_PAGE = window.PPB_PAGE || {};
                window.PPB_PAGE.id = currentPageId;
                window.PPB_PAGE.slug = res.data.slug;
                if (slugInput) slugInput.value = res.data.slug;
                setStatus('Saved', 'saved');
                if (!opts.silent) PPB.toast('Page saved', 'success');
                // Reflect the real id in the URL without reloading the editor.
                if (window.history && window.history.replaceState) {
                    var url = new URL(window.location.href);
                    url.searchParams.set('id', currentPageId);
                    url.searchParams.delete('new');
                    window.history.replaceState({}, '', url);
                }
                return res.data;
            } else {
                setStatus('Save failed', 'dirty');
                PPB.toast(res.data.message || 'Save failed', 'error');
                return null;
            }
        }).catch(function () {
            setStatus('Save failed', 'dirty');
            PPB.toast('Network error while saving', 'error');
            return null;
        });
    }

    function getCurrentPageId() {
        return currentPageId;
    }

    return { savePage: savePage, markDirty: markDirty, getCurrentPageId: getCurrentPageId, setStatus: setStatus };
})();
