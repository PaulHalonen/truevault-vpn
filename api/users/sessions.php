<?php
/**
 * TrueVault VPN - User Sessions API
 * Manage active login sessions
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
    // Ensure sessions table exists
    Database::execute('users', "CREATE TABLE IF NOT EXISTS user_sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token_hash TEXT NOT NULL,
        device_info TEXT,
        ip_address TEXT,
        user_agent TEXT,
        last_active DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_current INTEGER DEFAULT 0
    )");
    
    switch ($method) {
        case 'GET':
            // List active sessions
            $sessions = Database::query('users', "
                SELECT id, device_info, ip_address, user_agent, last_active, created_at, is_current
                FROM user_sessions 
                WHERE user_id = ?
                ORDER BY last_active DESC
            ", [$user['id']]);
            
            // Parse user agent to get readable device info
            $formattedSessions = [];
            foreach ($sessions as $s) {
                $ua = $s['user_agent'] ?? '';
                $device = 'Unknown Device';
                $browser = 'Unknown Browser';
                
                if (strpos($ua, 'Windows') !== false) $device = 'Windows PC';
                elseif (strpos($ua, 'Mac') !== false) $device = 'Mac';
                elseif (strpos($ua, 'iPhone') !== false) $device = 'iPhone';
                elseif (strpos($ua, 'iPad') !== false) $device = 'iPad';
                elseif (strpos($ua, 'Android') !== false) $device = 'Android';
                elseif (strpos($ua, 'Linux') !== false) $device = 'Linux';
                
                if (strpos($ua, 'Chrome') !== false) $browser = 'Chrome';
                elseif (strpos($ua, 'Firefox') !== false) $browser = 'Firefox';
                elseif (strpos($ua, 'Safari') !== false) $browser = 'Safari';
                elseif (strpos($ua, 'Edge') !== false) $browser = 'Edge';
                
                $formattedSessions[] = [
                    'id' => $s['id'],
                    'device' => $s['device_info'] ?: $device,
                    'browser' => $browser,
                    'ip_address' => $s['ip_address'],
                    'location' => 'Unknown',
                    'last_active' => $s['last_active'],
                    'created_at' => $s['created_at'],
                    'is_current' => (bool)$s['is_current']
                ];
            }
            
            Response::success(['sessions' => $formattedSessions]);
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? '';
            
            if ($action === 'revoke') {
                if (empty($data['session_id'])) {
                    Response::error('Session ID is required');
                }
                
                $session = Database::queryOne('users', 
                    "SELECT is_current FROM user_sessions WHERE id = ? AND user_id = ?", 
                    [$data['session_id'], $user['id']]
                );
                
                if (!$session) {
                    Response::error('Session not found', 404);
                }
                
                if ($session['is_current']) {
                    Response::error('Cannot revoke current session. Use logout instead.');
                }
                
                Database::execute('users', 
                    "DELETE FROM user_sessions WHERE id = ? AND user_id = ?", 
                    [$data['session_id'], $user['id']]
                );
                
                Response::success(['message' => 'Session revoked']);
                
            } elseif ($action === 'revoke_all') {
                $result = Database::execute('users', 
                    "DELETE FROM user_sessions WHERE user_id = ? AND is_current = 0", 
                    [$user['id']]
                );
                
                Response::success(['message' => 'All other sessions revoked']);
                
            } else {
                Response::error('Invalid action');
            }
            break;
            
        case 'DELETE':
            $sessionId = $_GET['id'] ?? null;
            if (!$sessionId) {
                Response::error('Session ID is required');
            }
            
            Database::execute('users', 
                "DELETE FROM user_sessions WHERE id = ? AND user_id = ? AND is_current = 0", 
                [$sessionId, $user['id']]
            );
            
            Response::success(['message' => 'Session revoked']);
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Sessions API error: " . $e->getMessage());
    Response::error('Server error', 500);
}
