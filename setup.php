<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// ---------------------------------------------------------------------
// System checks (always shown, never require a database connection).
// ---------------------------------------------------------------------
$checks = [
    ['label' => 'PHP version >= 8.0', 'ok' => version_compare(PHP_VERSION, '8.0.0', '>='), 'detail' => PHP_VERSION],
    ['label' => 'PDO extension', 'ok' => extension_loaded('pdo'), 'detail' => extension_loaded('pdo') ? 'available' : 'missing'],
    ['label' => 'PDO MySQL driver', 'ok' => extension_loaded('pdo_mysql'), 'detail' => extension_loaded('pdo_mysql') ? 'available' : 'missing'],
    ['label' => 'ZipArchive extension', 'ok' => class_exists('ZipArchive'), 'detail' => class_exists('ZipArchive') ? 'available' : 'missing'],
    ['label' => 'uploads/images/ is writable', 'ok' => is_dir(UPLOADS_DIR) && is_writable(UPLOADS_DIR), 'detail' => UPLOADS_DIR],
    ['label' => 'exports/ is writable', 'ok' => is_dir(EXPORTS_DIR) && is_writable(EXPORTS_DIR), 'detail' => EXPORTS_DIR],
    ['label' => 'config/ is writable', 'ok' => is_writable(APP_ROOT . '/config'), 'detail' => APP_ROOT . '/config'],
];
$allChecksOk = true;
foreach ($checks as $c) {
    if (!$c['ok']) {
        $allChecksOk = false;
    }
}

$error = '';
$message = '';
$messageType = '';

/**
 * Create the required tables. Safe to call repeatedly — never drops data.
 */
