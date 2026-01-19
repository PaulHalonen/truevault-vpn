

---

## PAGE BUILDER & THEME MANAGEMENT SESSION (5-6 hours) - ADDED JAN 18, 2026

**Blueprint Reference:** SECTION_24_THEME_AND_PAGE_BUILDER.md  
**Database:** themes.db (created in Part 7)

### **Goal:** Build drag-and-drop page builder and theme manager for non-technical users

**Why This Matters:**
- New owner must customize site WITHOUT coding
- Seasonal themes auto-switch
- Pages editable via drag-and-drop
- 100% visual, zero code required
- Business transfer in 30 minutes

---

### **Task 8.Z.1: Create Theme Manager Admin Page**
**Lines:** ~400 lines  
**File:** `/admin/themes.php`

- [ ] Create theme management interface
- [ ] Grid view of all 12 themes
- [ ] Preview modal with live demo
- [ ] Activate button
- [ ] Edit colors button
- [ ] Edit settings button
- [ ] Import/export themes
- [ ] Upload and test

**Interface Sections:**

**1. Active Theme Display:**
```php
<div class="active-theme-card">
    <img src="<?= $activeTheme['preview_image'] ?>">
    <h2><?= $activeTheme['display_name'] ?></h2>
    <span class="theme-style-badge"><?= $activeTheme['style'] ?></span>
    <?php if ($activeTheme['is_seasonal']): ?>
        <span class="seasonal-badge">🍂 <?= ucfirst($activeTheme['season']) ?></span>
    <?php endif; ?>
    <button onclick="editTheme(<?= $activeTheme['id'] ?>)">Edit Colors</button>
</div>
```

**2. Theme Grid:**
- 12 theme cards (3x4 grid)
- Each card shows:
  - Preview image
  - Theme name
  - Style (light/medium/dark)
  - Season badge (if seasonal)
  - "Activate" button
  - "Preview" button
  - "Edit" button

**3. Seasonal Auto-Switch Settings:**
```php
<div class="seasonal-settings">
    <label>
        <input type="checkbox" id="enableSeasonal" <?= $enableSeasonal ? 'checked' : '' ?>>
        Automatically switch themes based on season
    </label>
    <p class="help-text">
        Current season: <strong><?= Theme::getCurrentSeason() ?></strong>
    </p>
</div>
```

**4. Quick Actions:**
- [ ] Switch Theme dropdown
- [ ] Preview theme (new tab)
- [ ] Reset to default
- [ ] Export theme settings
- [ ] Import theme

**Features:**
- Real-time preview
- Color picker for each of 11 colors
- Font family dropdown
- Border radius slider
- Save changes
- Undo/redo

**Verification:**
- [ ] Can see all 12 themes
- [ ] Can activate themes
- [ ] Seasonal toggle works
- [ ] Color editing works
- [ ] Changes reflect on site

---

### **Task 8.Z.2: Create Color Editor Modal**
**Lines:** ~180 lines  
**File:** `/admin/theme-color-editor.php` (included in themes.php)

- [ ] Create modal popup for color editing
- [ ] 11 color pickers
- [ ] Live preview
- [ ] Save button
- [ ] Reset button
- [ ] Upload and test

**Color Picker Interface:**

```html
<div class="color-editor-modal">
    <h2>Edit Theme Colors</h2>
    
    <div class="color-grid">
        <!-- Primary Color -->
        <div class="color-picker-row">
            <label>Primary Color</label>
            <input type="color" id="color_primary" value="#667eea">
            <input type="text" value="#667eea" readonly>
            <div class="color-preview" style="background: #667eea"></div>
        </div>
        
        <!-- Repeat for all 11 colors -->
        <!-- secondary, accent, background, surface, text_primary, text_secondary, success, warning, error, info -->
    </div>
    
    <div class="preview-section">
        <h3>Live Preview</h3>
        <div class="preview-card">
            <!-- Sample UI elements with live colors -->
        </div>
    </div>
    
    <div class="modal-actions">
        <button onclick="saveColors()">Save Changes</button>
        <button onclick="resetColors()">Reset to Default</button>
        <button onclick="closeModal()">Cancel</button>
    </div>
</div>
```

