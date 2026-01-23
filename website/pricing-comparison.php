<?php
/**
 * TrueVault VPN - Pricing Comparison Page
 * ALL CONTENT FROM DATABASE - NO HARDCODING
 * 
 * Blueprint: SECTION_26_PRICING_COMPARISON.md
 * Checklist: MASTER_CHECKLIST_PART12B.md
 * 
 * Data Sources:
 * - pages table: Hero title, subtitle, meta tags
 * - page_sections table: All section text content
 * - competitors table: Competitor pricing data
 * - competitor_comparison table: Comparison table rows
 * - unique_features table: Features only TrueVault offers
 * - use_cases table: Who should choose what
 * - honest_assessment table: Pros/cons list
 * - trust_badges table: CTA badges
 * - settings table: Prices, site name, CTA text
 */

require_once __DIR__ . '/includes/content-functions.php';

// Get all data from database
$page = getPage('pricing-comparison');
$sections = getPageSections('pricing-comparison');
$competitors = getCompetitors();
$comparisonRows = getCompetitorComparison();
$uniqueFeatures = getUniqueFeatures();
$useCases = getUseCases();
$advantages = getHonestAssessment('advantage');
$limitations = getHonestAssessment('limitation');
$trustBadges = getTrustBadgesByPage('pricing-comparison');

// Get settings
$siteName = getSetting('site_title', 'TrueVault VPN');
$ctaPrimary = getSetting('cta_primary_text', 'Start Free Trial');
$refundDays = getSetting('feature_refund_days', '30');

// Get competitor data as indexed array for easy access
$competitorsByKey = [];
foreach ($competitors as $c) {
    $competitorsByKey[$c['competitor_key']] = $c;
}

// Variables for template replacement
$templateVars = [
    'days' => $refundDays,
    'price' => $competitorsByKey['truevault']['real_monthly_cost'] ?? '39.97',
    'site_name' => $siteName
];

include __DIR__ . '/templates/header.php';
?>

<style>
<?php include __DIR__ . '/includes/theme-css.php'; ?>

