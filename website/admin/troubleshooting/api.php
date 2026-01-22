

function executeSSH($host, $command) {
    $user = 'root';
    $password = 'Andassi8';
    
    // Try using PHP SSH2 extension
    if (function_exists('ssh2_connect')) {
        $conn = @ssh2_connect($host, 22);
        if ($conn && @ssh2_auth_password($conn, $user, $password)) {
            $stream = ssh2_exec($conn, $command);
            stream_set_blocking($stream, true);
            $output = stream_get_contents($stream);
            fclose($stream);
            return $output;
        }
    }
    
    // Fallback: Use shell exec with sshpass
    $escapedPass = escapeshellarg($password);
    $escapedCmd = escapeshellarg($command);
    $output = shell_exec("sshpass -p {$escapedPass} ssh -o StrictHostKeyChecking=no {$user}@{$host} {$escapedCmd} 2>&1");
    
    return $output ?: 'Command executed (no output)';
}

function checkSSH($host) {
    $user = 'root';
    $password = 'Andassi8';
    
    if (function_exists('ssh2_connect')) {
        $conn = @ssh2_connect($host, 22);
        return $conn && @ssh2_auth_password($conn, $user, $password);
    }
    
    return @fsockopen($host, 22, $errno, $errstr, 2) !== false;
}

function logAction($action, $serverId, $output) {
    $db = Database::getInstance('logs');
    $stmt = $db->prepare("
        INSERT INTO admin_actions (action, server_id, output, admin_id, created_at)
        VALUES (:action, :server_id, :output, :admin_id, datetime('now'))
    ");
    $stmt->bindValue(':action', $action, SQLITE3_TEXT);
    $stmt->bindValue(':server_id', $serverId, SQLITE3_INTEGER);
    $stmt->bindValue(':output', $output, SQLITE3_TEXT);
    $stmt->bindValue(':admin_id', $_SESSION['admin_id'] ?? 1, SQLITE3_INTEGER);
    $stmt->execute();
}

function notifyUsersOfReboot($serverId) {
    // Get users connected to this server
    $db = Database::getInstance('devices');
    $stmt = $db->prepare("SELECT DISTINCT user_id FROM devices WHERE server_id = :sid");
    $stmt->bindValue(':sid', $serverId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Queue notification email
        Email::send($row['user_id'], 'server_maintenance', [
            'server_id' => $serverId,
            'message' => 'Server will reboot in 1 minute for maintenance.'
        ]);
    }
}
