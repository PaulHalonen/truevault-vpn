<?php
/**
 * TrueVault VPN - SQLite3 Diagnostic
 * Testing native SQLite3 class (not PDO)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>TrueVault VPN - SQLite3 Test</h1>";
echo "<pre>";

// Test 1: SQLite3 class exists?
echo "1. SQLite3 Class: " . (class_exists('SQLite3') ? "✅ Available" : "❌ NOT Available") . "\n";

// Test 2: SQLite3 extension loaded?
echo "2. SQLite3 Extension: " . (extension_loaded('sqlite3') ? "✅ Loaded" : "❌ NOT LOADED") . "\n";

// Test 3: Database path
$dbPath = dirname(__DIR__) . '/databases/';
echo "3. DB Path: " . $dbPath . "\n";
echo "4. DB Dir Writable: " . (is_writable($dbPath) ? "✅ Yes" : "❌ No") . "\n";

// Test 4: Try creating a database with SQLite3 class
echo "\n5. Testing SQLite3 database creation...\n";

if (class_exists('SQLite3')) {
    try {
        $testDb = $dbPath . 'test.db';
        $db = new SQLite3($testDb);
        $db->exec('CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, name TEXT)');
        $db->exec("INSERT INTO test (name) VALUES ('test_value')");
        $result = $db->querySingle("SELECT name FROM test", false);
        echo "   ✅ Database created and working!\n";
        echo "   Test value: " . $result . "\n";
        
        $db->close();
        unlink($testDb);
        echo "   ✅ Test database cleaned up\n";
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ SQLite3 class not available\n";
}

echo "\n</pre>";

if (class_exists('SQLite3')) {
    echo "<h2 style='color:green'>✅ SQLite3 is working! I'll rewrite the setup script to use SQLite3 instead of PDO.</h2>";
} else {
    echo "<h2 style='color:red'>❌ SQLite3 not available. Need to enable in cPanel.</h2>";
}
?>
