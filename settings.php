<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
$alloc = $conn->query("SELECT * FROM capital_allocations WHERE user_id = $uid")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $name = trim($_POST['full_name']);
        $persona = $_POST['persona'];
        $stmt = $conn->prepare("UPDATE users SET full_name=?, persona=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $persona, $uid);
        $stmt->execute();
        $_SESSION['full_name'] = $name;
        header('Location: settings.php?saved=1');
        exit;
    }
    if ($action === 'change_password') {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        if (password_verify($current, $user['password_hash'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $stmt->bind_param("si", $hash, $uid);
            $stmt->execute();
            header('Location: settings.php?pw_changed=1');
        } else {
            header('Location: settings.php?pw_error=1');
        }
        exit;
    }
}

$trade_count = $conn->query("SELECT COUNT(*) as c FROM trade_slots WHERE user_id=$uid AND status='active'")->fetch_assoc()['c'];
$total_trades = $conn->query("SELECT COUNT(*) as c FROM trade_slots WHERE user_id=$uid AND status='closed'")->fetch_assoc()['c'];
$total_pnl = $conn->query("SELECT COALESCE(SUM(pnl),0) as t FROM trade_slots WHERE user_id=$uid AND status='closed'")->fetch_assoc()['t'];
$goal_count = $conn->query("SELECT COUNT(*) as c FROM goals WHERE user_id=$uid AND status='active'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings – GanjiSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<div class="app-layout">
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
    <div class="market-ticker"><div class="ticker-inner" id="ticker-inner"></div></div>
    <div class="topbar">
        <div class="topbar-left">
            <h1>⚙️ Settings</h1>
            <p>Control center for your GanjiSmart instance, Chief.</p>
        </div>
        <div class="topbar-right">
            <a href="logout.php" class="topbar-btn">🚪</a>
        </div>
    </div>

    <div class="page-content">
        <?php if (isset($_GET['saved'])): ?>
        <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:12px;padding:14px 18px;margin-bottom:20px;color:var(--green);">✅ Profile updated. Sawa sawa, Chief.</div>
        <?php endif; ?>
        <?php if (isset($_GET['pw_changed'])): ?>
        <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:12px;padding:14px 18px;margin-bottom:20px;color:var(--green);">✅ Password changed. Lock it down, Chief.</div>
        <?php endif; ?>
        <?php if (isset($_GET['pw_error'])): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:12px;padding:14px 18px;margin-bottom:20px;color:var(--red);">⚠️ Incorrect current password.</div>
        <?php endif; ?>

        <!-- Account Stats -->
        <div class="stat-grid mb-6">
            <div class="stat-card purple">
                <div class="stat-icon purple">📊</div>
                <div class="stat-label">Active Trades</div>
                <div class="stat-value"><?= $trade_count ?></div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon green">📈</div>
                <div class="stat-label">Closed Trades</div>
                <div class="stat-value"><?= $total_trades ?></div>
            </div>
            <div class="stat-card <?= $total_pnl >= 0 ? 'green' : 'red' ?>">
                <div class="stat-icon <?= $total_pnl >= 0 ? 'green' : 'red' ?>">💰</div>
                <div class="stat-label">Total PnL</div>
                <div class="stat-value"><?= $total_pnl >= 0 ? '+' : '' ?>$<?= number_format(abs($total_pnl),0) ?></div>
            </div>
            <div class="stat-card amber">
                <div class="stat-icon amber">🎯</div>
                <div class="stat-label">Active Goals</div>
                <div class="stat-value"><?= $goal_count ?></div>
            </div>
        </div>

        <div class="grid-2 mb-6">
            <!-- Profile Settings -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">👤 Profile Settings</div>
                </div>
                <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;">
                    <div style="width:72px;height:72px;background:linear-gradient(135deg,#6c63ff,#ec4899);border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:32px;box-shadow:0 0 20px rgba(108,99,255,0.3);">
                        <?= mb_strtoupper(mb_substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-size:20px;font-weight:800;color:#fff;"><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></div>
                        <div style="font-size:13px;color:var(--text-muted);">@<?= htmlspecialchars($user['username']) ?></div>
                        <div style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-input" value="<?= htmlspecialchars($user['full_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">AI Persona</label>
                        <select name="persona" class="form-select">
                            <option value="gen_z_bro" <?= $user['persona']==='gen_z_bro'?'selected':'' ?>>🤙 Gen Z Bro (Kenyan AF)</option>
                            <option value="gen_z_girlie" <?= $user['persona']==='gen_z_girlie'?'selected':'' ?>>💅 Gen Z Girlie</option>
                            <option value="sharp_millennial" <?= $user['persona']==='sharp_millennial'?'selected':'' ?>>📊 Sharp Millennial</option>
                            <option value="vintage" <?= $user['persona']==='vintage'?'selected':'' ?>>🎩 Vintage Investor</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">💾 Save Profile</button>
                </form>
            </div>

            <!-- Security -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">🔒 Security</div>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-input" placeholder="Current password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-input" placeholder="New strong password" required>
                    </div>
                    <button type="submit" class="btn btn-ghost w-full">🔑 Change Password</button>
                </form>

                <div style="margin-top:24px;border-top:1px solid var(--border);padding-top:20px;">
                    <div class="card-title" style="font-size:14px;margin-bottom:12px;">📚 Philosophy Core</div>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <?php
                        $books = [
                            ['🏛️','The Richest Man in Babylon','Save 10%, invest wisely, compound.'],
                            ['🧠','The Psychology of Money','Behavior > Intelligence in investing.'],
                            ['💡','I Will Teach You to Be Rich','Automate, optimize, execute.'],
                            ['📋','One-Page Financial Plan','Goals first, money second.'],
                            ['⚠️','Howard Marks','Risk first. Margin of safety always.'],
                            ['⚡','Jared Dillian','Hunt outliers. Ride momentum.'],
                        ];
                        foreach ($books as [$icon, $title, $desc]):
                        ?>
                        <div style="display:flex;gap:10px;padding:10px;background:rgba(255,255,255,0.03);border-radius:8px;">
                            <span style="font-size:18px;"><?= $icon ?></span>
                            <div>
                                <div style="font-size:12px;font-weight:700;color:#fff;"><?= $title ?></div>
                                <div style="font-size:11px;color:var(--text-muted);"><?= $desc ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Behavioral Protection System -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">🛡️ Behavioral Protection System</div>
                <span class="badge badge-green">ACTIVE</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
                <?php
                $protections = [
                    ['🎯','Fixed Trade Slots','13 max positions enforced. Cannot overtrade.','green'],
                    ['💰','Capital Allocation','Funds split before discretionary spending.','purple'],
                    ['🚪','Disciplined Exits','Exit signals remove emotional decision-making.','amber'],
                    ['😤','FOMO Prevention','No urgency language in any notification.','cyan'],
                    ['🔄','Capital Recycling','Exited capital returned to pool automatically.','green'],
                    ['📊','No Probabilities','AI only says when to enter, exit, and how much.','purple'],
                    ['⏱️','Max Hold Period','~1 month maximum to avoid bag-holding.','amber'],
                    ['⚡','Signal Thresholds','Minimum 4/7 signals needed before recommending.','blue'],
                ];
                foreach ($protections as [$icon, $title, $desc, $color]):
                ?>
                <div style="background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:10px;padding:14px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                        <span style="font-size:20px;"><?= $icon ?></span>
                        <div class="signal-dot active"></div>
                    </div>
                    <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:4px;"><?= $title ?></div>
                    <div style="font-size:11px;color:var(--text-muted);"><?= $desc ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</div>

<div id="notif-stack" style="position:fixed;top:80px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;max-width:400px;"></div>
<script src="assets/js/app.js"></script>
</body>
</html>
