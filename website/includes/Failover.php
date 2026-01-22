<?php
/**
 * Server Failover Handler
 * 
 * PURPOSE: Handle automatic failover when servers go down
 * - Detect server failures
 * - Find backup servers
 * - Migrate devices to backup servers
 * - Notify users of server changes
 * 
 * @created January 23, 2026
 * @version 1.0.0
 */

if (!defined('TRUEVAULT_INIT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

class Failover {
    
    /**
     * Handle server failure - migrate all devices to backup
     * 
     * @param int $failedServerId The server that failed
     * @return array Result of failover operation
     */
    public static function handleServerFailover($failedServerId) {
        $serversDb = Database::getInstance('servers');
        $devicesDb = Database::getInstance('devices');
        
        // Get failed server details
        $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id");
        $stmt->bindValue(':id', $failedServerId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $failedServer = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$failedServer) {
            throw new Exception("Server not found: {$failedServerId}");
        }
        
        // Find backup server
        $backupServer = self::findBackupServer($failedServerId);
        
        if (!$backupServer) {
            // Log critical error - no backup available
            self::logFailoverEvent($failedServerId, null, 'no_backup', 'No backup server available');
            throw new Exception("No backup server available for failover");
        }
        
        // Get all devices on failed server
        $stmt = $devicesDb->prepare("SELECT * FROM devices WHERE server_id = :sid AND status = 'active'");
        $stmt->bindValue(':sid', $failedServerId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $migratedCount = 0;
        $failedCount = 0;
        
        while ($device = $result->fetchArray(SQLITE3_ASSOC)) {
            try {
                self::migrateDeviceToServer($device['id'], $backupServer['id']);
                $migratedCount++;
            } catch (Exception $e) {
                $failedCount++;
                error_log("Failed to migrate device {$device['id']}: " . $e->getMessage());
            }
        }
        
        // Log failover event
        self::logFailoverEvent($failedServerId, $backupServer['id'], 'completed', 
            "Migrated {$migratedCount} devices, {$failedCount} failed");
        
        return [
            'success' => true,
            'failed_server' => $failedServer['name'],
            'backup_server' => $backupServer['name'],
            'migrated' => $migratedCount,
            'failed' => $failedCount
        ];
    }
    
    /**
     * Find a suitable backup server
     * 
     * @param int $excludeId Server ID to exclude (the failed one)
     * @return array|null Backup server or null if none available
     */
    public static function findBackupServer($excludeId) {
        $serversDb = Database::getInstance('servers');
        
        // Find active public servers that aren't the failed one
        // Prioritize by: 1) Same provider, 2) Lowest load, 3) Port forwarding allowed
        $stmt = $serversDb->prepare("
            SELECT * FROM servers 
            WHERE id != :exclude 
            AND status = 'active' 
            AND dedicated_user_email IS NULL
            ORDER BY load_percentage ASC, port_forwarding_allowed DESC
            LIMIT 1
        ");
        $stmt->bindValue(':exclude', $excludeId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    
    /**
     * Migrate a device to a new server
     * 
     * @param int $deviceId Device ID
     * @param int $newServerId New server ID
     * @return bool Success
     */
    public static function migrateDeviceToServer($deviceId, $newServerId) {
        $devicesDb = Database::getInstance('devices');
        $serversDb = Database::getInstance('servers');
        
        // Get device
        $stmt = $devicesDb->prepare("SELECT * FROM devices WHERE id = :id");
        $stmt->bindValue(':id', $deviceId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $device = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$device) {
            throw new Exception("Device not found: {$deviceId}");
        }
        
        // Get new server
        $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id");
        $stmt->bindValue(':id', $newServerId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $newServer = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$newServer) {
            throw new Exception("New server not found: {$newServerId}");
        }
        
        // Remove from old server (if possible)
        if ($device['server_id'] && $device['public_key']) {
            try {
                WireGuard::removePeer($device['server_id'], $device['public_key']);
            } catch (Exception $e) {
                // Old server is likely down, continue anyway
                error_log("Could not remove peer from old server: " . $e->getMessage());
            }
        }
        
        // Add to new server
        if ($device['public_key']) {
            WireGuard::addPeer($newServerId, $device['public_key'], $device['assigned_ip'] . '/32');
        }
        
        // Update device record
        $stmt = $devicesDb->prepare("
            UPDATE devices 
            SET server_id = :sid, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        $stmt->bindValue(':sid', $newServerId, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $deviceId, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Notify user
        self::notifyUserServerChange($device['user_id'], $device, $newServer);
        
        return true;
    }
    
    /**
     * Notify user of server change
     * 
     * @param int $userId User ID
     * @param array $device Device data
     * @param array $newServer New server data
     */
    public static function notifyUserServerChange($userId, $device, $newServer) {
        $usersDb = Database::getInstance('users');
        
        // Get user email
        $stmt = $usersDb->prepare("SELECT email, first_name FROM users WHERE id = :id");
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$user) {
            return;
        }
        
        // Send email notification
        try {
            Email::send(
                $user['email'],
                'TrueVault VPN - Server Migration Notice',
                "Hi {$user['first_name']},\n\n" .
                "Your device '{$device['name']}' has been automatically migrated to our {$newServer['name']} server " .
                "due to maintenance on the previous server.\n\n" .
                "No action is required on your part. Your VPN connection may have been briefly interrupted.\n\n" .
                "If you experience any issues, please reconnect your VPN.\n\n" .
                "Thank you for your patience.\n\n" .
                "TrueVault VPN Team"
            );
        } catch (Exception $e) {
            error_log("Failed to send migration notification: " . $e->getMessage());
        }
    }
    
    /**
     * Log failover event
     */
    private static function logFailoverEvent($fromServerId, $toServerId, $status, $details) {
        $serversDb = Database::getInstance('servers');
        
        // Create failover_log table if not exists
        $serversDb->exec("
            CREATE TABLE IF NOT EXISTS failover_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                from_server_id INTEGER,
                to_server_id INTEGER,
                status TEXT,
                details TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $stmt = $serversDb->prepare("
            INSERT INTO failover_log (from_server_id, to_server_id, status, details)
            VALUES (:from, :to, :status, :details)
        ");
        $stmt->bindValue(':from', $fromServerId, SQLITE3_INTEGER);
        $stmt->bindValue(':to', $toServerId, SQLITE3_INTEGER);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':details', $details, SQLITE3_TEXT);
        $stmt->execute();
    }
}
