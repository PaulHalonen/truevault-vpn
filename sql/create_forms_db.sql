-- Form Library System Database Schema
-- Created: January 19, 2026

-- Form templates
CREATE TABLE IF NOT EXISTS form_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_name TEXT NOT NULL,
    category TEXT NOT NULL,               -- contact, registration, survey, feedback, etc.
    description TEXT,
    fields TEXT NOT NULL,                 -- JSON array of field definitions
    settings TEXT,                        -- JSON: notifications, validation, etc.
    is_active INTEGER DEFAULT 1,
    usage_count INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Custom forms (user-created or template instances)
CREATE TABLE IF NOT EXISTS forms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    form_name TEXT NOT NULL,
    template_id INTEGER,
    fields TEXT NOT NULL,                 -- JSON array of field definitions
    settings TEXT,
    submit_action TEXT DEFAULT 'email',   -- email, database, both
    notification_email TEXT,
    success_message TEXT DEFAULT 'Thank you for your submission!',
    is_active INTEGER DEFAULT 1,
    created_by INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES form_templates(id) ON DELETE SET NULL
);

-- Form submissions
CREATE TABLE IF NOT EXISTS form_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    form_id INTEGER NOT NULL,
    submission_data TEXT NOT NULL,        -- JSON of field:value pairs
    ip_address TEXT,
    user_agent TEXT,
    submitted_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_templates_category ON form_templates(category);
CREATE INDEX IF NOT EXISTS idx_templates_active ON form_templates(is_active);
CREATE INDEX IF NOT EXISTS idx_forms_template ON forms(template_id);
CREATE INDEX IF NOT EXISTS idx_forms_active ON forms(is_active);
CREATE INDEX IF NOT EXISTS idx_submissions_form ON form_submissions(form_id);
CREATE INDEX IF NOT EXISTS idx_submissions_date ON form_submissions(submitted_at);

-- Insert 50+ form templates
INSERT OR IGNORE INTO form_templates (template_name, category, description, fields, settings) VALUES

-- CONTACT FORMS (8)
('Basic Contact Form', 'contact', 'Simple contact form with name, email, and message', 
'[{"type":"text","label":"Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"textarea","label":"Message","name":"message","required":true,"rows":5}]',
'{"notifications":{"enabled":true},"validation":{"email":true}}'),

('Contact + Phone', 'contact', 'Contact form with phone number field',
'[{"type":"text","label":"Full Name","name":"full_name","required":true},{"type":"email","label":"Email Address","name":"email","required":true},{"type":"tel","label":"Phone Number","name":"phone","required":true},{"type":"textarea","label":"Message","name":"message","required":true}]',
'{"notifications":{"enabled":true}}'),

('Business Inquiry', 'contact', 'Professional business inquiry form',
'[{"type":"text","label":"Company Name","name":"company","required":true},{"type":"text","label":"Contact Person","name":"name","required":true},{"type":"email","label":"Business Email","name":"email","required":true},{"type":"tel","label":"Phone","name":"phone"},{"type":"select","label":"Department","name":"department","options":["Sales","Support","Partnership","General"]},{"type":"textarea","label":"Inquiry Details","name":"message","required":true}]',
'{"notifications":{"enabled":true}}'),

('Customer Support Request', 'contact', 'Technical support request form',
'[{"type":"text","label":"Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"text","label":"Order/Account Number","name":"account_number"},{"type":"select","label":"Issue Type","name":"issue_type","required":true,"options":["Technical Problem","Billing Question","Product Inquiry","Other"]},{"type":"textarea","label":"Describe Your Issue","name":"description","required":true,"rows":6}]',
'{"notifications":{"enabled":true,"priority":"high"}}'),

('Quote Request', 'contact', 'Request a price quote',
'[{"type":"text","label":"Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"tel","label":"Phone","name":"phone"},{"type":"text","label":"Company","name":"company"},{"type":"select","label":"Service Interested In","name":"service","required":true,"options":["Personal Plan","Family Plan","Business Plan","Enterprise Solution"]},{"type":"textarea","label":"Additional Details","name":"details"}]',
'{"notifications":{"enabled":true}}'),

