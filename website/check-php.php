<?php
// Check PHP configuration
echo '<h1>PHP Configuration Check</h1>';
echo '<h2>PDO Available:</h2>';
echo extension_loaded('pdo') ? '✅ YES' : '❌ NO';
echo '<br>';
echo '<h2>PDO SQLite Available:</h2>';
echo extension_loaded('pdo_sqlite') ? '✅ YES' : '❌ NO';
echo '<br>';
echo '<h2>SQLite3 Available:</h2>';
echo extension_loaded('sqlite3') ? '✅ YES' : '❌ NO';
echo '<br><br>';
echo '<h2>All Loaded Extensions:</h2>';
echo '<pre>';
print_r(get_loaded_extensions());
echo '</pre>';
?>
