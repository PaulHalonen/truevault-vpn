<?php
/**
 * TrueVault VPN - VIP Manager (Database-Driven)
 * Handles VIP user detection and special access
 * VIP list is stored in database and managed via admin panel
 */

require_once __DIR__ . '/../config/database.php';

class VIPManager {
    
    private static $vipTableCreated = false;
    
    /**
     * Ensure VIP table exists
     */
    private static function ensureTable() {
        if (self::$vipTableCreated) return;
        
        $db = Database::getConnection('users');
        $db->exec("CREATE TABLE IF NOT EXISTS vip_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            type TEXT NOT NULL DEFAULT 'vip_basic',
            plan TEXT NOT NULL DEFAULT 'family',
            dedicated_server_id INTEGER,
            description TEXT,
            added_by TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create index for fast lookups
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vip_email ON vip_users(email)");
        
        self::$vipTableCreated = true;
    }
    
    /**
     * Check if email is a VIP user
     */
    public static function isVIP($email) {
        self::ensureTable();
        $email = strtolower(trim($email));
        
        $result = Database::queryOne('users',
            "SELECT id FROM vip_users WHERE LOWER(email) = ?",
            [$email]
        );
        
        return $result !== false && $result !== null;
    }
    
    /**
     * Get VIP details for email
     */
    public static function getVIPDetails($email) {
        self::ensureTable();
        $email = strtolower(trim($email));
        
        return Database::queryOne('users',
            "SELECT * FROM vip_users WHERE LOWER(email) = ?",
            [$email]
        );
    }
    
    /**
     * Get VIP type (owner, vip_dedicated, vip_basic)
     */
    public static function getVIPType($email) {
        $details = self::getVIPDetails($email);
        return $details ? $details['type'] : null;
    }
    
    /**
     * Get VIP plan type for subscription
     */
    public static function getVIPPlan($email) {
        $details = self::getVIPDetails($email);
        return $details ? $details['plan'] : null;
    }
    
    /**
     * Check if user is owner
     */
    public static function isOwner($email) {
        $details = self::getVIPDetails($email);
        return $details && $details['type'] === 'owner';
    }
    
    /**
     * Check if user can access a specific server
     */
    public static function canAccessServer($email, $serverId) {
        $details = self::getVIPDetails($email);
        
        if (!$details) {
            return false; // Not VIP, need to check subscription
        }
        
        // Owner can access everything
        if ($details['type'] === 'owner') {
            return true;
        }
        
        // Check if this is a VIP-dedicated server
        $server = Database::queryOne('vpn',
            "SELECT is_vip, vip_user_email FROM vpn_servers WHERE id = ?",
            [$serverId]
        );
        
        if ($server && $server['is_vip']) {
            // VIP server - check if user has dedicated access
            if ($details['dedicated_server_id'] == $serverId) {
                return true;
            }
            // Check if server is assigned to this email
            if ($server['vip_user_email'] && strtolower($server['vip_user_email']) === strtolower($email)) {
                return true;
            }
            return false;
        }
        
        // All VIPs can access shared servers
        return true;
    }
    
    /**
     * Get list of servers VIP can access
     */
    public static function getAccessibleServers($email) {
        $details = self::getVIPDetails($email);
        
        if (!$details) {
            return []; // Not VIP
        }
        
        // Owner gets all servers
        if ($details['type'] === 'owner') {
            $servers = Database::query('vpn', "SELECT id FROM vpn_servers WHERE status = 'online'");
            return array_column($servers, 'id');
        }
        
        // Get shared servers
        $servers = Database::query('vpn', 
            "SELECT id FROM vpn_servers WHERE status = 'online' AND is_vip = 0"
        );
        $serverIds = array_column($servers, 'id');
        
        // Add dedicated server if user has one
        if ($details['dedicated_server_id']) {
            $serverIds[] = (int)$details['dedicated_server_id'];
        }
        
        return $serverIds;
    }
    
    /**
     * Check if user has dedicated server
     */
    public static function hasDedicatedServer($email) {
        $details = self::getVIPDetails($email);
        return $details && !empty($details['dedicated_server_id']);
    }
    
    /**
     * Get dedicated server ID for user
     */
    public static function getDedicatedServer($email) {
        $details = self::getVIPDetails($email);
        return $details ? $details['dedicated_server_id'] : null;
    }
    
    /**
     * Get all VIP users (for admin)
     */
    public static function getAllVIPs() {
        self::ensureTable();
        return Database::query('users',
            "SELECT * FROM vip_users ORDER BY type, created_at"
        );
    }
    
    /**
     * Get all VIP emails
     */
    public static function getAllVIPEmails() {
        $vips = self::getAllVIPs();
        return array_column($vips, 'email');
    }
    
    /**
     * Get VIP count by type
     */
    public static function getVIPCounts() {
        self::ensureTable();
        
        $counts = ['owner' => 0, 'vip_dedicated' => 0, 'vip_basic' => 0, 'total' => 0];
        
        $results = Database::query('users',
            "SELECT type, COUNT(*) as count FROM vip_users GROUP BY type"
        );
        
        foreach ($results as $row) {
            $counts[$row['type']] = (int)$row['count'];
            $counts['total'] += (int)$row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Add a VIP user
     */
    public static function addVIP($email, $type = 'vip_basic', $plan = 'family', $dedicatedServerId = null, $description = '', $addedBy = null) {
        self::ensureTable();
        $email = strtolower(trim($email));
        
        // Check if already VIP
        if (self::isVIP($email)) {
            return ['success' => false, 'error' => 'Email is already a VIP'];
        }
        
        // Validate type
        $validTypes = ['owner', 'vip_dedicated', 'vip_basic'];
        if (!in_array($type, $validTypes)) {
            return ['success' => false, 'error' => 'Invalid VIP type'];
        }
        
        // Validate plan
        $validPlans = ['dedicated', 'family', 'personal'];
        if (!in_array($plan, $validPlans)) {
            return ['success' => false, 'error' => 'Invalid plan type'];
        }
        
        try {
            Database::execute('users',
                "INSERT INTO vip_users (email, type, plan, dedicated_server_id, description, added_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, datetime('now'))",
                [$email, $type, $plan, $dedicatedServerId, $description, $addedBy]
            );
            
            $vipId = Database::lastInsertId('users');
            
            // If user exists in users table, update their is_vip flag
            Database::execute('users',
                "UPDATE users SET is_vip = 1 WHERE LOWER(email) = ?",
                [$email]
            );
            
            // If user has an account, create/update their subscription
            $user = Database::queryOne('users', 
                "SELECT id FROM users WHERE LOWER(email) = ?", 
                [$email]
            );
            
            if ($user) {
                // Check if subscription exists
                $existingSub = Database::queryOne('billing',
                    "SELECT id FROM subscriptions WHERE user_id = ?",
                    [$user['id']]
                );
                
                $maxDevices = $plan === 'dedicated' ? 999 : ($plan === 'family' ? 10 : 3);
                
                if ($existingSub) {
                    // Update existing subscription
                    Database::execute('billing',
                        "UPDATE subscriptions SET plan_type = ?, status = 'active', max_devices = ?, 
                         end_date = datetime('now', '+100 years') WHERE user_id = ?",
                        [$plan, $maxDevices, $user['id']]
                    );
                } else {
                    // Create new subscription
                    Database::execute('billing',
                        "INSERT INTO subscriptions (user_id, plan_type, status, max_devices, start_date, end_date, created_at)
                         VALUES (?, ?, 'active', ?, datetime('now'), datetime('now', '+100 years'), datetime('now'))",
                        [$user['id'], $plan, $maxDevices]
                    );
                }
            }
            
            return ['success' => true, 'id' => $vipId];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Remove a VIP user
     */
    public static function removeVIP($email) {
        self::ensureTable();
        $email = strtolower(trim($email));
        
        // Check if VIP exists
        $vip = self::getVIPDetails($email);
        if (!$vip) {
            return ['success' => false, 'error' => 'Email is not a VIP'];
        }
        
        // Prevent removing owner
        if ($vip['type'] === 'owner') {
            return ['success' => false, 'error' => 'Cannot remove owner from VIP list'];
        }
        
        try {
            Database::execute('users',
                "DELETE FROM vip_users WHERE LOWER(email) = ?",
                [$email]
            );
            
            // Update user's is_vip flag
            Database::execute('users',
                "UPDATE users SET is_vip = 0 WHERE LOWER(email) = ?",
                [$email]
            );
            
            // Note: We don't delete their subscription - it will expire naturally
            // or admin can manually cancel it
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update a VIP user
     */
    public static function updateVIP($email, $data) {
        self::ensureTable();
        $email = strtolower(trim($email));
        
        // Check if VIP exists
        if (!self::isVIP($email)) {
            return ['success' => false, 'error' => 'Email is not a VIP'];
        }
        
        $updates = [];
        $params = [];
        
        if (isset($data['type'])) {
            $validTypes = ['owner', 'vip_dedicated', 'vip_basic'];
            if (!in_array($data['type'], $validTypes)) {
                return ['success' => false, 'error' => 'Invalid VIP type'];
            }
            $updates[] = "type = ?";
            $params[] = $data['type'];
        }
        
        if (isset($data['plan'])) {
            $validPlans = ['dedicated', 'family', 'personal'];
            if (!in_array($data['plan'], $validPlans)) {
                return ['success' => false, 'error' => 'Invalid plan type'];
            }
            $updates[] = "plan = ?";
            $params[] = $data['plan'];
        }
        
        if (array_key_exists('dedicated_server_id', $data)) {
            $updates[] = "dedicated_server_id = ?";
            $params[] = $data['dedicated_server_id'];
        }
        
        if (isset($data['description'])) {
            $updates[] = "description = ?";
            $params[] = $data['description'];
        }
        
        if (empty($updates)) {
            return ['success' => false, 'error' => 'No updates provided'];
        }
        
        $params[] = $email;
        
        try {
            Database::execute('users',
                "UPDATE vip_users SET " . implode(', ', $updates) . " WHERE LOWER(email) = ?",
                $params
            );
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Seed initial VIP users (run once during setup)
     */
    public static function seedInitialVIPs() {
        self::ensureTable();
        
        $initialVIPs = [
            [
                'email' => 'paulhalonen@gmail.com',
                'type' => 'owner',
                'plan' => 'dedicated',
                'dedicated_server_id' => null, // Owner has access to all
                'description' => 'System Owner'
            ],
            [
                'email' => 'seige235@yahoo.com',
                'type' => 'vip_dedicated',
                'plan' => 'dedicated',
                'dedicated_server_id' => 2, // STL server
                'description' => 'VIP Dedicated - St. Louis Server'
            ]
        ];
        
        $added = 0;
        foreach ($initialVIPs as $vip) {
            if (!self::isVIP($vip['email'])) {
                $result = self::addVIP(
                    $vip['email'],
                    $vip['type'],
                    $vip['plan'],
                    $vip['dedicated_server_id'],
                    $vip['description'],
                    'system_setup'
                );
                if ($result['success']) {
                    $added++;
                }
            }
        }
        
        return $added;
    }
}
