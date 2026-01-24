# MASTER CHECKLIST - PART 14: FORM LIBRARY & BUILDER

**Created:** January 18, 2026 - 10:45 PM CST  
**Blueprint:** SECTION_17_FORM_LIBRARY.md (573 lines)  
**Status:** ✅ COMPLETE  
**Priority:** 🟠 HIGH - Business Forms System  
**Estimated Time:** 8-10 hours  
**Estimated Lines:** ~2,500 lines  

---

## 📋 OVERVIEW

Build a complete form library with 50+ professional pre-built forms, each in 3 distinct styles.

**Core Principle:** *"Pick a form, choose a style, and you're done"*

**What This Includes:**
- 50+ ready-to-use form templates
- 3 styles per form = 150 total variations (Casual, Business, Corporate)
- Visual drag-and-drop form builder
- Typeform-style interface
- No coding required

---

## 🎯 KEY FEATURES

✅ 50+ professional form templates  
✅ 3 distinct styles (Casual, Business, Corporate)  
✅ Visual form builder (drag-and-drop)  
✅ Conditional logic (show/hide fields based on answers)  
✅ Multi-page forms with progress tracking  
✅ File uploads and signature fields  
✅ Email notifications (user + admin)  
✅ Database integration  
✅ CSV/Excel export  
✅ Spam protection (reCAPTCHA)  
✅ Mobile-responsive  

---

## 💾 TASK 14.1: Create Database Schema (forms.db)

**Time:** 30 minutes  
**Lines:** ~150 lines  
**File:** `/admin/forms/setup-forms.php`

### **Create forms.db with 3 tables:**

```sql
-- TABLE 1: forms (all form definitions)
CREATE TABLE IF NOT EXISTS forms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    form_name TEXT NOT NULL,                -- Internal: "customer_registration"
    display_name TEXT NOT NULL,             -- User-friendly: "Customer Registration"
    category TEXT NOT NULL,                 -- customer, support, payment, etc.
    style TEXT DEFAULT 'business',          -- casual, business, corporate
    description TEXT,
    fields TEXT NOT NULL,                   -- JSON array of form fields
    settings TEXT,                          -- JSON: notifications, redirects, etc.
    is_template INTEGER DEFAULT 0,          -- 0=custom, 1=pre-built template
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER,                     -- Admin user ID
    submission_count INTEGER DEFAULT 0
);

-- TABLE 2: form_submissions (all form responses)
CREATE TABLE IF NOT EXISTS form_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    form_id INTEGER NOT NULL,
    form_data TEXT NOT NULL,                -- JSON: all field values
    submitter_ip TEXT,
    submitter_email TEXT,
    submitter_name TEXT,
    status TEXT DEFAULT 'new',              -- new, read, processed, spam
    submitted_at TEXT DEFAULT CURRENT_TIMESTAMP,
    processed_at TEXT,
    processed_by INTEGER,                   -- Admin who processed it
    notes TEXT,                             -- Admin notes
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
);

-- TABLE 3: form_files (uploaded files from forms)
CREATE TABLE IF NOT EXISTS form_files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    submission_id INTEGER NOT NULL,
    field_name TEXT NOT NULL,
    original_filename TEXT NOT NULL,
    stored_filename TEXT NOT NULL,
    file_path TEXT NOT NULL,
    file_size INTEGER,
    mime_type TEXT,
    uploaded_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES form_submissions(id) ON DELETE CASCADE
);
```

### **Verification:**
- [ ] forms.db created
- [ ] All 3 tables exist
- [ ] Can insert test data
- [ ] Foreign keys working

---

## 📚 TASK 14.2: Create 50+ Form Templates

**Time:** 3 hours  
**Lines:** ~1,200 lines (24 lines per template)  
**Files:** 50+ JSON template files in `/admin/forms/templates/`

### **Form Categories:**

