<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'TrueVault VPN') ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description ?? 'Secure VPN protection') ?>">
    <link rel="icon" href="/assets/favicon.ico">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f0f1a; color: #fff; }
        header { background: #1a1a2e; padding: 1rem 2rem; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(0,217,255,0.2); }
        .header-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: #fff; font-size: 1.3rem; font-weight: 700; }
        .logo:hover { color: #00d9ff; }
        nav { display: flex; gap: 2rem; align-items: center; }
        nav a { color: #ccc; text-decoration: none; font-weight: 500; transition: 0.3s; }
        nav a:hover { color: #00d9ff; }
        .cta-button { padding: 0.6rem 1.5rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border-radius: 8px; font-weight: 700; }
        .cta-button:hover { transform: translateY(-2px); color: #000; }
        .mobile-menu-toggle { display: none; background: transparent; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; }
        
        @media (max-width: 768px) {
            nav { display: none; position: absolute; top: 100%; left: 0; right: 0; background: #1a1a2e; flex-direction: column; padding: 1rem; gap: 1rem; border-top: 1px solid rgba(0,217,255,0.2); }
            nav.active { display: flex; }
            .mobile-menu-toggle { display: block; }
        }
    </style>
</head>
<body>
<header>
    <div class="header-container">
        <a href="/" class="logo">🔒 TrueVault VPN</a>
        <button class="mobile-menu-toggle" onclick="toggleMenu()">☰</button>
        <nav id="mainNav">
            <a href="/">Home</a>
            <a href="/pricing.php">Pricing</a>
            <a href="/features.php">Features</a>
            <a href="/comparison.php">Compare</a>
            <a href="/about.php">About</a>
            <a href="/contact.php">Contact</a>
            <a href="/login.php" class="cta-button">Login</a>
        </nav>
    </div>
</header>
<script>
function toggleMenu() {
    document.getElementById('mainNav').classList.toggle('active');
}
</script>
