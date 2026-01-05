<?php
// Group accounts for display
$grouped = ['Asset'=>[], 'Liability'=>[], 'Equity'=>[], 'Income'=>[], 'Expense'=>[]];
foreach($acc_list as $a) $grouped[$a['type']][] = $a;

// Check for Edit Mode
$edit_data = null;
if(isset($_GET['edit'])) {
    foreach($acc_list as $a) if($a['id']==$_GET['edit']) { $edit_data=$a; break; }
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 style="margin:0;">📂 Chart of Accounts</h2>
        <a href="index.php" class="btn-blue">Back to Dashboard</a>
    </div>

    <?php if(isset($msg_acc) && $msg_acc): ?><div style="color:red; margin:10px 0;"><?= $msg_acc ?></div><?php endif; ?>

    <div style="background:#f9f9f9; padding:15px; margin:20px 0; border:1px solid #ddd; border-radius:4px;">
        <h3><?= $edit_data ? '✏️ Edit Account' : '➕ New Account' ?></h3>
        <form method="POST" action="index.php">
            <?php if($edit_data): ?><input type="hidden" name="acc_id" value="<?= $edit_data['id'] ?>"><?php endif; ?>
            <input type="text" name="name" value="<?= $edit_data['name']??'' ?>" placeholder="Name" required style="width:40%; padding:8px;">
            <select name="type" style="width:30%; padding:8px;">
                <?php foreach(['Expense','Income','Asset','Liability','Equity'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($edit_data && $edit_data['type']==$t)?'selected':'' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="save_acc" class="btn-blue"><?= $edit_data?'Update':'Create' ?></button>
            <?php if($edit_data): ?><a href="index.php?page=accounts" style="color:red; margin-left:10px;">Cancel</a><?php endif; ?>
        </form>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
        <?php 
        $colors = ['Asset'=>'#17a2b8', 'Liability'=>'#dc3545', 'Equity'=>'#dc3545', 'Income'=>'#28a745', 'Expense'=>'#ffc107'];
        foreach(['Asset', 'Liability', 'Income', 'Expense'] as $grp): 
            // Combine Liab & Equity
            $data = ($grp=='Liability') ? array_merge($grouped['Liability'], $grouped['Equity']) : $grouped[$grp];
            $col = $colors[$grp];
        ?>
        <div>
            <h3 style="background:<?= $col ?>; color:<?= $grp=='Expense'?'black':'white' ?>; padding:10px; margin:0;"><?= $grp=='Liability'?'Liabilities & Equity':$grp ?></h3>
            <table style="border:1px solid <?= $col ?>">
                <?php foreach($data as $acc): ?>
                <tr>
                    <td>
                        <a href="index.php?page=ledger&id=<?= $acc['id'] ?>" style="font-weight:bold; color:#0056b3; text-decoration:none;">
                            <?= htmlspecialchars($acc['name']) ?>
                        </a>
                        <br><span style="font-size:0.8em; color:#666"><?= $acc['type'] ?></span>
                    </td>
                    <td class="num"><?= fmtIndian($acc['bal']) ?></td>
                    <td style="text-align:right;">
                        <a href="index.php?page=accounts&edit=<?= $acc['id'] ?>" style="text-decoration:none;">✏️</a>
                        <form method="POST" action="index.php" style="display:inline;" onsubmit="return confirm('Delete?');">
                            <input type="hidden" name="acc_id" value="<?= $acc['id'] ?>">
                            <input type="hidden" name="del_acc" value="1">
                            <button style="border:none; background:none; cursor:pointer;">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endforeach; ?>
    </div>
</div>