-- Additional Form Templates (Part 4 - Final)
-- Miscellaneous forms to complete 50+ templates

-- MISCELLANEOUS FORMS (12)
INSERT OR IGNORE INTO form_templates (template_name, category, description, fields, settings) VALUES

('Change Request Form', 'misc', 'Request changes to services',
'[{"type":"text","label":"Your Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"text","label":"Account/Order Number","name":"account","required":true},{"type":"select","label":"Change Type","name":"change_type","required":true,"options":["Address Change","Plan Change","Billing Change","Cancellation","Other"]},{"type":"textarea","label":"Describe Requested Changes","name":"description","required":true},{"type":"date","label":"Effective Date","name":"effective_date"}]',
'{"notifications":{"enabled":true}}'),

('Refund Request', 'misc', 'Request a refund',
'[{"type":"text","label":"Full Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"text","label":"Order/Invoice Number","name":"order_number","required":true},{"type":"date","label":"Purchase Date","name":"purchase_date","required":true},{"type":"number","label":"Refund Amount","name":"amount","required":true},{"type":"select","label":"Reason for Refund","name":"reason","required":true,"options":["Product Defective","Wrong Item Received","Changed Mind","Not as Described","Service Issue","Other"]},{"type":"textarea","label":"Additional Details","name":"details","required":true}]',
'{"notifications":{"enabled":true,"priority":"high"}}'),

('Complaint Form', 'misc', 'File a complaint',
'[{"type":"text","label":"Your Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"tel","label":"Phone","name":"phone"},{"type":"select","label":"Complaint Type","name":"type","required":true,"options":["Product Quality","Service Quality","Staff Behavior","Billing Issue","Delivery Issue","Other"]},{"type":"date","label":"Date of Incident","name":"incident_date","required":true},{"type":"textarea","label":"Describe the Issue","name":"description","required":true,"rows":6},{"type":"select","label":"Desired Resolution","name":"resolution","required":true,"options":["Refund","Replacement","Apology","Investigation","Other"]},{"type":"checkbox","label":"I would like to be contacted","name":"contact","value":"yes"}]',
'{"notifications":{"enabled":true,"priority":"high"}}'),

('Testimonial Submission', 'misc', 'Share your success story',
'[{"type":"text","label":"Your Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"text","label":"Company/Title","name":"company"},{"type":"textarea","label":"Your Testimonial","name":"testimonial","required":true,"maxlength":500},{"type":"radio","label":"May we publish this?","name":"publish","required":true,"options":["Yes, with my name","Yes, anonymously","No, internal use only"]},{"type":"file","label":"Your Photo (Optional)","name":"photo","accept":"image/*"},{"type":"url","label":"Website/LinkedIn","name":"url"}]',
'{"notifications":{"enabled":true}}'),

('Bug Report', 'misc', 'Report a software bug',
'[{"type":"text","label":"Your Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"select","label":"Bug Severity","name":"severity","required":true,"options":["Critical - System Down","High - Major Feature Broken","Medium - Functionality Issue","Low - Minor Annoyance"]},{"type":"text","label":"Page/Feature Affected","name":"location","required":true},{"type":"textarea","label":"Steps to Reproduce","name":"steps","required":true},{"type":"textarea","label":"Expected vs Actual Behavior","name":"behavior","required":true},{"type":"text","label":"Browser/Device","name":"device"},{"type":"file","label":"Screenshot","name":"screenshot","accept":"image/*"}]',
'{"notifications":{"enabled":true,"priority":"high"}}'),

('Feature Request', 'misc', 'Suggest new features',
'[{"type":"text","label":"Your Name","name":"name"},{"type":"email","label":"Email","name":"email"},{"type":"text","label":"Feature Title","name":"title","required":true},{"type":"textarea","label":"Feature Description","name":"description","required":true},{"type":"textarea","label":"How would this help you?","name":"use_case","required":true},{"type":"select","label":"Priority Level","name":"priority","options":["Critical Need","High Priority","Nice to Have","Low Priority"]},{"type":"checkbox","label":"I would pay for this feature","name":"willing_to_pay","value":"yes"}]',
'{"notifications":{"enabled":true}}'),

('Access Request', 'misc', 'Request system access',
'[{"type":"text","label":"Full Name","name":"name","required":true},{"type":"email","label":"Work Email","name":"email","required":true},{"type":"text","label":"Department","name":"department","required":true},{"type":"text","label":"Manager Name","name":"manager","required":true},{"type":"email","label":"Manager Email","name":"manager_email","required":true},{"type":"checkbox","label":"Systems Requested","name":"systems","required":true,"options":["CRM","Financial System","HR Portal","Project Management","Database Access","Admin Panel"]},{"type":"select","label":"Access Level","name":"level","required":true,"options":["View Only","Standard User","Power User","Administrator"]},{"type":"textarea","label":"Business Justification","name":"justification","required":true}]',
'{"notifications":{"enabled":true,"admin_approval":true}}'),

