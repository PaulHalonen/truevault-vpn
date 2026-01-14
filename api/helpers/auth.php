<?php
/**
 * TrueVault VPN - Authentication Helper
 * JWT token management and user authentication
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/vip.php';

class Auth {
    
    private static $secret = 'TrueVault2026JWTSecretKey!@#$';
    private static $expiry = 604800; // 7 days in seconds
    
    /**
     * Generate JWT token
     */
    public static function generateToken($userId, $email, $isAdmin = false) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload = json_encode([
            'user_id' => $userId,
            'email' => $email,
            'is_admin' => $isAdmin,
            'is_vip' => VIPManager::isVIP($email),
            'iat' => time(),
            'exp' => time() + self::$expiry
        ]);
        
        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", self::$secret, true);
        $base64Signature = self::base64UrlEncode($signature);
        
        return "$base64Header.$base64Payload.$base64Signature";
    }
    
    /**
     * Validate JWT token and return payload
     */
    public static function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // Verify signature
        $signature = self::base64UrlDecode($base64Signature);
        $expectedSignature = hash_hmac('sha256', "$base64Header.$base64Payload", self::$secret, true);
        
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        
        // Check expiration
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
    
    /**
     * Get token from Authorization header
     */
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Require authentication - returns user or sends error
     */
    public static function requireAuth() {
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            Response::unauthorized('No token provided');
        }
        
        $payload = self::validateToken($token);
        
        if (!$payload) {
            Response::unauthorized('Invalid or expired token');
        }
        
        // Get user from database
        $user = Database::queryOne('users',
            "SELECT id, uuid, email, first_name, last_name, status, created_at 
             FROM users WHERE id = ?",
            [$payload['user_id']]
        );
        
        if (!$user) {
            Response::unauthorized('User not found');
        }
        
        if ($user['status'] !== 'active') {
            Response::forbidden('Account is ' . $user['status']);
        }
        
        // Add VIP info
        $user['is_vip'] = VIPManager::isVIP($user['email']);
        $user['vip_type'] = VIPManager::getVIPType($user['email']);
        
        return $user;
    }
    
    /**
     * Require admin authentication
     */
    public static function requireAdmin() {
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            Response::unauthorized('No token provided');
        }
        
        $payload = self::validateToken($token);
        
        if (!$payload) {
            Response::unauthorized('Invalid or expired token');
        }
        
        if (empty($payload['is_admin'])) {
            Response::forbidden('Admin access required');
        }
        
        // Get admin from database
        $admin = Database::queryOne('admin',
            "SELECT * FROM admin_users WHERE id = ?",
            [$payload['user_id']]
        );
        
        if (!$admin || $admin['status'] !== 'active') {
            Response::forbidden('Admin account not active');
        }
        
        return $admin;
    }
    
    /**
     * Optional auth - returns user if valid token, null otherwise
     */
    public static function optionalAuth() {
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            return null;
        }
        
        $payload = self::validateToken($token);
        
        if (!$payload) {
            return null;
        }
        
        return Database::queryOne('users',
            "SELECT * FROM users WHERE id = ?",
            [$payload['user_id']]
        );
    }
    
    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate UUID
     */
    public static function generateUUID() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    /**
     * Generate random token
     */
    public static function generateToken_($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
