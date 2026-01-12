<?php
/**
 * TrueVault VPN - Database Setup Script
 * Creates all SQLite databases with schemas and default data
 * 
 * Run this script once to initialize all databases:
 * php setup-databases.php
 * 
 * Created: January 11, 2026
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base path for databases
define('DB_BASE_PATH', __DIR__ . '/../databases');

// Database paths
$databases = [
    'core' => ['users', 'sessions', 'admin'],
    'vpn' => ['servers', 'connections', 'certificates', 'identities', 'routing'],
    'devices' => ['discovered', 'cameras', 'port_forwarding', 'mesh_network'],
    'billing' => ['subscriptions', 'invoices', 'payments', 'transactions'],
    'cms' => ['pages', 'themes', 'templates', 'media'],
    'automation' => ['workflows', 'tasks', 'logs', 'emails'],
    'analytics' => ['usage', 'bandwidth', 'events']
];

/**
 * Create directory if it doesn't exist
 */
function ensureDirectory($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "Created directory: $path\n";
    }
}

/**
 * Create SQLite database and execute schema
 */
function createDatabase($folder, $name, $schema) {
    $dbPath = DB_BASE_PATH . "/$folder/$name.db";
    
    // Ensure directory exists
    ensureDirectory(DB_BASE_PATH . "/$folder");
    
    // Remove existing database if present
    if (file_exists($dbPath)) {
        unlink($dbPath);
        echo "Removed existing database: $dbPath\n";
    }
    
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Execute schema
        $pdo->exec($schema);
        
        echo "✓ Created database: $folder/$name.db\n";
        return $pdo;
    } catch (PDOException $e) {
        echo "✗ Error creating $folder/$name.db: " . $e->getMessage() . "\n";
        return null;
    }
}

// ============================================
// SCHEMAS
// ============================================

// CORE: users.db
$schema_users = "
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    plan_type TEXT DEFAULT 'personal' CHECK(plan_type IN ('personal', 'family', 'business', 'trial')),
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'active', 'suspended', 'cancelled')),
    is_vip INTEGER DEFAULT 0,
    vip_server_id INTEGER,
    device_limit INTEGER DEFAULT 3,
    mesh_user_limit INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    email_verified INTEGER DEFAULT 0,
    email_verification_token TEXT,
    two_factor_enabled INTEGER DEFAULT 0,
    two_factor_secret TEXT,
    password_reset_token TEXT,
    password_reset_expires DATETIME
);

CREATE TABLE user_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    setting_key TEXT NOT NULL,
    setting_value TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, setting_key)
);

CREATE TABLE user_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_uuid TEXT UNIQUE NOT NULL,
    device_name TEXT,
    device_type TEXT CHECK(device_type IN ('desktop', 'laptop', 'mobile', 'tablet', 'router', 'other')),
    os_type TEXT,
    public_key TEXT,
    private_key_encrypted TEXT,
    last_connected DATETIME,
    last_ip TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_uuid ON users(uuid);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_user_devices_user_id ON user_devices(user_id);
CREATE INDEX idx_user_settings_user_id ON user_settings(user_id);
";

// CORE: sessions.db
$schema_sessions = "
CREATE TABLE sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT UNIQUE NOT NULL,
    refresh_token TEXT UNIQUE,
    ip_address TEXT,
    user_agent TEXT,
    device_info TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_valid INTEGER DEFAULT 1
);

CREATE TABLE refresh_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_revoked INTEGER DEFAULT 0
);

CREATE INDEX idx_sessions_token ON sessions(token);
CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_expires ON sessions(expires_at);
CREATE INDEX idx_refresh_tokens_token ON refresh_tokens(token);
";

// CORE: admin.db
$schema_admin = "
CREATE TABLE admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    role_id INTEGER DEFAULT 1,
    is_active INTEGER DEFAULT 1,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin_roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_name TEXT UNIQUE NOT NULL,
    role_slug TEXT UNIQUE NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    permission_name TEXT UNIQUE NOT NULL,
    permission_slug TEXT UNIQUE NOT NULL,
    description TEXT,
    category TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE role_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    FOREIGN KEY (role_id) REFERENCES admin_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES admin_permissions(id) ON DELETE CASCADE,
    UNIQUE(role_id, permission_id)
);

CREATE TABLE admin_activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    entity_type TEXT,
    entity_id INTEGER,
    details TEXT,
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_admin_users_email ON admin_users(email);
CREATE INDEX idx_admin_activity_admin_id ON admin_activity_log(admin_id);
";

// VPN: servers.db
$schema_servers = "
CREATE TABLE vpn_servers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_name TEXT NOT NULL,
    server_slug TEXT UNIQUE NOT NULL,
    server_type TEXT DEFAULT 'wireguard' CHECK(server_type IN ('wireguard', 'openvpn')),
    provider TEXT NOT NULL,
    region TEXT NOT NULL,
    country TEXT NOT NULL,
    city TEXT,
    ip_address TEXT NOT NULL,
    ipv6_address TEXT,
    wireguard_port INTEGER DEFAULT 51820,
    api_port INTEGER DEFAULT 8080,
    api_secret TEXT,
    public_key TEXT,
    endpoint TEXT,
    dns_servers TEXT DEFAULT '1.1.1.1, 1.0.0.1',
    allowed_ips TEXT DEFAULT '0.0.0.0/0, ::/0',
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'maintenance', 'offline', 'full')),
    max_connections INTEGER DEFAULT 100,
    current_connections INTEGER DEFAULT 0,
    bandwidth_limit_mbps INTEGER,
    is_vip_only INTEGER DEFAULT 0,
    vip_user_email TEXT,
    load_percent INTEGER DEFAULT 0,
    latency_ms INTEGER,
    last_health_check DATETIME,
    health_status TEXT DEFAULT 'unknown',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE server_configs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_id INTEGER NOT NULL,
    config_key TEXT NOT NULL,
    config_value TEXT,
    FOREIGN KEY (server_id) REFERENCES vpn_servers(id) ON DELETE CASCADE,
    UNIQUE(server_id, config_key)
);

CREATE INDEX idx_servers_status ON vpn_servers(status);
CREATE INDEX idx_servers_region ON vpn_servers(region);
CREATE INDEX idx_servers_vip ON vpn_servers(is_vip_only);
";

// VPN: connections.db
$schema_connections = "
CREATE TABLE active_connections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    server_id INTEGER NOT NULL,
    device_id INTEGER,
    client_ip TEXT,
    assigned_ip TEXT,
    public_key TEXT,
    bytes_sent INTEGER DEFAULT 0,
    bytes_received INTEGER DEFAULT 0,
    connected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_handshake DATETIME,
    identity_id INTEGER
);

CREATE TABLE connection_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    server_id INTEGER NOT NULL,
    device_id INTEGER,
    client_ip TEXT,
    assigned_ip TEXT,
    bytes_sent INTEGER DEFAULT 0,
    bytes_received INTEGER DEFAULT 0,
    connected_at DATETIME,
    disconnected_at DATETIME,
    duration_seconds INTEGER,
    disconnect_reason TEXT
);

CREATE INDEX idx_active_connections_user ON active_connections(user_id);
CREATE INDEX idx_active_connections_server ON active_connections(server_id);
CREATE INDEX idx_connection_history_user ON connection_history(user_id);
CREATE INDEX idx_connection_history_date ON connection_history(connected_at);
";

