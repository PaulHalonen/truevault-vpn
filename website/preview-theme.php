<?php
/**
 * TrueVault VPN - Theme Preview Page
 * Part 8 - Task 8.8
 * Standalone preview for testing themes before activation
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/configs/config.php';

$themeId = (int)($_GET['id'] ?? 0);

// Get theme from database
$db = new SQLite3(DB_THEMES);
$db->enableExceptions(true);

if ($themeId) {
    $stmt = $db->prepare("SELECT * FROM themes WHERE id = :id");
    $stmt->bindValue(':id', $themeId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $theme = $result->fetchArray(SQLITE3_ASSOC);
} else {
    // Get active theme
    $result = $db->query("SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
    $theme = $result->fetchArray(SQLITE3_ASSOC);
}

$db->close();

if (!$theme) {
    die('Theme not found');
}

// Parse theme data
$colors = json_decode($theme['colors'], true);
$fonts = json_decode($theme['fonts'], true);
$spacing = json_decode($theme['spacing'], true);
$borders = json_decode($theme['borders'], true);
$shadows = json_decode($theme['shadows'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?= htmlspecialchars($theme['display_name']) ?> - TrueVault VPN</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@400;600&family=Fira+Code&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Colors */
            --primary: <?= $colors['primary'] ?>;
            --secondary: <?= $colors['secondary'] ?>;
            --accent: <?= $colors['accent'] ?>;
            --background: <?= $colors['background'] ?>;
            --surface: <?= $colors['surface'] ?>;
            --text: <?= $colors['text'] ?>;
            --text-muted: <?= $colors['text_muted'] ?>;
            --border: <?= $colors['border'] ?>;
            --success: <?= $colors['success'] ?>;
            --warning: <?= $colors['warning'] ?>;
            --error: <?= $colors['error'] ?>;
            
            /* Fonts */
            --font-heading: <?= $fonts['heading'] ?>;
            --font-body: <?= $fonts['body'] ?>;
            --font-mono: <?= $fonts['mono'] ?>;
            
            /* Spacing */
            --spacing-xs: <?= $spacing['xs'] ?>;
            --spacing-sm: <?= $spacing['sm'] ?>;
            --spacing-md: <?= $spacing['md'] ?>;
            --spacing-lg: <?= $spacing['lg'] ?>;
            --spacing-xl: <?= $spacing['xl'] ?>;
            
            /* Borders */
            --radius-sm: <?= $borders['radius_sm'] ?>;
            --radius-md: <?= $borders['radius_md'] ?>;
            --radius-lg: <?= $borders['radius_lg'] ?>;
            
            /* Shadows */
            --shadow-sm: <?= $shadows['sm'] ?>;
            --shadow-md: <?= $shadows['md'] ?>;
            --shadow-lg: <?= $shadows['lg'] ?>;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: var(--font-body);
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
        }
        
        /* Preview Banner */
        .preview-banner {
            background: linear-gradient(90deg, #ff9800, #f57c00);
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .preview-banner h3 { font-size: 0.95rem; }
        .preview-banner .actions { display: flex; gap: 10px; }
        .preview-banner button, .preview-banner a {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        .preview-banner .btn-activate { background: #4caf50; color: white; }
        .preview-banner .btn-close { background: rgba(255,255,255,0.2); color: white; }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: var(--spacing-xl) var(--spacing-lg);
            text-align: center;
        }
        .hero h1 {
            font-family: var(--font-heading);
            font-size: 2.5rem;
            margin-bottom: var(--spacing-md);
        }
        .hero p {
            font-size: 1.2rem;
            margin-bottom: var(--spacing-lg);
            opacity: 0.9;
        }
        .hero .btn {
            display: inline-block;
            padding: var(--spacing-md) var(--spacing-xl);
            background: white;
            color: var(--primary);
            border-radius: var(--radius-md);
            font-weight: 600;
            text-decoration: none;
            box-shadow: var(--shadow-md);
        }
        
        /* Stats */
        .stats {
            display: flex;
            justify-content: center;
            gap: var(--spacing-xl);
            padding: var(--spacing-lg);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
        }
        .stat { text-align: center; }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            font-family: var(--font-heading);
        }
        .stat-label { color: var(--text-muted); font-size: 0.9rem; }
        
        /* Features */
        .features {
            padding: var(--spacing-xl) var(--spacing-lg);
            max-width: 1200px;
            margin: 0 auto;
        }
        .features h2 {
            font-family: var(--font-heading);
            text-align: center;
            margin-bottom: var(--spacing-xl);
            color: var(--text);
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--spacing-lg);
        }
        .feature-card {
            background: var(--surface);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .feature-card .icon {
            font-size: 2.5rem;
            margin-bottom: var(--spacing-md);
        }
        .feature-card h3 {
            font-family: var(--font-heading);
            margin-bottom: var(--spacing-sm);
            color: var(--text);
        }
        .feature-card p { color: var(--text-muted); }
        
        /* Pricing */
        .pricing {
            background: var(--surface);
            padding: var(--spacing-xl) var(--spacing-lg);
        }
        .pricing h2 {
            font-family: var(--font-heading);
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--spacing-lg);
            max-width: 1000px;
            margin: 0 auto;
        }
        .price-card {
            background: var(--background);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            text-align: center;
        }
        .price-card.featured {
            border-color: var(--primary);
            position: relative;
        }
        .price-card.featured::before {
            content: 'POPULAR';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .price-card h3 { margin-bottom: var(--spacing-sm); }
        .price-card .price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            font-family: var(--font-heading);
        }
        .price-card .period { color: var(--text-muted); }
        .price-card ul {
            list-style: none;
            margin: var(--spacing-lg) 0;
            text-align: left;
        }
        .price-card li {
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--border);
            color: var(--text-muted);
        }
        .price-card li::before {
            content: '✓';
            color: var(--success);
            margin-right: var(--spacing-sm);
        }
        .price-card .btn {
            display: block;
            padding: var(--spacing-md);
            background: var(--primary);
            color: white;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
        }
        
        /* Alerts Demo */
        .alerts-demo {
            padding: var(--spacing-xl) var(--spacing-lg);
            max-width: 800px;
            margin: 0 auto;
        }
        .alerts-demo h2 { margin-bottom: var(--spacing-lg); text-align: center; }
        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
        }
        .alert-success { background: color-mix(in srgb, var(--success) 15%, transparent); border-left: 4px solid var(--success); }
        .alert-warning { background: color-mix(in srgb, var(--warning) 15%, transparent); border-left: 4px solid var(--warning); }
        .alert-error { background: color-mix(in srgb, var(--error) 15%, transparent); border-left: 4px solid var(--error); }
        
        /* Buttons Demo */
        .buttons-demo {
            padding: var(--spacing-xl) var(--spacing-lg);
            background: var(--surface);
            text-align: center;
        }
        .buttons-demo h2 { margin-bottom: var(--spacing-lg); }
        .buttons-demo .btn-row { display: flex; gap: var(--spacing-md); justify-content: center; flex-wrap: wrap; }
        .btn-demo {
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-md);
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: var(--secondary); color: white; }
        .btn-accent { background: var(--accent); color: white; }
        .btn-outline { background: transparent; border: 2px solid var(--primary); color: var(--primary); }
        
        /* Footer */
        footer {
            background: var(--text);
            color: var(--background);
            padding: var(--spacing-xl) var(--spacing-lg);
            text-align: center;
        }
        footer a { color: var(--accent); }
        
        /* Color Palette Display */
        .color-palette {
            padding: var(--spacing-xl) var(--spacing-lg);
            max-width: 1000px;
            margin: 0 auto;
        }
        .color-palette h2 { margin-bottom: var(--spacing-lg); text-align: center; }
        .palette-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: var(--spacing-md);
        }
        .color-swatch {
            height: 80px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: flex-end;
            padding: var(--spacing-sm);
            font-size: 0.75rem;
            font-family: var(--font-mono);
        }
        
        @media (max-width: 768px) {
            .hero h1 { font-size: 1.8rem; }
            .stats { flex-wrap: wrap; gap: var(--spacing-md); }
            .preview-banner { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <!-- Preview Banner -->
    <div class="preview-banner">
        <h3>🎨 Previewing: <?= htmlspecialchars($theme['display_name']) ?> (<?= ucfirst($theme['category']) ?>)</h3>
        <div class="actions">
            <button class="btn-activate" onclick="activateTheme(<?= $theme['id'] ?>)">✓ Activate Theme</button>
            <a href="/admin/theme-manager.php" class="btn-close">← Back to Manager</a>
        </div>
    </div>
    
    <!-- Hero -->
    <section class="hero">
        <h1>Welcome to TrueVault VPN</h1>
        <p>Your Complete Digital Fortress - Secure, Fast, Private</p>
        <a href="#" class="btn">Start Free Trial</a>
    </section>
    
    <!-- Stats -->
    <section class="stats">
        <div class="stat">
            <div class="stat-value">256-bit</div>
            <div class="stat-label">Encryption</div>
        </div>
        <div class="stat">
            <div class="stat-value">4+</div>
            <div class="stat-label">Server Locations</div>
        </div>
        <div class="stat">
            <div class="stat-value">Zero</div>
            <div class="stat-label">Log Policy</div>
        </div>
        <div class="stat">
            <div class="stat-value">∞</div>
            <div class="stat-label">Devices</div>
        </div>
    </section>
    
    <!-- Features -->
    <section class="features">
        <h2>Revolutionary Features</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="icon">🔐</div>
                <h3>Smart Identity Router</h3>
                <p>Maintain persistent digital identities for different regions with consistent fingerprints.</p>
            </div>
            <div class="feature-card">
                <div class="icon">👨‍👩‍👧‍👦</div>
                <h3>Family Mesh Network</h3>
                <p>Connect all your devices as if they're on the same local network, anywhere in the world.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🌐</div>
                <h3>Decentralized Network</h3>
                <p>Route traffic through residential nodes worldwide. No central servers to compromise.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🤖</div>
                <h3>AI-Powered Routing</h3>
                <p>Smart system learns your habits and optimizes routes automatically.</p>
            </div>
            <div class="feature-card">
                <div class="icon">📜</div>
                <h3>Personal Certificates</h3>
                <p>You own your encryption. We generate YOUR personal certificate infrastructure.</p>
            </div>
            <div class="feature-card">
                <div class="icon">👻</div>
                <h3>Invisible Mode</h3>
                <p>Advanced traffic obfuscation makes your VPN look like normal HTTPS traffic.</p>
            </div>
        </div>
    </section>
    
    <!-- Pricing -->
    <section class="pricing">
        <h2>Choose Your Plan</h2>
        <div class="pricing-grid">
            <div class="price-card">
                <h3>Personal</h3>
                <div class="price">$9.97</div>
                <div class="period">per month</div>
                <ul>
                    <li>3 Devices</li>
                    <li>Personal Certificates</li>
                    <li>3 Regional Identities</li>
                    <li>Smart Routing</li>
                    <li>24/7 Support</li>
                </ul>
                <a href="#" class="btn">Get Started</a>
            </div>
            <div class="price-card featured">
                <h3>Family</h3>
                <div class="price">$14.97</div>
                <div class="period">per month</div>
                <ul>
                    <li>Unlimited Devices</li>
                    <li>Full Certificate Suite</li>
                    <li>All Regional Identities</li>
                    <li>Mesh Networking (6 users)</li>
                    <li>Priority Support</li>
                </ul>
                <a href="#" class="btn">Most Popular</a>
            </div>
            <div class="price-card">
                <h3>Business</h3>
                <div class="price">$39.97</div>
                <div class="period">per month</div>
                <ul>
                    <li>Unlimited Everything</li>
                    <li>Enterprise Certificates</li>
                    <li>Team Mesh (25 users)</li>
                    <li>Admin Dashboard</li>
                    <li>Dedicated Support</li>
                </ul>
                <a href="#" class="btn">Contact Sales</a>
            </div>
        </div>
    </section>
    
    <!-- Alerts Demo -->
    <section class="alerts-demo">
        <h2>UI Components</h2>
        <div class="alert alert-success">✓ Success! Your settings have been saved successfully.</div>
        <div class="alert alert-warning">⚠ Warning! Your subscription will expire in 7 days.</div>
        <div class="alert alert-error">✕ Error! Unable to connect to server. Please try again.</div>
    </section>
    
    <!-- Buttons Demo -->
    <section class="buttons-demo">
        <h2>Button Styles</h2>
        <div class="btn-row">
            <button class="btn-demo btn-primary">Primary</button>
            <button class="btn-demo btn-secondary">Secondary</button>
            <button class="btn-demo btn-accent">Accent</button>
            <button class="btn-demo btn-outline">Outline</button>
        </div>
    </section>
    
    <!-- Color Palette -->
    <section class="color-palette">
        <h2>Color Palette</h2>
        <div class="palette-grid">
            <div class="color-swatch" style="background: var(--primary); color: white;">Primary<br><?= $colors['primary'] ?></div>
            <div class="color-swatch" style="background: var(--secondary); color: white;">Secondary<br><?= $colors['secondary'] ?></div>
            <div class="color-swatch" style="background: var(--accent); color: white;">Accent<br><?= $colors['accent'] ?></div>
            <div class="color-swatch" style="background: var(--background); border: 1px solid var(--border);">Background<br><?= $colors['background'] ?></div>
            <div class="color-swatch" style="background: var(--surface); border: 1px solid var(--border);">Surface<br><?= $colors['surface'] ?></div>
            <div class="color-swatch" style="background: var(--text); color: white;">Text<br><?= $colors['text'] ?></div>
            <div class="color-swatch" style="background: var(--success); color: white;">Success<br><?= $colors['success'] ?></div>
            <div class="color-swatch" style="background: var(--warning); color: white;">Warning<br><?= $colors['warning'] ?></div>
            <div class="color-swatch" style="background: var(--error); color: white;">Error<br><?= $colors['error'] ?></div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer>
        <p>&copy; 2026 TrueVault VPN. All rights reserved.</p>
        <p><a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
    </footer>
    
    <script>
        async function activateTheme(themeId) {
            if (!confirm('Activate this theme for your entire site?')) return;
            
            try {
                const response = await fetch('/api/themes/activate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ theme_id: themeId })
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('Theme activated successfully!');
                    window.location.href = '/admin/theme-manager.php';
                } else {
                    alert('Error: ' + (data.error || 'Failed to activate theme'));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
    </script>
</body>
</html>