function ppb_create_tables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(60) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `pages` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `title` VARCHAR(190) NOT NULL,
        `slug` VARCHAR(190) NOT NULL,
        `html` LONGTEXT NULL,
        `css` LONGTEXT NULL,
        `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_slug` (`slug`),
        KEY `idx_user_id` (`user_id`),
        CONSTRAINT `fk_pages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `templates` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `name` VARCHAR(190) NOT NULL,
        `thumbnail` VARCHAR(255) NULL,
        `html` LONGTEXT NULL,
        `css` LONGTEXT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_user_id` (`user_id`),
        CONSTRAINT `fk_templates_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `media` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `filename` VARCHAR(255) NOT NULL,
        `original_name` VARCHAR(255) NOT NULL,
        `path` VARCHAR(255) NOT NULL,
        `mime_type` VARCHAR(100) NOT NULL,
        `size` INT UNSIGNED NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_user_id` (`user_id`),
        CONSTRAINT `fk_media_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ---------------------------------------------------------------------
// STEP 1 — database connection (only shown while not installed).
// ---------------------------------------------------------------------
if (!APP_INSTALLED && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['wizard_step'] ?? '') === '1') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please try again.';
    } else {
        $dbHost = trim($_POST['db_host'] ?? '');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = (string) ($_POST['db_pass'] ?? '');

        if ($dbHost === '' || $dbName === '' || $dbUser === '') {
            $error = 'Database host, name, and username are required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{1,64}$/', $dbName)) {
            $error = 'Database name may only contain letters, numbers, and underscores.';
        } elseif (strpos($dbHost, ';') !== false || strpos($dbUser, ';') !== false) {
            $error = 'Database host/username contain invalid characters.';
        } else {
            try {
                $pdo = new PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                $configContent = "<?php\n"
                    . "// Generated by setup.php — do not edit by hand while the wizard is running.\n"
                    . "define('DB_HOST', " . var_export($dbHost, true) . ");\n"
                    . "define('DB_NAME', " . var_export($dbName, true) . ");\n"
                    . "define('DB_USER', " . var_export($dbUser, true) . ");\n"
                    . "define('DB_PASS', " . var_export($dbPass, true) . ");\n";

                if (file_put_contents(INSTALL_CONFIG_FILE, $configContent) === false) {
                    $error = 'Could not write config/installed.php. Check that the config/ folder is writable.';
                } else {
                    // Reload as a fresh GET so APP_INSTALLED is recomputed and
                    // the wizard moves on to step 2.
                    redirect('/setup.php');
                }
            } catch (Throwable $e) {
                error_log('Setup step 1 error: ' . $e->getMessage());
                $error = 'Could not connect: ' . $e->getMessage();
            }
        }
    }
}

// ---------------------------------------------------------------------
// STEP 2 — admin account (only shown once installed).
// ---------------------------------------------------------------------
$dbReady = false;
$userCount = 0;
$adminCreated = false;

if (APP_INSTALLED) {
    require_once __DIR__ . '/config/database.php';
    try {
        $pdo = Database::getConnection();
        ppb_create_tables($pdo);
        $dbReady = true;
        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    } catch (Throwable $e) {
        error_log('Setup step 2 error: ' . $e->getMessage());
        $error = 'Installed, but could not connect using config/installed.php: ' . $e->getMessage();
    }

    if ($dbReady && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['wizard_step'] ?? '') === '2') {
        if (!csrf_verify($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token. Please try again.';
        } else {
            $adminUser = trim($_POST['admin_username'] ?? '');
            $adminPass = (string) ($_POST['admin_password'] ?? '');
            $adminPassConfirm = (string) ($_POST['admin_password_confirm'] ?? '');

            if ($adminUser === '' || $adminPass === '') {
                $error = 'Admin username and password are required.';
            } elseif ($adminPass !== $adminPassConfirm) {
                $error = 'Passwords do not match.';
            } elseif (strlen($adminPass) < 8) {
                $error = 'Password must be at least 8 characters.';
            } else {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
                $stmt->execute([$adminUser]);
                if ((int) $stmt->fetchColumn() > 0) {
                    $error = "Username '{$adminUser}' already exists.";
                } else {
                    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
                    $ins->execute([$adminUser, $hash]);
                    $adminCreated = true;
                    $userCount++;
                    $message = "Admin account '{$adminUser}' created. Setup is complete.";
                    $messageType = 'success';
                }
            }
        }
    }
}

$step = !APP_INSTALLED ? 1 : ($userCount === 0 ? 2 : 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/app.css">
<style>
.wizard-steps { display:flex; gap:8px; margin-bottom:22px; }
.wizard-steps span { flex:1; text-align:center; padding:8px 4px; border-radius:6px; font-size:12px; font-weight:700; background:#f3f4f6; color:#9ca3af; }
.wizard-steps span.step-active { background:#4f46e5; color:#fff; }
.wizard-steps span.step-done { background:#d1fae5; color:#065f46; }
</style>
</head>
<body class="auth-body">
<div class="auth-card auth-card-wide">
    <h1><?= e(APP_NAME) ?> — Setup Wizard</h1>

    <div class="wizard-steps">
        <span class="<?= $step === 1 ? 'step-active' : ($step > 1 ? 'step-done' : '') ?>">1. Database</span>
        <span class="<?= $step === 2 ? 'step-active' : ($step > 2 ? 'step-done' : '') ?>">2. Admin Account</span>
        <span class="<?= $step === 3 ? 'step-active' : '' ?>">3. Done</span>
    </div>

    <h2>System Checks</h2>
    <ul class="checklist">
        <?php foreach ($checks as $c): ?>
            <li class="<?= $c['ok'] ? 'check-ok' : 'check-fail' ?>">
                <?= $c['ok'] ? '✔' : '✘' ?> <?= e($c['label']) ?> — <span class="check-detail"><?= e((string) $c['detail']) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($message): ?>
        <div class="alert alert-<?= e($messageType) ?>"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if (!$allChecksOk): ?>
        <p style="font-size:13px;color:var(--color-text-light);">Fix the failing checks above before continuing.</p>
    <?php elseif ($step === 1): ?>
        <h2>Step 1 — Database Connection</h2>
        <p style="font-size:13px;color:var(--color-text-light);">Enter the credentials for a MySQL/MariaDB server. The database will be created automatically if it doesn't exist yet.</p>
        <form method="post" action="<?= APP_URL ?>/setup.php">
            <?= csrf_field() ?>
            <input type="hidden" name="wizard_step" value="1">
            <label>Database Host
                <input type="text" name="db_host" value="<?= e($_POST['db_host'] ?? 'localhost') ?>" required>
            </label>
            <label>Database Name
                <input type="text" name="db_name" value="<?= e($_POST['db_name'] ?? 'php_page_builder') ?>" required>
            </label>
            <label>Database Username
                <input type="text" name="db_user" value="<?= e($_POST['db_user'] ?? 'root') ?>" required>
            </label>
            <label>Database Password
                <input type="password" name="db_pass" autocomplete="new-password">
            </label>
            <button type="submit" class="btn btn-primary btn-block">Next: Create Database →</button>
        </form>
    <?php elseif ($step === 2): ?>
        <h2>Step 2 — Admin Account</h2>
        <p style="font-size:13px;color:var(--color-text-light);">Database connected and tables are ready. Create the first admin account to finish setup.</p>
        <form method="post" action="<?= APP_URL ?>/setup.php">
            <?= csrf_field() ?>
            <input type="hidden" name="wizard_step" value="2">
            <label>Admin Username
                <input type="text" name="admin_username" autocomplete="off" required>
            </label>
            <label>Admin Password
                <input type="password" name="admin_password" autocomplete="new-password" required minlength="8">
            </label>
            <label>Confirm Password
                <input type="password" name="admin_password_confirm" autocomplete="new-password" required minlength="8">
            </label>
            <button type="submit" class="btn btn-primary btn-block">Finish Setup</button>
        </form>
    <?php else: ?>
        <h2>Setup Complete</h2>
        <p style="font-size:13px;color:var(--color-text-light);">The database is connected, tables are ready, and at least one admin account exists.</p>
        <p class="auth-hint"><a href="<?= APP_URL ?>/login.php">Go to login →</a></p>

        <h2>Add Another Admin (optional)</h2>
        <form method="post" action="<?= APP_URL ?>/setup.php">
            <?= csrf_field() ?>
            <input type="hidden" name="wizard_step" value="2">
            <label>Admin Username
                <input type="text" name="admin_username" autocomplete="off">
            </label>
            <label>Admin Password
                <input type="password" name="admin_password" autocomplete="new-password" minlength="8">
            </label>
            <label>Confirm Password
                <input type="password" name="admin_password_confirm" autocomplete="new-password" minlength="8">
            </label>
            <button type="submit" class="btn btn-secondary btn-block">Create Additional Admin</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
