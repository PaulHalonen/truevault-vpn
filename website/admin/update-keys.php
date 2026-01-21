<?php
/**
 * Update Server Public Keys - Run Once
 */
define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

try {
    $db = new SQLite3(DB_SERVERS);
    
    // Update New York (Contabo)
    $stmt = $db->prepare("UPDATE servers SET public_key = :key WHERE endpoint LIKE '66.94.103.91%'");
    $stmt->bindValue(':key', 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+lKGai9n3s=', SQLITE3_TEXT);
    $stmt->execute();
    echo "✅ New York key updated<br>";
    
    // Update St. Louis (Contabo VIP)
    $stmt = $db->prepare("UPDATE servers SET public_key = :key WHERE endpoint LIKE '144.126.133.253%'");
    $stmt->bindValue(':key', 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=', SQLITE3_TEXT);
    $stmt->execute();
    echo "✅ St. Louis VIP key updated<br>";
    
    // Verify updates
    echo "<br><strong>Current Servers:</strong><br>";
    $results = $db->query("SELECT name, endpoint, public_key FROM servers");
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $key = $row['public_key'] ?: 'NOT SET';
        echo "{$row['name']} ({$row['endpoint']}): {$key}<br>";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