**1. Customer Management (10 forms)**
- customer_registration.json
- customer_profile_update.json
- customer_feedback.json
- customer_satisfaction_survey.json
- customer_complaint.json
- rma_request.json
- product_return.json
- account_cancellation.json
- reactivation_request.json
- referral_program.json

**2. Support & Service (10 forms)**
- support_ticket.json
- bug_report.json
- feature_request.json
- technical_support.json
- billing_inquiry.json
- general_inquiry.json
- live_chat_request.json
- callback_request.json
- appointment_booking.json
- service_request.json

**3. Payment & Billing (5 forms)**
- payment_form.json
- invoice_payment.json
- refund_request.json
- payment_method_update.json
- billing_dispute.json

**4. Registration & Signup (8 forms)**
- event_registration.json
- newsletter_signup.json
- webinar_registration.json
- trial_signup.json
- membership_application.json
- partner_registration.json
- vendor_application.json
- affiliate_signup.json

**5. Surveys & Feedback (8 forms)**
- nps_survey.json (Net Promoter Score)
- employee_satisfaction.json
- exit_survey.json
- product_review.json
- service_review.json
- market_research.json
- event_feedback.json
- training_evaluation.json

**6. Lead Generation (5 forms)**
- contact_us.json
- quote_request.json
- demo_request.json
- consultation_booking.json
- free_trial_request.json

**7. HR & Employment (4 forms)**
- job_application.json
- interview_feedback.json
- time_off_request.json
- expense_report.json

### **Template Format Example:**

```json
{
  "form_name": "customer_registration",
  "display_name": "Customer Registration",
  "category": "customer",
  "description": "New customer signup form with email verification",
  "fields": [
    {
      "type": "text",
      "name": "full_name",
      "label": "Full Name",
      "placeholder": "John Smith",
      "required": true,
      "validation": {"minLength": 2}
    },
    {
      "type": "email",
      "name": "email",
      "label": "Email Address",
      "placeholder": "john@example.com",
      "required": true,
      "unique": true
    },
    {
      "type": "password",
      "name": "password",
      "label": "Create Password",
      "required": true,
      "validation": {"minLength": 8}
    },
    {
      "type": "checkbox",
      "name": "terms",
      "label": "I agree to Terms & Conditions",
      "required": true
    }
  ],
  "settings": {
    "submit_button_text": "Create Account",
    "success_message": "Registration successful! Check your email to verify.",
    "redirect_url": "/dashboard",
    "send_confirmation_email": true,
    "send_admin_notification": true,
    "admin_email": "admin@truevault.com"
  }
}
```

### **Verification:**
- [ ] All 50+ templates created
- [ ] Each template is valid JSON
- [ ] All required fields present
- [ ] Can load templates into form builder

---

## 🎨 TASK 14.3: Create 3 Form Styles

**Time:** 2 hours  
**Lines:** ~600 lines (200 per style)  
**Files:** 3 CSS files

### **Style 1: CASUAL**
**File:** `/admin/forms/assets/css/form-casual.css`

**Design:**
- Rounded corners (border-radius: 12px)
- Soft colors (pastels)
- Playful fonts (Quicksand, Poppins)
- Emoji icons
- Gradient backgrounds
- Large buttons with shadows

**Use For:** Fun brands, creative agencies, startups

### **Style 2: BUSINESS**
**File:** `/admin/forms/assets/css/form-business.css`

**Design:**
- Clean lines (border-radius: 6px)
- Professional colors (blues, grays)
- Standard fonts (Inter, Roboto)
- Icon fonts
- Solid backgrounds
- Medium-sized buttons

**Use For:** Professional services, B2B, standard business

### **Style 3: CORPORATE**
**File:** `/admin/forms/assets/css/form-corporate.css`

**Design:**
- Sharp edges (border-radius: 3px)
- Formal colors (navy, charcoal)
- Serif fonts (Georgia, Times)
- Minimal icons
- White/gray backgrounds
- Formal button styling

