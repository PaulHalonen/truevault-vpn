<?php
/**
 * TrueVault VPN - Theme Database Setup
 * Part 8 - Task 8.1: Create Theme Database Schema
 * 
 * Creates themes table in themes.db (already exists, just verify/update)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Theme Database Setup - Part 8</title>
    <style>
        body { font-family: -apple-system, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #fff; }
        .container { background: #16213e; padding: 30px; border-radius: 10px; }
        h1 { color: #00d9ff; }
        h2 { color: #00ff88; margin-top: 20px; }
        .success { background: #155724; border: 1px solid #28a745; color: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #721c24; border: 1px solid #dc3545; color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #0c5460; border: 1px solid #17a2b8; color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>🎨 Part 8: Theme Database Setup</h1>

<?php

$results = [];

try {
    echo '<h2>Task 8.1: Theme Schema</h2>';
    
    $db = new SQLite3(DB_THEMES);
    $db->enableExceptions(true);
    
    // Drop existing themes table to recreate with full schema
    $db->exec("DROP TABLE IF EXISTS themes");
    $db->exec("DROP TABLE IF EXISTS theme_settings");
    
    // Create comprehensive themes table
    $db->exec("
        CREATE TABLE themes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            theme_name TEXT UNIQUE NOT NULL,
            display_name TEXT NOT NULL,
            description TEXT,
            category TEXT NOT NULL DEFAULT 'standard',
            season TEXT,
            holiday TEXT,
            colors TEXT NOT NULL,
            fonts TEXT NOT NULL,
            spacing TEXT NOT NULL,
            borders TEXT NOT NULL,
            shadows TEXT,
            is_active INTEGER DEFAULT 0,
            is_default INTEGER DEFAULT 0,
            preview_image TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create theme settings table for global theme options
    $db->exec("
        CREATE TABLE theme_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE NOT NULL,
            setting_value TEXT NOT NULL,
            setting_type TEXT DEFAULT 'string',
            description TEXT,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert default theme settings
    $settings = [
        ['seasonal_auto_switch', '0', 'boolean', 'Automatically switch themes by season'],
        ['holiday_auto_switch', '0', 'boolean', 'Automatically switch themes for holidays'],
        ['current_theme_id', '1', 'integer', 'Currently active theme ID'],
        ['custom_css_enabled', '0', 'boolean', 'Allow custom CSS overrides'],
        ['custom_css', '', 'text', 'Custom CSS code']
    ];
    
    $stmt = $db->prepare("INSERT INTO theme_settings (setting_key, setting_value, setting_type, description) VALUES (:key, :value, :type, :desc)");
    foreach ($settings as $s) {
        $stmt->bindValue(':key', $s[0], SQLITE3_TEXT);
        $stmt->bindValue(':value', $s[1], SQLITE3_TEXT);
        $stmt->bindValue(':type', $s[2], SQLITE3_TEXT);
        $stmt->bindValue(':desc', $s[3], SQLITE3_TEXT);
        $stmt->execute();
        $stmt->reset();
    }
    
    // Create indexes
    $db->exec("CREATE INDEX idx_themes_category ON themes(category)");
    $db->exec("CREATE INDEX idx_themes_season ON themes(season)");
    $db->exec("CREATE INDEX idx_themes_active ON themes(is_active)");
    
    $db->close();
    
    echo '<div class="success">✅ Theme tables created successfully!</div>';
    echo '<div class="info">Tables: themes, theme_settings<br>Indexes: category, season, active</div>';
    $results['theme_schema'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['theme_schema'] = 'error';
}

echo '<h2>Summary</h2>';
echo '<div class="info">';
echo '<p>Schema created. Run seed-themes.php next to populate 20+ themes.</p>';
echo '<p><a href="seed-themes.php" style="color: #00d9ff;">→ Run Theme Seeder</a></p>';
echo '</div>';

?>
</div>
</body>
</html>
