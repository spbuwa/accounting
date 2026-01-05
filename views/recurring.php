<div class="card" style="margin-bottom: 80px;"> <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>🗓️ Monthly Recurring Schedule</h2>
        <a href="index.php" class="btn-blue">Back to Dashboard</a>
    </div>
    <p style="color:#666;">Set up transactions that happen on specific dates. Click <b>Edit (✎)</b> to change amounts or dates.</p>

    <div style="background:#f8f9fa; padding:20px; border:1px solid #ddd; border-radius:8px; margin-bottom:30px; border-left: 5px solid <?= $edit_tpl ? '#ffc107' : '#28a745' ?>;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:15px;">
            <h3 style="margin:0;"><?= $edit_tpl ? '✎ Edit Template' : '➕ New Template' ?></h3>
            <?php if($edit_tpl): ?>
                <a href="index.php?page=recurring" style="font-size:0.9rem; color:#c0392b; text-decoration:none;">Cancel Edit ✕</a>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="index.php" id="tplForm" onsubmit="return validateTplForm()">
            <input type="hidden" name="save_template" value="1">
            <?php if($edit_tpl): ?>
                <input type="hidden" name="tpl_id" value="<?= $edit_tpl['head']['id'] ?>">
            <?php endif; ?>
            
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div style="width:120px;">
                    <label style="font-weight:bold; font-size:0.9em;">Day of Month</label>
                    <select name="day" class="form-input-lg" style="padding:5px;" required>
                        <?php for($i=1; $i<=31; $i++): 
                            $sel = ($edit_tpl && $edit_tpl['head']['day_of_month'] == $i) ? 'selected' : '';
                        ?>
                            <option value="<?= $i ?>" <?= $sel ?>><?= $i ?><?= ($i==1)?'st':(($i==2)?'nd':(($i==3)?'rd':'th')) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div style="flex:1;">
                    <label style="font-weight:bold; font-size:0.9em;">Template Name</label>
                    <input type="text" name="desc" value="<?= htmlspecialchars($edit_tpl['head']['description'] ?? '') ?>" placeholder="e.g. Salary Received" class="form-input-lg" required>
                </div>
            </div>

            <table style="width:100%;">
                <thead>
                    <tr><th style="width:50%">Account</th><th style="width:20%">Debit</th><th style="width:20%">Credit</th><th style="width:10%"></th></tr>
                </thead>
                <tbody id="tpl_lines">
                    <?php 
                    $rows_to_show = [];
                    if ($edit_tpl) {
                        foreach($edit_tpl['lines'] as $l) {
                            $rows_to_show[] = [
                                'acc_id' => $l['account_id'],
                                'dr' => ($l['type']=='DEBIT') ? $l['amount'] : '',
                                'cr' => ($l['type']=='CREDIT') ? $l['amount'] : ''
                            ];
                        }
                    } else {
                        $rows_to_show = [['acc_id'=>'', 'dr'=>'', 'cr'=>''], ['acc_id'=>'', 'dr'=>'', 'cr'=>'']];
                    }

                    foreach($rows_to_show as $row): 
                    ?>
                    <tr>
                        <td>
                            <select name="acc_id[]" class="entry-select" style="width:100%" required>
                                <option></option>
                                <?php foreach($acc_list as $a): ?>
                                    <option value="<?= $a['id'] ?>" <?= ($row['acc_id']==$a['id'])?'selected':'' ?>>
                                        <?= htmlspecialchars($a['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" step="0.01" name="dr_amount[]" value="<?= $row['dr'] ?>" class="form-input-lg tpl-dr" placeholder="0.00" onkeyup="calcTplTotals()"></td>
                        <td><input type="number" step="0.01" name="cr_amount[]" value="<?= $row['cr'] ?>" class="form-input-lg tpl-cr" placeholder="0.00" onkeyup="calcTplTotals()"></td>
                        <td style="text-align:center;"><button type="button" class="btn-sm btn-del" onclick="removeTplRow(this)">×</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td style="text-align:right; font-weight:bold; padding-top:10px;">Total:</td>
                        <td style="font-weight:bold; padding-top:10px;"><span id="t_dr" style="color:#c0392b">0.00</span></td>
                        <td style="font-weight:bold; padding-top:10px;"><span id="t_cr" style="color:#27ae60">0.00</span></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <div style="margin-top:15px; display:flex; gap:10px;">
                <button type="button" class="btn-purple" onclick="addTplRow()" style="font-size:0.9rem;">+ Add Line</button>
                <button type="submit" class="btn-green" style="flex:1;">
                    <?= $edit_tpl ? '💾 Update Template' : '💾 Save Template' ?>
                </button>
            </div>
        </form>
    </div>

    <form method="POST" action="index.php">
        <input type="hidden" name="process_recurring" value="1">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; background:#e8f4f8; padding:15px; border-radius:4px; border:1px solid #b8daff;">
            <div style="display:flex; align-items:center; gap:10px;">
                <strong style="color:#004085;">📅 Post Date:</strong>
                <input type="date" name="target_date" value="<?= date('Y-m-d') ?>" class="form-input-lg" style="width:auto; height:40px;">
            </div>
            <button type="submit" class="btn-purple" onclick="return confirm('Post selected templates?')">🚀 Post Selected</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">Select</th>
                    <th style="width:80px; text-align:center; background:#fffbe6;">Day</th>
                    <th>Template Name</th>
                    <th>Account</th>
                    <th class="num" style="width:120px;">Debit</th>
                    <th class="num" style="width:120px;">Credit</th>
                    <th style="width:80px; text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $tpls = $pdo->query("SELECT * FROM recurring_templates ORDER BY day_of_month ASC, description ASC")->fetchAll();
                
                if(!$tpls) echo '<tr><td colspan="7" style="text-align:center; padding:20px; color:#999;">No templates yet.</td></tr>';

                foreach($tpls as $t): 
                    // Fetch lines including ACCOUNT TYPE for calculation
                    $lines_stmt = $pdo->prepare("SELECT l.*, a.name, a.type as acc_type FROM recurring_lines l JOIN accounts a ON l.account_id=a.id WHERE template_id=?");
                    $lines_stmt->execute([$t['id']]);
                    $rows = $lines_stmt->fetchAll();

                    // Fallback logic
                    if(empty($rows) && !empty($t['dr_acc_id'])) {
                        // We fetch type here too for fallback
                        $dr_info = $pdo->query("SELECT name, type FROM accounts WHERE id=".$t['dr_acc_id'])->fetch();
                        $cr_info = $pdo->query("SELECT name, type FROM accounts WHERE id=".$t['cr_acc_id'])->fetch();
                        $rows = [
                            ['name' => $dr_info['name'], 'type' => 'DEBIT', 'amount' => $t['amount'], 'acc_type' => $dr_info['type']],
                            ['name' => $cr_info['name'], 'type' => 'CREDIT', 'amount' => $t['amount'], 'acc_type' => $cr_info['type']]
                        ];
                    }

                    $count = count($rows);
                    foreach($rows as $index => $r):
                ?>
                <tr 
                    style="border-bottom: <?= ($index == $count-1) ? '2px solid #ddd' : '1px solid #f9f9f9' ?>;" 
                    class="tpl-row-<?= $t['id'] ?>"
                    data-acc-type="<?= $r['acc_type'] ?>" 
                    data-dr-cr="<?= $r['type'] ?>" 
                    data-amount="<?= $r['amount'] ?>"
                >
                    <?php if($index === 0): ?>
                        <td rowspan="<?= $count ?>" style="text-align:center; vertical-align:middle; background:#fff;">
                            <input type="checkbox" name="tpl_ids[]" value="<?= $t['id'] ?>" class="chk-row" style="width:20px; height:20px; cursor:pointer;" onchange="updateForecast()">
                        </td>
                        <td rowspan="<?= $count ?>" style="text-align:center; vertical-align:middle; font-weight:bold; font-size:1.2rem; background:#fffbe6; color:#b7791f;">
                            <?= $t['day_of_month'] ?>
                        </td>
                        <td rowspan="<?= $count ?>" style="vertical-align:middle; font-weight:bold; background:#fff; color:#333;">
                            <?= htmlspecialchars($t['description']) ?>
                        </td>
                    <?php endif; ?>

                    <td style="color:#555;">
                        <?= htmlspecialchars($r['name']) ?> 
                        <span style="font-size:0.7em; color:#999;">(<?= $r['acc_type'] ?>)</span>
                    </td>
                    <td class="num" style="color:#c0392b;"><?= ($r['type']=='DEBIT') ? fmtIndian($r['amount']) : '' ?></td>
                    <td class="num" style="color:#27ae60;"><?= ($r['type']=='CREDIT') ? fmtIndian($r['amount']) : '' ?></td>

                    <?php if($index === 0): ?>
                        <td rowspan="<?= $count ?>" style="text-align:center; vertical-align:middle; background:#fff;">
                            <a href="index.php?page=recurring&edit_template=<?= $t['id'] ?>" class="btn-sm btn-clone" title="Edit">✎</a>
                            <a href="index.php?del_template=<?= $t['id'] ?>" onclick="return confirm('Delete Template?')" class="btn-sm btn-del" title="Delete">×</a>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; endforeach; ?>
            </tbody>
        </table>
    </form>
</div>

<div id="forecastBar" style="position:fixed; bottom:0; left:0; width:100%; background:#2c3e50; color:white; padding:15px; box-shadow:0 -2px 10px rgba(0,0,0,0.2); display:none; justify-content:center; gap:30px; z-index:999;">
    <div style="text-align:center;">
        <span style="display:block; font-size:0.8em; color:#bdc3c7;">TOTAL INCOME</span>
        <span id="fc_income" style="font-weight:bold; font-size:1.1em; color:#2ecc71;">0.00</span>
    </div>
    <div style="text-align:center;">
        <span style="display:block; font-size:0.8em; color:#bdc3c7;">TOTAL EXPENSE</span>
        <span id="fc_expense" style="font-weight:bold; font-size:1.1em; color:#e74c3c;">0.00</span>
    </div>
    <div style="border-left:1px solid #7f8c8d;"></div>
    <div style="text-align:center;">
        <span style="display:block; font-size:0.8em; color:#bdc3c7;">CASH INFLOW</span>
        <span id="fc_in" style="font-weight:bold; font-size:1.1em; color:#3498db;">0.00</span>
    </div>
    <div style="text-align:center;">
        <span style="display:block; font-size:0.8em; color:#bdc3c7;">CASH OUTFLOW</span>
        <span id="fc_out" style="font-weight:bold; font-size:1.1em; color:#f1c40f;">0.00</span>
    </div>
</div>

<script>
// --- FORECAST CALCULATION LOGIC ---
function updateForecast() {
    let totalIncome = 0;
    let totalExpense = 0;
    let cashIn = 0;
    let cashOut = 0;
    let anyChecked = false;

    // Loop through all checkboxes
    document.querySelectorAll('.chk-row').forEach(chk => {
        if (chk.checked) {
            anyChecked = true;
            let tplId = chk.value;
            // Find all rows belonging to this template
            document.querySelectorAll('.tpl-row-' + tplId).forEach(row => {
                let amt = parseFloat(row.getAttribute('data-amount')) || 0;
                let drCr = row.getAttribute('data-dr-cr'); // DEBIT or CREDIT
                let type = row.getAttribute('data-acc-type'); // Asset, Expense, Income, etc.

                // 1. P&L Impact
                if (type === 'Income' && drCr === 'CREDIT') totalIncome += amt;
                if (type === 'Expense' && drCr === 'DEBIT') totalExpense += amt;

                // 2. Cash Flow Impact (Asset Movement)
                if (type === 'Asset') {
                    if (drCr === 'DEBIT') cashIn += amt;  // Money entering bank
                    if (drCr === 'CREDIT') cashOut += amt; // Money leaving bank
                }
            });
        }
    });

    // Update UI
    if (anyChecked) {
        $('#forecastBar').css('display', 'flex');
        $('#fc_income').text(totalIncome.toLocaleString('en-IN', {minimumFractionDigits: 2}));
        $('#fc_expense').text(totalExpense.toLocaleString('en-IN', {minimumFractionDigits: 2}));
        $('#fc_in').text(cashIn.toLocaleString('en-IN', {minimumFractionDigits: 2}));
        $('#fc_out').text(cashOut.toLocaleString('en-IN', {minimumFractionDigits: 2}));
    } else {
        $('#forecastBar').hide();
    }
}

// --- TEMPLATE FORM LOGIC (Existing) ---
function calcTplTotals() {
    let dr = 0, cr = 0;
    document.querySelectorAll('.tpl-dr').forEach(i => dr += Number(i.value));
    document.querySelectorAll('.tpl-cr').forEach(i => cr += Number(i.value));
    
    let drSpan = document.getElementById('t_dr');
    let crSpan = document.getElementById('t_cr');
    
    drSpan.innerText = dr.toFixed(2);
    crSpan.innerText = cr.toFixed(2);
    
    if (Math.abs(dr - cr) > 0.01) {
        drSpan.style.borderBottom = "3px solid #dc3545";
        crSpan.style.borderBottom = "3px solid #dc3545";
    } else {
        drSpan.style.borderBottom = "none";
        crSpan.style.borderBottom = "none";
    }
}
function addTplRow() {
    let html = `<tr>
        <td><select name="acc_id[]" class="new-tpl-select" style="width:100%"><option></option>
            <?php foreach($acc_list as $a): ?><option value="<?= $a['id'] ?>"><?= addslashes(htmlspecialchars($a['name'])) ?></option><?php endforeach; ?>
        </select></td>
        <td><input type="number" step="0.01" name="dr_amount[]" class="form-input-lg tpl-dr" placeholder="0.00" onkeyup="calcTplTotals()"></td>
        <td><input type="number" step="0.01" name="cr_amount[]" class="form-input-lg tpl-cr" placeholder="0.00" onkeyup="calcTplTotals()"></td>
        <td style="text-align:center;"><button type="button" class="btn-sm btn-del" onclick="removeTplRow(this)">×</button></td>
    </tr>`;
    $('#tpl_lines').append(html);
    $('.new-tpl-select').select2({ placeholder: "Select Account...", width: '100%' }).removeClass('new-tpl-select').addClass('entry-select');
}
function removeTplRow(btn) {
    if(document.querySelectorAll('#tpl_lines tr').length > 2) {
        btn.closest('tr').remove();
        calcTplTotals();
    } else { alert("Minimum 2 rows required."); }
}
function validateTplForm() {
    let dr = parseFloat(document.getElementById('t_dr').innerText);
    let cr = parseFloat(document.getElementById('t_cr').innerText);
    if (Math.abs(dr - cr) > 0.01) {
        alert("⚠️ Unbalanced Entry!\n\nTotals do not match. Please fix before saving."); return false;
    }
    if (dr === 0) { alert("⚠️ Amount cannot be zero."); return false; }
    return true;
}
$(document).ready(function() {
    calcTplTotals();
});
</script>