<?php
require_once 'config.php';
require_once 'db.php';

$page_title = 'About Us - TrueVault VPN';
$page_description = 'Learn about TrueVault VPN and our mission to protect your privacy';

include 'header.php';
?>

<style>
    body { background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; }
    .about-container { max-width: 900px; margin: 2rem auto; padding: 0 2rem; }
    .page-header { text-align: center; margin: 3rem 0; }
    .page-header h1 { font-size: 3rem; margin-bottom: 1rem; }
    .content-section { margin: 3rem 0; line-height: 1.8; }
    .content-section h2 { font-size: 2rem; margin-bottom: 1rem; color: #00d9ff; }
    .content-section p { color: #ccc; margin-bottom: 1.5rem; }
    .values-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin: 2rem 0; }
    .value-card { background: rgba(255,255,255,0.05); border-left: 3px solid #00d9ff; padding: 1.5rem; border-radius: 8px; }
    .value-card h3 { color: #00ff88; margin-bottom: 0.5rem; }
</style>

<div class="about-container">
    <div class="page-header">
        <h1>About TrueVault VPN</h1>
        <p style="font-size: 1.2rem; color: #888;">Privacy-first VPN with business tools</p>
    </div>

    <div class="content-section">
        <h2>Our Mission</h2>
        <p>
            TrueVault VPN was built out of frustration with overpriced, overcomplicated VPN services that nickel-and-dime 
            customers with hidden fees and restrictive user limits. We believe everyone deserves enterprise-grade privacy 
            protection without enterprise pricing.
        </p>
        <p>
            What started as a simple VPN service evolved into a complete digital protection platform when we realized our 
            customers needed more than just IP masking—they needed port forwarding for home cameras, parental controls for 
            family safety, and business tools for remote teams.
        </p>
    </div>

    <div class="content-section">
        <h2>Why We're Different</h2>
        <p>
            Most VPN companies advertise low prices, then hit you with per-user fees, setup charges, and premium feature costs. 
            We don't play those games. Our price is our price—no asterisks, no fine print, no surprises on your bill.
        </p>
        <p>
            We're also the only VPN that includes business automation tools completely free. Need a database builder? 
            It's included. Want HR management for your remote team? It's included. Looking for marketing automation? 
            Also included. We're not just protecting your connection—we're protecting your productivity.
        </p>
    </div>

    <div class="content-section">
        <h2>Our Values</h2>
        <div class="values-grid">
            <div class="value-card">
                <h3>🔒 Privacy First</h3>
                <p style="color: #ccc;">No logs, no tracking, no data collection. Your activity is yours alone.</p>
            </div>
            <div class="value-card">
                <h3>💰 Honest Pricing</h3>
                <p style="color: #ccc;">What you see is what you pay. No hidden fees, no surprise charges.</p>
            </div>
            <div class="value-card">
                <h3>⚡ Simplicity</h3>
                <p style="color: #ccc;">2-click setup, zero technical knowledge required. If it's not simple, we don't ship it.</p>
            </div>
            <div class="value-card">
                <h3>🚀 Innovation</h3>
                <p style="color: #ccc;">We're constantly adding features others charge extra for. FileMaker Pro alternative? Free.</p>
            </div>
        </div>
    </div>

    <div class="content-section">
        <h2>Our Technology</h2>
        <p>
            Built on WireGuard protocol—the modern, faster, more secure alternative to legacy VPN technologies. Our servers 
            run on enterprise-grade hardware in premium data centers across North America. Every connection is encrypted with 
            256-bit AES encryption, the same standard used by governments and financial institutions worldwide.
        </p>
    </div>

    <div class="content-section">
        <h2>Contact Us</h2>
        <p>
            Questions? Concerns? Feature requests? We actually read and respond to every email.
        </p>
        <p>
            <strong>Email:</strong> <a href="mailto:support@vpn.the-truth-publishing.com" style="color: #00d9ff; text-decoration: none;">support@vpn.the-truth-publishing.com</a><br>
            <strong>Response Time:</strong> Usually within 24 hours
        </p>
        <div style="margin-top: 2rem;">
            <a href="/contact.php" style="display: inline-block; padding: 1rem 2rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; text-decoration: none; border-radius: 8px; font-weight: 700;">
                Get in Touch
            </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
