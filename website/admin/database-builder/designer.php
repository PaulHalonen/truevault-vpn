<?php
/**
 * TrueVault VPN - Table Designer Interface
 * Part 13 - Task 13.3
 * Edit table structure with drag-and-drop fields
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_BUILDER', DB_PATH . 'builder.db');

$tableId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$isNew = isset($_GET['new']) && $_GET['new'] === '1';

$db = new SQLite3(DB_BUILDER);
$db->enableExceptions(true);

$table = null;
$fields = [];

if ($tableId > 0) {
    $stmt = $db->prepare("SELECT * FROM custom_tables WHERE id = ?");
    $stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $table = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($table) {
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
    <title><?= $isNew ? 'Create New Table' : 'Edit Table' ?> - Database Builder</title>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .back-link { color: #00d9ff; text-decoration: none; display: flex; align-items: center; gap: 5px; }
        .back-link:hover { text-decoration: underline; }
        .container { max-width: 1000px; margin: 0 auto; padding: 30px; }
        .section { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .section h3 { margin-bottom: 20px; font-size: 1rem; color: #888; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #aaa; font-size: 0.9rem; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; font-size: 1rem; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #00d9ff; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .icon-picker { display: flex; gap: 10px; flex-wrap: wrap; }
        .icon-picker .icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05); border: 2px solid transparent; border-radius: 8px; cursor: pointer; font-size: 1.2rem; }
        .icon-picker .icon.selected { border-color: #00d9ff; background: rgba(0,217,255,0.1); }
        .icon-picker .icon:hover { background: rgba(255,255,255,0.1); }
        .color-picker { display: flex; gap: 10px; }
        .color-picker input[type="color"] { width: 50px; height: 40px; border: none; cursor: pointer; border-radius: 8px; }
        .fields-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .fields-header h3 { margin: 0; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .btn-danger { background: rgba(255,80,80,0.2); color: #ff5050; border: 1px solid rgba(255,80,80,0.3); }
        .btn:hover { transform: translateY(-2px); }
        .field-list { list-style: none; }
        .field-item { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 15px; margin-bottom: 10px; display: flex; align-items: center; gap: 15px; cursor: grab; }
        .field-item:hover { border-color: #00d9ff; }
        .field-item .drag-handle { color: #555; font-size: 1.2rem; }
        .field-item .field-info { flex: 1; }
        .field-item .field-name { font-weight: 600; }
        .field-item .field-type { color: #00d9ff; font-size: 0.85rem; }
        .field-item .field-badges { display: flex; gap: 5px; margin-top: 5px; }
        .field-item .badge { padding: 2px 8px; background: rgba(255,255,255,0.1); border-radius: 4px; font-size: 0.75rem; color: #888; }
        .field-item .badge.required { background: rgba(255,107,107,0.2); color: #ff6b6b; }
        .field-item .badge.unique { background: rgba(0,217,255,0.2); color: #00d9ff; }
        .field-item .field-actions { display: flex; gap: 8px; }
        .field-item .field-actions button { padding: 6px 12px; font-size: 0.8rem; }
        .empty-fields { text-align: center; padding: 40px; color: #555; }
        .empty-fields .icon { font-size: 3rem; margin-bottom: 15px; }
        .actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="back-link">⬅️ Back to Dashboard</a>
        <h2><?= $isNew ? '✨ Create New Table' : '🔧 Edit Table Structure' ?></h2>
        <div></div>
    </div>
    
    <div class="container">
        <form id="tableForm" method="POST" action="api/tables.php">
            <input type="hidden" name="action" value="<?= $isNew ? 'create' : 'update' ?>">
            <input type="hidden" name="table_id" value="<?= $tableId ?>">
            
            <div class="section">
                <h3>📋 BASIC INFO</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Display Name *</label>
                        <input type="text" name="display_name" required placeholder="e.g., Customer Records" value="<?= htmlspecialchars($table['display_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Internal Name (auto-generated)</label>
                        <input type="text" name="table_name" readonly placeholder="customer_records" value="<?= htmlspecialchars($table['table_name'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="2" placeholder="What is this table for?"><?= htmlspecialchars($table['description'] ?? '') ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Icon</label>
                        <div class="icon-picker">
                            <?php $icons = ['📋','📊','👥','📧','💰','📦','🎫','📝','⚙️','🔧','🛡️','📱']; ?>
                            <?php foreach ($icons as $icon): ?>
                            <div class="icon <?= ($table['icon'] ?? '📋') === $icon ? 'selected' : '' ?>" data-icon="<?= $icon ?>"><?= $icon ?></div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="icon" value="<?= htmlspecialchars($table['icon'] ?? '📋') ?>">
                    </div>
                    <div class="form-group">
                        <label>Color</label>
                        <div class="color-picker">
                            <input type="color" name="color" value="<?= htmlspecialchars($table['color'] ?? '#3b82f6') ?>">
                            <input type="text" name="color_text" value="<?= htmlspecialchars($table['color'] ?? '#3b82f6') ?>" style="width: 100px;">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <div class="fields-header">
                    <h3>📝 FIELDS (<?= count($fields) ?>)</h3>
                    <button type="button" class="btn btn-primary" onclick="openFieldEditor()">+ Add Field</button>
                </div>
                
                <?php if (empty($fields)): ?>
                <div class="empty-fields">
                    <div class="icon">📝</div>
                    <p>No fields yet. Add your first field to get started!</p>
                </div>
                <?php else: ?>
                <ul class="field-list" id="fieldList">
                    <?php foreach ($fields as $field): ?>
                    <li class="field-item" data-id="<?= $field['id'] ?>">
                        <span class="drag-handle">☰</span>
                        <div class="field-info">
                            <div class="field-name"><?= htmlspecialchars($field['display_name']) ?></div>
                            <div class="field-type"><?= strtoupper($field['field_type']) ?></div>
                            <div class="field-badges">
                                <?php if ($field['is_required']): ?><span class="badge required">Required</span><?php endif; ?>
                                <?php if ($field['is_unique']): ?><span class="badge unique">Unique</span><?php endif; ?>
                            </div>
                        </div>
                        <div class="field-actions">
                            <button type="button" class="btn btn-secondary" onclick="editField(<?= $field['id'] ?>)">Edit</button>
                            <button type="button" class="btn btn-danger" onclick="deleteField(<?= $field['id'] ?>)">Delete</button>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            
            <div class="actions">
                <button type="button" class="btn btn-secondary" onclick="location.href='index.php'">Cancel</button>
                <button type="button" class="btn btn-secondary" onclick="previewTable()">Preview Table</button>
                <button type="submit" class="btn btn-primary">💾 Save Structure</button>
            </div>
        </form>
    </div>
    
    <script>
        // Icon picker
        document.querySelectorAll('.icon-picker .icon').forEach(icon => {
            icon.addEventListener('click', () => {
                document.querySelectorAll('.icon-picker .icon').forEach(i => i.classList.remove('selected'));
                icon.classList.add('selected');
                document.querySelector('input[name="icon"]').value = icon.dataset.icon;
            });
        });
        
        // Auto-generate internal name
        document.querySelector('input[name="display_name"]').addEventListener('input', function() {
            const internal = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
            document.querySelector('input[name="table_name"]').value = internal;
        });
        
        // Color picker sync
        document.querySelector('input[type="color"]').addEventListener('input', function() {
            document.querySelector('input[name="color_text"]').value = this.value;
        });
        document.querySelector('input[name="color_text"]').addEventListener('input', function() {
            document.querySelector('input[type="color"]').value = this.value;
        });
        
        function openFieldEditor(fieldId = null) {
            window.location.href = 'field-editor.php?table_id=<?= $tableId ?>' + (fieldId ? '&field_id=' + fieldId : '');
        }
        
        function editField(fieldId) {
            openFieldEditor(fieldId);
        }
        
        function deleteField(fieldId) {
            if (confirm('Delete this field? This cannot be undone.')) {
                fetch('api/fields.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({field_id: fieldId})
                }).then(r => r.json()).then(data => {
                    if (data.success) location.reload();
                    else alert(data.error || 'Failed to delete');
                });
            }
        }
        
        function previewTable() {
            window.open('view-data.php?table=<?= $tableId ?>', '_blank');
        }
        
        // Initialize drag-drop sorting
        <?php if (!empty($fields)): ?>
        new Sortable(document.getElementById('fieldList'), {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function() {
                const order = Array.from(document.querySelectorAll('.field-item')).map(el => el.dataset.id);
                fetch('api/fields.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'reorder', order: order})
                });
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
