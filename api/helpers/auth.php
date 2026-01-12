<?php
/**
 * TrueVault VPN - Auth Helper
 * Authentication middleware and user retrieval
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/logger.php';

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
        
        // Get user from database
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
        
        // Check if admin token
        if (empty($payload['is_admin'])) {
            Response::forbidden('Admin access required');
        }
        
        // Get admin from database
        $admin = self::getAdminById($payload['sub']);
        
        if (!$admin) {
            Response::unauthorized('Admin not found');
        }
        
        if (!$admin['is_active']) {
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
     * Check if user is VIP
     */
    public static function isVipUser($userId = null) {
        if ($userId === null) {
            $user = self::$currentUser;
        } else {
            $user = self::getUserById($userId);
        }
        
        return $user && $user['is_vip'] == 1;
    }
    
    /**
     * Check if user is VIP and get their dedicated server
     */
    public static function getVipServer($userId = null) {
        if (!self::isVipUser($userId)) {
            return null;
        }
        
        $user = $userId ? self::getUserById($userId) : self::$currentUser;
        
        if (!$user || !$user['vip_server_id']) {
            return null;
        }
        
        try {
            $db = DatabaseManager::getInstance()->servers();
            $stmt = $db->prepare("SELECT * FROM vpn_servers WHERE id = ?");
            $stmt->execute([$user['vip_server_id']]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get user by ID
     */
    public static function getUserById($id) {
        try {
            $db = DatabaseManager::getInstance()->users();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get user by email
     */
    public static function getUserByEmail($email) {
        try {
            $db = DatabaseManager::getInstance()->users();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get user by UUID
     */
    public static function getUserByUuid($uuid) {
        try {
            $db = DatabaseManager::getInstance()->users();
            $stmt = $db->prepare("SELECT * FROM users WHERE uuid = ?");
            $stmt->execute([$uuid]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get admin by ID
     */
    public static function getAdminById($id) {
        try {
            $db = DatabaseManager::getInstance()->admin();
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get admin by email
     */
    public static function getAdminByEmail($email) {
        try {
            $db = DatabaseManager::getInstance()->admin();
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Check user's plan limits
     */
    public static function checkPlanLimit($user, $limitType) {
        $planLimits = [
            'trial' => ['devices' => 3, 'identities' => 3, 'mesh_users' => 0],
            'personal' => ['devices' => 3, 'identities' => 3, 'mesh_users' => 0],
            'family' => ['devices' => 999, 'identities' => 999, 'mesh_users' => 6],
            'business' => ['devices' => 999, 'identities' => 999, 'mesh_users' => 25]
        ];
        
        $plan = $user['plan_type'] ?? 'personal';
        $limits = $planLimits[$plan] ?? $planLimits['personal'];
        
        return $limits[$limitType] ?? 0;
    }
    
    /**
     * Update user's last login
     */
    public static function updateLastLogin($userId) {
        try {
            $db = DatabaseManager::getInstance()->users();
            $stmt = $db->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            // Silently fail
        }
    }
    
    /**
     * Create a session record
     */
    public static function createSession($userId, $token, $refreshToken = null) {
        try {
            $db = DatabaseManager::getInstance()->sessions();
            $stmt = $db->prepare("
                INSERT INTO sessions (user_id, token, refresh_token, ip_address, user_agent, expires_at)
                VALUES (?, ?, ?, ?, ?, datetime('now', '+7 days'))
            ");
            $stmt->execute([
                $userId,
                $token,
                $refreshToken,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Invalidate a session
     */
    public static function invalidateSession($token) {
        try {
            $db = DatabaseManager::getInstance()->sessions();
            $stmt = $db->prepare("UPDATE sessions SET is_valid = 0 WHERE token = ?");
            $stmt->execute([$token]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get user's subscription
     */
    public static function getUserSubscription($userId) {
        try {
            $db = DatabaseManager::getInstance()->subscriptions();
            $stmt = $db->prepare("
                SELECT s.*, p.plan_name, p.price_monthly, p.device_limit, p.mesh_user_limit
                FROM subscriptions s
                JOIN subscription_plans p ON s.plan_id = p.id
                WHERE s.user_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch();
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
        unset($user['password_hash']);
        unset($user['two_factor_secret']);
        unset($user['password_reset_token']);
        unset($user['password_reset_expires']);
        unset($user['email_verification_token']);
        return $user;
    }
}