// VPN: certificates.db
$schema_certificates = "
CREATE TABLE certificate_authority (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE NOT NULL,
    ca_certificate TEXT NOT NULL,
    ca_private_key_encrypted TEXT NOT NULL,
    ca_serial INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_active INTEGER DEFAULT 1
);

CREATE TABLE user_certificates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    ca_id INTEGER NOT NULL,
    certificate_type TEXT NOT NULL CHECK(certificate_type IN ('root', 'device', 'regional', 'mesh')),
    certificate_name TEXT NOT NULL,
    certificate_data TEXT NOT NULL,
    private_key_encrypted TEXT,
    public_key TEXT,
    serial_number TEXT UNIQUE NOT NULL,
    issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_revoked INTEGER DEFAULT 0,
    revoked_at DATETIME,
    revocation_reason TEXT,
    device_id INTEGER,
    region_code TEXT,
    mesh_network_id INTEGER,
    FOREIGN KEY (user_id) REFERENCES certificate_authority(user_id) ON DELETE CASCADE,
    FOREIGN KEY (ca_id) REFERENCES certificate_authority(id) ON DELETE CASCADE
);

CREATE TABLE certificate_revocations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    certificate_id INTEGER NOT NULL,
    serial_number TEXT NOT NULL,
    revoked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reason TEXT,
    FOREIGN KEY (certificate_id) REFERENCES user_certificates(id)
);

CREATE INDEX idx_user_certificates_user ON user_certificates(user_id);
CREATE INDEX idx_user_certificates_type ON user_certificates(certificate_type);
CREATE INDEX idx_user_certificates_serial ON user_certificates(serial_number);
";

// VPN: identities.db
$schema_identities = "
CREATE TABLE regional_identities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    region_code TEXT NOT NULL,
    region_name TEXT NOT NULL,
    assigned_server_id INTEGER,
    persistent_ip TEXT,
    browser_fingerprint TEXT,
    timezone TEXT,
    locale TEXT,
    user_agent_template TEXT,
    last_used DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active INTEGER DEFAULT 1,
    UNIQUE(user_id, region_code)
);

CREATE TABLE identity_fingerprints (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    identity_id INTEGER NOT NULL,
    fingerprint_type TEXT NOT NULL,
    fingerprint_value TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (identity_id) REFERENCES regional_identities(id) ON DELETE CASCADE
);

CREATE TABLE regions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    region_code TEXT UNIQUE NOT NULL,
    region_name TEXT NOT NULL,
    country_code TEXT NOT NULL,
    timezone TEXT,
    locale TEXT,
    is_available INTEGER DEFAULT 1
);

CREATE INDEX idx_identities_user ON regional_identities(user_id);
CREATE INDEX idx_identities_region ON regional_identities(region_code);
";

// VPN: routing.db
$schema_routing = "
CREATE TABLE routing_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    rule_name TEXT NOT NULL,
    rule_type TEXT NOT NULL CHECK(rule_type IN ('app', 'domain', 'ip', 'port', 'protocol')),
    match_pattern TEXT NOT NULL,
    action TEXT NOT NULL CHECK(action IN ('direct', 'vpn', 'block', 'identity')),
    target_server_id INTEGER,
    target_identity_id INTEGER,
    priority INTEGER DEFAULT 100,
    is_system INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE routing_patterns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pattern_name TEXT NOT NULL,
    pattern_type TEXT NOT NULL,
    pattern_category TEXT,
    patterns TEXT NOT NULL,
    recommended_action TEXT,
    description TEXT,
    is_system INTEGER DEFAULT 1
);

CREATE TABLE user_routing_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    preference_key TEXT NOT NULL,
    preference_value TEXT,
    UNIQUE(user_id, preference_key)
);

CREATE INDEX idx_routing_rules_user ON routing_rules(user_id);
CREATE INDEX idx_routing_rules_type ON routing_rules(rule_type);
";

// DEVICES: discovered.db
$schema_discovered = "
CREATE TABLE discovered_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    mac_address TEXT,
    hostname TEXT,
    vendor TEXT,
    device_type TEXT,
    device_icon TEXT,
    type_name TEXT,
    is_local INTEGER DEFAULT 0,
    is_online INTEGER DEFAULT 1,
    last_seen DATETIME,
    first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, mac_address)
);

CREATE TABLE device_ports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id INTEGER NOT NULL,
    port_number INTEGER NOT NULL,
    protocol TEXT DEFAULT 'tcp',
    service_name TEXT,
    is_open INTEGER DEFAULT 1,
    last_checked DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES discovered_devices(id) ON DELETE CASCADE,
    UNIQUE(device_id, port_number, protocol)
);

CREATE INDEX idx_discovered_user ON discovered_devices(user_id);
CREATE INDEX idx_discovered_mac ON discovered_devices(mac_address);
CREATE INDEX idx_discovered_type ON discovered_devices(device_type);
";

// DEVICES: cameras.db
$schema_cameras = "
CREATE TABLE discovered_cameras (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id TEXT NOT NULL,
    camera_name TEXT,
    ip_address TEXT NOT NULL,
    mac_address TEXT,
    vendor TEXT,
    model TEXT,
    firmware_version TEXT,
    rtsp_port INTEGER DEFAULT 554,
    http_port INTEGER DEFAULT 80,
    https_port INTEGER DEFAULT 443,
    onvif_port INTEGER,
    username TEXT,
    password_encrypted TEXT,
    stream_url_main TEXT,
    stream_url_sub TEXT,
    snapshot_url TEXT,
    is_ptz INTEGER DEFAULT 0,
    has_audio INTEGER DEFAULT 0,
    has_two_way_audio INTEGER DEFAULT 0,
    has_night_vision INTEGER DEFAULT 1,
    has_motion_detection INTEGER DEFAULT 1,
    has_floodlight INTEGER DEFAULT 0,
    is_online INTEGER DEFAULT 1,
    last_seen DATETIME,
    thumbnail_path TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE camera_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    camera_id INTEGER NOT NULL,
    setting_key TEXT NOT NULL,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES discovered_cameras(id) ON DELETE CASCADE,
    UNIQUE(camera_id, setting_key)
);

CREATE TABLE camera_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    camera_id INTEGER NOT NULL,
    event_type TEXT NOT NULL CHECK(event_type IN ('motion', 'sound', 'person', 'vehicle', 'animal', 'package', 'manual')),
    event_data TEXT,
    thumbnail_path TEXT,
    video_clip_path TEXT,
    duration_seconds INTEGER,
    is_viewed INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES discovered_cameras(id) ON DELETE CASCADE
);

CREATE TABLE camera_recordings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    camera_id INTEGER NOT NULL,
    recording_type TEXT DEFAULT 'continuous' CHECK(recording_type IN ('continuous', 'motion', 'scheduled', 'manual')),
    file_path TEXT NOT NULL,
    file_size INTEGER,
    duration_seconds INTEGER,
    start_time DATETIME NOT NULL,
    end_time DATETIME,
    is_archived INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES discovered_cameras(id) ON DELETE CASCADE
);

CREATE INDEX idx_cameras_user ON discovered_cameras(user_id);
CREATE INDEX idx_cameras_mac ON discovered_cameras(mac_address);
CREATE INDEX idx_camera_events_camera ON camera_events(camera_id);
CREATE INDEX idx_camera_events_type ON camera_events(event_type);
CREATE INDEX idx_camera_events_date ON camera_events(created_at);
";

