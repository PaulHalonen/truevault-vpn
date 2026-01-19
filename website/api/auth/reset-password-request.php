<?php
/**
 * TrueVault VPN - Password Reset Request
 * 
 * PURPOSE: Request a password reset link
 * METHOD: POST
 * 
 * FLOW:
 * 1. User enters email
 * 2. Generate reset token
 * 3. Send reset email
 * 4. User clicks link in email
 * 5. User enters new password
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = strtolower(trim($input['email'] ?? ''));
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Valid email required']);
        exit;
    }
    
    // Check if user exists
    $db = Database::getInstance();
    $usersConn = $db->getConnection('users');
    
    $stmt = $usersConn->prepare("
        SELECT user_id, first_name
        FROM users
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Always return success to prevent email enumeration
    if (!$user) {
        echo json_encode([
            'success' => true,
            'message' => 'If that email exists, a reset link has been sent'
        ]);
        exit;
    }
    
    // Generate reset token
    $resetToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
    
    // Save token
    $stmt = $usersConn->prepare("
        INSERT INTO password_reset_tokens (user_id, token, expires_at)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['user_id'], $resetToken, $expiresAt]);
    
    // Send reset email
    $emailService = new Email();
    $emailService->sendPasswordReset($email, $user['first_name'], $resetToken);
    
    echo json_encode([
        'success' => true,
        'message' => 'If that email exists, a reset link has been sent'
    ]);
    
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to process request'
    ]);
}
