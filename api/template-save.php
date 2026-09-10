<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed.', [], 405);
}

$body = json_input();
$name = trim($body['name'] ?? '');
$html = strip_outer_body_tag((string) ($body['html'] ?? ''));
$css = (string) ($body['css'] ?? '');

if ($name === '') {
    json_response(false, 'Template name is required.', [], 400);
}

$stmt = $db->prepare('INSERT INTO templates (user_id, name, html, css) VALUES (?, ?, ?, ?)');
$stmt->execute([$userId, $name, $html, $css]);

json_response(true, 'Template saved.', ['id' => (int) $db->lastInsertId()]);
