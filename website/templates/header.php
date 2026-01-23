<?php
/**
 * TrueVault VPN - Header Template
 * Part 12 - Database-driven header
 * ALL content from database - NO hardcoding
 */

// Load content functions if not already loaded
if (!function_exists('getSetting')) {
    require_once __DIR__ . '/../includes/content-functions.php';
}

// Get settings and navigation
$siteName = getSetting('site_name', 'TrueVault VPN');
$siteLogo = getSetting('site_logo', '/assets/images/logo.png');
$tagline = getSetting('site_tagline', 'Your Complete Digital Fortress');
$ctaText = getSetting('cta_primary_text', 'Start Free Trial');
$ctaUrl = getSetting('cta_primary_url', '/register.php');
$headerNav = getNavigation('header');
$theme = getActiveTheme();

// Current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (isset($pageData)): ?>
    <title><?= e($pageData['meta_title'] ?? $pageData['page_title']) ?><?= getSetting('seo_title_suffix', ' | TrueVault VPN') ?></title>
    <meta name="description" content="<?= e($pageData['meta_description'] ?? getSetting('seo_default_description')) ?>">
    <meta name="keywords" content="<?= e($pageData['meta_keywords'] ?? getSetting('seo_default_keywords')) ?>">
    <?php else: ?>
    <title><?= e($siteName) ?></title>
    <meta name="description" content="<?= e(getSetting('seo_default_description')) ?>">
    <?php endif; ?>
    
    <link rel="icon" href="<?= e(getSetting('site_favicon', '/assets/images/favicon.ico')) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= e($pageData['meta_title'] ?? $siteName) ?>">
    <meta property="og:description" content="<?= e($pageData['meta_description'] ?? getSetting('seo_default_description')) ?>">
    <meta property="og:type" content="website">
    
    <style>
    /* Theme CSS Variables - ALL from database */
    :root {
        --primary: <?= e($theme['primary_color'] ?? '#00d9ff') ?>;
        --secondary: <?= e($theme['secondary_color'] ?? '#00ff88') ?>;
        --background: <?= e($theme['background_color'] ?? '#0f0f1a') ?>;
        --card-bg: <?= e($theme['card_bg_color'] ?? '#1a1a2e') ?>;
        --text-primary: <?= e($theme['text_primary'] ?? '#ffffff') ?>;
        --text-secondary: <?= e($theme['text_secondary'] ?? '#a0a0a0') ?>;
        --accent: <?= e($theme['accent_color'] ?? '#ff6b6b') ?>;
        --font-family: <?= $theme['font_family'] ?? 'system-ui, -apple-system, sans-serif' ?>;
    }
    
    /* Base Reset */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
        font-family: var(--font-family);
        background: var(--background);
        color: var(--text-primary);
        line-height: 1.6;
        min-height: 100vh;
    }
    
    a { color: var(--primary); text-decoration: none; transition: all 0.3s; }
    a:hover { color: var(--secondary); }
    
    .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
    
    /* Header Styles */
    .site-header {
        background: rgba(15, 15, 26, 0.95);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255,255,255,0.05);
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    
    .header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .logo {
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--text-primary);
        font-weight: 700;
        font-size: 1.3rem;
    }
    
    .logo img {
        height: 40px;
        width: auto;
    }
    
    .logo-text {
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .header-nav {
        display: flex;
        gap: 30px;
        align-items: center;
    }
    
    .header-nav a {
        color: var(--text-secondary);
        font-weight: 500;
        padding: 8px 0;
        position: relative;
    }
    
    .header-nav a:hover,
    .header-nav a.active {
        color: var(--text-primary);
    }
    
    .header-nav a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        transition: width 0.3s;
    }
    
    .header-nav a:hover::after,
    .header-nav a.active::after {
        width: 100%;
    }
    
    .header-actions {
        display: flex;
        gap: 15px;
        align-items: center;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
    }
    
    .btn-primary {
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        color: #0f0f1a;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0, 217, 255, 0.3);
        color: #0f0f1a;
    }
    
    .btn-secondary {
        background: transparent;
        color: var(--text-primary);
        border: 1px solid rgba(255,255,255,0.2);
    }
    
    .btn-secondary:hover {
        background: rgba(255,255,255,0.05);
        border-color: var(--primary);
        color: var(--primary);
    }
    
    /* Mobile Menu */
    .mobile-toggle {
        display: none;
        background: none;
        border: none;
        color: var(--text-primary);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 5px;
    }
    
    @media (max-width: 900px) {
        .header-nav { display: none; }
        .header-actions .btn-secondary { display: none; }
        .mobile-toggle { display: block; }
        
        .header-nav.active {
            display: flex;
            flex-direction: column;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--card-bg);
            padding: 20px;
            gap: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
    }
    </style>
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="/" class="logo">
            <?php if ($siteLogo && file_exists(__DIR__ . '/..' . $siteLogo)): ?>
            <img src="<?= e($siteLogo) ?>" alt="<?= e($siteName) ?>">
            <?php else: ?>
            <span style="font-size: 2rem;">🛡️</span>
            <?php endif; ?>
            <span class="logo-text"><?= e($siteName) ?></span>
        </a>
        
        <nav class="header-nav" id="mainNav">
            <?php foreach ($headerNav as $item): ?>
            <a href="<?= e($item['url']) ?>" 
               class="<?= ($currentPage === basename($item['url'], '.php')) ? 'active' : '' ?>"
               <?= $item['open_new_tab'] ? 'target="_blank"' : '' ?>>
                <?= e($item['label']) ?>
            </a>
            <?php endforeach; ?>
        </nav>
        
        <div class="header-actions">
            <a href="/login.php" class="btn btn-secondary">Sign In</a>
            <a href="<?= e($ctaUrl) ?>" class="btn btn-primary"><?= e($ctaText) ?></a>
            <button class="mobile-toggle" onclick="document.getElementById('mainNav').classList.toggle('active')">☰</button>
        </div>
    </div>
</header>

<main>
