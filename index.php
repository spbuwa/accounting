<?php
// index.php - The Controller

// 1. START OUTPUT BUFFERING
ob_start();

// 2. ENABLE ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// 3. LOGIN LOGIC
$valid_user = "admin";
$valid_pass = "1234";
$login_err = '';

if (isset($_GET['logout'])) {
    if(isset($_SESSION['log_id'])) {
        $pdo->prepare("UPDATE usage_log SET logout_time=NOW(), duration_sec=TIMESTAMPDIFF(SECOND, login_time, NOW()) WHERE id=?")->execute([$_SESSION['log_id']]);
    }
    session_destroy(); 
    header("Location: index.php"); 
    exit; 
}

if (isset($_POST['do_login'])) {
    if ($_POST['username'] === $valid_user && $_POST['password'] === $valid_pass) {
        $_SESSION['loggedin'] = true; 
        
        // START TRACKING
        $pdo->query("INSERT INTO usage_log (login_time) VALUES (NOW())");
        $_SESSION['log_id'] = $pdo->lastInsertId();
        $_SESSION['login_timestamp'] = time(); 
        
        header("Location: index.php"); exit;
    } else { $login_err = "Invalid Credentials"; }
}

// 4. AJAX SEARCH
if (isset($_POST['ajax_search'])) {
    $term = "%".trim($_POST['search_term'])."%";
    $sql = "SELECT j.*, a.name as acc_name 
            FROM journal j 
            JOIN accounts a ON j.account_id = a.id 
            WHERE j.description LIKE ? OR a.name LIKE ? OR j.trans_date LIKE ? OR j.entry_group_id LIKE ?
            ORDER BY j.trans_date DESC, j.entry_group_id DESC, j.debit DESC LIMIT 100";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$term, $term, $term, $term]);
    $results = $stmt->fetchAll();
    
    if(!$results) { echo '<tr><td colspan="7" style="text-align:center; padding:20px;">No results found</td></tr>'; exit; }
    
    foreach($results as $r) {
        $fut = ($r['trans_date'] > date('Y-m-d')) ? '<span class="future-tag">FUT</span>' : '';
        $cls = ($r['trans_date'] > date('Y-m-d')) ? 'future-row' : '';
        $dr = $r['debit']>0 ? fmtIndian($r['debit']) : ''; 
        $cr = $r['credit']>0 ? fmtIndian($r['credit']) : '';
        $displayId = (strpos($r['entry_group_id'], '-') !== false) ? $r['entry_group_id'] : '<span style="color:#ccc; font-size:0.8em">OLD</span>';
        
        echo "<tr class='$cls'><td>$fut ".fmtDate($r['trans_date'])."</td><td class='ref-col'>$displayId</td>
              <td>".htmlspecialchars($r['description'])."</td><td>".htmlspecialchars($r['acc_name'])."</td>
              <td class='num' style='color:#c0392b'>$dr</td><td class='num' style='color:#27ae60'>$cr</td>
              <td style='text-align:center'>
                <a href='index.php?clone=".$r['entry_group_id']."' class='btn-sm btn-clone'>♻️</a>
                <a href='index.php?edit=".$r['entry_group_id']."' class='btn-sm btn-edit'>✎</a>
                <form method='POST' style='display:inline' onsubmit='return confirm(\"Delete?\")'>
                    <input type='hidden' name='grp_id' value='".$r['entry_group_id']."'>
                    <button type='submit' name='del_trans' class='btn-sm btn-del'>×</button>
                </form>
              </td></tr>";
    }
    exit;
}

// 5. QUICK ADD ACCOUNT
if (isset($_POST['quick_add_name'])) {
    if (empty($_POST['quick_add_name'])) {
        echo json_encode(['status'=>'error', 'message'=>'Name is required']); exit;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO accounts (name, type) VALUES (?, ?)");
        $stmt->execute([trim($_POST['quick_add_name']), $_POST['quick_add_type']]);
        echo json_encode(['status'=>'success', 'id'=>$pdo->lastInsertId(), 'name'=>trim($_POST['quick_add_name'])]);
    } catch (Exception $e) {
        echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
    }
    exit;
}

// 6. DELETE TRANSACTION
if (isset($_POST['del_trans'])) {
    $pdo->prepare("DELETE FROM journal WHERE entry_group_id=?")->execute([$_POST['grp_id']]);
    header("Location: index.php"); exit;
}

