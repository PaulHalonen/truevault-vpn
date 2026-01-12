<?php
/**
 * TrueVault VPN - Billing Database Setup
 * Creates all billing-related tables
 */

require_once __DIR__ . '/../config/database.php';

echo "<h1>TrueVault Billing Database Setup</h1><pre>\n";

try {
    $db = Database::getConnection('billing');
    
    // Subscriptions table
    $db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        plan_type TEXT NOT NULL,
        status TEXT DEFAULT 'active',
        payment_id TEXT,
        max_devices INTEGER DEFAULT 3,
        max_cameras INTEGER DEFAULT 1,
        start_date DATETIME,
        end_date DATETIME,
        cancelled_at DATETIME,
        cancel_reason TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME
    )");
    echo "✓ Created subscriptions table\n";
    
    // Invoices table
    $db->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        invoice_number TEXT UNIQUE NOT NULL,
        plan_id TEXT,
        amount REAL NOT NULL,
        payment_id TEXT,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Created invoices table\n";
    
    // Pending orders table
    $db->exec("CREATE TABLE IF NOT EXISTS pending_orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        order_id TEXT UNIQUE NOT NULL,
        plan_id TEXT NOT NULL,
        amount REAL NOT NULL,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME
    )");
    echo "✓ Created pending_orders table\n";
    
    // Webhook log table
    $db->exec("CREATE TABLE IF NOT EXISTS webhook_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        webhook_id TEXT,
        event_type TEXT,
        payload TEXT,
        processed INTEGER DEFAULT 0,
        processed_at DATETIME,
        error TEXT,
        received_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Created webhook_log table\n";
    
    // Payment failures table
    $db->exec("CREATE TABLE IF NOT EXISTS payment_failures (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        failure_date DATETIME,
        grace_end_date DATETIME,
        notified INTEGER DEFAULT 0,
        resolved INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Created payment_failures table\n";
    
    // Scheduled revocations table
    $db->exec("CREATE TABLE IF NOT EXISTS scheduled_revocations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL UNIQUE,
        revoke_at DATETIME NOT NULL,
        status TEXT DEFAULT 'pending',
        completed_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Created scheduled_revocations table\n";
    
    // Disputes table
    $db->exec("CREATE TABLE IF NOT EXISTS disputes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        dispute_id TEXT,
        reason TEXT,
        status TEXT DEFAULT 'open',
        payload TEXT,
        resolved_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Created disputes table\n";
    
    // Create indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_subscriptions_user ON subscriptions(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_invoices_user ON invoices(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_pending_orders_order ON pending_orders(order_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_revocations_date ON scheduled_revocations(revoke_at)");
    echo "✓ Created indexes\n";
    
    echo "\n========================================\n";
    echo "BILLING DATABASE SETUP COMPLETE!\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
