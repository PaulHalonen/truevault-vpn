<?php
/**
 * TrueVault VPN - User Profile API
 * GET /api/user/profile.php - Get profile
 * PUT /api/user/profile.php - Update profile
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

// Require authentication
$user = Auth::requireAuth();
if (!$user) exit;

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get full user profile
            $profile = Database::queryOne('users',
                "SELECT id, uuid, email, first_name, last_name, status, is_vip, created_at, last_login
                 FROM users WHERE id = ?",
                [$user['id']]
            );
            
            if (!$profile) {
                Response::notFound('User not found');
            }
            
            // Get subscription info
            $subscription = Database::queryOne('billing',
                "SELECT plan_type, status, trial_ends_at, current_period_end
                 FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
                [$user['id']]
            );
            
            // Get device count
            $deviceCount = Database::queryOne('users',
                "SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND is_active = 1",
                [$user['id']]
            );
            
            // Check VIP status
            $isVIP = VIPManager::isVIP($profile['email']);
            $vipDetails = $isVIP ? VIPManager::getVIPDetails($profile['email']) : null;
            
            Response::success([
                'user' => [
                    'id' => (int)$profile['id'],
                    'uuid' => $profile['uuid'],
                    'email' => $profile['email'],
                    'first_name' => $profile['first_name'],
                    'last_name' => $profile['last_name'],
                    'status' => $profile['status'],
                    'is_vip' => (bool)$profile['is_vip'] || $isVIP,
                    'vip_tier' => $vipDetails['tier'] ?? null,
                    'created_at' => $profile['created_at'],
                    'last_login' => $profile['last_login']
                ],
                'subscription' => $subscription ? [
                    'plan_type' => $subscription['plan_type'],
                    'status' => $subscription['status'],
                    'trial_ends_at' => $subscription['trial_ends_at'],
                    'current_period_end' => $subscription['current_period_end']
                ] : null,
                'stats' => [
                    'device_count' => (int)($deviceCount['count'] ?? 0)
                ]
            ]);
            break;
            
        case 'PUT':
            // Update profile
            $input = Response::getJsonInput();
            
            $updates = [];
            $params = [];
            
            // Only allow updating certain fields
            if (isset($input['first_name'])) {
                $updates[] = "first_name = ?";
                $params[] = trim($input['first_name']);
            }
            
            if (isset($input['last_name'])) {
                $updates[] = "last_name = ?";
                $params[] = trim($input['last_name']);
            }
            
            if (empty($updates)) {
                Response::error('No valid fields to update', 400);
            }
            
            $params[] = $user['id'];
            
            Database::execute('users',
                "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?",
                $params
            );
            
            // Get updated profile
            $updated = Database::queryOne('users',
                "SELECT id, email, first_name, last_name, status, is_vip, created_at
                 FROM users WHERE id = ?",
                [$user['id']]
            );
            
            Response::success([
                'user' => $updated
            ], 'Profile updated successfully');
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    Response::serverError('Profile operation failed: ' . $e->getMessage());
}
