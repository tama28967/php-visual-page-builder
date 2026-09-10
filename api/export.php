<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exporter.php';

require_installed();
require_login();

$db = Database::getConnection();
$userId = current_user_id();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

function fetch_owned_page(PDO $db, int $userId, int $id): ?array
{
    $stmt = $db->prepare('SELECT id, title, slug, html, css, status FROM pages WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$id, $userId]);
    $page = $stmt->fetch();
    return $page ?: null;
}

// ---------------------------------------------------------------------
// GET: quick single-page exports (raw HTML text, raw CSS text)
// ---------------------------------------------------------------------
if ($method === 'GET' && $action === 'html') {
    $id = (int) ($_GET['id'] ?? 0);
    $page = fetch_owned_page($db, $userId, $id);
    if (!$page) {
        http_response_code(404);
        exit('Page not found.');
    }
    $html = exporter_rewrite_upload_urls($page['html'] ?? '');
    $doc = exporter_build_html_document($page['title'], $html, 'style.css');

    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="index.html"');
    echo $doc;
    exit;
}

if ($method === 'GET' && $action === 'css') {
    $id = (int) ($_GET['id'] ?? 0);
    $page = fetch_owned_page($db, $userId, $id);
    if (!$page) {
        http_response_code(404);
        exit('Page not found.');
    }
    $css = exporter_rewrite_upload_urls($page['css'] ?? '');

    header('Content-Type: text/css; charset=UTF-8');
    header('Content-Disposition: attachment; filename="style.css"');
    echo $css;
    exit;
}

if ($method === 'GET' && $action === 'page-zip') {
    $id = (int) ($_GET['id'] ?? 0);
    $page = fetch_owned_page($db, $userId, $id);
    if (!$page) {
        http_response_code(404);
        exit('Page not found.');
    }

    $html = exporter_rewrite_upload_urls($page['html'] ?? '');
    $css = exporter_rewrite_upload_urls($page['css'] ?? '');
    $doc = exporter_build_html_document($page['title'], $html, 'style.css');
    $usedImages = exporter_find_used_images($page['html'] ?? '', $page['css'] ?? '');

    if (!is_dir(EXPORTS_DIR)) {
        mkdir(EXPORTS_DIR, 0775, true);
    }
    $tmpZip = tempnam(EXPORTS_DIR, 'ppb_page_');
    $zip = new ZipArchive();
    $zip->open($tmpZip, ZipArchive::OVERWRITE);
    $zip->addFromString('index.html', $doc);
    $zip->addFromString('style.css', $css);
    foreach ($usedImages as $filename) {
        $safeName = basename($filename);
        $src = UPLOADS_DIR . '/' . $safeName;
        if (is_file($src)) {
            $zip->addFile($src, 'images/' . $safeName);
        }
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . slug_to_filename($page['slug'], 'zip') . '"');
    header('Content-Length: ' . filesize($tmpZip));
    readfile($tmpZip);
    unlink($tmpZip);
    exit;
}

// ---------------------------------------------------------------------
// POST: full website export (multiple pages) as HTML/CSS or PHP ZIP
// ---------------------------------------------------------------------
if ($method === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $format = ($_POST['format'] ?? 'html') === 'php' ? 'php' : 'html';
    $mode = ($_POST['mode'] ?? 'all') === 'selected' ? 'selected' : 'all';

    if ($mode === 'selected') {
        $ids = json_decode($_POST['page_ids'] ?? '[]', true);
        $ids = is_array($ids) ? array_map('intval', $ids) : [];
        if (empty($ids)) {
            http_response_code(400);
            exit('No pages selected.');
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT id, title, slug, html, css, status FROM pages WHERE user_id = ? AND id IN ($placeholders)");
        $stmt->execute(array_merge([$userId], $ids));
    } else {
        $stmt = $db->prepare('SELECT id, title, slug, html, css, status FROM pages WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
    $pages = $stmt->fetchAll();

    if (empty($pages)) {
        http_response_code(400);
        exit('No pages to export.');
    }

    // Build slug => filename map for internal link rewriting.
    $slugToFilename = [];
    foreach ($pages as $p) {
        $ext = $format === 'php' ? 'php' : 'html';
        $slugToFilename[$p['slug']] = slug_to_filename($p['slug'], $ext);
    }

    if (!is_dir(EXPORTS_DIR)) {
        mkdir(EXPORTS_DIR, 0775, true);
    }
    $tmpZip = tempnam(EXPORTS_DIR, 'ppb_site_');
    $zip = new ZipArchive();
    $zip->open($tmpZip, ZipArchive::OVERWRITE);

    $combinedCss = '';
    $allUsedImages = [];

    foreach ($pages as $p) {
        $rawHtml = exporter_rewrite_internal_links($p['html'] ?? '', $slugToFilename);
        $rawHtml = exporter_rewrite_upload_urls($rawHtml);
        $rawCss = exporter_rewrite_upload_urls($p['css'] ?? '');

        $combinedCss .= "\n/* --- Page: {$p['title']} ({$p['slug']}) --- */\n" . $rawCss . "\n";

        $filename = slug_to_filename($p['slug'], $format === 'php' ? 'php' : 'html');
        $doc = $format === 'php'
            ? exporter_build_php_document($p['title'], $rawHtml, 'css/style.css')
            : exporter_build_html_document($p['title'], $rawHtml, 'css/style.css');

        $zip->addFromString($filename, $doc);

        foreach (exporter_find_used_images($p['html'] ?? '', $p['css'] ?? '') as $img) {
            $allUsedImages[basename($img)] = true;
        }
    }

    $zip->addFromString('css/style.css', $combinedCss);
    $zip->addFromString('js/script.js', "// Exported by PHP Visual Page Builder\n");

    foreach (array_keys($allUsedImages) as $filename) {
        $src = UPLOADS_DIR . '/' . $filename;
        if (is_file($src)) {
            $zip->addFile($src, 'images/' . $filename);
        }
    }

    $readme = "PHP Visual Page Builder — Exported Website\n"
        . "Format: " . strtoupper($format) . "\n"
        . "Generated: " . date('Y-m-d H:i:s') . "\n\n"
        . "Open " . ($format === 'php' ? 'index.php' : 'index.html') . " in a browser, or deploy this folder to any web server"
        . ($format === 'php' ? ' with PHP support.' : '.') . "\n"
        . "All internal links and image paths are relative, so this folder is fully portable.\n";
    $zip->addFromString('README.txt', $readme);

    $zip->close();

    $zipName = $format === 'php' ? 'website-php.zip' : 'website-export.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($tmpZip));
    readfile($tmpZip);
    unlink($tmpZip);
    exit;
}

http_response_code(400);
exit('Invalid request.');
