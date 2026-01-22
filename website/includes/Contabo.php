<?php
/**
 * Contabo API Helper Class
 * 
 * PURPOSE: Interact with Contabo API for server management
 * - Get server status
 * - Restart servers
 * - Get usage statistics
 * 
 * NOTE: Contabo API credentials stored in database (business_settings)
 * 
 * @created January 23, 2026
 * @version 1.0.0
 */

if (!defined('TRUEVAULT_INIT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

class Contabo {
    
    private static $apiBaseUrl = 'https://api.contabo.com/v1';
    private static $accessToken = null;
    private static $tokenExpiry = 0;
    
    /**
     * Get API credentials from database
     */
    private static function getCredentials() {
        $settingsDb = Database::getInstance('settings');
        
        $credentials = [];
        $keys = ['contabo_client_id', 'contabo_client_secret', 'contabo_api_user', 'contabo_api_password'];
        
        foreach ($keys as $key) {
            $stmt = $settingsDb->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key");
            $stmt->bindValue(':key', $key, SQLITE3_TEXT);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $credentials[$key] = $row ? $row['setting_value'] : null;
        }
        
        return $credentials;
    }
    
    /**
     * Get access token for API requests
     * 
     * @return string Access token
     */
    public static function getAccessToken() {
        // Return cached token if still valid
        if (self::$accessToken && time() < self::$tokenExpiry) {
            return self::$accessToken;
        }
        
        $credentials = self::getCredentials();
        
        if (!$credentials['contabo_client_id'] || !$credentials['contabo_client_secret']) {
            throw new Exception('Contabo API credentials not configured');
        }
        
        // Request new token
        $ch = curl_init('https://auth.contabo.com/auth/realms/contabo/protocol/openid-connect/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $credentials['contabo_client_id'],
            'client_secret' => $credentials['contabo_client_secret'],
            'username' => $credentials['contabo_api_user'],
            'password' => $credentials['contabo_api_password'],
            'grant_type' => 'password'
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to get Contabo access token: HTTP {$httpCode}");
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new Exception('Invalid token response from Contabo');
        }
        
        self::$accessToken = $data['access_token'];
        self::$tokenExpiry = time() + ($data['expires_in'] ?? 300) - 60; // Refresh 1 min before expiry
        
        return self::$accessToken;
    }
    
    /**
     * Make API request to Contabo
     */
    private static function apiRequest($endpoint, $method = 'GET', $data = null) {
        $token = self::getAccessToken();
        
        $ch = curl_init(self::$apiBaseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'x-request-id: ' . uniqid('tv-')
        ]);
        
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("Contabo API error: HTTP {$httpCode}");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get server status
     * 
     * @param string $instanceId Contabo instance ID (e.g., vmi2990026)
     * @return array Server status
     */
    public static function getServerStatus($instanceId) {
        try {
            $response = self::apiRequest("/compute/instances/{$instanceId}");
            
            if (isset($response['data'])) {
                $instance = $response['data'][0] ?? $response['data'];
                return [
                    'success' => true,
                    'instance_id' => $instance['instanceId'] ?? $instanceId,
                    'name' => $instance['name'] ?? '',
                    'status' => $instance['status'] ?? 'unknown',
                    'ip_config' => $instance['ipConfig'] ?? [],
                    'created_date' => $instance['createdDate'] ?? '',
                    'region' => $instance['region'] ?? ''
                ];
            }
            
            return ['success' => false, 'error' => 'Invalid response'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Restart a server
     * 
     * @param string $instanceId Contabo instance ID
     * @return array Result
     */
    public static function restartServer($instanceId) {
        try {
            $response = self::apiRequest("/compute/instances/{$instanceId}/actions/restart", 'POST');
            return ['success' => true, 'message' => 'Server restart initiated'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get usage statistics
     * 
     * @param string $instanceId Contabo instance ID
     * @return array Usage stats
     */
    public static function getUsageStats($instanceId) {
        try {
            // Contabo doesn't have a direct usage API, so we'll return basic info
            $status = self::getServerStatus($instanceId);
            
            if (!$status['success']) {
                return $status;
            }
            
            return [
                'success' => true,
                'instance_id' => $instanceId,
                'status' => $status['status'],
                'note' => 'Detailed usage stats require SSH access to server'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * List all instances
     * 
     * @return array List of instances
     */
    public static function listInstances() {
        try {
            $response = self::apiRequest('/compute/instances');
            return [
                'success' => true,
                'instances' => $response['data'] ?? []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
