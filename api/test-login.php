<?php
/**
 * TrueVault VPN - Login Diagnostic Test
 */
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

// Test 1: Check if database.php exists and loads
try {
    $dbFile = __DIR__ . '/config/database.php';
    $results['tests']['database_file'] = [
        'exists' => file_exists($dbFile),
        'path' => $dbFile
    ];
    
    if (file_exists($dbFile)) {
        require_once $dbFile;
        $results['tests']['database_class'] = [
            'loaded' => class_exists('Database'),
            'methods' => class_exists('Database') ? get_class_methods('Database') : []
        ];
    }
} catch (Exception $e) {
    $results['tests']['database_file'] = ['error' => $e->getMessage()];
}

// Test 2: Check data folder
$dataPath = __DIR__ . '/../data';
$results['tests']['data_folder'] = [
    'path' => realpath($dataPath) ?: $dataPath,
    'exists' => is_dir($dataPath),
    'writable' => is_writable($dataPath)
];

if (is_dir($dataPath)) {
    $files = glob($dataPath . '/*.db');
    $results['tests']['data_folder']['databases'] = array_map('basename', $files);
}

// Test 3: Check users.db specifically
$usersDb = $dataPath . '/users.db';
$results['tests']['users_db'] = [
    'path' => $usersDb,
    'exists' => file_exists($usersDb)
];

if (file_exists($usersDb)) {
    try {
        $db = new SQLite3($usersDb);
        
        // Check if users table exists
        $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        $hasTable = $tableCheck->fetchArray() ? true : false;
        $results['tests']['users_db']['has_users_table'] = $hasTable;
        
        if ($hasTable) {
            // Count users
            $count = $db->querySingle("SELECT COUNT(*) FROM users");
            $results['tests']['users_db']['user_count'] = $count;
            
            // List users (email only)
            $users = $db->query("SELECT id, email, is_vip, status FROM users LIMIT 10");
            $userList = [];
            while ($row = $users->fetchArray(SQLITE3_ASSOC)) {
                $userList[] = $row;
            }
            $results['tests']['users_db']['users'] = $userList;
        }
        
        $db->close();
    } catch (Exception $e) {
        $results['tests']['users_db']['error'] = $e->getMessage();
    }
}

// Test 4: Check vip.db
$vipDb = $dataPath . '/vip.db';
$results['tests']['vip_db'] = [
    'path' => $vipDb,
    'exists' => file_exists($vipDb)
];

if (file_exists($vipDb)) {
    try {
        $db = new SQLite3($vipDb);
        
        // Check tables
        $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='vip_users'");
        $hasTable = $tableCheck->fetchArray() ? true : false;
        $results['tests']['vip_db']['has_vip_users_table'] = $hasTable;
        
        if ($hasTable) {
            // List VIP users
            $vips = $db->query("SELECT * FROM vip_users");
            $vipList = [];
            while ($row = $vips->fetchArray(SQLITE3_ASSOC)) {
                $vipList[] = $row;
            }
            $results['tests']['vip_db']['vip_users'] = $vipList;
        }
        
        $db->close();
    } catch (Exception $e) {
        $results['tests']['vip_db']['error'] = $e->getMessage();
    }
}

// Test 5: Check helper files
$helpers = ['auth.php', 'response.php', 'vip.php'];
foreach ($helpers as $helper) {
    $path = __DIR__ . '/helpers/' . $helper;
    $results['tests']['helpers'][$helper] = [
        'exists' => file_exists($path),
        'size' => file_exists($path) ? filesize($path) : 0
    ];
}

// Test 6: Check JWT config
$jwtFile = __DIR__ . '/config/jwt.php';
$results['tests']['jwt_config'] = [
    'exists' => file_exists($jwtFile)
];

// Test 7: Try to load all required files for login
try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/jwt.php';
    require_once __DIR__ . '/helpers/response.php';
    require_once __DIR__ . '/helpers/auth.php';
    require_once __DIR__ . '/helpers/vip.php';
    
    $results['tests']['all_requires'] = [
        'success' => true,
        'classes_loaded' => [
            'Database' => class_exists('Database'),
            'JWTManager' => class_exists('JWTManager'),
            'Response' => class_exists('Response'),
            'Auth' => class_exists('Auth'),
            'VIPManager' => class_exists('VIPManager'),
            'PlanLimits' => class_exists('PlanLimits'),
            'ServerRules' => class_exists('ServerRules')
        ]
    ];
} catch (Exception $e) {
    $results['tests']['all_requires'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
} catch (Error $e) {
    $results['tests']['all_requires'] = [
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT);
