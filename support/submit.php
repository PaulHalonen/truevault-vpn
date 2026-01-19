<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Support Ticket - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 3rem; }
        .header h1 { font-size: 2.5rem; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.5rem; }
        .header p { color: #888; font-size: 1.1rem; }
        .kb-section { background: rgba(0,217,255,0.1); border: 1px solid rgba(0,217,255,0.3); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .kb-section h3 { color: #00d9ff; margin-bottom: 1rem; }
        .kb-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .kb-link { padding: 0.75rem; background: rgba(255,255,255,0.05); border-radius: 8px; text-decoration: none; color: #fff; transition: 0.3s; display: block; }
        .kb-link:hover { background: rgba(0,217,255,0.2); transform: translateY(-2px); }
        .form-section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #ccc; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; font-size: 1rem; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #00d9ff; }
        .form-group small { display: block; color: #666; font-size: 0.85rem; margin-top: 0.25rem; }
        .btn { width: 100%; padding: 1rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,217,255,0.4); }
        .success-message { background: rgba(0,255,136,0.2); border: 1px solid #00ff88; color: #00ff88; padding: 1.5rem; border-radius: 12px; text-align: center; margin-bottom: 2rem; }
        .success-message .ticket-number { font-size: 1.5rem; font-weight: 700; margin: 1rem 0; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎫 Need Help?</h1>
        <p>We're here to assist you. Submit a support ticket below.</p>
    </div>

    <div class="kb-section">
        <h3>💡 Check our Knowledge Base first</h3>
        <p style="margin-bottom: 1rem; color: #888;">You might find an instant answer to your question:</p>
        <div class="kb-links">
            <a href="/support/kb.php?category=getting_started" class="kb-link">🚀 Getting Started</a>
            <a href="/support/kb.php?category=troubleshooting" class="kb-link">🔧 Troubleshooting</a>
            <a href="/support/kb.php?category=billing" class="kb-link">💳 Billing & Plans</a>
            <a href="/support/kb.php?category=account" class="kb-link">👤 Account Settings</a>
        </div>
    </div>

    <div id="successMessage" style="display: none;" class="success-message">
        <div style="font-size: 3rem; margin-bottom: 1rem;">✓</div>
        <h2>Ticket Submitted!</h2>
        <div class="ticket-number" id="ticketNumber"></div>
        <p>We've received your support request and will respond within 24 hours.</p>
        <p style="margin-top: 1rem; color: #888;">Check your email for updates.</p>
    </div>

    <div class="form-section" id="ticketForm">
        <h2 style="margin-bottom: 1.5rem;">Submit a Ticket</h2>
        <form id="submitForm">
            <div class="form-group">
                <label for="email">Your Email *</label>
                <input type="email" id="email" name="email" required placeholder="your@email.com">
            </div>

            <div class="form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" required>
                    <option value="">- Select Category -</option>
                    <option value="technical">Technical Issue</option>
                    <option value="billing">Billing Question</option>
                    <option value="account">Account Management</option>
                    <option value="complaint">Complaint</option>
                    <option value="general">General Inquiry</option>
                </select>
            </div>

            <div class="form-group">
                <label for="subject">Subject *</label>
                <input type="text" id="subject" name="subject" required placeholder="Brief description of your issue">
            </div>

            <div class="form-group">
                <label for="message">Message *</label>
                <textarea id="message" name="message" rows="8" required placeholder="Please provide as much detail as possible..."></textarea>
                <small>Be specific to help us resolve your issue faster</small>
            </div>

            <div class="form-group">
                <label for="attachment">Attachment (optional)</label>
                <input type="file" id="attachment" name="attachment" accept="image/*,.pdf,.txt">
                <small>Supported: Images, PDF, Text files (Max 10MB)</small>
            </div>

            <button type="submit" class="btn">📨 Submit Ticket</button>
        </form>
    </div>
</div>

<script>
document.getElementById('submitForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '⏳ Submitting...';
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('/support/api.php?action=create', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('ticketForm').style.display = 'none';
            document.getElementById('successMessage').style.display = 'block';
            document.getElementById('ticketNumber').textContent = result.ticket_number;
            window.scrollTo(0, 0);
        } else {
            alert('Error: ' + (result.error || 'Failed to submit ticket'));
            btn.disabled = false;
            btn.textContent = '📨 Submit Ticket';
        }
    } catch (error) {
        alert('Error submitting ticket. Please try again.');
        btn.disabled = false;
        btn.textContent = '📨 Submit Ticket';
    }
});
</script>
</body>
</html>
