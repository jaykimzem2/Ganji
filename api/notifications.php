<?php

require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: /'); exit; }
$uid = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

// Mark all as read
$conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");

// Fetch with pagination
$page = (int)($_GET['page'] ?? 0);
$limit = 20;
$offset = $page * $limit;
$notifs = $conn->query("SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC LIMIT $limit OFFSET $offset")->fetch_all(MYSQLI_ASSOC);
$total = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$uid")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications – GanjiSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<div class="app-layout">
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
    <div class="market-ticker"><div class="ticker-inner" id="ticker-inner"></div></div>
    <div class="topbar">
        <div class="topbar-left">
            <h1>🔔 Notifications</h1>
            <p><?= $total ?> total notifications on file</p>
        </div>
        <div class="topbar-right">
            <a href="logout.php" class="topbar-btn">🚪</a>
        </div>
    </div>

    <div class="page-content">
        <?php if (empty($notifs)): ?>
        <div style="text-align:center;padding:80px;color:var(--text-muted);">
            <div style="font-size:60px;margin-bottom:16px;">🔔</div>
            <p style="font-size:18px;color:var(--text-secondary);">No notifications yet, Chief.</p>
            <p style="margin-top:8px;">GanjiSmart will alert you when opportunities appear.</p>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px;max-width:700px;">
            <?php foreach ($notifs as $n):
                $type_cfg = [
                    'entry' => ['🟢', 'var(--green)', 'rgba(16,185,129,0.08)', 'rgba(16,185,129,0.2)'],
                    'exit' => ['🚨', 'var(--amber)', 'rgba(245,158,11,0.08)', 'rgba(245,158,11,0.2)'],
                    'overspend' => ['⚠️', 'var(--red)', 'rgba(239,68,68,0.08)', 'rgba(239,68,68,0.2)'],
                    'motivation' => ['💎', '#a78bfa', 'rgba(108,99,255,0.08)', 'rgba(108,99,255,0.2)'],
                    'income' => ['💵', 'var(--green)', 'rgba(16,185,129,0.08)', 'rgba(16,185,129,0.2)'],
                    'compounding' => ['🌳', 'var(--amber)', 'rgba(245,158,11,0.08)', 'rgba(245,158,11,0.2)'],
                ];
                [$emoji, $color, $bg, $border] = $type_cfg[$n['type']] ?? ['📌', '#fff', 'rgba(255,255,255,0.04)', 'var(--border)'];
            ?>
            <div style="background:<?= $bg ?>;border:1px solid <?= $border ?>;border-radius:14px;padding:18px 20px;display:flex;gap:14px;align-items:flex-start;">
                <div style="font-size:28px;flex-shrink:0;"><?= $emoji ?></div>
                <div style="flex:1;">
                    <div style="font-size:10px;font-weight:700;color:<?= $color ?>;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;"><?= strtoupper($n['type']) ?></div>
                    <div style="font-size:15px;color:#fff;font-weight:500;line-height:1.5;"><?= htmlspecialchars($n['message']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:8px;"><?= date('M d, Y · H:i', strtotime($n['created_at'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div style="display:flex;gap:8px;margin-top:20px;">
            <?php if ($page > 0): ?>
            <a href="?page=<?= $page-1 ?>" class="btn btn-ghost">← Previous</a>
            <?php endif; ?>
            <?php if (($page+1)*$limit < $total): ?>
            <a href="?page=<?= $page+1 ?>" class="btn btn-ghost">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<div id="notif-stack" style="position:fixed;top:80px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;max-width:400px;"></div>
<script src="/assets/js/app.js"></script>
</body>
</html>
