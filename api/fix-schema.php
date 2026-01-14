<?php
/**
 * Fix users table schema and check VIP data
 */
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TrueVault Schema Fix ===\n\n";

$dataPath = __DIR__ . '/../data';

// 1. Check users table schema
echo "1. Users Table Schema:\n";
$usersDb = $dataPath . '/users.db';
$db = new SQLite3($usersDb);

$schema = $db->query("PRAGMA table_info(users)");
$columns = [];
while ($row = $schema->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
    echo "   - {$row['name']} ({$row['type']})\n";
}

// 2. Add missing columns
echo "\n2. Adding Missing Columns:\n";

$requiredColumns = [
    'is_vip' => "ALTER TABLE users ADD COLUMN is_vip INTEGER DEFAULT 0",
    'plan_type' => "ALTER TABLE users ADD COLUMN plan_type TEXT DEFAULT 'basic'",
    'status' => "ALTER TABLE users ADD COLUMN status TEXT DEFAULT 'active'"
];

foreach ($requiredColumns as $col => $sql) {
    if (!in_array($col, $columns)) {
        try {
            $db->exec($sql);
            echo "   Added: $col ✓\n";
        } catch (Exception $e) {
            echo "   $col: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   $col already exists ✓\n";
    }
}

// 3. Show current users
echo "\n3. Current Users:\n";
$users = $db->query("SELECT * FROM users LIMIT 5");
while ($row = $users->fetchArray(SQLITE3_ASSOC)) {
    echo "   ID: {$row['id']}, Email: {$row['email']}\n";
    foreach ($row as $k => $v) {
        if ($k != 'id' && $k != 'email' && $k != 'password') {
            echo "      $k: $v\n";
        }
    }
}
$db->close();

// 4. Check VIP database
echo "\n4. VIP Database:\n";
$vipDb = $dataPath . '/vip.db';
$db = new SQLite3($vipDb);

$tables = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='vip_users'");
if ($tables) {
    echo "   vip_users table exists ✓\n";
    $schema = $db->query("PRAGMA table_info(vip_users)");
    echo "   Columns: ";
    $cols = [];
    while ($row = $schema->fetchArray(SQLITE3_ASSOC)) {
        $cols[] = $row['name'];
    }
    echo implode(', ', $cols) . "\n";
    
    $count = $db->querySingle("SELECT COUNT(*) FROM vip_users");
    echo "   VIP count: $count\n";
    
    if ($count > 0) {
        $vips = $db->query("SELECT * FROM vip_users");
        while ($row = $vips->fetchArray(SQLITE3_ASSOC)) {
            echo "   - {$row['email']}\n";
        }
    }
} else {
    echo "   vip_users table MISSING - Creating...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS vip_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        tier TEXT DEFAULT 'vip_basic',
        max_devices INTEGER DEFAULT 10,
        max_cameras INTEGER DEFAULT 5,
        dedicated_server_id INTEGER,
        dedicated_server_ip TEXT,
        notes TEXT,
        activated_at TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    echo "   Created vip_users table ✓\n";
}

// 5. Add VIP users if missing
echo "\n5. Ensuring VIP Users Exist:\n";

$vips = [
    ['paulhalonen@gmail.com', 'owner', 999, 999, null, null, 'Owner - Full access'],
    ['seige235@yahoo.com', 'vip_dedicated', 999, 999, 2, '144.126.133.253', 'Dedicated STL server']
];

foreach ($vips as $vip) {
    $check = $db->querySingle("SELECT id FROM vip_users WHERE LOWER(email) = LOWER('{$vip[0]}')");
    if (!$check) {
        $stmt = $db->prepare("INSERT INTO vip_users (email, tier, max_devices, max_cameras, dedicated_server_id, dedicated_server_ip, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bindValue(1, strtolower($vip[0]));
        $stmt->bindValue(2, $vip[1]);
        $stmt->bindValue(3, $vip[2]);
        $stmt->bindValue(4, $vip[3]);
        $stmt->bindValue(5, $vip[4]);
        $stmt->bindValue(6, $vip[5]);
        $stmt->bindValue(7, $vip[6]);
        $stmt->execute();
        echo "   Added: {$vip[0]} ✓\n";
    } else {
        echo "   Exists: {$vip[0]} ✓\n";
    }
}

$db->close();

echo "\n=== DONE ===\n";
echo "\nNow try logging in!\n";
