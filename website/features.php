<?php
/**
 * TrueVault VPN - Features Page
 * Part 12 - Database-driven features
 * ALL content from database - NO hardcoding
 * Features from SECTION_01_SYSTEM_OVERVIEW.md (real features only)
 */

require_once __DIR__ . '/includes/content-functions.php';

$pageData = getPage('features');
$features = getFeatures();
$featureComparison = getFeatureComparison(); // TrueVault vs Traditional VPN
$trialDays = getSetting('feature_trial_days', '7');

// Group features by category
$coreFeatures = array_filter($features, fn($f) => $f['category'] === 'core');
$uniqueFeatures = array_filter($features, fn($f) => $f['category'] === 'unique');
$extraFeatures = array_filter($features, fn($f) => $f['category'] === 'extra');

include __DIR__ . '/templates/header.php';
?>

<!-- Hero Section -->
<section class="page-hero">
    <div class="container">
        <h1><?= e($pageData['hero_title'] ?? 'More Than Just a VPN') ?></h1>
        <p><?= e($pageData['hero_subtitle'] ?? 'TrueVault actually solves real problems — not just hides your IP.') ?></p>
    </div>
</section>

<!-- What Makes Us Different (Unique Features from Blueprint) -->
<section class="section unique-features">
    <div class="container">
        <h2 class="section-title">What Makes TrueVault Different</h2>
        <p class="section-subtitle">Features you won't find in any other VPN</p>
        
        <div class="features-grid-large">
            <?php foreach ($uniqueFeatures as $index => $feature): ?>
            <div class="feature-card-large <?= $index % 2 === 1 ? 'reverse' : '' ?>">
                <div class="feature-content">
                    <div class="feature-icon-large"><?= e($feature['icon']) ?></div>
                    <h2><?= e($feature['title']) ?></h2>
                    <p><?= e($feature['description']) ?></p>
                </div>
                <div class="feature-visual">
                    <div class="feature-graphic"><?= e($feature['icon']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Core VPN Features -->
<section class="section core-features">
    <div class="container">
        <h2 class="section-title">Solid VPN Foundation</h2>
        <p class="section-subtitle">All the security you expect, plus so much more</p>
        
        <div class="features-grid">
            <?php foreach ($coreFeatures as $feature): ?>
            <div class="feature-card">
                <div class="feature-icon"><?= e($feature['icon']) ?></div>
                <h3><?= e($feature['title']) ?></h3>
                <p><?= e($feature['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Comparison Table (FROM DATABASE - TrueVault vs Traditional VPN) -->
<?php if (!empty($featureComparison)): ?>
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
                    <?php foreach ($featureComparison as $row): ?>
                    <tr>
                        <td><?= e($row['feature_name']) ?></td>
                        <td><?= e($row['traditional_vpn']) ?></td>
                        <td class="highlight"><?= e($row['truevault']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Additional Features -->
<?php if (!empty($extraFeatures)): ?>
<section class="section extra-features">
    <div class="container">
        <h2 class="section-title">Even More Features</h2>
        
        <div class="features-grid">
            <?php foreach ($extraFeatures as $feature): ?>
            <div class="feature-card">
                <div class="feature-icon"><?= e($feature['icon']) ?></div>
                <h3><?= e($feature['title']) ?></h3>
                <p><?= e($feature['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-box">
            <h2>Ready to Take Control?</h2>
            <p>Start your <?= e($trialDays) ?>-day free trial today. No credit card required.</p>
            <a href="/register.php" class="btn btn-primary btn-lg"><?= e(getSetting('cta_primary_text', 'Start Free Trial')) ?></a>
        </div>
    </div>
</section>

<style>
/* Page Hero */
.page-hero {
    padding: 80px 0 40px; text-align: center;
    background: linear-gradient(135deg, var(--background), var(--card-bg));
}

.page-hero h1 {
    font-size: 3rem; margin-bottom: 15px;
    background: linear-gradient(90deg, var(--text-primary), var(--primary));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

.page-hero p { color: var(--text-secondary); font-size: 1.2rem; max-width: 600px; margin: 0 auto; }

/* Sections */
.section { padding: 80px 0; }
.section-title { text-align: center; font-size: 2.5rem; margin-bottom: 15px; }
.section-subtitle { text-align: center; color: var(--text-secondary); margin-bottom: 50px; }

/* Large Feature Cards */
.unique-features { background: var(--card-bg); }
.features-grid-large { display: flex; flex-direction: column; gap: 80px; }

.feature-card-large {
    display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;
}

.feature-card-large.reverse { direction: rtl; }
.feature-card-large.reverse > * { direction: ltr; }

.feature-icon-large { font-size: 4rem; margin-bottom: 20px; }
.feature-content h2 { font-size: 2rem; color: var(--primary); margin-bottom: 15px; }
.feature-content p { color: var(--text-secondary); font-size: 1.1rem; line-height: 1.7; }

.feature-visual { display: flex; align-items: center; justify-content: center; }

.feature-graphic {
    width: 250px; height: 250px;
    background: linear-gradient(135deg, rgba(0, 217, 255, 0.1), rgba(0, 255, 136, 0.1));
    border: 1px solid rgba(0, 217, 255, 0.3); border-radius: 50%;
    display: flex; align-items: center; justify-content: center; font-size: 6rem;
}

/* Standard Feature Cards */
.features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }

.feature-card {
    background: var(--card-bg); border: 1px solid rgba(255,255,255,0.05);
    border-radius: 16px; padding: 30px; transition: all 0.3s;
}

.feature-card:hover {
    transform: translateY(-5px); border-color: var(--primary);
    box-shadow: 0 20px 40px rgba(0, 217, 255, 0.1);
}

.feature-icon { font-size: 3rem; margin-bottom: 20px; }
.feature-card h3 { font-size: 1.3rem; color: var(--primary); margin-bottom: 12px; }
.feature-card p { color: var(--text-secondary); line-height: 1.6; }

/* Comparison Section */
.comparison-section { padding: 80px 0; }
.comparison-table-wrapper { overflow-x: auto; max-width: 800px; margin: 0 auto; }
.comparison-table { width: 100%; border-collapse: collapse; }

.comparison-table th,
.comparison-table td {
    padding: 15px 25px; text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.comparison-table th { background: var(--card-bg); font-weight: 600; }
.comparison-table th:first-child, .comparison-table td:first-child { text-align: left; }

.comparison-table .highlight { background: rgba(0, 217, 255, 0.1); color: var(--primary); font-weight: 600; }
.comparison-table th.highlight { background: linear-gradient(135deg, rgba(0, 217, 255, 0.2), rgba(0, 255, 136, 0.2)); }

/* CTA Section */
.cta-section {
    padding: 80px 0;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
}

.cta-box { text-align: center; color: var(--background); }
.cta-box h2 { font-size: 2.5rem; margin-bottom: 15px; }
.cta-box p { font-size: 1.2rem; margin-bottom: 30px; opacity: 0.9; }
.cta-box .btn-primary { background: var(--background); color: var(--text-primary); }
.btn-lg { padding: 16px 32px; font-size: 1.1rem; }

/* Responsive */
@media (max-width: 900px) {
    .feature-card-large { grid-template-columns: 1fr; gap: 30px; }
    .feature-card-large.reverse { direction: ltr; }
    .feature-visual { order: -1; }
    .feature-graphic { width: 180px; height: 180px; font-size: 4rem; }
    .features-grid { grid-template-columns: repeat(2, 1fr); }
    .page-hero h1 { font-size: 2.2rem; }
}

@media (max-width: 600px) {
    .features-grid { grid-template-columns: 1fr; }
}
</style>

<?php include __DIR__ . '/templates/footer.php'; ?>
