<?php

require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if ($username && $password) {
            $stmt = $conn->prepare("SELECT id, username, full_name, password_hash FROM users WHERE username = ? OR email = ?");
            if (!$stmt) {
                $error = "System error: The 'users' table is missing. Chief, run /setup_db again.";
            } else {
                $stmt->bind_param("ss", $username, $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    header('Location: /dashboard');
                    exit;
                } else {
                    $error = "Bro, those credentials don't match our records.";
                }
            }
        } else {
            $error = "Mkuruu, fill in all fields first.";
        }
    } elseif ($action === 'register') {
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['reg_username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $capital = floatval($_POST['capital'] ?? 0);
        $income = floatval($_POST['income'] ?? 0);
        
        if ($full_name && $username && $email && $password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password_hash, total_capital, available_capital, monthly_income) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                $error = "Registration failed: " . $conn->error;
            } else {
                $avail = $capital * 0.4;
                $stmt->bind_param("ssssddd", $full_name, $username, $email, $hash, $capital, $avail, $income);
                
                if ($stmt->execute()) {
                    $new_id = $conn->insert_id;
                    
                    // Setup other tables for new user
                    $conn->query("INSERT INTO capital_allocations (user_id) VALUES ($new_id)");
                    for ($i = 1; $i <= 13; $i++) {
                        $layer = $i <= 11 ? 'L1' : 'L2';
                        $conn->query("INSERT INTO trade_slots (user_id, slot_number, layer, status) VALUES ($new_id, $i, '$layer', 'empty')");
                    }
                    
                    $success = "Sawa chief! Account created. Sign in to start.";
                } else {
                    $error = "Registration failed: " . $stmt->error;
                }
            }
        } else {
            $error = "Fill all required fields, bazu.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GanjiSmart – AI Financial Partner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        body { background: #050814; display: flex; align-items: center; justify-content: center; min-height: 100vh; overflow: hidden; margin:0; }
        .login-bg { position: fixed; inset: 0; z-index: 0; overflow: hidden; }
        .orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.18; animation: orbFloat 8s ease-in-out infinite; }
        .orb-1 { width: 600px; height: 600px; background: radial-gradient(circle, #6c63ff, #3b82f6); top: -200px; left: -200px; }
        .orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, #10b981, #06d6a0); bottom: -100px; right: -100px; animation-delay: 3s; }
        @keyframes orbFloat { 0%,100% { transform: scale(1) translate(0,0); } 50% { transform: scale(1.1) translate(20px, -20px); } }
        .grid-bg { position: absolute; inset: 0; background-image: linear-gradient(rgba(108,99,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(108,99,255,0.05) 1px, transparent 1px); background-size: 50px 50px; }
        .login-container { position: relative; z-index: 10; width: 100%; max-width: 440px; padding: 20px; }
        .login-card { background: rgba(255,255,255,0.04); backdrop-filter: blur(30px); border: 1px solid rgba(255,255,255,0.08); border-radius: 28px; padding: 40px; box-shadow: 0 40px 80px rgba(0,0,0,0.5); }
        .brand { text-align: center; margin-bottom: 30px; }
        .brand-logo { width: 64px; height: 64px; margin: 0 auto 12px; display: block; border-radius: 18px; object-fit: cover; }
        .brand-name { font-family: 'Space Grotesk', sans-serif; font-size: 26px; font-weight: 700; color: #fff; letter-spacing: -0.5px; }
        .brand-tagline { color: rgba(255,255,255,0.4); font-size: 13px; margin-top: 4px; }
        .tabs { display: flex; background: rgba(255,255,255,0.05); border-radius: 12px; padding: 4px; margin-bottom: 24px; gap: 4px; }
        .tab-btn { flex: 1; padding: 10px; border: none; background: transparent; color: rgba(255,255,255,0.5); border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .tab-btn.active { background: rgba(123,111,238,0.22); color: #fff; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
        .form-input { width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 13px 16px; color: #fff; font-size: 15px; font-family: 'Inter', sans-serif; transition: all 0.3s ease; box-sizing: border-box; }
        .form-input:focus { outline: none; border-color: rgba(123,111,238,0.4); background: rgba(123,111,238,0.06); }
        .btn-primary { width: 100%; padding: 16px; background: linear-gradient(135deg, #7b6fee, #5b4fd4); border: none; border-radius: 14px; color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s; margin-top: 8px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(123,111,238,0.3); }
        .alert { padding: 12px 16px; border-radius: 12px; font-size: 14px; margin-bottom: 20px; text-align: center; }
        .alert-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.2); color: #fca5a5; }
        .alert-success { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.2); color: #6ee7b7; }
        .demo-hint { text-align: center; margin-top: 20px; color: rgba(255,255,255,0.3); font-size: 13px; }
        .input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    </style>
</head>
<body>
<div class="login-bg">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="grid-bg"></div>
</div>

<div class="login-container">
    <div class="login-card">
        <div class="brand">
            <img src="/assets/icons/icon-192.png" alt="GanjiSmart" class="brand-logo">
            <div class="brand-name">GanjiSmart</div>
            <div class="brand-tagline">Premium AI Financial Partner</div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" id="tab-login" onclick="switchTab('login')">Sign In</button>
            <button class="tab-btn" id="tab-register" onclick="switchTab('register')">Register</button>
        </div>

        <form method="POST" id="login-form">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-input" placeholder="demo" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="password" required>
            </div>
            <button type="submit" class="btn-primary">Enter the Brain</button>
            <div class="demo-hint">Demo: <b>demo</b> / <b>password</b></div>
        </form>

        <form method="POST" id="register-form" style="display:none;">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-input" placeholder="Chief Bazu" required>
            </div>
            <div class="input-row">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="reg_username" class="form-input" placeholder="@handle" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" placeholder="you@email.com" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="reg_password" class="form-input" placeholder="••••••••" required>
            </div>
            <div class="input-row">
                <div class="form-group">
                    <label class="form-label">Capital ($)</label>
                    <input type="number" name="capital" class="form-input" placeholder="1000" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Salary ($)</label>
                    <input type="number" name="income" class="form-input" placeholder="5000" required>
                </div>
            </div>
            <button type="submit" class="btn-primary">Register Account</button>
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    document.getElementById('login-form').style.display = tab === 'login' ? 'block' : 'none';
    document.getElementById('register-form').style.display = tab === 'register' ? 'block' : 'none';
    document.getElementById('tab-login').classList.toggle('active', tab === 'login');
    document.getElementById('tab-register').classList.toggle('active', tab === 'register');
}
</script>
</body>
</html>
