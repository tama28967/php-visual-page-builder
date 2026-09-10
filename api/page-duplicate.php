<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed.', [], 405);
}

$body = json_input();
$id = isset($body['id']) ? (int) $body['id'] : 0;

if ($id <= 0) {
    json_response(false, 'Invalid page id.', [], 400);
}

$stmt = $db->prepare('SELECT title, slug, html, css FROM pages WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$id, $userId]);
$page = $stmt->fetch();

if (!$page) {
    json_response(false, 'Page not found.', [], 404);
}

$newTitle = $page['title'] . ' Copy';
$baseSlug = trim($page['slug'], '/');
$newSlug = '/' . ($baseSlug === '' ? 'home' : $baseSlug) . '-copy-' . substr(bin2hex(random_bytes(2)), 0, 4);

$stmt = $db->prepare('INSERT INTO pages (user_id, title, slug, html, css, status) VALUES (?, ?, ?, ?, ?, "draft")');
$stmt->execute([$userId, $newTitle, $newSlug, $page['html'], $page['css']]);

json_response(true, 'Page duplicated.', ['id' => (int) $db->lastInsertId()]);
