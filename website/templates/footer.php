<?php
/**
 * TrueVault VPN - Footer Template
 * Part 12 - Database-driven footer
 * ALL content from database - NO hardcoding
 */

// Get settings and navigation
$siteName = getSetting('site_name', 'TrueVault VPN');
$footerText = getSetting('footer_text', '© ' . date('Y') . ' TrueVault VPN. All rights reserved.');
$contactEmail = getSetting('contact_email', 'support@truevault.com');

// Get footer navigation sections
$footerCompany = getNavigation('footer_company');
$footerLegal = getNavigation('footer_legal');
$footerSupport = getNavigation('footer_support');

// Get social links
$socialTwitter = getSetting('social_twitter');
$socialFacebook = getSetting('social_facebook');
$socialInstagram = getSetting('social_instagram');
$socialYoutube = getSetting('social_youtube');
?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div class="footer-col footer-brand">
                <div class="footer-logo">
                    <span style="font-size: 2rem;">🛡️</span>
                    <span class="logo-text"><?= e($siteName) ?></span>
                </div>
                <p class="footer-tagline"><?= e(getSetting('site_tagline', 'Your Complete Digital Fortress')) ?></p>
                
                <?php if ($socialTwitter || $socialFacebook || $socialInstagram || $socialYoutube): ?>
                <div class="social-links">
                    <?php if ($socialTwitter): ?>
                    <a href="<?= e($socialTwitter) ?>" target="_blank" title="Twitter">𝕏</a>
                    <?php endif; ?>
                    <?php if ($socialFacebook): ?>
                    <a href="<?= e($socialFacebook) ?>" target="_blank" title="Facebook">📘</a>
                    <?php endif; ?>
                    <?php if ($socialInstagram): ?>
                    <a href="<?= e($socialInstagram) ?>" target="_blank" title="Instagram">📷</a>
                    <?php endif; ?>
                    <?php if ($socialYoutube): ?>
                    <a href="<?= e($socialYoutube) ?>" target="_blank" title="YouTube">▶️</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Company Links -->
            <div class="footer-col">
                <h4>Company</h4>
                <ul>
                    <?php foreach ($footerCompany as $item): ?>
                    <li><a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Legal Links -->
            <div class="footer-col">
                <h4>Legal</h4>
                <ul>
                    <?php foreach ($footerLegal as $item): ?>
                    <li><a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Support Links -->
            <div class="footer-col">
                <h4>Support</h4>
                <ul>
                    <?php foreach ($footerSupport as $item): ?>
                    <li><a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p><?= e($footerText) ?></p>
        </div>
    </div>
</footer>

<style>
/* Footer Styles */
.site-footer {
    background: var(--card-bg);
    border-top: 1px solid rgba(255,255,255,0.05);
    padding: 60px 0 30px;
    margin-top: 80px;
}

.footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 40px;
}

.footer-col h4 {
    color: var(--text-primary);
    font-size: 1rem;
    margin-bottom: 20px;
    font-weight: 600;
}

.footer-col ul {
    list-style: none;
}

.footer-col li {
    margin-bottom: 12px;
}

.footer-col a {
    color: var(--text-secondary);
    font-size: 0.95rem;
    transition: all 0.3s;
}

.footer-col a:hover {
    color: var(--primary);
    padding-left: 5px;
}

.footer-brand .footer-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.footer-brand .logo-text {
    font-size: 1.3rem;
    font-weight: 700;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.footer-tagline {
    color: var(--text-secondary);
    font-size: 0.95rem;
    margin-bottom: 20px;
    max-width: 300px;
}

.social-links {
    display: flex;
    gap: 15px;
}

.social-links a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    font-size: 1.2rem;
    transition: all 0.3s;
}

.social-links a:hover {
    background: var(--primary);
    transform: translateY(-3px);
}

.footer-bottom {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid rgba(255,255,255,0.05);
    text-align: center;
}

.footer-bottom p {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

@media (max-width: 900px) {
    .footer-grid {
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    
    .footer-brand {
        grid-column: 1 / -1;
    }
}

@media (max-width: 600px) {
    .footer-grid {
        grid-template-columns: 1fr;
    }
}
</style>

</body>
</html>
