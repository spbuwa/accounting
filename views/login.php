<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 350px; text-align: center; }
        h2 { margin-top: 0; color: #333; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #28a745; color: white; border: none; font-weight: bold; cursor: pointer; border-radius: 4px; font-size: 1rem; }
        button:hover { background: #218838; }
        .error { color: red; margin-bottom: 10px; font-size: 0.9rem; }
        .note { margin-top: 20px; font-size: 0.8rem; color: #666; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>🔒 Secured Access</h2>
        <?php if(!empty($login_err)): ?><div class="error"><?= $login_err ?></div><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="do_login">Login</button>
        </form>
        <div class="note">Default: admin / 1234</div>
    </div>
</body>
</html>