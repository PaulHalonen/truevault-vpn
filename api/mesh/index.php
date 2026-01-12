<?php
/**
 * TrueVault VPN - Mesh Network API
 * Manages family/team mesh networks with shared resources
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/response.php';

// Require authentication
$user = Auth::requireAuth();
if (!$user) exit;

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'status';

try {
    $db = Database::getConnection('mesh');
    
    switch ($method) {
        case 'GET':
            if ($action === 'status') {
                // Get user's mesh network status
                $stmt = $db->prepare("
                    SELECT mn.*, 
                           (SELECT COUNT(*) FROM mesh_members WHERE network_id = mn.id) as member_count
                    FROM mesh_networks mn
                    WHERE mn.owner_id = ? OR mn.id IN (
                        SELECT network_id FROM mesh_members WHERE user_id = ?
                    )
                    LIMIT 1
                ");
                $stmt->execute([$user['id'], $user['id']]);
                $network = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$network) {
                    Response::json([
                        'success' => true, 
                        'has_network' => false,
                        'message' => 'No mesh network found'
                    ]);
                }
                
                // Get members
                $stmt = $db->prepare("
                    SELECT mm.*, u.email, u.first_name, u.last_name
                    FROM mesh_members mm
                    JOIN users u ON mm.user_id = u.id
                    WHERE mm.network_id = ?
                ");
                $stmt->execute([$network['id']]);
                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get shared resources
                $stmt = $db->prepare("
                    SELECT * FROM shared_resources WHERE network_id = ?
                ");
                $stmt->execute([$network['id']]);
                $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json([
                    'success' => true,
                    'has_network' => true,
                    'network' => $network,
                    'members' => $members,
                    'resources' => $resources,
                    'is_owner' => $network['owner_id'] == $user['id']
                ]);
                
            } elseif ($action === 'members') {
                // Get members of user's network
                $stmt = $db->prepare("
                    SELECT mn.id FROM mesh_networks mn
                    WHERE mn.owner_id = ? OR mn.id IN (
                        SELECT network_id FROM mesh_members WHERE user_id = ?
                    )
                    LIMIT 1
                ");
                $stmt->execute([$user['id'], $user['id']]);
                $network = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$network) {
                    Response::error('No network found', 404);
                }
                
                $stmt = $db->prepare("
                    SELECT mm.*, u.email, u.first_name, u.last_name
                    FROM mesh_members mm
                    LEFT JOIN users u ON mm.user_id = u.id
                    WHERE mm.network_id = ?
                ");
                $stmt->execute([$network['id']]);
                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json(['success' => true, 'members' => $members]);
                
            } elseif ($action === 'resources') {
                // Get shared resources
                $stmt = $db->prepare("
                    SELECT mn.id FROM mesh_networks mn
                    WHERE mn.owner_id = ? OR mn.id IN (
                        SELECT network_id FROM mesh_members WHERE user_id = ?
                    )
                    LIMIT 1
                ");
                $stmt->execute([$user['id'], $user['id']]);
                $network = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$network) {
                    Response::error('No network found', 404);
                }
                
                $stmt = $db->prepare("SELECT * FROM shared_resources WHERE network_id = ?");
                $stmt->execute([$network['id']]);
                $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json(['success' => true, 'resources' => $resources]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'create') {
                // Check if user already has a network
                $stmt = $db->prepare("SELECT id FROM mesh_networks WHERE owner_id = ?");
                $stmt->execute([$user['id']]);
                if ($stmt->fetch()) {
                    Response::error('You already have a mesh network', 400);
                }
                
                // Check plan limits
                $maxMembers = 0;
                if ($user['plan_type'] === 'family') $maxMembers = 6;
                if ($user['plan_type'] === 'business') $maxMembers = 25;
                
                if ($maxMembers === 0) {
                    Response::error('Mesh networking requires Family or Business plan', 403);
                }
                
                // Create network
                $networkName = $data['name'] ?? ($user['first_name'] . "'s Network");
                
                $stmt = $db->prepare("
                    INSERT INTO mesh_networks (owner_id, name, max_members, created_at)
                    VALUES (?, ?, ?, datetime('now'))
                ");
                $stmt->execute([$user['id'], $networkName, $maxMembers]);
                $networkId = $db->lastInsertId();
                
                // Add owner as first member
                $stmt = $db->prepare("
                    INSERT INTO mesh_members (network_id, user_id, role, status, joined_at)
                    VALUES (?, ?, 'owner', 'active', datetime('now'))
                ");
                $stmt->execute([$networkId, $user['id']]);
                
                Response::json([
                    'success' => true,
                    'message' => 'Mesh network created',
                    'network_id' => $networkId
                ]);
                
            } elseif ($action === 'invite') {
                // Invite member to network
                if (empty($data['email'])) {
                    Response::error('Email is required', 400);
                }
                
                // Get user's network (must be owner)
                $stmt = $db->prepare("SELECT * FROM mesh_networks WHERE owner_id = ?");
                $stmt->execute([$user['id']]);
                $network = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$network) {
                    Response::error('You must be a network owner to invite members', 403);
                }
                
                // Check member limit
                $stmt = $db->prepare("SELECT COUNT(*) FROM mesh_members WHERE network_id = ?");
                $stmt->execute([$network['id']]);
                $memberCount = $stmt->fetchColumn();
                
                if ($memberCount >= $network['max_members']) {
                    Response::error('Member limit reached', 403);
                }
                
                // Check if already a member
                $stmt = $db->prepare("
                    SELECT mm.id FROM mesh_members mm
                    JOIN users u ON mm.user_id = u.id
                    WHERE mm.network_id = ? AND u.email = ?
                ");
                $stmt->execute([$network['id'], $data['email']]);
                if ($stmt->fetch()) {
                    Response::error('This user is already in your network', 400);
                }
                
                // Create pending invitation
                $inviteCode = bin2hex(random_bytes(16));
                
                $stmt = $db->prepare("
                    INSERT INTO mesh_invitations (network_id, email, invite_code, permissions, created_at, expires_at)
                    VALUES (?, ?, ?, ?, datetime('now'), datetime('now', '+7 days'))
                ");
                $stmt->execute([
                    $network['id'],
                    $data['email'],
                    $inviteCode,
                    $data['permissions'] ?? 'full'
                ]);
                
                // TODO: Send invitation email
                
                Response::json([
                    'success' => true,
                    'message' => 'Invitation sent',
                    'invite_code' => $inviteCode
                ]);
                
            } elseif ($action === 'share-resource') {
                // Share a resource to the network
                if (empty($data['name']) || empty($data['type'])) {
                    Response::error('Resource name and type are required', 400);
                }
                
                // Get user's network
                $stmt = $db->prepare("
                    SELECT mn.id FROM mesh_networks mn
                    WHERE mn.owner_id = ? OR mn.id IN (
                        SELECT network_id FROM mesh_members WHERE user_id = ? AND role IN ('owner', 'admin')
                    )
                    LIMIT 1
                ");
                $stmt->execute([$user['id'], $user['id']]);
                $network = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$network) {
                    Response::error('Network not found or insufficient permissions', 403);
                }
                
                $stmt = $db->prepare("
                    INSERT INTO shared_resources (network_id, owner_id, name, type, local_ip, access_level, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
                ");
                $stmt->execute([
                    $network['id'],
                    $user['id'],
                    $data['name'],
                    $data['type'],
                    $data['local_ip'] ?? null,
                    $data['access_level'] ?? 'view'
                ]);
                
                Response::json([
                    'success' => true,
                    'message' => 'Resource shared',
                    'resource_id' => $db->lastInsertId()
                ]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'DELETE':
            if ($action === 'member' && isset($_GET['id'])) {
                // Remove member from network
                $stmt = $db->prepare("SELECT * FROM mesh_networks WHERE owner_id = ?");
                $stmt->execute([$user['id']]);
                $network = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$network) {
                    Response::error('Only network owner can remove members', 403);
                }
                
                // Can't remove yourself as owner
                $stmt = $db->prepare("SELECT user_id FROM mesh_members WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $member = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($member && $member['user_id'] == $user['id']) {
                    Response::error('Cannot remove yourself from your own network', 400);
                }
                
                $stmt = $db->prepare("DELETE FROM mesh_members WHERE id = ? AND network_id = ?");
                $stmt->execute([$_GET['id'], $network['id']]);
                
                Response::json(['success' => true, 'message' => 'Member removed']);
                
            } elseif ($action === 'resource' && isset($_GET['id'])) {
                // Remove shared resource
                $stmt = $db->prepare("
                    DELETE FROM shared_resources 
                    WHERE id = ? AND owner_id = ?
                ");
                $stmt->execute([$_GET['id'], $user['id']]);
                
                if ($stmt->rowCount() === 0) {
                    Response::error('Resource not found or not owned by you', 404);
                }
                
                Response::json(['success' => true, 'message' => 'Resource removed']);
                
            } elseif ($action === 'network') {
                // Delete entire network (owner only)
                $stmt = $db->prepare("SELECT * FROM mesh_networks WHERE owner_id = ?");
                $stmt->execute([$user['id']]);
                $network = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$network) {
                    Response::error('Network not found or not owned by you', 404);
                }
                
                // Delete all related data
                $db->exec("DELETE FROM shared_resources WHERE network_id = " . $network['id']);
                $db->exec("DELETE FROM mesh_members WHERE network_id = " . $network['id']);
                $db->exec("DELETE FROM mesh_invitations WHERE network_id = " . $network['id']);
                $db->exec("DELETE FROM mesh_networks WHERE id = " . $network['id']);
                
                Response::json(['success' => true, 'message' => 'Network deleted']);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Mesh API error: " . $e->getMessage());
    Response::error('Server error', 500);
}
