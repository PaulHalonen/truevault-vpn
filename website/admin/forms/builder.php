<?php
/**
 * TrueVault VPN - Visual Form Builder
 * Part 14 - Task 14.5
 * Drag-and-drop form builder
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_FORMS', DB_PATH . 'forms.db');

$formId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$templateId = isset($_GET['template']) ? intval($_GET['template']) : 0;

$form = null;
$fields = [];

if (file_exists(DB_FORMS)) {
    $db = new SQLite3(DB_FORMS);
    $db->enableExceptions(true);
    
    if ($formId > 0) {
        $stmt = $db->prepare("SELECT * FROM forms WHERE id = ?");
        $stmt->bindValue(1, $formId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $form = $result->fetchArray(SQLITE3_ASSOC);
        if ($form) {
            $fields = json_decode($form['fields'], true) ?: [];
        }
    } elseif ($templateId > 0) {
        $stmt = $db->prepare("SELECT * FROM forms WHERE id = ? AND is_template = 1");
        $stmt->bindValue(1, $templateId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $form = $result->fetchArray(SQLITE3_ASSOC);
        if ($form) {
            $fields = json_decode($form['fields'], true) ?: [];
            $form['id'] = 0;
            $form['is_template'] = 0;
            $form['display_name'] = $form['display_name'] . ' (Copy)';
        }
    }
    
    $db->close();
}

$fieldTypes = [
    'text' => ['icon' => '📝', 'name' => 'Text Input'],
    'email' => ['icon' => '📧', 'name' => 'Email'],
    'tel' => ['icon' => '📞', 'name' => 'Phone'],
    'number' => ['icon' => '🔢', 'name' => 'Number'],
    'date' => ['icon' => '📅', 'name' => 'Date'],
    'textarea' => ['icon' => '📄', 'name' => 'Text Area'],
    'select' => ['icon' => '⬇️', 'name' => 'Dropdown'],
    'checkbox' => ['icon' => '☑️', 'name' => 'Checkbox'],
    'radio' => ['icon' => '⚪', 'name' => 'Radio'],
    'file' => ['icon' => '📎', 'name' => 'File Upload'],
    'rating' => ['icon' => '⭐', 'name' => 'Star Rating'],
    'url' => ['icon' => '🔗', 'name' => 'URL'],
    'password' => ['icon' => '🔒', 'name' => 'Password'],
    'hidden' => ['icon' => '👁️', 'name' => 'Hidden'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Builder - TrueVault Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .back-link { color: #00d9ff; text-decoration: none; }
        .header-actions { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-success { background: #00c853; color: #fff; }
        .btn:hover { transform: translateY(-2px); }
        .container { display: flex; height: calc(100vh - 70px); }
        .sidebar { width: 200px; background: rgba(0,0,0,0.3); border-right: 1px solid #333; padding: 15px; overflow-y: auto; }
        .sidebar h3 { font-size: 0.8rem; color: #888; margin-bottom: 12px; text-transform: uppercase; }
        .field-type { display: flex; align-items: center; gap: 8px; padding: 10px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; margin-bottom: 8px; cursor: grab; transition: all 0.2s; font-size: 0.85rem; }
        .field-type:hover { background: rgba(0,217,255,0.1); border-color: #00d9ff; }
        .field-type.dragging { opacity: 0.5; }
        .main { flex: 1; display: flex; flex-direction: column; }
        .form-settings { background: rgba(255,255,255,0.02); border-bottom: 1px solid #333; padding: 20px; }
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 0.8rem; color: #888; }
        .form-group input, .form-group select { padding: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #fff; font-size: 0.9rem; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #00d9ff; }
        .canvas-wrapper { flex: 1; overflow-y: auto; padding: 25px; }
        .canvas { max-width: 700px; margin: 0 auto; min-height: 400px; background: rgba(255,255,255,0.02); border: 2px dashed rgba(255,255,255,0.1); border-radius: 12px; padding: 20px; }
        .canvas.dragover { border-color: #00d9ff; background: rgba(0,217,255,0.05); }
        .canvas-empty { text-align: center; padding: 60px 20px; color: #555; }
        .canvas-empty .icon { font-size: 3rem; margin-bottom: 15px; }
        .field-item { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 15px; margin-bottom: 12px; cursor: move; transition: all 0.2s; }
        .field-item:hover { border-color: #00d9ff; }
        .field-item.dragging { opacity: 0.5; transform: scale(0.98); }
        .field-item .field-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .field-item .field-label { font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .field-item .field-actions { display: flex; gap: 5px; }
        .field-item .field-actions button { padding: 5px 10px; font-size: 0.75rem; background: rgba(255,255,255,0.05); border: none; border-radius: 4px; color: #888; cursor: pointer; }
        .field-item .field-actions button:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .field-item .field-actions .delete:hover { background: rgba(255,80,80,0.2); color: #ff5050; }
        .field-item .field-preview { background: rgba(0,0,0,0.2); border-radius: 6px; padding: 10px; }
        .field-item .field-preview input, .field-item .field-preview textarea, .field-item .field-preview select { width: 100%; padding: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #666; }
        .field-item .required { color: #ff5050; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1a2e; border-radius: 12px; padding: 25px; width: 500px; max-width: 95%; max-height: 85vh; overflow-y: auto; }
        .modal-content h3 { margin-bottom: 20px; }
        .modal-content .form-group { margin-bottom: 15px; }
        .modal-content .form-group textarea { min-height: 80px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .checkbox-row { display: flex; align-items: center; gap: 10px; }
        .checkbox-row input[type="checkbox"] { width: 18px; height: 18px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <a href="index.php" class="back-link">⬅️ Back</a>
            <h2>🔧 Form Builder</h2>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="previewForm()">👁️ Preview</button>
            <button class="btn btn-success" onclick="saveForm()">💾 Save Form</button>
        </div>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <h3>Field Types</h3>
            <p style="font-size:0.75rem;color:#555;margin-bottom:15px;">Drag to add fields</p>
            <?php foreach ($fieldTypes as $type => $info): ?>
            <div class="field-type" draggable="true" data-type="<?= $type ?>">
                <span><?= $info['icon'] ?></span>
                <span><?= $info['name'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="main">
            <div class="form-settings">
                <div class="settings-grid">
                    <div class="form-group">
                        <label>Form Name</label>
                        <input type="text" id="formName" value="<?= htmlspecialchars($form['display_name'] ?? 'New Form') ?>">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select id="formCategory">
                            <option value="customer" <?= ($form['category'] ?? '') === 'customer' ? 'selected' : '' ?>>Customer</option>
                            <option value="support" <?= ($form['category'] ?? '') === 'support' ? 'selected' : '' ?>>Support</option>
                            <option value="payment" <?= ($form['category'] ?? '') === 'payment' ? 'selected' : '' ?>>Payment</option>
                            <option value="registration" <?= ($form['category'] ?? '') === 'registration' ? 'selected' : '' ?>>Registration</option>
                            <option value="survey" <?= ($form['category'] ?? '') === 'survey' ? 'selected' : '' ?>>Survey</option>
                            <option value="lead" <?= ($form['category'] ?? '') === 'lead' ? 'selected' : '' ?>>Lead Gen</option>
                            <option value="hr" <?= ($form['category'] ?? '') === 'hr' ? 'selected' : '' ?>>HR</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Style</label>
                        <select id="formStyle">
                            <option value="casual" <?= ($form['style'] ?? '') === 'casual' ? 'selected' : '' ?>>Casual</option>
                            <option value="business" <?= ($form['style'] ?? 'business') === 'business' ? 'selected' : '' ?>>Business</option>
                            <option value="corporate" <?= ($form['style'] ?? '') === 'corporate' ? 'selected' : '' ?>>Corporate</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Submit Button Text</label>
                        <input type="text" id="submitText" value="<?= htmlspecialchars(json_decode($form['settings'] ?? '{}', true)['submit_text'] ?? 'Submit') ?>">
                    </div>
                </div>
            </div>
            
            <div class="canvas-wrapper">
                <div class="canvas" id="formCanvas">
                    <?php if (empty($fields)): ?>
                    <div class="canvas-empty" id="emptyState">
                        <div class="icon">📋</div>
                        <h3>Drag fields here to build your form</h3>
                        <p>Or click a field type to add it</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Field Editor Modal -->
    <div class="modal" id="fieldModal">
        <div class="modal-content">
            <h3>✏️ Edit Field</h3>
            <input type="hidden" id="editFieldIndex">
            
            <div class="form-group">
                <label>Label</label>
                <input type="text" id="fieldLabel">
            </div>
            
            <div class="form-group">
                <label>Field Name (internal)</label>
                <input type="text" id="fieldName">
            </div>
            
            <div class="form-group">
                <label>Placeholder</label>
                <input type="text" id="fieldPlaceholder">
            </div>
            
            <div class="form-group" id="optionsGroup" style="display:none;">
                <label>Options (one per line)</label>
                <textarea id="fieldOptions"></textarea>
            </div>
            
            <div class="form-group" id="maxGroup" style="display:none;">
                <label>Max Value</label>
                <input type="number" id="fieldMax" value="5">
            </div>
            
            <div class="checkbox-row">
                <input type="checkbox" id="fieldRequired">
                <label for="fieldRequired">Required field</label>
            </div>
            
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeFieldModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveFieldChanges()">Save Changes</button>
            </div>
        </div>
    </div>
    
    <script>
        let formId = <?= $formId ?>;
        let fields = <?= json_encode($fields) ?>;
        
        const fieldTypes = <?= json_encode($fieldTypes) ?>;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            renderFields();
            setupDragAndDrop();
        });
        
        function setupDragAndDrop() {
            // Field types (sidebar)
            document.querySelectorAll('.field-type').forEach(el => {
                el.addEventListener('dragstart', e => {
                    e.dataTransfer.setData('fieldType', el.dataset.type);
                    el.classList.add('dragging');
                });
                el.addEventListener('dragend', e => el.classList.remove('dragging'));
                el.addEventListener('click', () => addField(el.dataset.type));
            });
            
            // Canvas
            const canvas = document.getElementById('formCanvas');
            canvas.addEventListener('dragover', e => {
                e.preventDefault();
                canvas.classList.add('dragover');
            });
            canvas.addEventListener('dragleave', () => canvas.classList.remove('dragover'));
            canvas.addEventListener('drop', e => {
                e.preventDefault();
                canvas.classList.remove('dragover');
                const type = e.dataTransfer.getData('fieldType');
                if (type) addField(type);
            });
        }
        
        function addField(type) {
            const info = fieldTypes[type];
            const field = {
                type: type,
                name: type + '_' + Date.now(),
                label: info.name,
                placeholder: '',
                required: false
            };
            
            if (type === 'select' || type === 'radio') {
                field.options = ['Option 1', 'Option 2', 'Option 3'];
            }
            if (type === 'rating') {
                field.max = 5;
            }
            
            fields.push(field);
            renderFields();
            document.getElementById('emptyState')?.remove();
        }
        
        function renderFields() {
            const canvas = document.getElementById('formCanvas');
            const empty = document.getElementById('emptyState');
            if (empty && fields.length > 0) empty.remove();
            
            // Clear existing field items
            canvas.querySelectorAll('.field-item').forEach(el => el.remove());
            
            fields.forEach((field, index) => {
                const div = document.createElement('div');
                div.className = 'field-item';
                div.draggable = true;
                div.dataset.index = index;
                
                const info = fieldTypes[field.type] || {icon: '📝', name: 'Unknown'};
                
                let preview = '';
                switch (field.type) {
                    case 'textarea':
                        preview = `<textarea placeholder="${field.placeholder || ''}" disabled></textarea>`;
                        break;
                    case 'select':
                        preview = `<select disabled><option>${(field.options || []).join('</option><option>')}</option></select>`;
                        break;
                    case 'checkbox':
                        preview = `<label style="color:#888"><input type="checkbox" disabled> ${field.label}</label>`;
                        break;
                    case 'rating':
                        preview = '⭐'.repeat(field.max || 5);
                        break;
                    default:
                        preview = `<input type="${field.type}" placeholder="${field.placeholder || ''}" disabled>`;
                }
                
                div.innerHTML = `
                    <div class="field-header">
                        <div class="field-label">
                            <span>${info.icon}</span>
                            <span>${field.label}${field.required ? '<span class="required">*</span>' : ''}</span>
                        </div>
                        <div class="field-actions">
                            <button onclick="editField(${index})">Edit</button>
                            <button class="delete" onclick="deleteField(${index})">Delete</button>
                        </div>
                    </div>
                    <div class="field-preview">${preview}</div>
                `;
                
                // Drag for reordering
                div.addEventListener('dragstart', e => {
                    e.dataTransfer.setData('fieldIndex', index);
                    div.classList.add('dragging');
                });
                div.addEventListener('dragend', () => div.classList.remove('dragging'));
                div.addEventListener('dragover', e => e.preventDefault());
                div.addEventListener('drop', e => {
                    e.preventDefault();
                    const fromIndex = parseInt(e.dataTransfer.getData('fieldIndex'));
                    if (!isNaN(fromIndex) && fromIndex !== index) {
                        const [moved] = fields.splice(fromIndex, 1);
                        fields.splice(index, 0, moved);
                        renderFields();
                    }
                });
                
                canvas.appendChild(div);
            });
        }
        
        function editField(index) {
            const field = fields[index];
            document.getElementById('editFieldIndex').value = index;
            document.getElementById('fieldLabel').value = field.label || '';
            document.getElementById('fieldName').value = field.name || '';
            document.getElementById('fieldPlaceholder').value = field.placeholder || '';
            document.getElementById('fieldRequired').checked = field.required || false;
            
            // Show/hide options
            const optionsGroup = document.getElementById('optionsGroup');
            const maxGroup = document.getElementById('maxGroup');
            optionsGroup.style.display = (field.type === 'select' || field.type === 'radio') ? 'block' : 'none';
            maxGroup.style.display = field.type === 'rating' ? 'block' : 'none';
            
            if (field.options) {
                document.getElementById('fieldOptions').value = field.options.join('\n');
            }
            if (field.max) {
                document.getElementById('fieldMax').value = field.max;
            }
            
            document.getElementById('fieldModal').classList.add('active');
        }
        
        function closeFieldModal() {
            document.getElementById('fieldModal').classList.remove('active');
        }
        
        function saveFieldChanges() {
            const index = parseInt(document.getElementById('editFieldIndex').value);
            fields[index].label = document.getElementById('fieldLabel').value;
            fields[index].name = document.getElementById('fieldName').value || fields[index].name;
            fields[index].placeholder = document.getElementById('fieldPlaceholder').value;
            fields[index].required = document.getElementById('fieldRequired').checked;
            
            if (fields[index].type === 'select' || fields[index].type === 'radio') {
                fields[index].options = document.getElementById('fieldOptions').value.split('\n').filter(o => o.trim());
            }
            if (fields[index].type === 'rating') {
                fields[index].max = parseInt(document.getElementById('fieldMax').value) || 5;
            }
            
            closeFieldModal();
            renderFields();
        }
        
        function deleteField(index) {
            if (confirm('Delete this field?')) {
                fields.splice(index, 1);
                renderFields();
                if (fields.length === 0) {
                    document.getElementById('formCanvas').innerHTML = `
                        <div class="canvas-empty" id="emptyState">
                            <div class="icon">📋</div>
                            <h3>Drag fields here to build your form</h3>
                            <p>Or click a field type to add it</p>
                        </div>
                    `;
                }
            }
        }
        
        function previewForm() {
            // Save to session and open preview
            const data = getFormData();
            localStorage.setItem('formPreview', JSON.stringify(data));
            window.open('preview.php?preview=1', '_blank');
        }
        
        function getFormData() {
            return {
                id: formId,
                display_name: document.getElementById('formName').value,
                category: document.getElementById('formCategory').value,
                style: document.getElementById('formStyle').value,
                fields: fields,
                settings: {
                    submit_text: document.getElementById('submitText').value
                }
            };
        }
        
        function saveForm() {
            const data = getFormData();
            
            fetch('api/forms.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    alert('Form saved!');
                    if (!formId && result.form_id) {
                        formId = result.form_id;
                        history.replaceState(null, '', '?id=' + formId);
                    }
                } else {
                    alert(result.error || 'Failed to save');
                }
            })
            .catch(err => alert('Error: ' + err.message));
        }
    </script>
</body>
</html>
