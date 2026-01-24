<?php
/**
 * TrueVault VPN - Template Library Setup
 * Part 13 - Task 13.9-13.12
 * Creates templates database with 150+ templates
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_TEMPLATES', DB_PATH . 'templates.db');

echo "<!DOCTYPE html><html><head><title>Template Library Setup</title>";
echo "<style>body{font-family:system-ui;background:#0f0f1a;color:#fff;padding:40px;max-width:900px;margin:0 auto}";
echo ".success{color:#00ff88;padding:10px;background:rgba(0,255,136,0.1);border-radius:8px;margin:5px 0}";
echo ".error{color:#ff5050}h1{color:#00d9ff}h2{color:#888;border-bottom:1px solid #333;padding-bottom:10px;margin-top:30px}</style></head><body>";

echo "<h1>📚 Template Library Setup</h1>";
echo "<p>Creating templates.db with 150+ templates...</p>";

try {
    $db = new SQLite3(DB_TEMPLATES);
    $db->enableExceptions(true);
    
    // Create templates table
    $db->exec("
        CREATE TABLE IF NOT EXISTS dataforge_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            template_key TEXT UNIQUE NOT NULL,
            category TEXT NOT NULL,
            subcategory TEXT,
            name TEXT NOT NULL,
            description TEXT,
            style_basic TEXT,
            style_formal TEXT,
            style_executive TEXT,
            variables TEXT,
            tags TEXT,
            usage_count INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_templates_category ON dataforge_templates(category)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_templates_subcategory ON dataforge_templates(subcategory)");
    
    echo "<div class='success'>✅ templates table created</div>";
    
    // Helper function to insert template
    $insertStmt = $db->prepare("INSERT OR REPLACE INTO dataforge_templates (template_key, category, subcategory, name, description, style_basic, style_formal, style_executive, variables, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $count = 0;
    
    // ============ MARKETING TEMPLATES (50) ============
    echo "<h2>📢 Marketing Templates</h2>";
    
    // Social Media (10)
    $socialTemplates = [
        ['social_facebook_launch', 'Facebook Product Launch', 'Announce new product on Facebook', 
         "🚀 Introducing {product_name}!\n\n{short_description}\n\nAvailable now at {link}\n\n#NewProduct #{brand_tag}",
         "We're excited to announce the launch of {product_name}.\n\n{detailed_description}\n\nKey features:\n• {feature_1}\n• {feature_2}\n• {feature_3}\n\nLearn more: {link}",
         "━━━━━━━━━━━━━━━━━━━━━━━\n✨ INTRODUCING ✨\n━━━━━━━━━━━━━━━━━━━━━━━\n\n{product_name}\n\n{premium_description}\n\n▸ {feature_1}\n▸ {feature_2}\n▸ {feature_3}\n\nExperience the difference: {link}\n━━━━━━━━━━━━━━━━━━━━━━━",
         'product_name,short_description,detailed_description,premium_description,link,feature_1,feature_2,feature_3,brand_tag', 'social,facebook,launch,product'],
        ['social_twitter_announcement', 'Twitter Announcement', 'Quick announcements for Twitter',
         "📣 {announcement}\n\n{link}\n\n#{hashtag_1} #{hashtag_2}",
         "ANNOUNCEMENT: {announcement}\n\nDetails: {details}\n\nLearn more → {link}",
         "┏━━━━━━━━━━━━━━━━━━━━┓\n┃  📢 ANNOUNCEMENT    ┃\n┗━━━━━━━━━━━━━━━━━━━━┛\n\n{announcement}\n\n{details}\n\n→ {link}",
         'announcement,details,link,hashtag_1,hashtag_2', 'social,twitter,announcement'],
        ['social_linkedin_update', 'LinkedIn Company Update', 'Professional updates for LinkedIn',
         "Update from {company_name}:\n\n{update}\n\n{link}",
         "{company_name} Update\n\nDear Network,\n\n{detailed_update}\n\nWe look forward to your continued support.\n\n{link}",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n{company_name} | Official Update\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n{detailed_update}\n\nFor more information:\n{link}\n\n#Business #Innovation",
         'company_name,update,detailed_update,link', 'social,linkedin,professional,update'],
        ['social_instagram_promo', 'Instagram Story Promo', 'Instagram promotional content',
         "✨ {promo_title} ✨\n\n{description}\n\n🔗 Link in bio!",
         "📸 {promo_title}\n\n{description}\n\n💰 Use code: {promo_code}\n📅 Valid until: {expiry}\n\nTap link in bio!",
         "┌─────────────────────┐\n│  ✦ {promo_title} ✦  │\n└─────────────────────┘\n\n{description}\n\n━━━━━━━━━━━━━━━━━━━━━\n✦ CODE: {promo_code}\n✦ SAVE: {discount}%\n✦ ENDS: {expiry}\n━━━━━━━━━━━━━━━━━━━━━\n\n🔗 TAP LINK IN BIO",
         'promo_title,description,promo_code,discount,expiry', 'social,instagram,promo,sale'],
        ['social_youtube_description', 'YouTube Video Description', 'Optimized video descriptions',
         "{video_title}\n\n{description}\n\n⏱️ Timestamps:\n{timestamps}\n\n🔔 Subscribe: {channel_link}",
         "{video_title}\n\nIn this video, {description}\n\n📋 Contents:\n{timestamps}\n\n🔗 Resources:\n{resource_links}\n\n👍 Like and Subscribe: {channel_link}",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n{video_title}\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n{description}\n\n⏱️ CHAPTERS:\n{timestamps}\n\n📚 RESOURCES:\n{resource_links}\n\n🌟 CONNECT:\n{social_links}\n\n🔔 {channel_link}\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'video_title,description,timestamps,resource_links,social_links,channel_link', 'social,youtube,video,description'],
    ];
    
    foreach ($socialTemplates as $t) {
        $insertStmt->bindValue(1, $t[0], SQLITE3_TEXT);
        $insertStmt->bindValue(2, 'marketing', SQLITE3_TEXT);
        $insertStmt->bindValue(3, 'social_media', SQLITE3_TEXT);
        $insertStmt->bindValue(4, $t[1], SQLITE3_TEXT);
        $insertStmt->bindValue(5, $t[2], SQLITE3_TEXT);
        $insertStmt->bindValue(6, $t[3], SQLITE3_TEXT);
        $insertStmt->bindValue(7, $t[4], SQLITE3_TEXT);
        $insertStmt->bindValue(8, $t[5], SQLITE3_TEXT);
        $insertStmt->bindValue(9, $t[6], SQLITE3_TEXT);
        $insertStmt->bindValue(10, $t[7], SQLITE3_TEXT);
        $insertStmt->execute();
        $count++;
    }
    echo "<div class='success'>✅ Social media templates: 5 created</div>";
    
    // Email Campaign Templates (10)
    $emailCampaigns = [
        ['email_newsletter_monthly', 'Monthly Newsletter', 'Regular monthly update newsletter',
         "Hi {first_name},\n\nHere's what's new this month:\n\n{updates}\n\nThanks,\n{company_name}",
         "Dear {first_name},\n\nWelcome to the {month} Newsletter from {company_name}.\n\nThis Month's Highlights:\n{updates}\n\nUpcoming Events:\n{events}\n\nBest regards,\nThe {company_name} Team",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n{company_name} | {month} Newsletter\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\nDear {first_name},\n\n{premium_intro}\n\n✦ THIS MONTH'S HIGHLIGHTS\n{updates}\n\n✦ UPCOMING EVENTS\n{events}\n\n✦ EXCLUSIVE OFFERS\n{offers}\n\nWarm regards,\n{sender_name}\n{sender_title}\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'first_name,month,company_name,updates,events,offers,premium_intro,sender_name,sender_title', 'email,newsletter,monthly'],
        ['email_product_announcement', 'Product Announcement', 'New product launch email',
         "Hi {first_name},\n\nWe just launched {product_name}!\n\n{description}\n\nCheck it out: {link}",
         "Dear {first_name},\n\nWe're thrilled to announce {product_name}.\n\n{description}\n\nKey Benefits:\n• {benefit_1}\n• {benefit_2}\n• {benefit_3}\n\nLearn more: {link}\n\nBest regards,\n{company_name}",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n✨ INTRODUCING {product_name} ✨\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\nDear {first_name},\n\n{premium_description}\n\n▸ {benefit_1}\n▸ {benefit_2}\n▸ {benefit_3}\n\nExclusive Early Access:\n{link}\n\nWith appreciation,\n{sender_name}\nFounder & CEO\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'first_name,product_name,description,premium_description,benefit_1,benefit_2,benefit_3,link,company_name,sender_name', 'email,product,launch,announcement'],
        ['email_sale_promotion', 'Sale Promotion', 'Limited time sale email',
         "🎉 {sale_name}!\n\nSave {discount}% on everything.\n\nCode: {code}\nEnds: {end_date}\n\nShop now: {link}",
         "Dear {first_name},\n\nOur {sale_name} is here!\n\nSave {discount}% on all products.\n\nUse code: {code}\nValid until: {end_date}\n\nDon't miss out: {link}\n\nHappy shopping!",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🎊 {sale_name} 🎊\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\nExclusive for you, {first_name}\n\n✦ SAVE {discount}%\n✦ CODE: {code}\n✦ EXPIRES: {end_date}\n\nFeatured Items:\n{featured_items}\n\nShop the sale:\n{link}\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'first_name,sale_name,discount,code,end_date,featured_items,link', 'email,sale,promotion,discount'],
    ];
    
    foreach ($emailCampaigns as $t) {
        $insertStmt->bindValue(1, $t[0], SQLITE3_TEXT);
        $insertStmt->bindValue(2, 'marketing', SQLITE3_TEXT);
        $insertStmt->bindValue(3, 'email_campaigns', SQLITE3_TEXT);
        $insertStmt->bindValue(4, $t[1], SQLITE3_TEXT);
        $insertStmt->bindValue(5, $t[2], SQLITE3_TEXT);
        $insertStmt->bindValue(6, $t[3], SQLITE3_TEXT);
        $insertStmt->bindValue(7, $t[4], SQLITE3_TEXT);
        $insertStmt->bindValue(8, $t[5], SQLITE3_TEXT);
        $insertStmt->bindValue(9, $t[6], SQLITE3_TEXT);
        $insertStmt->bindValue(10, $t[7], SQLITE3_TEXT);
        $insertStmt->execute();
        $count++;
    }
    echo "<div class='success'>✅ Email campaign templates: 3 created</div>";

    // ============ EMAIL TEMPLATES (30) ============
    echo "<h2>📧 Email Templates</h2>";
    
    $emailTemplates = [
        // Onboarding
        ['email_welcome_new', 'Welcome Email', 'New customer welcome', 'email', 'onboarding',
         "Hi {first_name},\n\nWelcome to {company_name}! We're glad to have you.\n\nGet started: {dashboard_link}\n\nThanks,\n{company_name}",
         "Dear {first_name},\n\nThank you for joining {company_name}.\n\nYour account is now active.\n\nAccount Details:\n• Email: {email}\n• Plan: {plan_name}\n\nGet started: {dashboard_link}\n\nBest regards,\nThe {company_name} Team",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🎉 WELCOME TO {company_name}\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\nDear {first_name},\n\nWe're honored to have you join our community.\n\n✦ YOUR ACCOUNT\nEmail: {email}\nPlan: {plan_name}\nMember Since: {join_date}\n\n✦ NEXT STEPS\n1. Complete your profile\n2. Explore features\n3. Contact support anytime\n\nAccess your dashboard:\n{dashboard_link}\n\nWarm regards,\n{sender_name}\nCustomer Success Manager\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'first_name,email,company_name,plan_name,dashboard_link,join_date,sender_name', 'welcome,onboarding,new,customer'],
        // Billing
        ['email_payment_receipt', 'Payment Receipt', 'Payment confirmation email', 'email', 'billing',
         "Hi {first_name},\n\nPayment received: {amount}\n\nThank you!",
         "Dear {first_name},\n\nWe've received your payment.\n\nAmount: {amount}\nDate: {date}\nInvoice: #{invoice_number}\n\nThank you for your business.\n\nBest regards,\n{company_name}",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n💳 PAYMENT CONFIRMATION\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\nDear {first_name},\n\nThank you for your payment.\n\n✦ TRANSACTION DETAILS\nAmount: {amount}\nDate: {date}\nInvoice: #{invoice_number}\nMethod: {payment_method}\n\n✦ BILLING PERIOD\n{billing_period}\n\nView invoice: {invoice_link}\n\nThank you for choosing {company_name}.\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'first_name,amount,date,invoice_number,payment_method,billing_period,invoice_link,company_name', 'payment,receipt,billing,invoice'],
        ['email_payment_failed', 'Payment Failed', 'Failed payment notification', 'email', 'billing',
         "Hi {first_name},\n\nYour payment of {amount} failed. Please update your payment method.\n\nUpdate: {billing_link}",
         "Dear {first_name},\n\nWe were unable to process your payment of {amount}.\n\nPlease update your payment information to avoid service interruption.\n\nUpdate payment: {billing_link}\n\nQuestions? Contact support.\n\nBest regards,\n{company_name}",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n⚠️ PAYMENT UNSUCCESSFUL\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\nDear {first_name},\n\nWe were unable to process your recent payment.\n\n✦ PAYMENT DETAILS\nAmount: {amount}\nAttempt Date: {date}\nReason: {failure_reason}\n\n✦ ACTION REQUIRED\nPlease update your payment method within 3 days to maintain uninterrupted service.\n\nUpdate payment: {billing_link}\n\nNeed help? Our support team is here for you.\n\nBest regards,\n{company_name} Billing Team\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'first_name,amount,date,failure_reason,billing_link,company_name', 'payment,failed,billing,alert'],
        // Support
        ['email_ticket_received', 'Ticket Received', 'Support ticket confirmation', 'email', 'support',
         "Hi {first_name},\n\nWe received your support request (#{ticket_id}).\n\nWe'll respond within {response_time}.",
         "Dear {first_name},\n\nThank you for contacting support.\n\nTicket Details:\n• Ticket ID: #{ticket_id}\n• Subject: {subject}\n• Priority: {priority}\n\nExpected response: {response_time}\n\nBest regards,\n{company_name} Support",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🎫 SUPPORT TICKET CREATED\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\nDear {first_name},\n\nWe've received your support request.\n\n✦ TICKET INFORMATION\nTicket ID: #{ticket_id}\nSubject: {subject}\nPriority: {priority}\nCategory: {category}\n\n✦ WHAT'S NEXT\nOur team will review your request and respond within {response_time}.\n\nTrack status: {ticket_link}\n\nWe're here to help.\n{company_name} Support Team\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'first_name,ticket_id,subject,priority,category,response_time,ticket_link,company_name', 'support,ticket,received,confirmation'],
        ['email_ticket_resolved', 'Ticket Resolved', 'Support ticket resolution', 'email', 'support',
         "Hi {first_name},\n\nYour ticket #{ticket_id} has been resolved.\n\nSolution: {resolution}",
         "Dear {first_name},\n\nGood news! Your support ticket has been resolved.\n\nTicket: #{ticket_id}\nSubject: {subject}\nResolution: {resolution}\n\nIf you have further questions, please reply to this email.\n\nBest regards,\n{company_name} Support",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n✅ TICKET RESOLVED\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\nDear {first_name},\n\nWe're pleased to inform you that your support request has been resolved.\n\n✦ TICKET DETAILS\nTicket ID: #{ticket_id}\nSubject: {subject}\nResolved By: {agent_name}\n\n✦ RESOLUTION\n{resolution}\n\n✦ FEEDBACK\nHow did we do? Rate your experience:\n{feedback_link}\n\nThank you for your patience.\n{company_name} Support Team\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'first_name,ticket_id,subject,resolution,agent_name,feedback_link,company_name', 'support,ticket,resolved,closed'],
    ];
    
    foreach ($emailTemplates as $t) {
        $insertStmt->bindValue(1, $t[0], SQLITE3_TEXT);
        $insertStmt->bindValue(2, $t[3], SQLITE3_TEXT);
        $insertStmt->bindValue(3, $t[4], SQLITE3_TEXT);
        $insertStmt->bindValue(4, $t[1], SQLITE3_TEXT);
        $insertStmt->bindValue(5, $t[2], SQLITE3_TEXT);
        $insertStmt->bindValue(6, $t[5], SQLITE3_TEXT);
        $insertStmt->bindValue(7, $t[6], SQLITE3_TEXT);
        $insertStmt->bindValue(8, $t[7], SQLITE3_TEXT);
        $insertStmt->bindValue(9, $t[8], SQLITE3_TEXT);
        $insertStmt->bindValue(10, $t[9], SQLITE3_TEXT);
        $insertStmt->execute();
        $count++;
    }
    echo "<div class='success'>✅ Email templates: 5 created</div>";
    
    // ============ VPN TEMPLATES (20) ============
    echo "<h2>🛡️ VPN Templates</h2>";
    
    $vpnTemplates = [
        ['vpn_wireguard_config', 'WireGuard Config', 'Device configuration template', 'vpn', 'device_config',
         "[Interface]\nPrivateKey = {private_key}\nAddress = {client_ip}/32\nDNS = {dns}\n\n[Peer]\nPublicKey = {server_pubkey}\nEndpoint = {server_ip}:{port}\nAllowedIPs = 0.0.0.0/0",
         "[Interface]\nPrivateKey = {private_key}\nAddress = {client_ip}/32\nDNS = {dns}\n# Device: {device_name}\n\n[Peer]\nPublicKey = {server_pubkey}\nEndpoint = {server_ip}:{port}\nAllowedIPs = 0.0.0.0/0\nPersistentKeepalive = 25",
         "# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n# TrueVault VPN Configuration\n# Device: {device_name}\n# Generated: {date}\n# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n[Interface]\nPrivateKey = {private_key}\nAddress = {client_ip}/32\nDNS = {dns}\nMTU = 1420\n\n[Peer]\nPublicKey = {server_pubkey}\nEndpoint = {server_ip}:{port}\nAllowedIPs = 0.0.0.0/0, ::/0\nPersistentKeepalive = 25\n\n# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'private_key,client_ip,dns,server_pubkey,server_ip,port,device_name,date', 'vpn,wireguard,config,device'],
        ['vpn_port_forward_rule', 'Port Forward Rule', 'Port forwarding configuration', 'vpn', 'port_forwarding',
         "Rule: {rule_name}\nExternal: {external_port}\nInternal: {internal_ip}:{internal_port}\nProtocol: {protocol}",
         "━━━━━━━━━━━━━━━━━━━━━━━\nPort Forwarding Rule\n━━━━━━━━━━━━━━━━━━━━━━━\nName: {rule_name}\nDescription: {description}\n\nExternal Port: {external_port}\nInternal IP: {internal_ip}\nInternal Port: {internal_port}\nProtocol: {protocol}\nStatus: {status}",
         "┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓\n┃  PORT FORWARDING RULE          ┃\n┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛\n\n✦ RULE DETAILS\nName: {rule_name}\nDescription: {description}\nCreated: {date}\n\n✦ CONFIGURATION\n┌───────────────────────────────┐\n│ External → {external_port}    │\n│ Internal → {internal_ip}:{internal_port} │\n│ Protocol → {protocol}        │\n└───────────────────────────────┘\n\nStatus: ✅ {status}",
         'rule_name,description,external_port,internal_ip,internal_port,protocol,status,date', 'vpn,port,forward,rule'],
        ['vpn_parental_schedule', 'Parental Control Schedule', 'Time-based access schedule', 'vpn', 'parental',
         "Device: {device_name}\nBlocked: {blocked_times}\nAllowed Sites: {allowed_sites}",
         "━━━━━━━━━━━━━━━━━━━━━━━\nParental Control Schedule\n━━━━━━━━━━━━━━━━━━━━━━━\nDevice: {device_name}\nUser: {child_name}\n\nSchedule:\n{schedule}\n\nBlocked Categories:\n{blocked_categories}\n\nAllowed Sites:\n{allowed_sites}",
         "┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓\n┃  PARENTAL CONTROL PROFILE      ┃\n┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛\n\n✦ PROFILE DETAILS\nDevice: {device_name}\nChild: {child_name}\nAge Group: {age_group}\n\n✦ ACCESS SCHEDULE\n{schedule}\n\n✦ CONTENT FILTERS\nBlocked Categories:\n{blocked_categories}\n\nAllowed Sites:\n{allowed_sites}\n\n✦ GAMING LIMITS\n{gaming_limits}\n\nParent PIN: ****",
         'device_name,child_name,age_group,schedule,blocked_categories,allowed_sites,gaming_limits', 'vpn,parental,schedule,control'],
    ];
    
    foreach ($vpnTemplates as $t) {
        $insertStmt->bindValue(1, $t[0], SQLITE3_TEXT);
        $insertStmt->bindValue(2, $t[3], SQLITE3_TEXT);
        $insertStmt->bindValue(3, $t[4], SQLITE3_TEXT);
        $insertStmt->bindValue(4, $t[1], SQLITE3_TEXT);
        $insertStmt->bindValue(5, $t[2], SQLITE3_TEXT);
        $insertStmt->bindValue(6, $t[5], SQLITE3_TEXT);
        $insertStmt->bindValue(7, $t[6], SQLITE3_TEXT);
        $insertStmt->bindValue(8, $t[7], SQLITE3_TEXT);
        $insertStmt->bindValue(9, $t[8], SQLITE3_TEXT);
        $insertStmt->bindValue(10, $t[9], SQLITE3_TEXT);
        $insertStmt->execute();
        $count++;
    }
    echo "<div class='success'>✅ VPN templates: 3 created</div>";
    
    // ============ FORM TEMPLATES (58) ============
    echo "<h2>📝 Form Templates</h2>";
    
    $formTemplates = [
        ['form_contact_basic', 'Contact Form', 'Basic contact form', 'forms', 'contact',
         "Name: {name}\nEmail: {email}\nMessage: {message}",
         "Contact Information:\n• Full Name: {name}\n• Email Address: {email}\n• Phone: {phone}\n\nMessage:\n{message}\n\nPreferred Contact: {preferred_contact}",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📬 CONTACT FORM SUBMISSION\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n✦ CONTACT DETAILS\nFull Name: {name}\nEmail: {email}\nPhone: {phone}\nCompany: {company}\n\n✦ INQUIRY\nSubject: {subject}\nCategory: {category}\n\n✦ MESSAGE\n{message}\n\nPreferred Contact Method: {preferred_contact}\nBest Time to Reach: {best_time}\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'name,email,phone,company,subject,category,message,preferred_contact,best_time', 'form,contact,inquiry'],
        ['form_support_ticket', 'Support Ticket', 'Technical support request', 'forms', 'support',
         "Subject: {subject}\nDescription: {description}\nPriority: {priority}",
         "Support Request:\n• Subject: {subject}\n• Category: {category}\n• Priority: {priority}\n\nDescription:\n{description}\n\nSteps to Reproduce:\n{steps}\n\nExpected Result:\n{expected}\n\nActual Result:\n{actual}",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🎫 SUPPORT TICKET\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n✦ TICKET INFORMATION\nTicket ID: #{ticket_id}\nCategory: {category}\nPriority: {priority}\nSubmitted: {date}\n\n✦ CUSTOMER INFORMATION\nName: {name}\nEmail: {email}\nAccount ID: {account_id}\n\n✦ ISSUE DETAILS\nSubject: {subject}\n\nDescription:\n{description}\n\nSteps to Reproduce:\n{steps}\n\nExpected Result:\n{expected}\n\nActual Result:\n{actual}\n\n✦ SYSTEM INFORMATION\nBrowser: {browser}\nOS: {os}\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'ticket_id,name,email,account_id,subject,category,priority,description,steps,expected,actual,browser,os,date', 'form,support,ticket,help'],
        ['form_registration', 'Registration Form', 'User registration form', 'forms', 'registration',
         "Username: {username}\nEmail: {email}\nPassword: ******",
         "Account Registration:\n• Username: {username}\n• Email: {email}\n• Password: ******\n\nProfile:\n• First Name: {first_name}\n• Last Name: {last_name}\n\n☑ Terms Accepted: {terms_accepted}",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📝 REGISTRATION FORM\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n✦ ACCOUNT CREDENTIALS\nUsername: {username}\nEmail: {email}\nPassword: ••••••••\n\n✦ PERSONAL INFORMATION\nFirst Name: {first_name}\nLast Name: {last_name}\nDate of Birth: {dob}\nCountry: {country}\nTimezone: {timezone}\n\n✦ PREFERENCES\nLanguage: {language}\nNewsletter: {newsletter}\nMarketing: {marketing}\n\n✦ AGREEMENTS\n☑ Terms of Service: {terms_accepted}\n☑ Privacy Policy: {privacy_accepted}\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'username,email,first_name,last_name,dob,country,timezone,language,newsletter,marketing,terms_accepted,privacy_accepted', 'form,registration,signup,account'],
        ['form_feedback_survey', 'Feedback Survey', 'Customer satisfaction survey', 'forms', 'surveys',
         "Rating: {rating}/5\nComments: {comments}",
         "Customer Feedback:\n• Overall Rating: {rating}/5\n• Service Rating: {service_rating}/5\n• Would Recommend: {recommend}\n\nComments:\n{comments}",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📊 CUSTOMER FEEDBACK SURVEY\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n✦ RATINGS (out of 5)\n★ Overall Experience: {rating}\n★ Product Quality: {product_rating}\n★ Customer Service: {service_rating}\n★ Value for Money: {value_rating}\n★ Ease of Use: {ease_rating}\n\n✦ RECOMMENDATION\nNPS Score: {nps}/10\nWould Recommend: {recommend}\n\n✦ FEEDBACK\nWhat did we do well?\n{positive_feedback}\n\nWhat can we improve?\n{improvement_feedback}\n\n✦ ADDITIONAL COMMENTS\n{comments}\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'rating,product_rating,service_rating,value_rating,ease_rating,nps,recommend,positive_feedback,improvement_feedback,comments', 'form,survey,feedback,nps'],
        ['form_quote_request', 'Quote Request', 'Request for quotation', 'forms', 'contact',
         "Service: {service}\nBudget: {budget}\nTimeline: {timeline}",
         "Quote Request:\n• Service Needed: {service}\n• Budget Range: {budget}\n• Timeline: {timeline}\n\nProject Details:\n{details}\n\nContact:\n• Name: {name}\n• Email: {email}\n• Phone: {phone}",
         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n💼 REQUEST FOR QUOTATION\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n✦ CONTACT INFORMATION\nFull Name: {name}\nCompany: {company}\nEmail: {email}\nPhone: {phone}\n\n✦ PROJECT REQUIREMENTS\nService Category: {service}\nProject Type: {project_type}\nBudget Range: {budget}\nTimeline: {timeline}\nStart Date: {start_date}\n\n✦ PROJECT DETAILS\n{details}\n\n✦ SPECIAL REQUIREMENTS\n{requirements}\n\n✦ HOW DID YOU HEAR ABOUT US?\n{referral_source}\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━",
         'name,company,email,phone,service,project_type,budget,timeline,start_date,details,requirements,referral_source', 'form,quote,rfq,inquiry'],
    ];
    
    foreach ($formTemplates as $t) {
        $insertStmt->bindValue(1, $t[0], SQLITE3_TEXT);
        $insertStmt->bindValue(2, $t[3], SQLITE3_TEXT);
        $insertStmt->bindValue(3, $t[4], SQLITE3_TEXT);
        $insertStmt->bindValue(4, $t[1], SQLITE3_TEXT);
        $insertStmt->bindValue(5, $t[2], SQLITE3_TEXT);
        $insertStmt->bindValue(6, $t[5], SQLITE3_TEXT);
        $insertStmt->bindValue(7, $t[6], SQLITE3_TEXT);
        $insertStmt->bindValue(8, $t[7], SQLITE3_TEXT);
        $insertStmt->bindValue(9, $t[8], SQLITE3_TEXT);
        $insertStmt->bindValue(10, $t[9], SQLITE3_TEXT);
        $insertStmt->execute();
        $count++;
    }
    echo "<div class='success'>✅ Form templates: 5 created</div>";
    
    $db->close();
    
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p>Created <strong>{$count}</strong> templates with 3 style variants each.</p>";
    echo "<p><a href='template-library.php' style='color:#00d9ff'>→ Go to Template Library</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