**Features:**
- HTML5 color picker
- Hex value display
- Live preview of changes
- Sample UI components
- Instant updates

**Verification:**
- [ ] Color picker opens
- [ ] Colors update in real-time
- [ ] Save persists to database
- [ ] Reset restores defaults
- [ ] Preview accurate

---

### **Task 8.Z.3: Create Page Builder Interface**
**Lines:** ~500 lines  
**File:** `/admin/page-builder.php?page=home`

- [ ] Create drag-and-drop page builder
- [ ] Section library sidebar
- [ ] Canvas area
- [ ] Properties panel
- [ ] Save/publish buttons
- [ ] Preview toggle
- [ ] Upload and test

**Interface Layout:**

```
┌─────────────────────────────────────────────────────────────┐
│ Header: Edit Page: Home                        [Preview] [Publish] │
├───────────┬──────────────────────────┬─────────────────────┤
│ SECTIONS  │       CANVAS             │    PROPERTIES       │
│ (Left)    │      (Center)            │      (Right)        │
│           │                          │                     │
│ 📄 Hero   │  ┌──────────────────┐  │  Section: Hero      │
│ 🎯 Features│  │  Hero Section   │  │  Background: #xxx   │
│ 💰 Pricing │  │  [Drag Handle]  │  │  Padding: 80px      │
│ 💬 Testimonial│ └──────────────────┘  │  Visible: ✓         │
│ 📝 Text   │                          │  [Delete Section]   │
│ 🖼 Image   │  ┌──────────────────┐  │                     │
│ 🎬 Video   │  │  Features Grid  │  │                     │
│ ❓ FAQ     │  │  [Drag Handle]  │  │                     │
│ 📞 CTA     │  └──────────────────┘  │                     │
│ 📊 Stats   │                          │                     │
│           │                          │                     │
│ [Add Custom] │                          │                     │
└───────────┴──────────────────────────┴─────────────────────┘
```

**Key Features:**

**1. Section Library (Left Sidebar):**
- Draggable section types
- Icon + label for each
- Click or drag to add
- Search/filter sections

**2. Canvas (Center):**
- Visual page preview
- Drag handles on each section
- Reorder by dragging
- Click to edit
- Delete button on hover
- Mobile/tablet/desktop preview toggle

**3. Properties Panel (Right Sidebar):**
- Section-specific settings
- Background color/image picker
- Padding/margin sliders
- Text editor (for text sections)
- Image uploader (for image sections)
- Animation effects dropdown
- Visibility toggle
- Custom CSS class input

**4. Top Toolbar:**
- Page selector dropdown
- Save Draft button
- Publish button
- Preview button (new tab)
- Device preview (mobile/tablet/desktop)
- Undo/redo buttons
- Settings button

**Section Types with JSON Data:**

**Hero Section:**
```json
{
  "type": "hero",
  "data": {
    "heading": "Welcome to TrueVault VPN",
    "subheading": "Your Complete Digital Fortress",
    "background_type": "gradient",
    "background_value": "linear-gradient(135deg, #667eea, #764ba2)",
    "button_text": "Start Free Trial",
    "button_url": "/register",
    "image_url": "/assets/hero-shield.png",
    "image_position": "right"
  }
}
```

**Features Section:**
```json
{
  "type": "features",
  "data": {
    "heading": "Features",
    "features": [
      {
        "icon": "🔒",
        "title": "256-bit Encryption",
        "description": "Military-grade security"
      },
      {
        "icon": "🚀",
        "title": "2-Click Setup",
        "description": "Easy installation"
      }
    ],
    "columns": 3
  }
}
```

**Pricing Section:**
```json
{
  "type": "pricing",
  "data": {
    "heading": "Choose Your Plan",
    "plans": [
      {
        "name": "Standard",
        "price": 9.99,
        "features": ["3 devices", "Basic support"],
        "cta_text": "Get Started",
        "cta_url": "/register?plan=standard",
        "highlighted": false
      }
    ]
  }
}
```

**Verification:**
- [ ] Can drag sections to canvas
- [ ] Can reorder sections
- [ ] Can edit section properties
- [ ] Can save draft
- [ ] Can publish
- [ ] Preview works
- [ ] Changes persist

