<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Designer - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        header h1 { font-size: 1.8rem; }
        .back-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; transition: 0.3s; }
        .back-btn:hover { background: rgba(255,255,255,0.15); }
        .btn { padding: 0.75rem 1.5rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; transition: 0.3s; }
        .btn:hover { transform: translateY(-2px); }
        .info-card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .info-row { display: flex; gap: 2rem; margin-bottom: 0.5rem; }
        .info-label { color: #888; min-width: 120px; }
        .info-value { color: #fff; font-weight: 600; }
        .fields-section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; }
        .fields-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .fields-table { width: 100%; border-collapse: collapse; }
        .fields-table thead { background: rgba(255,255,255,0.1); }
        .fields-table th { text-align: left; padding: 1rem; border-bottom: 2px solid #00d9ff; font-weight: 600; }
        .fields-table td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .fields-table tbody tr:hover { background: rgba(255,255,255,0.05); }
        .field-type-badge { padding: 0.25rem 0.75rem; background: rgba(0,217,255,0.2); border: 1px solid #00d9ff; border-radius: 6px; font-size: 0.85rem; display: inline-block; }
        .required-badge { padding: 0.25rem 0.75rem; background: rgba(255,100,100,0.2); border: 1px solid #ff6464; border-radius: 6px; font-size: 0.85rem; display: inline-block; color: #ff6464; }
        .field-actions { display: flex; gap: 0.5rem; }
        .field-actions button { padding: 0.5rem 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: 0.3s; }
        .field-actions button:hover { background: rgba(255,255,255,0.1); border-color: #00d9ff; }
        .empty-state { text-align: center; padding: 3rem; color: #666; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; overflow-y: auto; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1a2e; border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; margin: 2rem; border: 1px solid rgba(255,255,255,0.1); max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h2 { font-size: 1.5rem; }
        .modal-close { background: transparent; border: none; color: #888; font-size: 1.5rem; cursor: pointer; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #ccc; font-weight: 600; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; font-size: 1rem; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #00d9ff; }
        .form-group small { color: #666; font-size: 0.85rem; display: block; margin-top: 0.25rem; }
        .checkbox-group { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox-group input[type="checkbox"] { width: auto; }
        .field-type-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem; }
        .field-type-option { padding: 1rem; background: rgba(255,255,255,0.05); border: 2px solid transparent; border-radius: 8px; cursor: pointer; text-align: center; transition: 0.3s; }
        .field-type-option:hover, .field-type-option.selected { border-color: #00d9ff; background: rgba(0,217,255,0.1); }
        .field-type-icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .field-type-label { font-size: 0.85rem; color: #ccc; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div>
            <a href="index.php" class="back-btn">← Back</a>
            <h1 id="tableName" style="display: inline; margin-left: 1rem;">Loading...</h1>
        </div>
        <button class="btn" onclick="openFieldModal()">+ Add Field</button>
    </header>

    <div class="info-card" id="tableInfo">
        <div class="info-row">
            <span class="info-label">Table Name:</span>
            <span class="info-value" id="infoTableName">-</span>
        </div>
        <div class="info-row">
            <span class="info-label">Description:</span>
            <span class="info-value" id="infoDescription">-</span>
        </div>
        <div class="info-row">
            <span class="info-label">Fields:</span>
            <span class="info-value" id="infoFieldCount">0</span>
        </div>
    </div>

    <div class="fields-section">
        <div class="fields-header">
            <h2>Fields</h2>
        </div>
        <div id="fieldsContainer">
            <div class="empty-state">
                <p>Loading fields...</p>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Field Modal -->
<div id="fieldModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Field</h2>
            <button class="modal-close" onclick="closeFieldModal()">×</button>
        </div>
        <form id="fieldForm">
            <input type="hidden" id="fieldId">
            <input type="hidden" id="tableId">
            
            <div class="form-group">
                <label>Field Type *</label>
                <div class="field-type-grid" id="fieldTypeGrid"></div>
                <input type="hidden" id="fieldType" required>
            </div>

            <div class="form-group">
                <label for="fieldName">Field Name *</label>
                <input type="text" id="fieldName" placeholder="e.g., customer_name, email, phone" required>
                <small>Technical name (lowercase, no spaces)</small>
            </div>

            <div class="form-group">
                <label for="displayName">Display Name *</label>
                <input type="text" id="displayName" placeholder="e.g., Customer Name, Email Address" required>
            </div>

            <div class="form-group">
                <label for="helpText">Help Text</label>
                <input type="text" id="helpText" placeholder="Optional hint for users">
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="isRequired">
                <label for="isRequired">Required Field</label>
            </div>

            <div class="form-group">
                <label for="defaultValue">Default Value</label>
                <input type="text" id="defaultValue" placeholder="Optional default value">
            </div>

            <div id="optionsContainer" style="display: none;">
                <div class="form-group">
                    <label for="options">Options (one per line)</label>
                    <textarea id="options" rows="4" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                    <small>For dropdown/radio fields</small>
                </div>
            </div>

            <button type="submit" class="btn" style="width: 100%;">Save Field</button>
        </form>
    </div>
</div>

<script src="field-types.js" type="module"></script>
<script type="module">
import FIELD_TYPES from './field-types.js';

const urlParams = new URLSearchParams(window.location.search);
const tableId = urlParams.get('table_id');
let currentTable = null;
let currentFields = [];
let selectedFieldType = 'text';
let editingFieldId = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    if (!tableId) {
        alert('No table specified');
        window.location.href = 'index.php';
        return;
    }
    loadTable();
    loadFields();
    renderFieldTypes();
});

async function loadTable() {
    try {
        const response = await fetch(`api/tables.php?action=get&id=${tableId}`);
        const data = await response.json();
        
        if (data.success) {
            currentTable = data.table;
            document.getElementById('tableName').textContent = `${data.table.icon} ${data.table.display_name}`;
            document.getElementById('infoTableName').textContent = data.table.display_name;
            document.getElementById('infoDescription').textContent = data.table.description || 'No description';
        }
    } catch (error) {
        console.error('Error loading table:', error);
    }
}

async function loadFields() {
    try {
        const response = await fetch(`api/fields.php?action=list&table_id=${tableId}`);
        const data = await response.json();
        
        if (data.success) {
            currentFields = data.fields;
            document.getElementById('infoFieldCount').textContent = data.fields.length;
            renderFields(data.fields);
        }
    } catch (error) {
        console.error('Error loading fields:', error);
    }
}

function renderFields(fields) {
    if (fields.length === 0) {
        document.getElementById('fieldsContainer').innerHTML = `
            <div class="empty-state">
                <p>No fields yet. Add your first field to get started!</p>
            </div>
        `;
        return;
    }

    const html = `
        <table class="fields-table">
            <thead>
                <tr>
                    <th>Field Name</th>
                    <th>Type</th>
                    <th>Required</th>
                    <th>Default</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${fields.map(field => {
                    const fieldType = FIELD_TYPES[field.field_type] || {};
                    return `
                        <tr>
                            <td>
                                <strong>${field.display_name}</strong><br>
                                <small style="color: #666;">${field.field_name}</small>
                            </td>
                            <td><span class="field-type-badge">${fieldType.icon || ''} ${fieldType.label || field.field_type}</span></td>
                            <td>${field.is_required ? '<span class="required-badge">Required</span>' : '-'}</td>
                            <td>${field.default_value || '-'}</td>
                            <td>
                                <div class="field-actions">
                                    <button onclick="editField(${field.id})">✏️ Edit</button>
                                    <button onclick="deleteField(${field.id}, '${field.display_name}')">🗑️ Delete</button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('')}
            </tbody>
        </table>
    `;
    document.getElementById('fieldsContainer').innerHTML = html;
}

function renderFieldTypes() {
    const grid = document.getElementById('fieldTypeGrid');
    grid.innerHTML = Object.entries(FIELD_TYPES).map(([key, type]) => `
        <div class="field-type-option ${key === selectedFieldType ? 'selected' : ''}" onclick="selectFieldType('${key}')">
            <div class="field-type-icon">${type.icon}</div>
            <div class="field-type-label">${type.label}</div>
        </div>
    `).join('');
}

window.selectFieldType = function(type) {
    selectedFieldType = type;
    document.getElementById('fieldType').value = type;
    renderFieldTypes();
    
    // Show/hide options based on field type
    const fieldType = FIELD_TYPES[type];
    document.getElementById('optionsContainer').style.display = 
        fieldType.requiresOptions ? 'block' : 'none';
};

window.openFieldModal = function() {
    editingFieldId = null;
    document.getElementById('modalTitle').textContent = 'Add Field';
    document.getElementById('fieldForm').reset();
    document.getElementById('fieldId').value = '';
    document.getElementById('tableId').value = tableId;
    selectedFieldType = 'text';
    renderFieldTypes();
    document.getElementById('fieldModal').classList.add('active');
};

window.closeFieldModal = function() {
    document.getElementById('fieldModal').classList.remove('active');
};

window.editField = async function(fieldId) {
    const field = currentFields.find(f => f.id === fieldId);
    if (!field) return;
    
    editingFieldId = fieldId;
    document.getElementById('modalTitle').textContent = 'Edit Field';
    document.getElementById('fieldId').value = field.id;
    document.getElementById('tableId').value = tableId;
    document.getElementById('fieldName').value = field.field_name;
    document.getElementById('displayName').value = field.display_name;
    document.getElementById('helpText').value = field.help_text || '';
    document.getElementById('isRequired').checked = field.is_required == 1;
    document.getElementById('defaultValue').value = field.default_value || '';
    
    selectedFieldType = field.field_type;
    document.getElementById('fieldType').value = field.field_type;
    renderFieldTypes();
    
    if (field.options) {
        const options = JSON.parse(field.options);
        document.getElementById('options').value = Array.isArray(options) ? options.join('\n') : '';
    }
    
    document.getElementById('fieldModal').classList.add('active');
};

window.deleteField = async function(fieldId, fieldName) {
    if (!confirm(`Delete field "${fieldName}"? Data in this field will be preserved but hidden.`)) {
        return;
    }
    
    try {
        const response = await fetch(`api/fields.php?action=delete&id=${fieldId}`, { method: 'POST' });
        const data = await response.json();
        
        if (data.success) {
            loadFields();
        } else {
            alert('Error deleting field');
        }
    } catch (error) {
        alert('Error deleting field');
        console.error(error);
    }
};

document.getElementById('fieldForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const fieldId = document.getElementById('fieldId').value;
    const fieldData = {
        table_id: tableId,
        field_name: document.getElementById('fieldName').value.toLowerCase().replace(/[^a-z0-9_]/g, ''),
        display_name: document.getElementById('displayName').value,
        field_type: document.getElementById('fieldType').value,
        field_order: currentFields.length,
        is_required: document.getElementById('isRequired').checked ? 1 : 0,
        default_value: document.getElementById('defaultValue').value || null,
        help_text: document.getElementById('helpText').value || null,
        validation_rules: {},
        options: []
    };
    
    // Parse options if applicable
    const fieldType = FIELD_TYPES[fieldData.field_type];
    if (fieldType.requiresOptions) {
        const optionsText = document.getElementById('options').value;
        fieldData.options = optionsText.split('\n').filter(o => o.trim());
    }
    
    try {
        const url = fieldId ? `api/fields.php?action=update&id=${fieldId}` : 'api/fields.php?action=create';
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(fieldData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeFieldModal();
            loadFields();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        alert('Error saving field');
        console.error(error);
    }
});
</script>
</body>
</html>
