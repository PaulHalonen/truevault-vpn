<?php
/**
 * TrueVault VPN - Server Management Class
 * 
 * Handles all server operations:
 * - Server listing and selection
 * - Health monitoring
 * - Load balancing
 * - VIP server assignment
 * - Bandwidth tracking
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

class ServerManager {
    private static $db = null;
    
    private static function getDB() {
        if (self::$db === null) {
            require_once __DIR__ . '/Database.php';
            $database = Database::getInstance();
            self::$db = $database->getConnection('servers');
        }
        return self::$db;
    }
    
    /**
     * Get all active servers
     * @param bool $includeHidden Include VIP-only servers
     * @return array List of servers
     */
    public static function getAllServers($includeHidden = false) {
        $db = self::getDB();
        
        $sql = "SELECT * FROM servers WHERE is_active = 1";
        if (!$includeHidden) {
            $sql .= " AND is_visible = 1";
        }
        $sql .= " ORDER BY location";
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get server by ID
     * @param int $serverId Server ID
     * @return array|false Server data or false
     */
    public static function getServerById($serverId) {
        $db = self::getDB();
        
        $stmt = $db->prepare("SELECT * FROM servers WHERE id = ?");
        $stmt->execute([$serverId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get best server for user
     * Considers: VIP status, load balancing, location
     * 
     * @param string $userEmail User's email
     * @param bool $isVIP Is user VIP
     * @return array|false Server data
     */
    public static function getBestServerForUser($userEmail, $isVIP = false) {
        $db = self::getDB();
        
        // Check if user has dedicated VIP server
        if ($isVIP) {
            $stmt = $db->prepare("
                SELECT * FROM servers 
                WHERE access_level = 'vip' 
                AND vip_email = ? 
                AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$userEmail]);
            $vipServer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($vipServer) {
                return $vipServer;
            }
        }
        
        // Get least loaded public server
        $stmt = $db->query("
            SELECT * FROM servers 
            WHERE access_level = 'public' 
            AND is_active = 1 
            AND is_visible = 1
            AND current_users < max_users
            ORDER BY (current_users * 1.0 / max_users) ASC, uptime_percentage DESC
            LIMIT 1
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get server by location
     * @param string $location Location name or country code
     * @return array|false Server data
     */
    public static function getServerByLocation($location) {
        $db = self::getDB();
        
        $stmt = $db->prepare("
            SELECT * FROM servers 
            WHERE (location LIKE ? OR country_code = ?)
            AND is_active = 1 
            AND is_visible = 1
            LIMIT 1
        ");
        $stmt->execute(["%{$location}%", $location]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Increment user count for server
     * @param int $serverId Server ID
     * @return bool Success
     */
    public static function incrementUserCount($serverId) {
        $db = self::getDB();
        
        $stmt = $db->prepare("
            UPDATE servers 
            SET current_users = current_users + 1,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$serverId]);
    }
    
    /**
     * Decrement user count for server
     * @param int $serverId Server ID
     * @return bool Success
     */
    public static function decrementUserCount($serverId) {
        $db = self::getDB();
        
        $stmt = $db->prepare("
            UPDATE servers 
            SET current_users = CASE 
                WHEN current_users > 0 THEN current_users - 1 
                ELSE 0 
            END,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$serverId]);
    }
    
    /**
     * Update server health status
     * @param int $serverId Server ID
     * @param string $status Status: online, offline, degraded
     * @param int $responseTime Response time in ms
     * @param string $details Additional details
     * @return bool Success
     */
    public static function updateHealthStatus($serverId, $status, $responseTime = null, $details = null) {
        $db = self::getDB();
        
        // Update server health
        $stmt = $db->prepare("
            UPDATE servers 
            SET health_status = ?,
                last_health_check = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$status, $serverId]);
        
        // Log health check
        $stmt = $db->prepare("
            INSERT INTO server_logs (server_id, check_type, status, response_time, details)
            VALUES (?, 'health_check', ?, ?, ?)
        ");
        $stmt->execute([$serverId, $status, $responseTime, $details]);
        
        return true;
    }
    
    /**
     * Get server health history
     * @param int $serverId Server ID
     * @param int $limit Number of records
     * @return array Health check logs
     */
    public static function getHealthHistory($serverId, $limit = 100) {
        $db = self::getDB();
        
        $stmt = $db->prepare("
            SELECT * FROM server_logs 
            WHERE server_id = ? 
            AND check_type = 'health_check'
            ORDER BY checked_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$serverId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Record bandwidth usage
     * @param int $serverId Server ID
     * @param int $bytesSent Bytes sent
     * @param int $bytesReceived Bytes received
     * @return bool Success
     */
    public static function recordBandwidth($serverId, $bytesSent, $bytesReceived) {
        $db = self::getDB();
        
        $today = date('Y-m-d');
        $totalBytes = $bytesSent + $bytesReceived;
        
        // Upsert bandwidth record
        $stmt = $db->prepare("
            INSERT INTO server_bandwidth (server_id, date, bytes_sent, bytes_received, total_bytes)
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT(server_id, date) DO UPDATE SET
                bytes_sent = bytes_sent + excluded.bytes_sent,
                bytes_received = bytes_received + excluded.bytes_received,
                total_bytes = total_bytes + excluded.total_bytes
        ");
        $stmt->execute([$serverId, $today, $bytesSent, $bytesReceived, $totalBytes]);
        
        // Update server total
        $stmt = $db->prepare("
            UPDATE servers 
            SET bandwidth_used = bandwidth_used + ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$totalBytes, $serverId]);
    }
    
    /**
     * Get bandwidth usage for server
     * @param int $serverId Server ID
     * @param int $days Number of days to retrieve
     * @return array Bandwidth data
     */
    public static function getBandwidthUsage($serverId, $days = 30) {
        $db = self::getDB();
        
        $stmt = $db->prepare("
            SELECT * FROM server_bandwidth 
            WHERE server_id = ? 
            AND date >= date('now', '-' || ? || ' days')
            ORDER BY date DESC
        ");
        $stmt->execute([$serverId, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get server statistics
     * @param int $serverId Server ID
     * @return array Statistics
     */
    public static function getServerStats($serverId) {
        $db = self::getDB();
        
        $server = self::getServerById($serverId);
        if (!$server) return false;
        
        // Get health check success rate (last 100 checks)
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_checks,
                SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as successful_checks
            FROM server_logs 
            WHERE server_id = ? 
            AND check_type = 'health_check'
            ORDER BY checked_at DESC 
            LIMIT 100
        ");
        $stmt->execute([$serverId]);
        $healthStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get bandwidth usage (last 30 days)
        $stmt = $db->prepare("
            SELECT SUM(total_bytes) as total_bandwidth
            FROM server_bandwidth 
            WHERE server_id = ? 
            AND date >= date('now', '-30 days')
        ");
        $stmt->execute([$serverId]);
        $bandwidthStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'server' => $server,
            'health' => $healthStats,
            'bandwidth_30d' => $bandwidthStats['total_bandwidth'] ?? 0,
            'load_percentage' => $server['max_users'] > 0 
                ? round(($server['current_users'] / $server['max_users']) * 100, 1) 
                : 0
        ];
    }
    
    /**
     * Get all servers with statistics
     * @return array Servers with stats
     */
    public static function getAllServersWithStats() {
        $servers = self::getAllServers(true); // Include hidden
        $result = [];
        
        foreach ($servers as $server) {
            $result[] = self::getServerStats($server['id']);
        }
        
        return $result;
    }
    
    /**
     * Check if server is available for new connections
     * @param int $serverId Server ID
     * @return bool Available
     */
    public static function isServerAvailable($serverId) {
        $server = self::getServerById($serverId);
        
        if (!$server) return false;
        
        return $server['is_active'] == 1 
            && $server['health_status'] === 'online'
            && $server['current_users'] < $server['max_users'];
    }
}
?>