**Use For:** Legal, finance, enterprise, government

### **Verification:**
- [ ] All 3 styles created
- [ ] Styles are visually distinct
- [ ] All form elements styled
- [ ] Mobile responsive
- [ ] No hardcoded colors (use theme variables)

---

## 🔧 TASK 14.4: Form Library Dashboard

**Time:** 1 hour  
**Lines:** ~300 lines  
**File:** `/admin/forms/index.php`

### **Dashboard Layout:**

```
┌────────────────────────────────────────────────────────────┐
│ 📝 Form Library                [Import] [+ Create Form]    │
├────────────────────────────────────────────────────────────┤
│                                                            │
│ 🔍 Search: [___________]  Category: [All ▼]  Style: [All ▼]│
│                                                            │
│ TEMPLATES (50+)                                            │
│                                                            │
│ ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│ │ Customer │  │ Support  │  │ Payment  │  │ Contact  │   │
│ │ Registra │  │ Ticket   │  │ Form     │  │ Us       │   │
│ │ 127 sub  │  │ 34 sub   │  │ 89 sub   │  │ 456 sub  │   │
│ │ [Use]    │  │ [Use]    │  │ [Use]    │  │ [Use]    │   │
│ │ [Preview]│  │ [Preview]│  │ [Preview]│  │ [Preview]│   │
│ └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
│                                                            │
│ MY FORMS (5)                                               │
│                                                            │
│ ┌──────────┐  ┌──────────┐                                │
│ │ Custom   │  │ VIP      │                                │
│ │ Survey   │  │ Request  │                                │
│ │ [Edit]   │  │ [Edit]   │                                │
│ │ [View]   │  │ [View]   │                                │
│ └──────────┘  └──────────┘                                │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

### **Features:**
- [ ] Grid view of all templates
- [ ] Search/filter forms
- [ ] Category filter
- [ ] Style filter
- [ ] Submission count per form
- [ ] "Use Template" button
- [ ] "Preview" button
- [ ] "Edit" button (for custom forms)
- [ ] "Create Form" button

### **Verification:**
- [ ] Dashboard loads
- [ ] Shows all templates
- [ ] Search works
- [ ] Filters work
- [ ] Buttons functional

---

## 🛠️ TASK 14.5: Visual Form Builder

**Time:** 2 hours  
**Lines:** ~500 lines  
**File:** `/admin/forms/builder.php`

### **Builder Interface:**

```
┌────────────────────────────────────────────────────────────┐
│ ⬅️ Back     Form Builder: "Customer Registration"         │
├────────────────────────────────────────────────────────────┤
│                                                            │
│ FORM SETTINGS                                              │
│ ┌────────────────────────────────────────────────────────┐│
│ │ Form Name: [Customer Registration_______________]     ││
│ │ Style: [Business ▼]                                   ││
│ │ Category: [Customer ▼]                                ││
│ │ Submit Button Text: [Create Account______________]    ││
│ │ Success Message: [Registration successful!________]   ││
│ └────────────────────────────────────────────────────────┘│
│                                                            │
│ ┌──────────────┐  ┌──────────────────────────────────────┐│
│ │ FIELD TYPES  │  │ FORM CANVAS                          ││
│ ├──────────────┤  ├──────────────────────────────────────┤│
│ │ 📝 Text      │  │ ┌──────────────────────────────────┐ ││
│ │ 📧 Email     │  │ │ Full Name * [_________________] │ ││
│ │ 🔢 Number    │  │ │ [Edit] [Delete] ↕                │ ││
│ │ 📞 Phone     │  │ └──────────────────────────────────┘ ││
│ │ 📅 Date      │  │ ┌──────────────────────────────────┐ ││
│ │ ⬇️ Dropdown  │  │ │ Email Address * [_____________] │ ││
│ │ ☑️ Checkbox  │  │ │ [Edit] [Delete] ↕                │ ││
│ │ ⚪ Radio     │  │ └──────────────────────────────────┘ ││
│ │ 📄 Textarea  │  │ ┌──────────────────────────────────┐ ││
│ │ 📎 File      │  │ │ Password * [____________________] │ ││
│ │ ✍️ Signature │  │ │ [Edit] [Delete] ↕                │ ││
│ │ ⭐ Rating    │  │ └──────────────────────────────────┘ ││
│ │              │  │                                      ││
│ │ Drag to add  │  │ [+ Add Field]                        ││
│ └──────────────┘  └──────────────────────────────────────┘│
│                                                            │
│ [Save Form] [Preview] [Publish] [Cancel]                   │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

