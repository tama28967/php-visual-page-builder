<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_installed();
require_login();

$db = Database::getConnection();
$userId = current_user_id();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $db->prepare('SELECT title, html, css FROM pages WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$id, $userId]);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    exit('Page not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page['title']) ?> — Preview</title>
<style><?= $page['css'] ?></style>
</head>
<body>
<?= $page['html'] ?>
</body>
</html>
