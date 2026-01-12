<?php
/**
 * TrueVault VPN - User Logout
 * POST /api/auth/logout.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow POST
Response::requireMethod('POST');

try {
    // Get token from header
    $token = JWTManager::getTokenFromHeader();
    
    if ($token) {
        // Validate token to get user info for logging
        $payload = JWTManager::validateToken($token);
        
        if ($payload) {
            // Invalidate session
            Auth::invalidateSession($token);
            
            // Log logout
            Logger::auth('logout', $payload['email'] ?? 'unknown', true);
        }
    }
    
    Response::success(null, 'Logged out successfully');
    
} catch (Exception $e) {
    // Even if there's an error, we return success
    // (user wanted to logout anyway)
    Response::success(null, 'Logged out');
}
