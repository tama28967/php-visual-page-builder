<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_installed();
require_login();

$db = Database::getConnection();
$userId = current_user_id();

$pageId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$page = null;

if ($pageId > 0) {
    $stmt = $db->prepare('SELECT id, title, slug, html, css, status FROM pages WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$pageId, $userId]);
    $page = $stmt->fetch();
    if (!$page) {
        redirect('/dashboard.php');
    }
} elseif (!isset($_GET['new'])) {
    redirect('/dashboard.php');
}

$pageTitle = ($page ? 'Edit: ' . $page['title'] : 'New Page') . ' — ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<link rel="stylesheet" href="<?= GRAPESJS_URL ?>/grapes.min.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/app.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/editor.css">
<script>
    window.APP_API_BASE = <?= json_encode(APP_URL . '/api') ?>;
    window.APP_DASHBOARD_URL = <?= json_encode(APP_URL . '/dashboard.php') ?>;
    window.APP_PREVIEW_URL = <?= json_encode(APP_URL . '/preview.php') ?>;
    window.GRAPESJS_ASSETS_URL = <?= json_encode(ASSETS_URL) ?>;
    window.PPB_PAGE = <?= json_encode([
        'id' => $page ? (int) $page['id'] : null,
        'title' => $page ? $page['title'] : '',
        'slug' => $page ? $page['slug'] : '',
        'html' => $page ? $page['html'] : '',
        'css' => $page ? $page['css'] : '',
        'status' => $page ? $page['status'] : 'draft',
    ]) ?>;
</script>
</head>
<body class="editor-body">

<div id="editor-topbar" class="editor-topbar">
    <div class="topbar-left">
        <a href="<?= APP_URL ?>/dashboard.php" class="topbar-logo">◀ PHP Page Builder</a>
        <input type="text" id="pageTitleInput" class="page-title-input" value="<?= e($page['title'] ?? '') ?>" placeholder="Page title">
        <input type="text" id="pageSlugInput" class="page-title-input" value="<?= e($page['slug'] ?? '') ?>" placeholder="/slug (e.g. / or /about)">
    </div>
    <div class="topbar-center">
        <button class="tb-btn" id="btn-undo" title="Undo">↶</button>
        <button class="tb-btn" id="btn-redo" title="Redo">↷</button>
        <span class="tb-sep"></span>
        <button class="tb-btn device-btn active" data-device="Desktop" title="Desktop">🖥</button>
        <button class="tb-btn device-btn" data-device="Tablet" title="Tablet">📱</button>
        <button class="tb-btn device-btn" data-device="Mobile" title="Mobile">📱</button>
    </div>
    <div class="topbar-right">
        <span id="saveStatus" class="save-status">Unsaved changes</span>
        <button class="btn btn-secondary btn-sm" id="btn-preview">Preview</button>
        <button class="btn btn-secondary btn-sm" id="btn-template">Save as Template</button>
        <button class="btn btn-secondary btn-sm" id="btn-load-template">Load Template</button>
        <button class="btn btn-secondary btn-sm" id="btn-export">Export</button>
        <button class="btn btn-primary btn-sm" id="btn-save">Save</button>
    </div>
</div>

<div id="gjs"><?= $page ? $page['html'] : '' ?></div>

<!-- Export single-page modal -->
<div class="modal-overlay hidden" id="pageExportModal">
    <div class="modal">
        <h2>Export This Page</h2>
        <p style="font-size:13px;color:var(--color-text-light);">Download the current page as static files.</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="PPB.closeModal('pageExportModal')">Cancel</button>
            <a class="btn btn-secondary" id="exportHtmlOnly" target="_blank">Export HTML</a>
            <a class="btn btn-secondary" id="exportCssOnly" target="_blank">Export CSS</a>
            <a class="btn btn-primary" id="exportPageZip">Export HTML+CSS ZIP</a>
        </div>
    </div>
</div>

<!-- Save as Template modal -->
<div class="modal-overlay hidden" id="templateSaveModal">
    <div class="modal">
        <h2>Save as Template</h2>
        <form id="templateSaveForm">
            <label>Template Name
                <input type="text" id="templateNameInput" required>
            </label>
        </form>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="PPB.closeModal('templateSaveModal')">Cancel</button>
            <button class="btn btn-primary" id="templateSaveConfirm">Save Template</button>
        </div>
    </div>
</div>

<!-- Load Template modal -->
<div class="modal-overlay hidden" id="templateLoadModal">
    <div class="modal">
        <h2>Load Template</h2>
        <p style="font-size:13px;color:var(--color-text-light);">Inserting a template replaces the current canvas content.</p>
        <div id="templateLoadList" class="checkbox-list" style="max-height:320px;">Loading…</div>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="PPB.closeModal('templateLoadModal')">Cancel</button>
        </div>
    </div>
</div>

<script src="<?= GRAPESJS_URL ?>/grapes.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/app.js"></script>
<script src="<?= ASSETS_URL ?>/js/blocks.js"></script>
<script src="<?= ASSETS_URL ?>/js/devices.js"></script>
<script src="<?= ASSETS_URL ?>/js/storage.js"></script>
<script src="<?= ASSETS_URL ?>/js/export.js"></script>
<script src="<?= ASSETS_URL ?>/js/editor.js"></script>
</body>
</html>
