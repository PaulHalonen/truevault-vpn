<?php
/**
 * TrueVault VPN - Form Preview/Render
 * Part 14 - Task 14.6
 * Preview and public form display
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_FORMS', DB_PATH . 'forms.db');

$formId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$isPreview = isset($_GET['preview']);

$form = null;
$fields = [];
$settings = [];

if ($isPreview) {
    // Load from localStorage via JS
} elseif ($formId > 0 && file_exists(DB_FORMS)) {
    $db = new SQLite3(DB_FORMS);
    $db->enableExceptions(true);
    
    $stmt = $db->prepare("SELECT * FROM forms WHERE id = ?");
    $stmt->bindValue(1, $formId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $form = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($form) {
        $fields = json_decode($form['fields'], true) ?: [];
        $settings = json_decode($form['settings'], true) ?: [];
    }
    
    $db->close();
}

$style = $form['style'] ?? 'business';

// Style configurations
$styles = [
    'casual' => [
        'bg' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'card' => '#fff',
        'text' => '#333',
        'accent' => '#667eea',
        'radius' => '16px',
        'font' => "'Quicksand', sans-serif"
    ],
    'business' => [
        'bg' => '#f5f7fa',
        'card' => '#fff',
        'text' => '#333',
        'accent' => '#2196f3',
        'radius' => '8px',
        'font' => "'Inter', sans-serif"
    ],
    'corporate' => [
        'bg' => '#1a1a2e',
        'card' => '#16213e',
        'text' => '#e0e0e0',
        'accent' => '#00d9ff',
        'radius' => '4px',
        'font' => "'Georgia', serif"
    ]
];

$s = $styles[$style] ?? $styles['business'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($form['display_name'] ?? 'Form Preview') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Quicksand:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: <?= $s['font'] ?>; 
            background: <?= $s['bg'] ?>; 
            color: <?= $s['text'] ?>; 
            min-height: 100vh; 
            padding: 40px 20px; 
        }
        .container { max-width: 600px; margin: 0 auto; }
        .form-card { 
            background: <?= $s['card'] ?>; 
            border-radius: <?= $s['radius'] ?>; 
            padding: 40px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .form-header { margin-bottom: 30px; text-align: center; }
        .form-header h1 { font-size: 1.8rem; margin-bottom: 10px; color: <?= $s['text'] ?>; }
        .form-header p { color: #888; }
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 500;
            color: <?= $s['text'] ?>;
        }
        .form-group .required { color: #e53935; }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; 
            padding: 14px; 
            border: 2px solid #e0e0e0; 
            border-radius: <?= $s['radius'] ?>; 
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
            background: <?= $style === 'corporate' ? 'rgba(255,255,255,0.05)' : '#fff' ?>;
            color: <?= $s['text'] ?>;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { 
            outline: none; 
            border-color: <?= $s['accent'] ?>; 
        }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .form-group .help { font-size: 0.85rem; color: #888; margin-top: 5px; }
        .checkbox-group, .radio-group { display: flex; flex-direction: column; gap: 10px; }
        .checkbox-item, .radio-item { 
            display: flex; 
            align-items: center; 
            gap: 10px;
            cursor: pointer;
            padding: 10px;
            border-radius: <?= $s['radius'] ?>;
            transition: background 0.2s;
        }
        .checkbox-item:hover, .radio-item:hover { background: rgba(0,0,0,0.05); }
        .checkbox-item input, .radio-item input { width: 20px; height: 20px; accent-color: <?= $s['accent'] ?>; }
        .rating-group { display: flex; gap: 8px; }
        .rating-star { 
            font-size: 2rem; 
            cursor: pointer; 
            color: #ddd; 
            transition: color 0.2s;
        }
        .rating-star.active, .rating-star:hover { color: #ffc107; }
        .submit-btn { 
            width: 100%; 
            padding: 16px; 
            background: <?= $s['accent'] ?>; 
            color: #fff; 
            border: none; 
            border-radius: <?= $s['radius'] ?>; 
            font-size: 1.1rem; 
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 20px;
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,0,0,0.2); }
        .success-message { 
            text-align: center; 
            padding: 40px; 
            display: none;
        }
        .success-message .icon { font-size: 4rem; margin-bottom: 20px; }
        .success-message h2 { margin-bottom: 10px; color: #00c853; }
        .preview-banner { 
            background: #ff9800; 
            color: #fff; 
            padding: 10px; 
            text-align: center; 
            position: fixed; 
            top: 0; 
            left: 0; 
            right: 0;
            z-index: 100;
        }
    </style>
</head>
<body>
    <?php if ($isPreview): ?>
    <div class="preview-banner">👁️ Preview Mode - Form submissions are disabled</div>
    <div style="height:40px;"></div>
    <?php endif; ?>
    
    <div class="container">
        <div class="form-card">
            <div id="formContent">
                <div class="form-header">
                    <h1 id="formTitle"><?= htmlspecialchars($form['display_name'] ?? 'Form') ?></h1>
                    <p id="formDescription"><?= htmlspecialchars($form['description'] ?? '') ?></p>
                </div>
                
                <form id="mainForm" onsubmit="return submitForm(event)">
                    <div id="fieldsContainer">
                        <?php foreach ($fields as $field): ?>
                        <?php renderField($field); ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <?= htmlspecialchars($settings['submit_text'] ?? 'Submit') ?>
                    </button>
                </form>
            </div>
            
            <div class="success-message" id="successMessage">
                <div class="icon">✅</div>
                <h2>Thank You!</h2>
                <p id="successText"><?= htmlspecialchars($settings['success_message'] ?? 'Your submission has been received.') ?></p>
            </div>
        </div>
    </div>
    
    <script>
        const isPreview = <?= $isPreview ? 'true' : 'false' ?>;
        const formId = <?= $formId ?>;
        
        // Load preview data if in preview mode
        if (isPreview) {
            const previewData = JSON.parse(localStorage.getItem('formPreview') || '{}');
            if (previewData.display_name) {
                document.getElementById('formTitle').textContent = previewData.display_name;
                document.getElementById('submitBtn').textContent = previewData.settings?.submit_text || 'Submit';
                renderFieldsJS(previewData.fields || []);
            }
        }
        
        function renderFieldsJS(fields) {
            const container = document.getElementById('fieldsContainer');
            container.innerHTML = '';
            
            fields.forEach(field => {
                const div = document.createElement('div');
                div.className = 'form-group';
                
                let input = '';
                switch (field.type) {
                    case 'textarea':
                        input = `<textarea name="${field.name}" placeholder="${field.placeholder || ''}" ${field.required ? 'required' : ''}></textarea>`;
                        break;
                    case 'select':
                        const opts = (field.options || []).map(o => `<option value="${o}">${o}</option>`).join('');
                        input = `<select name="${field.name}" ${field.required ? 'required' : ''}><option value="">Select...</option>${opts}</select>`;
                        break;
                    case 'checkbox':
                        input = `<div class="checkbox-item"><input type="checkbox" name="${field.name}" id="${field.name}" ${field.required ? 'required' : ''}><label for="${field.name}">${field.label}</label></div>`;
                        break;
                    case 'radio':
                        input = '<div class="radio-group">' + (field.options || []).map((o, i) => 
                            `<div class="radio-item"><input type="radio" name="${field.name}" value="${o}" id="${field.name}_${i}" ${field.required && i === 0 ? 'required' : ''}><label for="${field.name}_${i}">${o}</label></div>`
                        ).join('') + '</div>';
                        break;
                    case 'rating':
                        input = `<div class="rating-group" data-name="${field.name}" data-max="${field.max || 5}">` + 
                            '⭐'.repeat(field.max || 5).split('').map((s, i) => 
                                `<span class="rating-star" data-value="${i+1}" onclick="setRating(this)">${s}</span>`
                            ).join('') + 
                            `<input type="hidden" name="${field.name}" value="0"></div>`;
                        break;
                    default:
                        input = `<input type="${field.type}" name="${field.name}" placeholder="${field.placeholder || ''}" ${field.required ? 'required' : ''}>`;
                }
                
                if (field.type !== 'checkbox') {
                    div.innerHTML = `<label>${field.label}${field.required ? '<span class="required">*</span>' : ''}</label>${input}`;
                } else {
                    div.innerHTML = input;
                }
                
                container.appendChild(div);
            });
        }
        
        function setRating(star) {
            const group = star.parentElement;
            const value = parseInt(star.dataset.value);
            const input = group.querySelector('input[type="hidden"]');
            input.value = value;
            
            group.querySelectorAll('.rating-star').forEach((s, i) => {
                s.classList.toggle('active', i < value);
            });
        }
        
        function submitForm(e) {
            e.preventDefault();
            
            if (isPreview) {
                alert('Form submissions are disabled in preview mode.');
                return false;
            }
            
            const formData = new FormData(document.getElementById('mainForm'));
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
            fetch('api/submissions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({form_id: formId, data: data})
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    document.getElementById('formContent').style.display = 'none';
                    document.getElementById('successMessage').style.display = 'block';
                } else {
                    alert(result.error || 'Submission failed');
                }
            })
            .catch(err => alert('Error: ' + err.message));
            
            return false;
        }
    </script>
</body>
</html>

<?php
function renderField($field) {
    $req = !empty($field['required']);
    $name = htmlspecialchars($field['name']);
    $label = htmlspecialchars($field['label']);
    $placeholder = htmlspecialchars($field['placeholder'] ?? '');
    
    echo '<div class="form-group">';
    
    switch ($field['type']) {
        case 'textarea':
            echo "<label>{$label}" . ($req ? '<span class="required">*</span>' : '') . "</label>";
            echo "<textarea name=\"{$name}\" placeholder=\"{$placeholder}\"" . ($req ? ' required' : '') . "></textarea>";
            break;
            
        case 'select':
            echo "<label>{$label}" . ($req ? '<span class="required">*</span>' : '') . "</label>";
            echo "<select name=\"{$name}\"" . ($req ? ' required' : '') . "><option value=\"\">Select...</option>";
            foreach ($field['options'] ?? [] as $opt) {
                echo "<option value=\"" . htmlspecialchars($opt) . "\">" . htmlspecialchars($opt) . "</option>";
            }
            echo "</select>";
            break;
            
        case 'checkbox':
            echo "<div class=\"checkbox-item\"><input type=\"checkbox\" name=\"{$name}\" id=\"{$name}\"" . ($req ? ' required' : '') . "><label for=\"{$name}\">{$label}</label></div>";
            break;
            
        case 'radio':
            echo "<label>{$label}" . ($req ? '<span class="required">*</span>' : '') . "</label>";
            echo '<div class="radio-group">';
            foreach ($field['options'] ?? [] as $i => $opt) {
                echo "<div class=\"radio-item\"><input type=\"radio\" name=\"{$name}\" value=\"" . htmlspecialchars($opt) . "\" id=\"{$name}_{$i}\"" . ($req && $i === 0 ? ' required' : '') . "><label for=\"{$name}_{$i}\">" . htmlspecialchars($opt) . "</label></div>";
            }
            echo '</div>';
            break;
            
        case 'rating':
            $max = $field['max'] ?? 5;
            echo "<label>{$label}" . ($req ? '<span class="required">*</span>' : '') . "</label>";
            echo "<div class=\"rating-group\" data-name=\"{$name}\" data-max=\"{$max}\">";
            for ($i = 1; $i <= $max; $i++) {
                echo "<span class=\"rating-star\" data-value=\"{$i}\" onclick=\"setRating(this)\">⭐</span>";
            }
            echo "<input type=\"hidden\" name=\"{$name}\" value=\"0\"></div>";
            break;
            
        default:
            echo "<label>{$label}" . ($req ? '<span class="required">*</span>' : '') . "</label>";
            echo "<input type=\"{$field['type']}\" name=\"{$name}\" placeholder=\"{$placeholder}\"" . ($req ? ' required' : '') . ">";
    }
    
    echo '</div>';
}
?>
