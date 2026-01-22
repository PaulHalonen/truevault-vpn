<?php
/**
 * Bandwidth Tracking Class
 * 
 * PURPOSE: Track and manage bandwidth usage on VPN servers
 * - Track bandwidth per server
 * - Check bandwidth limits
 * - Alert when approaching limits
 * - Track daily/weekly/monthly trends
 * 
 * @created January 23, 2026
 * @version 1.0.0
 */

if (!defined('TRUEVAULT_INIT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

class Bandwidth {
    
    // Default bandwidth limits (in GB)
    const CONTABO_MONTHLY_LIMIT = 1000; // ~1TB fair use
    const FLYIO_MONTHLY_LIMIT = 100;    // Limited bandwidth
    
    /**
     * Track bandwidth for a server
     * 
     * @param int $serverId Server ID
     * @return array Bandwidth data
     */
    public static function trackBandwidth($serverId) {
        $serversDb = Database::getInstance('servers');
        
        // Get server
        $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id");
        $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $server = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$server) {
            throw new Exception("Server not found: {$serverId}");
        }
        
        // Get bandwidth from server API (if available)
        $bandwidthData = self::fetchBandwidthFromServer($server);
        
        if ($bandwidthData) {
            // Update database
            $stmt = $serversDb->prepare("
                UPDATE servers 
                SET bandwidth_used = :used, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $stmt->bindValue(':used', $bandwidthData['used_bytes'], SQLITE3_INTEGER);
            $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
            $stmt->execute();
            
            // Log bandwidth
            self::logBandwidth($serverId, $bandwidthData);
        }
        
        return $bandwidthData ?? ['used_bytes' => 0, 'error' => 'Could not fetch bandwidth'];
    }
    
    /**
     * Fetch bandwidth data from server
     */
    private static function fetchBandwidthFromServer($server) {
        // Try to get bandwidth from server API
        try {
            $apiUrl = "https://{$server['ip_address']}:" . ($server['api_port'] ?? 8443);
            
            $ch = curl_init("{$apiUrl}/api/bandwidth");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            if ($response) {
                return json_decode($response, true);
            }
        } catch (Exception $e) {
            error_log("Failed to fetch bandwidth: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Get bandwidth usage for a server
     * 
     * @param int $serverId Server ID
     * @return array Bandwidth usage data
     */
    public static function getBandwidthUsage($serverId) {
        $serversDb = Database::getInstance('servers');
        
        // Get server
        $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id");
        $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $server = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$server) {
            throw new Exception("Server not found: {$serverId}");
        }
        
        // Determine limit based on provider
        $limit = ($server['provider'] === 'Fly.io') ? self::FLYIO_MONTHLY_LIMIT : self::CONTABO_MONTHLY_LIMIT;
        $usedGB = ($server['bandwidth_used'] ?? 0) / (1024 * 1024 * 1024);
        $percentage = ($usedGB / $limit) * 100;
        
        return [
            'server_id' => $serverId,
            'server_name' => $server['name'],
            'used_bytes' => $server['bandwidth_used'] ?? 0,
            'used_gb' => round($usedGB, 2),
            'limit_gb' => $limit,
            'percentage' => round($percentage, 1),
            'status' => $percentage > 90 ? 'critical' : ($percentage > 75 ? 'warning' : 'ok')
        ];
    }
    
    /**
     * Check bandwidth limits for all servers
     * 
     * @return array Servers approaching limits
     */
    public static function checkBandwidthLimits() {
        $serversDb = Database::getInstance('servers');
        $result = $serversDb->query("SELECT * FROM servers WHERE status = 'active'");
        
        $warnings = [];
        
        while ($server = $result->fetchArray(SQLITE3_ASSOC)) {
            $usage = self::getBandwidthUsage($server['id']);
            
            if ($usage['status'] !== 'ok') {
                $warnings[] = $usage;
                
                // Send alert if critical
                if ($usage['status'] === 'critical') {
                    self::sendBandwidthAlert($server, $usage);
                }
            }
        }
        
        return $warnings;
    }
    
    /**
     * Get bandwidth trends for a server
     * 
     * @param int $serverId Server ID
     * @param string $period 'daily', 'weekly', or 'monthly'
     * @return array Bandwidth trend data
     */
    public static function getBandwidthTrends($serverId, $period = 'daily') {
        $serversDb = Database::getInstance('servers');
        
        // Create bandwidth_log table if not exists
        $serversDb->exec("
            CREATE TABLE IF NOT EXISTS bandwidth_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                server_id INTEGER NOT NULL,
                bytes_used BIGINT,
                recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Determine date range
        switch ($period) {
            case 'weekly':
                $dateFilter = "date('now', '-7 days')";
                break;
            case 'monthly':
                $dateFilter = "date('now', '-30 days')";
                break;
            default:
                $dateFilter = "date('now', '-1 day')";
        }
        
        $stmt = $serversDb->prepare("
            SELECT 
                date(recorded_at) as date,
                MAX(bytes_used) as bytes_used
            FROM bandwidth_log 
            WHERE server_id = :sid 
            AND recorded_at >= {$dateFilter}
            GROUP BY date(recorded_at)
            ORDER BY date ASC
        ");
        $stmt->bindValue(':sid', $serverId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $trends = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $trends[] = [
                'date' => $row['date'],
                'bytes' => $row['bytes_used'],
                'gb' => round($row['bytes_used'] / (1024 * 1024 * 1024), 2)
            ];
        }
        
        return $trends;
    }
    
    /**
     * Log bandwidth reading
     */
    private static function logBandwidth($serverId, $data) {
        $serversDb = Database::getInstance('servers');
        
        // Create table if not exists
        $serversDb->exec("
            CREATE TABLE IF NOT EXISTS bandwidth_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                server_id INTEGER NOT NULL,
                bytes_used BIGINT,
                recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $stmt = $serversDb->prepare("
            INSERT INTO bandwidth_log (server_id, bytes_used)
            VALUES (:sid, :bytes)
        ");
        $stmt->bindValue(':sid', $serverId, SQLITE3_INTEGER);
        $stmt->bindValue(':bytes', $data['used_bytes'] ?? 0, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    /**
     * Send bandwidth alert to admin
     */
    private static function sendBandwidthAlert($server, $usage) {
        try {
            Email::send(
                'paulhalonen@gmail.com',
                "TrueVault VPN - Bandwidth Alert: {$server['name']}",
                "BANDWIDTH ALERT\n\n" .
                "Server: {$server['name']}\n" .
                "IP: {$server['ip_address']}\n" .
                "Usage: {$usage['used_gb']} GB / {$usage['limit_gb']} GB ({$usage['percentage']}%)\n" .
                "Status: {$usage['status']}\n\n" .
                "Please take action to prevent service disruption."
            );
        } catch (Exception $e) {
            error_log("Failed to send bandwidth alert: " . $e->getMessage());
        }
    }
    
    /**
     * Reset monthly bandwidth counters (run on 1st of month)
     */
    public static function resetMonthlyCounters() {
        $serversDb = Database::getInstance('servers');
        $serversDb->exec("UPDATE servers SET bandwidth_used = 0, updated_at = CURRENT_TIMESTAMP");
        
        error_log("Monthly bandwidth counters reset at " . date('Y-m-d H:i:s'));
    }
}
