<?php
/**
 * TrueVault VPN - Mesh Network API
 * Manages family/team mesh networks with shared resources
 * 
 * FIXED: January 14, 2026 - Use Database static methods instead of PDO
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/response.php';

// Require authentication
$user = Auth::requireAuth();
if (!$user) exit;

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get user's mesh network with members and resources
            $network = Database::queryOne('mesh', "
                SELECT * FROM mesh_networks 
                WHERE owner_id = ? OR id IN (
                    SELECT network_id FROM mesh_members WHERE user_id = ?
                )
                LIMIT 1
            ", [$user['id'], $user['id']]);
            
            if (!$network) {
                // No network yet - return empty data
                Response::success([
                    'has_network' => false,
                    'members' => [],
                    'resources' => [],
                    'network' => null
                ]);
                exit;
            }
            
            // Get members
            $memberRows = Database::query('mesh', "
                SELECT id, user_id, role, permission, status, joined_at
                FROM mesh_members
                WHERE network_id = ?
                ORDER BY role = 'owner' DESC, joined_at ASC
            ", [$network['id']]);
            
            // Get user info for each member
            $members = [];
            foreach ($memberRows as $m) {
                $userInfo = Database::queryOne('users', 
                    "SELECT email, first_name, last_name FROM users WHERE id = ?", 
                    [$m['user_id']]
                );
                
                $members[] = [
                    'id' => $m['id'],
                    'user_id' => $m['user_id'],
                    'name' => $userInfo ? trim($userInfo['first_name'] . ' ' . $userInfo['last_name']) : 'Unknown',
                    'email' => $userInfo['email'] ?? '',
                    'role' => $m['role'],
                    'permission' => $m['permission'] ?? 'member',
                    'status' => $m['status'] ?? 'active',
                    'is_online' => false,
                    'device_count' => 0,
                    'joined_at' => $m['joined_at']
                ];
            }
            
            // Get pending invitations
            $invites = Database::query('mesh', "
                SELECT id, email, permissions, created_at 
                FROM mesh_invitations 
                WHERE network_id = ? AND status = 'pending'
            ", [$network['id']]);
            
            foreach ($invites as $inv) {
                $members[] = [
                    'id' => 'inv_' . $inv['id'],
                    'name' => 'Pending Invite',
                    'email' => $inv['email'],
                    'role' => 'invited',
                    'permission' => $inv['permissions'] ?? 'member',
                    'status' => 'pending',
                    'is_online' => false,
                    'device_count' => 0,
                    'invited_at' => $inv['created_at']
                ];
            }
            
            // Get shared resources
            $resources = Database::query('mesh', 
                "SELECT * FROM shared_resources WHERE network_id = ?", 
                [$network['id']]
            );
            
            // Format resources
            $formattedResources = [];
            foreach ($resources as $r) {
                $sharedBy = 'Unknown';
                if ($r['owner_id']) {
                    $owner = Database::queryOne('users', 
                        "SELECT first_name FROM users WHERE id = ?", 
                        [$r['owner_id']]
                    );
                    if ($owner) $sharedBy = $owner['first_name'];
                }
                
                $formattedResources[] = [
                    'id' => $r['id'],
                    'name' => $r['name'],
                    'type' => $r['type'] ?? 'other',
                    'local_ip' => $r['local_ip'],
                    'access_level' => $r['access_level'] ?? 'Full access',
                    'shared_by' => $sharedBy,
                    'created_at' => $r['created_at']
                ];
            }
            
            Response::success([
                'has_network' => true,
                'network' => [
                    'id' => $network['id'],
                    'name' => $network['name'],
                    'max_members' => $network['max_members']
                ],
                'members' => $members,
                'resources' => $formattedResources,
                'is_owner' => $network['owner_id'] == $user['id']
            ]);
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? 'create';
            
            if ($action === 'create') {
                // Check if user already has a network
                $existing = Database::queryOne('mesh', 
                    "SELECT id FROM mesh_networks WHERE owner_id = ?", 
                    [$user['id']]
                );
                
                if ($existing) {
                    Response::error('You already have a mesh network');
                    exit;
                }
                
                // Check plan limits
                $maxMembers = 3;
                if ($user['plan_type'] === 'family') $maxMembers = 6;
                if ($user['plan_type'] === 'business') $maxMembers = 25;
                
                // Create network
                $networkName = $data['name'] ?? ($user['first_name'] . "'s Network");
                
                $result = Database::execute('mesh', "
                    INSERT INTO mesh_networks (owner_id, name, max_members, created_at)
                    VALUES (?, ?, ?, datetime('now'))
                ", [$user['id'], $networkName, $maxMembers]);
                
                $networkId = $result['lastInsertId'];
                
                // Add owner as first member
                Database::execute('mesh', "
                    INSERT INTO mesh_members (network_id, user_id, role, status, joined_at)
                    VALUES (?, ?, 'owner', 'active', datetime('now'))
                ", [$networkId, $user['id']]);
                
                Response::success([
                    'message' => 'Mesh network created',
                    'network_id' => $networkId
                ]);
            } else {
                Response::error('Invalid action');
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Mesh API error: " . $e->getMessage());
    Response::error('Server error: ' . $e->getMessage(), 500);
}
