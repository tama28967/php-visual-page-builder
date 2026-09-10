<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, 'Method not allowed.', [], 405);
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    json_response(false, 'Invalid template id.', [], 400);
}

$stmt = $db->prepare('SELECT id, name, html, css FROM templates WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$id, $userId]);
$template = $stmt->fetch();

if (!$template) {
    json_response(false, 'Template not found.', [], 404);
}

json_response(true, 'OK', ['template' => $template]);
