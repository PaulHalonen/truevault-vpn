<?php
// Admin Panel Configuration
// USES: SQLite3 class (NOT PDO)
session_start();

define('ADMIN_DB_PATH', __DIR__ . '/../databases/main.db');

function getAdminDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new SQLite3(ADMIN_DB_PATH);
            $db->enableExceptions(true);
            $db->busyTimeout(5000);
        } catch (Exception $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $db;
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_token']);
}

// Require admin login
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

// Get current admin user
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    $db = getAdminDB();
    $stmt = $db->prepare("SELECT id, email, name, role FROM admin_users WHERE id = :id AND is_active = 1");
    $stmt->bindValue(':id', $_SESSION['admin_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

// Log activity
function logActivity($action, $entityType = null, $entityId = null, $details = null) {
    if (!isAdminLoggedIn()) {
        return;
    }
    
    $db = getAdminDB();
    $stmt = $db->prepare("
        INSERT INTO activity_log (admin_id, action, entity_type, entity_id, details, ip_address)
        VALUES (:admin_id, :action, :entity_type, :entity_id, :details, :ip)
    ");
    
    $detailsJson = $details ? json_encode($details) : null;
    
    $stmt->bindValue(':admin_id', $_SESSION['admin_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':action', $action, SQLITE3_TEXT);
    $stmt->bindValue(':entity_type', $entityType, SQLITE3_TEXT);
    $stmt->bindValue(':entity_id', $entityId, SQLITE3_INTEGER);
    $stmt->bindValue(':details', $detailsJson, SQLITE3_TEXT);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? null, SQLITE3_TEXT);
    $stmt->execute();
}

// Get system setting
function getSetting($key, $default = null) {
    $db = getAdminDB();
    $stmt = $db->prepare("SELECT setting_value, setting_type FROM system_settings WHERE setting_key = :key");
    $stmt->bindValue(':key', $key, SQLITE3_TEXT);
    $result = $stmt->execute();
    $setting = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$setting) {
        return $default;
    }
    
    $value = $setting['setting_value'];
    
    // Type casting
    switch ($setting['setting_type']) {
        case 'number':
            return is_numeric($value) ? (float)$value : $default;
        case 'boolean':
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        case 'json':
            return json_decode($value, true);
        default:
            return $value;
    }
}

// Update system setting
function updateSetting($key, $value) {
    $db = getAdminDB();
    $stmt = $db->prepare("
        UPDATE system_settings 
        SET setting_value = :value, updated_at = CURRENT_TIMESTAMP, updated_by = :admin_id
        WHERE setting_key = :key
    ");
    $stmt->bindValue(':value', $value, SQLITE3_TEXT);
    $stmt->bindValue(':admin_id', $_SESSION['admin_id'] ?? null, SQLITE3_INTEGER);
    $stmt->bindValue(':key', $key, SQLITE3_TEXT);
    $stmt->execute();
    
    logActivity('setting_updated', 'setting', null, ['key' => $key, 'value' => $value]);
}

// Get dashboard statistics
function getDashboardStats() {
    $db = getAdminDB();
    
    $stats = [];
    
    // Total users
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['total_users'] = $row['count'];
    
    // Active users (with active subscriptions)
    $result = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['active_users'] = $row['count'];
    
    // Total devices
    $result = $db->query("SELECT COUNT(*) as count FROM devices");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['total_devices'] = $row['count'];
    
    // Active devices (connected in last 7 days)
    $result = $db->query("SELECT COUNT(*) as count FROM devices WHERE last_connected >= datetime('now', '-7 days')");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['active_devices'] = $row['count'];
    
    // Total revenue (all time)
    $result = $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['total_revenue'] = $row['total'] ?? 0;
    
    // Revenue this month
    $result = $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed' AND date >= date('now', 'start of month')");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['monthly_revenue'] = $row['total'] ?? 0;
    
    // Open support tickets
    $result = $db->query("SELECT COUNT(*) as count FROM support_tickets WHERE status != 'closed'");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['open_tickets'] = $row['count'] ?? 0;
    
    // New users this month
    $result = $db->query("SELECT COUNT(*) as count FROM users WHERE created_at >= date('now', 'start of month')");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['new_users_month'] = $row['count'];
    
    return $stats;
}

// Format currency
function formatCurrency($amount, $currency = 'USD') {
    $symbol = $currency === 'CAD' ? 'CA$' : '$';
    return $symbol . number_format($amount, 2);
}

// Format date
function formatDate($datetime, $format = 'M j, Y') {
    if (!$datetime) return '-';
    return date($format, strtotime($datetime));
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
