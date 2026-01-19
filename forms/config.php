<?php
// Form Library System Configuration
// USES: SQLite3 class (NOT PDO)
define('FORMS_DB_PATH', __DIR__ . '/../databases/forms.db');

function getFormsDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new SQLite3(FORMS_DB_PATH);
            $db->enableExceptions(true);
            $db->busyTimeout(5000);
        } catch (Exception $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $db;
}

// Get all form templates
function getFormTemplates($category = null) {
    $db = getFormsDB();
    
    if ($category) {
        $stmt = $db->prepare("SELECT * FROM form_templates WHERE category = :category AND is_active = 1 ORDER BY template_name");
        $stmt->bindValue(':category', $category, SQLITE3_TEXT);
        $result = $stmt->execute();
    } else {
        $result = $db->query("SELECT * FROM form_templates WHERE is_active = 1 ORDER BY category, template_name");
    }
    
    $templates = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $templates[] = $row;
    }
    return $templates;
}

// Get template categories
function getTemplateCategories() {
    $db = getFormsDB();
    $result = $db->query("SELECT DISTINCT category FROM form_templates WHERE is_active = 1 ORDER BY category");
    
    $categories = [];
    while ($row = $result->fetchArray(SQLITE3_NUM)) {
        $categories[] = $row[0];
    }
    return $categories;
}

