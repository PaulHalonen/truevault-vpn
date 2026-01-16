<?php
/**
 * Quick API Diagnostic
 * Tests if auth.php is working
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Required for config.php security check
define('TRUEVAULT_INIT', true);

header('Content-Type: text/html');

echo "<h1>API Diagnostic</h1>";

// Test 1: Check if files exist
echo "<h2>1. File Check</h2>";
$files = [
    '../configs/config.php',
    '../includes/Database.php',
    '../includes/Auth.php',
    '../api/auth.php'
];

foreach ($files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $status = $exists ? '✅' : '❌';
    echo "<p>{$status} {$file}</p>";
}

// Test 2: Try to load config
echo "<h2>2. Config Load</h2>";
try {
    require_once __DIR__ . '/../configs/config.php';
    echo "<p>✅ Config loaded</p>";
    echo "<p>DB_PATH: " . (defined('DB_PATH') ? DB_PATH : 'NOT DEFINED') . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Config error: " . $e->getMessage() . "</p>";
}

// Test 3: Try to load Database class
echo "<h2>3. Database Class</h2>";
try {
    require_once __DIR__ . '/../includes/Database.php';
    echo "<p>✅ Database.php loaded</p>";
    
    // Try to connect
    $db = Database::getInstance('main');
    echo "<p>✅ Connected to main.db</p>";
    
    // Check VIP table
    $vips = $db->queryAll("SELECT email FROM vip_users WHERE is_active = 1");
    echo "<p>✅ VIP users found: " . count($vips) . "</p>";
    foreach ($vips as $vip) {
        echo "<p>&nbsp;&nbsp;&nbsp;- " . htmlspecialchars($vip['email']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 4: Try to load Auth class
echo "<h2>4. Auth Class</h2>";
try {
    require_once __DIR__ . '/../includes/Auth.php';
    echo "<p>✅ Auth.php loaded</p>";
    
    // Test VIP check
    $isVIP = Auth::isVIP('seige235@yahoo.com');
    echo "<p>✅ VIP check for seige235@yahoo.com: " . ($isVIP ? 'YES' : 'NO') . "</p>";
    
    $isVIP2 = Auth::isVIP('random@example.com');
    echo "<p>✅ VIP check for random@example.com: " . ($isVIP2 ? 'YES' : 'NO') . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Auth error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 5: Test registration
echo "<h2>5. Test Registration</h2>";
try {
    Auth::init(JWT_SECRET);
    
    $testEmail = 'diagnostic_test_' . time() . '@example.com';
    $result = Auth::register($testEmail, 'TestPassword123', 'Diag', 'Test');
    
    echo "<p>Registration result:</p>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p>❌ Registration error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Done!</h2>";
echo "<p><a href='test-api.php'>Back to Test Page</a></p>";
?>
