<?php
// 1. Fetch Period-Specific Data for P&L
// We cannot use the global $acc_list here because that shows "All Time" balances.
// P&L must only show activity for the selected dates (Start to End).

$sql_pl = "SELECT a.name, a.type, SUM(j.debit) as dr, SUM(j.credit) as cr 
           FROM journal j 
           JOIN accounts a ON j.account_id = a.id 
           WHERE a.type IN ('Income', 'Expense') 
           AND j.trans_date BETWEEN ? AND ?
           GROUP BY a.id, a.name, a.type 
           ORDER BY a.name ASC";

$stmt_pl = $pdo->prepare($sql_pl);
$stmt_pl->execute([$start_date, $end_date]);
$pl_rows = $stmt_pl->fetchAll();

// Separate into Lists
$expenses = [];
$incomes = [];
$total_exp = 0;
$total_inc = 0;

foreach($pl_rows as $row) {
    // Expense = Debit - Credit
    // Income = Credit - Debit
    if ($row['type'] == 'Expense') {
        $amt = $row['dr'] - $row['cr'];
        if ($amt != 0) {
            $expenses[] = ['name' => $row['name'], 'amt' => $amt];
            $total_exp += $amt;
        }
    } else {
        $amt = $row['cr'] - $row['dr'];
        if ($amt != 0) {
            $incomes[] = ['name' => $row['name'], 'amt' => $amt];
            $total_inc += $amt;
        }
    }
}

// Calculate Result
$net_profit = 0;
$net_loss = 0;
if ($total_inc >= $total_exp) {
    $net_profit = $total_inc - $total_exp;
} else {
    $net_loss = $total_exp - $total_inc;
}
$grand_total = max($total_inc, $total_exp);
?>

<div class="card" id="printArea">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:2px solid #333; padding-bottom:10px;">
        <div>
            <h1 style="margin:0; font-size:1.5rem; text-transform:uppercase; letter-spacing:1px;">Profit & Loss A/c</h1>
            <div style="color:#666; font-size:0.9rem;">
                For the period <b><?= fmtDate($start_date) ?></b> to <b><?= fmtDate($end_date) ?></b>
            </div>
        </div>
        <div class="no-print">
            <form method="GET" action="index.php" style="display:inline-flex; gap:5px; align-items:center;">
                <input type="hidden" name="page" value="profit_loss">
                <input type="date" name="start" value="<?= $start_date ?>" style="padding:4px; border:1px solid #ccc; border-radius:4px;">
                <span style="font-weight:bold;">to</span>
                <input type="date" name="end" value="<?= $end_date ?>" style="padding:4px; border:1px solid #ccc; border-radius:4px;">
                <button type="submit" class="btn-blue" style="padding:5px 10px;">Go</button>
            </form>
            <button onclick="window.print()" class="btn-sm" style="margin-left:10px; border:1px solid #ccc; background:#fff; cursor:pointer;">🖨️ Print</button>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; border:2px solid #333;">
        
        <div style="border-right:2px solid #333;">
            <div style="background:#eee; padding:8px; font-weight:bold; text-align:center; border-bottom:1px solid #999; text-transform:uppercase;">
                Particulars (Expenses)
            </div>
            <table style="width:100%; border-collapse:collapse;">
                <tbody>
                    <?php foreach($expenses as $e): ?>
                    <tr>
                        <td style="padding:6px 10px; border-bottom:1px solid #f0f0f0;">To <?= htmlspecialchars($e['name']) ?></td>
                        <td class="num" style="padding:6px 10px; border-bottom:1px solid #f0f0f0;"><?= fmtIndian($e['amt']) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if($net_profit > 0): ?>
                    <tr style="background:#e8f5e9;">
                        <td style="padding:10px; font-weight:bold; color:#2e7d32;">To Net Profit (Transferred to Capital)</td>
                        <td class="num" style="padding:10px; font-weight:bold; color:#2e7d32;"><?= fmtIndian($net_profit) ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <tr><td colspan="2" style="height:20px;"></td></tr>
                </tbody>
            </table>
        </div>

        <div>
            <div style="background:#eee; padding:8px; font-weight:bold; text-align:center; border-bottom:1px solid #999; text-transform:uppercase;">
                Particulars (Income)
            </div>
            <table style="width:100%; border-collapse:collapse;">
                <tbody>
                    <?php foreach($incomes as $i): ?>
                    <tr>
                        <td style="padding:6px 10px; border-bottom:1px solid #f0f0f0;">By <?= htmlspecialchars($i['name']) ?></td>
                        <td class="num" style="padding:6px 10px; border-bottom:1px solid #f0f0f0;"><?= fmtIndian($i['amt']) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if($net_loss > 0): ?>
                    <tr style="background:#ffebee;">
                        <td style="padding:10px; font-weight:bold; color:#c62828;">By Net Loss (Transferred to Capital)</td>
                        <td class="num" style="padding:10px; font-weight:bold; color:#c62828;"><?= fmtIndian($net_loss) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; border:2px solid #333; border-top:none; background:#333; color:white;">
        <div style="padding:8px 15px; display:flex; justify-content:space-between; font-weight:bold; border-right:2px solid #555;">
            <span>TOTAL</span>
            <span>₹ <?= fmtIndian($grand_total) ?></span>
        </div>
        <div style="padding:8px 15px; display:flex; justify-content:space-between; font-weight:bold;">
            <span>TOTAL</span>
            <span>₹ <?= fmtIndian($grand_total) ?></span>
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; left: 0; top: 0; width: 100%; border:none; box-shadow:none; }
        .no-print { display: none; }
    }
</style>