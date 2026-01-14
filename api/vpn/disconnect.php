<?php
/**
 * TrueVault VPN - VPN Disconnect
 * POST /api/vpn/disconnect.php
 * 
 * FIXED: January 14, 2026 - Changed DatabaseManager to Database class
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow POST
Response::requireMethod('POST');

// Require authentication
$user = Auth::requireAuth();

// Get input
$input = Response::getJsonInput();

$connectionId = $input['connection_id'] ?? null;

try {
    if ($connectionId) {
        // Disconnect specific connection
        $connection = Database::queryOne('vpn', 
            "SELECT * FROM active_connections WHERE id = ? AND user_id = ?", 
            [$connectionId, $user['id']]
        );
        
        if (!$connection) {
            Response::notFound('Connection not found');
        }
        
        // Calculate duration
        $duration = time() - strtotime($connection['connected_at']);
        
        // Move to history
        Database::execute('vpn', "
            INSERT INTO connection_history 
            (user_id, server_id, device_id, client_ip, assigned_ip, bytes_sent, bytes_received, connected_at, disconnected_at, duration_seconds, disconnect_reason)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), ?, 'user_disconnect')
        ", [
            $connection['user_id'],
            $connection['server_id'],
            $connection['device_id'] ?? null,
            $connection['client_ip'] ?? null,
            $connection['assigned_ip'] ?? null,
            $connection['bytes_sent'] ?? 0,
            $connection['bytes_received'] ?? 0,
            $connection['connected_at'],
            $duration
        ]);
        
        // Delete active connection
        Database::execute('vpn', "DELETE FROM active_connections WHERE id = ?", [$connectionId]);
        
        // Update server connection count
        Database::execute('vpn', 
            "UPDATE vpn_servers SET current_connections = MAX(0, current_connections - 1) WHERE id = ?", 
            [$connection['server_id']]
        );
        
        Logger::info('VPN disconnected', ['user_id' => $user['id'], 'connection_id' => $connectionId]);
        
    } else {
        // Disconnect all user connections
        $connections = Database::query('vpn', 
            "SELECT * FROM active_connections WHERE user_id = ?", 
            [$user['id']]
        );
        
        foreach ($connections as $conn) {
            $duration = time() - strtotime($conn['connected_at']);
            
            // Move to history
            Database::execute('vpn', "
                INSERT INTO connection_history 
                (user_id, server_id, device_id, client_ip, assigned_ip, bytes_sent, bytes_received, connected_at, disconnected_at, duration_seconds, disconnect_reason)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), ?, 'user_disconnect_all')
            ", [
                $conn['user_id'],
                $conn['server_id'],
                $conn['device_id'] ?? null,
                $conn['client_ip'] ?? null,
                $conn['assigned_ip'] ?? null,
                $conn['bytes_sent'] ?? 0,
                $conn['bytes_received'] ?? 0,
                $conn['connected_at'],
                $duration
            ]);
            
            // Update server count
            Database::execute('vpn', 
                "UPDATE vpn_servers SET current_connections = MAX(0, current_connections - 1) WHERE id = ?", 
                [$conn['server_id']]
            );
        }
        
        // Delete all active connections
        Database::execute('vpn', "DELETE FROM active_connections WHERE user_id = ?", [$user['id']]);
        
        Logger::info('All VPN connections disconnected', ['user_id' => $user['id'], 'count' => count($connections)]);
    }
    
    Response::success(null, 'Disconnected successfully');
    
} catch (Exception $e) {
    Logger::error('VPN disconnect failed: ' . $e->getMessage());
    Response::serverError('Failed to disconnect');
}