// 7. ACCOUNT ACTIONS
if (isset($_POST['save_acc'])) {
    if(isset($_POST['acc_id']) && $_POST['acc_id']) {
        $pdo->prepare("UPDATE accounts SET name=?, type=? WHERE id=?")->execute([$_POST['name'], $_POST['type'], $_POST['acc_id']]);
    } else {
        $pdo->prepare("INSERT INTO accounts (name, type) VALUES (?,?)")->execute([$_POST['name'], $_POST['type']]);
    }
    header("Location: index.php?page=accounts"); exit;
}
if (isset($_POST['del_acc'])) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM journal WHERE account_id=?");
    $chk->execute([$_POST['acc_id']]);
    if($chk->fetchColumn() > 0) $msg_acc = "❌ Cannot delete: Account has transactions.";
    else {
        $pdo->prepare("DELETE FROM accounts WHERE id=?")->execute([$_POST['acc_id']]);
        header("Location: index.php?page=accounts"); exit;
    }
}

// 8. SAVE TRANSACTION
if (isset($_POST['save_trans'])) {
    $date = $_POST['date'];
    $desc = $_POST['desc'];
    $gid = $_POST['group_id'];
    
    if ($gid) {
        $pdo->prepare("DELETE FROM journal WHERE entry_group_id=?")->execute([$gid]);
    } else {
        $pfx = date('Ym', strtotime($date)) . '-';
        $lst = $pdo->prepare("SELECT entry_group_id FROM journal WHERE entry_group_id LIKE ? ORDER BY length(entry_group_id) DESC, entry_group_id DESC LIMIT 1");
        $lst->execute([$pfx.'%']);
        $lid = $lst->fetchColumn();
        $num = $lid ? intval(substr($lid, strlen($pfx))) + 1 : 1;
        $gid = $pfx . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
    
    $acc_ids = $_POST['acc_id']; $debits = $_POST['dr_amount']; $credits = $_POST['cr_amount'];
    $stmt = $pdo->prepare("INSERT INTO journal (entry_group_id, trans_date, description, account_id, debit, credit) VALUES (?,?,?,?,?,?)");
    
    for ($i = 0; $i < count($acc_ids); $i++) {
        $aid = $acc_ids[$i]; $dr = floatval($debits[$i]); $cr = floatval($credits[$i]);
        if (empty($aid) || ($dr == 0 && $cr == 0)) continue;
        $stmt->execute([$gid, $date, $desc, $aid, $dr, $cr]);
    }
    header("Location: index.php"); exit;
}

// 9. SYSTEM TOOLS (Backup & Export)
if (isset($_GET['sys_action'])) {
    $act = $_GET['sys_action'];

    // A. EXPORT CSV
    if ($act == 'export_csv') {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="journal_export_'.date('Y-m-d').'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Entry ID', 'Description', 'Account', 'Type', 'Debit', 'Credit']);
        $sql = "SELECT j.trans_date, j.entry_group_id, j.description, a.name, a.type, j.debit, j.credit 
                FROM journal j JOIN accounts a ON j.account_id = a.id ORDER BY j.trans_date DESC";
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $r) { fputcsv($out, $r); }
        fclose($out); exit;
    }

    // B. BACKUP SQL (Fixed)
    if ($act == 'backup_sql') {
        // 1. Clean output buffer to ensure file isn't corrupt
        while (ob_get_level()) ob_end_clean();
        
        // 2. Get All Tables Dynamically
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $sql_dump = "-- BACKUP: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            // Structure
            $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
            $row2 = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
            $sql_dump .= $row2[1] . ";\n\n";
            
            // Data
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cols = array_map(function($c) { return "`$c`"; }, array_keys($row));
                
                // CRITICAL FIX: Handle NULLs correctly
                $vals = array_map(function($v) use ($pdo) { 
                    return is_null($v) ? "NULL" : $pdo->quote($v); 
                }, array_values($row));
                
                $sql_dump .= "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
            }
            $sql_dump .= "\n\n";
        }

        // 3. Force Download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="db_backup_'.date('Y-m-d').'.sql"');
        header('Content-Length: ' . strlen($sql_dump));
        echo $sql_dump; 
        exit;
    }

    // C. BACKUP ZIP (Redirect Method - Safe)
    if ($act == 'backup_app') {
        $folder = 'backups';
        if (!is_dir($folder)) mkdir($folder, 0777, true);
        
        $zipName = 'full_backup_' . date('Y-m-d_H-i') . '.zip';
        $savePath = $folder . '/' . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($savePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $rootPath = realpath('./');
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath) + 1);
                    if (strpos($relativePath, 'backups/') === 0 || substr($relativePath, -4) === '.zip') continue;
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
            
            // Redirect to file to avoid buffer corruption
            header("Location: $savePath");
            exit;
        } else { die("Error: Could not save Zip to '$folder'. Check permissions."); }
    }
}

