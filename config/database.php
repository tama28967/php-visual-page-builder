<?php
/**
 * PDO database connection (singleton).
 */

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            // These constants come from config/installed.php, written by
            // the setup.php wizard. Falling back to XAMPP's defaults only
            // matters if this is ever called before installation, which
            // require_installed() is meant to prevent.
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $dbname = defined('DB_NAME') ? DB_NAME : 'php_page_builder';
            $user = defined('DB_USER') ? DB_USER : 'root';
            $pass = defined('DB_PASS') ? DB_PASS : '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // No try/catch here on purpose: callers must go through
            // require_installed()/require_installed_api() first, which
            // probes the connection and can tell a missing database (reset
            // the wizard) apart from a merely offline server (show an
            // error, keep the config). Letting the PDOException propagate
            // preserves errorInfo for that classification.
            self::$instance = new PDO($dsn, $user, $pass, $options);
        }

        return self::$instance;
    }
}
