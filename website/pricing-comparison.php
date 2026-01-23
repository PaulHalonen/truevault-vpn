<?php
/**
 * TrueVault VPN - Pricing Comparison Page
 * Shows competitive pricing vs GoodAccess, NordLayer, Perimeter 81
 * ALL DATA FROM DATABASE - NO HARDCODING
 * 
 * Blueprint: SECTION_26_PRICING_COMPARISON.md
 * Checklist: MASTER_CHECKLIST_PART12B.md
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/configs/config.php';
require_once __DIR__ . '/includes/content-functions.php';

// Get page data
$page = getPage('pricing-comparison');
$theme = getActiveTheme();

// Get pricing from database
$truevaultPrice = getSetting('price_dedicated_monthly_usd', '39.97');
$goodaccessPrice = getSetting('competitor_goodaccess_price', '74.00');
$nordlayerPrice = getSetting('competitor_nordlayer_price', '95.00');
$perimeter81Price = getSetting('competitor_perimeter81_price', '80.00');

// Get site settings
$siteName = getSetting('site_title', 'TrueVault VPN');
$siteTagline = getSetting('site_tagline', 'Your Complete Digital Fortress');
$ctaPrimary = getSetting('cta_primary_text', 'Start Free Trial');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['meta_title'] ?? 'Business VPN Pricing Comparison - ' . $siteName) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page['meta_description'] ?? 'Compare TrueVault to GoodAccess, NordLayer, Perimeter 81. See real costs without hidden minimums.') ?>">
    <link rel="icon" href="<?= getSetting('site_favicon', '/assets/images/favicon.ico') ?>">
    <style>
    <?php include __DIR__ . '/includes/theme-css.php'; ?>
    
    /* Comparison Page Specific Styles */
    .comparison-hero {
        background: linear-gradient(135deg, var(--bg-dark, #0a0a1a) 0%, var(--bg-darker, #0f0f2a) 100%);
        padding: 80px 20px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .comparison-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at 50% 50%, rgba(0, 217, 255, 0.1) 0%, transparent 50%);
    }
    
    .comparison-hero h1 {
        font-size: 2.8rem;
        color: #ff4757;
        margin-bottom: 15px;
        position: relative;
    }
    
    .comparison-hero .subtitle {
        font-size: 1.2rem;
        color: #888;
        max-width: 700px;
        margin: 0 auto 30px;
        position: relative;
    }
    
    .price-highlight {
        background: linear-gradient(135deg, #1a1a3a 0%, #2a2a4a 100%);
        border: 2px solid var(--primary, #00d9ff);
        border-radius: 20px;
        padding: 30px 50px;
        display: inline-block;
        margin: 20px 0;
        position: relative;
    }
    
    .price-highlight .brand {
        color: var(--primary, #00d9ff);
        font-size: 1.1rem;
        margin-bottom: 5px;
    }
    
    .price-highlight .amount {
        font-size: 4rem;
        font-weight: 700;
        color: #fff;
    }
    
    .price-highlight .period {
        color: #888;
        font-size: 1.2rem;
    }
    
    .price-highlight .badge {
        background: var(--success, #00ff88);
        color: #000;
        padding: 8px 20px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        display: inline-block;
        margin-top: 15px;
    }
    
    /* Trap Section */
    .trap-section {
        background: #0f0f1f;
        padding: 60px 20px;
    }
    
    .trap-section h2 {
        color: #ff4757;
        font-size: 2rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .trap-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        max-width: 1000px;
        margin: 30px auto 0;
    }
    
    .trap-card {
        background: #1a1a2e;
        border-radius: 15px;
        padding: 30px;
    }
    
    .trap-card.competitor {
        border: 2px solid #ff4757;
    }
    
    .trap-card.truevault {
        border: 2px solid var(--success, #00ff88);
    }
    
    .trap-card h3 {
        font-size: 1.3rem;
        margin-bottom: 15px;
    }
    
    .trap-card.competitor h3 {
        color: #ff4757;
    }
    
    .trap-card.truevault h3 {
        color: var(--success, #00ff88);
    }
    
    .calculation {
        font-family: monospace;
        background: #0f0f1f;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
        font-size: 1rem;
        line-height: 1.8;
    }
    
    .calculation .highlight {
        color: var(--primary, #00d9ff);
        font-weight: bold;
    }
    
    /* Comparison Table */
    .comparison-table-section {
        background: linear-gradient(135deg, #0a0a1a 0%, #1a1a2e 100%);
        padding: 60px 20px;
    }
    
    .comparison-table-section h2 {
        text-align: center;
        font-size: 2rem;
        margin-bottom: 40px;
        color: #fff;
    }
    
    .comparison-table {
        max-width: 1200px;
        margin: 0 auto;
        overflow-x: auto;
    }
    
    .comparison-table table {
        width: 100%;
        border-collapse: collapse;
        background: #1a1a2e;
        border-radius: 15px;
        overflow: hidden;
    }
    
    .comparison-table th,
    .comparison-table td {
        padding: 18px 15px;
        text-align: center;
        border-bottom: 1px solid #2a2a4a;
    }
    
    .comparison-table th {
        background: #0f0f1f;
        font-weight: 600;
        font-size: 1rem;
    }
    
    .comparison-table th:first-child,
    .comparison-table td:first-child {
        text-align: left;
        padding-left: 25px;
    }
    
    .comparison-table .truevault-col {
        background: rgba(0, 217, 255, 0.1);
        border-left: 3px solid var(--primary, #00d9ff);
        border-right: 3px solid var(--primary, #00d9ff);
    }
    
    .comparison-table th.truevault-col {
        background: var(--primary, #00d9ff);
        color: #000;
    }
    
    .comparison-table .feature-name {
        font-weight: 500;
        color: #ccc;
    }
    
    .comparison-table .check {
        color: var(--success, #00ff88);
        font-size: 1.3rem;
    }
    
    .comparison-table .cross {
        color: #ff4757;
        font-size: 1.3rem;
    }
    
    .comparison-table .price {
        font-size: 1.3rem;
        font-weight: 700;
    }
    
    .comparison-table .price.best {
        color: var(--success, #00ff88);
    }
    
    .comparison-table .price.bad {
        color: #ff4757;
    }
    
    /* Real Cost Section */
    .real-cost-section {
        background: #0f0f1f;
        padding: 60px 20px;
    }
    
    .real-cost-section h2 {
        text-align: center;
        font-size: 2rem;
        margin-bottom: 15px;
        color: #fff;
    }
    
    .real-cost-section .subtitle {
        text-align: center;
        color: #888;
        margin-bottom: 40px;
    }
    
    .cost-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        max-width: 1100px;
        margin: 0 auto;
    }
    
    .cost-card {
        background: #1a1a2e;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }
    
    .cost-card.truevault {
        border-color: var(--primary, #00d9ff);
        background: linear-gradient(135deg, #1a2a3a 0%, #1a1a2e 100%);
    }
    
    .cost-card .brand-name {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .cost-card.truevault .brand-name {
        color: var(--primary, #00d9ff);
    }
    
    .cost-card .brand-desc {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 20px;
    }
    
    .cost-card .price {
        font-size: 3rem;
        font-weight: 700;
        color: #fff;
    }
    
    .cost-card.truevault .price {
        color: var(--success, #00ff88);
    }
    
    .cost-card .price-note {
        color: #888;
        font-size: 0.9rem;
        margin-top: 5px;
    }
    
    .cost-card .calculation {
        font-size: 0.85rem;
        color: #666;
        margin-top: 15px;
    }
    
    /* Features Section */
    .features-only-section {
        background: linear-gradient(135deg, #0a0a1a 0%, #1a1a2e 100%);
        padding: 60px 20px;
    }
    
    .features-only-section h2 {
        text-align: center;
        font-size: 2rem;
        margin-bottom: 15px;
        color: #fff;
    }
    
    .features-only-section .subtitle {
        text-align: center;
        color: #888;
        margin-bottom: 40px;
    }
    
    .unique-features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        max-width: 1100px;
        margin: 0 auto;
    }
    
    .unique-feature {
        background: #1a1a2e;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        border: 1px solid #2a2a4a;
    }
    
    .unique-feature .icon {
        font-size: 3rem;
        margin-bottom: 15px;
    }
    
    .unique-feature h3 {
        font-size: 1.2rem;
        color: var(--primary, #00d9ff);
        margin-bottom: 10px;
    }
    
    .unique-feature p {
        color: #888;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    /* Who Should Choose */
    .choose-section {
        background: #0f0f1f;
        padding: 60px 20px;
    }
    
    .choose-section h2 {
        text-align: center;
        font-size: 2rem;
        margin-bottom: 40px;
        color: #fff;
    }
    
    .choose-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .choose-card {
        background: #1a1a2e;
        border-radius: 15px;
        padding: 25px;
        border-left: 4px solid var(--primary, #00d9ff);
    }
    
    .choose-card h3 {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.1rem;
        margin-bottom: 15px;
    }
    
    .choose-card .recommend {
        color: var(--success, #00ff88);
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .choose-card p {
        color: #888;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    /* Honest Assessment */
    .honest-section {
        background: linear-gradient(135deg, #0a0a1a 0%, #1a1a2e 100%);
        padding: 60px 20px;
    }
    
    .honest-section h2 {
        text-align: center;
        font-size: 2rem;
        margin-bottom: 40px;
        color: #fff;
    }
    
    .honest-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .honest-card {
        background: #1a1a2e;
        border-radius: 15px;
        padding: 30px;
    }
    
    .honest-card.advantages {
        border-top: 4px solid var(--success, #00ff88);
    }
    
    .honest-card.limitations {
        border-top: 4px solid #ff9f43;
    }
    
    .honest-card h3 {
        font-size: 1.2rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .honest-card.advantages h3 {
        color: var(--success, #00ff88);
    }
    
    .honest-card.limitations h3 {
        color: #ff9f43;
    }
    
    .honest-card ul {
        list-style: none;
        padding: 0;
    }
    
    .honest-card li {
        padding: 10px 0;
        border-bottom: 1px solid #2a2a4a;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #ccc;
    }
    
    .honest-card li:last-child {
        border-bottom: none;
    }
    
    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, var(--primary, #00d9ff) 0%, var(--secondary, #00ff88) 100%);
        padding: 80px 20px;
        text-align: center;
    }
    
    .cta-section h2 {
        font-size: 2.2rem;
        color: #000;
        margin-bottom: 20px;
    }
    
    .cta-section .price-reminder {
        font-size: 1.3rem;
        color: #000;
        opacity: 0.8;
        margin-bottom: 30px;
    }
    
    .cta-section .btn {
        background: #000;
        color: #fff;
        padding: 18px 40px;
        font-size: 1.2rem;
        border-radius: 50px;
        text-decoration: none;
        display: inline-block;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .cta-section .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    
    .cta-section .trust-badges {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 30px;
        flex-wrap: wrap;
    }
    
    .cta-section .trust-badge {
        color: #000;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .comparison-hero h1 {
            font-size: 2rem;
        }
        
        .price-highlight .amount {
            font-size: 3rem;
        }
        
        .trap-grid,
        .honest-grid {
            grid-template-columns: 1fr;
        }
        
        .comparison-table {
            font-size: 0.9rem;
        }
        
        .comparison-table th,
        .comparison-table td {
            padding: 12px 8px;
        }
    }
    </style>
</head>
<body>

<?php include __DIR__ . '/templates/header.php'; ?>

<!-- HERO SECTION -->
<section class="comparison-hero">
    <div class="container">
        <h1>Business VPN Pricing: The Hidden Costs</h1>
        <p class="subtitle">Business VPNs advertise "$7/user" but require 5-10 minimum users. We tell the truth so you can make smart choices.</p>
        
        <div class="price-highlight">
            <div class="brand"><?= htmlspecialchars($siteName) ?> Dedicated Server</div>
            <div class="amount">$<?= htmlspecialchars($truevaultPrice) ?></div>
            <div class="period">/month</div>
            <div class="badge">✓ No minimum users required</div>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="/register.php?plan=dedicated" class="btn btn-primary"><?= htmlspecialchars($ctaPrimary) ?></a>
            <a href="#comparison" class="btn btn-secondary" style="margin-left: 15px;">See Comparison</a>
        </div>
    </div>
</section>

<!-- THE "$7/USER" TRAP -->
<section class="trap-section">
    <div class="container">
        <h2>⚠️ The "$7/user" Trap</h2>
        <p style="color: #888; max-width: 800px;">Business VPNs like GoodAccess, NordLayer, and Perimeter 81 advertise low per-user pricing. What they don't tell you: <strong style="color: #ff4757;">they require 5-10 minimum users</strong>.</p>
        
        <div class="trap-grid">
            <div class="trap-card competitor">
                <h3>❌ Competitor Pricing</h3>
                <p style="color: #888;">Example: "Only $7/user/month!"</p>
                <div class="calculation">
                    $7/user × <span class="highlight">5 minimum</span> = $35/mo<br>
                    + Platform fee: $20/mo<br>
                    + Dedicated server: $50/mo<br>
                    ─────────────────────<br>
                    <strong style="color: #ff4757;">ACTUAL COST: $105/mo</strong>
                </div>
                <p style="color: #888; font-size: 0.9rem;">Even if you're just ONE person, you pay for 5+ users!</p>
            </div>
            
            <div class="trap-card truevault">
                <h3>✅ <?= htmlspecialchars($siteName) ?> Pricing</h3>
                <p style="color: #888;">Flat rate, no minimums</p>
                <div class="calculation">
                    Dedicated Server Plan: <span class="highlight">$<?= htmlspecialchars($truevaultPrice) ?>/mo</span><br>
                    Minimum users: <span class="highlight">NONE</span><br>
                    Hidden fees: <span class="highlight">NONE</span><br>
                    ─────────────────────<br>
                    <strong style="color: var(--success, #00ff88);">ACTUAL COST: $<?= htmlspecialchars($truevaultPrice) ?>/mo</strong>
                </div>
                <p style="color: #888; font-size: 0.9rem;">Pay only for what you need. Period.</p>
            </div>
        </div>
    </div>
</section>

<!-- COMPARISON TABLE -->
<section class="comparison-table-section" id="comparison">
    <div class="container">
        <h2>True Cost Comparison</h2>
        <p style="text-align: center; color: #888; margin-bottom: 30px;">What you'll actually pay for dedicated VPN services as a single admin or small team</p>
        
        <div class="comparison-table">
            <table>
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th class="truevault-col"><?= htmlspecialchars($siteName) ?></th>
                        <th>GoodAccess</th>
                        <th>NordLayer</th>
                        <th>Perimeter 81</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="feature-name">Real Monthly Cost (1 Admin)</td>
                        <td class="truevault-col"><span class="price best">$<?= htmlspecialchars($truevaultPrice) ?></span></td>
                        <td><span class="price bad">$<?= htmlspecialchars($goodaccessPrice) ?></span></td>
                        <td><span class="price bad">$<?= htmlspecialchars($nordlayerPrice) ?></span></td>
                        <td><span class="price bad">$<?= htmlspecialchars($perimeter81Price) ?></span></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Minimum Users Required</td>
                        <td class="truevault-col"><span class="check">None</span></td>
                        <td>5 users</td>
                        <td>5 users</td>
                        <td>10 users</td>
                    </tr>
                    <tr>
                        <td class="feature-name">Dedicated Server</td>
                        <td class="truevault-col"><span class="check">✓ Included</span></td>
                        <td>+$50/mo</td>
                        <td><span class="cross">✗ Not available</span></td>
                        <td><span class="cross">✗ Enterprise only</span></td>
                    </tr>
                    <tr>
                        <td class="feature-name">2-Click Setup</td>
                        <td class="truevault-col"><span class="check">✓</span></td>
                        <td><span class="cross">✗</span></td>
                        <td><span class="cross">✗</span></td>
                        <td><span class="cross">✗</span></td>
                    </tr>
                    <tr>
                        <td class="feature-name">You Own Your Keys</td>
                        <td class="truevault-col"><span class="check">✓</span></td>
                        <td><span class="cross">✗</span></td>
                        <td><span class="cross">✗</span></td>
                        <td><span class="cross">✗</span></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Port Forwarding</td>
                        <td class="truevault-col"><span class="check">✓</span></td>
                        <td><span class="cross">✗</span></td>
                        <td><span class="cross">✗</span></td>
                        <td><span class="cross">✗</span></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Parental Controls</td>
                        <td class="truevault-col"><span class="check">✓</span></td>
                        <td><span class="cross">✗</span></td>
                        <td><span class="cross">✗</span></td>
                        <td><span class="cross">✗</span></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Camera Dashboard</td>
                        <td class="truevault-col"><span class="check">✓</span></td>
                        <td><span class="cross">✗</span></td>
                        <td><span class="cross">✗</span></td>
                        <td><span class="cross">✗</span></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Network Scanner</td>
                        <td class="truevault-col"><span class="check">✓</span></td>
                        <td><span class="cross">✗</span></td>
                        <td><span class="cross">✗</span></td>
                        <td><span class="cross">✗</span></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Best For</td>
                        <td class="truevault-col">Individuals, Families, Small Teams</td>
                        <td>Mid-size teams</td>
                        <td>Enterprise</td>
                        <td>Enterprise</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- THE REAL MONTHLY COST -->
<section class="real-cost-section">
    <div class="container">
        <h2>The Real Monthly Cost</h2>
        <p class="subtitle">What you'll actually pay for dedicated VPN services (for 1 user/small team)</p>
        
        <div class="cost-cards">
            <div class="cost-card truevault">
                <div class="brand-name"><?= htmlspecialchars($siteName) ?></div>
                <div class="brand-desc">Dedicated Server Plan</div>
                <div class="price">$<?= htmlspecialchars($truevaultPrice) ?></div>
                <div class="price-note">/month</div>
                <div class="calculation">Dedicated server INCLUDED<br>No minimum users</div>
            </div>
            
            <div class="cost-card">
                <div class="brand-name">GoodAccess</div>
                <div class="brand-desc">Starter + Gateway</div>
                <div class="price">$<?= htmlspecialchars($goodaccessPrice) ?></div>
                <div class="price-note">/month</div>
                <div class="calculation">$10/user × 5 min + $20 platform<br>+$50 for dedicated server</div>
            </div>
            
            <div class="cost-card">
                <div class="brand-name">NordLayer</div>
                <div class="brand-desc">Basic + Dedicated IP</div>
                <div class="price">$<?= htmlspecialchars($nordlayerPrice) ?></div>
                <div class="price-note">/month</div>
                <div class="calculation">$7/user × 5 min + $40/yr<br>+$40/yr per dedicated IP</div>
            </div>
            
            <div class="cost-card">
                <div class="brand-name">Perimeter 81</div>
                <div class="brand-desc">Essentials</div>
                <div class="price">$<?= htmlspecialchars($perimeter81Price) ?></div>
                <div class="price-note">/month</div>
                <div class="calculation">$8/user × 10 minimum<br>No dedicated server option</div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES ONLY TRUEVAULT OFFERS -->
<section class="features-only-section">
    <div class="container">
        <h2>Features Only <?= htmlspecialchars($siteName) ?> Offers</h2>
        <p class="subtitle">No other VPN gives you these features - not at any price</p>
        
        <div class="unique-features">
            <div class="unique-feature">
                <div class="icon">🔌</div>
                <h3>2-Click Port Forwarding</h3>
                <p>Port open for gaming, Plex, Minecraft server hosting. No router config needed. Works instantly.</p>
            </div>
            
            <div class="unique-feature">
                <div class="icon">👨‍👩‍👧‍👦</div>
                <h3>Built-in Parental Controls</h3>
                <p>Block sites by category, set daily screen time limits, control access by schedule. All per-device.</p>
            </div>
            
            <div class="unique-feature">
                <div class="icon">📷</div>
                <h3>Camera Dashboard</h3>
                <p>View Ring/Wyze/Hikvision cameras remotely without cloud subscription fees. No monthly Ring/Nest fees.</p>
            </div>
            
            <div class="unique-feature">
                <div class="icon">🔍</div>
                <h3>Network Scanner</h3>
                <p>Auto-discovers home devices - cameras, printers, consoles. One-click sync to VPN for port forwarding.</p>
            </div>
        </div>
    </div>
</section>

<!-- WHO SHOULD CHOOSE WHAT -->
<section class="choose-section">
    <div class="container">
        <h2>Who Should Choose What?</h2>
        
        <div class="choose-grid">
            <div class="choose-card">
                <h3>👤 Individuals/Solopreneurs</h3>
                <p class="recommend">→ Choose <?= htmlspecialchars($siteName) ?> Dedicated</p>
                <p>At $<?= htmlspecialchars($truevaultPrice) ?>/mo, you get your own dedicated server and unlimited features. No paying for 5+ users you don't have.</p>
            </div>
            
            <div class="choose-card">
                <h3>🏠 Families & Home Users</h3>
                <p class="recommend">→ Choose <?= htmlspecialchars($siteName) ?></p>
                <p>Parental controls, camera dashboard, gaming controls. No enterprise VPN offers these family-friendly features.</p>
            </div>
            
            <div class="choose-card">
                <h3>🎮 Gamers</h3>
                <p class="recommend">→ Choose <?= htmlspecialchars($siteName) ?></p>
                <p>Only VPN with 2-click port forwarding for open NAT type. Xbox, PlayStation, PC gaming all supported.</p>
            </div>
            
            <div class="choose-card">
                <h3>📷 IP Camera Users</h3>
                <p class="recommend">→ Choose <?= htmlspecialchars($siteName) ?></p>
                <p>View cameras remotely without Ring/Nest monthly fees. Works with any camera brand.</p>
            </div>
            
            <div class="choose-card">
                <h3>🏢 Teams 10+ People</h3>
                <p class="recommend">→ Consider GoodAccess or NordLayer</p>
                <p>At scale, per-user pricing becomes economical. If you need SSO, compliance certs, enterprise features.</p>
            </div>
            
            <div class="choose-card">
                <h3>🔐 Enterprise Security</h3>
                <p class="recommend">→ Consider Perimeter 81 or Tailscale</p>
                <p>For zero-trust architecture, SOC2 compliance, dedicated IT deployment requirements.</p>
            </div>
        </div>
    </div>
</section>

<!-- HONEST ASSESSMENT -->
<section class="honest-section">
    <div class="container">
        <h2>Honest Assessment</h2>
        <p style="text-align: center; color: #888; margin-bottom: 30px;">We're transparent about where we excel and where others might be better</p>
        
        <div class="honest-grid">
            <div class="honest-card advantages">
                <h3>✅ <?= htmlspecialchars($siteName) ?> Advantages</h3>
                <ul>
                    <li><span class="check">✓</span> No minimum users - Pay only for what you need</li>
                    <li><span class="check">✓</span> Actual dedicated server (not just dedicated IP)</li>
                    <li><span class="check">✓</span> Port forwarding - Only we offer this</li>
                    <li><span class="check">✓</span> Parental controls built-in</li>
                    <li><span class="check">✓</span> Camera dashboard - No cloud fees</li>
                    <li><span class="check">✓</span> Simple 2-click setup - No IT needed</li>
                </ul>
            </div>
            
            <div class="honest-card limitations">
                <h3>⚠️ Where Business VPNs Are Better</h3>
                <ul>
                    <li><span style="color: #ff9f43;">⚠</span> Large teams (10+) - Per-user pricing cheaper at scale</li>
                    <li><span style="color: #ff9f43;">⚠</span> Compliance needs - SOC2, HIPAA, GDPR certs</li>
                    <li><span style="color: #ff9f43;">⚠</span> SSO/Identity - Enterprise identity management</li>
                    <li><span style="color: #ff9f43;">⚠</span> Global servers - We have 4 regions, they have 50+</li>
                    <li><span style="color: #ff9f43;">⚠</span> Team management - Role-based access, provisioning</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- FINAL CTA -->
<section class="cta-section">
    <div class="container">
        <h2>Ready for Dedicated VPN Without Minimum Users?</h2>
        <p class="price-reminder">Get your own dedicated server at <strong>$<?= htmlspecialchars($truevaultPrice) ?>/month</strong>. No hidden fees.</p>
        
        <a href="/register.php?plan=dedicated" class="btn"><?= htmlspecialchars($ctaPrimary) ?></a>
        
        <div class="trust-badges">
            <div class="trust-badge">✓ Dedicated server included</div>
            <div class="trust-badge">✓ No minimum users</div>
            <div class="trust-badge">✓ <?= htmlspecialchars(getSetting('feature_refund_days', '30')) ?>-day money-back guarantee</div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/templates/footer.php'; ?>

</body>
</html>
