-- Marketing Automation Database Schema
-- Created: January 19, 2026

-- Marketing platforms configuration
CREATE TABLE IF NOT EXISTS marketing_platforms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    platform_name TEXT NOT NULL UNIQUE,
    platform_type TEXT NOT NULL,           -- email, social, sms, ads
    api_endpoint TEXT,
    requires_api_key INTEGER DEFAULT 1,
    is_active INTEGER DEFAULT 1,
    icon TEXT,
    description TEXT,
    documentation_url TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Platform credentials (per user/account)
CREATE TABLE IF NOT EXISTS platform_credentials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    platform_id INTEGER NOT NULL,
    credential_name TEXT NOT NULL,          -- User-friendly name
    api_key TEXT,
    api_secret TEXT,
    access_token TEXT,
    refresh_token TEXT,
    additional_data TEXT,                   -- JSON for extra fields
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (platform_id) REFERENCES marketing_platforms(id) ON DELETE CASCADE
);

-- Marketing campaigns
CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_name TEXT NOT NULL,
    campaign_type TEXT NOT NULL,            -- email, social, multi
    status TEXT DEFAULT 'draft',            -- draft, scheduled, active, paused, completed
    target_audience TEXT,                   -- JSON: {plan: "family", status: "active"}
    platforms TEXT,                         -- JSON array of platform IDs
    start_date TEXT,
    end_date TEXT,
    created_by INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Campaign messages/content
CREATE TABLE IF NOT EXISTS campaign_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER NOT NULL,
    platform_id INTEGER NOT NULL,
    message_type TEXT,                      -- email, post, tweet, ad
    subject TEXT,
    body TEXT,
    media_url TEXT,                         -- For images/videos
    call_to_action TEXT,
    scheduled_time TEXT,
    status TEXT DEFAULT 'pending',          -- pending, sent, failed
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES marketing_platforms(id) ON DELETE CASCADE
);

-- Campaign analytics
CREATE TABLE IF NOT EXISTS campaign_analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER NOT NULL,
    platform_id INTEGER NOT NULL,
    metric_name TEXT NOT NULL,              -- impressions, clicks, conversions, etc.
    metric_value INTEGER DEFAULT 0,
    metric_date TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES marketing_platforms(id) ON DELETE CASCADE
);

-- Email templates
CREATE TABLE IF NOT EXISTS email_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_name TEXT NOT NULL,
    template_type TEXT,                     -- welcome, promo, newsletter, transactional
    subject_line TEXT NOT NULL,
    html_body TEXT NOT NULL,
    text_body TEXT,
    variables TEXT,                         -- JSON array of available variables
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_campaigns_status ON marketing_campaigns(status);
CREATE INDEX IF NOT EXISTS idx_campaigns_dates ON marketing_campaigns(start_date, end_date);
CREATE INDEX IF NOT EXISTS idx_messages_campaign ON campaign_messages(campaign_id);
CREATE INDEX IF NOT EXISTS idx_messages_status ON campaign_messages(status);
CREATE INDEX IF NOT EXISTS idx_analytics_campaign ON campaign_analytics(campaign_id);
CREATE INDEX IF NOT EXISTS idx_credentials_platform ON platform_credentials(platform_id);

-- Insert 50+ marketing platforms
INSERT OR IGNORE INTO marketing_platforms (platform_name, platform_type, icon, description) VALUES
-- Email Marketing (15)
('Mailchimp', 'email', '📧', 'Popular email marketing platform'),
('Constant Contact', 'email', '📧', 'Email marketing and automation'),
('SendGrid', 'email', '📧', 'Transactional and marketing email'),
('Mailgun', 'email', '📧', 'Email API service'),
('Amazon SES', 'email', '📧', 'Amazon email service'),
('SendinBlue', 'email', '📧', 'Email marketing and SMS'),
('GetResponse', 'email', '📧', 'Email marketing automation'),
('AWeber', 'email', '📧', 'Email marketing for small business'),
('ConvertKit', 'email', '📧', 'Email marketing for creators'),
('ActiveCampaign', 'email', '📧', 'Customer experience automation'),
('Drip', 'email', '📧', 'E-commerce CRM'),
('MailerLite', 'email', '📧', 'Email marketing made simple'),
('Campaign Monitor', 'email', '📧', 'Email marketing platform'),
('Klaviyo', 'email', '📧', 'E-commerce email marketing'),
('HubSpot Email', 'email', '📧', 'Email marketing by HubSpot'),

