<div id="quickAccModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="background:white; width:350px; margin:100px auto; padding:25px; border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0;">⚡ New Account</h3>
        
        <label style="font-weight:bold; font-size:0.9em;">Account Name</label>
        <input type="text" id="qa_name" placeholder="e.g. Office Rent" class="form-input-lg" style="width:100%; margin-bottom:15px;">
        
        <label style="font-weight:bold; font-size:0.9em;">Type</label>
        <select id="qa_type" class="form-input-lg" style="width:100%; margin-bottom:20px;">
            <option value="Expense">Expense</option>
            <option value="Income">Income</option>
            <option value="Asset">Asset</option>
            <option value="Liability">Liability</option>
            <option value="Equity">Equity</option>
        </select>
        
        <div style="text-align:right;">
            <button type="button" onclick="$('#quickAccModal').hide()" style="background:#ddd; color:#333; border:none; padding:10px 15px; border-radius:4px; cursor:pointer; margin-right:10px;">Cancel</button>
            <button type="button" onclick="saveQuickAccount()" class="btn-green" style="height:auto; padding:10px 20px; font-size:0.9rem;">Save Account</button>
        </div>
    </div>
</div>

<div class="card" style="background: #e8f4f8; border: 1px solid #b8daff;">
    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
        <h3 style="margin:0; color:#004085;">🏦 Liquid Assets (Bank & Cash)</h3>
        <h3 style="margin:0; color:#004085;">Total: <?= fmtIndian($total_liq) ?></h3>
    </div>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:15px;">
        <?php foreach($liquids as $lb): ?>
            <div style="background:white; padding:10px; border-radius:4px; border-left: 4px solid #17a2b8; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div style="font-size:0.85rem; color:#666; font-weight:bold;"><?= htmlspecialchars($lb['name']) ?></div>
                <div style="font-size:1.1rem; font-weight:bold;">
                    <a href="index.php?page=ledger&id=<?= $lb['id'] ?>" style="text-decoration:none; color:#0056b3; border-bottom:1px dashed #0056b3;">
                        <?= fmtIndian($lb['bal']) ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="grid-row">
    <div class="card">
        <h2>📉 Profit & Loss</h2>
        <table>
            <tr><td>Income</td><td class="num" style="color:#27ae60;"><?= fmtIndian($totals['Income']) ?></td></tr>
            <tr><td>Expenses</td><td class="num" style="color:#c0392b;">-<?= fmtIndian($totals['Expense']) ?></td></tr>
            <tr style="font-weight:bold; border-top:2px solid #ddd;"><td>Net Profit</td><td class="num"><?= fmtIndian($net_profit) ?></td></tr>
        </table>
    </div>

    <div class="card">
        <h2>⚖️ Balance Sheet</h2>
        <table>
            <tr><td>Assets</td><td class="num"><?= fmtIndian($totals['Asset']) ?></td></tr>
            <tr><td colspan="2" style="height:10px;"></td></tr> <tr><td>Liabilities</td><td class="num"><?= fmtIndian($totals['Liability']) ?></td></tr>
            <tr><td>Total Equity</td><td class="num"><?= fmtIndian($totals['Equity'] + $net_profit) ?></td></tr>
            
            <tr style="font-weight:bold; border-top:2px solid #333; background:#f8f9fa;">
                <td>Total (Liab + Eq)</td>
                <td class="num" style="color: #004085;">
                    <?= fmtIndian($totals['Liability'] + $totals['Equity'] + $net_profit) ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>💼 Owner's Equity</h2>
        <table>
            <tr><td style="color:#666;">Opening Balances (Capital)</td><td class="num"><?= fmtIndian($totals['Equity']) ?></td></tr>
            <tr><td colspan="2" style="border-bottom:1px solid #eee;"></td></tr>
            
            <tr><td>(+) Income</td><td class="num" style="color:#27ae60;"><?= fmtIndian($totals['Income']) ?></td></tr>
            <tr><td>(-) Expense</td><td class="num" style="color:#c0392b;">-<?= fmtIndian($totals['Expense']) ?></td></tr>
            <tr style="background:#fffbe6;"><td style="font-weight:bold;">= Retained Earnings</td><td class="num" style="font-weight:bold;"><?= fmtIndian($net_profit) ?></td></tr>
            
            <tr style="font-weight:bold; border-top:2px solid #333; background:#e8f4f8;">
                <td>Total Equity</td>
                <td class="num" style="color: #004085;">
                    <?= fmtIndian($totals['Equity'] + $net_profit) ?>
                </td>
            </tr>
        </table>
    </div>
