<?php
/**
 * WireGuard Peer Management Class
 * 
 * PURPOSE: Manage WireGuard peers on VPN servers
 * - Add peers to servers
 * - Remove peers from servers
 * - List peers on servers
 * - Reload server configurations
 * 
 * @created January 23, 2026
 * @version 1.0.0
 */

if (!defined('TRUEVAULT_INIT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

class WireGuard {
    
    /**
     * Get API URL for a server
     */
    private static function getApiUrl($server) {
        $port = $server['api_port'] ?? 8443;
        return "https://{$server['ip_address']}:{$port}";
    }
    
    /**
     * Get API secret for a server
     */
    private static function getApiSecret($serverId) {
        $settingsDb = Database::getInstance('settings');
        $stmt = $settingsDb->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key");
        $stmt->bindValue(':key', "server_api_secret_{$serverId}", SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['setting_value'] : null;
    }
    
    /**
     * Make API request to VPN server
     */
    private static function apiRequest($server, $endpoint, $method = 'GET', $data = null) {
        $apiUrl = self::getApiUrl($server);
        $apiSecret = self::getApiSecret($server['id']);
        
        if (!$apiSecret) {
            throw new Exception("No API secret configured for server {$server['id']}");
        }
        
        $url = "{$apiUrl}{$endpoint}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $headers = [
            'Content-Type: application/json',
            'X-API-Secret: ' . $apiSecret
        ];
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("API request failed: {$error}");
        }
        
        if ($httpCode >= 400) {
            throw new Exception("API returned error code: {$httpCode}");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Add a peer to a VPN server
     * 
     * @param int $serverId Server ID
     * @param string $publicKey Client's WireGuard public key
     * @param string $allowedIPs Allowed IPs for the peer
     * @return array Response from server
     */
    public static function addPeer($serverId, $publicKey, $allowedIPs = null) {
        $serversDb = Database::getInstance('servers');
        $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id");
        $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $server = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$server) {
            throw new Exception("Server not found: {$serverId}");
        }
        
        $data = [
            'public_key' => $publicKey
        ];
        
        if ($allowedIPs) {
            $data['allowed_ips'] = $allowedIPs;
        }
        
        return self::apiRequest($server, '/api/add-peer', 'POST', $data);
    }
    
    /**
     * Remove a peer from a VPN server
     * 
     * @param int $serverId Server ID
     * @param string $publicKey Client's WireGuard public key
     * @return array Response from server
     */
    public static function removePeer($serverId, $publicKey) {
        $serversDb = Database::getInstance('servers');
        $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id");
        $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $server = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$server) {
            throw new Exception("Server not found: {$serverId}");
        }
        
        $data = [
            'public_key' => $publicKey
        ];
        
        return self::apiRequest($server, '/api/remove-peer', 'POST', $data);
    }
    
    /**
     * List all peers on a VPN server
     * 
     * @param int $serverId Server ID
     * @return array List of peers
     */
    public static function listPeers($serverId) {
        $serversDb = Database::getInstance('servers');
        $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id");
        $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $server = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$server) {
            throw new Exception("Server not found: {$serverId}");
        }
        
        return self::apiRequest($server, '/api/list-peers', 'GET');
    }
    
    /**
     * Check server health
     * 
     * @param int $serverId Server ID
     * @return array Health status
     */
    public static function checkHealth($serverId) {
        $serversDb = Database::getInstance('servers');
        $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id");
        $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $server = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$server) {
            throw new Exception("Server not found: {$serverId}");
        }
        
        try {
            $response = self::apiRequest($server, '/health', 'GET');
            return [
                'online' => true,
                'response' => $response
            ];
        } catch (Exception $e) {
            return [
                'online' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate WireGuard config file content for a device
     * 
     * @param array $device Device data
     * @param array $server Server data
     * @return string WireGuard config file content
     */
    public static function generateConfig($device, $server) {
        $config = "[Interface]\n";
        $config .= "PrivateKey = {$device['private_key']}\n";
        $config .= "Address = {$device['assigned_ip']}/32\n";
        $config .= "DNS = 1.1.1.1, 8.8.8.8\n\n";
        
        $config .= "[Peer]\n";
        $config .= "PublicKey = {$server['public_key']}\n";
        $config .= "Endpoint = {$server['ip_address']}:51820\n";
        $config .= "AllowedIPs = 0.0.0.0/0\n";
        $config .= "PersistentKeepalive = 25\n";
        
        return $config;
    }
}
