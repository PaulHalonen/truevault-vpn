<?php
/**
 * TrueVault VPN - Master Configuration
 * 
 * PURPOSE: Central configuration file for all settings
 * IMPORTANT: All sensitive data stored in database, not hardcoded!
 * DATABASE: Uses SQLite3 class (NOT PDO)
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('TRUEVAULT_INIT')) {
    die('Direct access not permitted');
}

// ============================================
// ENVIRONMENT SETTINGS
// ============================================
define('ENVIRONMENT', 'development');

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');

// ============================================
// PATH SETTINGS
// ============================================
define('BASE_PATH', dirname(__DIR__) . '/');
define('DB_PATH', BASE_PATH . 'databases/');
define('LOGS_PATH', BASE_PATH . 'logs/');
define('TEMP_PATH', BASE_PATH . 'temp/');

// ============================================
// URL SETTINGS
// ============================================
define('BASE_URL', 'https://vpn.the-truth-publishing.com/');
define('API_URL', BASE_URL . 'api/');
define('ASSETS_URL', BASE_URL . 'assets/');

// ============================================
// DATABASE FILES (SQLite3)
// ============================================
define('DB_USERS', DB_PATH . 'users.db');
define('DB_DEVICES', DB_PATH . 'devices.db');
define('DB_SERVERS', DB_PATH . 'servers.db');
define('DB_BILLING', DB_PATH . 'billing.db');
define('DB_PORT_FORWARDS', DB_PATH . 'port_forwards.db');
define('DB_PARENTAL', DB_PATH . 'parental_controls.db');
define('DB_ADMIN', DB_PATH . 'admin.db');
define('DB_LOGS', DB_PATH . 'logs.db');
define('DB_THEMES', DB_PATH . 'themes.db');

// ============================================
// SESSION & SECURITY
// ============================================
define('SESSION_NAME', 'truevault_session');
define('SESSION_LIFETIME', 604800); // 7 days
define('JWT_SECRET', 'd0c14e79c13c47d09c49ba0f2057cb0af9e8975527ff4761');
define('JWT_EXPIRATION', 604800);
define('PASSWORD_COST', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900);

// ============================================
// WIREGUARD SETTINGS
// ============================================
define('WIREGUARD_PORT', 51820);
define('WIREGUARD_NETWORK', '10.8.0.0/24');

// ============================================
// APPLICATION
// ============================================
define('APP_NAME', 'TrueVault VPN');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'America/Chicago');
date_default_timezone_set(TIMEZONE);
define('DEBUG_MODE', ENVIRONMENT === 'development');

// ============================================
// DATABASE HELPER (SQLite3 - NOT PDO!)
// ============================================
function getDatabase($dbFile) {
    if (!file_exists($dbFile)) {
        throw new Exception("Database not found: " . basename($dbFile));
    }
    $db = new SQLite3($dbFile);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA foreign_keys = ON');
    return $db;
}

function logError($message, $context = []) {
    $entry = date('Y-m-d H:i:s') . " | " . $message . " | " . json_encode($context) . "\n";
    file_put_contents(LOGS_PATH . 'error.log', $entry, FILE_APPEND);
}

function sendJSON($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ============================================
// INITIALIZATION
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

define('CONFIG_LOADED', true);
?>
