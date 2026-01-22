

# ============== WEB SERVER ==============
HTML = '''<!DOCTYPE html>
<html><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>TrueVault Network Scanner</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(135deg,#0f0f1a,#1a1a2e);color:#fff;min-height:100vh;padding:20px}
.container{max-width:1100px;margin:0 auto}
header{display:flex;align-items:center;justify-content:space-between;margin-bottom:25px;flex-wrap:wrap;gap:15px}
.logo{display:flex;align-items:center;gap:12px}
.logo h1{font-size:1.6rem;background:linear-gradient(90deg,#00d9ff,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.badge{padding:6px 14px;border-radius:20px;font-size:.85rem;font-weight:600}
.badge-ok{background:rgba(0,255,136,.15);color:#00ff88;border:1px solid #00ff88}
.badge-no{background:rgba(255,100,100,.15);color:#ff6464;border:1px solid #ff6464}
.card{background:rgba(255,255,255,.04);border-radius:14px;padding:18px;margin-bottom:18px;border:1px solid rgba(255,255,255,.08)}
.card h2{font-size:1.15rem;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.btn{padding:10px 20px;border:none;border-radius:8px;font-size:.95rem;font-weight:600;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:linear-gradient(90deg,#00d9ff,#00ff88);color:#0f0f1a}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 4px 15px rgba(0,217,255,.3)}
.btn-primary:disabled{opacity:.4;cursor:not-allowed;transform:none}
.btn-secondary{background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.15)}
.btn-danger{background:rgba(255,80,80,.15);color:#ff5050;border:1px solid rgba(255,80,80,.4)}
.progress{height:5px;background:rgba(255,255,255,.1);border-radius:3px;overflow:hidden;margin:12px 0}
.progress-bar{height:100%;background:linear-gradient(90deg,#00d9ff,#00ff88);transition:width .3s}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
.device{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:12px;cursor:pointer;transition:.2s}
.device:hover{background:rgba(255,255,255,.07);border-color:#00d9ff}
.device.selected{border-color:#00ff88;background:rgba(0,255,136,.08)}
.device.camera{border-left:3px solid #ff6b6b}
.device-head{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.device-icon{font-size:1.8rem}
.device h3{font-size:.95rem;color:#fff}
.device .ip{font-family:monospace;color:#00d9ff;font-size:.85rem}
.tags{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}
.tag{padding:3px 7px;background:rgba(255,255,255,.08);border-radius:4px;font-size:.7rem;color:#999}
.tag.port{color:#00ff88}
.tag.camera{background:rgba(255,107,107,.2);color:#ff6b6b}
.tag.creds{background:rgba(0,255,136,.2);color:#00ff88}
.tag.onvif{background:rgba(0,217,255,.2);color:#00d9ff}
.empty{text-align:center;padding:35px;color:#555}
.empty .icon{font-size:3.5rem;margin-bottom:12px}
.actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:15px}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:15px}
.stat{background:rgba(255,255,255,.03);border-radius:8px;padding:12px;text-align:center}
.stat-num{font-size:1.8rem;font-weight:700;background:linear-gradient(90deg,#00d9ff,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.stat-label{font-size:.75rem;color:#666;margin-top:2px}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
.scanning{animation:pulse 1s infinite}
.toast{position:fixed;bottom:20px;right:20px;padding:12px 18px;border-radius:8px;font-weight:600;z-index:1000;animation:slideIn .3s}
.toast.ok{background:#00c853}.toast.err{background:#ff5252}
@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}
.camera-info{margin-top:8px;padding:8px;background:rgba(255,107,107,.1);border-radius:6px;font-size:.8rem}
.camera-info .rtsp{color:#ff6b6b;font-family:monospace;word-break:break-all}
</style>
</head><body>
<div class="container">
<header>
<div class="logo"><span style="font-size:2rem">🔐</span><div><h1>TrueVault Scanner</h1><small style="color:#555">v''' + VERSION + ''' - Brute Force Camera Discovery</small></div></div>
<div id="status" class="badge badge-ok">✓ Connected</div>
</header>

<div class="stats">
<div class="stat"><div class="stat-num" id="total-count">0</div><div class="stat-label">Total Devices</div></div>
<div class="stat"><div class="stat-num" id="camera-count">0</div><div class="stat-label">📷 Cameras</div></div>
<div class="stat"><div class="stat-num" id="creds-count">0</div><div class="stat-label">🔑 Creds Found</div></div>
<div class="stat"><div class="stat-num" id="other-count">0</div><div class="stat-label">Other</div></div>
</div>

<div class="card">
<h2>📡 Network Scan (Brute Force)</h2>
<p style="color:#888;margin-bottom:10px;font-size:.9rem">Scans ALL ports, tests credentials, discovers ONVIF/UPnP cameras</p>
<div id="scan-msg" style="color:#888;margin-bottom:10px">Ready to scan your network</div>
<div class="progress"><div id="prog" class="progress-bar" style="width:0%"></div></div>
<button id="scan-btn" class="btn btn-primary" onclick="startScan()">🔍 Scan Network</button>
</div>

<div class="card">
<h2>📱 Discovered Devices</h2>
<div id="devices" class="grid"><div class="empty"><div class="icon">🔍</div><p>Click "Scan Network" to discover devices</p></div></div>
<div class="actions">
<button class="btn btn-primary" onclick="syncSelected()" id="sync-btn" disabled>☁️ Sync to TrueVault</button>
<button class="btn btn-secondary" onclick="selectCameras()">📷 Select Cameras</button>
<button class="btn btn-secondary" onclick="selectAll()">✅ All</button>
<button class="btn btn-secondary" onclick="deselectAll()">❌ None</button>
</div>
</div>
</div>

<script>
let devices=[],selected=new Set(),poll=null;
const $=id=>document.getElementById(id);

function toast(m,ok=true){const t=document.createElement('div');t.className='toast '+(ok?'ok':'err');t.textContent=m;document.body.appendChild(t);setTimeout(()=>t.remove(),3000)}

async function startScan(){
  $('scan-btn').disabled=true;$('scan-btn').innerHTML='🔄 Scanning...';$('scan-btn').classList.add('scanning');
  await fetch('/scan',{method:'POST'});
  poll=setInterval(pollStatus,500);
}

async function pollStatus(){
  const r=await fetch('/status').then(r=>r.json());
  $('scan-msg').textContent=r.message;$('prog').style.width=r.progress+'%';
  if(!r.running){
    clearInterval(poll);$('scan-btn').disabled=false;$('scan-btn').innerHTML='🔍 Scan Network';$('scan-btn').classList.remove('scanning');
    devices=await fetch('/devices').then(r=>r.json());
    render();
  }
}

function render(){
  const cams=devices.filter(d=>d.type==='ip_camera').length;
  const creds=devices.filter(d=>d.credentials_found).length;
  $('total-count').textContent=devices.length;
  $('camera-count').textContent=cams;
  $('creds-count').textContent=creds;
  $('other-count').textContent=devices.length-cams;
  
  if(!devices.length){$('devices').innerHTML='<div class="empty"><div class="icon">📭</div><p>No devices found</p></div>';return}
  
  $('devices').innerHTML=devices.map(d=>`
    <div class="device ${selected.has(d.id)?'selected':''} ${d.type==='ip_camera'?'camera':''}" onclick="toggle('${d.id}')">
      <div class="device-head"><span class="device-icon">${d.icon}</span><div><h3>${d.hostname||d.type_name}</h3><div class="ip">${d.ip}</div></div></div>
      <div class="tags">
        <span class="tag">${d.vendor}</span>
        ${d.type==='ip_camera'?'<span class="tag camera">CAMERA</span>':''}
        ${d.credentials_found?'<span class="tag creds">🔑 CREDS</span>':''}
        ${d.discovered_via==='ONVIF'?'<span class="tag onvif">ONVIF</span>':''}
        ${d.open_ports.slice(0,3).map(p=>'<span class="tag port">:'+p.port+'</span>').join('')}
      </div>
      ${d.type==='ip_camera' && d.rtsp_url ? `
        <div class="camera-info">
          <div class="rtsp">${d.rtsp_url}</div>
          ${d.credentials_found ? '<div style="color:#00ff88;margin-top:4px">✓ Credentials: '+d.rtsp_username+':****</div>' : ''}
        </div>
      ` : ''}
    </div>
  `).join('');
  $('sync-btn').disabled=selected.size===0;
}

function toggle(id){selected.has(id)?selected.delete(id):selected.add(id);render()}
function selectAll(){devices.forEach(d=>selected.add(d.id));render()}
function deselectAll(){selected.clear();render()}
function selectCameras(){selected.clear();devices.filter(d=>d.type==='ip_camera').forEach(d=>selected.add(d.id));render();toast('Selected '+selected.size+' cameras')}

async function syncSelected(){
  const sel=devices.filter(d=>selected.has(d.id));
  if(!sel.length){toast('Select devices first',false);return}
  $('sync-btn').disabled=true;$('sync-btn').innerHTML='⏳ Syncing...';
  const r=await fetch('/sync',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({devices:sel})}).then(r=>r.json());
  $('sync-btn').disabled=false;$('sync-btn').innerHTML='☁️ Sync to TrueVault';
  if(r.success){toast('Synced '+sel.length+' devices!')}else{toast(r.error||'Sync failed',false)}
}

// Auto-scan on load
setTimeout(startScan,500);
</script>
</body></html>'''

