
    
    <script>
        const API_BASE = '/admin/troubleshooting/api.php';
        
        document.addEventListener('DOMContentLoaded', () => { checkAllServers(); loadLogs(); });
        
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
                    badge.textContent = data.online ? 'Online' : 'Offline';
                    badge.className = 'status-badge ' + (data.online ? 'status-online' : 'status-offline');
                } catch (e) {
                    badge.textContent = 'Error';
                    badge.className = 'status-badge status-offline';
                }
            }
        }
        
        async function runFix(action) {
            const serverId = document.getElementById('targetServer').value;
            if (!serverId) { alert('Please select a server first'); return; }
            if (action === 'reboot_server' && !confirm('Reboot server? All users will be disconnected.')) return;
            const output = document.getElementById('fixOutput');
            output.textContent = `Running ${action}...\n`;
            try {
                const resp = await fetch(`${API_BASE}?action=${action}&server_id=${serverId}`);
                const data = await resp.json();
                output.textContent += data.output || JSON.stringify(data, null, 2);
            } catch (e) { output.textContent += `Error: ${e.message}`; }
        }
        
        async function runDiagnostics() {
            const serverId = document.getElementById('targetServer').value;
            if (!serverId) { alert('Please select a server first'); return; }
            const checks = ['ping', 'ssh', 'wg', 'port', 'api', 'disk'];
            checks.forEach(c => { document.getElementById('check-' + c).textContent = '⏳'; });
            try {
                const resp = await fetch(`${API_BASE}?action=full_diagnostics&server_id=${serverId}`);
                const data = await resp.json();
                for (const [check, result] of Object.entries(data.checks || {})) {
                    const icon = document.getElementById('check-' + check);
                    if (icon) { icon.textContent = result ? '✅' : '❌'; icon.className = 'check-icon ' + (result ? 'check-ok' : 'check-fail'); }
                }
            } catch (e) { console.error('Diagnostics error:', e); }
        }
        
        async function runManual(action) {
            const serverId = document.getElementById('targetServer').value;
            if (!serverId && action !== 'send_maintenance') { alert('Please select a server first'); return; }
            if (!confirm(`Run ${action}?`)) return;
            const output = document.getElementById('fixOutput');
            output.textContent = `Running ${action}...\n`;
            try {
                const resp = await fetch(`${API_BASE}?action=${action}&server_id=${serverId}`);
                const data = await resp.json();
                output.textContent += data.output || JSON.stringify(data, null, 2);
            } catch (e) { output.textContent += `Error: ${e.message}`; }
        }
        
        async function loadLogs() {
            try {
                const resp = await fetch(`${API_BASE}?action=get_logs`);
                const data = await resp.json();
                document.getElementById('serverLogs').textContent = data.logs || 'No logs found';
            } catch (e) { document.getElementById('serverLogs').textContent = `Error: ${e.message}`; }
        }
    </script>
</body>
</html>