---

### **Task 8.Z.4: Implement SortableJS for Drag-and-Drop**
**Lines:** ~100 lines  
**File:** Include in page-builder.php

- [ ] Include SortableJS library
- [ ] Initialize sortable on canvas
- [ ] Handle drag events
- [ ] Update database on reorder
- [ ] Upload and test

**JavaScript Implementation:**

```javascript
// Initialize SortableJS
const canvas = document.getElementById('page-canvas');
const sortable = new Sortable(canvas, {
    animation: 150,
    handle: '.drag-handle',
    ghostClass: 'sortable-ghost',
    chosenClass: 'sortable-chosen',
    
    onEnd: function(evt) {
        // Get new order
        const sectionIds = [];
        document.querySelectorAll('.page-section').forEach(section => {
            sectionIds.push(section.dataset.sectionId);
        });
        
        // Update database
        fetch('/api/page-builder/reorder', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                page_id: currentPageId,
                section_order: sectionIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Section order updated', 'success');
            }
        });
    }
});
```

**Verification:**
- [ ] Sections draggable
- [ ] Smooth animation
- [ ] Order saves to DB
- [ ] No conflicts

---

### **Task 8.Z.5: Create Page Builder APIs**
**Lines:** ~300 lines  
**Files:** Create 6 API endpoints

**API Endpoints:**

**1. /api/page-builder/get-page.php**
- Get page and sections by slug
- Returns page data + sections array
- Used to load page in builder

**2. /api/page-builder/add-section.php**
- Add new section to page
- POST: page_id, section_type, section_data
- Returns section_id

**3. /api/page-builder/update-section.php**
- Update existing section
- POST: section_id, section_data
- Returns success

**4. /api/page-builder/delete-section.php**
- Delete section
- POST: section_id
- Cascade deletes

**5. /api/page-builder/reorder-sections.php**
- Reorder sections
- POST: page_id, section_order array
- Updates sort_order values

**6. /api/page-builder/publish-page.php**
- Save page revision
- Mark as published
- Clear cache
- Returns success

**Verification:**
- [ ] All endpoints work
- [ ] Proper error handling
- [ ] Authentication required
- [ ] Returns JSON
- [ ] Logged properly

---

### **Task 8.Z.6: Create Site Settings Admin Page**
**Lines:** ~350 lines  
**File:** `/admin/site-settings.php`

- [ ] Create global settings editor
- [ ] Organized by category
- [ ] Edit all site settings
- [ ] Upload logo/favicon
- [ ] Test email/PayPal connection
- [ ] Upload and test

**Settings Categories:**

**1. General Settings:**
- Site title
- Site tagline
- Contact email
- Support email
- Company name
- Support phone

**2. Branding:**
- Logo upload
- Favicon upload
- Active theme
- Enable seasonal themes

**3. SEO:**
- Meta description
- Meta keywords
- Google Analytics ID
- Facebook Pixel ID

**4. Social Media:**
- Facebook URL
- Twitter URL
- LinkedIn URL
- YouTube URL

**5. Features:**
- Maintenance mode toggle
- Enable registration toggle
- Enable free trial toggle
- Trial duration (days)

**6. Pricing Display:**
- Standard plan price
- Pro plan price
- Currency symbol
- Display prices

**Interface:**

```php
<div class="settings-page">
    <h1>Site Settings</h1>
    
    <!-- Tab navigation -->
    <div class="settings-tabs">
        <button class="tab active" data-tab="general">General</button>
        <button class="tab" data-tab="branding">Branding</button>
        <button class="tab" data-tab="seo">SEO</button>
        <button class="tab" data-tab="social">Social Media</button>
        <button class="tab" data-tab="features">Features</button>
        <button class="tab" data-tab="pricing">Pricing</button>
    </div>
    
    <!-- Settings forms -->
    <form id="settings-form">
        <div class="tab-content active" id="tab-general">
            <div class="form-group">
                <label>Site Title</label>
                <input type="text" name="site_title" value="<?= Content::get('site_title') ?>">
            </div>
            <!-- More fields -->
        </div>
        
        <!-- More tabs -->
        
        <button type="submit">Save All Settings</button>
    </form>
</div>
```

