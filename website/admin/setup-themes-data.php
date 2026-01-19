<?php
/**
 * TrueVault VPN - Insert 12 Pre-built Themes
 * 
 * Inserts 12 complete themes with color palettes:
 * - 3 Base themes (light, medium, dark)
 * - 8 Seasonal themes (4 seasons × 2 styles)
 * - 1 VIP theme (gold)
 * 
 * Each theme has 11 colors:
 * primary, secondary, accent, background, surface,
 * text_primary, text_secondary, success, warning, error, info
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Database path
$dbPath = __DIR__ . '/../databases/themes.db';

if (!file_exists($dbPath)) {
    die("ERROR: themes.db not found! Run setup-themes-database.php first.");
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>TrueVault Themes - Insert Theme Data</h1>\n";
    echo "<pre>\n";
    
    // ============================================
    // THEME DEFINITIONS
    // ============================================
    
    $themes = [
        // BASE THEMES (3)
        [
            'name' => 'default_light',
            'display_name' => 'Light Mode',
            'description' => 'Clean white background with blue accents',
            'preview_image' => '/assets/themes/light-preview.png',
            'style' => 'light',
            'is_active' => 0,
            'is_seasonal' => 0,
            'season' => null,
            'colors' => [
                'primary' => '#3b82f6',
                'secondary' => '#8b5cf6',
                'accent' => '#06b6d4',
                'background' => '#ffffff',
                'surface' => '#f8fafc',
                'text_primary' => '#1e293b',
                'text_secondary' => '#64748b',
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'error' => '#ef4444',
                'info' => '#3b82f6'
            ]
        ],
        [
            'name' => 'default_medium',
            'display_name' => 'Medium Mode',
            'description' => 'Soft gray tones with purple accents',
            'preview_image' => '/assets/themes/medium-preview.png',
            'style' => 'medium',
            'is_active' => 0,
            'is_seasonal' => 0,
            'season' => null,
            'colors' => [
                'primary' => '#8b5cf6',
                'secondary' => '#a855f7',
                'accent' => '#ec4899',
                'background' => '#f1f5f9',
                'surface' => '#ffffff',
                'text_primary' => '#334155',
                'text_secondary' => '#64748b',
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'error' => '#ef4444',
                'info' => '#3b82f6'
            ]
        ],
        [
            'name' => 'default_dark',
            'display_name' => 'Dark Mode (Current)',
            'description' => 'Deep blue/purple gradient background',
            'preview_image' => '/assets/themes/dark-preview.png',
            'style' => 'dark',
            'is_active' => 1, // THIS IS THE ACTIVE THEME
            'is_seasonal' => 0,
            'season' => null,
            'colors' => [
                'primary' => '#667eea',
                'secondary' => '#764ba2',
                'accent' => '#00d9ff',
                'background' => '#0f0f1a',
                'surface' => '#1a1a2e',
                'text_primary' => '#ffffff',
                'text_secondary' => '#94a3b8',
                'success' => '#10b981',
                'warning' => '#fbbf24',
                'error' => '#f87171',
                'info' => '#60a5fa'
            ]
        ],
        
        // WINTER THEMES (2)
        [
            'name' => 'winter_light',
            'display_name' => 'Winter Snow',
            'description' => 'Cool white with ice blue accents',
            'preview_image' => '/assets/themes/winter-light-preview.png',
            'style' => 'light',
            'is_active' => 0,
            'is_seasonal' => 1,
            'season' => 'winter',
            'colors' => [
                'primary' => '#0ea5e9',
                'secondary' => '#38bdf8',
                'accent' => '#7dd3fc',
                'background' => '#f0f9ff',
                'surface' => '#ffffff',
                'text_primary' => '#0c4a6e',
                'text_secondary' => '#075985',
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'error' => '#ef4444',
                'info' => '#0ea5e9'
            ]
        ],
        [
            'name' => 'winter_dark',
            'display_name' => 'Winter Night',
            'description' => 'Midnight blue with snowflake effects',
            'preview_image' => '/assets/themes/winter-dark-preview.png',
            'style' => 'dark',
            'is_active' => 0,
            'is_seasonal' => 1,
            'season' => 'winter',
            'colors' => [
                'primary' => '#0ea5e9',
                'secondary' => '#38bdf8',
                'accent' => '#7dd3fc',
                'background' => '#0c1e2e',
                'surface' => '#1a3346',
                'text_primary' => '#e0f2fe',
                'text_secondary' => '#7dd3fc',
                'success' => '#10b981',
                'warning' => '#fbbf24',
                'error' => '#f87171',
                'info' => '#38bdf8'
            ]
        ],
        
        // SPRING THEMES (2)
        [
            'name' => 'spring_light',
            'display_name' => 'Spring Bloom',
            'description' => 'Pastel greens with floral pink',
            'preview_image' => '/assets/themes/spring-light-preview.png',
            'style' => 'light',
            'is_active' => 0,
            'is_seasonal' => 1,
            'season' => 'spring',
            'colors' => [
                'primary' => '#22c55e',
                'secondary' => '#4ade80',
                'accent' => '#ec4899',
                'background' => '#f0fdf4',
                'surface' => '#ffffff',
                'text_primary' => '#14532d',
                'text_secondary' => '#166534',
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'error' => '#ef4444',
                'info' => '#3b82f6'
            ]
        ],
        [
            'name' => 'spring_dark',
            'display_name' => 'Spring Forest',
            'description' => 'Deep forest green with nature tones',
            'preview_image' => '/assets/themes/spring-dark-preview.png',
            'style' => 'dark',
            'is_active' => 0,
            'is_seasonal' => 1,
            'season' => 'spring',
            'colors' => [
                'primary' => '#22c55e',
                'secondary' => '#4ade80',
                'accent' => '#86efac',
                'background' => '#0a1f0f',
                'surface' => '#14361f',
                'text_primary' => '#dcfce7',
                'text_secondary' => '#86efac',
                'success' => '#22c55e',
                'warning' => '#fbbf24',
                'error' => '#f87171',
                'info' => '#4ade80'
            ]
        ],
        
        // SUMMER THEMES (2)
        [
            'name' => 'summer_light',
            'display_name' => 'Summer Sunshine',
            'description' => 'Bright yellow with ocean blue',
            'preview_image' => '/assets/themes/summer-light-preview.png',
            'style' => 'light',
            'is_active' => 0,
            'is_seasonal' => 1,
            'season' => 'summer',
            'colors' => [
                'primary' => '#f59e0b',
                'secondary' => '#fbbf24',
                'accent' => '#06b6d4',
                'background' => '#fffbeb',
                'surface' => '#ffffff',
                'text_primary' => '#78350f',
                'text_secondary' => '#92400e',
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'error' => '#ef4444',
                'info' => '#06b6d4'
            ]
        ],
        [
            'name' => 'summer_dark',
            'display_name' => 'Summer Ocean',
            'description' => 'Deep ocean blue with sunset orange',
            'preview_image' => '/assets/themes/summer-dark-preview.png',
            'style' => 'dark',
            'is_active' => 0,
            'is_seasonal' => 1,
            'season' => 'summer',
            'colors' => [
                'primary' => '#06b6d4',
                'secondary' => '#0ea5e9',
                'accent' => '#f59e0b',
                'background' => '#0c1e2e',
                'surface' => '#1a3846',
                'text_primary' => '#cffafe',
                'text_secondary' => '#67e8f9',
                'success' => '#10b981',
                'warning' => '#fbbf24',
                'error' => '#f87171',
                'info' => '#22d3ee'
            ]
        ],
        
        // FALL THEMES (2)
        [
            'name' => 'fall_light',
            'display_name' => 'Fall Harvest',
            'description' => 'Warm orange with golden brown',
            'preview_image' => '/assets/themes/fall-light-preview.png',
            'style' => 'light',
            'is_active' => 0,
            'is_seasonal' => 1,
            'season' => 'fall',
            'colors' => [
                'primary' => '#f97316',
                'secondary' => '#fb923c',
                'accent' => '#fbbf24',
                'background' => '#fff7ed',
                'surface' => '#ffffff',
                'text_primary' => '#7c2d12',
                'text_secondary' => '#9a3412',
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'error' => '#ef4444',
                'info' => '#3b82f6'
            ]
        ],
        [
            'name' => 'fall_dark',
            'display_name' => 'Fall Evening',
            'description' => 'Deep burgundy with autumn red',
            'preview_image' => '/assets/themes/fall-dark-preview.png',
            'style' => 'dark',
            'is_active' => 0,
            'is_seasonal' => 1,
            'season' => 'fall',
            'colors' => [
                'primary' => '#dc2626',
                'secondary' => '#ef4444',
                'accent' => '#f97316',
                'background' => '#1f0f0a',
                'surface' => '#361f1a',
                'text_primary' => '#fecaca',
                'text_secondary' => '#fca5a5',
                'success' => '#10b981',
                'warning' => '#fbbf24',
                'error' => '#dc2626',
                'info' => '#f87171'
            ]
        ],
        
        // VIP THEME (1)
        [
            'name' => 'vip_gold',
            'display_name' => 'VIP Gold (Exclusive)',
            'description' => 'Luxurious gold gradient on black',
            'preview_image' => '/assets/themes/vip-preview.png',
            'style' => 'dark',
            'is_active' => 0,
            'is_seasonal' => 0,
            'season' => null,
            'colors' => [
                'primary' => '#ffd700',
                'secondary' => '#ffed4e',
                'accent' => '#fbbf24',
                'background' => '#000000',
                'surface' => '#1a1a1a',
                'text_primary' => '#ffffff',
                'text_secondary' => '#ffd700',
                'success' => '#10b981',
                'warning' => '#fbbf24',
                'error' => '#ef4444',
                'info' => '#ffd700'
            ]
        ],
    ];
    
    // ============================================
    // INSERT THEMES
    // ============================================
    
    echo "Inserting themes...\n\n";
    
    $insertedCount = 0;
    
    foreach ($themes as $theme) {
        // Check if theme already exists
        $stmt = $db->prepare("SELECT id FROM themes WHERE name = ?");
        $stmt->execute([$theme['name']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            echo "  ⊙ {$theme['display_name']} - Already exists (skipping)\n";
            continue;
        }
        
        // Insert theme
        $stmt = $db->prepare("
            INSERT INTO themes (name, display_name, description, preview_image, style, is_active, is_seasonal, season)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $theme['name'],
            $theme['display_name'],
            $theme['description'],
            $theme['preview_image'],
            $theme['style'],
            $theme['is_active'],
            $theme['is_seasonal'],
            $theme['season']
        ]);
        
        $themeId = $db->lastInsertId();
        
        // Insert colors for this theme
        $colorCount = 0;
        foreach ($theme['colors'] as $key => $value) {
            $stmt = $db->prepare("
                INSERT INTO theme_colors (theme_id, color_key, color_value)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$themeId, $key, $value]);
            $colorCount++;
        }
        
        echo "  ✓ {$theme['display_name']} - Inserted with {$colorCount} colors\n";
        $insertedCount++;
    }
    
    // ============================================
    // VERIFICATION
    // ============================================
    
    echo "\n";
    echo "Verification:\n";
    
    $themeCount = $db->query("SELECT COUNT(*) FROM themes")->fetchColumn();
    $colorCount = $db->query("SELECT COUNT(*) FROM theme_colors")->fetchColumn();
    $activeTheme = $db->query("SELECT display_name FROM themes WHERE is_active = 1")->fetchColumn();
    
    echo "  Total themes: $themeCount\n";
    echo "  Total colors: $colorCount\n";
    echo "  Active theme: $activeTheme\n";
    
    echo "\n";
    echo "========================================\n";
    echo "THEMES INSERTED SUCCESSFULLY!\n";
    echo "========================================\n";
    echo "New themes: $insertedCount\n";
    echo "Total themes: $themeCount\n";
    echo "Expected colors: 132 (11 × 12)\n";
    echo "Actual colors: $colorCount\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Run setup-site-settings.php to insert global settings\n";
    echo "2. Test Theme::getActiveTheme() in your code\n";
    echo "3. Access /admin/themes.php to manage themes\n";
    echo "\n</pre>";
    
} catch (PDOException $e) {
    echo "<pre>";
    echo "ERROR: Failed to insert themes!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "</pre>";
    exit(1);
}
?>
