<?php
/**
 * TrueVault VPN - Frontend Page Renderer
 * 
 * Renders pages with theme colors and sections
 * Used by index.php and all public pages
 */

define('TRUEVAULT_INIT', true);

require_once __DIR__ . '/configs/config.php';
require_once __DIR__ . '/includes/Theme.php';
require_once __DIR__ . '/includes/Content.php';
require_once __DIR__ . '/includes/PageBuilder.php';

// Get page slug from URL
$slug = $_GET['page'] ?? 'home';
$page = PageBuilder::getPage($slug);

if (!$page) {
    http_response_code(404);
    die('Page not found');
}

// Get theme and settings
$theme = Theme::getActiveTheme();
$colors = Theme::getAllColors();
$siteTitle = Content::get('site_title', 'TrueVault VPN');
$siteLogo = Content::get('site_logo', '');

// Get page sections
$sections = PageBuilder::getSections($page['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title']) ?> - <?= htmlspecialchars($siteTitle) ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars($page['meta_description'] ?? Content::get('meta_description')) ?>">
    <meta name="keywords" content="<?= htmlspecialchars(Content::get('meta_keywords')) ?>">
    
    <!-- Theme Colors -->
    <style>
        :root {
            --primary: <?= $colors['primary'] ?>;
            --secondary: <?= $colors['secondary'] ?>;
            --accent: <?= $colors['accent'] ?>;
            --background: <?= $colors['background'] ?>;
            --surface: <?= $colors['surface'] ?>;
            --text-primary: <?= $colors['text_primary'] ?>;
            --text-secondary: <?= $colors['text_secondary'] ?>;
            --success: <?= $colors['success'] ?>;
            --warning: <?= $colors['warning'] ?>;
            --error: <?= $colors['error'] ?>;
            --info: <?= $colors['info'] ?>;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header {
            background: var(--surface);
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }
        
        .nav {
            display: flex;
            gap: 25px;
        }
        
        .nav a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav a:hover {
            color: var(--primary);
        }
        
        .btn {
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .section {
            padding: 60px 0;
        }
        
        .section-title {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-primary);
        }
        
        .section-subtitle {
            font-size: 20px;
            color: var(--text-secondary);
            margin-bottom: 40px;
        }
        
        .footer {
            background: var(--surface);
            padding: 40px 0;
            margin-top: 60px;
            border-top: 1px solid rgba(0,0,0,0.1);
        }
        
        .footer-content {
            text-align: center;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <a href="/" class="logo"><?= htmlspecialchars($siteTitle) ?></a>
                <nav class="nav">
                    <a href="/">Home</a>
                    <a href="/?page=features">Features</a>
                    <a href="/?page=pricing">Pricing</a>
                    <a href="/login.php">Login</a>
                </nav>
                <a href="/register.php" class="btn">Get Started</a>
            </div>
        </div>
    </header>

    <main>
        <?php foreach ($sections as $section): ?>
            <?php
            $data = json_decode($section['section_data'], true);
            $type = $section['section_type'];
            ?>
            
            <?php if ($type === 'hero'): ?>
                <section class="section" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; text-align: center;">
                    <div class="container">
                        <h1 class="section-title" style="color: white;"><?= htmlspecialchars($data['title'] ?? 'Welcome') ?></h1>
                        <p class="section-subtitle" style="color: rgba(255,255,255,0.9);"><?= htmlspecialchars($data['subtitle'] ?? '') ?></p>
                        <?php if (!empty($data['cta_text'])): ?>
                            <a href="<?= htmlspecialchars($data['cta_link'] ?? '#') ?>" class="btn" style="background: white; color: var(--primary);">
                                <?= htmlspecialchars($data['cta_text']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </section>
            
            <?php elseif ($type === 'content'): ?>
                <section class="section">
                    <div class="container">
                        <?php if (!empty($data['title'])): ?>
                            <h2 class="section-title"><?= htmlspecialchars($data['title']) ?></h2>
                        <?php endif; ?>
                        <div><?= $data['content'] ?? '' ?></div>
                    </div>
                </section>
            
            <?php else: ?>
                <section class="section">
                    <div class="container">
                        <p style="color: var(--text-secondary);">Section type: <?= htmlspecialchars($type) ?> (template coming soon)</p>
                    </div>
                </section>
            <?php endif; ?>
            
        <?php endforeach; ?>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteTitle) ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
