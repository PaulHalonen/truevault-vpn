<?php
/**
 * TrueVault VPN - Relationship Builder
 * Part 13 - Task 13.5
 * Visual relationship builder between tables
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_BUILDER', DB_PATH . 'builder.db');

$db = new SQLite3(DB_BUILDER);
$db->enableExceptions(true);

// Get all tables
$result = $db->query("SELECT * FROM custom_tables WHERE status = 'active' ORDER BY display_name");
$tables = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    // Get fields for each table
    $stmt = $db->prepare("SELECT * FROM custom_fields WHERE table_id = ? ORDER BY sort_order");
    $stmt->bindValue(1, $row['id'], SQLITE3_INTEGER);
    $fieldsResult = $stmt->execute();
    $row['fields'] = [];
    while ($field = $fieldsResult->fetchArray(SQLITE3_ASSOC)) {
        $row['fields'][] = $field;
    }
    $tables[] = $row;
}

// Get existing relationships
$result = $db->query("SELECT r.*, pt.display_name as parent_name, ct.display_name as child_name 
    FROM table_relationships r 
    JOIN custom_tables pt ON r.parent_table_id = pt.id 
    JOIN custom_tables ct ON r.child_table_id = ct.id");
$relationships = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $relationships[] = $row;
}

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relationship Builder - Database Builder</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .back-link { color: #00d9ff; text-decoration: none; }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .section { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .section h3 { margin-bottom: 20px; font-size: 1rem; color: #888; display: flex; justify-content: space-between; align-items: center; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-danger { background: rgba(255,80,80,0.2); color: #ff5050; }
        .btn:hover { transform: translateY(-2px); }
        .diagram { background: rgba(0,0,0,0.3); border-radius: 12px; padding: 30px; min-height: 400px; position: relative; }
        .table-box { background: rgba(255,255,255,0.05); border: 2px solid #3b82f6; border-radius: 10px; padding: 15px; width: 180px; position: absolute; }
        .table-box h4 { font-size: 0.9rem; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .table-box .field { font-size: 0.75rem; color: #888; padding: 3px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .table-box .field:last-child { border: none; }
        .relationship-line { stroke: #00d9ff; stroke-width: 2; fill: none; }
        .rel-list { list-style: none; }
        .rel-item { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 15px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .rel-info { flex: 1; }
        .rel-info .type { color: #00d9ff; font-size: 0.85rem; }
        .empty { text-align: center; padding: 40px; color: #555; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1a2e; border-radius: 12px; padding: 30px; width: 500px; max-width: 90%; }
        .modal-content h3 { margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #aaa; }
        .form-group select, .form-group input { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; }
        .radio-group { display: flex; flex-direction: column; gap: 10px; }
        .radio-group label { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 8px; }
        .radio-group label:hover { background: rgba(255,255,255,0.08); }
        .radio-group input[type="radio"] { width: 18px; height: 18px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin-top: 15px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px; }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="back-link">⬅️ Back to Dashboard</a>
        <h2>🔗 Relationship Builder</h2>
        <div></div>
    </div>
    
    <div class="container">
        <div class="section">
            <h3>
                📊 VISUAL DIAGRAM
                <span style="font-size: 0.8rem; color: #555;">Drag tables to arrange</span>
            </h3>
            <div class="diagram" id="diagram">
                <?php 
                $x = 50; $y = 50;
                foreach ($tables as $i => $table): 
                    $posX = $x + ($i % 3) * 220;
                    $posY = $y + floor($i / 3) * 180;
                ?>
                <div class="table-box" style="left: <?= $posX ?>px; top: <?= $posY ?>px;" data-id="<?= $table['id'] ?>">
                    <h4><?= $table['icon'] ?> <?= htmlspecialchars($table['display_name']) ?></h4>
                    <?php foreach ($table['fields'] as $field): ?>
                    <div class="field"><?= htmlspecialchars($field['display_name']) ?> (<?= $field['field_type'] ?>)</div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($tables)): ?>
                <div class="empty">
                    <p>No tables yet. Create tables first to build relationships.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h3>
                🔗 RELATIONSHIPS (<?= count($relationships) ?>)
                <button class="btn btn-primary" onclick="openModal()">+ Add Relationship</button>
            </h3>
            
            <?php if (empty($relationships)): ?>
            <div class="empty">
                <p>No relationships defined yet.</p>
            </div>
            <?php else: ?>
            <ul class="rel-list">
                <?php foreach ($relationships as $rel): ?>
                <li class="rel-item">
                    <div class="rel-info">
                        <strong><?= htmlspecialchars($rel['parent_name']) ?></strong>
                        <span class="type">→ <?= strtoupper(str_replace('_', ' ', $rel['relationship_type'])) ?> →</span>
                        <strong><?= htmlspecialchars($rel['child_name']) ?></strong>
                        <div style="font-size: 0.8rem; color: #666; margin-top: 5px;">
                            <?= $rel['parent_field'] ?> → <?= $rel['child_field'] ?>
                            <?= $rel['cascade_delete'] ? '(cascade delete)' : '' ?>
                        </div>
                    </div>
                    <button class="btn btn-danger" onclick="deleteRelationship(<?= $rel['id'] ?>)">Delete</button>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Relationship Modal -->
    <div class="modal" id="relationshipModal">
        <div class="modal-content">
            <h3>🔗 Create Relationship</h3>
            <form id="relationshipForm" action="api/relationships.php" method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label>Parent Table</label>
                    <select name="parent_table_id" id="parentTable" required onchange="loadFields('parent')">
                        <option value="">Select table...</option>
                        <?php foreach ($tables as $table): ?>
                        <option value="<?= $table['id'] ?>" data-fields='<?= json_encode($table['fields']) ?>'><?= htmlspecialchars($table['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Child Table</label>
                    <select name="child_table_id" id="childTable" required onchange="loadFields('child')">
                        <option value="">Select table...</option>
                        <?php foreach ($tables as $table): ?>
                        <option value="<?= $table['id'] ?>" data-fields='<?= json_encode($table['fields']) ?>'><?= htmlspecialchars($table['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Relationship Type</label>
                    <div class="radio-group">
                        <label><input type="radio" name="relationship_type" value="one_to_one"> ONE-TO-ONE (e.g., User has one Profile)</label>
                        <label><input type="radio" name="relationship_type" value="one_to_many" checked> ONE-TO-MANY (e.g., Customer has many Orders)</label>
                        <label><input type="radio" name="relationship_type" value="many_to_many"> MANY-TO-MANY (e.g., Students and Courses)</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Parent Field</label>
                    <select name="parent_field" id="parentField" required>
                        <option value="id">id (primary key)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Child Field</label>
                    <select name="child_field" id="childField" required>
                        <option value="">Select parent table first...</option>
                    </select>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="cascade_delete" id="cascadeDelete">
                    <label for="cascadeDelete">Delete children when parent is deleted</label>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Relationship</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('relationshipModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('relationshipModal').classList.remove('active');
        }
        
        function loadFields(type) {
            const select = document.getElementById(type === 'parent' ? 'parentTable' : 'childTable');
            const option = select.options[select.selectedIndex];
            const fields = JSON.parse(option.dataset.fields || '[]');
            
            const fieldSelect = document.getElementById(type === 'parent' ? 'parentField' : 'childField');
            fieldSelect.innerHTML = '<option value="id">id (primary key)</option>';
            
            fields.forEach(f => {
                fieldSelect.innerHTML += `<option value="${f.field_name}">${f.display_name}</option>`;
            });
        }
        
        function deleteRelationship(id) {
            if (confirm('Delete this relationship?')) {
                fetch('api/relationships.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({relationship_id: id})
                }).then(r => r.json()).then(data => {
                    if (data.success) location.reload();
                    else alert(data.error || 'Failed to delete');
                });
            }
        }
        
        // Make table boxes draggable
        document.querySelectorAll('.table-box').forEach(box => {
            box.style.cursor = 'move';
            let isDragging = false, startX, startY, startLeft, startTop;
            
            box.addEventListener('mousedown', e => {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                startLeft = parseInt(box.style.left);
                startTop = parseInt(box.style.top);
            });
            
            document.addEventListener('mousemove', e => {
                if (!isDragging) return;
                box.style.left = (startLeft + e.clientX - startX) + 'px';
                box.style.top = (startTop + e.clientY - startY) + 'px';
            });
            
            document.addEventListener('mouseup', () => isDragging = false);
        });
    </script>
</body>
</html>