// Get single template
function getTemplate($id) {
    $db = getFormsDB();
    $stmt = $db->prepare("SELECT * FROM form_templates WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

// Create custom form from template
function createFormFromTemplate($templateId, $formName, $customFields = null, $settings = null) {
    $db = getFormsDB();
    
    $template = getTemplate($templateId);
    if (!$template) {
        return false;
    }
    
    $fields = $customFields ?? $template['fields'];
    $formSettings = $settings ?? $template['settings'];
    
    $stmt = $db->prepare("
        INSERT INTO forms (form_name, template_id, fields, settings)
        VALUES (:name, :template_id, :fields, :settings)
    ");
    $stmt->bindValue(':name', $formName, SQLITE3_TEXT);
    $stmt->bindValue(':template_id', $templateId, SQLITE3_INTEGER);
    $stmt->bindValue(':fields', $fields, SQLITE3_TEXT);
    $stmt->bindValue(':settings', $formSettings, SQLITE3_TEXT);
    $stmt->execute();
    
    $formId = $db->lastInsertRowID();
    
    // Increment template usage
    $stmt = $db->prepare("UPDATE form_templates SET usage_count = usage_count + 1 WHERE id = :id");
    $stmt->bindValue(':id', $templateId, SQLITE3_INTEGER);
    $stmt->execute();
    
    return $formId;
}

// Create custom form (no template)
function createCustomForm($formName, $fields, $settings = null) {
    $db = getFormsDB();
    
    $defaultSettings = json_encode([
        'notifications' => ['enabled' => true],
        'submit_action' => 'email'
    ]);
    
    $stmt = $db->prepare("
        INSERT INTO forms (form_name, fields, settings)
        VALUES (:name, :fields, :settings)
    ");
    $stmt->bindValue(':name', $formName, SQLITE3_TEXT);
    $stmt->bindValue(':fields', $fields, SQLITE3_TEXT);
    $stmt->bindValue(':settings', $settings ?? $defaultSettings, SQLITE3_TEXT);
    $stmt->execute();
    
    return $db->lastInsertRowID();
}

// Get all custom forms
function getForms($activeOnly = true) {
    $db = getFormsDB();
    
    if ($activeOnly) {
        $result = $db->query("
            SELECT f.*, ft.template_name
            FROM forms f
            LEFT JOIN form_templates ft ON f.template_id = ft.id
            WHERE f.is_active = 1
            ORDER BY f.created_at DESC
        ");
    } else {
        $result = $db->query("
            SELECT f.*, ft.template_name
            FROM forms f
            LEFT JOIN form_templates ft ON f.template_id = ft.id
            ORDER BY f.created_at DESC
        ");
    }
    
    $forms = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $forms[] = $row;
    }
    return $forms;
}

// Get single form
function getForm($id) {
    $db = getFormsDB();
    $stmt = $db->prepare("
        SELECT f.*, ft.template_name
        FROM forms f
        LEFT JOIN form_templates ft ON f.template_id = ft.id
        WHERE f.id = :id
    ");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

// Submit form data
function submitForm($formId, $submissionData, $ipAddress = null, $userAgent = null) {
    $db = getFormsDB();
    
    $form = getForm($formId);
    if (!$form) {
        return ['success' => false, 'error' => 'Form not found'];
    }
    
    // Validate submission against form fields
    $fields = json_decode($form['fields'], true);
    $errors = validateSubmission($fields, $submissionData);
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Save submission
    $stmt = $db->prepare("
        INSERT INTO form_submissions (form_id, submission_data, ip_address, user_agent)
        VALUES (:form_id, :data, :ip, :user_agent)
    ");
    $stmt->bindValue(':form_id', $formId, SQLITE3_INTEGER);
    $stmt->bindValue(':data', json_encode($submissionData), SQLITE3_TEXT);
    $stmt->bindValue(':ip', $ipAddress, SQLITE3_TEXT);
    $stmt->bindValue(':user_agent', $userAgent, SQLITE3_TEXT);
    $stmt->execute();
    
    $submissionId = $db->lastInsertRowID();
    
    // Handle notifications
    $settings = json_decode($form['settings'], true);
    if (isset($settings['notifications']['enabled']) && $settings['notifications']['enabled']) {
        sendFormNotification($form, $submissionData);
    }
    
    return ['success' => true, 'submission_id' => $submissionId];
}

// Validate submission data
function validateSubmission($fields, $data) {
    $errors = [];
    
    foreach ($fields as $field) {
        $name = $field['name'];
        $value = $data[$name] ?? null;
        
        // Check required fields
        if (isset($field['required']) && $field['required'] && empty($value)) {
            $errors[$name] = $field['label'] . ' is required';
            continue;
        }
        
        // Type-specific validation
        if (!empty($value)) {
            switch ($field['type']) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$name] = 'Invalid email address';
                    }
                    break;
                    
                case 'url':
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        $errors[$name] = 'Invalid URL';
                    }
                    break;
                    
                case 'number':
                    if (!is_numeric($value)) {
                        $errors[$name] = 'Must be a number';
                    } elseif (isset($field['min']) && $value < $field['min']) {
                        $errors[$name] = 'Minimum value is ' . $field['min'];
                    } elseif (isset($field['max']) && $value > $field['max']) {
                        $errors[$name] = 'Maximum value is ' . $field['max'];
                    }
                    break;
                    
                case 'tel':
                    if (!preg_match('/^[\d\s\-\(\)\+]+$/', $value)) {
                        $errors[$name] = 'Invalid phone number';
                    }
                    break;
            }
            
            // Length validation
            if (isset($field['maxlength']) && strlen($value) > $field['maxlength']) {
                $errors[$name] = 'Maximum length is ' . $field['maxlength'] . ' characters';
            }
            if (isset($field['minlength']) && strlen($value) < $field['minlength']) {
                $errors[$name] = 'Minimum length is ' . $field['minlength'] . ' characters';
            }
        }
    }
    
    return $errors;
}

// Send form notification email
function sendFormNotification($form, $submissionData) {
    $to = $form['notification_email'] ?? 'admin@vpn.the-truth-publishing.com';
    $subject = 'New Form Submission: ' . $form['form_name'];
    
    $message = "New submission for: " . $form['form_name'] . "\n\n";
    $message .= "Submission Details:\n";
    $message .= str_repeat('-', 40) . "\n\n";
    
    foreach ($submissionData as $key => $value) {
        $message .= ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "\n";
    }
    
    $message .= "\n" . str_repeat('-', 40) . "\n";
    $message .= "Submitted: " . date('Y-m-d H:i:s') . "\n";
    
    $headers = "From: noreply@vpn.the-truth-publishing.com\r\n";
    $headers .= "Reply-To: noreply@vpn.the-truth-publishing.com\r\n";
    
    mail($to, $subject, $message, $headers);
}

// Get form submissions
function getSubmissions($formId, $limit = 100) {
    $db = getFormsDB();
    $stmt = $db->prepare("
        SELECT * FROM form_submissions
        WHERE form_id = :form_id
        ORDER BY submitted_at DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':form_id', $formId, SQLITE3_INTEGER);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $submissions = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $submissions[] = $row;
    }
    return $submissions;
}

// Get form statistics
function getFormStats($formId = null) {
    $db = getFormsDB();
    
    if ($formId) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM form_submissions WHERE form_id = :id");
        $stmt->bindValue(':id', $formId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row['count'];
    } else {
        $result = $db->query("SELECT COUNT(*) as count FROM form_submissions");
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row['count'];
    }
}

// Delete form
function deleteForm($formId) {
    $db = getFormsDB();
    $stmt = $db->prepare("UPDATE forms SET is_active = 0 WHERE id = :id");
    $stmt->bindValue(':id', $formId, SQLITE3_INTEGER);
    $stmt->execute();
    return true;
}

// Export submissions as CSV
function exportSubmissionsCSV($formId) {
    $form = getForm($formId);
    $submissions = getSubmissions($formId, 10000);
    
    if (empty($submissions)) {
        return false;
    }
    
    $fields = json_decode($form['fields'], true);
    $headers = array_column($fields, 'label');
    $headers[] = 'Submitted At';
    
    $filename = 'form_' . $formId . '_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    
    foreach ($submissions as $submission) {
        $data = json_decode($submission['submission_data'], true);
        $row = [];
        
        foreach ($fields as $field) {
            $row[] = $data[$field['name']] ?? '';
        }
        $row[] = $submission['submitted_at'];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
