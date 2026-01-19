<?php
/**
 * TrueVault VPN - Parental Schedules API
 * 
 * Manages calendar-based parental control schedules
 * 
 * Endpoints:
 * - GET    /schedules           - List all schedules
 * - GET    /schedules/{id}      - Get schedule details
 * - POST   /schedules           - Create new schedule
 * - PUT    /schedules/{id}      - Update schedule
 * - DELETE /schedules/{id}      - Delete schedule
 * - POST   /schedules/clone     - Clone template
 * 
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Authentication.php';

header('Content-Type: application/json');

$auth = new Authentication();
$user = $auth->authenticate();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection('parental');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Extract ID from path if present
$scheduleId = null;
if (count($pathParts) > 3 && is_numeric($pathParts[3])) {
    $scheduleId = (int)$pathParts[3];
}

try {
    switch ($method) {
        case 'GET':
            if ($scheduleId) {
                getScheduleDetails($conn, $user['id'], $scheduleId);
            } else {
                listSchedules($conn, $user['id']);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Check if cloning a template
            if (isset($input['action']) && $input['action'] === 'clone') {
                cloneTemplate($conn, $user['id'], $input);
            } else {
                createSchedule($conn, $user['id'], $input);
            }
            break;
            
        case 'PUT':
            if (!$scheduleId) {
                throw new Exception('Schedule ID required');
            }
            $input = json_decode(file_get_contents('php://input'), true);
            updateSchedule($conn, $user['id'], $scheduleId, $input);
            break;
            
        case 'DELETE':
            if (!$scheduleId) {
                throw new Exception('Schedule ID required');
            }
            deleteSchedule($conn, $user['id'], $scheduleId);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * List all schedules for user
 */
