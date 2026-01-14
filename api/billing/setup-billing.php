<?php
/**
 * TrueVault VPN - Billing Database Setup
 * Creates all billing-related tables
 */

require_once __DIR__ . '/../config/database.php';

echo "<pre>";
echo "=== TrueVault Billing Database Setup ===\n\n";

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
        expiry_warned INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Created subscriptions table\n";
    
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
    
    // Invoices table
    $db->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        invoice_number TEXT UNIQUE NOT NULL,
        plan_id TEXT,
        amount REAL NOT NULL,
        tax REAL DEFAULT 0,
        total REAL NOT NULL,
        payment_id TEXT,
        status TEXT DEFAULT 'pending',
        due_date DATETIME,
        paid_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Created invoices table\n";
    
    // Payments table
    $db->exec("CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        invoice_id INTEGER,
        payment_method TEXT,
        amount REAL NOT NULL,
        currency TEXT DEFAULT 'USD',
        paypal_order_id TEXT,
        paypal_capture_id TEXT,
        status TEXT DEFAULT 'completed',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Created payments table\n";
    
    // Payment events table (webhook log)
    $db->exec("CREATE TABLE IF NOT EXISTS payment_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_type TEXT NOT NULL,
        event_id TEXT,
        resource_id TEXT,
        payload TEXT,
        processed INTEGER DEFAULT 0,
        processed_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Created payment_events table\n";
    
    // Payment failures table
    $db->exec("CREATE TABLE IF NOT EXISTS payment_failures (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        failure_date DATETIME NOT NULL,
        grace_end_date DATETIME NOT NULL,
        retry_count INTEGER DEFAULT 0,
        last_retry DATETIME,
        notified INTEGER DEFAULT 0,
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
    
    // Create indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_subscriptions_user ON subscriptions(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_pending_orders_order ON pending_orders(order_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_invoices_user ON invoices(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_payments_user ON payments(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_revocations_date ON scheduled_revocations(revoke_at)");
    echo "✓ Created indexes\n";
    
    echo "\n=== Billing Database Setup Complete ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
