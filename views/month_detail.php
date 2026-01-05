<?php
$month = $_GET['month'] ?? date('Y-m');
$monthName = date("F Y", strtotime($month . "-01"));

// Fetch detailed transactions for this month (Income & Expense only)
$sql = "SELECT j.*, a.name as acc_name, a.type as acc_type 
        FROM journal j 
        JOIN accounts a ON j.account_id = a.id 
        WHERE DATE_FORMAT(j.trans_date, '%Y-%m') = ? 
        AND a.type IN ('Income', 'Expense') 
        ORDER BY j.trans_date ASC, j.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$month]);
$rows = $stmt->fetchAll();

$total_inc = 0;
$total_exp = 0;
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <h2 style="margin:0;">📅 Report: <?= $monthName ?></h2>
            <form method="GET" action="index.php" style="margin:0;">
                <input type="hidden" name="page" value="month_detail">
                <input type="month" name="month" value="<?= $month ?>" onchange="this.form.submit()" style="padding:5px; border-radius:4px; border:1px solid #ccc;">
            </form>
        </div>
        <a href="index.php" class="btn-blue">Back to Dashboard</a>
    </div>

    <table>
        <thead><tr><th>Date</th><th>Ref</th><th>Description</th><th>Account</th><th>Type</th><th class="num">Amount</th><th style="width:100px;"></th></tr></thead>
        <tbody>
            <?php if(empty($rows)): ?>
                <tr><td colspan="7" style="text-align:center; padding:20px; color:#999;">No Income or Expenses recorded this month.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): 
                    $amt = $r['debit'] > 0 ? $r['debit'] : $r['credit'];
                    if($r['acc_type'] == 'Income') $total_inc += $amt; 
                    else $total_exp += $amt;
                    
                    $displayId = (strpos($r['entry_group_id'], '-') !== false) ? $r['entry_group_id'] : 'OLD';
                ?>
                <tr>
                    <td><?= fmtDate($r['trans_date']) ?></td>
                    <td class="ref-col"><?= $displayId ?></td>
                    <td><?= htmlspecialchars($r['description']) ?></td>
                    <td><?= htmlspecialchars($r['acc_name']) ?></td>
                    <td>
                        <span style="padding:2px 6px; border-radius:3px; font-size:0.8em; 
                            background:<?= $r['acc_type'] == 'Income' ? '#d4edda; color:#155724' : '#f8d7da; color:#721c24' ?>">
                            <?= $r['acc_type'] ?>
                        </span>
                    </td>
                    <td class="num" style="font-weight:bold; color:<?= $r['acc_type'] == 'Income' ? '#27ae60' : '#c0392b' ?>">
                        <?= fmtIndian($amt) ?>
                    </td>
                    <td style="text-align:center;">
                        <a href="index.php?edit=<?= $r['entry_group_id'] ?>" class="btn-sm btn-edit">✎</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#f9f9f9; font-weight:bold; border-top:2px solid #333;">
                    <td colspan="5" style="text-align:right;">
                        Income: <span style="color:#27ae60"><?= fmtIndian($total_inc) ?></span> | 
                        Expense: <span style="color:#c0392b"><?= fmtIndian($total_exp) ?></span> | 
                        Net:
                    </td>
                    <td class="num" style="color: <?= ($total_inc - $total_exp) >= 0 ? '#27ae60' : '#c0392b' ?>">
                        <?= fmtIndian($total_inc - $total_exp) ?>
                    </td>
                    <td></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>