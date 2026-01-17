<?php
/**
 * Fix servers.db - recreate with correct schema
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbPath = __DIR__ . '/../databases/servers.db';

echo "<h2>Fixing servers.db</h2>";

try {
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);
    
    // Drop and recreate
    $db->exec("DROP TABLE IF EXISTS servers");
    
    $db->exec("CREATE TABLE servers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        location TEXT NOT NULL,
        country TEXT DEFAULT 'US',
        ip TEXT NOT NULL,
        port INTEGER DEFAULT 51820,
        public_key TEXT,
        endpoint TEXT,
        dns TEXT DEFAULT '1.1.1.1, 8.8.8.8',
        allowed_ips TEXT DEFAULT '0.0.0.0/0',
        type TEXT DEFAULT 'shared',
        is_active INTEGER DEFAULT 1,
        max_users INTEGER DEFAULT 100,
        current_users INTEGER DEFAULT 0,
        last_check DATETIME,
        last_status TEXT DEFAULT 'unknown',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert servers
    $db->exec("INSERT INTO servers (id, name, location, country, ip, port, type, max_users) VALUES 
        (1, 'US East', 'New York, USA', 'US', '66.94.103.91', 51820, 'shared', 100),
        (2, 'US Central (VIP)', 'St. Louis, USA', 'US', '144.126.133.253', 51820, 'dedicated', 1),
        (3, 'US South', 'Dallas, USA', 'US', '66.241.124.4', 51820, 'shared', 100),
        (4, 'Canada', 'Toronto, Canada', 'CA', '66.241.125.247', 51820, 'shared', 100)
    ");
    
    $db->close();
    
    echo "<p style='color: #00ff88;'>✅ servers.db fixed! 4 servers added.</p>";
    echo "<p><a href='index.html'>Go to Admin Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: #ff5050;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
