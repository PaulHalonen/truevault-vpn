<?php
/**
 * TrueVault VPN - Auth Helper (SQLite3 version)
 * Authentication middleware and user retrieval
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/response.php';

class Auth {
    private static $currentUser = null;
    private static $currentAdmin = null;
    
    /**
     * Require authentication - returns user or sends 401
     */
    public static function requireAuth() {
        $token = JWTManager::getTokenFromHeader();
        
        if (!$token) {
            Response::unauthorized('Authentication required');
        }
        
        $payload = JWTManager::validateToken($token);
        
        if (!$payload) {
            Response::unauthorized('Invalid or expired token');
        }
        
        $user = self::getUserById($payload['sub']);
        
        if (!$user) {
            Response::unauthorized('User not found');
        }
        
        if ($user['status'] !== 'active') {
            Response::unauthorized('Account is not active');
        }
        
        self::$currentUser = $user;
        return $user;
    }
    
    /**
     * Require admin authentication
     */
    public static function requireAdmin() {
        $token = JWTManager::getTokenFromHeader();
        
        if (!$token) {
            Response::unauthorized('Admin authentication required');
        }
        
        $payload = JWTManager::validateToken($token);
        
        if (!$payload) {
            Response::unauthorized('Invalid or expired token');
        }
        
        if (empty($payload['is_admin'])) {
            Response::forbidden('Admin access required');
        }
        
        $admin = self::getAdminById($payload['sub']);
        
        if (!$admin) {
            Response::unauthorized('Admin not found');
        }
        
        if ($admin['status'] !== 'active') {
            Response::unauthorized('Admin account is not active');
        }
        
        self::$currentAdmin = $admin;
        return $admin;
    }
    
    /**
     * Optional authentication - returns user or null
     */
    public static function optionalAuth() {
        $token = JWTManager::getTokenFromHeader();
        
        if (!$token) {
            return null;
        }
        
        $payload = JWTManager::validateToken($token);
        
        if (!$payload) {
            return null;
        }
        
        $user = self::getUserById($payload['sub']);
        
        if ($user && $user['status'] === 'active') {
            self::$currentUser = $user;
            return $user;
        }
        
        return null;
    }
    
    /**
     * Get the currently authenticated user
     */
    public static function getCurrentUser() {
        return self::$currentUser;
    }
    
    /**
     * Get the currently authenticated admin
     */
    public static function getCurrentAdmin() {
        return self::$currentAdmin;
    }
    
    /**
     * Get user by ID
     */
    public static function getUserById($id) {
        try {
            return Database::queryOne('users', "SELECT * FROM users WHERE id = ?", [$id]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get user by email
     */
    public static function getUserByEmail($email) {
        try {
            return Database::queryOne('users', "SELECT * FROM users WHERE email = ?", [$email]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get admin by ID
     */
    public static function getAdminById($id) {
        try {
            return Database::queryOne('admin_users', "SELECT * FROM admin_users WHERE id = ?", [$id]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get admin by email
     */
    public static function getAdminByEmail($email) {
        try {
            return Database::queryOne('admin_users', "SELECT * FROM admin_users WHERE email = ?", [$email]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Check if user is VIP
     */
    public static function isVipUser($userId = null) {
        if ($userId === null) {
            $user = self::$currentUser;
        } else {
            $user = self::getUserById($userId);
        }
        
        return $user && ($user['plan_type'] === 'vip' || !empty($user['is_vip']));
    }
    
    /**
     * Update user's last login
     */
    public static function updateLastLogin($userId) {
        try {
            Database::execute('users', "UPDATE users SET updated_at = datetime('now') WHERE id = ?", [$userId]);
        } catch (Exception $e) {
            // Silently fail
        }
    }
    
    /**
     * Create a session record
     */
    public static function createSession($userId, $token, $refreshToken = null) {
        // Sessions are managed via JWT, no database record needed for simple implementation
        return true;
    }
    
    /**
     * Get user's subscription
     */
    public static function getUserSubscription($userId) {
        try {
            return Database::queryOne('subscriptions', "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'", [$userId]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Check if user has active subscription
     */
    public static function hasActiveSubscription($userId = null) {
        $user = $userId ? self::getUserById($userId) : self::$currentUser;
        if (!$user) return false;
        
        $subscription = self::getUserSubscription($user['id']);
        
        if (!$subscription) return false;
        
        return in_array($subscription['status'], ['active', 'trial']);
    }
    
    /**
     * Sanitize user data for response (remove sensitive fields)
     */
    public static function sanitizeUser($user) {
        unset($user['password']);
        unset($user['password_hash']);
        unset($user['two_factor_secret']);
        unset($user['password_reset_token']);
        unset($user['password_reset_expires']);
        unset($user['email_verification_token']);
        unset($user['email_verify_token']);
        return $user;
    }
    
    /**
     * Check user's plan limits
     */
    public static function checkPlanLimit($user, $limitType) {
        $planLimits = [
            'trial' => ['devices' => 3, 'identities' => 3, 'mesh_users' => 0],
            'personal' => ['devices' => 3, 'identities' => 3, 'mesh_users' => 0],
            'family' => ['devices' => 10, 'identities' => 10, 'mesh_users' => 6],
            'business' => ['devices' => 50, 'identities' => 50, 'mesh_users' => 25],
            'vip' => ['devices' => 100, 'identities' => 100, 'mesh_users' => 100]
        ];
        
        $plan = $user['plan_type'] ?? 'personal';
        $limits = $planLimits[$plan] ?? $planLimits['personal'];
        
        return $limits[$limitType] ?? 0;
    }
}
