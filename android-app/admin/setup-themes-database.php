<?php
/**
 * Theme Database Setup Script
 * Creates themes table and inserts default theme
 * USES: SQLite3 class (NOT PDO)
 */

// Database path
$dbPath = __DIR__ . '/../databases/main.db';

// Create databases directory if it doesn't exist
$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
    echo "Created databases directory<br>";
}

try {
    // Connect to database using SQLite3
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    
    echo "<h2>Setting up Theme Database...</h2>";
    
    // Create themes table
    $db->exec("
        CREATE TABLE IF NOT EXISTS themes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            theme_name TEXT NOT NULL UNIQUE,
            primary_color TEXT DEFAULT '#00d9ff',
            secondary_color TEXT DEFAULT '#00ff88',
            background_color TEXT DEFAULT '#0f0f1a',
            text_color TEXT DEFAULT '#ffffff',
            button_style TEXT DEFAULT 'gradient',
            font_family TEXT DEFAULT '-apple-system, BlinkMacSystemFont, sans-serif',
            custom_css TEXT,
            is_active INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Created themes table<br>";
    
    // Create page_styles table for individual page customization
    $db->exec("
        CREATE TABLE IF NOT EXISTS page_styles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_name TEXT NOT NULL UNIQUE,
            theme_id INTEGER,
            custom_colors TEXT,
            custom_css TEXT,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE SET NULL
        )
    ");
    echo "✅ Created page_styles table<br>";
    
    // Check if default theme exists
    $result = $db->query("SELECT COUNT(*) as count FROM themes WHERE theme_name = 'Default Dark'");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $exists = $row['count'] > 0;
    
    if (!$exists) {
        // Insert default theme
        $db->exec("
            INSERT INTO themes (
                theme_name, primary_color, secondary_color, 
                background_color, text_color, button_style, 
                font_family, is_active
            ) VALUES (
                'Default Dark',
                '#00d9ff',
                '#00ff88',
                '#0f0f1a',
                '#ffffff',
                'gradient',
                '-apple-system, BlinkMacSystemFont, sans-serif',
                1
            )
        ");
        echo "✅ Inserted default theme<br>";
    } else {
        echo "ℹ️ Default theme already exists<br>";
    }
    
    // Insert additional themes
    $additionalThemes = [
        [
            'name' => 'Ocean Blue',
            'primary' => '#0088ff',
            'secondary' => '#00ddff',
            'background' => '#0a1929',
            'text' => '#ffffff'
        ],
        [
            'name' => 'Forest Green',
            'primary' => '#00ff88',
            'secondary' => '#88ff00',
            'background' => '#0a1a0f',
            'text' => '#ffffff'
        ],
        [
            'name' => 'Royal Purple',
            'primary' => '#8800ff',
            'secondary' => '#ff00ff',
            'background' => '#1a0a29',
            'text' => '#ffffff'
        ],
        [
            'name' => 'Crimson Red',
            'primary' => '#ff0044',
            'secondary' => '#ff6600',
            'background' => '#1a0a0a',
            'text' => '#ffffff'
        ]
    ];
    
    foreach ($additionalThemes as $theme) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM themes WHERE theme_name = :name");
        $stmt->bindValue(':name', $theme['name'], SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $exists = $row['count'] > 0;
        
        if (!$exists) {
            $stmt = $db->prepare("
                INSERT INTO themes (
                    theme_name, primary_color, secondary_color,
                    background_color, text_color, button_style,
                    font_family, is_active
                ) VALUES (:name, :primary, :secondary, :background, :text, 'gradient', '-apple-system, BlinkMacSystemFont, sans-serif', 0)
            ");
            $stmt->bindValue(':name', $theme['name'], SQLITE3_TEXT);
            $stmt->bindValue(':primary', $theme['primary'], SQLITE3_TEXT);
            $stmt->bindValue(':secondary', $theme['secondary'], SQLITE3_TEXT);
            $stmt->bindValue(':background', $theme['background'], SQLITE3_TEXT);
            $stmt->bindValue(':text', $theme['text'], SQLITE3_TEXT);
            $stmt->execute();
            echo "✅ Added theme: {$theme['name']}<br>";
        }
    }
    
    // Get total count
    $result = $db->query("SELECT COUNT(*) as count FROM themes");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $totalThemes = $row['count'];
    
    echo "<br><h3>✅ Theme Database Setup Complete!</h3>";
    echo "<p>Total themes: $totalThemes</p>";
    echo "<p><a href='/admin/'>← Back to Admin Panel</a></p>";
    
    // Close database
    $db->close();
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Database path: " . htmlspecialchars($dbPath) . "</p>";
    echo "<p>Check that the databases directory is writable.</p>";
}
?>