function listSchedules($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            s.*,
            COUNT(DISTINCT w.id) as window_count,
            COUNT(DISTINCT dr.device_id) as device_count
        FROM parental_schedules s
        LEFT JOIN schedule_windows w ON w.schedule_id = s.id
        LEFT JOIN device_rules dr ON dr.schedule_id = s.id
        WHERE s.user_id = :user_id OR s.is_template = 1
        GROUP BY s.id
        ORDER BY s.is_template DESC, s.is_active DESC, s.created_at DESC
    ");
    
    $stmt->execute(['user_id' => $userId]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert boolean fields
    foreach ($schedules as &$schedule) {
        $schedule['is_template'] = (bool)$schedule['is_template'];
        $schedule['is_active'] = (bool)$schedule['is_active'];
        $schedule['window_count'] = (int)$schedule['window_count'];
        $schedule['device_count'] = (int)$schedule['device_count'];
    }
    
    echo json_encode([
        'success' => true,
        'schedules' => $schedules
    ]);
}

/**
 * Get schedule details with time windows
 */
function getScheduleDetails($conn, $userId, $scheduleId) {
    // Get schedule
    $stmt = $conn->prepare("
        SELECT * FROM parental_schedules 
        WHERE id = :id AND (user_id = :user_id OR is_template = 1)
    ");
    $stmt->execute(['id' => $scheduleId, 'user_id' => $userId]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        http_response_code(404);
        echo json_encode(['error' => 'Schedule not found']);
        return;
    }
    
    // Get time windows
    $stmt = $conn->prepare("
        SELECT * FROM schedule_windows 
        WHERE schedule_id = :schedule_id
        ORDER BY 
            CASE 
                WHEN day_of_week IS NOT NULL THEN day_of_week
                ELSE 7
            END,
            start_time
    ");
    $stmt->execute(['schedule_id' => $scheduleId]);
    $windows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get assigned devices
    $stmt = $conn->prepare("
        SELECT dr.device_id, d.device_name, d.device_type
        FROM device_rules dr
        JOIN devices d ON d.id = dr.device_id
        WHERE dr.schedule_id = :schedule_id AND dr.user_id = :user_id
    ");
    $stmt->execute(['schedule_id' => $scheduleId, 'user_id' => $userId]);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $schedule['is_template'] = (bool)$schedule['is_template'];
    $schedule['is_active'] = (bool)$schedule['is_active'];
    $schedule['windows'] = $windows;
    $schedule['assigned_devices'] = $devices;
    
    echo json_encode([
        'success' => true,
        'schedule' => $schedule
    ]);
}

/**
 * Create new schedule
 */
function createSchedule($conn, $userId, $input) {
    if (empty($input['schedule_name'])) {
        throw new Exception('Schedule name required');
    }
    
    $conn->beginTransaction();
    
    try {
        // Insert schedule
        $stmt = $conn->prepare("
            INSERT INTO parental_schedules 
            (user_id, device_id, schedule_name, description, is_active)
            VALUES (:user_id, :device_id, :schedule_name, :description, :is_active)
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'device_id' => $input['device_id'] ?? null,
            'schedule_name' => $input['schedule_name'],
            'description' => $input['description'] ?? null,
            'is_active' => $input['is_active'] ?? 1
        ]);
        
        $scheduleId = $conn->lastInsertId();
        
        // Insert time windows if provided
        if (!empty($input['windows'])) {
            foreach ($input['windows'] as $window) {
                addTimeWindow($conn, $scheduleId, $window);
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'schedule_id' => $scheduleId,
            'message' => 'Schedule created successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

/**
 * Update schedule
 */
function updateSchedule($conn, $userId, $scheduleId, $input) {
    // Verify ownership
    $stmt = $conn->prepare("
        SELECT id FROM parental_schedules 
        WHERE id = :id AND user_id = :user_id
    ");
    $stmt->execute(['id' => $scheduleId, 'user_id' => $userId]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Schedule not found']);
        return;
    }
    
    $conn->beginTransaction();
    
    try {
        // Update schedule
        $stmt = $conn->prepare("
            UPDATE parental_schedules 
            SET schedule_name = :schedule_name,
                description = :description,
                is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND user_id = :user_id
        ");
        
        $stmt->execute([
            'id' => $scheduleId,
            'user_id' => $userId,
            'schedule_name' => $input['schedule_name'] ?? null,
            'description' => $input['description'] ?? null,
            'is_active' => $input['is_active'] ?? 1
        ]);
        
        // Update windows if provided
        if (isset($input['windows'])) {
            // Delete existing windows
            $conn->prepare("DELETE FROM schedule_windows WHERE schedule_id = :id")
                 ->execute(['id' => $scheduleId]);
            
            // Insert new windows
            foreach ($input['windows'] as $window) {
                addTimeWindow($conn, $scheduleId, $window);
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Schedule updated successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

/**
 * Delete schedule
 */
function deleteSchedule($conn, $userId, $scheduleId) {
    $stmt = $conn->prepare("
        DELETE FROM parental_schedules 
        WHERE id = :id AND user_id = :user_id AND is_template = 0
    ");
    
    $stmt->execute(['id' => $scheduleId, 'user_id' => $userId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Schedule not found or is a template']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Schedule deleted successfully'
    ]);
}

/**
 * Clone template to user's schedules
 */
function cloneTemplate($conn, $userId, $input) {
    if (empty($input['template_id'])) {
        throw new Exception('Template ID required');
    }
    
    $conn->beginTransaction();
    
    try {
        // Get template
        $stmt = $conn->prepare("
            SELECT * FROM parental_schedules 
            WHERE id = :id AND is_template = 1
        ");
        $stmt->execute(['id' => $input['template_id']]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            throw new Exception('Template not found');
        }
        
        // Create new schedule from template
        $stmt = $conn->prepare("
            INSERT INTO parental_schedules 
            (user_id, schedule_name, description, is_active)
            VALUES (:user_id, :schedule_name, :description, 1)
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'schedule_name' => $input['schedule_name'] ?? $template['schedule_name'],
            'description' => $template['description']
        ]);
        
        $newScheduleId = $conn->lastInsertId();
        
        // Copy time windows
        $stmt = $conn->prepare("
            SELECT * FROM schedule_windows WHERE schedule_id = :template_id
        ");
        $stmt->execute(['template_id' => $input['template_id']]);
        $windows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($windows as $window) {
            addTimeWindow($conn, $newScheduleId, $window);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'schedule_id' => $newScheduleId,
            'message' => 'Template cloned successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

/**
 * Helper: Add time window
 */
function addTimeWindow($conn, $scheduleId, $window) {
    $stmt = $conn->prepare("
        INSERT INTO schedule_windows 
        (schedule_id, day_of_week, specific_date, start_time, end_time, access_type, notes)
        VALUES (:schedule_id, :day_of_week, :specific_date, :start_time, :end_time, :access_type, :notes)
    ");
    
    $stmt->execute([
        'schedule_id' => $scheduleId,
        'day_of_week' => $window['day_of_week'] ?? null,
        'specific_date' => $window['specific_date'] ?? null,
        'start_time' => $window['start_time'],
        'end_time' => $window['end_time'],
        'access_type' => $window['access_type'],
        'notes' => $window['notes'] ?? null
    ]);
}
?>
