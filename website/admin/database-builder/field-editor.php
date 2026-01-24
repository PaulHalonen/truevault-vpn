<?php
/**
 * TrueVault VPN - Field Editor Modal/Page
 * Part 13 - Task 13.4
 * Add/edit field with 15 field types
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_BUILDER', DB_PATH . 'builder.db');

$tableId = isset($_GET['table_id']) ? intval($_GET['table_id']) : 0;
$fieldId = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;

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

// Get field if editing
$field = null;
if ($fieldId > 0) {
    $stmt = $db->prepare("SELECT * FROM custom_fields WHERE id = ?");
    $stmt->bindValue(1, $fieldId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $field = $result->fetchArray(SQLITE3_ASSOC);
}
$db->close();

// Field types with their settings
$fieldTypes = [
    'text' => ['name' => 'Text', 'icon' => '📝', 'desc' => 'Single line text'],
    'textarea' => ['name' => 'Textarea', 'icon' => '📄', 'desc' => 'Multi-line text'],
    'number' => ['name' => 'Number', 'icon' => '🔢', 'desc' => 'Integer or decimal'],
    'currency' => ['name' => 'Currency', 'icon' => '💰', 'desc' => 'Money values'],
    'date' => ['name' => 'Date/Time', 'icon' => '📅', 'desc' => 'Dates and timestamps'],
    'email' => ['name' => 'Email', 'icon' => '📧', 'desc' => 'Email addresses'],
    'phone' => ['name' => 'Phone', 'icon' => '📞', 'desc' => 'Phone numbers'],
    'url' => ['name' => 'URL', 'icon' => '🔗', 'desc' => 'Website addresses'],
    'dropdown' => ['name' => 'Dropdown', 'icon' => '📋', 'desc' => 'Select one from list'],
    'checkbox' => ['name' => 'Checkbox', 'icon' => '☑️', 'desc' => 'Yes/No toggle'],
    'radio' => ['name' => 'Radio', 'icon' => '🔘', 'desc' => 'Choose one option'],
    'file' => ['name' => 'File', 'icon' => '📎', 'desc' => 'File upload'],
    'rating' => ['name' => 'Rating', 'icon' => '⭐', 'desc' => 'Star ratings'],
    'color' => ['name' => 'Color', 'icon' => '🎨', 'desc' => 'Color picker'],
    'signature' => ['name' => 'Signature', 'icon' => '✍️', 'desc' => 'Electronic signature'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $fieldId ? 'Edit Field' : 'Add Field' ?> - Database Builder</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .back-link { color: #00d9ff; text-decoration: none; }
        .container { max-width: 800px; margin: 0 auto; padding: 30px; }
        .section { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .section h3 { margin-bottom: 20px; font-size: 1rem; color: #888; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #aaa; font-size: 0.9rem; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; font-size: 1rem; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #00d9ff; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; }
        .type-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; }
        .type-card { background: rgba(255,255,255,0.03); border: 2px solid transparent; border-radius: 10px; padding: 15px; text-align: center; cursor: pointer; transition: all 0.2s; }
        .type-card:hover { background: rgba(255,255,255,0.08); }
        .type-card.selected { border-color: #00d9ff; background: rgba(0,217,255,0.1); }
        .type-card .icon { font-size: 1.8rem; margin-bottom: 8px; }
        .type-card .name { font-size: 0.85rem; font-weight: 600; }
        .type-card .desc { font-size: 0.7rem; color: #666; margin-top: 4px; }
        .btn { padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 1rem; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn:hover { transform: translateY(-2px); }
        .actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
        .options-editor { margin-top: 15px; }
        .options-editor .option-row { display: flex; gap: 10px; margin-bottom: 8px; }
        .options-editor .option-row input { flex: 1; }
        .options-editor .option-row button { padding: 8px 12px; }
        .help-text { font-size: 0.8rem; color: #666; margin-top: 5px; }
        #typeSpecificOptions { display: none; }
    </style>
</head>
<body>
    <div class="header">
        <a href="designer.php?id=<?= $tableId ?>" class="back-link">⬅️ Back to <?= htmlspecialchars($table['display_name'] ?? 'Table') ?></a>
        <h2><?= $fieldId ? '✏️ Edit Field' : '➕ Add New Field' ?></h2>
        <div></div>
    </div>
    
    <div class="container">
        <form id="fieldForm" method="POST" action="api/fields.php">
            <input type="hidden" name="action" value="<?= $fieldId ? 'update' : 'create' ?>">
            <input type="hidden" name="table_id" value="<?= $tableId ?>">
            <input type="hidden" name="field_id" value="<?= $fieldId ?>">
            
            <div class="section">
                <h3>🎯 FIELD TYPE</h3>
                <div class="type-grid">
                    <?php foreach ($fieldTypes as $type => $info): ?>
                    <div class="type-card <?= ($field['field_type'] ?? '') === $type ? 'selected' : '' ?>" data-type="<?= $type ?>" onclick="selectType('<?= $type ?>')">
                        <div class="icon"><?= $info['icon'] ?></div>
                        <div class="name"><?= $info['name'] ?></div>
                        <div class="desc"><?= $info['desc'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="field_type" id="fieldType" value="<?= htmlspecialchars($field['field_type'] ?? 'text') ?>">
            </div>
            
            <div class="section">
                <h3>📝 FIELD DETAILS</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Display Name *</label>
                        <input type="text" name="display_name" required placeholder="e.g., Email Address" value="<?= htmlspecialchars($field['display_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Internal Name (auto)</label>
                        <input type="text" name="field_name" readonly placeholder="email_address" value="<?= htmlspecialchars($field['field_name'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Placeholder</label>
                        <input type="text" name="placeholder" placeholder="e.g., Enter your email" value="<?= htmlspecialchars($field['placeholder'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Default Value</label>
                        <input type="text" name="default_value" placeholder="Leave empty for no default" value="<?= htmlspecialchars($field['default_value'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Help Text</label>
                    <input type="text" name="help_text" placeholder="Additional instructions for users" value="<?= htmlspecialchars($field['help_text'] ?? '') ?>">
                </div>
            </div>
            
            <div class="section">
                <h3>⚙️ VALIDATION</h3>
                <div class="form-row">
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_required" id="isRequired" <?= ($field['is_required'] ?? 0) ? 'checked' : '' ?>>
                            <label for="isRequired">Required field</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_unique" id="isUnique" <?= ($field['is_unique'] ?? 0) ? 'checked' : '' ?>>
                            <label for="isUnique">Must be unique</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="section" id="typeSpecificOptions">
                <h3>🔧 TYPE-SPECIFIC OPTIONS</h3>
                <div id="dropdownOptions" style="display:none;">
                    <label>Dropdown Options (one per line)</label>
                    <textarea name="options" rows="5" placeholder="Option 1&#10;Option 2&#10;Option 3"><?= htmlspecialchars($field['options'] ?? '') ?></textarea>
                </div>
                <div id="radioOptions" style="display:none;">
                    <label>Radio Options (one per line)</label>
                    <textarea name="radio_options" rows="5" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                </div>
                <div id="numberOptions" style="display:none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Minimum Value</label>
                            <input type="number" name="min_value" placeholder="No minimum">
                        </div>
                        <div class="form-group">
                            <label>Maximum Value</label>
                            <input type="number" name="max_value" placeholder="No maximum">
                        </div>
                    </div>
                </div>
                <div id="ratingOptions" style="display:none;">
                    <div class="form-group">
                        <label>Maximum Stars</label>
                        <select name="max_stars">
                            <option value="5">5 Stars</option>
                            <option value="10">10 Stars</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="actions">
                <button type="button" class="btn btn-secondary" onclick="location.href='designer.php?id=<?= $tableId ?>'">Cancel</button>
                <button type="submit" class="btn btn-primary">💾 Save Field</button>
            </div>
        </form>
    </div>
    
    <script>
        function selectType(type) {
            document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
            document.querySelector(`.type-card[data-type="${type}"]`).classList.add('selected');
            document.getElementById('fieldType').value = type;
            
            // Show/hide type-specific options
            const optionsSection = document.getElementById('typeSpecificOptions');
            document.querySelectorAll('#typeSpecificOptions > div').forEach(d => d.style.display = 'none');
            
            if (['dropdown', 'radio', 'number', 'rating'].includes(type)) {
                optionsSection.style.display = 'block';
                if (type === 'dropdown') document.getElementById('dropdownOptions').style.display = 'block';
                if (type === 'radio') document.getElementById('radioOptions').style.display = 'block';
                if (type === 'number' || type === 'currency') document.getElementById('numberOptions').style.display = 'block';
                if (type === 'rating') document.getElementById('ratingOptions').style.display = 'block';
            } else {
                optionsSection.style.display = 'none';
            }
        }
        
        // Auto-generate internal name
        document.querySelector('input[name="display_name"]').addEventListener('input', function() {
            const internal = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
            document.querySelector('input[name="field_name"]').value = internal;
        });
        
        // Initialize type selection
        selectType('<?= $field['field_type'] ?? 'text' ?>');
    </script>
</body>
</html>
