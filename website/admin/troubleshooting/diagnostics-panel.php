
            
            <!-- Diagnostic Information -->
            <div class="card">
                <h2>🔍 Diagnostic Checks</h2>
                <ul class="diagnostics-list" id="diagnosticsList">
                    <li>
                        <span>Ping Response</span>
                        <span class="check-icon check-pending" id="check-ping">⏳</span>
                    </li>
                    <li>
                        <span>SSH Connection</span>
                        <span class="check-icon check-pending" id="check-ssh">⏳</span>
                    </li>
                    <li>
                        <span>WireGuard Running</span>
                        <span class="check-icon check-pending" id="check-wg">⏳</span>
                    </li>
                    <li>
                        <span>Port 51820 Open</span>
                        <span class="check-icon check-pending" id="check-port">⏳</span>
                    </li>
                    <li>
                        <span>API Responsive</span>
                        <span class="check-icon check-pending" id="check-api">⏳</span>
                    </li>
                    <li>
                        <span>Disk Space OK</span>
                        <span class="check-icon check-pending" id="check-disk">⏳</span>
                    </li>
                </ul>
                <button class="btn btn-primary" onclick="runDiagnostics()" style="margin-top:15px;">▶️ Run Full Diagnostics</button>
            </div>
            
            <!-- Manual Intervention -->
            <div class="card">
                <h2>🛠️ Manual Intervention</h2>
                
                <div class="alert alert-warning">
                    ⚠️ These actions may affect active users. Use with caution.
                </div>
                
                <div class="btn-group" style="flex-direction: column;">
                    <button class="btn btn-warning" onclick="runManual('migrate_users')">
                        👥 Migrate Users to Backup Server
                    </button>
                    <button class="btn btn-warning" onclick="runManual('regenerate_keys')">
                        🔑 Regenerate Server Keys
                    </button>
                    <button class="btn btn-danger" onclick="runManual('force_disconnect')">
                        ⛔ Force Disconnect All Peers
                    </button>
                    <button class="btn btn-primary" onclick="runManual('send_maintenance')">
                        📧 Send Maintenance Notice
                    </button>
                </div>
            </div>
            
            <!-- Recent Logs -->
            <div class="card" style="grid-column: span 2;">
                <h2>📜 Recent Server Logs</h2>
                <div class="output-box" id="serverLogs" style="max-height: 200px;">Loading logs...</div>
                <button class="btn btn-primary btn-sm" onclick="loadLogs()" style="margin-top:10px;">🔄 Refresh Logs</button>
            </div>
        </div>
    </div>
    
    <script>
        const API_BASE = '/admin/troubleshooting/api.php';
        
        // Check all server statuses on page load
        document.addEventListener('DOMContentLoaded', () => {
            checkAllServers();
            loadLogs();
        });
        
        async function checkAllServers() {
            const servers = document.querySelectorAll('[data-server-id]');
            
            for (const server of servers) {
                const id = server.dataset.serverId;
                const badge = document.getElementById('status-' + id);
                badge.textContent = 'Checking...';
                badge.className = 'status-badge status-checking';
                
                try {
                    const resp = await fetch(`${API_BASE}?action=check_server&id=${id}`);
                    const data = await resp.json();
                    
                    if (data.online) {
                        badge.textContent = 'Online';
                        badge.className = 'status-badge status-online';
                    } else {
                        badge.textContent = 'Offline';
                        badge.className = 'status-badge status-offline';
                    }
                } catch (e) {
                    badge.textContent = 'Error';
                    badge.className = 'status-badge status-offline';
                }
            }
        }
        
        async function runFix(action) {
            const select = document.getElementById('targetServer');
            const serverId = select.value;
            
            if (!serverId) {
                alert('Please select a server first');
                return;
            }
            
            if (action === 'reboot_server' && !confirm('Are you sure you want to reboot this server? All users will be disconnected.')) {
                return;
            }
            
            const output = document.getElementById('fixOutput');
            output.textContent = `Running ${action}...\n`;
            
            try {
                const resp = await fetch(`${API_BASE}?action=${action}&server_id=${serverId}`);
                const data = await resp.json();
                
                output.textContent += data.output || JSON.stringify(data, null, 2);
            } catch (e) {
                output.textContent += `Error: ${e.message}`;
            }
        }
        
        async function runDiagnostics() {
            const select = document.getElementById('targetServer');
            const serverId = select.value;
            
            if (!serverId) {
                alert('Please select a server first');
                return;
            }
            
            const checks = ['ping', 'ssh', 'wg', 'port', 'api', 'disk'];
            
            for (const check of checks) {
                const icon = document.getElementById('check-' + check);
                icon.textContent = '⏳';
                icon.className = 'check-icon check-pending';
            }
            
            try {
                const resp = await fetch(`${API_BASE}?action=full_diagnostics&server_id=${serverId}`);
                const data = await resp.json();
                
                for (const [check, result] of Object.entries(data.checks || {})) {
                    const icon = document.getElementById('check-' + check);
                    if (icon) {
                        icon.textContent = result ? '✅' : '❌';
                        icon.className = 'check-icon ' + (result ? 'check-ok' : 'check-fail');
                    }
                }
            } catch (e) {
                console.error('Diagnostics error:', e);
            }
        }
        
        async function runManual(action) {
            const select = document.getElementById('targetServer');
            const serverId = select.value;
            
            if (!serverId && action !== 'send_maintenance') {
                alert('Please select a server first');
                return;
            }
            
            if (!confirm(`Are you sure you want to run: ${action}?`)) {
                return;
            }
            
            const output = document.getElementById('fixOutput');
            output.textContent = `Running ${action}...\n`;
            
            try {
                const resp = await fetch(`${API_BASE}?action=${action}&server_id=${serverId}`);
                const data = await resp.json();
                
                output.textContent += data.output || JSON.stringify(data, null, 2);
            } catch (e) {
                output.textContent += `Error: ${e.message}`;
            }
        }
        
        async function loadLogs() {
            const logsBox = document.getElementById('serverLogs');
            
            try {
                const resp = await fetch(`${API_BASE}?action=get_logs`);
                const data = await resp.json();
                
                logsBox.textContent = data.logs || 'No logs found';
            } catch (e) {
                logsBox.textContent = `Error loading logs: ${e.message}`;
            }
        }
    </script>
</body>
</html>
