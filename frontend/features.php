<?php
require_once 'config.php';
require_once 'db.php';

$page_title = 'Features - TrueVault VPN';
$page_description = 'All the features you need for complete digital protection';

include 'header.php';
?>

<style>
    body { background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; }
    .features-container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
    .page-header { text-align: center; margin: 3rem 0; }
    .page-header h1 { font-size: 3rem; margin-bottom: 1rem; }
    .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin: 3rem 0; }
    .feature-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 2rem; transition: 0.3s; }
    .feature-card:hover { transform: translateY(-5px); border-color: #00d9ff; box-shadow: 0 10px 30px rgba(0,217,255,0.2); }
    .feature-icon { font-size: 3rem; margin-bottom: 1rem; }
    .feature-title { font-size: 1.5rem; margin-bottom: 1rem; color: #fff; font-weight: 700; }
    .feature-description { color: #ccc; line-height: 1.6; }
</style>

<div class="features-container">
    <div class="page-header">
        <h1>Everything You Need</h1>
        <p style="font-size: 1.2rem; color: #888;">Enterprise-grade security meets simplicity</p>
    </div>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">🔒</div>
            <div class="feature-title">Military-Grade Encryption</div>
            <div class="feature-description">
                256-bit AES encryption protects all your data. Same security used by governments and banks worldwide.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">⚡</div>
            <div class="feature-title">2-Click Setup</div>
            <div class="feature-description">
                No technical knowledge required. Download config, import to app, connected in 30 seconds.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🌍</div>
            <div class="feature-title">4 Server Locations</div>
            <div class="feature-description">
                New York, St. Louis, Dallas, and Toronto. Choose the closest server for maximum speed.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📹</div>
            <div class="feature-title">Camera Dashboard</div>
            <div class="feature-description">
                Access your home security cameras from anywhere. Geeni, Wyze, Hikvision, and more supported.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🔓</div>
            <div class="feature-title">Port Forwarding</div>
            <div class="feature-description">
                Access your home devices remotely. Printers, servers, gaming consoles - all securely accessible.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">👨‍👩‍👧‍👦</div>
            <div class="feature-title">Parental Controls</div>
            <div class="feature-description">
                Block inappropriate content, set screen time limits, schedule internet access by day/time.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🚫</div>
            <div class="feature-title">No Logs Policy</div>
            <div class="feature-description">
                We don't track, store, or share your browsing activity. Your privacy is guaranteed.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">💾</div>
            <div class="feature-title">Unlimited Bandwidth</div>
            <div class="feature-description">
                No data caps, no throttling, no restrictions. Use as much bandwidth as you need.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📱</div>
            <div class="feature-title">All Devices Supported</div>
            <div class="feature-description">
                Windows, Mac, Linux, iOS, Android. Protect all your devices with one account.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🛡️</div>
            <div class="feature-title">Kill Switch</div>
            <div class="feature-description">
                Automatic internet disconnect if VPN connection drops. Your IP never leaks.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">⚙️</div>
            <div class="feature-title">Network Scanner</div>
            <div class="feature-description">
                Discover all devices on your network. Automatic detection of cameras, printers, and smart devices.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <div class="feature-title">Usage Analytics</div>
            <div class="feature-description">
                Track bandwidth usage, connection history, and device activity. Full transparency.
            </div>
        </div>
    </div>

    <div style="text-align: center; margin: 4rem 0;">
        <h2 style="font-size: 2rem; margin-bottom: 1rem;">Ready to get started?</h2>
        <a href="/pricing.php" style="display: inline-block; padding: 1rem 3rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 1.2rem;">
            View Pricing
        </a>
    </div>
</div>

<?php include 'footer.php'; ?>