-- Social Media (20)
('Facebook', 'social', '📘', 'Facebook posts and ads'),
('Instagram', 'social', '📷', 'Instagram posts and stories'),
('Twitter', 'social', '🐦', 'Twitter tweets and threads'),
('LinkedIn', 'social', '💼', 'LinkedIn posts and articles'),
('Pinterest', 'social', '📌', 'Pinterest pins and boards'),
('TikTok', 'social', '🎵', 'TikTok videos'),
('YouTube', 'social', '📺', 'YouTube videos'),
('Reddit', 'social', '🤖', 'Reddit posts'),
('Snapchat', 'social', '👻', 'Snapchat stories and ads'),
('WhatsApp Business', 'social', '💬', 'WhatsApp messaging'),
('Telegram', 'social', '✈️', 'Telegram channels'),
('Discord', 'social', '🎮', 'Discord communities'),
('Tumblr', 'social', '📝', 'Tumblr blog posts'),
('Medium', 'social', '✍️', 'Medium articles'),
('Quora', 'social', '❓', 'Quora answers'),
('Buffer', 'social', '📊', 'Social media scheduler'),
('Hootsuite', 'social', '🦉', 'Social media management'),
('Sprout Social', 'social', '🌱', 'Social media platform'),
('Later', 'social', '⏰', 'Visual social media planner'),
('Planoly', 'social', '📅', 'Instagram planner'),

-- SMS Marketing (5)
('Twilio', 'sms', '📱', 'SMS and voice API'),
('Nexmo', 'sms', '📱', 'SMS API platform'),
('ClickSend', 'sms', '📱', 'SMS marketing'),
('EZ Texting', 'sms', '📱', 'Business text messaging'),
('SimpleTexting', 'sms', '📱', 'SMS marketing service'),

-- Advertising (10)
('Google Ads', 'ads', '🎯', 'Google advertising'),
('Facebook Ads', 'ads', '📘', 'Facebook advertising'),
('Instagram Ads', 'ads', '📷', 'Instagram advertising'),
('Twitter Ads', 'ads', '🐦', 'Twitter advertising'),
('LinkedIn Ads', 'ads', '💼', 'LinkedIn advertising'),
('TikTok Ads', 'ads', '🎵', 'TikTok advertising'),
('Snapchat Ads', 'ads', '👻', 'Snapchat advertising'),
('Pinterest Ads', 'ads', '📌', 'Pinterest advertising'),
('Reddit Ads', 'ads', '🤖', 'Reddit advertising'),
('Bing Ads', 'ads', '🔍', 'Microsoft advertising'),

-- Others (5)
('Slack', 'messaging', '💬', 'Team communication'),
('Microsoft Teams', 'messaging', '💼', 'Business communication'),
('Zapier', 'automation', '⚡', 'Workflow automation'),
('IFTTT', 'automation', '🔗', 'Connect services'),
('Make (Integromat)', 'automation', '🔧', 'Advanced automation');

-- Sample email templates
INSERT OR IGNORE INTO email_templates (template_name, template_type, subject_line, html_body, text_body, variables) VALUES
('Welcome Email', 'welcome', 'Welcome to TrueVault VPN!', 
'<h1>Welcome {first_name}!</h1><p>Thanks for joining TrueVault VPN.</p>', 
'Welcome {first_name}! Thanks for joining TrueVault VPN.',
'["first_name", "email", "plan"]'),

('Promo Campaign', 'promo', 'Special Offer: {discount}% Off!',
'<h1>Limited Time Offer</h1><p>Get {discount}% off your subscription!</p>',
'Limited Time Offer - Get {discount}% off your subscription!',
'["first_name", "discount", "promo_code"]'),

('Newsletter', 'newsletter', 'TrueVault VPN Monthly Newsletter',
'<h1>Monthly Updates</h1><p>Here''s what''s new this month...</p>',
'Monthly Updates - Here''s what''s new this month...',
'["first_name", "month", "year"]');
