<?php
/**
 * TrueVault VPN - Business Transfer Verification API
 * Created: January 19, 2026
 * Purpose: Handle all verification tests and setting updates for business transfer
 * 
 * Endpoints:
 * - save_settings: Save section settings to database
 * - test_paypal: Test PayPal API connection
 * - test_smtp: Test email sending
 * - test_imap: Test email receiving
 * - test_ssh: Test SSH connections to new servers
 * - verify_webhook: Verify PayPal webhook configuration
 * - check_dns: Check DNS propagation
 * - add_server: Add new server to system
 * - remove_server: Remove server from system
 * - get_progress: Get current transfer progress
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

// Helper: Get setting value
function getSetting($db, $key) {
    $stmt = $db->prepare("SELECT setting_value FROM business_settings WHERE setting_key = ?");
    $stmt->bindValue(1, $key);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row ? $row['setting_value'] : null;
}

// Helper: Update setting
function updateSetting($db, $key, $value) {
    // Check if masked password (unchanged)
    if ($value === '••••••••') {
        return true; // Don't update masked passwords
    }
    
    $stmt = $db->prepare("
        UPDATE business_settings 
        SET setting_value = ?, 
            updated_at = datetime('now'),
            updated_by = 'transfer_admin'
        WHERE setting_key = ?
    ");
    $stmt->bindValue(1, $value);
    $stmt->bindValue(2, $key);
    return $stmt->execute();
}

// Helper: Update verification status
function updateVerification($db, $key, $status) {
    $stmt = $db->prepare("
        UPDATE business_settings 
        SET verification_status = ?,
            last_verified = datetime('now')
        WHERE setting_key = ?
    ");
    $stmt->bindValue(1, $status);
    $stmt->bindValue(2, $key);
    return $stmt->execute();
}

// Helper: Log audit
function logAudit($db, $key, $oldValue, $newValue, $ipAddress) {
    $stmt = $db->prepare("
        INSERT INTO business_settings_audit 
        (setting_key, old_value, new_value, changed_by, ip_address)
        VALUES (?, ?, ?, 'transfer_admin', ?)
    ");
    $stmt->bindValue(1, $key);
    $stmt->bindValue(2, $oldValue);
    $stmt->bindValue(3, $newValue);
    $stmt->bindValue(4, $ipAddress);
    return $stmt->execute();
}

// Action router
switch ($input['action']) {
    
    // ===== SAVE SETTINGS =====
    case 'save_settings':
        $section = $input['section'] ?? '';
        $data = $input['data'] ?? [];
        
        if (empty($section) || empty($data)) {
            echo json_encode(['success' => false, 'error' => 'Missing section or data']);
            exit;
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $oldValue = getSetting($db, $key);
                updateSetting($db, $key, $value);
                logAudit($db, $key, $oldValue, $value, $ip);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Settings saved']);
        break;
    
    // ===== TEST PAYPAL =====
    case 'test_paypal':
        $clientId = $input['client_id'] ?? getSetting($db, 'paypal_client_id');
        $secret = $input['secret'] ?? '';
        
        // If secret is masked, get from database
        if ($secret === '••••••••' || empty($secret)) {
            $secret = getSetting($db, 'paypal_secret');
        }
        
        if (empty($clientId) || empty($secret)) {
            echo json_encode(['success' => false, 'error' => 'PayPal credentials not configured']);
            exit;
        }
        
        // Test PayPal OAuth endpoint
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api-m.paypal.com/v1/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $clientId . ':' . $secret,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en_US'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            updateVerification($db, 'paypal_client_id', 'failed');
            echo json_encode(['success' => false, 'error' => 'Connection error: ' . $error]);
            exit;
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode === 200 && isset($data['access_token'])) {
            updateVerification($db, 'paypal_client_id', 'verified');
            updateVerification($db, 'paypal_secret', 'verified');
            echo json_encode(['success' => true, 'message' => 'PayPal connection successful']);
        } else {
            updateVerification($db, 'paypal_client_id', 'failed');
            $errorMsg = $data['error_description'] ?? 'Invalid credentials';
            echo json_encode(['success' => false, 'error' => $errorMsg]);
        }
        break;
    
    // ===== TEST SMTP =====
    case 'test_smtp':
        $email = $input['email'] ?? getSetting($db, 'customer_email');
        $password = $input['password'] ?? '';
        $server = $input['server'] ?? getSetting($db, 'smtp_server');
        $port = $input['port'] ?? getSetting($db, 'smtp_port');
        
        if ($password === '••••••••' || empty($password)) {
            $password = getSetting($db, 'customer_email_password');
        }
        
        if (empty($email) || empty($password) || empty($server)) {
            echo json_encode(['success' => false, 'error' => 'Email credentials not configured']);
            exit;
        }
        
        // Test SMTP connection using fsockopen
        $errno = 0;
        $errstr = '';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $protocol = ($port == 465) ? 'ssl://' : '';
        $connection = @stream_socket_client(
            $protocol . $server . ':' . $port,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$connection) {
            updateVerification($db, 'smtp_server', 'failed');
            echo json_encode(['success' => false, 'error' => "Cannot connect to SMTP: $errstr ($errno)"]);
            exit;
        }
        
        // Read greeting
        $response = fgets($connection, 515);
        if (strpos($response, '220') === false) {
            fclose($connection);
            updateVerification($db, 'smtp_server', 'failed');
            echo json_encode(['success' => false, 'error' => 'Invalid SMTP greeting']);
            exit;
        }
        
        // Send EHLO
        fwrite($connection, "EHLO localhost\r\n");
        $response = '';
        while ($line = fgets($connection, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        
        // Attempt AUTH LOGIN
        fwrite($connection, "AUTH LOGIN\r\n");
        $response = fgets($connection, 515);
        
        if (strpos($response, '334') !== false) {
            // Send username
            fwrite($connection, base64_encode($email) . "\r\n");
            $response = fgets($connection, 515);
            
            if (strpos($response, '334') !== false) {
                // Send password
                fwrite($connection, base64_encode($password) . "\r\n");
                $response = fgets($connection, 515);
                
                if (strpos($response, '235') !== false) {
                    // Authentication successful
                    fwrite($connection, "QUIT\r\n");
                    fclose($connection);
                    
                    updateVerification($db, 'smtp_server', 'verified');
                    updateVerification($db, 'customer_email', 'verified');
                    updateVerification($db, 'customer_email_password', 'verified');
                    
                    echo json_encode(['success' => true, 'message' => 'SMTP authentication successful']);
                    exit;
                }
            }
        }
        
        fclose($connection);
        updateVerification($db, 'smtp_server', 'failed');
        echo json_encode(['success' => false, 'error' => 'SMTP authentication failed']);
        break;
    
    // ===== TEST IMAP =====
    case 'test_imap':
        $email = $input['email'] ?? getSetting($db, 'customer_email');
        $password = $input['password'] ?? '';
        $server = $input['server'] ?? getSetting($db, 'imap_server');
        $port = $input['port'] ?? getSetting($db, 'imap_port');
        
        if ($password === '••••••••' || empty($password)) {
            $password = getSetting($db, 'customer_email_password');
        }
        
        if (empty($email) || empty($password) || empty($server)) {
            echo json_encode(['success' => false, 'error' => 'IMAP credentials not configured']);
            exit;
        }
        
        // Check if IMAP extension is available
        if (!function_exists('imap_open')) {
            // Fall back to socket test
            $errno = 0;
            $errstr = '';
            $connection = @fsockopen('ssl://' . $server, $port, $errno, $errstr, 30);
            
            if (!$connection) {
                echo json_encode(['success' => false, 'error' => "Cannot connect to IMAP: $errstr"]);
                exit;
            }
            
            $response = fgets($connection, 515);
            fclose($connection);
            
            if (strpos($response, 'OK') !== false) {
                updateVerification($db, 'imap_server', 'verified');
                echo json_encode(['success' => true, 'message' => 'IMAP server reachable (manual auth required)']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid IMAP response']);
            }
            exit;
        }
        
        // Use IMAP extension
        $mailbox = '{' . $server . ':' . $port . '/imap/ssl/novalidate-cert}INBOX';
        
        $imap = @imap_open($mailbox, $email, $password);
        
        if ($imap) {
            imap_close($imap);
            updateVerification($db, 'imap_server', 'verified');
            echo json_encode(['success' => true, 'message' => 'IMAP connection successful']);
        } else {
            $error = imap_last_error();
            updateVerification($db, 'imap_server', 'failed');
            echo json_encode(['success' => false, 'error' => 'IMAP error: ' . $error]);
        }
        break;
    
    // ===== TEST SSH =====
    case 'test_ssh':
        // Get list of new servers to test
        $result = $db->query("SELECT * FROM servers WHERE is_new_owner = 1");
        
        if (!$result) {
            // servers table might not have is_new_owner column yet
            echo json_encode(['success' => false, 'error' => 'No new servers added yet']);
            exit;
        }
        
        $servers = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $servers[] = $row;
        }
        
        if (empty($servers)) {
            echo json_encode(['success' => false, 'error' => 'No new servers to test']);
            exit;
        }
        
        $rootPassword = getSetting($db, 'server_root_password');
        if (empty($rootPassword)) {
            echo json_encode(['success' => false, 'error' => 'Server root password not configured']);
            exit;
        }
        
        $allPassed = true;
        $results = [];
        
        foreach ($servers as $server) {
            // Test SSH connection using socket
            $ip = $server['ip_address'];
            $port = $server['ssh_port'] ?? 22;
            
            $connection = @fsockopen($ip, $port, $errno, $errstr, 10);
            
            if ($connection) {
                $response = fgets($connection, 256);
                fclose($connection);
                
                if (strpos($response, 'SSH') !== false) {
                    $results[$ip] = 'OK';
                } else {
                    $results[$ip] = 'Invalid SSH response';
                    $allPassed = false;
                }
            } else {
                $results[$ip] = "Cannot connect: $errstr";
                $allPassed = false;
            }
        }
        
        if ($allPassed) {
            echo json_encode(['success' => true, 'message' => 'All SSH connections verified', 'results' => $results]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Some SSH tests failed', 'results' => $results]);
        }
        break;
    
    // ===== VERIFY WEBHOOK =====
    case 'verify_webhook':
        $webhookId = $input['webhook_id'] ?? getSetting($db, 'paypal_webhook_id');
        $clientId = getSetting($db, 'paypal_client_id');
        $secret = getSetting($db, 'paypal_secret');
        
        if (empty($webhookId)) {
            echo json_encode(['success' => false, 'error' => 'Webhook ID not configured']);
            exit;
        }
        
        if (empty($clientId) || empty($secret)) {
            echo json_encode(['success' => false, 'error' => 'PayPal credentials required first']);
            exit;
        }
        
        // Get access token
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api-m.paypal.com/v1/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $clientId . ':' . $secret,
            CURLOPT_HTTPHEADER => ['Accept: application/json']
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $tokenData = json_decode($response, true);
        
        if (!isset($tokenData['access_token'])) {
            echo json_encode(['success' => false, 'error' => 'Cannot get PayPal access token']);
            exit;
        }
        
        // Get webhook details
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api-m.paypal.com/v1/notifications/webhooks/' . $webhookId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $tokenData['access_token'],
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $webhookData = json_decode($response, true);
            
            $domain = getSetting($db, 'business_domain');
            $expectedUrl = 'https://' . $domain . '/api/paypal-webhook.php';
            
            if (isset($webhookData['url']) && strpos($webhookData['url'], $domain) !== false) {
                updateVerification($db, 'paypal_webhook_id', 'verified');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Webhook verified',
                    'url' => $webhookData['url']
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'error' => 'Webhook URL does not match domain',
                    'current_url' => $webhookData['url'] ?? 'unknown',
                    'expected_domain' => $domain
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Cannot verify webhook - may not exist']);
        }
        break;
    
    // ===== CHECK DNS =====
    case 'check_dns':
        $domain = $input['domain'] ?? getSetting($db, 'business_domain');
        
        if (empty($domain)) {
            echo json_encode(['success' => false, 'error' => 'Domain not configured']);
            exit;
        }
        
        // Remove protocol if present
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = rtrim($domain, '/');
        
        // Check DNS resolution
        $ip = gethostbyname($domain);
        
        if ($ip === $domain) {
            echo json_encode(['success' => false, 'error' => 'DNS not resolving']);
            exit;
        }
        
        // Try to connect to the domain
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://' . $domain,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 500) {
            updateVerification($db, 'business_domain', 'verified');
            echo json_encode([
                'success' => true, 
                'message' => 'DNS propagated',
                'ip' => $ip,
                'http_code' => $httpCode
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'error' => 'Domain not responding properly',
                'ip' => $ip,
                'http_code' => $httpCode
            ]);
        }
        break;
    
    // ===== ADD SERVER =====
    case 'add_server':
        $server = $input['server'] ?? [];
        
        if (empty($server['ip']) || empty($server['name'])) {
            echo json_encode(['success' => false, 'error' => 'Server IP and name required']);
            exit;
        }
        
        // Test basic connection first
        $ip = $server['ip'];
        $port = $server['ssh_port'] ?? 22;
        
        $connection = @fsockopen($ip, $port, $errno, $errstr, 10);
        
        if (!$connection) {
            echo json_encode(['success' => false, 'error' => "Cannot connect to server: $errstr"]);
            exit;
        }
        
        $response = fgets($connection, 256);
        fclose($connection);
        
        if (strpos($response, 'SSH') === false) {
            echo json_encode(['success' => false, 'error' => 'Server does not appear to be running SSH']);
            exit;
        }
        
        // Ensure servers table has is_new_owner column
        $db->exec("ALTER TABLE servers ADD COLUMN is_new_owner BOOLEAN DEFAULT 0");
        $db->exec("ALTER TABLE servers ADD COLUMN ssh_port INTEGER DEFAULT 22");
        
        // Add server to database
        $stmt = $db->prepare("
            INSERT INTO servers (name, ip_address, location, provider, ssh_port, is_new_owner, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, 1, 1, datetime('now'))
        ");
        $stmt->bindValue(1, $server['name']);
        $stmt->bindValue(2, $server['ip']);
        $stmt->bindValue(3, $server['location'] ?? '');
        $stmt->bindValue(4, $server['provider'] ?? 'Unknown');
        $stmt->bindValue(5, $port);
        $stmt->execute();
        
        $serverId = $db->lastInsertRowID();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Server added successfully',
            'server_id' => $serverId
        ]);
        break;
    
    // ===== REMOVE SERVER =====
    case 'remove_server':
        $serverId = $input['server_id'] ?? 0;
        
        if (empty($serverId)) {
            echo json_encode(['success' => false, 'error' => 'Server ID required']);
            exit;
        }
        
        // Only allow removing new owner's servers
        $stmt = $db->prepare("DELETE FROM servers WHERE id = ? AND is_new_owner = 1");
        $stmt->bindValue(1, $serverId);
        $stmt->execute();
        
        if ($db->changes() > 0) {
            echo json_encode(['success' => true, 'message' => 'Server removed']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Cannot remove this server']);
        }
        break;
    
    // ===== GET PROGRESS =====
    case 'get_progress':
        // Calculate progress based on settings completion
        $checks = [
            'business' => false,
            'payment' => false,
            'email' => false,
            'server' => false,
            'verified' => false
        ];
        
        // Check business info
        $businessName = getSetting($db, 'business_name');
        $ownerName = getSetting($db, 'owner_name');
        $checks['business'] = !empty($businessName) && !empty($ownerName);
        
        // Check payment
        $paypalId = getSetting($db, 'paypal_client_id');
        $paypalEmail = getSetting($db, 'paypal_account_email');
        $checks['payment'] = !empty($paypalId) && !empty($paypalEmail);
        
        // Check email
        $customerEmail = getSetting($db, 'customer_email');
        $smtpServer = getSetting($db, 'smtp_server');
        $checks['email'] = !empty($customerEmail) && !empty($smtpServer);
        
        // Check server
        $serverEmail = getSetting($db, 'server_provider_email');
        $checks['server'] = !empty($serverEmail);
        
        // Check verifications
        $result = $db->query("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) as verified
            FROM business_settings 
            WHERE requires_verification = 1
        ");
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $checks['verified'] = ($row['total'] > 0 && $row['verified'] == $row['total']);
        
        $complete = array_filter($checks);
        $percentage = round((count($complete) / count($checks)) * 100);
        
        echo json_encode([
            'success' => true,
            'percentage' => $percentage,
            'checks' => $checks
        ]);
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $input['action']]);
}
