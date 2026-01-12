<?php
/**
 * TrueVault VPN - Update Server Public Keys
 * Run once to add the WireGuard public keys to the database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TrueVault VPN - Update Server Keys</h2><pre>\n";

$dbPath = __DIR__ . '/../../data/vpn.db';

if (!file_exists($dbPath)) {
    die("❌ Database not found! Run setup-databases.php first.\n");
}

try {
    $db = new SQLite3($dbPath);
    
    // Server public keys from WireGuard
    $servers = [
        1 => 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=',  // US-East (66.94.103.91)
        2 => 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=',  // US-Central VIP (144.126.133.253)
        // Fly.io servers - keys to be added later
        // 3 => '',  // Dallas
        // 4 => '',  // Canada
    ];
    
    foreach ($servers as $id => $publicKey) {
        $stmt = $db->prepare("UPDATE vpn_servers SET public_key = :key WHERE id = :id");
        $stmt->bindValue(':key', $publicKey, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        if ($result) {
            echo "✅ Updated server ID $id with public key\n";
        } else {
            echo "❌ Failed to update server ID $id\n";
        }
    }
    
    // Verify
    echo "\n--- Current Server Status ---\n";
    $result = $db->query("SELECT id, name, ip_address, public_key, is_vip FROM vpn_servers ORDER BY id");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $keyStatus = $row['public_key'] ? '✅ Has Key' : '❌ No Key';
        $vipStatus = $row['is_vip'] ? ' [VIP]' : '';
        echo "Server {$row['id']}: {$row['name']} ({$row['ip_address']}) - $keyStatus$vipStatus\n";
    }
    
    $db->close();
    echo "\n✅ Done!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
