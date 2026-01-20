# MASTER CHECKLIST - PART 8: THEME SYSTEM + VISUAL EDITOR

**Created:** January 18, 2026 - 11:35 PM CST  
**Updated:** January 20, 2026 - User Decision #6 Applied
**Status:** ⏳ NOT STARTED  
**Priority:** 🟡 HIGH - Business Transfer Requirement  
**Estimated Time:** 15-18 hours (increased for 20+ themes + GrapesJS + React)

---

## 📋 OVERVIEW

Build complete theme management system with 20+ pre-built themes, visual editor (GrapesJS), and React preview components.

**CRITICAL USER DECISION:**
Theme system MUST include:
1. ✅ 20+ pre-built themes (NOT just 3-4)
2. ✅ GrapesJS visual editor (drag-and-drop)
3. ✅ React preview components (live theme preview)
4. ✅ Seasonal auto-switching
5. ✅ Holiday themes
6. ✅ Complete customization via GUI

**Why This Matters:**
- New owner changes theme in 30 seconds
- NO coding required
- Seasonal themes auto-switch
- Visual editor for customization
- Professional appearance instantly

---

## 📊 THEME CATEGORIES (20+ THEMES)

### **Seasonal Themes (4):**
1. **Winter Frost** - Cool blues, whites, icy tones
2. **Summer Breeze** - Warm yellows, oranges, sunny
3. **Autumn Harvest** - Browns, oranges, earthy
4. **Spring Bloom** - Greens, pinks, pastels

### **Holiday Themes (8):**
1. **Christmas Joy** - Red, green, gold
2. **Thanksgiving Warmth** - Orange, brown, cream
3. **Halloween Spooky** - Orange, black, purple
4. **Easter Pastel** - Pink, blue, yellow
5. **Valentine Romance** - Red, pink, white
6. **Independence Day** - Red, white, blue
7. **New Year Celebration** - Gold, silver, black
8. **St. Patrick's Day** - Green, gold, white

### **Standard Professional (4):**
1. **Professional Blue** - Corporate, trustworthy
2. **Modern Dark** - Sleek, contemporary
3. **Classic Light** - Timeless, elegant
4. **Minimal White** - Clean, spacious

### **Color Scheme Themes (4):**
1. **Ocean Blue** - Deep blues, aqua accents
2. **Forest Green** - Natural greens, earth tones
3. **Royal Purple** - Rich purples, gold accents
4. **Sunset Orange** - Warm oranges, coral

**Total:** 20 pre-built themes

---

## 💾 TASK 8.1: Create Theme Database Schema

**Time:** 45 minutes  
**File:** `/databases/setup-themes.php`

**Create themes table in admin.db:**

```sql
CREATE TABLE IF NOT EXISTS themes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_name TEXT NOT NULL UNIQUE,
    display_name TEXT NOT NULL,
    category TEXT NOT NULL,              -- 'seasonal', 'holiday', 'standard', 'color'
    season TEXT,                          -- 'winter', 'spring', 'summer', 'fall'
    style TEXT DEFAULT 'light',          -- 'light', 'medium', 'dark'
    colors TEXT NOT NULL,                 -- JSON: {primary, secondary, accent, etc.}
    fonts TEXT NOT NULL,                  -- JSON: {heading, body, mono}
    spacing TEXT NOT NULL,                -- JSON: {xs, sm, md, lg, xl}
    borders TEXT NOT NULL,                -- JSON: {radius_sm, radius_md, radius_lg, width}
    shadows TEXT NOT NULL,                -- JSON: {sm, md, lg}
    preview_image TEXT,                   -- Path to preview screenshot
    is_active INTEGER DEFAULT 0,
    is_seasonal INTEGER DEFAULT 0,        -- Auto-switch based on season?
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS theme_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);
```

**Insert Theme Settings:**
```sql
INSERT INTO theme_settings (setting_key, setting_value) VALUES
('enable_seasonal_switching', '1'),
('current_season', 'winter'),
('default_theme', 'professional_blue');
```

**Verification:**
- [ ] themes table created
- [ ] theme_settings table created
- [ ] Settings seeded

---

## 🎨 TASK 8.2: Seed 20+ Pre-Built Themes