// DEVICES: port_forwarding.db
$schema_port_forwarding = "
CREATE TABLE port_forwarding_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    rule_name TEXT NOT NULL,
    device_id INTEGER,
    internal_ip TEXT NOT NULL,
    internal_port INTEGER NOT NULL,
    external_port INTEGER NOT NULL,
    protocol TEXT DEFAULT 'tcp' CHECK(protocol IN ('tcp', 'udp', 'both')),
    server_id INTEGER,
    is_enabled INTEGER DEFAULT 1,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, external_port, protocol)
);

CREATE INDEX idx_port_forwarding_user ON port_forwarding_rules(user_id);
CREATE INDEX idx_port_forwarding_device ON port_forwarding_rules(device_id);
";

// DEVICES: mesh_network.db
$schema_mesh = "
CREATE TABLE mesh_networks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_id INTEGER NOT NULL,
    network_name TEXT NOT NULL,
    network_uuid TEXT UNIQUE NOT NULL,
    description TEXT,
    max_members INTEGER DEFAULT 6,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE mesh_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    network_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role TEXT DEFAULT 'member' CHECK(role IN ('owner', 'admin', 'member')),
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active INTEGER DEFAULT 1,
    FOREIGN KEY (network_id) REFERENCES mesh_networks(id) ON DELETE CASCADE,
    UNIQUE(network_id, user_id)
);

CREATE TABLE mesh_invitations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    network_id INTEGER NOT NULL,
    inviter_id INTEGER NOT NULL,
    invitee_email TEXT NOT NULL,
    invitation_token TEXT UNIQUE NOT NULL,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'accepted', 'declined', 'expired')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    accepted_at DATETIME,
    FOREIGN KEY (network_id) REFERENCES mesh_networks(id) ON DELETE CASCADE
);

CREATE TABLE mesh_connections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    network_id INTEGER NOT NULL,
    from_user_id INTEGER NOT NULL,
    to_user_id INTEGER NOT NULL,
    connection_status TEXT DEFAULT 'disconnected',
    last_connected DATETIME,
    bytes_transferred INTEGER DEFAULT 0,
    FOREIGN KEY (network_id) REFERENCES mesh_networks(id) ON DELETE CASCADE
);

CREATE INDEX idx_mesh_members_network ON mesh_members(network_id);
CREATE INDEX idx_mesh_members_user ON mesh_members(user_id);
CREATE INDEX idx_mesh_invitations_token ON mesh_invitations(invitation_token);
";

// BILLING: subscriptions.db
$schema_subscriptions = "
CREATE TABLE subscription_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plan_name TEXT NOT NULL,
    plan_slug TEXT UNIQUE NOT NULL,
    description TEXT,
    price_monthly REAL NOT NULL,
    price_yearly REAL,
    device_limit INTEGER DEFAULT 3,
    mesh_user_limit INTEGER DEFAULT 0,
    identity_limit INTEGER DEFAULT 3,
    bandwidth_limit_gb INTEGER,
    features TEXT,
    is_active INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE NOT NULL,
    plan_id INTEGER NOT NULL,
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'past_due', 'cancelled', 'expired', 'trial')),
    billing_cycle TEXT DEFAULT 'monthly' CHECK(billing_cycle IN ('monthly', 'yearly')),
    current_period_start DATETIME,
    current_period_end DATETIME,
    trial_ends_at DATETIME,
    cancelled_at DATETIME,
    cancel_reason TEXT,
    paypal_subscription_id TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

CREATE INDEX idx_subscriptions_user ON subscriptions(user_id);
CREATE INDEX idx_subscriptions_status ON subscriptions(status);
CREATE INDEX idx_subscriptions_paypal ON subscriptions(paypal_subscription_id);
";

// BILLING: invoices.db
$schema_invoices = "
CREATE TABLE invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    invoice_number TEXT UNIQUE NOT NULL,
    subscription_id INTEGER,
    status TEXT DEFAULT 'draft' CHECK(status IN ('draft', 'sent', 'paid', 'void', 'refunded')),
    subtotal REAL NOT NULL,
    tax REAL DEFAULT 0,
    total REAL NOT NULL,
    currency TEXT DEFAULT 'USD',
    due_date DATETIME,
    paid_at DATETIME,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE invoice_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    quantity INTEGER DEFAULT 1,
    unit_price REAL NOT NULL,
    total REAL NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

CREATE INDEX idx_invoices_user ON invoices(user_id);
CREATE INDEX idx_invoices_number ON invoices(invoice_number);
CREATE INDEX idx_invoices_status ON invoices(status);
";

// BILLING: payments.db
$schema_payments = "
CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    invoice_id INTEGER,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'USD',
    payment_method TEXT DEFAULT 'paypal',
    payment_status TEXT DEFAULT 'pending' CHECK(payment_status IN ('pending', 'completed', 'failed', 'refunded')),
    paypal_transaction_id TEXT,
    paypal_payer_id TEXT,
    paypal_payer_email TEXT,
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    method_type TEXT DEFAULT 'paypal',
    paypal_email TEXT,
    is_default INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payments_user ON payments(user_id);
CREATE INDEX idx_payments_invoice ON payments(invoice_id);
CREATE INDEX idx_payments_paypal ON payments(paypal_transaction_id);
";

// BILLING: transactions.db
$schema_transactions = "
CREATE TABLE transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('charge', 'refund', 'credit', 'debit')),
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'USD',
    description TEXT,
    reference_type TEXT,
    reference_id INTEGER,
    balance_after REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_transactions_user ON transactions(user_id);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_transactions_date ON transactions(created_at);
";

// CMS: pages.db
$schema_pages = "
CREATE TABLE pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE NOT NULL,
    title TEXT NOT NULL,
    meta_description TEXT,
    meta_keywords TEXT,
    content_html TEXT,
    content_css TEXT,
    grapesjs_data TEXT,
    status TEXT DEFAULT 'draft' CHECK(status IN ('draft', 'published', 'archived')),
    template TEXT DEFAULT 'default',
    author_id INTEGER,
    published_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE page_versions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id INTEGER NOT NULL,
    version_number INTEGER NOT NULL,
    content_html TEXT,
    content_css TEXT,
    grapesjs_data TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);

CREATE INDEX idx_pages_slug ON pages(slug);
CREATE INDEX idx_pages_status ON pages(status);
";

// CMS: themes.db (CRITICAL - ALL STYLING)
$schema_themes = "
CREATE TABLE themes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_name TEXT NOT NULL,
    theme_slug TEXT UNIQUE NOT NULL,
    description TEXT,
    is_active INTEGER DEFAULT 0,
    is_system INTEGER DEFAULT 0,
    preview_image TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE theme_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_id INTEGER NOT NULL,
    setting_category TEXT NOT NULL,
    setting_key TEXT NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type TEXT DEFAULT 'text' CHECK(setting_type IN ('text', 'color', 'number', 'select', 'font', 'size', 'gradient')),
    setting_label TEXT,
    setting_options TEXT,
    sort_order INTEGER DEFAULT 0,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE,
    UNIQUE(theme_id, setting_category, setting_key)
);

CREATE INDEX idx_themes_active ON themes(is_active);
CREATE INDEX idx_theme_settings_theme ON theme_settings(theme_id);
CREATE INDEX idx_theme_settings_category ON theme_settings(setting_category);
";

