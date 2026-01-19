# SECTION 24: THEME MANAGEMENT & PAGE BUILDER SYSTEM

**Created:** January 18, 2026  
**Priority:** CRITICAL  
**Purpose:** 100% database-driven frontend with theme management and page builder  
**Philosophy:** **ZERO HARDCODED VALUES** - Everything customizable without touching code  

---

## 📋 TABLE OF CONTENTS

1. [System Overview](#overview)
2. [Core Principles](#principles)
3. [Database Architecture](#database)
4. [Theme System](#themes)
5. [Page Builder](#page-builder)
6. [Content Management](#cms)
7. [Implementation Guide](#implementation)

---

## 🎯 SYSTEM OVERVIEW

### **The Problem with Traditional Sites**

Most websites have **hardcoded** values:
```php
// BAD - Hardcoded
$primaryColor = "#667eea";
$siteTitle = "TrueVault VPN";
$heroText = "Welcome to TrueVault";
```

**Problems:**
- New owner must edit PHP files
- Requires coding knowledge
- Risk of breaking site
- No business transferability

### **TrueVault Solution: 100% Database-Driven**

```php
// GOOD - Database driven
$primaryColor = Settings::get('theme_primary_color');
$siteTitle = Settings::get('site_title');
$heroText = Content::get('home_hero_text');
```

**Benefits:**
- Edit everything from admin panel
- No code knowledge required
- Safe changes (can't break site)
- Business transfer in 30 minutes
- Multiple themes ready to switch
- Seasonal themes automated

---

## ⚖️ CORE PRINCIPLES

### **1. ZERO HARDCODED VALUES**

**NEVER:**
```php
<h1 style="color: #667eea">TrueVault VPN</h1>
```

**ALWAYS:**
```php
<h1 style="color: <?= Theme::color('primary') ?>"><?= Content::get('site_title') ?></h1>
```

### **2. THEME INHERITANCE**

**Base Theme** → **Season Override** → **Custom Override**

Example:
- Base: Blue gradient background
- Winter Override: Snow particles, cool blue
- Custom Override: User's uploaded logo

### **3. DRAG-AND-DROP FIRST**

Admin should be able to:
- Rearrange page sections by dragging
- Add/remove content blocks
- Change layouts without code
- Preview before publishing

### **4. MOBILE-RESPONSIVE ALWAYS**

All themes must:
- Work on mobile (320px+)
- Work on tablet (768px+)
- Work on desktop (1024px+)
- Adapt automatically

### **5. PERFORMANCE OPTIMIZED**

- CSS minified automatically
- Images lazy-loaded
- Settings cached
- Database queries optimized

---

## 🗄️ DATABASE ARCHITECTURE

### **New Tables Required**

#### **1. themes (Theme Definitions)**

```sql
CREATE TABLE themes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    display_name TEXT NOT NULL,
    description TEXT,
    preview_image TEXT,
    style TEXT NOT NULL, -- 'light', 'medium', 'dark'
    is_active INTEGER DEFAULT 0,
    is_seasonal INTEGER DEFAULT 0,
    season TEXT, -- 'winter', 'spring', 'summer', 'fall', null
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Pre-installed Themes:**
- `default_light` - Clean white background
- `default_medium` - Soft gray tones
- `default_dark` - Deep blue/purple gradient (current)
- `winter_light` - Snow theme, cool tones
- `winter_dark` - Midnight blue, snowflakes
- `spring_light` - Pastel greens, floral
- `spring_dark` - Deep greens, nature
- `summer_light` - Bright yellows, sunny
- `summer_dark` - Ocean blue, warm
- `fall_light` - Orange/brown, leaves
- `fall_dark` - Deep burgundy, autumn

#### **2. theme_colors (Color Palettes)**

```sql
CREATE TABLE theme_colors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_id INTEGER NOT NULL,
    color_key TEXT NOT NULL, -- 'primary', 'secondary', 'accent', etc.
    color_value TEXT NOT NULL, -- Hex code
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE
);

CREATE INDEX idx_theme_colors ON theme_colors(theme_id, color_key);
```

**Standard Color Keys:**
- `primary` - Main brand color
- `secondary` - Secondary brand color
- `accent` - Accent/highlight color
- `background` - Page background
- `surface` - Card/panel background
- `text_primary` - Main text color
- `text_secondary` - Muted text
- `success` - Green (success messages)
- `warning` - Yellow (warnings)
- `error` - Red (errors)
- `info` - Blue (info messages)

#### **3. theme_settings (Theme Configuration)**

```sql
CREATE TABLE theme_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_id INTEGER NOT NULL,
    setting_key TEXT NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type TEXT NOT NULL, -- 'text', 'number', 'boolean', 'json'
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE
);

CREATE INDEX idx_theme_settings ON theme_settings(theme_id, setting_key);
```

**Standard Settings:**
- `font_family` - Typography
- `font_size_base` - Base font size
- `border_radius` - Roundness
- `shadow_intensity` - Shadow depth
- `animation_speed` - Transition speed
- `button_style` - Button appearance
- `nav_style` - Navigation layout

#### **4. pages (Page Definitions)**

```sql
CREATE TABLE pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE, -- URL path
    title TEXT NOT NULL,
    meta_description TEXT,
    meta_keywords TEXT,
    is_public INTEGER DEFAULT 1, -- 1 = anyone, 0 = logged in only
    is_active INTEGER DEFAULT 1,
    layout_template TEXT NOT NULL, -- 'default', 'blank', 'landing'
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Pre-installed Pages:**
- `/` - Home (landing page)
- `/pricing` - Pricing comparison
- `/features` - Features showcase
- `/about` - About us
- `/contact` - Contact form
- `/login` - User login
- `/register` - User registration
- `/terms` - Terms of service
- `/privacy` - Privacy policy

#### **5. page_sections (Page Content Blocks)**

```sql
CREATE TABLE page_sections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id INTEGER NOT NULL,
    section_type TEXT NOT NULL, -- 'hero', 'features', 'pricing', 'text', 'image', 'cta'
    section_data TEXT NOT NULL, -- JSON content
    sort_order INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);

CREATE INDEX idx_page_sections ON page_sections(page_id, sort_order);
```

**Section Types:**
- `hero` - Large banner with CTA
- `features` - Feature grid (3-4 columns)
- `pricing` - Pricing cards
- `testimonials` - Customer quotes
- `cta` - Call-to-action block
- `text` - Rich text content
- `image` - Image gallery
- `video` - Video embed
- `faq` - FAQ accordion
- `stats` - Statistics display
- `form` - Contact/signup form

#### **6. site_settings (Global Settings)**

```sql
CREATE TABLE site_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type TEXT NOT NULL, -- 'text', 'number', 'boolean', 'json', 'image'
    category TEXT, -- 'general', 'branding', 'seo', 'social'
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Pre-installed Settings:**
- `site_title` - "TrueVault VPN"
- `site_tagline` - "Your Complete Digital Fortress"
- `site_logo` - Path to logo image
- `site_favicon` - Path to favicon
- `contact_email` - support@vpn.the-truth-publishing.com
- `company_name` - "TrueVault VPN"
- `support_phone` - Phone number
- `facebook_url` - Social link
- `twitter_url` - Social link
- `enable_seasonal_themes` - Auto-switch themes
- `maintenance_mode` - Site offline mode

#### **7. navigation_menus (Dynamic Navigation)**

```sql
CREATE TABLE navigation_menus (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    menu_location TEXT NOT NULL, -- 'header', 'footer', 'sidebar'
    label TEXT NOT NULL,
    url TEXT NOT NULL,
    target TEXT DEFAULT '_self', -- '_self' or '_blank'
    icon TEXT, -- Icon class or emoji
    parent_id INTEGER, -- For dropdowns
    sort_order INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1,
    required_role TEXT -- null, 'user', 'admin'
);

CREATE INDEX idx_navigation_menus ON navigation_menus(menu_location, sort_order);
```

---

## 🎨 THEME SYSTEM

### **Theme Structure**

Each theme consists of:
1. **Base Colors** (11 color values)
2. **Typography** (font family, sizes)
3. **Spacing** (margins, paddings)
4. **Components** (buttons, cards, inputs)
5. **Animations** (transitions, effects)

### **Theme Switching Process**

```
User clicks "Switch Theme" 
   ↓
Admin selects theme from dropdown
   ↓
System updates site_settings: active_theme_id = X
   ↓
Cache cleared
   ↓
Next page load uses new theme
```

### **Seasonal Auto-Switch**

```
Cron job runs daily
   ↓
Check current date → Determine season
   ↓
If enable_seasonal_themes = true
   ↓
Switch to season's theme automatically
   ↓
Log theme change
```

**Season Detection:**
- Winter: Dec 1 - Feb 28/29
- Spring: Mar 1 - May 31
- Summer: Jun 1 - Aug 31
- Fall: Sep 1 - Nov 30

### **Theme Inheritance Example**

```php
// Get color with fallback chain
$primaryColor = Theme::getColor('primary');

// Process:
// 1. Check custom overrides
// 2. Check seasonal theme
// 3. Check base active theme
// 4. Fallback to default (#667eea)
```

---

## 🏗️ PAGE BUILDER

### **Builder Interface Components**

#### **1. Section Library (Left Sidebar)**

Drag these onto canvas:
- 📄 Hero Banner
- 🎯 Features Grid
- 💰 Pricing Table
- 💬 Testimonials
- 📝 Text Block
- 🖼️ Image Gallery
- 🎬 Video Player
- ❓ FAQ Accordion
- 📞 Contact Form
- 📊 Stats Counter
- 🔔 Call-to-Action

#### **2. Canvas (Center)**

- Visual preview of page
- Drag sections to reorder
- Click section to edit
- Delete button on hover
- Mobile/tablet/desktop preview toggle

#### **3. Properties Panel (Right Sidebar)**

When section selected:
- Section-specific settings
- Background color/image
- Padding/margin
- Animation effects
- Visibility toggle
- Custom CSS class

### **Section Data Format (JSON)**

```json
{
  "type": "hero",
  "data": {
    "heading": "Welcome to TrueVault VPN",
    "subheading": "Your Complete Digital Fortress",
    "background_type": "gradient",
    "background_value": "linear-gradient(135deg, #667eea 0%, #764ba2 100%)",
    "button_text": "Start Free Trial",
    "button_url": "/register",
    "button_style": "primary",
    "image_url": "/assets/hero-shield.png",
    "image_position": "right"
  }
}
```

### **Drag-and-Drop Implementation**

**Technology:** SortableJS (lightweight, no jQuery needed)

```javascript
// Initialize sortable
new Sortable(document.getElementById('canvas'), {
    animation: 150,
    handle: '.drag-handle',
    onEnd: function(evt) {
        // Update sort_order in database
        updateSectionOrder(evt.oldIndex, evt.newIndex);
    }
});
```

---

## 📝 CONTENT MANAGEMENT

### **Content Editing Flow**

```
Admin clicks "Edit Page"
   ↓
Page Builder loads with existing sections
   ↓
Admin drags/edits sections
   ↓
Click "Preview" → See changes (not live)
   ↓
Click "Publish" → Update database
   ↓
Cache cleared
   ↓
Public sees updated page
```

### **Content Versioning**

```sql
CREATE TABLE page_revisions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id INTEGER NOT NULL,
    revision_data TEXT NOT NULL, -- Full page JSON
    created_by INTEGER, -- Admin user ID
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);
```

- Keep last 10 revisions per page
- "Restore" button to revert
- Compare revisions side-by-side

### **Media Library**

```sql
CREATE TABLE media_library (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL,
    original_filename TEXT NOT NULL,
    file_path TEXT NOT NULL,
    file_type TEXT NOT NULL, -- 'image', 'video', 'document'
    file_size INTEGER NOT NULL, -- bytes
    mime_type TEXT,
    alt_text TEXT,
    uploaded_by INTEGER,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Features:**
- Drag-and-drop upload
- Thumbnail preview
- Search by filename
- Filter by type
- Insert into page builder

---

## 🛠️ IMPLEMENTATION GUIDE

### **Phase 1: Database Setup**

**File:** `/admin/setup-theme-tables.php`

Create all tables:
- themes
- theme_colors
- theme_settings
- pages
- page_sections
- site_settings
- navigation_menus
- page_revisions
- media_library

### **Phase 2: Helper Classes**

**File:** `/includes/Theme.php`

```php
class Theme {
    public static function getActiveTheme()
    public static function getColor($key, $fallback = '#667eea')
    public static function getSetting($key, $fallback = '')
    public static function switchTheme($themeId)
    public static function listThemes()
    public static function autoSwitchSeasonal()
}
```

**File:** `/includes/PageBuilder.php`

```php
class PageBuilder {
    public static function getPage($slug)
    public static function getSections($pageId)
    public static function addSection($pageId, $type, $data)
    public static function updateSection($sectionId, $data)
    public static function deleteSection($sectionId)
    public static function reorderSections($pageId, $order)
}
```

**File:** `/includes/Content.php`

```php
class Content {
    public static function get($key, $fallback = '')
    public static function set($key, $value)
    public static function render($pageSlug)
}
```

### **Phase 3: Admin Interfaces**

#### **3.1 Theme Manager** 
**File:** `/admin/themes.php`

Features:
- Grid view of all themes
- Preview modal
- Activate button
- Edit colors
- Edit settings
- Import/export themes

#### **3.2 Page Builder**
**File:** `/admin/page-builder.php?page=home`

Features:
- Section library sidebar
- Canvas with live preview
- Properties panel
- Save draft / Publish
- Mobile/tablet/desktop preview
- Undo/redo
- Revision history

#### **3.3 Site Settings**
**File:** `/admin/site-settings.php`

Categories:
- General (title, tagline, logo)
- Branding (colors, fonts)
- SEO (meta tags)
- Social Media (links)
- Maintenance Mode

#### **3.4 Navigation Editor**
**File:** `/admin/navigation.php`

Features:
- Drag-and-drop menu items
- Add/edit/delete items
- Icon picker
- Visibility toggle
- Multi-level menus (dropdowns)

#### **3.5 Media Library**
**File:** `/admin/media.php`

Features:
- Upload interface
- Grid view with thumbnails
- Delete/edit
- Filter by type
- Search
- Copy URL button

### **Phase 4: Frontend Rendering**

**File:** `/includes/render-page.php`

```php
function renderPage($slug) {
    $theme = Theme::getActiveTheme();
    $page = PageBuilder::getPage($slug);
    $sections = PageBuilder::getSections($page['id']);
    
    include "/templates/{$page['layout_template']}.php";
}
```

**Template Files:**
- `/templates/default.php` - Standard layout
- `/templates/blank.php` - No header/footer
- `/templates/landing.php` - Marketing page

### **Phase 5: Pre-installed Themes**

Create 12 themes:
- 3 base styles (light, medium, dark)
- 4 seasons × 2 styles each (light/dark)

Each theme includes:
- 11 color definitions
- Font settings
- Component styles
- Sample preview

### **Phase 6: Pre-installed Pages**

Create 9 essential pages:
- Home (`/`)
- Pricing (`/pricing`)
- Features (`/features`)
- About (`/about`)
- Contact (`/contact`)
- Login (`/login`)
- Register (`/register`)
- Terms (`/terms`)
- Privacy (`/privacy`)

Each page includes:
- SEO meta tags
- 2-5 sections
- Responsive layout

---

## 🎯 BUSINESS TRANSFER BENEFITS

### **Why This Matters**

**Scenario:** You sell business to new owner

**WITHOUT This System:**
- New owner: "Where do I change the colors?"
- You: "Edit line 347 in styles.css"
- New owner: "Where's that?"
- You: "Login to FTP, navigate to..."
- **Result:** 2-hour phone call, frustrated owner

**WITH This System:**
- New owner: "Where do I change colors?"
- You: "Admin Panel → Themes → Edit"
- **Result:** 30-second answer, happy owner

### **Marketing Angle**

*"Not just a VPN business. A complete, turnkey operation with point-and-click customization. New owner makes it their own in 30 minutes—no coding required."*

---

## 📊 SUMMARY

### **What We're Building**

1. **12 Pre-built Themes** (ready to activate)
2. **Seasonal Auto-Switching** (winter→spring→summer→fall)
3. **Drag-and-Drop Page Builder** (zero code)
4. **100% Database-Driven** (all settings editable)
5. **Media Library** (image management)
6. **Navigation Editor** (dynamic menus)
7. **Content Versioning** (undo mistakes)
8. **Mobile-Responsive** (all devices)
9. **SEO Optimized** (meta tags)
10. **Transfer-Ready** (30-minute handoff)

### **Zero Hardcoded Values**

- Colors → Database
- Text → Database
- Images → Database
- Menus → Database
- Pages → Database
- Settings → Database

### **Result**

**A VPN business that anyone can run, customize, and transfer—without writing a single line of code.**

---

**END OF SECTION 24**

**Next:** Implement in MASTER_CHECKLIST_PART7.md and PART8.md
