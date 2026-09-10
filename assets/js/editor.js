/**
 * Main GrapesJS bootstrap. GrapesJS itself provides the Block Manager,
 * Style Manager, Layer Manager, Trait Manager, Asset Manager, Device
 * Manager, Undo Manager and default panel UI — this file only configures
 * and wires it up; it does not reimplement any of that.
 */
(function () {
    var editor = grapesjs.init({
        container: '#gjs',
        fromElement: true,
        height: 'calc(100vh - 52px)',
        width: 'auto',
        storageManager: false,
        undoManager: { trackSelection: false },

        deviceManager: {
            devices: [
                { name: 'Desktop', width: '' },
                { name: 'Tablet', width: '768px', widthMedia: '992px' },
                { name: 'Mobile', width: '375px', widthMedia: '575px' },
            ],
        },

        assetManager: {
            upload: APP_API_BASE + '/media-upload.php',
            uploadName: 'file',
            headers: { 'X-CSRF-Token': PPB.csrfToken() },
            autoAdd: true,
            multiUpload: false,
        },

        canvas: {
            styles: [],
        },

        styleManager: {
            sectors: [
                {
                    name: 'Layout',
                    open: true,
                    properties: [
                        'display', 'position', 'width', 'height', 'min-width', 'max-width',
                        'min-height', 'max-height', 'margin', 'padding', 'overflow', 'z-index',
                    ],
                },
                {
                    name: 'Typography',
                    open: false,
                    properties: [
                        'font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing',
                        'text-align', 'text-transform', 'text-decoration', 'color',
                    ],
                },
                {
                    name: 'Background',
                    open: false,
                    properties: [
                        'background-color', 'background-image', 'background-size',
                        'background-position', 'background-repeat',
                    ],
                },
                {
                    name: 'Border',
                    open: false,
                    properties: [
                        'border-width', 'border-style', 'border-color', 'border-radius',
                    ],
                },
                {
                    name: 'Effects',
                    open: false,
                    properties: ['box-shadow', 'opacity'],
                },
            ],
        },
    });

    // ---- Load stored page (HTML already injected server-side via #gjs,
    // so just load the CSS on top of it).
    if (window.PPB_PAGE && window.PPB_PAGE.css) {
        editor.setStyle(window.PPB_PAGE.css);
    }

    registerBlocks(editor);
    initDeviceButtons(editor);
    initExportUI(editor);

    // ---- Undo / redo buttons
    document.getElementById('btn-undo').addEventListener('click', function () {
        editor.UndoManager.undo();
    });
    document.getElementById('btn-redo').addEventListener('click', function () {
        editor.UndoManager.redo();
    });

    // ---- Save
    function doSave(opts) {
        return PPBStorage.savePage(editor, opts || {});
    }
    document.getElementById('btn-save').addEventListener('click', function () {
        doSave();
    });

    // Mark dirty on any change; keyboard shortcut Ctrl/Cmd+S saves.
    editor.on('component:update component:add component:remove style:update', function () {
        PPBStorage.markDirty();
    });
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            doSave();
        }
    });

    // ---- Preview
    document.getElementById('btn-preview').addEventListener('click', function () {
        doSave({ silent: true }).then(function (result) {
            var id = result ? result.id : PPBStorage.getCurrentPageId();
            if (id) {
                window.open(APP_PREVIEW_URL + '?id=' + id, '_blank');
            } else {
                PPB.toast('Enter a title and save first', 'error');
            }
        });
    });

    // ---- Save as Template
    document.getElementById('btn-template').addEventListener('click', function () {
        var titleInput = document.getElementById('pageTitleInput');
        document.getElementById('templateNameInput').value = (titleInput.value || 'Untitled') + ' Template';
        PPB.openModal('templateSaveModal');
    });

    document.getElementById('templateSaveConfirm').addEventListener('click', function () {
        var name = document.getElementById('templateNameInput').value.trim();
        if (!name) {
            PPB.toast('Template name is required', 'error');
            return;
        }
        PPB.apiFetch(APP_API_BASE + '/template-save.php', {
            method: 'POST',
            body: JSON.stringify({ name: name, html: editor.getHtml(), css: editor.getCss() }),
        }).then(function (res) {
            if (res.data.success) {
                PPB.toast('Template saved', 'success');
                PPB.closeModal('templateSaveModal');
            } else {
                PPB.toast(res.data.message || 'Failed to save template', 'error');
            }
        });
    });

    // ---- Load Template
    document.getElementById('btn-load-template').addEventListener('click', function () {
        var list = document.getElementById('templateLoadList');
        list.textContent = 'Loading…';
        PPB.openModal('templateLoadModal');
        PPB.apiFetch(APP_API_BASE + '/template-list.php').then(function (res) {
            if (!res.data.success || !res.data.templates.length) {
                list.innerHTML = '<div class="empty-state">No templates saved yet.</div>';
                return;
            }
            list.innerHTML = '';
            res.data.templates.forEach(function (t) {
                var row = document.createElement('label');
                row.style.justifyContent = 'space-between';
                row.innerHTML = '<span>' + t.name.replace(/</g, '&lt;') + '</span>';
                var btnGroup = document.createElement('span');
                btnGroup.style.display = 'flex';
                btnGroup.style.gap = '6px';

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-secondary btn-sm';
                btn.textContent = 'Insert';
                btn.addEventListener('click', function () {
                    PPB.apiFetch(APP_API_BASE + '/template-load.php?id=' + t.id).then(function (r) {
                        if (r.data.success) {
                            editor.setComponents(r.data.template.html || '');
                            editor.setStyle(r.data.template.css || '');
                            PPBStorage.markDirty();
                            PPB.closeModal('templateLoadModal');
                            PPB.toast('Template loaded', 'success');
                        } else {
                            PPB.toast(r.data.message || 'Failed to load template', 'error');
                        }
                    });
                });

                var delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'btn btn-danger btn-sm';
                delBtn.textContent = 'Delete';
                delBtn.addEventListener('click', function () {
                    if (!confirm('Delete template "' + t.name + '"?')) return;
                    PPB.apiFetch(APP_API_BASE + '/template-delete.php', {
                        method: 'POST',
                        body: JSON.stringify({ id: t.id }),
                    }).then(function (r) {
                        if (r.data.success) {
                            row.remove();
                            PPB.toast('Template deleted', 'success');
                        } else {
                            PPB.toast(r.data.message || 'Failed to delete template', 'error');
                        }
                    });
                });

                btnGroup.appendChild(btn);
                btnGroup.appendChild(delBtn);
                row.appendChild(btnGroup);
                list.appendChild(row);
            });
        });
    });

    // Warn on unload if there are unsaved changes.
    window.addEventListener('beforeunload', function (e) {
        var status = document.getElementById('saveStatus');
        if (status && status.classList.contains('dirty') && status.textContent !== 'Saving...') {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();
