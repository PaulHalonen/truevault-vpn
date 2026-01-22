<?php
/**
 * TrueVault VPN - Theme Seeder
 * Part 8 - Task 8.2: Seed 20+ Pre-built Themes
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Theme Seeder - Part 8</title>
    <style>
        body { font-family: -apple-system, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #fff; }
        .container { background: #16213e; padding: 30px; border-radius: 10px; }
        h1 { color: #00d9ff; }
        h2 { color: #00ff88; margin-top: 20px; font-size: 1.1rem; }
        .success { background: #155724; border: 1px solid #28a745; color: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .error { background: #721c24; border: 1px solid #dc3545; color: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .info { background: #0c5460; border: 1px solid #17a2b8; color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .theme-preview { display: inline-block; width: 20px; height: 20px; border-radius: 4px; margin-right: 8px; vertical-align: middle; }
    </style>
</head>
<body>
<div class="container">
    <h1>🎨 Part 8: Seeding 20+ Themes</h1>

<?php

// Default font and spacing values (shared by all themes)
$defaultFonts = json_encode([
    'heading' => 'Montserrat, -apple-system, sans-serif',
    'body' => 'Open Sans, -apple-system, sans-serif',
    'mono' => 'Fira Code, Monaco, monospace'
]);

$defaultSpacing = json_encode([
    'xs' => '4px',
    'sm' => '8px',
    'md' => '16px',
    'lg' => '24px',
    'xl' => '32px',
    'xxl' => '48px'
]);

$defaultBorders = json_encode([
    'radius_sm' => '4px',
    'radius_md' => '8px',
    'radius_lg' => '16px',
    'radius_xl' => '24px'
]);

// ALL 20+ THEMES
$themes = [
    // ==================== SEASONAL THEMES (4) ====================
    [
        'theme_name' => 'winter_frost',
        'display_name' => 'Winter Frost',
        'description' => 'Cool blues and whites, perfect for winter months',
        'category' => 'seasonal',
        'season' => 'winter',
        'holiday' => null,
        'colors' => json_encode([
            'primary' => '#4A90E2',
            'secondary' => '#89CFF0',
            'accent' => '#00D4FF',
            'background' => '#F0F8FF',
            'surface' => '#FFFFFF',
            'text' => '#2C3E50',
            'text_muted' => '#7F8C8D',
            'border' => '#D0E8F2',
            'success' => '#2ECC71',
            'warning' => '#F39C12',
            'error' => '#E74C3C'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(74, 144, 226, 0.1)',
            'md' => '0 4px 12px rgba(74, 144, 226, 0.15)',
            'lg' => '0 8px 24px rgba(74, 144, 226, 0.2)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'summer_breeze',
        'display_name' => 'Summer Breeze',
        'description' => 'Warm yellows and oranges for sunny days',
        'category' => 'seasonal',
        'season' => 'summer',
        'holiday' => null,
        'colors' => json_encode([
            'primary' => '#FF9500',
            'secondary' => '#FFCC00',
            'accent' => '#FF6B00',
            'background' => '#FFFAF0',
            'surface' => '#FFFFFF',
            'text' => '#2D2D2D',
            'text_muted' => '#6B6B6B',
            'border' => '#FFE4B5',
            'success' => '#32CD32',
            'warning' => '#FFD700',
            'error' => '#FF4500'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(255, 149, 0, 0.1)',
            'md' => '0 4px 12px rgba(255, 149, 0, 0.15)',
            'lg' => '0 8px 24px rgba(255, 149, 0, 0.2)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'autumn_harvest',
        'display_name' => 'Autumn Harvest',
        'description' => 'Rich browns and oranges, earthy tones',
        'category' => 'seasonal',
        'season' => 'fall',
        'holiday' => null,
        'colors' => json_encode([
            'primary' => '#D2691E',
            'secondary' => '#CD853F',
            'accent' => '#FF8C00',
            'background' => '#FFF8DC',
            'surface' => '#FFFAF0',
            'text' => '#3E2723',
            'text_muted' => '#6D4C41',
            'border' => '#DEB887',
            'success' => '#6B8E23',
            'warning' => '#DAA520',
            'error' => '#B22222'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(210, 105, 30, 0.1)',
            'md' => '0 4px 12px rgba(210, 105, 30, 0.15)',
            'lg' => '0 8px 24px rgba(210, 105, 30, 0.2)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'spring_bloom',
        'display_name' => 'Spring Bloom',
        'description' => 'Fresh greens and pastels for spring',
        'category' => 'seasonal',
        'season' => 'spring',
        'holiday' => null,
        'colors' => json_encode([
            'primary' => '#4CAF50',
            'secondary' => '#8BC34A',
            'accent' => '#E91E63',
            'background' => '#F1F8E9',
            'surface' => '#FFFFFF',
            'text' => '#33691E',
            'text_muted' => '#689F38',
            'border' => '#C8E6C9',
            'success' => '#4CAF50',
            'warning' => '#FFC107',
            'error' => '#F44336'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(76, 175, 80, 0.1)',
            'md' => '0 4px 12px rgba(76, 175, 80, 0.15)',
            'lg' => '0 8px 24px rgba(76, 175, 80, 0.2)'
        ]),
        'is_default' => 0
    ],
    
    // ==================== HOLIDAY THEMES (8) ====================
    [
        'theme_name' => 'christmas_joy',
        'display_name' => 'Christmas Joy',
        'description' => 'Festive red, green, and gold',
        'category' => 'holiday',
        'season' => 'winter',
        'holiday' => 'christmas',
        'colors' => json_encode([
            'primary' => '#C41E3A',
            'secondary' => '#228B22',
            'accent' => '#FFD700',
            'background' => '#FFF5F5',
            'surface' => '#FFFFFF',
            'text' => '#1A1A1A',
            'text_muted' => '#666666',
            'border' => '#E8D4D4',
            'success' => '#228B22',
            'warning' => '#FFD700',
            'error' => '#C41E3A'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(196, 30, 58, 0.1)',
            'md' => '0 4px 12px rgba(196, 30, 58, 0.15)',
            'lg' => '0 8px 24px rgba(196, 30, 58, 0.2)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'thanksgiving_warmth',
        'display_name' => 'Thanksgiving Warmth',
        'description' => 'Warm orange, brown, and cream',
        'category' => 'holiday',
        'season' => 'fall',
        'holiday' => 'thanksgiving',
        'colors' => json_encode([
            'primary' => '#D2691E',
            'secondary' => '#8B4513',
            'accent' => '#FFD700',
            'background' => '#FFFAF0',
            'surface' => '#FFF8DC',
            'text' => '#3E2723',
            'text_muted' => '#795548',
            'border' => '#DEB887',
            'success' => '#6B8E23',
            'warning' => '#FF8C00',
            'error' => '#8B0000'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(210, 105, 30, 0.1)',
            'md' => '0 4px 12px rgba(210, 105, 30, 0.15)',
            'lg' => '0 8px 24px rgba(210, 105, 30, 0.2)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'halloween_spooky',
        'display_name' => 'Halloween Spooky',
        'description' => 'Spooky orange, black, and purple',
        'category' => 'holiday',
        'season' => 'fall',
        'holiday' => 'halloween',
        'colors' => json_encode([
            'primary' => '#FF6600',
            'secondary' => '#6A0DAD',
            'accent' => '#00FF00',
            'background' => '#1A1A1A',
            'surface' => '#2D2D2D',
            'text' => '#FFFFFF',
            'text_muted' => '#AAAAAA',
            'border' => '#444444',
            'success' => '#00FF00',
            'warning' => '#FF6600',
            'error' => '#FF0000'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(255, 102, 0, 0.2)',
            'md' => '0 4px 12px rgba(255, 102, 0, 0.3)',
            'lg' => '0 8px 24px rgba(106, 13, 173, 0.4)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'easter_pastel',
        'display_name' => 'Easter Pastel',
        'description' => 'Soft pastels for Easter',
        'category' => 'holiday',
        'season' => 'spring',
        'holiday' => 'easter',
        'colors' => json_encode([
            'primary' => '#FFB6C1',
            'secondary' => '#87CEEB',
            'accent' => '#DDA0DD',
            'background' => '#FFFACD',
            'surface' => '#FFFFFF',
            'text' => '#4A4A4A',
            'text_muted' => '#808080',
            'border' => '#E6E6FA',
            'success' => '#98FB98',
            'warning' => '#FFE4B5',
            'error' => '#FFA07A'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(255, 182, 193, 0.15)',
            'md' => '0 4px 12px rgba(255, 182, 193, 0.2)',
            'lg' => '0 8px 24px rgba(135, 206, 235, 0.25)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'valentine_romance',
        'display_name' => 'Valentine Romance',
        'description' => 'Romantic red, pink, and white',
        'category' => 'holiday',
        'season' => 'winter',
        'holiday' => 'valentines',
        'colors' => json_encode([
            'primary' => '#E91E63',
            'secondary' => '#F48FB1',
            'accent' => '#FF1744',
            'background' => '#FFF0F5',
            'surface' => '#FFFFFF',
            'text' => '#880E4F',
            'text_muted' => '#AD1457',
            'border' => '#F8BBD9',
            'success' => '#4CAF50',
            'warning' => '#FF9800',
            'error' => '#D32F2F'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(233, 30, 99, 0.1)',
            'md' => '0 4px 12px rgba(233, 30, 99, 0.15)',
            'lg' => '0 8px 24px rgba(233, 30, 99, 0.2)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'independence_day',
        'display_name' => 'Independence Day',
        'description' => 'Patriotic red, white, and blue',
        'category' => 'holiday',
        'season' => 'summer',
        'holiday' => 'july4th',
        'colors' => json_encode([
            'primary' => '#002868',
            'secondary' => '#BF0A30',
            'accent' => '#FFD700',
            'background' => '#F5F5F5',
            'surface' => '#FFFFFF',
            'text' => '#1A1A1A',
            'text_muted' => '#666666',
            'border' => '#CCCCCC',
            'success' => '#228B22',
            'warning' => '#FFD700',
            'error' => '#BF0A30'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(0, 40, 104, 0.1)',
            'md' => '0 4px 12px rgba(0, 40, 104, 0.15)',
            'lg' => '0 8px 24px rgba(0, 40, 104, 0.2)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'new_year_celebration',
        'display_name' => 'New Year Celebration',
        'description' => 'Elegant gold, silver, and black',
        'category' => 'holiday',
        'season' => 'winter',
        'holiday' => 'newyear',
        'colors' => json_encode([
            'primary' => '#FFD700',
            'secondary' => '#C0C0C0',
            'accent' => '#00CED1',
            'background' => '#0D0D0D',
            'surface' => '#1A1A1A',
            'text' => '#FFFFFF',
            'text_muted' => '#AAAAAA',
            'border' => '#333333',
            'success' => '#00FF7F',
            'warning' => '#FFD700',
            'error' => '#FF4444'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(255, 215, 0, 0.15)',
            'md' => '0 4px 12px rgba(255, 215, 0, 0.2)',
            'lg' => '0 8px 24px rgba(255, 215, 0, 0.3)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'st_patricks_day',
        'display_name' => 'St. Patrick\'s Day',
        'description' => 'Lucky green and gold',
        'category' => 'holiday',
        'season' => 'spring',
        'holiday' => 'stpatricks',
        'colors' => json_encode([
            'primary' => '#009A44',
            'secondary' => '#00843D',
            'accent' => '#FFD700',
            'background' => '#F0FFF0',
            'surface' => '#FFFFFF',
            'text' => '#1A3C34',
            'text_muted' => '#2E8B57',
            'border' => '#90EE90',
            'success' => '#32CD32',
            'warning' => '#FFD700',
            'error' => '#DC143C'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(0, 154, 68, 0.1)',
            'md' => '0 4px 12px rgba(0, 154, 68, 0.15)',
            'lg' => '0 8px 24px rgba(0, 154, 68, 0.2)'
        ]),
        'is_default' => 0
    ],
    
    // ==================== STANDARD BUSINESS THEMES (4) ====================
    [
        'theme_name' => 'professional_blue',
        'display_name' => 'Professional Blue',
        'description' => 'Corporate and trustworthy blue theme',
        'category' => 'standard',
        'season' => null,
        'holiday' => null,
        'colors' => json_encode([
            'primary' => '#2563EB',
            'secondary' => '#3B82F6',
            'accent' => '#06B6D4',
            'background' => '#F8FAFC',
            'surface' => '#FFFFFF',
            'text' => '#1E293B',
            'text_muted' => '#64748B',
            'border' => '#E2E8F0',
            'success' => '#22C55E',
            'warning' => '#F59E0B',
            'error' => '#EF4444'
        ]),
        'shadows' => json_encode([
            'sm' => '0 1px 2px rgba(0, 0, 0, 0.05)',
            'md' => '0 4px 6px rgba(0, 0, 0, 0.1)',
            'lg' => '0 10px 15px rgba(0, 0, 0, 0.1)'
        ]),
        'is_default' => 1  // DEFAULT THEME
    ],
    [
        'theme_name' => 'modern_dark',
        'display_name' => 'Modern Dark',
        'description' => 'Sleek dark theme with cyan accents',
        'category' => 'standard',
        'season' => null,
        'holiday' => null,
        'colors' => json_encode([
            'primary' => '#00D9FF',
            'secondary' => '#00FF88',
            'accent' => '#A855F7',
            'background' => '#0F0F1A',
            'surface' => '#1A1A2E',
            'text' => '#FFFFFF',
            'text_muted' => '#888888',
            'border' => '#2D2D4A',
            'success' => '#00FF88',
            'warning' => '#FFB800',
            'error' => '#FF4757'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(0, 0, 0, 0.3)',
            'md' => '0 4px 12px rgba(0, 0, 0, 0.4)',
            'lg' => '0 8px 24px rgba(0, 217, 255, 0.15)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'classic_light',
        'display_name' => 'Classic Light',
        'description' => 'Timeless and elegant light theme',
        'category' => 'standard',
        'season' => null,
        'holiday' => null,
        'colors' => json_encode([
            'primary' => '#4A5568',
            'secondary' => '#718096',
            'accent' => '#ED8936',
            'background' => '#FFFFFF',
            'surface' => '#F7FAFC',
            'text' => '#1A202C',
            'text_muted' => '#718096',
            'border' => '#E2E8F0',
            'success' => '#48BB78',
            'warning' => '#ED8936',
            'error' => '#F56565'
        ]),
        'shadows' => json_encode([
            'sm' => '0 1px 3px rgba(0, 0, 0, 0.1)',
            'md' => '0 4px 6px rgba(0, 0, 0, 0.1)',
            'lg' => '0 10px 20px rgba(0, 0, 0, 0.1)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'minimal_white',
        'display_name' => 'Minimal White',
        'description' => 'Clean and spacious minimal design',
        'category' => 'standard',
        'season' => null,
        'holiday' => null,
        'colors' => json_encode([
            'primary' => '#000000',
            'secondary' => '#333333',
            'accent' => '#0066FF',
            'background' => '#FFFFFF',
            'surface' => '#FAFAFA',
            'text' => '#000000',
            'text_muted' => '#666666',
            'border' => '#EEEEEE',
            'success' => '#00C853',
            'warning' => '#FFAB00',
            'error' => '#FF1744'
        ]),
        'shadows' => json_encode([
            'sm' => '0 1px 2px rgba(0, 0, 0, 0.04)',
            'md' => '0 2px 8px rgba(0, 0, 0, 0.08)',
            'lg' => '0 4px 16px rgba(0, 0, 0, 0.12)'
        ]),
        'is_default' => 0
    ],
    
    // ==================== COLOR SCHEME THEMES (4) ====================
    [
        'theme_name' => 'ocean_blue',
        'display_name' => 'Ocean Blue',
        'description' => 'Deep blues and teals like the ocean',
        'category' => 'color_scheme',
        'season' => null,
        'holiday' => null,
        'colors' => json_encode([
            'primary' => '#0077B6',
            'secondary' => '#00B4D8',
            'accent' => '#90E0EF',
            'background' => '#CAF0F8',
            'surface' => '#FFFFFF',
            'text' => '#03045E',
            'text_muted' => '#023E8A',
            'border' => '#ADE8F4',
            'success' => '#2A9D8F',
            'warning' => '#E9C46A',
            'error' => '#E76F51'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(0, 119, 182, 0.1)',
            'md' => '0 4px 12px rgba(0, 119, 182, 0.15)',
            'lg' => '0 8px 24px rgba(0, 119, 182, 0.2)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'forest_green',
        'display_name' => 'Forest Green',
        'description' => 'Natural greens and browns',
        'category' => 'color_scheme',
        'season' => null,
        'holiday' => null,
        'colors' => json_encode([
            'primary' => '#2D6A4F',
            'secondary' => '#40916C',
            'accent' => '#95D5B2',
            'background' => '#D8F3DC',
            'surface' => '#FFFFFF',
            'text' => '#1B4332',
            'text_muted' => '#52796F',
            'border' => '#B7E4C7',
            'success' => '#40916C',
            'warning' => '#E9C46A',
            'error' => '#9B2226'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(45, 106, 79, 0.1)',
            'md' => '0 4px 12px rgba(45, 106, 79, 0.15)',
            'lg' => '0 8px 24px rgba(45, 106, 79, 0.2)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'royal_purple',
        'display_name' => 'Royal Purple',
        'description' => 'Regal purples with gold accents',
        'category' => 'color_scheme',
        'season' => null,
        'holiday' => null,
        'colors' => json_encode([
            'primary' => '#7B2CBF',
            'secondary' => '#9D4EDD',
            'accent' => '#FFD700',
            'background' => '#F3E8FF',
            'surface' => '#FFFFFF',
            'text' => '#240046',
            'text_muted' => '#5A189A',
            'border' => '#E0AAFF',
            'success' => '#06D6A0',
            'warning' => '#FFD166',
            'error' => '#EF476F'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(123, 44, 191, 0.1)',
            'md' => '0 4px 12px rgba(123, 44, 191, 0.15)',
            'lg' => '0 8px 24px rgba(123, 44, 191, 0.2)'
        ]),
        'is_default' => 0
    ],
    [
        'theme_name' => 'sunset_orange',
        'display_name' => 'Sunset Orange',
        'description' => 'Warm oranges and reds like a sunset',
        'category' => 'color_scheme',
        'season' => null,
        'holiday' => null,
        'colors' => json_encode([
            'primary' => '#F4511E',
            'secondary' => '#FF7043',
            'accent' => '#FFD54F',
            'background' => '#FFF3E0',
            'surface' => '#FFFFFF',
            'text' => '#BF360C',
            'text_muted' => '#E64A19',
            'border' => '#FFCCBC',
            'success' => '#66BB6A',
            'warning' => '#FFA726',
            'error' => '#EF5350'
        ]),
        'shadows' => json_encode([
            'sm' => '0 2px 4px rgba(244, 81, 30, 0.1)',
            'md' => '0 4px 12px rgba(244, 81, 30, 0.15)',
            'lg' => '0 8px 24px rgba(244, 81, 30, 0.2)'
        ]),
        'is_default' => 0
    ]
];

try {
    $db = new SQLite3(DB_THEMES);
    $db->enableExceptions(true);
    
    // Clear existing themes
    $db->exec("DELETE FROM themes");
    
    // Prepare insert statement
    $stmt = $db->prepare("
        INSERT INTO themes (theme_name, display_name, description, category, season, holiday, colors, fonts, spacing, borders, shadows, is_default, is_active, preview_image)
        VALUES (:name, :display, :desc, :category, :season, :holiday, :colors, :fonts, :spacing, :borders, :shadows, :is_default, :is_active, :preview)
    ");
    
    $count = 0;
    foreach ($themes as $theme) {
        $stmt->bindValue(':name', $theme['theme_name'], SQLITE3_TEXT);
        $stmt->bindValue(':display', $theme['display_name'], SQLITE3_TEXT);
        $stmt->bindValue(':desc', $theme['description'], SQLITE3_TEXT);
        $stmt->bindValue(':category', $theme['category'], SQLITE3_TEXT);
        $stmt->bindValue(':season', $theme['season'], $theme['season'] ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->bindValue(':holiday', $theme['holiday'], $theme['holiday'] ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->bindValue(':colors', $theme['colors'], SQLITE3_TEXT);
        $stmt->bindValue(':fonts', $defaultFonts, SQLITE3_TEXT);
        $stmt->bindValue(':spacing', $defaultSpacing, SQLITE3_TEXT);
        $stmt->bindValue(':borders', $defaultBorders, SQLITE3_TEXT);
        $stmt->bindValue(':shadows', $theme['shadows'], SQLITE3_TEXT);
        $stmt->bindValue(':is_default', $theme['is_default'], SQLITE3_INTEGER);
        $stmt->bindValue(':is_active', $theme['is_default'], SQLITE3_INTEGER); // Default theme is active
        $stmt->bindValue(':preview', '/assets/themes/' . $theme['theme_name'] . '.png', SQLITE3_TEXT);
        $stmt->execute();
        $stmt->reset();
        
        $colors = json_decode($theme['colors'], true);
        $preview = "<span class='theme-preview' style='background: {$colors['primary']}'></span>";
        echo "<div class='success'>{$preview} ✅ {$theme['display_name']} ({$theme['category']})</div>";
        $count++;
    }
    
    // Update theme_settings with default theme ID
    $result = $db->query("SELECT id FROM themes WHERE is_default = 1");
    $defaultTheme = $result->fetchArray(SQLITE3_ASSOC);
    if ($defaultTheme) {
        $db->prepare("UPDATE theme_settings SET setting_value = :id WHERE setting_key = 'current_theme_id'")
           ->bindValue(':id', $defaultTheme['id'], SQLITE3_INTEGER);
    }
    
    $db->close();
    
    echo '<div class="info">';
    echo "<h2>🎉 Seeded $count Themes Successfully!</h2>";
    echo '<p>Categories:</p>';
    echo '<ul>';
    echo '<li>Seasonal: 4 themes (Winter, Summer, Fall, Spring)</li>';
    echo '<li>Holiday: 8 themes (Christmas, Thanksgiving, Halloween, Easter, Valentine, July 4th, New Year, St. Patrick)</li>';
    echo '<li>Standard: 4 themes (Professional Blue [DEFAULT], Modern Dark, Classic Light, Minimal White)</li>';
    echo '<li>Color Scheme: 4 themes (Ocean Blue, Forest Green, Royal Purple, Sunset Orange)</li>';
    echo '</ul>';
    echo '<p><a href="theme-manager.php" style="color: #00d9ff;">→ Go to Theme Manager</a></p>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

?>
</div>
</body>
</html>
