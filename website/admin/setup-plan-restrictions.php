<?php
/**
 * Add Plan Restriction Columns to Servers Table
 * 
 * Run once to add new columns for plan restrictions
 * 
 * @created January 22, 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Adding Plan Restriction Columns</h1><pre>";

try {
    $serversDb = Database::getInstance('servers');
    
    // Add port_forwarding_allowed column
    echo "Adding port_forwarding_allowed column...\n";
    $serversDb->exec("ALTER TABLE servers ADD COLUMN port_forwarding_allowed INTEGER DEFAULT 1");
    echo "✓ Done\n";
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "✓ Column already exists\n";
    } else {
        echo "Note: " . $e->getMessage() . "\n";
    }
}

try {
    $serversDb = Database::getInstance('servers');
    
    // Add high_bandwidth_allowed column
    echo "Adding high_bandwidth_allowed column...\n";
    $serversDb->exec("ALTER TABLE servers ADD COLUMN high_bandwidth_allowed INTEGER DEFAULT 1");
    echo "✓ Done\n";
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "✓ Column already exists\n";
    } else {
        echo "Note: " . $e->getMessage() . "\n";
    }
}

// Update server restrictions
echo "\nUpdating server restrictions...\n";

$serversDb = Database::getInstance('servers');

// NY Contabo - All features allowed
$serversDb->exec("UPDATE servers SET port_forwarding_allowed=1, high_bandwidth_allowed=1 WHERE ip_address='66.94.103.91'");
echo "✓ NY Contabo: Port forwarding=YES, High bandwidth=YES\n";

// St. Louis - Dedicated, all features
$serversDb->exec("UPDATE servers SET port_forwarding_allowed=1, high_bandwidth_allowed=1 WHERE ip_address='144.126.133.253'");
echo "✓ St. Louis (Dedicated): Port forwarding=YES, High bandwidth=YES\n";

// Dallas Fly.io - Limited
$serversDb->exec("UPDATE servers SET port_forwarding_allowed=0, high_bandwidth_allowed=0 WHERE ip_address='66.241.124.4'");
echo "✓ Dallas Fly.io: Port forwarding=NO, High bandwidth=NO\n";

// Toronto Fly.io - Limited
$serversDb->exec("UPDATE servers SET port_forwarding_allowed=0, high_bandwidth_allowed=0 WHERE ip_address='66.241.125.247'");
echo "✓ Toronto Fly.io: Port forwarding=NO, High bandwidth=NO\n";

echo "\n✅ Plan restrictions applied to servers!\n";
echo "</pre>";