('PTO Request', 'misc', 'Request time off (HR)',
'[{"type":"text","label":"Employee Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"text","label":"Department","name":"department","required":true},{"type":"select","label":"Leave Type","name":"leave_type","required":true,"options":["Vacation","Sick Leave","Personal Day","Bereavement","Jury Duty","Other"]},{"type":"date","label":"Start Date","name":"start_date","required":true},{"type":"date","label":"End Date","name":"end_date","required":true},{"type":"number","label":"Total Days","name":"days","required":true,"min":0.5,"step":0.5},{"type":"textarea","label":"Notes/Reason","name":"notes"}]',
'{"notifications":{"enabled":true,"manager_approval":true}}'),

('Expense Report', 'misc', 'Submit expense reimbursement',
'[{"type":"text","label":"Employee Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"text","label":"Department","name":"department","required":true},{"type":"date","label":"Expense Date","name":"expense_date","required":true},{"type":"select","label":"Expense Category","name":"category","required":true,"options":["Travel","Meals","Supplies","Equipment","Training","Client Entertainment","Other"]},{"type":"number","label":"Amount","name":"amount","required":true,"min":0,"step":0.01},{"type":"textarea","label":"Business Purpose","name":"purpose","required":true},{"type":"file","label":"Receipt","name":"receipt","required":true,"accept":"image/*,.pdf"}]',
'{"notifications":{"enabled":true,"finance_approval":true}}'),

('RMA Request', 'misc', 'Return merchandise authorization',
'[{"type":"text","label":"Customer Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"text","label":"Order Number","name":"order_number","required":true},{"type":"text","label":"Product Name/SKU","name":"product","required":true},{"type":"select","label":"Return Reason","name":"reason","required":true,"options":["Defective","Wrong Item","Not as Described","Changed Mind","Damaged in Shipping","Other"]},{"type":"textarea","label":"Describe the Issue","name":"description","required":true},{"type":"select","label":"Preferred Resolution","name":"resolution","required":true,"options":["Refund","Exchange","Store Credit"]},{"type":"checkbox","label":"Item is unused and in original packaging","name":"condition","value":"yes"}]',
'{"notifications":{"enabled":true}}'),

('Interview Scheduling', 'misc', 'Schedule a job interview',
'[{"type":"text","label":"Candidate Name","name":"name","required":true},{"type":"email","label":"Email","name":"email","required":true},{"type":"tel","label":"Phone","name":"phone","required":true},{"type":"text","label":"Position Applied For","name":"position","required":true},{"type":"select","label":"Interview Type","name":"interview_type","required":true,"options":["Phone Screen","Video Interview","In-Person Interview","Panel Interview"]},{"type":"date","label":"Preferred Date 1","name":"date1","required":true},{"type":"select","label":"Time Slot 1","name":"time1","required":true,"options":["9:00 AM","10:00 AM","11:00 AM","1:00 PM","2:00 PM","3:00 PM","4:00 PM"]},{"type":"date","label":"Preferred Date 2","name":"date2"},{"type":"select","label":"Time Slot 2","name":"time2","options":["9:00 AM","10:00 AM","11:00 AM","1:00 PM","2:00 PM","3:00 PM","4:00 PM"]},{"type":"textarea","label":"Special Accommodations Needed","name":"accommodations"}]',
'{"notifications":{"enabled":true}}'),

('Data Deletion Request', 'misc', 'GDPR/Privacy data deletion',
'[{"type":"text","label":"Full Name","name":"name","required":true},{"type":"email","label":"Email Address","name":"email","required":true},{"type":"text","label":"Account ID (if known)","name":"account_id"},{"type":"checkbox","label":"Data to Delete","name":"data_types","required":true,"options":["Personal Information","Purchase History","Communication History","Account Data","All Data"]},{"type":"textarea","label":"Reason for Deletion","name":"reason"},{"type":"checkbox","label":"I understand this action is permanent","name":"confirmation","required":true,"value":"yes"},{"type":"checkbox","label":"I have read the data deletion policy","name":"policy","required":true,"value":"yes"}]',
'{"notifications":{"enabled":true,"priority":"high","legal_review":true}}');

-- Total templates: 58 forms
-- Categories: contact(8), registration(10), survey(12), order(8), application(8), misc(12)
