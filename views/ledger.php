<?php
// 1. Get Account ID (if exists)
$id = $_GET['id'] ?? 0;
$account = null;
$entries = [];
$balance = 0;

// 2. Fetch Data if ID is set
if ($id) {
    // Get Account Details
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
    $stmt->execute([$id]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($account) {
        // Get Transactions
        $sql = "SELECT * FROM journal WHERE account_id = ? ORDER BY trans_date ASC, entry_group_id ASC";
        $entries = $pdo->prepare($sql);
        $entries->execute([$id]);
        $entries = $entries->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>📖 General Ledger</h2>
        <a href="index.php" class="btn-blue">Back to Dashboard</a>
    </div>

    <div style="background:#f8f9fa; padding:15px; border-radius:8px; border:1px solid #ddd; margin-bottom:20px;">
        <label style="font-weight:bold; color:#555; display:block; margin-bottom:5px;">🔍 Select Account to View:</label>
        <select id="ledgerSelect" class="entry-select" onchange="window.location.href='index.php?page=ledger&id='+this.value">
            <option></option>
            <?php foreach($acc_list as $a): ?>
                <option value="<?= $a['id'] ?>" <?= ($id == $a['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a['name']) ?> (<?= $a['type'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($account): ?>
        <div style="margin-bottom:20px; border-left:5px solid #007bff; padding-left:15px;">
            <h1 style="margin:0; color:#333;"><?= htmlspecialchars($account['name']) ?></h1>
            <span style="color:#666; font-size:0.9em; text-transform:uppercase; font-weight:bold; letter-spacing:1px;"><?= $account['type'] ?> ACCOUNT</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:120px;">Date</th>
                    <th>Description</th>
                    <th class="num" style="width:120px;">Debit</th>
                    <th class="num" style="width:120px;">Credit</th>
                    <th class="num" style="width:120px; background:#e8f4f8;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background:#f9f9f9;">
                    <td colspan="4" style="text-align:right; font-weight:bold; color:#999;">Opening Balance</td>
                    <td class="num" style="font-weight:bold;">0.00</td>
                </tr>

                <?php 
                $running_bal = 0;
                $total_dr = 0;
                $total_cr = 0;

                foreach($entries as $row): 
                    $dr = floatval($row['debit']);
                    $cr = floatval($row['credit']);
                    $total_dr += $dr;
                    $total_cr += $cr;

                    // Calculate Running Balance based on Account Type
                    if (in_array($account['type'], ['Asset', 'Expense'])) {
                        $running_bal += ($dr - $cr);
                    } else {
                        // Liability, Equity, Income (Credit normal)
                        $running_bal += ($cr - $dr);
                    }

                    // --- NEW: FUTURE DATE CHECK ---
                    $is_future = ($row['trans_date'] > date('Y-m-d'));
                    
                    // 1. Row Style (Light Blue)
                    $row_style = $is_future ? 'style="background-color: #e3f2fd !important;"' : ''; 
                    
                    // 2. Badge (FUT)
                    $fut_badge = $is_future ? '<span style="background:#17a2b8; color:white; padding:2px 6px; border-radius:4px; font-size:0.75em; font-weight:bold; margin-right:5px;">FUT</span>' : '';
                ?>
                <tr <?= $row_style ?>>
                    <td style="color:#555;">
                        <?= $fut_badge ?><?= fmtDate($row['trans_date']) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($row['description']) ?>
                        <div style="font-size:0.75em; color:#999; margin-top:2px;">Ref: <?= $row['entry_group_id'] ?></div>
                    </td>
                    <td class="num" style="color:#c0392b;"><?= ($dr > 0) ? fmtIndian($dr) : '-' ?></td>
                    <td class="num" style="color:#27ae60;"><?= ($cr > 0) ? fmtIndian($cr) : '-' ?></td>
                    <td class="num" style="font-weight:bold; background:#e8f4f8; color:#004085;"><?= fmtIndian($running_bal) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#333; color:white;">
                    <td colspan="2" style="text-align:right; font-weight:bold;">TOTALS</td>
                    <td class="num" style="font-weight:bold; color:#ffcccc;"><?= fmtIndian($total_dr) ?></td>
                    <td class="num" style="font-weight:bold; color:#ccffcc;"><?= fmtIndian($total_cr) ?></td>
                    <td class="num" style="font-weight:bold; background:#2c3e50;"><?= fmtIndian($running_bal) ?></td>
                </tr>
            </tfoot>
        </table>

    <?php elseif ($id && !$account): ?>
        <div style="text-align:center; padding:50px; color:#c0392b;">
            <h3>❌ Account Not Found</h3>
            <p>The account ID you requested does not exist.</p>
        </div>

    <?php else: ?>
        <div style="text-align:center; padding:60px 20px; color:#666;">
            <div style="font-size:3em; margin-bottom:10px;">📚</div>
            <h3>Select an Account</h3>
            <p>Use the search box above to view the ledger for any account.</p>
        </div>
    <?php endif; ?>
</div>

<?php if(!$id): ?>
<script>
$(document).ready(function() {
    $('#ledgerSelect').select2('open');
});
</script>
<?php endif; ?>