// CMS: templates.db
$schema_templates = "
CREATE TABLE email_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_name TEXT NOT NULL,
    template_slug TEXT UNIQUE NOT NULL,
    subject TEXT NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT,
    variables TEXT,
    category TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE page_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_name TEXT NOT NULL,
    template_slug TEXT UNIQUE NOT NULL,
    description TEXT,
    content_html TEXT,
    content_css TEXT,
    grapesjs_data TEXT,
    thumbnail TEXT,
    category TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_email_templates_slug ON email_templates(template_slug);
CREATE INDEX idx_page_templates_slug ON page_templates(template_slug);
";

// CMS: media.db
$schema_media = "
CREATE TABLE media_files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    folder_id INTEGER,
    filename TEXT NOT NULL,
    original_filename TEXT NOT NULL,
    file_path TEXT NOT NULL,
    file_type TEXT NOT NULL,
    mime_type TEXT,
    file_size INTEGER,
    width INTEGER,
    height INTEGER,
    alt_text TEXT,
    caption TEXT,
    uploaded_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE media_folders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parent_id INTEGER,
    folder_name TEXT NOT NULL,
    folder_path TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES media_folders(id) ON DELETE CASCADE
);

CREATE INDEX idx_media_files_folder ON media_files(folder_id);
CREATE INDEX idx_media_files_type ON media_files(file_type);
";

// AUTOMATION: workflows.db
$schema_workflows = "
CREATE TABLE workflows (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workflow_name TEXT NOT NULL,
    workflow_slug TEXT UNIQUE NOT NULL,
    description TEXT,
    trigger_type TEXT NOT NULL CHECK(trigger_type IN ('event', 'schedule', 'manual', 'webhook')),
    trigger_config TEXT,
    is_active INTEGER DEFAULT 1,
    is_system INTEGER DEFAULT 0,
    run_count INTEGER DEFAULT 0,
    last_run DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE workflow_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workflow_id INTEGER NOT NULL,
    step_order INTEGER NOT NULL,
    step_name TEXT NOT NULL,
    action_type TEXT NOT NULL CHECK(action_type IN ('email', 'api_call', 'db_update', 'wait', 'condition', 'webhook', 'notification')),
    action_config TEXT NOT NULL,
    delay_seconds INTEGER DEFAULT 0,
    condition_expression TEXT,
    on_success_step INTEGER,
    on_failure_step INTEGER,
    is_active INTEGER DEFAULT 1,
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE
);

CREATE TABLE workflow_triggers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workflow_id INTEGER NOT NULL,
    event_name TEXT NOT NULL,
    is_active INTEGER DEFAULT 1,
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE
);

CREATE TABLE workflow_executions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workflow_id INTEGER NOT NULL,
    trigger_type TEXT,
    trigger_data TEXT,
    status TEXT DEFAULT 'running' CHECK(status IN ('running', 'completed', 'failed', 'cancelled')),
    current_step INTEGER,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    error_message TEXT,
    context_data TEXT,
    FOREIGN KEY (workflow_id) REFERENCES workflows(id)
);

CREATE INDEX idx_workflows_slug ON workflows(workflow_slug);
CREATE INDEX idx_workflows_active ON workflows(is_active);
CREATE INDEX idx_workflow_steps_workflow ON workflow_steps(workflow_id);
CREATE INDEX idx_workflow_executions_workflow ON workflow_executions(workflow_id);
";

// AUTOMATION: tasks.db
$schema_tasks = "
CREATE TABLE scheduled_tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_name TEXT NOT NULL,
    task_type TEXT NOT NULL,
    task_data TEXT,
    workflow_id INTEGER,
    execution_id INTEGER,
    step_id INTEGER,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'running', 'completed', 'failed', 'cancelled')),
    execute_at DATETIME NOT NULL,
    started_at DATETIME,
    completed_at DATETIME,
    retry_count INTEGER DEFAULT 0,
    max_retries INTEGER DEFAULT 3,
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE task_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_id INTEGER,
    task_name TEXT,
    task_type TEXT,
    status TEXT,
    executed_at DATETIME,
    duration_ms INTEGER,
    result TEXT,
    error_message TEXT
);

CREATE INDEX idx_scheduled_tasks_status ON scheduled_tasks(status);
CREATE INDEX idx_scheduled_tasks_execute ON scheduled_tasks(execute_at);
CREATE INDEX idx_task_history_date ON task_history(executed_at);
";

// AUTOMATION: logs.db
$schema_logs = "
CREATE TABLE automation_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workflow_id INTEGER,
    execution_id INTEGER,
    step_id INTEGER,
    log_level TEXT DEFAULT 'info' CHECK(log_level IN ('debug', 'info', 'warning', 'error')),
    message TEXT NOT NULL,
    context_data TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE system_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    log_type TEXT NOT NULL,
    log_level TEXT DEFAULT 'info',
    message TEXT NOT NULL,
    source TEXT,
    user_id INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    request_data TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_automation_logs_workflow ON automation_logs(workflow_id);
CREATE INDEX idx_automation_logs_execution ON automation_logs(execution_id);
CREATE INDEX idx_automation_logs_level ON automation_logs(log_level);
CREATE INDEX idx_system_logs_type ON system_logs(log_type);
CREATE INDEX idx_system_logs_date ON system_logs(created_at);
";

// AUTOMATION: emails.db
$schema_emails = "
CREATE TABLE email_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    to_email TEXT NOT NULL,
    to_name TEXT,
    from_email TEXT,
    from_name TEXT,
    subject TEXT NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT,
    template_id INTEGER,
    template_variables TEXT,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'sending', 'sent', 'failed')),
    priority INTEGER DEFAULT 5,
    attempts INTEGER DEFAULT 0,
    max_attempts INTEGER DEFAULT 3,
    scheduled_at DATETIME,
    sent_at DATETIME,
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE email_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    to_email TEXT NOT NULL,
    to_name TEXT,
    subject TEXT NOT NULL,
    template_slug TEXT,
    status TEXT,
    opened_at DATETIME,
    clicked_at DATETIME,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_email_queue_status ON email_queue(status);
CREATE INDEX idx_email_queue_scheduled ON email_queue(scheduled_at);
CREATE INDEX idx_email_history_email ON email_history(to_email);
CREATE INDEX idx_email_history_date ON email_history(sent_at);
";

// ANALYTICS: usage.db
$schema_usage = "
CREATE TABLE usage_stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    stat_type TEXT NOT NULL,
    stat_value REAL NOT NULL,
    stat_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, stat_type, stat_date)
);

CREATE TABLE daily_usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    date DATE NOT NULL,
    connections INTEGER DEFAULT 0,
    total_duration_seconds INTEGER DEFAULT 0,
    bytes_sent INTEGER DEFAULT 0,
    bytes_received INTEGER DEFAULT 0,
    unique_servers INTEGER DEFAULT 0,
    UNIQUE(user_id, date)
);

CREATE INDEX idx_usage_stats_user ON usage_stats(user_id);
CREATE INDEX idx_usage_stats_date ON usage_stats(stat_date);
CREATE INDEX idx_daily_usage_user ON daily_usage(user_id);
CREATE INDEX idx_daily_usage_date ON daily_usage(date);
";

