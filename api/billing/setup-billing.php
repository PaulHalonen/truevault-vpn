<?php
/**
 * TrueVault VPN - Billing Database Setup
 * Run once to create billing tables
 * 
 * URL: /api/billing/setup-billing.php
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$results = [];

try {
    // ==================== BILLING DATABASE ====================
    
    // Subscriptions table
    Database::execute('billing', "CREATE TABLE IF NOT EXISTS subscriptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        plan_type TEXT NOT NULL DEFAULT 'basic',
        status TEXT NOT NULL DEFAULT 'active',
        payment_id TEXT,
        paypal_subscription_id TEXT,
        max_devices INTEGER DEFAULT 3,
        max_cameras INTEGER DEFAULT 1,
        start_date TEXT,
        end_date TEXT,
        cancelled_at TEXT,
        cancel_reason TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT
    )");
    $results[] = "✓ Created subscriptions table";
    
    // Pending orders table
    Database::execute('billing', "CREATE TABLE IF NOT EXISTS pending_orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        order_id TEXT NOT NULL UNIQUE,
        plan_id TEXT NOT NULL,
        amount REAL NOT NULL,
        status TEXT DEFAULT 'pending',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        completed_at TEXT
    )");
    $results[] = "✓ Created pending_orders table";
    
    // Invoices table
    Database::execute('billing', "CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        invoice_number TEXT NOT NULL UNIQUE,
        plan_id TEXT,
        amount REAL NOT NULL,
        payment_id TEXT,
        status TEXT DEFAULT 'pending',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    $results[] = "✓ Created invoices table";
    
    // Scheduled revocations table
    Database::execute('billing', "CREATE TABLE IF NOT EXISTS scheduled_revocations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL UNIQUE,
        revoke_at TEXT NOT NULL,
        status TEXT DEFAULT 'pending',
        completed_at TEXT
    )");
    $results[] = "✓ Created scheduled_revocations table";
    
    // Payment failures table
    Database::execute('billing', "CREATE TABLE IF NOT EXISTS payment_failures (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        failure_date TEXT NOT NULL,
        grace_end_date TEXT,
        notified INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    $results[] = "✓ Created payment_failures table";
    
    // Disputes table
    Database::execute('billing', "CREATE TABLE IF NOT EXISTS disputes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        dispute_id TEXT NOT NULL UNIQUE,
        user_id INTEGER,
        amount REAL,
        status TEXT DEFAULT 'open',
        outcome TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        resolved_at TEXT
    )");
    $results[] = "✓ Created disputes table";
    
    // ==================== VPN DATABASE ====================
    
    // User peers table (tracks which servers user has access to)
    Database::execute('vpn', "CREATE TABLE IF NOT EXISTS user_peers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        server_id INTEGER NOT NULL,
        public_key TEXT NOT NULL,
        assigned_ip TEXT,
        status TEXT DEFAULT 'active',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        revoked_at TEXT,
        UNIQUE(user_id, server_id)
    )");
    $results[] = "✓ Created user_peers table";
    
    // VPN connections log
    Database::execute('vpn', "CREATE TABLE IF NOT EXISTS vpn_connections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        server_id INTEGER NOT NULL,
        status TEXT DEFAULT 'pending',
        assigned_ip TEXT,
        connected_at TEXT DEFAULT CURRENT_TIMESTAMP,
        disconnected_at TEXT,
        bytes_sent INTEGER DEFAULT 0,
        bytes_received INTEGER DEFAULT 0
    )");
    $results[] = "✓ Created vpn_connections table";
    
    // ==================== LOGS DATABASE ====================
    
    // Webhook log
    Database::execute('logs', "CREATE TABLE IF NOT EXISTS webhook_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        source TEXT NOT NULL,
        headers TEXT,
        payload TEXT,
        received_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    $results[] = "✓ Created webhook_log table";
    
    // ==================== CERTIFICATES DATABASE ====================
    
    // User certificates table
    Database::execute('certificates', "CREATE TABLE IF NOT EXISTS user_certificates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT DEFAULT 'WireGuard Key',
        type TEXT DEFAULT 'wireguard',
        public_key TEXT NOT NULL,
        private_key TEXT NOT NULL,
        status TEXT DEFAULT 'active',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        revoked_at TEXT
    )");
    $results[] = "✓ Created user_certificates table";
    
    // Create indexes
    Database::execute('billing', "CREATE INDEX IF NOT EXISTS idx_subscriptions_user ON subscriptions(user_id)");
    Database::execute('billing', "CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status)");
    Database::execute('billing', "CREATE INDEX IF NOT EXISTS idx_invoices_user ON invoices(user_id)");
    Database::execute('vpn', "CREATE INDEX IF NOT EXISTS idx_peers_user ON user_peers(user_id)");
    Database::execute('vpn', "CREATE INDEX IF NOT EXISTS idx_peers_status ON user_peers(status)");
    $results[] = "✓ Created indexes";
    
    echo json_encode([
        'success' => true,
        'message' => 'Billing database setup complete',
        'results' => $results
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'results' => $results
    ], JSON_PRETTY_PRINT);
}
