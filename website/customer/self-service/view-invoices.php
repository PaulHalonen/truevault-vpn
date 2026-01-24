<?php
/**
 * Self-Service: View Invoices
 * Access and download billing history
 */

$invoices = [];
if ($customerId) {
    // In production, fetch from billing database
    // Sample invoices
    $invoices = [
        ['id' => 'INV-2026-001', 'date' => '2026-01-15', 'amount' => 9.97, 'status' => 'paid', 'plan' => 'Personal Monthly'],
        ['id' => 'INV-2025-012', 'date' => '2025-12-15', 'amount' => 9.97, 'status' => 'paid', 'plan' => 'Personal Monthly'],
        ['id' => 'INV-2025-011', 'date' => '2025-11-15', 'amount' => 9.97, 'status' => 'paid', 'plan' => 'Personal Monthly'],
        ['id' => 'INV-2025-010', 'date' => '2025-10-15', 'amount' => 99.97, 'status' => 'paid', 'plan' => 'Personal Annual'],
    ];
}
?>

<style>
    .invoices-table { width: 100%; border-collapse: collapse; }
    .invoices-table th, .invoices-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .invoices-table th { color: #888; font-weight: 500; font-size: 0.85rem; }
    .invoices-table tr:hover { background: rgba(255,255,255,0.02); }
    .status-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-paid { background: rgba(0,200,100,0.15); color: #00c864; }
    .status-pending { background: rgba(255,180,0,0.15); color: #ffb400; }
    .status-failed { background: rgba(255,80,80,0.15); color: #ff5050; }
    .total-summary {
        margin-top: 20px;
        padding: 15px;
        background: rgba(255,255,255,0.03);
        border-radius: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
</style>

<p style="color: #aaa; margin-bottom: 20px;">Your billing history and invoices:</p>

<?php if (empty($invoices)): ?>
<p style="color: #888; text-align: center; padding: 30px;">No invoices found.</p>
<?php else: ?>
<div style="overflow-x: auto;">
    <table class="invoices-table">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Date</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $inv): ?>
            <tr>
                <td style="font-family: monospace; color: <?php echo $primaryColor; ?>;"><?php echo $inv['id']; ?></td>
                <td><?php echo date('M j, Y', strtotime($inv['date'])); ?></td>
                <td><?php echo htmlspecialchars($inv['plan']); ?></td>
                <td>$<?php echo number_format($inv['amount'], 2); ?></td>
                <td><span class="status-badge status-<?php echo $inv['status']; ?>"><?php echo ucfirst($inv['status']); ?></span></td>
                <td>
                    <a href="/api/invoice-pdf.php?id=<?php echo $inv['id']; ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-download"></i> PDF
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="total-summary">
    <div>
        <div style="color: #888; font-size: 0.85rem;">Total Paid (All Time)</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #00c864;">
            $<?php echo number_format(array_sum(array_column($invoices, 'amount')), 2); ?>
        </div>
    </div>
    <a href="/api/invoices-export.php?format=csv" class="btn btn-secondary">
        <i class="fas fa-file-csv"></i> Export CSV
    </a>
</div>
<?php endif; ?>
