<?php
/**
 * TrueVault VPN - JWT Manager
 * Handles JSON Web Token creation and validation
 */

// JWT Secret - In production, this should be in environment variables
define('JWT_SECRET', 'TrueVault_VPN_JWT_Secret_Key_2026_!@#$%^&*()');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRY', 60 * 60 * 24 * 7); // 7 days
define('JWT_REFRESH_EXPIRY', 60 * 60 * 24 * 30); // 30 days

class JWTManager {
    
    /**
     * Generate a JWT token
     * 
     * @param int $userId User ID
     * @param string $email User email
     * @param bool $isAdmin Whether user is admin
     * @param array $extra Extra claims to include
     * @return string JWT token
     */
    public static function generateToken($userId, $email, $isAdmin = false, $extra = []) {
        $header = [
            'typ' => 'JWT',
            'alg' => JWT_ALGORITHM
        ];
        
        $payload = array_merge([
            'iss' => 'TrueVault VPN',
            'iat' => time(),
            'exp' => time() + JWT_EXPIRY,
            'sub' => $userId,
            'email' => $email,
            'is_admin' => $isAdmin
        ], $extra);
        
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }
    
    /**
     * Generate a refresh token
     */
    public static function generateRefreshToken($userId) {
        $token = bin2hex(random_bytes(32));
        
        // Store in database
        require_once __DIR__ . '/database.php';
        $db = DatabaseManager::getInstance()->sessions();
        
        $stmt = $db->prepare("
            INSERT INTO refresh_tokens (user_id, token, expires_at)
            VALUES (?, ?, datetime('now', '+30 days'))
        ");
        $stmt->execute([$userId, $token]);
        
        return $token;
    }
    
    /**
     * Validate a JWT token
     * 
     * @param string $token JWT token
     * @return array|false Decoded payload or false if invalid
     */
    public static function validateToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Verify signature
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        if (!$payload) {
            return false;
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Refresh a token using refresh token
     */
    public static function refreshToken($refreshToken) {
        require_once __DIR__ . '/database.php';
        $db = DatabaseManager::getInstance()->sessions();
        
        // Find valid refresh token
        $stmt = $db->prepare("
            SELECT * FROM refresh_tokens 
            WHERE token = ? 
            AND is_revoked = 0 
            AND expires_at > datetime('now')
        ");
        $stmt->execute([$refreshToken]);
        $tokenRecord = $stmt->fetch();
        
        if (!$tokenRecord) {
            return false;
        }
        
        // Get user
        $usersDb = DatabaseManager::getInstance()->users();
        $stmt = $usersDb->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$tokenRecord['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Revoke old refresh token
        $stmt = $db->prepare("UPDATE refresh_tokens SET is_revoked = 1 WHERE id = ?");
        $stmt->execute([$tokenRecord['id']]);
        
        // Generate new tokens
        $newToken = self::generateToken($user['id'], $user['email'], false);
        $newRefreshToken = self::generateRefreshToken($user['id']);
        
        return [
            'token' => $newToken,
            'refresh_token' => $newRefreshToken
        ];
    }
    
    /**
     * Decode a token without validation (for debugging)
     */
    public static function decodeToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        return [
            'header' => json_decode(self::base64UrlDecode($parts[0]), true),
            'payload' => json_decode(self::base64UrlDecode($parts[1]), true)
        ];
    }
    
    /**
     * Get token from Authorization header
     */
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        
        // Check Authorization header
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        // Also check lowercase (some servers)
        if (isset($headers['authorization'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers['authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        // Check query parameter as fallback
        if (isset($_GET['token'])) {
            return $_GET['token'];
        }
        
        return null;
    }
    
    /**
     * Revoke all tokens for a user
     */
    public static function revokeAllTokens($userId) {
        require_once __DIR__ . '/database.php';
        $db = DatabaseManager::getInstance()->sessions();
        
        // Revoke all refresh tokens
        $stmt = $db->prepare("UPDATE refresh_tokens SET is_revoked = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Invalidate all sessions
        $stmt = $db->prepare("UPDATE sessions SET is_valid = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
    
    // Helper methods
    
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
}
