<?php
/**
 * TrueVault VPN - Theme Manager Admin Page
 * Part 8 - Task 8.3
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

// Check admin auth
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

// Get themes from database
$db = new SQLite3(DB_THEMES);
$db->enableExceptions(true);

// Get active theme
$activeResult = $db->query("SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
$activeTheme = $activeResult->fetchArray(SQLITE3_ASSOC);

// Get all themes by category
function getThemesByCategory($db, $category) {
    $stmt = $db->prepare("SELECT * FROM themes WHERE category = :cat ORDER BY display_name");
    $stmt->bindValue(':cat', $category, SQLITE3_TEXT);
    $result = $stmt->execute();
    $themes = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $themes[] = $row;
    }
    return $themes;
}

$seasonal = getThemesByCategory($db, 'seasonal');
$holiday = getThemesByCategory($db, 'holiday');
$standard = getThemesByCategory($db, 'standard');
$colorSchemes = getThemesByCategory($db, 'color_scheme');

// Get theme settings
$settingsResult = $db->query("SELECT * FROM theme_settings");
$settings = [];
while ($row = $settingsResult->fetchArray(SQLITE3_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Manager - TrueVault Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .header h1 {
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.8rem;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,217,255,0.3); }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-secondary:hover { background: rgba(255,255,255,0.2); }
        .btn-small { padding: 6px 12px; font-size: 0.8rem; }
        
        /* Active Theme Card */
        .active-theme-card {
            background: linear-gradient(135deg, rgba(0,217,255,0.1), rgba(0,255,136,0.1));
            border: 2px solid #00d9ff;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 25px;
        }
        .active-theme-preview {
            width: 200px;
            height: 120px;
            border-radius: 10px;
            display: flex;
            overflow: hidden;
        }
        .active-theme-preview .color-bar {
            flex: 1;
            height: 100%;
        }
        .active-theme-info { flex: 1; }
        .active-theme-info h2 { color: #00d9ff; margin-bottom: 8px; }
        .active-theme-info p { color: #888; margin-bottom: 15px; }
        .active-theme-actions { display: flex; gap: 10px; }
        
        /* Settings Panel */
        .settings-panel {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .settings-panel h3 { color: #00ff88; margin-bottom: 15px; }
        .settings-row {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .setting-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .setting-item label { color: #ccc; }
        .toggle {
            position: relative;
            width: 50px;
            height: 26px;
        }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.1);
            border-radius: 26px;
            transition: 0.3s;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: 0.3s;
        }
        .toggle input:checked + .toggle-slider { background: #00d9ff; }
        .toggle input:checked + .toggle-slider:before { transform: translateX(24px); }
        
        /* Category Section */
        .category-section { margin-bottom: 40px; }
        .category-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .category-header h3 { color: #fff; font-size: 1.2rem; }
        .category-header .count {
            background: rgba(255,255,255,0.1);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            color: #888;
        }
        
        /* Theme Grid */
        .theme-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .theme-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
        }
        .theme-card:hover {
            border-color: #00d9ff;
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,217,255,0.15);
        }
        .theme-card.active {
            border-color: #00ff88;
            box-shadow: 0 0 20px rgba(0,255,136,0.2);
        }
        .theme-preview {
            height: 100px;
            display: flex;
        }
        .theme-preview .color-bar {
            flex: 1;
            height: 100%;
        }
        .theme-info {
            padding: 15px;
        }
        .theme-info h4 { color: #fff; margin-bottom: 5px; }
        .theme-info p { color: #666; font-size: 0.85rem; margin-bottom: 12px; }
        .theme-actions {
            display: flex;
            gap: 8px;
        }
        .theme-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-active { background: rgba(0,255,136,0.2); color: #00ff88; }
        .badge-default { background: rgba(0,217,255,0.2); color: #00d9ff; }
        .badge-seasonal { background: rgba(255,193,7,0.2); color: #ffc107; }
        .badge-holiday { background: rgba(233,30,99,0.2); color: #e91e63; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.85);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #1a1a2e;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            padding: 30px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h2 { color: #00d9ff; }
        .modal-close {
            background: none;
            border: none;
            color: #888;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 2000;
            animation: slideIn 0.3s ease;
        }
        .toast.success { background: #00c853; color: #fff; }
        .toast.error { background: #ff5252; color: #fff; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .active-theme-card { flex-direction: column; text-align: center; }
            .active-theme-preview { width: 100%; }
            .settings-row { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎨 Theme Manager</h1>
            <div>
                <a href="/admin/dashboard.php" class="btn btn-secondary">← Dashboard</a>
            </div>
        </div>
        
        <!-- Active Theme -->
        <?php if ($activeTheme): 
            $colors = json_decode($activeTheme['colors'], true);
        ?>
        <div class="active-theme-card">
            <div class="active-theme-preview">
                <div class="color-bar" style="background: <?= $colors['primary'] ?>"></div>
                <div class="color-bar" style="background: <?= $colors['secondary'] ?>"></div>
                <div class="color-bar" style="background: <?= $colors['accent'] ?>"></div>
                <div class="color-bar" style="background: <?= $colors['background'] ?>"></div>
            </div>
            <div class="active-theme-info">
                <span class="theme-badge badge-active">Active</span>
                <?php if ($activeTheme['is_default']): ?>
                    <span class="theme-badge badge-default">Default</span>
                <?php endif; ?>
                <h2><?= htmlspecialchars($activeTheme['display_name']) ?></h2>
                <p><?= htmlspecialchars($activeTheme['description']) ?></p>
                <div class="active-theme-actions">
                    <a href="theme-editor.php?id=<?= $activeTheme['id'] ?>" class="btn btn-primary btn-small">✏️ Edit Theme</a>
                    <a href="/preview-theme.php?id=<?= $activeTheme['id'] ?>" target="_blank" class="btn btn-secondary btn-small">👁️ Preview</a>
                    <button class="btn btn-secondary btn-small" onclick="exportTheme(<?= $activeTheme['id'] ?>)">📤 Export</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Settings Panel -->
        <div class="settings-panel">
            <h3>⚙️ Auto-Switch Settings</h3>
            <div class="settings-row">
                <div class="setting-item">
                    <label class="toggle">
                        <input type="checkbox" id="seasonalToggle" <?= ($settings['seasonal_auto_switch'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <label for="seasonalToggle">Auto-switch by Season</label>
                </div>
                <div class="setting-item">
                    <label class="toggle">
                        <input type="checkbox" id="holidayToggle" <?= ($settings['holiday_auto_switch'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <label for="holidayToggle">Auto-switch for Holidays</label>
                </div>
            </div>
        </div>
        
        <!-- Seasonal Themes -->
        <div class="category-section">
            <div class="category-header">
                <h3>🌡️ Seasonal Themes</h3>
                <span class="count"><?= count($seasonal) ?> themes</span>
            </div>
            <div class="theme-grid">
                <?php foreach ($seasonal as $theme): 
                    $colors = json_decode($theme['colors'], true);
                ?>
                <div class="theme-card <?= $theme['is_active'] ? 'active' : '' ?>" data-id="<?= $theme['id'] ?>">
                    <div class="theme-preview">
                        <div class="color-bar" style="background: <?= $colors['primary'] ?>"></div>
                        <div class="color-bar" style="background: <?= $colors['secondary'] ?>"></div>
                        <div class="color-bar" style="background: <?= $colors['accent'] ?>"></div>
                        <div class="color-bar" style="background: <?= $colors['background'] ?>"></div>
                    </div>
                    <div class="theme-info">
                        <?php if ($theme['is_active']): ?><span class="theme-badge badge-active">Active</span><?php endif; ?>
                        <span class="theme-badge badge-seasonal"><?= ucfirst($theme['season']) ?></span>
                        <h4><?= htmlspecialchars($theme['display_name']) ?></h4>
                        <p><?= htmlspecialchars($theme['description']) ?></p>
                        <div class="theme-actions">
                            <button class="btn btn-primary btn-small" onclick="activateTheme(<?= $theme['id'] ?>)">Activate</button>
                            <a href="/preview-theme.php?id=<?= $theme['id'] ?>" target="_blank" class="btn btn-secondary btn-small">Preview</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Holiday Themes -->
        <div class="category-section">
            <div class="category-header">
                <h3>🎉 Holiday Themes</h3>
                <span class="count"><?= count($holiday) ?> themes</span>
            </div>
            <div class="theme-grid">
                <?php foreach ($holiday as $theme): 
                    $colors = json_decode($theme['colors'], true);
                ?>
                <div class="theme-card <?= $theme['is_active'] ? 'active' : '' ?>" data-id="<?= $theme['id'] ?>">
                    <div class="theme-preview">
                        <div class="color-bar" style="background: <?= $colors['primary'] ?>"></div>
                        <div class="color-bar" style="background: <?= $colors['secondary'] ?>"></div>
                        <div class="color-bar" style="background: <?= $colors['accent'] ?>"></div>
                        <div class="color-bar" style="background: <?= $colors['background'] ?>"></div>
                    </div>
                    <div class="theme-info">
                        <?php if ($theme['is_active']): ?><span class="theme-badge badge-active">Active</span><?php endif; ?>
                        <span class="theme-badge badge-holiday"><?= ucfirst(str_replace('_', ' ', $theme['holiday'])) ?></span>
                        <h4><?= htmlspecialchars($theme['display_name']) ?></h4>
                        <p><?= htmlspecialchars($theme['description']) ?></p>
                        <div class="theme-actions">
                            <button class="btn btn-primary btn-small" onclick="activateTheme(<?= $theme['id'] ?>)">Activate</button>
                            <a href="/preview-theme.php?id=<?= $theme['id'] ?>" target="_blank" class="btn btn-secondary btn-small">Preview</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Standard Themes -->
        <div class="category-section">
            <div class="category-header">
                <h3>💼 Standard Business Themes</h3>
                <span class="count"><?= count($standard) ?> themes</span>
            </div>
            <div class="theme-grid">
                <?php foreach ($standard as $theme): 
                    $colors = json_decode($theme['colors'], true);
                ?>
                <div class="theme-card <?= $theme['is_active'] ? 'active' : '' ?>" data-id="<?= $theme['id'] ?>">
                    <div class="theme-preview">
                        <div class="color-bar" style="background: <?= $colors['primary'] ?>"></div>
                        <div class="color-bar" style="background: <?= $colors['secondary'] ?>"></div>
                        <div class="color-bar" style="background: <?= $colors['accent'] ?>"></div>
                        <div class="color-bar" style="background: <?= $colors['background'] ?>"></div>
                    </div>
                    <div class="theme-info">
                        <?php if ($theme['is_active']): ?><span class="theme-badge badge-active">Active</span><?php endif; ?>
                        <?php if ($theme['is_default']): ?><span class="theme-badge badge-default">Default</span><?php endif; ?>
                        <h4><?= htmlspecialchars($theme['display_name']) ?></h4>
                        <p><?= htmlspecialchars($theme['description']) ?></p>
                        <div class="theme-actions">
                            <button class="btn btn-primary btn-small" onclick="activateTheme(<?= $theme['id'] ?>)">Activate</button>
                            <a href="/preview-theme.php?id=<?= $theme['id'] ?>" target="_blank" class="btn btn-secondary btn-small">Preview</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Color Scheme Themes -->
        <div class="category-section">
            <div class="category-header">
                <h3>🌈 Color Scheme Themes</h3>
                <span class="count"><?= count($colorSchemes) ?> themes</span>
            </div>
            <div class="theme-grid">
                <?php foreach ($colorSchemes as $theme): 
                    $colors = json_decode($theme['colors'], true);
                ?>
                <div class="theme-card <?= $theme['is_active'] ? 'active' : '' ?>" data-id="<?= $theme['id'] ?>">
                    <div class="theme-preview">
                        <div class="color-bar" style="background: <?= $colors['primary'] ?>"></div>
                        <div class="color-bar" style="background: <?= $colors['secondary'] ?>"></div>
                        <div class="color-bar" style="background: <?= $colors['accent'] ?>"></div>
                        <div class="color-bar" style="background: <?= $colors['background'] ?>"></div>
                    </div>
                    <div class="theme-info">
                        <?php if ($theme['is_active']): ?><span class="theme-badge badge-active">Active</span><?php endif; ?>
                        <h4><?= htmlspecialchars($theme['display_name']) ?></h4>
                        <p><?= htmlspecialchars($theme['description']) ?></p>
                        <div class="theme-actions">
                            <button class="btn btn-primary btn-small" onclick="activateTheme(<?= $theme['id'] ?>)">Activate</button>
                            <a href="/preview-theme.php?id=<?= $theme['id'] ?>" target="_blank" class="btn btn-secondary btn-small">Preview</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Activate theme
        async function activateTheme(themeId) {
            try {
                const response = await fetch('/api/themes/activate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ theme_id: themeId })
                });
                const data = await response.json();
                
                if (data.success) {
                    showToast('Theme activated successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to activate theme', 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        }
        
        // Export theme
        function exportTheme(themeId) {
            window.location.href = '/api/themes/export.php?id=' + themeId;
        }
        
        // Setting toggles
        document.getElementById('seasonalToggle').addEventListener('change', async function() {
            await updateSetting('seasonal_auto_switch', this.checked ? '1' : '0');
        });
        
        document.getElementById('holidayToggle').addEventListener('change', async function() {
            await updateSetting('holiday_auto_switch', this.checked ? '1' : '0');
        });
        
        async function updateSetting(key, value) {
            try {
                const response = await fetch('/api/themes/settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ key, value })
                });
                const data = await response.json();
                
                if (data.success) {
                    showToast('Setting updated!', 'success');
                } else {
                    showToast(data.error || 'Failed to update setting', 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        }
        
        // Toast notification
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = 'toast ' + type;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>
</html>
