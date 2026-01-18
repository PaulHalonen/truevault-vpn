<?php
/**
 * Admin Login Diagnostic - Check what's in the database
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Admin Login Diagnostic</h1>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#fff;padding:20px;} .ok{color:#0f0;} .err{color:#f55;} pre{background:#000;padding:10px;overflow:auto;}</style>";

$dbPath = __DIR__ . '/../databases/';

// Check if databases exist
echo "<h2>1. Database Files</h2>";
$dbFiles = ['admin.db', 'main.db', 'logs.db', 'servers.db', 'devices.db', 'billing.db', 'support.db', 'port_forwards.db'];
foreach ($dbFiles as $file) {
    $path = $dbPath . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "<p class='ok'>✓ {$file} exists ({$size} bytes)</p>";
    } else {
        echo "<p class='err'>✗ {$file} NOT FOUND</p>";
    }
}

// Check admin.db contents
echo "<h2>2. Admin Users Table</h2>";
try {
    $adminDb = new SQLite3($dbPath . 'admin.db');
    $adminDb->enableExceptions(true);
    
    // Check if table exists
    $tables = $adminDb->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='admin_users'");
    if ($tables) {
        echo "<p class='ok'>✓ admin_users table exists</p>";
        
        // Get all admin users
        $result = $adminDb->query("SELECT id, email, name, role, is_active, password_hash, last_login FROM admin_users");
        echo "<h3>Admin Users:</h3><pre>";
        $count = 0;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $count++;
            echo "ID: {$row['id']}\n";
            echo "Email: {$row['email']}\n";
            echo "Name: {$row['name']}\n";
            echo "Role: {$row['role']}\n";
            echo "Active: {$row['is_active']}\n";
            echo "Password Hash: " . substr($row['password_hash'], 0, 20) . "...\n";
            echo "Last Login: {$row['last_login']}\n";
            
            // Test password verification
            $testPassword = 'Athena8';
            $hashValid = password_verify($testPassword, $row['password_hash']);
            echo "Password 'Athena8' valid: " . ($hashValid ? 'YES' : 'NO') . "\n";
            echo "---\n";
        }
        echo "</pre>";
        echo "<p>Total admin users: {$count}</p>";
    } else {
        echo "<p class='err'>✗ admin_users table NOT FOUND</p>";
    }
    
    $adminDb->close();
} catch (Exception $e) {
    echo "<p class='err'>Error reading admin.db: " . $e->getMessage() . "</p>";
}

// Test creating a new password hash
echo "<h2>3. Password Hash Test</h2>";
$newHash = password_hash('Athena8', PASSWORD_DEFAULT);
echo "<p>New hash for 'Athena8': <code>{$newHash}</code></p>";
echo "<p>Verify new hash: " . (password_verify('Athena8', $newHash) ? '<span class="ok">OK</span>' : '<span class="err">FAIL</span>') . "</p>";

// Check Database.php
echo "<h2>4. Database Class Check</h2>";
$dbClassPath = __DIR__ . '/../includes/Database.php';
if (file_exists($dbClassPath)) {
    echo "<p class='ok'>✓ Database.php exists</p>";
    
    // Check if getInstance exists
    $content = file_get_contents($dbClassPath);
    if (strpos($content, 'function getInstance') !== false) {
        echo "<p class='ok'>✓ getInstance method found</p>";
    } else {
        echo "<p class='err'>✗ getInstance method NOT FOUND</p>";
    }
    
    // Try to use it
    require_once $dbClassPath;
    if (method_exists('Database', 'getInstance')) {
        echo "<p class='ok'>✓ Database::getInstance() is callable</p>";
        try {
            $db = Database::getInstance('admin');
            echo "<p class='ok'>✓ Database::getInstance('admin') works</p>";
        } catch (Exception $e) {
            echo "<p class='err'>✗ Error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='err'>✗ Database::getInstance() is NOT callable</p>";
    }
} else {
    echo "<p class='err'>✗ Database.php NOT FOUND</p>";
}

// Fix option
echo "<h2>5. Quick Fix - Reset Admin Password</h2>";
if (isset($_GET['fix']) && $_GET['fix'] === 'yes') {
    try {
        $adminDb = new SQLite3($dbPath . 'admin.db');
        $adminDb->enableExceptions(true);
        
        $newHash = password_hash('Athena8', PASSWORD_DEFAULT);
        $stmt = $adminDb->prepare("UPDATE admin_users SET password_hash = ? WHERE email = ?");
        $stmt->bindValue(1, $newHash, SQLITE3_TEXT);
        $stmt->bindValue(2, 'paulhalonen@gmail.com', SQLITE3_TEXT);
        $stmt->execute();
        
        $changes = $adminDb->changes();
        $adminDb->close();
        
        if ($changes > 0) {
            echo "<p class='ok'>✓ Password reset successful! Try logging in now.</p>";
        } else {
            echo "<p class='err'>✗ No rows updated. User may not exist.</p>";
        }
    } catch (Exception $e) {
        echo "<p class='err'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p><a href='?fix=yes' style='color:#0ff;'>Click here to reset password for paulhalonen@gmail.com to 'Athena8'</a></p>";
}

// Link to setup
echo "<h2>6. Actions</h2>";
echo "<p><a href='setup-databases-FIXED.php' style='color:#0ff;'>Run Full Database Setup (FIXED version)</a></p>";
echo "<p><a href='index.html' style='color:#0ff;'>Go to Admin Login</a></p>";
?>
