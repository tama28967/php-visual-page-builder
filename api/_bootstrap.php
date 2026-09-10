<?php
/**
 * Shared bootstrap for all API endpoints: config, db, auth, CSRF check for
 * state-changing requests.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_installed_api();
require_login_api();

$db = Database::getConnection();
$userId = current_user_id();

// CSRF check for any state-changing method. Reads (GET) are exempt.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if ($token === null) {
        // Fall back to form-encoded/json body token for non-fetch clients.
        $body = json_input();
        $token = $body['csrf_token'] ?? ($_POST['csrf_token'] ?? null);
    }
    if (!csrf_verify($token)) {
        json_response(false, 'Invalid or missing CSRF token.', [], 403);
    }
}
