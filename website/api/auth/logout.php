<?php
/**
 * User Logout API Endpoint - SQLITE3 VERSION
 * 
 * PURPOSE: Invalidate user session/token
 * METHOD: POST
 * ENDPOINT: /api/auth/logout.php
 * REQUIRES: Bearer token in Authorization header
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration
require_once __DIR__ . '/../../configs/config.php';

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

try {
    // ============================================
    // STEP 1: GET TOKEN
    // ============================================
    
    $token = JWT::getTokenFromHeader();
    
    if (!$token) {
        // No token provided, but that's okay for logout
        echo json_encode(['success' => true, 'message' => 'Logged out']);
        exit;
    }
    
    $payload = JWT::verify($token);
    
    if (!$payload) {
        // Invalid/expired token, but that's okay for logout
        echo json_encode(['success' => true, 'message' => 'Logged out']);
        exit;
    }
    
    $userId = $payload['user_id'];
    
    // ============================================
    // STEP 2: DELETE USER SESSIONS (SQLite3)
    // ============================================
    
    $usersDb = Database::getInstance('users');
    
    // Delete all sessions for this user (log out everywhere)
    // If you want single-device logout, you'd need to track session tokens
    $stmt = $usersDb->prepare("DELETE FROM sessions WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // ============================================
    // STEP 3: LOG LOGOUT
    // ============================================
    
    $logsDb = Database::getInstance('logs');
    $stmt = $logsDb->prepare("
        INSERT INTO audit_log (user_id, action, entity_type, entity_id, ip_address, created_at)
        VALUES (:user_id, 'logout', 'user', :entity_id, :ip, datetime('now'))
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':entity_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->execute();
    
    // ============================================
    // STEP 4: RETURN SUCCESS
    // ============================================
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
    
} catch (Exception $e) {
    logError('Logout failed: ' . $e->getMessage());
    
    // Still return success for logout - don't want to trap user
    echo json_encode([
        'success' => true,
        'message' => 'Logged out'
    ]);
}
