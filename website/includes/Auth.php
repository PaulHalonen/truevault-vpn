<?php
/**
 * TrueVault VPN - Authentication Class
 * 
 * Handles JWT tokens, password hashing, VIP detection, and session management.
 * 
 * @created January 2026
 * @version 1.0.0
 */

require_once __DIR__ . '/Database.php';

class Auth {
    
    private static $jwtSecret;
    private static $tokenExpiry = 86400 * 7; // 7 days
    
    /**
     * Initialize with JWT secret
     */
    public static function init($secret = null) {
        self::$jwtSecret = $secret ?? (defined('JWT_SECRET') ? JWT_SECRET : 'default-secret-change-me');
    }
    
    // ==========================================
    // PASSWORD HANDLING
    // ==========================================
    
    /**
     * Hash a password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify a password against a hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // ==========================================
    // JWT TOKEN MANAGEMENT
    // ==========================================
    
    /**
     * Generate a JWT token
     */
    public static function generateToken($userId, $email, $extra = []) {
        if (!self::$jwtSecret) self::init();
        
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload = array_merge([
            'user_id' => $userId,
            'email' => $email,
            'iat' => time(),
            'exp' => time() + self::$tokenExpiry
        ], $extra);
        
        $payload = json_encode($payload);
        
        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", self::$jwtSecret, true);
        $base64Signature = self::base64UrlEncode($signature);
        
        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }
    
    /**
     * Verify and decode a JWT token
     */
    public static function verifyToken($token) {
        if (!self::$jwtSecret) self::init();
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return ['valid' => false, 'error' => 'Invalid token format'];
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // Verify signature
        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", self::$jwtSecret, true);
        $expectedSignature = self::base64UrlEncode($signature);
        
        if (!hash_equals($expectedSignature, $base64Signature)) {
            return ['valid' => false, 'error' => 'Invalid signature'];
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        
        if (!$payload) {
            return ['valid' => false, 'error' => 'Invalid payload'];
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return ['valid' => false, 'error' => 'Token expired'];
        }
        
        return ['valid' => true, 'payload' => $payload];
    }
    
    /**
     * Get user from token (convenience method)
     */
    public static function getUserFromToken($token) {
        $result = self::verifyToken($token);
        if (!$result['valid']) {
            return null;
        }
        return $result['payload'];
    }
    
    /**
     * Require authentication - returns user data or sends 401 and exits
     * Use this at the start of protected API endpoints
     */
    public static function requireAuth() {
        // Get Authorization header
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (empty($authHeader)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Authorization header required']);
            exit;
        }
        
        // Extract Bearer token
        if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid authorization format']);
            exit;
        }
        
        $token = $matches[1];
        
        // Verify token
        $result = self::verifyToken($token);
        
        if (!$result['valid']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Invalid token']);
            exit;
        }
        
        // Get full user data from database
        $userId = $result['payload']['user_id'];
        $user = self::getUserById($userId);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        
        // Check account status
        if ($user['status'] === 'suspended') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Account suspended']);
            exit;
        }
        
        // Return user data with token payload
        return array_merge($user, ['token_payload' => $result['payload']]);
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
    
    // ==========================================
    // VIP DETECTION
    // ==========================================
    
