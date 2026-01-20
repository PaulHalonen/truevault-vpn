<?php
require_once 'config.php';
requireAdminLogin();

$admin = getCurrentAdmin();
$db = getAdminDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8);
            updateSetting($settingKey, $value);
        }
    }
    $_SESSION['success_message'] = 'Settings updated successfully!';
    header('Location: /admin/settings.php');
    exit;
}

// Get all settings grouped by category
$stmt = $db->query("SELECT * FROM system_settings ORDER BY category, setting_key");
$allSettings = $stmt->fetchAll();

$settingsByCategory = [];
foreach ($allSettings as $setting) {
    $category = $setting['category'];
    if (!isset($settingsByCategory[$category])) {
        $settingsByCategory[$category] = [];
    }
    $settingsByCategory[$category][] = $setting;
}

$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin</title>
    <style>
        body { background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; font-family: -apple-system, sans-serif; margin: 0; padding: 0; }
        .page-container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .back-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; }
        .success-message { background: rgba(0,255,136,0.2); border: 1px solid #00ff88; color: #00ff88; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
        .settings-section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .settings-section h3 { margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #00d9ff; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #ccc; font-weight: 600; }
        .form-group small { display: block; color: #666; font-size: 0.85rem; margin-top: 0.25rem; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="email"] { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; font-size: 1rem; }
        .form-group input:focus { outline: none; border-color: #00d9ff; }
        .form-group input[type="checkbox"] { width: auto; margin-right: 0.5rem; }
        .checkbox-group { display: flex; align-items: center; }
        .btn-primary { padding: 1rem 2rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,217,255,0.4); }
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        @media (max-width: 768px) { .settings-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <div>
            <a href="/admin/index.php" class="back-btn">← Dashboard</a>
            <h1 style="display: inline; margin-left: 1rem;">System Settings</h1>
        </div>
    </div>

    <?php if ($successMessage): ?>
        <div class="success-message"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <form method="POST">
        <?php foreach ($settingsByCategory as $category => $settings): ?>
            <div class="settings-section">
                <h3><?= ucwords(str_replace('_', ' ', $category)) ?> Settings</h3>
                
                <div class="settings-grid">
                    <?php foreach ($settings as $setting): ?>
                        <div class="form-group">
                            <label for="setting_<?= $setting['setting_key'] ?>">
                                <?= ucwords(str_replace('_', ' ', $setting['setting_key'])) ?>
                            </label>
                            
                            <?php if ($setting['setting_type'] === 'boolean'): ?>
                                <div class="checkbox-group">
                                    <input type="checkbox" 
                                           id="setting_<?= $setting['setting_key'] ?>" 
                                           name="setting_<?= $setting['setting_key'] ?>"
                                           value="1"
                                           <?= $setting['setting_value'] == '1' ? 'checked' : '' ?>>
                                    <label for="setting_<?= $setting['setting_key'] ?>" style="margin: 0; font-weight: normal;">
                                        <?= $setting['description'] ?>
                                    </label>
                                </div>
                            <?php elseif ($setting['setting_type'] === 'number'): ?>
                                <input type="number" 
                                       id="setting_<?= $setting['setting_key'] ?>" 
                                       name="setting_<?= $setting['setting_key'] ?>"
                                       value="<?= htmlspecialchars($setting['setting_value']) ?>"
                                       step="0.01">
                                <?php if ($setting['description']): ?>
                                    <small><?= htmlspecialchars($setting['description']) ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <input type="<?= $setting['setting_key'] === 'admin_email' ? 'email' : 'text' ?>" 
                                       id="setting_<?= $setting['setting_key'] ?>" 
                                       name="setting_<?= $setting['setting_key'] ?>"
                                       value="<?= htmlspecialchars($setting['setting_value']) ?>">
                                <?php if ($setting['description']): ?>
                                    <small><?= htmlspecialchars($setting['description']) ?></small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="text-align: center;">
            <button type="submit" class="btn-primary">💾 Save Settings</button>
        </div>
    </form>
</div>
</body>
</html>
