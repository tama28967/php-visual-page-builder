<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_installed_api();
require_login_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed.', [], 405);
}

// GrapesJS AssetManager posts multipart/form-data; the CSRF token travels
// as a header set in editor.js's assetManager config.
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
if (!csrf_verify($token)) {
    json_response(false, 'Invalid or missing CSRF token.', [], 403);
}

$db = Database::getConnection();
$userId = current_user_id();

if (empty($_FILES['file'])) {
    json_response(false, 'No file uploaded.', [], 400);
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response(false, 'Upload error (code ' . $file['error'] . ').', [], 400);
}

if ($file['size'] > MAX_UPLOAD_BYTES) {
    json_response(false, 'File exceeds the 5 MB limit.', [], 400);
}

$originalName = $file['name'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($ext, ALLOWED_IMAGE_EXT, true)) {
    json_response(false, 'Unsupported file extension.', [], 400);
}

// Never trust the client-supplied MIME type or extension alone — verify
// the actual file content.
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$actualMime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($actualMime, ALLOWED_IMAGE_MIME, true)) {
    json_response(false, 'File content does not match an allowed image type.', [], 400);
}

if (@getimagesize($file['tmp_name']) === false) {
    json_response(false, 'File is not a valid image.', [], 400);
}

$filename = generate_unique_filename($ext);
$destination = UPLOADS_DIR . '/' . $filename;

if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0775, true);
}

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    json_response(false, 'Failed to store uploaded file.', [], 500);
}

$stmt = $db->prepare('INSERT INTO media (user_id, filename, original_name, path, mime_type, size) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$userId, $filename, $originalName, 'uploads/images/' . $filename, $actualMime, $file['size']]);

$url = UPLOADS_URL . '/' . $filename;

// Response shaped for GrapesJS AssetManager: it expects { data: [...] } or
// a single object; we return both a GrapesJS-friendly asset and our own
// success envelope.
json_response(true, 'Uploaded.', [
    'data' => [[
        'src' => $url,
        'name' => $originalName,
        'type' => 'image',
    ]],
    'src' => $url,
]);
