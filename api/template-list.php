<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, 'Method not allowed.', [], 405);
}

$stmt = $db->prepare('SELECT id, name, thumbnail, created_at, updated_at FROM templates WHERE user_id = ? ORDER BY updated_at DESC');
$stmt->execute([$userId]);

json_response(true, 'OK', ['templates' => $stmt->fetchAll()]);
