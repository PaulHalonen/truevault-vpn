<?php
/**
 * TrueVault VPN - Reset Password
 * POST /api/auth/reset-password.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/encryption.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow POST
Response::requireMethod('POST');

// Get input
$input = Response::getJsonInput();

// Validate input
$validator = Validator::make($input, [
    'token' => 'required',
    'password' => 'required|min:8',
    'password_confirmation' => 'required'
]);

if ($validator->fails()) {
    Response::validationError($validator->errors());
}

if ($input['password'] !== $input['password_confirmation']) {
    Response::validationError(['password_confirmation' => ['Passwords do not match']]);
}

$token = $input['token'];
$password = $input['password'];

try {
    $db = DatabaseManager::getInstance()->users();
    
    // Find user with this token
    $stmt = $db->prepare("
        SELECT * FROM users 
        WHERE password_reset_token = ? 
        AND password_reset_expires > datetime('now')
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        Response::error('Invalid or expired reset token', 400);
    }
    
    // Hash new password
    $passwordHash = Encryption::hashPassword($password);
    
    // Update password and clear reset token
    $stmt = $db->prepare("
        UPDATE users 
        SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL, updated_at = datetime('now')
        WHERE id = ?
    ");
    $stmt->execute([$passwordHash, $user['id']]);
    
    // Invalidate all existing sessions
    $sessionsDb = DatabaseManager::getInstance()->sessions();
    $stmt = $sessionsDb->prepare("UPDATE sessions SET is_valid = 0 WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    
    // Revoke all refresh tokens
    $stmt = $sessionsDb->prepare("UPDATE refresh_tokens SET is_revoked = 1 WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    
    Logger::info('Password reset completed', ['user_id' => $user['id'], 'email' => $user['email']]);
    
    Response::success(null, 'Password reset successful. Please login with your new password.');
    
} catch (Exception $e) {
    Logger::error('Password reset failed: ' . $e->getMessage());
    Response::serverError('Password reset failed. Please try again.');
}
