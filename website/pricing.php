<?php
/**
 * TrueVault VPN - Pricing Page
 * Part 12 - Database-driven pricing
 * ALL content from database - NO hardcoding
 * Comparison table from plan_comparison table (SECTION 25 data)
 */

require_once __DIR__ . '/includes/content-functions.php';

$pageData = getPage('pricing');
$plans = getPricingPlans();
$comparison = getPlanComparison(); // FROM SECTION 25
$faqs = getFAQs('billing');
$refundDays = getSetting('feature_refund_days', '30');

include __DIR__ . '/templates/header.php';
?>

<!-- Hero Section -->
<section class="page-hero">
    <div class="container">
        <h1><?= e($pageData['hero_title'] ?? 'Choose Your Plan') ?></h1>
        <p><?= e($pageData['hero_subtitle'] ?? 'Simple, transparent pricing with no hidden fees') ?></p>
    </div>
</section>

<!-- Billing Toggle -->
<section class="billing-toggle-section">
    <div class="container">
        <div class="billing-toggle">
            <span class="toggle-label active" data-period="monthly">Monthly</span>
            <label class="toggle-switch">
                <input type="checkbox" id="billingToggle">
                <span class="toggle-slider"></span>
            </label>
            <span class="toggle-label" data-period="yearly">
                Yearly
                <span class="save-badge">Save 17%</span>
            </span>
        </div>
        
        <!-- Currency Toggle -->
        <div class="currency-toggle">
            <button class="currency-btn active" data-currency="USD">USD ($)</button>
            <button class="currency-btn" data-currency="CAD">CAD (C$)</button>
        </div>
    </div>
</section>

<!-- Pricing Cards (from database) -->
<section class="pricing-section">
    <div class="container">
        <div class="pricing-grid">
            <?php foreach ($plans as $plan): ?>
            <div class="pricing-card <?= $plan['is_popular'] ? 'popular' : '' ?>" data-plan="<?= e($plan['plan_key']) ?>">
                <?php if ($plan['is_popular']): ?>
                <div class="popular-badge">Most Popular</div>
                <?php endif; ?>
                
                <h3 class="plan-name"><?= e($plan['plan_name']) ?></h3>
                
                <div class="plan-price">
                    <!-- USD Monthly -->
                    <span class="price-display price-usd price-monthly">
                        <span class="currency">$</span>
                        <span class="amount"><?= number_format($plan['price_monthly_usd'], 2) ?></span>
                        <span class="period">/month</span>
                    </span>
                    <!-- USD Yearly (per month) -->
                    <span class="price-display price-usd price-yearly" style="display: none;">
                        <span class="currency">$</span>
                        <span class="amount"><?= number_format($plan['price_yearly_usd'] / 12, 2) ?></span>
                        <span class="period">/month</span>
                        <span class="billed">billed $<?= number_format($plan['price_yearly_usd'], 2) ?>/year</span>
                    </span>
                    <!-- CAD Monthly -->
                    <span class="price-display price-cad price-monthly" style="display: none;">
                        <span class="currency">C$</span>
                        <span class="amount"><?= number_format($plan['price_monthly_cad'], 2) ?></span>
                        <span class="period">/month</span>
                    </span>
                    <!-- CAD Yearly (per month) -->
                    <span class="price-display price-cad price-yearly" style="display: none;">
                        <span class="currency">C$</span>
                        <span class="amount"><?= number_format($plan['price_yearly_cad'] / 12, 2) ?></span>
                        <span class="period">/month</span>
                        <span class="billed">billed C$<?= number_format($plan['price_yearly_cad'], 2) ?>/year</span>
                    </span>
                </div>
                
                <p class="plan-desc"><?= e($plan['description']) ?></p>
                
                <ul class="plan-features">
                    <?php foreach ($plan['features'] as $feature): ?>
                    <li><span class="check">✓</span> <?= e($feature) ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <a href="<?= e($plan['cta_url']) ?>" class="btn <?= $plan['is_popular'] ? 'btn-primary' : 'btn-secondary' ?> btn-block">
                    <?= e($plan['cta_text']) ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Comparison Table (FROM DATABASE - SECTION 25 DATA) -->
