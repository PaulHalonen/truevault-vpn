<?php
// Simple test file to diagnose 403 errors
echo "<h1>TrueVault Test Page</h1>";
echo "<p>If you can see this, PHP is working!</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";

// Test file existence
echo "<h2>File Check:</h2>";
$files = [
    'includes/content-functions.php',
    'templates/header.php',
    'templates/footer.php',
    'databases/content.db',
    'configs/config.php'
];

echo "<ul>";
foreach ($files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    echo "<li>{$file}: {$status}</li>";
}
echo "</ul>";
?>
