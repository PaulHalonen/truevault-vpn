<?php
/**
 * TrueVault VPN - Logger Helper
 * System and automation logging
 */

require_once __DIR__ . '/../config/database.php';

class Logger {
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    
    /**
     * Log a debug message
     */
    public static function debug($message, $context = []) {
        self::log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log an info message
     */
    public static function info($message, $context = []) {
        self::log(self::INFO, $message, $context);
    }
    
    /**
     * Log a warning message
     */
    public static function warning($message, $context = []) {
        self::log(self::WARNING, $message, $context);
    }
    
    /**
     * Log an error message
     */
    public static function error($message, $context = []) {
        self::log(self::ERROR, $message, $context);
    }
    
    /**
     * Log a message
     */
    public static function log($level, $message, $context = []) {
        // Log to database
        self::logToDatabase($level, $message, $context);
        
        // Also log to file for critical errors
        if ($level === self::ERROR) {
            self::logToFile($level, $message, $context);
        }
    }
    
    /**
     * Log to database
     */
    private static function logToDatabase($level, $message, $context = []) {
        try {
            $db = DatabaseManager::getInstance()->logs();
            $stmt = $db->prepare("
                INSERT INTO system_logs (log_type, log_level, message, source, user_id, ip_address, user_agent, request_data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $context['type'] ?? 'system',
                $level,
                $message,
                $context['source'] ?? null,
                $context['user_id'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                isset($context['request']) ? json_encode($context['request']) : null
            ]);
        } catch (Exception $e) {
            // Fall back to file logging if database fails
            self::logToFile($level, $message, $context);
        }
    }
    
    /**
     * Log to file
     */
    private static function logToFile($level, $message, $context = []) {
        $logDir = dirname(dirname(__DIR__)) . '/logs';
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/app_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        
        $logLine = "[$timestamp] [$level] $message$contextStr\n";
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log automation activity
     */
    public static function automation($workflowId, $executionId, $stepId, $message, $level = self::INFO, $context = []) {
        try {
            $db = DatabaseManager::getInstance()->logs();
            $stmt = $db->prepare("
                INSERT INTO automation_logs (workflow_id, execution_id, step_id, log_level, message, context_data)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $workflowId,
                $executionId,
                $stepId,
                $level,
                $message,
                !empty($context) ? json_encode($context) : null
            ]);
        } catch (Exception $e) {
            self::error("Failed to log automation: " . $e->getMessage());
        }
    }
    
    /**
     * Log API request
     */
    public static function apiRequest($endpoint, $method, $userId = null, $responseCode = 200) {
        self::info("API Request: $method $endpoint", [
            'type' => 'api',
            'source' => $endpoint,
            'user_id' => $userId,
            'request' => [
                'method' => $method,
                'response_code' => $responseCode
            ]
        ]);
    }
    
    /**
     * Log security event
     */
    public static function security($event, $details = [], $userId = null) {
        self::warning("Security: $event", [
            'type' => 'security',
            'user_id' => $userId,
            'request' => $details
        ]);
    }
    
    /**
     * Log authentication event
     */
    public static function auth($event, $email, $success = true) {
        $level = $success ? self::INFO : self::WARNING;
        self::log($level, "Auth: $event for $email", [
            'type' => 'auth',
            'request' => [
                'email' => $email,
                'success' => $success
            ]
        ]);
    }
    
    /**
     * Clean old logs (keep last 30 days)
     */
    public static function cleanOldLogs($days = 30) {
        try {
            $db = DatabaseManager::getInstance()->logs();
            $stmt = $db->prepare("DELETE FROM system_logs WHERE created_at < datetime('now', '-' || ? || ' days')");
            $stmt->execute([$days]);
            
            $stmt = $db->prepare("DELETE FROM automation_logs WHERE created_at < datetime('now', '-' || ? || ' days')");
            $stmt->execute([$days]);
            
            // Also clean old log files
            $logDir = dirname(dirname(__DIR__)) . '/logs';
            $files = glob($logDir . '/app_*.log');
            $cutoff = strtotime("-$days days");
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff) {
                    unlink($file);
                }
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
