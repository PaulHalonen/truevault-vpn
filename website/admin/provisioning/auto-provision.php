
    
    /**
     * Assign server to customer in database
     */
    private function assignServerToCustomer($serverId, $customerId, $email) {
        $this->log("Assigning server {$serverId} to customer {$customerId}");
        
        $stmt = $this->db->prepare("
            UPDATE servers 
            SET dedicated_user_email = :email,
                access_level = 'dedicated',
                is_visible = 0,
                updated_at = datetime('now')
            WHERE id = :id
        ");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Update user record
        $usersDb = Database::getInstance('users');
        $stmt = $usersDb->prepare("
            UPDATE users 
            SET dedicated_server_id = :sid,
                tier = 'dedicated',
                updated_at = datetime('now')
            WHERE id = :uid
        ");
        $stmt->bindValue(':sid', $serverId, SQLITE3_INTEGER);
        $stmt->bindValue(':uid', $customerId, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    /**
     * Email configuration file to customer
     */
    private function emailConfigToCustomer($configData, $serverPassword) {
        $this->log("Emailing config to: {$this->customerEmail}");
        
        // Get server details
        $stmt = $this->db->prepare("SELECT * FROM servers WHERE id = :id");
        $stmt->bindValue(':id', $this->serverId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $server = $result->fetchArray(SQLITE3_ASSOC);
        
        // Save config as temp file
        $configFile = sys_get_temp_dir() . "/truevault_{$this->customerId}.conf";
        file_put_contents($configFile, $configData['config']);
        
        // Build email
        $subject = "Your Dedicated TrueVault VPN Server is Ready!";
        $body = "
Dear TrueVault Customer,

Your dedicated VPN server has been provisioned and is ready to use!

=== SERVER DETAILS ===
Server Name: {$server['name']}
Location: {$server['location']}
IP Address: {$server['ip_address']}
Your VPN IP: {$configData['client_ip']}

=== SSH ACCESS (Advanced Users) ===
Host: {$server['ip_address']}
Username: root
Password: {$serverPassword}

=== GETTING STARTED ===
1. Download the attached .conf file
2. Import it into WireGuard app
3. Connect and enjoy your private server!

=== IMPORTANT NOTES ===
- This server is EXCLUSIVELY yours
- You can add unlimited devices
- Port forwarding is available
- For support, visit: https://vpn.the-truth-publishing.com/support

Thank you for choosing TrueVault VPN!

Best regards,
TrueVault VPN Team
";
        
        // Send email with attachment
        $emailer = new Email();
        $result = $emailer->sendWithAttachment(
            $this->customerEmail,
            $subject,
            $body,
            $configFile,
            "truevault-dedicated.conf"
        );
        
        // Clean up temp file
        unlink($configFile);
        
        if (!$result) {
            $this->log("WARNING: Email delivery may have failed", 'warning');
        }
        
        return $result;
    }
    
    /**
     * Log provisioning activity
     */
    private function log($message, $level = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
        
        // Write to log file
        $logFile = __DIR__ . '/../../logs/provisioning.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also store in database
        $settingsDb = Database::getInstance('settings');
        $stmt = $settingsDb->prepare("
            INSERT INTO automation_log (workflow_name, step_name, status, details, executed_at)
            VALUES ('server_provisioning', :msg, :level, :details, datetime('now'))
        ");
        $stmt->bindValue(':msg', substr($message, 0, 100), SQLITE3_TEXT);
        $stmt->bindValue(':level', $level, SQLITE3_TEXT);
        $stmt->bindValue(':details', $message, SQLITE3_TEXT);
        $stmt->execute();
    }
    
    /**
     * Alert admin of issues
     */
    private function alertAdmin($message) {
        $emailer = new Email();
        $emailer->send(
            'paulhalonen@gmail.com',
            'ALERT: Server Provisioning Issue',
            "A provisioning issue occurred:\n\n{$message}\n\nCustomer: {$this->customerEmail}\nLocation: {$this->location}"
        );
    }
}

// CLI or API execution
if (php_sapi_name() === 'cli' || isset($_POST['action'])) {
    $customerId = $_POST['customer_id'] ?? ($argv[1] ?? null);
    $email = $_POST['email'] ?? ($argv[2] ?? null);
    $location = $_POST['location'] ?? ($argv[3] ?? 'US-East');
    
    if ($customerId && $email) {
        $provisioner = new ServerProvisioner();
        $result = $provisioner->provision($customerId, $email, $location);
        
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: application/json');
            echo json_encode($result);
        } else {
            print_r($result);
        }
    } else {
        echo "Usage: php auto-provision.php <customer_id> <email> [location]\n";
    }
}