    /**
     * Check if email is in VIP list (SECRET - never expose this!)
     */
    public static function isVIP($email) {
        try {
            $db = Database::getInstance('main');
            $email = strtolower(trim($email));
            $escaped = $db->escape($email);
            
            // Remove quotes added by escape for the WHERE clause
            $escaped = trim($escaped, "'");
            
            return $db->exists('vip_users', "LOWER(email) = LOWER('{$escaped}') AND is_active = 1");
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get VIP user details
     */
    public static function getVIPInfo($email) {
        try {
            $db = Database::getInstance('main');
            $email = strtolower(trim($email));
            $escaped = $db->escape($email);
            $escaped = trim($escaped, "'");
            
            return $db->queryOne("SELECT * FROM vip_users WHERE LOWER(email) = LOWER('{$escaped}') AND is_active = 1");
        } catch (Exception $e) {
            return null;
        }
    }
    
    // ==========================================
    // USER REGISTRATION
    // ==========================================
    
    /**
     * Register a new user
     * Returns: ['success' => bool, 'user_id' => int, 'is_vip' => bool, 'message' => string]
     */
    public static function register($email, $password, $firstName = '', $lastName = '') {
        try {
            $db = Database::getInstance('users');
            $email = strtolower(trim($email));
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email address'];
            }
            
            // Validate password
            if (strlen($password) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters'];
            }
            
            // Check if email already exists
            $escaped = trim($db->escape($email), "'");
            if ($db->exists('users', "LOWER(email) = LOWER('{$escaped}')")) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            // Check VIP status (SECRET!)
            $isVIP = self::isVIP($email);
            $vipInfo = $isVIP ? self::getVIPInfo($email) : null;
            
            // Determine account type and plan
            $accountType = $isVIP ? 'vip' : 'standard';
            $plan = $isVIP ? 'vip' : 'personal';
            $status = $isVIP ? 'active' : 'pending'; // VIP = instant access, others need payment
            
            // VIP device limits:
            // - Regular VIP = 10 devices
            // - VIP with dedicated server = 999 devices
            $maxDevices = 3; // Default for standard users
            if ($isVIP) {
                $maxDevices = 10; // Regular VIP
                if ($vipInfo && !empty($vipInfo['dedicated_server_id'])) {
                    $maxDevices = 999; // VIP with dedicated server
                }
            }
            
            // Set trial period for non-VIP (30 days)
            $trialEnds = $isVIP ? null : date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Hash password
            $passwordHash = self::hashPassword($password);
            
            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Insert user
            $userId = $db->insert('users', [
                'email' => $email,
                'password_hash' => $passwordHash,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'account_type' => $accountType,
                'plan' => $plan,
                'status' => $status,
                'max_devices' => $maxDevices,
                'email_verified' => $isVIP ? 1 : 0, // Auto-verify VIP
                'verification_token' => $isVIP ? null : $verificationToken,
                'trial_ends_at' => $trialEnds,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Log the registration
            self::logActivity($userId, 'user_registered', 'user', $userId, [
                'email' => $email,
                'is_vip' => $isVIP,
                'account_type' => $accountType
            ]);
            
            // Generate JWT token for immediate login
            $token = self::generateToken($userId, $email, [
                'account_type' => $accountType,
                'plan' => $plan,
                'status' => $status
            ]);
            
            return [
                'success' => true,
                'user_id' => $userId,
                'is_vip' => $isVIP,
                'account_type' => $accountType,
                'status' => $status,
                'token' => $token,
                'message' => $isVIP ? 'Welcome! Your VIP access is activated.' : 'Registration successful!'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    // ==========================================
    // USER LOGIN
    // ==========================================
    
    /**
     * Login a user
     */
    public static function login($email, $password, $ipAddress = null, $userAgent = null) {
        try {
            $db = Database::getInstance('users');
            $email = strtolower(trim($email));
            
            // Check brute force protection
            if (self::isLockedOut($email, $ipAddress)) {
                return ['success' => false, 'message' => 'Too many failed attempts. Try again in 15 minutes.'];
            }
            
            // Find user
            $escaped = trim($db->escape($email), "'");
            $user = $db->queryOne("SELECT * FROM users WHERE LOWER(email) = LOWER('{$escaped}')");
            
            if (!$user) {
                self::logFailedLogin($email, $ipAddress);
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Verify password
            if (!self::verifyPassword($password, $user['password_hash'])) {
                self::logFailedLogin($email, $ipAddress);
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Check account status
            if ($user['status'] === 'suspended') {
                return ['success' => false, 'message' => 'Account suspended. Contact support.'];
            }
            
            // Update last login
            $db->update('users', [
                'last_login' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = {$user['id']}");
            
            // Generate JWT token
            $token = self::generateToken($user['id'], $user['email'], [
                'account_type' => $user['account_type'],
                'plan' => $user['plan'],
                'status' => $user['status']
            ]);
            
            // Create session record
            self::createSession($user['id'], $token, $ipAddress, $userAgent);
            
            // Log successful login
            self::logActivity($user['id'], 'user_login', 'user', $user['id'], [
                'ip_address' => $ipAddress
            ]);
            
            // Clear failed attempts
            self::clearFailedAttempts($email, $ipAddress);
            
            return [
                'success' => true,
                'user_id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'account_type' => $user['account_type'],
                'plan' => $user['plan'],
                'status' => $user['status'],
                'max_devices' => $user['max_devices'],
                'token' => $token,
                'message' => 'Login successful'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }
    
    // ==========================================
    // SESSION MANAGEMENT
    // ==========================================
    
    /**
     * Create a session record
     */
    private static function createSession($userId, $token, $ipAddress = null, $userAgent = null) {
        try {
            $db = Database::getInstance('users');
            $db->insert('sessions', [
                'user_id' => $userId,
                'token' => $token,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', time() + self::$tokenExpiry),
                'last_activity' => date('Y-m-d H:i:s'),
                'is_valid' => 1
            ]);
        } catch (Exception $e) {
            // Session logging is not critical
        }
    }
    
    /**
     * Logout - invalidate session
     */
    public static function logout($token) {
        try {
            $db = Database::getInstance('users');
            $escaped = trim($db->escape($token), "'");
            $db->update('sessions', ['is_valid' => 0], "token = '{$escaped}'");
            return ['success' => true, 'message' => 'Logged out successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Logout failed'];
        }
    }
    
    /**
     * Verify session is still valid
     */
    public static function verifySession($token) {
        try {
            // First verify JWT
            $jwtResult = self::verifyToken($token);
            if (!$jwtResult['valid']) {
                return false;
            }
            
            // Then check session table
            $db = Database::getInstance('users');
            $escaped = trim($db->escape($token), "'");
            $session = $db->queryOne("SELECT * FROM sessions WHERE token = '{$escaped}' AND is_valid = 1");
            
            if (!$session) {
                return false;
            }
            
            // Update last activity
            $db->update('sessions', [
                'last_activity' => date('Y-m-d H:i:s')
            ], "id = {$session['id']}");
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // ==========================================
    // BRUTE FORCE PROTECTION
    // ==========================================
    
    /**
     * Check if user/IP is locked out
     */
    private static function isLockedOut($email, $ipAddress) {
        try {
            $db = Database::getInstance('logs');
            $cutoff = date('Y-m-d H:i:s', strtotime('-15 minutes'));
            $emailEsc = trim($db->escape($email), "'");
            $ipEsc = trim($db->escape($ipAddress ?? ''), "'");
            
            // Check failed attempts in last 15 minutes
            $count = $db->queryValue(
                "SELECT COUNT(*) FROM activity_logs 
                 WHERE action = 'failed_login' 
                 AND created_at > '{$cutoff}'
                 AND (details LIKE '%{$emailEsc}%' OR ip_address = '{$ipEsc}')"
            );
            
            return $count >= 5;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Log failed login attempt
     */
    private static function logFailedLogin($email, $ipAddress) {
        self::logActivity(null, 'failed_login', 'auth', null, [
            'email' => $email,
            'ip_address' => $ipAddress
        ], $ipAddress);
    }
    
    /**
     * Clear failed login attempts after successful login
     */
    private static function clearFailedAttempts($email, $ipAddress) {
        // We don't delete logs, just the lockout expires naturally
    }
    
    // ==========================================
    // PASSWORD RESET
    // ==========================================
    
    /**
     * Request password reset
     */
    public static function requestPasswordReset($email) {
        try {
            $db = Database::getInstance('users');
            $email = strtolower(trim($email));
            $escaped = trim($db->escape($email), "'");
            
            $user = $db->queryOne("SELECT id, email FROM users WHERE LOWER(email) = LOWER('{$escaped}')");
            
            if (!$user) {
                // Don't reveal if email exists
                return ['success' => true, 'message' => 'If the email exists, a reset link has been sent.'];
            }
            
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $db->update('users', [
                'reset_token' => $resetToken,
                'reset_token_expires' => $expiresAt,
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = {$user['id']}");
            
            // TODO: Send email with reset link
            // For now, return the token (in production, only send via email)
            
            return [
                'success' => true,
                'message' => 'If the email exists, a reset link has been sent.',
                'debug_token' => $resetToken // Remove in production!
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Password reset request failed'];
        }
    }
    
    /**
     * Reset password with token
     */
    public static function resetPassword($token, $newPassword) {
        try {
            $db = Database::getInstance('users');
            
            if (strlen($newPassword) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters'];
            }
            
            $escaped = trim($db->escape($token), "'");
            $now = date('Y-m-d H:i:s');
            
            $user = $db->queryOne(
                "SELECT id FROM users 
                 WHERE reset_token = '{$escaped}' 
                 AND reset_token_expires > '{$now}'"
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid or expired reset token'];
            }
            
            // Update password and clear token
            $passwordHash = self::hashPassword($newPassword);
            
            $db->update('users', [
                'password_hash' => $passwordHash,
                'reset_token' => null,
                'reset_token_expires' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = {$user['id']}");
            
            // Invalidate all existing sessions
            $db->update('sessions', ['is_valid' => 0], "user_id = {$user['id']}");
            
            return ['success' => true, 'message' => 'Password reset successful. Please login with your new password.'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Password reset failed'];
        }
    }
    
    // ==========================================
    // ACTIVITY LOGGING
    // ==========================================
    
    /**
     * Log user activity
     */
    public static function logActivity($userId, $action, $entityType = null, $entityId = null, $details = [], $ipAddress = null) {
        try {
            $db = Database::getInstance('logs');
            $db->insert('activity_logs', [
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => json_encode($details),
                'ip_address' => $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? null),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Logging failure shouldn't break the app
        }
    }
    
    // ==========================================
    // USER INFO
    // ==========================================
    
    /**
     * Get user by ID
     */
    public static function getUserById($userId) {
        try {
            $db = Database::getInstance('users');
            return $db->queryOne("SELECT * FROM users WHERE id = " . (int)$userId);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get user by email
     */
    public static function getUserByEmail($email) {
        try {
            $db = Database::getInstance('users');
            $email = strtolower(trim($email));
            $escaped = trim($db->escape($email), "'");
            return $db->queryOne("SELECT * FROM users WHERE LOWER(email) = LOWER('{$escaped}')");
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Update user profile
     */
    public static function updateProfile($userId, $data) {
        try {
            $db = Database::getInstance('users');
            
            $allowedFields = ['first_name', 'last_name'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $db->update('users', $updateData, "id = " . (int)$userId);
            
            return ['success' => true, 'message' => 'Profile updated successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }
}
