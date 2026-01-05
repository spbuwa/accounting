<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Accounting App</title>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* --- 1. GLOBAL & LAYOUT --- */
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding-bottom: 50px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 15px; }
        
        /* --- 2. CARDS & GRID --- */
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .grid-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        @media (max-width: 768px) { .grid-row { grid-template-columns: 1fr; } }

        /* --- 3. TYPOGRAPHY & NUMBERS --- */
        h2 { margin-top: 0; color: #333; font-size: 1.2rem; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .num { text-align: right; font-family: 'Courier New', Courier, monospace; font-weight: bold; }
        .link { text-decoration: none; color: #007bff; font-weight: bold; }
        
        /* --- 4. TABLES --- */
        table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        th { text-align: left; padding: 10px; background: #f8f9fa; border-bottom: 2px solid #dee2e6; color: #495057; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 10px; border-bottom: 1px solid #dee2e6; vertical-align: middle; }
        tr:hover { background-color: #f8f9fa; }

        /* --- 5. FORMS --- */
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-col { display: flex; flex-direction: column; }
        .form-label { font-size: 0.85rem; font-weight: bold; color: #555; margin-bottom: 5px; }
        .form-input-lg { padding: 8px 10px; border: 1px solid #ced4da; border-radius: 4px; font-size: 1rem; }
        .search-box { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; margin-bottom: 10px; }

        /* --- 6. BUTTONS --- */
        button, .btn { cursor: pointer; border: none; border-radius: 4px; font-weight: 500; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-green { background: #28a745; color: white; padding: 10px 20px; font-size: 1rem; }
        .btn-green:hover { background: #218838; }
        .btn-blue { background: #007bff; color: white; padding: 8px 15px; font-size: 0.9rem; }
        .btn-blue:hover { background: #0056b3; }
        .btn-purple { background: #6f42c1; color: white; padding: 8px 15px; }
        .btn-logout { background: #dc3545; color: white; padding: 5px 10px; font-size: 0.8rem; margin-left: 10px; }
        
        /* Small Action Buttons */
        .btn-sm { padding: 2px 6px; font-size: 0.8rem; margin: 0 2px; }
        .btn-del { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .btn-clone { background: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; }
        .btn-edit { background: #fff3e0; color: #ef6c00; border: 1px solid #ffe0b2; }

        /* --- 7. NAVIGATION BAR --- */
        .navbar { background: #343a40; padding: 10px 0; color: white; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .nav-inner { max-width: 1200px; margin: 0 auto; padding: 0 15px; display: flex; justify-content: space-between; align-items: center; }
        .nav-logo { font-size: 1.2rem; font-weight: bold; color: white; text-decoration: none; margin-right: 20px; }
        .nav-links a { color: #ccc; text-decoration: none; margin-right: 15px; font-size: 0.9rem; transition: color 0.2s; }
        .nav-links a:hover { color: white; }
        .nav-links a.active { color: white; font-weight: bold; border-bottom: 2px solid #28a745; padding-bottom: 2px; }

        /* --- 8. DROPDOWN MENU (System Tools) --- */
        .dropdown { position: relative; display: inline-block; }
        .dropdown-btn { background: #495057; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; cursor: pointer; border: none; }
        .dropdown-content { display: none; position: absolute; right: 0; background-color: white; min-width: 200px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1000; border-radius: 4px; overflow: hidden; }
        .dropdown-content a { color: #333; padding: 10px 15px; text-decoration: none; display: block; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        .dropdown-content a:hover { background-color: #f1f1f1; color: #000; }
        .dropdown:hover .dropdown-content { display: block; }

        /* --- 9. STOPWATCH & TAGS --- */
        #sessionTimer { background: #222; color: #00ff00; font-family: 'Courier New', monospace; padding: 4px 8px; border-radius: 4px; font-weight: bold; border: 1px solid #555; font-size: 0.9rem; }
        .future-row { background-color: #fffbe6 !important; opacity: 0.8; }
        .future-tag { background: #ffc107; color: #333; font-size: 0.6em; padding: 1px 4px; border-radius: 3px; font-weight: bold; vertical-align: middle; margin-right: 5px; }

        /* --- FUTURE TRANSACTIONS --- */
.future-row { 
    background-color: #e3f2fd !important; /* Light Blue Background */
}

.future-row td {
    border-bottom: 1px solid #b8daff;     /* Slightly darker border for contrast */
}

.future-tag { 
    background-color: #17a2b8; 
    color: white; 
    padding: 2px 6px; 
    border-radius: 4px; 
    font-size: 0.75em; 
    font-weight: bold;
    margin-right: 5px;
    display: inline-block;
    vertical-align: middle;
}
    </style>
</head>
<body>

<div class="navbar">
    <div class="nav-inner">
        <div class="nav-links" style="display:flex; align-items:center;">
            <a href="index.php" class="nav-logo">📊 MyAccounts</a>
            <a href="index.php" class="<?= (!isset($_GET['page'])) ? 'active' : '' ?>">Dashboard</a>
            <a href="?page=accounts" class="<?= (isset($_GET['page']) && $_GET['page']=='accounts') ? 'active' : '' ?>">Accounts</a>
            <a href="?page=ledger" class="<?= (isset($_GET['page']) && $_GET['page']=='ledger') ? 'active' : '' ?>">Ledger</a>
            <a href="?page=cashflow" class="<?= (isset($_GET['page']) && $_GET['page']=='cashflow') ? 'active' : '' ?>">Cash Flow</a>
            <a href="?page=balance_sheet" class="<?= ($_GET['page']??'')=='balance_sheet'?'active':'' ?>">Balance Sheet</a>
            <a href="?page=profit_loss" class="<?= ($_GET['page']??'')=='profit_loss'?'active':'' ?>">P&L A/c</a>
            <a href="?page=recurring" class="<?= (isset($_GET['page']) && $_GET['page']=='recurring') ? 'active' : '' ?>">Recurring</a>
        </div>

        <div style="display:flex; align-items:center; gap:15px;">
            
            <div class="dropdown">
                <button class="dropdown-btn">💾 Data Tools ▼</button>
                <div class="dropdown-content">
                    <a href="index.php?sys_action=export_csv" target="_blank">📊 Export CSV</a>
                    <a href="index.php?sys_action=backup_sql" target="_blank">🗄️ Backup Database (SQL)</a>
                    <a href="index.php?sys_action=backup_app" target="_blank">📦 Backup App (Zip)</a>
                </div>
            </div>

            <div id="sessionTimer" title="Session Duration">00:00:00</div>
            
            <a href="?page=usage" class="btn-sm" style="background:#17a2b8; color:white; padding:5px 10px; text-decoration:none; border-radius:4px;">History</a>
            <a href="?logout=1" class="btn-logout">Logout</a>
        </div>
    </div>
</div>

<div class="container">

<script>
function startTimer() {
    // Get login timestamp from PHP Session (passed via logic in index.php)
    // Default to current time if not set to prevent errors
    var loginTime = <?= isset($_SESSION['login_timestamp']) ? $_SESSION['login_timestamp'] : time() ?>; 
    
    setInterval(function() {
        var now = Math.floor(Date.now() / 1000);
        var elapsed = now - loginTime;
        
        if(elapsed < 0) elapsed = 0; // Prevent negative on clock skew

        var h = Math.floor(elapsed / 3600);
        var m = Math.floor((elapsed % 3600) / 60);
        var s = elapsed % 60;
        
        // Pad with zeros
        h = h < 10 ? "0" + h : h;
        m = m < 10 ? "0" + m : m;
        s = s < 10 ? "0" + s : s;
        
        document.getElementById("sessionTimer").innerText = h + ":" + m + ":" + s;
    }, 1000);
}

// Start timer if user is logged in
<?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
    startTimer();
<?php endif; ?>

// Initialize Select2 globally for any input with class 'entry-select'
$(document).ready(function() {
    $('.entry-select').select2({ width: '100%', placeholder: "Select Account..." });
});
</script>