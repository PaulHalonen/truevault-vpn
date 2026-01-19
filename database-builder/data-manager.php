<?php
$tableId = $_GET['table_id'] ?? 0;
if (!$tableId) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Manager - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1600px; margin: 0 auto; padding: 2rem; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        header h1 { font-size: 1.8rem; }
        .back-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; transition: 0.3s; }
        .back-btn:hover { background: rgba(255,255,255,0.15); }
        .btn { padding: 0.75rem 1.5rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn:hover { transform: translateY(-2px); }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .toolbar { display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .search-box { flex: 1; min-width: 250px; }
        .search-box input { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; }
        .data-section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; min-width: 800px; }
        .data-table thead { background: rgba(255,255,255,0.1); position: sticky; top: 0; z-index: 10; }
        .data-table th { text-align: left; padding: 1rem; border-bottom: 2px solid #00d9ff; font-weight: 600; white-space: nowrap; }
        .data-table td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .data-table tbody tr:hover { background: rgba(255,255,255,0.05); cursor: pointer; }
        .pagination { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
        .pagination-info { color: #888; }
        .pagination-buttons { display: flex; gap: 0.5rem; }
        .pagination-buttons button { padding: 0.5rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        .pagination-buttons button:hover:not(:disabled) { background: rgba(255,255,255,0.1); border-color: #00d9ff; }
        .pagination-buttons button:disabled { opacity: 0.3; cursor: not-allowed; }
        .empty-state { text-align: center; padding: 4rem 2rem; color: #666; }
        .empty-state .icon { font-size: 4rem; margin-bottom: 1rem; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; overflow-y: auto; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1a2e; border-radius: 12px; padding: 2rem; max-width: 700px; width: 90%; margin: 2rem; border: 1px solid rgba(255,255,255,0.1); max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h2 { font-size: 1.5rem; }
        .modal-close { background: transparent; border: none; color: #888; font-size: 1.5rem; cursor: pointer; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #ccc; font-weight: 600; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; font-size: 1rem; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #00d9ff; }
        .form-group small { color: #666; font-size: 0.85rem; display: block; margin-top: 0.25rem; }
        .checkbox-input { width: auto !important; }
        .error-message { color: #ff6464; font-size: 0.85rem; margin-top: 0.25rem; }
        .actions-cell { display: flex; gap: 0.5rem; }
        .actions-cell button { padding: 0.5rem 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: 0.3s; }
        .actions-cell button:hover { background: rgba(255,255,255,0.1); border-color: #00d9ff; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div>
            <a href="index.php" class="back-btn">← Back</a>
            <h1 id="tableName" style="display: inline; margin-left: 1rem;">Loading...</h1>
        </div>
        <div style="display: flex; gap: 1rem;">
            <button class="btn btn-secondary" onclick="window.location.href='designer.php?table_id=<?= $tableId ?>'">⚙️ Design Table</button>
            <button class="btn" onclick="openRecordModal()">+ Add Record</button>
        </div>
    </header>

    <div class="toolbar">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search records..." onkeyup="handleSearch()">
        </div>
        <button class="btn btn-secondary" onclick="exportData()">📊 Export CSV</button>
        <button class="btn btn-secondary" onclick="importData()">📥 Import CSV</button>
    </div>

    <div class="data-section">
        <div id="dataContainer">
            <div class="empty-state">
                <div class="icon">📊</div>
                <p>Loading data...</p>
            </div>
        </div>
        
        <div class="pagination" id="pagination" style="display: none;">
            <div class="pagination-info" id="paginationInfo"></div>
            <div class="pagination-buttons">
                <button id="prevBtn" onclick="changePage(-1)">← Previous</button>
                <button id="nextBtn" onclick="changePage(1)">Next →</button>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Record Modal -->
<div id="recordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Record</h2>
            <button class="modal-close" onclick="closeRecordModal()">×</button>
        </div>
        <form id="recordForm">
            <input type="hidden" id="recordId">
            <div id="formFields"></div>
            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Save Record</button>
        </form>
    </div>
</div>

<script>
const tableId = <?= $tableId ?>;
let currentTable = null;
let currentFields = [];
let currentRecords = [];
let currentPage = 1;
let totalPages = 1;
let editingRecordId = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadData();
});

async function loadData() {
    try {
        const response = await fetch(`api/data.php?action=list&table_id=${tableId}&page=${currentPage}`);
        const data = await response.json();
        
        if (data.success) {
            currentTable = data.table;
            currentFields = data.fields;
            currentRecords = data.records;
            totalPages = data.pagination.total_pages;
            
            document.getElementById('tableName').textContent = `${data.table.icon} ${data.table.display_name}`;
            
            if (data.records.length > 0) {
                renderData(data.records, data.fields);
                renderPagination(data.pagination);
            } else {
                document.getElementById('dataContainer').innerHTML = `
                    <div class="empty-state">
                        <div class="icon">📝</div>
                        <p>No records yet. Add your first record to get started!</p>
                    </div>
                `;
                document.getElementById('pagination').style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error loading data:', error);
    }
}

function renderData(records, fields) {
    const html = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    ${fields.map(f => `<th>${f.display_name}</th>`).join('')}
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${records.map(record => `
                    <tr onclick="editRecord(${record.id})">
                        <td>${record.id}</td>
                        ${fields.map(field => {
                            let value = record[field.field_name];
                            if (field.field_type === 'checkbox') {
                                value = value ? '✓' : '✗';
                            } else if (!value) {
                                value = '-';
                            }
                            return `<td>${value}</td>`;
                        }).join('')}
                        <td onclick="event.stopPropagation()">
                            <div class="actions-cell">
                                <button onclick="editRecord(${record.id})">✏️ Edit</button>
                                <button onclick="deleteRecord(${record.id})">🗑️ Delete</button>
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    document.getElementById('dataContainer').innerHTML = html;
}

function renderPagination(pagination) {
    document.getElementById('pagination').style.display = 'flex';
    document.getElementById('paginationInfo').textContent = 
        `Showing ${(pagination.page - 1) * pagination.per_page + 1} - ${Math.min(pagination.page * pagination.per_page, pagination.total_records)} of ${pagination.total_records}`;
    
    document.getElementById('prevBtn').disabled = pagination.page === 1;
    document.getElementById('nextBtn').disabled = pagination.page === pagination.total_pages;
}

function changePage(delta) {
    currentPage += delta;
    loadData();
}

function handleSearch() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    if (!query) {
        renderData(currentRecords, currentFields);
        return;
    }
    
    const filtered = currentRecords.filter(record => {
        return currentFields.some(field => {
            const value = String(record[field.field_name] || '').toLowerCase();
            return value.includes(query);
        });
    });
    
    renderData(filtered, currentFields);
}

function openRecordModal(recordId = null) {
    editingRecordId = recordId;
    document.getElementById('modalTitle').textContent = recordId ? 'Edit Record' : 'Add Record';
    document.getElementById('recordId').value = recordId || '';
    
    // Generate form fields
    const formFieldsHtml = currentFields.map(field => {
        let inputHtml = '';
        
        switch (field.field_type) {
            case 'textarea':
                inputHtml = `<textarea id="field_${field.field_name}" rows="4" ${field.is_required ? 'required' : ''}></textarea>`;
                break;
            case 'checkbox':
                inputHtml = `<input type="checkbox" id="field_${field.field_name}" class="checkbox-input">`;
                break;
            case 'select':
            case 'radio':
                const options = JSON.parse(field.options || '[]');
                if (field.field_type === 'select') {
                    inputHtml = `
                        <select id="field_${field.field_name}" ${field.is_required ? 'required' : ''}>
                            <option value="">- Select -</option>
                            ${options.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                        </select>
                    `;
                } else {
                    inputHtml = options.map(opt => `
                        <label style="display: block; margin: 0.5rem 0;">
                            <input type="radio" name="field_${field.field_name}" value="${opt}" ${field.is_required ? 'required' : ''}>
                            ${opt}
                        </label>
                    `).join('');
                }
                break;
            case 'date':
                inputHtml = `<input type="date" id="field_${field.field_name}" ${field.is_required ? 'required' : ''}>`;
                break;
            case 'datetime':
                inputHtml = `<input type="datetime-local" id="field_${field.field_name}" ${field.is_required ? 'required' : ''}>`;
                break;
            case 'email':
                inputHtml = `<input type="email" id="field_${field.field_name}" ${field.is_required ? 'required' : ''}>`;
                break;
            case 'url':
                inputHtml = `<input type="url" id="field_${field.field_name}" ${field.is_required ? 'required' : ''}>`;
                break;
            case 'number':
            case 'currency':
            case 'rating':
                inputHtml = `<input type="number" step="0.01" id="field_${field.field_name}" ${field.is_required ? 'required' : ''}>`;
                break;
            default:
                inputHtml = `<input type="text" id="field_${field.field_name}" ${field.is_required ? 'required' : ''}>`;
        }
        
        return `
            <div class="form-group">
                <label for="field_${field.field_name}">${field.display_name} ${field.is_required ? '*' : ''}</label>
                ${inputHtml}
                ${field.help_text ? `<small>${field.help_text}</small>` : ''}
            </div>
        `;
    }).join('');
    
    document.getElementById('formFields').innerHTML = formFieldsHtml;
    
    // If editing, populate values
    if (recordId) {
        const record = currentRecords.find(r => r.id === recordId);
        if (record) {
            currentFields.forEach(field => {
                const element = document.getElementById(`field_${field.field_name}`);
                if (element) {
                    if (field.field_type === 'checkbox') {
                        element.checked = record[field.field_name] == 1;
                    } else if (field.field_type === 'radio') {
                        const radios = document.getElementsByName(`field_${field.field_name}`);
                        radios.forEach(r => {
                            if (r.value === record[field.field_name]) r.checked = true;
                        });
                    } else {
                        element.value = record[field.field_name] || '';
                    }
                }
            });
        }
    }
    
    document.getElementById('recordModal').classList.add('active');
}

function closeRecordModal() {
    document.getElementById('recordModal').classList.remove('active');
}

async function editRecord(recordId) {
    // Load the record data first
    try {
        const response = await fetch(`api/data.php?action=get&table_id=${tableId}&record_id=${recordId}`);
        const data = await response.json();
        
        if (data.success) {
            openRecordModal(recordId);
        }
    } catch (error) {
        console.error('Error loading record:', error);
    }
}

async function deleteRecord(recordId) {
    if (!confirm('Delete this record? This cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch(`api/data.php?action=delete&table_id=${tableId}&record_id=${recordId}`, { method: 'POST' });
        const data = await response.json();
        
        if (data.success) {
            loadData();
        } else {
            alert('Error deleting record');
        }
    } catch (error) {
        alert('Error deleting record');
        console.error(error);
    }
}

document.getElementById('recordForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const recordId = document.getElementById('recordId').value;
    const formData = new FormData();
    formData.append('table_id', tableId);
    if (recordId) formData.append('record_id', recordId);
    
    const data = {};
    currentFields.forEach(field => {
        const element = document.getElementById(`field_${field.field_name}`);
        if (element) {
            if (field.field_type === 'checkbox') {
                data[field.field_name] = element.checked ? 1 : 0;
            } else if (field.field_type === 'radio') {
                const radios = document.getElementsByName(`field_${field.field_name}`);
                const checked = Array.from(radios).find(r => r.checked);
                data[field.field_name] = checked ? checked.value : '';
            } else {
                data[field.field_name] = element.value;
            }
        }
    });
    
    formData.append('data', JSON.stringify(data));
    
    try {
        const action = recordId ? 'update' : 'create';
        const response = await fetch(`api/data.php?action=${action}`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeRecordModal();
            loadData();
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error saving record');
        console.error(error);
    }
});

function exportData() {
    window.location.href = `export.php?table_id=${tableId}`;
}

function importData() {
    window.location.href = `import.php?table_id=${tableId}`;
}
</script>
</body>
</html>
