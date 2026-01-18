<?php
/**
 * Authentication Middleware
 * 
 * PURPOSE: Verify JWT tokens and protect API endpoints
 * USAGE: Include at top of protected endpoints with Auth::require()
 * 
 * EXAMPLES:
 * - $user = Auth::require(); // Require authentication
 * - Auth::requireTier(['pro', 'vip'], $user); // Require specific tier
 * - Auth::requireAdmin(); // Require admin access
 * - Auth::requireVIP(); // Require VIP tier
 * 
 * @created January 2026
 * @version 1.0.0
 */

class Auth {
    
    /**
     * Require authentication
     * Validates JWT token and returns user data
     * Automatically checks if session is still valid
     * 
     * @return array User data from token
     * @throws Exception if authentication fails (sends JSON error and exits)
     */
    public static function require() {
        // Get token from Authorization header
        $token = JWT::getTokenFromHeader();
        
        if (!$token) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Authentication required. Please provide a valid token.'
            ]);
            exit;
        }
        
        // Validate token
        try {
            $payload = JWT::validate($token);
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid or expired token. Please login again.'
            ]);
            exit;
        }
        
        // Verify session exists in database (if session_token provided)
        if (isset($payload['session_token'])) {
            $session = Database::queryOne('users',
                "SELECT id, user_id, expires_at 
                FROM sessions 
                WHERE session_token = ? 
                AND expires_at > datetime('now')",
                [$payload['session_token']]
            );
            
            if (!$session) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Session expired. Please login again.'
                ]);
                exit;
            }
            
            // Update last activity timestamp
            Database::execute('users',
                "UPDATE sessions 
                SET last_activity = datetime('now') 
                WHERE session_token = ?",
                [$payload['session_token']]
            );
        }
        
        // Verify user still exists and is active
        $user = Database::queryOne('users',
            "SELECT id, email, tier, status 
            FROM users 
            WHERE id = ?",
            [$payload['user_id']]
        );
        
        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'User not found'
            ]);
            exit;
        }
        
        // Check if user account is suspended or cancelled
        if ($user['status'] === 'suspended') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Account suspended. Please contact support.'
            ]);
            exit;
        }
        
        if ($user['status'] === 'cancelled') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Account cancelled. Please contact support to reactivate.'
            ]);
            exit;
        }
        
        // Return complete user data from token + database
        return array_merge($payload, [
            'status' => $user['status']
        ]);
    }
    
    /**
     * Require specific user tier(s)
     * 
     * @param array $allowedTiers Array of allowed tiers (e.g., ['pro', 'vip'])
     * @param array $user User data from Auth::require()
     */
    public static function requireTier($allowedTiers, $user) {
        if (!in_array($user['tier'], $allowedTiers, true)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Insufficient permissions. This feature requires: ' . implode(' or ', $allowedTiers) . ' tier.'
            ]);
            exit;
        }
    }
    
    /**
     * Require admin access
     * Checks admin.db for admin user, not regular users
     * 
     * @return array Admin user data
     */
    public static function requireAdmin() {
        // Get token
        $token = JWT::getTokenFromHeader();
        
        if (!$token) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Admin authentication required'
            ]);
            exit;
        }
        
        // Validate token
        try {
            $payload = JWT::validate($token);
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid admin token'
            ]);
            exit;
        }
        
        // Check if this is an admin token (has admin_id)
        if (!isset($payload['admin_id'])) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Admin access required'
            ]);
            exit;
        }
        
        // Verify admin still exists and is active
        $admin = Database::queryOne('admin',
            "SELECT id, email, role, status 
            FROM admin_users 
            WHERE id = ?",
            [$payload['admin_id']]
        );
        
        if (!$admin || $admin['status'] !== 'active') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Admin account not active'
            ]);
            exit;
        }
        
        return $admin;
    }
    
    /**
     * Require VIP tier
     * Shortcut for Auth::requireTier(['vip'], $user)
     * 
     * @param array $user User data from Auth::require()
     */
    public static function requireVIP($user) {
        self::requireTier(['vip'], $user);
    }
    
    /**
     * Check if user is VIP (without exiting)
     * 
     * @param array $user User data
     * @return bool True if VIP
     */
    public static function isVIP($user) {
        return isset($user['tier']) && $user['tier'] === 'vip';
    }
    
    /**
     * Check if email is in VIP list
     * Used during registration to auto-approve VIP users
     * 
     * @param string $email Email address
     * @return bool True if email is in VIP list
     */
    public static function isVIPEmail($email) {
        $vip = Database::queryOne('admin',
            "SELECT id FROM vip_list WHERE email = ? AND status = 'active'",
            [strtolower(trim($email))]
        );
        
        return (bool)$vip;
    }
}
?>
