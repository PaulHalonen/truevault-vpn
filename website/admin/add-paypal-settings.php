<?php
/**
 * Add PayPal settings to system_settings
 * Uses credentials from project docs
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

$adminDb = Database::getInstance('admin');

// First, let's see what columns exist
$result = $adminDb->query("PRAGMA table_info(system_settings)");
$columns = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}

// PayPal credentials from project docs
$settings = [
    ['paypal_client_id', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk'],
    ['paypal_secret', 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN'],
    ['paypal_mode', 'live'],
    ['paypal_webhook_id', '46924926WL757580D'],
    ['paypal_verify_webhooks', 'true'],
    ['paypal_plan_standard', ''],
    ['paypal_plan_pro', ''],
];

$added = 0;
$updated = 0;

foreach ($settings as $setting) {
    // Check if exists
    $stmt = $adminDb->prepare("SELECT id FROM system_settings WHERE setting_key = :key");
    $stmt->bindValue(':key', $setting[0], SQLITE3_TEXT);
    $result = $stmt->execute();
    $exists = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($exists) {
        // Update
        $stmt = $adminDb->prepare("UPDATE system_settings SET setting_value = :val WHERE setting_key = :key");
        $stmt->bindValue(':key', $setting[0], SQLITE3_TEXT);
        $stmt->bindValue(':val', $setting[1], SQLITE3_TEXT);
        $stmt->execute();
        $updated++;
    } else {
        // Insert with required columns
        $stmt = $adminDb->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES (:key, :val, 'string')");
        $stmt->bindValue(':key', $setting[0], SQLITE3_TEXT);
        $stmt->bindValue(':val', $setting[1], SQLITE3_TEXT);
        $stmt->execute();
        $added++;
    }
}

echo json_encode([
    'success' => true, 
    'message' => "PayPal settings configured",
    'added' => $added,
    'updated' => $updated,
    'columns' => $columns
]);
