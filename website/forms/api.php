<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// Create form from template
if ($action === 'create_from_template' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateId = $_POST['template_id'] ?? null;
    $formName = $_POST['form_name'] ?? '';
    
    if (!$templateId || !$formName) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
    }
    
    $formId = createFormFromTemplate($templateId, $formName);
    jsonResponse(['success' => true, 'form_id' => $formId]);
}

// Create custom form
if ($action === 'create_custom' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $formName = $_POST['form_name'] ?? '';
    $fields = $_POST['fields'] ?? '[]';
    $settings = $_POST['settings'] ?? null;
    
    if (!$formName) {
        jsonResponse(['success' => false, 'error' => 'Form name required'], 400);
    }
    
    $formId = createCustomForm($formName, $fields, $settings);
    jsonResponse(['success' => true, 'form_id' => $formId]);
}

// Submit form
if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $formId = $_POST['form_id'] ?? null;
    
    if (!$formId) {
        jsonResponse(['success' => false, 'error' => 'Form ID required'], 400);
    }
    
    // Extract submission data (all POST data except form_id)
    $submissionData = $_POST;
    unset($submissionData['form_id']);
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $result = submitForm($formId, $submissionData, $ipAddress, $userAgent);
    jsonResponse($result);
}

// Get form
if ($action === 'get') {
    $formId = $_GET['form_id'] ?? null;
    
    if (!$formId) {
        jsonResponse(['success' => false, 'error' => 'Form ID required'], 400);
    }
    
    $form = getForm($formId);
    
    if (!$form) {
        jsonResponse(['success' => false, 'error' => 'Form not found'], 404);
    }
    
    jsonResponse(['success' => true, 'form' => $form]);
}

// Get template
if ($action === 'get_template') {
    $templateId = $_GET['template_id'] ?? null;
    
    if (!$templateId) {
        jsonResponse(['success' => false, 'error' => 'Template ID required'], 400);
    }
    
    $template = getTemplate($templateId);
    
    if (!$template) {
        jsonResponse(['success' => false, 'error' => 'Template not found'], 404);
    }
    
    jsonResponse(['success' => true, 'template' => $template]);
}

// List forms
if ($action === 'list') {
    $forms = getForms();
    jsonResponse(['success' => true, 'forms' => $forms]);
}

// Get submissions
if ($action === 'get_submissions') {
    $formId = $_GET['form_id'] ?? null;
    $limit = $_GET['limit'] ?? 100;
    
    if (!$formId) {
        jsonResponse(['success' => false, 'error' => 'Form ID required'], 400);
    }
    
    $submissions = getSubmissions($formId, $limit);
    jsonResponse(['success' => true, 'submissions' => $submissions]);
}

// Export submissions
if ($action === 'export') {
    $formId = $_GET['form_id'] ?? null;
    
    if (!$formId) {
        jsonResponse(['success' => false, 'error' => 'Form ID required'], 400);
    }
    
    exportSubmissionsCSV($formId);
    exit;
}

// Delete form
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $formId = $_POST['form_id'] ?? null;
    
    if (!$formId) {
        jsonResponse(['success' => false, 'error' => 'Form ID required'], 400);
    }
    
    deleteForm($formId);
    jsonResponse(['success' => true]);
}

// Default response
jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
?>
