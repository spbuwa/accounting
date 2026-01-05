<div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:20px;">
    <div>
        <h2 style="margin:0; border:none; padding:0;">🌊 Cash Flow Statement</h2>
        <p style="color:#666; margin:5px 0 0 0;">
            Flow for <b><?= fmtDate($start_date) ?></b> to <b><?= fmtDate($end_date) ?></b>
        </p>
    </div>
    
    <form method="GET" action="index.php" style="background:#fff; padding:10px; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,0.1); display:flex; gap:10px; align-items:center;">
        <input type="hidden" name="page" value="cashflow">
        <div style="display:flex; flex-direction:column;">
            <label style="font-size:0.7em; font-weight:bold; color:#555;">FROM</label>
            <input type="date" name="start" value="<?= $start_date ?>" style="border:1px solid #ddd; padding:4px; border-radius:4px;">
        </div>
        <div style="display:flex; flex-direction:column;">
            <label style="font-size:0.7em; font-weight:bold; color:#555;">TO</label>
            <input type="date" name="end" value="<?= $end_date ?>" style="border:1px solid #ddd; padding:4px; border-radius:4px;">
        </div>
        <button type="submit" class="btn-blue" style="height:35px; align-self:flex-end;">Filter</button>
    </form>
</div>

<?php
// 2. IDENTIFY LIQUID ACCOUNTS (Bank/Cash)
// We look for keywords like 'Cash', 'HDFC', 'SBI', etc.
$liquid_ids = [];
$liq_keywords = ['HDFC','Maharashtra','SBI','SVC','UPI','Fastag','Cash'];

foreach($all_acc as $a) {
    if ($a['type'] == 'Asset') {
        foreach ($liq_keywords as $k) {
            if (stripos($a['name'], $k) !== false) {
                $liquid_ids[] = $a['id'];
                break;
            }
        }
    }
}

if(empty($liquid_ids)) {
    echo "<div class='card'><h3>⚠️ No Cash/Bank Accounts Found</h3><p>Ensure your asset accounts contain words like 'Cash', 'Bank', 'HDFC', etc.</p></div>";
} else {
    $ids_str = implode(',', $liquid_ids);
    
    // 3. FETCH DATA
    
    // INFLOWS: Debit to Liquid Asset > 0
    $sql_in = "SELECT j.*, a.name as acc_name FROM journal j 
               JOIN accounts a ON j.account_id = a.id 
               WHERE j.account_id IN ($ids_str) AND j.debit > 0 
               AND j.trans_date BETWEEN '$start_date' AND '$end_date'
               ORDER BY j.trans_date DESC";
    $inflows = $pdo->query($sql_in)->fetchAll();

    // OUTFLOWS: Credit to Liquid Asset > 0
    $sql_out = "SELECT j.*, a.name as acc_name FROM journal j 
                JOIN accounts a ON j.account_id = a.id 
                WHERE j.account_id IN ($ids_str) AND j.credit > 0 
                AND j.trans_date BETWEEN '$start_date' AND '$end_date'
                ORDER BY j.trans_date DESC";
    $outflows = $pdo->query($sql_out)->fetchAll();
    
    // 4. CALCULATE TOTALS (Fixed Bug Here)
    $tot_in = 0; 
    foreach($inflows as $i) $tot_in += $i['debit'];
    
    $tot_out = 0; 
    foreach($outflows as $o) $tot_out += $o['credit']; // FIXED: Was incorrectly using $i
    
    $net_change = $tot_in - $tot_out;
}
?>

<div class="grid-row" style="grid-template-columns: 1fr 1fr 1fr; gap:20px; margin-bottom:20px;">
    <div class="card" style="text-align:center; border-top: 4px solid #28a745; margin:0;">
        <div style="font-size:0.9rem; color:#666;">TOTAL INFLOW</div>
        <div style="font-size:1.5rem; font-weight:bold; color:#28a745;">₹ <?= fmtIndian($tot_in) ?></div>
    </div>
    <div class="card" style="text-align:center; border-top: 4px solid #dc3545; margin:0;">
        <div style="font-size:0.9rem; color:#666;">TOTAL OUTFLOW</div>
        <div style="font-size:1.5rem; font-weight:bold; color:#dc3545;">₹ <?= fmtIndian($tot_out) ?></div>
    </div>
    <div class="card" style="text-align:center; background:#333; color:white; margin:0;">
        <div style="font-size:0.9rem; opacity:0.8;">NET CHANGE</div>
        <div style="font-size:1.5rem; font-weight:bold; color: <?= $net_change >= 0 ? '#2ecc71' : '#e74c3c' ?>;">
            <?= $net_change >= 0 ? '+' : '' ?><?= fmtIndian($net_change) ?>
        </div>
    </div>
</div>

<div class="grid-row" style="grid-template-columns: 1fr 1fr; gap:20px;">
    
    <div class="card">
        <h3 style="color:#28a745; margin-top:0;">⬇️ Money In</h3>
        <table style="font-size:0.85rem;">
            <thead><tr><th>Date</th><th>Desc</th><th>Account</th><th class="num">Amount</th></tr></thead>
            <tbody>
                <?php foreach($inflows as $r): ?>
                <tr>
                    <td><?= fmtDate($r['trans_date']) ?></td>
                    <td><?= htmlspecialchars($r['description']) ?></td>
                    <td><?= htmlspecialchars($r['acc_name']) ?></td>
                    <td class="num" style="font-weight:bold; color:#28a745;"><?= fmtIndian($r['debit']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($inflows)) echo "<tr><td colspan='4' style='text-align:center; color:#999; padding:20px;'>No inflows found</td></tr>"; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3 style="color:#dc3545; margin-top:0;">⬆️ Money Out</h3>
        <table style="font-size:0.85rem;">
            <thead><tr><th>Date</th><th>Desc</th><th>Account</th><th class="num">Amount</th></tr></thead>
            <tbody>
                <?php foreach($outflows as $r): ?>
                <tr>
                    <td><?= fmtDate($r['trans_date']) ?></td>
                    <td><?= htmlspecialchars($r['description']) ?></td>
                    <td><?= htmlspecialchars($r['acc_name']) ?></td>
                    <td class="num" style="font-weight:bold; color:#dc3545;"><?= fmtIndian($r['credit']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($outflows)) echo "<tr><td colspan='4' style='text-align:center; color:#999; padding:20px;'>No outflows found</td></tr>"; ?>
            </tbody>
        </table>
    </div>
</div>