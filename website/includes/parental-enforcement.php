<?php
/**
 * TrueVault VPN - Parental Enforcement Engine
 * Part 11 - Task 11.9
 * Master function to check if domain/port access is allowed
 * 
 * USES SQLite3 CLASS (NOT PDO!) per Master Checklist
 */

/**
 * Check if access to domain/port is allowed
 * Priority order:
 * 1. Blacklist (always block)
 * 2. Temporary blocks (check expiry)
 * 3. Quick action overrides
 * 4. Whitelist (always allow)
 * 5. Schedule rules
 * 6. Category filters
 * 7. Gaming restrictions
 * 
 * @param SQLite3 $db Database connection
 * @return array ['allowed' => bool, 'reason' => string, 'access_type' => string]
 */
function isAccessAllowed($db, $userId, $deviceId = null, $domain = null, $port = null) {
    $result = ['allowed' => true, 'reason' => 'Default allow', 'access_type' => 'full'];
    
    // Gaming ports/domains
    $gamingPorts = [3074, 3075, 3478, 3479, 3480, 27015, 27016, 27017];
    $gamingDomains = ['xbox.com', 'xboxlive.com', 'playstation.com', 'playstation.net', 'steampowered.com', 'steamcommunity.com', 'epicgames.com', 'fortnite.com', 'roblox.com'];
    $streamingDomains = ['netflix.com', 'hulu.com', 'disneyplus.com', 'youtube.com', 'twitch.tv', 'hbomax.com'];
    $socialDomains = ['facebook.com', 'instagram.com', 'tiktok.com', 'snapchat.com', 'twitter.com', 'x.com', 'reddit.com'];
    
    // 1. CHECK BLACKLIST (always block)
    if ($domain) {
        $stmt = $db->prepare("SELECT id FROM blocked_domains WHERE user_id = ? AND ? LIKE '%' || domain || '%'");
        $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
        $stmt->bindValue(2, $domain, SQLITE3_TEXT);
        $result_db = $stmt->execute();
        if ($result_db->fetchArray()) {
            return ['allowed' => false, 'reason' => 'Domain blacklisted', 'access_type' => 'blocked'];
        }
    }
    
    // 2. CHECK TEMPORARY BLOCKS
    if ($domain) {
        $stmt = $db->prepare("
            SELECT id, blocked_until FROM temporary_blocks 
            WHERE user_id = ? AND ? LIKE '%' || domain || '%' AND blocked_until > CURRENT_TIMESTAMP
            AND (device_id IS NULL OR device_id = ?)
        ");
        $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
        $stmt->bindValue(2, $domain, SQLITE3_TEXT);
        $stmt->bindValue(3, $deviceId, SQLITE3_INTEGER);
        $result_db = $stmt->execute();
        $tempBlock = $result_db->fetchArray(SQLITE3_ASSOC);
        if ($tempBlock) {
            return ['allowed' => false, 'reason' => 'Temporarily blocked until ' . $tempBlock['blocked_until'], 'access_type' => 'blocked'];
        }
    }
    
    // 3. CHECK QUICK ACTION OVERRIDES
    $stmt = $db->prepare("
        SELECT override_type, override_until FROM device_rules 
        WHERE user_id = ? AND override_enabled = 1 AND override_until > CURRENT_TIMESTAMP
        AND (device_id IS NULL OR device_id = ?)
    ");
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $stmt->bindValue(2, $deviceId, SQLITE3_INTEGER);
    $result_db = $stmt->execute();
    $override = $result_db->fetchArray(SQLITE3_ASSOC);
    
    if ($override) {
        switch ($override['override_type']) {
            case 'emergency_block':
            case 'bedtime':
                return ['allowed' => false, 'reason' => ucfirst(str_replace('_', ' ', $override['override_type'])) . ' active', 'access_type' => 'blocked'];
                
            case 'homework_mode':
                // Only whitelist allowed
                if ($domain) {
                    $stmt2 = $db->prepare("SELECT id FROM parental_whitelist WHERE (user_id = ? OR user_id = 0) AND ? LIKE '%' || domain || '%'");
                    $stmt2->bindValue(1, $userId, SQLITE3_INTEGER);
                    $stmt2->bindValue(2, $domain, SQLITE3_TEXT);
                    $result2 = $stmt2->execute();
                    if (!$result2->fetchArray()) {
                        return ['allowed' => false, 'reason' => 'Homework mode - educational sites only', 'access_type' => 'homework_only'];
                    }
                }
                break;
                
            case 'gaming_blocked':
                $isGaming = $port && in_array($port, $gamingPorts);
                $isGamingDomain = $domain && matchDomainList($domain, $gamingDomains);
                if ($isGaming || $isGamingDomain) {
                    return ['allowed' => false, 'reason' => 'Gaming temporarily blocked', 'access_type' => 'blocked'];
                }
                break;
                
            case 'extended_time':
                // Allow everything during extended time
                return ['allowed' => true, 'reason' => 'Extended time active', 'access_type' => 'full'];
        }
    }
    
    // 4. CHECK WHITELIST (always allow)
    if ($domain) {
        $stmt = $db->prepare("
            SELECT id FROM parental_whitelist 
            WHERE (user_id = ? OR user_id = 0) AND ? LIKE '%' || domain || '%'
            AND (device_id IS NULL OR device_id = ?)
        ");
        $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
        $stmt->bindValue(2, $domain, SQLITE3_TEXT);
        $stmt->bindValue(3, $deviceId, SQLITE3_INTEGER);
        $result_db = $stmt->execute();
        if ($result_db->fetchArray()) {
            return ['allowed' => true, 'reason' => 'Domain whitelisted', 'access_type' => 'full'];
        }
    }
    
    // 5. CHECK SCHEDULE RULES
    $currentWindow = getActiveWindow($db, $userId, $deviceId);
    if ($currentWindow) {
        switch ($currentWindow['access_type']) {
            case 'blocked':
                return ['allowed' => false, 'reason' => 'Blocked by schedule: ' . ($currentWindow['notes'] ?? 'Schedule rule'), 'access_type' => 'blocked'];
                
            case 'homework_only':
                $stmt2 = $db->prepare("SELECT id FROM parental_whitelist WHERE (user_id = ? OR user_id = 0) AND ? LIKE '%' || domain || '%'");
                $stmt2->bindValue(1, $userId, SQLITE3_INTEGER);
                $stmt2->bindValue(2, $domain, SQLITE3_TEXT);
                $result2 = $stmt2->execute();
                if (!$result2->fetchArray()) {
                    return ['allowed' => false, 'reason' => 'Homework time - educational only', 'access_type' => 'homework_only'];
                }
                break;
                
            case 'streaming_only':
                if (!matchDomainList($domain, $streamingDomains)) {
                    return ['allowed' => false, 'reason' => 'Streaming time - streaming only', 'access_type' => 'streaming_only'];
                }
                break;
                
            case 'gaming_only':
                $isGaming = matchDomainList($domain, $gamingDomains);
                if (!$isGaming) {
                    return ['allowed' => false, 'reason' => 'Gaming time - games only', 'access_type' => 'gaming_only'];
                }
                break;
        }
    }
    
    // 6. CHECK CATEGORY FILTERS (from Part 6)
    if ($domain) {
        $stmt = $db->prepare("
            SELECT bc.category FROM blocked_categories bc
            JOIN domain_categories dc ON bc.category = dc.category
            WHERE bc.user_id = ? AND ? LIKE '%' || dc.domain || '%'
        ");
        $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
        $stmt->bindValue(2, $domain, SQLITE3_TEXT);
        $result_db = $stmt->execute();
        $blocked = $result_db->fetchArray(SQLITE3_ASSOC);
        if ($blocked) {
            return ['allowed' => false, 'reason' => 'Category blocked: ' . $blocked['category'], 'access_type' => 'blocked'];
        }
    }
    
    // 7. CHECK GAMING RESTRICTIONS
    $isGamingRequest = ($port && in_array($port, $gamingPorts)) || matchDomainList($domain, $gamingDomains);
    if ($isGamingRequest) {
        $stmt = $db->prepare("
            SELECT gaming_enabled, daily_limit_minutes, minutes_used_today 
            FROM gaming_restrictions 
            WHERE user_id = ? AND (device_id IS NULL OR device_id = ?)
        ");
        $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
        $stmt->bindValue(2, $deviceId, SQLITE3_INTEGER);
        $result_db = $stmt->execute();
        $gaming = $result_db->fetchArray(SQLITE3_ASSOC);
        
        if ($gaming) {
            if (!$gaming['gaming_enabled']) {
                return ['allowed' => false, 'reason' => 'Gaming disabled', 'access_type' => 'blocked'];
            }
            if ($gaming['daily_limit_minutes'] && $gaming['minutes_used_today'] >= $gaming['daily_limit_minutes']) {
                return ['allowed' => false, 'reason' => 'Daily gaming limit reached', 'access_type' => 'blocked'];
            }
        }
    }
    
    return $result;
}

/**
 * Get the currently active schedule window
 */
function getActiveWindow($db, $userId, $deviceId = null) {
    $dayOfWeek = date('w'); // 0 = Sunday, 6 = Saturday
    $currentTime = date('H:i:s');
    $today = date('Y-m-d');
    
    // First check specific date overrides
    $stmt = $db->prepare("
        SELECT sw.* FROM schedule_windows sw
        JOIN parental_schedules ps ON sw.schedule_id = ps.id
        WHERE ps.user_id = ? AND ps.is_active = 1
        AND sw.specific_date = ?
        AND sw.start_time <= ? AND sw.end_time > ?
        AND (ps.device_id IS NULL OR ps.device_id = ?)
        ORDER BY sw.specific_date DESC LIMIT 1
    ");
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $stmt->bindValue(2, $today, SQLITE3_TEXT);
    $stmt->bindValue(3, $currentTime, SQLITE3_TEXT);
    $stmt->bindValue(4, $currentTime, SQLITE3_TEXT);
    $stmt->bindValue(5, $deviceId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $window = $result->fetchArray(SQLITE3_ASSOC);
    if ($window) return $window;
    
    // Check day-of-week patterns
    $stmt = $db->prepare("
        SELECT sw.* FROM schedule_windows sw
        JOIN parental_schedules ps ON sw.schedule_id = ps.id
        WHERE ps.user_id = ? AND ps.is_active = 1
        AND sw.day_of_week = ?
        AND sw.start_time <= ? AND sw.end_time > ?
        AND (ps.device_id IS NULL OR ps.device_id = ?)
        LIMIT 1
    ");
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $stmt->bindValue(2, $dayOfWeek, SQLITE3_INTEGER);
    $stmt->bindValue(3, $currentTime, SQLITE3_TEXT);
    $stmt->bindValue(4, $currentTime, SQLITE3_TEXT);
    $stmt->bindValue(5, $deviceId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

/**
 * Check if domain matches any in list
 */
function matchDomainList($domain, $list) {
    if (!$domain) return false;
    $domain = strtolower($domain);
    foreach ($list as $d) {
        if (strpos($domain, $d) !== false) return true;
    }
    return false;
}

/**
 * Categorize domain for statistics
 */
function categorizeDomain($domain, $port) {
    $gamingDomains = ['xbox.com', 'xboxlive.com', 'playstation.com', 'steampowered.com', 'epicgames.com', 'roblox.com'];
    $streamingDomains = ['netflix.com', 'hulu.com', 'disneyplus.com', 'youtube.com', 'twitch.tv'];
    $socialDomains = ['facebook.com', 'instagram.com', 'tiktok.com', 'snapchat.com', 'twitter.com'];
    $educationalDomains = ['khanacademy.org', 'wikipedia.org', 'duolingo.com', 'coursera.org'];
    
    if (matchDomainList($domain, $gamingDomains)) return 'gaming';
    if (matchDomainList($domain, $streamingDomains)) return 'streaming';
    if (matchDomainList($domain, $socialDomains)) return 'social';
    if (matchDomainList($domain, $educationalDomains)) return 'educational';
    return 'other';
}
?>