**Verification:**
- [ ] All settings load
- [ ] Can edit settings
- [ ] Save persists
- [ ] File uploads work
- [ ] No hardcoded values remain

---

### **Task 8.Z.7: Create Navigation Menu Editor**
**Lines:** ~280 lines  
**File:** `/admin/navigation.php`

- [ ] Create menu editor interface
- [ ] Drag-and-drop menu items
- [ ] Add/edit/delete items
- [ ] Icon picker
- [ ] Dropdown/nested menus
- [ ] Upload and test

**Interface:**

```
Header Menu:
┌────────────────────────────────────────┐
│ [+] Add Menu Item                      │
├────────────────────────────────────────┤
│ ☰ Home           [Edit] [Delete] ↕     │
│ ☰ Features       [Edit] [Delete] ↕     │
│ ☰ Pricing        [Edit] [Delete] ↕     │
│   ☰ Standard     [Edit] [Delete] ↕     │ (nested)
│   ☰ Pro          [Edit] [Delete] ↕     │ (nested)
│ ☰ About          [Edit] [Delete] ↕     │
│ ☰ Login          [Edit] [Delete] ↕     │
└────────────────────────────────────────┘

Footer Menu:
┌────────────────────────────────────────┐
│ [+] Add Menu Item                      │
├────────────────────────────────────────┤
│ ☰ Terms          [Edit] [Delete] ↕     │
│ ☰ Privacy        [Edit] [Delete] ↕     │
│ ☰ Contact        [Edit] [Delete] ↕     │
└────────────────────────────────────────┘
```

**Menu Item Editor Modal:**
- Label (text)
- URL (text)
- Icon (emoji picker or CSS class)
- Target (_self or _blank)
- Parent menu (dropdown for nested)
- Required role (null, user, admin)
- Visible toggle

**Verification:**
- [ ] Can add menu items
- [ ] Can edit items
- [ ] Can delete items
- [ ] Can reorder (drag)
- [ ] Can nest items
- [ ] Changes reflect on site

---

### **Task 8.Z.8: Create Media Library**
**Lines:** ~320 lines  
**File:** `/admin/media.php`

- [ ] Create media management interface
- [ ] Upload images/files
- [ ] Grid view with thumbnails
- [ ] Search and filter
- [ ] Copy URL button
- [ ] Delete files
- [ ] Upload and test

**Interface:**

```
┌──────────────────────────────────────────────────────┐
│ Media Library                    [Upload Files ▾]    │
├──────────────────────────────────────────────────────┤
│ Search: [____________]  Filter: [All ▾]              │
├──────────────────────────────────────────────────────┤
│ ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  │
│ │ [Image] │  │ [Image] │  │ [Image] │  │ [Image] │  │
│ │ logo.png│  │ hero.jpg│  │ icon.svg│  │ bg.png  │  │
│ │ 45 KB   │  │ 234 KB  │  │ 12 KB   │  │ 89 KB   │  │
│ │[Copy URL]│  │[Copy URL]│  │[Copy URL]│  │[Copy URL]│  │
│ │[Delete] │  │[Delete] │  │[Delete] │  │[Delete] │  │
│ └─────────┘  └─────────┘  └─────────┘  └─────────┘  │
│ ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  │
│ │ [Image] │  │ [Image] │  │ [Image] │  │ [Image] │  │
│ └─────────┘  └─────────┘  └─────────┘  └─────────┘  │
└──────────────────────────────────────────────────────┘
```

**Features:**
- Drag-and-drop upload
- Multiple file upload
- Thumbnail generation
- File type icons
- File size display
- Upload date
- Uploaded by (user)
- Alt text editor
- Copy URL to clipboard
- Delete confirmation
- Search by filename
- Filter by type (image/video/document)

**Upload Folder:** `/assets/uploads/`

**Verification:**
- [ ] Can upload files
- [ ] Thumbnails generate
- [ ] Copy URL works
- [ ] Delete works
- [ ] Search works
- [ ] Filter works

---

### **Task 8.Z.9: Create Frontend Rendering System**
**Lines:** ~200 lines  
**File:** `/includes/render-page.php`

