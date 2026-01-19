<?php
require_once 'config.php';
require_once 'db.php';

$page_title = 'TrueVault VPN - Secure Privacy Protection';
$page_description = 'Protect your privacy with TrueVault VPN';

include 'header.php';
?>

<main style="max-width: 1200px; margin: 2rem auto; padding: 0 2rem;">
    <section style="text-align: center; padding: 4rem 0;">
        <h2 style="font-size: 3rem; margin-bottom: 1rem; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            Your Complete Digital Fortress
        </h2>
        <p style="font-size: 1.2rem; color: #666; margin-bottom: 2rem;">
            Enterprise-grade VPN protection + Business automation tools
        </p>
        <a href="/pricing.php" style="display: inline-block; padding: 1rem 2rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; text-decoration: none; border-radius: 8px; font-weight: bold;">
            Start Free Trial
        </a>
    </section>
</main>

<?php include 'footer.php'; ?>
