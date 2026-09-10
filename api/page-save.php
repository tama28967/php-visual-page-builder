<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed.', [], 405);
}

$body = json_input();

$id = isset($body['id']) ? (int) $body['id'] : 0;
$title = trim($body['title'] ?? '');
$slugInput = trim($body['slug'] ?? '');
$html = strip_outer_body_tag((string) ($body['html'] ?? ''));
$css = (string) ($body['css'] ?? '');
$status = ($body['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

if ($title === '') {
    json_response(false, 'Title is required.', [], 400);
}

$slug = slugify($slugInput !== '' ? $slugInput : $title);

try {
    if ($id > 0) {
        // Verify ownership.
        $stmt = $db->prepare('SELECT id FROM pages WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $userId]);
        if (!$stmt->fetch()) {
            json_response(false, 'Page not found.', [], 404);
        }

        // Ensure slug uniqueness (excluding self).
        $stmt = $db->prepare('SELECT id FROM pages WHERE slug = ? AND id != ? LIMIT 1');
        $stmt->execute([$slug, $id]);
        if ($stmt->fetch()) {
            $slug = $slug . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        $stmt = $db->prepare('UPDATE pages SET title = ?, slug = ?, html = ?, css = ?, status = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$title, $slug, $html, $css, $status, $id, $userId]);

        json_response(true, 'Page saved.', ['id' => $id, 'slug' => $slug]);
    } else {
        $stmt = $db->prepare('SELECT id FROM pages WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug = $slug . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        $stmt = $db->prepare('INSERT INTO pages (user_id, title, slug, html, css, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $title, $slug, $html, $css, $status]);
        $newId = (int) $db->lastInsertId();

        json_response(true, 'Page created.', ['id' => $newId, 'slug' => $slug]);
    }
} catch (Throwable $e) {
    error_log('page-save error: ' . $e->getMessage());
    json_response(false, 'Failed to save page.', [], 500);
}
