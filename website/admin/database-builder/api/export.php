<?php
/**
 * TrueVault VPN - Export API
 * Part 13 - Task 13.7/13.8
 * Export table data to CSV/Excel
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../../configs/config.php';

define('DB_BUILDER', DB_PATH . 'builder.db');

$tableId = intval($_GET['table_id'] ?? 0);
$format = $_GET['format'] ?? 'csv';

if (!$tableId) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Table ID required']);
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
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Table not found']);
        exit;
    }
    
    // Get fields
    $stmt = $db->prepare("SELECT * FROM custom_fields WHERE table_id = ? ORDER BY sort_order");
    $stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $fields = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $fields[] = $row;
    }
    
    // Get records
    $dataTableName = 'data_' . $table['table_name'];
    $result = $db->query("SELECT * FROM {$dataTableName} ORDER BY id");
    $records = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $records[] = $row;
    }
    
    $db->close();
    
    // Generate filename
    $filename = $table['table_name'] . '_' . date('Y-m-d') . '.' . $format;
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        $headers = ['id'];
        foreach ($fields as $field) {
            $headers[] = $field['display_name'];
        }
        $headers[] = 'created_at';
        $headers[] = 'updated_at';
        fputcsv($output, $headers);
        
        // Data rows
        foreach ($records as $record) {
            $row = [$record['id']];
            foreach ($fields as $field) {
                $row[] = $record[$field['field_name']] ?? '';
            }
            $row[] = $record['created_at'] ?? '';
            $row[] = $record['updated_at'] ?? '';
            fputcsv($output, $row);
        }
        
        fclose($output);
        
    } elseif ($format === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode(['table' => $table, 'fields' => $fields, 'records' => $records], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