<?php if (!empty($comparison)): ?>
<section class="comparison-section">
    <div class="container">
        <h2 class="section-title">Compare Plans</h2>
        
        <div class="comparison-table-wrapper">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <?php foreach ($plans as $plan): ?>
                        <th class="<?= $plan['is_popular'] ? 'highlight' : '' ?>"><?= e($plan['plan_name']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comparison as $row): ?>
                    <tr>
                        <td><?= e($row['feature_name']) ?></td>
                        <td><?= e($row['personal_value']) ?></td>
                        <td class="highlight"><?= e($row['family_value']) ?></td>
                        <td><?= e($row['dedicated_value']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- FAQ Section (from database) -->
<?php if (!empty($faqs)): ?>
<section class="section faq-section">
    <div class="container">
        <h2 class="section-title">Billing FAQ</h2>
        
        <div class="faq-list">
            <?php foreach ($faqs as $faq): ?>
            <div class="faq-item">
                <button class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                    <?= e($faq['question']) ?>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p><?= e($faq['answer']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Guarantee Section (uses database setting) -->
<section class="guarantee-section">
    <div class="container">
        <div class="guarantee-box">
            <div class="guarantee-icon">🛡️</div>
            <h2><?= e($refundDays) ?>-Day Money-Back Guarantee</h2>
            <p>Try TrueVault VPN risk-free. If you're not completely satisfied within <?= e($refundDays) ?> days, we'll refund 100% of your payment. No questions asked.</p>
            <a href="/refund.php" class="btn btn-secondary">Learn About Our Refund Policy</a>
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

.page-hero p { color: var(--text-secondary); font-size: 1.2rem; }

/* Billing Toggle */
.billing-toggle-section { padding: 30px 0; }

.billing-toggle {
    display: flex; align-items: center; justify-content: center; gap: 15px;
    margin-bottom: 20px;
}

.toggle-label {
    color: var(--text-secondary); cursor: pointer; transition: all 0.3s;
    display: flex; align-items: center; gap: 8px;
}

.toggle-label.active { color: var(--text-primary); font-weight: 600; }

.save-badge {
    background: var(--secondary); color: var(--background);
    padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;
}

.toggle-switch { position: relative; width: 50px; height: 26px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }

.toggle-slider {
    position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
    background: var(--card-bg); border: 1px solid rgba(255,255,255,0.2);
    border-radius: 26px; transition: 0.3s;
}

.toggle-slider::before {
    position: absolute; content: ""; height: 20px; width: 20px;
    left: 3px; bottom: 2px; background: var(--primary);
    border-radius: 50%; transition: 0.3s;
}

.toggle-switch input:checked + .toggle-slider::before { transform: translateX(23px); }

/* Currency Toggle */
.currency-toggle { display: flex; justify-content: center; gap: 10px; }

.currency-btn {
    padding: 8px 16px; background: var(--card-bg);
    border: 1px solid rgba(255,255,255,0.2); border-radius: 8px;
    color: var(--text-secondary); cursor: pointer; transition: all 0.3s;
}

.currency-btn.active {
    background: var(--primary); color: var(--background);
    border-color: var(--primary);
}

/* Pricing Section */
.pricing-section { padding: 40px 0 80px; }

.pricing-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;
    max-width: 1100px; margin: 0 auto;
}

.pricing-card {
    background: var(--card-bg); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 20px; padding: 35px; position: relative; transition: all 0.3s;
}

.pricing-card:hover { border-color: var(--primary); }

.pricing-card.popular {
    border-color: var(--primary); transform: scale(1.05);
    box-shadow: 0 20px 60px rgba(0, 217, 255, 0.2);
}

.popular-badge {
    position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    color: var(--background); padding: 6px 20px; border-radius: 20px;
    font-size: 0.85rem; font-weight: 600;
}

.plan-name { font-size: 1.5rem; margin-bottom: 20px; text-align: center; }

.plan-price { text-align: center; margin-bottom: 20px; min-height: 80px; }

.price-display { display: block; }
.plan-price .currency { font-size: 1.5rem; vertical-align: top; color: var(--primary); }
.plan-price .amount { font-size: 3.5rem; font-weight: 700; color: var(--primary); }
.plan-price .period { color: var(--text-secondary); }
.plan-price .billed { display: block; font-size: 0.85rem; color: var(--text-secondary); margin-top: 5px; }

.plan-desc {
    text-align: center; color: var(--text-secondary); margin-bottom: 25px;
    padding-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1);
}

