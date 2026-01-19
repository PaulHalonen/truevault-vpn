<?php
// Database Builder Configuration
// USES: SQLite3 class (NOT PDO)
define('BUILDER_DB_PATH', __DIR__ . '/../databases/builder.db');

function getBuilderDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new SQLite3(BUILDER_DB_PATH);
            $db->enableExceptions(true);
            $db->busyTimeout(5000);
        } catch (Exception $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $db;
}

// Helper function to create dynamic table
function createDynamicTable($tableName, $fields) {
    $db = getBuilderDB();
    
    // Start building CREATE TABLE statement
    $sql = "CREATE TABLE IF NOT EXISTS data_{$tableName} (";
    $sql .= "id INTEGER PRIMARY KEY AUTOINCREMENT,";
    
    foreach ($fields as $field) {
        $fieldName = $field['field_name'];
        $fieldType = $field['field_type'];
        
        // Map field types to SQLite types
        $sqlType = 'TEXT';
        if (in_array($fieldType, ['number', 'currency', 'rating'])) {
            $sqlType = 'REAL';
        } elseif (in_array($fieldType, ['date', 'datetime'])) {
            $sqlType = 'TEXT';
        } elseif ($fieldType === 'checkbox') {
            $sqlType = 'INTEGER';
        }
        
        $sql .= "{$fieldName} {$sqlType},";
    }
    
    $sql .= "created_at TEXT DEFAULT CURRENT_TIMESTAMP,";
    $sql .= "updated_at TEXT DEFAULT CURRENT_TIMESTAMP";
    $sql .= ")";
    
    $db->exec($sql);
    return true;
}

// Helper function to validate field value
function validateField($value, $field) {
    $type = $field['field_type'];
    $rules = json_decode($field['validation_rules'] ?? '{}', true);
    
    // Required check
    if ($field['is_required'] && empty($value)) {
        return ['valid' => false, 'error' => "{$field['display_name']} is required"];
    }
    
    // Type-specific validation
    switch ($type) {
        case 'email':
            if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return ['valid' => false, 'error' => 'Invalid email address'];
            }
            break;
        case 'phone':
            if (!empty($value) && !preg_match('/^[\d\s\-\+\(\)]+$/', $value)) {
                return ['valid' => false, 'error' => 'Invalid phone number'];
            }
            break;
        case 'url':
            if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                return ['valid' => false, 'error' => 'Invalid URL'];
            }
            break;
        case 'number':
        case 'currency':
            if (!empty($value) && !is_numeric($value)) {
                return ['valid' => false, 'error' => 'Must be a number'];
            }
            if (isset($rules['min']) && $value < $rules['min']) {
                return ['valid' => false, 'error' => "Minimum value is {$rules['min']}"];
            }
            if (isset($rules['max']) && $value > $rules['max']) {
                return ['valid' => false, 'error' => "Maximum value is {$rules['max']}"];
            }
            break;
    }
    
    return ['valid' => true];
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
