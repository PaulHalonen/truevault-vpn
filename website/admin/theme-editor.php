<?php
/**
 * TrueVault VPN - Theme Editor
 * Part 8 - Task 8.4
 * Visual editor for customizing themes
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

// Check admin auth
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

$themeId = (int)($_GET['id'] ?? 0);

if (!$themeId) {
    header('Location: /admin/theme-manager.php');
    exit;
}

// Get theme
$db = new SQLite3(DB_THEMES);
$db->enableExceptions(true);

$stmt = $db->prepare("SELECT * FROM themes WHERE id = :id");
$stmt->bindValue(':id', $themeId, SQLITE3_INTEGER);
$result = $stmt->execute();
$theme = $result->fetchArray(SQLITE3_ASSOC);
$db->close();

if (!$theme) {
    header('Location: /admin/theme-manager.php');
    exit;
}

$colors = json_decode($theme['colors'], true);
$fonts = json_decode($theme['fonts'], true);
$spacing = json_decode($theme['spacing'], true);
$borders = json_decode($theme['borders'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Theme: <?= htmlspecialchars($theme['display_name']) ?> - TrueVault Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@400;600&family=Fira+Code&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f1a;
            color: #fff;
            min-height: 100vh;
        }
        
        /* Layout */
        .editor-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .editor-sidebar {
            background: #1a1a2e;
            border-right: 1px solid rgba(255,255,255,0.1);
            padding: 20px;
            overflow-y: auto;
            max-height: 100vh;
        }
        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header h2 {
            color: #00d9ff;
            font-size: 1.1rem;
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
        }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-danger { background: rgba(255,80,80,0.2); color: #ff5050; }
        .btn-small { padding: 6px 12px; font-size: 0.8rem; }
        
        /* Section */
        .editor-section {
            margin-bottom: 25px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            cursor: pointer;
        }
        .section-header h3 {
            color: #00ff88;
            font-size: 0.95rem;
        }
        .section-content {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        /* Color Input */
        .color-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .color-input label {
            flex: 1;
            color: #ccc;
            font-size: 0.85rem;
        }
        .color-input input[type="color"] {
            width: 40px;
            height: 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: transparent;
        }
        .color-input input[type="text"] {
            width: 80px;
            padding: 6px 8px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 4px;
            color: #fff;
            font-family: monospace;
            font-size: 0.85rem;
        }
        
        /* Select Input */
        .select-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .select-input label {
            flex: 1;
            color: #ccc;
            font-size: 0.85rem;
        }
        .select-input select {
            flex: 1;
            padding: 8px 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            color: #fff;
            font-size: 0.85rem;
        }
        
        /* Range Input */
        .range-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .range-input label {
            flex: 1;
            color: #ccc;
            font-size: 0.85rem;
        }
        .range-input input[type="range"] {
            flex: 1;
            -webkit-appearance: none;
            background: rgba(255,255,255,0.1);
            height: 6px;
            border-radius: 3px;
        }
        .range-input input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            background: #00d9ff;
            border-radius: 50%;
            cursor: pointer;
        }
        .range-input span {
            width: 50px;
            text-align: right;
            color: #888;
            font-size: 0.8rem;
        }
        
        /* Preview Panel */
        .preview-panel {
            background: #0a0a14;
            padding: 20px;
            overflow-y: auto;
        }
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .preview-header h2 { color: #fff; }
        
        /* Preview Frame */
        .preview-frame {
            background: var(--preview-bg, #fff);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        
        /* Preview Styles (applied dynamically) */
        .preview-frame .hero {
            background: linear-gradient(135deg, var(--preview-primary), var(--preview-secondary));
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .preview-frame .hero h1 {
            font-family: var(--preview-font-heading);
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        .preview-frame .hero p {
            opacity: 0.9;
            margin-bottom: 20px;
        }
        .preview-frame .hero .btn {
            display: inline-block;
            padding: 12px 30px;
            background: white;
            color: var(--preview-primary);
            border-radius: var(--preview-radius);
            font-weight: 600;
            text-decoration: none;
        }
        
        .preview-frame .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 30px;
            background: var(--preview-surface);
        }
        .preview-frame .feature-card {
            background: var(--preview-bg);
            padding: 20px;
            border-radius: var(--preview-radius);
            border: 1px solid var(--preview-border);
            text-align: center;
        }
        .preview-frame .feature-card .icon { font-size: 2rem; margin-bottom: 10px; }
        .preview-frame .feature-card h3 {
            font-family: var(--preview-font-heading);
            color: var(--preview-text);
            margin-bottom: 8px;
            font-size: 1rem;
        }
        .preview-frame .feature-card p {
            color: var(--preview-text-muted);
            font-size: 0.85rem;
        }
        
        .preview-frame .alerts {
            padding: 20px 30px;
            background: var(--preview-bg);
        }
        .preview-frame .alert {
            padding: 12px 15px;
            border-radius: var(--preview-radius);
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        .preview-frame .alert-success {
            background: color-mix(in srgb, var(--preview-success) 15%, transparent);
            border-left: 4px solid var(--preview-success);
            color: var(--preview-text);
        }
        .preview-frame .alert-warning {
            background: color-mix(in srgb, var(--preview-warning) 15%, transparent);
            border-left: 4px solid var(--preview-warning);
            color: var(--preview-text);
        }
        .preview-frame .alert-error {
            background: color-mix(in srgb, var(--preview-error) 15%, transparent);
            border-left: 4px solid var(--preview-error);
            color: var(--preview-text);
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
        
        @media (max-width: 1024px) {
            .editor-layout { grid-template-columns: 1fr; }
            .editor-sidebar { max-height: none; }
            .preview-frame .features { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="editor-layout">
        <!-- Sidebar -->
        <div class="editor-sidebar">
            <div class="sidebar-header">
                <h2>✏️ <?= htmlspecialchars($theme['display_name']) ?></h2>
                <a href="/admin/theme-manager.php" class="btn btn-secondary btn-small">← Back</a>
            </div>
            
            <!-- Colors Section -->
            <div class="editor-section">
                <div class="section-header">
                    <h3>🎨 Colors</h3>
                </div>
                <div class="section-content">
                    <?php 
                    $colorLabels = [
                        'primary' => 'Primary',
                        'secondary' => 'Secondary',
                        'accent' => 'Accent',
                        'background' => 'Background',
                        'surface' => 'Surface',
                        'text' => 'Text',
                        'text_muted' => 'Text Muted',
                        'border' => 'Border',
                        'success' => 'Success',
                        'warning' => 'Warning',
                        'error' => 'Error'
                    ];
                    foreach ($colorLabels as $key => $label): 
                    ?>
                    <div class="color-input">
                        <label><?= $label ?></label>
                        <input type="color" id="color-<?= $key ?>" value="<?= $colors[$key] ?>" onchange="updateColor('<?= $key ?>', this.value)">
                        <input type="text" id="color-<?= $key ?>-hex" value="<?= $colors[$key] ?>" onchange="updateColor('<?= $key ?>', this.value)">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Fonts Section -->
            <div class="editor-section">
                <div class="section-header">
                    <h3>🔤 Fonts</h3>
                </div>
                <div class="section-content">
                    <div class="select-input">
                        <label>Heading</label>
                        <select id="font-heading" onchange="updateFont('heading', this.value)">
                            <option value="Montserrat, sans-serif" <?= strpos($fonts['heading'], 'Montserrat') !== false ? 'selected' : '' ?>>Montserrat</option>
                            <option value="'Playfair Display', serif" <?= strpos($fonts['heading'], 'Playfair') !== false ? 'selected' : '' ?>>Playfair Display</option>
                            <option value="Poppins, sans-serif" <?= strpos($fonts['heading'], 'Poppins') !== false ? 'selected' : '' ?>>Poppins</option>
                            <option value="'Roboto Slab', serif" <?= strpos($fonts['heading'], 'Roboto Slab') !== false ? 'selected' : '' ?>>Roboto Slab</option>
                            <option value="Inter, sans-serif" <?= strpos($fonts['heading'], 'Inter') !== false ? 'selected' : '' ?>>Inter</option>
                        </select>
                    </div>
                    <div class="select-input">
                        <label>Body</label>
                        <select id="font-body" onchange="updateFont('body', this.value)">
                            <option value="'Open Sans', sans-serif" <?= strpos($fonts['body'], 'Open Sans') !== false ? 'selected' : '' ?>>Open Sans</option>
                            <option value="Roboto, sans-serif" <?= strpos($fonts['body'], 'Roboto') !== false ? 'selected' : '' ?>>Roboto</option>
                            <option value="Lato, sans-serif" <?= strpos($fonts['body'], 'Lato') !== false ? 'selected' : '' ?>>Lato</option>
                            <option value="'Source Sans Pro', sans-serif" <?= strpos($fonts['body'], 'Source Sans') !== false ? 'selected' : '' ?>>Source Sans Pro</option>
                            <option value="Inter, sans-serif" <?= strpos($fonts['body'], 'Inter') !== false ? 'selected' : '' ?>>Inter</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Borders Section -->
            <div class="editor-section">
                <div class="section-header">
                    <h3>📐 Border Radius</h3>
                </div>
                <div class="section-content">
                    <div class="range-input">
                        <label>Small</label>
                        <input type="range" id="radius-sm" min="0" max="16" value="<?= (int)$borders['radius_sm'] ?>" onchange="updateBorder('radius_sm', this.value)">
                        <span id="radius-sm-val"><?= $borders['radius_sm'] ?></span>
                    </div>
                    <div class="range-input">
                        <label>Medium</label>
                        <input type="range" id="radius-md" min="0" max="24" value="<?= (int)$borders['radius_md'] ?>" onchange="updateBorder('radius_md', this.value)">
                        <span id="radius-md-val"><?= $borders['radius_md'] ?></span>
                    </div>
                    <div class="range-input">
                        <label>Large</label>
                        <input type="range" id="radius-lg" min="0" max="32" value="<?= (int)$borders['radius_lg'] ?>" onchange="updateBorder('radius_lg', this.value)">
                        <span id="radius-lg-val"><?= $borders['radius_lg'] ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="editor-section">
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button class="btn btn-primary" onclick="saveTheme()">💾 Save Changes</button>
                    <button class="btn btn-secondary" onclick="resetTheme()">↩️ Reset</button>
                    <a href="/preview-theme.php?id=<?= $themeId ?>" target="_blank" class="btn btn-secondary">👁️ Full Preview</a>
                </div>
            </div>
        </div>
        
        <!-- Preview Panel -->
        <div class="preview-panel">
            <div class="preview-header">
                <h2>Live Preview</h2>
            </div>
            
            <div class="preview-frame" id="previewFrame">
                <div class="hero">
                    <h1>Welcome to TrueVault VPN</h1>
                    <p>Your Complete Digital Fortress</p>
                    <a href="#" class="btn">Get Started</a>
                </div>
                
                <div class="features">
                    <div class="feature-card">
                        <div class="icon">🔐</div>
                        <h3>Secure</h3>
                        <p>Military-grade 256-bit encryption</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">⚡</div>
                        <h3>Fast</h3>
                        <p>Lightning-fast servers worldwide</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">👻</div>
                        <h3>Private</h3>
                        <p>Zero-log policy guaranteed</p>
                    </div>
                </div>
                
                <div class="alerts">
                    <div class="alert alert-success">✓ Success alert message</div>
                    <div class="alert alert-warning">⚠ Warning alert message</div>
                    <div class="alert alert-error">✕ Error alert message</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const themeId = <?= $themeId ?>;
        let currentColors = <?= json_encode($colors) ?>;
        let currentFonts = <?= json_encode($fonts) ?>;
        let currentBorders = <?= json_encode($borders) ?>;
        
        // Update preview CSS variables
        function updatePreview() {
            const frame = document.getElementById('previewFrame');
            
            // Colors
            frame.style.setProperty('--preview-primary', currentColors.primary);
            frame.style.setProperty('--preview-secondary', currentColors.secondary);
            frame.style.setProperty('--preview-accent', currentColors.accent);
            frame.style.setProperty('--preview-bg', currentColors.background);
            frame.style.setProperty('--preview-surface', currentColors.surface);
            frame.style.setProperty('--preview-text', currentColors.text);
            frame.style.setProperty('--preview-text-muted', currentColors.text_muted);
            frame.style.setProperty('--preview-border', currentColors.border);
            frame.style.setProperty('--preview-success', currentColors.success);
            frame.style.setProperty('--preview-warning', currentColors.warning);
            frame.style.setProperty('--preview-error', currentColors.error);
            
            // Fonts
            frame.style.setProperty('--preview-font-heading', currentFonts.heading);
            frame.style.setProperty('--preview-font-body', currentFonts.body);
            
            // Borders
            frame.style.setProperty('--preview-radius', currentBorders.radius_md);
        }
        
        // Update color
        function updateColor(key, value) {
            // Validate hex color
            if (!/^#[0-9A-Fa-f]{6}$/.test(value)) return;
            
            currentColors[key] = value;
            document.getElementById('color-' + key).value = value;
            document.getElementById('color-' + key + '-hex').value = value;
            updatePreview();
        }
        
        // Update font
        function updateFont(key, value) {
            currentFonts[key] = value;
            updatePreview();
        }
        
        // Update border
        function updateBorder(key, value) {
            currentBorders[key] = value + 'px';
            document.getElementById(key.replace('_', '-') + '-val').textContent = value + 'px';
            updatePreview();
        }
        
        // Save theme
        async function saveTheme() {
            try {
                const response = await fetch('/api/themes/update.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        theme_id: themeId,
                        colors: currentColors,
                        fonts: currentFonts,
                        borders: currentBorders
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Theme saved successfully!', 'success');
                } else {
                    showToast(data.error || 'Failed to save theme', 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        }
        
        // Reset theme
        function resetTheme() {
            if (confirm('Reset all changes? This will reload the page.')) {
                location.reload();
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
        
        // Initialize preview
        updatePreview();
    </script>
</body>
</html>
