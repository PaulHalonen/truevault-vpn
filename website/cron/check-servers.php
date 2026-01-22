<?php
/**
 * Server Health Check Cron Job
 * 
 * Runs every 5 minutes to check all VPN server health
 * Updates server status and logs issues
 * 
 * Cron: */5 * * * * php /path/to/cron/check-servers.php
 * 
 * @created January 22, 2026
 */

// Allow CLI and web access with key
if (php_sapi_name() !== 'cli') {
    $cronKey = $_GET['key'] ?? '';
    if ($cronKey !== 'TrueVaultCron2026ServerCheck') {
        http_response_code(403);
        die('Forbidden');
    }
}

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

$startTime = microtime(true);
$results = [];

try {
    $serversDb = Database::getInstance('servers');
    $adminDb = Database::getInstance('admin');
    $logsDb = Database::getInstance('logs');
    
    // Get all servers
    $servers = [];
    $result = $serversDb->query("SELECT * FROM servers");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $servers[] = $row;
    }
    
    foreach ($servers as $server) {
        $serverId = $server['id'];
        $serverName = $server['name'];
        $serverIp = $server['ip_address'];
        $apiPort = $server['api_port'] ?? 8443;
        
        $checkResult = [
            'server_id' => $serverId,
            'name' => $serverName,
            'ip' => $serverIp,
            'status' => 'unknown',
            'latency' => null,
            'peer_count' => null,
            'error' => null
        ];
        
        // Step 1: Check API health endpoint
        $healthUrl = "http://{$serverIp}:{$apiPort}/api/health";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'ignore_errors' => true
            ]
        ]);
        
        $pingStart = microtime(true);
        $response = @file_get_contents($healthUrl, false, $context);
        $latency = round((microtime(true) - $pingStart) * 1000);
        
        if ($response === false) {
            $checkResult['status'] = 'offline';
            $checkResult['error'] = 'Connection failed';
        } else {
            $data = json_decode($response, true);
            if (isset($data['status']) && $data['status'] === 'online') {
                $checkResult['status'] = 'active';
                $checkResult['latency'] = $latency;
            } elseif (isset($data['status']) && $data['status'] === 'degraded') {
                $checkResult['status'] = 'degraded';
                $checkResult['latency'] = $latency;
            } else {
                $checkResult['status'] = 'unknown';
                $checkResult['error'] = 'Unexpected response';
            }
        }
        
        // Step 2: Get peer count (if online)
        if ($checkResult['status'] === 'active') {
            // Get API secret
            $stmt = $adminDb->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key");
            $stmt->bindValue(':key', 'server_api_secret_' . $serverId, SQLITE3_TEXT);
            $result = $stmt->execute();
            $secretRow = $result->fetchArray(SQLITE3_ASSOC);
            $apiSecret = $secretRow['setting_value'] ?? 'TRUEVAULT_API_SECRET_2026';
            
            $peersUrl = "http://{$serverIp}:{$apiPort}/api/list-peers";
            $authContext = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Authorization: Bearer {$apiSecret}\r\n",
                    'timeout' => 10,
                    'ignore_errors' => true
                ]
            ]);
            
            $peersResponse = @file_get_contents($peersUrl, false, $authContext);
            if ($peersResponse) {
                $peersData = json_decode($peersResponse, true);
                if (isset($peersData['peer_count'])) {
                    $checkResult['peer_count'] = $peersData['peer_count'];
                }
            }
        }
        
        // Step 3: Update server status in database
        $stmt = $serversDb->prepare("
            UPDATE servers SET 
                status = :status,
                current_clients = COALESCE(:clients, current_clients),
                load_percentage = CASE 
                    WHEN :clients IS NOT NULL AND max_clients > 0 
                    THEN ROUND((:clients * 100.0) / max_clients)
                    ELSE load_percentage 
                END,
                updated_at = datetime('now')
            WHERE id = :id
        ");
        $stmt->bindValue(':status', $checkResult['status'], SQLITE3_TEXT);
        $stmt->bindValue(':clients', $checkResult['peer_count'], SQLITE3_INTEGER);
        $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Step 4: Log the check
        $stmt = $logsDb->prepare("
            INSERT INTO server_health_log (server_id, status, latency_ms, peer_count, error, checked_at)
            VALUES (:server_id, :status, :latency, :peers, :error, datetime('now'))
        ");
        $stmt->bindValue(':server_id', $serverId, SQLITE3_INTEGER);
        $stmt->bindValue(':status', $checkResult['status'], SQLITE3_TEXT);
        $stmt->bindValue(':latency', $checkResult['latency'], SQLITE3_INTEGER);
        $stmt->bindValue(':peers', $checkResult['peer_count'], SQLITE3_INTEGER);
        $stmt->bindValue(':error', $checkResult['error'], SQLITE3_TEXT);
        $stmt->execute();
        
        // Step 5: Alert if server went offline
        if ($checkResult['status'] === 'offline') {
            // Check previous status
            $stmt = $serversDb->prepare("SELECT status FROM servers WHERE id = :id");
            $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
            $prevResult = $stmt->execute();
            $prevStatus = $prevResult->fetchArray(SQLITE3_ASSOC)['status'] ?? 'unknown';
            
            if ($prevStatus === 'active') {
                // Server just went offline - trigger alert workflow
                // This would call the automation engine
                $stmt = $logsDb->prepare("
                    INSERT INTO alert_log (alert_type, severity, message, context, created_at)
                    VALUES ('server_down', 'critical', :msg, :ctx, datetime('now'))
                ");
                $stmt->bindValue(':msg', "Server {$serverName} ({$serverIp}) went OFFLINE", SQLITE3_TEXT);
                $stmt->bindValue(':ctx', json_encode($checkResult), SQLITE3_TEXT);
                $stmt->execute();
            }
        }
        
        $results[] = $checkResult;
    }
    
    $elapsed = round((microtime(true) - $startTime) * 1000);
    
    // Create server_health_log table if not exists
    $logsDb->exec("
        CREATE TABLE IF NOT EXISTS server_health_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            server_id INTEGER NOT NULL,
            status TEXT,
            latency_ms INTEGER,
            peer_count INTEGER,
            error TEXT,
            checked_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create alert_log table if not exists
    $logsDb->exec("
        CREATE TABLE IF NOT EXISTS alert_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            alert_type TEXT,
            severity TEXT,
            message TEXT,
            context TEXT,
            acknowledged INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Output results
    $output = [
        'success' => true,
        'checked_at' => date('Y-m-d H:i:s'),
        'elapsed_ms' => $elapsed,
        'servers_checked' => count($results),
        'results' => $results
    ];
    
    if (php_sapi_name() === 'cli') {
        echo "Server Health Check Complete\n";
        echo "============================\n";
        echo "Time: " . date('Y-m-d H:i:s') . "\n";
        echo "Duration: {$elapsed}ms\n";
        echo "Servers: " . count($results) . "\n\n";
        
        foreach ($results as $r) {
            $status = strtoupper($r['status']);
            $latency = $r['latency'] ? "{$r['latency']}ms" : 'N/A';
            $peers = $r['peer_count'] ?? 'N/A';
            echo "{$r['name']} ({$r['ip']}): {$status} - Latency: {$latency} - Peers: {$peers}\n";
            if ($r['error']) echo "  Error: {$r['error']}\n";
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode($output, JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    $error = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    
    if (php_sapi_name() === 'cli') {
        echo "ERROR: " . $e->getMessage() . "\n";
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode($error);
    }
}
