# SECTION 15: ERROR HANDLING (Part 2/2)

**Created:** January 15, 2026  
**Status:** Complete Error Handling Specification  
**Continuation of:** SECTION_15_ERROR_HANDLING_PART1.md  

---

## 📝 ERROR LOGGING

### **Comprehensive Error Logger**

```php
<?php
// File: /includes/error-logger.php

class ErrorLogger {
    
    private $logDir;
    private $maxLogSize = 10485760; // 10 MB
    
    public function __construct() {
        $this->logDir = __DIR__ . '/../logs/';
        
        // Create logs directory if it doesn't exist
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * Log error with context
     */
    public function log($message, $level = 'ERROR', $context = []) {
        $logFile = $this->logDir . 'error.log';
        
        // Rotate log if too large
        if (file_exists($logFile) && filesize($logFile) > $this->maxLogSize) {
            $this->rotateLog($logFile);
        }
        
        // Build log entry
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'file' => $context['file'] ?? 'unknown',
            'line' => $context['line'] ?? 0,
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'context' => $context
        ];
        
        // Write to log
        $logMessage = json_encode($entry) . "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Alert for critical errors
        if ($level === 'CRITICAL') {
            $this->alertAdmin($entry);
        }
    }
    
    /**
     * Log exception
     */
    public function logException($exception, $context = []) {
        $this->log($exception->getMessage(), 'ERROR', array_merge([
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ], $context));
    }
    
    /**
     * Rotate log file
     */
    private function rotateLog($logFile) {
        $timestamp = date('Y-m-d_H-i-s');
        $rotatedFile = str_replace('.log', '_' . $timestamp . '.log', $logFile);
        rename($logFile, $rotatedFile);
        
        // Compress old log
        if (function_exists('gzencode')) {
            $content = file_get_contents($rotatedFile);
            $compressed = gzencode($content, 9);
            file_put_contents($rotatedFile . '.gz', $compressed);
            unlink($rotatedFile);
        }
        
        // Clean old logs (keep last 30 days)
        $this->cleanOldLogs(30);
    }
    
    /**
     * Clean old log files
     */
    private function cleanOldLogs($days) {
        $cutoff = time() - ($days * 24 * 60 * 60);
        $files = glob($this->logDir . '*.log*');
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
    
    /**
     * Alert admin for critical errors
     */
    private function alertAdmin($entry) {
        $to = 'admin@vpn.the-truth-publishing.com';
        $subject = 'CRITICAL ERROR: ' . $entry['message'];
        
        $body = "Critical error detected:\n\n";
        $body .= "Message: {$entry['message']}\n";
        $body .= "Time: {$entry['timestamp']}\n";
        $body .= "File: {$entry['file']}:{$entry['line']}\n";
        $body .= "User: {$entry['user_id']}\n";
        $body .= "IP: {$entry['ip']}\n";
        $body .= "URL: {$entry['url']}\n\n";
        $body .= "Context:\n" . json_encode($entry['context'], JSON_PRETTY_PRINT);
        
        mail($to, $subject, $body);
    }
}

// Global error logging function
function logError($message, $context = []) {
    global $errorLogger;
    
    if (!isset($errorLogger)) {
        $errorLogger = new ErrorLogger();
    }
    
    // Add caller info
    $backtrace = debug_backtrace();
    if (isset($backtrace[0])) {
        $context['file'] = $backtrace[0]['file'] ?? 'unknown';
        $context['line'] = $backtrace[0]['line'] ?? 0;
    }
    
    $errorLogger->log($message, 'ERROR', $context);
}

// Global critical error logging
function logCriticalError($message, $context = []) {
    global $errorLogger;
    
    if (!isset($errorLogger)) {
        $errorLogger = new ErrorLogger();
    }
    
    $errorLogger->log($message, 'CRITICAL', $context);
}
```

### **PHP Error Handler**

