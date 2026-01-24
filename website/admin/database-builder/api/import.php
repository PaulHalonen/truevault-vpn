<?php
/**
 * TrueVault VPN - Import API
 * Part 13 - Task 13.7/13.8
 * Import CSV data into tables
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../../configs/config.php';

header('Content-Type: application/json');
define('DB_BUILDER', DB_PATH . 'builder.db');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}

$tableId = intval($_POST['table_id'] ?? 0);

if (!$tableId || !isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'Table ID and file required']);
    exit;
}

try {
    $db = new SQLite3(DB_BUILDER);
    $db->enableExceptions(true);
    
    // Get table info
    $stmt = $db->prepare("SELECT * FROM custom_tables WHERE id = ?");
    $stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $table = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$table) {
        echo json_encode(['success' => false, 'error' => 'Table not found']);
        exit;
    }
    
    // Get fields
    $stmt = $db->prepare("SELECT * FROM custom_fields WHERE table_id = ? ORDER BY sort_order");
    $stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $fields = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $fields[$row['display_name']] = $row['field_name'];
        $fields[$row['field_name']] = $row['field_name'];
    }
    
    $dataTableName = 'data_' . $table['table_name'];
    
    // Parse CSV
    $file = $_FILES['file']['tmp_name'];
    $handle = fopen($file, 'r');
    
    if (!$handle) {
        echo json_encode(['success' => false, 'error' => 'Could not read file']);
        exit;
    }
    
    // Read header row
    $headers = fgetcsv($handle);
    if (!$headers) {
        echo json_encode(['success' => false, 'error' => 'Empty file or invalid CSV']);
        exit;
    }
    
    // Map headers to field names
    $columnMap = [];
    foreach ($headers as $index => $header) {
        $header = trim($header);
        if (isset($fields[$header])) {
            $columnMap[$index] = $fields[$header];
        }
    }
    
    if (empty($columnMap)) {
        echo json_encode(['success' => false, 'error' => 'No matching columns found']);
        exit;
    }
    
    // Import rows
    $imported = 0;
    $errors = 0;
    
    while (($row = fgetcsv($handle)) !== false) {
        $data = [];
        foreach ($columnMap as $index => $fieldName) {
            if (isset($row[$index])) {
                $data[$fieldName] = $row[$index];
            }
        }
        
        if (!empty($data)) {
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            try {
                $stmt = $db->prepare("INSERT INTO {$dataTableName} ({$columns}) VALUES ({$placeholders})");
                foreach ($data as $key => $value) {
                    $stmt->bindValue(":{$key}", $value, SQLITE3_TEXT);
                }
                $stmt->execute();
                $imported++;
            } catch (Exception $e) {
                $errors++;
            }
        }
    }
    
    fclose($handle);
    
    // Update record count
    $stmt = $db->prepare("UPDATE custom_tables SET record_count = record_count + ? WHERE id = ?");
    $stmt->bindValue(1, $imported, SQLITE3_INTEGER);
    $stmt->bindValue(2, $tableId, SQLITE3_INTEGER);
    $stmt->execute();
    
    $db->close();
    
    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'errors' => $errors,
        'message' => "Imported {$imported} records" . ($errors > 0 ? ", {$errors} errors" : "")
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
