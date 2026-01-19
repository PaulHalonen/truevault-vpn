<?php
/**
 * TrueVault VPN - Themes Database Setup
 * 
 * Creates themes.db with 9 tables for complete theme and page management
 * 
 * TABLES:
 * 1. themes - Theme definitions
 * 2. theme_colors - Color palettes
 * 3. theme_settings - Theme configuration
 * 4. pages - Page definitions
 * 5. page_sections - Page content blocks
 * 6. site_settings - Global site settings
 * 7. navigation_menus - Dynamic navigation
 * 8. page_revisions - Content versioning
 * 9. media_library - File management
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Database path
$dbPath = __DIR__ . '/../databases/themes.db';

// Create databases directory if it doesn't exist
if (!is_dir(__DIR__ . '/../databases')) {
    mkdir(__DIR__ . '/../databases', 0755, true);
}

try {
    // Connect to database (creates file if doesn't exist)
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>TrueVault Themes Database Setup</h1>\n";
    echo "<pre>\n";
    
    // ============================================
    // TABLE 1: THEMES
    // ============================================
    
    echo "Creating table: themes...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS themes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            display_name TEXT NOT NULL,
            description TEXT,
            preview_image TEXT,
            style TEXT NOT NULL, -- 'light', 'medium', 'dark'
            is_active INTEGER DEFAULT 0,
            is_seasonal INTEGER DEFAULT 0,
            season TEXT, -- 'winter', 'spring', 'summer', 'fall', null
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Table 'themes' created\n\n";
    
    // ============================================
    // TABLE 2: THEME_COLORS
    // ============================================
    
    echo "Creating table: theme_colors...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS theme_colors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            theme_id INTEGER NOT NULL,
            color_key TEXT NOT NULL,
            color_value TEXT NOT NULL,
            FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE
        )
    ");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_theme_colors ON theme_colors(theme_id, color_key)");
    echo "✓ Table 'theme_colors' created with index\n\n";
    
    // ============================================
    // TABLE 3: THEME_SETTINGS
    // ============================================
    
    echo "Creating table: theme_settings...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS theme_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            theme_id INTEGER NOT NULL,
            setting_key TEXT NOT NULL,
            setting_value TEXT NOT NULL,
            setting_type TEXT NOT NULL,
            FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE
        )
    ");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_theme_settings ON theme_settings(theme_id, setting_key)");
    echo "✓ Table 'theme_settings' created with index\n\n";
    
    // ============================================
    // TABLE 4: PAGES
    // ============================================
    
    echo "Creating table: pages...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT NOT NULL UNIQUE,
            title TEXT NOT NULL,
            meta_description TEXT,
            meta_keywords TEXT,
            is_public INTEGER DEFAULT 1,
            is_active INTEGER DEFAULT 1,
            layout_template TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Table 'pages' created\n\n";
    
    // ============================================
    // TABLE 5: PAGE_SECTIONS
    // ============================================
    
    echo "Creating table: page_sections...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS page_sections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            section_type TEXT NOT NULL,
            section_data TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0,
            is_visible INTEGER DEFAULT 1,
            FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
        )
    ");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_page_sections ON page_sections(page_id, sort_order)");
    echo "✓ Table 'page_sections' created with index\n\n";
    
    // ============================================
    // TABLE 6: SITE_SETTINGS
    // ============================================
    
    echo "Creating table: site_settings...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT NOT NULL,
            setting_type TEXT NOT NULL,
            category TEXT,
            description TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Table 'site_settings' created\n\n";
    
    // ============================================
    // TABLE 7: NAVIGATION_MENUS
    // ============================================
    
    echo "Creating table: navigation_menus...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS navigation_menus (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            menu_location TEXT NOT NULL,
            label TEXT NOT NULL,
            url TEXT NOT NULL,
            target TEXT DEFAULT '_self',
            icon TEXT,
            parent_id INTEGER,
            sort_order INTEGER DEFAULT 0,
            is_visible INTEGER DEFAULT 1,
            required_role TEXT
        )
    ");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_navigation_menus ON navigation_menus(menu_location, sort_order)");
    echo "✓ Table 'navigation_menus' created with index\n\n";
    
    // ============================================
    // TABLE 8: PAGE_REVISIONS
    // ============================================
    
    echo "Creating table: page_revisions...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS page_revisions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_id INTEGER NOT NULL,
            revision_data TEXT NOT NULL,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Table 'page_revisions' created\n\n";
    
    // ============================================
    // TABLE 9: MEDIA_LIBRARY
    // ============================================
    
    echo "Creating table: media_library...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS media_library (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL,
            original_filename TEXT NOT NULL,
            file_path TEXT NOT NULL,
            file_type TEXT NOT NULL,
            file_size INTEGER NOT NULL,
            mime_type TEXT,
            alt_text TEXT,
            uploaded_by INTEGER,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Table 'media_library' created\n\n";
    
    // ============================================
    // VERIFICATION
    // ============================================
    
    echo "Verifying all tables exist...\n";
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    
    $expectedTables = [
        'themes',
        'theme_colors',
        'theme_settings',
        'pages',
        'page_sections',
        'site_settings',
        'navigation_menus',
        'page_revisions',
        'media_library'
    ];
    
    foreach ($expectedTables as $table) {
        if (in_array($table, $tables)) {
            echo "  ✓ $table\n";
        } else {
            echo "  ✗ $table MISSING!\n";
        }
    }
    
    echo "\n";
    echo "========================================\n";
    echo "DATABASE SETUP COMPLETE!\n";
    echo "========================================\n";
    echo "Location: $dbPath\n";
    echo "Tables: " . count($tables) . "\n";
    echo "Status: Ready for theme data\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Run setup-themes-data.php to insert 12 themes\n";
    echo "2. Run setup-site-settings.php to insert settings\n";
    echo "3. Test Theme::getActiveTheme()\n";
    echo "\n</pre>";
    
} catch (PDOException $e) {
    echo "<pre>";
    echo "ERROR: Database setup failed!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "</pre>";
    exit(1);
}
?>
