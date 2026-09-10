<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, 'Method not allowed.', [], 405);
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    json_response(false, 'Invalid page id.', [], 400);
}

$stmt = $db->prepare('SELECT id, title, slug, html, css, status, created_at, updated_at FROM pages WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$id, $userId]);
$page = $stmt->fetch();

if (!$page) {
    json_response(false, 'Page not found.', [], 404);
}

json_response(true, 'OK', ['page' => $page]);