**Time:** 2 hours  
**File:** `/databases/seed-themes.php`

**Insert ALL 20 themes with complete JSON configs:**

### **Example Theme Data Structure:**
```php
<?php
$themes = [
    // SEASONAL THEMES (4)
    [
        'theme_name' => 'winter_frost',
        'display_name' => 'Winter Frost',
        'category' => 'seasonal',
        'season' => 'winter',
        'style' => 'light',
        'colors' => json_encode([
            'primary' => '#3B82F6',      // Blue
            'secondary' => '#60A5FA',    // Light blue
            'accent' => '#93C5FD',       // Ice blue
            'background' => '#F0F9FF',   // Very light blue
            'surface' => '#FFFFFF',      // White
            'text' => '#1E3A8A',        // Dark blue
            'text_light' => '#3B82F6',  // Blue
            'border' => '#DBEAFE',       // Light blue border
            'success' => '#10B981',
            'warning' => '#F59E0B',
            'danger' => '#EF4444'
        ]),
        'fonts' => json_encode([
            'heading' => 'Poppins, sans-serif',
            'body' => 'Inter, sans-serif',
            'mono' => 'Fira Code, monospace'
        ]),
        'spacing' => json_encode([
            'xs' => '4px',
            'sm' => '8px',
            'md' => '16px',
            'lg' => '24px',
            'xl' => '48px'
        ]),
        'borders' => json_encode([
            'radius_sm' => '4px',
            'radius_md' => '8px',
            'radius_lg' => '16px',
            'width' => '1px'
        ]),
        'shadows' => json_encode([
            'sm' => '0 1px 3px rgba(0,0,0,0.12)',
            'md' => '0 4px 6px rgba(0,0,0,0.1)',
            'lg' => '0 10px 15px rgba(0,0,0,0.1)'
        ]),
        'is_seasonal' => 1
    ],
    
    // SUMMER BREEZE
    [
        'theme_name' => 'summer_breeze',
        'display_name' => 'Summer Breeze',
        'category' => 'seasonal',
        'season' => 'summer',
        'style' => 'light',
        'colors' => json_encode([
            'primary' => '#F59E0B',      // Orange
            'secondary' => '#FBBF24',    // Yellow
            'accent' => '#FCD34D',       // Light yellow
            'background' => '#FFFBEB',   // Cream
            'surface' => '#FFFFFF',
            'text' => '#78350F',        // Brown
            'text_light' => '#92400E',
            'border' => '#FEF3C7',
            'success' => '#10B981',
            'warning' => '#F59E0B',
            'danger' => '#EF4444'
        ]),
        'fonts' => json_encode([
            'heading' => 'Poppins, sans-serif',
            'body' => 'Inter, sans-serif',
            'mono' => 'Fira Code, monospace'
        ]),
        'spacing' => json_encode([
            'xs' => '4px',
            'sm' => '8px',
            'md' => '16px',
            'lg' => '24px',
            'xl' => '48px'
        ]),
        'borders' => json_encode([
            'radius_sm' => '4px',
            'radius_md' => '8px',
            'radius_lg' => '16px',
            'width' => '1px'
        ]),
        'shadows' => json_encode([
            'sm' => '0 1px 3px rgba(0,0,0,0.12)',
            'md' => '0 4px 6px rgba(0,0,0,0.1)',
            'lg' => '0 10px 15px rgba(0,0,0,0.1)'
        ]),
        'is_seasonal' => 1
    ],
    
    // AUTUMN HARVEST
    [
        'theme_name' => 'autumn_harvest',
        'display_name' => 'Autumn Harvest',
        'category' => 'seasonal',
        'season' => 'fall',
        'style' => 'medium',
        'colors' => json_encode([
            'primary' => '#D97706',      // Dark orange
            'secondary' => '#B45309',    // Brown
            'accent' => '#F59E0B',       // Orange
            'background' => '#FEF3C7',   // Light cream
            'surface' => '#FFFBEB',
            'text' => '#78350F',
            'text_light' => '#92400E',
            'border' => '#FDE68A',
            'success' => '#10B981',
            'warning' => '#F59E0B',
            'danger' => '#EF4444'
        ]),
        'fonts' => json_encode([
            'heading' => 'Poppins, sans-serif',
            'body' => 'Inter, sans-serif',
            'mono' => 'Fira Code, monospace'
        ]),
        'spacing' => json_encode([
            'xs' => '4px',
            'sm' => '8px',
            'md' => '16px',
            'lg' => '24px',
            'xl' => '48px'
        ]),
        'borders' => json_encode([
            'radius_sm' => '4px',
            'radius_md' => '8px',
            'radius_lg' => '16px',
            'width' => '1px'
        ]),
        'shadows' => json_encode([
            'sm' => '0 1px 3px rgba(0,0,0,0.12)',
            'md' => '0 4px 6px rgba(0,0,0,0.1)',
            'lg' => '0 10px 15px rgba(0,0,0,0.1)'
        ]),
        'is_seasonal' => 1
    ],
    
    // SPRING BLOOM
    [
        'theme_name' => 'spring_bloom',
        'display_name' => 'Spring Bloom',
        'category' => 'seasonal',
        'season' => 'spring',
        'style' => 'light',
        'colors' => json_encode([
            'primary' => '#10B981',      // Green
            'secondary' => '#34D399',    // Light green
            'accent' => '#F472B6',       // Pink
            'background' => '#F0FDF4',   // Very light green
            'surface' => '#FFFFFF',
            'text' => '#065F46',        // Dark green
            'text_light' => '#059669',
            'border' => '#D1FAE5',
            'success' => '#10B981',
            'warning' => '#F59E0B',
            'danger' => '#EF4444'
        ]),
        'fonts' => json_encode([
            'heading' => 'Poppins, sans-serif',
            'body' => 'Inter, sans-serif',
            'mono' => 'Fira Code, monospace'
        ]),
        'spacing' => json_encode([
            'xs' => '4px',
            'sm' => '8px',
            'md' => '16px',
            'lg' => '24px',
            'xl' => '48px'
        ]),
        'borders' => json_encode([
            'radius_sm' => '4px',
            'radius_md' => '8px',
            'radius_lg' => '16px',
            'width' => '1px'
        ]),
        'shadows' => json_encode([
            'sm' => '0 1px 3px rgba(0,0,0,0.12)',
            'md' => '0 4px 6px rgba(0,0,0,0.1)',
            'lg' => '0 10px 15px rgba(0,0,0,0.1)'
        ]),
        'is_seasonal' => 1
    ],
    
    // HOLIDAY THEMES (8) - Christmas
    [
        'theme_name' => 'christmas_joy',
        'display_name' => 'Christmas Joy',
        'category' => 'holiday',
        'season' => null,
        'style' => 'medium',
        'colors' => json_encode([
            'primary' => '#DC2626',      // Red
            'secondary' => '#059669',    // Green
            'accent' => '#F59E0B',       // Gold
            'background' => '#FEF2F2',   // Light red
            'surface' => '#FFFFFF',
            'text' => '#7F1D1D',
            'text_light' => '#991B1B',
            'border' => '#FECACA',
            'success' => '#059669',
            'warning' => '#F59E0B',
            'danger' => '#DC2626'
        ]),
        'fonts' => json_encode([
            'heading' => 'Poppins, sans-serif',
            'body' => 'Inter, sans-serif',
            'mono' => 'Fira Code, monospace'
        ]),
        'spacing' => json_encode([
            'xs' => '4px',
            'sm' => '8px',
            'md' => '16px',
            'lg' => '24px',
            'xl' => '48px'
        ]),
        'borders' => json_encode([
            'radius_sm' => '4px',
            'radius_md' => '8px',
            'radius_lg' => '16px',
            'width' => '1px'
        ]),
        'shadows' => json_encode([
            'sm' => '0 1px 3px rgba(0,0,0,0.12)',
            'md' => '0 4px 6px rgba(0,0,0,0.1)',
            'lg' => '0 10px 15px rgba(0,0,0,0.1)'
        ]),
        'is_seasonal' => 0
    ],
    
    // Continue for ALL 20 themes...
    // (I'll include the seed script that generates all 20)
];

// Insert all themes
foreach ($themes as $theme) {
    $db->insert('themes', $theme);
}
```

