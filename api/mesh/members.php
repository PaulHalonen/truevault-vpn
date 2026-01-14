<?php
/**
 * TrueVault VPN - Mesh Network Members API
 * Manage mesh network members
 * 
 * FIXED: January 14, 2026 - Use Database static methods
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/response.php';

// Require authentication
$user = Auth::requireAuth();
if (!$user) exit;

$method = $_SERVER['REQUEST_METHOD'];

try {
    // Get user's network (as owner or member)
    $network = Database::queryOne('mesh', "
        SELECT mn.*, 
            CASE WHEN mn.owner_id = ? THEN 'owner' ELSE mm.role END as user_role
        FROM mesh_networks mn
        LEFT JOIN mesh_members mm ON mm.network_id = mn.id AND mm.user_id = ?
        WHERE mn.owner_id = ? OR mm.user_id = ?
        LIMIT 1
    ", [$user['id'], $user['id'], $user['id'], $user['id']]);
    
    if (!$network) {
        Response::error('No mesh network found', 404);
    }
    
    $isOwner = $network['owner_id'] == $user['id'];
    $isAdmin = $isOwner || $network['user_role'] === 'admin';
    
    switch ($method) {
        case 'GET':
            // List all members and pending invitations
            $members = Database::query('mesh', "
                SELECT mm.id, mm.user_id, mm.role, mm.permission, mm.status, mm.joined_at
                FROM mesh_members mm
                WHERE mm.network_id = ?
                ORDER BY mm.role = 'owner' DESC, mm.joined_at ASC
            ", [$network['id']]);
            
            $result = [];
            foreach ($members as $m) {
                $userInfo = Database::queryOne('users', 
                    "SELECT email, first_name, last_name FROM users WHERE id = ?", 
                    [$m['user_id']]
                );
                
                $result[] = [
                    'id' => $m['id'],
                    'user_id' => $m['user_id'],
                    'name' => $userInfo ? trim($userInfo['first_name'] . ' ' . $userInfo['last_name']) : 'Unknown',
                    'email' => $userInfo['email'] ?? '',
                    'role' => $m['role'],
                    'permission' => $m['permission'] ?? 'member',
                    'status' => $m['status'] ?? 'active',
                    'joined_at' => $m['joined_at']
                ];
            }
            
            // Add pending invitations
            $invites = Database::query('mesh', "
                SELECT id, email, permissions, created_at, expires_at
                FROM mesh_invitations 
                WHERE network_id = ? AND status = 'pending'
            ", [$network['id']]);
            
            foreach ($invites as $inv) {
                $result[] = [
                    'id' => 'inv_' . $inv['id'],
                    'name' => 'Pending Invitation',
                    'email' => $inv['email'],
                    'role' => 'invited',
                    'permission' => $inv['permissions'] ?? 'member',
                    'status' => 'pending',
                    'invited_at' => $inv['created_at'],
                    'expires_at' => $inv['expires_at']
                ];
            }
            
            Response::success(['members' => $result]);
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? '';
            
            if ($action === 'update') {
                // Update member permission
                if (!$isAdmin) {
                    Response::error('Admin access required', 403);
                }
                
                $memberId = $data['member_id'] ?? null;
                $newPermission = $data['permission'] ?? null;
                
                if (!$memberId || !$newPermission) {
                    Response::error('Member ID and permission required');
                }
                
                // Can't change owner
                $member = Database::queryOne('mesh', 
                    "SELECT role FROM mesh_members WHERE id = ? AND network_id = ?", 
                    [$memberId, $network['id']]
                );
                
                if (!$member) {
                    Response::error('Member not found', 404);
                }
                
                if ($member['role'] === 'owner') {
                    Response::error('Cannot change owner permissions');
                }
                
                Database::execute('mesh', 
                    "UPDATE mesh_members SET permission = ? WHERE id = ?", 
                    [$newPermission, $memberId]
                );
                
                Response::success(['message' => 'Permission updated']);
                
            } elseif ($action === 'remove') {
                // Remove member or cancel invitation
                $memberId = $data['member_id'] ?? null;
                
                if (!$memberId) {
                    Response::error('Member ID required');
                }
                
                // Check if it's an invitation
                if (strpos($memberId, 'inv_') === 0) {
                    $invId = substr($memberId, 4);
                    
                    if (!$isAdmin) {
                        Response::error('Admin access required', 403);
                    }
                    
                    Database::execute('mesh', 
                        "DELETE FROM mesh_invitations WHERE id = ? AND network_id = ?", 
                        [$invId, $network['id']]
                    );
                    
                    Response::success(['message' => 'Invitation cancelled']);
                    exit;
                }
                
                // It's a member
                $member = Database::queryOne('mesh', 
                    "SELECT user_id, role FROM mesh_members WHERE id = ? AND network_id = ?", 
                    [$memberId, $network['id']]
                );
                
                if (!$member) {
                    Response::error('Member not found', 404);
                }
                
                if ($member['role'] === 'owner') {
                    Response::error('Cannot remove network owner');
                }
                
                // Non-admins can only remove themselves
                if (!$isAdmin && $member['user_id'] != $user['id']) {
                    Response::error('You can only remove yourself', 403);
                }
                
                Database::execute('mesh', 
                    "DELETE FROM mesh_members WHERE id = ? AND network_id = ?", 
                    [$memberId, $network['id']]
                );
                
                Response::success(['message' => 'Member removed']);
                
            } else {
                Response::error('Invalid action');
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Mesh members error: " . $e->getMessage());
    Response::error('Server error: ' . $e->getMessage(), 500);
}
