<?php
/**
 * TrueVault VPN - PayPal Webhook Handler
 * 
 * Receives and processes PayPal webhook events
 * Webhook URL: https://vpn.the-truth-publishing.com/api/billing/webhook.php
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

// Always respond 200 to PayPal quickly
http_response_code(200);

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/PayPal.php';

// Get raw POST data
$rawBody = file_get_contents('php://input');

// Log webhook for debugging
try {
    $logsDb = Database::getInstance('logs');
    $logsDb->insert('webhook_logs', [
        'source' => 'paypal',
        'event_type' => 'incoming',
        'payload' => $rawBody,
        'processed' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    // Continue even if logging fails
}

// Parse the webhook
$event = json_decode($rawBody, true);

if (!$event) {
    exit;
}

$eventType = $event['event_type'] ?? '';
$resource = $event['resource'] ?? [];

try {
    // Process the webhook
    $result = PayPal::processWebhook($eventType, $resource);
    
    // Update log with result
    $logsDb->query(
        "UPDATE webhook_logs SET event_type = ?, processed = 1, updated_at = ? WHERE id = (SELECT MAX(id) FROM webhook_logs WHERE source = 'paypal')",
        [$eventType, date('Y-m-d H:i:s')]
    );
    
    // Log activity
    $logsDb->insert('activity_logs', [
        'action' => 'paypal_webhook',
        'entity_type' => 'webhook',
        'details' => json_encode(['event' => $eventType, 'result' => $result]),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Log error
    try {
        $logsDb->insert('error_logs', [
            'level' => 'error',
            'message' => 'PayPal webhook error: ' . $e->getMessage(),
            'context' => json_encode(['event' => $eventType]),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $logError) {
        // Silently fail
    }
}

exit;