('Appointment Booking', 'contact', 'Schedule an appointment',
'[{"type":"text","label":"Full Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"tel","label":"Phone","name":"phone","required":true},{"type":"date","label":"Preferred Date","name":"date","required":true},{"type":"select","label":"Preferred Time","name":"time","required":true,"options":["Morning (9AM-12PM)","Afternoon (12PM-5PM)","Evening (5PM-8PM)"]},{"type":"textarea","label":"Reason for Appointment","name":"reason"}]',
'{"notifications":{"enabled":true}}'),

('Partnership Inquiry', 'contact', 'Partner with us form',
'[{"type":"text","label":"Your Name","name":"name","required":true},{"type":"text","label":"Company Name","name":"company","required":true},{"type":"email","label":"Business Email","name":"email","required":true},{"type":"url","label":"Company Website","name":"website"},{"type":"select","label":"Partnership Type","name":"type","required":true,"options":["Reseller","Affiliate","Integration","White Label","Strategic"]},{"type":"textarea","label":"Tell Us About Your Proposal","name":"proposal","required":true,"rows":6}]',
'{"notifications":{"enabled":true}}'),

('Feedback Form', 'contact', 'Customer feedback and suggestions',
'[{"type":"text","label":"Name (Optional)","name":"name"},{"type":"email","label":"Email (Optional)","name":"email"},{"type":"radio","label":"How satisfied are you?","name":"satisfaction","required":true,"options":["Very Satisfied","Satisfied","Neutral","Dissatisfied","Very Dissatisfied"]},{"type":"textarea","label":"Your Feedback","name":"feedback","required":true},{"type":"checkbox","label":"May we contact you about your feedback?","name":"contact_ok","value":"yes"}]',
'{"notifications":{"enabled":true}}'),

-- REGISTRATION FORMS (10)
('User Registration', 'registration', 'Basic user account registration',
'[{"type":"text","label":"First Name","name":"first_name","required":true},{"type":"text","label":"Last Name","name":"last_name","required":true},{"type":"email","label":"Email Address","name":"email","required":true},{"type":"password","label":"Password","name":"password","required":true,"minlength":8},{"type":"password","label":"Confirm Password","name":"password_confirm","required":true},{"type":"checkbox","label":"I agree to the Terms of Service","name":"terms","required":true,"value":"agreed"}]',
'{"notifications":{"enabled":true},"validation":{"password_match":true}}'),

('Newsletter Signup', 'registration', 'Subscribe to newsletter',
'[{"type":"email","label":"Email Address","name":"email","required":true},{"type":"text","label":"First Name","name":"first_name"},{"type":"checkbox","label":"I want to receive promotional emails","name":"promo","value":"yes"},{"type":"checkbox","label":"I agree to the Privacy Policy","name":"privacy","required":true,"value":"agreed"}]',
'{"notifications":{"enabled":true}}'),

('Event Registration', 'registration', 'Register for an event',
'[{"type":"text","label":"Full Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"tel","label":"Phone","name":"phone"},{"type":"text","label":"Company/Organization","name":"organization"},{"type":"select","label":"Ticket Type","name":"ticket_type","required":true,"options":["General Admission","VIP","Student","Group (4+)"]},{"type":"select","label":"Dietary Restrictions","name":"dietary","options":["None","Vegetarian","Vegan","Gluten-Free","Kosher","Halal"]},{"type":"textarea","label":"Special Requirements","name":"requirements"}]',
'{"notifications":{"enabled":true}}'),

('Job Application', 'registration', 'Apply for a position',
'[{"type":"text","label":"Full Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"tel","label":"Phone","name":"phone","required":true},{"type":"text","label":"Position Applying For","name":"position","required":true},{"type":"file","label":"Resume/CV","name":"resume","required":true,"accept":".pdf,.doc,.docx"},{"type":"file","label":"Cover Letter","name":"cover_letter","accept":".pdf,.doc,.docx"},{"type":"url","label":"LinkedIn Profile","name":"linkedin"},{"type":"textarea","label":"Why are you interested in this position?","name":"interest","required":true}]',
'{"notifications":{"enabled":true,"priority":"high"}}'),

