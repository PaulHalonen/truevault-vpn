<?php
/**
 * TrueVault VPN - Admin Users API
 * GET/POST/PUT/DELETE /api/admin/users.php
 * 
 * Manages all user accounts
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/logger.php';

// Require admin authentication
$token = JWTManager::getBearerToken();
if (!$token) {
    Response::unauthorized('No token provided');
}

$payload = JWTManager::validateToken($token);
if (!$payload || !isset($payload['is_admin']) || !$payload['is_admin']) {
    Response::forbidden('Admin access required');
}

$method = Response::getMethod();

try {
    $usersDb = DatabaseManager::getInstance()->users();
    $subscriptionsDb = DatabaseManager::getInstance()->subscriptions();
    
    switch ($method) {
        case 'GET':
            // Get users list or single user
            $userId = $_GET['id'] ?? null;
            
            if ($userId) {
                // Get single user
                $stmt = $usersDb->prepare("
                    SELECT id, email, first_name, last_name, plan_type, 
                           subscription_status, is_vip, email_verified, 
                           created_at, last_login
                    FROM users 
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    Response::notFound('User not found');
                }
                
                // Get device count
                $stmt = $usersDb->prepare("SELECT COUNT(*) FROM user_devices WHERE user_id = ?");
                $stmt->execute([$userId]);
                $user['device_count'] = (int)$stmt->fetchColumn();
                
                Response::success(['user' => $user]);
            } else {
                // Get all users with pagination
                $page = (int)($_GET['page'] ?? 1);
                $perPage = (int)($_GET['per_page'] ?? 20);
                $offset = ($page - 1) * $perPage;
                
                $search = $_GET['search'] ?? '';
                $status = $_GET['status'] ?? '';
                $plan = $_GET['plan'] ?? '';
                
                $where = '1=1';
                $params = [];
                
                if ($search) {
                    $where .= " AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                
                if ($status) {
                    $where .= " AND subscription_status = ?";
                    $params[] = $status;
                }
                
                if ($plan) {
                    $where .= " AND plan_type = ?";
                    $params[] = $plan;
                }
                
                // Get total count
                $countStmt = $usersDb->prepare("SELECT COUNT(*) FROM users WHERE $where");
                $countStmt->execute($params);
                $total = (int)$countStmt->fetchColumn();
                
                // Get users
                $stmt = $usersDb->prepare("
                    SELECT id, email, first_name, last_name, plan_type, 
                           subscription_status, is_vip, created_at, last_login
                    FROM users 
                    WHERE $where
                    ORDER BY created_at DESC
                    LIMIT ? OFFSET ?
                ");
                $allParams = array_merge($params, [$perPage, $offset]);
                $stmt->execute($allParams);
                $users = $stmt->fetchAll();
                
                // Get device counts
                foreach ($users as &$user) {
                    $stmt = $usersDb->prepare("SELECT COUNT(*) FROM user_devices WHERE user_id = ?");
                    $stmt->execute([$user['id']]);
                    $user['device_count'] = (int)$stmt->fetchColumn();
                }
                
                Response::success([
                    'users' => $users,
                    'pagination' => [
                        'total' => $total,
                        'page' => $page,
                        'per_page' => $perPage,
                        'total_pages' => ceil($total / $perPage)
                    ],
                    'stats' => [
                        'total' => $total,
                        'active' => countByStatus($usersDb, 'active'),
                        'trial' => countByStatus($usersDb, 'trial'),
                        'vip' => countVip($usersDb)
                    ]
                ]);
            }
            break;
            
        case 'POST':
            // Create new user
            $input = Response::getJsonInput();
            
            $required = ['email', 'password', 'first_name', 'last_name'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    Response::error("$field is required", 400);
                }
            }
            
            // Check if email exists
            $stmt = $usersDb->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$input['email']]);
            if ($stmt->fetch()) {
                Response::error('Email already exists', 400);
            }
            
            $planType = $input['plan_type'] ?? 'personal';
            $status = $input['subscription_status'] ?? 'trial';
            
            $stmt = $usersDb->prepare("
                INSERT INTO users (email, password_hash, first_name, last_name, plan_type, subscription_status, is_vip)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $input['email'],
                password_hash($input['password'], PASSWORD_DEFAULT),
                $input['first_name'],
                $input['last_name'],
                $planType,
                $status,
                $input['is_vip'] ?? 0
            ]);
            
            $userId = $usersDb->lastInsertId();
            
            Logger::info('Admin created user', [
                'admin_id' => $payload['sub'],
                'user_id' => $userId,
                'email' => $input['email']
            ]);
            
            Response::success(['user_id' => $userId], 'User created successfully', 201);
            break;
            
        case 'PUT':
            // Update user
            $input = Response::getJsonInput();
            
            if (empty($input['id'])) {
                Response::error('User ID required', 400);
            }
            
            // Check user exists
            $stmt = $usersDb->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$input['id']]);
            if (!$stmt->fetch()) {
                Response::notFound('User not found');
            }
            
            $updates = [];
            $params = [];
            
            $allowedFields = ['email', 'first_name', 'last_name', 'plan_type', 'subscription_status', 'is_vip'];
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            // Handle password update
            if (!empty($input['password'])) {
                $updates[] = "password_hash = ?";
                $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($updates)) {
                Response::error('No fields to update', 400);
            }
            
            $params[] = $input['id'];
            $stmt = $usersDb->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($params);
            
            Logger::info('Admin updated user', [
                'admin_id' => $payload['sub'],
                'user_id' => $input['id']
            ]);
            
            Response::success([], 'User updated successfully');
            break;
            
        case 'DELETE':
            // Delete user
            $userId = $_GET['id'] ?? null;
            
            if (!$userId) {
                Response::error('User ID required', 400);
            }
            
            // Check user exists
            $stmt = $usersDb->prepare("SELECT id, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                Response::notFound('User not found');
            }
            
            // Delete user (cascade will handle related data)
            $stmt = $usersDb->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            Logger::info('Admin deleted user', [
                'admin_id' => $payload['sub'],
                'user_id' => $userId,
                'email' => $user['email']
            ]);
            
            Response::success([], 'User deleted successfully');
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    Logger::error('Admin users error: ' . $e->getMessage());
    Response::serverError('Failed to process request');
}

function countByStatus($db, $status) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE subscription_status = ?");
    $stmt->execute([$status]);
    return (int)$stmt->fetchColumn();
}

function countVip($db) {
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_vip = 1");
    return (int)$stmt->fetchColumn();
}
