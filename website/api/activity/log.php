<?php
/**
 * TrueVault VPN - Activity Logging API
 * 
 * PURPOSE: Log user activities for audit trail
 * AUTHENTICATION: JWT required
 * 
 * LOGS:
 * - Device creation
 * - Device deletion
 * - Server switches
 * - Password changes
 * - Login attempts
 * - Port forwarding changes
 * - Settings changes
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load required files
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/JWT.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check authentication
try {
    $user = Auth::require();
    $userId = $user['user_id'];
    $userEmail = $user['email'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$action = $input['action'] ?? '';
$details = $input['details'] ?? '';
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

if (empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Action is required']);
    exit;
}

// Connect to database
try {
    $db = Database::getInstance();
    $conn = $db->getConnection('users');
    
    // Check if activity_logs table exists, create if not
    $tableCheck = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='activity_logs'");
    
    if (!$tableCheck->fetch()) {
        // Create activity_logs table
        $conn->exec("
            CREATE TABLE activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                action TEXT NOT NULL,
                details TEXT,
                ip_address TEXT,
                user_agent TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $conn->exec("CREATE INDEX idx_activity_user_id ON activity_logs(user_id)");
        $conn->exec("CREATE INDEX idx_activity_created ON activity_logs(created_at)");
    }
    
    // Insert activity log
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $action,
        $details,
        $ipAddress,
        $userAgent
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Activity logged',
        'log_id' => $conn->lastInsertId()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to log activity: ' . $e->getMessage()
    ]);
}