.plan-features { list-style: none; margin-bottom: 30px; }

.plan-features li {
    padding: 10px 0; color: var(--text-secondary);
    display: flex; align-items: center; gap: 10px;
}

.plan-features .check { color: var(--secondary); font-weight: bold; }
.btn-block { display: block; width: 100%; text-align: center; }

/* Comparison Table */
.comparison-section { padding: 80px 0; background: var(--card-bg); }
.comparison-table-wrapper { overflow-x: auto; }

.comparison-table { width: 100%; border-collapse: collapse; min-width: 600px; }

.comparison-table th,
.comparison-table td {
    padding: 15px 20px; text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.comparison-table th { background: var(--background); font-weight: 600; color: var(--primary); }
.comparison-table th:first-child,
.comparison-table td:first-child { text-align: left; }
.comparison-table tr:hover { background: rgba(255,255,255,0.02); }
.comparison-table .highlight { background: rgba(0, 217, 255, 0.1); }
.comparison-table th.highlight { background: linear-gradient(135deg, rgba(0, 217, 255, 0.2), rgba(0, 255, 136, 0.2)); }

/* FAQ Section */
.section { padding: 80px 0; }
.section-title { text-align: center; font-size: 2.5rem; margin-bottom: 40px; }
.faq-list { max-width: 800px; margin: 0 auto; }

.faq-item {
    background: var(--card-bg); border-radius: 12px; margin-bottom: 15px;
    overflow: hidden; border: 1px solid rgba(255,255,255,0.05);
}

.faq-question {
    width: 100%; padding: 20px 25px; background: none; border: none;
    color: var(--text-primary); font-size: 1.1rem; font-weight: 600;
    text-align: left; cursor: pointer;
    display: flex; justify-content: space-between; align-items: center;
}

.faq-icon { font-size: 1.5rem; color: var(--primary); transition: transform 0.3s; }
.faq-item.open .faq-icon { transform: rotate(45deg); }
.faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s; }
.faq-item.open .faq-answer { max-height: 300px; }
.faq-answer p { padding: 0 25px 20px; color: var(--text-secondary); line-height: 1.6; }

/* Guarantee Section */
.guarantee-section { padding: 80px 0; }

.guarantee-box {
    background: linear-gradient(135deg, rgba(0, 217, 255, 0.1), rgba(0, 255, 136, 0.1));
    border: 1px solid rgba(0, 217, 255, 0.3); border-radius: 20px;
    padding: 50px; text-align: center; max-width: 700px; margin: 0 auto;
}

.guarantee-icon { font-size: 4rem; margin-bottom: 20px; }
.guarantee-box h2 { font-size: 2rem; margin-bottom: 15px; color: var(--primary); }
.guarantee-box p { color: var(--text-secondary); margin-bottom: 25px; line-height: 1.6; }

/* Responsive */
@media (max-width: 900px) {
    .pricing-grid { grid-template-columns: 1fr; max-width: 400px; }
    .pricing-card.popular { transform: none; }
    .page-hero h1 { font-size: 2.2rem; }
}
</style>

<script>
// Billing period toggle
document.getElementById('billingToggle').addEventListener('change', function() {
    const isYearly = this.checked;
    
    document.querySelectorAll('.toggle-label').forEach(label => {
        label.classList.toggle('active', 
            (label.dataset.period === 'yearly' && isYearly) ||
            (label.dataset.period === 'monthly' && !isYearly)
        );
    });
    
    updatePriceDisplay();
});

// Currency toggle
document.querySelectorAll('.currency-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.currency-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        updatePriceDisplay();
    });
});

function updatePriceDisplay() {
    const isYearly = document.getElementById('billingToggle').checked;
    const currency = document.querySelector('.currency-btn.active').dataset.currency;
    
    document.querySelectorAll('.price-display').forEach(el => {
        el.style.display = 'none';
    });
    
    const periodClass = isYearly ? 'price-yearly' : 'price-monthly';
    const currencyClass = currency === 'CAD' ? 'price-cad' : 'price-usd';
    
    document.querySelectorAll(`.${currencyClass}.${periodClass}`).forEach(el => {
        el.style.display = 'block';
    });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
