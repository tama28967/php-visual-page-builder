/**
 * Editor-side export wiring: single-page HTML / CSS / ZIP downloads.
 * Full multi-page website export lives on the dashboard (dashboard.js).
 */
function initExportUI(editor) {
    var exportBtn = document.getElementById('btn-export');
    var htmlLink = document.getElementById('exportHtmlOnly');
    var cssLink = document.getElementById('exportCssOnly');
    var zipBtn = document.getElementById('exportPageZip');

    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            var id = PPBStorage.getCurrentPageId();
            if (!id) {
                PPB.toast('Save the page before exporting', 'error');
                return;
            }
            htmlLink.href = APP_API_BASE + '/export.php?action=html&id=' + id;
            cssLink.href = APP_API_BASE + '/export.php?action=css&id=' + id;
            PPB.openModal('pageExportModal');
        });
    }

    if (zipBtn) {
        zipBtn.addEventListener('click', function () {
            var id = PPBStorage.getCurrentPageId();
            if (!id) return;
            window.location.href = APP_API_BASE + '/export.php?action=page-zip&id=' + id;
            PPB.closeModal('pageExportModal');
        });
    }
}
