<?php
/**
 * TrueVault VPN - Marketing Automation Setup
 * Part 15 - Task 15.1
 * Creates campaigns.db with 5 tables
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_CAMPAIGNS', DB_PATH . 'campaigns.db');

echo "<h1>Marketing Automation Setup</h1>\n";

try {
    // Create database
    $db = new SQLite3(DB_CAMPAIGNS);
    $db->enableExceptions(true);
    
    // TABLE 1: advertising_platforms
    $db->exec("CREATE TABLE IF NOT EXISTS advertising_platforms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        platform_name TEXT NOT NULL UNIQUE,
        platform_type TEXT NOT NULL,
        platform_url TEXT NOT NULL,
        api_available INTEGER DEFAULT 0,
        api_endpoint TEXT,
        api_key TEXT,
        posting_frequency TEXT DEFAULT 'daily',
        is_active INTEGER DEFAULT 1,
        last_posted_at TEXT,
        success_count INTEGER DEFAULT 0,
        failure_count INTEGER DEFAULT 0,
        notes TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Table: advertising_platforms</p>\n";
    
    // TABLE 2: content_calendar
    $db->exec("CREATE TABLE IF NOT EXISTS content_calendar (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        calendar_date TEXT NOT NULL,
        day_of_year INTEGER,
        is_holiday INTEGER DEFAULT 0,
        holiday_name TEXT,
        post_type TEXT NOT NULL,
        post_title TEXT NOT NULL,
        post_content TEXT NOT NULL,
        platforms TEXT,
        pricing_override TEXT,
        is_posted INTEGER DEFAULT 0,
        posted_at TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_calendar_date ON content_calendar(calendar_date)");
    echo "<p>✅ Table: content_calendar</p>\n";
    
    // TABLE 3: scheduled_posts
    $db->exec("CREATE TABLE IF NOT EXISTS scheduled_posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        calendar_id INTEGER NOT NULL,
        platform_id INTEGER NOT NULL,
        scheduled_for TEXT NOT NULL,
        post_content TEXT NOT NULL,
        media_urls TEXT,
        status TEXT DEFAULT 'pending',
        posted_at TEXT,
        error_message TEXT,
        clicks INTEGER DEFAULT 0,
        impressions INTEGER DEFAULT 0,
        FOREIGN KEY (calendar_id) REFERENCES content_calendar(id),
        FOREIGN KEY (platform_id) REFERENCES advertising_platforms(id)
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_scheduled_status ON scheduled_posts(status)");
    echo "<p>✅ Table: scheduled_posts</p>\n";
    
    // TABLE 4: post_templates
    $db->exec("CREATE TABLE IF NOT EXISTS post_templates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        template_name TEXT NOT NULL,
        template_type TEXT NOT NULL,
        template_content TEXT NOT NULL,
        platforms TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Table: post_templates</p>\n";
    
    // TABLE 5: marketing_analytics
    $db->exec("CREATE TABLE IF NOT EXISTS marketing_analytics (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        date TEXT NOT NULL,
        platform_id INTEGER NOT NULL,
        impressions INTEGER DEFAULT 0,
        clicks INTEGER DEFAULT 0,
        conversions INTEGER DEFAULT 0,
        revenue REAL DEFAULT 0.0,
        cost REAL DEFAULT 0.0,
        FOREIGN KEY (platform_id) REFERENCES advertising_platforms(id)
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_analytics_date ON marketing_analytics(date)");
    echo "<p>✅ Table: marketing_analytics</p>\n";
    
    // ========================================
    // INSERT 50+ ADVERTISING PLATFORMS
    // ========================================
    
    $platforms = [
        // SOCIAL MEDIA (10)
        ['Facebook', 'social', 'https://facebook.com', 1, 'https://graph.facebook.com', 'daily'],
        ['Twitter/X', 'social', 'https://twitter.com', 1, 'https://api.twitter.com', 'daily'],
        ['LinkedIn', 'social', 'https://linkedin.com', 1, 'https://api.linkedin.com', 'daily'],
        ['Pinterest', 'social', 'https://pinterest.com', 1, 'https://api.pinterest.com', 'daily'],
        ['Instagram', 'social', 'https://instagram.com', 1, 'https://graph.instagram.com', 'daily'],
        ['TikTok', 'social', 'https://tiktok.com', 0, null, 'daily'],
        ['YouTube', 'social', 'https://youtube.com', 1, 'https://www.googleapis.com/youtube', 'weekly'],
        ['Snapchat', 'social', 'https://snapchat.com', 0, null, 'weekly'],
        ['Tumblr', 'social', 'https://tumblr.com', 1, 'https://api.tumblr.com', 'weekly'],
        ['Reddit', 'social', 'https://reddit.com', 1, 'https://oauth.reddit.com', 'weekly'],
        
        // PRESS RELEASE SITES (20)
        ['24-7 Press Release', 'press_release', 'https://www.24-7pressrelease.com', 0, null, 'monthly'],
        ['PR.com', 'press_release', 'https://www.pr.com', 0, null, 'monthly'],
        ['OpenPR', 'press_release', 'https://www.openpr.com', 0, null, 'monthly'],
        ['PRLog', 'press_release', 'https://www.prlog.org', 0, null, 'monthly'],
        ['PR Newswire Free', 'press_release', 'https://www.prnewswire.com', 0, null, 'monthly'],
        ['Press Release Jet', 'press_release', 'https://pressreleasejet.com', 0, null, 'monthly'],
        ['Free Press Release', 'press_release', 'https://www.free-press-release.com', 0, null, 'monthly'],
        ['News Wire Today', 'press_release', 'https://newswiretoday.com', 0, null, 'monthly'],
        ['PR Fire', 'press_release', 'https://prfire.com', 0, null, 'monthly'],
        ['PR Zoom', 'press_release', 'https://przoom.com', 0, null, 'monthly'],
        ['Express Press Release', 'press_release', 'https://expresspressrelease.com', 0, null, 'monthly'],
        ['1888 Press Release', 'press_release', 'https://www.1888pressrelease.com', 0, null, 'monthly'],
        ['PRFree', 'press_release', 'https://www.prfree.com', 0, null, 'monthly'],
        ['Online PR News', 'press_release', 'https://www.onlineprnews.com', 0, null, 'monthly'],
        ['Press Release Point', 'press_release', 'https://www.pressreleasepoint.com', 0, null, 'monthly'],
        ['I-Newswire', 'press_release', 'https://www.i-newswire.com', 0, null, 'monthly'],
        ['Press Release Distribution', 'press_release', 'https://www.pressreleasedistribution.com', 0, null, 'monthly'],
        ['WebWire', 'press_release', 'https://www.webwire.com', 0, null, 'monthly'],
        ['SBWire', 'press_release', 'https://www.sbwire.com', 0, null, 'monthly'],
        ['Newswire Today', 'press_release', 'https://www.newswiretoday.com', 0, null, 'monthly'],
        
        // CLASSIFIED SITES (5)
        ['Craigslist', 'classified', 'https://craigslist.org', 0, null, 'weekly'],
        ['Gumtree', 'classified', 'https://gumtree.com', 0, null, 'weekly'],
        ['Oodle', 'classified', 'https://www.oodle.com', 0, null, 'weekly'],
        ['ClassifiedAds', 'classified', 'https://www.classifiedads.com', 0, null, 'weekly'],
        ['Locanto', 'classified', 'https://www.locanto.com', 0, null, 'weekly'],
        
        // BUSINESS DIRECTORIES (10)
        ['Google Business Profile', 'directory', 'https://business.google.com', 1, 'https://mybusiness.googleapis.com', 'weekly'],
        ['Yelp for Business', 'directory', 'https://biz.yelp.com', 0, null, 'monthly'],
        ['Yellow Pages', 'directory', 'https://www.yellowpages.com', 0, null, 'monthly'],
        ['Bing Places', 'directory', 'https://www.bingplaces.com', 0, null, 'monthly'],
        ['Apple Maps Connect', 'directory', 'https://mapsconnect.apple.com', 0, null, 'monthly'],
        ['Manta', 'directory', 'https://www.manta.com', 0, null, 'monthly'],
        ['Merchant Circle', 'directory', 'https://www.merchantcircle.com', 0, null, 'monthly'],
        ['Hotfrog', 'directory', 'https://www.hotfrog.com', 0, null, 'monthly'],
        ['Cylex', 'directory', 'https://www.cylex.us.com', 0, null, 'monthly'],
        ['Tupalo', 'directory', 'https://www.tupalo.com', 0, null, 'monthly'],
        
        // CONTENT PLATFORMS (5)
        ['Medium', 'content', 'https://medium.com', 1, 'https://api.medium.com', 'weekly'],
        ['Quora', 'content', 'https://quora.com', 0, null, 'daily'],
        ['LinkedIn Articles', 'content', 'https://linkedin.com/pulse', 1, 'https://api.linkedin.com', 'weekly'],
        ['Substack', 'content', 'https://substack.com', 0, null, 'weekly'],
        ['Dev.to', 'content', 'https://dev.to', 1, 'https://dev.to/api', 'weekly'],
    ];
    
    $stmt = $db->prepare("INSERT OR IGNORE INTO advertising_platforms (platform_name, platform_type, platform_url, api_available, api_endpoint, posting_frequency) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($platforms as $p) {
        $stmt->reset();
        $stmt->bindValue(1, $p[0], SQLITE3_TEXT);
        $stmt->bindValue(2, $p[1], SQLITE3_TEXT);
        $stmt->bindValue(3, $p[2], SQLITE3_TEXT);
        $stmt->bindValue(4, $p[3], SQLITE3_INTEGER);
        $stmt->bindValue(5, $p[4], SQLITE3_TEXT);
        $stmt->bindValue(6, $p[5], SQLITE3_TEXT);
        $stmt->execute();
    }
    
    echo "<p>✅ Inserted " . count($platforms) . " advertising platforms</p>\n";
    
    // ========================================
    // INSERT POST TEMPLATES
    // ========================================
    
    $templates = [
        // Announcements
        ['Product Launch', 'announcement', "🚀 Exciting News!\n\n{title}\n\n{description}\n\nLearn more: {url}\n\n#TrueVault #VPN #Privacy", '["facebook","twitter","linkedin"]'],
        ['Feature Update', 'announcement', "✨ New Feature Alert!\n\nWe just released {feature_name}!\n\n{benefits}\n\nUpdate now: {url}", '["facebook","twitter"]'],
        ['Company News', 'announcement', "📢 {title}\n\n{content}\n\nRead more: {url}", '["linkedin","facebook"]'],
        
        // Promotions
        ['Holiday Sale', 'promotion', "🎉 {holiday} Special!\n\nGet {discount}% OFF all plans!\n\nUse code: {code}\n\nValid until {expiry}\n\n{url}", '["facebook","twitter","instagram"]'],
        ['Flash Sale', 'promotion', "⚡ FLASH SALE!\n\n{discount}% off for the next {hours} hours only!\n\nCode: {code}\n\n{url}", '["twitter","facebook"]'],
        ['New User Deal', 'promotion', "👋 New to TrueVault?\n\nGet your first month for just {price}!\n\nStart protecting your privacy today.\n\n{url}", '["facebook","instagram"]'],
        
        // Tips & Education
        ['VPN Tip', 'tip', "💡 VPN Tip #{number}\n\n{tip}\n\n{explanation}\n\n#VPNTips #Privacy #CyberSecurity", '["twitter","facebook"]'],
        ['Security Alert', 'tip', "🔒 Security Alert\n\n{alert_title}\n\n{details}\n\nProtect yourself: {url}", '["twitter","linkedin"]'],
        ['Privacy Fact', 'tip', "📊 Did you know?\n\n{fact}\n\n{source}\n\nProtect your data with TrueVault VPN.\n\n#Privacy", '["twitter","facebook"]'],
        
        // Testimonials
        ['Customer Review', 'testimonial', "⭐⭐⭐⭐⭐\n\n\"{quote}\"\n\n- {customer_name}, {location}\n\nJoin thousands of satisfied users.\n\n{url}", '["facebook","instagram"]'],
        ['Case Study', 'testimonial', "📈 Success Story\n\n{company} improved their {metric} by {percentage}% with TrueVault VPN.\n\nRead the full story: {url}", '["linkedin","twitter"]'],
        
        // Engagement
        ['Question', 'engagement', "🤔 {question}\n\nShare your thoughts below! 👇\n\n#VPN #Privacy #Poll", '["twitter","facebook"]'],
        ['Poll', 'engagement', "📊 Quick Poll:\n\n{poll_question}\n\nA) {option_a}\nB) {option_b}\nC) {option_c}\n\nVote now!", '["twitter"]'],
    ];
    
    $stmt = $db->prepare("INSERT INTO post_templates (template_name, template_type, template_content, platforms) VALUES (?, ?, ?, ?)");
    
    foreach ($templates as $t) {
        $stmt->reset();
        $stmt->bindValue(1, $t[0], SQLITE3_TEXT);
        $stmt->bindValue(2, $t[1], SQLITE3_TEXT);
        $stmt->bindValue(3, $t[2], SQLITE3_TEXT);
        $stmt->bindValue(4, $t[3], SQLITE3_TEXT);
        $stmt->execute();
    }
    
    echo "<p>✅ Inserted " . count($templates) . " post templates</p>\n";
    
    $db->close();
    
    echo "<h2>✅ Marketing Automation Setup Complete!</h2>\n";
    echo "<p><a href='index.php'>Go to Marketing Dashboard</a></p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
