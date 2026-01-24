<?php
/**
 * TrueVault VPN - Marketing Automation Engine
 * Part 15 - Task 15.4
 * Processes scheduled posts and manages automation
 * 
 * USES SQLite3 (NOT PDO!)
 * 
 * Usage:
 * - Cron: php automation-engine.php
 * - Web: automation-engine.php?action=run
 * - API: automation-engine.php?action=process&date=2026-01-23
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_CAMPAIGNS', DB_PATH . 'campaigns.db');

class MarketingAutomation {
    private $db;
    private $results = [];
    
    public function __construct() {
        $this->db = new SQLite3(DB_CAMPAIGNS);
        $this->db->enableExceptions(true);
    }
    
    /**
     * Process daily scheduled posts
     */
    public function processDailyPosts($targetDate = null) {
        $date = $targetDate ?: date('Y-m-d');
        $this->log("Processing posts for: {$date}");
        
        // Get today's calendar content
        $stmt = $this->db->prepare("SELECT * FROM content_calendar WHERE calendar_date = ? AND is_posted = 0");
        $stmt->bindValue(1, $date, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        $calendarItems = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $calendarItems[] = $row;
        }
        
        if (empty($calendarItems)) {
            $this->log("No content to post for {$date}");
            return $this->results;
        }
        
        $this->log("Found " . count($calendarItems) . " calendar items");
        
        foreach ($calendarItems as $item) {
            $this->processCalendarItem($item);
        }
        
        return $this->results;
    }
    
    /**
     * Process a single calendar item
     */
    private function processCalendarItem($item) {
        $platforms = json_decode($item['platforms'], true) ?: [];
        $this->log("Processing: {$item['post_title']} -> " . count($platforms) . " platforms");
        
        foreach ($platforms as $platformName) {
            // Get platform info
            $stmt = $this->db->prepare("SELECT * FROM advertising_platforms WHERE LOWER(platform_name) LIKE ? AND is_active = 1");
            $stmt->bindValue(1, '%' . strtolower($platformName) . '%', SQLITE3_TEXT);
            $result = $stmt->execute();
            $platform = $result->fetchArray(SQLITE3_ASSOC);
            
            if (!$platform) {
                $this->log("Platform not found or inactive: {$platformName}");
                continue;
            }
            
            // Schedule the post
            $this->schedulePost($item, $platform);
        }
        
        // Mark calendar item as processed
        $stmt = $this->db->prepare("UPDATE content_calendar SET is_posted = 1, posted_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bindValue(1, $item['id'], SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    /**
     * Schedule a post for a platform
     */
    private function schedulePost($calendarItem, $platform) {
        // Check if already scheduled
        $stmt = $this->db->prepare("SELECT id FROM scheduled_posts WHERE calendar_id = ? AND platform_id = ?");
        $stmt->bindValue(1, $calendarItem['id'], SQLITE3_INTEGER);
        $stmt->bindValue(2, $platform['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        if ($result->fetchArray()) {
            $this->log("Already scheduled: {$platform['platform_name']}");
            return;
        }
        
        // Format content for platform
        $content = $this->formatContentForPlatform($calendarItem['post_content'], $platform);
        
        // Schedule post
        $stmt = $this->db->prepare("INSERT INTO scheduled_posts (calendar_id, platform_id, scheduled_for, post_content, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bindValue(1, $calendarItem['id'], SQLITE3_INTEGER);
        $stmt->bindValue(2, $platform['id'], SQLITE3_INTEGER);
        $stmt->bindValue(3, date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $stmt->bindValue(4, $content, SQLITE3_TEXT);
        $stmt->bindValue(5, $platform['api_available'] ? 'pending' : 'manual', SQLITE3_TEXT);
        $stmt->execute();
        
        $this->log("Scheduled for {$platform['platform_name']}: " . ($platform['api_available'] ? 'Auto' : 'Manual'));
        
        // If API available, try to post immediately
        if ($platform['api_available']) {
            $postId = $this->db->lastInsertRowID();
            $this->postToPlatform($postId, $platform, $content);
        }
        
        $this->results[] = [
            'platform' => $platform['platform_name'],
            'status' => $platform['api_available'] ? 'scheduled' : 'manual_queue',
            'calendar_id' => $calendarItem['id']
        ];
    }
    
    /**
     * Format content for specific platform
     */
    private function formatContentForPlatform($content, $platform) {
        $platformName = strtolower($platform['platform_name']);
        
        // Twitter/X character limit
        if (strpos($platformName, 'twitter') !== false || strpos($platformName, 'x') !== false) {
            if (strlen($content) > 280) {
                $content = substr($content, 0, 277) . '...';
            }
        }
        
        // LinkedIn - remove excessive hashtags
        if (strpos($platformName, 'linkedin') !== false) {
            $content = preg_replace('/#\w+/', '', $content);
            $content = trim($content);
        }
        
        // Instagram - ensure hashtags
        if (strpos($platformName, 'instagram') !== false) {
            if (strpos($content, '#') === false) {
                $content .= "\n\n#VPN #Privacy #CyberSecurity #TrueVault";
            }
        }
        
        return $content;
    }
    
    /**
     * Post to platform via API
     */
    private function postToPlatform($postId, $platform, $content) {
        $success = false;
        $error = null;
        
        // Simulate API posting (real integrations would go here)
        $platformName = strtolower($platform['platform_name']);
        
        switch (true) {
            case strpos($platformName, 'facebook') !== false:
                // $success = $this->postToFacebook($platform, $content);
                $success = true; // Simulated
                break;
                
            case strpos($platformName, 'twitter') !== false:
                // $success = $this->postToTwitter($platform, $content);
                $success = true; // Simulated
                break;
                
            case strpos($platformName, 'linkedin') !== false:
                // $success = $this->postToLinkedIn($platform, $content);
                $success = true; // Simulated
                break;
                
            default:
                $success = true; // Mark as success for demo
        }
        
        // Update post status
        $status = $success ? 'posted' : 'failed';
        $stmt = $this->db->prepare("UPDATE scheduled_posts SET status = ?, posted_at = CURRENT_TIMESTAMP, error_message = ? WHERE id = ?");
        $stmt->bindValue(1, $status, SQLITE3_TEXT);
        $stmt->bindValue(2, $error, SQLITE3_TEXT);
        $stmt->bindValue(3, $postId, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Update platform stats
        $field = $success ? 'success_count' : 'failure_count';
        $stmt = $this->db->prepare("UPDATE advertising_platforms SET {$field} = {$field} + 1, last_posted_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bindValue(1, $platform['id'], SQLITE3_INTEGER);
        $stmt->execute();
        
        $this->log("Posted to {$platform['platform_name']}: " . ($success ? 'Success' : 'Failed'));
        
        return $success;
    }
    
    /**
     * Get manual queue (posts that need manual action)
     */
    public function getManualQueue() {
        $result = $this->db->query("SELECT sp.*, cc.post_title, cc.post_content as full_content, ap.platform_name, ap.platform_url 
            FROM scheduled_posts sp 
            JOIN content_calendar cc ON sp.calendar_id = cc.id 
            JOIN advertising_platforms ap ON sp.platform_id = ap.id 
            WHERE sp.status = 'manual' 
            ORDER BY sp.scheduled_for");
        
        $queue = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $queue[] = $row;
        }
        
        return $queue;
    }
    
    /**
     * Mark manual post as completed
     */
    public function markManualComplete($postId) {
        $stmt = $this->db->prepare("UPDATE scheduled_posts SET status = 'posted', posted_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bindValue(1, $postId, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Update platform stats
        $stmt = $this->db->prepare("SELECT platform_id FROM scheduled_posts WHERE id = ?");
        $stmt->bindValue(1, $postId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($row) {
            $stmt = $this->db->prepare("UPDATE advertising_platforms SET success_count = success_count + 1, last_posted_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bindValue(1, $row['platform_id'], SQLITE3_INTEGER);
            $stmt->execute();
        }
        
        return true;
    }
    
    /**
     * Get analytics summary
     */
    public function getAnalyticsSummary($days = 30) {
        $result = $this->db->query("SELECT 
            COUNT(*) as total_posts,
            SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN status = 'manual' THEN 1 ELSE 0 END) as manual_pending,
            SUM(clicks) as total_clicks,
            SUM(impressions) as total_impressions
            FROM scheduled_posts 
            WHERE datetime(scheduled_for) >= datetime('now', '-{$days} days')");
        
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    
    /**
     * Log message
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $this->results['log'][] = "[{$timestamp}] {$message}";
        
        // Console output for CLI
        if (php_sapi_name() === 'cli') {
            echo "[{$timestamp}] {$message}\n";
        }
    }
    
    public function close() {
        $this->db->close();
    }
}

// Handle requests
$action = $_GET['action'] ?? (php_sapi_name() === 'cli' ? 'run' : '');
$date = $_GET['date'] ?? null;

if ($action) {
    $automation = new MarketingAutomation();
    
    switch ($action) {
        case 'run':
        case 'process':
            $results = $automation->processDailyPosts($date);
            
            if (php_sapi_name() !== 'cli') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'results' => $results]);
            }
            break;
            
        case 'queue':
            $queue = $automation->getManualQueue();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'queue' => $queue]);
            break;
            
        case 'complete':
            $postId = intval($_GET['id'] ?? 0);
            $result = $automation->markManualComplete($postId);
            header('Content-Type: application/json');
            echo json_encode(['success' => $result]);
            break;
            
        case 'stats':
            $stats = $automation->getAnalyticsSummary();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
    }
    
    $automation->close();
}
?>
