<?php
/**
 * TrueVault VPN - Encryption Helper
 * Handles password hashing, encryption, and token generation
 */

class Encryption {
    // Encryption key - In production, this should be in environment variables
    private static $encryptionKey = 'TrueVault_Encryption_Key_2026_!@#$';
    private static $cipher = 'AES-256-CBC';
    
    /**
     * Encrypt data
     */
    public static function encrypt($data, $key = null) {
        $key = $key ?? self::$encryptionKey;
        $key = hash('sha256', $key, true);
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$cipher));
        $encrypted = openssl_encrypt($data, self::$cipher, $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt data
     */
    public static function decrypt($data, $key = null) {
        $key = $key ?? self::$encryptionKey;
        $key = hash('sha256', $key, true);
        
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length(self::$cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        return openssl_decrypt($encrypted, self::$cipher, $key, 0, $iv);
    }
    
    /**
     * Hash a password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }
    
    /**
     * Verify a password against hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate a UUID v4
     */
    public static function generateUUID() {
        $data = random_bytes(16);
        
        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    /**
     * Generate a secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate an API key
     */
    public static function generateApiKey() {
        return 'tv_' . self::generateToken(24);
    }
    
    /**
     * Generate a short code (for verification, etc.)
     */
    public static function generateShortCode($length = 6) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $code;
    }
    
    /**
     * Hash data with SHA256
     */
    public static function sha256($data) {
        return hash('sha256', $data);
    }
    
    /**
     * Create HMAC signature
     */
    public static function hmac($data, $key = null) {
        $key = $key ?? self::$encryptionKey;
        return hash_hmac('sha256', $data, $key);
    }
    
    /**
     * Verify HMAC signature
     */
    public static function verifyHmac($data, $signature, $key = null) {
        $expected = self::hmac($data, $key);
        return hash_equals($expected, $signature);
    }
    
    /**
     * Generate a secure scanner token (includes user ID)
     */
    public static function generateScannerToken($userId) {
        $timestamp = time();
        $random = self::generateToken(16);
        $data = "$userId:$timestamp:$random";
        $signature = self::hmac($data);
        return base64_encode("$data:$signature");
    }
    
    /**
     * Validate and decode scanner token
     */
    public static function validateScannerToken($token) {
        try {
            $decoded = base64_decode($token);
            $parts = explode(':', $decoded);
            
            if (count($parts) !== 4) {
                return false;
            }
            
            list($userId, $timestamp, $random, $signature) = $parts;
            
            // Verify signature
            $data = "$userId:$timestamp:$random";
            if (!self::verifyHmac($data, $signature)) {
                return false;
            }
            
            // Check expiration (30 days)
            if (time() - $timestamp > 60 * 60 * 24 * 30) {
                return false;
            }
            
            return [
                'user_id' => (int) $userId,
                'timestamp' => (int) $timestamp
            ];
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Generate WireGuard private key (for testing only - real keys generated on server)
     */
    public static function generateWireGuardPrivateKey() {
        // This is a placeholder - real keys are generated on the VPN servers
        return base64_encode(random_bytes(32));
    }
    
    /**
     * Generate WireGuard public key from private key (placeholder)
     */
    public static function generateWireGuardPublicKey($privateKey) {
        // This is a placeholder - real keys are generated on the VPN servers
        // In reality, this would use curve25519
        return base64_encode(hash('sha256', base64_decode($privateKey), true));
    }
}
