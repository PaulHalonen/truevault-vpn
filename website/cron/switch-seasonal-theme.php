<?php
/**
 * TrueVault VPN - Seasonal Theme Auto-Switcher
 * 
 * Cron job that automatically switches themes based on season
 * Runs daily at 2 AM
 * 
 * CRON SCHEDULE:
 * 0 2 * * * /usr/bin/php /path/to/cron/switch-seasonal-theme.php
 * 
 * SEASONS:
 * Winter: Dec 1 - Feb 28/29
 * Spring: Mar 1 - May 31
 * Summer: Jun 1 - Aug 31
 * Fall: Sep 1 - Nov 30
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration and helpers
require_once __DIR__ . '/../includes/Theme.php';
require_once __DIR__ . '/../includes/Content.php';
require_once __DIR__ . '/../configs/config.php';

// Log file
$logFile = __DIR__ . '/../logs/seasonal-theme.log';

// Create logs directory if it doesn't exist
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

// Log function
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    logMessage("=== Seasonal Theme Switcher Started ===");
    
    // Check if seasonal themes are enabled
    $enabled = Content::get('enable_seasonal_themes', '0');
    
    if ($enabled != '1') {
        logMessage("Seasonal theme switching is DISABLED in settings");
        logMessage("=== Script Completed (No Action) ===\n");
        exit(0);
    }
    
    logMessage("Seasonal theme switching is ENABLED");
    
    // Get current season
    $currentSeason = Theme::getCurrentSeason();
    logMessage("Current season: " . ucfirst($currentSeason));
    
    // Get active theme
    $activeTheme = Theme::getActiveTheme();
    
    if (!$activeTheme) {
        logMessage("ERROR: No active theme found!");
        logMessage("=== Script Failed ===\n");
        exit(1);
    }
    
    logMessage("Active theme: {$activeTheme['display_name']} ({$activeTheme['name']})");
    logMessage("Active theme style: {$activeTheme['style']}");
    
    // Check if we need to switch
    if ($activeTheme['is_seasonal'] && $activeTheme['season'] == $currentSeason) {
        logMessage("Already on correct seasonal theme for $currentSeason");
        logMessage("=== Script Completed (No Change Needed) ===\n");
        exit(0);
    }
    
    // Find appropriate seasonal theme
    logMessage("Looking for seasonal theme: season=$currentSeason, style={$activeTheme['style']}");
    
    require_once __DIR__ . '/../includes/Database.php';
    $db = Database::getInstance();
    $themesConn = $db->getConnection('themes');
    
    $stmt = $themesConn->prepare("
        SELECT id, name, display_name FROM themes 
        WHERE is_seasonal = 1 AND season = ? AND style = ?
        LIMIT 1
    ");
    $stmt->execute([$currentSeason, $activeTheme['style']]);
    $newTheme = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$newTheme) {
        logMessage("WARNING: No matching seasonal theme found");
        logMessage("Keeping current theme: {$activeTheme['display_name']}");
        logMessage("=== Script Completed (No Suitable Theme) ===\n");
        exit(0);
    }
    
    logMessage("Found seasonal theme: {$newTheme['display_name']} (ID: {$newTheme['id']})");
    
    // Switch to seasonal theme
    $success = Theme::switchTheme($newTheme['id']);
    
    if ($success) {
        logMessage("SUCCESS: Switched to {$newTheme['display_name']}");
        logMessage("Theme change completed successfully");
    } else {
        logMessage("ERROR: Failed to switch theme");
        logMessage("=== Script Failed ===\n");
        exit(1);
    }
    
    logMessage("=== Script Completed Successfully ===\n");
    exit(0);
    
} catch (Exception $e) {
    logMessage("EXCEPTION: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    logMessage("=== Script Failed with Exception ===\n");
    exit(1);
}
?>
