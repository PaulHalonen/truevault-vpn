<?php
// Marketing Automation Configuration
// USES: SQLite3 class (NOT PDO)
define('MARKETING_DB_PATH', __DIR__ . '/../databases/main.db');

function getMarketingDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new SQLite3(MARKETING_DB_PATH);
            $db->enableExceptions(true);
            $db->busyTimeout(5000);
        } catch (Exception $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $db;
}

// Get all active platforms by type
function getPlatformsByType($type = null) {
    $db = getMarketingDB();
    
    if ($type) {
        $stmt = $db->prepare("SELECT * FROM marketing_platforms WHERE platform_type = :type AND is_active = 1 ORDER BY platform_name");
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $result = $stmt->execute();
    } else {
        $result = $db->query("SELECT * FROM marketing_platforms WHERE is_active = 1 ORDER BY platform_type, platform_name");
    }
    
    $platforms = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $platforms[] = $row;
    }
    return $platforms;
}

// Get platform credentials
function getPlatformCredential($platformId) {
    $db = getMarketingDB();
    $stmt = $db->prepare("SELECT * FROM platform_credentials WHERE platform_id = :id AND is_active = 1 LIMIT 1");
    $stmt->bindValue(':id', $platformId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

// Save platform credentials
function savePlatformCredential($platformId, $credentialName, $apiKey, $apiSecret = null, $additionalData = null) {
    $db = getMarketingDB();
    
    // Check if credential exists
    $stmt = $db->prepare("SELECT id FROM platform_credentials WHERE platform_id = :id AND is_active = 1");
    $stmt->bindValue(':id', $platformId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $existing = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($existing) {
        // Update
        $stmt = $db->prepare("
            UPDATE platform_credentials 
            SET credential_name = :name, api_key = :key, api_secret = :secret, 
                additional_data = :data, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->bindValue(':name', $credentialName, SQLITE3_TEXT);
        $stmt->bindValue(':key', $apiKey, SQLITE3_TEXT);
        $stmt->bindValue(':secret', $apiSecret, SQLITE3_TEXT);
        $stmt->bindValue(':data', $additionalData, SQLITE3_TEXT);
        $stmt->bindValue(':id', $existing['id'], SQLITE3_INTEGER);
        $stmt->execute();
    } else {
        // Insert
        $stmt = $db->prepare("
            INSERT INTO platform_credentials (platform_id, credential_name, api_key, api_secret, additional_data)
            VALUES (:platform_id, :name, :key, :secret, :data)
        ");
        $stmt->bindValue(':platform_id', $platformId, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $credentialName, SQLITE3_TEXT);
        $stmt->bindValue(':key', $apiKey, SQLITE3_TEXT);
        $stmt->bindValue(':secret', $apiSecret, SQLITE3_TEXT);
        $stmt->bindValue(':data', $additionalData, SQLITE3_TEXT);
        $stmt->execute();
    }
    
    return true;
}

// Create campaign
function createCampaign($name, $type, $targetAudience = null, $platforms = []) {
    $db = getMarketingDB();
    $stmt = $db->prepare("
        INSERT INTO marketing_campaigns (campaign_name, campaign_type, target_audience, platforms, status)
        VALUES (:name, :type, :audience, :platforms, 'draft')
    ");
    
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':type', $type, SQLITE3_TEXT);
    $stmt->bindValue(':audience', json_encode($targetAudience), SQLITE3_TEXT);
    $stmt->bindValue(':platforms', json_encode($platforms), SQLITE3_TEXT);
    $stmt->execute();
    
    return $db->lastInsertRowID();
}

// Get campaigns
function getCampaigns($status = null) {
    $db = getMarketingDB();
    
    if ($status) {
        $stmt = $db->prepare("SELECT * FROM marketing_campaigns WHERE status = :status ORDER BY created_at DESC");
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $result = $stmt->execute();
    } else {
        $result = $db->query("SELECT * FROM marketing_campaigns ORDER BY created_at DESC");
    }
    
    $campaigns = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $campaigns[] = $row;
    }
    return $campaigns;
}

// Get campaign analytics
function getCampaignAnalytics($campaignId) {
    $db = getMarketingDB();
    $stmt = $db->prepare("
        SELECT ca.*, mp.platform_name, mp.icon
        FROM campaign_analytics ca
        LEFT JOIN marketing_platforms mp ON ca.platform_id = mp.id
        WHERE ca.campaign_id = :id
        ORDER BY ca.metric_date DESC
    ");
    $stmt->bindValue(':id', $campaignId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $analytics = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $analytics[] = $row;
    }
    return $analytics;
}

// Get email templates
function getEmailTemplates($type = null) {
    $db = getMarketingDB();
    
    if ($type) {
        $stmt = $db->prepare("SELECT * FROM email_templates WHERE template_type = :type AND is_active = 1 ORDER BY template_name");
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $result = $stmt->execute();
    } else {
        $result = $db->query("SELECT * FROM email_templates WHERE is_active = 1 ORDER BY template_type, template_name");
    }
    
    $templates = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $templates[] = $row;
    }
    return $templates;
}

// Replace variables in template
function replaceTemplateVariables($template, $variables) {
    $content = $template;
    foreach ($variables as $key => $value) {
        $content = str_replace('{' . $key . '}', $value, $content);
    }
    return $content;
}

// Send email via platform (placeholder - needs actual API integration)
function sendEmailViaPlatform($platformId, $to, $subject, $body) {
    // This would integrate with actual platform APIs
    // For now, just log the attempt
    $db = getMarketingDB();
    
    // Get platform details
    $stmt = $db->prepare("SELECT platform_name FROM marketing_platforms WHERE id = :id");
    $stmt->bindValue(':id', $platformId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $platform = $result->fetchArray(SQLITE3_ASSOC);
    
    // Log to file for now
    $logEntry = date('Y-m-d H:i:s') . " - Sending email via {$platform['platform_name']} to $to\n";
    file_put_contents(__DIR__ . '/../logs/marketing.log', $logEntry, FILE_APPEND);
    
    return ['success' => true, 'message' => 'Email queued for delivery'];
}

// Get marketing dashboard stats
function getMarketingStats() {
    $db = getMarketingDB();
    
    $stats = [];
    
    // Total campaigns
    $result = $db->query("SELECT COUNT(*) as count FROM marketing_campaigns");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['total_campaigns'] = $row['count'];
    
    // Active campaigns
    $result = $db->query("SELECT COUNT(*) as count FROM marketing_campaigns WHERE status = 'active'");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['active_campaigns'] = $row['count'];
    
    // Connected platforms
    $result = $db->query("SELECT COUNT(DISTINCT platform_id) as count FROM platform_credentials WHERE is_active = 1");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['connected_platforms'] = $row['count'];
    
    // Messages sent today
    $result = $db->query("SELECT COUNT(*) as count FROM campaign_messages WHERE status = 'sent' AND DATE(created_at) = DATE('now')");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['messages_today'] = $row['count'];
    
    return $stats;
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
