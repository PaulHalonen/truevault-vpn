<?php
/**
 * TrueVault VPN - Automatic Server Provisioning
 * 
 * This script orchestrates the entire provisioning process:
 * 1. Parses Contabo email for server details
 * 2. Changes server password to Andassi8
 * 3. Installs WireGuard on the server
 * 4. Generates client configuration
 * 5. Emails .conf file to customer
 * 6. Updates customer dashboard
 * 
 * Usage: Can be called manually or via webhook
 */

// Configuration
define('DB_PATH', __DIR__ . '/../../vpn.db');
define('SCRIPTS_PATH', __DIR__ . '/../../server-scripts');
define('TARGET_PASSWORD', 'Andassi8');
define('ADMIN_EMAIL', 'paulhalonen@gmail.com');

/**
 * Parse Contabo email to extract server details
 */
function parseContaboEmail($emailContent) {
    $result = [
        'success' => false,
        'ip' => null,
        'location' => null,
        'ipv6' => null,
        'temp_password' => null
    ];
    
    // Extract IP address
    if (preg_match('/(\d+\.\d+\.\d+\.\d+)\s+Cloud VPS/', $emailContent, $matches)) {
        $result['ip'] = $matches[1];
    }
    
    // Extract location
    if (preg_match('/Location.*?(New York|St\. Louis|Dallas|Toronto|US-east|US-central|US-west)/i', $emailContent, $matches)) {
        $result['location'] = $matches[1];
    }
    
    // Extract IPv6
    if (preg_match('/([\da-f:]+\/64)/i', $emailContent, $matches)) {
        $result['ipv6'] = $matches[1];
    }
    
    // Password note - Contabo says "as chosen by you during order process"
    // So we need to get this from the order form or email notification
    $result['temp_password'] = null; // Will need to be provided separately
    
    $result['success'] = !empty($result['ip']) && !empty($result['location']);
    
    return $result;
}

/**
 * Change server root password via SSH
 */
function changeServerPassword($serverIp, $tempPassword) {
    $pythonScript = __DIR__ . '/change-server-password.py';
    
    if (!file_exists($pythonScript)) {
        return ['success' => false, 'message' => 'Python script not found'];
    }
    
    $command = sprintf(
        'python3 %s %s %s 2>&1',
        escapeshellarg($pythonScript),
        escapeshellarg($serverIp),
        escapeshellarg($tempPassword)
    );
    
    exec($command, $output, $returnCode);
    
    return [
        'success' => ($returnCode === 0),
        'message' => implode("\n", $output)
    ];
}

/**
 * Upload and execute script on remote server
 */
function executeRemoteScript($serverIp, $localScriptPath, $args = []) {
    $connection = ssh2_connect($serverIp, 22);
    
    if (!$connection) {
        return ['success' => false, 'message' => 'SSH connection failed'];
    }
    
    if (!ssh2_auth_password($connection, 'root', TARGET_PASSWORD)) {
        return ['success' => false, 'message' => 'SSH authentication failed'];
    }
    
    // Create remote path
    $scriptName = basename($localScriptPath);
    $remotePath = '/root/' . $scriptName;
    
    // Upload script
    if (!ssh2_scp_send($connection, $localScriptPath, $remotePath, 0755)) {
        return ['success' => false, 'message' => 'Failed to upload script'];
    }
    
    // Execute script
    $command = $remotePath;
    if (!empty($args)) {
        $command .= ' ' . implode(' ', array_map('escapeshellarg', $args));
    }
    
    $stream = ssh2_exec($connection, $command);
    $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    
    stream_set_blocking($stream, true);
    stream_set_blocking($errorStream, true);
    
    $output = stream_get_contents($stream);
    $error = stream_get_contents($errorStream);
    
    fclose($stream);
    fclose($errorStream);
    
    return [
        'success' => true,
        'output' => $output,
        'error' => $error
    ];
}

/**
 * Install WireGuard on server
 */
function installWireGuard($serverIp) {
    $installScript = SCRIPTS_PATH . '/install-wireguard.sh';
    
    if (!file_exists($installScript)) {
        return ['success' => false, 'message' => 'Install script not found'];
    }
    
    return executeRemoteScript($serverIp, $installScript);
}

/**
 * Create client configuration
 */
function createClientConfig($serverIp, $customerId, $customerEmail) {
    $configScript = SCRIPTS_PATH . '/create-client-config.sh';
    
    if (!file_exists($configScript)) {
        return ['success' => false, 'message' => 'Config script not found'];
    }
    
    $result = executeRemoteScript($serverIp, $configScript, [$customerId, $customerEmail]);
    
    if ($result['success']) {
        // Extract .conf file from output
        $result['conf_file'] = $result['output'];
    }
    
    return $result;
}

/**
 * Email .conf file to customer
 */
