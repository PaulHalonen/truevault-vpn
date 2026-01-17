<?php
/**
 * TrueVault VPN - WireGuard Server Manager
 * Handles peer management on VPN servers via SSH
 */

class WireGuard {
    private $sshUser = 'root';
    private $sshKeyPath = '/path/to/ssh/key'; // Configure this
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/Database.php';
        $this->db = new Database('servers');
    }
    
    /**
     * Get server details by ID
     */
    public function getServer($serverId) {
        $stmt = $this->db->prepare("SELECT * FROM servers WHERE id = ?");
        $stmt->execute([$serverId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all active servers
     */
    public function getActiveServers() {
        return $this->db->query("SELECT * FROM servers WHERE is_active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate WireGuard client configuration
     * 
     * @param array $server Server details
     * @param string $clientPrivateKey Client's private key
     * @param string $clientPublicKey Client's public key  
     * @param string $assignedIp Client's assigned IP (e.g., 10.0.0.5/32)
     * @return string WireGuard config file contents
     */
    public function generateClientConfig($server, $clientPrivateKey, $clientPublicKey, $assignedIp) {
        $serverEndpoint = $server['ip'] . ':' . ($server['port'] ?? 51820);
        $serverPublicKey = $server['public_key'] ?? '';
        $dns = $server['dns'] ?? '1.1.1.1, 8.8.8.8';
        
        // Clean up assigned IP - ensure /32 suffix
        if (strpos($assignedIp, '/') === false) {
            $clientIp = $assignedIp . '/32';
        } else {
            $clientIp = $assignedIp;
        }
        
        // Get just the IP without CIDR for Address field
        $ipOnly = explode('/', $assignedIp)[0];
        
        $config = "[Interface]\n";
        $config .= "PrivateKey = {$clientPrivateKey}\n";
        $config .= "Address = {$ipOnly}/24\n";
        $config .= "DNS = {$dns}\n";
        $config .= "\n";
        $config .= "[Peer]\n";
        $config .= "PublicKey = {$serverPublicKey}\n";
        $config .= "AllowedIPs = 0.0.0.0/0, ::/0\n";
        $config .= "Endpoint = {$serverEndpoint}\n";
        $config .= "PersistentKeepalive = 25\n";
        
        return $config;
    }
    
    /**
     * Add a peer to server (requires SSH access)
     * NOTE: This is a placeholder - actual SSH implementation needed
     */
    public function addPeerToServer($serverId, $publicKey, $allowedIps) {
        $server = $this->getServer($serverId);
        if (!$server) {
            return ['success' => false, 'error' => 'Server not found'];
        }
        
        // Build wg command
        $cmd = sprintf(
            'wg set wg0 peer %s allowed-ips %s',
            escapeshellarg($publicKey),
            escapeshellarg($allowedIps)
        );
        
        // In production, execute via SSH
        // $result = $this->executeSSH($server['ip'], $cmd);
        
        // For now, log the action
        $this->logAction($serverId, 'add_peer', [
            'public_key' => substr($publicKey, 0, 20) . '...',
            'allowed_ips' => $allowedIps
        ]);
        
        return ['success' => true, 'message' => 'Peer add command queued'];
    }
    
    /**
     * Remove a peer from server
     */
    public function removePeerFromServer($serverId, $publicKey) {
        $server = $this->getServer($serverId);
        if (!$server) {
            return ['success' => false, 'error' => 'Server not found'];
        }
        
        $cmd = sprintf('wg set wg0 peer %s remove', escapeshellarg($publicKey));
        
        // Log the action
        $this->logAction($serverId, 'remove_peer', [
            'public_key' => substr($publicKey, 0, 20) . '...'
        ]);
        
        return ['success' => true, 'message' => 'Peer remove command queued'];
    }
    
    /**
     * Get list of peers on server
     */
    public function getPeerList($serverId) {
        $server = $this->getServer($serverId);
        if (!$server) {
            return ['success' => false, 'error' => 'Server not found'];
        }
        
        // Would execute: wg show wg0 peers
        // Parse output for peer list
        
        return ['success' => true, 'peers' => []];
    }
    
    /**
     * Get next available IP for a server
     */
    public function getNextAvailableIp($serverId) {
        require_once __DIR__ . '/Database.php';
        $devicesDb = new Database('devices');
        
        // Get server's IP range (default: 10.0.X.0/24 where X is server ID)
        $server = $this->getServer($serverId);
        $baseOctet = $server['id'] ?? 1;
        
        // Find used IPs for this server
        $stmt = $devicesDb->prepare("
            SELECT assigned_ip FROM devices WHERE server_id = ?
        ");
        $stmt->execute([$serverId]);
        $usedIps = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Find next available (2-254, skip .1 for server)
        for ($i = 2; $i <= 254; $i++) {
            $ip = "10.0.{$baseOctet}.{$i}";
            if (!in_array($ip, $usedIps) && !in_array("{$ip}/32", $usedIps)) {
                return $ip;
            }
        }
        
        return null; // No IPs available
    }
    
    /**
     * Get best server for new device
     * Considers: load, user's tier, server type
     */
    public function getBestServer($userId = null, $preferredLocation = null) {
        // Check if user is VIP
        $isVip = false;
        if ($userId) {
            $mainDb = new Database('main');
            $stmt = $mainDb->prepare("SELECT tier FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $isVip = ($user && $user['tier'] === 'vip');
        }
        
        // Get available servers
        $query = "SELECT * FROM servers WHERE is_active = 1";
        
        if ($isVip) {
            // VIP users can use VIP servers
            // Order by type (vip first) then load
            $query .= " ORDER BY CASE WHEN type = 'vip' THEN 0 ELSE 1 END, current_users ASC";
        } else {
            // Non-VIP users get shared servers only
            $query .= " AND type != 'vip' ORDER BY current_users ASC";
        }
        
        $servers = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($servers)) {
            return null;
        }
        
        // If preferred location specified, try to match
        if ($preferredLocation) {
            foreach ($servers as $server) {
                if (stripos($server['location'], $preferredLocation) !== false ||
                    stripos($server['name'], $preferredLocation) !== false) {
                    return $server;
                }
            }
        }
        
        // Return least loaded server
        return $servers[0];
    }
    
    /**
     * Check server health
     */
    public function checkServerHealth($serverId) {
        $server = $this->getServer($serverId);
        if (!$server) {
            return ['success' => false, 'error' => 'Server not found'];
        }
        
        $ip = $server['ip'];
        $port = $server['port'] ?? 51820;
        
        $result = [
            'server_id' => $serverId,
            'ip' => $ip,
            'ping' => false,
            'port' => false,
            'status' => 'offline'
        ];
        
        // Ping check
        $pingOutput = [];
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("ping -n 1 -w 2000 " . escapeshellarg($ip), $pingOutput, $pingReturn);
        } else {
            exec("ping -c 1 -W 2 " . escapeshellarg($ip), $pingOutput, $pingReturn);
        }
        $result['ping'] = ($pingReturn === 0);
        
        // Port check
        $socket = @fsockopen($ip, $port, $errno, $errstr, 3);
        if ($socket) {
            $result['port'] = true;
            fclose($socket);
        }
        
        // Determine status
        if ($result['ping'] && $result['port']) {
            $result['status'] = 'online';
        } elseif ($result['ping']) {
            $result['status'] = 'degraded';
        }
        
        // Update database
        $stmt = $this->db->prepare("
            UPDATE servers SET last_status = ?, last_check = datetime('now') WHERE id = ?
        ");
        $stmt->execute([$result['status'], $serverId]);
        
        return $result;
    }
    
    /**
     * Update server user count
     */
    public function updateServerUserCount($serverId) {
        $devicesDb = new Database('devices');
        
        $stmt = $devicesDb->prepare("
            SELECT COUNT(*) FROM devices WHERE server_id = ? AND is_active = 1
        ");
        $stmt->execute([$serverId]);
        $count = $stmt->fetchColumn();
        
        $stmt = $this->db->prepare("UPDATE servers SET current_users = ? WHERE id = ?");
        $stmt->execute([$count, $serverId]);
        
        return $count;
    }
    
    /**
     * Log server action
     */
    private function logAction($serverId, $action, $details = []) {
        $logsDb = new Database('logs');
        
        $logsDb->exec("
            CREATE TABLE IF NOT EXISTS wireguard_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                server_id INTEGER,
                action TEXT,
                details TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $stmt = $logsDb->prepare("INSERT INTO wireguard_log (server_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$serverId, $action, json_encode($details)]);
    }
    
    /**
     * Execute SSH command on server
     * NOTE: Requires proper SSH key setup
     */
    private function executeSSH($host, $command, $user = null) {
        $user = $user ?? $this->sshUser;
        
        // Build SSH command
        $sshCmd = sprintf(
            'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 %s@%s %s',
            escapeshellarg($user),
            escapeshellarg($host),
            escapeshellarg($command)
        );
        
        $output = [];
        $returnCode = 0;
        exec($sshCmd . ' 2>&1', $output, $returnCode);
        
        return [
            'success' => $returnCode === 0,
            'output' => implode("\n", $output),
            'return_code' => $returnCode
        ];
    }
}
