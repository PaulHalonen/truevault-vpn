<?php
/**
 * TrueVault VPN - Theme Manager
 * 
 * Admin interface for managing themes and colors
 * 
 * FEATURES:
 * - View all 12 themes
 * - Switch active theme
 * - Edit theme colors
 * - Preview themes
 * - Enable/disable seasonal switching
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Initialize
define('TRUEVAULT_INIT', true);
session_start();

require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Theme.php';
require_once __DIR__ . '/../includes/Content.php';

// Check authentication
if (!Auth::isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'switch_theme':
            $themeId = intval($_POST['theme_id'] ?? 0);
            $success = Theme::switchTheme($themeId);
            echo json_encode(['success' => $success]);
            exit;
            
        case 'toggle_seasonal':
            $enabled = $_POST['enabled'] === 'true' ? '1' : '0';
            $success = Content::set('enable_seasonal_themes', $enabled, 'boolean', 'branding');
            echo json_encode(['success' => $success]);
            exit;
            
        case 'update_colors':
            $themeId = intval($_POST['theme_id'] ?? 0);
            $colors = json_decode($_POST['colors'] ?? '{}', true);
            
            require_once __DIR__ . '/../includes/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $themesConn->beginTransaction();
            
            try {
                $stmt = $themesConn->prepare("
                    UPDATE theme_colors 
                    SET color_value = ?
                    WHERE theme_id = ? AND color_key = ?
                ");
                
                foreach ($colors as $key => $value) {
                    $stmt->execute([$value, $themeId, $key]);
                }
                
                $themesConn->commit();
                
                // Clear cache
                Theme::clearCache();
                
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $themesConn->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
    }
}

// Get all themes
$themes = Theme::listThemes();
$activeTheme = Theme::getActiveTheme();
$seasonalEnabled = Content::get('enable_seasonal_themes', '0') === '1';
$currentSeason = Theme::getCurrentSeason();

// Page title
$pageTitle = 'Theme Manager';
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
            max-width: 1400px;
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
        
        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .seasonal-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .seasonal-toggle label {
            font-weight: 600;
            color: #666;
            cursor: pointer;
        }
        
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
            cursor: pointer;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            border-radius: 26px;
            transition: 0.3s;
        }
        
        .toggle-slider:before {
            content: "";
            position: absolute;
            height: 18px;
            width: 18px;
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
            transform: translateX(24px);
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
        
        .themes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .theme-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: 3px solid transparent;
        }
        
        .theme-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .theme-card.active {
            border-color: #10b981;
        }
        
        .theme-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .theme-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }
        
        .theme-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-seasonal {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-vip {
            background: #fef3c7;
            color: #78350f;
        }
        
        .theme-meta {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .color-palette {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .color-swatch {
            aspect-ratio: 1;
            border-radius: 6px;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .color-swatch:hover {
            transform: scale(1.1);
            border-color: #667eea;
        }
        
        .theme-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
            flex: 1;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .season-indicator {
            display: inline-block;
            padding: 6px 12px;
            background: #f0fdf4;
            color: #15803d;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            padding: 20px;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .modal-header h2 {
            font-size: 24px;
            color: #333;
        }
        
        .close-modal {
            font-size: 28px;
            cursor: pointer;
            color: #999;
            line-height: 1;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
        .color-editor {
            display: grid;
            gap: 15px;
        }
        
        .color-input-group {
            display: grid;
            grid-template-columns: 150px 1fr 80px;
            gap: 12px;
            align-items: center;
        }
        
        .color-input-group label {
            font-weight: 600;
            color: #666;
        }
        
        .color-input-group input[type="color"] {
            width: 100%;
            height: 40px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .color-input-group input[type="text"] {
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: monospace;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>🎨 Theme Manager</h1>
                <p style="color: #666; margin-top: 5px;">Current Season: <strong><?= ucfirst($currentSeason) ?></strong></p>
            </div>
            <div class="header-actions">
                <div class="seasonal-toggle">
                    <label for="seasonal-switch">Seasonal Auto-Switch</label>
                    <label class="toggle-switch">
                        <input type="checkbox" id="seasonal-switch" <?= $seasonalEnabled ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <a href="/admin/" class="btn btn-primary">← Back to Admin</a>
            </div>
        </div>

        <div class="themes-grid">
            <?php foreach ($themes as $theme): 
                $isActive = $theme['id'] == $activeTheme['id'];
                $colors = Theme::getAllColors($theme['id']);
            ?>
            <div class="theme-card <?= $isActive ? 'active' : '' ?>">
                <div class="theme-header">
                    <div class="theme-title"><?= htmlspecialchars($theme['display_name']) ?></div>
                    <?php if ($isActive): ?>
                        <span class="theme-badge badge-active">✓ Active</span>
                    <?php elseif ($theme['is_seasonal']): ?>
                        <span class="theme-badge badge-seasonal">🍂 Seasonal</span>
                    <?php elseif ($theme['name'] === 'vip_gold'): ?>
                        <span class="theme-badge badge-vip">⭐ VIP</span>
                    <?php endif; ?>
                </div>
                
                <div class="theme-meta">
                    <strong>Style:</strong> <?= ucfirst($theme['style']) ?>
                    <?php if ($theme['is_seasonal']): ?>
                        | <strong>Season:</strong> <?= ucfirst($theme['season']) ?>
                    <?php endif; ?>
                </div>
                
                <div class="color-palette">
                    <?php foreach (array_slice($colors, 0, 6) as $key => $color): ?>
                        <div class="color-swatch" 
                             style="background: <?= htmlspecialchars($color) ?>"
                             title="<?= htmlspecialchars($key) ?>: <?= htmlspecialchars($color) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="theme-actions">
                    <?php if (!$isActive): ?>
                        <button class="btn btn-sm btn-success" onclick="switchTheme(<?= $theme['id'] ?>)">
                            ✓ Activate
                        </button>
                    <?php else: ?>
                        <button class="btn btn-sm btn-secondary" disabled>
                            Active Theme
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-secondary" onclick="editColors(<?= $theme['id'] ?>, '<?= htmlspecialchars($theme['display_name']) ?>')">
                        🎨 Edit Colors
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Color Editor Modal -->
    <div id="colorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Theme Colors</h2>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div id="colorEditor" class="color-editor"></div>
            <div style="margin-top: 25px; display: flex; gap: 10px;">
                <button class="btn btn-primary" onclick="saveColors()">💾 Save Changes</button>
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let currentThemeId = null;
        let originalColors = {};
        
        // Toggle seasonal switching
        document.getElementById('seasonal-switch').addEventListener('change', function() {
            const enabled = this.checked;
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=toggle_seasonal&enabled=${enabled}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(enabled ? 'Seasonal auto-switching enabled!' : 'Seasonal auto-switching disabled.');
                }
            });
        });
        
        // Switch theme
        function switchTheme(themeId) {
            if (!confirm('Switch to this theme?')) return;
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=switch_theme&theme_id=${themeId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to switch theme');
                }
            });
        }
        
        // Edit colors
        function editColors(themeId, themeName) {
            currentThemeId = themeId;
            
            // Get all colors for this theme
            fetch(`/api/themes/get-colors.php?theme_id=${themeId}`)
                .then(r => r.json())
                .then(data => {
                    originalColors = data.colors;
                    renderColorEditor(themeName, data.colors);
                    document.getElementById('colorModal').classList.add('active');
                });
        }
        
        // Render color editor
        function renderColorEditor(themeName, colors) {
            const editor = document.getElementById('colorEditor');
            editor.innerHTML = `<p style="color: #666; margin-bottom: 20px;">Editing: <strong>${themeName}</strong></p>`;
            
            const colorNames = {
                'primary': 'Primary Color',
                'secondary': 'Secondary Color',
                'accent': 'Accent Color',
                'background': 'Background',
                'surface': 'Surface',
                'text_primary': 'Text Primary',
                'text_secondary': 'Text Secondary',
                'success': 'Success',
                'warning': 'Warning',
                'error': 'Error',
                'info': 'Info'
            };
            
            for (const [key, value] of Object.entries(colors)) {
                editor.innerHTML += `
                    <div class="color-input-group">
                        <label>${colorNames[key] || key}</label>
                        <input type="color" 
                               id="color_${key}" 
                               value="${value}"
                               onchange="updateColorText('${key}')">
                        <input type="text" 
                               id="text_${key}" 
                               value="${value}"
                               maxlength="7"
                               pattern="#[0-9A-Fa-f]{6}"
                               onchange="updateColorPicker('${key}')">
                    </div>
                `;
            }
        }
        
        // Update color text when picker changes
        function updateColorText(key) {
            const picker = document.getElementById(`color_${key}`);
            const text = document.getElementById(`text_${key}`);
            text.value = picker.value;
        }
        
        // Update color picker when text changes
        function updateColorPicker(key) {
            const text = document.getElementById(`text_${key}`);
            const picker = document.getElementById(`color_${key}`);
            if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) {
                picker.value = text.value;
            }
        }
        
        // Save colors
        function saveColors() {
            const colors = {};
            
            for (const key of Object.keys(originalColors)) {
                colors[key] = document.getElementById(`text_${key}`).value;
            }
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_colors&theme_id=${currentThemeId}&colors=${encodeURIComponent(JSON.stringify(colors))}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Colors updated successfully!');
                    location.reload();
                } else {
                    alert('Failed to update colors: ' + (data.error || 'Unknown error'));
                }
            });
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('colorModal').classList.remove('active');
        }
    </script>
</body>
</html>