// 10. RECURRING TEMPLATES
if (isset($_POST['save_template'])) {
    $desc = $_POST['desc']; $day = intval($_POST['day']);
    $acc_ids = $_POST['acc_id']; $debits = $_POST['dr_amount']; $credits = $_POST['cr_amount'];
    $edit_id = $_POST['tpl_id'] ?? null;

    $sum_dr = 0; $sum_cr = 0;
    for ($i = 0; $i < count($acc_ids); $i++) { $sum_dr += floatval($debits[$i]); $sum_cr += floatval($credits[$i]); }
    if (abs($sum_dr - $sum_cr) > 0.01) { echo "<script>alert('❌ Unbalanced!'); window.history.back();</script>"; exit; }

    if ($edit_id) {
        $pdo->prepare("UPDATE recurring_templates SET description=?, day_of_month=? WHERE id=?")->execute([$desc, $day, $edit_id]);
        $tpl_id = $edit_id;
        $pdo->prepare("DELETE FROM recurring_lines WHERE template_id=?")->execute([$tpl_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO recurring_templates (description, day_of_month) VALUES (?,?)");
        $stmt->execute([$desc, $day]);
        $tpl_id = $pdo->lastInsertId();
    }
    $stmt_line = $pdo->prepare("INSERT INTO recurring_lines (template_id, account_id, type, amount) VALUES (?,?,?,?)");
    for ($i = 0; $i < count($acc_ids); $i++) {
        $aid = $acc_ids[$i]; $dr = floatval($debits[$i]); $cr = floatval($credits[$i]);
        if (empty($aid) || ($dr == 0 && $cr == 0)) continue;
        if ($dr > 0) $stmt_line->execute([$tpl_id, $aid, 'DEBIT', $dr]);
        if ($cr > 0) $stmt_line->execute([$tpl_id, $aid, 'CREDIT', $cr]);
    }
    header("Location: index.php?page=recurring"); exit;
}
if (isset($_GET['del_template'])) {
    $pdo->prepare("DELETE FROM recurring_lines WHERE template_id=?")->execute([$_GET['del_template']]);
    $pdo->prepare("DELETE FROM recurring_templates WHERE id=?")->execute([$_GET['del_template']]);
    header("Location: index.php?page=recurring"); exit;
}
if (isset($_POST['process_recurring']) && !empty($_POST['tpl_ids'])) {
    $date = $_POST['target_date'];
    foreach ($_POST['tpl_ids'] as $tid) {
        $t_head = $pdo->prepare("SELECT description FROM recurring_templates WHERE id=?");
        $t_head->execute([$tid]);
        $desc = $t_head->fetchColumn();
        $t_lines = $pdo->prepare("SELECT * FROM recurring_lines WHERE template_id=?");
        $t_lines->execute([$tid]);
        $rows = $t_lines->fetchAll();

        $pfx = date('Ym', strtotime($date)) . '-';
        $lst = $pdo->prepare("SELECT entry_group_id FROM journal WHERE entry_group_id LIKE ? ORDER BY length(entry_group_id) DESC, entry_group_id DESC LIMIT 1");
        $lst->execute([$pfx.'%']);
        $lid = $lst->fetchColumn();
        $num = $lid ? intval(substr($lid, strlen($pfx))) + 1 : 1;
        $gid = $pfx . str_pad($num, 3, '0', STR_PAD_LEFT);

        $ins = $pdo->prepare("INSERT INTO journal (entry_group_id, trans_date, description, account_id, debit, credit) VALUES (?,?,?,?,?,?)");
        foreach ($rows as $r) {
            $dr = ($r['type'] == 'DEBIT') ? $r['amount'] : 0;
            $cr = ($r['type'] == 'CREDIT') ? $r['amount'] : 0;
            $ins->execute([$gid, $date, $desc, $r['account_id'], $dr, $cr]);
        }
    }
    header("Location: index.php"); exit;
}

// 11. PREPARE VIEW DATA
if (!isset($_SESSION['loggedin'])) { require 'views/login.php'; exit; }

$cur_m = date('n'); $cur_y = date('Y');
$fy_start = ($cur_m > 3) ? "$cur_y-04-01" : ($cur_y-1)."-04-01";
$start_date = $_GET['start'] ?? $fy_start;
$end_date   = $_GET['end']   ?? date('Y-m-d');

// Balances
$sql_accounts = "SELECT a.id, a.name, a.type, SUM(j.debit) as total_debit, SUM(j.credit) as total_credit 
                 FROM accounts a LEFT JOIN journal j ON a.id = j.account_id 
                 GROUP BY a.id, a.name, a.type ORDER BY a.name ASC";
$all_acc = $pdo->query($sql_accounts)->fetchAll();

$acc_list = []; $totals = ['Asset'=>0, 'Liability'=>0, 'Equity'=>0, 'Income'=>0, 'Expense'=>0];
$liquids = []; $total_liq = 0; $liq_keywords = ['HDFC','Maharashtra','SBI','SVC','UPI','Fastag','Cash'];

foreach($all_acc as $a) {
    if (in_array($a['type'], ['Asset', 'Expense'])) { $bal = $a['total_debit'] - $a['total_credit']; } 
    else { $bal = $a['total_credit'] - $a['total_debit']; }
    $totals[$a['type']] += $bal;
    $acc_list[] = ['id' => $a['id'], 'name' => $a['name'], 'type' => $a['type'], 'bal' => $bal];
    
    if ($a['type'] == 'Asset') {
        foreach ($liq_keywords as $k) {
            if (stripos($a['name'], $k) !== false) {
                $liquids[] = ['id'=>$a['id'], 'name'=>$a['name'], 'bal'=>$bal];
                $total_liq += $bal; break;
            }
        }
    }
}
$net_profit = $totals['Income'] - $totals['Expense'];

// Period Stats
$stmt_p = $pdo->prepare("SELECT a.type, SUM(j.credit - j.debit) as net_val FROM journal j JOIN accounts a ON j.account_id=a.id WHERE j.trans_date BETWEEN ? AND ? AND a.type IN ('Income','Expense') GROUP BY a.type");
$stmt_p->execute([$start_date, $end_date]);
$p_rows = $stmt_p->fetchAll(PDO::FETCH_KEY_PAIR);
$period_inc = $p_rows['Income'] ?? 0;
$period_exp = abs($p_rows['Expense'] ?? 0); 
$period_profit = $period_inc - $period_exp;

// Monthly Stats
$stmt_m = $pdo->prepare("SELECT DATE_FORMAT(j.trans_date, '%Y-%m') as mth, a.type, SUM(j.debit) as dr, SUM(j.credit) as cr FROM journal j JOIN accounts a ON j.account_id = a.id WHERE a.type IN ('Income', 'Expense') AND j.trans_date BETWEEN ? AND ? GROUP BY DATE_FORMAT(j.trans_date, '%Y-%m'), a.type ORDER BY mth DESC");
$stmt_m->execute([$start_date, $end_date]);
$monthly = [];
foreach($stmt_m->fetchAll() as $r) {
    $m = $r['mth'];
    if(!isset($monthly[$m])) $monthly[$m] = ['Income'=>0, 'Expense'=>0];
    $val = ($r['type']=='Income') ? ($r['cr']-$r['dr']) : ($r['dr']-$r['cr']);
    $monthly[$m][$r['type']] += $val;
}

// 12. ROUTING
$page = $_GET['page'] ?? 'dashboard';
require 'views/header.php';

if ($page == 'accounts') require 'views/accounts.php';
elseif ($page == 'ledger') require 'views/ledger.php';
elseif ($page == 'cashflow') require 'views/cashflow.php';
elseif ($page == 'balance_sheet') require 'views/balance_sheet.php'; // <--- ADD THIS LINE
elseif ($page == 'profit_loss') require 'views/profit_loss.php'; // <--- ADD THIS
elseif ($page == 'month_detail') require 'views/month_detail.php';
elseif ($page == 'usage') require 'views/usage.php';
elseif ($page == 'recurring') {
    $edit_tpl = null;
    if (isset($_GET['edit_template'])) {
        $stmt = $pdo->prepare("SELECT * FROM recurring_templates WHERE id=?");
        $stmt->execute([$_GET['edit_template']]);
        $head = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($head) {
            $l_stmt = $pdo->prepare("SELECT * FROM recurring_lines WHERE template_id=?");
            $l_stmt->execute([$head['id']]);
            $lines = $l_stmt->fetchAll(PDO::FETCH_ASSOC);
            $edit_tpl = ['head' => $head, 'lines' => $lines];
        }
    }
    require 'views/recurring.php';
}
else {
    $edit_rows = []; $edit_trans_data = null;
    if (isset($_GET['clone']) || isset($_GET['edit'])) {
        $gid = $_GET['clone'] ?? $_GET['edit'];
        $is_edit = isset($_GET['edit']);
        $stmt = $pdo->prepare("SELECT * FROM journal WHERE entry_group_id=?");
        $stmt->execute([$gid]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $edit_trans_data = ['date' => $is_edit?$rows[0]['trans_date']:date('Y-m-d'), 'desc' => $rows[0]['description'], 'group_id' => $is_edit?$gid:''];
            foreach($rows as $r) { $edit_rows[] = ['account_id'=>$r['account_id'], 'debit'=>$r['debit'], 'credit'=>$r['credit']]; }
        }
    }
    require 'views/dashboard.php';
}
require 'views/footer.php';
?>