**Verification:**
- [ ] All 20 themes inserted
- [ ] Each theme has complete JSON config
- [ ] Preview images generated
- [ ] Can query themes by category

---

## 🖼️ TASK 8.3: Theme Manager Admin Interface

**Time:** 3 hours  
**File:** `/admin/themes.php`

**Complete theme management dashboard:**

```php
<?php
require_once '../includes/auth.php';
requireAdmin();

$db = new Database();

// Get all themes
$themes = $db->query("SELECT * FROM themes ORDER BY category, display_name")->fetchAll();
$activeTheme = $db->query("SELECT * FROM themes WHERE is_active = 1 LIMIT 1")->fetch();
$settings = $db->query("SELECT * FROM theme_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Theme Manager</title>
    <style>
    /* Theme manager styles */
    .themes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px;
    }
    
    .theme-card {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 15px;
        background: white;
        transition: all 0.3s;
    }
    
    .theme-card:hover {
        border-color: #3B82F6;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .theme-card.active {
        border-color: #10B981;
        background: #F0FDF4;
    }
    
    .theme-preview {
        width: 100%;
        height: 180px;
        border-radius: 8px;
        margin-bottom: 10px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }
    
    .theme-meta {
        display: flex;
        gap: 8px;
        margin-top: 10px;
    }
    
    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-seasonal {
        background: #DBEAFE;
        color: #1E40AF;
    }
    
    .badge-holiday {
        background: #FEE2E2;
        color: #991B1B;
    }
    
    .badge-active {
        background: #D1FAE5;
        color: #065F46;
    }
    </style>
</head>
<body>

<div class="admin-container">
    <h1>Theme Manager</h1>
    
    <!-- Seasonal Switching Toggle -->
    <div class="seasonal-toggle">
        <label>
            <input type="checkbox" id="enableSeasonal" 
                   <?= $settings['enable_seasonal_switching'] ? 'checked' : '' ?>>
            Automatically switch themes based on season
        </label>
        <p>Current season: <strong><?= ucfirst($settings['current_season']) ?></strong></p>
    </div>
    
    <!-- Active Theme -->
    <div class="active-theme-section">
        <h2>Active Theme</h2>
        <div class="theme-card active">
            <div class="theme-preview" style="<?= generatePreviewStyle($activeTheme) ?>"></div>
            <h3><?= $activeTheme['display_name'] ?></h3>
            <div class="theme-meta">
                <span class="badge badge-active">✓ Active</span>
                <?php if ($activeTheme['is_seasonal']): ?>
                    <span class="badge badge-seasonal">🍂 <?= ucfirst($activeTheme['season']) ?></span>
                <?php endif; ?>
            </div>
            <button onclick="editTheme(<?= $activeTheme['id'] ?>)">Edit Colors</button>
            <button onclick="customizeTheme(<?= $activeTheme['id'] ?>)">Visual Editor</button>
        </div>
    </div>
    
    <!-- All Themes Grid -->
    <h2>All Themes (<?= count($themes) ?>)</h2>
    
    <div class="category-tabs">
        <button data-category="all" class="active">All (<?= count($themes) ?>)</button>
        <button data-category="seasonal">Seasonal (4)</button>
        <button data-category="holiday">Holidays (8)</button>
        <button data-category="standard">Standard (4)</button>
        <button data-category="color">Colors (4)</button>
    </div>
    
    <div class="themes-grid">
        <?php foreach ($themes as $theme): ?>
        <div class="theme-card" data-category="<?= $theme['category'] ?>">
            <div class="theme-preview" style="<?= generatePreviewStyle($theme) ?>"></div>
            <h3><?= $theme['display_name'] ?></h3>
            
            <div class="theme-meta">
                <?php if ($theme['category'] === 'seasonal'): ?>
                    <span class="badge badge-seasonal">🍂 <?= ucfirst($theme['season']) ?></span>
                <?php elseif ($theme['category'] === 'holiday'): ?>
                    <span class="badge badge-holiday">🎉 Holiday</span>
                <?php endif; ?>
                
                <span class="badge" style="background: #F3F4F6; color: #374151;">
                    <?= ucfirst($theme['style']) ?>
                </span>
            </div>
            
            <div class="theme-actions">
                <button onclick="previewTheme(<?= $theme['id'] ?>)">Preview</button>
                <button onclick="activateTheme(<?= $theme['id'] ?>)">Activate</button>
                <button onclick="editTheme(<?= $theme['id'] ?>)">Edit</button>
                <button onclick="customizeTheme(<?= $theme['id'] ?>)">Customize</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Category filtering
document.querySelectorAll('.category-tabs button').forEach(btn => {
    btn.addEventListener('click', (e) => {
        const category = e.target.dataset.category;
        
        // Update active tab
        document.querySelectorAll('.category-tabs button').forEach(b => b.classList.remove('active'));
        e.target.classList.add('active');
        
        // Filter cards
        document.querySelectorAll('.theme-card').forEach(card => {
            if (category === 'all' || card.dataset.category === category) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
});

// Preview theme
function previewTheme(themeId) {
    window.open(`/preview-theme.php?id=${themeId}`, '_blank');
}

// Activate theme
async function activateTheme(themeId) {
    if (!confirm('Activate this theme? This will change the site appearance immediately.')) return;
    
    const response = await fetch('/api/themes/activate.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({theme_id: themeId})
    });
    
    const data = await response.json();
    if (data.success) {
        alert('Theme activated!');
        location.reload();
    }
}

// Edit theme colors
function editTheme(themeId) {
    window.location.href = `/admin/theme-editor.php?id=${themeId}`;
}

// Visual customizer (GrapesJS)
function customizeTheme(themeId) {
    window.location.href = `/admin/theme-customizer.php?id=${themeId}`;
}

// Seasonal toggle
document.getElementById('enableSeasonal').addEventListener('change', async (e) => {
    const response = await fetch('/api/themes/settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            enable_seasonal_switching: e.target.checked ? 1 : 0
        })
    });
    
    const data = await response.json();
    if (data.success) {
        alert('Seasonal switching ' + (e.target.checked ? 'enabled' : 'disabled'));
    }
});
</script>

</body>
</html>

<?php
function generatePreviewStyle($theme) {
    $colors = json_decode($theme['colors'], true);
    return sprintf(
        "background: linear-gradient(135deg, %s, %s);",
        $colors['primary'],
        $colors['secondary']
    );
}
?>
```