```php
<?php
// File: /includes/error-handler.php

/**
 * Custom error handler
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    // Don't log errors that are suppressed with @
    if (error_reporting() === 0) {
        return false;
    }
    
    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED'
    ];
    
    $type = $errorTypes[$errno] ?? 'UNKNOWN';
    
    logError("[$type] $errstr", [
        'file' => $errfile,
        'line' => $errline,
        'errno' => $errno
    ]);
    
    // For fatal errors, show error page
    if (in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        show500Page();
    }
    
    return true;
}

/**
 * Custom exception handler
 */
function customExceptionHandler($exception) {
    global $errorLogger;
    
    if (!isset($errorLogger)) {
        $errorLogger = new ErrorLogger();
    }
    
    $errorLogger->logException($exception, [
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
    ]);
    
    // Show user-friendly error page
    if (isAPIRequest()) {
        sendAPIError(
            'An unexpected error occurred. Our team has been notified',
            'SYS_9001',
            500,
            ['request_id' => uniqid('err_')]
        );
    } else {
        show500Page(uniqid('err_'));
    }
}

/**
 * Shutdown handler for fatal errors
 */
function shutdownHandler() {
    $error = error_get_last();
    
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logCriticalError($error['message'], [
            'file' => $error['file'],
            'line' => $error['line'],
            'type' => $error['type']
        ]);
        
        if (!headers_sent()) {
            show500Page();
        }
    }
}

// Register handlers
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');
register_shutdown_function('shutdownHandler');

// Set error reporting based on environment
if (getenv('ENVIRONMENT') === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
```

---

## 🔄 ERROR RECOVERY

### **Automatic Recovery Mechanisms**

```php
<?php
// File: /includes/error-recovery.php

class ErrorRecovery {
    
    /**
     * Retry operation with exponential backoff
     */
    public static function retry($callable, $maxAttempts = 3, $delayMs = 100) {
        $attempt = 0;
        $lastException = null;
        
        while ($attempt < $maxAttempts) {
            try {
                return $callable();
            } catch (Exception $e) {
                $lastException = $e;
                $attempt++;
                
                if ($attempt < $maxAttempts) {
                    // Exponential backoff: 100ms, 200ms, 400ms
                    $delay = $delayMs * pow(2, $attempt - 1);
                    usleep($delay * 1000);
                    
                    logError("Retry attempt $attempt failed: " . $e->getMessage(), [
                        'attempt' => $attempt,
                        'max_attempts' => $maxAttempts,
                        'delay_ms' => $delay
                    ]);
                }
            }
        }
        
        // All attempts failed
        throw $lastException;
    }
    
    /**
     * Fallback to alternative method
     */
    public static function fallback($primary, $fallback, $context = 'operation') {
        try {
            return $primary();
        } catch (Exception $e) {
            logError("Primary $context failed, using fallback: " . $e->getMessage());
            
            try {
                return $fallback();
            } catch (Exception $e2) {
                logCriticalError("Both primary and fallback $context failed", [
                    'primary_error' => $e->getMessage(),
                    'fallback_error' => $e2->getMessage()
                ]);
                
                throw new Exception("$context failed");
            }
        }
    }
    
    /**
     * Graceful degradation
     */
    public static function graceful($operation, $defaultValue) {
        try {
            return $operation();
        } catch (Exception $e) {
            logError("Operation failed, using default value: " . $e->getMessage());
            return $defaultValue;
        }
    }
}

// Usage examples

/**
 * Retry database connection
 */
function connectToDatabase() {
    return ErrorRecovery::retry(function() {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    }, 3, 500);
}

/**
 * Fallback to secondary server
 */
function getServerConnection($serverId) {
    return ErrorRecovery::fallback(
        function() use ($serverId) {
            return connectToPrimaryServer($serverId);
        },
        function() use ($serverId) {
            return connectToSecondaryServer($serverId);
        },
        'server connection'
    );
}

/**
 * Graceful degradation for user avatar
 */
function getUserAvatar($userId) {
    return ErrorRecovery::graceful(
        function() use ($userId) {
            return fetchAvatarFromCDN($userId);
        },
        '/assets/default-avatar.png'
    );
}
```

### **Circuit Breaker Pattern**

