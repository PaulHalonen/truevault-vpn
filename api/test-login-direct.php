<?php
/**
 * Direct login test
 */
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Login Test ===\n\n";

// Load required files one by one
echo "Loading files:\n";

$configDb = __DIR__ . '/config/database.php';
echo "1. database.php: " . (file_exists($configDb) ? 'EXISTS' : 'MISSING') . "\n";
if (file_exists($configDb)) {
    require_once $configDb;
    echo "   Database class exists: " . (class_exists('Database') ? 'YES' : 'NO') . "\n";
}

$configJwt = __DIR__ . '/config/jwt.php';
echo "2. jwt.php: " . (file_exists($configJwt) ? 'EXISTS' : 'MISSING') . "\n";
if (file_exists($configJwt)) {
    require_once $configJwt;
    echo "   JWTManager class exists: " . (class_exists('JWTManager') ? 'YES' : 'NO') . "\n";
}

$helperResponse = __DIR__ . '/helpers/response.php';
echo "3. response.php: " . (file_exists($helperResponse) ? 'EXISTS' : 'MISSING') . "\n";
if (file_exists($helperResponse)) {
    require_once $helperResponse;
    echo "   Response class exists: " . (class_exists('Response') ? 'YES' : 'NO') . "\n";
}

$helperAuth = __DIR__ . '/helpers/auth.php';
echo "4. auth.php: " . (file_exists($helperAuth) ? 'EXISTS' : 'MISSING') . "\n";
if (file_exists($helperAuth)) {
    require_once $helperAuth;
    echo "   Auth class exists: " . (class_exists('Auth') ? 'YES' : 'NO') . "\n";
}

$helperVip = __DIR__ . '/helpers/vip.php';
echo "5. vip.php: " . (file_exists($helperVip) ? 'EXISTS' : 'MISSING') . "\n";
if (file_exists($helperVip)) {
    require_once $helperVip;
    echo "   VIPManager class exists: " . (class_exists('VIPManager') ? 'YES' : 'NO') . "\n";
    echo "   PlanLimits class exists: " . (class_exists('PlanLimits') ? 'YES' : 'NO') . "\n";
    echo "   ServerRules class exists: " . (class_exists('ServerRules') ? 'YES' : 'NO') . "\n";
}

// Test login with hardcoded credentials
echo "\n=== Testing Login ===\n";
$testEmail = 'paulhalonen@gmail.com';
$testPassword = 'Asasasas4!';

// Check VIP status
echo "\n1. VIP Check:\n";
$isVip = VIPManager::isVIP($testEmail);
echo "   Is VIP: " . ($isVip ? 'YES' : 'NO') . "\n";
if ($isVip) {
    $vipDetails = VIPManager::getVIPDetails($testEmail);
    echo "   Tier: " . ($vipDetails['tier'] ?? 'N/A') . "\n";
}

// Get user from database
echo "\n2. User Lookup:\n";
try {
    $user = Auth::getUserByEmail($testEmail);
    if ($user) {
        echo "   Found user: YES\n";
        echo "   ID: {$user['id']}\n";
        echo "   Email: {$user['email']}\n";
        echo "   Has password: " . (!empty($user['password']) ? 'YES' : 'NO') . "\n";
        
        // Test password
        echo "\n3. Password Check:\n";
        $passwordValid = password_verify($testPassword, $user['password']);
        echo "   Password valid: " . ($passwordValid ? 'YES' : 'NO') . "\n";
        
        if (!$passwordValid) {
            echo "   Stored hash: " . substr($user['password'], 0, 20) . "...\n";
        }
    } else {
        echo "   Found user: NO\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== END ===\n";
