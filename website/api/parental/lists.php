<?php
/**
 * TrueVault VPN - Whitelist/Blacklist API
 * Part 11 - Task 11.6
 * Domain whitelist, blacklist, and temporary blocks
 * USES SQLite3 CLASS (NOT PDO!) per Master Checklist
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Helper for fetchAll
function fetchAllAssoc($result) {
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) { $rows[] = $row; }
    return $rows;
}

$user = authenticateRequest();
if (!$user) { http_response_code(401); echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'] ?? 'whitelist';
$action = $_GET['action'] ?? 'list';

try {
    $db = new SQLite3(DB_USERS);
    $db->enableExceptions(true);
    
    switch ($method) {
        case 'GET':
            if ($type === 'whitelist') {
                $stmt = $db->prepare("SELECT * FROM parental_whitelist WHERE user_id = ? OR user_id = 0 ORDER BY category, domain");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $result = $stmt->execute();
                echo json_encode(['success' => true, 'whitelist' => fetchAllAssoc($result)]);
                
            } elseif ($type === 'blacklist') {
                $stmt = $db->prepare("SELECT * FROM blocked_domains WHERE user_id = ? ORDER BY domain");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $result = $stmt->execute();
                echo json_encode(['success' => true, 'blacklist' => fetchAllAssoc($result)]);
                
            } elseif ($type === 'temporary') {
                $stmt = $db->prepare("SELECT * FROM temporary_blocks WHERE user_id = ? AND blocked_until > CURRENT_TIMESTAMP ORDER BY blocked_until");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $result = $stmt->execute();
                echo json_encode(['success' => true, 'temporary_blocks' => fetchAllAssoc($result)]);
                
            } elseif ($type === 'suggestions') {
                $educational = ['khanacademy.org', 'wikipedia.org', 'duolingo.com', 'quizlet.com', 'codecademy.com', 'coursera.org', 'edx.org', 'mathway.com', 'wolframalpha.com', 'britannica.com'];
                $social = ['facebook.com', 'instagram.com', 'tiktok.com', 'snapchat.com', 'twitter.com', 'x.com', 'reddit.com', 'tumblr.com', 'pinterest.com'];
                $streaming = ['netflix.com', 'hulu.com', 'disneyplus.com', 'hbomax.com', 'youtube.com', 'twitch.tv', 'peacocktv.com', 'paramountplus.com'];
                $gaming = ['roblox.com', 'minecraft.net', 'fortnite.com', 'epicgames.com', 'steampowered.com', 'ea.com', 'blizzard.com'];
                
                echo json_encode(['success' => true, 'suggestions' => [
                    'educational' => $educational, 'social_media' => $social, 'streaming' => $streaming, 'gaming' => $gaming
                ]]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $domain = strtolower(trim($input['domain'] ?? ''));
            
            if (empty($domain)) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Domain required']); exit; }
            
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = preg_replace('#^www\.#', '', $domain);
            $domain = rtrim($domain, '/');
            
            if ($type === 'whitelist') {
                $stmt = $db->prepare("INSERT OR REPLACE INTO parental_whitelist (user_id, device_id, domain, category, notes, added_by) VALUES (?, ?, ?, ?, ?, 'parent')");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $input['device_id'] ?? null, SQLITE3_INTEGER);
                $stmt->bindValue(3, $domain, SQLITE3_TEXT);
                $stmt->bindValue(4, $input['category'] ?? 'custom', SQLITE3_TEXT);
                $stmt->bindValue(5, $input['notes'] ?? '', SQLITE3_TEXT);
                $stmt->execute();
                echo json_encode(['success' => true, 'id' => $db->lastInsertRowID(), 'domain' => $domain]);
                
            } elseif ($type === 'blacklist') {
                $stmt = $db->prepare("INSERT OR REPLACE INTO blocked_domains (user_id, domain, reason) VALUES (?, ?, ?)");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $domain, SQLITE3_TEXT);
                $stmt->bindValue(3, $input['reason'] ?? 'Blocked by parent', SQLITE3_TEXT);
                $stmt->execute();
                echo json_encode(['success' => true, 'id' => $db->lastInsertRowID(), 'domain' => $domain]);
                
            } elseif ($type === 'temporary') {
                $duration = $input['duration'] ?? '1_hour';
                $until = match($duration) {
                    '1_hour' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                    '2_hours' => date('Y-m-d H:i:s', strtotime('+2 hours')),
                    'until_bedtime' => date('Y-m-d') . ' 20:00:00',
                    'until_tomorrow' => date('Y-m-d', strtotime('+1 day')) . ' 07:00:00',
                    '1_week' => date('Y-m-d H:i:s', strtotime('+1 week')),
                    default => date('Y-m-d H:i:s', strtotime('+1 hour'))
                };
                
                $stmt = $db->prepare("INSERT INTO temporary_blocks (user_id, device_id, domain, blocked_until, reason, blocked_by) VALUES (?, ?, ?, ?, ?, 'parent')");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $input['device_id'] ?? null, SQLITE3_INTEGER);
                $stmt->bindValue(3, $domain, SQLITE3_TEXT);
                $stmt->bindValue(4, $until, SQLITE3_TEXT);
                $stmt->bindValue(5, $input['reason'] ?? '', SQLITE3_TEXT);
                $stmt->execute();
                echo json_encode(['success' => true, 'id' => $db->lastInsertRowID(), 'domain' => $domain, 'blocked_until' => $until]);
                
            } elseif ($action === 'bulk_add') {
                $domains = $input['domains'] ?? [];
                $added = 0;
                foreach ($domains as $d) {
                    $d = strtolower(trim($d));
                    if (empty($d)) continue;
                    try {
                        if ($type === 'whitelist') {
                            $stmt = $db->prepare("INSERT OR IGNORE INTO parental_whitelist (user_id, domain, category) VALUES (?, ?, ?)");
                            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                            $stmt->bindValue(2, $d, SQLITE3_TEXT);
                            $stmt->bindValue(3, $input['category'] ?? 'custom', SQLITE3_TEXT);
                        } else {
                            $stmt = $db->prepare("INSERT OR IGNORE INTO blocked_domains (user_id, domain) VALUES (?, ?)");
                            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                            $stmt->bindValue(2, $d, SQLITE3_TEXT);
                        }
                        $stmt->execute();
                        $added++;
                    } catch (Exception $e) { /* skip duplicates */ }
                }
                echo json_encode(['success' => true, 'added' => $added]);
            }
            break;
            
        case 'DELETE':
            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            $domain = $_GET['domain'] ?? null;
            
            if ($type === 'whitelist') {
                if ($id) {
                    $stmt = $db->prepare("DELETE FROM parental_whitelist WHERE id = ? AND user_id = ?");
                    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
                    $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
                } elseif ($domain) {
                    $stmt = $db->prepare("DELETE FROM parental_whitelist WHERE domain = ? AND user_id = ?");
                    $stmt->bindValue(1, $domain, SQLITE3_TEXT);
                    $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
                }
                if (isset($stmt)) $stmt->execute();
            } elseif ($type === 'blacklist') {
                if ($id) {
                    $stmt = $db->prepare("DELETE FROM blocked_domains WHERE id = ? AND user_id = ?");
                    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
                    $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
                } elseif ($domain) {
                    $stmt = $db->prepare("DELETE FROM blocked_domains WHERE domain = ? AND user_id = ?");
                    $stmt->bindValue(1, $domain, SQLITE3_TEXT);
                    $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
                }
                if (isset($stmt)) $stmt->execute();
            } elseif ($type === 'temporary') {
                if ($id) {
                    $stmt = $db->prepare("DELETE FROM temporary_blocks WHERE id = ? AND user_id = ?");
                    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
                    $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                }
            }
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