```php
<?php
/**
 * Circuit breaker for external services
 */
class CircuitBreaker {
    
    private $failureThreshold = 5;
    private $timeout = 60; // seconds
    private $cacheDir;
    
    public function __construct() {
        $this->cacheDir = sys_get_temp_dir() . '/circuit_breaker/';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Execute with circuit breaker
     */
    public function execute($serviceName, $callable) {
        $state = $this->getState($serviceName);
        
        // Circuit is open - fail fast
        if ($state === 'open') {
            if ($this->shouldAttemptReset($serviceName)) {
                // Try half-open state
                try {
                    $result = $callable();
                    $this->recordSuccess($serviceName);
                    return $result;
                } catch (Exception $e) {
                    $this->recordFailure($serviceName);
                    throw new Exception("Service $serviceName is unavailable");
                }
            } else {
                throw new Exception("Service $serviceName is unavailable");
            }
        }
        
        // Circuit is closed or half-open - try operation
        try {
            $result = $callable();
            $this->recordSuccess($serviceName);
            return $result;
        } catch (Exception $e) {
            $this->recordFailure($serviceName);
            throw $e;
        }
    }
    
    /**
     * Get circuit state
     */
    private function getState($serviceName) {
        $stateFile = $this->cacheDir . md5($serviceName) . '.json';
        
        if (!file_exists($stateFile)) {
            return 'closed';
        }
        
        $state = json_decode(file_get_contents($stateFile), true);
        
        if ($state['failures'] >= $this->failureThreshold) {
            return 'open';
        }
        
        return 'closed';
    }
    
    /**
     * Should attempt reset?
     */
    private function shouldAttemptReset($serviceName) {
        $stateFile = $this->cacheDir . md5($serviceName) . '.json';
        
        if (!file_exists($stateFile)) {
            return true;
        }
        
        $state = json_decode(file_get_contents($stateFile), true);
        
        return (time() - $state['last_failure']) > $this->timeout;
    }
    
    /**
     * Record success
     */
    private function recordSuccess($serviceName) {
        $stateFile = $this->cacheDir . md5($serviceName) . '.json';
        
        $state = [
            'failures' => 0,
            'last_success' => time()
        ];
        
        file_put_contents($stateFile, json_encode($state));
    }
    
    /**
     * Record failure
     */
    private function recordFailure($serviceName) {
        $stateFile = $this->cacheDir . md5($serviceName) . '.json';
        
        $state = ['failures' => 0, 'last_failure' => 0];
        
        if (file_exists($stateFile)) {
            $state = json_decode(file_get_contents($stateFile), true);
        }
        
        $state['failures']++;
        $state['last_failure'] = time();
        
        file_put_contents($stateFile, json_encode($state));
        
        if ($state['failures'] >= $this->failureThreshold) {
            logError("Circuit breaker opened for $serviceName", $state);
        }
    }
}
```

---

## 🐛 DEBUGGING PROCEDURES

### **Debug Mode**

```php
<?php
// File: /includes/debug.php

class Debug {
    
    private static $enabled = false;
    private static $startTime = null;
    private static $queries = [];
    private static $logs = [];
    
    /**
     * Enable debug mode
     */
    public static function enable() {
        self::$enabled = true;
        self::$startTime = microtime(true);
    }
    
    /**
     * Check if debug mode is enabled
     */
    public static function isEnabled() {
        return self::$enabled || getenv('DEBUG_MODE') === 'true';
    }
    
    /**
     * Log debug message
     */
    public static function log($message, $data = null) {
        if (!self::isEnabled()) {
            return;
        }
        
        self::$logs[] = [
            'time' => microtime(true) - self::$startTime,
            'message' => $message,
            'data' => $data
        ];
    }
    
    /**
     * Log SQL query
     */
    public static function logQuery($query, $params = [], $duration = 0) {
        if (!self::isEnabled()) {
            return;
        }
        
        self::$queries[] = [
            'query' => $query,
            'params' => $params,
            'duration' => $duration
        ];
    }
    
    /**
     * Dump debug information
     */
    public static function dump() {
        if (!self::isEnabled()) {
            return;
        }
        
        $totalTime = microtime(true) - self::$startTime;
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;
        
        ?>
        <div style="background: #1e1e1e; color: #d4d4d4; padding: 20px; font-family: monospace; font-size: 12px; position: fixed; bottom: 0; left: 0; right: 0; max-height: 50vh; overflow: auto; z-index: 9999; border-top: 3px solid #007acc;">
            <h3 style="color: #4ec9b0; margin: 0 0 15px 0;">🐛 Debug Information</h3>
            
            <div style="margin-bottom: 15px;">
                <strong style="color: #dcdcaa;">Performance:</strong><br>
                Total Time: <?php echo number_format($totalTime * 1000, 2); ?> ms<br>
                Memory Usage: <?php echo number_format($memoryUsage, 2); ?> MB<br>
                Peak Memory: <?php echo number_format($peakMemory, 2); ?> MB<br>
            </div>
            
            <div style="margin-bottom: 15px;">
                <strong style="color: #dcdcaa;">SQL Queries (<?php echo count(self::$queries); ?>):</strong><br>
                <?php foreach (self::$queries as $i => $query): ?>
                    <div style="margin: 10px 0; padding: 10px; background: #2d2d2d; border-left: 3px solid #569cd6;">
                        <div style="color: #569cd6;">#<?php echo $i + 1; ?> (<?php echo number_format($query['duration'] * 1000, 2); ?> ms)</div>
                        <div style="color: #ce9178; margin: 5px 0;"><?php echo htmlspecialchars($query['query']); ?></div>
                        <?php if (!empty($query['params'])): ?>
                            <div style="color: #9cdcfe; font-size: 11px;">Params: <?php echo htmlspecialchars(json_encode($query['params'])); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div>
                <strong style="color: #dcdcaa;">Debug Logs (<?php echo count(self::$logs); ?>):</strong><br>
                <?php foreach (self::$logs as $log): ?>
                    <div style="margin: 5px 0;">
                        <span style="color: #858585;">[<?php echo number_format($log['time'] * 1000, 2); ?> ms]</span>
                        <span style="color: #d4d4d4;"><?php echo htmlspecialchars($log['message']); ?></span>
                        <?php if ($log['data']): ?>
                            <pre style="margin: 5px 0; padding: 5px; background: #2d2d2d; color: #9cdcfe; font-size: 11px;"><?php echo htmlspecialchars(print_r($log['data'], true)); ?></pre>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}

// Usage
if (getenv('DEBUG_MODE') === 'true') {
    Debug::enable();
}

Debug::log('Application started');
Debug::log('User authenticated', ['user_id' => 123]);
Debug::logQuery('SELECT * FROM users WHERE id = ?', [123], 0.002);

// At end of script
if (Debug::isEnabled()) {
    Debug::dump();
}
```

