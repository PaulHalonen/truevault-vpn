<?php
/**
 * JWT (JSON Web Token) Helper Class
 * 
 * PURPOSE: Generate and verify JWT tokens for authentication
 * Uses HMAC-SHA256 for signing
 * 
 * USAGE:
 *   $token = JWT::generate(['user_id' => 123]);
 *   $payload = JWT::verify($token);
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Security check
if (!defined('TRUEVAULT_INIT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

class JWT {
    
    /**
     * Generate JWT token
     * 
     * @param array $payload Data to encode in token
     * @param int $expiry Expiry time in seconds (default: 30 days)
     * @return string JWT token
     */
    public static function generate($payload, $expiry = 2592000) {
        // Header
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        // Add standard claims
        $payload['iat'] = time();                    // Issued at
        $payload['exp'] = time() + $expiry;          // Expiry
        $payload['iss'] = 'truevault-vpn';           // Issuer
        
        // Encode header and payload
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        // Create signature
        $signature = hash_hmac(
            'sha256',
            "{$headerEncoded}.{$payloadEncoded}",
            JWT_SECRET,
            true
        );
        $signatureEncoded = self::base64UrlEncode($signature);
        
        // Return complete token
        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }
    
    /**
     * Verify JWT token
     * 
     * @param string $token JWT token to verify
     * @return array|false Payload if valid, false if invalid
     */
    public static function verify($token) {
        // Split token
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Verify signature
        $expectedSignature = hash_hmac(
            'sha256',
            "{$headerEncoded}.{$payloadEncoded}",
            JWT_SECRET,
            true
        );
        $expectedSignatureEncoded = self::base64UrlEncode($expectedSignature);
        
        if (!hash_equals($expectedSignatureEncoded, $signatureEncoded)) {
            return false; // Invalid signature
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        if (!$payload) {
            return false; // Invalid payload
        }
        
        // Check expiry
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false; // Token expired
        }
        
        return $payload;
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
    
    /**
     * Extract user ID from token without full verification
     */
    public static function getUserId($token) {
        $payload = self::verify($token);
        return $payload['user_id'] ?? null;
    }
    
    /**
     * Get token from Authorization header
     * 
     * @return string|null Token or null if not found
     */
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Require valid authentication
     * Returns payload if valid, sends 401 response and exits if not
     * 
     * @return array Payload from valid token
     */
    public static function requireAuth() {
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No authentication token provided']);
            exit;
        }
        
        $payload = self::verify($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
            exit;
        }
        
        return $payload;
    }
}
