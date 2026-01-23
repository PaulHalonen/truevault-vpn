<?php
/**
 * TrueVault VPN - Advanced Parental Controls Database Setup
 * Part 11 - Task 11.1
 * Creates all tables for advanced parental controls
 */

header('Content-Type: application/json');

// Database path
$dbPath = __DIR__ . '/../db/truevault.db';

// Ensure db directory exists
if (!file_exists(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0755, true);
}

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tables = [];
    
    // Table 1: parental_schedules - Main schedule definitions
    $db->exec("
        CREATE TABLE IF NOT EXISTS parental_schedules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER,
            schedule_name TEXT NOT NULL,
            description TEXT,
            is_template BOOLEAN DEFAULT 0,
            is_active BOOLEAN DEFAULT 1,
            timezone TEXT DEFAULT 'America/New_York',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
        )
    ");
    $tables[] = 'parental_schedules';
    
    // Index for faster lookups
    $db->exec("CREATE INDEX IF NOT EXISTS idx_schedules_user ON parental_schedules(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_schedules_device ON parental_schedules(device_id)");
    
    // Table 2: schedule_windows - Time windows within schedules
    $db->exec("
        CREATE TABLE IF NOT EXISTS schedule_windows (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            schedule_id INTEGER NOT NULL,
            day_of_week INTEGER,
            specific_date DATE,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            access_type TEXT NOT NULL CHECK(access_type IN ('full', 'homework_only', 'streaming_only', 'gaming_only', 'blocked')),
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (schedule_id) REFERENCES parental_schedules(id) ON DELETE CASCADE
        )
    ");
    $tables[] = 'schedule_windows';
    
    // Index for schedule lookups
    $db->exec("CREATE INDEX IF NOT EXISTS idx_windows_schedule ON schedule_windows(schedule_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_windows_day ON schedule_windows(day_of_week)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_windows_date ON schedule_windows(specific_date)");
    
    // Table 3: parental_whitelist - Always-allowed domains
    $db->exec("
        CREATE TABLE IF NOT EXISTS parental_whitelist (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER,
            domain TEXT NOT NULL,
            category TEXT DEFAULT 'custom',
            notes TEXT,
            added_by TEXT DEFAULT 'parent',
            added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
            UNIQUE(user_id, device_id, domain)
        )
    ");
    $tables[] = 'parental_whitelist';
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_whitelist_user ON parental_whitelist(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_whitelist_domain ON parental_whitelist(domain)");
    
    // Table 4: temporary_blocks - Time-limited domain blocks
    $db->exec("
        CREATE TABLE IF NOT EXISTS temporary_blocks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER,
            domain TEXT NOT NULL,
            blocked_until DATETIME NOT NULL,
            reason TEXT,
            blocked_by TEXT DEFAULT 'parent',
            added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
        )
    ");
    $tables[] = 'temporary_blocks';
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tempblocks_user ON temporary_blocks(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tempblocks_until ON temporary_blocks(blocked_until)");
    
    // Table 5: gaming_restrictions - Gaming-specific controls
    $db->exec("
        CREATE TABLE IF NOT EXISTS gaming_restrictions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER,
            gaming_enabled BOOLEAN DEFAULT 1,
            xbox_enabled BOOLEAN DEFAULT 1,
            playstation_enabled BOOLEAN DEFAULT 1,
            steam_enabled BOOLEAN DEFAULT 1,
            nintendo_enabled BOOLEAN DEFAULT 1,
            daily_limit_minutes INTEGER,
            minutes_used_today INTEGER DEFAULT 0,
            last_reset_date DATE,
            last_toggled_at DATETIME,
            toggled_by TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
            UNIQUE(user_id, device_id)
        )
    ");
    $tables[] = 'gaming_restrictions';
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_gaming_user ON gaming_restrictions(user_id)");
    
    // Table 6: device_rules - Per-device rule assignments
    $db->exec("
        CREATE TABLE IF NOT EXISTS device_rules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER NOT NULL,
            schedule_id INTEGER,
            override_enabled BOOLEAN DEFAULT 0,
            override_type TEXT,
            override_until DATETIME,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
            FOREIGN KEY (schedule_id) REFERENCES parental_schedules(id) ON DELETE SET NULL,
            UNIQUE(user_id, device_id)
        )
    ");
    $tables[] = 'device_rules';
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_devrules_user ON device_rules(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_devrules_device ON device_rules(device_id)");
    
    // Table 7: device_groups - Group devices together
    $db->exec("
        CREATE TABLE IF NOT EXISTS device_groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            group_name TEXT NOT NULL,
            description TEXT,
            icon TEXT DEFAULT '📱',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $tables[] = 'device_groups';
    
    // Table 8: device_group_members - Link devices to groups
    $db->exec("
        CREATE TABLE IF NOT EXISTS device_group_members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            group_id INTEGER NOT NULL,
            device_id INTEGER NOT NULL,
            added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES device_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
            UNIQUE(group_id, device_id)
        )
    ");
    $tables[] = 'device_group_members';
    
    // Table 9: parental_activity_log - Track all parental actions
    $db->exec("
        CREATE TABLE IF NOT EXISTS parental_activity_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            action_type TEXT NOT NULL,
            target_type TEXT,
            target_id INTEGER,
            details TEXT,
            performed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $tables[] = 'parental_activity_log';
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_actlog_user ON parental_activity_log(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_actlog_date ON parental_activity_log(performed_at)");
    
    // Table 10: parental_statistics - Daily usage stats
    $db->exec("
        CREATE TABLE IF NOT EXISTS parental_statistics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER,
            stat_date DATE NOT NULL,
            total_minutes INTEGER DEFAULT 0,
            gaming_minutes INTEGER DEFAULT 0,
            streaming_minutes INTEGER DEFAULT 0,
            social_minutes INTEGER DEFAULT 0,
            educational_minutes INTEGER DEFAULT 0,
            blocked_requests INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
            UNIQUE(user_id, device_id, stat_date)
        )
    ");
    $tables[] = 'parental_statistics';
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_stats_user_date ON parental_statistics(user_id, stat_date)");
    
    // Insert default schedule templates
    $checkTemplates = $db->query("SELECT COUNT(*) FROM parental_schedules WHERE is_template = 1");
    if ($checkTemplates->fetchColumn() == 0) {
        // System templates (user_id = 0)
        $db->exec("
            INSERT INTO parental_schedules (user_id, schedule_name, description, is_template, is_active) VALUES
            (0, 'School Day', 'Standard schedule for school days with homework time', 1, 1),
            (0, 'Weekend', 'Relaxed schedule for weekends', 1, 1),
            (0, 'Holiday', 'Special holiday schedule', 1, 1),
            (0, 'Study Mode', 'Educational sites only - no gaming or streaming', 1, 1),
            (0, 'Bedtime', 'Everything blocked during sleep hours', 1, 1)
        ");
        
        // Get template IDs
        $templates = $db->query("SELECT id, schedule_name FROM parental_schedules WHERE is_template = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // School Day windows (Monday-Friday)
        if (isset($templates['School Day'])) {
            $schoolId = $templates['School Day'];
            for ($day = 1; $day <= 5; $day++) {
                $db->exec("
                    INSERT INTO schedule_windows (schedule_id, day_of_week, start_time, end_time, access_type, notes) VALUES
                    ($schoolId, $day, '07:00', '08:00', 'full', 'Morning routine'),
                    ($schoolId, $day, '15:00', '17:00', 'homework_only', 'Homework time'),
                    ($schoolId, $day, '17:00', '19:00', 'full', 'Free time'),
                    ($schoolId, $day, '19:00', '20:00', 'streaming_only', 'TV time'),
                    ($schoolId, $day, '20:00', '07:00', 'blocked', 'Bedtime')
                ");
            }
        }
        
        // Weekend windows (Saturday-Sunday)
        if (isset($templates['Weekend'])) {
            $weekendId = $templates['Weekend'];
            for ($day = 0; $day <= 6; $day += 6) { // 0 = Sunday, 6 = Saturday
                $actualDay = $day == 0 ? 0 : 6;
                $db->exec("
                    INSERT INTO schedule_windows (schedule_id, day_of_week, start_time, end_time, access_type, notes) VALUES
                    ($weekendId, $actualDay, '08:00', '12:00', 'full', 'Morning free time'),
                    ($weekendId, $actualDay, '12:00', '20:00', 'full', 'Afternoon'),
                    ($weekendId, $actualDay, '20:00', '21:00', 'streaming_only', 'Evening wind-down'),
                    ($weekendId, $actualDay, '21:00', '08:00', 'blocked', 'Bedtime')
                ");
            }
        }
        
        // Study Mode - educational only all day
        if (isset($templates['Study Mode'])) {
            $studyId = $templates['Study Mode'];
            for ($day = 0; $day <= 6; $day++) {
                $db->exec("
                    INSERT INTO schedule_windows (schedule_id, day_of_week, start_time, end_time, access_type, notes) VALUES
                    ($studyId, $day, '00:00', '23:59', 'homework_only', 'Educational sites only')
                ");
            }
        }
        
        // Bedtime - blocked all day (for override use)
        if (isset($templates['Bedtime'])) {
            $bedtimeId = $templates['Bedtime'];
            for ($day = 0; $day <= 6; $day++) {
                $db->exec("
                    INSERT INTO schedule_windows (schedule_id, day_of_week, start_time, end_time, access_type, notes) VALUES
                    ($bedtimeId, $day, '00:00', '23:59', 'blocked', 'All access blocked')
                ");
            }
        }
    }
    
    // Insert default educational whitelist
    $db->exec("
        INSERT OR IGNORE INTO parental_whitelist (user_id, device_id, domain, category, notes) VALUES
        (0, NULL, 'google.com', 'educational', 'Search engine'),
        (0, NULL, 'wikipedia.org', 'educational', 'Encyclopedia'),
        (0, NULL, 'khanacademy.org', 'educational', 'Learning platform'),
        (0, NULL, 'duolingo.com', 'educational', 'Language learning'),
        (0, NULL, 'codecademy.com', 'educational', 'Coding lessons'),
        (0, NULL, 'mathway.com', 'educational', 'Math help'),
        (0, NULL, 'quizlet.com', 'educational', 'Study flashcards'),
        (0, NULL, 'coursera.org', 'educational', 'Online courses'),
        (0, NULL, 'edx.org', 'educational', 'Online courses'),
        (0, NULL, 'pbskids.org', 'educational', 'Kids education')
    ");
    
    echo json_encode([
        'success' => true,
        'message' => 'Advanced parental control tables created successfully',
        'tables_created' => $tables,
        'count' => count($tables),
        'templates' => 5,
        'default_whitelist' => 10
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
