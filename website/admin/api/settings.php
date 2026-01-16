<?php
/**
 * TrueVault VPN - Admin Settings API
 * 
 * GET ?type=settings  - Get all settings
 * GET ?type=theme     - Get all theme values
 * GET ?type=vip       - Get VIP users
 * PUT ?type=settings  - Update settings
 * PUT ?type=theme     - Update theme values
 * POST ?type=vip      - Add VIP user
 * DELETE ?type=vip&id=X - Remove VIP user
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Verify admin token
Auth::init(JWT_SECRET);

$token = null;
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$payload = Auth::verifyToken($token);
if (!$payload || ($payload['type'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$mainDb = Database::getInstance('main');
$type = $_GET['type'] ?? 'settings';
$id = $_GET['id'] ?? null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($type === 'settings') {
            // Get all settings
            $settings = $mainDb->queryAll("SELECT * FROM settings ORDER BY setting_key");
            
            // Convert to key-value pairs
            $settingsMap = [];
            foreach ($settings as $s) {
                $settingsMap[$s['setting_key']] = [
                    'id' => $s['id'],
                    'value' => $s['setting_value'],
                    'type' => $s['setting_type'],
                    'description' => $s['description']
                ];
            }
            
            echo json_encode(['success' => true, 'settings' => $settingsMap]);
            
        } elseif ($type === 'theme') {
            // Get all theme values
            $theme = $mainDb->queryAll("SELECT * FROM theme ORDER BY element_category, element_name");
            
            // Group by category
            $themeGrouped = [];
            foreach ($theme as $t) {
                $cat = $t['element_category'] ?? 'other';
                if (!isset($themeGrouped[$cat])) {
                    $themeGrouped[$cat] = [];
                }
                $themeGrouped[$cat][] = [
                    'id' => $t['id'],
                    'name' => $t['element_name'],
                    'value' => $t['element_value'],
                    'description' => $t['description']
                ];
            }
            
            echo json_encode(['success' => true, 'theme' => $themeGrouped]);
            
        } elseif ($type === 'vip') {
            // Get VIP users
            $vipUsers = $mainDb->queryAll("SELECT * FROM vip_users ORDER BY added_date DESC");
            echo json_encode(['success' => true, 'vip_users' => $vipUsers]);
            
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid type']);
        }
        break;
        
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($type === 'settings') {
            // Update settings
            if (!is_array($input)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid data']);
                exit;
            }
            
            foreach ($input as $key => $value) {
                $mainDb->query(
                    "UPDATE settings SET setting_value = ?, updated_at = ? WHERE setting_key = ?",
                    [$value, date('Y-m-d H:i:s'), $key]
                );
            }
            
            echo json_encode(['success' => true, 'message' => 'Settings updated']);
            
        } elseif ($type === 'theme') {
            // Update theme values
            if (!is_array($input)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid data']);
                exit;
            }
            
            foreach ($input as $name => $value) {
                $mainDb->query(
                    "UPDATE theme SET element_value = ?, updated_at = ? WHERE element_name = ?",
                    [$value, date('Y-m-d H:i:s'), $name]
                );
            }
            
            echo json_encode(['success' => true, 'message' => 'Theme updated']);
            
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid type']);
        }
        break;
        
    case 'POST':
        if ($type === 'vip') {
            // Add VIP user
            $input = json_decode(file_get_contents('php://input'), true);
            
            $email = trim($input['email'] ?? '');
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Valid email required']);
                exit;
            }
            
            // Check if already exists
            $existing = $mainDb->queryOne("SELECT id FROM vip_users WHERE email = ?", [$email]);
            if ($existing) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Email already in VIP list']);
                exit;
            }
            
            $vipId = $mainDb->insert('vip_users', [
                'email' => $email,
                'added_by' => $payload['email'],
                'notes' => $input['notes'] ?? '',
                'dedicated_server_id' => $input['dedicated_server_id'] ?? null,
                'max_devices' => $input['max_devices'] ?? 999,
                'is_active' => 1,
                'added_date' => date('Y-m-d H:i:s')
            ]);
            
            // Update user if exists
            $usersDb = Database::getInstance('users');
            $usersDb->query(
                "UPDATE users SET account_type = 'vip', max_devices = ?, status = 'active', updated_at = ? WHERE email = ?",
                [$input['max_devices'] ?? 999, date('Y-m-d H:i:s'), $email]
            );
            
            echo json_encode([
                'success' => true,
                'vip_id' => $vipId,
                'message' => 'VIP user added'
            ]);
            
        } elseif ($type === 'settings') {
            // Add new setting
            $input = json_decode(file_get_contents('php://input'), true);
            
            $key = trim($input['key'] ?? '');
            $value = $input['value'] ?? '';
            
            if (!$key) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Setting key required']);
                exit;
            }
            
            $mainDb->insert('settings', [
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_type' => $input['type'] ?? 'string',
                'description' => $input['description'] ?? '',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Setting added']);
            
        } elseif ($type === 'theme') {
            // Add new theme element
            $input = json_decode(file_get_contents('php://input'), true);
            
            $name = trim($input['name'] ?? '');
            $value = $input['value'] ?? '';
            
            if (!$name) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Element name required']);
                exit;
            }
            
            $mainDb->insert('theme', [
                'element_name' => $name,
                'element_value' => $value,
                'element_category' => $input['category'] ?? 'other',
                'description' => $input['description'] ?? '',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Theme element added']);
            
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid type']);
        }
        break;
        
    case 'DELETE':
        if ($type === 'vip' && $id) {
            // Remove VIP user
            $vip = $mainDb->queryOne("SELECT email FROM vip_users WHERE id = ?", [$id]);
            
            if (!$vip) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'VIP user not found']);
                exit;
            }
            
            // Don't allow removing admin
            $adminEmail = $mainDb->queryValue(
                "SELECT setting_value FROM settings WHERE setting_key = 'admin_email'"
            );
            if ($vip['email'] === $adminEmail) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Cannot remove admin from VIP list']);
                exit;
            }
            
            $mainDb->query("DELETE FROM vip_users WHERE id = ?", [$id]);
            
            // Update user account type
            $usersDb = Database::getInstance('users');
            $usersDb->query(
                "UPDATE users SET account_type = 'standard', updated_at = ? WHERE email = ?",
                [date('Y-m-d H:i:s'), $vip['email']]
            );
            
            echo json_encode(['success' => true, 'message' => 'VIP user removed']);
            
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
