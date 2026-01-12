<?php
/**
 * TrueVault VPN - Change Password
 * POST /api/users/change-password.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/encryption.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow POST
Response::requireMethod('POST');

// Require authentication
$user = Auth::requireAuth();

// Get input
$input = Response::getJsonInput();

// Validate input
$validator = Validator::make($input, [
    'current_password' => 'required',
    'new_password' => 'required|min:8',
    'new_password_confirmation' => 'required'
]);

if ($validator->fails()) {
    Response::validationError($validator->errors());
}

if ($input['new_password'] !== $input['new_password_confirmation']) {
    Response::validationError(['new_password_confirmation' => ['Passwords do not match']]);
}

try {
    $db = DatabaseManager::getInstance()->users();
    
    // Get user with password hash
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch();
    
    // Verify current password
    if (!Encryption::verifyPassword($input['current_password'], $userData['password_hash'])) {
        Response::error('Current password is incorrect', 401);
    }
    
    // Hash new password
    $newPasswordHash = Encryption::hashPassword($input['new_password']);
    
    // Update password
    $stmt = $db->prepare("UPDATE users SET password_hash = ?, updated_at = datetime('now') WHERE id = ?");
    $stmt->execute([$newPasswordHash, $user['id']]);
    
    // Get current token
    $currentToken = JWTManager::getTokenFromHeader();
    
    // Invalidate all sessions except current
    $sessionsDb = DatabaseManager::getInstance()->sessions();
    $stmt = $sessionsDb->prepare("UPDATE sessions SET is_valid = 0 WHERE user_id = ? AND token != ?");
    $stmt->execute([$user['id'], $currentToken]);
    
    // Revoke all refresh tokens except current session's
    $stmt = $sessionsDb->prepare("
        UPDATE refresh_tokens 
        SET is_revoked = 1 
        WHERE user_id = ? 
        AND token NOT IN (SELECT refresh_token FROM sessions WHERE token = ? AND is_valid = 1)
    ");
    $stmt->execute([$user['id'], $currentToken]);
    
    Logger::info('Password changed', ['user_id' => $user['id']]);
    
    Response::success(null, 'Password changed successfully. Other sessions have been logged out.');
    
} catch (Exception $e) {
    Logger::error('Password change failed: ' . $e->getMessage());
    Response::serverError('Failed to change password');
}
