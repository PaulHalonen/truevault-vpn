<?php
// CSV Export for Database Builder
require_once 'config.php';

$tableId = $_GET['table_id'] ?? 0;
if (!$tableId) {
    die('No table specified');
}

$db = getBuilderDB();

// Get table info
$stmt = $db->prepare("SELECT * FROM custom_tables WHERE id = ?");
$stmt->execute([$tableId]);
$table = $stmt->fetch();

if (!$table) {
    die('Table not found');
}

// Get fields
$fieldsStmt = $db->prepare("SELECT * FROM custom_fields WHERE table_id = ? ORDER BY field_order");
$fieldsStmt->execute([$tableId]);
$fields = $fieldsStmt->fetchAll();

// Get data
try {
    $dataStmt = $db->query("SELECT * FROM data_{$table['table_name']}");
    $records = $dataStmt->fetchAll();
} catch (Exception $e) {
    die('Error fetching data: ' . $e->getMessage());
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $table['table_name'] . '_export_' . date('Y-m-d') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write header row
$headers = ['ID'];
foreach ($fields as $field) {
    $headers[] = $field['display_name'];
}
$headers[] = 'Created At';
$headers[] = 'Updated At';
fputcsv($output, $headers);

// Write data rows
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
exit;
?>
