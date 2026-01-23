<?php
/**
 * TrueVault VPN - About Page
 * Part 12 - Database-driven about page
 * ALL content from database - NO hardcoding
 */

require_once __DIR__ . '/includes/content-functions.php';

$pageData = getPage('about');
$siteName = getSetting('site_name', 'TrueVault VPN');

include __DIR__ . '/templates/header.php';
?>

<!-- Hero Section -->
<section class="page-hero">
    <div class="container">
        <h1><?= e($pageData['hero_title'] ?? 'About TrueVault') ?></h1>
        <p><?= e($pageData['hero_subtitle'] ?? 'We believe everyone deserves true digital privacy.') ?></p>
    </div>
</section>

<!-- Mission Section -->
<section class="about-section">
    <div class="container">
        <div class="about-grid">
            <div class="about-content">
                <h2>Our Mission</h2>
                <p>In an age of mass surveillance and data harvesting, we built <?= e($siteName) ?> to give you back control. Not just privacy — but true digital sovereignty.</p>
                <p>Traditional VPNs hide your IP address. That's a start, but it's not enough. Your digital identity is more than an IP. It's your browser fingerprint, your behavioral patterns, your connection history.</p>
                <p><?= e($siteName) ?> protects all of it.</p>
            </div>
            <div class="about-visual">
                <div class="mission-icon">🛡️</div>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="values-section">
    <div class="container">
        <h2 class="section-title">Our Values</h2>
        
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">🔐</div>
                <h3>Privacy First</h3>
                <p>We don't just protect your privacy — we build systems where even we can't see your data. Your keys, your control.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">🎯</div>
                <h3>Simplicity</h3>
                <p>Advanced security shouldn't require a PhD. We make complex technology accessible with 2-click setup.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">🤝</div>
                <h3>Transparency</h3>
                <p>No hidden fees, no data selling, no compromises. We're upfront about what we do and don't do.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">⚡</div>
                <h3>Innovation</h3>
                <p>We're not satisfied with "good enough." We continuously push the boundaries of what privacy technology can do.</p>
            </div>
        </div>
    </div>
</section>

<!-- Why Different Section -->
<section class="different-section">
    <div class="container">
        <div class="about-grid reverse">
            <div class="about-content">
                <h2>Why We're Different</h2>
                <p>Most VPN companies are in the business of routing traffic. We're in the business of building complete privacy infrastructure.</p>
                
                <ul class="different-list">
                    <li><strong>Personal Certificates:</strong> You own your encryption keys, not us.</li>
                    <li><strong>Smart Identities:</strong> Persistent regional personas that don't trigger suspicion.</li>
                    <li><strong>Mesh Networking:</strong> Connect your family's devices like they're on the same network.</li>
                    <li><strong>Decentralized:</strong> No single point of failure or surveillance.</li>
                </ul>
            </div>
            <div class="about-visual">
                <div class="mission-icon">🌐</div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team-section">
    <div class="container">
        <h2 class="section-title">Built By Privacy Advocates</h2>
        <p class="section-subtitle"><?= e($siteName) ?> was built by people who believe privacy is a fundamental right, not a premium feature.</p>
        
        <div class="team-message">
            <blockquote>
                "We started <?= e($siteName) ?> because we were tired of VPNs that promised privacy but couldn't deliver. We wanted something that gave users real control — where even we couldn't access their data if we wanted to. That's the product we built."
            </blockquote>
            <p class="team-signature">— The <?= e($siteName) ?> Team</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-box">
            <h2>Join Us</h2>
            <p>Take back control of your digital life today.</p>
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
}

/* About Sections */
.about-section,
.different-section {
    padding: 80px 0;
}

.about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.about-grid.reverse {
    direction: rtl;
}

.about-grid.reverse > * {
    direction: ltr;
}

.about-content h2 {
    font-size: 2rem;
    color: var(--primary);
    margin-bottom: 20px;
}

.about-content p {
    color: var(--text-secondary);
    font-size: 1.1rem;
    line-height: 1.8;
    margin-bottom: 15px;
}

.about-visual {
    display: flex;
    align-items: center;
    justify-content: center;
}

.mission-icon {
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

.different-list {
    list-style: none;
    margin-top: 20px;
}

.different-list li {
    padding: 12px 0;
    color: var(--text-secondary);
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.different-list strong {
    color: var(--primary);
}

/* Values Section */
.values-section {
    padding: 80px 0;
    background: var(--card-bg);
}

.section-title {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 50px;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
}

.value-card {
    text-align: center;
    padding: 30px;
}

.value-icon {
    font-size: 3rem;
    margin-bottom: 20px;
}

.value-card h3 {
    font-size: 1.3rem;
    margin-bottom: 15px;
    color: var(--primary);
}

.value-card p {
    color: var(--text-secondary);
    line-height: 1.6;
}

/* Team Section */
.team-section {
    padding: 80px 0;
}

.section-subtitle {
    text-align: center;
    color: var(--text-secondary);
    margin-bottom: 50px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.team-message {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.team-message blockquote {
    font-size: 1.3rem;
    font-style: italic;
    color: var(--text-secondary);
    line-height: 1.8;
    padding: 30px;
    background: var(--card-bg);
    border-radius: 16px;
    border-left: 4px solid var(--primary);
}

.team-signature {
    margin-top: 20px;
    color: var(--primary);
    font-weight: 600;
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
    .about-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .about-grid.reverse {
        direction: ltr;
    }
    
    .about-visual {
        order: -1;
    }
    
    .mission-icon {
        width: 180px;
        height: 180px;
        font-size: 4rem;
    }
    
    .values-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .page-hero h1 {
        font-size: 2.2rem;
    }
}

@media (max-width: 600px) {
    .values-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . '/templates/footer.php'; ?>
