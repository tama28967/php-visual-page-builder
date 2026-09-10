<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare('SELECT id, filename, original_name, path, mime_type, size, created_at FROM media WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['url'] = UPLOADS_URL . '/' . $row['filename'];
    }
    unset($row);

    json_response(true, 'OK', ['media' => $rows]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete action (kept in this file since the project structure only
    // defines media-upload.php and media-list.php for the media API).
    $body = json_input();
    $id = isset($body['id']) ? (int) $body['id'] : 0;

    if ($id <= 0) {
        json_response(false, 'Invalid media id.', [], 400);
    }

    $stmt = $db->prepare('SELECT filename FROM media WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$id, $userId]);
    $media = $stmt->fetch();

    if (!$media) {
        json_response(false, 'Media not found.', [], 404);
    }

    $stmt = $db->prepare('DELETE FROM media WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);

    $filePath = UPLOADS_DIR . '/' . basename($media['filename']);
    if (is_file($filePath)) {
        unlink($filePath);
    }

    json_response(true, 'Media deleted.');
}

json_response(false, 'Method not allowed.', [], 405);
