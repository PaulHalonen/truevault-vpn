<?php
/**
 * TrueVault VPN - 365-Day Content Calendar Generator
 * Part 15 - Task 15.3
 * Pre-generates entire year of marketing content
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_CAMPAIGNS', DB_PATH . 'campaigns.db');

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Define holidays with special promotions
$holidays = [
    '01-01' => ['name' => 'New Years Day', 'discount' => 50, 'code' => 'NEWYEAR' . $year],
    '01-15' => ['name' => 'MLK Day', 'discount' => 25, 'code' => 'MLK' . $year],
    '02-14' => ['name' => 'Valentines Day', 'discount' => 30, 'code' => 'LOVE' . $year],
    '02-17' => ['name' => 'Presidents Day', 'discount' => 25, 'code' => 'PRES' . $year],
    '03-17' => ['name' => 'St Patricks Day', 'discount' => 17, 'code' => 'LUCKY' . $year],
    '04-22' => ['name' => 'Earth Day', 'discount' => 22, 'code' => 'EARTH' . $year],
    '05-05' => ['name' => 'Cinco de Mayo', 'discount' => 25, 'code' => 'CINCO' . $year],
    '05-26' => ['name' => 'Memorial Day', 'discount' => 30, 'code' => 'MEMORIAL' . $year],
    '06-19' => ['name' => 'Juneteenth', 'discount' => 20, 'code' => 'FREEDOM' . $year],
    '07-04' => ['name' => 'Independence Day', 'discount' => 40, 'code' => 'JULY4' . $year],
    '09-01' => ['name' => 'Labor Day', 'discount' => 30, 'code' => 'LABOR' . $year],
    '10-31' => ['name' => 'Halloween', 'discount' => 31, 'code' => 'SPOOKY' . $year],
    '11-11' => ['name' => 'Veterans Day', 'discount' => 30, 'code' => 'VETS' . $year],
    '11-27' => ['name' => 'Thanksgiving', 'discount' => 35, 'code' => 'THANKS' . $year],
    '11-28' => ['name' => 'Black Friday', 'discount' => 60, 'code' => 'BLACKFRI' . $year],
    '12-01' => ['name' => 'Cyber Monday', 'discount' => 55, 'code' => 'CYBER' . $year],
    '12-25' => ['name' => 'Christmas', 'discount' => 40, 'code' => 'XMAS' . $year],
    '12-31' => ['name' => 'New Years Eve', 'discount' => 45, 'code' => 'NYE' . $year],
];

// Daily content themes by day of week
$dailyThemes = [
    1 => ['type' => 'tip', 'title' => 'Monday VPN Tip', 'platforms' => ['twitter', 'facebook']],
    2 => ['type' => 'news', 'title' => 'Tuesday Security News', 'platforms' => ['twitter', 'linkedin']],
    3 => ['type' => 'testimonial', 'title' => 'Wednesday Wins', 'platforms' => ['facebook', 'instagram']],
    4 => ['type' => 'feature', 'title' => 'Thursday Feature Spotlight', 'platforms' => ['twitter', 'facebook', 'linkedin']],
    5 => ['type' => 'promo', 'title' => 'Friday Deals', 'platforms' => ['twitter', 'facebook', 'instagram']],
    6 => ['type' => 'fact', 'title' => 'Saturday Privacy Facts', 'platforms' => ['twitter', 'facebook']],
    0 => ['type' => 'roundup', 'title' => 'Sunday Weekly Roundup', 'platforms' => ['facebook', 'linkedin']],
];

// VPN Tips pool
$vpnTips = [
    "Always enable your VPN kill switch to prevent data leaks if your connection drops.",
    "Use split tunneling to route only specific apps through your VPN.",
    "Connect to the nearest server for better speeds when not geo-bypassing.",
    "Enable auto-connect on startup to stay protected automatically.",
    "Check for DNS leaks regularly using online tools.",
    "Use obfuscation mode if your ISP throttles VPN connections.",
    "Keep your VPN app updated for the latest security patches.",
    "Multi-hop connections add extra encryption but reduce speed.",
    "WireGuard protocol offers the best balance of speed and security.",
    "Use dedicated IPs for banking and important accounts.",
    "Change your VPN server periodically for better privacy.",
    "VPN + Tor provides maximum anonymity for sensitive browsing.",
    "Avoid free VPNs - if the product is free, you're the product.",
    "Test your VPN's speed on different servers to find the fastest.",
    "Use port forwarding for remote access and gaming.",
    "Check your VPN provider's logging policy carefully.",
    "Mobile VPNs protect you on public WiFi networks.",
    "Browser extensions only protect browser traffic, not apps.",
    "Streaming services may block known VPN IPs - switch servers if needed.",
    "Enable IPv6 leak protection in your VPN settings.",
    "Use your VPN's built-in malware and ad blocker if available.",
    "Connect before joining any public WiFi network.",
    "Some routers support VPN connections for whole-network protection.",
    "VPNs encrypt your traffic but don't make you anonymous alone.",
    "Check if your VPN supports P2P on all or specific servers.",
];

// Security facts pool
$securityFacts = [
    "Over 4.1 billion records were exposed in data breaches in the first half of 2019 alone.",
    "43% of cyber attacks target small businesses.",
    "95% of cybersecurity breaches are caused by human error.",
    "The average cost of a data breach is $3.86 million.",
    "Hackers attack every 39 seconds on average.",
    "Remote work has increased cybersecurity vulnerabilities by 238%.",
    "Phishing attacks account for 90% of data breaches.",
    "IoT devices are attacked within 5 minutes of being connected.",
    "Only 5% of companies' folders are properly protected.",
    "It takes an average of 280 days to identify a breach.",
    "Password attacks happen 921 times per second globally.",
    "Ransomware attacks increased by 150% in 2020.",
    "The average ransom demand is over $200,000.",
    "65% of Americans have never checked if they were affected by a breach.",
    "Public WiFi networks are the #1 target for hackers.",
];

// Feature highlights pool
$features = [
    ["name" => "WireGuard Protocol", "desc" => "Experience lightning-fast speeds with our modern WireGuard implementation. Up to 3x faster than OpenVPN."],
    ["name" => "Smart Identity Router", "desc" => "Maintain consistent digital identities for different regions. Your banking persona stays the same every time."],
    ["name" => "Mesh Network", "desc" => "Connect all your devices as if they're on the same network, anywhere in the world."],
    ["name" => "Zero-Log Policy", "desc" => "We never store your browsing data, connection logs, or any identifying information."],
    ["name" => "Kill Switch", "desc" => "Automatically disconnect your internet if the VPN drops, preventing any data leaks."],
    ["name" => "Split Tunneling", "desc" => "Choose which apps use the VPN and which connect directly."],
    ["name" => "Port Forwarding", "desc" => "Perfect for remote access, gaming servers, and hosting services."],
    ["name" => "Parental Controls", "desc" => "Protect your family with content filters, time limits, and device management."],
    ["name" => "Multi-Device Support", "desc" => "Protect up to 10 devices with a single subscription."],
    ["name" => "24/7 Support", "desc" => "Our team is always available to help with any issues."],
];

// Testimonials pool
$testimonials = [
    ["quote" => "TrueVault is the fastest VPN I've ever used. No more buffering!", "name" => "Mike T.", "location" => "Texas"],
    ["quote" => "Finally a VPN that just works. Simple setup, great speeds.", "name" => "Sarah L.", "location" => "California"],
    ["quote" => "The parental controls are amazing. Peace of mind for my family.", "name" => "Jennifer K.", "location" => "Florida"],
    ["quote" => "Been using it for 6 months, zero issues. Highly recommend!", "name" => "David R.", "location" => "New York"],
    ["quote" => "Customer support is incredible. Got help in under 5 minutes.", "name" => "Amanda S.", "location" => "Washington"],
    ["quote" => "Switched from NordVPN and couldn't be happier.", "name" => "Chris M.", "location" => "Colorado"],
    ["quote" => "The mesh network feature is a game-changer for my business.", "name" => "Robert J.", "location" => "Illinois"],
    ["quote" => "Best value VPN on the market. Quality at a fair price.", "name" => "Lisa P.", "location" => "Arizona"],
];

if (isset($_POST['generate'])) {
    try {
        $db = new SQLite3(DB_CAMPAIGNS);
        $db->enableExceptions(true);
        
        // Clear existing calendar for the year
        $stmt = $db->prepare("DELETE FROM content_calendar WHERE calendar_date LIKE ?");
        $stmt->bindValue(1, $year . '-%', SQLITE3_TEXT);
        $stmt->execute();
        
        $stmt = $db->prepare("INSERT INTO content_calendar (calendar_date, day_of_year, is_holiday, holiday_name, post_type, post_title, post_content, platforms, pricing_override) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $inserted = 0;
        $tipIndex = 0;
        $factIndex = 0;
        $featureIndex = 0;
        $testimonialIndex = 0;
        
        // Generate content for each day
        $startDate = new DateTime("{$year}-01-01");
        $endDate = new DateTime("{$year}-12-31");
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($startDate, $interval, $endDate->modify('+1 day'));
        
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $monthDay = $date->format('m-d');
            $dayOfYear = intval($date->format('z')) + 1;
            $dayOfWeek = intval($date->format('w'));
            $weekNum = intval($date->format('W'));
            
            $isHoliday = isset($holidays[$monthDay]) ? 1 : 0;
            $holidayName = $isHoliday ? $holidays[$monthDay]['name'] : null;
            $pricingOverride = null;
            
            $theme = $dailyThemes[$dayOfWeek];
            $postType = $theme['type'];
            $postTitle = $theme['title'];
            $platforms = json_encode($theme['platforms']);
            
            // Generate content based on type
            if ($isHoliday) {
                $h = $holidays[$monthDay];
                $postType = 'promotion';
                $postTitle = "{$h['name']} Special: {$h['discount']}% OFF!";
                $postContent = "🎉 {$h['name']} Sale!\n\nGet {$h['discount']}% OFF all TrueVault VPN plans!\n\nUse code: {$h['code']}\n\n✅ Military-grade encryption\n✅ Unlimited bandwidth\n✅ 10+ device connections\n\nProtect your privacy today!\n\nhttps://vpn.the-truth-publishing.com\n\n#TrueVault #VPN #{$h['name']}Sale";
                $platforms = json_encode(['facebook', 'twitter', 'instagram', 'linkedin']);
                $pricingOverride = json_encode(['discount' => $h['discount'], 'code' => $h['code']]);
            } else {
                switch ($postType) {
                    case 'tip':
                        $tip = $vpnTips[$tipIndex % count($vpnTips)];
                        $tipIndex++;
                        $postContent = "💡 {$postTitle} #{$tipIndex}\n\n{$tip}\n\n#VPNTips #Privacy #CyberSecurity #TrueVault";
                        break;
                        
                    case 'news':
                        $postContent = "🔒 {$postTitle}\n\nStay informed about the latest cybersecurity threats and how to protect yourself.\n\nFollow us for daily security updates!\n\n#CyberSecurity #Privacy #InfoSec";
                        break;
                        
                    case 'testimonial':
                        $t = $testimonials[$testimonialIndex % count($testimonials)];
                        $testimonialIndex++;
                        $postContent = "⭐⭐⭐⭐⭐ {$postTitle}\n\n\"{$t['quote']}\"\n\n- {$t['name']}, {$t['location']}\n\nJoin thousands of satisfied users!\n\nhttps://vpn.the-truth-publishing.com\n\n#CustomerReview #TrueVault";
                        break;
                        
                    case 'feature':
                        $f = $features[$featureIndex % count($features)];
                        $featureIndex++;
                        $postContent = "✨ {$postTitle}: {$f['name']}\n\n{$f['desc']}\n\nTry it free for 7 days!\n\nhttps://vpn.the-truth-publishing.com\n\n#VPN #Privacy #TrueVault";
                        break;
                        
                    case 'promo':
                        $postContent = "🔥 Weekend Deal!\n\nGet 25% off your first month with TrueVault VPN!\n\n✅ Unlimited bandwidth\n✅ 10+ devices\n✅ 24/7 support\n\nCode: WEEKEND25\n\nhttps://vpn.the-truth-publishing.com\n\n#VPN #Deal #Privacy";
                        break;
                        
                    case 'fact':
                        $fact = $securityFacts[$factIndex % count($securityFacts)];
                        $factIndex++;
                        $postContent = "📊 {$postTitle}\n\n{$fact}\n\nProtect yourself with TrueVault VPN.\n\n#CyberSecurity #Privacy #DigitalSafety";
                        break;
                        
                    case 'roundup':
                        $postContent = "📰 {$postTitle} - Week {$weekNum}\n\nThis week in privacy:\n• New security tips shared\n• Feature highlights covered\n• Customer success stories\n\nThanks for being part of the TrueVault community!\n\n#WeeklyRoundup #TrueVault #Privacy";
                        break;
                        
                    default:
                        $postContent = "🛡️ Protect your privacy with TrueVault VPN.\n\n#VPN #Privacy #TrueVault";
                }
            }
            
            $stmt->reset();
            $stmt->bindValue(1, $dateStr, SQLITE3_TEXT);
            $stmt->bindValue(2, $dayOfYear, SQLITE3_INTEGER);
            $stmt->bindValue(3, $isHoliday, SQLITE3_INTEGER);
            $stmt->bindValue(4, $holidayName, SQLITE3_TEXT);
            $stmt->bindValue(5, $postType, SQLITE3_TEXT);
            $stmt->bindValue(6, $postTitle, SQLITE3_TEXT);
            $stmt->bindValue(7, $postContent, SQLITE3_TEXT);
            $stmt->bindValue(8, $platforms, SQLITE3_TEXT);
            $stmt->bindValue(9, $pricingOverride, SQLITE3_TEXT);
            $stmt->execute();
            
            $inserted++;
        }
        
        $db->close();
        
        $message = "✅ Generated {$inserted} days of content for {$year}!";
        $success = true;
        
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $success = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar Generator - TrueVault Marketing</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; padding: 30px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { margin-bottom: 30px; display: flex; align-items: center; gap: 15px; }
        .card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .card h2 { font-size: 1.1rem; margin-bottom: 20px; color: #00d9ff; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #888; }
        .form-group select, .form-group input { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; font-size: 1rem; }
        .btn { padding: 12px 25px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 1rem; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn:hover { transform: translateY(-2px); }
        .message { padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; }
        .message.success { background: rgba(0,200,83,0.15); border: 1px solid #00c853; }
        .message.error { background: rgba(255,80,80,0.15); border: 1px solid #ff5050; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat { background: rgba(255,255,255,0.03); padding: 20px; border-radius: 10px; text-align: center; }
        .stat-num { font-size: 2rem; font-weight: 700; color: #00d9ff; }
        .stat-label { font-size: 0.85rem; color: #888; margin-top: 5px; }
        .holiday-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
        .holiday-item { padding: 10px 15px; background: rgba(255,183,77,0.1); border-radius: 8px; font-size: 0.9rem; }
        .holiday-item .discount { color: #00ff88; font-weight: 600; }
        a { color: #00d9ff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📅 365-Day Calendar Generator</h1>
        
        <?php if (isset($message)): ?>
        <div class="message <?= $success ? 'success' : 'error' ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Generate Content Calendar</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Year</label>
                    <select name="year">
                        <?php for ($y = date('Y'); $y <= date('Y') + 2; $y++): ?>
                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" name="generate" class="btn btn-primary">🚀 Generate 365 Days of Content</button>
            </form>
        </div>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-num">365</div>
                <div class="stat-label">Daily Posts</div>
            </div>
            <div class="stat">
                <div class="stat-num"><?= count($holidays) ?></div>
                <div class="stat-label">Holiday Promotions</div>
            </div>
            <div class="stat">
                <div class="stat-num">7</div>
                <div class="stat-label">Content Types</div>
            </div>
            <div class="stat">
                <div class="stat-num">4+</div>
                <div class="stat-label">Platforms Each</div>
            </div>
        </div>
        
        <div class="card">
            <h2>🎉 Holiday Promotions (<?= $year ?>)</h2>
            <div class="holiday-list">
                <?php foreach ($holidays as $date => $h): ?>
                <div class="holiday-item">
                    <?= $h['name'] ?><br>
                    <span class="discount"><?= $h['discount'] ?>% OFF</span> • <?= $h['code'] ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <p><a href="index.php">← Back to Marketing Dashboard</a></p>
    </div>
</body>
</html>
