<?php
/**
 * TrueVault VPN - Theme API
 * GET /api/theme/index.php - Get active theme
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // Get active theme
        $theme = Database::queryOne('themes', "SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
        
        if (!$theme) {
            // Return default theme if none active
            Response::success([
                'id' => 0,
                'name' => 'Default Dark',
                'variables' => getDefaultThemeVariables()
            ]);
            return;
        }
        
        // Parse variables JSON
        $variables = json_decode($theme['variables'], true);
        
        Response::success([
            'id' => $theme['id'],
            'name' => $theme['name'],
            'variables' => $variables
        ]);
        
    } catch (Exception $e) {
        // Return default theme on error
        Response::success([
            'id' => 0,
            'name' => 'Default Dark',
            'variables' => getDefaultThemeVariables()
        ]);
    }
} else {
    Response::error('Method not allowed', 405);
}

function getDefaultThemeVariables() {
    return [
        'colors' => [
            'primary' => '#00d9ff',
            'secondary' => '#00ff88',
            'accent' => '#ff6b6b',
            'background' => '#0f0f1a',
            'backgroundSecondary' => '#1a1a2e',
            'backgroundTertiary' => '#252540',
            'text' => '#ffffff',
            'textMuted' => '#888888',
            'success' => '#00ff88',
            'warning' => '#ffbb00',
            'error' => '#ff5050',
            'border' => 'rgba(255,255,255,0.08)'
        ],
        'gradients' => [
            'primary' => 'linear-gradient(90deg, #00d9ff, #00ff88)',
            'background' => 'linear-gradient(135deg, #0f0f1a, #1a1a2e)'
        ],
        'typography' => [
            'fontFamily' => 'Inter, -apple-system, BlinkMacSystemFont, sans-serif',
            'fontSizeBase' => '16px'
        ],
        'buttons' => [
            'borderRadius' => '8px',
            'padding' => '10px 20px'
        ],
        'cards' => [
            'borderRadius' => '14px',
            'padding' => '20px'
        ]
    ];
}