**Verification:**
- [ ] Theme grid displays 20 themes
- [ ] Category tabs filter correctly
- [ ] Preview opens in new tab
- [ ] Activate button works
- [ ] Seasonal toggle functional

---

## 🎨 TASK 8.4: GrapesJS Visual Theme Editor

**Time:** 4 hours  
**File:** `/admin/theme-customizer.php`

**Integrate GrapesJS for visual theme editing:**

```html
<!DOCTYPE html>
<html>
<head>
    <title>Theme Customizer - GrapesJS</title>
    
    <!-- GrapesJS CDN -->
    <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
    <script src="https://unpkg.com/grapesjs"></script>
    
    <!-- GrapesJS Plugins -->
    <script src="https://unpkg.com/grapesjs-preset-webpage"></script>
    
    <style>
    body, html {
        margin: 0;
        padding: 0;
        height: 100%;
    }
    
    #gjs {
        border: 3px solid #444;
        height: 100vh;
        width: 100%;
    }
    
    .gjs-one-bg {
        background-color: #1f2937;
    }
    
    .gjs-two-color {
        color: #e5e7eb;
    }
    </style>
</head>
<body>

<div id="gjs"></div>

<script>
const themeId = <?= $_GET['id'] ?? 0 ?>;

// Load current theme
let currentTheme;
fetch(`/api/themes/get.php?id=${themeId}`)
    .then(r => r.json())
    .then(data => {
        currentTheme = data.theme;
        initializeEditor();
    });

function initializeEditor() {
    const editor = grapesjs.init({
        container: '#gjs',
        height: '100%',
        width: 'auto',
        
        plugins: ['gjs-preset-webpage'],
        pluginsOpts: {
            'gjs-preset-webpage': {}
        },
        
        storageManager: {
            type: 'remote',
            autosave: true,
            autoload: true,
            stepsBeforeSave: 1,
            
            // Custom storage
            urlStore: `/api/themes/save-design.php?id=${themeId}`,
            urlLoad: `/api/themes/load-design.php?id=${themeId}`,
            
            headers: {
                'Content-Type': 'application/json'
            }
        },
        
        assetManager: {
            upload: `/api/themes/upload-asset.php?id=${themeId}`,
            uploadName: 'files',
            assets: currentTheme.assets || []
        },
        
        styleManager: {
            sectors: [
                {
                    name: 'Theme Colors',
                    open: true,
                    buildProps: ['primary-color', 'secondary-color', 'accent-color', 'background-color', 'text-color']
                },
                {
                    name: 'Typography',
                    open: false,
                    buildProps: ['font-family', 'font-size', 'font-weight', 'letter-spacing', 'line-height']
                },
                {
                    name: 'Spacing',
                    open: false,
                    buildProps: ['margin', 'padding']
                },
                {
                    name: 'Borders',
                    open: false,
                    buildProps: ['border-radius', 'border', 'box-shadow']
                }
            ]
        },
        
        canvas: {
            styles: [
                '/assets/css/theme-base.css'
            ],
            scripts: [
                '/assets/js/theme-preview.js'
            ]
        }
    });
    
    // Add custom theme properties
    editor.StyleManager.addProperty('Theme Colors', {
        name: 'Primary Color',
        property: '--primary',
        type: 'color',
        defaults: currentTheme.colors.primary
    });
    
    editor.StyleManager.addProperty('Theme Colors', {
        name: 'Secondary Color',
        property: '--secondary',
        type: 'color',
        defaults: currentTheme.colors.secondary
    });
    
    // Add more properties for all theme variables...
    
    // Save button
    editor.Panels.addButton('options', {
        id: 'save-theme',
        className: 'fa fa-floppy-o',
        command: 'save-theme',
        attributes: { title: 'Save Theme' }
    });
    
    editor.Commands.add('save-theme', {
        run(editor, sender) {
            sender.set('active');
            editor.store();
            alert('Theme saved!');
            sender.set('active', false);
        }
    });
    
    // Apply theme button
    editor.Panels.addButton('options', {
        id: 'apply-theme',
        className: 'fa fa-check',
        command: 'apply-theme',
        attributes: { title: 'Apply to Site' }
    });
    
    editor.Commands.add('apply-theme', {
        run(editor, sender) {
            if (!confirm('Apply this theme to the live site?')) return;
            
            // Save and activate
            editor.store();
            fetch(`/api/themes/activate.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({theme_id: themeId})
            }).then(() => {
                alert('Theme applied to site!');
            });
        }
    });
}
</script>

