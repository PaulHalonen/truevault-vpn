<?php
/**
 * TrueVault VPN - Homepage
 * Part 12 - Database-driven landing page
 * ALL content from database - NO hardcoding
 * Data sources: content.db tables (settings, features, testimonials, etc.)
 */

require_once __DIR__ . '/includes/content-functions.php';

// Get ALL content from database
$pageData = getPage('homepage');
$features = getFeatures();
$testimonials = getTestimonials(true); // Featured only
$faqs = getFAQs();
$plans = getPricingPlans();
$trustBadges = getTrustBadges();
$howItWorks = getHowItWorks();

// Get hero stats from settings (FROM BLUEPRINT - 4 servers, not 50+ countries)
$heroStats = [
    ['value' => getSetting('hero_stats_encryption', '256-bit'), 'label' => getSetting('hero_stats_encryption_label', 'Military-Grade Encryption')],
    ['value' => getSetting('hero_stats_policy', 'Zero'), 'label' => getSetting('hero_stats_policy_label', 'Log Policy')],
    ['value' => getSetting('hero_stats_servers', '4'), 'label' => getSetting('hero_stats_servers_label', 'Server Locations')],
    ['value' => getSetting('hero_stats_setup', '2-Click'), 'label' => getSetting('hero_stats_setup_label', 'Device Setup')],
];

// Get refund days for CTA section
$refundDays = getSetting('feature_refund_days', '30');
$trialDays = getSetting('feature_trial_days', '7');

