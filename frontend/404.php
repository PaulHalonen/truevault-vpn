<?php
http_response_code(404);

require_once 'config.php';
require_once 'db.php';

$page_title = '404 - Page Not Found - TrueVault VPN';
$page_description = 'The page you are looking for could not be found';

include 'header.php';
?>

<style>
    body { background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; display: flex; flex-direction: column; }
    .error-container { max-width: 800px; margin: auto; padding: 2rem; text-align: center; flex: 1; display: flex; flex-direction: column; justify-content: center; }
    .error-code { font-size: 8rem; font-weight: 700; background: linear-gradient(90deg, #ff6464, #ff9966); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem; }
    .error-title { font-size: 2.5rem; margin-bottom: 1rem; }
    .error-message { font-size: 1.2rem; color: #888; margin-bottom: 3rem; line-height: 1.6; }
    .action-buttons { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
    .btn { padding: 1rem 2rem; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 1.1rem; transition: 0.3s; display: inline-block; }
    .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,217,255,0.4); }
    .btn-secondary { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; }
    .btn-secondary:hover { background: rgba(255,255,255,0.15); }
    .quick-links { margin-top: 3rem; }
    .quick-links h3 { color: #00d9ff; margin-bottom: 1rem; }
    .quick-links-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem; }
    .quick-link-card { background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); }
    .quick-link-card a { color: #fff; text-decoration: none; display: block; }
    .quick-link-card a:hover { color: #00d9ff; }
</style>

<div class="error-container">
    <div>
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">
            Oops! The page you're looking for doesn't exist. It might have been moved, deleted, or the URL might be incorrect.
        </p>

        <div class="action-buttons">
            <a href="/" class="btn btn-primary">← Back to Homepage</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>

        <div class="quick-links">
            <h3>Looking for something?</h3>
            <div class="quick-links-grid">
                <div class="quick-link-card">
                    <a href="/">
                        <strong>🏠 Homepage</strong><br>
                        <small style="color: #888;">Learn about TrueVault VPN</small>
                    </a>
                </div>
                <div class="quick-link-card">
                    <a href="/pricing.php">
                        <strong>💰 Pricing</strong><br>
                        <small style="color: #888;">View our plans</small>
                    </a>
                </div>
                <div class="quick-link-card">
                    <a href="/features.php">
                        <strong>⚡ Features</strong><br>
                        <small style="color: #888;">See what we offer</small>
                    </a>
                </div>
                <div class="quick-link-card">
                    <a href="/comparison.php">
                        <strong>📊 Comparison</strong><br>
                        <small style="color: #888;">Compare providers</small>
                    </a>
                </div>
                <div class="quick-link-card">
                    <a href="/about.php">
                        <strong>ℹ️ About Us</strong><br>
                        <small style="color: #888;">Our mission</small>
                    </a>
                </div>
                <div class="quick-link-card">
                    <a href="/contact.php">
                        <strong>📧 Contact</strong><br>
                        <small style="color: #888;">Get in touch</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
