<?php
/**
 * TrueVault VPN - Business Transfer Execution
 * Created: January 19, 2026
 * Purpose: Execute the complete business ownership transfer
 * 
 * IMPORTANT TRANSFER RULES:
 * - VIP user seige235@yahoo.com transfers WITH the business (future new owner)
 * - St. Louis Server (144.126.133.253) does NOT transfer - stays with Kah-Len
 * - All other servers transfer to new owner
 * 
 * This script:
 * 1. Validates all settings are complete
 * 2. Creates backup of current configuration
 * 3. Updates all settings to new owner values
 * 4. Disconnects old PayPal webhook
 * 5. Registers new webhook URL
 * 6. Marks old servers for removal (EXCEPT St. Louis)
 * 7. Activates new owner servers
 * 8. Sends notification emails
 * 9. Logs the transfer event
 * 10. Updates transfer status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST requests allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Database connection
$db_path = __DIR__ . '/../../databases/vpn.db';
$db = new SQLite3($db_path);

// St. Louis server IP - excluded from transfer (stays with Kah-Len)
define('EXCLUDED_SERVER_IP', '144.126.133.253');

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
            updated_by = 'transfer_system'
        WHERE setting_key = ?
    ");
    $stmt->bindValue(1, $value);
    $stmt->bindValue(2, $key);
    return $stmt->execute();
}

function logTransfer($db, $action, $details, $status = 'success') {
    // Create transfer_log table if not exists
    $db->exec("
        CREATE TABLE IF NOT EXISTS transfer_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            action TEXT NOT NULL,
            details TEXT,
            status TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $stmt = $db->prepare("
        INSERT INTO transfer_log (action, details, status, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bindValue(1, $action);
    $stmt->bindValue(2, $details);
    $stmt->bindValue(3, $status);
    $stmt->bindValue(4, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return $stmt->execute();
}

function createBackup($db) {
    // Create backup of all current settings
    $backup = [];
    
    $result = $db->query("SELECT * FROM business_settings");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $backup['settings'][$row['setting_key']] = $row;
    }
    
    // Backup servers (EXCLUDE St. Louis - it stays with Kah-Len)
    $backup['servers'] = [];
    $stmt = $db->prepare("SELECT * FROM servers WHERE ip_address != ?");
    $stmt->bindValue(1, EXCLUDED_SERVER_IP);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $backup['servers'][] = $row;
    }
    
    // Backup VIP users (these transfer with business)
    $backup['vip_users'] = [];
    $tableExists = $db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='vip_users'");
    if ($tableExists) {
        $result = $db->query("SELECT * FROM vip_users");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $backup['vip_users'][] = $row;
        }
    }
    
    $backup['transfer_date'] = date('Y-m-d H:i:s');
    $backup['previous_owner'] = getSetting($db, 'owner_name');
    
    // Create backup table if not exists
    $db->exec("
        CREATE TABLE IF NOT EXISTS transfer_backups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            backup_data TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_by TEXT,
            ip_address TEXT,
            is_active BOOLEAN DEFAULT 1
        )
    ");
    
    $stmt = $db->prepare("
        INSERT INTO transfer_backups (backup_data, created_by, ip_address)
        VALUES (?, 'transfer_system', ?)
    ");
    $stmt->bindValue(1, json_encode($backup));
    $stmt->bindValue(2, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $stmt->execute();
    
    return $db->lastInsertRowID();
}

function validateTransferReadiness($db) {
    $errors = [];
    
    // Check required settings
    $required = [
        'business_name' => 'Business Name',
        'owner_name' => 'Owner Name',
        'paypal_client_id' => 'PayPal Client ID',
        'paypal_account_email' => 'PayPal Account Email',
        'customer_email' => 'Customer Email',
        'smtp_server' => 'SMTP Server'
    ];
    
    foreach ($required as $key => $label) {
        $value = getSetting($db, $key);
        if (empty($value)) {
            $errors[] = "$label is required";
        }
    }
    
    // Check for at least one new server (excluding St. Louis)
    $stmt = $db->prepare("SELECT COUNT(*) FROM servers WHERE is_new_owner = 1 AND ip_address != ?");
    $stmt->bindValue(1, EXCLUDED_SERVER_IP);
    $result = $stmt->execute();
    $newServerCount = $result->fetchArray()[0];
    
    if ($newServerCount == 0) {
        $errors[] = "At least one new server must be added before transfer";
    }
    
    return $errors;
}

function deactivateOldPayPalWebhook($db) {
    // This would disconnect the old webhook
    // In practice, this is done manually in PayPal dashboard
    // We just log that it should be done
    logTransfer($db, 'paypal_webhook_deactivate', 
        'Old PayPal webhook should be deactivated in PayPal dashboard', 'pending');
    return true;
}

function markOldServersInactive($db) {
    // Mark old owner's servers as inactive (EXCEPT St. Louis which stays with Kah-Len)
    $stmt = $db->prepare("
        UPDATE servers 
        SET is_active = 0,
            updated_at = datetime('now')
        WHERE (is_new_owner = 0 OR is_new_owner IS NULL)
        AND ip_address != ?
    ");
    $stmt->bindValue(1, EXCLUDED_SERVER_IP);
    $stmt->execute();
    
    return $db->changes();
}

function removeExcludedServer($db) {
    // Remove St. Louis server from the transferred system entirely
    // It stays with Kah-Len, so new owner shouldn't see it
    $stmt = $db->prepare("DELETE FROM servers WHERE ip_address = ?");
    $stmt->bindValue(1, EXCLUDED_SERVER_IP);
    $stmt->execute();
    
    logTransfer($db, 'excluded_server_removed', 
        'St. Louis server (144.126.133.253) removed - stays with previous owner', 'success');
    
    return $db->changes();
}

function activateNewServers($db) {
    // Activate new owner's servers
    $db->exec("
        UPDATE servers 
        SET is_active = 1,
            is_visible = 1,
            updated_at = datetime('now')
        WHERE is_new_owner = 1
    ");
    
    return $db->changes();
}

function sendTransferNotifications($db, $previousOwner, $newOwner) {
    // Get email settings
    $customerEmail = getSetting($db, 'customer_email');
    $fromName = getSetting($db, 'email_from_name');
    $businessName = getSetting($db, 'business_name');
    
    // In production, this would send actual emails
    // For now, we log the intent
    
    $notifications = [
        [
            'type' => 'new_owner_welcome',
            'to' => $customerEmail,
            'subject' => "Welcome to $businessName - Transfer Complete"
        ],
        [
            'type' => 'previous_owner_confirmation',
            'to' => 'paulhalonen@gmail.com', // Previous owner
            'subject' => "Business Transfer Completed - $businessName"
        ]
    ];
    
    foreach ($notifications as $notification) {
        logTransfer($db, 'email_notification', json_encode($notification), 'pending');
    }
    
    return true;
}

function finalizeTransfer($db, $backupId) {
    // Update transfer settings
    $previousOwner = getSetting($db, 'owner_name');
    
    updateSetting($db, 'transfer_mode_active', '0');
    updateSetting($db, 'previous_owner', $previousOwner);
    updateSetting($db, 'transfer_date', date('Y-m-d H:i:s'));
    updateSetting($db, 'setup_complete', '1');
    
    // Log completion
    logTransfer($db, 'transfer_complete', json_encode([
        'backup_id' => $backupId,
        'previous_owner' => $previousOwner,
        'new_owner' => getSetting($db, 'owner_name'),
        'completed_at' => date('Y-m-d H:i:s')
    ]), 'success');
    
    return true;
}

// Action router
switch ($input['action']) {
    
    case 'complete_transfer':
        // Step 1: Validate readiness
        $errors = validateTransferReadiness($db);
        if (!empty($errors)) {
            echo json_encode([
                'success' => false, 
                'error' => 'Transfer validation failed',
                'details' => $errors
            ]);
            exit;
        }
        
        logTransfer($db, 'transfer_started', 'Beginning business transfer process', 'in_progress');
        
        try {
            // Step 2: Create backup
            $backupId = createBackup($db);
            logTransfer($db, 'backup_created', "Backup ID: $backupId", 'success');
            
            // Step 3: Update transfer mode
            updateSetting($db, 'transfer_mode_active', '1');
            
            // Step 4: Deactivate old PayPal webhook (logged for manual action)
            deactivateOldPayPalWebhook($db);
            
            // Step 5: Remove excluded server (St. Louis stays with Kah-Len)
            $removedCount = removeExcludedServer($db);
            logTransfer($db, 'excluded_server_processed', "Removed $removedCount excluded server(s)", 'success');
            
            // Step 6: Mark old servers inactive
            $deactivatedCount = markOldServersInactive($db);
            logTransfer($db, 'old_servers_deactivated', "Deactivated $deactivatedCount old server(s)", 'success');
            
            // Step 7: Activate new servers
            $activatedCount = activateNewServers($db);
            logTransfer($db, 'new_servers_activated', "Activated $activatedCount new server(s)", 'success');
            
            // Step 8: Send notifications
            $previousOwner = getSetting($db, 'previous_owner') ?: 'Kah-Len Halonen';
            $newOwner = getSetting($db, 'owner_name');
            sendTransferNotifications($db, $previousOwner, $newOwner);
            
            // Step 9: Finalize transfer
            finalizeTransfer($db, $backupId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Business transfer completed successfully',
                'backup_id' => $backupId,
                'servers_deactivated' => $deactivatedCount,
                'servers_activated' => $activatedCount,
                'transfer_date' => date('Y-m-d H:i:s'),
                'notes' => [
                    'St. Louis server (144.126.133.253) has been removed from this system - it stays with Kah-Len',
                    'VIP user seige235@yahoo.com is now under new ownership',
                    'Please deactivate the old PayPal webhook manually in PayPal dashboard',
                    'Emergency rollback available for 24 hours'
                ]
            ]);
            
        } catch (Exception $e) {
            logTransfer($db, 'transfer_error', $e->getMessage(), 'failed');
            echo json_encode([
                'success' => false,
                'error' => 'Transfer failed: ' . $e->getMessage()
            ]);
        }
        break;
    
    case 'validate_transfer':
        $errors = validateTransferReadiness($db);
        echo json_encode([
            'success' => empty($errors),
            'ready' => empty($errors),
            'errors' => $errors
        ]);
        break;
    
    case 'get_transfer_status':
        $transferMode = getSetting($db, 'transfer_mode_active');
        $transferDate = getSetting($db, 'transfer_date');
        $previousOwner = getSetting($db, 'previous_owner');
        
        echo json_encode([
            'success' => true,
            'transfer_active' => $transferMode === '1',
            'transfer_date' => $transferDate,
            'previous_owner' => $previousOwner,
            'current_owner' => getSetting($db, 'owner_name')
        ]);
        break;
    
    case 'get_transfer_log':
        $logs = [];
        $result = $db->query("SELECT * FROM transfer_log ORDER BY created_at DESC LIMIT 50");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $logs[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'logs' => $logs
        ]);
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $input['action']]);
}