### **Debug Helper Functions**

```php
<?php
/**
 * Pretty print variable for debugging
 */
function dd($var) {
    echo '<pre style="background: #1e1e1e; color: #d4d4d4; padding: 20px; margin: 20px; border-radius: 5px; overflow: auto;">';
    var_dump($var);
    echo '</pre>';
    die();
}

/**
 * Debug print and continue
 */
function dp($var) {
    echo '<pre style="background: #1e1e1e; color: #d4d4d4; padding: 20px; margin: 20px; border-radius: 5px; overflow: auto;">';
    print_r($var);
    echo '</pre>';
}

/**
 * Log to browser console
 */
function console_log($data) {
    echo '<script>';
    echo 'console.log(' . json_encode($data) . ');';
    echo '</script>';
}

/**
 * Measure execution time
 */
function benchmark($callback, $iterations = 1) {
    $start = microtime(true);
    
    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }
    
    $end = microtime(true);
    $duration = ($end - $start) / $iterations;
    
    echo "Average execution time: " . number_format($duration * 1000, 4) . " ms\n";
}
```

---

## 📊 ERROR MONITORING

### **Real-Time Error Monitoring**

```php
<?php
// File: /admin/error-monitor.php

/**
 * Get recent errors
 */
function getRecentErrors($limit = 50) {
    $logFile = __DIR__ . '/../logs/error.log';
    
    if (!file_exists($logFile)) {
        return [];
    }
    
    // Read last N lines
    $lines = file($logFile);
    $lines = array_slice($lines, -$limit);
    
    $errors = [];
    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if ($entry) {
            $errors[] = $entry;
        }
    }
    
    return array_reverse($errors);
}

/**
 * Get error statistics
 */
function getErrorStats() {
    $logFile = __DIR__ . '/../logs/error.log';
    
    if (!file_exists($logFile)) {
        return [
            'total' => 0,
            'by_level' => [],
            'by_user' => [],
            'by_endpoint' => []
        ];
    }
    
    $lines = file($logFile);
    $stats = [
        'total' => count($lines),
        'by_level' => [],
        'by_user' => [],
        'by_endpoint' => []
    ];
    
    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if (!$entry) continue;
        
        // By level
        $level = $entry['level'] ?? 'UNKNOWN';
        $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
        
        // By user
        $userId = $entry['user_id'] ?? 'guest';
        $stats['by_user'][$userId] = ($stats['by_user'][$userId] ?? 0) + 1;
        
        // By endpoint
        $url = $entry['url'] ?? 'unknown';
        $stats['by_endpoint'][$url] = ($stats['by_endpoint'][$url] ?? 0) + 1;
    }
    
    // Sort
    arsort($stats['by_level']);
    arsort($stats['by_user']);
    arsort($stats['by_endpoint']);
    
    return $stats;
}

/**
 * Check for critical errors
 */
function checkCriticalErrors($minutes = 5) {
    $logFile = __DIR__ . '/../logs/error.log';
    
    if (!file_exists($logFile)) {
        return [];
    }
    
    $cutoff = time() - ($minutes * 60);
    $lines = file($logFile);
    
    $critical = [];
    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if (!$entry) continue;
        
        $timestamp = strtotime($entry['timestamp']);
        
        if ($entry['level'] === 'CRITICAL' && $timestamp > $cutoff) {
            $critical[] = $entry;
        }
    }
    
    return $critical;
}
```

