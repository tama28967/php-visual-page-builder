<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_installed();
require_login();

$db = Database::getConnection();
$userId = current_user_id();

$stmt = $db->prepare('SELECT COUNT(*) FROM pages WHERE user_id = ?');
$stmt->execute([$userId]);
$totalPages = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM templates WHERE user_id = ?');
$stmt->execute([$userId]);
$totalTemplates = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM media WHERE user_id = ?');
$stmt->execute([$userId]);
$totalMedia = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT MAX(updated_at) FROM pages WHERE user_id = ?');
$stmt->execute([$userId]);
$lastUpdated = $stmt->fetchColumn();

$stmt = $db->prepare('SELECT id, title, slug, status, updated_at FROM pages WHERE user_id = ? ORDER BY updated_at DESC');
$stmt->execute([$userId]);
$pages = $stmt->fetchAll();

$pageTitle = 'Dashboard — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<div class="page-header">
    <h1>Dashboard</h1>
    <div class="page-header-actions">
        <button class="btn btn-secondary" onclick="PPB.openModal('exportModal')">Export Website</button>
        <a class="btn btn-primary" href="<?= APP_URL ?>/editor.php?new=1">+ New Page</a>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-label">Total Pages</div><div class="stat-value"><?= $totalPages ?></div></div>
    <div class="stat-card"><div class="stat-label">Total Templates</div><div class="stat-value"><?= $totalTemplates ?></div></div>
    <div class="stat-card"><div class="stat-label">Total Media</div><div class="stat-value"><?= $totalMedia ?></div></div>
    <div class="stat-card"><div class="stat-label">Last Updated</div><div class="stat-value" style="font-size:16px;"><?= $lastUpdated ? e(date('M j, Y H:i', strtotime($lastUpdated))) : '—' ?></div></div>
</div>

<div class="card">
    <div class="card-header"><h2>Pages</h2></div>
    <?php if (empty($pages)): ?>
        <div class="empty-state">No pages yet. Click "+ New Page" to get started.</div>
    <?php else: ?>
    <table class="data-table" id="pagesTable">
        <thead>
            <tr><th>Title</th><th>Slug</th><th>Status</th><th>Updated</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($pages as $p): ?>
            <tr data-page-id="<?= $p['id'] ?>">
                <td><?= e($p['title']) ?></td>
                <td><?= e($p['slug']) ?></td>
                <td><span class="badge badge-<?= $p['status'] ?>"><?= e($p['status']) ?></span></td>
                <td><?= e(date('M j, Y H:i', strtotime($p['updated_at']))) ?></td>
                <td class="action-links">
                    <a href="<?= APP_URL ?>/editor.php?id=<?= $p['id'] ?>">Edit</a>
                    <a href="<?= APP_URL ?>/preview.php?id=<?= $p['id'] ?>" target="_blank">Preview</a>
                    <button type="button" onclick="dashboardDuplicatePage(<?= $p['id'] ?>)">Duplicate</button>
                    <button type="button" class="danger" onclick="dashboardDeletePage(<?= $p['id'] ?>)">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Export Modal -->
<div class="modal-overlay hidden" id="exportModal">
    <div class="modal">
        <h2>Export Website</h2>
        <p style="font-size:13px;color:var(--color-text-light);">Choose pages and format to export.</p>
        <div class="checkbox-list" id="exportPageList">
            <label><input type="checkbox" class="export-page-all" checked> <strong>All Pages</strong></label>
            <?php foreach ($pages as $p): ?>
                <label><input type="checkbox" class="export-page-item" value="<?= $p['id'] ?>" checked> <?= e($p['title']) ?> (<?= e($p['slug']) ?>)</label>
            <?php endforeach; ?>
        </div>
        <div class="radio-row">
            <label><input type="radio" name="exportFormat" value="html" checked> HTML/CSS</label>
            <label><input type="radio" name="exportFormat" value="php"> PHP</label>
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="PPB.closeModal('exportModal')">Cancel</button>
            <button class="btn btn-primary" id="exportZipBtn">Export ZIP</button>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
<script src="<?= ASSETS_URL ?>/js/dashboard.js"></script>
