<?php
/**
 * TrueVault VPN - Parental Calendar UI
 * 
 * Monthly calendar view for managing schedules
 * Color-coded days:
 * - Green: Full access
 * - Yellow: Restricted (homework/streaming only)
 * - Red: Blocked
 * - Blue: Custom schedule
 * 
 * @created January 18, 2026
 */

define('TRUEVAULT_INIT', true);

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

$auth = Auth::authenticate();
if (!$auth['success']) {
    header('Location: /login.php');
    exit;
}

$userId = $auth['user']['id'];

// Get current month/year or from params
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Validate month/year
if ($month < 1 || $month > 12) $month = (int)date('n');
if ($year < 2020 || $year > 2030) $year = (int)date('Y');

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDayOfWeek = date('w', $firstDay);

$monthName = date('F Y', $firstDay);

// Previous/Next month
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parental Calendar - TrueVault VPN</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a, #1a1a2e);
            color: #fff;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            font-size: 2rem;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            color: #0f0f1a;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 12px;
        }
        
        .calendar-nav h2 {
            font-size: 1.5rem;
            color: #00d9ff;
        }
        
        .month-nav {
            display: flex;
            gap: 10px;
        }
        
        .calendar {
            background: rgba(255, 255, 255, 0.04);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }
        
        .day-header {
            text-align: center;
            font-weight: 600;
            padding: 10px;
            color: #00d9ff;
            font-size: 0.9rem;
        }
        
        .day-cell {
            aspect-ratio: 1;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: 0.2s;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 80px;
        }
        
        .day-cell:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 217, 255, 0.3);
        }
        
        .day-cell.empty {
            background: transparent;
            cursor: default;
        }
        
        .day-cell.empty:hover {
            transform: none;
            box-shadow: none;
        }
        
        .day-cell.full-access {
            background: rgba(0, 255, 136, 0.15);
            border: 2px solid #00ff88;
        }
        
        .day-cell.restricted {
            background: rgba(255, 184, 77, 0.15);
            border: 2px solid #ffb84d;
        }
        
        .day-cell.blocked {
            background: rgba(255, 82, 82, 0.15);
            border: 2px solid #ff5252;
        }
        
        .day-cell.custom {
            background: rgba(0, 217, 255, 0.15);
            border: 2px solid #00d9ff;
        }
        
        .day-cell.today {
            box-shadow: 0 0 0 3px rgba(0, 217, 255, 0.5);
        }
        
        .day-number {
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .day-status {
            font-size: 0.7rem;
            margin-top: 5px;
            opacity: 0.8;
        }
        
        .day-icons {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 5px;
        }
        
        .icon {
            font-size: 1rem;
        }
        
        .legend {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .legend-color {
            width: 30px;
            height: 30px;
            border-radius: 6px;
        }
        
        .legend-color.full {
            background: rgba(0, 255, 136, 0.15);
            border: 2px solid #00ff88;
        }
        
        .legend-color.restricted {
            background: rgba(255, 184, 77, 0.15);
            border: 2px solid #ffb84d;
        }
        
        .legend-color.blocked {
            background: rgba(255, 82, 82, 0.15);
            border: 2px solid #ff5252;
        }
        
        .legend-color.custom {
            background: rgba(0, 217, 255, 0.15);
            border: 2px solid #00d9ff;
        }
        
        @media (max-width: 768px) {
            .calendar-grid {
                gap: 5px;
            }
            
            .day-cell {
                min-height: 60px;
                padding: 5px;
            }
            
            .day-number {
                font-size: 1rem;
            }
            
            .day-status {
                font-size: 0.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Parental Calendar</h1>
            <div class="nav-buttons">
                <a href="/dashboard/" class="btn btn-secondary">← Back to Dashboard</a>
                <a href="#" class="btn btn-primary" onclick="manageSchedules()">📋 Manage Schedules</a>
            </div>
        </div>
        
        <div class="calendar-nav">
            <div class="month-nav">
                <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-secondary">← Prev</a>
            </div>
            <h2><?= $monthName ?></h2>
            <div class="month-nav">
                <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-secondary">Next →</a>
            </div>
        </div>
        
        <div class="calendar">
            <div class="calendar-grid">
                <!-- Day headers -->
                <div class="day-header">Sun</div>
                <div class="day-header">Mon</div>
                <div class="day-header">Tue</div>
                <div class="day-header">Wed</div>
                <div class="day-header">Thu</div>
                <div class="day-header">Fri</div>
                <div class="day-header">Sat</div>
                
                <!-- Empty cells for days before month starts -->
                <?php for ($i = 0; $i < $startDayOfWeek; $i++): ?>
                    <div class="day-cell empty"></div>
                <?php endfor; ?>
                
                <!-- Day cells -->
                <?php for ($day = 1; $day <= $daysInMonth; $day++): 
                    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $isToday = ($date === date('Y-m-d'));
                    $dayOfWeek = date('w', mktime(0, 0, 0, $month, $day, $year));
                    
                    // Determine day status (simplified for now - will be enhanced)
                    $status = 'full-access';
                    $statusText = 'Full Access';
                    
                    // Weekday vs Weekend logic
                    if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                        // Weekday - restricted during school hours
                        $status = 'restricted';
                        $statusText = 'School Day';
                    } else {
                        // Weekend - full access
                        $status = 'full-access';
                        $statusText = 'Weekend';
                    }
                    
                    $todayClass = $isToday ? 'today' : '';
                ?>
                    <div class="day-cell <?= $status ?> <?= $todayClass ?>" 
                         onclick="editDay('<?= $date ?>', <?= $dayOfWeek ?>)">
                        <div class="day-number"><?= $day ?></div>
                        <div class="day-status"><?= $statusText ?></div>
                        <div class="day-icons">
                            <?php if ($status === 'restricted'): ?>
                                <span class="icon">📚</span>
                            <?php elseif ($status === 'blocked'): ?>
                                <span class="icon">🚫</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color full"></div>
                <span>Full Access</span>
            </div>
            <div class="legend-item">
                <div class="legend-color restricted"></div>
                <span>Restricted (Homework/Streaming)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color blocked"></div>
                <span>Blocked</span>
            </div>
            <div class="legend-item">
                <div class="legend-color custom"></div>
                <span>Custom Schedule</span>
            </div>
        </div>
    </div>
    
    <script>
        function editDay(date, dayOfWeek) {
            // TODO: Open modal to edit schedule for this day
            console.log('Edit day:', date, 'Day of week:', dayOfWeek);
            alert('Edit schedule for ' + date + '\n\nThis will open a modal to manage time windows.');
        }
        
        function manageSchedules() {
            // TODO: Open schedule management page
            alert('Schedule management interface coming soon!');
        }
    </script>
</body>
</html>
