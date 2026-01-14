<?php
/**
 * TrueVault VPN - Admin Login
 * POST /api/auth/admin-login.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';

Response::requireMethod('POST');

$input = Response::getJsonInput();

if (empty($input['email']) || empty($input['password'])) {
    Response::error('Email and password required', 400);
}

$email = strtolower(trim($input['email']));
$password = $input['password'];

// Ensure admin_users table exists
$db = Database::getConnection('admin');
$db->exec("CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    name TEXT,
    role TEXT DEFAULT 'admin',
    status TEXT DEFAULT 'active',
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Find admin user
$admin = Database::queryOne('admin',
    "SELECT * FROM admin_users WHERE email = ?",
    [$email]
);

if (!$admin) {
    Response::error('Invalid credentials', 401);
}

// Verify password
if (!password_verify($password, $admin['password_hash'])) {
    Response::error('Invalid credentials', 401);
}

// Check status
if ($admin['status'] !== 'active') {
    Response::error('Admin account is ' . $admin['status'], 403);
}

// Update last login
Database::execute('admin',
    "UPDATE admin_users SET last_login = datetime('now') WHERE id = ?",
    [$admin['id']]
);

// Generate admin token (with is_admin flag)
$token = Auth::generateToken($admin['id'], $admin['email'], true);

Response::success([
    'token' => $token,
    'admin' => [
        'id' => $admin['id'],
        'email' => $admin['email'],
        'name' => $admin['name'],
        'role' => $admin['role']
    ]
], 'Admin login successful');
