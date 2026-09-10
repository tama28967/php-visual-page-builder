function dashboardDeletePage(id) {
    if (!confirm('Delete this page? This cannot be undone.')) return;
    PPB.apiFetch(APP_API_BASE + '/page-delete.php', {
        method: 'POST',
        body: JSON.stringify({ id: id }),
    }).then(function (res) {
        if (res.data.success) {
            document.querySelector('tr[data-page-id="' + id + '"]').remove();
            PPB.toast('Page deleted', 'success');
        } else {
            PPB.toast(res.data.message || 'Delete failed', 'error');
        }
    });
}

function dashboardDuplicatePage(id) {
    PPB.apiFetch(APP_API_BASE + '/page-duplicate.php', {
        method: 'POST',
        body: JSON.stringify({ id: id }),
    }).then(function (res) {
        if (res.data.success) {
            PPB.toast('Page duplicated', 'success');
            window.location.reload();
        } else {
            PPB.toast(res.data.message || 'Duplicate failed', 'error');
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    var allCb = document.querySelector('.export-page-all');
    var itemCbs = document.querySelectorAll('.export-page-item');

    if (allCb) {
        allCb.addEventListener('change', function () {
            itemCbs.forEach(function (cb) {
                cb.checked = allCb.checked;
                cb.disabled = allCb.checked;
            });
        });
        itemCbs.forEach(function (cb) { cb.disabled = allCb.checked; });
    }

    var exportBtn = document.getElementById('exportZipBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            var format = document.querySelector('input[name="exportFormat"]:checked').value;
            var ids = [];
            if (!allCb.checked) {
                itemCbs.forEach(function (cb) {
                    if (cb.checked) ids.push(parseInt(cb.value, 10));
                });
                if (ids.length === 0) {
                    PPB.toast('Select at least one page', 'error');
                    return;
                }
            }
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = APP_API_BASE + '/export.php';

            function addField(name, value) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            }
            addField('csrf_token', PPB.csrfToken());
            addField('format', format);
            addField('mode', allCb.checked ? 'all' : 'selected');
            if (ids.length) addField('page_ids', JSON.stringify(ids));

            document.body.appendChild(form);
            form.submit();
            form.remove();
            PPB.closeModal('exportModal');
        });
    }
});
