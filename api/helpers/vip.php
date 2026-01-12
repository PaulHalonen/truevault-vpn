<?php
/**
 * TrueVault VPN - VIP Helper Functions
 * Checks VIP status, manages VIP privileges
 */

class VIPManager {
    private static $db = null;
    
    private static function getDB() {
        if (self::$db === null) {
            $dbPath = __DIR__ . '/../../data/vip.db';
            if (!file_exists($dbPath)) {
                return null;
            }
            self::$db = new SQLite3($dbPath);
        }
        return self::$db;
    }
    
    /**
     * Check if email is a VIP
     */
    public static function isVIP($email) {
        $db = self::getDB();
        if (!$db) return false;
        
        $stmt = $db->prepare("SELECT * FROM vip_users WHERE LOWER(email) = LOWER(?)");
        $stmt->bindValue(1, strtolower($email), SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        return $result ? true : false;
    }
    
    /**
     * Get VIP details for a user
     */
    public static function getVIPDetails($email) {
        $db = self::getDB();
        if (!$db) return null;
        
        $stmt = $db->prepare("SELECT * FROM vip_users WHERE LOWER(email) = LOWER(?)");
        $stmt->bindValue(1, strtolower($email), SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        return $result ?: null;
    }
    
    /**
     * Activate VIP user (when they first register/login)
     */
    public static function activateVIP($email, $userId, $firstName = null, $lastName = null) {
        $db = self::getDB();
        if (!$db) return false;
        
        $stmt = $db->prepare("UPDATE vip_users SET activated_at = CURRENT_TIMESTAMP WHERE LOWER(email) = LOWER(?) AND activated_at IS NULL");
        $stmt->bindValue(1, strtolower($email), SQLITE3_TEXT);
        $stmt->execute();
        
        return true;
    }
    
    /**
     * Get plan limits for VIP user
     */
    public static function getVIPLimits($email) {
        $vip = self::getVIPDetails($email);
        if (!$vip) return null;
        
        return [
            'tier' => $vip['tier'],
            'max_devices' => $vip['max_devices'],
            'max_cameras' => $vip['max_cameras'],
            'dedicated_server_id' => $vip['dedicated_server_id'],
            'dedicated_server_ip' => $vip['dedicated_server_ip'],
            'is_dedicated' => $vip['tier'] === 'vip_dedicated',
            'bypass_payment' => true,
            'badge' => $vip['tier'] === 'vip_dedicated' ? '👑 VIP Dedicated' : '⭐ VIP'
        ];
    }
    
    /**
     * Check if user has dedicated server
     */
    public static function hasDedicatedServer($email) {
        $vip = self::getVIPDetails($email);
        return $vip && !empty($vip['dedicated_server_id']);
    }
    
    /**
     * Get user's dedicated server
     */
    public static function getDedicatedServer($email) {
        $vip = self::getVIPDetails($email);
        if (!$vip || empty($vip['dedicated_server_id'])) return null;
        
        return [
            'server_id' => $vip['dedicated_server_id'],
            'server_ip' => $vip['dedicated_server_ip']
        ];
    }
    
    /**
     * Add new VIP user (admin function)
     */
    public static function addVIP($email, $tier = 'vip_basic', $maxDevices = 8, $maxCameras = 2, $notes = '') {
        $db = self::getDB();
        if (!$db) return false;
        
        $stmt = $db->prepare("INSERT OR REPLACE INTO vip_users 
            (email, tier, max_devices, max_cameras, notes) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->bindValue(1, strtolower($email), SQLITE3_TEXT);
        $stmt->bindValue(2, $tier, SQLITE3_TEXT);
        $stmt->bindValue(3, $maxDevices, SQLITE3_INTEGER);
        $stmt->bindValue(4, $maxCameras, SQLITE3_INTEGER);
        $stmt->bindValue(5, $notes, SQLITE3_TEXT);
        
        return $stmt->execute() ? true : false;
    }
    
    /**
     * Get all VIP users (admin function)
     */
    public static function getAllVIPs() {
        $db = self::getDB();
        if (!$db) return [];
        
        $result = $db->query("SELECT * FROM vip_users ORDER BY created_at DESC");
        $vips = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $vips[] = $row;
        }
        return $vips;
    }
}

/**
 * Plan limits for regular (non-VIP) users
 */
class PlanLimits {
    
    const PLANS = [
        'basic' => [
            'name' => 'Basic',
            'price' => 9.99,
            'max_devices' => 3,
            'max_cameras' => 1,
            'camera_server' => 'ny_only',  // Cameras only on NY
            'servers' => 'shared',
            'features' => ['Basic VPN access', '3 devices', '1 IP camera (NY server)', 'Network scanner']
        ],
        'family' => [
            'name' => 'Family',
            'price' => 14.99,
            'max_devices' => 5,
            'max_cameras' => 2,
            'camera_server' => 'ny_only',
            'servers' => 'shared',
            'features' => ['Family VPN access', '5 devices', '2 IP cameras', 'Network scanner', 'Priority support']
        ],
        'dedicated' => [
            'name' => 'Dedicated',
            'price' => 29.99,
            'max_devices' => 999,  // Unlimited on dedicated
            'max_devices_ny' => 5,  // Plus 5 on NY
            'max_cameras' => 12,
            'camera_server' => 'any',
            'servers' => 'dedicated',
            'features' => [
                'Your own dedicated server',
                'Unlimited devices',
                'Up to 12 IP cameras',
                'Static IP addresses',
                'Drag & drop port forwarding',
                'Advanced terminal access',
                'Full bandwidth - torrents OK',
                '24/7 priority support'
            ]
        ],
        'corporate' => [
            'name' => 'Corporate',
            'price' => 0,  // Contact for pricing
            'max_devices' => 999,
            'max_cameras' => 999,
            'camera_server' => 'any',
            'servers' => 'dedicated',
            'features' => ['Custom solution', '12+ cameras', 'Multiple dedicated servers', 'SLA guarantee']
        ]
    ];
    
    public static function getPlan($planType) {
        return self::PLANS[$planType] ?? self::PLANS['basic'];
    }
    
    public static function getAllPlans() {
        return self::PLANS;
    }
    
    public static function getLimits($planType) {
        $plan = self::getPlan($planType);
        return [
            'max_devices' => $plan['max_devices'],
            'max_cameras' => $plan['max_cameras'],
            'camera_server' => $plan['camera_server']
        ];
    }
}

/**
 * Server rules and restrictions
 */
class ServerRules {
    
    const SERVERS = [
        1 => [
            'id' => 1,
            'name' => 'New York',
            'location' => 'New York, USA',
            'ip' => '66.94.103.91',
            'type' => 'shared',
            'provider' => 'Contabo',
            'flag' => '🇺🇸',
            'rules' => [
                'torrents' => true,
                'xbox' => true,
                'gaming' => true,
                'streaming' => true,
                'cameras' => true,
                'home_devices' => true
            ],
            'description' => 'Full access - Best for home devices, gaming, torrents',
            'recommended_for' => ['Xbox', 'PlayStation', 'Torrents', 'Home devices', 'IP cameras']
        ],
        2 => [
            'id' => 2,
            'name' => 'St. Louis (VIP)',
            'location' => 'St. Louis, USA',
            'ip' => '144.126.133.253',
            'type' => 'dedicated',
            'provider' => 'Contabo',
            'flag' => '🇺🇸',
            'vip_only' => true,
            'assigned_to' => 'seige235@yahoo.com',
            'rules' => [
                'torrents' => true,
                'xbox' => true,
                'gaming' => true,
                'streaming' => true,
                'unlimited_bandwidth' => true
            ],
            'description' => 'Dedicated VIP server - Unlimited bandwidth',
            'recommended_for' => ['Everything - Dedicated access']
        ],
        3 => [
            'id' => 3,
            'name' => 'Dallas',
            'location' => 'Dallas, USA',
            'ip' => '66.241.124.4',
            'type' => 'shared',
            'provider' => 'Fly.io',
            'flag' => '🇺🇸',
            'rules' => [
                'torrents' => false,
                'xbox' => false,
                'gaming' => false,
                'streaming' => true,
                'netflix' => true
            ],
            'description' => 'Netflix unblocked - NO torrents/gaming (limited bandwidth)',
            'recommended_for' => ['Netflix', 'Streaming', 'Browsing'],
            'blocked' => ['Torrents', 'Xbox', 'High-bandwidth gaming']
        ],
        4 => [
            'id' => 4,
            'name' => 'Toronto',
            'location' => 'Toronto, Canada',
            'ip' => '66.241.125.247',
            'type' => 'shared',
            'provider' => 'Fly.io',
            'flag' => '🇨🇦',
            'rules' => [
                'torrents' => false,
                'xbox' => false,
                'gaming' => false,
                'streaming' => true,
                'netflix' => true
            ],
            'description' => 'Canadian IP - Netflix unblocked - NO torrents/gaming',
            'recommended_for' => ['Canadian Netflix', 'Streaming', 'Browsing'],
            'blocked' => ['Torrents', 'Xbox', 'High-bandwidth gaming']
        ]
    ];
    
    public static function getServer($id) {
        return self::SERVERS[$id] ?? null;
    }
    
    public static function getAllServers() {
        return self::SERVERS;
    }
    
    public static function getSharedServers() {
        return array_filter(self::SERVERS, fn($s) => $s['type'] === 'shared');
    }
    
    public static function getAvailableServers($email = null) {
        $servers = [];
        
        foreach (self::SERVERS as $server) {
            // Skip VIP-only servers unless user is assigned
            if (!empty($server['vip_only'])) {
                if ($email && strtolower($email) === strtolower($server['assigned_to'])) {
                    $servers[] = $server;
                }
                continue;
            }
            
            $servers[] = $server;
        }
        
        return $servers;
    }
    
    public static function canUseServer($serverId, $email) {
        $server = self::getServer($serverId);
        if (!$server) return false;
        
        // Check if VIP-only server
        if (!empty($server['vip_only'])) {
            return $email && strtolower($email) === strtolower($server['assigned_to']);
        }
        
        return true;
    }
    
    public static function isAllowed($serverId, $ruleType) {
        $server = self::getServer($serverId);
        if (!$server) return false;
        
        return $server['rules'][$ruleType] ?? false;
    }
}
