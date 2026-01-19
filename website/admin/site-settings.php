<?php
/**
 * TrueVault VPN - Site Settings Manager
 * 
 * Admin interface for managing all global site settings
 * 
 * CATEGORIES:
 * - General (site title, logos, emails)
 * - Branding (theme, logo sizes)
 * - SEO (meta tags, analytics)
 * - Social (social media links)
 * - Features (maintenance, registration, trials)
 * - Pricing (prices, currency)
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Initialize
define('TRUEVAULT_INIT', true);
session_start();

require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Content.php';

// Check authentication
if (!Auth::isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $updated = 0;
    $errors = [];
    
    foreach ($_POST as $key => $value) {
        if ($key === 'action') continue;
        
        // Determine type from current value
        $current = Content::get($key);
        $type = 'text';
        
        if (is_bool($current) || in_array($value, ['0', '1', 'true', 'false'])) {
            $type = 'boolean';
            $value = in_array($value, ['1', 'true']) ? '1' : '0';
        } elseif (is_numeric($value)) {
            $type = 'number';
        }
        
        if (Content::set($key, $value, $type)) {
            $updated++;
        } else {
            $errors[] = $key;
        }
    }
    
    $successMessage = "Updated $updated settings successfully!";
    if (!empty($errors)) {
        $successMessage .= " (Failed: " . implode(', ', $errors) . ")";
    }
}

// Get all settings grouped by category
$allSettings = Content::getAll();

// Group by category
$grouped = [];
foreach ($allSettings as $key => $data) {
    $category = $data['category'];
    if (!isset($grouped[$category])) {
        $grouped[$category] = [];
    }
    $grouped[$category][$key] = $data;
}

$pageTitle = 'Site Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - TrueVault Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            color: #333;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #10b981;
            color: white;
            font-size: 16px;
            padding: 12px 30px;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .success-message {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .settings-form {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .category-section {
            margin-bottom: 40px;
        }
        
        .category-section:last-child {
            margin-bottom: 0;
        }
        
        .category-header {
            font-size: 22px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        
        .settings-grid {
            display: grid;
            gap: 20px;
        }
        
        .setting-item {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 15px;
            align-items: start;
        }
        
        .setting-label {
            font-weight: 600;
            color: #555;
            padding-top: 10px;
        }
        
        .setting-description {
            font-size: 13px;
            color: #999;
            margin-top: 3px;
        }
        
        .setting-input {
            width: 100%;
        }
        
        .setting-input input[type="text"],
        .setting-input input[type="email"],
        .setting-input input[type="url"],
        .setting-input input[type="number"],
        .setting-input textarea {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .setting-input input:focus,
        .setting-input textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .setting-input textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            border-radius: 30px;
            transition: 0.3s;
        }
        
        .toggle-slider:before {
            content: "";
            position: absolute;
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        
        input:checked + .toggle-slider {
            background-color: #667eea;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }
        
        .form-actions {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e5e7eb;
            display: flex;
            gap: 15px;
        }
        
        .category-icon {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>⚙️ Site Settings</h1>
                <p style="color: #666; margin-top: 5px;">Manage global site configuration</p>
            </div>
            <a href="/admin/" class="btn btn-primary">← Back to Admin</a>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="success-message">
                ✓ <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="settings-form">
            <input type="hidden" name="action" value="save_settings">
            
            <?php 
            $categoryIcons = [
                'general' => '🏢',
                'branding' => '🎨',
                'seo' => '🔍',
                'social' => '📱',
                'features' => '⚡',
                'pricing' => '💰'
            ];
            
            $categoryNames = [
                'general' => 'General Settings',
                'branding' => 'Branding & Appearance',
                'seo' => 'SEO & Analytics',
                'social' => 'Social Media',
                'features' => 'Features & Toggles',
                'pricing' => 'Pricing & Currency'
            ];
            
            foreach ($grouped as $category => $settings): 
            ?>
            <div class="category-section">
                <h2 class="category-header">
                    <span class="category-icon"><?= $categoryIcons[$category] ?? '📋' ?></span>
                    <?= $categoryNames[$category] ?? ucfirst($category) ?>
                </h2>
                
                <div class="settings-grid">
                    <?php foreach ($settings as $key => $data): ?>
                    <div class="setting-item">
                        <div class="setting-label">
                            <div><?= ucwords(str_replace('_', ' ', $key)) ?></div>
                            <?php if ($data['description']): ?>
                                <div class="setting-description"><?= htmlspecialchars($data['description']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="setting-input">
                            <?php if ($data['type'] === 'boolean'): ?>
                                <label class="toggle-switch">
                                    <input type="checkbox" 
                                           name="<?= htmlspecialchars($key) ?>" 
                                           value="1"
                                           <?= $data['value'] ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            <?php elseif ($data['type'] === 'number'): ?>
                                <input type="number" 
                                       name="<?= htmlspecialchars($key) ?>" 
                                       value="<?= htmlspecialchars($data['value']) ?>"
                                       step="0.01">
                            <?php elseif (strpos($key, 'email') !== false): ?>
                                <input type="email" 
                                       name="<?= htmlspecialchars($key) ?>" 
                                       value="<?= htmlspecialchars($data['value']) ?>">
                            <?php elseif (strpos($key, 'url') !== false): ?>
                                <input type="url" 
                                       name="<?= htmlspecialchars($key) ?>" 
                                       value="<?= htmlspecialchars($data['value']) ?>"
                                       placeholder="https://">
                            <?php elseif (strpos($key, 'description') !== false || strpos($key, 'keywords') !== false): ?>
                                <textarea name="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($data['value']) ?></textarea>
                            <?php else: ?>
                                <input type="text" 
                                       name="<?= htmlspecialchars($key) ?>" 
                                       value="<?= htmlspecialchars($data['value']) ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    💾 Save All Settings
                </button>
                <button type="button" class="btn btn-primary" onclick="window.location.reload()">
                    🔄 Reset Form
                </button>
            </div>
        </form>
    </div>
</body>
</html>
