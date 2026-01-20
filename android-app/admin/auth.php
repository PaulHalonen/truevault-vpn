<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/login.php');
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: /admin/login.php?error=invalid');
    exit;
}

$db = getAdminDB();

// Get admin user
$stmt = $db->prepare("SELECT * FROM admin_users WHERE email = ?");
$stmt->execute([$email]);
$admin = $stmt->fetch();

if (!$admin) {
    header('Location: /admin/login.php?error=invalid');
    exit;
}

// Verify password
if (!password_verify($password, $admin['password'])) {
    header('Location: /admin/login.php?error=invalid');
    exit;
}

// Check if active
if ($admin['is_active'] != 1) {
    header('Location: /admin/login.php?error=inactive');
    exit;
}

// Create session
$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_email'] = $admin['email'];
$_SESSION['admin_name'] = $admin['name'];
$_SESSION['admin_role'] = $admin['role'];

// Generate session token
$token = bin2hex(random_bytes(32));
$_SESSION['admin_token'] = $token;

// Store session in database
$expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
$stmt = $db->prepare("
    INSERT INTO admin_sessions (admin_id, session_token, ip_address, user_agent, expires_at)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([
    $admin['id'],
    $token,
    $_SERVER['REMOTE_ADDR'] ?? null,
    $_SERVER['HTTP_USER_AGENT'] ?? null,
    $expiresAt
]);

// Update last login
$stmt = $db->prepare("UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->execute([$admin['id']]);

// Log activity
$stmt = $db->prepare("
    INSERT INTO activity_log (admin_id, action, ip_address)
    VALUES (?, 'admin_login', ?)
");
$stmt->execute([$admin['id'], $_SERVER['REMOTE_ADDR'] ?? null]);

// Redirect to dashboard
header('Location: /admin/index.php');
exit;
?>
