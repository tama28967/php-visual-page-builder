<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed.', [], 405);
}

$body = json_input();
$id = isset($body['id']) ? (int) $body['id'] : 0;

if ($id <= 0) {
    json_response(false, 'Invalid template id.', [], 400);
}

$stmt = $db->prepare('DELETE FROM templates WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $userId]);

if ($stmt->rowCount() === 0) {
    json_response(false, 'Template not found.', [], 404);
}

json_response(true, 'Template deleted.');