</div> 

<div class="card" style="border-top: 6px solid #28a745;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 style="margin-top:0;">➕ Record Transaction</h2>
        <button type="button" onclick="$('#quickAccModal').show(); $('#qa_name').focus();" class="btn-blue" style="font-size:0.8rem;">⚡ New Account</button>
    </div>
    <form method="POST" action="index.php" id="entryForm">
        <input type="hidden" name="save_trans" value="1">
        <input type="hidden" name="group_id" value="<?= $edit_trans_data['group_id'] ?? '' ?>">
        
        <div class="form-row">
            <div class="form-col" style="flex:1;">
                <label class="form-label">Date</label>
                <input type="date" name="date" value="<?= $edit_trans_data['date'] ?? date('Y-m-d') ?>" class="form-input-lg" required>
            </div>
            <div class="form-col" style="flex:3;">
                <label class="form-label">Description</label>
                <input type="text" name="desc" value="<?= htmlspecialchars($edit_trans_data['desc'] ?? '') ?>" class="form-input-lg" placeholder="Transaction details..." required>
            </div>
        </div>

        <table style="width:100%; margin-top:15px;" id="entryTable">
            <thead>
                <tr><th style="width:50%">Account</th><th style="width:20%">Debit</th><th style="width:20%">Credit</th><th style="width:10%"></th></tr>
            </thead>
            <tbody id="lines">
                <?php 
                $rows_to_show = $edit_rows ?? [];
                if (empty($rows_to_show)) {
                    $rows_to_show = [['account_id'=>'', 'debit'=>'', 'credit'=>''], ['account_id'=>'', 'debit'=>'', 'credit'=>'']];
                }
                foreach($rows_to_show as $row): 
                ?>
                <tr>
                    <td>
                        <select name="acc_id[]" class="entry-select" required>
                            <option></option>
                            <?php foreach($acc_list as $a): ?>
                                <option value="<?= $a['id'] ?>" data-balance="<?= $a['bal'] ?>" data-type="<?= $a['type'] ?>" <?= ($row['account_id']==$a['id'])?'selected':'' ?>>
                                    <?= htmlspecialchars($a['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="bal-indicator" style="font-size:0.75rem; margin-left:5px;"></span>
                    </td>
                    <td><input type="number" step="0.01" name="dr_amount[]" value="<?= floatval($row['debit']) ?: '' ?>" class="form-input-lg dr-input" placeholder="0.00" onkeyup="smartAutoFill(this)"></td>
                    <td><input type="number" step="0.01" name="cr_amount[]" value="<?= floatval($row['credit']) ?: '' ?>" class="form-input-lg cr-input" placeholder="0.00" onkeyup="smartAutoFill(this)"></td>
                    <td style="text-align:center;"><button type="button" class="btn-sm btn-del" onclick="removeRow(this)">×</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#fff; font-weight:bold;">
                    <td style="text-align:right; padding-top:10px;">TOTAL:</td>
                    <td style="padding-top:10px;"><span id="tot_dr" style="color:#c0392b">0.00</span></td>
                    <td style="padding-top:10px;"><span id="tot_cr" style="color:#27ae60">0.00</span></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top:10px; display:flex; gap:10px;">
            <button type="button" class="btn-purple" onclick="addRow()" style="font-size:0.9rem; width:auto; padding:0 15px;">+ Add Line</button>
            <button type="submit" class="btn-green" style="flex:1;">Save Transaction</button>
        </div>
    </form>
</div>

<div class="card">
    <h2>📅 Monthly Report</h2>
    <table>
        <thead><tr><th>Month</th><th class="num">Income</th><th class="num">Expense</th><th class="num">Net Savings</th></tr></thead>
        <tbody>
            <?php foreach ($monthly as $m => $data): $net = $data['Income'] - $data['Expense']; ?>
            <tr>
                <td><a href="index.php?page=month_detail&month=<?= $m ?>" class="link"><?= date("F Y", strtotime($m . "-01")) ?></a></td>
                <td class="num" style="color:#27ae60;"><?= fmtIndian($data['Income']) ?></td>
                <td class="num" style="color:#c0392b;"><?= fmtIndian($data['Expense']) ?></td>
                <td class="num" style="font-weight:bold; color: <?= $net >= 0 ? '#27ae60' : '#c0392b' ?>"><?= fmtIndian($net) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>📚 Recent Journal</h2>
    <?php $count = $pdo->query("SELECT COUNT(DISTINCT entry_group_id) FROM journal")->fetchColumn(); ?>
    <span style="font-size:0.8em; color:#666; font-weight:normal;">(Total Vouchers: <?= $count ?>)</span>
    <input type="text" id="searchInput" class="search-box" placeholder="🔍 Search transactions...">
    <table id="journalTable">
        <thead><tr><th>Date</th><th>Ref</th><th>Desc</th><th>Account</th><th class="num">Dr</th><th class="num">Cr</th><th>Action</th></tr></thead>
        <tbody>
            <tr><td colspan="7" style="text-align:center; padding:20px; color:#999;">Type in search box to load history...</td></tr>
        </tbody>
    </table>
</div>

<script>
    function fmtInd(x) {
        if(!x) return '0.00';
        x = x.toString();
        var lastThree = x.substring(x.length-3);
        var otherNumbers = x.substring(0,x.length-3);
        if(otherNumbers != '') lastThree = ',' + lastThree;
        return otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ",") + lastThree;
    }

    // --- LIVE BALANCE DISPLAY ---
    $(document).on('change', '.entry-select', function() {
        let opt = $(this).find(':selected');
        let bal = parseFloat(opt.data('balance'));
        let type = opt.data('type');
        let span = $(this).closest('td').find('.bal-indicator');
        
        if (isNaN(bal)) { span.text(''); return; }
        
        let isNeg = false;
        if ((type === 'Asset' || type === 'Expense') && bal < 0) isNeg = true;
        if ((type === 'Liability' || type === 'Income' || type === 'Equity') && bal < 0) isNeg = true;
        
        let color = isNeg ? '#c62828' : '#28a745';
        let icon = isNeg ? '⚠️ ' : '';
        span.html(`<span style="color:${color}; font-weight:bold;">${icon}₹ ${fmtInd(bal.toFixed(2))}</span>`);
    });

    // --- FIX: CORRECT MATH FOR AUTO FILL ---
    function smartAutoFill(element) {
        let currentRow = $(element).closest('tr');
        let nextRow = currentRow.next('tr');
        
        // Only proceed if there is a next row
        if (nextRow.length === 0) return;

        // Determine which column we are filling (Opposite of current)
        let isDrInput = $(element).hasClass('dr-input');
        let targetSelector = isDrInput ? '.cr-input' : '.dr-input';
        let targetInput = nextRow.find(targetSelector);

        // 1. Calculate sums of ALL inputs EXCEPT the target one
        let sumDr = 0;
        let sumCr = 0;

        $('.dr-input').each(function() {
            if ($(this).is(targetInput)) return; // Ignore target
            sumDr += Number($(this).val()) || 0;
        });

        $('.cr-input').each(function() {
            if ($(this).is(targetInput)) return; // Ignore target
            sumCr += Number($(this).val()) || 0;
        });

        // 2. The difference is exactly what goes into target to balance it
        let diff = Math.abs(sumDr - sumCr);

        // 3. Fill target
        targetInput.val(diff.toFixed(2));

        // 4. Update UI totals
        calcTotals();
    }

    function saveQuickAccount() {
        let name = $('#qa_name').val();
        let type = $('#qa_type').val();
        if(!name) { alert("Please enter an account name."); return; }
        
        let btn = event.target;
        let oldText = btn.innerText;
        btn.innerText = "Saving...";
        btn.disabled = true;

        $.post("index.php", { quick_add_name: name, quick_add_type: type }, function(data) {
            try {
                let res = JSON.parse(data);
                if(res.status === 'success') {
                    let newOption = new Option(res.name, res.id, false, false);
                    $(newOption).attr('data-balance', 0).attr('data-type', type);
                    $('.entry-select').append(newOption).trigger('change');
                    $('#quickAccModal').hide();
                    $('#qa_name').val('');
                    alert("Account '" + res.name + "' created!");
                } else {
                    alert("Error: " + res.message);
                }
            } catch(e) { console.log(data); alert("System Error."); }
            btn.innerText = oldText;
            btn.disabled = false;
        });
    }

    function calcTotals() {
        let dr = 0, cr = 0;
        document.querySelectorAll('.dr-input').forEach(i => dr += Number(i.value));
        document.querySelectorAll('.cr-input').forEach(i => cr += Number(i.value));
        
        document.getElementById('tot_dr').innerText = dr.toFixed(2);
        document.getElementById('tot_cr').innerText = cr.toFixed(2);
        
        if(Math.abs(dr - cr) > 0.01) {
            document.getElementById('tot_dr').style.borderBottom = "2px solid red";
            document.getElementById('tot_cr').style.borderBottom = "2px solid red";
        } else {
            document.getElementById('tot_dr').style.borderBottom = "none";
            document.getElementById('tot_cr').style.borderBottom = "none";
        }
    }

    function addRow() {
        let html = `<tr>
            <td>
                <select name="acc_id[]" class="new-select" required style="width:100%"><option></option>
                <?php foreach($acc_list as $a): ?>
                    <option value="<?= $a['id'] ?>" data-balance="<?= $a['bal'] ?>" data-type="<?= $a['type'] ?>"><?= addslashes(htmlspecialchars($a['name'])) ?></option>
                <?php endforeach; ?>
                </select>
                <span class="bal-indicator" style="font-size:0.75rem; margin-left:5px;"></span>
            </td>
            <td><input type="number" step="0.01" name="dr_amount[]" class="form-input-lg dr-input" placeholder="0.00" onkeyup="smartAutoFill(this)"></td>
            <td><input type="number" step="0.01" name="cr_amount[]" class="form-input-lg cr-input" placeholder="0.00" onkeyup="smartAutoFill(this)"></td>
            <td style="text-align:center;"><button type="button" class="btn-sm btn-del" onclick="removeRow(this)">×</button></td>
        </tr>`;
        $('#lines').append(html);
        $('.new-select').select2({ placeholder: "Select Account...", width: '100%' }).removeClass('new-select').addClass('entry-select');
    }

    function removeRow(btn) {
        if(document.querySelectorAll('#lines tr').length > 2) {
            btn.closest('tr').remove();
            calcTotals();
        } else { alert("Minimum 2 rows required."); }
    }
    
   $(document).ready(function() {
    fetchJournal('');
    $('#searchInput').on('keyup', function() {
        let searchTerm = $(this).val();
        fetchJournal(searchTerm);
    });
    calcTotals();
    $('.entry-select').trigger('change');
});

function fetchJournal(term) {
    $.post("index.php", { ajax_search: 1, search_term: term }, function(data) { 
        $("#journalTable tbody").html(data); 
    });
}
</script>