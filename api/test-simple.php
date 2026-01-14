<?php
/**
 * Simple diagnostic - no requires
 */
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TrueVault Diagnostic ===\n\n";

// 1. Check data folder
$dataPath = __DIR__ . '/../data';
echo "1. Data folder: $dataPath\n";
echo "   Exists: " . (is_dir($dataPath) ? 'YES' : 'NO') . "\n";

if (is_dir($dataPath)) {
    $files = glob($dataPath . '/*.db');
    echo "   Databases found: " . count($files) . "\n";
    foreach ($files as $f) {
        echo "   - " . basename($f) . "\n";
    }
}

// 2. Check users.db
echo "\n2. Users Database:\n";
$usersDb = $dataPath . '/users.db';
if (file_exists($usersDb)) {
    echo "   File exists: YES\n";
    try {
        $db = new SQLite3($usersDb);
        $count = $db->querySingle("SELECT COUNT(*) FROM users");
        echo "   User count: $count\n";
        
        $users = $db->query("SELECT id, email, is_vip, status FROM users LIMIT 5");
        while ($row = $users->fetchArray(SQLITE3_ASSOC)) {
            echo "   - {$row['email']} (VIP: {$row['is_vip']}, Status: {$row['status']})\n";
        }
        $db->close();
    } catch (Exception $e) {
        echo "   Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   File exists: NO\n";
}

// 3. Check vip.db
echo "\n3. VIP Database:\n";
$vipDb = $dataPath . '/vip.db';
if (file_exists($vipDb)) {
    echo "   File exists: YES\n";
    try {
        $db = new SQLite3($vipDb);
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
        echo "   Tables: ";
        $tableList = [];
        while ($row = $tables->fetchArray()) {
            $tableList[] = $row[0];
        }
        echo implode(', ', $tableList) . "\n";
        
        if (in_array('vip_users', $tableList)) {
            $vips = $db->query("SELECT * FROM vip_users");
            echo "   VIP Users:\n";
            while ($row = $vips->fetchArray(SQLITE3_ASSOC)) {
                echo "   - {$row['email']} (Tier: {$row['tier']})\n";
            }
        }
        $db->close();
    } catch (Exception $e) {
        echo "   Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   File exists: NO\n";
}

// 4. Check config files
echo "\n4. Config Files:\n";
$configs = ['database.php', 'jwt.php', 'constants.php', 'settings.php'];
foreach ($configs as $c) {
    $path = __DIR__ . '/config/' . $c;
    echo "   $c: " . (file_exists($path) ? 'EXISTS' : 'MISSING') . "\n";
}

// 5. Check helper files
echo "\n5. Helper Files:\n";
$helpers = ['auth.php', 'response.php', 'vip.php', 'validator.php'];
foreach ($helpers as $h) {
    $path = __DIR__ . '/helpers/' . $h;
    echo "   $h: " . (file_exists($path) ? 'EXISTS' : 'MISSING') . "\n";
}

echo "\n=== END ===\n";