### **Features:**
- [ ] Drag fields from palette to canvas
- [ ] Reorder fields (drag up/down)
- [ ] Edit field properties (modal)
- [ ] Delete fields
- [ ] Add new fields
- [ ] Form settings panel
- [ ] Save/preview/publish buttons
- [ ] Real-time preview

### **Verification:**
- [ ] Drag-and-drop works
- [ ] Field reordering works
- [ ] Field editor works
- [ ] Can save form
- [ ] Preview shows correctly

---

## 📊 TASK 14.6: Form Preview & Publishing

**Time:** 1 hour  
**Lines:** ~250 lines  
**File:** `/admin/forms/preview.php` and `/admin/forms/embed.php`

### **Preview Mode:**
- [ ] Show form exactly as users will see it
- [ ] Apply selected style (Casual/Business/Corporate)
- [ ] Test form validation
- [ ] Test submission (save to test database)
- [ ] Mobile preview toggle

### **Publishing Options:**
- [ ] Get embed code (iframe)
- [ ] Get direct link
- [ ] Get WordPress shortcode
- [ ] Copy to clipboard buttons

### **Embed Code Example:**
```html
<iframe src="https://vpn.the-truth-publishing.com/forms/customer_registration" 
        width="100%" height="600" frameborder="0"></iframe>
```

### **Verification:**
- [ ] Preview loads correctly
- [ ] Form submits test data
- [ ] Embed codes work
- [ ] Direct links work
- [ ] All 3 styles display correctly

---

## 📤 TASK 14.7: Submission Management

**Time:** 1.5 hours  
**Lines:** ~350 lines  
**File:** `/admin/forms/submissions.php`

### **Submissions Dashboard:**

