<?php
/**
 * TrueVault VPN - Master Configuration
 * 
 * PURPOSE: Central configuration file for all settings
 * IMPORTANT: All sensitive data stored in database, not hardcoded!
 * DATABASE: Uses SQLite3 class (NOT PDO!)
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

// Set environment (development/production)
define('ENVIRONMENT', 'development'); // TODO: Change to 'production' before launch

// Set error reporting based on environment
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

// Base path (absolute server path)
define('BASE_PATH', dirname(__DIR__) . '/');

// Database path (all SQLite databases stored here)
define('DB_PATH', BASE_PATH . 'databases/');

// Logs path
define('LOGS_PATH', BASE_PATH . 'logs/');

// Temp path for temporary files
define('TEMP_PATH', BASE_PATH . 'temp/');

// ============================================
// URL SETTINGS
// ============================================

// Base URL (with trailing slash)
define('BASE_URL', 'https://vpn.the-truth-publishing.com/');

// API URL
define('API_URL', BASE_URL . 'api/');

// Assets URL
define('ASSETS_URL', BASE_URL . 'assets/');

// ============================================
// DATABASE FILES (SQLite3)
// ============================================

// Database file names (all in /databases/ folder)
define('DB_USERS', DB_PATH . 'users.db');
define('DB_DEVICES', DB_PATH . 'devices.db');
define('DB_SERVERS', DB_PATH . 'servers.db');
define('DB_BILLING', DB_PATH . 'billing.db');
define('DB_PORT_FORWARDS', DB_PATH . 'port_forwards.db');
define('DB_PARENTAL_CONTROLS', DB_PATH . 'parental_controls.db');
define('DB_ADMIN', DB_PATH . 'admin.db');
define('DB_LOGS', DB_PATH . 'logs.db');
define('DB_THEMES', DB_PATH . 'themes.db');

// ============================================
// SESSION SETTINGS
// ============================================

// Session name
define('SESSION_NAME', 'truevault_session');

// Session timeout (7 days in seconds)
define('SESSION_LIFETIME', 604800);

// ============================================
// SECURITY SETTINGS
// ============================================

// JWT Secret Key (random string for token signing)
define('JWT_SECRET', 'd0c14e79c13c47d09c49ba0f2057cb0af9e8975527ff4761');

// JWT Expiration (7 days in seconds)
define('JWT_EXPIRATION', 604800);

// Password hash cost (bcrypt cost factor - higher = more secure but slower)
define('PASSWORD_COST', 12);

// Max login attempts before lockout
define('MAX_LOGIN_ATTEMPTS', 5);

// Lockout duration (15 minutes in seconds)
define('LOCKOUT_DURATION', 900);

// ============================================
// RATE LIMITING
// ============================================

// Rate limits by user tier (requests per minute)
define('RATE_LIMIT_STANDARD', 30);
define('RATE_LIMIT_PRO', 60);
define('RATE_LIMIT_VIP', 120);
define('RATE_LIMIT_ADMIN', 999);

// ============================================
// FILE UPLOAD SETTINGS
// ============================================

// Max file upload size (10 MB in bytes)
define('MAX_UPLOAD_SIZE', 10485760);

// Allowed file extensions for uploads
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip']);

// ============================================
// EMAIL SETTINGS
// ============================================

// From email address
define('EMAIL_FROM', 'noreply@vpn.the-truth-publishing.com');

// From name
define('EMAIL_FROM_NAME', 'TrueVault VPN');

// Support email
define('EMAIL_SUPPORT', 'support@vpn.the-truth-publishing.com');

// ============================================
// WIREGUARD SETTINGS
// ============================================

// WireGuard port
define('WIREGUARD_PORT', 51820);

// WireGuard network (subnet for clients)
define('WIREGUARD_NETWORK', '10.8.0.0/24');

// ============================================
// APPLICATION SETTINGS
// ============================================

// Application name
define('APP_NAME', 'TrueVault VPN');

// Application version
define('APP_VERSION', '1.0.0');

// Timezone
define('TIMEZONE', 'America/Chicago');
date_default_timezone_set(TIMEZONE);

// ============================================
// DEBUG SETTINGS
// ============================================

// Enable debug mode (only in development)
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Debug log file
define('DEBUG_LOG', LOGS_PATH . 'debug.log');

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Get database connection (SQLite3 - NOT PDO!)
 * 
 * @param string $dbFile Full path to database file
 * @return SQLite3 Database connection
 */
function getDatabase($dbFile) {
    try {
        // Check if database file exists
        if (!file_exists($dbFile)) {
            throw new Exception("Database file not found: " . basename($dbFile));
        }
        
        // Create SQLite3 connection (NOT PDO!)
        $db = new SQLite3($dbFile);
        
        // Enable exceptions
        $db->enableExceptions(true);
        
        // Set busy timeout
        $db->busyTimeout(5000);
        
        // Enable foreign keys
        $db->exec('PRAGMA foreign_keys = ON');
        
        return $db;
        
    } catch (Exception $e) {
        // Log error
        error_log("Database connection error: " . $e->getMessage());
        
        // Show user-friendly error in development
        if (DEBUG_MODE) {
            die("Database Error: " . $e->getMessage());
        } else {
            die("Database connection failed. Please contact support.");
        }
    }
}

/**
 * Log error to file
 * 
 * @param string $message Error message
 * @param array $context Additional context
 */
function logError($message, $context = []) {
    $logFile = LOGS_PATH . 'error.log';
    
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'file' => $context['file'] ?? 'unknown',
        'line' => $context['line'] ?? 0,
        'user_id' => $_SESSION['user_id'] ?? 'guest',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ];
    
    file_put_contents($logFile, json_encode($entry) . "\n", FILE_APPEND);
}

/**
 * Send JSON response
 * 
 * @param array $data Response data
 * @param int $status HTTP status code
 */
function sendJSON($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ============================================
// AUTOLOADER
// ============================================

/**
 * Autoload classes from /includes/ folder
 */
spl_autoload_register(function($class) {
    $file = BASE_PATH . 'includes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// ============================================
// INITIALIZATION
// ============================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
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

// Create necessary directories if they don't exist
$dirs = [LOGS_PATH, TEMP_PATH];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Mark config as loaded
define('CONFIG_LOADED', true);

?>
