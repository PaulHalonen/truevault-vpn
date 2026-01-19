<?php
// Enterprise Business Hub Configuration
// USES: SQLite3 class (NOT PDO)
define('ENTERPRISE_DB_PATH', __DIR__ . '/../databases/enterprise.db');

function getEnterpriseDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new SQLite3(ENTERPRISE_DB_PATH);
            $db->enableExceptions(true);
            $db->busyTimeout(5000);
        } catch (Exception $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $db;
}

// ==================== CLIENTS ====================

function getClients($activeOnly = true) {
    $db = getEnterpriseDB();
    
    if ($activeOnly) {
        $result = $db->query("SELECT * FROM enterprise_clients WHERE status = 'active' ORDER BY company_name");
    } else {
        $result = $db->query("SELECT * FROM enterprise_clients ORDER BY company_name");
    }
    
    $clients = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $clients[] = $row;
    }
    return $clients;
}

function getClient($id) {
    $db = getEnterpriseDB();
    $stmt = $db->prepare("SELECT * FROM enterprise_clients WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

function createClient($data) {
    $db = getEnterpriseDB();
    $stmt = $db->prepare("
        INSERT INTO enterprise_clients (
            company_name, contact_name, contact_email, contact_phone,
            industry, company_size, billing_address, payment_terms, hourly_rate
        ) VALUES (:company, :name, :email, :phone, :industry, :size, :address, :terms, :rate)
    ");
    $stmt->bindValue(':company', $data['company_name'], SQLITE3_TEXT);
    $stmt->bindValue(':name', $data['contact_name'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':email', $data['contact_email'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':phone', $data['contact_phone'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':industry', $data['industry'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':size', $data['company_size'] ?? 'medium', SQLITE3_TEXT);
    $stmt->bindValue(':address', $data['billing_address'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':terms', $data['payment_terms'] ?? 30, SQLITE3_INTEGER);
    $stmt->bindValue(':rate', $data['hourly_rate'] ?? 150.00, SQLITE3_FLOAT);
    $stmt->execute();
    return $db->lastInsertRowID();
}

// ==================== PROJECTS ====================

function getProjects($clientId = null, $status = null) {
    $db = getEnterpriseDB();
    
    $where = [];
    $params = [];
    
    if ($clientId) {
        $where[] = 'p.client_id = :client_id';
        $params[':client_id'] = [$clientId, SQLITE3_INTEGER];
    }
    
    if ($status) {
        $where[] = 'p.status = :status';
        $params[':status'] = [$status, SQLITE3_TEXT];
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $stmt = $db->prepare("
        SELECT p.*, c.company_name
        FROM enterprise_projects p
        LEFT JOIN enterprise_clients c ON p.client_id = c.id
        $whereClause
        ORDER BY p.created_at DESC
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value[0], $value[1]);
    }
    
    $result = $stmt->execute();
    $projects = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $projects[] = $row;
    }
    return $projects;
}

function getProject($id) {
    $db = getEnterpriseDB();
    $stmt = $db->prepare("
        SELECT p.*, c.company_name, c.contact_name
        FROM enterprise_projects p
        LEFT JOIN enterprise_clients c ON p.client_id = c.id
        WHERE p.id = :id
    ");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

function createProject($data) {
    $db = getEnterpriseDB();
    $stmt = $db->prepare("
        INSERT INTO enterprise_projects (
            client_id, project_name, description, project_type,
            budget, hourly_rate, start_date, end_date, priority
        ) VALUES (:client_id, :name, :desc, :type, :budget, :rate, :start, :end, :priority)
    ");
    $stmt->bindValue(':client_id', $data['client_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':name', $data['project_name'], SQLITE3_TEXT);
    $stmt->bindValue(':desc', $data['description'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':type', $data['project_type'] ?? 'hourly', SQLITE3_TEXT);
    $stmt->bindValue(':budget', $data['budget'] ?? null, SQLITE3_FLOAT);
    $stmt->bindValue(':rate', $data['hourly_rate'] ?? null, SQLITE3_FLOAT);
    $stmt->bindValue(':start', $data['start_date'] ?? date('Y-m-d'), SQLITE3_TEXT);
    $stmt->bindValue(':end', $data['end_date'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':priority', $data['priority'] ?? 'medium', SQLITE3_TEXT);
    $stmt->execute();
    return $db->lastInsertRowID();
}

function updateProjectProgress($projectId, $percent) {
    $db = getEnterpriseDB();
    $stmt = $db->prepare("UPDATE enterprise_projects SET completion_percent = :percent WHERE id = :id");
    $stmt->bindValue(':percent', $percent, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $projectId, SQLITE3_INTEGER);
    $stmt->execute();
}

// ==================== TASKS ====================

function getTasks($projectId = null, $status = null) {
    $db = getEnterpriseDB();
    
    $where = [];
    $params = [];
    
    if ($projectId) {
        $where[] = 'project_id = :project_id';
        $params[':project_id'] = [$projectId, SQLITE3_INTEGER];
    }
    
    if ($status) {
        $where[] = 'status = :status';
        $params[':status'] = [$status, SQLITE3_TEXT];
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $stmt = $db->prepare("SELECT * FROM enterprise_tasks $whereClause ORDER BY due_date");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value[0], $value[1]);
    }
    
    $result = $stmt->execute();
    $tasks = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tasks[] = $row;
    }
    return $tasks;
}

function createTask($data) {
    $db = getEnterpriseDB();
    $stmt = $db->prepare("
        INSERT INTO enterprise_tasks (
            project_id, task_name, description, assigned_to,
            priority, estimated_hours, due_date
        ) VALUES (:project_id, :name, :desc, :assigned, :priority, :hours, :due)
    ");
    $stmt->bindValue(':project_id', $data['project_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':name', $data['task_name'], SQLITE3_TEXT);
    $stmt->bindValue(':desc', $data['description'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':assigned', $data['assigned_to'] ?? null, SQLITE3_INTEGER);
    $stmt->bindValue(':priority', $data['priority'] ?? 'medium', SQLITE3_TEXT);
    $stmt->bindValue(':hours', $data['estimated_hours'] ?? null, SQLITE3_FLOAT);
    $stmt->bindValue(':due', $data['due_date'] ?? null, SQLITE3_TEXT);
    $stmt->execute();
    return $db->lastInsertRowID();
}

function updateTaskStatus($taskId, $status) {
    $db = getEnterpriseDB();
    
    $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
    
    $stmt = $db->prepare("UPDATE enterprise_tasks SET status = :status, completed_at = :completed WHERE id = :id");
    $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    $stmt->bindValue(':completed', $completedAt, SQLITE3_TEXT);
    $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
    $stmt->execute();
}

// ==================== TIME TRACKING ====================

function logTime($data) {
    $db = getEnterpriseDB();
    $stmt = $db->prepare("
        INSERT INTO enterprise_time_entries (
            project_id, task_id, team_member_id, description,
            hours, billable, hourly_rate, entry_date
        ) VALUES (:project_id, :task_id, :member_id, :desc, :hours, :billable, :rate, :date)
    ");
    $stmt->bindValue(':project_id', $data['project_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':task_id', $data['task_id'] ?? null, SQLITE3_INTEGER);
    $stmt->bindValue(':member_id', $data['team_member_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':desc', $data['description'], SQLITE3_TEXT);
    $stmt->bindValue(':hours', $data['hours'], SQLITE3_FLOAT);
    $stmt->bindValue(':billable', $data['billable'] ?? 1, SQLITE3_INTEGER);
    $stmt->bindValue(':rate', $data['hourly_rate'] ?? null, SQLITE3_FLOAT);
    $stmt->bindValue(':date', $data['entry_date'] ?? date('Y-m-d'), SQLITE3_TEXT);
    $stmt->execute();
    return $db->lastInsertRowID();
}

function getTimeEntries($projectId = null, $startDate = null, $endDate = null) {
    $db = getEnterpriseDB();
    
    $where = [];
    $params = [];
    
    if ($projectId) {
        $where[] = 't.project_id = :project_id';
        $params[':project_id'] = [$projectId, SQLITE3_INTEGER];
    }
    
    if ($startDate) {
        $where[] = 't.entry_date >= :start';
        $params[':start'] = [$startDate, SQLITE3_TEXT];
    }
    
    if ($endDate) {
        $where[] = 't.entry_date <= :end';
        $params[':end'] = [$endDate, SQLITE3_TEXT];
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $stmt = $db->prepare("
        SELECT t.*, tm.full_name as member_name, p.project_name
        FROM enterprise_time_entries t
        LEFT JOIN enterprise_team tm ON t.team_member_id = tm.id
        LEFT JOIN enterprise_projects p ON t.project_id = p.id
        $whereClause
        ORDER BY t.entry_date DESC
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value[0], $value[1]);
    }
    
    $result = $stmt->execute();
    $entries = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $entries[] = $row;
    }
    return $entries;
}

function calculateProjectHours($projectId) {
    $db = getEnterpriseDB();
    $stmt = $db->prepare("
        SELECT SUM(hours) as total_hours, SUM(hours * hourly_rate) as total_cost
        FROM enterprise_time_entries
        WHERE project_id = :id AND billable = 1
    ");
    $stmt->bindValue(':id', $projectId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

// ==================== INVOICES ====================

function generateInvoiceNumber() {
    $db = getEnterpriseDB();
    $result = $db->query("SELECT COUNT(*) as count FROM enterprise_invoices");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $count = $row['count'];
    return 'INV-' . date('Y') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

function createInvoice($data) {
    $db = getEnterpriseDB();
    
    $invoiceNumber = $data['invoice_number'] ?? generateInvoiceNumber();
    $invoiceDate = $data['invoice_date'] ?? date('Y-m-d');
    $paymentTerms = $data['payment_terms'] ?? 30;
    $dueDate = date('Y-m-d', strtotime($invoiceDate . " +$paymentTerms days"));
    
    $stmt = $db->prepare("
        INSERT INTO enterprise_invoices (
            invoice_number, client_id, project_id, invoice_date,
            due_date, subtotal, tax_rate, tax_amount, total_amount
        ) VALUES (:number, :client_id, :project_id, :inv_date, :due_date, :subtotal, :tax_rate, :tax_amount, :total)
    ");
    
    $subtotal = $data['subtotal'] ?? 0;
    $taxRate = $data['tax_rate'] ?? 0;
    $taxAmount = $subtotal * ($taxRate / 100);
    $total = $subtotal + $taxAmount;
    
    $stmt->bindValue(':number', $invoiceNumber, SQLITE3_TEXT);
    $stmt->bindValue(':client_id', $data['client_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':project_id', $data['project_id'] ?? null, SQLITE3_INTEGER);
    $stmt->bindValue(':inv_date', $invoiceDate, SQLITE3_TEXT);
    $stmt->bindValue(':due_date', $dueDate, SQLITE3_TEXT);
    $stmt->bindValue(':subtotal', $subtotal, SQLITE3_FLOAT);
    $stmt->bindValue(':tax_rate', $taxRate, SQLITE3_FLOAT);
    $stmt->bindValue(':tax_amount', $taxAmount, SQLITE3_FLOAT);
    $stmt->bindValue(':total', $total, SQLITE3_FLOAT);
    $stmt->execute();
    
    return $db->lastInsertRowID();
}

function addInvoiceItem($invoiceId, $description, $quantity, $unitPrice) {
    $db = getEnterpriseDB();
    
    $amount = $quantity * $unitPrice;
    
    $stmt = $db->prepare("
        INSERT INTO enterprise_invoice_items (invoice_id, description, quantity, unit_price, amount)
        VALUES (:invoice_id, :desc, :qty, :price, :amount)
    ");
    $stmt->bindValue(':invoice_id', $invoiceId, SQLITE3_INTEGER);
    $stmt->bindValue(':desc', $description, SQLITE3_TEXT);
    $stmt->bindValue(':qty', $quantity, SQLITE3_FLOAT);
    $stmt->bindValue(':price', $unitPrice, SQLITE3_FLOAT);
    $stmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
    $stmt->execute();
    
    // Update invoice totals
    recalculateInvoice($invoiceId);
}

function recalculateInvoice($invoiceId) {
    $db = getEnterpriseDB();
    
    $stmt = $db->prepare("SELECT SUM(amount) as subtotal FROM enterprise_invoice_items WHERE invoice_id = :id");
    $stmt->bindValue(':id', $invoiceId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $subtotal = $row['subtotal'] ?? 0;
    
    $stmt = $db->prepare("SELECT tax_rate FROM enterprise_invoices WHERE id = :id");
    $stmt->bindValue(':id', $invoiceId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $taxRate = $row['tax_rate'] ?? 0;
    
    $taxAmount = $subtotal * ($taxRate / 100);
    $total = $subtotal + $taxAmount;
    
    $stmt = $db->prepare("
        UPDATE enterprise_invoices
        SET subtotal = :subtotal, tax_amount = :tax, total_amount = :total
        WHERE id = :id
    ");
    $stmt->bindValue(':subtotal', $subtotal, SQLITE3_FLOAT);
    $stmt->bindValue(':tax', $taxAmount, SQLITE3_FLOAT);
    $stmt->bindValue(':total', $total, SQLITE3_FLOAT);
    $stmt->bindValue(':id', $invoiceId, SQLITE3_INTEGER);
    $stmt->execute();
}

function getInvoices($clientId = null, $status = null) {
    $db = getEnterpriseDB();
    
    $where = [];
    $params = [];
    
    if ($clientId) {
        $where[] = 'i.client_id = :client_id';
        $params[':client_id'] = [$clientId, SQLITE3_INTEGER];
    }
    
    if ($status) {
        $where[] = 'i.status = :status';
        $params[':status'] = [$status, SQLITE3_TEXT];
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $stmt = $db->prepare("
        SELECT i.*, c.company_name
        FROM enterprise_invoices i
        LEFT JOIN enterprise_clients c ON i.client_id = c.id
        $whereClause
        ORDER BY i.invoice_date DESC
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value[0], $value[1]);
    }
    
    $result = $stmt->execute();
    $invoices = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $invoices[] = $row;
    }
    return $invoices;
}

// ==================== STATISTICS ====================

function getEnterpriseStats() {
    $db = getEnterpriseDB();
    
    $stats = [];
    
    $result = $db->query("SELECT COUNT(*) as count FROM enterprise_clients WHERE status = 'active'");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['active_clients'] = $row['count'];
    
    $result = $db->query("SELECT COUNT(*) as count FROM enterprise_projects WHERE status = 'active'");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['active_projects'] = $row['count'];
    
    $result = $db->query("SELECT SUM(total_amount) as total FROM enterprise_invoices WHERE status = 'paid'");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['total_revenue'] = $row['total'] ?? 0;
    
    $result = $db->query("SELECT SUM(total_amount) as total FROM enterprise_invoices WHERE status IN ('sent', 'overdue')");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['outstanding'] = $row['total'] ?? 0;
    
    return $stats;
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
