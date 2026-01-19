<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Get statistics
if ($action === 'stats') {
    $stats = getEnterpriseStats();
    jsonResponse(['success' => true, 'stats' => $stats]);
}

// Client operations
if ($action === 'clients') {
    if ($method === 'GET') {
        $clients = getClients(false);
        jsonResponse(['success' => true, 'clients' => $clients]);
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $clientId = createClient($input);
        jsonResponse(['success' => true, 'client_id' => $clientId]);
    }
}

// Project operations
if ($action === 'projects') {
    if ($method === 'GET') {
        $clientId = $_GET['client_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $projects = getProjects($clientId, $status);
        jsonResponse(['success' => true, 'projects' => $projects]);
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = createProject($input);
        jsonResponse(['success' => true, 'project_id' => $projectId]);
    }
}

// Time tracking
if ($action === 'log_time' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $entryId = logTime($input);
    jsonResponse(['success' => true, 'entry_id' => $entryId]);
}

if ($action === 'time_entries') {
    $projectId = $_GET['project_id'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $entries = getTimeEntries($projectId, $startDate, $endDate);
    jsonResponse(['success' => true, 'entries' => $entries]);
}

// Invoicing
if ($action === 'create_invoice' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $invoiceId = createInvoice($input);
    jsonResponse(['success' => true, 'invoice_id' => $invoiceId]);
}

if ($action === 'add_invoice_item' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    addInvoiceItem(
        $input['invoice_id'],
        $input['description'],
        $input['quantity'],
        $input['unit_price']
    );
    jsonResponse(['success' => true]);
}

if ($action === 'invoices') {
    $clientId = $_GET['client_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $invoices = getInvoices($clientId, $status);
    jsonResponse(['success' => true, 'invoices' => $invoices]);
}

// Task operations
if ($action === 'create_task' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $taskId = createTask($input);
    jsonResponse(['success' => true, 'task_id' => $taskId]);
}

if ($action === 'update_task_status' && $method === 'POST') {
    $taskId = $_POST['task_id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$taskId || !$status) {
        jsonResponse(['success' => false, 'error' => 'Task ID and status required'], 400);
    }
    
    updateTaskStatus($taskId, $status);
    jsonResponse(['success' => true]);
}

// Default response
jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
?>