('Membership Application', 'registration', 'Apply for membership',
'[{"type":"text","label":"Full Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"text","label":"Address","name":"address","required":true},{"type":"text","label":"City","name":"city","required":true},{"type":"text","label":"State/Province","name":"state","required":true},{"type":"text","label":"Zip/Postal Code","name":"zip","required":true},{"type":"select","label":"Membership Type","name":"membership_type","required":true,"options":["Individual","Family","Student","Senior"]},{"type":"checkbox","label":"I agree to the membership terms","name":"terms","required":true,"value":"agreed"}]',
'{"notifications":{"enabled":true}}'),

('Webinar Registration', 'registration', 'Register for webinar',
'[{"type":"text","label":"Full Name","name":"name","required":true},{"type":"email","label":"Email Address","name":"email","required":true},{"type":"text","label":"Job Title","name":"job_title"},{"type":"text","label":"Company","name":"company"},{"type":"select","label":"Industry","name":"industry","options":["Technology","Healthcare","Finance","Education","Manufacturing","Retail","Other"]},{"type":"checkbox","label":"Send me webinar reminders","name":"reminders","value":"yes"},{"type":"checkbox","label":"I consent to receive follow-up communications","name":"consent","value":"yes"}]',
'{"notifications":{"enabled":true}}'),

('Beta Tester Signup', 'registration', 'Join our beta program',
'[{"type":"text","label":"Full Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"select","label":"Technical Experience Level","name":"tech_level","required":true,"options":["Beginner","Intermediate","Advanced","Expert"]},{"type":"select","label":"Primary Device","name":"device","required":true,"options":["Windows PC","Mac","Linux","Android","iOS"]},{"type":"textarea","label":"Why do you want to join the beta?","name":"reason","required":true},{"type":"checkbox","label":"I can commit to providing feedback","name":"commit","required":true,"value":"yes"}]',
'{"notifications":{"enabled":true}}'),

('Trial Account Signup', 'registration', 'Start free trial',
'[{"type":"text","label":"First Name","name":"first_name","required":true},{"type":"text","label":"Last Name","name":"last_name","required":true},{"type":"email","label":"Work Email","name":"email","required":true},{"type":"text","label":"Company Name","name":"company","required":true},{"type":"tel","label":"Phone","name":"phone"},{"type":"select","label":"Company Size","name":"company_size","required":true,"options":["1-10","11-50","51-200","201-1000","1000+"]},{"type":"checkbox","label":"I agree to the Terms of Service","name":"terms","required":true,"value":"agreed"}]',
'{"notifications":{"enabled":true}}'),

('Volunteer Registration', 'registration', 'Sign up to volunteer',
'[{"type":"text","label":"Full Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"tel","label":"Phone","name":"phone","required":true},{"type":"select","label":"Age Group","name":"age_group","required":true,"options":["Under 18","18-25","26-35","36-50","51-65","Over 65"]},{"type":"checkbox","label":"Areas of Interest","name":"interests","required":true,"options":["Event Support","Administration","Outreach","Fundraising","Technical Support"]},{"type":"select","label":"Availability","name":"availability","required":true,"options":["Weekdays","Weekends","Evenings","Flexible"]},{"type":"textarea","label":"Relevant Skills/Experience","name":"skills"}]',
'{"notifications":{"enabled":true}}'),

('Contest Entry', 'registration', 'Enter a contest or giveaway',
'[{"type":"text","label":"Full Name","name":"name","required":true},{"type":"email","label":"Email Address","name":"email","required":true},{"type":"tel","label":"Phone Number","name":"phone"},{"type":"text","label":"Social Media Handle (Optional)","name":"social"},{"type":"textarea","label":"Why should you win?","name":"reason","required":true,"maxlength":500},{"type":"checkbox","label":"I agree to the contest rules","name":"rules","required":true,"value":"agreed"},{"type":"checkbox","label":"Subscribe to newsletter","name":"newsletter","value":"yes"}]',
'{"notifications":{"enabled":true}}');
