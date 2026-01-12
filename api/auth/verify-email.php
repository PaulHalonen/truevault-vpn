<?php
/**
 * TrueVault VPN - Email Verification
 * GET /api/auth/verify-email.php?token=xxx
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow GET
Response::requireMethod('GET');

$token = $_GET['token'] ?? '';

if (empty($token)) {
    Response::error('Verification token is required', 400);
}

try {
    $db = DatabaseManager::getInstance()->users();
    
    // Find user with this token
    $stmt = $db->prepare("SELECT * FROM users WHERE email_verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        Response::error('Invalid verification token', 400);
    }
    
    if ($user['email_verified']) {
        Response::success(null, 'Email already verified');
    }
    
    // Update user
    $stmt = $db->prepare("
        UPDATE users 
        SET email_verified = 1, email_verification_token = NULL, updated_at = datetime('now')
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);
    
    Logger::info('Email verified', ['user_id' => $user['id'], 'email' => $user['email']]);
    
    // Redirect to dashboard or return success
    if (isset($_GET['redirect'])) {
        header('Location: https://vpn.the-truth-publishing.com/dashboard?verified=1');
        exit;
    }
    
    Response::success(null, 'Email verified successfully');
    
} catch (Exception $e) {
    Logger::error('Email verification failed: ' . $e->getMessage());
    Response::serverError('Verification failed');
}
