<?php
/**
 * TrueVault VPN - Data Management Interface
 * Part 13 - Task 13.6
 * Spreadsheet-like view for CRUD operations
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_BUILDER', DB_PATH . 'builder.db');

$tableId = isset($_GET['table']) ? intval($_GET['table']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 50;
$search = $_GET['search'] ?? '';

if (!$tableId) {
    header('Location: index.php');
    exit;
}

$db = new SQLite3(DB_BUILDER);
$db->enableExceptions(true);

// Get table info
$stmt = $db->prepare("SELECT * FROM custom_tables WHERE id = ?");
$stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
$result = $stmt->execute();
$table = $result->fetchArray(SQLITE3_ASSOC);

if (!$table) {
    header('Location: index.php');
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

// Get records from data table
$dataTableName = 'data_' . $table['table_name'];
$records = [];
$totalRecords = 0;

try {
    // Count total
    $countResult = $db->querySingle("SELECT COUNT(*) FROM {$dataTableName}");
    $totalRecords = intval($countResult);
    
    // Get paginated records
    $offset = ($page - 1) * $perPage;
    $result = $db->query("SELECT * FROM {$dataTableName} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $records[] = $row;
    }
} catch (Exception $e) {
    // Table might not exist yet
}

$totalPages = ceil($totalRecords / $perPage);
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($table['display_name']) ?> - Data Manager</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .back-link { color: #00d9ff; text-decoration: none; }
        .header h2 { display: flex; align-items: center; gap: 10px; }
        .header-actions { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-danger { background: rgba(255,80,80,0.2); color: #ff5050; }
        .btn:hover { transform: translateY(-2px); }
        .container { padding: 20px 30px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .search-box { display: flex; gap: 10px; }
        .search-box input { padding: 10px 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; width: 300px; }
        .search-box input:focus { outline: none; border-color: #00d9ff; }
        .bulk-actions { display: flex; gap: 10px; align-items: center; }
        .bulk-actions span { color: #888; font-size: 0.9rem; }
        .data-table { width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.02); border-radius: 12px; overflow: hidden; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .data-table th { background: rgba(255,255,255,0.05); color: #888; font-size: 0.85rem; text-transform: uppercase; cursor: pointer; }
        .data-table th:hover { color: #00d9ff; }
        .data-table tr:hover { background: rgba(255,255,255,0.03); }
        .data-table td { font-size: 0.9rem; }
        .data-table td.editable { cursor: text; }
        .data-table td.editable:hover { background: rgba(0,217,255,0.1); }
        .data-table input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .data-table .actions { display: flex; gap: 5px; }
        .data-table .actions button { padding: 5px 10px; font-size: 0.8rem; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px; }
        .pagination button { padding: 8px 15px; }
        .pagination span { color: #888; }
        .empty { text-align: center; padding: 60px 20px; color: #555; }
        .empty .icon { font-size: 4rem; margin-bottom: 15px; }
        .record-count { color: #888; font-size: 0.9rem; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1a2e; border-radius: 12px; padding: 30px; width: 600px; max-width: 90%; max-height: 80vh; overflow-y: auto; }
        .modal-content h3 { margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #aaa; font-size: 0.9rem; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; }
        .form-group .help { font-size: 0.8rem; color: #666; margin-top: 5px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px; }
        .inline-edit { background: rgba(255,255,255,0.05); border: 1px solid #00d9ff; padding: 8px; border-radius: 4px; color: #fff; width: 100%; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <a href="index.php" class="back-link">⬅️ Back</a>
            <h2><?= $table['icon'] ?> <?= htmlspecialchars($table['display_name']) ?></h2>
            <span class="record-count"><?= $totalRecords ?> records</span>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="exportData('csv')">📥 Export CSV</button>
            <button class="btn btn-secondary" onclick="location.href='import-export.php?table=<?= $tableId ?>'">📤 Import</button>
            <button class="btn btn-primary" onclick="openAddModal()">+ Add Record</button>
        </div>
    </div>
    
    <div class="container">
        <div class="toolbar">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search records..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-secondary" onclick="searchRecords()">Search</button>
            </div>
            <div class="bulk-actions">
                <span id="selectedCount">0 selected</span>
                <button class="btn btn-secondary" onclick="bulkEdit()" disabled id="bulkEditBtn">✏️ Bulk Edit</button>
                <button class="btn btn-danger" onclick="bulkDelete()" disabled id="bulkDeleteBtn">🗑️ Delete</button>
            </div>
        </div>
        
        <?php if (empty($records) && empty($fields)): ?>
        <div class="empty">
            <div class="icon">📋</div>
            <h3>No Fields Defined</h3>
            <p>Add fields to this table first before adding records.</p>
            <button class="btn btn-primary" onclick="location.href='designer.php?id=<?= $tableId ?>'">Edit Table Structure</button>
        </div>
        <?php elseif (empty($records)): ?>
        <div class="empty">
            <div class="icon">📭</div>
            <h3>No Records Yet</h3>
            <p>Add your first record to get started.</p>
            <button class="btn btn-primary" onclick="openAddModal()">+ Add First Record</button>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:40px"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                    <th style="width:60px">ID</th>
                    <?php foreach ($fields as $field): ?>
                    <th onclick="sortBy('<?= $field['field_name'] ?>')"><?= htmlspecialchars($field['display_name']) ?> ↕</th>
                    <?php endforeach; ?>
                    <th style="width:120px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                <tr data-id="<?= $record['id'] ?>">
                    <td><input type="checkbox" class="row-select" value="<?= $record['id'] ?>" onchange="updateSelection()"></td>
                    <td><?= $record['id'] ?></td>
                    <?php foreach ($fields as $field): ?>
                    <td class="editable" data-field="<?= $field['field_name'] ?>" ondblclick="inlineEdit(this)"><?= htmlspecialchars($record[$field['field_name']] ?? '') ?></td>
                    <?php endforeach; ?>
                    <td class="actions">
                        <button class="btn btn-secondary" onclick="editRecord(<?= $record['id'] ?>)">Edit</button>
                        <button class="btn btn-danger" onclick="deleteRecord(<?= $record['id'] ?>)">🗑️</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <button class="btn btn-secondary" <?= $page <= 1 ? 'disabled' : '' ?> onclick="goToPage(<?= $page - 1 ?>)">◄ Prev</button>
            <span>Page <?= $page ?> of <?= $totalPages ?></span>
            <button class="btn btn-secondary" <?= $page >= $totalPages ? 'disabled' : '' ?> onclick="goToPage(<?= $page + 1 ?>)">Next ►</button>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Add/Edit Record Modal -->
    <div class="modal" id="recordModal">
        <div class="modal-content">
            <h3 id="modalTitle">➕ Add New Record</h3>
            <form id="recordForm">
                <input type="hidden" name="table_id" value="<?= $tableId ?>">
                <input type="hidden" name="record_id" id="recordId" value="">
                
                <?php foreach ($fields as $field): ?>
                <div class="form-group">
                    <label><?= htmlspecialchars($field['display_name']) ?> <?= $field['is_required'] ? '*' : '' ?></label>
                    <?php if ($field['field_type'] === 'textarea'): ?>
                    <textarea name="<?= $field['field_name'] ?>" rows="3" placeholder="<?= htmlspecialchars($field['placeholder'] ?? '') ?>" <?= $field['is_required'] ? 'required' : '' ?>></textarea>
                    <?php elseif ($field['field_type'] === 'dropdown'): ?>
                    <select name="<?= $field['field_name'] ?>" <?= $field['is_required'] ? 'required' : '' ?>>
                        <option value="">Select...</option>
                        <?php foreach (explode("\n", $field['options'] ?? '') as $opt): ?>
                        <option value="<?= htmlspecialchars(trim($opt)) ?>"><?= htmlspecialchars(trim($opt)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php elseif ($field['field_type'] === 'checkbox'): ?>
                    <input type="checkbox" name="<?= $field['field_name'] ?>" value="1" style="width:auto">
                    <?php else: ?>
                    <input type="<?= $field['field_type'] === 'email' ? 'email' : ($field['field_type'] === 'number' || $field['field_type'] === 'currency' ? 'number' : ($field['field_type'] === 'date' ? 'date' : 'text')) ?>" name="<?= $field['field_name'] ?>" placeholder="<?= htmlspecialchars($field['placeholder'] ?? '') ?>" <?= $field['is_required'] ? 'required' : '' ?>>
                    <?php endif; ?>
                    <?php if ($field['help_text']): ?>
                    <div class="help"><?= htmlspecialchars($field['help_text']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">💾 Save Record</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const tableId = <?= $tableId ?>;
        const tableName = '<?= $table['table_name'] ?>';
        
        function openAddModal() {
            document.getElementById('modalTitle').textContent = '➕ Add New Record';
            document.getElementById('recordId').value = '';
            document.getElementById('recordForm').reset();
            document.getElementById('recordModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('recordModal').classList.remove('active');
        }
        
        function editRecord(id) {
            fetch(`api/data.php?table_id=${tableId}&record_id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.record) {
                        document.getElementById('modalTitle').textContent = '✏️ Edit Record';
                        document.getElementById('recordId').value = id;
                        const form = document.getElementById('recordForm');
                        Object.keys(data.record).forEach(key => {
                            const field = form.querySelector(`[name="${key}"]`);
                            if (field) {
                                if (field.type === 'checkbox') field.checked = !!data.record[key];
                                else field.value = data.record[key] || '';
                            }
                        });
                        document.getElementById('recordModal').classList.add('active');
                    }
                });
        }
        
        function deleteRecord(id) {
            if (confirm('Delete this record? This cannot be undone.')) {
                fetch('api/data.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({table_id: tableId, record_id: id})
                }).then(r => r.json()).then(data => {
                    if (data.success) location.reload();
                    else alert(data.error || 'Failed to delete');
                });
            }
        }
        
        document.getElementById('recordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {table_id: tableId, record_id: document.getElementById('recordId').value};
            formData.forEach((v, k) => data[k] = v);
            
            fetch('api/data.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            }).then(r => r.json()).then(result => {
                if (result.success) location.reload();
                else alert(result.error || 'Failed to save');
            });
        });
        
        function toggleSelectAll() {
            const checked = document.getElementById('selectAll').checked;
            document.querySelectorAll('.row-select').forEach(cb => cb.checked = checked);
            updateSelection();
        }
        
        function updateSelection() {
            const selected = document.querySelectorAll('.row-select:checked').length;
            document.getElementById('selectedCount').textContent = selected + ' selected';
            document.getElementById('bulkEditBtn').disabled = selected === 0;
            document.getElementById('bulkDeleteBtn').disabled = selected === 0;
        }
        
        function bulkDelete() {
            const ids = Array.from(document.querySelectorAll('.row-select:checked')).map(cb => cb.value);
            if (confirm(`Delete ${ids.length} records? This cannot be undone.`)) {
                fetch('api/data.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({table_id: tableId, record_ids: ids})
                }).then(r => r.json()).then(data => {
                    if (data.success) location.reload();
                    else alert(data.error || 'Failed to delete');
                });
            }
        }
        
        function inlineEdit(cell) {
            if (cell.querySelector('input')) return;
            const value = cell.textContent;
            const field = cell.dataset.field;
            const rowId = cell.parentElement.dataset.id;
            
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'inline-edit';
            input.value = value;
            cell.textContent = '';
            cell.appendChild(input);
            input.focus();
            
            input.addEventListener('blur', () => saveInlineEdit(cell, field, rowId, input.value));
            input.addEventListener('keypress', e => { if (e.key === 'Enter') input.blur(); });
        }
        
        function saveInlineEdit(cell, field, rowId, value) {
            fetch('api/data.php', {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({table_id: tableId, record_id: rowId, field: field, value: value})
            }).then(r => r.json()).then(data => {
                cell.textContent = value;
                if (!data.success) alert('Failed to save');
            });
        }
        
        function exportData(format) {
            window.location.href = `api/export.php?table_id=${tableId}&format=${format}`;
        }
        
        function goToPage(page) {
            window.location.href = `?table=${tableId}&page=${page}`;
        }
        
        function searchRecords() {
            const search = document.getElementById('searchInput').value;
            window.location.href = `?table=${tableId}&search=${encodeURIComponent(search)}`;
        }
        
        function sortBy(field) {
            const url = new URL(window.location);
            url.searchParams.set('sort', field);
            url.searchParams.set('order', url.searchParams.get('order') === 'asc' ? 'desc' : 'asc');
            window.location = url;
        }
    </script>
</body>
</html>