// ANALYTICS: bandwidth.db
$schema_bandwidth = "
CREATE TABLE bandwidth_usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    server_id INTEGER NOT NULL,
    bytes_sent INTEGER DEFAULT 0,
    bytes_received INTEGER DEFAULT 0,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bandwidth_daily (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    server_id INTEGER,
    date DATE NOT NULL,
    bytes_sent INTEGER DEFAULT 0,
    bytes_received INTEGER DEFAULT 0,
    peak_mbps REAL,
    UNIQUE(user_id, server_id, date)
);

CREATE TABLE bandwidth_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE NOT NULL,
    monthly_limit_gb INTEGER,
    current_usage_gb REAL DEFAULT 0,
    reset_date DATE,
    is_throttled INTEGER DEFAULT 0
);

CREATE INDEX idx_bandwidth_usage_user ON bandwidth_usage(user_id);
CREATE INDEX idx_bandwidth_usage_date ON bandwidth_usage(recorded_at);
CREATE INDEX idx_bandwidth_daily_user ON bandwidth_daily(user_id);
CREATE INDEX idx_bandwidth_daily_date ON bandwidth_daily(date);
";

// ANALYTICS: events.db
$schema_events = "
CREATE TABLE events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    event_type TEXT NOT NULL,
    event_category TEXT,
    event_action TEXT,
    event_label TEXT,
    event_value REAL,
    event_data TEXT,
    ip_address TEXT,
    user_agent TEXT,
    session_id TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE event_aggregates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_type TEXT NOT NULL,
    date DATE NOT NULL,
    count INTEGER DEFAULT 0,
    unique_users INTEGER DEFAULT 0,
    total_value REAL DEFAULT 0,
    UNIQUE(event_type, date)
);

CREATE INDEX idx_events_user ON events(user_id);
CREATE INDEX idx_events_type ON events(event_type);
CREATE INDEX idx_events_date ON events(created_at);
CREATE INDEX idx_event_aggregates_date ON event_aggregates(date);
";

// ============================================
// CREATE ALL DATABASES
// ============================================

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║       TrueVault VPN - Database Setup Script                ║\n";
echo "║       Creating all SQLite databases...                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Ensure base directory exists
ensureDirectory(DB_BASE_PATH);

// Create all databases
echo "Creating CORE databases...\n";
$pdo_users = createDatabase('core', 'users', $schema_users);
$pdo_sessions = createDatabase('core', 'sessions', $schema_sessions);
$pdo_admin = createDatabase('core', 'admin', $schema_admin);

echo "\nCreating VPN databases...\n";
$pdo_servers = createDatabase('vpn', 'servers', $schema_servers);
$pdo_connections = createDatabase('vpn', 'connections', $schema_connections);
$pdo_certificates = createDatabase('vpn', 'certificates', $schema_certificates);
$pdo_identities = createDatabase('vpn', 'identities', $schema_identities);
$pdo_routing = createDatabase('vpn', 'routing', $schema_routing);

echo "\nCreating DEVICES databases...\n";
$pdo_discovered = createDatabase('devices', 'discovered', $schema_discovered);
$pdo_cameras = createDatabase('devices', 'cameras', $schema_cameras);
$pdo_port_forwarding = createDatabase('devices', 'port_forwarding', $schema_port_forwarding);
$pdo_mesh = createDatabase('devices', 'mesh_network', $schema_mesh);

echo "\nCreating BILLING databases...\n";
$pdo_subscriptions = createDatabase('billing', 'subscriptions', $schema_subscriptions);
$pdo_invoices = createDatabase('billing', 'invoices', $schema_invoices);
$pdo_payments = createDatabase('billing', 'payments', $schema_payments);
$pdo_transactions = createDatabase('billing', 'transactions', $schema_transactions);

echo "\nCreating CMS databases...\n";
$pdo_pages = createDatabase('cms', 'pages', $schema_pages);
$pdo_themes = createDatabase('cms', 'themes', $schema_themes);
$pdo_templates = createDatabase('cms', 'templates', $schema_templates);
$pdo_media = createDatabase('cms', 'media', $schema_media);

echo "\nCreating AUTOMATION databases...\n";
$pdo_workflows = createDatabase('automation', 'workflows', $schema_workflows);
$pdo_tasks = createDatabase('automation', 'tasks', $schema_tasks);
$pdo_logs = createDatabase('automation', 'logs', $schema_logs);
$pdo_emails = createDatabase('automation', 'emails', $schema_emails);

echo "\nCreating ANALYTICS databases...\n";
$pdo_usage = createDatabase('analytics', 'usage', $schema_usage);
$pdo_bandwidth = createDatabase('analytics', 'bandwidth', $schema_bandwidth);
$pdo_events = createDatabase('analytics', 'events', $schema_events);

