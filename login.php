<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_installed();

if (is_logged_in()) {
    redirect('/dashboard.php');
}

// Installed but no admin account yet (e.g. it was deleted from
// phpMyAdmin) — send people back to the wizard's admin-account step
// instead of a dead-end login form. Only on a plain page load, not while
// a login POST is being processed.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $db = Database::getConnection();
    $hasUsers = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    if (!$hasUsers) {
        redirect('/setup.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Username and password are required.';
        } else {
            try {
                $db = Database::getConnection();
                if (attempt_login($db, $username, $password)) {
                    redirect('/dashboard.php');
                }
                $error = 'Invalid username or password.';
            } catch (Throwable $e) {
                error_log('Login error: ' . $e->getMessage());
                $error = 'Login failed. Has setup.php been run yet?';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/app.css">
</head>
<body class="auth-body">
<div class="auth-card">
    <h1><?= e(APP_NAME) ?></h1>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= APP_URL ?>/login.php">
        <?= csrf_field() ?>
        <label>Username
            <input type="text" name="username" autocomplete="username" required autofocus>
        </label>
        <label>Password
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button type="submit" class="btn btn-primary btn-block">Login</button>
    </form>
    <p class="auth-hint">First time? Run <a href="<?= APP_URL ?>/setup.php">setup.php</a> to create the database and an admin account.</p>
</div>
</body>
</html>
