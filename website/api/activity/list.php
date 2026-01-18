<?php
/**
 * TrueVault VPN - Activity Logs List API
 * 
 * PURPOSE: Retrieve user activity logs
 * AUTHENTICATION: JWT required
 * METHOD: GET
 * 
 * QUERY PARAMETERS:
 * - limit: Number of logs to return (default: 50, max: 200)
 * - offset: Pagination offset (default: 0)
 * 
 * RETURNS: Array of activity logs
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
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get query parameters
$limit = min(intval($_GET['limit'] ?? 50), 200);
$offset = intval($_GET['offset'] ?? 0);

// Connect to database
try {
    $db = Database::getInstance();
    $conn = $db->getConnection('users');
    
    // Check if activity_logs table exists
    $tableCheck = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='activity_logs'");
    
    if (!$tableCheck->fetch()) {
        // Table doesn't exist yet, return empty array
        echo json_encode([
            'success' => true,
            'logs' => [],
            'total' => 0,
            'limit' => $limit,
            'offset' => $offset
        ]);
        exit;
    }
    
    // Get total count
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM activity_logs WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get logs
    $stmt = $conn->prepare("
        SELECT 
            id,
            action,
            details,
            ip_address,
            user_agent,
            created_at
        FROM activity_logs
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$userId, $limit, $offset]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve logs: ' . $e->getMessage()
    ]);
}
