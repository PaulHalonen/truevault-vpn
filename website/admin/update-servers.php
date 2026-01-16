<?php
/**
 * TrueVault VPN - Update Servers with Real Data
 * 
 * This script updates the servers table with actual server information
 * Run once to populate real server data
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

echo "<h1>🖥️ Updating Servers with Real Data</h1>";

try {
    $db = new SQLite3(DB_SERVERS);
    $db->enableExceptions(true);
    
    // Clear existing server data
    $db->exec("DELETE FROM servers");
    echo "<p>✅ Cleared old server data</p>";
    
    // Real server data
    $servers = [
        [
            'name' => 'US East',
            'hostname' => 'us-east.truevaultvpn.com',
            'ip_address' => '66.94.103.91',
            'port' => 51820,
            'country' => 'United States',
            'country_code' => 'US',
            'city' => 'New York',
            'provider' => 'Contabo',
            'type' => 'shared',
            'is_premium' => 0,
            'max_clients' => 100,
            'bandwidth_limit' => 1000, // 1TB
            'status' => 'active',
            'wireguard_port' => 51820,
            'api_port' => 8080,
            'priority' => 1
        ],
        [
            'name' => 'US Central VIP',
            'hostname' => 'us-central-vip.truevaultvpn.com',
            'ip_address' => '144.126.133.253',
            'port' => 51820,
            'country' => 'United States',
            'country_code' => 'US',
            'city' => 'St. Louis',
            'provider' => 'Contabo',
            'type' => 'dedicated',
            'is_premium' => 1,
            'max_clients' => 10,
            'bandwidth_limit' => 0, // Unlimited
            'status' => 'active',
            'wireguard_port' => 51820,
            'api_port' => 8080,
            'priority' => 10,
            'dedicated_to_email' => 'seige235@yahoo.com'
        ],
        [
            'name' => 'US South',
            'hostname' => 'us-south.truevaultvpn.com',
            'ip_address' => '66.241.124.4',
            'port' => 51820,
            'country' => 'United States',
            'country_code' => 'US',
            'city' => 'Dallas',
            'provider' => 'Fly.io',
            'type' => 'shared',
            'is_premium' => 0,
            'max_clients' => 100,
            'bandwidth_limit' => 1000,
            'status' => 'active',
            'wireguard_port' => 51820,
            'api_port' => 8080,
            'priority' => 2
        ],
        [
            'name' => 'Canada',
            'hostname' => 'ca.truevaultvpn.com',
            'ip_address' => '66.241.125.247',
            'port' => 51820,
            'country' => 'Canada',
            'country_code' => 'CA',
            'city' => 'Toronto',
            'provider' => 'Fly.io',
            'type' => 'shared',
            'is_premium' => 0,
            'max_clients' => 100,
            'bandwidth_limit' => 1000,
            'status' => 'active',
            'wireguard_port' => 51820,
            'api_port' => 8080,
            'priority' => 3
        ]
    ];
    
    // Insert servers
    $stmt = $db->prepare("
        INSERT INTO servers (
            name, hostname, ip_address, port, country, country_code, city, 
            provider, type, is_premium, max_clients, bandwidth_limit, status,
            wireguard_port, api_port, priority, created_at, updated_at
        ) VALUES (
            :name, :hostname, :ip_address, :port, :country, :country_code, :city,
            :provider, :type, :is_premium, :max_clients, :bandwidth_limit, :status,
            :wireguard_port, :api_port, :priority, datetime('now'), datetime('now')
        )
    ");
    
    foreach ($servers as $server) {
        $stmt->bindValue(':name', $server['name'], SQLITE3_TEXT);
        $stmt->bindValue(':hostname', $server['hostname'], SQLITE3_TEXT);
        $stmt->bindValue(':ip_address', $server['ip_address'], SQLITE3_TEXT);
        $stmt->bindValue(':port', $server['port'], SQLITE3_INTEGER);
        $stmt->bindValue(':country', $server['country'], SQLITE3_TEXT);
        $stmt->bindValue(':country_code', $server['country_code'], SQLITE3_TEXT);
        $stmt->bindValue(':city', $server['city'], SQLITE3_TEXT);
        $stmt->bindValue(':provider', $server['provider'], SQLITE3_TEXT);
        $stmt->bindValue(':type', $server['type'], SQLITE3_TEXT);
        $stmt->bindValue(':is_premium', $server['is_premium'], SQLITE3_INTEGER);
        $stmt->bindValue(':max_clients', $server['max_clients'], SQLITE3_INTEGER);
        $stmt->bindValue(':bandwidth_limit', $server['bandwidth_limit'], SQLITE3_INTEGER);
        $stmt->bindValue(':status', $server['status'], SQLITE3_TEXT);
        $stmt->bindValue(':wireguard_port', $server['wireguard_port'], SQLITE3_INTEGER);
        $stmt->bindValue(':api_port', $server['api_port'], SQLITE3_INTEGER);
        $stmt->bindValue(':priority', $server['priority'], SQLITE3_INTEGER);
        $stmt->execute();
        $stmt->reset();
        
        $flag = $server['country_code'] === 'US' ? '🇺🇸' : '🇨🇦';
        $vip = $server['is_premium'] ? ' ⭐ VIP' : '';
        echo "<p>✅ Added: {$flag} {$server['name']} ({$server['ip_address']}){$vip}</p>";
    }
    
    // Update VIP user's dedicated server assignment
    $mainDb = new SQLite3(DB_MAIN);
    $mainDb->enableExceptions(true);
    
    // Get the VIP server ID (should be 2)
    $vipServerId = $db->querySingle("SELECT id FROM servers WHERE type = 'dedicated' LIMIT 1");
    
    if ($vipServerId) {
        $mainDb->exec("UPDATE vip_users SET dedicated_server_id = {$vipServerId} WHERE email = 'seige235@yahoo.com'");
        echo "<p>✅ Linked seige235@yahoo.com to dedicated server ID: {$vipServerId}</p>";
    }
    
    echo "<h2>📊 Server Summary</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background:#333;color:#fff;'><th>ID</th><th>Name</th><th>IP</th><th>Location</th><th>Type</th><th>Provider</th></tr>";
    
    $results = $db->query("SELECT * FROM servers ORDER BY priority");
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $flag = $row['country_code'] === 'US' ? '🇺🇸' : '🇨🇦';
        $type = $row['is_premium'] ? "<span style='color:gold;'>⭐ {$row['type']}</span>" : $row['type'];
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$flag} {$row['name']}</td>";
        echo "<td><code>{$row['ip_address']}</code></td>";
        echo "<td>{$row['city']}, {$row['country_code']}</td>";
        echo "<td>{$type}</td>";
        echo "<td>{$row['provider']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>✅ Real Server Data Loaded!</h2>";
    echo "<p>You can now delete this file.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
