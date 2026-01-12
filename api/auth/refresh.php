<?php
/**
 * TrueVault VPN - Token Refresh
 * POST /api/auth/refresh.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow POST
Response::requireMethod('POST');

// Get input
$input = Response::getJsonInput();

// Validate input
$validator = Validator::make($input, [
    'refresh_token' => 'required'
]);

if ($validator->fails()) {
    Response::validationError($validator->errors());
}

$refreshToken = $input['refresh_token'];

try {
    // Refresh token
    $tokens = JWTManager::refreshToken($refreshToken);
    
    if (!$tokens) {
        Response::unauthorized('Invalid or expired refresh token');
    }
    
    // Get user from new token
    $payload = JWTManager::validateToken($tokens['token']);
    $user = Auth::getUserById($payload['sub']);
    
    if (!$user) {
        Response::unauthorized('User not found');
    }
    
    // Create new session
    Auth::createSession($user['id'], $tokens['token'], $tokens['refresh_token']);
    
    // Sanitize user data
    $userData = Auth::sanitizeUser($user);
    
    Response::success([
        'user' => $userData,
        'token' => $tokens['token'],
        'refresh_token' => $tokens['refresh_token'],
        'expires_in' => 60 * 60 * 24 * 7
    ], 'Token refreshed');
    
} catch (Exception $e) {
    Logger::error('Token refresh failed: ' . $e->getMessage());
    Response::serverError('Token refresh failed');
}