</body>
</html>
```

**Features:**
- Drag-and-drop page builder
- Visual color picker
- Typography controls
- Spacing editor
- Border/shadow customization
- Live preview
- Save to database
- Export theme

**Verification:**
- [ ] GrapesJS loads
- [ ] Can edit colors visually
- [ ] Can modify typography
- [ ] Can adjust spacing
- [ ] Changes save to database
- [ ] Can apply to live site

---

## ⚛️ TASK 8.5: React Theme Preview Component

**Time:** 2 hours  
**File:** `/assets/js/ThemePreview.jsx`

**Create React component for live theme preview:**

```jsx
import React, { useState, useEffect } from 'react';

function ThemePreview({ themeId }) {
    const [theme, setTheme] = useState(null);
    const [loading, setLoading] = useState(true);
    
    useEffect(() => {
        // Load theme data
        fetch(`/api/themes/get.php?id=${themeId}`)
            .then(r => r.json())
            .then(data => {
                setTheme(data.theme);
                setLoading(false);
            });
    }, [themeId]);
    
    if (loading) return <div>Loading preview...</div>;
    
    const colors = JSON.parse(theme.colors);
    const fonts = JSON.parse(theme.fonts);
    const spacing = JSON.parse(theme.spacing);
    const borders = JSON.parse(theme.borders);
    const shadows = JSON.parse(theme.shadows);
    
    // Generate CSS variables
    const style = {
        '--primary': colors.primary,
        '--secondary': colors.secondary,
        '--accent': colors.accent,
        '--background': colors.background,
        '--text': colors.text,
        '--font-heading': fonts.heading,
        '--font-body': fonts.body,
        '--spacing-md': spacing.md,
        '--radius-md': borders.radius_md,
        '--shadow-md': shadows.md
    };
    
    return (
        <div className="theme-preview-container" style={style}>
            <div className="preview-header" style={{
                background: `linear-gradient(135deg, ${colors.primary}, ${colors.secondary})`,
                padding: spacing.lg,
                color: 'white',
                fontFamily: fonts.heading
            }}>
                <h1>TrueVault VPN</h1>
                <p>Your Complete Digital Fortress</p>
                <button style={{
                    background: colors.accent,
                    padding: `${spacing.sm} ${spacing.md}`,
                    borderRadius: borders.radius_md,
                    border: 'none',
                    color: 'white',
                    fontWeight: 'bold',
                    boxShadow: shadows.md
                }}>
                    Start Free Trial
                </button>
            </div>
            
            <div className="preview-content" style={{
                padding: spacing.lg,
                background: colors.background,
                fontFamily: fonts.body
            }}>
                <div className="feature-cards" style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(3, 1fr)',
                    gap: spacing.md
                }}>
                    {['Port Forwarding', 'Camera Dashboard', 'Parental Controls'].map(feature => (
                        <div key={feature} style={{
                            background: colors.surface,
                            padding: spacing.md,
                            borderRadius: borders.radius_md,
                            boxShadow: shadows.sm,
                            border: `1px solid ${colors.border}`
                        }}>
                            <h3 style={{
                                color: colors.primary,
                                fontFamily: fonts.heading,
                                marginBottom: spacing.sm
                            }}>
                                {feature}
                            </h3>
                            <p style={{ color: colors.text }}>
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

export default ThemePreview;
```

**Verification:**
- [ ] React component renders
- [ ] Theme variables applied
- [ ] Colors show correctly
- [ ] Fonts load properly
- [ ] Spacing accurate
- [ ] Responsive layout

---

## 🔧 TASK 8.6: Theme API Endpoints

**Time:** 2 hours  
**Files:** Create 7 API endpoints

### **1. /api/themes/get.php**
```php
<?php
require_once '../../includes/Database.php';
$db = new Database();

$themeId = $_GET['id'] ?? null;

if ($themeId) {
    $theme = $db->query("SELECT * FROM themes WHERE id = ?", [$themeId])->fetch();
    
    if ($theme) {
        // Parse JSON fields
        $theme['colors'] = json_decode($theme['colors'], true);
        $theme['fonts'] = json_decode($theme['fonts'], true);
        $theme['spacing'] = json_decode($theme['spacing'], true);
        $theme['borders'] = json_decode($theme['borders'], true);
        $theme['shadows'] = json_decode($theme['shadows'], true);
        
        echo json_encode(['success' => true, 'theme' => $theme]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Theme not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing theme ID']);
}
```

### **2. /api/themes/activate.php**
```php
<?php
require_once '../../includes/Database.php';
requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);
$themeId = $data['theme_id'] ?? null;

if ($themeId) {
    $db = new Database();
    
    // Deactivate all themes
    $db->execute("UPDATE themes SET is_active = 0");
    
    // Activate selected theme
    $db->execute("UPDATE themes SET is_active = 1 WHERE id = ?", [$themeId]);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Missing theme ID']);
}
```

### **3. /api/themes/update-colors.php**
```php
<?php
require_once '../../includes/Database.php';
requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);
$themeId = $data['theme_id'];
$colors = $data['colors'];

$db = new Database();
$db->execute(
    "UPDATE themes SET colors = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
    [json_encode($colors), $themeId]
);

echo json_encode(['success' => true]);
```

### **4-7. More endpoints...**
- save-design.php (GrapesJS storage)
- load-design.php (GrapesJS loading)
- settings.php (seasonal switching)
- export.php (theme export)

**Verification:**
- [ ] All 7 endpoints created
- [ ] GET requests work
- [ ] POST requests work
- [ ] Admin auth required
- [ ] JSON responses valid

---

## 🌡️ TASK 8.7: Seasonal Auto-Switching

**Time:** 1 hour  
**File:** `/includes/SeasonalThemes.php`

**Auto-detect season and switch theme:**

```php
<?php
class SeasonalThemes {
    public static function getCurrentSeason() {
        $month = date('n'); // 1-12
        
        if ($month >= 3 && $month <= 5) return 'spring';
        if ($month >= 6 && $month <= 8) return 'summer';
        if ($month >= 9 && $month <= 11) return 'fall';
        return 'winter';
    }
    
    public static function checkAndSwitch() {
        $db = new Database();
        
        // Check if seasonal switching enabled
        $enabled = $db->query(
            "SELECT setting_value FROM theme_settings WHERE setting_key = 'enable_seasonal_switching'"
        )->fetchColumn();
        
        if (!$enabled) return false;
        
        // Get current season
        $currentSeason = self::getCurrentSeason();
        
        // Check stored season
        $storedSeason = $db->query(
            "SELECT setting_value FROM theme_settings WHERE setting_key = 'current_season'"
        )->fetchColumn();
        
        // If season changed, switch theme
        if ($currentSeason !== $storedSeason) {
            // Find theme for this season
            $theme = $db->query(
                "SELECT id FROM themes WHERE season = ? AND is_seasonal = 1 LIMIT 1",
                [$currentSeason]
            )->fetch();
            
            if ($theme) {
                // Deactivate all
                $db->execute("UPDATE themes SET is_active = 0");
                
                // Activate seasonal theme
                $db->execute("UPDATE themes SET is_active = 1 WHERE id = ?", [$theme['id']]);
                
                // Update stored season
                $db->execute(
                    "UPDATE theme_settings SET setting_value = ? WHERE setting_key = 'current_season'",
                    [$currentSeason]
                );
                
                return true;
            }
        }
        
        return false;
    }
}

// Run on every page load (in config.php)
SeasonalThemes::checkAndSwitch();
```

**Cron Job (optional):**
```bash
# Check daily at midnight
0 0 * * * php /path/to/check-seasonal-themes.php
```

**Verification:**
- [ ] Detects current season correctly
- [ ] Switches theme when season changes
- [ ] Respects enable/disable setting
- [ ] Updates stored season

---

## ✅ FINAL VERIFICATION - PART 8

**Database:**
- [ ] themes table exists
- [ ] theme_settings table exists
- [ ] 20+ themes seeded
- [ ] All themes have complete JSON

**Admin Interface:**
- [ ] Theme manager loads
- [ ] Shows all 20 themes
- [ ] Category filtering works
- [ ] Preview opens
- [ ] Activate works
- [ ] Seasonal toggle works

**Visual Editors:**
- [ ] Color editor modal functional
- [ ] GrapesJS loads
- [ ] Can edit visually
- [ ] Changes save
- [ ] Can apply to site

**React Preview:**
- [ ] Component renders
- [ ] Shows live preview
- [ ] Theme variables applied
- [ ] Updates dynamically

**API Endpoints:**
- [ ] All 7 endpoints work
- [ ] Admin auth enforced
- [ ] JSON responses valid

**Seasonal Auto-Switching:**
- [ ] Detects season correctly
- [ ] Switches automatically
- [ ] Toggle works
- [ ] Stored season updates

**Business Transfer:**
- [ ] New owner can switch themes instantly
- [ ] Can customize colors via GUI
- [ ] Can use visual editor
- [ ] NO coding required
- [ ] 30-second theme change

---

## 📊 TIME ESTIMATE

**Part 8 Total:** 15-18 hours (increased from 5-6)

**Breakdown:**
- Task 8.1: Database schema (45 min)
- Task 8.2: Seed 20 themes (2 hrs)
- Task 8.3: Theme manager UI (3 hrs)
- Task 8.4: GrapesJS integration (4 hrs)
- Task 8.5: React preview (2 hrs)
- Task 8.6: API endpoints (2 hrs)
- Task 8.7: Seasonal switching (1 hr)

**Total Lines:** ~4,500 lines

---

## 🎯 CRITICAL SUCCESS FACTORS

✅ 20+ pre-built themes (NOT 3-4)  
✅ GrapesJS visual editor  
✅ React preview components  
✅ Seasonal auto-switching  
✅ Holiday themes  
✅ Complete GUI customization  
✅ NO coding required  
✅ 30-second theme switching  

**THIS IS THE COMPLETE THEME SYSTEM!**

