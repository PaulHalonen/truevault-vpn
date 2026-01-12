<?php
/**
 * TrueVault VPN - Theme API
 * GET /api/theme/index.php
 * 
 * CRITICAL: This endpoint provides ALL styling from the database
 * NO HARDCODED STYLES - Everything comes from themes.db
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../helpers/response.php';

// Allow GET without authentication (themes are public)
Response::requireMethod('GET');

try {
    $settings = Settings::getInstance();
    
    // Get active theme
    $theme = $settings->getActiveTheme();
    
    if (!$theme) {
        // Return defaults if no theme in database
        Response::success([
            'theme' => [
                'id' => 0,
                'name' => 'Default',
                'slug' => 'default'
            ],
            'settings' => getDefaultThemeSettings(),
            'css_variables' => generateDefaultCSSVariables()
        ]);
    }
    
    // Get all theme settings
    $themeSettings = $settings->getThemeSettings($theme['id']);
    
    // Generate CSS variables
    $cssVariables = $settings->generateCSSVariables();
    
    Response::success([
        'theme' => [
            'id' => $theme['id'],
            'name' => $theme['theme_name'],
            'slug' => $theme['theme_slug'],
            'description' => $theme['description']
        ],
        'settings' => $themeSettings,
        'css_variables' => $cssVariables
    ]);
    
} catch (Exception $e) {
    // Return defaults on error
    Response::success([
        'theme' => [
            'id' => 0,
            'name' => 'Default',
            'slug' => 'default'
        ],
        'settings' => getDefaultThemeSettings(),
        'css_variables' => generateDefaultCSSVariables()
    ]);
}

/**
 * Default theme settings (fallback if database unavailable)
 */
function getDefaultThemeSettings() {
    return [
        'colors' => [
            'primary' => '#00d9ff',
            'secondary' => '#00ff88',
            'accent' => '#ff6b6b',
            'background' => '#0f0f1a',
            'background_secondary' => '#1a1a2e',
            'background_card' => 'rgba(255,255,255,0.04)',
            'text' => '#ffffff',
            'text_secondary' => '#cccccc',
            'text_muted' => '#888888',
            'success' => '#00ff88',
            'warning' => '#ffbb00',
            'error' => '#ff5050',
            'info' => '#00d9ff',
            'border' => 'rgba(255,255,255,0.08)',
            'border_light' => 'rgba(255,255,255,0.15)'
        ],
        'gradients' => [
            'primary' => 'linear-gradient(90deg, #00d9ff, #00ff88)',
            'background' => 'linear-gradient(135deg, #0f0f1a, #1a1a2e)'
        ],
        'typography' => [
            'font_family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            'font_size_base' => '16px',
            'line_height' => '1.5'
        ],
        'buttons' => [
            'border_radius' => '8px',
            'padding' => '10px 20px',
            'font_weight' => '600'
        ],
        'layout' => [
            'max_width' => '1200px',
            'sidebar_width' => '250px',
            'spacing_unit' => '8px'
        ],
        'cards' => [
            'background' => 'rgba(255,255,255,0.04)',
            'border_radius' => '14px',
            'padding' => '18px'
        ]
    ];
}

/**
 * Generate default CSS variables
 */
function generateDefaultCSSVariables() {
    return ':root {
  /* colors */
  --colors-primary: #00d9ff;
  --colors-secondary: #00ff88;
  --colors-accent: #ff6b6b;
  --colors-background: #0f0f1a;
  --colors-background-secondary: #1a1a2e;
  --colors-background-card: rgba(255,255,255,0.04);
  --colors-text: #ffffff;
  --colors-text-secondary: #cccccc;
  --colors-text-muted: #888888;
  --colors-success: #00ff88;
  --colors-warning: #ffbb00;
  --colors-error: #ff5050;
  --colors-info: #00d9ff;
  --colors-border: rgba(255,255,255,0.08);
  --colors-border-light: rgba(255,255,255,0.15);

  /* gradients */
  --gradients-primary: linear-gradient(90deg, #00d9ff, #00ff88);
  --gradients-background: linear-gradient(135deg, #0f0f1a, #1a1a2e);

  /* typography */
  --typography-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --typography-font-size-base: 16px;
  --typography-line-height: 1.5;

  /* buttons */
  --buttons-border-radius: 8px;
  --buttons-padding: 10px 20px;
  --buttons-font-weight: 600;

  /* layout */
  --layout-max-width: 1200px;
  --layout-sidebar-width: 250px;
  --layout-spacing-unit: 8px;

  /* cards */
  --cards-background: rgba(255,255,255,0.04);
  --cards-border-radius: 14px;
  --cards-padding: 18px;
}';
}
