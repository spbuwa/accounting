<?php
// 1. GET SELECTED DATE (Default to Today)
$bs_date = $_GET['bs_date'] ?? date('Y-m-d');

// 2. CALCULATE NET PROFIT (Retained Earnings) UP TO SELECTED DATE
// Profit = (Total Income - Total Expense) from the beginning of time until $bs_date
$sql_np = "SELECT SUM(j.credit - j.debit) 
           FROM journal j 
           JOIN accounts a ON j.account_id = a.id 
           WHERE a.type IN ('Income', 'Expense') AND j.trans_date <= ?";
$stmt_np = $pdo->prepare($sql_np);
$stmt_np->execute([$bs_date]);
$retained_earnings = $stmt_np->fetchColumn() ?: 0;

// 3. FETCH ACCOUNT BALANCES UP TO SELECTED DATE
// We perform a fresh query here to ensure we ignore transactions after $bs_date
$sql_bal = "SELECT a.id, a.name, a.type, SUM(j.debit) as d, SUM(j.credit) as c 
            FROM accounts a 
            LEFT JOIN journal j ON a.id = j.account_id AND j.trans_date <= ? 
            GROUP BY a.id, a.name, a.type 
            ORDER BY a.name ASC";
$stmt_bal = $pdo->prepare($sql_bal);
$stmt_bal->execute([$bs_date]);
$bs_accounts = $stmt_bal->fetchAll();

// 4. ORGANIZE DATA
$assets = [];
$liabilities = [];
$equity = [];
$tot_asset = 0; 
$tot_liab = 0;

foreach($bs_accounts as $a) {
    // Calculate Balance based on Type
    if (in_array($a['type'], ['Asset', 'Expense'])) {
        $bal = $a['d'] - $a['c'];
    } else {
        $bal = $a['c'] - $a['d'];
    }

    if ($bal == 0) continue; // Skip zero balance accounts

    if ($a['type'] == 'Asset') {
        $assets[] = ['name' => $a['name'], 'bal' => $bal];
        $tot_asset += $bal;
    } elseif ($a['type'] == 'Liability') {
        $liabilities[] = ['name' => $a['name'], 'bal' => $bal];
        $tot_liab += $bal;
    } elseif ($a['type'] == 'Equity') {
        $equity[] = ['name' => $a['name'], 'bal' => $bal];
        $tot_liab += $bal;
    }
}

// Add Net Profit to Liability Side Total
$tot_liab += $retained_earnings;
?>

<div class="card" id="printArea">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:20px; border-bottom:2px solid #333; padding-bottom:15px;">
        <div>
            <h1 style="margin:0; font-size:1.5rem; text-transform:uppercase; letter-spacing:1px;">Balance Sheet</h1>
            <div style="color:#666; margin-top:5px;">Position as on <b><?= date('d F Y', strtotime($bs_date)) ?></b></div>
        </div>
        
        <div class="no-print" style="display:flex; gap:10px; align-items:center;">
            <form method="GET" action="index.php" style="display:flex; align-items:center; gap:5px; background:#f8f9fa; padding:5px 10px; border-radius:4px; border:1px solid #ddd;">
                <input type="hidden" name="page" value="balance_sheet">
                <label style="font-size:0.8rem; font-weight:bold;">As On:</label>
                <input type="date" name="bs_date" value="<?= $bs_date ?>" style="padding:5px; border:1px solid #ccc; border-radius:3px;">
                <button type="submit" class="btn-blue" style="padding:5px 10px;">Go</button>
            </form>
            <button onclick="window.print()" class="btn-sm" style="background:#fff; border:1px solid #ccc; color:#333; height:32px;">🖨️ Print</button>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; border:2px solid #333;">
        
        <div style="border-right:2px solid #333;">
            <div style="background:#eee; padding:8px; font-weight:bold; text-align:center; border-bottom:1px solid #999; text-transform:uppercase;">
                Assets
            </div>
            <table style="width:100%; border-collapse:collapse;">
                <tbody>
                    <?php foreach($assets as $item): ?>
                    <tr>
                        <td style="padding:6px 15px; border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($item['name']) ?></td>
                        <td class="num" style="padding:6px 15px; border-bottom:1px solid #f0f0f0; color:#004085;"><?= fmtIndian($item['bal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr><td colspan="2" style="height:50px;"></td></tr>
                </tbody>
            </table>
        </div>

        <div>
            <div style="background:#eee; padding:8px; font-weight:bold; text-align:center; border-bottom:1px solid #999; text-transform:uppercase;">
                Liabilities & Equity
            </div>
            <table style="width:100%; border-collapse:collapse;">
                <tbody>
                    <tr><td colspan="2" style="padding:5px 15px; background:#fafafa; font-weight:bold; font-size:0.85rem; color:#666;">CAPITAL & EQUITY</td></tr>
                    <?php foreach($equity as $item): ?>
                    <tr>
                        <td style="padding:6px 15px; border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($item['name']) ?></td>
                        <td class="num" style="padding:6px 15px; border-bottom:1px solid #f0f0f0;"><?= fmtIndian($item['bal']) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <tr style="background: <?= $retained_earnings >= 0 ? '#e8f5e9' : '#ffebee' ?>;">
                        <td style="padding:6px 15px; border-bottom:1px solid #f0f0f0; font-weight:bold; color:#333;">
                            <?= $retained_earnings >= 0 ? 'Net Profit (Retained)' : 'Net Loss (Retained)' ?>
                        </td>
                        <td class="num" style="padding:6px 15px; border-bottom:1px solid #f0f0f0; font-weight:bold; color:#333;">
                            <?= fmtIndian($retained_earnings) ?>
                        </td>
                    </tr>

                    <tr><td colspan="2" style="padding:5px 15px; background:#fafafa; font-weight:bold; font-size:0.85rem; color:#666;">LOANS & LIABILITIES</td></tr>
                    <?php foreach($liabilities as $item): ?>
                    <tr>
                        <td style="padding:6px 15px; border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($item['name']) ?></td>
                        <td class="num" style="padding:6px 15px; border-bottom:1px solid #f0f0f0;"><?= fmtIndian($item['bal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; border:2px solid #333; border-top:none; background:#333; color:white;">
        <div style="padding:10px 15px; display:flex; justify-content:space-between; font-weight:bold; border-right:2px solid #555;">
            <span>TOTAL ASSETS</span>
            <span>₹ <?= fmtIndian($tot_asset) ?></span>
        </div>
        <div style="padding:10px 15px; display:flex; justify-content:space-between; font-weight:bold;">
            <span>TOTAL LIAB. & EQUITY</span>
            <span>₹ <?= fmtIndian($tot_liab) ?></span>
        </div>
    </div>

    <?php if(abs($tot_asset - $tot_liab) > 1): ?>
    <div style="margin-top:15px; padding:10px; background:#ffebee; color:#c62828; border:1px solid #ffcdd2; text-align:center;">
        ⚠️ <b>Mismatch:</b> The sheet is off by <?= fmtIndian(abs($tot_asset - $tot_liab)) ?>. Check initial opening balances.
    </div>
    <?php endif; ?>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; left: 0; top: 0; width: 100%; border:none; box-shadow:none; }
        .no-print { display: none !important; }
        .card { padding: 0; box-shadow: none; }
    }
</style>