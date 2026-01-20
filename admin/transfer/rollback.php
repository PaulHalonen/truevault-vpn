<?php
/**
 * TrueVault VPN - Emergency Rollback System
 * Created: January 19, 2026
 * Purpose: Restore previous owner settings if transfer fails
 * 
 * ROLLBACK RULES:
 * - Available within 24 hours of transfer
 * - Restores all settings from backup
 * - Re-activates previous owner's servers
 * - St. Louis server was already removed during transfer (stays with Kah-Len)
 * - VIP user seige235@yahoo.com rolls back to previous owner
 * 
 * ROLLBACK TIME: < 5 minutes
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST requests allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Database connection
$db_path = __DIR__ . '/../../databases/vpn.db';
$db = new SQLite3($db_path);

define('ROLLBACK_WINDOW_HOURS', 24);

// Helper functions
function getSetting($db, $key) {
    $stmt = $db->prepare("SELECT setting_value FROM business_settings WHERE setting_key = ?");
    $stmt->bindValue(1, $key);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row ? $row['setting_value'] : null;
}

function updateSetting($db, $key, $value) {
    $stmt = $db->prepare("
        UPDATE business_settings 
        SET setting_value = ?, 
            updated_at = datetime('now'),
            updated_by = 'rollback_system'
        WHERE setting_key = ?
    ");
    $stmt->bindValue(1, $value);
    $stmt->bindValue(2, $key);
    return $stmt->execute();
}

function logRollback($db, $action, $details, $status = 'success') {
    $db->exec("
        CREATE TABLE IF NOT EXISTS rollback_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            action TEXT NOT NULL,
            details TEXT,
            status TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $stmt = $db->prepare("
        INSERT INTO rollback_log (action, details, status, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bindValue(1, $action);
    $stmt->bindValue(2, $details);
    $stmt->bindValue(3, $status);
    $stmt->bindValue(4, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return $stmt->execute();
}

function getLatestBackup($db) {
    $tableExists = $db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='transfer_backups'");
    if (!$tableExists) return null;
    
    $result = $db->query("SELECT * FROM transfer_backups WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($row) {
        $row['backup_data'] = json_decode($row['backup_data'], true);
    }
    
    return $row;
}

function isWithinRollbackWindow($db) {
    $transferDate = getSetting($db, 'transfer_date');
    if (empty($transferDate)) return false;
    
    $transferTime = strtotime($transferDate);
    $currentTime = time();
    $hoursSinceTransfer = ($currentTime - $transferTime) / 3600;
    
    return $hoursSinceTransfer <= ROLLBACK_WINDOW_HOURS;
}

function restoreSettings($db, $backupData) {
    if (!isset($backupData['settings'])) return 0;
    
    $restoredCount = 0;
    foreach ($backupData['settings'] as $key => $setting) {
        // Skip transfer-related settings
        if (in_array($key, ['transfer_mode_active', 'transfer_date', 'previous_owner'])) {
            continue;
        }
        
        $stmt = $db->prepare("
            UPDATE business_settings 
            SET setting_value = ?,
                verification_status = ?,
                updated_at = datetime('now'),
                updated_by = 'rollback_system'
            WHERE setting_key = ?
        ");
        $stmt->bindValue(1, $setting['setting_value']);
        $stmt->bindValue(2, $setting['verification_status'] ?? null);
        $stmt->bindValue(3, $key);
        $stmt->execute();
        $restoredCount++;
    }
    
    return $restoredCount;
}

function restoreServers($db, $backupData) {
    if (!isset($backupData['servers'])) return 0;
    
    $restoredCount = 0;
    foreach ($backupData['servers'] as $server) {
        // Re-insert or update server
        $stmt = $db->prepare("
            INSERT OR REPLACE INTO servers 
            (id, name, ip_address, location, is_active, is_visible, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, 1, ?, datetime('now'))
        ");
        $stmt->bindValue(1, $server['id']);
        $stmt->bindValue(2, $server['name']);
        $stmt->bindValue(3, $server['ip_address']);
        $stmt->bindValue(4, $server['location']);
        $stmt->bindValue(5, $server['created_at']);
        $stmt->execute();
        $restoredCount++;
    }
    
    // Deactivate new owner's servers
    $db->exec("UPDATE servers SET is_active = 0 WHERE is_new_owner = 1");
    
    return $restoredCount;
}

// Action router
switch ($input['action']) {
    
    case 'rollback':
        $reason = $input['reason'] ?? 'No reason provided';
        
        // Check rollback window
        if (!isWithinRollbackWindow($db)) {
            echo json_encode([
                'success' => false,
                'error' => 'Rollback window expired. Rollback only allowed within ' . ROLLBACK_WINDOW_HOURS . ' hours of transfer.'
            ]);
            exit;
        }
        
        // Get backup
        $backup = getLatestBackup($db);
        if (!$backup || empty($backup['backup_data'])) {
            echo json_encode([
                'success' => false,
                'error' => 'No backup found to restore from'
            ]);
            exit;
        }
        
        logRollback($db, 'rollback_started', "Reason: $reason", 'in_progress');
        
        try {
            // Store current owner info
            $currentOwner = getSetting($db, 'owner_name');
            
            // Restore settings
            $settingsRestored = restoreSettings($db, $backup['backup_data']);
            logRollback($db, 'settings_restored', "Restored $settingsRestored settings");
            
            // Restore servers (Note: St. Louis was removed during transfer, won't be restored)
            $serversRestored = restoreServers($db, $backup['backup_data']);
            logRollback($db, 'servers_restored', "Restored $serversRestored servers");
            
            // Clear transfer status
            updateSetting($db, 'transfer_mode_active', '0');
            
            // Mark backup as used
            $stmt = $db->prepare("UPDATE transfer_backups SET is_active = 0 WHERE id = ?");
            $stmt->bindValue(1, $backup['id']);
            $stmt->execute();
            
            logRollback($db, 'rollback_complete', json_encode([
                'reason' => $reason,
                'settings_restored' => $settingsRestored,
                'servers_restored' => $serversRestored,
                'rolled_back_from' => $currentOwner,
                'rolled_back_to' => getSetting($db, 'owner_name')
            ]), 'success');
            
            echo json_encode([
                'success' => true,
                'message' => 'Rollback completed successfully',
                'settings_restored' => $settingsRestored,
                'servers_restored' => $serversRestored,
                'notes' => [
                    'St. Louis server (144.126.133.253) was NOT restored - it was never part of the transfer',
                    'VIP user seige235@yahoo.com is now back under original ownership',
                    'PayPal webhook may need manual reconfiguration'
                ]
            ]);
            
        } catch (Exception $e) {
            logRollback($db, 'rollback_error', $e->getMessage(), 'failed');
            echo json_encode([
                'success' => false,
                'error' => 'Rollback failed: ' . $e->getMessage()
            ]);
        }
        break;
    
    case 'check_rollback_available':
        $canRollback = isWithinRollbackWindow($db);
        $backup = getLatestBackup($db);
        $transferDate = getSetting($db, 'transfer_date');
        
        $hoursRemaining = 0;
        if (!empty($transferDate)) {
            $transferTime = strtotime($transferDate);
            $hoursSinceTransfer = (time() - $transferTime) / 3600;
            $hoursRemaining = max(0, ROLLBACK_WINDOW_HOURS - $hoursSinceTransfer);
        }
        
        echo json_encode([
            'success' => true,
            'can_rollback' => $canRollback && !empty($backup),
            'has_backup' => !empty($backup),
            'transfer_date' => $transferDate,
            'hours_remaining' => round($hoursRemaining, 1),
            'rollback_window_hours' => ROLLBACK_WINDOW_HOURS
        ]);
        break;
    
    case 'get_rollback_log':
        $logs = [];
        $tableExists = $db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='rollback_log'");
        
        if ($tableExists) {
            $result = $db->query("SELECT * FROM rollback_log ORDER BY created_at DESC LIMIT 50");
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $logs[] = $row;
            }
        }
        
        echo json_encode([
            'success' => true,
            'logs' => $logs
        ]);
        break;
    
    case 'get_backup_info':
        $backup = getLatestBackup($db);
        
        if ($backup) {
            echo json_encode([
                'success' => true,
                'backup' => [
                    'id' => $backup['id'],
                    'created_at' => $backup['created_at'],
                    'settings_count' => count($backup['backup_data']['settings'] ?? []),
                    'servers_count' => count($backup['backup_data']['servers'] ?? []),
                    'previous_owner' => $backup['backup_data']['previous_owner'] ?? 'Unknown'
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No backup found'
            ]);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $input['action']]);
}
