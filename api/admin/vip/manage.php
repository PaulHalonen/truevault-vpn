<?php
/**
 * TrueVault VPN - VIP Management API
 * GET/POST/PUT/DELETE /api/admin/vip/manage.php
 * 
 * GET - List all VIPs
 * POST - Add new VIP
 * PUT - Update VIP
 * DELETE - Remove VIP
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/vip.php';

// Require admin authentication
$admin = Auth::requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // List all VIPs
        $vips = VIPManager::getAllVIPs();
        $counts = VIPManager::getVIPCounts();
        
        // Get server info for dedicated VIPs
        $servers = Database::query('vpn', "SELECT id, name, location FROM vpn_servers");
        $serverMap = [];
        foreach ($servers as $s) {
            $serverMap[$s['id']] = $s['name'] . ' (' . $s['location'] . ')';
        }
        
        // Enrich VIP data with server names
        foreach ($vips as &$vip) {
            $vip['dedicated_server_name'] = null;
            if ($vip['dedicated_server_id']) {
                $vip['dedicated_server_name'] = $serverMap[$vip['dedicated_server_id']] ?? 'Unknown';
            }
            
            // Check if user has registered
            $user = Database::queryOne('users', 
                "SELECT id, first_name, last_name, status, last_login FROM users WHERE LOWER(email) = ?",
                [strtolower($vip['email'])]
            );
            $vip['has_account'] = $user !== false && $user !== null;
            $vip['user_name'] = $user ? trim($user['first_name'] . ' ' . $user['last_name']) : null;
            $vip['user_status'] = $user ? $user['status'] : null;
            $vip['last_login'] = $user ? $user['last_login'] : null;
        }
        
        Response::success([
            'vips' => $vips,
            'counts' => $counts,
            'servers' => $servers
        ]);
        break;
        
    case 'POST':
        // Add new VIP
        $input = Response::getJsonInput();
        
        if (empty($input['email'])) {
            Response::error('Email is required', 400);
        }
        
        $email = strtolower(trim($input['email']));
        $type = $input['type'] ?? 'vip_basic';
        $plan = $input['plan'] ?? 'family';
        $dedicatedServerId = $input['dedicated_server_id'] ?? null;
        $description = $input['description'] ?? '';
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format', 400);
        }
        
        $result = VIPManager::addVIP($email, $type, $plan, $dedicatedServerId, $description, $admin['email']);
        
        if ($result['success']) {
            // Log action
            Database::execute('logs',
                "INSERT INTO activity_log (user_id, action, details, ip_address, created_at) 
                 VALUES (?, 'vip_added', ?, ?, datetime('now'))",
                [$admin['id'], json_encode(['email' => $email, 'type' => $type]), $_SERVER['REMOTE_ADDR'] ?? '']
            );
            
            Response::success(['id' => $result['id']], 'VIP added successfully');
        } else {
            Response::error($result['error'], 400);
        }
        break;
        
    case 'PUT':
        // Update VIP
        $input = Response::getJsonInput();
        
        if (empty($input['email'])) {
            Response::error('Email is required', 400);
        }
        
        $email = strtolower(trim($input['email']));
        unset($input['email']); // Remove email from update data
        
        $result = VIPManager::updateVIP($email, $input);
        
        if ($result['success']) {
            // Log action
            Database::execute('logs',
                "INSERT INTO activity_log (user_id, action, details, ip_address, created_at) 
                 VALUES (?, 'vip_updated', ?, ?, datetime('now'))",
                [$admin['id'], json_encode(['email' => $email, 'changes' => $input]), $_SERVER['REMOTE_ADDR'] ?? '']
            );
            
            Response::success(null, 'VIP updated successfully');
        } else {
            Response::error($result['error'], 400);
        }
        break;
        
    case 'DELETE':
        // Remove VIP
        $input = Response::getJsonInput();
        
        if (empty($input['email'])) {
            Response::error('Email is required', 400);
        }
        
        $email = strtolower(trim($input['email']));
        
        $result = VIPManager::removeVIP($email);
        
        if ($result['success']) {
            // Log action
            Database::execute('logs',
                "INSERT INTO activity_log (user_id, action, details, ip_address, created_at) 
                 VALUES (?, 'vip_removed', ?, ?, datetime('now'))",
                [$admin['id'], json_encode(['email' => $email]), $_SERVER['REMOTE_ADDR'] ?? '']
            );
            
            Response::success(null, 'VIP removed successfully');
        } else {
            Response::error($result['error'], 400);
        }
        break;
        
    default:
        Response::error('Method not allowed', 405);
}
