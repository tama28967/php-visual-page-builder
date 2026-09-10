<?php
/**
 * Authentication helpers. Requires config/config.php to already be loaded
 * (for session handling) before this file is included.
 */

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function is_logged_in(): bool
{
    return current_user_id() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

function require_login_api(): void
{
    if (!is_logged_in()) {
        json_response(false, 'Authentication required.', [], 401);
    }
}

function attempt_login(PDO $db, string $username, string $password): bool
{
    $stmt = $db->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        return true;
    }

    return false;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
