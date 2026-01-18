<?php
/**
 * TrueVault VPN - Admin Users Management API
 * 
 * Endpoints:
 * GET  ?action=list     - List all admin users
 * POST ?action=create   - Create new admin user
 * POST ?action=update   - Update admin user
 * POST ?action=delete   - Delete/deactivate admin user
 * POST ?action=password - Change admin password
 * 
 * @created January 17, 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Initialize Auth
Auth::init(JWT_SECRET);

// Verify admin token
$token = null;
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if ($authHeader) {
    $token = str_replace('Bearer ', '', $authHeader);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$result = Auth::verifyToken($token);
if (!$result['valid'] || ($result['payload']['type'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$currentAdmin = $result['payload'];
$action = $_GET['action'] ?? '';

try {
    $adminDb = Database::getInstance('admin');
    
    switch ($action) {
        case 'list':
            $admins = $adminDb->queryAll(
                "SELECT id, email, name, role, is_active, last_login, created_at 
                 FROM admin_users 
                 ORDER BY created_at DESC"
            );
            echo json_encode(['success' => true, 'admins' => $admins]);
            break;
            
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'POST required']);
                exit;
            }
            
            // Only superadmin can create admins
            if ($currentAdmin['role'] !== 'superadmin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Only superadmin can create admin users']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $email = strtolower(trim($input['email'] ?? ''));
            $password = $input['password'] ?? '';
            $name = trim($input['name'] ?? '');
            $role = $input['role'] ?? 'admin';
            
            if (!$email || !$password || !$name) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Email, password, and name are required']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid email address']);
                exit;
            }
            
            if (strlen($password) < 6) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
                exit;
            }
            
            // Check if email already exists
            $escaped = trim($adminDb->escape($email), "'");
            $existing = $adminDb->queryOne("SELECT id FROM admin_users WHERE LOWER(email) = LOWER('{$escaped}')");
            if ($existing) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Email already exists']);
                exit;
            }
            
            // Validate role
            $validRoles = ['admin', 'superadmin'];
            if (!in_array($role, $validRoles)) {
                $role = 'admin';
            }
            
            // Create admin
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $adminId = $adminDb->insert('admin_users', [
                'email' => $email,
                'password_hash' => $passwordHash,
                'name' => $name,
                'role' => $role,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Log the action
            try {
                $logsDb = Database::getInstance('logs');
                $logsDb->insert('activity_logs', [
                    'user_id' => $currentAdmin['user_id'],
                    'action' => 'admin_created',
                    'entity_type' => 'admin',
                    'entity_id' => $adminId,
                    'details' => json_encode(['email' => $email, 'role' => $role, 'created_by' => $currentAdmin['email']]),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $e) {}
            
            echo json_encode([
                'success' => true, 
                'message' => 'Admin user created successfully',
                'admin_id' => $adminId
            ]);
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'POST required']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $adminId = (int)($input['id'] ?? 0);
            
            if (!$adminId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Admin ID required']);
                exit;
            }
            
            // Get admin to update
            $admin = $adminDb->queryOne("SELECT * FROM admin_users WHERE id = {$adminId}");
            if (!$admin) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Admin not found']);
                exit;
            }
            
            // Only superadmin can update other admins
            if ($currentAdmin['role'] !== 'superadmin' && $currentAdmin['user_id'] != $adminId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permission denied']);
                exit;
            }
            
            $updateData = [];
            if (isset($input['name']) && trim($input['name'])) {
                $updateData['name'] = trim($input['name']);
            }
            if (isset($input['role']) && in_array($input['role'], ['admin', 'superadmin'])) {
                $updateData['role'] = $input['role'];
            }
            if (isset($input['is_active'])) {
                $updateData['is_active'] = $input['is_active'] ? 1 : 0;
            }
            
            if (empty($updateData)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No valid fields to update']);
                exit;
            }
            
            $adminDb->update('admin_users', $updateData, "id = {$adminId}");
            
            echo json_encode(['success' => true, 'message' => 'Admin updated successfully']);
            break;
            
        case 'password':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'POST required']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $adminId = (int)($input['id'] ?? 0);
            $newPassword = $input['password'] ?? '';
            
            if (!$adminId || !$newPassword) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Admin ID and new password required']);
                exit;
            }
            
            if (strlen($newPassword) < 6) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
                exit;
            }
            
            // Only superadmin can change other admins' passwords
            if ($currentAdmin['role'] !== 'superadmin' && $currentAdmin['user_id'] != $adminId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permission denied']);
                exit;
            }
            
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $adminDb->update('admin_users', ['password_hash' => $passwordHash], "id = {$adminId}");
            
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'POST required']);
                exit;
            }
            
            // Only superadmin can delete
            if ($currentAdmin['role'] !== 'superadmin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Only superadmin can delete admin users']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $adminId = (int)($input['id'] ?? 0);
            
            if (!$adminId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Admin ID required']);
                exit;
            }
            
            // Can't delete yourself
            if ($currentAdmin['user_id'] == $adminId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Cannot delete your own account']);
                exit;
            }
            
            // Soft delete - just deactivate
            $adminDb->update('admin_users', ['is_active' => 0], "id = {$adminId}");
            
            echo json_encode(['success' => true, 'message' => 'Admin deactivated successfully']);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action. Use: list, create, update, password, delete']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
