<?php
declare(strict_types=1);

// Connection details come from the environment so no credentials live in the
// repository. Copy .env.example to .env (or export these) before running.
define('DB_HOST', (string) (getenv('PAWORA_DB_HOST') ?: '127.0.0.1'));
define('DB_PORT', (string) (getenv('PAWORA_DB_PORT') ?: '3306'));
define('DB_NAME', (string) (getenv('PAWORA_DB_NAME') ?: 'pawora_db'));
define('DB_USER', (string) (getenv('PAWORA_DB_USER') ?: 'root'));
define('DB_PASS', (string) (getenv('PAWORA_DB_PASS') ?: ''));
const APP_NAME = 'PAWORA';
const APP_TAGLINE = 'Everything They Need. A Home They Deserve.';
define('BASE_URL', rtrim((string) (getenv('PAWORA_BASE_URL') ?: '/pawora'), '/'));
define('MAX_UPLOAD_SIZE', 4194304);

date_default_timezone_set('Asia/Colombo');

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('pawora_session');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    return $pdo;
}

function app_url(string $path = ''): string
{
    if ($path === '') {
        return BASE_URL === '' ? '/' : BASE_URL . '/';
    }
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset_url(string $path): string
{
    return app_url('assets/' . ltrim($path, '/'));
}
