<?php
/**
 * TrueVault VPN - Master Configuration
 * 
 * PURPOSE: Central configuration file for all settings
 * IMPORTANT: Uses SQLite3 class (not PDO)
 * 
 * @created January 2026
 * @version 1.0.1
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
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

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
// DATABASE FILES
// ============================================

define('DB_MAIN', DB_PATH . 'main.db');
define('DB_USERS', DB_PATH . 'users.db');
define('DB_DEVICES', DB_PATH . 'devices.db');
define('DB_SERVERS', DB_PATH . 'servers.db');
define('DB_BILLING', DB_PATH . 'billing.db');
define('DB_LOGS', DB_PATH . 'logs.db');
define('DB_SUPPORT', DB_PATH . 'support.db');

// ============================================
// SESSION SETTINGS
// ============================================

define('SESSION_NAME', 'truevault_session');
define('SESSION_LIFETIME', 604800); // 7 days

// ============================================
// SECURITY SETTINGS
// ============================================

define('JWT_SECRET', 'TrueVault2026JWTSecretKey!@#$');
define('JWT_EXPIRATION', 604800);
define('PASSWORD_COST', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900);
define('PEER_API_SECRET', 'TrueVault2026SecretKey');

// ============================================
// RATE LIMITING
// ============================================

define('RATE_LIMIT_STANDARD', 30);
define('RATE_LIMIT_PRO', 60);
define('RATE_LIMIT_VIP', 120);
define('RATE_LIMIT_ADMIN', 999);

// ============================================
// EMAIL SETTINGS
// ============================================

define('EMAIL_FROM', 'noreply@vpn.the-truth-publishing.com');
define('EMAIL_FROM_NAME', 'TrueVault VPN');
define('EMAIL_SUPPORT', 'paulhalonen@gmail.com');

// ============================================
// PAYPAL SETTINGS
// ============================================

define('PAYPAL_MODE', 'live');
define('PAYPAL_CLIENT_ID', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk');
define('PAYPAL_SECRET', 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN');
define('PAYPAL_WEBHOOK_ID', '46924926WL757580D');

// ============================================
// WIREGUARD SETTINGS
// ============================================

define('WIREGUARD_PORT', 51820);
define('WIREGUARD_NETWORK', '10.8.0.0/24');

// ============================================
// APPLICATION SETTINGS
// ============================================

define('APP_NAME', 'TrueVault VPN');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'America/Chicago');
date_default_timezone_set(TIMEZONE);

// ============================================
// DEBUG SETTINGS
// ============================================

define('DEBUG_MODE', ENVIRONMENT === 'development');
define('DEBUG_LOG', LOGS_PATH . 'debug.log');

// ============================================
// HELPER FUNCTIONS (SQLite3 - NOT PDO)
// ============================================

/**
 * Get database connection using SQLite3 class
 * 
 * @param string $dbFile Full path to database file
 * @return SQLite3 Database connection
 */
function getDatabase($dbFile) {
    try {
        if (!file_exists($dbFile)) {
            throw new Exception("Database file not found: " . basename($dbFile));
        }
        
        $db = new SQLite3($dbFile);
        $db->enableExceptions(true);
        $db->exec('PRAGMA foreign_keys = ON');
        
        return $db;
        
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        
        if (DEBUG_MODE) {
            die("Database Error: " . $e->getMessage());
        } else {
            die("Database connection failed. Please contact support.");
        }
    }
}

/**
 * Create a new database file with SQLite3
 * 
 * @param string $dbFile Full path to database file
 * @return SQLite3 Database connection
 */
function createDatabase($dbFile) {
    try {
        $db = new SQLite3($dbFile);
        $db->enableExceptions(true);
        $db->exec('PRAGMA foreign_keys = ON');
        return $db;
    } catch (Exception $e) {
        error_log("Database creation error: " . $e->getMessage());
        return null;
    }
}

/**
 * Log error to file
 */
function logError($message, $context = []) {
    $logFile = LOGS_PATH . 'error.log';
    
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ];
    
    file_put_contents($logFile, json_encode($entry) . "\n", FILE_APPEND);
}

/**
 * Send JSON response
 */
function sendJSON($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    echo json_encode($data);
    exit;
}

/**
 * Check if email is VIP
 */
function isVIPEmail($email) {
    $vipEmails = [
        'paulhalonen@gmail.com',
        'seige235@yahoo.com'
    ];
    return in_array(strtolower($email), array_map('strtolower', $vipEmails));
}

/**
 * Get VIP server assignment
 */
function getVIPServerAssignment($email) {
    $assignments = [
        'seige235@yahoo.com' => 2
    ];
    return $assignments[strtolower($email)] ?? null;
}

/**
 * Escape string for SQLite3 (helper function)
 */
function dbEscape($db, $string) {
    return $db->escapeString($string);
}

// ============================================
// INITIALIZATION
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    // Set session save path to our temp directory (GoDaddy fix)
    $sessionPath = TEMP_PATH . 'sessions';
    if (!file_exists($sessionPath)) {
        mkdir($sessionPath, 0755, true);
    }
    session_save_path($sessionPath);
    
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '.the-truth-publishing.com',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

$dirs = [LOGS_PATH, TEMP_PATH];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

date_default_timezone_set(TIMEZONE);
define('CONFIG_LOADED', true);
?>