// ============================================
// INSERT DEFAULT DATA
// ============================================

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Inserting default data...\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Insert default admin user
if ($pdo_admin) {
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo_admin->exec("
        INSERT INTO admin_roles (role_name, role_slug, description) VALUES
        ('Super Admin', 'super_admin', 'Full system access'),
        ('Admin', 'admin', 'Administrative access'),
        ('Moderator', 'moderator', 'Limited administrative access');
    ");
    
    $pdo_admin->exec("
        INSERT INTO admin_permissions (permission_name, permission_slug, category) VALUES
        ('View Users', 'view_users', 'users'),
        ('Edit Users', 'edit_users', 'users'),
        ('Delete Users', 'delete_users', 'users'),
        ('View Servers', 'view_servers', 'servers'),
        ('Edit Servers', 'edit_servers', 'servers'),
        ('View Subscriptions', 'view_subscriptions', 'billing'),
        ('Edit Subscriptions', 'edit_subscriptions', 'billing'),
        ('View Logs', 'view_logs', 'system'),
        ('Edit Themes', 'edit_themes', 'cms'),
        ('Edit Pages', 'edit_pages', 'cms'),
        ('Manage Automation', 'manage_automation', 'automation');
    ");
    
    $pdo_admin->exec("
        INSERT INTO admin_users (email, password_hash, first_name, last_name, role_id, is_active) 
        VALUES ('kahlen@truthvault.com', '$password_hash', 'Kah-Len', 'Admin', 1, 1);
    ");
    
    echo "✓ Inserted default admin user (kahlen@truthvault.com / admin123)\n";
}

// Insert VPN servers
if ($pdo_servers) {
    $pdo_servers->exec("
        INSERT INTO vpn_servers (server_name, server_slug, provider, region, country, city, ip_address, ipv6_address, wireguard_port, api_port, status, max_connections, is_vip_only, vip_user_email) VALUES
        ('US East', 'us-east', 'Contabo', 'us-east', 'US', 'New York', '66.94.103.91', '2605:a142:2299:0026:0000:0000:0000:0001', 51820, 8080, 'active', 100, 0, NULL),
        ('US Central (VIP)', 'us-central-vip', 'Contabo', 'us-central', 'US', 'St. Louis', '144.126.133.253', '2605:a140:2299:0005:0000:0000:0000:0001', 51820, 8080, 'active', 1, 1, 'seige235@yahoo.com'),
        ('US South', 'us-south', 'Fly.io', 'us-south', 'US', 'Dallas', '66.241.124.4', NULL, 51820, 8443, 'active', 100, 0, NULL),
        ('Canada', 'canada', 'Fly.io', 'ca-central', 'CA', 'Toronto', '66.241.125.247', NULL, 51820, 8080, 'active', 100, 0, NULL);
    ");
    echo "✓ Inserted 4 VPN servers (including VIP server for seige235@yahoo.com)\n";
}

// Insert subscription plans
if ($pdo_subscriptions) {
    $pdo_subscriptions->exec("
        INSERT INTO subscription_plans (plan_name, plan_slug, description, price_monthly, price_yearly, device_limit, mesh_user_limit, identity_limit, features, is_active, sort_order) VALUES
        ('Personal', 'personal', '3 Devices, Personal Certificates, 3 Regional Identities, Smart Routing, 24/7 Support', 9.99, 99.99, 3, 0, 3, '{\"personal_cert\":true,\"smart_routing\":true,\"support\":\"24/7\"}', 1, 1),
        ('Family', 'family', 'Unlimited Devices, Full Certificate Suite, All Regional Identities, Mesh Networking (6 users), Priority Support, Bandwidth Rewards', 14.99, 149.99, 999, 6, 999, '{\"personal_cert\":true,\"smart_routing\":true,\"mesh\":true,\"support\":\"priority\",\"bandwidth_rewards\":true}', 1, 2),
        ('Business', 'business', 'Unlimited Everything, Enterprise Certificates, Team Mesh (25 users), Admin Dashboard, API Access, Dedicated Support', 29.99, 299.99, 999, 25, 999, '{\"personal_cert\":true,\"smart_routing\":true,\"mesh\":true,\"support\":\"dedicated\",\"api_access\":true,\"admin_dashboard\":true}', 1, 3),
        ('Trial', 'trial', '7-day free trial with Personal plan features', 0, 0, 3, 0, 3, '{\"personal_cert\":true,\"smart_routing\":true,\"trial\":true}', 1, 0);
    ");
    echo "✓ Inserted 4 subscription plans (Personal, Family, Business, Trial)\n";
}

// Insert default theme (CRITICAL - ALL STYLING)
if ($pdo_themes) {
    $pdo_themes->exec("
        INSERT INTO themes (theme_name, theme_slug, description, is_active, is_system) 
        VALUES ('TrueVault Dark', 'truevault-dark', 'Default dark theme for TrueVault VPN', 1, 1);
    ");
    
    // Get the theme ID
    $theme_id = $pdo_themes->lastInsertId();
    
    // Insert all theme settings
    $theme_settings = [
        // Colors
        ['colors', 'primary', '#00d9ff', 'color', 'Primary Color'],
        ['colors', 'secondary', '#00ff88', 'color', 'Secondary Color'],
        ['colors', 'accent', '#ff6b6b', 'color', 'Accent Color'],
        ['colors', 'background', '#0f0f1a', 'color', 'Background Color'],
        ['colors', 'background_secondary', '#1a1a2e', 'color', 'Secondary Background'],
        ['colors', 'background_card', 'rgba(255,255,255,0.04)', 'color', 'Card Background'],
        ['colors', 'text', '#ffffff', 'color', 'Text Color'],
        ['colors', 'text_secondary', '#cccccc', 'color', 'Secondary Text'],
        ['colors', 'text_muted', '#888888', 'color', 'Muted Text'],
        ['colors', 'success', '#00ff88', 'color', 'Success Color'],
        ['colors', 'warning', '#ffbb00', 'color', 'Warning Color'],
        ['colors', 'error', '#ff5050', 'color', 'Error Color'],
        ['colors', 'info', '#00d9ff', 'color', 'Info Color'],
        ['colors', 'border', 'rgba(255,255,255,0.08)', 'color', 'Border Color'],
        ['colors', 'border_light', 'rgba(255,255,255,0.15)', 'color', 'Light Border'],
        
        // Gradients
        ['gradients', 'primary', 'linear-gradient(90deg, #00d9ff, #00ff88)', 'gradient', 'Primary Gradient'],
        ['gradients', 'background', 'linear-gradient(135deg, #0f0f1a, #1a1a2e)', 'gradient', 'Background Gradient'],
        ['gradients', 'card_hover', 'linear-gradient(135deg, rgba(0,217,255,0.1), rgba(0,255,136,0.1))', 'gradient', 'Card Hover Gradient'],
        
        // Typography
        ['typography', 'font_family', '-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif', 'font', 'Font Family'],
        ['typography', 'heading_font', 'inherit', 'font', 'Heading Font'],
        ['typography', 'monospace_font', '\"SF Mono\", \"Fira Code\", \"Consolas\", monospace', 'font', 'Monospace Font'],
        ['typography', 'font_size_base', '16px', 'size', 'Base Font Size'],
        ['typography', 'font_size_small', '14px', 'size', 'Small Font Size'],
        ['typography', 'font_size_large', '18px', 'size', 'Large Font Size'],
        ['typography', 'font_size_h1', '2.5rem', 'size', 'H1 Size'],
        ['typography', 'font_size_h2', '2rem', 'size', 'H2 Size'],
        ['typography', 'font_size_h3', '1.5rem', 'size', 'H3 Size'],
        ['typography', 'font_size_h4', '1.25rem', 'size', 'H4 Size'],
        ['typography', 'line_height', '1.5', 'number', 'Line Height'],
        ['typography', 'heading_line_height', '1.2', 'number', 'Heading Line Height'],
        ['typography', 'font_weight_normal', '400', 'number', 'Normal Weight'],
        ['typography', 'font_weight_medium', '500', 'number', 'Medium Weight'],
        ['typography', 'font_weight_bold', '700', 'number', 'Bold Weight'],
        
        // Buttons
        ['buttons', 'border_radius', '8px', 'size', 'Border Radius'],
        ['buttons', 'border_radius_small', '4px', 'size', 'Small Border Radius'],
        ['buttons', 'border_radius_large', '12px', 'size', 'Large Border Radius'],
        ['buttons', 'border_radius_round', '50px', 'size', 'Round Border Radius'],
        ['buttons', 'padding', '10px 20px', 'text', 'Button Padding'],
        ['buttons', 'padding_small', '6px 12px', 'text', 'Small Button Padding'],
        ['buttons', 'padding_large', '14px 28px', 'text', 'Large Button Padding'],
        ['buttons', 'font_weight', '600', 'number', 'Button Font Weight'],
        ['buttons', 'transition', '0.2s ease', 'text', 'Button Transition'],
        ['buttons', 'hover_transform', 'translateY(-2px)', 'text', 'Hover Transform'],
        ['buttons', 'primary_bg', 'linear-gradient(90deg, #00d9ff, #00ff88)', 'gradient', 'Primary Button BG'],
        ['buttons', 'primary_text', '#0f0f1a', 'color', 'Primary Button Text'],
        ['buttons', 'secondary_bg', 'rgba(255,255,255,0.08)', 'color', 'Secondary Button BG'],
        ['buttons', 'secondary_text', '#ffffff', 'color', 'Secondary Button Text'],
        ['buttons', 'secondary_border', 'rgba(255,255,255,0.15)', 'color', 'Secondary Button Border'],
        ['buttons', 'danger_bg', 'rgba(255,80,80,0.15)', 'color', 'Danger Button BG'],
        ['buttons', 'danger_text', '#ff5050', 'color', 'Danger Button Text'],
        ['buttons', 'danger_border', 'rgba(255,80,80,0.4)', 'color', 'Danger Button Border'],
        
        // Layout
        ['layout', 'max_width', '1200px', 'size', 'Max Width'],
        ['layout', 'container_padding', '20px', 'size', 'Container Padding'],
        ['layout', 'sidebar_width', '250px', 'size', 'Sidebar Width'],
        ['layout', 'sidebar_collapsed_width', '60px', 'size', 'Collapsed Sidebar Width'],
        ['layout', 'header_height', '60px', 'size', 'Header Height'],
        ['layout', 'spacing_unit', '8px', 'size', 'Spacing Unit'],
        ['layout', 'spacing_xs', '4px', 'size', 'XS Spacing'],
        ['layout', 'spacing_sm', '8px', 'size', 'SM Spacing'],
        ['layout', 'spacing_md', '16px', 'size', 'MD Spacing'],
        ['layout', 'spacing_lg', '24px', 'size', 'LG Spacing'],
        ['layout', 'spacing_xl', '32px', 'size', 'XL Spacing'],
        
        // Cards
        ['cards', 'background', 'rgba(255,255,255,0.04)', 'color', 'Card Background'],
        ['cards', 'border', '1px solid rgba(255,255,255,0.08)', 'text', 'Card Border'],
        ['cards', 'border_radius', '14px', 'size', 'Card Border Radius'],
        ['cards', 'padding', '18px', 'size', 'Card Padding'],
        ['cards', 'shadow', '0 4px 6px rgba(0,0,0,0.1)', 'text', 'Card Shadow'],
        ['cards', 'hover_border', '#00d9ff', 'color', 'Card Hover Border'],
        ['cards', 'hover_background', 'rgba(255,255,255,0.07)', 'color', 'Card Hover BG'],
        
        // Forms
        ['forms', 'input_bg', 'rgba(255,255,255,0.05)', 'color', 'Input Background'],
        ['forms', 'input_border', 'rgba(255,255,255,0.1)', 'color', 'Input Border'],
        ['forms', 'input_border_focus', '#00d9ff', 'color', 'Input Focus Border'],
        ['forms', 'input_text', '#ffffff', 'color', 'Input Text'],
        ['forms', 'input_placeholder', '#666666', 'color', 'Input Placeholder'],
        ['forms', 'input_padding', '12px 16px', 'text', 'Input Padding'],
        ['forms', 'input_border_radius', '8px', 'size', 'Input Border Radius'],
        ['forms', 'label_color', '#cccccc', 'color', 'Label Color'],
        ['forms', 'label_font_size', '14px', 'size', 'Label Font Size'],
        
        // Badges
        ['badges', 'success_bg', 'rgba(0,255,136,0.15)', 'color', 'Success Badge BG'],
        ['badges', 'success_text', '#00ff88', 'color', 'Success Badge Text'],
        ['badges', 'success_border', '#00ff88', 'color', 'Success Badge Border'],
        ['badges', 'warning_bg', 'rgba(255,187,0,0.15)', 'color', 'Warning Badge BG'],
        ['badges', 'warning_text', '#ffbb00', 'color', 'Warning Badge Text'],
        ['badges', 'error_bg', 'rgba(255,100,100,0.15)', 'color', 'Error Badge BG'],
        ['badges', 'error_text', '#ff6464', 'color', 'Error Badge Text'],
        ['badges', 'error_border', '#ff6464', 'color', 'Error Badge Border'],
        ['badges', 'info_bg', 'rgba(0,217,255,0.15)', 'color', 'Info Badge BG'],
        ['badges', 'info_text', '#00d9ff', 'color', 'Info Badge Text'],
        ['badges', 'padding', '6px 14px', 'text', 'Badge Padding'],
        ['badges', 'border_radius', '20px', 'size', 'Badge Border Radius'],
        
        // Tables
        ['tables', 'header_bg', 'rgba(255,255,255,0.05)', 'color', 'Table Header BG'],
        ['tables', 'row_hover', 'rgba(255,255,255,0.03)', 'color', 'Row Hover BG'],
        ['tables', 'border', 'rgba(255,255,255,0.08)', 'color', 'Table Border'],
        ['tables', 'stripe_bg', 'rgba(255,255,255,0.02)', 'color', 'Stripe Background'],
        
        // Animations
        ['animations', 'transition_fast', '0.15s ease', 'text', 'Fast Transition'],
        ['animations', 'transition_normal', '0.2s ease', 'text', 'Normal Transition'],
        ['animations', 'transition_slow', '0.3s ease', 'text', 'Slow Transition'],
        ['animations', 'hover_scale', '1.02', 'number', 'Hover Scale'],
        ['animations', 'active_scale', '0.98', 'number', 'Active Scale'],
    ];
    
    $stmt = $pdo_themes->prepare("
        INSERT INTO theme_settings (theme_id, setting_category, setting_key, setting_value, setting_type, setting_label)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $order = 0;
    foreach ($theme_settings as $setting) {
        $stmt->execute([$theme_id, $setting[0], $setting[1], $setting[2], $setting[3], $setting[4]]);
        $order++;
    }
    
    echo "✓ Inserted default theme with " . count($theme_settings) . " settings (ALL STYLING IS DATABASE-DRIVEN)\n";
}

// Insert default regions
if ($pdo_identities) {
    $pdo_identities->exec("
        INSERT INTO regions (region_code, region_name, country_code, timezone, locale, is_available) VALUES
        ('us-east', 'United States (East)', 'US', 'America/New_York', 'en-US', 1),
        ('us-central', 'United States (Central)', 'US', 'America/Chicago', 'en-US', 1),
        ('us-west', 'United States (West)', 'US', 'America/Los_Angeles', 'en-US', 1),
        ('ca-central', 'Canada', 'CA', 'America/Toronto', 'en-CA', 1),
        ('uk', 'United Kingdom', 'GB', 'Europe/London', 'en-GB', 1),
        ('eu-west', 'Europe (West)', 'NL', 'Europe/Amsterdam', 'en-EU', 1),
        ('eu-central', 'Europe (Central)', 'DE', 'Europe/Berlin', 'de-DE', 1),
        ('au', 'Australia', 'AU', 'Australia/Sydney', 'en-AU', 1),
        ('jp', 'Japan', 'JP', 'Asia/Tokyo', 'ja-JP', 1),
        ('sg', 'Singapore', 'SG', 'Asia/Singapore', 'en-SG', 1);
    ");
    echo "✓ Inserted 10 regional identity options\n";
}

// Insert default routing patterns
if ($pdo_routing) {
    $pdo_routing->exec("
        INSERT INTO routing_patterns (pattern_name, pattern_type, pattern_category, patterns, recommended_action, description, is_system) VALUES
        ('Banking Apps', 'app', 'finance', '{\"apps\":[\"chase\",\"wellsfargo\",\"bankofamerica\",\"capitalone\"]}', 'identity', 'Route banking through regional identity', 1),
        ('Streaming Services', 'domain', 'entertainment', '{\"domains\":[\"netflix.com\",\"hulu.com\",\"disneyplus.com\",\"hbomax.com\"]}', 'vpn', 'Route streaming through VPN for geo-unlock', 1),
        ('Gaming Platforms', 'domain', 'gaming', '{\"domains\":[\"steampowered.com\",\"epicgames.com\",\"xbox.com\",\"playstation.com\"]}', 'direct', 'Direct connection for lowest latency', 1),
        ('Social Media', 'domain', 'social', '{\"domains\":[\"facebook.com\",\"twitter.com\",\"instagram.com\",\"tiktok.com\"]}', 'vpn', 'VPN for privacy', 1),
        ('Work Apps', 'app', 'productivity', '{\"apps\":[\"slack\",\"zoom\",\"teams\",\"outlook\"]}', 'vpn', 'VPN for security', 1);
    ");
    echo "✓ Inserted 5 default routing patterns\n";
}

// Insert default workflows
if ($pdo_workflows) {
    $pdo_workflows->exec("
        INSERT INTO workflows (workflow_name, workflow_slug, description, trigger_type, trigger_config, is_active, is_system) VALUES
        ('New User Signup', 'new_user_signup', 'Triggered when a new user registers', 'event', '{\"event\":\"user.registered\"}', 1, 1),
        ('Scanner Device Sync', 'scanner_sync', 'Triggered when scanner syncs devices', 'event', '{\"event\":\"scanner.synced\"}', 1, 1),
        ('Payment Success', 'payment_success', 'Triggered when payment completes', 'event', '{\"event\":\"payment.completed\"}', 1, 1),
        ('Payment Failed', 'payment_failed', 'Triggered when payment fails', 'event', '{\"event\":\"payment.failed\"}', 1, 1),
        ('Certificate Generation', 'certificate_generation', 'Triggered when certificate is requested', 'event', '{\"event\":\"certificate.requested\"}', 1, 1),
        ('Server Health Check', 'server_health_check', 'Runs every 5 minutes', 'schedule', '{\"cron\":\"*/5 * * * *\"}', 1, 1),
        ('Subscription Expiring', 'subscription_expiring', 'Triggered when subscription is about to expire', 'event', '{\"event\":\"subscription.expiring\"}', 1, 1),
        ('VPN Connection', 'vpn_connection', 'Triggered when user connects to VPN', 'event', '{\"event\":\"vpn.connected\"}', 1, 1);
    ");
    
    // Insert workflow steps for new_user_signup
    $pdo_workflows->exec("
        INSERT INTO workflow_steps (workflow_id, step_order, step_name, action_type, action_config, delay_seconds) VALUES
        (1, 1, 'Send Welcome Email', 'email', '{\"template\":\"welcome\",\"to\":\"{{user.email}}\"}', 0),
        (1, 2, 'Generate Personal CA', 'api_call', '{\"endpoint\":\"/api/certificates/ca\",\"method\":\"POST\"}', 5),
        (1, 3, 'Create Default Identities', 'api_call', '{\"endpoint\":\"/api/identities/create-defaults\",\"method\":\"POST\"}', 0),
        (1, 4, 'Send Scanner Download Link', 'email', '{\"template\":\"scanner_ready\",\"to\":\"{{user.email}}\"}', 60),
        (1, 5, 'Schedule Follow-up', 'wait', '{\"duration\":86400}', 86400),
        (1, 6, 'Send Follow-up Email', 'email', '{\"template\":\"follow_up\",\"to\":\"{{user.email}}\"}', 0);
    ");
    
    echo "✓ Inserted 8 automation workflows with steps\n";
}

// Insert email templates
if ($pdo_templates) {
    $pdo_templates->exec("
        INSERT INTO email_templates (template_name, template_slug, subject, body_html, variables, category, is_active) VALUES
        ('Welcome Email', 'welcome', 'Welcome to TrueVault VPN!', '<h1>Welcome {{first_name}}!</h1><p>Your account has been created. Get started by downloading our apps.</p>', '{\"first_name\":\"string\",\"email\":\"string\"}', 'onboarding', 1),
        ('Invoice', 'invoice', 'Your TrueVault Invoice #{{invoice_number}}', '<h1>Invoice #{{invoice_number}}</h1><p>Amount: \${{amount}}</p>', '{\"invoice_number\":\"string\",\"amount\":\"number\"}', 'billing', 1),
        ('Payment Failed Day 0', 'payment_failed_day0', 'Payment Issue with Your TrueVault Account', '<h1>Hi {{first_name}}</h1><p>We had trouble processing your payment.</p>', '{\"first_name\":\"string\"}', 'billing', 1),
        ('Payment Failed Day 3', 'payment_failed_day3', 'Urgent: Update Your Payment Method', '<h1>Action Required</h1><p>Your service may be interrupted.</p>', '{\"first_name\":\"string\"}', 'billing', 1),
        ('Payment Failed Day 7', 'payment_failed_day7', 'Final Notice: Service Suspension Imminent', '<h1>Final Notice</h1><p>Your account will be suspended tomorrow.</p>', '{\"first_name\":\"string\"}', 'billing', 1),
        ('Scanner Ready', 'scanner_ready', 'Your Network Scanner is Ready', '<h1>Discover Your Devices</h1><p>Download the scanner to find cameras and IoT devices.</p>', '{\"first_name\":\"string\",\"download_link\":\"string\"}', 'onboarding', 1),
        ('Certificate Ready', 'certificate_ready', 'Your Certificate is Ready', '<h1>Certificate Generated</h1><p>Download your new certificate from the dashboard.</p>', '{\"first_name\":\"string\",\"cert_type\":\"string\"}', 'certificates', 1),
        ('Subscription Reminder 7 Day', 'subscription_reminder_7', 'Your Subscription Expires in 7 Days', '<h1>Renew Soon</h1><p>Keep your privacy protection active.</p>', '{\"first_name\":\"string\",\"expiry_date\":\"string\"}', 'billing', 1),
        ('Subscription Reminder 3 Day', 'subscription_reminder_3', 'Your Subscription Expires in 3 Days', '<h1>Almost Time</h1><p>Renew now to avoid interruption.</p>', '{\"first_name\":\"string\"}', 'billing', 1),
        ('Subscription Reminder 1 Day', 'subscription_reminder_1', 'Your Subscription Expires Tomorrow', '<h1>Last Chance</h1><p>Renew today to keep your protection.</p>', '{\"first_name\":\"string\"}', 'billing', 1);
    ");
    echo "✓ Inserted 10 email templates\n";
}

// Insert default landing page
if ($pdo_pages) {
    $pdo_pages->exec("
        INSERT INTO pages (slug, title, meta_description, status, template) VALUES
        ('home', 'TrueVault VPN - Your Complete Digital Fortress', 'Advanced VPN with personal certificates, mesh networking, and camera management', 'published', 'landing'),
        ('pricing', 'Pricing - TrueVault VPN', 'Choose your plan: Personal, Family, or Business', 'published', 'landing'),
        ('features', 'Features - TrueVault VPN', 'Smart Identity Router, Mesh Network, Personal Certificates, and more', 'published', 'landing'),
        ('download', 'Download - TrueVault VPN', 'Download TrueVault VPN for Windows, Mac, iOS, and Android', 'published', 'landing');
    ");
    echo "✓ Inserted 4 default pages\n";
}

// ============================================
// SUMMARY
// ============================================

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    SETUP COMPLETE!                         ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n";
echo "║  Created 21 SQLite databases with all schemas              ║\n";
echo "║  Inserted all default data                                 ║\n";
echo "║                                                            ║\n";
echo "║  CRITICAL: All styling is now database-driven!             ║\n";
echo "║  Edit themes in cms/themes.db - NO HARDCODING!             ║\n";
echo "║                                                            ║\n";
echo "║  Default Admin: kahlen@truthvault.com / admin123           ║\n";
echo "║  VIP Server: 144.126.133.253 (seige235@yahoo.com only)     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

?>
