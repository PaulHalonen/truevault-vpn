<?php
/**
 * TrueVault VPN - Seasonal Theme Auto-Switch Cron Job
 * Part 8 - Task 8.7
 * 
 * CRON SETUP: Run daily at midnight
 * 0 0 * * * /usr/bin/php /path/to/cron/seasonal-theme-switch.php
 */

// Allow CLI or cron key access
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    http_response_code(403);
    die('Access denied');
}

if (php_sapi_name() !== 'cli' && ($_GET['cron_key'] ?? '') !== 'TV_THEME_CRON_2026') {
    http_response_code(403);
    die('Invalid cron key');
}

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

// Logging
function themeLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/../logs/theme-switch.log';
    @file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    if (php_sapi_name() === 'cli') {
        echo "[$timestamp] $message\n";
    }
}

themeLog('=== Seasonal Theme Switch Started ===');

try {
    $db = new SQLite3(DB_THEMES);
    $db->enableExceptions(true);
    
    // Check if seasonal auto-switch is enabled
    $result = $db->query("SELECT setting_value FROM theme_settings WHERE setting_key = 'seasonal_auto_switch'");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $seasonalEnabled = ($row['setting_value'] ?? '0') === '1';
    
    // Check if holiday auto-switch is enabled
    $result = $db->query("SELECT setting_value FROM theme_settings WHERE setting_key = 'holiday_auto_switch'");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $holidayEnabled = ($row['setting_value'] ?? '0') === '1';
    
    if (!$seasonalEnabled && !$holidayEnabled) {
        themeLog('Both seasonal and holiday auto-switch are disabled. Exiting.');
        exit(0);
    }
    
    // Determine current date info
    $month = (int)date('n');
    $day = (int)date('j');
    
    // Determine current season
    function getCurrentSeason() {
        $month = (int)date('n');
        if ($month >= 3 && $month <= 5) return 'spring';
        if ($month >= 6 && $month <= 8) return 'summer';
        if ($month >= 9 && $month <= 11) return 'fall';
        return 'winter';
    }
    
    // Check for holidays (approximate date ranges)
    function getCurrentHoliday() {
        $month = (int)date('n');
        $day = (int)date('j');
        
        // Christmas: Dec 1-31
        if ($month == 12) return 'christmas';
        
        // New Year: Jan 1-7
        if ($month == 1 && $day <= 7) return 'newyear';
        
        // Valentine's Day: Feb 7-14
        if ($month == 2 && $day >= 7 && $day <= 14) return 'valentines';
        
        // St. Patrick's Day: Mar 10-17
        if ($month == 3 && $day >= 10 && $day <= 17) return 'stpatricks';
        
        // Easter: Late March / Early April (approximate)
        if (($month == 3 && $day >= 25) || ($month == 4 && $day <= 15)) return 'easter';
        
        // Independence Day: Jun 28 - Jul 4
        if (($month == 6 && $day >= 28) || ($month == 7 && $day <= 4)) return 'july4th';
        
        // Halloween: Oct 15-31
        if ($month == 10 && $day >= 15) return 'halloween';
        
        // Thanksgiving: Nov 15-30 (US)
        if ($month == 11 && $day >= 15) return 'thanksgiving';
        
        return null;
    }
    
    $targetTheme = null;
    $switchReason = '';
    
    // Priority: Holiday themes first (if enabled), then seasonal
    if ($holidayEnabled) {
        $holiday = getCurrentHoliday();
        if ($holiday) {
            $stmt = $db->prepare("SELECT * FROM themes WHERE holiday = :holiday LIMIT 1");
            $stmt->bindValue(':holiday', $holiday, SQLITE3_TEXT);
            $result = $stmt->execute();
            $targetTheme = $result->fetchArray(SQLITE3_ASSOC);
            if ($targetTheme) {
                $switchReason = "Holiday: $holiday";
            }
        }
    }
    
    // Fall back to seasonal if no holiday theme
    if (!$targetTheme && $seasonalEnabled) {
        $season = getCurrentSeason();
        $stmt = $db->prepare("SELECT * FROM themes WHERE season = :season AND holiday IS NULL LIMIT 1");
        $stmt->bindValue(':season', $season, SQLITE3_TEXT);
        $result = $stmt->execute();
        $targetTheme = $result->fetchArray(SQLITE3_ASSOC);
        if ($targetTheme) {
            $switchReason = "Season: $season";
        }
    }
    
    if (!$targetTheme) {
        themeLog('No matching theme found for current date. Keeping current theme.');
        exit(0);
    }
    
    // Check if already active
    $activeResult = $db->query("SELECT id, display_name FROM themes WHERE is_active = 1 LIMIT 1");
    $activeTheme = $activeResult->fetchArray(SQLITE3_ASSOC);
    
    if ($activeTheme && $activeTheme['id'] == $targetTheme['id']) {
        themeLog("Theme '{$targetTheme['display_name']}' is already active. No change needed.");
        exit(0);
    }
    
    // Switch theme
    $db->exec("UPDATE themes SET is_active = 0");
    
    $activateStmt = $db->prepare("UPDATE themes SET is_active = 1 WHERE id = :id");
    $activateStmt->bindValue(':id', $targetTheme['id'], SQLITE3_INTEGER);
    $activateStmt->execute();
    
    // Update settings
    $settingStmt = $db->prepare("UPDATE theme_settings SET setting_value = :id, updated_at = datetime('now') WHERE setting_key = 'current_theme_id'");
    $settingStmt->bindValue(':id', (string)$targetTheme['id'], SQLITE3_TEXT);
    $settingStmt->execute();
    
    $db->close();
    
    $fromTheme = $activeTheme ? $activeTheme['display_name'] : 'None';
    themeLog("✅ Theme switched! $fromTheme → {$targetTheme['display_name']} ($switchReason)");
    
    // Output for web access
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'switched' => true,
            'from' => $fromTheme,
            'to' => $targetTheme['display_name'],
            'reason' => $switchReason
        ]);
    }
    
} catch (Exception $e) {
    themeLog('ERROR: ' . $e->getMessage());
    
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    exit(1);
}

themeLog('=== Seasonal Theme Switch Completed ===');
