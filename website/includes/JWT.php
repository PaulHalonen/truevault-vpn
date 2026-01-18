<?php
/**
 * JWT (JSON Web Token) Class
 * 
 * PURPOSE: Handle JWT token creation, validation, and decoding
 * USAGE: Used for user authentication and API access
 * 
 * EXAMPLES:
 * - $token = JWT::encode(['user_id' => 1, 'email' => 'test@example.com']);
 * - $payload = JWT::decode($token);
 * - $payload = JWT::validate($token); // Throws exception if invalid
 * - $token = JWT::getTokenFromHeader();
 * 
 * @created January 2026
 * @version 1.0.0
 */

class JWT {
    
    /**
     * Encode payload into JWT token
     * 
     * @param array $payload Data to encode
     * @param int $expiration Expiration time in seconds (default: 7 days)
     * @return string JWT token
     */
    public static function encode($payload, $expiration = null) {
        // Use default expiration if not provided
        if ($expiration === null) {
            $expiration = JWT_EXPIRATION;
        }
        
        // Add standard JWT claims
        $payload['iat'] = time(); // Issued At
        $payload['exp'] = time() + $expiration; // Expiration
        
        // Create header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        // Encode header and payload
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        // Create signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        // Combine all parts
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }
    
    /**
     * Decode JWT token (does NOT validate signature)
     * Use validate() for secure decoding with signature verification
     * 
     * @param string $token JWT token
     * @return array|null Payload data or null if invalid format
     */
    public static function decode($token) {
        try {
            // Split token into parts
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return null;
            }
            
            // Decode payload
            $payload = json_decode(self::base64UrlDecode($parts[1]), true);
            
            return $payload;
            
        } catch (Exception $e) {
            error_log("JWT decode error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Validate JWT token (verifies signature and expiration)
     * 
     * @param string $token JWT token
     * @return array Payload data
     * @throws Exception if token is invalid or expired
     */
    public static function validate($token) {
        try {
            // Split token into parts
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                throw new Exception('Invalid token format');
            }
            
            list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
            
            // Verify signature
            $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
            $signatureValid = self::base64UrlEncode($signature);
            
            if ($signatureEncoded !== $signatureValid) {
                throw new Exception('Invalid signature');
            }
            
            // Decode payload
            $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
            
            if (!$payload) {
                throw new Exception('Invalid payload');
            }
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new Exception('Token expired');
            }
            
            return $payload;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Get JWT token from Authorization header
     * 
     * @return string|null Token or null if not found
     */
    public static function getTokenFromHeader() {
        // Check Authorization header
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            // Extract token from "Bearer TOKEN" format
            $authHeader = $headers['Authorization'];
            
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Base64 URL encode (JWT standard)
     * 
     * @param string $data Data to encode
     * @return string Base64 URL encoded string
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode (JWT standard)
     * 
     * @param string $data Data to decode
     * @return string Decoded string
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Refresh token (create new token from existing valid token)
     * 
     * @param string $token Existing valid token
     * @return string New JWT token
     * @throws Exception if token is invalid
     */
    public static function refresh($token) {
        // Validate existing token
        $payload = self::validate($token);
        
        // Remove old claims
        unset($payload['iat']);
        unset($payload['exp']);
        
        // Create new token
        return self::encode($payload);
    }
}
?>
