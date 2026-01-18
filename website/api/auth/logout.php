<?php
/**
 * User Logout API Endpoint
 * 
 * PURPOSE: Invalidate user session and JWT token
 * METHOD: POST
 * ENDPOINT: /api/auth/logout.php
 * REQUIRES: Authorization header with Bearer token
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
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get token from Authorization header
    $token = JWT::getTokenFromHeader();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'No token provided'
        ]);
        exit;
    }
    
    // Decode token (don't need full validation since we're logging out anyway)
    $payload = JWT::decode($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid token'
        ]);
        exit;
    }
    
    // Delete session from database
    if (isset($payload['session_token'])) {
        Database::execute('users',
            "DELETE FROM sessions WHERE session_token = ?",
            [$payload['session_token']]
        );
    }
    
    // Log logout event
    if (isset($payload['user_id'])) {
        Database::execute('logs',
            "INSERT INTO security_events (event_type, severity, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
            [
                'logout',
                'low',
                $payload['user_id'],
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        );
    }
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Logout failed'
    ]);
}
?>
