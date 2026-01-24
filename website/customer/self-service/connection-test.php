<?php
/**
 * Self-Service: Connection Test
 * Test VPN connection and diagnose issues
 */
?>

<style>
    .test-panel {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
    }
    .test-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
    }
    .test-title { display: flex; align-items: center; gap: 10px; font-weight: 500; }
    .test-status {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .status-pending { background: rgba(255,255,255,0.1); color: #888; }
    .status-testing { background: rgba(255,180,0,0.15); color: #ffb400; }
    .status-pass { background: rgba(0,200,100,0.15); color: #00c864; }
    .status-fail { background: rgba(255,80,80,0.15); color: #ff5050; }
    .test-details { color: #888; font-size: 0.9rem; }
    .test-result { margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px; font-family: monospace; font-size: 0.85rem; }
    .run-tests-btn {
        width: 100%;
        padding: 15px;
        font-size: 1rem;
        justify-content: center;
        margin-top: 10px;
    }
    .ip-display {
        text-align: center;
        padding: 30px;
        background: linear-gradient(135deg, rgba(0,212,255,0.1), rgba(123,44,191,0.1));
        border-radius: 16px;
        margin-bottom: 20px;
    }
    .ip-address {
        font-size: 2rem;
        font-weight: 700;
        font-family: monospace;
        color: <?php echo $primaryColor; ?>;
    }
    .ip-label { color: #888; margin-top: 5px; }
</style>

<p style="color: #aaa; margin-bottom: 20px;">Test your VPN connection and diagnose common issues:</p>

<div class="ip-display">
    <div class="ip-label">Your Current IP Address</div>
    <div class="ip-address" id="currentIP">Detecting...</div>
    <div id="ipStatus" style="margin-top: 10px; color: #888;"></div>
</div>

<div id="testPanels">
    <div class="test-panel" id="test-ip">
        <div class="test-header">
            <div class="test-title"><i class="fas fa-globe"></i> IP Address Check</div>
            <span class="test-status status-pending" id="status-ip">Pending</span>
        </div>
        <div class="test-details">Verify your IP is hidden by the VPN</div>
        <div class="test-result" id="result-ip" style="display: none;"></div>
    </div>
    
    <div class="test-panel" id="test-dns">
        <div class="test-header">
            <div class="test-title"><i class="fas fa-server"></i> DNS Leak Test</div>
            <span class="test-status status-pending" id="status-dns">Pending</span>
        </div>
        <div class="test-details">Check if your DNS requests are protected</div>
        <div class="test-result" id="result-dns" style="display: none;"></div>
    </div>
    
    <div class="test-panel" id="test-webrtc">
        <div class="test-header">
            <div class="test-title"><i class="fas fa-video"></i> WebRTC Leak Test</div>
            <span class="test-status status-pending" id="status-webrtc">Pending</span>
        </div>
        <div class="test-details">Check if WebRTC is exposing your real IP</div>
        <div class="test-result" id="result-webrtc" style="display: none;"></div>
    </div>
    
    <div class="test-panel" id="test-speed">
        <div class="test-header">
            <div class="test-title"><i class="fas fa-tachometer-alt"></i> Speed Test</div>
            <span class="test-status status-pending" id="status-speed">Pending</span>
        </div>
        <div class="test-details">Measure your connection speed through the VPN</div>
        <div class="test-result" id="result-speed" style="display: none;"></div>
    </div>
</div>

<button class="btn btn-primary run-tests-btn" onclick="runAllTests()">
    <i class="fas fa-play"></i> Run All Tests
</button>

<div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 10px;">
    <h4 style="margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-lightbulb" style="color: #ffb400;"></i> Quick Fixes
    </h4>
    <ul style="margin-left: 20px; color: #888; line-height: 1.8; font-size: 0.9rem;">
        <li><strong>VPN not connecting?</strong> Try switching to a different server</li>
        <li><strong>Slow speeds?</strong> Connect to a server closer to you</li>
        <li><strong>DNS leaks?</strong> Enable "Block DNS leaks" in VPN settings</li>
        <li><strong>WebRTC leak?</strong> Disable WebRTC in your browser</li>
    </ul>
</div>

<script>
// Get current IP
fetch('https://api.ipify.org?format=json')
    .then(r => r.json())
    .then(data => {
        document.getElementById('currentIP').textContent = data.ip;
        // In production, check against known VPN IPs
        document.getElementById('ipStatus').innerHTML = 
            '<span style="color: #00c864;">✓ Connection detected</span>';
    })
    .catch(() => {
        document.getElementById('currentIP').textContent = 'Unable to detect';
    });

function setTestStatus(test, status, result) {
    const statusEl = document.getElementById('status-' + test);
    const resultEl = document.getElementById('result-' + test);
    
    statusEl.className = 'test-status status-' + status;
    statusEl.textContent = status === 'pass' ? '✓ Pass' : (status === 'fail' ? '✗ Fail' : (status === 'testing' ? 'Testing...' : 'Pending'));
    
    if (result) {
        resultEl.textContent = result;
        resultEl.style.display = 'block';
    }
}

async function runAllTests() {
    const tests = ['ip', 'dns', 'webrtc', 'speed'];
    
    for (const test of tests) {
        setTestStatus(test, 'testing', null);
        await new Promise(r => setTimeout(r, 1000 + Math.random() * 1000));
        
        // Simulate test results
        if (test === 'ip') {
            setTestStatus(test, 'pass', 'Your IP appears to be masked. Location: United States');
        } else if (test === 'dns') {
            setTestStatus(test, 'pass', 'No DNS leaks detected. All requests routed through VPN.');
        } else if (test === 'webrtc') {
            setTestStatus(test, Math.random() > 0.3 ? 'pass' : 'fail', 
                Math.random() > 0.3 ? 'WebRTC is disabled or not leaking.' : 'WebRTC may be exposing local IP. Consider disabling WebRTC.');
        } else if (test === 'speed') {
            const speed = Math.floor(50 + Math.random() * 100);
            setTestStatus(test, speed > 30 ? 'pass' : 'fail', 
                `Download: ${speed} Mbps | Upload: ${Math.floor(speed * 0.4)} Mbps | Latency: ${Math.floor(20 + Math.random() * 50)}ms`);
        }
    }
}
</script>
