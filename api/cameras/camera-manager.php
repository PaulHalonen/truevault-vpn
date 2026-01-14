<?php
/**
 * TrueVault VPN - Camera Management API
 * GET /api/cameras/list.php - List cameras
 * POST /api/cameras/register.php - Register camera
 * POST /api/cameras/remove.php - Remove camera
 * GET /api/cameras/view.php?id=X - Get camera stream info
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../devices/device-manager.php';

class CameraManager {
    
    // Supported camera types with RTSP patterns
    private static $cameraTypes = [
        'geeni' => [
            'name' => 'Geeni',
            'rtsp_pattern' => 'rtsp://{user}:{pass}@{ip}:{port}/stream1',
            'default_port' => 554,
            'default_user' => 'admin'
        ],
        'wyze' => [
            'name' => 'Wyze',
            'rtsp_pattern' => 'rtsp://{user}:{pass}@{ip}:{port}/live',
            'default_port' => 554,
            'default_user' => 'admin',
            'note' => 'Requires Wyze RTSP firmware'
        ],
        'hikvision' => [
            'name' => 'Hikvision',
            'rtsp_pattern' => 'rtsp://{user}:{pass}@{ip}:{port}/Streaming/Channels/101',
            'default_port' => 554,
            'default_user' => 'admin'
        ],
        'dahua' => [
            'name' => 'Dahua',
            'rtsp_pattern' => 'rtsp://{user}:{pass}@{ip}:{port}/cam/realmonitor?channel=1&subtype=0',
            'default_port' => 554,
            'default_user' => 'admin'
        ],
        'amcrest' => [
            'name' => 'Amcrest',
            'rtsp_pattern' => 'rtsp://{user}:{pass}@{ip}:{port}/cam/realmonitor?channel=1&subtype=0',
            'default_port' => 554,
            'default_user' => 'admin'
        ],
        'reolink' => [
            'name' => 'Reolink',
            'rtsp_pattern' => 'rtsp://{user}:{pass}@{ip}:{port}/h264Preview_01_main',
            'default_port' => 554,
            'default_user' => 'admin'
        ],
        'generic' => [
            'name' => 'Generic RTSP',
            'rtsp_pattern' => 'rtsp://{user}:{pass}@{ip}:{port}/stream',
            'default_port' => 554,
            'default_user' => 'admin'
        ]
    ];
    
    /**
     * Get supported camera types
     */
    public static function getCameraTypes() {
        return self::$cameraTypes;
    }
    
    /**
     * Generate RTSP URL for camera
     */
    public static function generateRtspUrl($camera) {
        $type = self::$cameraTypes[$camera['type']] ?? self::$cameraTypes['generic'];
        
        $url = str_replace(
            ['{user}', '{pass}', '{ip}', '{port}'],
            [
                $camera['username'] ?? $type['default_user'],
                $camera['password'] ?? '',
                $camera['ip_address'],
                $camera['port'] ?? $type['default_port']
            ],
            $type['rtsp_pattern']
        );
        
        return $url;
    }
    
    /**
     * Get port forwarding info for camera
     */
    public static function getPortForwardingInfo($userId, $cameraId) {
        $camera = Database::queryOne('devices',
            "SELECT c.*, s.ip_address as server_ip, s.name as server_name
             FROM user_cameras c
             LEFT JOIN vpn_servers s ON s.id = c.server_id
             WHERE c.user_id = ? AND c.camera_id = ? AND c.status = 'active'",
            [$userId, $cameraId]
        );
        
        if (!$camera) {
            return null;
        }
        
        // Generate external port (base + camera id hash)
        $externalPort = 10000 + (crc32($cameraId) % 5000);
        
        return [
            'camera_id' => $cameraId,
            'camera_name' => $camera['name'],
            'internal_ip' => $camera['ip_address'],
            'internal_port' => $camera['port'],
            'external_ip' => $camera['server_ip'],
            'external_port' => $externalPort,
            'server' => $camera['server_name'],
            'rtsp_url' => self::generateRtspUrl($camera),
            'external_rtsp_url' => "rtsp://{$camera['server_ip']}:{$externalPort}/stream"
        ];
    }
}
