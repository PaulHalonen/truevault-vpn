<?php
/**
 * TrueVault VPN - Advanced Parental Controls Database Setup
 * 
 * Creates tables for advanced parental control features:
 * - parental_schedules (calendar-based schedules)
 * - schedule_windows (time windows for each day)
 * - parental_whitelist (always-allowed domains)
 * - temporary_blocks (time-limited blocks)
 * - gaming_restrictions (Xbox, PS, Steam controls)
 * - device_rules (device-specific overrides)
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);

require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Database.php';

echo "<h1>Advanced Parental Controls Database Setup</h1>\n";
echo "<p>Creating advanced parental control tables...</p>\n";

try {
    $db = Database::getInstance();
    $parentalConn = $db->getConnection('parental');
    
    // 1. Create parental_schedules table
    echo "<h2>1. Creating parental_schedules table...</h2>\n";
    $parentalConn->exec("
        CREATE TABLE IF NOT EXISTS parental_schedules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER,
            schedule_name TEXT NOT NULL,
            is_template BOOLEAN DEFAULT 0,
            is_active BOOLEAN DEFAULT 1,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p style='color: green;'>✓ parental_schedules table created</p>\n";
    
    // 2. Create schedule_windows table
    echo "<h2>2. Creating schedule_windows table...</h2>\n";
    $parentalConn->exec("
        CREATE TABLE IF NOT EXISTS schedule_windows (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            schedule_id INTEGER NOT NULL,
            day_of_week INTEGER,
            specific_date DATE,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            access_type TEXT NOT NULL,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (schedule_id) REFERENCES parental_schedules(id) ON DELETE CASCADE
        )
    ");
    echo "<p style='color: green;'>✓ schedule_windows table created</p>\n";
    echo "<p><em>access_type values: full, homework_only, streaming_only, blocked</em></p>\n";
    
    // 3. Create parental_whitelist table
    echo "<h2>3. Creating parental_whitelist table...</h2>\n";
    $parentalConn->exec("
        CREATE TABLE IF NOT EXISTS parental_whitelist (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            domain TEXT NOT NULL,
            category TEXT,
            notes TEXT,
            added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE(user_id, domain)
        )
    ");
    echo "<p style='color: green;'>✓ parental_whitelist table created</p>\n";
    
    // 4. Create temporary_blocks table
    echo "<h2>4. Creating temporary_blocks table...</h2>\n";
    $parentalConn->exec("
        CREATE TABLE IF NOT EXISTS temporary_blocks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            domain TEXT NOT NULL,
            blocked_until DATETIME NOT NULL,
            reason TEXT,
            added_by TEXT,
            added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p style='color: green;'>✓ temporary_blocks table created</p>\n";
    
    // 5. Create gaming_restrictions table
    echo "<h2>5. Creating gaming_restrictions table...</h2>\n";
    $parentalConn->exec("
        CREATE TABLE IF NOT EXISTS gaming_restrictions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER,
            platform TEXT NOT NULL,
            is_blocked BOOLEAN DEFAULT 0,
            daily_limit_minutes INTEGER,
            last_toggled_at DATETIME,
            toggled_by TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE(user_id, device_id, platform)
        )
    ");
    echo "<p style='color: green;'>✓ gaming_restrictions table created</p>\n";
    echo "<p><em>Platforms: xbox, playstation, steam, nintendo, general</em></p>\n";
    
    // 6. Create device_rules table
    echo "<h2>6. Creating device_rules table...</h2>\n";
    $parentalConn->exec("
        CREATE TABLE IF NOT EXISTS device_rules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER NOT NULL,
            schedule_id INTEGER,
            override_enabled BOOLEAN DEFAULT 0,
            override_until DATETIME,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (schedule_id) REFERENCES parental_schedules(id) ON DELETE SET NULL,
            UNIQUE(user_id, device_id)
        )
    ");
    echo "<p style='color: green;'>✓ device_rules table created</p>\n";
    
    // 7. Create weekly_stats table for reports
    echo "<h2>7. Creating weekly_stats table...</h2>\n";
    $parentalConn->exec("
        CREATE TABLE IF NOT EXISTS weekly_stats (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER,
            week_start DATE NOT NULL,
            total_blocked_requests INTEGER DEFAULT 0,
            total_allowed_requests INTEGER DEFAULT 0,
            most_blocked_domain TEXT,
            most_blocked_category TEXT,
            peak_usage_hour INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE(user_id, device_id, week_start)
        )
    ");
    echo "<p style='color: green;'>✓ weekly_stats table created</p>\n";
    
    // 8. Create default schedule templates
    echo "<h2>8. Creating default schedule templates...</h2>\n";
    
    // Template 1: School Day (Mon-Fri)
    $parentalConn->exec("
        INSERT OR IGNORE INTO parental_schedules 
        (user_id, schedule_name, is_template, description) 
        VALUES 
        (0, 'School Day', 1, 'Monday through Friday schedule with homework time')
    ");
    $templateId = $parentalConn->lastInsertId();
    
    if ($templateId > 0) {
        // 6am-8am: Full access (before school)
        $parentalConn->exec("
            INSERT INTO schedule_windows 
            (schedule_id, day_of_week, start_time, end_time, access_type, notes)
            VALUES 
            ($templateId, 1, '06:00', '08:00', 'full', 'Before school'),
            ($templateId, 2, '06:00', '08:00', 'full', 'Before school'),
            ($templateId, 3, '06:00', '08:00', 'full', 'Before school'),
            ($templateId, 4, '06:00', '08:00', 'full', 'Before school'),
            ($templateId, 5, '06:00', '08:00', 'full', 'Before school')
        ");
        
        // 8am-3pm: Blocked (school hours)
        $parentalConn->exec("
            INSERT INTO schedule_windows 
            (schedule_id, day_of_week, start_time, end_time, access_type, notes)
            VALUES 
            ($templateId, 1, '08:00', '15:00', 'blocked', 'School hours'),
            ($templateId, 2, '08:00', '15:00', 'blocked', 'School hours'),
            ($templateId, 3, '08:00', '15:00', 'blocked', 'School hours'),
            ($templateId, 4, '08:00', '15:00', 'blocked', 'School hours'),
            ($templateId, 5, '08:00', '15:00', 'blocked', 'School hours')
        ");
        
        // 3pm-6pm: Homework only
        $parentalConn->exec("
            INSERT INTO schedule_windows 
            (schedule_id, day_of_week, start_time, end_time, access_type, notes)
            VALUES 
            ($templateId, 1, '15:00', '18:00', 'homework_only', 'Homework time'),
            ($templateId, 2, '15:00', '18:00', 'homework_only', 'Homework time'),
            ($templateId, 3, '15:00', '18:00', 'homework_only', 'Homework time'),
            ($templateId, 4, '15:00', '18:00', 'homework_only', 'Homework time'),
            ($templateId, 5, '15:00', '18:00', 'homework_only', 'Homework time')
        ");
        
        // 6pm-8pm: Full access (after homework)
        $parentalConn->exec("
            INSERT INTO schedule_windows 
            (schedule_id, day_of_week, start_time, end_time, access_type, notes)
            VALUES 
            ($templateId, 1, '18:00', '20:00', 'full', 'After homework'),
            ($templateId, 2, '18:00', '20:00', 'full', 'After homework'),
            ($templateId, 3, '18:00', '20:00', 'full', 'After homework'),
            ($templateId, 4, '18:00', '20:00', 'full', 'After homework'),
            ($templateId, 5, '18:00', '20:00', 'full', 'After homework')
        ");
        
        // 8pm-10pm: Streaming only (bedtime prep)
        $parentalConn->exec("
            INSERT INTO schedule_windows 
            (schedule_id, day_of_week, start_time, end_time, access_type, notes)
            VALUES 
            ($templateId, 1, '20:00', '22:00', 'streaming_only', 'Wind down time'),
            ($templateId, 2, '20:00', '22:00', 'streaming_only', 'Wind down time'),
            ($templateId, 3, '20:00', '22:00', 'streaming_only', 'Wind down time'),
            ($templateId, 4, '20:00', '22:00', 'streaming_only', 'Wind down time'),
            ($templateId, 5, '20:00', '22:00', 'streaming_only', 'Wind down time')
        ");
        
        echo "<p style='color: green;'>✓ 'School Day' template created with time windows</p>\n";
    }
    
    // Template 2: Weekend
    $parentalConn->exec("
        INSERT OR IGNORE INTO parental_schedules 
        (user_id, schedule_name, is_template, description) 
        VALUES 
        (0, 'Weekend', 1, 'Saturday and Sunday relaxed schedule')
    ");
    $weekendId = $parentalConn->lastInsertId();
    
    if ($weekendId > 0) {
        // 8am-10pm: Full access (relaxed weekend)
        $parentalConn->exec("
            INSERT INTO schedule_windows 
            (schedule_id, day_of_week, start_time, end_time, access_type, notes)
            VALUES 
            ($weekendId, 6, '08:00', '22:00', 'full', 'Weekend fun'),
            ($weekendId, 0, '08:00', '22:00', 'full', 'Weekend fun')
        ");
        
        echo "<p style='color: green;'>✓ 'Weekend' template created</p>\n";
    }
    
    // 9. Verify setup
    echo "<h2>9. Verification</h2>\n";
    $tables = ['parental_schedules', 'schedule_windows', 'parental_whitelist', 
               'temporary_blocks', 'gaming_restrictions', 'device_rules', 'weekly_stats'];
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 20px 0;'>\n";
    echo "<tr style='background: #667eea; color: white;'>";
    echo "<th>Table Name</th><th>Row Count</th><th>Status</th>";
    echo "</tr>\n";
    
    foreach ($tables as $table) {
        $stmt = $parentalConn->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['count'];
        
        echo "<tr>";
        echo "<td><strong>$table</strong></td>";
        echo "<td>$count</td>";
        echo "<td style='color: green;'>✓ OK</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h2 style='color: green;'>✅ Advanced Parental Controls Database Setup Complete!</h2>\n";
    echo "<p><strong>Created:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>7 new tables</li>\n";
    echo "<li>2 default schedule templates</li>\n";
    echo "<li>25 time window rules</li>\n";
    echo "</ul>\n";
    
    echo "<p><strong>Next steps:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Create schedule management APIs</li>\n";
    echo "<li>Build calendar UI interface</li>\n";
    echo "<li>Implement gaming controls</li>\n";
    echo "<li>Create weekly reports</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error</h2>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>
