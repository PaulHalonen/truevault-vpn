<?php
/**
 * TrueVault VPN - Content Calendar View
 * Part 15 - Calendar Browser
 * View and manage content calendar
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_CAMPAIGNS', DB_PATH . 'campaigns.db');

$db = new SQLite3(DB_CAMPAIGNS);
$db->enableExceptions(true);

$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Get calendar content for the month
$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = date('Y-m-t', strtotime($startDate));

$stmt = $db->prepare("SELECT * FROM content_calendar WHERE calendar_date BETWEEN ? AND ? ORDER BY calendar_date");
$stmt->bindValue(1, $startDate, SQLITE3_TEXT);
$stmt->bindValue(2, $endDate, SQLITE3_TEXT);
$result = $stmt->execute();

$content = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $day = intval(date('j', strtotime($row['calendar_date'])));
    $content[$day] = $row;
}

$db->close();

// Calendar calculation
$firstDay = date('w', strtotime($startDate));
$daysInMonth = date('t', strtotime($startDate));
$monthName = date('F Y', strtotime($startDate));

$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$typeColors = [
    'tip' => '#00d9ff',
    'news' => '#9c27b0',
    'testimonial' => '#ff9800',
    'feature' => '#4caf50',
    'promo' => '#f44336',
    'promotion' => '#e91e63',
    'fact' => '#2196f3',
    'roundup' => '#607d8b',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Calendar - Marketing</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .header h1 { font-size: 1.5rem; }
        .nav-links { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .container { padding: 25px; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .calendar-header h2 { font-size: 1.8rem; }
        .month-nav { display: flex; gap: 10px; align-items: center; }
        .month-nav a { padding: 8px 16px; background: rgba(255,255,255,0.05); border-radius: 6px; color: #fff; text-decoration: none; }
        .month-nav a:hover { background: rgba(255,255,255,0.1); }
        .legend { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
        .legend-item { display: flex; align-items: center; gap: 6px; font-size: 0.85rem; }
        .legend-item .dot { width: 12px; height: 12px; border-radius: 3px; }
        .calendar { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; background: rgba(255,255,255,0.05); border-radius: 12px; overflow: hidden; }
        .calendar-day-header { padding: 12px; text-align: center; background: rgba(255,255,255,0.05); color: #888; font-weight: 500; font-size: 0.85rem; }
        .calendar-day { min-height: 120px; background: rgba(255,255,255,0.02); padding: 8px; position: relative; }
        .calendar-day.other-month { opacity: 0.3; }
        .calendar-day.today { background: rgba(0,217,255,0.08); }
        .calendar-day.holiday { background: rgba(255,183,77,0.08); }
        .day-number { font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; }
        .day-content { display: flex; flex-direction: column; gap: 4px; }
        .content-item { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer; transition: all 0.2s; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .content-item:hover { transform: scale(1.02); }
        .content-item.posted { opacity: 0.6; text-decoration: line-through; }
        .holiday-badge { position: absolute; top: 5px; right: 5px; font-size: 0.65rem; background: rgba(255,183,77,0.3); color: #ffb74d; padding: 2px 6px; border-radius: 3px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1a2e; border-radius: 12px; padding: 25px; width: 500px; max-width: 95%; max-height: 80vh; overflow-y: auto; }
        .modal-content h3 { margin-bottom: 20px; }
        .modal-content .close { float: right; background: none; border: none; color: #888; font-size: 1.5rem; cursor: pointer; }
        .detail-row { margin-bottom: 15px; }
        .detail-row label { display: block; color: #888; font-size: 0.85rem; margin-bottom: 5px; }
        .detail-row .value { background: rgba(255,255,255,0.05); padding: 12px; border-radius: 8px; }
        .detail-row .platforms { display: flex; gap: 8px; flex-wrap: wrap; }
        .detail-row .platform { padding: 4px 10px; background: rgba(0,217,255,0.15); color: #00d9ff; border-radius: 4px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📅 Content Calendar</h1>
        <div class="nav-links">
            <a href="calendar-generator.php" class="btn btn-primary">🔄 Generate</a>
            <a href="index.php" class="btn btn-secondary">⬅️ Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <div class="calendar-header">
            <h2><?= $monthName ?></h2>
            <div class="month-nav">
                <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>">◄ Prev</a>
                <a href="?month=<?= date('m') ?>&year=<?= date('Y') ?>">Today</a>
                <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>">Next ►</a>
            </div>
        </div>
        
        <div class="legend">
            <?php foreach ($typeColors as $type => $color): ?>
            <div class="legend-item">
                <span class="dot" style="background:<?= $color ?>;"></span>
                <span><?= ucfirst($type) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="calendar">
            <div class="calendar-day-header">Sun</div>
            <div class="calendar-day-header">Mon</div>
            <div class="calendar-day-header">Tue</div>
            <div class="calendar-day-header">Wed</div>
            <div class="calendar-day-header">Thu</div>
            <div class="calendar-day-header">Fri</div>
            <div class="calendar-day-header">Sat</div>
            
            <?php
            $today = date('Y-m-d');
            $day = 1;
            
            for ($row = 0; $row < 6; $row++):
                for ($col = 0; $col < 7; $col++):
                    $cellNum = $row * 7 + $col;
                    $isCurrentMonth = ($cellNum >= $firstDay && $day <= $daysInMonth);
                    
                    if ($isCurrentMonth):
                        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $isToday = ($dateStr === $today);
                        $hasContent = isset($content[$day]);
                        $isHoliday = $hasContent && $content[$day]['is_holiday'];
                        $item = $hasContent ? $content[$day] : null;
            ?>
            <div class="calendar-day <?= $isToday ? 'today' : '' ?> <?= $isHoliday ? 'holiday' : '' ?>" 
                 <?php if ($hasContent): ?>onclick="showDetail(<?= htmlspecialchars(json_encode($item)) ?>)"<?php endif; ?>>
                <div class="day-number"><?= $day ?></div>
                <?php if ($isHoliday): ?>
                <span class="holiday-badge"><?= htmlspecialchars($item['holiday_name']) ?></span>
                <?php endif; ?>
                <?php if ($hasContent): ?>
                <div class="day-content">
                    <div class="content-item <?= $item['is_posted'] ? 'posted' : '' ?>" 
                         style="background: <?= $typeColors[$item['post_type']] ?? '#666' ?>20; border-left: 3px solid <?= $typeColors[$item['post_type']] ?? '#666' ?>;">
                        <?= htmlspecialchars(substr($item['post_title'], 0, 25)) ?>...
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php
                        $day++;
                    else:
            ?>
            <div class="calendar-day other-month"></div>
            <?php
                    endif;
                endfor;
                if ($day > $daysInMonth) break;
            endfor;
            ?>
        </div>
    </div>
    
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <button class="close" onclick="closeModal()">&times;</button>
            <h3 id="modalTitle"></h3>
            
            <div class="detail-row">
                <label>Type</label>
                <div class="value" id="modalType"></div>
            </div>
            
            <div class="detail-row">
                <label>Platforms</label>
                <div class="value platforms" id="modalPlatforms"></div>
            </div>
            
            <div class="detail-row">
                <label>Content</label>
                <div class="value" id="modalContent" style="white-space:pre-wrap;"></div>
            </div>
            
            <div class="detail-row" id="discountRow" style="display:none;">
                <label>Promotion</label>
                <div class="value" id="modalDiscount"></div>
            </div>
            
            <div class="detail-row">
                <label>Status</label>
                <div class="value" id="modalStatus"></div>
            </div>
        </div>
    </div>
    
    <script>
        function showDetail(item) {
            document.getElementById('modalTitle').textContent = item.post_title;
            document.getElementById('modalType').textContent = item.post_type.charAt(0).toUpperCase() + item.post_type.slice(1);
            document.getElementById('modalContent').textContent = item.post_content;
            document.getElementById('modalStatus').innerHTML = item.is_posted ? 
                '<span style="color:#00c853">✅ Posted</span>' : 
                '<span style="color:#ffb74d">⏳ Scheduled</span>';
            
            const platforms = JSON.parse(item.platforms || '[]');
            document.getElementById('modalPlatforms').innerHTML = platforms.map(p => 
                `<span class="platform">${p}</span>`
            ).join('');
            
            if (item.pricing_override) {
                const pricing = JSON.parse(item.pricing_override);
                document.getElementById('discountRow').style.display = 'block';
                document.getElementById('modalDiscount').innerHTML = 
                    `<strong style="color:#00ff88">${pricing.discount}% OFF</strong> • Code: <code>${pricing.code}</code>`;
            } else {
                document.getElementById('discountRow').style.display = 'none';
            }
            
            document.getElementById('detailModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('detailModal').classList.remove('active');
        }
        
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
