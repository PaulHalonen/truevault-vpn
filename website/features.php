<?php
/**
 * TrueVault VPN - Features Page
 * Part 12 - Database-driven features
 * ALL content from database - NO hardcoding
 */

require_once __DIR__ . '/includes/content-functions.php';

$pageData = getPage('features');
$features = getFeatures();

include __DIR__ . '/templates/header.php';
?>

<!-- Hero Section -->
<section class="page-hero">
    <div class="container">
        <h1><?= e($pageData['hero_title'] ?? 'Revolutionary Features') ?></h1>
        <p><?= e($pageData['hero_subtitle'] ?? 'TrueVault isn\'t just another VPN. It\'s a complete digital identity platform.') ?></p>
    </div>
</section>

<!-- Main Features Grid -->
<section class="features-main">
    <div class="container">
        <div class="features-grid-large">
            <?php foreach ($features as $index => $feature): ?>
            <div class="feature-card-large <?= $index % 2 === 1 ? 'reverse' : '' ?>">
                <div class="feature-content">
                    <div class="feature-icon-large"><?= e($feature['icon']) ?></div>
                    <h2><?= e($feature['title']) ?></h2>
                    <p><?= e($feature['description']) ?></p>
                </div>
                <div class="feature-visual">
                    <div class="feature-graphic">
                        <?= e($feature['icon']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Additional Features List -->
<section class="features-list-section">
    <div class="container">
        <h2 class="section-title">Everything You Need</h2>
        <p class="section-subtitle">All the features to keep you safe and connected</p>
        
        <div class="features-checklist">
            <div class="checklist-col">
                <h3>🔒 Security</h3>
                <ul>
                    <li>256-bit AES Encryption</li>
                    <li>WireGuard® Protocol</li>
                    <li>Kill Switch Protection</li>
                    <li>DNS Leak Protection</li>
                    <li>IPv6 Leak Protection</li>
                    <li>No-Log Policy</li>
                </ul>
            </div>
            
            <div class="checklist-col">
                <h3>🌐 Network</h3>
                <ul>
                    <li>Multiple Server Locations</li>
                    <li>Unlimited Bandwidth</li>
                    <li>Port Forwarding</li>
                    <li>Split Tunneling</li>
                    <li>Custom DNS</li>
                    <li>P2P Optimized Servers</li>
                </ul>
            </div>
            
            <div class="checklist-col">
                <h3>👨‍👩‍👧‍👦 Family</h3>
                <ul>
                    <li>Parental Controls</li>
                    <li>Screen Time Limits</li>
                    <li>Content Filtering</li>
                    <li>Activity Reports</li>
                    <li>Gaming Console Controls</li>
                    <li>Device Management</li>
                </ul>
            </div>
            
            <div class="checklist-col">
                <h3>📱 Apps & Support</h3>
                <ul>
                    <li>Windows App</li>
                    <li>macOS App</li>
                    <li>iOS App</li>
                    <li>Android App</li>
                    <li>Router Support</li>
                    <li>24/7 Customer Support</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Comparison Section -->
<section class="comparison-section">
    <div class="container">
        <h2 class="section-title">How We Compare</h2>
        <p class="section-subtitle">See why TrueVault is the complete solution</p>
        
        <div class="comparison-table-wrapper">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Traditional VPN</th>
                        <th class="highlight">TrueVault</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Hides IP Address</td>
                        <td>✓</td>
                        <td class="highlight">✓</td>
                    </tr>
                    <tr>
                        <td>Persistent Regional Identities</td>
                        <td>✗</td>
                        <td class="highlight">✓</td>
                    </tr>
                    <tr>
                        <td>Personal Certificate Authority</td>
                        <td>✗</td>
                        <td class="highlight">✓</td>
                    </tr>
                    <tr>
                        <td>Family/Team Mesh Network</td>
                        <td>✗</td>
                        <td class="highlight">✓</td>
                    </tr>
                    <tr>
                        <td>Decentralized Architecture</td>
                        <td>✗</td>
                        <td class="highlight">✓</td>
                    </tr>
                    <tr>
                        <td>AI-Powered Routing</td>
                        <td>✗</td>
                        <td class="highlight">✓</td>
                    </tr>
                    <tr>
                        <td>Bypasses VPN Detection</td>
                        <td>✗</td>
                        <td class="highlight">✓</td>
                    </tr>
                    <tr>
                        <td>You Control the Keys</td>
                        <td>✗</td>
                        <td class="highlight">✓</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-box">
            <h2>Ready to Take Control?</h2>
            <p>Start your 7-day free trial today. No credit card required.</p>
            <a href="/register.php" class="btn btn-primary btn-lg">Start Free Trial</a>
        </div>
    </div>
</section>

<style>
/* Page Hero */
.page-hero {
    padding: 80px 0 40px;
    text-align: center;
    background: linear-gradient(135deg, var(--background), var(--card-bg));
}

.page-hero h1 {
    font-size: 3rem;
    margin-bottom: 15px;
    background: linear-gradient(90deg, var(--text-primary), var(--primary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-hero p {
    color: var(--text-secondary);
    font-size: 1.2rem;
    max-width: 600px;
    margin: 0 auto;
}

/* Features Main */
.features-main {
    padding: 80px 0;
}

.features-grid-large {
    display: flex;
    flex-direction: column;
    gap: 80px;
}

.feature-card-large {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.feature-card-large.reverse {
    direction: rtl;
}

.feature-card-large.reverse > * {
    direction: ltr;
}

.feature-icon-large {
    font-size: 4rem;
    margin-bottom: 20px;
}

.feature-content h2 {
    font-size: 2rem;
    color: var(--primary);
    margin-bottom: 15px;
}

.feature-content p {
    color: var(--text-secondary);
    font-size: 1.1rem;
    line-height: 1.7;
}

.feature-visual {
    display: flex;
    align-items: center;
    justify-content: center;
}

.feature-graphic {
    width: 250px;
    height: 250px;
    background: linear-gradient(135deg, rgba(0, 217, 255, 0.1), rgba(0, 255, 136, 0.1));
    border: 1px solid rgba(0, 217, 255, 0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 6rem;
}

/* Features List Section */
.features-list-section {
    padding: 80px 0;
    background: var(--card-bg);
}

.section-title {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 15px;
}

.section-subtitle {
    text-align: center;
    color: var(--text-secondary);
    margin-bottom: 50px;
}

.features-checklist {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 40px;
}

.checklist-col h3 {
    font-size: 1.2rem;
    margin-bottom: 20px;
    color: var(--primary);
}

.checklist-col ul {
    list-style: none;
}

.checklist-col li {
    padding: 10px 0;
    color: var(--text-secondary);
    border-bottom: 1px solid rgba(255,255,255,0.05);
    position: relative;
    padding-left: 25px;
}

.checklist-col li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: var(--secondary);
}

/* Comparison Section */
.comparison-section {
    padding: 80px 0;
}

.comparison-table-wrapper {
    overflow-x: auto;
    max-width: 800px;
    margin: 0 auto;
}

.comparison-table {
    width: 100%;
    border-collapse: collapse;
}

.comparison-table th,
.comparison-table td {
    padding: 15px 25px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.comparison-table th {
    background: var(--card-bg);
    font-weight: 600;
}

.comparison-table th:first-child,
.comparison-table td:first-child {
    text-align: left;
}

.comparison-table .highlight {
    background: rgba(0, 217, 255, 0.1);
    color: var(--primary);
    font-weight: 600;
}

.comparison-table th.highlight {
    background: linear-gradient(135deg, rgba(0, 217, 255, 0.2), rgba(0, 255, 136, 0.2));
}

/* CTA Section */
.cta-section {
    padding: 80px 0;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
}

.cta-box {
    text-align: center;
    color: var(--background);
}

.cta-box h2 {
    font-size: 2.5rem;
    margin-bottom: 15px;
}

.cta-box p {
    font-size: 1.2rem;
    margin-bottom: 30px;
    opacity: 0.9;
}

.cta-box .btn-primary {
    background: var(--background);
    color: var(--text-primary);
}

.btn-lg {
    padding: 16px 32px;
    font-size: 1.1rem;
}

/* Responsive */
@media (max-width: 900px) {
    .feature-card-large {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .feature-card-large.reverse {
        direction: ltr;
    }
    
    .feature-visual {
        order: -1;
    }
    
    .feature-graphic {
        width: 180px;
        height: 180px;
        font-size: 4rem;
    }
    
    .features-checklist {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .page-hero h1 {
        font-size: 2.2rem;
    }
}

@media (max-width: 600px) {
    .features-checklist {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . '/templates/footer.php'; ?>
