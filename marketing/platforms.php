<?php
require_once 'config.php';

$platformId = $_GET['id'] ?? null;
$db = getMarketingDB();

// Get platform details
if ($platformId) {
    $stmt = $db->prepare("SELECT * FROM marketing_platforms WHERE id = ?");
    $stmt->execute([$platformId]);
    $platform = $stmt->fetch();
    
    // Get credentials
    $credential = getPlatformCredential($platformId);
}

// Handle credential save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $platformId) {
    $credentialName = $_POST['credential_name'] ?? '';
    $apiKey = $_POST['api_key'] ?? '';
    $apiSecret = $_POST['api_secret'] ?? '';
    
    savePlatformCredential($platformId, $credentialName, $apiKey, $apiSecret);
    header('Location: /marketing/platforms.php?id=' . $platformId . '&success=1');
    exit;
}

$success = $_GET['success'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Configuration - Marketing</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 800px; margin: 0 auto; padding: 2rem; }
        .back-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; }
        .platform-header { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin: 2rem 0; text-align: center; }
        .platform-icon { font-size: 4rem; margin-bottom: 1rem; }
        .platform-name { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .platform-type { color: #888; }
        .config-section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; }
        .success-message { background: rgba(0,255,136,0.2); border: 1px solid #00ff88; color: #00ff88; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #ccc; font-weight: 600; }
        .form-group input { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; }
        .form-group small { display: block; color: #666; font-size: 0.85rem; margin-top: 0.25rem; }
        .btn { padding: 0.75rem 1.5rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
<div class="container">
    <a href="/marketing/index.php" class="back-btn">← Back to Marketing</a>
    
    <?php if ($platform): ?>
        <div class="platform-header">
            <div class="platform-icon"><?= $platform['icon'] ?></div>
            <div class="platform-name"><?= htmlspecialchars($platform['platform_name']) ?></div>
            <div class="platform-type"><?= ucfirst($platform['platform_type']) ?> Platform</div>
        </div>
        
        <?php if ($success): ?>
            <div class="success-message">✓ Credentials saved successfully!</div>
        <?php endif; ?>
        
        <div class="config-section">
            <h2 style="margin-bottom: 1.5rem;">API Configuration</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="credential_name">Configuration Name</label>
                    <input type="text" id="credential_name" name="credential_name" 
                           value="<?= htmlspecialchars($credential['credential_name'] ?? '') ?>" 
                           placeholder="e.g., Production API">
                    <small>Give this configuration a memorable name</small>
                </div>
                
                <div class="form-group">
                    <label for="api_key">API Key</label>
                    <input type="text" id="api_key" name="api_key" 
                           value="<?= htmlspecialchars($credential['api_key'] ?? '') ?>" 
                           placeholder="Enter your API key">
                    <small>Find this in your <?= $platform['platform_name'] ?> dashboard</small>
                </div>
                
                <div class="form-group">
                    <label for="api_secret">API Secret (Optional)</label>
                    <input type="password" id="api_secret" name="api_secret" 
                           value="<?= htmlspecialchars($credential['api_secret'] ?? '') ?>" 
                           placeholder="Enter your API secret if required">
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">💾 Save Configuration</button>
            </form>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem; color: #666;">
            <p>Platform not found</p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
