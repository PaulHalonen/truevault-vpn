<?php
/**
 * TrueVault VPN - Import Interface
 * Part 13 - Task 13.8
 * Import CSV/Excel data into tables
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_BUILDER', DB_PATH . 'builder.db');

$tableId = isset($_GET['table']) ? intval($_GET['table']) : 0;

$db = new SQLite3(DB_BUILDER);
$db->enableExceptions(true);

// Get tables for dropdown
$result = $db->query("SELECT * FROM custom_tables WHERE status = 'active' ORDER BY display_name");
$tables = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $tables[] = $row;
}

// Get selected table info
$selectedTable = null;
$fields = [];
if ($tableId > 0) {
    $stmt = $db->prepare("SELECT * FROM custom_tables WHERE id = ?");
    $stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $selectedTable = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($selectedTable) {
        $stmt = $db->prepare("SELECT * FROM custom_fields WHERE table_id = ? ORDER BY sort_order");
        $stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $fields[] = $row;
        }
    }
}

$db->close();

// Handle file upload
$message = '';
$imported = 0;
$failed = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvfile']) && $selectedTable) {
    $file = $_FILES['csvfile']['tmp_name'];
    
    if (($handle = fopen($file, 'r')) !== false) {
        $db = new SQLite3(DB_BUILDER);
        $db->enableExceptions(true);
        
        // Read header row
        $headers = fgetcsv($handle);
        
        // Map headers to field names
        $columnMap = [];
        foreach ($_POST['mapping'] as $csvCol => $fieldName) {
            if (!empty($fieldName)) {
                $columnMap[$csvCol] = $fieldName;
            }
        }
        
        // Import rows
        while (($data = fgetcsv($handle)) !== false) {
            $columns = [];
            $values = [];
            $placeholders = [];
            
            foreach ($columnMap as $csvCol => $fieldName) {
                if (isset($data[$csvCol])) {
                    $columns[] = $fieldName;
                    $values[] = $data[$csvCol];
                    $placeholders[] = '?';
                }
            }
            
            if (!empty($columns)) {
                try {
                    $sql = "INSERT INTO data_{$selectedTable['table_name']} (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
                    $stmt = $db->prepare($sql);
                    foreach ($values as $i => $value) {
                        $stmt->bindValue($i + 1, $value, SQLITE3_TEXT);
                    }
                    $stmt->execute();
                    $imported++;
                } catch (Exception $e) {
                    $failed++;
                }
            }
        }
        
        fclose($handle);
        
        // Update record count
        $db->exec("UPDATE custom_tables SET record_count = (SELECT COUNT(*) FROM data_{$selectedTable['table_name']}) WHERE id = {$tableId}");
        
        $db->close();
        
        $message = "✅ Imported {$imported} records" . ($failed > 0 ? " ({$failed} failed)" : '');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data - Database Builder</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .back-link { color: #00d9ff; text-decoration: none; }
        .container { max-width: 800px; margin: 0 auto; padding: 30px; }
        .section { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .section h3 { margin-bottom: 20px; color: #888; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #aaa; }
        .form-group select, .form-group input[type="file"] { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; }
        .btn { padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn:hover { transform: translateY(-2px); }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .message.success { background: rgba(0,255,136,0.1); border: 1px solid #00ff88; color: #00ff88; }
        .mapping-table { width: 100%; border-collapse: collapse; }
        .mapping-table th, .mapping-table td { padding: 10px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .mapping-table th { color: #888; }
        .mapping-table select { width: 100%; padding: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #fff; }
        .upload-area { border: 2px dashed rgba(255,255,255,0.2); border-radius: 12px; padding: 40px; text-align: center; }
        .upload-area:hover { border-color: #00d9ff; }
        .upload-area input { display: none; }
        .upload-area label { cursor: pointer; }
        .upload-area .icon { font-size: 3rem; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="back-link">⬅️ Back to Dashboard</a>
        <h2>📥 Import Data</h2>
        <div></div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="section">
            <h3>Step 1: Select Table</h3>
            <form method="GET">
                <div class="form-group">
                    <label>Import into table:</label>
                    <select name="table" onchange="this.form.submit()">
                        <option value="">Select a table...</option>
                        <?php foreach ($tables as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $tableId == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        
        <?php if ($selectedTable): ?>
        <form method="POST" enctype="multipart/form-data" id="importForm">
            <div class="section">
                <h3>Step 2: Upload CSV File</h3>
                <div class="upload-area">
                    <div class="icon">📄</div>
                    <label for="csvfile">
                        <p>Drop your CSV file here or <span style="color:#00d9ff">browse</span></p>
                        <small style="color:#666">Supports .csv files</small>
                    </label>
                    <input type="file" name="csvfile" id="csvfile" accept=".csv" required onchange="previewCSV(this)">
                </div>
            </div>
            
            <div class="section" id="mappingSection" style="display:none;">
                <h3>Step 3: Map Columns</h3>
                <p style="color:#888;margin-bottom:15px">Match CSV columns to table fields:</p>
                <table class="mapping-table">
                    <thead>
                        <tr>
                            <th>CSV Column</th>
                            <th>Maps to Field</th>
                        </tr>
                    </thead>
                    <tbody id="mappingBody">
                    </tbody>
                </table>
            </div>
            
            <div class="section" id="previewSection" style="display:none;">
                <h3>Step 4: Preview & Import</h3>
                <p style="color:#888;margin-bottom:15px">Preview first 5 rows:</p>
                <div id="preview" style="overflow-x:auto;"></div>
                <div style="margin-top:20px;display:flex;gap:15px;">
                    <button type="button" class="btn btn-secondary" onclick="location.reload()">Cancel</button>
                    <button type="submit" class="btn btn-primary">📥 Import Data</button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    <script>
        const fields = <?= json_encode($fields) ?>;
        
        function previewCSV(input) {
            const file = input.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const lines = e.target.result.split('\n');
                if (lines.length < 1) return;
                
                const headers = parseCSVLine(lines[0]);
                
                // Build mapping table
                let mappingHtml = '';
                headers.forEach((header, index) => {
                    mappingHtml += `
                        <tr>
                            <td>${escapeHtml(header)}</td>
                            <td>
                                <select name="mapping[${index}]">
                                    <option value="">-- Skip --</option>
                                    ${fields.map(f => `<option value="${f.field_name}" ${header.toLowerCase().includes(f.display_name.toLowerCase()) || header.toLowerCase() === f.field_name ? 'selected' : ''}>${escapeHtml(f.display_name)}</option>`).join('')}
                                </select>
                            </td>
                        </tr>
                    `;
                });
                document.getElementById('mappingBody').innerHTML = mappingHtml;
                document.getElementById('mappingSection').style.display = 'block';
                
                // Build preview
                let previewHtml = '<table style="width:100%;border-collapse:collapse;font-size:0.85rem;"><thead><tr>';
                headers.forEach(h => previewHtml += `<th style="padding:8px;border:1px solid #333;background:rgba(255,255,255,0.05)">${escapeHtml(h)}</th>`);
                previewHtml += '</tr></thead><tbody>';
                
                for (let i = 1; i < Math.min(6, lines.length); i++) {
                    if (!lines[i].trim()) continue;
                    const cols = parseCSVLine(lines[i]);
                    previewHtml += '<tr>';
                    cols.forEach(col => previewHtml += `<td style="padding:8px;border:1px solid #333">${escapeHtml(col)}</td>`);
                    previewHtml += '</tr>';
                }
                
                previewHtml += '</tbody></table>';
                document.getElementById('preview').innerHTML = previewHtml;
                document.getElementById('previewSection').style.display = 'block';
            };
            reader.readAsText(file);
        }
        
        function parseCSVLine(line) {
            const result = [];
            let current = '';
            let inQuotes = false;
            
            for (let i = 0; i < line.length; i++) {
                const char = line[i];
                if (char === '"') {
                    inQuotes = !inQuotes;
                } else if (char === ',' && !inQuotes) {
                    result.push(current.trim());
                    current = '';
                } else {
                    current += char;
                }
            }
            result.push(current.trim());
            return result;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
