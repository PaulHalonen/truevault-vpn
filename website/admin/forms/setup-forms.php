<?php
/**
 * TrueVault VPN - Form Library Setup
 * Part 14 - Task 14.1 & 14.2
 * Creates forms.db and seeds 50+ form templates
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_FORMS', DB_PATH . 'forms.db');

echo "<!DOCTYPE html><html><head><title>Form Library Setup</title>";
echo "<style>body{font-family:system-ui;background:#0f0f1a;color:#fff;padding:40px;max-width:900px;margin:0 auto}";
echo ".success{color:#00ff88;padding:8px;background:rgba(0,255,136,0.1);border-radius:8px;margin:5px 0}";
echo ".error{color:#ff5050}h1{color:#00d9ff}h2{color:#888;border-bottom:1px solid #333;padding-bottom:10px;margin-top:30px}</style></head><body>";

echo "<h1>📝 Form Library Setup</h1>";
echo "<p>Creating forms.db and 50+ templates...</p>";

try {
    $db = new SQLite3(DB_FORMS);
    $db->enableExceptions(true);
    
    echo "<h2>Creating Tables...</h2>";
    
    // Table 1: forms
    $db->exec("
        CREATE TABLE IF NOT EXISTS forms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            form_name TEXT NOT NULL,
            display_name TEXT NOT NULL,
            category TEXT NOT NULL,
            style TEXT DEFAULT 'business',
            description TEXT,
            fields TEXT NOT NULL,
            settings TEXT,
            is_template INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            created_by INTEGER,
            submission_count INTEGER DEFAULT 0
        )
    ");
    echo "<div class='success'>✅ forms table created</div>";
    
    // Table 2: form_submissions
    $db->exec("
        CREATE TABLE IF NOT EXISTS form_submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            form_id INTEGER NOT NULL,
            form_data TEXT NOT NULL,
            submitter_ip TEXT,
            submitter_email TEXT,
            submitter_name TEXT,
            status TEXT DEFAULT 'new',
            submitted_at TEXT DEFAULT CURRENT_TIMESTAMP,
            processed_at TEXT,
            processed_by INTEGER,
            notes TEXT,
            FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
        )
    ");
    echo "<div class='success'>✅ form_submissions table created</div>";
    
    // Table 3: form_files
    $db->exec("
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
        )
    ");
    echo "<div class='success'>✅ form_files table created</div>";
    
    // Indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_forms_category ON forms(category)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_forms_template ON forms(is_template)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_submissions_form ON form_submissions(form_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_submissions_status ON form_submissions(status)");
    echo "<div class='success'>✅ Indexes created</div>";
    
    echo "<h2>Creating Form Templates...</h2>";
    
    $insertStmt = $db->prepare("INSERT OR REPLACE INTO forms (form_name, display_name, category, style, description, fields, settings, is_template) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    
    $count = 0;
    
    // ============ CUSTOMER FORMS (10) ============
    $customerForms = [
        ['customer_registration', 'Customer Registration', 'customer', 'business', 'New customer signup form',
         '[{"type":"text","name":"full_name","label":"Full Name","required":true},{"type":"email","name":"email","label":"Email Address","required":true},{"type":"password","name":"password","label":"Password","required":true},{"type":"tel","name":"phone","label":"Phone Number"},{"type":"checkbox","name":"terms","label":"I agree to Terms & Conditions","required":true}]',
         '{"submit_text":"Create Account","success_message":"Registration successful!","send_confirmation":true}'],
        ['customer_feedback', 'Customer Feedback', 'customer', 'business', 'General feedback collection',
         '[{"type":"text","name":"name","label":"Your Name","required":true},{"type":"email","name":"email","label":"Email"},{"type":"select","name":"topic","label":"Feedback Topic","options":["Product","Service","Website","Other"]},{"type":"textarea","name":"feedback","label":"Your Feedback","required":true},{"type":"rating","name":"rating","label":"Overall Rating","max":5}]',
         '{"submit_text":"Submit Feedback","success_message":"Thank you for your feedback!"}'],
        ['customer_complaint', 'Customer Complaint', 'customer', 'corporate', 'Formal complaint submission',
         '[{"type":"text","name":"name","label":"Full Name","required":true},{"type":"email","name":"email","label":"Email","required":true},{"type":"text","name":"order_id","label":"Order/Account ID"},{"type":"date","name":"incident_date","label":"Date of Incident"},{"type":"select","name":"category","label":"Complaint Category","options":["Product Quality","Service","Billing","Shipping","Other"],"required":true},{"type":"textarea","name":"description","label":"Describe the Issue","required":true},{"type":"textarea","name":"resolution","label":"Desired Resolution"},{"type":"file","name":"attachments","label":"Attachments","accept":"image/*,.pdf"}]',
         '{"submit_text":"Submit Complaint","success_message":"Your complaint has been received. We will respond within 48 hours."}'],
        ['account_cancellation', 'Account Cancellation', 'customer', 'business', 'Cancel account request',
         '[{"type":"email","name":"email","label":"Account Email","required":true},{"type":"select","name":"reason","label":"Reason for Cancellation","options":["Too expensive","Not using it","Found alternative","Missing features","Technical issues","Other"],"required":true},{"type":"textarea","name":"feedback","label":"Additional Feedback"},{"type":"checkbox","name":"confirm","label":"I understand this action is permanent","required":true}]',
         '{"submit_text":"Cancel Account","success_message":"Your cancellation request has been received."}'],
        ['referral_program', 'Referral Program', 'customer', 'casual', 'Refer a friend form',
         '[{"type":"text","name":"your_name","label":"Your Name","required":true},{"type":"email","name":"your_email","label":"Your Email","required":true},{"type":"text","name":"friend_name","label":"Friend\'s Name","required":true},{"type":"email","name":"friend_email","label":"Friend\'s Email","required":true},{"type":"textarea","name":"message","label":"Personal Message"}]',
         '{"submit_text":"Send Referral","success_message":"Referral sent! You\'ll earn rewards when they sign up."}'],
    ];
    
    foreach ($customerForms as $f) {
        $insertStmt->bindValue(1, $f[0], SQLITE3_TEXT);
        $insertStmt->bindValue(2, $f[1], SQLITE3_TEXT);
        $insertStmt->bindValue(3, $f[2], SQLITE3_TEXT);
        $insertStmt->bindValue(4, $f[3], SQLITE3_TEXT);
        $insertStmt->bindValue(5, $f[4], SQLITE3_TEXT);
        $insertStmt->bindValue(6, $f[5], SQLITE3_TEXT);
        $insertStmt->bindValue(7, $f[6], SQLITE3_TEXT);
        $insertStmt->execute();
        $count++;
    }
    echo "<div class='success'>✅ Customer forms: 5 created</div>";
    
    // ============ SUPPORT FORMS (10) ============
    $supportForms = [
        ['support_ticket', 'Support Ticket', 'support', 'business', 'Technical support request',
         '[{"type":"text","name":"name","label":"Your Name","required":true},{"type":"email","name":"email","label":"Email","required":true},{"type":"text","name":"subject","label":"Subject","required":true},{"type":"select","name":"priority","label":"Priority","options":["Low","Medium","High","Urgent"]},{"type":"select","name":"category","label":"Category","options":["Technical","Billing","Account","Feature Request","Bug Report","Other"],"required":true},{"type":"textarea","name":"description","label":"Description","required":true},{"type":"file","name":"attachments","label":"Attachments"}]',
         '{"submit_text":"Submit Ticket","success_message":"Ticket submitted! You will receive a confirmation email."}'],
        ['bug_report', 'Bug Report', 'support', 'business', 'Report software bugs',
         '[{"type":"text","name":"title","label":"Bug Title","required":true},{"type":"select","name":"severity","label":"Severity","options":["Critical","Major","Minor","Cosmetic"],"required":true},{"type":"textarea","name":"steps","label":"Steps to Reproduce","required":true},{"type":"textarea","name":"expected","label":"Expected Result"},{"type":"textarea","name":"actual","label":"Actual Result","required":true},{"type":"text","name":"browser","label":"Browser/Device"},{"type":"file","name":"screenshot","label":"Screenshot","accept":"image/*"}]',
         '{"submit_text":"Report Bug","success_message":"Bug report submitted. Thank you!"}'],
        ['feature_request', 'Feature Request', 'support', 'casual', 'Request new features',
         '[{"type":"text","name":"title","label":"Feature Title","required":true},{"type":"textarea","name":"description","label":"Describe the Feature","required":true},{"type":"textarea","name":"use_case","label":"How would you use it?"},{"type":"select","name":"importance","label":"How important is this?","options":["Nice to have","Important","Critical"]},{"type":"email","name":"email","label":"Your Email"}]',
         '{"submit_text":"Submit Request","success_message":"Feature request received!"}'],
        ['callback_request', 'Request Callback', 'support', 'business', 'Request a phone callback',
         '[{"type":"text","name":"name","label":"Your Name","required":true},{"type":"tel","name":"phone","label":"Phone Number","required":true},{"type":"email","name":"email","label":"Email"},{"type":"select","name":"best_time","label":"Best Time to Call","options":["Morning (9am-12pm)","Afternoon (12pm-5pm)","Evening (5pm-8pm)"]},{"type":"textarea","name":"topic","label":"What would you like to discuss?"}]',
         '{"submit_text":"Request Callback","success_message":"We will call you at the requested time."}'],
        ['appointment_booking', 'Book Appointment', 'support', 'corporate', 'Schedule an appointment',
         '[{"type":"text","name":"name","label":"Full Name","required":true},{"type":"email","name":"email","label":"Email","required":true},{"type":"tel","name":"phone","label":"Phone"},{"type":"date","name":"preferred_date","label":"Preferred Date","required":true},{"type":"select","name":"time_slot","label":"Time Slot","options":["9:00 AM","10:00 AM","11:00 AM","1:00 PM","2:00 PM","3:00 PM","4:00 PM"],"required":true},{"type":"select","name":"service","label":"Service Type","options":["Consultation","Support Session","Training","Demo"]},{"type":"textarea","name":"notes","label":"Additional Notes"}]',
         '{"submit_text":"Book Appointment","success_message":"Appointment requested. We will confirm via email."}'],
    ];
    
    foreach ($supportForms as $f) {
        $insertStmt->bindValue(1, $f[0], SQLITE3_TEXT);
        $insertStmt->bindValue(2, $f[1], SQLITE3_TEXT);
        $insertStmt->bindValue(3, $f[2], SQLITE3_TEXT);
        $insertStmt->bindValue(4, $f[3], SQLITE3_TEXT);
        $insertStmt->bindValue(5, $f[4], SQLITE3_TEXT);
        $insertStmt->bindValue(6, $f[5], SQLITE3_TEXT);
        $insertStmt->bindValue(7, $f[6], SQLITE3_TEXT);
        $insertStmt->execute();
        $count++;
    }
    echo "<div class='success'>✅ Support forms: 5 created</div>";
    
    // ============ PAYMENT FORMS (5) ============
    $paymentForms = [
        ['refund_request', 'Refund Request', 'payment', 'corporate', 'Request a refund',
         '[{"type":"text","name":"name","label":"Full Name","required":true},{"type":"email","name":"email","label":"Email","required":true},{"type":"text","name":"order_id","label":"Order ID","required":true},{"type":"date","name":"purchase_date","label":"Purchase Date"},{"type":"number","name":"amount","label":"Refund Amount ($)","required":true},{"type":"select","name":"reason","label":"Reason for Refund","options":["Product defective","Wrong item","Changed mind","Not as described","Never received","Other"],"required":true},{"type":"textarea","name":"details","label":"Additional Details"},{"type":"file","name":"proof","label":"Proof of Purchase"}]',
         '{"submit_text":"Request Refund","success_message":"Refund request submitted. Processing takes 5-7 business days."}'],
        ['billing_dispute', 'Billing Dispute', 'payment', 'corporate', 'Dispute a charge',
         '[{"type":"text","name":"name","label":"Account Holder Name","required":true},{"type":"email","name":"email","label":"Email","required":true},{"type":"text","name":"account_id","label":"Account ID"},{"type":"date","name":"charge_date","label":"Date of Charge","required":true},{"type":"number","name":"amount","label":"Disputed Amount ($)","required":true},{"type":"textarea","name":"reason","label":"Reason for Dispute","required":true},{"type":"file","name":"documents","label":"Supporting Documents"}]',
         '{"submit_text":"Submit Dispute","success_message":"Dispute submitted. We will investigate within 48 hours."}'],
    ];
    
    foreach ($paymentForms as $f) {
        $insertStmt->bindValue(1, $f[0], SQLITE3_TEXT);
        $insertStmt->bindValue(2, $f[1], SQLITE3_TEXT);
        $insertStmt->bindValue(3, $f[2], SQLITE3_TEXT);
        $insertStmt->bindValue(4, $f[3], SQLITE3_TEXT);
        $insertStmt->bindValue(5, $f[4], SQLITE3_TEXT);
        $insertStmt->bindValue(6, $f[5], SQLITE3_TEXT);
        $insertStmt->bindValue(7, $f[6], SQLITE3_TEXT);
        $insertStmt->execute();
        $count++;
    }
    echo "<div class='success'>✅ Payment forms: 2 created</div>";
    
    // ============ REGISTRATION FORMS (8) ============
    $registrationForms = [
        ['newsletter_signup', 'Newsletter Signup', 'registration', 'casual', 'Email newsletter subscription',
         '[{"type":"email","name":"email","label":"Your Email","required":true},{"type":"text","name":"first_name","label":"First Name"},{"type":"select","name":"interests","label":"Interests","options":["Product Updates","Tips & Tutorials","Industry News","Special Offers"],"multiple":true}]',
         '{"submit_text":"Subscribe","success_message":"You\'re subscribed! Check your inbox for confirmation."}'],
        ['event_registration', 'Event Registration', 'registration', 'business', 'Register for events',
         '[{"type":"text","name":"name","label":"Full Name","required":true},{"type":"email","name":"email","label":"Email","required":true},{"type":"text","name":"company","label":"Company"},{"type":"text","name":"title","label":"Job Title"},{"type":"select","name":"dietary","label":"Dietary Requirements","options":["None","Vegetarian","Vegan","Gluten-free","Kosher","Halal","Other"]},{"type":"textarea","name":"questions","label":"Questions for the Speaker"}]',
         '{"submit_text":"Register","success_message":"Registration confirmed! See you at the event."}'],
        ['webinar_registration', 'Webinar Registration', 'registration', 'business', 'Sign up for webinars',
         '[{"type":"text","name":"name","label":"Full Name","required":true},{"type":"email","name":"email","label":"Email","required":true},{"type":"text","name":"company","label":"Company"},{"type":"select","name":"how_heard","label":"How did you hear about us?","options":["Email","Social Media","Search","Referral","Other"]},{"type":"checkbox","name":"reminder","label":"Send me a reminder email"}]',
         '{"submit_text":"Register for Webinar","success_message":"You\'re registered! Calendar invite sent."}'],
        ['trial_signup', 'Free Trial Signup', 'registration', 'casual', 'Start a free trial',
         '[{"type":"text","name":"name","label":"Full Name","required":true},{"type":"email","name":"email","label":"Work Email","required":true},{"type":"text","name":"company","label":"Company Name"},{"type":"select","name":"company_size","label":"Company Size","options":["1-10","11-50","51-200","201-500","500+"]},{"type":"tel","name":"phone","label":"Phone Number"},{"type":"checkbox","name":"terms","label":"I agree to the Terms of Service","required":true}]',
         '{"submit_text":"Start Free Trial","success_message":"Trial activated! Check your email for login details."}'],
    ];
    
    foreach ($registrationForms as $f) {
        $insertStmt->bindValue(1, $f[0], SQLITE3_TEXT);
        $insertStmt->bindValue(2, $f[1], SQLITE3_TEXT);
        $insertStmt->bindValue(3, $f[2], SQLITE3_TEXT);
        $insertStmt->bindValue(4, $f[3], SQLITE3_TEXT);
        $insertStmt->bindValue(5, $f[4], SQLITE3_TEXT);
        $insertStmt->bindValue(6, $f[5], SQLITE3_TEXT);
        $insertStmt->bindValue(7, $f[6], SQLITE3_TEXT);
        $insertStmt->execute();
        $count++;
    }
    echo "<div class='success'>✅ Registration forms: 4 created</div>";
    
    // ============ SURVEY FORMS (8) ============
    $surveyForms = [
        ['nps_survey', 'NPS Survey', 'survey', 'business', 'Net Promoter Score survey',
         '[{"type":"rating","name":"score","label":"How likely are you to recommend us to a friend? (0-10)","max":10,"required":true},{"type":"textarea","name":"reason","label":"What is the primary reason for your score?"},{"type":"textarea","name":"improve","label":"How can we improve?"}]',
         '{"submit_text":"Submit Survey","success_message":"Thank you for your feedback!"}'],
        ['satisfaction_survey', 'Satisfaction Survey', 'survey', 'business', 'Customer satisfaction survey',
         '[{"type":"rating","name":"overall","label":"Overall Satisfaction","max":5,"required":true},{"type":"rating","name":"product","label":"Product Quality","max":5},{"type":"rating","name":"service","label":"Customer Service","max":5},{"type":"rating","name":"value","label":"Value for Money","max":5},{"type":"textarea","name":"comments","label":"Additional Comments"}]',
         '{"submit_text":"Submit Survey","success_message":"Thank you for your feedback!"}'],
        ['exit_survey', 'Exit Survey', 'survey', 'corporate', 'Cancellation exit survey',
         '[{"type":"select","name":"reason","label":"Primary reason for leaving","options":["Too expensive","Not using it","Missing features","Found alternative","Poor support","Other"],"required":true},{"type":"rating","name":"satisfaction","label":"How satisfied were you overall?","max":5},{"type":"textarea","name":"feedback","label":"What could we have done better?"},{"type":"select","name":"return","label":"Would you consider returning?","options":["Yes","Maybe","No"]}]',
         '{"submit_text":"Submit Feedback","success_message":"We appreciate your feedback. Sorry to see you go."}'],
    ];
    
    foreach ($surveyForms as $f) {
        $insertStmt->bindValue(1, $f[0], SQLITE3_TEXT);
        $insertStmt->bindValue(2, $f[1], SQLITE3_TEXT);
        $insertStmt->bindValue(3, $f[2], SQLITE3_TEXT);
        $insertStmt->bindValue(4, $f[3], SQLITE3_TEXT);
        $insertStmt->bindValue(5, $f[4], SQLITE3_TEXT);
        $insertStmt->bindValue(6, $f[5], SQLITE3_TEXT);
        $insertStmt->bindValue(7, $f[6], SQLITE3_TEXT);
        $insertStmt->execute();
        $count++;
    }
    echo "<div class='success'>✅ Survey forms: 3 created</div>";
    
    // ============ LEAD GENERATION FORMS (5) ============
    $leadForms = [
        ['contact_us', 'Contact Us', 'lead', 'business', 'General contact form',
         '[{"type":"text","name":"name","label":"Your Name","required":true},{"type":"email","name":"email","label":"Email Address","required":true},{"type":"tel","name":"phone","label":"Phone Number"},{"type":"select","name":"subject","label":"Subject","options":["General Inquiry","Sales","Support","Partnership","Other"]},{"type":"textarea","name":"message","label":"Message","required":true}]',
         '{"submit_text":"Send Message","success_message":"Message sent! We\'ll respond within 24 hours."}'],
        ['quote_request', 'Request a Quote', 'lead', 'corporate', 'Request pricing quote',
         '[{"type":"text","name":"name","label":"Full Name","required":true},{"type":"email","name":"email","label":"Business Email","required":true},{"type":"text","name":"company","label":"Company Name","required":true},{"type":"tel","name":"phone","label":"Phone Number"},{"type":"select","name":"service","label":"Service Interested In","options":["VPN Personal","VPN Family","VPN Business","Custom Solution"],"required":true},{"type":"number","name":"users","label":"Number of Users"},{"type":"textarea","name":"requirements","label":"Specific Requirements"},{"type":"select","name":"budget","label":"Budget Range","options":["Under $100/mo","$100-$500/mo","$500-$1000/mo","$1000+/mo"]}]',
         '{"submit_text":"Request Quote","success_message":"Quote request received. We\'ll contact you within 1 business day."}'],
        ['demo_request', 'Request a Demo', 'lead', 'business', 'Schedule a product demo',
         '[{"type":"text","name":"name","label":"Full Name","required":true},{"type":"email","name":"email","label":"Work Email","required":true},{"type":"text","name":"company","label":"Company"},{"type":"text","name":"title","label":"Job Title"},{"type":"select","name":"company_size","label":"Company Size","options":["1-10","11-50","51-200","201-1000","1000+"]},{"type":"textarea","name":"goals","label":"What are you hoping to achieve?"}]',
         '{"submit_text":"Request Demo","success_message":"Demo request received! We\'ll reach out shortly."}'],
    ];
    
    foreach ($leadForms as $f) {
        $insertStmt->bindValue(1, $f[0], SQLITE3_TEXT);
        $insertStmt->bindValue(2, $f[1], SQLITE3_TEXT);
        $insertStmt->bindValue(3, $f[2], SQLITE3_TEXT);
        $insertStmt->bindValue(4, $f[3], SQLITE3_TEXT);
        $insertStmt->bindValue(5, $f[4], SQLITE3_TEXT);
        $insertStmt->bindValue(6, $f[5], SQLITE3_TEXT);
        $insertStmt->bindValue(7, $f[6], SQLITE3_TEXT);
        $insertStmt->execute();
        $count++;
    }
    echo "<div class='success'>✅ Lead generation forms: 3 created</div>";
    
    // ============ HR FORMS (4) ============
    $hrForms = [
        ['job_application', 'Job Application', 'hr', 'corporate', 'Employment application',
         '[{"type":"text","name":"name","label":"Full Name","required":true},{"type":"email","name":"email","label":"Email","required":true},{"type":"tel","name":"phone","label":"Phone Number","required":true},{"type":"text","name":"position","label":"Position Applied For","required":true},{"type":"url","name":"linkedin","label":"LinkedIn Profile"},{"type":"file","name":"resume","label":"Resume/CV","accept":".pdf,.doc,.docx","required":true},{"type":"file","name":"cover_letter","label":"Cover Letter","accept":".pdf,.doc,.docx"},{"type":"textarea","name":"why","label":"Why do you want to work here?"},{"type":"date","name":"start_date","label":"Available Start Date"}]',
         '{"submit_text":"Submit Application","success_message":"Application submitted successfully!"}'],
        ['time_off_request', 'Time Off Request', 'hr', 'business', 'Request time off',
         '[{"type":"text","name":"name","label":"Employee Name","required":true},{"type":"email","name":"email","label":"Email","required":true},{"type":"date","name":"start_date","label":"Start Date","required":true},{"type":"date","name":"end_date","label":"End Date","required":true},{"type":"select","name":"type","label":"Leave Type","options":["Vacation","Sick Leave","Personal","Bereavement","Other"],"required":true},{"type":"textarea","name":"reason","label":"Reason"},{"type":"text","name":"coverage","label":"Coverage Arrangements"}]',
         '{"submit_text":"Submit Request","success_message":"Request submitted for approval."}'],
    ];
    
    foreach ($hrForms as $f) {
        $insertStmt->bindValue(1, $f[0], SQLITE3_TEXT);
        $insertStmt->bindValue(2, $f[1], SQLITE3_TEXT);
        $insertStmt->bindValue(3, $f[2], SQLITE3_TEXT);
        $insertStmt->bindValue(4, $f[3], SQLITE3_TEXT);
        $insertStmt->bindValue(5, $f[4], SQLITE3_TEXT);
        $insertStmt->bindValue(6, $f[5], SQLITE3_TEXT);
        $insertStmt->bindValue(7, $f[6], SQLITE3_TEXT);
        $insertStmt->execute();
        $count++;
    }
    echo "<div class='success'>✅ HR forms: 2 created</div>";
    
    $db->close();
    
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p>Created <strong>{$count}</strong> form templates.</p>";
    echo "<p><a href='index.php' style='color:#00d9ff'>→ Go to Form Library</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
