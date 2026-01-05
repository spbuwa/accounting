<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>⏱️ App Usage History</h2>
        <a href="index.php" class="btn-blue">Back to Dashboard</a>
    </div>

    <?php
        // Calculate Total Time for TODAY
        $today = date('Y-m-d');
        $sum = $pdo->query("SELECT SUM(duration_sec) FROM usage_log WHERE DATE(login_time) = '$today'")->fetchColumn();
        
        // FIX: Ensure sum is treated as integer (default to 0 if null)
        $sum = (int)$sum; 

        // FIX: Use explicit integer math
        $hrs = floor($sum / 3600);
        $mins = floor(($sum % 3600) / 60);
    ?>
    <div style="background:#2c3e50; color:white; padding:20px; border-radius:8px; margin-bottom:20px; text-align:center;">
        <span style="display:block; font-size:0.9em; opacity:0.8;">TIME SPENT TODAY</span>
        <span style="font-size:2em; font-weight:bold;"><?= $hrs ?>h <?= $mins ?>m</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Login Time</th>
                <th>Logout Time</th>
                <th>Duration</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $logs = $pdo->query("SELECT * FROM usage_log ORDER BY id DESC LIMIT 50")->fetchAll();
            foreach($logs as $log):
                $dur = (int)$log['duration_sec']; // Force Integer
                
                // FIX: Cleaner Integer Math avoids Deprecation warnings
                $h = floor($dur / 3600);
                $m = floor(($dur % 3600) / 60);
                $s = $dur % 60;
                
                $duration_fmt = sprintf('%02d:%02d:%02d', $h, $m, $s);
                
                // Styling for active session
                $logout_display = $log['logout_time'] ? date('h:i A', strtotime($log['logout_time'])) : '<span style="color:green; font-weight:bold;">Active Now...</span>';
                $dur_display = $log['logout_time'] ? $duration_fmt : '-';
            ?>
            <tr>
                <td style="font-weight:bold; color:#555;"><?= date('d M Y', strtotime($log['login_time'])) ?></td>
                <td><?= date('h:i A', strtotime($log['login_time'])) ?></td>
                <td><?= $logout_display ?></td>
                <td style="font-family:monospace; font-weight:bold; color:#004085;"><?= $dur_display ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>