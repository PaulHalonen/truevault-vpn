<?php
/**
 * Add Admin User - Run Once
 */
define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

$email = 'paulhalonen@gmail.com';
$password = 'Asasasas4!';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    $db = new SQLite3(DB_ADMIN);
    
    // Check if user exists
    $check = $db->querySingle("SELECT id FROM admin_users WHERE email = '$email'");
    
    if ($check) {
        echo "Admin user already exists!";
    } else {
        $stmt = $db->prepare("INSERT INTO admin_users (email, password_hash, role) VALUES (:email, :hash, 'super_admin')");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
        $stmt->execute();
        echo "✅ Admin user created: $email";
    }
    
    $db->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
