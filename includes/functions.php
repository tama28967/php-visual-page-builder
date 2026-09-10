<?php
/**
 * Shared helper functions.
 */

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Turn arbitrary input into a safe URL slug like "about" or "/" for home.
 */
function slugify(string $input): string
{
    $input = trim($input);
    if ($input === '' || $input === '/') {
        return '/';
    }
    $slug = strtolower($input);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    $slug = preg_replace('/-{2,}/', '-', $slug);
    if ($slug === '') {
        $slug = 'page-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }
    return '/' . $slug;
}

/**
 * Convert a stored slug ("/about", "/") into a safe filename ("about.html", "index.html").
 */
function slug_to_filename(string $slug, string $ext = 'html'): string
{
    $slug = trim($slug, '/');
    if ($slug === '') {
        return 'index.' . $ext;
    }
    // Defense in depth: strip anything that isn't a safe filename character.
    $safe = preg_replace('/[^a-zA-Z0-9_-]/', '-', $slug);
    return $safe . '.' . $ext;
}

function json_response(bool $success, string $message, array $extra = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * GrapesJS's wrapper component defaults to a <body> tag, so getHtml()
 * includes an outer <body>...</body>. Strip it so stored HTML is a plain
 * fragment we can safely embed in our own document wrapper.
 */
function strip_outer_body_tag(string $html): string
{
    $html = trim($html);
    if (preg_match('/^<body[^>]*>([\s\S]*)<\/body>$/i', $html, $m)) {
        return trim($m[1]);
    }
    return $html;
}

function generate_unique_filename(string $extension): string
{
    return bin2hex(random_bytes(16)) . '.' . strtolower($extension);
}

/**
 * Actually probe the database connection (not just "does config/installed.php
 * exist") and classify failures: MySQL error 1049 means the configured
 * database itself is gone (e.g. dropped from phpMyAdmin) — a "not installed"
 * state we can self-heal from. Anything else (server offline, wrong
 * credentials) is a real outage the wizard can't safely paper over by
 * resetting, since the database may still be there once the server is back.
 *
 * Requires config/database.php to already be loaded.
 */
function check_database_connection(): array
{
    try {
        Database::getConnection();
        return ['ok' => true, 'missing_database' => false, 'error' => null];
    } catch (PDOException $e) {
        $driverCode = $e->errorInfo[1] ?? null;
        return [
            'ok' => false,
            'missing_database' => $driverCode === 1049,
            'error' => $e->getMessage(),
        ];
    }
}

/**
 * Send visitors to the setup wizard until config/installed.php exists AND
 * the database it points to is actually reachable. If the database was
 * deleted out from under an existing install, this resets the installer
 * state automatically so setup.php starts a fresh wizard instead of a
 * dead-end error page.
 */
function require_installed(): void
{
    if (!APP_INSTALLED) {
        redirect('/setup.php');
    }

    $status = check_database_connection();
    if (!$status['ok']) {
        if ($status['missing_database']) {
            error_log('Configured database is missing — resetting installer state: ' . $status['error']);
            @unlink(INSTALL_CONFIG_FILE);
        } else {
            error_log('Database unreachable: ' . $status['error']);
        }
        redirect('/setup.php');
    }
}

/**
 * API equivalent of require_installed() — returns JSON instead of a
 * redirect, since API callers expect JSON responses.
 */
function require_installed_api(): void
{
    if (!APP_INSTALLED) {
        json_response(false, 'Application is not installed yet. Please run setup.php.', [], 503);
    }

    $status = check_database_connection();
    if (!$status['ok']) {
        if ($status['missing_database']) {
            error_log('Configured database is missing — resetting installer state: ' . $status['error']);
            @unlink(INSTALL_CONFIG_FILE);
            json_response(false, 'The configured database no longer exists. Please run setup.php again.', [], 503);
        }
        error_log('Database unreachable: ' . $status['error']);
        json_response(false, 'Database is currently unreachable. Please check that MySQL is running.', [], 503);
    }
}

function redirect(string $path): void
{
    header('Location: ' . APP_URL . $path);
    exit;
}
