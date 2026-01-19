<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; }
        header { margin-bottom: 2rem; }
        header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .back-btn { display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; transition: 0.3s; margin-bottom: 1rem; }
        .back-btn:hover { background: rgba(255,255,255,0.15); }
        .upload-section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; }
        .upload-area { border: 2px dashed rgba(255,255,255,0.3); border-radius: 8px; padding: 3rem; text-align: center; cursor: pointer; transition: 0.3s; margin-bottom: 2rem; }
        .upload-area:hover { border-color: #00d9ff; background: rgba(0,217,255,0.05); }
        .upload-area input[type="file"] { display: none; }
        .upload-icon { font-size: 3rem; margin-bottom: 1rem; }
        .instructions { background: rgba(0,217,255,0.1); border-left: 3px solid #00d9ff; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
        .instructions h3 { margin-bottom: 1rem; color: #00d9ff; }
        .instructions ul { margin-left: 2rem; line-height: 1.8; }
        .btn { padding: 0.75rem 1.5rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-block; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .preview { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 1.5rem; margin-top: 2rem; }
        .preview h3 { margin-bottom: 1rem; }
        .preview-table { width: 100%; border-collapse: collapse; overflow-x: auto; }
        .preview-table th, .preview-table td { padding: 0.75rem; border: 1px solid rgba(255,255,255,0.1); text-align: left; }
        .preview-table th { background: rgba(255,255,255,0.1); font-weight: 600; }
        .success-message { background: rgba(0,255,136,0.2); border: 1px solid #00ff88; color: #00ff88; padding: 1rem; border-radius: 8px; margin-top: 1rem; }
        .error-message { background: rgba(255,100,100,0.2); border: 1px solid #ff6464; color: #ff6464; padding: 1rem; border-radius: 8px; margin-top: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <a href="data-manager.php?table_id=<?= $_GET['table_id'] ?? 0 ?>" class="back-btn">← Back to Data</a>
    
    <header>
        <h1>📥 Import CSV Data</h1>
        <p style="color: #888;">Upload a CSV file to import data into your table</p>
    </header>

    <div class="instructions">
        <h3>Before You Import:</h3>
        <ul>
            <li>Your CSV file must include column headers in the first row</li>
            <li>Column names should match your field names (case-insensitive)</li>
            <li>Required fields must have values</li>
            <li>Dates should be in YYYY-MM-DD format</li>
            <li>Boolean fields should use 1/0, true/false, or yes/no</li>
        </ul>
    </div>

    <div class="upload-section">
        <div class="upload-area" onclick="document.getElementById('fileInput').click()">
            <div class="upload-icon">📄</div>
            <p><strong>Click to upload</strong> or drag and drop</p>
            <p style="color: #666; font-size: 0.9rem;">CSV files only</p>
            <input type="file" id="fileInput" accept=".csv" onchange="handleFileSelect(event)">
        </div>
        
        <div id="fileName" style="color: #00d9ff; margin-bottom: 1rem; display: none;"></div>
        
        <button class="btn" id="importBtn" onclick="importData()" disabled>Import Data</button>
        
        <div id="message"></div>
        
        <div id="preview" class="preview" style="display: none;">
            <h3>Preview (first 5 rows):</h3>
            <div style="overflow-x: auto;">
                <table class="preview-table" id="previewTable"></table>
            </div>
        </div>
    </div>
</div>

<script>
const tableId = <?= $_GET['table_id'] ?? 0 ?>;
let csvData = null;
let headers = [];

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    document.getElementById('fileName').textContent = `Selected: ${file.name}`;
    document.getElementById('fileName').style.display = 'block';
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        parseCSV(text);
    };
    reader.readAsText(file);
}

function parseCSV(text) {
    const lines = text.split('\n').filter(line => line.trim());
    if (lines.length < 2) {
        showMessage('CSV file must have at least a header row and one data row', 'error');
        return;
    }
    
    // Parse headers
    headers = lines[0].split(',').map(h => h.trim().replace(/^"|"$/g, ''));
    
    // Parse data
    csvData = [];
    for (let i = 1; i < lines.length; i++) {
        const values = lines[i].split(',').map(v => v.trim().replace(/^"|"$/g, ''));
        const row = {};
        headers.forEach((header, index) => {
            row[header] = values[index] || '';
        });
        csvData.push(row);
    }
    
    showPreview();
    document.getElementById('importBtn').disabled = false;
}

function showPreview() {
    const preview = csvData.slice(0, 5);
    let html = '<thead><tr>';
    headers.forEach(h => html += `<th>${h}</th>`);
    html += '</tr></thead><tbody>';
    
    preview.forEach(row => {
        html += '<tr>';
        headers.forEach(h => html += `<td>${row[h]}</td>`);
        html += '</tr>';
    });
    html += '</tbody>';
    
    document.getElementById('previewTable').innerHTML = html;
    document.getElementById('preview').style.display = 'block';
}

async function importData() {
    if (!csvData || csvData.length === 0) {
        showMessage('No data to import', 'error');
        return;
    }
    
    document.getElementById('importBtn').disabled = true;
    document.getElementById('importBtn').textContent = 'Importing...';
    
    let successCount = 0;
    let errorCount = 0;
    
    for (const row of csvData) {
        try {
            const formData = new FormData();
            formData.append('table_id', tableId);
            formData.append('data', JSON.stringify(row));
            
            const response = await fetch('api/data.php?action=create', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                successCount++;
            } else {
                errorCount++;
            }
        } catch (error) {
            errorCount++;
        }
    }
    
    document.getElementById('importBtn').textContent = 'Import Data';
    
    if (errorCount === 0) {
        showMessage(`✓ Successfully imported ${successCount} records!`, 'success');
        setTimeout(() => {
            window.location.href = `data-manager.php?table_id=${tableId}`;
        }, 2000);
    } else {
        showMessage(`Imported ${successCount} records with ${errorCount} errors.`, 'error');
        document.getElementById('importBtn').disabled = false;
    }
}

function showMessage(text, type) {
    const messageDiv = document.getElementById('message');
    messageDiv.className = type === 'success' ? 'success-message' : 'error-message';
    messageDiv.textContent = text;
    messageDiv.style.display = 'block';
}
</script>
</body>
</html>
