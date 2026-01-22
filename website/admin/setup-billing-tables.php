<?php
/**
 * Add missing tables for Part 5 - Billing system
 */
define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

header('Content-Type: application/json');

try {
    $logsDb = Database::getInstance('logs');
    
    // Add webhook_log table
    $logsDb->exec("
        CREATE TABLE IF NOT EXISTS webhook_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            source TEXT NOT NULL DEFAULT 'paypal',
            event_type TEXT NOT NULL,
            payload TEXT,
            headers TEXT,
            processed INTEGER DEFAULT 0,
            processed_at TEXT,
            error TEXT,
            created_at TEXT DEFAULT (datetime('now'))
        )
    ");
    
    // Add missing columns to billing tables if needed
    $billingDb = Database::getInstance('billing');
    
    // Add refund_amount column to invoices if missing
    try {
        $billingDb->exec("ALTER TABLE invoices ADD COLUMN refund_amount TEXT");
    } catch (Exception $e) {
        // Column might already exist
    }
    
    try {
        $billingDb->exec("ALTER TABLE invoices ADD COLUMN refunded_at TEXT");
    } catch (Exception $e) {
        // Column might already exist
    }
    
    // Add PayPal plan IDs to system_settings if not exist
    $adminDb = Database::getInstance('admin');
    
    $settings = [
        ['paypal_plan_standard', 'P-XXXXXXXXXXXXXXXXX', 'PayPal Plan ID for Standard tier'],
        ['paypal_plan_pro', 'P-XXXXXXXXXXXXXXXXX', 'PayPal Plan ID for Pro tier'],
        ['paypal_webhook_id', '46924926WL757580D', 'PayPal Webhook ID'],
        ['price_standard', '9.97', 'Monthly price for Standard plan'],
        ['price_pro', '14.97', 'Monthly price for Pro/Family plan'],
        ['price_business', '39.97', 'Monthly price for Business plan']
    ];
    
    foreach ($settings as $setting) {
        $stmt = $adminDb->prepare("INSERT OR IGNORE INTO system_settings (setting_key, setting_value, description) VALUES (:key, :value, :desc)");
        $stmt->bindValue(':key', $setting[0], SQLITE3_TEXT);
        $stmt->bindValue(':value', $setting[1], SQLITE3_TEXT);
        $stmt->bindValue(':desc', $setting[2], SQLITE3_TEXT);
        $stmt->execute();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Part 5 billing tables updated',
        'tables_created' => ['webhook_log'],
        'settings_added' => count($settings)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