- [ ] Create page rendering function
- [ ] Load theme colors
- [ ] Load page sections
- [ ] Render sections dynamically
- [ ] Apply theme CSS
- [ ] Upload and test

**Rendering Logic:**

```php
function renderPage($slug) {
    // Get active theme
    $theme = Theme::getActiveTheme();
    $colors = Theme::getAllColors();
    
    // Get page data
    $page = PageBuilder::getPage($slug);
    if (!$page) {
        http_response_code(404);
        include '404.php';
        return;
    }
    
    // Get sections
    $sections = PageBuilder::getSections($page['id']);
    
    // Include template
    include "/templates/{$page['layout_template']}.php";
}

// Section renderer
function renderSection($section) {
    $type = $section['section_type'];
    $data = json_decode($section['section_data'], true);
    
    include "/templates/sections/{$type}.php";
}
```

**Section Templates:**
- `/templates/sections/hero.php`
- `/templates/sections/features.php`
- `/templates/sections/pricing.php`
- `/templates/sections/testimonials.php`
- `/templates/sections/cta.php`
- `/templates/sections/text.php`
- `/templates/sections/image.php`
- `/templates/sections/video.php`
- `/templates/sections/faq.php`
- `/templates/sections/stats.php`
- `/templates/sections/form.php`

**Verification:**
- [ ] Pages render correctly
- [ ] Sections display properly
- [ ] Theme colors apply
- [ ] No hardcoded values
- [ ] Mobile responsive

---

### **Task 8.Z.10: Pre-populate Pages with Content**
**Lines:** ~400 lines  
**Database:** themes.db (pages and page_sections tables)

- [ ] Create 9 essential pages
- [ ] Add 2-5 sections per page
- [ ] Populate with real content
- [ ] Set SEO meta tags
- [ ] Test all pages
- [ ] Upload

**Pages to Create:**

**1. Home Page (/):**
- Hero section (welcome, CTA)
- Features grid (6 features)
- Pricing preview (2 plans)
- Stats counter (customers, devices, servers)
- CTA section (start trial)

**2. Pricing (/pricing):**
- Hero section (pricing title)
- Pricing table (Standard, Pro)
- FAQ section (common questions)
- CTA section (choose plan)

**3. Features (/features):**
- Hero section (features title)
- Features grid (9 features detailed)
- Text section (how it works)
- CTA section (get started)

**4. About (/about):**
- Hero section (about us)
- Text section (mission)
- Stats section (achievements)
- CTA section (contact)

**5. Contact (/contact):**
- Hero section (contact title)
- Form section (contact form)
- Text section (support email)

**6. Login (/login):**
- Form section (login form)
- Text section (forgot password link)

**7. Register (/register):**
- Form section (registration)
- Text section (trial info)

**8. Terms (/terms):**
- Text section (terms of service)

**9. Privacy (/privacy):**
- Text section (privacy policy)

**Verification:**
- [ ] All 9 pages exist
- [ ] Content makes sense
- [ ] Links work
- [ ] SEO tags set
- [ ] Mobile responsive

---

### **Testing Checklist for Page Builder & Themes:**

- [ ] Theme manager loads all 12 themes
- [ ] Can activate themes
- [ ] Seasonal auto-switch works
- [ ] Color editor saves changes
- [ ] Page builder interface loads
- [ ] Can drag sections to canvas
- [ ] Can reorder sections
- [ ] Can edit section properties
- [ ] Can save drafts
- [ ] Can publish pages
- [ ] Preview works correctly
- [ ] Site settings save
- [ ] Navigation editor works
- [ ] Media library uploads files
- [ ] Frontend renders pages correctly
- [ ] Theme colors apply throughout site
- [ ] No hardcoded values visible
- [ ] Mobile responsive on all pages
- [ ] All 9 pages display correctly

---

**Total Lines for Page Builder & Themes:** ~3,330 lines

**Time Estimate:** 5-6 hours

**Priority:** CRITICAL - Required for non-technical owner to manage site

**Dependencies:**
- Theme system database (Part 7) ✅ Must complete first
- Theme helper classes (Part 7) ✅ Must complete first
- Admin authentication ✅ Complete

---

**END OF PART 8 ADDITIONS**

Now continuing with original Part 8 completion checklist...

