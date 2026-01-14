<?php
/**
 * TrueVault VPN - Master Setup Script
 * Runs all database setup scripts and initializes the system
 * 
 * ACCESS: /api/setup-all.php?key=TrueVault2026Setup
 */

// Security check
$setupKey = 'TrueVault2026Setup';
if (!isset($_GET['key']) || $_GET['key'] !== $setupKey) {
    http_response_code(403);
    die('Forbidden - Setup key required');
}

echo "<!DOCTYPE html><html><head><title>TrueVault Setup</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;}";
echo "h1{color:#00d9ff;}h2{color:#ffd700;margin-top:30px;}";
echo ".success{color:#00ff88;}.error{color:#ff6b6b;}</style></head><body>";

echo "<h1>🔐 TrueVault VPN - Master Setup</h1>";
echo "<p>Started: " . date('Y-m-d H:i:s') . "</p>";

$results = [];

// Run each setup script
$setupScripts = [
    'Main Database' => __DIR__ . '/config/setup-databases.php',
    'VIP Database' => __DIR__ . '/config/setup-vip.php',
    'Billing Database' => __DIR__ . '/billing/setup-billing.php',
    'Devices Database' => __DIR__ . '/devices/setup-devices.php'
];

foreach ($setupScripts as $name => $script) {
    echo "<h2>📦 {$name}</h2>";
    
    if (file_exists($script)) {
        ob_start();
        try {
            include $script;
            $output = ob_get_clean();
            echo "<pre>" . strip_tags($output) . "</pre>";
            $results[$name] = 'success';
        } catch (Exception $e) {
            ob_end_clean();
            echo "<p class='error'>❌ ERROR: " . $e->getMessage() . "</p>";
            $results[$name] = 'error: ' . $e->getMessage();
        }
    } else {
        echo "<p class='error'>❌ Script not found: {$script}</p>";
        $results[$name] = 'not found';
    }
}

// Create data directories
echo "<h2>📁 Creating Directories</h2>";
$directories = [
    __DIR__ . '/../data',
    __DIR__ . '/../data/backups',
    __DIR__ . '/../data/logs',
    __DIR__ . '/../data/temp'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p class='success'>✓ Created: {$dir}</p>";
        } else {
            echo "<p class='error'>✗ Failed to create: {$dir}</p>";
        }
    } else {
        echo "<p class='success'>✓ Exists: {$dir}</p>";
    }
}

// Verify all required files exist
echo "<h2>✅ Verifying Core Files</h2>";
$requiredFiles = [
    'api/config/database.php' => 'Database config',
    'api/helpers/response.php' => 'Response helper',
    'api/helpers/auth.php' => 'Auth helper',
    'api/helpers/vip.php' => 'VIP helper',
    'api/billing/billing-manager.php' => 'Billing manager',
    'api/billing/webhook.php' => 'PayPal webhook',
    'api/devices/device-manager.php' => 'Device manager',
    'api/cameras/camera-manager.php' => 'Camera manager',
    'api/vpn/config.php' => 'VPN config generator',
    'api/vpn/servers.php' => 'Servers API',
    'api/cron/process.php' => 'Cron processor'
];

foreach ($requiredFiles as $file => $name) {
    $fullPath = __DIR__ . '/../' . $file;
    if (file_exists($fullPath)) {
        echo "<p class='success'>✓ {$name}</p>";
    } else {
        echo "<p class='error'>✗ {$name} - MISSING!</p>";
    }
}

// Summary
echo "<h2>📊 Setup Summary</h2>";
$successCount = count(array_filter($results, fn($r) => $r === 'success'));
$totalCount = count($results);

echo "<p>Database setups: {$successCount}/{$totalCount} successful</p>";

if ($successCount === $totalCount) {
    echo "<p class='success' style='font-size:1.5em'>✅ SETUP COMPLETE!</p>";
    echo "<p>Next steps:</p>";
    echo "<ol>";
    echo "<li>Configure PayPal webhook: <code>https://vpn.the-truth-publishing.com/api/billing/webhook.php</code></li>";
    echo "<li>Set up cron job: <code>*/5 * * * * php /path/to/api/cron/process.php</code></li>";
    echo "<li>Deploy peer_api.py to all 4 VPN servers</li>";
    echo "<li>Test VPN connection with a test account</li>";
    echo "</ol>";
} else {
    echo "<p class='error' style='font-size:1.5em'>⚠️ SETUP INCOMPLETE - Check errors above</p>";
}

echo "<p>Finished: " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
