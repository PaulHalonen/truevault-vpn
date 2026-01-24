<?php
/**
 * TrueVault VPN - Tutorial System Setup
 * Part 16 - Task 16.1 & 16.2
 * Creates tutorials.db and seeds 35 lessons
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_TUTORIALS', DB_PATH . 'tutorials.db');

echo "<h1>Tutorial System Setup</h1>\n";

try {
    $db = new SQLite3(DB_TUTORIALS);
    $db->enableExceptions(true);
    
    // TABLE 1: tutorial_lessons
    $db->exec("CREATE TABLE IF NOT EXISTS tutorial_lessons (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lesson_number INTEGER NOT NULL,
        category TEXT NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        duration_minutes INTEGER DEFAULT 5,
        difficulty TEXT DEFAULT 'beginner',
        lesson_content TEXT NOT NULL,
        video_url TEXT,
        prerequisite_lesson_id INTEGER,
        is_active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Table: tutorial_lessons</p>\n";
    
    // TABLE 2: user_tutorial_progress
    $db->exec("CREATE TABLE IF NOT EXISTS user_tutorial_progress (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        lesson_id INTEGER NOT NULL,
        status TEXT DEFAULT 'not_started',
        current_step INTEGER DEFAULT 0,
        started_at TEXT,
        completed_at TEXT,
        time_spent_minutes INTEGER DEFAULT 0,
        FOREIGN KEY (lesson_id) REFERENCES tutorial_lessons(id),
        UNIQUE(user_id, lesson_id)
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_progress_user ON user_tutorial_progress(user_id)");
    echo "<p>✅ Table: user_tutorial_progress</p>\n";
    
    // TABLE 3: help_bubbles
    $db->exec("CREATE TABLE IF NOT EXISTS help_bubbles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        page_url TEXT NOT NULL,
        element_selector TEXT NOT NULL,
        bubble_title TEXT NOT NULL,
        bubble_content TEXT NOT NULL,
        bubble_position TEXT DEFAULT 'right',
        show_on_hover INTEGER DEFAULT 1,
        auto_show INTEGER DEFAULT 0,
        priority INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Table: help_bubbles</p>\n";
    
    // ========================================
    // SEED 35 TUTORIAL LESSONS
    // ========================================
    
    $lessons = [
        // GETTING STARTED (5 lessons)
        [
            'number' => 1, 'category' => 'getting_started',
            'title' => 'Understanding Databases',
            'description' => 'Learn what databases are in simple terms - no tech jargon!',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'What is a Database?', 'text' => "Think of a database like a super-organized filing cabinet for your computer. Instead of paper files, you store digital information that you can search, sort, and filter instantly.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Tables are Like Folders', 'text' => "Each TABLE in a database is like a folder in your filing cabinet. You might have a 'Customers' table, an 'Orders' table, and a 'Products' table.", 'action' => 'next'],
                ['step' => 3, 'title' => 'Records are Like Papers', 'text' => "Each RECORD is like a single piece of paper in your folder. One customer = one record. One order = one record.", 'action' => 'next'],
                ['step' => 4, 'title' => 'Fields are Like Blanks', 'text' => "FIELDS are the specific pieces of information on each record - like Name, Email, Phone Number. These are the blanks you fill in.", 'action' => 'next'],
                ['step' => 5, 'title' => 'Congratulations!', 'text' => "You now understand the basics of databases! In the next lesson, you'll create your very first table.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 2, 'category' => 'getting_started',
            'title' => 'Your First Table',
            'description' => 'Create your first database table step by step',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Navigate to Database Builder', 'text' => "Go to Admin → Database Builder. This is where all your tables live.", 'action' => 'navigate', 'target' => '/admin/database-builder/'],
                ['step' => 2, 'title' => 'Click Create Table', 'text' => "Look for the blue 'Create Table' button and click it.", 'action' => 'click', 'target' => '.btn-primary'],
                ['step' => 3, 'title' => 'Name Your Table', 'text' => "Type 'contacts' as your table name. Use lowercase letters and underscores, no spaces!", 'action' => 'input', 'target' => '#tableName'],
                ['step' => 4, 'title' => 'Add Display Name', 'text' => "Type 'My Contacts' as the display name. This is what you'll see in the interface.", 'action' => 'input', 'target' => '#displayName'],
                ['step' => 5, 'title' => 'Save Your Table', 'text' => "Click the Save button to create your first table!", 'action' => 'click', 'target' => '#saveBtn'],
                ['step' => 6, 'title' => 'Success!', 'text' => "You created your first database table! Next, we'll add fields to store contact information.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 3, 'category' => 'getting_started',
            'title' => 'Adding Fields',
            'description' => 'Add name, email, and phone fields to your table',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Open Field Editor', 'text' => "Click on your 'contacts' table, then click 'Edit Fields'.", 'action' => 'navigate', 'target' => '/admin/database-builder/field-editor.php'],
                ['step' => 2, 'title' => 'Add Name Field', 'text' => "Click 'Add Field', set Type to 'Text', Name to 'full_name', Label to 'Full Name'. Check 'Required'.", 'action' => 'guided'],
                ['step' => 3, 'title' => 'Add Email Field', 'text' => "Add another field: Type 'Email', Name 'email', Label 'Email Address'. Check 'Required' and 'Unique'.", 'action' => 'guided'],
                ['step' => 4, 'title' => 'Add Phone Field', 'text' => "Add one more: Type 'Phone', Name 'phone', Label 'Phone Number'. Leave optional.", 'action' => 'guided'],
                ['step' => 5, 'title' => 'Save Fields', 'text' => "Click Save to add all three fields to your table.", 'action' => 'click', 'target' => '#saveBtn'],
                ['step' => 6, 'title' => 'Perfect!', 'text' => "Your contacts table now has name, email, and phone fields. Let's add some data!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 4, 'category' => 'getting_started',
            'title' => 'Adding Records',
            'description' => 'Insert your first customer record',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Open Data View', 'text' => "Go to Database Builder → Data Management → Select 'contacts' table.", 'action' => 'navigate', 'target' => '/admin/database-builder/data.php'],
                ['step' => 2, 'title' => 'Click Add Record', 'text' => "Click the green 'Add Record' button to create a new contact.", 'action' => 'click', 'target' => '.btn-success'],
                ['step' => 3, 'title' => 'Fill In Details', 'text' => "Enter: Name: 'John Smith', Email: 'john@example.com', Phone: '555-123-4567'", 'action' => 'input'],
                ['step' => 4, 'title' => 'Save Record', 'text' => "Click Save to store this contact in your database.", 'action' => 'click', 'target' => '#saveRecord'],
                ['step' => 5, 'title' => 'You Did It!', 'text' => "Your first record is saved! You can now view, edit, or delete it anytime.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 5, 'category' => 'getting_started',
            'title' => 'Viewing & Editing Data',
            'description' => 'Browse, search, and edit your records',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'View Your Data', 'text' => "You should see your contact in a spreadsheet-like view. Each row is a record.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Try Searching', 'text' => "Type 'John' in the search box. Watch how it filters to show only matching records.", 'action' => 'input', 'target' => '#searchInput'],
                ['step' => 3, 'title' => 'Edit a Record', 'text' => "Double-click on the phone number cell. Change it to a new number and press Enter.", 'action' => 'doubleclick'],
                ['step' => 4, 'title' => 'Auto-Save', 'text' => "Notice how changes save automatically! No need to click Save for inline edits.", 'action' => 'next'],
                ['step' => 5, 'title' => 'Getting Started Complete!', 'text' => "🎉 Congratulations! You've completed the Getting Started tutorials. You now know the basics of databases!", 'action' => 'complete'],
            ]
        ],
        
        // DATABASE BUILDER (10 lessons)
        [
            'number' => 6, 'category' => 'database',
            'title' => 'Field Types Overview',
            'description' => 'Tour of all 15 field types and when to use each',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Text Fields', 'text' => "TEXT: For names, titles, short answers. EMAIL: Auto-validates email format. URL: For website links.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Number Fields', 'text' => "INTEGER: Whole numbers (1, 2, 3). DECIMAL: Numbers with decimals (19.99). CURRENCY: For money.", 'action' => 'next'],
                ['step' => 3, 'title' => 'Date & Time', 'text' => "DATE: For dates. TIME: For times. DATETIME: Both together.", 'action' => 'next'],
                ['step' => 4, 'title' => 'Selection Fields', 'text' => "SELECT: Dropdown menu. CHECKBOX: Yes/No toggle. RADIO: Choose one option.", 'action' => 'next'],
                ['step' => 5, 'title' => 'Special Fields', 'text' => "TEXTAREA: Long text. FILE: Upload documents. PASSWORD: Hidden input. PHONE: Phone numbers.", 'action' => 'next'],
                ['step' => 6, 'title' => 'Choose Wisely', 'text' => "Using the right field type helps with validation and makes data entry easier!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 7, 'category' => 'database',
            'title' => 'Required Fields',
            'description' => 'Make fields mandatory so users can\'t skip them',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Why Required?', 'text' => "Required fields MUST be filled in before saving. Use for critical data like email or name.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Open Field Editor', 'text' => "Go to your contacts table and open Field Editor.", 'action' => 'navigate'],
                ['step' => 3, 'title' => 'Find Required Checkbox', 'text' => "Click on a field. Look for the 'Required' checkbox in the settings.", 'action' => 'guided'],
                ['step' => 4, 'title' => 'Check It', 'text' => "Check the 'Required' box. A red asterisk (*) will appear next to the field label.", 'action' => 'click'],
                ['step' => 5, 'title' => 'Test It', 'text' => "Try adding a record without filling in the required field. It won't save!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 8, 'category' => 'database',
            'title' => 'Unique Fields',
            'description' => 'Prevent duplicate values like duplicate emails',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Why Unique?', 'text' => "Unique fields prevent duplicates. Two customers can't have the same email address!", 'action' => 'next'],
                ['step' => 2, 'title' => 'Find Unique Setting', 'text' => "In Field Editor, click on the email field. Find the 'Unique' checkbox.", 'action' => 'guided'],
                ['step' => 3, 'title' => 'Enable Unique', 'text' => "Check the 'Unique' box for the email field.", 'action' => 'click'],
                ['step' => 4, 'title' => 'Test It', 'text' => "Try adding two contacts with the same email. The second one will be rejected!", 'action' => 'guided'],
                ['step' => 5, 'title' => 'Great for IDs', 'text' => "Use unique for: emails, usernames, order numbers, product SKUs.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 9, 'category' => 'database',
            'title' => 'Default Values',
            'description' => 'Set automatic defaults to save time',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'What are Defaults?', 'text' => "Defaults are pre-filled values. New records start with these values automatically.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Common Defaults', 'text' => "Examples: Status = 'Active', Country = 'USA', Created Date = Today", 'action' => 'next'],
                ['step' => 3, 'title' => 'Set a Default', 'text' => "In Field Editor, find 'Default Value' input. Type your default.", 'action' => 'guided'],
                ['step' => 4, 'title' => 'Special Defaults', 'text' => "Use CURRENT_TIMESTAMP for auto-dates, or leave blank for no default.", 'action' => 'next'],
                ['step' => 5, 'title' => 'Time Saver!', 'text' => "Defaults speed up data entry. Users can always override them if needed.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 10, 'category' => 'database',
            'title' => 'Dropdown Options',
            'description' => 'Create dropdown menus for consistent data',
            'duration' => 7, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Why Dropdowns?', 'text' => "Dropdowns ensure consistent data. Instead of typing 'Active', 'active', 'ACTIVE' - everyone picks from the same list.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Add Select Field', 'text' => "Create a new field with Type = 'Select'. Name it 'status'.", 'action' => 'guided'],
                ['step' => 3, 'title' => 'Add Options', 'text' => "Find 'Options' input. Add each option on a new line: Active, Inactive, Pending", 'action' => 'input'],
                ['step' => 4, 'title' => 'Set Default', 'text' => "Set default to 'Active' so new records start as active.", 'action' => 'input'],
                ['step' => 5, 'title' => 'Save & Test', 'text' => "Save the field. Now when adding records, you'll see a dropdown!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 11, 'category' => 'database',
            'title' => 'One-to-Many Relationships',
            'description' => 'Link customers to their orders',
            'duration' => 10, 'difficulty' => 'intermediate',
            'content' => [
                ['step' => 1, 'title' => 'What is One-to-Many?', 'text' => "ONE customer can have MANY orders. ONE category can have MANY products. This is the most common relationship.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Create Orders Table', 'text' => "Create a new table called 'orders' with fields: order_date, total, status.", 'action' => 'guided'],
                ['step' => 3, 'title' => 'Add Customer Field', 'text' => "Add a field 'customer_id' with Type = 'Integer'. This will link to contacts.", 'action' => 'guided'],
                ['step' => 4, 'title' => 'Create Relationship', 'text' => "Go to Relationships tab. Click 'Add Relationship'. Select contacts (parent) → orders (child).", 'action' => 'navigate'],
                ['step' => 5, 'title' => 'Map Fields', 'text' => "Link contacts.id → orders.customer_id. This connects each order to a customer.", 'action' => 'guided'],
                ['step' => 6, 'title' => 'Test It', 'text' => "Now when viewing a customer, you can see all their orders!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 12, 'category' => 'database',
            'title' => 'Validation Rules',
            'description' => 'Ensure data quality with validation',
            'duration' => 7, 'difficulty' => 'intermediate',
            'content' => [
                ['step' => 1, 'title' => 'Why Validate?', 'text' => "Validation catches errors BEFORE saving. Invalid email? Number too high? Validation stops it.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Built-In Validation', 'text' => "Email fields auto-validate format. Phone fields check for valid numbers. Dates must be real dates.", 'action' => 'next'],
                ['step' => 3, 'title' => 'Min/Max Length', 'text' => "Set minimum and maximum character lengths. Names must be 2+ characters.", 'action' => 'guided'],
                ['step' => 4, 'title' => 'Number Ranges', 'text' => "For numbers, set min/max values. Quantity must be 1-100.", 'action' => 'guided'],
                ['step' => 5, 'title' => 'Custom Patterns', 'text' => "Advanced: Use regex patterns for custom validation like ZIP codes.", 'action' => 'next'],
                ['step' => 6, 'title' => 'Clean Data!', 'text' => "With validation, your database stays clean and consistent.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 13, 'category' => 'database',
            'title' => 'Import from CSV',
            'description' => 'Bulk import existing data from spreadsheets',
            'duration' => 7, 'difficulty' => 'intermediate',
            'content' => [
                ['step' => 1, 'title' => 'What is CSV?', 'text' => "CSV = Comma-Separated Values. It's the universal format for spreadsheet data. Excel can save as CSV.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Prepare Your CSV', 'text' => "Make sure the first row has column headers that match your field names.", 'action' => 'next'],
                ['step' => 3, 'title' => 'Go to Import', 'text' => "Database Builder → Import/Export → Select your table → Click 'Import CSV'", 'action' => 'navigate'],
                ['step' => 4, 'title' => 'Upload File', 'text' => "Drag and drop your CSV file or click to browse.", 'action' => 'guided'],
                ['step' => 5, 'title' => 'Map Columns', 'text' => "Match each CSV column to a database field. Preview shows first 5 rows.", 'action' => 'guided'],
                ['step' => 6, 'title' => 'Import!', 'text' => "Click Import. Watch the progress bar. Thousands of records in seconds!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 14, 'category' => 'database',
            'title' => 'Export to Excel',
            'description' => 'Backup your data or share with others',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Why Export?', 'text' => "Export to: backup data, share with others, analyze in Excel, migrate to another system.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Go to Export', 'text' => "Database Builder → Import/Export → Select your table", 'action' => 'navigate'],
                ['step' => 3, 'title' => 'Choose Format', 'text' => "Click 'Export CSV' or 'Export JSON'. CSV opens in Excel.", 'action' => 'click'],
                ['step' => 4, 'title' => 'Download', 'text' => "File downloads automatically. Open it in Excel, Google Sheets, or any spreadsheet app.", 'action' => 'next'],
                ['step' => 5, 'title' => 'Regular Backups', 'text' => "Pro tip: Export your data weekly as a backup!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 15, 'category' => 'database',
            'title' => 'Many-to-Many Relationships',
            'description' => 'Advanced: Products and Categories',
            'duration' => 10, 'difficulty' => 'advanced',
            'content' => [
                ['step' => 1, 'title' => 'What is Many-to-Many?', 'text' => "One product can be in MANY categories. One category can have MANY products. This needs a junction table.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Create Products Table', 'text' => "Create 'products' table with: name, price, description.", 'action' => 'guided'],
                ['step' => 3, 'title' => 'Create Categories Table', 'text' => "Create 'categories' table with: name, description.", 'action' => 'guided'],
                ['step' => 4, 'title' => 'Create Junction Table', 'text' => "Create 'product_categories' with: product_id, category_id. This links them!", 'action' => 'guided'],
                ['step' => 5, 'title' => 'Add Relationships', 'text' => "Create 2 relationships: products → junction, categories → junction.", 'action' => 'guided'],
                ['step' => 6, 'title' => 'Advanced Complete!', 'text' => "You've mastered the most complex relationship type. 🎓", 'action' => 'complete'],
            ]
        ],
        
        // FORM BUILDER (10 lessons) - lessons 16-25
        [
            'number' => 16, 'category' => 'forms',
            'title' => 'Using Form Templates',
            'description' => 'Start with a pre-built template and customize',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Form Library', 'text' => "Go to Admin → Forms. You'll see dozens of pre-built templates.", 'action' => 'navigate', 'target' => '/admin/forms/'],
                ['step' => 2, 'title' => 'Browse Categories', 'text' => "Templates are organized: Customer, Support, Payment, Registration, Survey, Lead Gen.", 'action' => 'next'],
                ['step' => 3, 'title' => 'Preview a Template', 'text' => "Click 'Preview' on any template to see how it looks.", 'action' => 'click'],
                ['step' => 4, 'title' => 'Use Template', 'text' => "Found one you like? Click 'Use' to create your own copy.", 'action' => 'click'],
                ['step' => 5, 'title' => 'Customize', 'text' => "Now you can edit fields, change labels, add/remove questions.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 17, 'category' => 'forms',
            'title' => 'Creating Custom Forms',
            'description' => 'Build a form from scratch with drag-and-drop',
            'duration' => 7, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Open Form Builder', 'text' => "Go to Forms → Create Form. This opens the visual builder.", 'action' => 'navigate', 'target' => '/admin/forms/builder.php'],
                ['step' => 2, 'title' => 'Name Your Form', 'text' => "Enter a name like 'Customer Feedback'. Choose a category.", 'action' => 'input'],
                ['step' => 3, 'title' => 'Drag Fields', 'text' => "From the left sidebar, drag fields onto the canvas. Try Text, Email, and Textarea.", 'action' => 'drag'],
                ['step' => 4, 'title' => 'Configure Field', 'text' => "Click a field to edit: change label, add placeholder, mark as required.", 'action' => 'click'],
                ['step' => 5, 'title' => 'Reorder Fields', 'text' => "Drag fields up or down to reorder them.", 'action' => 'drag'],
                ['step' => 6, 'title' => 'Save Form', 'text' => "Click Save. Your form is ready to use!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 18, 'category' => 'forms',
            'title' => 'Form Styling',
            'description' => 'Choose Casual, Business, or Corporate styles',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Three Styles', 'text' => "Each form can use one of three styles: Casual (fun), Business (professional), Corporate (formal).", 'action' => 'next'],
                ['step' => 2, 'title' => 'Casual Style', 'text' => "Bright colors, rounded corners, friendly tone. Great for surveys and feedback.", 'action' => 'next'],
                ['step' => 3, 'title' => 'Business Style', 'text' => "Clean, professional look. Blue accents. Perfect for most business forms.", 'action' => 'next'],
                ['step' => 4, 'title' => 'Corporate Style', 'text' => "Dark theme, minimal, sophisticated. For VIP clients and executive portals.", 'action' => 'next'],
                ['step' => 5, 'title' => 'Change Style', 'text' => "In Form Builder, use the 'Style' dropdown to switch instantly.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 19, 'category' => 'forms',
            'title' => 'Conditional Logic',
            'description' => 'Show or hide fields based on answers',
            'duration' => 10, 'difficulty' => 'intermediate',
            'content' => [
                ['step' => 1, 'title' => 'What is Conditional Logic?', 'text' => "Show fields ONLY when certain conditions are met. Example: Show 'Company Name' only if they select 'Business' account.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Add Trigger Field', 'text' => "First, add a dropdown field like 'Account Type' with options: Personal, Business.", 'action' => 'guided'],
                ['step' => 3, 'title' => 'Add Conditional Field', 'text' => "Add a text field 'Company Name'. Click to edit it.", 'action' => 'guided'],
                ['step' => 4, 'title' => 'Set Condition', 'text' => "Find 'Show If' setting. Select: Account Type = Business.", 'action' => 'guided'],
                ['step' => 5, 'title' => 'Test It', 'text' => "Preview the form. Company Name only appears when Business is selected!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 20, 'category' => 'forms',
            'title' => 'File Upload Fields',
            'description' => 'Accept documents, images, and attachments',
            'duration' => 7, 'difficulty' => 'intermediate',
            'content' => [
                ['step' => 1, 'title' => 'Add File Field', 'text' => "Drag the 'File' field type onto your form.", 'action' => 'drag'],
                ['step' => 2, 'title' => 'Configure Limits', 'text' => "Set max file size (e.g., 5MB) and allowed types (.pdf, .jpg, .png).", 'action' => 'guided'],
                ['step' => 3, 'title' => 'Multiple Files', 'text' => "Enable 'Allow Multiple' to let users upload several files at once.", 'action' => 'checkbox'],
                ['step' => 4, 'title' => 'Security', 'text' => "Files are scanned for viruses and stored securely.", 'action' => 'next'],
                ['step' => 5, 'title' => 'Accessing Files', 'text' => "View uploaded files in the Submissions area. Download or preview them.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 21, 'category' => 'forms',
            'title' => 'Multi-Page Forms',
            'description' => 'Break long forms into steps with progress indicator',
            'duration' => 10, 'difficulty' => 'intermediate',
            'content' => [
                ['step' => 1, 'title' => 'Why Multiple Pages?', 'text' => "Long forms feel overwhelming. Break them into steps for better completion rates.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Add Page Break', 'text' => "Drag the 'Page Break' element between fields where you want to split.", 'action' => 'drag'],
                ['step' => 3, 'title' => 'Name Each Page', 'text' => "Click the page break to name each section: 'Contact Info', 'Details', 'Confirm'.", 'action' => 'input'],
                ['step' => 4, 'title' => 'Progress Indicator', 'text' => "A progress bar automatically shows: Step 1 of 3.", 'action' => 'next'],
                ['step' => 5, 'title' => 'Navigation', 'text' => "Users see 'Next' and 'Previous' buttons. They can't skip required fields.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 22, 'category' => 'forms',
            'title' => 'Email Notifications',
            'description' => 'Auto-send confirmations when forms are submitted',
            'duration' => 7, 'difficulty' => 'intermediate',
            'content' => [
                ['step' => 1, 'title' => 'Two Types of Emails', 'text' => "1) Confirmation to user 2) Notification to admin. Both can be automated.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Open Form Settings', 'text' => "In Form Builder, click the Settings tab.", 'action' => 'click'],
                ['step' => 3, 'title' => 'Enable Confirmation', 'text' => "Check 'Send confirmation email'. Enter subject and message.", 'action' => 'checkbox'],
                ['step' => 4, 'title' => 'Admin Notification', 'text' => "Check 'Notify admin'. Enter your email to receive alerts.", 'action' => 'checkbox'],
                ['step' => 5, 'title' => 'Use Variables', 'text' => "Use {name}, {email} in messages to personalize them!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 23, 'category' => 'forms',
            'title' => 'Form Embedding',
            'description' => 'Add your form to any website',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Get Embed Code', 'text' => "Open your form, click 'Share' or 'Embed'. Copy the HTML code.", 'action' => 'click'],
                ['step' => 2, 'title' => 'Two Options', 'text' => "Embed as iframe (easiest) or direct link (for email campaigns).", 'action' => 'next'],
                ['step' => 3, 'title' => 'Paste on Website', 'text' => "Paste the iframe code into your website's HTML where you want the form.", 'action' => 'next'],
                ['step' => 4, 'title' => 'Responsive', 'text' => "Forms automatically resize for mobile devices.", 'action' => 'next'],
                ['step' => 5, 'title' => 'Done!', 'text' => "Your form is now live on your website and collecting submissions.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 24, 'category' => 'forms',
            'title' => 'Viewing Submissions',
            'description' => 'Manage and respond to form responses',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Submissions Page', 'text' => "Go to Forms → Submissions. See all responses in one place.", 'action' => 'navigate', 'target' => '/admin/forms/submissions.php'],
                ['step' => 2, 'title' => 'Filter by Form', 'text' => "Use the dropdown to filter submissions by specific form.", 'action' => 'select'],
                ['step' => 3, 'title' => 'View Details', 'text' => "Click 'View' to see full submission details in a modal.", 'action' => 'click'],
                ['step' => 4, 'title' => 'Change Status', 'text' => "Mark as Read, Processed, or Spam. Track what you've handled.", 'action' => 'select'],
                ['step' => 5, 'title' => 'Delete', 'text' => "Delete spam or old submissions to keep things clean.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 25, 'category' => 'forms',
            'title' => 'Export Form Data',
            'description' => 'Download submissions to Excel',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Why Export?', 'text' => "Export to analyze in Excel, share with team, or backup data.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Select Form', 'text' => "In Submissions, select the form you want to export.", 'action' => 'select'],
                ['step' => 3, 'title' => 'Click Export', 'text' => "Click the 'Export' button. Choose CSV or Excel format.", 'action' => 'click'],
                ['step' => 4, 'title' => 'Open in Excel', 'text' => "File downloads. Open it in Excel or Google Sheets.", 'action' => 'next'],
                ['step' => 5, 'title' => 'Form Builder Complete!', 'text' => "🎉 You've mastered the Form Builder! Create amazing forms.", 'action' => 'complete'],
            ]
        ],
        
        // MARKETING AUTOMATION (10 lessons) - lessons 26-35
        [
            'number' => 26, 'category' => 'marketing',
            'title' => 'Platform Overview',
            'description' => 'Tour of 50+ free advertising platforms',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'Marketing Dashboard', 'text' => "Go to Admin → Marketing. This is your command center.", 'action' => 'navigate', 'target' => '/admin/marketing/'],
                ['step' => 2, 'title' => '50+ Platforms', 'text' => "We've configured 50+ FREE platforms: social media, press releases, directories, and more.", 'action' => 'next'],
                ['step' => 3, 'title' => 'Platform Types', 'text' => "📱 Social (10), 📰 Press Releases (20), 📋 Classifieds (5), 📍 Directories (10), ✍️ Content (5)", 'action' => 'next'],
                ['step' => 4, 'title' => 'Zero Cost', 'text' => "ALL platforms are free. No ad budget needed!", 'action' => 'next'],
                ['step' => 5, 'title' => 'Explore', 'text' => "Click 'Platforms' to see the full list and configure them.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 27, 'category' => 'marketing',
            'title' => 'Activating Automation',
            'description' => 'Turn on daily posting with one click',
            'duration' => 5, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'The Power of Automation', 'text' => "Once activated, the system posts to multiple platforms DAILY. Set it and forget it!", 'action' => 'next'],
                ['step' => 2, 'title' => 'Run Engine', 'text' => "Click the green 'Run Now' button to process today's scheduled posts.", 'action' => 'click'],
                ['step' => 3, 'title' => 'Daily Cron', 'text' => "For full automation, set up a cron job to run daily at 9am.", 'action' => 'next'],
                ['step' => 4, 'title' => 'Check Status', 'text' => "Dashboard shows: Posts today, Scheduled, Manual queue, Posted.", 'action' => 'next'],
                ['step' => 5, 'title' => 'It's Running!', 'text' => "Your marketing is now on autopilot!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 28, 'category' => 'marketing',
            'title' => 'Content Calendar',
            'description' => '365 days of pre-planned content explained',
            'duration' => 7, 'difficulty' => 'beginner',
            'content' => [
                ['step' => 1, 'title' => 'What is the Calendar?', 'text' => "365 days of content, pre-written and scheduled. Every day has posts ready.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Daily Themes', 'text' => "Mon: Tips, Tue: News, Wed: Testimonials, Thu: Features, Fri: Deals, Sat: Facts, Sun: Roundup", 'action' => 'next'],
                ['step' => 3, 'title' => 'Holiday Specials', 'text' => "18 holidays automatically get special promotions with discount codes.", 'action' => 'next'],
                ['step' => 4, 'title' => 'View Calendar', 'text' => "Click 'Calendar' to see the month view. Click any day to see its content.", 'action' => 'navigate'],
                ['step' => 5, 'title' => 'Generate New', 'text' => "Need next year's calendar? Click 'Generate' and it's created instantly!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 29, 'category' => 'marketing',
            'title' => 'Customizing Posts',
            'description' => 'Edit scheduled content before it goes live',
            'duration' => 7, 'difficulty' => 'intermediate',
            'content' => [
                ['step' => 1, 'title' => 'Find a Post', 'text' => "Open the Calendar. Click on any day to see scheduled content.", 'action' => 'navigate'],
                ['step' => 2, 'title' => 'View Details', 'text' => "A modal shows: title, content, platforms, status.", 'action' => 'click'],
                ['step' => 3, 'title' => 'Edit Content', 'text' => "Click 'Edit' to modify the post text. Personalize it!", 'action' => 'click'],
                ['step' => 4, 'title' => 'Change Platforms', 'text' => "Add or remove platforms. Maybe add Instagram for this one.", 'action' => 'guided'],
                ['step' => 5, 'title' => 'Save Changes', 'text' => "Click Save. Your customized post will go out instead.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 30, 'category' => 'marketing',
            'title' => 'Manual Posting Queue',
            'description' => 'Handle platforms without API access',
            'duration' => 7, 'difficulty' => 'intermediate',
            'content' => [
                ['step' => 1, 'title' => 'Why Manual?', 'text' => "Some platforms (Craigslist, Reddit) don't allow automated posting. You do it yourself.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Manual Queue', 'text' => "Go to Marketing → Manual Queue. These need your attention.", 'action' => 'navigate', 'target' => '/admin/marketing/manual-queue.php'],
                ['step' => 3, 'title' => 'Copy Content', 'text' => "Click 'Copy Text' to copy the pre-written post to clipboard.", 'action' => 'click'],
                ['step' => 4, 'title' => 'Open Platform', 'text' => "Click 'Open Platform' to go to their site. Paste and post!", 'action' => 'click'],
                ['step' => 5, 'title' => 'Mark Complete', 'text' => "After posting, click 'Mark as Posted' to track it.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 31, 'category' => 'marketing',
            'title' => 'Holiday Promotions',
            'description' => 'Auto-adjust pricing for holidays',
            'duration' => 7, 'difficulty' => 'intermediate',
            'content' => [
                ['step' => 1, 'title' => '18 Holidays', 'text' => "The calendar includes 18 major holidays with automatic promotions.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Auto Discounts', 'text' => "Each holiday has a pre-set discount: New Years 50%, Black Friday 60%, etc.", 'action' => 'next'],
                ['step' => 3, 'title' => 'Discount Codes', 'text' => "Unique codes generated: NEWYEAR2026, BLACKFRI2026, etc.", 'action' => 'next'],
                ['step' => 4, 'title' => 'Edit Discounts', 'text' => "Don't like the discount? Edit it in the calendar entry.", 'action' => 'guided'],
                ['step' => 5, 'title' => 'Automatic!', 'text' => "Holiday posts go out automatically on the right day.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 32, 'category' => 'marketing',
            'title' => 'Analytics Dashboard',
            'description' => 'Track performance across all platforms',
            'duration' => 7, 'difficulty' => 'intermediate',
            'content' => [
                ['step' => 1, 'title' => 'Open Analytics', 'text' => "Go to Marketing → Analytics. See your performance data.", 'action' => 'navigate', 'target' => '/admin/marketing/analytics.php'],
                ['step' => 2, 'title' => 'Key Metrics', 'text' => "Total Posts, Impressions, Clicks, Click-Through Rate (CTR).", 'action' => 'next'],
                ['step' => 3, 'title' => 'Top Platforms', 'text' => "See which platforms drive the most clicks.", 'action' => 'next'],
                ['step' => 4, 'title' => 'Content Performance', 'text' => "Which content types work best? Tips? Promotions?", 'action' => 'next'],
                ['step' => 5, 'title' => 'Date Ranges', 'text' => "Filter by 7 days, 30 days, 90 days, or full year.", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 33, 'category' => 'marketing',
            'title' => 'Adding New Platforms',
            'description' => 'Configure additional channels',
            'duration' => 5, 'difficulty' => 'intermediate',
            'content' => [
                ['step' => 1, 'title' => 'Platform Management', 'text' => "Go to Marketing → Platforms. See all 50+ platforms.", 'action' => 'navigate', 'target' => '/admin/marketing/platforms.php'],
                ['step' => 2, 'title' => 'Activate/Deactivate', 'text' => "Click 'Active' button to toggle any platform on or off.", 'action' => 'click'],
                ['step' => 3, 'title' => 'Add Custom', 'text' => "Found a new platform? Click 'Add Platform' and enter details.", 'action' => 'click'],
                ['step' => 4, 'title' => 'Set Frequency', 'text' => "Choose posting frequency: daily, weekly, or monthly.", 'action' => 'select'],
                ['step' => 5, 'title' => 'Growing Reach', 'text' => "More platforms = more visibility = more customers!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 34, 'category' => 'marketing',
            'title' => 'API Integrations',
            'description' => 'Connect Facebook, Twitter, LinkedIn APIs',
            'duration' => 10, 'difficulty' => 'advanced',
            'content' => [
                ['step' => 1, 'title' => 'What are APIs?', 'text' => "APIs let us post automatically to platforms like Facebook without manual work.", 'action' => 'next'],
                ['step' => 2, 'title' => 'Get API Keys', 'text' => "Each platform requires you to create a developer app and get API keys.", 'action' => 'next'],
                ['step' => 3, 'title' => 'Facebook Setup', 'text' => "Go to developers.facebook.com, create app, get Page access token.", 'action' => 'next'],
                ['step' => 4, 'title' => 'Enter Keys', 'text' => "In Platforms, click 'API Config' next to Facebook. Paste your keys.", 'action' => 'click'],
                ['step' => 5, 'title' => 'Test Connection', 'text' => "Click 'Test' to verify the API works. Green checkmark = success!", 'action' => 'complete'],
            ]
        ],
        [
            'number' => 35, 'category' => 'marketing',
            'title' => 'Performance Optimization',
            'description' => 'Improve click-through rates',
            'duration' => 10, 'difficulty' => 'advanced',
            'content' => [
                ['step' => 1, 'title' => 'Check Analytics', 'text' => "Start by reviewing which posts get the most clicks.", 'action' => 'navigate'],
                ['step' => 2, 'title' => 'Best Performing', 'text' => "Identify patterns: Do tips outperform promotions? Which platforms work best?", 'action' => 'next'],
                ['step' => 3, 'title' => 'Double Down', 'text' => "Post MORE of what works. Reduce what doesn't.", 'action' => 'next'],
                ['step' => 4, 'title' => 'Test Headlines', 'text' => "Try different titles. Questions often work better than statements.", 'action' => 'next'],
                ['step' => 5, 'title' => 'Timing', 'text' => "Test posting at different times. 9am vs noon vs evening.", 'action' => 'next'],
                ['step' => 6, 'title' => 'Congratulations!', 'text' => "🎓 You've completed ALL tutorials! You're now a TrueVault expert.", 'action' => 'complete'],
            ]
        ],
    ];
    
    // Insert lessons
    $stmt = $db->prepare("INSERT INTO tutorial_lessons (lesson_number, category, title, description, duration_minutes, difficulty, lesson_content) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($lessons as $lesson) {
        $stmt->reset();
        $stmt->bindValue(1, $lesson['number'], SQLITE3_INTEGER);
        $stmt->bindValue(2, $lesson['category'], SQLITE3_TEXT);
        $stmt->bindValue(3, $lesson['title'], SQLITE3_TEXT);
        $stmt->bindValue(4, $lesson['description'], SQLITE3_TEXT);
        $stmt->bindValue(5, $lesson['duration'], SQLITE3_INTEGER);
        $stmt->bindValue(6, $lesson['difficulty'], SQLITE3_TEXT);
        $stmt->bindValue(7, json_encode($lesson['content']), SQLITE3_TEXT);
        $stmt->execute();
    }
    
    echo "<p>✅ Inserted " . count($lessons) . " tutorial lessons</p>\n";
    
    // ========================================
    // SEED HELP BUBBLES
    // ========================================
    
    $bubbles = [
        ['/admin/database-builder/', '#createTableBtn', 'Create Table', 'Click here to create a new database table. Tables are like folders that hold your data.', 'bottom', 1, 1],
        ['/admin/database-builder/data.php', '#searchInput', 'Search Records', 'Type here to instantly filter and find records. Try searching by name or email.', 'bottom', 1, 0],
        ['/admin/database-builder/field-editor.php', '.field-type-select', 'Field Types', 'Choose the right type for your data: Text for names, Email for emails, Number for quantities.', 'right', 1, 0],
        ['/admin/forms/', '.btn-primary', 'Create Form', 'Build custom forms with our drag-and-drop builder. No coding required!', 'bottom', 1, 1],
        ['/admin/forms/builder.php', '.field-type', 'Drag Fields', 'Drag these field types onto the canvas to build your form.', 'right', 1, 1],
        ['/admin/marketing/', '.btn-success', 'Run Automation', 'Click to process all scheduled posts for today. Watch the magic happen!', 'bottom', 1, 0],
        ['/admin/marketing/platforms.php', '.toggle-on', 'Toggle Platform', 'Click to enable or disable this platform for posting.', 'left', 1, 0],
    ];
    
    $stmt = $db->prepare("INSERT INTO help_bubbles (page_url, element_selector, bubble_title, bubble_content, bubble_position, show_on_hover, auto_show) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($bubbles as $b) {
        $stmt->reset();
        $stmt->bindValue(1, $b[0], SQLITE3_TEXT);
        $stmt->bindValue(2, $b[1], SQLITE3_TEXT);
        $stmt->bindValue(3, $b[2], SQLITE3_TEXT);
        $stmt->bindValue(4, $b[3], SQLITE3_TEXT);
        $stmt->bindValue(5, $b[4], SQLITE3_TEXT);
        $stmt->bindValue(6, $b[5], SQLITE3_INTEGER);
        $stmt->bindValue(7, $b[6], SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    echo "<p>✅ Inserted " . count($bubbles) . " help bubbles</p>\n";
    
    $db->close();
    
    echo "<h2>✅ Tutorial System Setup Complete!</h2>\n";
    echo "<p>35 lessons created across 4 categories.</p>\n";
    echo "<p><a href='index.php'>Go to Tutorial Dashboard</a></p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
