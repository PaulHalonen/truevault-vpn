<?php
/**
 * TrueVault VPN - Automation Processor Cron Job
 * Task 7 - Process scheduled workflow steps and email queue
 * 
 * CRON SETUP:
 * Run every 5 minutes: */5 * * * * /usr/bin/php /path/to/cron/process-automation.php
 */

// Prevent web access
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    http_response_code(403);
    die('Access denied');
}

// Security key for web-based cron (optional)
$cronKey = $_GET['cron_key'] ?? '';
if (php_sapi_name() !== 'cli' && $cronKey !== 'TV_CRON_2026_SECRET') {
    http_response_code(403);
    die('Invalid cron key');
}

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Email.php';
require_once __DIR__ . '/../includes/AutomationEngine.php';

// Logging function
function cronLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/../logs/cron.log';
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    
    // Also echo for CLI
    if (php_sapi_name() === 'cli') {
        echo "[$timestamp] $message\n";
    }
}

cronLog('=== Automation Processor Started ===');

try {
    // 1. Process Scheduled Workflow Steps
    cronLog('Processing scheduled workflow steps...');
    
    $db = new SQLite3(DB_LOGS);
    $db->enableExceptions(true);
    
    // Get pending scheduled steps that are due
    $stmt = $db->prepare("
        SELECT s.*, w.workflow_name 
        FROM scheduled_workflow_steps s
        JOIN workflow_executions w ON s.execution_id = w.id
        WHERE s.status = 'pending' 
        AND s.execute_at <= datetime('now')
        ORDER BY s.execute_at ASC
        LIMIT 50
    ");
    $result = $stmt->execute();
    
    $stepsProcessed = 0;
    while ($step = $result->fetchArray(SQLITE3_ASSOC)) {
        cronLog("Processing step: {$step['step_name']} (Execution ID: {$step['execution_id']})");
        
        try {
            // Mark as processing
            $updateStmt = $db->prepare("UPDATE scheduled_workflow_steps SET status = 'processing' WHERE id = :id");
            $updateStmt->bindValue(':id', $step['id'], SQLITE3_INTEGER);
            $updateStmt->execute();
            
            // Execute the step
            $stepData = json_decode($step['step_data'], true) ?: [];
            $stepResult = AutomationEngine::executeStep($step['step_name'], $stepData, $step['execution_id']);
            
            // Mark as completed
            $completeStmt = $db->prepare("
                UPDATE scheduled_workflow_steps 
                SET status = 'completed', executed_at = datetime('now'), result = :result 
                WHERE id = :id
            ");
            $completeStmt->bindValue(':id', $step['id'], SQLITE3_INTEGER);
            $completeStmt->bindValue(':result', json_encode($stepResult), SQLITE3_TEXT);
            $completeStmt->execute();
            
            $stepsProcessed++;
            cronLog("  ✓ Step completed successfully");
            
        } catch (Exception $e) {
            // Mark as failed
            $failStmt = $db->prepare("
                UPDATE scheduled_workflow_steps 
                SET status = 'failed', error_message = :error 
                WHERE id = :id
            ");
            $failStmt->bindValue(':id', $step['id'], SQLITE3_INTEGER);
            $failStmt->bindValue(':error', $e->getMessage(), SQLITE3_TEXT);
            $failStmt->execute();
            
            cronLog("  ✗ Step failed: " . $e->getMessage());
        }
    }
    
    cronLog("Workflow steps processed: $stepsProcessed");
    
    // 2. Process Email Queue
    cronLog('Processing email queue...');
    
    $emailStmt = $db->prepare("
        SELECT * FROM email_queue 
        WHERE status = 'pending' 
        AND scheduled_for <= datetime('now')
        AND attempts < 3
        ORDER BY priority ASC, created_at ASC
        LIMIT 20
    ");
    $emailResult = $emailStmt->execute();
    
    $emailsSent = 0;
    $emailsFailed = 0;
    
    while ($email = $emailResult->fetchArray(SQLITE3_ASSOC)) {
        cronLog("Sending email to: {$email['recipient']} - {$email['subject']}");
        
        try {
            // Increment attempts
            $attemptStmt = $db->prepare("UPDATE email_queue SET attempts = attempts + 1 WHERE id = :id");
            $attemptStmt->bindValue(':id', $email['id'], SQLITE3_INTEGER);
            $attemptStmt->execute();
            
            // Parse template variables
            $variables = json_decode($email['template_variables'], true) ?: [];
            
            // Send email
            $result = Email::sendTemplate(
                $email['recipient'],
                $email['template_name'],
                $variables,
                $email['email_type'] === 'admin' ? 'gmail' : 'smtp'
            );
            
            if ($result['success']) {
                // Mark as sent
                $sentStmt = $db->prepare("UPDATE email_queue SET status = 'sent', sent_at = datetime('now') WHERE id = :id");
                $sentStmt->bindValue(':id', $email['id'], SQLITE3_INTEGER);
                $sentStmt->execute();
                
                $emailsSent++;
                cronLog("  ✓ Email sent successfully");
            } else {
                throw new Exception($result['error'] ?? 'Unknown error');
            }
            
        } catch (Exception $e) {
            $attempts = $email['attempts'] + 1;
            
            if ($attempts >= 3) {
                // Max attempts reached, mark as failed
                $failStmt = $db->prepare("UPDATE email_queue SET status = 'failed', last_error = :error WHERE id = :id");
                $failStmt->bindValue(':id', $email['id'], SQLITE3_INTEGER);
                $failStmt->bindValue(':error', $e->getMessage(), SQLITE3_TEXT);
                $failStmt->execute();
            } else {
                // Update error for retry
                $errorStmt = $db->prepare("UPDATE email_queue SET last_error = :error WHERE id = :id");
                $errorStmt->bindValue(':id', $email['id'], SQLITE3_INTEGER);
                $errorStmt->bindValue(':error', $e->getMessage(), SQLITE3_TEXT);
                $errorStmt->execute();
            }
            
            $emailsFailed++;
            cronLog("  ✗ Email failed: " . $e->getMessage());
        }
    }
    
    cronLog("Emails sent: $emailsSent, Failed: $emailsFailed");
    
    // 3. Check for stale workflow executions
    cronLog('Checking for stale executions...');
    
    $staleStmt = $db->prepare("
        UPDATE workflow_executions 
        SET status = 'failed', error_message = 'Execution timed out'
        WHERE status = 'running' 
        AND started_at < datetime('now', '-1 hour')
    ");
    $staleStmt->execute();
    $staleCount = $db->changes();
    
    if ($staleCount > 0) {
        cronLog("Marked $staleCount stale executions as failed");
    }
    
    // 4. Clean up old completed records (older than 30 days)
    cronLog('Cleaning up old records...');
    
    $cleanupStmt = $db->prepare("
        DELETE FROM workflow_executions 
        WHERE status IN ('completed', 'failed') 
        AND completed_at < datetime('now', '-30 days')
    ");
    $cleanupStmt->execute();
    $cleanedUp = $db->changes();
    
    if ($cleanedUp > 0) {
        cronLog("Cleaned up $cleanedUp old execution records");
    }
    
    $db->close();
    
    cronLog('=== Automation Processor Completed ===');
    
    // Output summary for web access
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'workflow_steps_processed' => $stepsProcessed,
            'emails_sent' => $emailsSent,
            'emails_failed' => $emailsFailed,
            'stale_executions' => $staleCount,
            'records_cleaned' => $cleanedUp,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (Exception $e) {
    cronLog('FATAL ERROR: ' . $e->getMessage());
    
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    exit(1);
}
