<?php
/**
 * TrueVault VPN - Form Submissions API
 * Part 14 - Task 14.6
 * Handle form submissions and retrieval
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../../configs/config.php';

header('Content-Type: application/json');
define('DB_FORMS', DB_PATH . 'forms.db');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = new SQLite3(DB_FORMS);
    $db->enableExceptions(true);
    
    switch ($method) {
        case 'GET':
            $formId = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
            $submissionId = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $status = $_GET['status'] ?? '';
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = 50;
            
            if ($submissionId) {
                // Get single submission
                $stmt = $db->prepare("SELECT s.*, f.display_name as form_name FROM form_submissions s JOIN forms f ON s.form_id = f.id WHERE s.id = ?");
                $stmt->bindValue(1, $submissionId, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $submission = $result->fetchArray(SQLITE3_ASSOC);
                
                if ($submission) {
                    $submission['form_data'] = json_decode($submission['form_data'], true);
                    echo json_encode(['success' => true, 'submission' => $submission]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Submission not found']);
                }
            } else {
                // List submissions
                $where = [];
                $params = [];
                
                if ($formId) {
                    $where[] = "s.form_id = ?";
                    $params[] = $formId;
                }
                if ($status) {
                    $where[] = "s.status = ?";
                    $params[] = $status;
                }
                
                $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
                $offset = ($page - 1) * $perPage;
                
                // Count total
                $countSql = "SELECT COUNT(*) FROM form_submissions s {$whereClause}";
                $countStmt = $db->prepare($countSql);
                foreach ($params as $i => $p) {
                    $countStmt->bindValue($i + 1, $p, is_int($p) ? SQLITE3_INTEGER : SQLITE3_TEXT);
                }
                $total = $countStmt->execute()->fetchArray()[0];
                
                // Get submissions
                $sql = "SELECT s.*, f.display_name as form_name FROM form_submissions s JOIN forms f ON s.form_id = f.id {$whereClause} ORDER BY s.submitted_at DESC LIMIT {$perPage} OFFSET {$offset}";
                $stmt = $db->prepare($sql);
                foreach ($params as $i => $p) {
                    $stmt->bindValue($i + 1, $p, is_int($p) ? SQLITE3_INTEGER : SQLITE3_TEXT);
                }
                $result = $stmt->execute();
                
                $submissions = [];
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $row['form_data'] = json_decode($row['form_data'], true);
                    $submissions[] = $row;
                }
                
                echo json_encode([
                    'success' => true,
                    'submissions' => $submissions,
                    'total' => $total,
                    'page' => $page,
                    'pages' => ceil($total / $perPage)
                ]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $formId = intval($input['form_id'] ?? 0);
            $data = $input['data'] ?? [];
            
            if (!$formId || empty($data)) {
                echo json_encode(['success' => false, 'error' => 'Form ID and data required']);
                break;
            }
            
            // Get submitter info from data
            $email = $data['email'] ?? null;
            $name = $data['name'] ?? $data['full_name'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $stmt = $db->prepare("INSERT INTO form_submissions (form_id, form_data, submitter_ip, submitter_email, submitter_name, status) VALUES (?, ?, ?, ?, ?, 'new')");
            $stmt->bindValue(1, $formId, SQLITE3_INTEGER);
            $stmt->bindValue(2, json_encode($data), SQLITE3_TEXT);
            $stmt->bindValue(3, $ip, SQLITE3_TEXT);
            $stmt->bindValue(4, $email, SQLITE3_TEXT);
            $stmt->bindValue(5, $name, SQLITE3_TEXT);
            $stmt->execute();
            
            $submissionId = $db->lastInsertRowID();
            
            // Update submission count
            $stmt = $db->prepare("UPDATE forms SET submission_count = submission_count + 1 WHERE id = ?");
            $stmt->bindValue(1, $formId, SQLITE3_INTEGER);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'submission_id' => $submissionId]);
            break;
            
        case 'PATCH':
            $input = json_decode(file_get_contents('php://input'), true);
            $submissionId = intval($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $notes = $input['notes'] ?? '';
            
            if ($submissionId > 0 && $status) {
                $stmt = $db->prepare("UPDATE form_submissions SET status = ?, notes = ?, processed_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bindValue(1, $status, SQLITE3_TEXT);
                $stmt->bindValue(2, $notes, SQLITE3_TEXT);
                $stmt->bindValue(3, $submissionId, SQLITE3_INTEGER);
                $stmt->execute();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Submission ID and status required']);
            }
            break;
            
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $submissionId = intval($input['id'] ?? 0);
            
            if ($submissionId > 0) {
                // Get form_id first
                $stmt = $db->prepare("SELECT form_id FROM form_submissions WHERE id = ?");
                $stmt->bindValue(1, $submissionId, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $row = $result->fetchArray(SQLITE3_ASSOC);
                
                if ($row) {
                    $stmt = $db->prepare("DELETE FROM form_submissions WHERE id = ?");
                    $stmt->bindValue(1, $submissionId, SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    // Update submission count
                    $stmt = $db->prepare("UPDATE forms SET submission_count = MAX(0, submission_count - 1) WHERE id = ?");
                    $stmt->bindValue(1, $row['form_id'], SQLITE3_INTEGER);
                    $stmt->execute();
                }
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Submission ID required']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
