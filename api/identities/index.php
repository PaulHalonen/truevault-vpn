<?php
/**
 * TrueVault VPN - Regional Identities API
 * Manages persistent digital identities for different regions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/response.php';

// Require authentication
$user = Auth::requireAuth();
if (!$user) exit;

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    $db = Database::getConnection('identities');
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // Get all identities for user
                $stmt = $db->prepare("
                    SELECT * FROM regional_identities 
                    WHERE user_id = ? 
                    ORDER BY is_active DESC, created_at DESC
                ");
                $stmt->execute([$user['id']]);
                $identities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json(['success' => true, 'identities' => $identities]);
            } elseif ($action === 'get' && isset($_GET['id'])) {
                // Get single identity
                $stmt = $db->prepare("
                    SELECT * FROM regional_identities 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$_GET['id'], $user['id']]);
                $identity = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$identity) {
                    Response::error('Identity not found', 404);
                }
                
                Response::json(['success' => true, 'identity' => $identity]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'create') {
                // Validate required fields
                if (empty($data['name']) || empty($data['region'])) {
                    Response::error('Name and region are required', 400);
                }
                
                // Check user's plan for identity limit
                $maxIdentities = 3; // Default for personal plan
                if ($user['plan_type'] === 'family') $maxIdentities = 10;
                if ($user['plan_type'] === 'business') $maxIdentities = 50;
                
                $stmt = $db->prepare("SELECT COUNT(*) FROM regional_identities WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $count = $stmt->fetchColumn();
                
                if ($count >= $maxIdentities) {
                    Response::error("Identity limit reached ($maxIdentities for your plan)", 403);
                }
                
                // Region configurations
                $regionConfig = [
                    'US' => ['timezone' => 'America/New_York', 'ip_prefix' => '10.100.1.'],
                    'CA' => ['timezone' => 'America/Toronto', 'ip_prefix' => '10.100.2.'],
                    'UK' => ['timezone' => 'Europe/London', 'ip_prefix' => '10.100.3.'],
                    'DE' => ['timezone' => 'Europe/Berlin', 'ip_prefix' => '10.100.4.'],
                    'FR' => ['timezone' => 'Europe/Paris', 'ip_prefix' => '10.100.5.'],
                    'AU' => ['timezone' => 'Australia/Sydney', 'ip_prefix' => '10.100.6.'],
                    'JP' => ['timezone' => 'Asia/Tokyo', 'ip_prefix' => '10.100.7.'],
                    'SG' => ['timezone' => 'Asia/Singapore', 'ip_prefix' => '10.100.8.'],
                ];
                
                $region = strtoupper($data['region']);
                if (!isset($regionConfig[$region])) {
                    Response::error('Invalid region', 400);
                }
                
                $config = $regionConfig[$region];
                
                // Generate persistent IP for this identity
                $persistentIp = $config['ip_prefix'] . rand(10, 250);
                
                // Generate browser fingerprint hash
                $fingerprint = hash('sha256', $user['id'] . $region . time());
                
                $stmt = $db->prepare("
                    INSERT INTO regional_identities 
                    (user_id, name, region, persistent_ip, timezone, fingerprint_hash, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
                ");
                $stmt->execute([
                    $user['id'],
                    $data['name'],
                    $region,
                    $persistentIp,
                    $config['timezone'],
                    substr($fingerprint, 0, 12)
                ]);
                
                $identityId = $db->lastInsertId();
                
                // Log the creation
                $logDb = Database::getConnection('logs');
                $logStmt = $logDb->prepare("
                    INSERT INTO activity_log (user_id, action, details, ip_address, created_at)
                    VALUES (?, 'identity_created', ?, ?, datetime('now'))
                ");
                $logStmt->execute([
                    $user['id'],
                    json_encode(['identity_id' => $identityId, 'region' => $region]),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                Response::json([
                    'success' => true,
                    'message' => 'Identity created',
                    'identity' => [
                        'id' => $identityId,
                        'name' => $data['name'],
                        'region' => $region,
                        'persistent_ip' => $persistentIp,
                        'timezone' => $config['timezone']
                    ]
                ]);
            } elseif ($action === 'activate' && isset($data['id'])) {
                // Deactivate all identities first
                $stmt = $db->prepare("UPDATE regional_identities SET is_active = 0 WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                
                // Activate the selected one
                $stmt = $db->prepare("
                    UPDATE regional_identities SET is_active = 1 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$data['id'], $user['id']]);
                
                Response::json(['success' => true, 'message' => 'Identity activated']);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                Response::error('Identity ID required', 400);
            }
            
            // Verify ownership
            $stmt = $db->prepare("SELECT * FROM regional_identities WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'], $user['id']]);
            $identity = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$identity) {
                Response::error('Identity not found', 404);
            }
            
            // Update name if provided
            if (!empty($data['name'])) {
                $stmt = $db->prepare("UPDATE regional_identities SET name = ? WHERE id = ?");
                $stmt->execute([$data['name'], $data['id']]);
            }
            
            Response::json(['success' => true, 'message' => 'Identity updated']);
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                Response::error('Identity ID required', 400);
            }
            
            // Verify ownership
            $stmt = $db->prepare("SELECT * FROM regional_identities WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
            $identity = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$identity) {
                Response::error('Identity not found', 404);
            }
            
            // Delete the identity
            $stmt = $db->prepare("DELETE FROM regional_identities WHERE id = ?");
            $stmt->execute([$id]);
            
            Response::json(['success' => true, 'message' => 'Identity deleted']);
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Identities API error: " . $e->getMessage());
    Response::error('Server error', 500);
}