function emailConfigToCustomer($customerEmail, $customerId, $confContent) {
    $to = $customerEmail;
    $subject = "Your TrueVault VPN Configuration";
    
    $message = "Hello,\n\n";
    $message .= "Your dedicated VPN server is now ready!\n\n";
    $message .= "SETUP INSTRUCTIONS:\n";
    $message .= "1. Download the WireGuard app:\n";
    $message .= "   - Windows/Mac: https://www.wireguard.com/install/\n";
    $message .= "   - iOS: https://apps.apple.com/us/app/wireguard/id1441195209\n";
    $message .= "   - Android: https://play.google.com/store/apps/details?id=com.wireguard.android\n\n";
    $message .= "2. Open the app and click 'Add Tunnel' or 'Import from file'\n\n";
    $message .= "3. Your configuration is attached to this email\n\n";
    $message .= "Need help? Visit your dashboard: https://vpn.the-truth-publishing.com/dashboard\n\n";
    $message .= "Best regards,\n";
    $message .= "TrueVault VPN Team\n";
    
    $headers = "From: TrueVault VPN <noreply@vpn.the-truth-publishing.com>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"boundary\"\r\n";
    
    $body = "--boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $body .= $message . "\r\n";
    $body .= "--boundary\r\n";
    $body .= "Content-Type: text/plain; name=\"wireguard-config.conf\"\r\n";
    $body .= "Content-Disposition: attachment; filename=\"truthvault-vpn.conf\"\r\n\r\n";
    $body .= $confContent . "\r\n";
    $body .= "--boundary--";
    
    return mail($to, $subject, $body, $headers);
}

/**
 * Update database with server info
 */
function updateDatabase($customerId, $serverIp, $location, $confFile) {
    try {
        $db = new SQLite3(DB_PATH);
        
        // Update customer record
        $stmt = $db->prepare('
            UPDATE customers 
            SET server_ip = :ip, 
                server_location = :location,
                vpn_config = :config,
                server_status = "online",
                provisioned_at = CURRENT_TIMESTAMP
            WHERE id = :customer_id
        ');
        
        $stmt->bindValue(':ip', $serverIp, SQLITE3_TEXT);
        $stmt->bindValue(':location', $location, SQLITE3_TEXT);
        $stmt->bindValue(':config', $confFile, SQLITE3_TEXT);
        $stmt->bindValue(':customer_id', $customerId, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        $db->close();
        
        return true;
        
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

/**
 * Main provisioning workflow
 */
function provisionServer($customerId, $customerEmail, $serverIp, $tempPassword, $location) {
    $log = [];
    $log[] = "Starting provisioning for customer $customerId ($customerEmail)";
    $log[] = "Server: $serverIp ($location)";
    
    // Step 1: Change password
    $log[] = "\n--- Step 1: Changing server password ---";
    $passwordResult = changeServerPassword($serverIp, $tempPassword);
    if (!$passwordResult['success']) {
        $log[] = "ERROR: " . $passwordResult['message'];
        return ['success' => false, 'log' => $log];
    }
    $log[] = "✅ Password changed successfully";
    
    // Wait for server to be ready
    sleep(5);
    
    // Step 2: Install WireGuard
    $log[] = "\n--- Step 2: Installing WireGuard ---";
    $installResult = installWireGuard($serverIp);
    if (!$installResult['success']) {
        $log[] = "ERROR: " . $installResult['message'];
        return ['success' => false, 'log' => $log];
    }
    $log[] = "✅ WireGuard installed successfully";
    
    // Wait for WireGuard to start
    sleep(3);
    
    // Step 3: Create client config
    $log[] = "\n--- Step 3: Creating client configuration ---";
    $configResult = createClientConfig($serverIp, $customerId, $customerEmail);
    if (!$configResult['success']) {
        $log[] = "ERROR: " . $configResult['message'];
        return ['success' => false, 'log' => $log];
    }
    $log[] = "✅ Client configuration created";
    
    $confFile = $configResult['conf_file'];
    
    // Step 4: Email config to customer
    $log[] = "\n--- Step 4: Emailing configuration ---";
    if (emailConfigToCustomer($customerEmail, $customerId, $confFile)) {
        $log[] = "✅ Configuration emailed to $customerEmail";
    } else {
        $log[] = "⚠️  Failed to email configuration (but config was created)";
    }
    
    // Step 5: Update database
    $log[] = "\n--- Step 5: Updating database ---";
    if (updateDatabase($customerId, $serverIp, $location, $confFile)) {
        $log[] = "✅ Database updated";
    } else {
        $log[] = "⚠️  Failed to update database";
    }
    
    $log[] = "\n===========================================";
    $log[] = "✅ PROVISIONING COMPLETE!";
    $log[] = "===========================================";
    $log[] = "Customer: $customerEmail";
    $log[] = "Server: $serverIp ($location)";
    $log[] = "Status: Online and ready to use";
    
    return ['success' => true, 'log' => $log, 'conf_file' => $confFile];
}

// If called directly (for testing)
if (php_sapi_name() === 'cli') {
    if ($argc < 6) {
        echo "Usage: php auto-provision.php <customer_id> <email> <server_ip> <temp_password> <location>\n";
        echo "\nExample:\n";
        echo "  php auto-provision.php 123 user@example.com 144.126.133.253 TempPass123 'St. Louis'\n";
        exit(1);
    }
    
    $customerId = $argv[1];
    $customerEmail = $argv[2];
    $serverIp = $argv[3];
    $tempPassword = $argv[4];
    $location = $argv[5];
    
    $result = provisionServer($customerId, $customerEmail, $serverIp, $tempPassword, $location);
    
    echo implode("\n", $result['log']) . "\n";
    
    exit($result['success'] ? 0 : 1);
}