### **Error Dashboard**

```php
<?php
// File: /admin/error-dashboard.php

require_once __DIR__ . '/error-monitor.php';

$stats = getErrorStats();
$recent = getRecentErrors(20);
$critical = checkCriticalErrors(60);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Error Dashboard - TrueVault VPN Admin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .error-list {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .error-item {
            padding: 15px;
            border-left: 4px solid #667eea;
            margin-bottom: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .error-item.critical {
            border-left-color: #f5576c;
            background: #fff5f7;
        }
        .error-time {
            color: #999;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .error-message {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .error-details {
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐛 Error Dashboard</h1>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Errors</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number" style="color: #f5576c;">
                    <?php echo $stats['by_level']['CRITICAL'] ?? 0; ?>
                </div>
                <div class="stat-label">Critical Errors</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number" style="color: #ffa726;">
                    <?php echo $stats['by_level']['ERROR'] ?? 0; ?>
                </div>
                <div class="stat-label">Errors</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number" style="color: #66bb6a;">
                    <?php echo count($critical); ?>
                </div>
                <div class="stat-label">Critical (Last Hour)</div>
            </div>
        </div>
        
        <?php if (!empty($critical)): ?>
        <div class="error-list">
            <h2>🚨 Critical Errors (Last Hour)</h2>
            <?php foreach ($critical as $error): ?>
                <div class="error-item critical">
                    <div class="error-time"><?php echo htmlspecialchars($error['timestamp']); ?></div>
                    <div class="error-message"><?php echo htmlspecialchars($error['message']); ?></div>
                    <div class="error-details">
                        File: <?php echo htmlspecialchars($error['file']); ?>:<?php echo $error['line']; ?><br>
                        User: <?php echo htmlspecialchars($error['user_id']); ?> | 
                        IP: <?php echo htmlspecialchars($error['ip']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="error-list" style="margin-top: 30px;">
            <h2>Recent Errors</h2>
            <?php foreach ($recent as $error): ?>
                <div class="error-item <?php echo $error['level'] === 'CRITICAL' ? 'critical' : ''; ?>">
                    <div class="error-time">
                        <?php echo htmlspecialchars($error['timestamp']); ?> | 
                        <strong><?php echo htmlspecialchars($error['level']); ?></strong>
                    </div>
                    <div class="error-message"><?php echo htmlspecialchars($error['message']); ?></div>
                    <div class="error-details">
                        File: <?php echo htmlspecialchars($error['file']); ?>:<?php echo $error['line']; ?><br>
                        URL: <?php echo htmlspecialchars($error['url']); ?><br>
                        User: <?php echo htmlspecialchars($error['user_id']); ?> | 
                        IP: <?php echo htmlspecialchars($error['ip']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
```

---

## 🎯 USER SUPPORT INTEGRATION

### **Error Reporting to Support**

