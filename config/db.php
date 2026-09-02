<?php
/**
 * Database configuration + session bootstrap
 * ────────────────────────────────────────────
 * Centralises PDO setup, session settings, and the IS_DEV constant.
 *
 * IS_DEV = true  → verbose errors logged/shown for development
 * IS_DEV = false → generic messages only (set to false before deployment)
 */

// ── Development flag ─────────────────────────────────────────────
define('IS_DEV', true);   // ← Change to false before deploying to production

// ── Session settings ─────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    // Detect HTTPS (for Secure flag without breaking localhost)
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 30 * 24 * 60 * 60, // 30 days
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,          // only send over HTTPS in production
        'httponly' => true,              // JS cannot read the cookie
        'samesite' => 'Strict',          // CSRF mitigation
    ]);
    session_start();
}

// ── Database credentials ──────────────────────────────────────────
$host    = '127.0.0.1';
$db      = 'zyva_db';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Log the real error, show a safe message to the user
    error_log('[Swipe Nest] DB connection failed: ' . $e->getMessage());

    if (IS_DEV) {
        // During development, surface the error so you can fix it
        http_response_code(503);
        die('<pre style="color:red">Database connection failed (DEV mode):<br>' . htmlspecialchars($e->getMessage()) . '</pre>');
    }

    http_response_code(503);
    die('We are experiencing technical difficulties. Please try again later.');
}

// ── Load CSRF helper ─────────────────────────────────────────────
require_once __DIR__ . '/csrf.php';
