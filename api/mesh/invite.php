<?php
/**
 * TrueVault VPN - Mesh Network Invite API
 * Send invitation to join mesh network
 * 
 * FIXED: January 14, 2026 - Use Database static methods
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/response.php';

// Require authentication
$user = Auth::requireAuth();
if (!$user) exit;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$permission = $data['permission'] ?? 'member';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Valid email address is required');
}

try {
    // Get or create user's mesh network
    $network = Database::queryOne('mesh', 
        "SELECT * FROM mesh_networks WHERE owner_id = ?", 
        [$user['id']]
    );
    
    if (!$network) {
        // Auto-create network
        $maxMembers = 3;
        if ($user['plan_type'] === 'family') $maxMembers = 6;
        if ($user['plan_type'] === 'business') $maxMembers = 25;
        
        $networkName = $user['first_name'] . "'s Network";
        $result = Database::execute('mesh', "
            INSERT INTO mesh_networks (owner_id, name, max_members, created_at)
            VALUES (?, ?, ?, datetime('now'))
        ", [$user['id'], $networkName, $maxMembers]);
        
        $networkId = $result['lastInsertId'];
        
        // Add owner as member
        Database::execute('mesh', "
            INSERT INTO mesh_members (network_id, user_id, role, status, joined_at)
            VALUES (?, ?, 'owner', 'active', datetime('now'))
        ", [$networkId, $user['id']]);
        
        $network = Database::queryOne('mesh', "SELECT * FROM mesh_networks WHERE id = ?", [$networkId]);
    }
    
    // Check member limit
    $memberCount = Database::queryOne('mesh', 
        "SELECT COUNT(*) as count FROM mesh_members WHERE network_id = ?", 
        [$network['id']]
    );
    
    $pendingCount = Database::queryOne('mesh', 
        "SELECT COUNT(*) as count FROM mesh_invitations WHERE network_id = ? AND status = 'pending'", 
        [$network['id']]
    );
    
    $total = ($memberCount['count'] ?? 0) + ($pendingCount['count'] ?? 0);
    
    if ($total >= $network['max_members']) {
        Response::error('Member limit reached. Upgrade your plan for more members.', 403);
    }
    
    // Check if already invited
    $existingInvite = Database::queryOne('mesh', 
        "SELECT id FROM mesh_invitations WHERE network_id = ? AND email = ? AND status = 'pending'", 
        [$network['id'], $email]
    );
    
    if ($existingInvite) {
        Response::error('This email has already been invited');
    }
    
    // Check if already a member
    $existingUser = Database::queryOne('users', "SELECT id FROM users WHERE email = ?", [$email]);
    
    if ($existingUser) {
        $existingMember = Database::queryOne('mesh', 
            "SELECT id FROM mesh_members WHERE network_id = ? AND user_id = ?", 
            [$network['id'], $existingUser['id']]
        );
        
        if ($existingMember) {
            Response::error('This user is already a member');
        }
    }
    
    // Create invitation
    $inviteCode = bin2hex(random_bytes(16));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    Database::execute('mesh', "
        INSERT INTO mesh_invitations (network_id, email, invite_code, permissions, status, created_at, expires_at)
        VALUES (?, ?, ?, ?, 'pending', datetime('now'), ?)
    ", [$network['id'], $email, $inviteCode, $permission, $expiresAt]);
    
    // TODO: Send email invitation
    
    Response::success([
        'message' => 'Invitation sent',
        'invite_code' => $inviteCode,
        'expires_at' => $expiresAt
    ]);
    
} catch (Exception $e) {
    error_log("Mesh invite error: " . $e->getMessage());
    Response::error('Failed to send invitation', 500);
}