class Handler(SimpleHTTPRequestHandler):
    def log_message(self, *args): pass
    
    def send_json(self, data, code=200):
        self.send_response(code)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()
        self.wfile.write(json.dumps(data).encode())
    
    def do_GET(self):
        if self.path == '/':
            self.send_response(200)
            self.send_header('Content-Type', 'text/html')
            self.end_headers()
            self.wfile.write(HTML.encode())
        elif self.path == '/status':
            self.send_json(scan_status)
        elif self.path == '/devices':
            self.send_json(discovered_devices)
        else:
            self.send_json({"error": "Not found"}, 404)
    
    def do_POST(self):
        if self.path == '/scan':
            threading.Thread(target=scan_network, daemon=True).start()
            self.send_json({"success": True, "message": "Scan started"})
        elif self.path == '/sync':
            length = int(self.headers.get('Content-Length', 0))
            data = json.loads(self.rfile.read(length)) if length else {}
            result = sync_to_truthvault(data.get('devices', []))
            self.send_json(result)
        else:
            self.send_json({"error": "Not found"}, 404)

def main():
    global auth_token, user_email
    
    print(f"""
╔══════════════════════════════════════════════════════════╗
║       TrueVault Network Scanner v{VERSION}                   ║
║       Brute Force Camera Discovery                       ║
╠══════════════════════════════════════════════════════════╣
║  Features:                                               ║
║  • ONVIF camera discovery                                ║
║  • UPnP/mDNS scanning                                    ║
║  • Brute force port scanning                             ║
║  • Credential testing (50+ default combos)               ║
║  • HTTP fingerprinting                                   ║
╚══════════════════════════════════════════════════════════╝
""")
    
    # Get credentials from args or prompt
    if len(sys.argv) >= 3:
        user_email = sys.argv[1]
        auth_token = sys.argv[2]
    else:
        print("Usage: python truthvault_scanner.py EMAIL TOKEN")
        print("Or run without args for manual entry\n")
        user_email = input("TrueVault Email: ").strip()
        auth_token = input("Auth Token: ").strip()
    
    print(f"\n✓ User: {user_email}")
    print(f"✓ Starting scanner on http://localhost:{LOCAL_PORT}")
    print("\nOpening browser...")
    
    # Open browser
    webbrowser.open(f"http://localhost:{LOCAL_PORT}")
    
    # Start server
    server = HTTPServer(('0.0.0.0', LOCAL_PORT), Handler)
    print(f"\n🔍 Scanner ready! Press Ctrl+C to quit.\n")
    
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n\nScanner stopped. Goodbye!")
        server.shutdown()

if __name__ == '__main__':
    main()