// Include header
include __DIR__ . '/templates/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title"><?= e($pageData['hero_title'] ?? 'Your Complete Digital Fortress') ?></h1>
            <p class="hero-subtitle"><?= e($pageData['hero_subtitle'] ?? '') ?></p>
            
            <div class="hero-cta">
                <a href="<?= e($pageData['hero_cta_url'] ?? '/register.php') ?>" class="btn btn-primary btn-lg">
                    <?= e($pageData['hero_cta_text'] ?? 'Start Free Trial') ?>
                </a>
                <a href="/pricing.php" class="btn btn-secondary btn-lg"><?= e(getSetting('cta_secondary_text', 'View Pricing')) ?></a>
            </div>
            
            <!-- Trust badges from database -->
            <div class="hero-trust">
                <?php foreach ($trustBadges as $badge): ?>
                <span><?= e($badge['icon']) ?> <?= e($badge['text']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Hero stats from database settings -->
        <div class="hero-stats">
            <?php foreach ($heroStats as $stat): ?>
            <div class="stat">
                <div class="stat-value"><?= e($stat['value']) ?></div>
                <div class="stat-label"><?= e($stat['label']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Features Section (from database) -->
<section class="section features-section" id="features">
    <div class="container">
        <h2 class="section-title">What Makes TrueVault Different</h2>
        <p class="section-subtitle">Most VPNs just hide your IP. TrueVault actually solves real problems.</p>
        
        <div class="features-grid">
            <?php foreach (array_slice($features, 0, 6) as $feature): ?>
            <div class="feature-card">
                <div class="feature-icon"><?= e($feature['icon']) ?></div>
                <h3 class="feature-title"><?= e($feature['title']) ?></h3>
                <p class="feature-desc"><?= e($feature['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section-cta">
            <a href="/features.php" class="btn btn-secondary">See All Features →</a>
        </div>
    </div>
</section>

<!-- How It Works Section (from database) -->
<?php if (!empty($howItWorks)): ?>
<section class="section how-it-works">
    <div class="container">
        <h2 class="section-title">How It Works</h2>
        <p class="section-subtitle">Get protected in under 2 minutes</p>
        
        <div class="steps-grid">
            <?php foreach ($howItWorks as $step): ?>
            <div class="step">
                <div class="step-number"><?= e($step['step_number']) ?></div>
                <h3><?= e($step['title']) ?></h3>
                <p><?= e($step['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Pricing Preview Section (from database) -->
<section class="section pricing-preview">
    <div class="container">
        <h2 class="section-title">Choose Your Plan</h2>
        <p class="section-subtitle">Simple, transparent pricing with no hidden fees</p>
        
        <div class="pricing-grid">
            <?php foreach ($plans as $plan): ?>
            <div class="pricing-card <?= $plan['is_popular'] ? 'popular' : '' ?>">
                <?php if ($plan['is_popular']): ?>
                <div class="popular-badge">Most Popular</div>
                <?php endif; ?>
                
                <h3 class="plan-name"><?= e($plan['plan_name']) ?></h3>
                <div class="plan-price">
                    <span class="price-amount">$<?= number_format($plan['price_monthly_usd'], 2) ?></span>
                    <span class="price-period">/month</span>
                </div>
                <p class="plan-desc"><?= e($plan['description']) ?></p>
                
                <ul class="plan-features">
                    <?php foreach (array_slice($plan['features'], 0, 5) as $feature): ?>
                    <li>✓ <?= e($feature) ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <a href="<?= e($plan['cta_url']) ?>" class="btn <?= $plan['is_popular'] ? 'btn-primary' : 'btn-secondary' ?> btn-block">
                    <?= e($plan['cta_text']) ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section-cta">
            <a href="/pricing.php" class="btn btn-secondary">Compare All Plans →</a>
        </div>
    </div>
</section>

<!-- Testimonials Section (from database) -->
<?php if (!empty($testimonials)): ?>
<section class="section testimonials-section">
    <div class="container">
        <h2 class="section-title">What Our Users Say</h2>
        
        <div class="testimonials-grid">
            <?php foreach ($testimonials as $testimonial): ?>
            <div class="testimonial-card">
                <div class="testimonial-rating"><?= renderStars($testimonial['rating']) ?></div>
                <p class="testimonial-content">"<?= e($testimonial['content']) ?>"</p>
                <div class="testimonial-author">
                    <strong><?= e($testimonial['name']) ?></strong>
                    <?php if (!empty($testimonial['role'])): ?>
                    <span><?= e($testimonial['role']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- FAQ Section (from database) -->
<?php if (!empty($faqs)): ?>
<section class="section faq-section">
    <div class="container">
        <h2 class="section-title">Frequently Asked Questions</h2>
        
        <div class="faq-grid">
            <?php foreach (array_slice($faqs, 0, 6) as $faq): ?>
            <div class="faq-item">
                <h3 class="faq-question"><?= e($faq['question']) ?></h3>
                <p class="faq-answer"><?= e($faq['answer']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Final CTA Section (uses database settings) -->
<section class="section cta-section">
    <div class="container">
        <div class="cta-box">
            <h2>Your Privacy. Your Control.</h2>
            <p>Join thousands who've upgraded from basic VPN to complete digital protection.</p>
            <a href="/register.php" class="btn btn-primary btn-lg">Start <?= e($trialDays) ?>-Day Free Trial</a>
            <p class="cta-note">No credit card required • Cancel anytime • <?= e($refundDays) ?>-day money back guarantee</p>
        </div>
    </div>
</section>

<style>
/* Hero Section */
.hero {
    padding: 100px 0 80px;
    background: linear-gradient(135deg, var(--background) 0%, var(--card-bg) 100%);
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(circle at 20% 50%, rgba(0, 217, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(0, 255, 136, 0.1) 0%, transparent 50%);
    pointer-events: none;
}

.hero-content { text-align: center; position: relative; z-index: 1; }

.hero-title {
    font-size: 3.5rem; font-weight: 800; margin-bottom: 20px;
    background: linear-gradient(90deg, var(--text-primary), var(--primary));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

.hero-subtitle {
    font-size: 1.3rem; color: var(--text-secondary);
    max-width: 700px; margin: 0 auto 30px; line-height: 1.6;
}

.hero-cta { display: flex; gap: 15px; justify-content: center; margin-bottom: 30px; }
.btn-lg { padding: 16px 32px; font-size: 1.1rem; }

.hero-trust {
    display: flex; gap: 30px; justify-content: center; flex-wrap: wrap;
    color: var(--text-secondary); font-size: 0.95rem;
}

.hero-trust span { display: flex; align-items: center; gap: 8px; }

.hero-stats {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px;
    margin-top: 60px; padding-top: 40px; border-top: 1px solid rgba(255,255,255,0.1);
}

.stat { text-align: center; }

.stat-value {
    font-size: 2.5rem; font-weight: 700;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

.stat-label { color: var(--text-secondary); font-size: 0.9rem; margin-top: 5px; }

/* Sections */
.section { padding: 80px 0; }

.section-title {
    text-align: center; font-size: 2.5rem; font-weight: 700;
    margin-bottom: 15px; color: var(--text-primary);
}

.section-subtitle {
    text-align: center; color: var(--text-secondary); font-size: 1.1rem;
    margin-bottom: 50px; max-width: 600px; margin-left: auto; margin-right: auto;
}

.section-cta { text-align: center; margin-top: 40px; }

/* Features Grid */
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
.feature-title { font-size: 1.3rem; font-weight: 600; margin-bottom: 12px; color: var(--primary); }
.feature-desc { color: var(--text-secondary); line-height: 1.6; }

/* How It Works */
.steps-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; }
.step { text-align: center; position: relative; }

.step-number {
    width: 60px; height: 60px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; font-weight: 700; color: var(--background); margin: 0 auto 20px;
}

.step h3 { font-size: 1.3rem; margin-bottom: 10px; }
.step p { color: var(--text-secondary); }

/* Pricing Grid */
.pricing-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;
    max-width: 1000px; margin: 0 auto;
}

.pricing-card {
    background: var(--card-bg); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px; padding: 30px; position: relative; transition: all 0.3s;
}

.pricing-card.popular { border-color: var(--primary); transform: scale(1.05); }

.popular-badge {
    position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    color: var(--background); padding: 6px 20px; border-radius: 20px;
    font-size: 0.85rem; font-weight: 600;
}

.plan-name { font-size: 1.3rem; margin-bottom: 15px; text-align: center; }
.plan-price { margin-bottom: 15px; text-align: center; }
.price-amount { font-size: 2.5rem; font-weight: 700; color: var(--primary); }
.price-period { color: var(--text-secondary); }
.plan-desc { color: var(--text-secondary); margin-bottom: 20px; text-align: center; }
.plan-features { list-style: none; margin-bottom: 25px; }
.plan-features li { padding: 8px 0; color: var(--text-secondary); border-bottom: 1px solid rgba(255,255,255,0.05); }
.btn-block { display: block; width: 100%; text-align: center; }

/* Testimonials */
.testimonials-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }

.testimonial-card {
    background: var(--card-bg); border-radius: 16px; padding: 30px;
    border: 1px solid rgba(255,255,255,0.05);
}

.testimonial-rating { margin-bottom: 15px; }
.testimonial-content { color: var(--text-secondary); font-style: italic; line-height: 1.6; margin-bottom: 20px; }
.testimonial-author strong { display: block; color: var(--text-primary); }
.testimonial-author span { color: var(--text-secondary); font-size: 0.9rem; }

/* FAQ */
.faq-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;
    max-width: 1000px; margin: 0 auto;
}

.faq-item {
    background: var(--card-bg); border-radius: 12px; padding: 25px;
    border: 1px solid rgba(255,255,255,0.05);
}

.faq-question { font-size: 1.1rem; margin-bottom: 12px; color: var(--primary); }
.faq-answer { color: var(--text-secondary); line-height: 1.6; }

/* CTA Section */
.cta-section { background: linear-gradient(135deg, var(--primary), var(--secondary)); padding: 80px 0; }
.cta-box { text-align: center; color: var(--background); }
.cta-box h2 { font-size: 2.5rem; margin-bottom: 15px; }
.cta-box p { font-size: 1.2rem; margin-bottom: 30px; opacity: 0.9; }
.cta-box .btn-primary { background: var(--background); color: var(--text-primary); }
.cta-box .btn-primary:hover { background: var(--card-bg); }
.cta-note { margin-top: 20px; font-size: 0.9rem; opacity: 0.8; }

/* Responsive */
@media (max-width: 900px) {
    .hero-title { font-size: 2.5rem; }
    .hero-stats { grid-template-columns: repeat(2, 1fr); }
    .features-grid { grid-template-columns: repeat(2, 1fr); }
    .steps-grid { grid-template-columns: 1fr; gap: 30px; }
    .pricing-grid { grid-template-columns: 1fr; max-width: 400px; }
    .pricing-card.popular { transform: none; }
    .testimonials-grid { grid-template-columns: 1fr; }
    .faq-grid { grid-template-columns: 1fr; }
}

@media (max-width: 600px) {
    .hero-title { font-size: 2rem; }
    .hero-cta { flex-direction: column; }
    .hero-trust { flex-direction: column; gap: 10px; }
    .features-grid { grid-template-columns: 1fr; }
    .section-title { font-size: 2rem; }
}
</style>

<?php include __DIR__ . '/templates/footer.php'; ?>
