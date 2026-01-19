<?php
/**
 * TrueVault VPN - Integration Helper
 * 
 * PURPOSE: Helper functions to integrate features across the system
 * FUNCTIONS:
 * - Send emails on events
 * - Log activities
 * - VIP auto-assignment
 * - Email notifications
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Must be included, not accessed directly
if (!defined('TRUEVAULT_INIT')) {
    die('Direct access not permitted');
}

class Integration {
    
    /**
     * Log user activity
     * 
     * @param int $userId User ID
     * @param string $action Action performed
     * @param string $details Action details
     */
    public static function logActivity($userId, $action, $details = '') {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection('users');
            
            // Check if table exists
            $tableCheck = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='activity_logs'");
            if (!$tableCheck->fetch()) {
                // Create table if it doesn't exist
                $conn->exec("
                    CREATE TABLE activity_logs (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        action TEXT NOT NULL,
                        details TEXT,
                        ip_address TEXT,
                        user_agent TEXT,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                $conn->exec("CREATE INDEX idx_activity_user_id ON activity_logs(user_id)");
                $conn->exec("CREATE INDEX idx_activity_created ON activity_logs(created_at)");
            }
            
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $action,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
        } catch (Exception $e) {
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Assign VIP user to dedicated server
     * 
     * @param int $userId User ID
     * @return bool Success
     */
    public static function assignVIPDedicatedServer($userId) {
        try {
            $db = Database::getInstance();
            
            // Get user email
            $usersConn = $db->getConnection('users');
            $stmt = $usersConn->prepare("SELECT email FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false;
            }
            
            // Check if user is seige235@yahoo.com (gets St. Louis dedicated server)
            if ($user['email'] === 'seige235@yahoo.com') {
                // Get St. Louis server
                $serversConn = $db->getConnection('servers');
                $stmt = $serversConn->prepare("
                    SELECT server_id 
                    FROM servers 
                    WHERE location LIKE '%St. Louis%' OR location LIKE '%St Louis%'
                    AND status = 'active'
                    LIMIT 1
                ");
                $stmt->execute();
                $server = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($server) {
                    // Update all user's devices to use this server
                    $devicesConn = $db->getConnection('devices');
                    $stmt = $devicesConn->prepare("
                        UPDATE devices
                        SET current_server_id = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$server['server_id'], $userId]);
                    
                    self::logActivity($userId, 'vip_dedicated_server_assigned', 'St. Louis dedicated server');
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("VIP server assignment failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send payment notification emails
     * 
     * @param string $email User email
     * @param string $firstName User first name
     * @param string $amount Payment amount
     * @param string $plan Plan name
     * @param string $type 'success' or 'failed'
     */
    public static function sendPaymentEmail($email, $firstName, $amount, $plan, $type = 'success') {
        try {
            require_once __DIR__ . '/Email.php';
            $emailService = new Email();
            
            if ($type === 'success') {
                $emailService->sendPaymentReceipt($email, $firstName, $amount, $plan);
            } else {
                $emailService->sendPaymentFailed($email, $firstName, $amount);
            }
            
        } catch (Exception $e) {
            error_log("Payment email failed: " . $e->getMessage());
        }
    }
    
    /**
     * Send VIP upgrade notification
     * 
     * @param string $email User email
     * @param string $firstName User first name
     */
    public static function sendVIPUpgrade($email, $firstName) {
        try {
            require_once __DIR__ . '/Email.php';
            $emailService = new Email();
            $emailService->sendVIPUpgrade($email, $firstName);
            
        } catch (Exception $e) {
            error_log("VIP upgrade email failed: " . $e->getMessage());
        }
    }
    
    /**
     * Send service suspension notification
     * 
     * @param string $email User email
     * @param string $firstName User first name
     */
    public static function sendSuspension($email, $firstName) {
        try {
            require_once __DIR__ . '/Email.php';
            $emailService = new Email();
            $emailService->sendSuspension($email, $firstName);
            
        } catch (Exception $e) {
            error_log("Suspension email failed: " . $e->getMessage());
        }
    }
    
    /**
     * Check device limit for user
     * 
     * @param int $userId User ID
     * @param string $tier User tier
     * @return array ['reached' => bool, 'current' => int, 'limit' => int]
     */
    public static function checkDeviceLimit($userId, $tier) {
        $limits = [
            'standard' => 3,
            'pro' => 5,
            'vip' => 999,
            'admin' => 999
        ];
        
        $limit = $limits[$tier] ?? 3;
        
        try {
            $db = Database::getInstance();
            $devicesConn = $db->getConnection('devices');
            
            $stmt = $devicesConn->prepare("
                SELECT COUNT(*) as count
                FROM devices
                WHERE user_id = ? AND status = 'active'
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $current = $result['count'];
            
            return [
                'reached' => $current >= $limit,
                'current' => $current,
                'limit' => $limit
            ];
            
        } catch (Exception $e) {
            error_log("Device limit check failed: " . $e->getMessage());
            return ['reached' => false, 'current' => 0, 'limit' => $limit];
        }
    }
    
    /**
     * Send device limit reached notification
     * 
     * @param string $email User email
     * @param string $firstName User first name
     * @param string $plan Current plan
     * @param int $limit Device limit
     */
    public static function sendDeviceLimitEmail($email, $firstName, $plan, $limit) {
        try {
            require_once __DIR__ . '/Email.php';
            $emailService = new Email();
            $emailService->sendDeviceLimitReached($email, $firstName, $plan, $limit);
            
        } catch (Exception $e) {
            error_log("Device limit email failed: " . $e->getMessage());
        }
    }
}
