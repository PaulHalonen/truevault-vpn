<?php
/**
 * TrueVault VPN - Peer Provisioner
 * Handles adding/removing peers from VPN servers
 * 
 * Called by billing system when:
 * - User pays → provisionUser()
 * - User cancels/fails → revokeAccess()
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/vip.php';

class PeerProvisioner {
    
    // Server API configuration
    private static $servers = [
        1 => ['name' => 'NY', 'ip' => '66.94.103.91', 'api_port' => 8080, 'network' => '10.0.0'],
        2 => ['name' => 'STL', 'ip' => '144.126.133.253', 'api_port' => 8080, 'network' => '10.0.1', 'vip_only' => 'seige235@yahoo.com'],
        3 => ['name' => 'TX', 'ip' => '66.241.124.4', 'api_port' => 8080, 'network' => '10.10.1'],
        4 => ['name' => 'CAN', 'ip' => '66.241.125.247', 'api_port' => 8080, 'network' => '10.10.0']
    ];
    
    private static $apiSecret = 'TrueVault2026SecretKey';
    
    /**
     * Provision user access to VPN servers
     * Called when payment succeeds
     */
    public static function provisionUser($userId, $email) {
        $results = [];
        
        // Get or create WireGuard keys
        $userKey = self::getOrCreateUserKey($userId);
        
        if (!$userKey) {
            return ['success' => false, 'error' => 'Failed to get/create user keys'];
        }
        
        // Determine which servers user can access
        $accessibleServers = self::getAccessibleServers($email);
        
        foreach ($accessibleServers as $serverId => $server) {
            // Check if already provisioned
            $existing = Database::queryOne('vpn',
                "SELECT * FROM user_peers WHERE user_id = ? AND server_id = ? AND status = 'active'",
                [$userId, $serverId]
            );
            
            if ($existing) {
                $results[$serverId] = ['status' => 'already_provisioned', 'ip' => $existing['assigned_ip']];
                continue;
            }
            
            // Assign IP
            $assignedIp = self::assignIp($userId, $serverId, $server['network']);
            
            // Call server API to add peer
            $result = self::addPeerToServer($serverId, $userKey['public_key'], $assignedIp, $userId);
            
            if ($result['success']) {
                // Update database
                Database::execute('vpn',
                    "INSERT OR REPLACE INTO user_peers (user_id, server_id, public_key, assigned_ip, status, provisioned_at, created_at)
                     VALUES (?, ?, ?, ?, 'active', datetime('now'), datetime('now'))",
                    [$userId, $serverId, $userKey['public_key'], $assignedIp]
                );
                
                $results[$serverId] = ['status' => 'provisioned', 'ip' => $assignedIp];
            } else {
                $results[$serverId] = ['status' => 'failed', 'error' => $result['error'] ?? 'Unknown error'];
            }
        }
        
        // Log provisioning
        Database::execute('logs',
            "INSERT INTO activity_log (user_id, action, details, created_at)
             VALUES (?, 'peer_provisioned', ?, datetime('now'))",
            [$userId, json_encode($results)]
        );
        
        return ['success' => true, 'results' => $results];
    }
    
    /**
     * Revoke user access from all VPN servers
     * Called when payment fails or subscription cancelled
     */
    public static function revokeAccess($userId) {
        $results = [];
        
        // Get user's public key
        $userKey = Database::queryOne('certificates',
            "SELECT public_key FROM user_certificates WHERE user_id = ? AND type = 'wireguard' AND status = 'active'",
            [$userId]
        );
        
        if (!$userKey) {
            return ['success' => false, 'error' => 'No active keys found'];
        }
        
        // Get all active peers for this user
        $activePeers = Database::queryAll('vpn',
            "SELECT * FROM user_peers WHERE user_id = ? AND status = 'active'",
            [$userId]
        );
        
        foreach ($activePeers as $peer) {
            // Call server API to remove peer
            $result = self::removePeerFromServer($peer['server_id'], $userKey['public_key']);
            
            // Update database regardless of API result
            Database::execute('vpn',
                "UPDATE user_peers SET status = 'revoked', revoked_at = datetime('now') WHERE id = ?",
                [$peer['id']]
            );
            
            $results[$peer['server_id']] = $result['success'] ? 'revoked' : 'revoked_db_only';
        }
        
        // Log revocation
        Database::execute('logs',
            "INSERT INTO activity_log (user_id, action, details, created_at)
             VALUES (?, 'peer_revoked', ?, datetime('now'))",
            [$userId, json_encode($results)]
        );
        
        return ['success' => true, 'results' => $results];
    }
    
    /**
     * Add peer to specific server
     */
    private static function addPeerToServer($serverId, $publicKey, $allowedIp, $userId) {
        $server = self::$servers[$serverId] ?? null;
        if (!$server) {
            return ['success' => false, 'error' => 'Unknown server'];
        }
        
        return self::serverRequest($server, 'POST', '/peers/add', [
            'public_key' => $publicKey,
            'allowed_ip' => $allowedIp,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Remove peer from specific server
     */
    private static function removePeerFromServer($serverId, $publicKey) {
        $server = self::$servers[$serverId] ?? null;
        if (!$server) {
            return ['success' => false, 'error' => 'Unknown server'];
        }
        
        return self::serverRequest($server, 'POST', '/peers/remove', [
            'public_key' => $publicKey
        ]);
    }
    
    /**
     * Make HTTP request to server API
     */
    private static function serverRequest($server, $method, $endpoint, $data = null) {
        $url = "http://{$server['ip']}:{$server['api_port']}{$endpoint}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . self::$apiSecret
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => "Connection failed: {$error}"];
        }
        
        if ($httpCode === 0) {
            return ['success' => false, 'error' => 'Server unreachable'];
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return $result ?: ['success' => true];
        }
        
        return ['success' => false, 'error' => $result['error'] ?? "HTTP {$httpCode}"];
    }
    
    /**
     * Get servers user can access
     */
    private static function getAccessibleServers($email) {
        $accessible = [];
        
        foreach (self::$servers as $serverId => $server) {
            if (isset($server['vip_only'])) {
                // VIP-only server
                if (strtolower($email) === strtolower($server['vip_only'])) {
                    $accessible[$serverId] = $server;
                }
            } else {
                // Shared server - everyone gets access
                $accessible[$serverId] = $server;
            }
        }
        
        return $accessible;
    }
    
    /**
     * Get or create user's WireGuard key
     */
    private static function getOrCreateUserKey($userId) {
        $existing = Database::queryOne('certificates',
            "SELECT * FROM user_certificates WHERE user_id = ? AND type = 'wireguard' AND status = 'active'",
            [$userId]
        );
        
        if ($existing) {
            return $existing;
        }
        
        // Generate new keypair
        $privateKey = self::generatePrivateKey();
        $publicKey = self::generatePublicKey($privateKey);
        
        Database::execute('certificates',
            "INSERT INTO user_certificates (user_id, name, type, public_key, private_key, status, created_at)
             VALUES (?, 'WireGuard Key', 'wireguard', ?, ?, 'active', datetime('now'))",
            [$userId, $publicKey, $privateKey]
        );
        
        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey
        ];
    }
    
    /**
     * Assign IP address for user on server
     */
    private static function assignIp($userId, $serverId, $network) {
        // Check existing assignment
        $existing = Database::queryOne('vpn',
            "SELECT assigned_ip FROM user_peers WHERE user_id = ? AND server_id = ?",
            [$userId, $serverId]
        );
        
        if ($existing && $existing['assigned_ip']) {
            return $existing['assigned_ip'];
        }
        
        // Get used IPs on this server
        $used = Database::queryAll('vpn',
            "SELECT assigned_ip FROM user_peers WHERE server_id = ? AND assigned_ip LIKE ?",
            [$serverId, "{$network}.%"]
        );
        
        $usedOctets = [];
        foreach ($used as $row) {
            $parts = explode('.', $row['assigned_ip']);
            if (count($parts) === 4) {
                $usedOctets[] = (int) $parts[3];
            }
        }
        
        // Find next available (2-254)
        for ($i = 2; $i <= 254; $i++) {
            if (!in_array($i, $usedOctets)) {
                return "{$network}.{$i}";
            }
        }
        
        // Fallback
        return "{$network}." . (($userId % 253) + 2);
    }
    
    /**
     * Generate WireGuard private key
     */
    private static function generatePrivateKey() {
        $bytes = random_bytes(32);
        $bytes[0] = chr(ord($bytes[0]) & 248);
        $bytes[31] = chr((ord($bytes[31]) & 127) | 64);
        return base64_encode($bytes);
    }
    
    /**
     * Generate WireGuard public key
     */
    private static function generatePublicKey($privateKey) {
        if (function_exists('sodium_crypto_scalarmult_base')) {
            return base64_encode(sodium_crypto_scalarmult_base(base64_decode($privateKey)));
        }
        return base64_encode(hash('sha256', base64_decode($privateKey), true));
    }
    
    /**
     * Check server health
     */
    public static function checkServerHealth($serverId = null) {
        $results = [];
        $serversToCheck = $serverId ? [$serverId => self::$servers[$serverId]] : self::$servers;
        
        foreach ($serversToCheck as $id => $server) {
            if (!$server) continue;
            
            $result = self::serverRequest($server, 'GET', '/health');
            $results[$id] = [
                'name' => $server['name'],
                'ip' => $server['ip'],
                'healthy' => isset($result['status']) && $result['status'] === 'healthy',
                'response' => $result
            ];
        }
        
        return $results;
    }
}