```php
<?php
// File: /includes/support-integration.php

/**
 * Allow users to report errors
 */
function submitErrorReport($errorId, $userDescription) {
    global $db_support;
    
    // Get error details from log
    $errorDetails = getErrorById($errorId);
    
    if (!$errorDetails) {
        return ['success' => false, 'error' => 'Error not found'];
    }
    
    // Create support ticket
    $stmt = $db_support->prepare("
        INSERT INTO tickets (
            user_id, subject, description, category,
            priority, status, error_data, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        'Error Report: ' . substr($errorDetails['message'], 0, 50),
        $userDescription,
        'technical',
        'high',
        'open',
        json_encode($errorDetails)
    ]);
    
    $ticketId = $db_support->lastInsertId();
    
    // Send notification to support
    notifySupport('New error report', [
        'ticket_id' => $ticketId,
        'error_id' => $errorId,
        'user_id' => $_SESSION['user_id']
    ]);
    
    return [
        'success' => true,
        'ticket_id' => $ticketId
    ];
}

/**
 * Auto-suggest solutions based on error
 */
function suggestSolutions($errorCode) {
    $solutions = [
        'AUTH_1001' => [
            'Check your email and password',
            'Reset your password if needed',
            'Clear your browser cache'
        ],
        'DEV_3002' => [
            'Upgrade your plan to add more devices',
            'Remove unused devices',
            'Contact support for assistance'
        ],
        'SRV_4002' => [
            'Try a different server',
            'Check your internet connection',
            'Wait a few minutes and try again'
        ],
        'PAY_6001' => [
            'Check your payment method',
            'Ensure sufficient funds',
            'Try a different payment method',
            'Contact your bank'
        ]
    ];
    
    return $solutions[$errorCode] ?? [
        'Try refreshing the page',
        'Clear your browser cache',
        'Contact support if the problem persists'
    ];
}
```

---

## ✅ PRODUCTION BEST PRACTICES

### **Error Handling Checklist**

```markdown
# Error Handling Best Practices

## Development
- [ ] All errors logged with context
- [ ] Debug mode available for development
- [ ] Error handlers registered
- [ ] Exception handlers registered
- [ ] Shutdown handler registered

## User Experience
- [ ] User-friendly error pages (404, 500, 503)
- [ ] No technical details exposed to users
- [ ] Clear, actionable error messages
- [ ] Contact support options visible
- [ ] Request IDs for error tracking

## API Errors
- [ ] Standardized error format
- [ ] Consistent error codes
- [ ] Appropriate HTTP status codes
- [ ] Detailed validation errors
- [ ] Rate limit info in headers

## Logging
- [ ] All errors logged with timestamp
- [ ] Context information included
- [ ] User ID and IP logged
- [ ] Stack traces for exceptions
- [ ] Log rotation configured
- [ ] Old logs cleaned automatically

## Recovery
- [ ] Retry mechanisms for transient failures
- [ ] Fallback options available
- [ ] Graceful degradation implemented
- [ ] Circuit breakers for external services
- [ ] Database connection retries

## Monitoring
- [ ] Error dashboard accessible
- [ ] Critical error alerts configured
- [ ] Error statistics tracked
- [ ] Trends analyzed
- [ ] Response time for issues defined

## Support
- [ ] Error reporting system for users
- [ ] Auto-suggest solutions
- [ ] Support ticket integration
- [ ] Knowledge base linked
- [ ] Quick resolution paths

## Security
- [ ] No sensitive data in error messages
- [ ] No stack traces to users
- [ ] Error logs secured (chmod 640)
- [ ] Admin-only error dashboard
- [ ] Rate limiting on error endpoints
```

---

## 🎯 SUMMARY

### **Key Error Handling Principles**

✅ **User-Friendly**
- Clear, simple messages
- No technical jargon
- Actionable next steps
- Support contact info

✅ **Comprehensive Logging**
- All errors logged with context
- Stack traces for debugging
- User and request info
- Automatic rotation

✅ **Graceful Recovery**
- Retry transient failures
- Fallback mechanisms
- Graceful degradation
- Circuit breakers

✅ **Security First**
- No sensitive data leaked
- Admin-only error access
- Secured log files
- Rate limit protection

✅ **Support Integration**
- Error reporting system
- Auto-suggest solutions
- Ticket creation
- Quick resolution

---

**END OF SECTION 15: ERROR HANDLING (Complete)**

**Status:** ✅ COMPLETE  
**Total Lines:** ~1,800 lines (Parts 1 + 2)  
**Created:** January 15, 2026 - 7:25 AM CST

**Features Covered:**
- Error philosophy & categories
- Standardized error codes
- User-friendly error pages
- API error responses
- Comprehensive logging
- Automatic recovery
- Retry mechanisms
- Circuit breakers
- Debug procedures
- Error monitoring
- Support integration
- Production best practices

**This is THE FINAL SECTION!**

🎉🎉🎉 **MASTER BLUEPRINT 100% COMPLETE!** 🎉🎉🎉
