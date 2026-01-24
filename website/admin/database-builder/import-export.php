<?php
/**
 * TrueVault VPN - Import/Export Interface
 * Part 13 - Task 13.8
 * CSV/Excel import and export
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_BUILDER', DB_PATH . 'builder.db');

$tableId = isset($_GET['table']) ? intval($_GET['table']) : 0;

$db = new SQLite3(DB_BUILDER);
$db->enableExceptions(true);

// Get all tables for dropdown
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import/Export - Database Builder</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .back-link { color: #00d9ff; text-decoration: none; }
        .container { max-width: 1000px; margin: 0 auto; padding: 30px; }
        .card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .card h3 { margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #aaa; }
        .form-group select, .form-group input { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; }
        .btn { padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 1rem; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn:hover { transform: translateY(-2px); }
        .upload-zone { border: 2px dashed rgba(255,255,255,0.2); border-radius: 12px; padding: 40px; text-align: center; transition: all 0.2s; cursor: pointer; }
        .upload-zone:hover { border-color: #00d9ff; background: rgba(0,217,255,0.05); }
        .upload-zone.dragover { border-color: #00ff88; background: rgba(0,255,136,0.1); }
        .upload-zone .icon { font-size: 3rem; margin-bottom: 15px; }
        .upload-zone p { color: #888; margin-bottom: 15px; }
        .export-options { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .export-option { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s; }
        .export-option:hover { border-color: #00d9ff; }
        .export-option .icon { font-size: 2rem; margin-bottom: 10px; }
        .export-option h4 { margin-bottom: 5px; }
        .export-option p { font-size: 0.85rem; color: #666; }
        .field-list { background: rgba(0,0,0,0.2); border-radius: 8px; padding: 15px; margin-top: 15px; }
        .field-list h4 { font-size: 0.9rem; color: #888; margin-bottom: 10px; }
        .field-list ul { list-style: none; display: flex; flex-wrap: wrap; gap: 8px; }
        .field-list li { background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 5px; font-size: 0.85rem; }
        .result { padding: 15px; border-radius: 8px; margin-top: 20px; }
        .result.success { background: rgba(0,255,136,0.1); border: 1px solid #00ff88; color: #00ff88; }
        .result.error { background: rgba(255,80,80,0.1); border: 1px solid #ff5050; color: #ff5050; }
        .progress { height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; margin-top: 15px; display: none; }
        .progress-bar { height: 100%; background: linear-gradient(90deg, #00d9ff, #00ff88); width: 0; transition: width 0.3s; }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="back-link">⬅️ Back to Dashboard</a>
        <h2>📥 Import / Export</h2>
        <div></div>
    </div>
    
    <div class="container">
        <!-- Table Selector -->
        <div class="card">
            <h3>📋 Select Table</h3>
            <div class="form-group">
                <select id="tableSelect" onchange="selectTable(this.value)">
                    <option value="">Choose a table...</option>
                    <?php foreach ($tables as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $t['id'] == $tableId ? 'selected' : '' ?>><?= htmlspecialchars($t['display_name']) ?> (<?= $t['record_count'] ?> records)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($selectedTable && !empty($fields)): ?>
            <div class="field-list">
                <h4>Available Fields:</h4>
                <ul>
                    <?php foreach ($fields as $field): ?>
                    <li><?= htmlspecialchars($field['display_name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($selectedTable): ?>
        <!-- Import Section -->
        <div class="card">
            <h3>📤 Import Data</h3>
            <form id="importForm" enctype="multipart/form-data">
                <input type="hidden" name="table_id" value="<?= $tableId ?>">
                <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                    <div class="icon">📄</div>
                    <p>Drag & drop a CSV file here, or click to browse</p>
                    <input type="file" id="fileInput" name="file" accept=".csv,.xlsx,.xls" style="display:none" onchange="handleFileSelect(this)">
                    <span id="fileName" style="color: #00d9ff;"></span>
                </div>
                <div class="progress" id="importProgress">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
                <div id="importResult"></div>
                <div style="margin-top: 20px;">
                    <button type="button" class="btn btn-primary" onclick="startImport()" id="importBtn" disabled>📥 Start Import</button>
                </div>
            </form>
        </div>
        
        <!-- Export Section -->
        <div class="card">
            <h3>📥 Export Data</h3>
            <div class="export-options">
                <div class="export-option" onclick="exportData('csv')">
                    <div class="icon">📊</div>
                    <h4>CSV Format</h4>
                    <p>Compatible with Excel, Google Sheets</p>
                </div>
                <div class="export-option" onclick="exportData('json')">
                    <div class="icon">📋</div>
                    <h4>JSON Format</h4>
                    <p>For developers and APIs</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        const tableId = <?= $tableId ?: 'null' ?>;
        
        function selectTable(id) {
            if (id) window.location.href = '?table=' + id;
        }
        
        // Drag and drop
        const uploadZone = document.getElementById('uploadZone');
        if (uploadZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadZone.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); });
            });
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadZone.addEventListener(eventName, () => uploadZone.classList.add('dragover'));
            });
            ['dragleave', 'drop'].forEach(eventName => {
                uploadZone.addEventListener(eventName, () => uploadZone.classList.remove('dragover'));
            });
            uploadZone.addEventListener('drop', e => {
                const file = e.dataTransfer.files[0];
                if (file) {
                    document.getElementById('fileInput').files = e.dataTransfer.files;
                    handleFileSelect(document.getElementById('fileInput'));
                }
            });
        }
        
        function handleFileSelect(input) {
            if (input.files.length > 0) {
                document.getElementById('fileName').textContent = input.files[0].name;
                document.getElementById('importBtn').disabled = false;
            }
        }
        
        function startImport() {
            const form = document.getElementById('importForm');
            const formData = new FormData(form);
            
            document.getElementById('importProgress').style.display = 'block';
            document.getElementById('progressBar').style.width = '50%';
            document.getElementById('importBtn').disabled = true;
            
            fetch('api/import.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('progressBar').style.width = '100%';
                const resultDiv = document.getElementById('importResult');
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = '✅ ' + data.message;
                    setTimeout(() => location.reload(), 2000);
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = '❌ ' + (data.error || 'Import failed');
                    document.getElementById('importBtn').disabled = false;
                }
            })
            .catch(err => {
                document.getElementById('importResult').className = 'result error';
                document.getElementById('importResult').innerHTML = '❌ ' + err.message;
                document.getElementById('importBtn').disabled = false;
            });
        }
        
        function exportData(format) {
            window.location.href = 'api/export.php?table_id=' + tableId + '&format=' + format;
        }
    </script>
</body>
</html>
