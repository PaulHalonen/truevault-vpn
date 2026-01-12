<?php
/**
 * TrueVault VPN - VPN Disconnect
 * POST /api/vpn/disconnect.php
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
    $connectionsDb = DatabaseManager::getInstance()->connections();
    
    if ($connectionId) {
        // Disconnect specific connection
        $stmt = $connectionsDb->prepare("SELECT * FROM active_connections WHERE id = ? AND user_id = ?");
        $stmt->execute([$connectionId, $user['id']]);
        $connection = $stmt->fetch();
        
        if (!$connection) {
            Response::notFound('Connection not found');
        }
        
        // Move to history
        $stmt = $connectionsDb->prepare("
            INSERT INTO connection_history 
            (user_id, server_id, device_id, client_ip, assigned_ip, bytes_sent, bytes_received, connected_at, disconnected_at, duration_seconds, disconnect_reason)
            SELECT user_id, server_id, device_id, client_ip, assigned_ip, bytes_sent, bytes_received, connected_at, datetime('now'),
                   CAST((julianday('now') - julianday(connected_at)) * 86400 AS INTEGER), 'user_disconnect'
            FROM active_connections WHERE id = ?
        ");
        $stmt->execute([$connectionId]);
        
        // Delete active connection
        $stmt = $connectionsDb->prepare("DELETE FROM active_connections WHERE id = ?");
        $stmt->execute([$connectionId]);
        
        // Update server connection count
        $serversDb = DatabaseManager::getInstance()->servers();
        $stmt = $serversDb->prepare("UPDATE vpn_servers SET current_connections = MAX(0, current_connections - 1) WHERE id = ?");
        $stmt->execute([$connection['server_id']]);
        
        Logger::info('VPN disconnected', ['user_id' => $user['id'], 'connection_id' => $connectionId]);
        
    } else {
        // Disconnect all user connections
        $stmt = $connectionsDb->prepare("SELECT * FROM active_connections WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $connections = $stmt->fetchAll();
        
        foreach ($connections as $conn) {
            // Move to history
            $stmt = $connectionsDb->prepare("
                INSERT INTO connection_history 
                (user_id, server_id, device_id, client_ip, assigned_ip, bytes_sent, bytes_received, connected_at, disconnected_at, duration_seconds, disconnect_reason)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), ?, 'user_disconnect_all')
            ");
            $duration = time() - strtotime($conn['connected_at']);
            $stmt->execute([
                $conn['user_id'], $conn['server_id'], $conn['device_id'],
                $conn['client_ip'], $conn['assigned_ip'], $conn['bytes_sent'],
                $conn['bytes_received'], $conn['connected_at'], $duration
            ]);
            
            // Update server count
            $serversDb = DatabaseManager::getInstance()->servers();
            $stmt = $serversDb->prepare("UPDATE vpn_servers SET current_connections = MAX(0, current_connections - 1) WHERE id = ?");
            $stmt->execute([$conn['server_id']]);
        }
        
        // Delete all active connections
        $stmt = $connectionsDb->prepare("DELETE FROM active_connections WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        
        Logger::info('All VPN connections disconnected', ['user_id' => $user['id'], 'count' => count($connections)]);
    }
    
    Response::success(null, 'Disconnected successfully');
    
} catch (Exception $e) {
    Logger::error('VPN disconnect failed: ' . $e->getMessage());
    Response::serverError('Failed to disconnect');
}
