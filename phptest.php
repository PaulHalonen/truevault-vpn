<?php
// PHP Info Test
echo "<h2>PHP Version: " . phpversion() . "</h2>";

echo "<h3>PDO Status:</h3>";
if (class_exists('PDO')) {
    echo "<p style='color:green'>✅ PDO is available</p>";
    echo "<p>Available drivers: " . implode(', ', PDO::getAvailableDrivers()) . "</p>";
} else {
    echo "<p style='color:red'>❌ PDO is NOT available</p>";
}

echo "<h3>SQLite3 Status:</h3>";
if (class_exists('SQLite3')) {
    echo "<p style='color:green'>✅ SQLite3 is available</p>";
} else {
    echo "<p style='color:red'>❌ SQLite3 is NOT available</p>";
}

echo "<h3>Loaded Extensions:</h3>";
$extensions = get_loaded_extensions();
sort($extensions);
echo "<p>" . implode(', ', $extensions) . "</p>";

echo "<hr><p><a href='?full'>Click here for full phpinfo()</a></p>";

if (isset($_GET['full'])) {
    phpinfo();
}