/* Comparison Page Styles */
.comparison-hero {
    background: linear-gradient(135deg, var(--background) 0%, var(--card-bg) 100%);
    padding: 80px 20px; text-align: center; position: relative; overflow: hidden;
}
.comparison-hero::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(circle at 50% 50%, rgba(0, 217, 255, 0.1) 0%, transparent 50%);
}
.comparison-hero h1 { font-size: 2.8rem; color: #ff4757; margin-bottom: 15px; position: relative; }
.comparison-hero .subtitle { font-size: 1.2rem; color: var(--text-secondary); max-width: 700px; margin: 0 auto 30px; position: relative; }

.price-highlight {
    background: linear-gradient(135deg, #1a1a3a 0%, #2a2a4a 100%);
    border: 2px solid var(--primary); border-radius: 20px; padding: 30px 50px;
    display: inline-block; margin: 20px 0; position: relative;
}
.price-highlight .brand { color: var(--primary); font-size: 1.1rem; margin-bottom: 5px; }
.price-highlight .amount { font-size: 4rem; font-weight: 700; color: #fff; }
.price-highlight .period { color: var(--text-secondary); font-size: 1.2rem; }
.price-highlight .badge {
    background: var(--secondary); color: #000; padding: 8px 20px; border-radius: 20px;
    font-size: 0.9rem; font-weight: 600; display: inline-block; margin-top: 15px;
}

.section { padding: 60px 20px; }
.section-dark { background: var(--background); }
.section-light { background: var(--card-bg); }
.section h2 { text-align: center; font-size: 2rem; margin-bottom: 15px; }
.section .subtitle { text-align: center; color: var(--text-secondary); margin-bottom: 40px; }

/* Trap Section */
.trap-section h2 { color: #ff4757; text-align: left; display: flex; align-items: center; gap: 10px; }
.trap-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; max-width: 1000px; margin: 30px auto 0; }
.trap-card { background: var(--card-bg); border-radius: 15px; padding: 30px; }
.trap-card.competitor { border: 2px solid #ff4757; }
.trap-card.truevault { border: 2px solid var(--secondary); }
.trap-card h3 { font-size: 1.3rem; margin-bottom: 15px; }
.trap-card.competitor h3 { color: #ff4757; }
.trap-card.truevault h3 { color: var(--secondary); }
.calculation {
    font-family: monospace; background: var(--background); padding: 15px;
    border-radius: 8px; margin: 15px 0; font-size: 1rem; line-height: 1.8; white-space: pre-line;
}
.calculation .highlight { color: var(--primary); font-weight: bold; }

/* Comparison Table */
.comparison-table-wrapper { overflow-x: auto; max-width: 1200px; margin: 0 auto; }
.comparison-table { width: 100%; border-collapse: collapse; background: var(--card-bg); border-radius: 15px; overflow: hidden; }
.comparison-table th, .comparison-table td { padding: 18px 15px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
.comparison-table th { background: var(--background); font-weight: 600; font-size: 1rem; }
.comparison-table th:first-child, .comparison-table td:first-child { text-align: left; padding-left: 25px; }
.comparison-table .truevault-col { background: rgba(0, 217, 255, 0.1); border-left: 3px solid var(--primary); border-right: 3px solid var(--primary); }
.comparison-table th.truevault-col { background: var(--primary); color: #000; }
.comparison-table .check { color: var(--secondary); }
.comparison-table .cross { color: #ff4757; }
.comparison-table .price { font-size: 1.3rem; font-weight: 700; }
.comparison-table .price.best { color: var(--secondary); }
.comparison-table .price.bad { color: #ff4757; }

/* Cost Cards */
.cost-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; max-width: 1100px; margin: 0 auto; }
.cost-card { background: var(--card-bg); border-radius: 15px; padding: 30px; text-align: center; border: 2px solid transparent; }
.cost-card.truevault { border-color: var(--primary); background: linear-gradient(135deg, #1a2a3a 0%, var(--card-bg) 100%); }
.cost-card .brand-name { font-size: 1.2rem; font-weight: 600; margin-bottom: 5px; }
.cost-card.truevault .brand-name { color: var(--primary); }
.cost-card .brand-desc { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 20px; }
.cost-card .price { font-size: 3rem; font-weight: 700; color: #fff; }
.cost-card.truevault .price { color: var(--secondary); }
.cost-card .price-note { color: var(--text-secondary); font-size: 0.9rem; margin-top: 5px; }
.cost-card .calculation { font-size: 0.85rem; color: var(--text-secondary); margin-top: 15px; background: none; padding: 0; }

/* Unique Features */
.unique-features { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; max-width: 1100px; margin: 0 auto; }
.unique-feature { background: var(--card-bg); border-radius: 15px; padding: 30px; text-align: center; border: 1px solid rgba(255,255,255,0.1); }
.unique-feature .icon { font-size: 3rem; margin-bottom: 15px; }
.unique-feature h3 { font-size: 1.2rem; color: var(--primary); margin-bottom: 10px; }
.unique-feature p { color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; }

/* Use Cases */
.choose-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; max-width: 1200px; margin: 0 auto; }
.choose-card { background: var(--card-bg); border-radius: 15px; padding: 25px; border-left: 4px solid var(--primary); }
.choose-card h3 { display: flex; align-items: center; gap: 10px; font-size: 1.1rem; margin-bottom: 15px; }
.choose-card .recommend { color: var(--secondary); font-size: 0.9rem; font-weight: 600; }
.choose-card p { color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; }

/* Honest Assessment */
.honest-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; max-width: 1000px; margin: 0 auto; }
.honest-card { background: var(--card-bg); border-radius: 15px; padding: 30px; }
.honest-card.advantages { border-top: 4px solid var(--secondary); }
.honest-card.limitations { border-top: 4px solid #ff9f43; }
.honest-card h3 { font-size: 1.2rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.honest-card.advantages h3 { color: var(--secondary); }
.honest-card.limitations h3 { color: #ff9f43; }
.honest-card ul { list-style: none; padding: 0; }
.honest-card li { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 10px; color: var(--text-secondary); }
.honest-card li:last-child { border-bottom: none; }

/* CTA Section */
.cta-section { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 80px 20px; text-align: center; }
.cta-section h2 { font-size: 2.2rem; color: #000; margin-bottom: 20px; }
.cta-section .price-reminder { font-size: 1.3rem; color: #000; opacity: 0.8; margin-bottom: 30px; }
.cta-section .btn { background: #000; color: #fff; padding: 18px 40px; font-size: 1.2rem; border-radius: 50px; text-decoration: none; display: inline-block; font-weight: 600; }
.cta-section .btn:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
.cta-section .trust-badges { display: flex; justify-content: center; gap: 30px; margin-top: 30px; flex-wrap: wrap; }
.cta-section .trust-badge { color: #000; font-weight: 500; display: flex; align-items: center; gap: 8px; }

@media (max-width: 768px) {
    .comparison-hero h1 { font-size: 2rem; }
    .price-highlight .amount { font-size: 3rem; }
    .trap-grid, .honest-grid { grid-template-columns: 1fr; }
}
</style>

<!-- HERO (from pages table) -->
<section class="comparison-hero">
    <div class="container">
        <h1><?= e($page['hero_title'] ?? 'Business VPN Pricing: The Hidden Costs') ?></h1>
        <p class="subtitle"><?= e($page['hero_subtitle'] ?? '') ?></p>
        
        <?php $tv = $competitorsByKey['truevault'] ?? []; ?>
        <div class="price-highlight">
            <div class="brand"><?= e($siteName) ?> Dedicated Server</div>
            <div class="amount">$<?= number_format($tv['real_monthly_cost'] ?? 39.97, 2) ?></div>
            <div class="period">/month</div>
            <div class="badge"><?= e($sections['badge_no_minimum']['title'] ?? '✓ No minimum users required') ?></div>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="<?= e($page['hero_cta_url'] ?? '/register.php') ?>" class="btn btn-primary"><?= e($ctaPrimary) ?></a>
            <a href="#comparison" class="btn btn-secondary" style="margin-left: 15px;">See Comparison</a>
        </div>
    </div>
</section>

<!-- THE "$7/USER" TRAP (from page_sections) -->
<section class="section section-light trap-section">
    <div class="container">
        <h2><?= e($sections['trap_title']['icon'] ?? '⚠️') ?> <?= e($sections['trap_title']['title'] ?? 'The "$7/user" Trap') ?></h2>
        <p style="color: var(--text-secondary); max-width: 800px;"><?= e($sections['trap_intro']['content'] ?? '') ?></p>
        
        <div class="trap-grid">
            <div class="trap-card competitor">
                <h3><?= e($sections['trap_competitor_title']['icon'] ?? '❌') ?> <?= e($sections['trap_competitor_title']['title'] ?? 'Competitor Pricing') ?></h3>
                <p style="color: var(--text-secondary);"><?= e($sections['trap_competitor_title']['subtitle'] ?? '') ?></p>
                <div class="calculation"><?= nl2br(e($sections['trap_competitor_calc']['content'] ?? '')) ?></div>
                <p style="color: var(--text-secondary); font-size: 0.9rem;"><?= e($sections['trap_competitor_note']['content'] ?? '') ?></p>
            </div>
            
            <div class="trap-card truevault">
                <h3><?= e($sections['trap_truevault_title']['icon'] ?? '✅') ?> <?= e($sections['trap_truevault_title']['title'] ?? $siteName . ' Pricing') ?></h3>
                <p style="color: var(--text-secondary);"><?= e($sections['trap_truevault_title']['subtitle'] ?? '') ?></p>
                <div class="calculation"><?= nl2br(e($sections['trap_truevault_calc']['content'] ?? '')) ?></div>
                <p style="color: var(--text-secondary); font-size: 0.9rem;"><?= e($sections['trap_truevault_note']['content'] ?? '') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- COMPARISON TABLE (from competitor_comparison table) -->
<section class="section section-dark" id="comparison">
    <div class="container">
        <h2><?= e($sections['comparison_title']['title'] ?? 'True Cost Comparison') ?></h2>
        <p class="subtitle"><?= e($sections['comparison_title']['subtitle'] ?? '') ?></p>
        
        <div class="comparison-table-wrapper">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th class="truevault-col"><?= e($siteName) ?></th>
                        <th>GoodAccess</th>
                        <th>NordLayer</th>
                        <th>Perimeter 81</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comparisonRows as $row): ?>
                    <tr>
                        <td><?= e($row['feature_name']) ?></td>
                        <td class="truevault-col">
                            <?php 
                            $val = $row['truevault_value'];
                            if ($row['feature_name'] === 'Real Monthly Cost (1 Admin)') {
                                echo '<span class="price best">' . e($val) . '</span>';
                            } elseif (strpos($val, '✓') !== false) {
                                echo '<span class="check">' . e($val) . '</span>';
                            } else {
                                echo e($val);
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            $val = $row['goodaccess_value'];
                            if ($row['feature_name'] === 'Real Monthly Cost (1 Admin)') {
                                echo '<span class="price bad">' . e($val) . '</span>';
                            } elseif (strpos($val, '✗') !== false) {
                                echo '<span class="cross">' . e($val) . '</span>';
                            } else {
                                echo e($val);
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            $val = $row['nordlayer_value'];
                            if ($row['feature_name'] === 'Real Monthly Cost (1 Admin)') {
                                echo '<span class="price bad">' . e($val) . '</span>';
                            } elseif (strpos($val, '✗') !== false) {
                                echo '<span class="cross">' . e($val) . '</span>';
                            } else {
                                echo e($val);
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            $val = $row['perimeter81_value'];
                            if ($row['feature_name'] === 'Real Monthly Cost (1 Admin)') {
                                echo '<span class="price bad">' . e($val) . '</span>';
                            } elseif (strpos($val, '✗') !== false) {
                                echo '<span class="cross">' . e($val) . '</span>';
                            } else {
                                echo e($val);
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- THE REAL MONTHLY COST (from competitors table) -->
<section class="section section-light">
    <div class="container">
        <h2><?= e($sections['realcost_title']['title'] ?? 'The Real Monthly Cost') ?></h2>
        <p class="subtitle"><?= e($sections['realcost_title']['subtitle'] ?? '') ?></p>
        
        <div class="cost-cards">
            <?php foreach ($competitors as $comp): ?>
            <div class="cost-card <?= $comp['competitor_key'] === 'truevault' ? 'truevault' : '' ?>">
                <div class="brand-name"><?= e($comp['name']) ?></div>
                <div class="brand-desc"><?= e($comp['advertised_price']) ?></div>
                <div class="price">$<?= number_format($comp['real_monthly_cost'], 2) ?></div>
                <div class="price-note">/month</div>
                <div class="calculation"><?= nl2br(e($comp['price_calculation'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FEATURES ONLY TRUEVAULT OFFERS (from unique_features table) -->
<section class="section section-dark">
    <div class="container">
        <h2><?= e($sections['unique_title']['title'] ?? 'Features Only ' . $siteName . ' Offers') ?></h2>
        <p class="subtitle"><?= e($sections['unique_title']['subtitle'] ?? '') ?></p>
        
        <div class="unique-features">
            <?php foreach ($uniqueFeatures as $feature): ?>
            <div class="unique-feature">
                <div class="icon"><?= e($feature['icon']) ?></div>
                <h3><?= e($feature['title']) ?></h3>
                <p><?= e($feature['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- WHO SHOULD CHOOSE WHAT (from use_cases table) -->
<section class="section section-light">
    <div class="container">
        <h2><?= e($sections['choose_title']['title'] ?? 'Who Should Choose What?') ?></h2>
        
        <div class="choose-grid">
            <?php foreach ($useCases as $useCase): ?>
            <div class="choose-card">
                <h3><?= e($useCase['icon']) ?> <?= e($useCase['title']) ?></h3>
                <p class="recommend">→ <?= e($useCase['recommendation']) ?></p>
                <p><?= e($useCase['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- HONEST ASSESSMENT (from honest_assessment table) -->
<section class="section section-dark">
    <div class="container">
        <h2><?= e($sections['honest_title']['title'] ?? 'Honest Assessment') ?></h2>
        <p class="subtitle"><?= e($sections['honest_title']['subtitle'] ?? '') ?></p>
        
        <div class="honest-grid">
            <div class="honest-card advantages">
                <h3>✅ <?= e($siteName) ?> Advantages</h3>
                <ul>
                    <?php foreach ($advantages as $item): ?>
                    <li><span class="check"><?= e($item['icon']) ?></span> <?= e($item['text']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="honest-card limitations">
                <h3>⚠️ Where Business VPNs Are Better</h3>
                <ul>
                    <?php foreach ($limitations as $item): ?>
                    <li><span style="color: #ff9f43;"><?= e($item['icon']) ?></span> <?= e($item['text']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- FINAL CTA (from page_sections + trust_badges) -->
<section class="cta-section">
    <div class="container">
        <h2><?= e($sections['cta_title']['title'] ?? 'Ready for Dedicated VPN Without Minimum Users?') ?></h2>
        <p class="price-reminder"><?= e(replaceVars($sections['cta_subtitle']['content'] ?? '', $templateVars)) ?></p>
        
        <a href="<?= e($page['hero_cta_url'] ?? '/register.php') ?>" class="btn"><?= e($ctaPrimary) ?></a>
        
        <div class="trust-badges">
            <?php foreach ($trustBadges as $badge): ?>
            <div class="trust-badge"><?= e($badge['icon']) ?> <?= e(replaceVars($badge['text'], $templateVars)) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/templates/footer.php'; ?>