```
┌────────────────────────────────────────────────────────────┐
│ 📨 Form Submissions: "Customer Registration"              │
├────────────────────────────────────────────────────────────┤
│ Status: [All ▼]  Date: [Last 30 days ▼]  [Export CSV]     │
│                                                            │
│ ┌─────┬──────────┬───────────────────┬─────────┬────────┐ │
│ │ [☑] │ Date     │ Name              │ Email   │ Status │ │
│ ├─────┼──────────┼───────────────────┼─────────┼────────┤ │
│ │ [ ] │ Jan 18   │ John Smith        │ john@.. │ New    │ │
│ │ [ ] │ Jan 18   │ Sarah Jones       │ sarah@..│ Read   │ │
│ │ [ ] │ Jan 17   │ Mike Davis        │ mike@.. │ Proc'd │ │
│ └─────┴──────────┴───────────────────┴─────────┴────────┘ │
│                                                            │
│ Selected: 0    [Showing 1-50 of 127]    [◄] [1] [2] [►]   │
│                                                            │
│ BULK ACTIONS: [Mark as Read] [Delete] [Export Selected]    │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

### **View Submission Detail:**
- [ ] Click row to view full submission
- [ ] Show all field values
- [ ] Show files/attachments
- [ ] Show submission timestamp
- [ ] Show IP address
- [ ] Add admin notes
- [ ] Change status (New/Read/Processed)
- [ ] Reply to submitter (email)

### **Export Options:**
- [ ] Export all submissions as CSV
- [ ] Export selected submissions
- [ ] Export with date filter
- [ ] Include/exclude certain fields

### **Verification:**
- [ ] Can view all submissions
- [ ] Can view submission details
- [ ] Can mark as read/processed
- [ ] Can delete submissions
- [ ] Export works

---

## 🔌 TASK 14.8: API Endpoints

**Time:** 1 hour  
**Lines:** ~350 lines  
**Files:** 4 API files

**1. /api/forms.php** (~100 lines)
- GET - List all forms
- GET /:id - Get form details
- POST - Create new form
- PUT /:id - Update form
- DELETE /:id - Delete form

**2. /api/submissions.php** (~100 lines)
- POST /:form_id - Submit form (public endpoint)
- GET - List submissions (admin only)
- GET /:id - Get submission details
- PUT /:id - Update submission status
- DELETE /:id - Delete submission

**3. /api/templates.php** (~100 lines)
- GET - List all templates
- GET /:id - Get template JSON
- POST /clone - Clone template to custom form

**4. /api/export.php** (~50 lines)
- GET /:form_id - Export submissions as CSV

### **Verification:**
- [ ] All endpoints respond
- [ ] Form submission works (public)
- [ ] Admin endpoints require auth
- [ ] Export works

---

## 🧪 TESTING CHECKLIST

### **Form Templates:**
- [ ] All 50+ templates load
- [ ] Templates valid JSON
- [ ] Can create form from template
- [ ] Can customize template

### **Form Builder:**
- [ ] Can create custom form
- [ ] Drag-and-drop works
- [ ] Field editor works
- [ ] Can save form
- [ ] Can publish form

### **Form Styles:**
- [ ] Casual style displays correctly
- [ ] Business style displays correctly
- [ ] Corporate style displays correctly
- [ ] Can switch between styles
- [ ] Mobile responsive

### **Form Submission:**
- [ ] Form submits successfully
- [ ] Validation works (required fields, email format, etc.)
- [ ] Confirmation email sent
- [ ] Admin notification sent
- [ ] Data saved to database
- [ ] File uploads work

### **Submission Management:**
- [ ] Can view all submissions
- [ ] Can view submission details
- [ ] Can mark as read/processed
- [ ] Can export to CSV
- [ ] Can delete submissions

---

## 📦 FILE STRUCTURE

```
/admin/forms/
├── index.php (library dashboard)
├── builder.php (form builder)
├── preview.php (form preview)
├── embed.php (embeddable forms)
├── submissions.php (submission management)
├── setup-forms.php (database setup)
├── api/
│   ├── forms.php
│   ├── submissions.php
│   ├── templates.php
│   └── export.php
├── templates/ (50+ JSON files)
│   ├── customer_registration.json
│   ├── support_ticket.json
│   └── [48 more...]
├── uploads/ (user-uploaded files)
├── assets/
│   ├── css/
│   │   ├── form-casual.css
│   │   ├── form-business.css
│   │   └── form-corporate.css
│   └── js/
│       ├── form-builder.js
│       ├── form-validator.js
│       └── conditional-logic.js
└── databases/
    └── forms.db
```

---

## 🚀 DEPLOYMENT CHECKLIST

- [ ] All files uploaded
- [ ] forms.db created and writable
- [ ] uploads/ directory writable
- [ ] All 50+ templates present
- [ ] API endpoints accessible
- [ ] Test form submission works
- [ ] Email notifications work
- [ ] No errors in error_log

---

## 📊 SUMMARY

**Total Tasks:** 8 major tasks  
**Total Files:** 60+ files (50+ templates + code files)  
**Total Lines:** ~2,500 lines  
**Total Time:** 8-10 hours  

**Dependencies:**
- Part 1 (Database infrastructure) ✅
- Part 4 (Admin authentication) ✅
- Part 7 (Theme system) ✅

---

**END OF PART 14 CHECKLIST - FORM LIBRARY & BUILDER**
