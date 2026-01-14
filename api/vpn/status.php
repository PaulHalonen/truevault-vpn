<?php
/**
 * TrueVault VPN - VPN Status
 * GET /api/vpn/status.php
 * 
 * FIXED: January 14, 2026 - Changed DatabaseManager to Database class
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow GET
Response::requireMethod('GET');

// Require authentication
$user = Auth::requireAuth();

try {
    // Get active connections using correct Database class
    $activeConnections = Database::query('vpn', "
        SELECT ac.*, vs.server_name, vs.region, vs.country, vs.ip_address as server_ip
        FROM active_connections ac
        JOIN vpn_servers vs ON ac.server_id = vs.id
        WHERE ac.user_id = ?
    ", [$user['id']]);
    
    // Format connections
    $connections = [];
    foreach ($activeConnections as $conn) {
        $connectedAt = strtotime($conn['connected_at']);
        $duration = time() - $connectedAt;
        
        $connections[] = [
            'id' => $conn['id'],
            'server' => [
                'id' => $conn['server_id'],
                'name' => $conn['server_name'],
                'region' => $conn['region'],
                'country' => $conn['country'],
                'ip' => $conn['server_ip']
            ],
            'assigned_ip' => $conn['assigned_ip'],
            'connected_at' => $conn['connected_at'],
            'duration_seconds' => $duration,
            'duration_formatted' => formatDuration($duration),
            'bytes_sent' => (int) ($conn['bytes_sent'] ?? 0),
            'bytes_received' => (int) ($conn['bytes_received'] ?? 0),
            'last_handshake' => $conn['last_handshake'] ?? null
        ];
    }
    
    // Get today's usage from logs database
    $today = date('Y-m-d');
    $todayUsage = Database::queryOne('logs', 
        "SELECT * FROM daily_usage WHERE user_id = ? AND date = ?", 
        [$user['id'], $today]
    );
    
    // Get monthly usage
    $monthStart = date('Y-m-01');
    $monthlyUsage = Database::queryOne('logs', "
        SELECT SUM(bytes_sent) as total_sent, SUM(bytes_received) as total_received, 
               SUM(total_duration_seconds) as total_duration, COUNT(DISTINCT date) as active_days
        FROM daily_usage 
        WHERE user_id = ? AND date >= ?
    ", [$user['id'], $monthStart]);
    
    Response::success([
        'is_connected' => count($connections) > 0,
        'connections' => $connections,
        'connection_count' => count($connections),
        'usage' => [
            'today' => [
                'bytes_sent' => (int) ($todayUsage['bytes_sent'] ?? 0),
                'bytes_received' => (int) ($todayUsage['bytes_received'] ?? 0),
                'duration_seconds' => (int) ($todayUsage['total_duration_seconds'] ?? 0),
                'connections' => (int) ($todayUsage['connections'] ?? 0)
            ],
            'month' => [
                'bytes_sent' => (int) ($monthlyUsage['total_sent'] ?? 0),
                'bytes_received' => (int) ($monthlyUsage['total_received'] ?? 0),
                'duration_seconds' => (int) ($monthlyUsage['total_duration'] ?? 0),
                'active_days' => (int) ($monthlyUsage['active_days'] ?? 0)
            ]
        ]
    ]);
    
} catch (Exception $e) {
    Logger::error('VPN status failed: ' . $e->getMessage());
    Response::serverError('Failed to get status');
}

/**
 * Format duration in human-readable format
 */
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%dh %dm %ds', $hours, $minutes, $secs);
    } elseif ($minutes > 0) {
        return sprintf('%dm %ds', $minutes, $secs);
    } else {
        return sprintf('%ds', $secs);
    }
}
