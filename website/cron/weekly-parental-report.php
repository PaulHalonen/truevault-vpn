<?php
/**
 * TrueVault VPN - Weekly Parental Report Cron Job
 * Part 11 - Task 11.8
 * Run weekly: 0 8 * * 0 (Sunday 8 AM)
 * 
 * USES SQLite3 CLASS (NOT PDO!) per Master Checklist
 */

require_once __DIR__ . '/../configs/config.php';

echo "=== Weekly Parental Reports ===\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

// Helper function for fetchAll with SQLite3
function fetchAllAssoc($result) {
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}

try {
    $db = new SQLite3(DB_USERS);
    $db->enableExceptions(true);
    
    // Get all users with parental controls enabled (have data in parental_statistics)
    $result = $db->query("
        SELECT DISTINCT u.id, u.email, u.first_name 
        FROM users u
        JOIN parental_statistics ps ON u.id = ps.user_id
        WHERE ps.stat_date >= date('now', '-7 days')
    ");
    $users = fetchAllAssoc($result);
    
    echo "Found " . count($users) . " users with parental data\n\n";
    
    foreach ($users as $user) {
        echo "Generating report for: {$user['email']}...\n";
        
        // Get statistics summary
        $stmt = $db->prepare("
            SELECT 
                SUM(total_minutes) as screen_time,
                SUM(gaming_minutes) as gaming,
                SUM(streaming_minutes) as streaming,
                SUM(social_minutes) as social,
                SUM(educational_minutes) as educational,
                SUM(blocked_requests) as blocked
            FROM parental_statistics 
            WHERE user_id = ? AND stat_date >= date('now', '-7 days')
        ");
        $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $stats = $result->fetchArray(SQLITE3_ASSOC);
        
        // Get top blocked sites
        $stmt = $db->prepare("
            SELECT domain, COUNT(*) as count 
            FROM blocked_requests 
            WHERE user_id = ? AND blocked_at >= datetime('now', '-7 days')
            GROUP BY domain ORDER BY count DESC LIMIT 5
        ");
        $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $blocked = fetchAllAssoc($result);
        
        // Get daily breakdown
        $stmt = $db->prepare("
            SELECT stat_date, total_minutes, gaming_minutes
            FROM parental_statistics 
            WHERE user_id = ? AND stat_date >= date('now', '-7 days')
            ORDER BY stat_date
        ");
        $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $daily = fetchAllAssoc($result);
        
        // Build email HTML
        $html = buildReportEmail($user, $stats, $blocked, $daily);
        
        // Send email
        $subject = "TrueVault VPN - Weekly Parental Report";
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: TrueVault VPN <noreply@the-truth-publishing.com>\r\n";
        
        $sent = mail($user['email'], $subject, $html, $headers);
        
        if ($sent) {
            echo "  ✓ Email sent successfully\n";
        } else {
            echo "  ✗ Failed to send email\n";
        }
    }
    
    echo "\n=== Completed ===\n";
    echo "Finished: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

function buildReportEmail($user, $stats, $blocked, $daily) {
    $firstName = $user['first_name'] ?: 'Parent';
    $screenHours = floor(($stats['screen_time'] ?? 0) / 60);
    $screenMins = ($stats['screen_time'] ?? 0) % 60;
    $gamingHours = floor(($stats['gaming'] ?? 0) / 60);
    $gamingMins = ($stats['gaming'] ?? 0) % 60;
    
    $blockedHtml = '';
    if (!empty($blocked)) {
        $blockedHtml = '<ul style="margin:0;padding-left:20px;">';
        foreach ($blocked as $b) {
            $blockedHtml .= "<li>{$b['domain']} - {$b['count']} blocks</li>";
        }
        $blockedHtml .= '</ul>';
    } else {
        $blockedHtml = '<p style="color:#888;">No sites were blocked this week.</p>';
    }
    
    $dailyHtml = '';
    foreach ($daily as $d) {
        $mins = $d['total_minutes'] ?? 0;
        $day = date('D M j', strtotime($d['stat_date']));
        $bar = min(100, round($mins / 3));
        $dailyHtml .= "
            <div style='margin-bottom:8px;'>
                <div style='display:flex;justify-content:space-between;font-size:12px;'>
                    <span>{$day}</span>
                    <span>" . floor($mins/60) . "h " . ($mins%60) . "m</span>
                </div>
                <div style='background:#333;border-radius:4px;height:8px;overflow:hidden;'>
                    <div style='background:linear-gradient(90deg,#00d9ff,#00ff88);width:{$bar}%;height:100%;'></div>
                </div>
            </div>
        ";
    }
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width,initial-scale=1'>
    </head>
    <body style='margin:0;padding:0;background:#0f0f1a;font-family:-apple-system,BlinkMacSystemFont,sans-serif;'>
        <div style='max-width:600px;margin:0 auto;padding:20px;'>
            <div style='text-align:center;padding:30px 0;'>
                <h1 style='color:#00d9ff;margin:0;font-size:24px;'>🛡️ TrueVault VPN</h1>
                <p style='color:#888;margin:10px 0 0;'>Weekly Parental Report</p>
            </div>
            
            <div style='background:#1a1a2e;border-radius:16px;padding:25px;margin-bottom:20px;'>
                <h2 style='color:#fff;margin:0 0 20px;font-size:18px;'>Hi {$firstName},</h2>
                <p style='color:#aaa;margin:0 0 25px;line-height:1.6;'>Here's your family's internet usage summary for the past week.</p>
                
                <div style='display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:25px;'>
                    <div style='background:rgba(0,217,255,0.1);border-radius:12px;padding:20px;text-align:center;'>
                        <div style='font-size:28px;font-weight:700;color:#00d9ff;'>{$screenHours}h {$screenMins}m</div>
                        <div style='font-size:12px;color:#888;margin-top:5px;'>Total Screen Time</div>
                    </div>
                    <div style='background:rgba(255,107,107,0.1);border-radius:12px;padding:20px;text-align:center;'>
                        <div style='font-size:28px;font-weight:700;color:#ff6b6b;'>{$gamingHours}h {$gamingMins}m</div>
                        <div style='font-size:12px;color:#888;margin-top:5px;'>Gaming Time</div>
                    </div>
                </div>
                
                <h3 style='color:#fff;font-size:14px;margin:0 0 15px;'>📊 Daily Breakdown</h3>
                {$dailyHtml}
                
                <h3 style='color:#fff;font-size:14px;margin:25px 0 10px;'>🚫 Top Blocked Sites</h3>
                <div style='color:#aaa;font-size:14px;'>
                    {$blockedHtml}
                </div>
                
                <div style='background:rgba(255,80,80,0.1);border-radius:8px;padding:15px;margin-top:20px;text-align:center;'>
                    <span style='font-size:24px;font-weight:700;color:#ff5050;'>" . ($stats['blocked'] ?? 0) . "</span>
                    <span style='color:#888;font-size:14px;'> requests blocked this week</span>
                </div>
            </div>
            
            <div style='text-align:center;padding:20px 0;'>
                <a href='https://vpn.the-truth-publishing.com/dashboard/parental-controls.php' style='display:inline-block;background:linear-gradient(90deg,#00d9ff,#00ff88);color:#0f0f1a;padding:12px 30px;border-radius:8px;text-decoration:none;font-weight:600;'>View Full Dashboard</a>
            </div>
            
            <div style='text-align:center;padding:20px 0;border-top:1px solid #333;'>
                <p style='color:#555;font-size:12px;margin:0;'>
                    TrueVault VPN - Protecting Your Family Online<br>
                    <a href='https://vpn.the-truth-publishing.com/settings/notifications' style='color:#00d9ff;'>Manage email preferences</a>
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>
