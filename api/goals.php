<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_goal') {
        $title = trim($_POST['title']);
        $desc = trim($_POST['description']);
        $target = floatval($_POST['target_amount']);
        $current = floatval($_POST['current_amount'] ?? 0);
        $date = $_POST['target_date'];
        $priority = $_POST['priority'];
        $stmt = $conn->prepare("INSERT INTO goals (user_id, title, description, target_amount, current_amount, target_date, priority) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issddss", $uid, $title, $desc, $target, $current, $date, $priority);
        $stmt->execute();
        header('Location: goals.php?added=1');
        exit;
    }
    if ($action === 'add_funds') {
        $goal_id = (int)$_POST['goal_id'];
        $amount = floatval($_POST['amount']);
        $conn->query("UPDATE goals SET current_amount = current_amount + $amount WHERE id=$goal_id AND user_id=$uid");
        header('Location: goals.php?funded=1');
        exit;
    }
}

$goals = $conn->query("SELECT * FROM goals WHERE user_id=$uid ORDER BY FIELD(priority,'high','medium','low'), created_at DESC")->fetch_all(MYSQLI_ASSOC);
$achieved = array_filter($goals, fn($g) => $g['status'] === 'achieved');
$active_goals = array_filter($goals, fn($g) => $g['status'] === 'active');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>Life Goals – GanjiSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<div class="app-layout">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-content">

    <div class="flex-between mb-8">
        <div>
            <h1 class="greeting">Life Goals</h1>
            <p class="greeting-sub">Visions becoming reality through discipline.</p>
        </div>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('add-goal-modal').classList.remove('hidden')">➕ Add Vision</button>
    </div>

    <!-- Summary -->
    <div class="card mb-8" style="background:var(--grad-hero);border:1px solid rgba(123,111,238,0.15);">
        <div class="grid-3">
            <div class="text-center">
                <div class="wealth-number" style="font-size:44px;"><?= count($active_goals) ?></div>
                <div class="wealth-label">Active</div>
            </div>
            <div class="text-center">
                <div class="wealth-number c-mint" style="font-size:44px;"><?= count($achieved) ?></div>
                <div class="wealth-label">Achieved</div>
            </div>
            <div class="text-center">
                <?php
                $total_target = array_sum(array_column(array_values($active_goals), 'target_amount'));
                $total_funded = array_sum(array_column(array_values($active_goals), 'current_amount'));
                $overall_pct = $total_target > 0 ? round(($total_funded/$total_target)*100,1) : 0;
                ?>
                <div class="wealth-number c-gold" style="font-size:44px;"><?= $overall_pct ?>%</div>
                <div class="wealth-label">Momentum</div>
            </div>
        </div>
    </div>

    <!-- Goals Grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;">
        <?php foreach ($active_goals as $goal):
            $pct = $goal['target_amount'] > 0 ? min(100, ($goal['current_amount']/$goal['target_amount'])*100) : 0;
            $remaining = $goal['target_amount'] - $goal['current_amount'];
            $priority_cfg = [
                'high' => ['badge-rose', '🔥 High'],
                'medium' => ['badge-mint', '🎯 Medium'],
                'low' => ['badge-sky', '📌 Low'],
            ];
            [$bc, $blabel] = $priority_cfg[$goal['priority']];
        ?>
        <div class="card" style="padding:28px;position:relative;overflow:hidden;">
            <div class="flex-between mb-4">
                <div class="section-title"><?= htmlspecialchars($goal['title']) ?></div>
                <span class="badge <?= $bc ?>"><?= $blabel ?></span>
            </div>
            
            <div class="flex-between mb-3">
                <div class="wealth-number" style="font-size:28px;"><?= round($pct,0) ?>%</div>
                <div class="text-right">
                    <div class="text-sm font-bold">$<?= number_format($goal['current_amount'],0) ?></div>
                    <div class="text-xs text-muted">of $<?= number_format($goal['target_amount'],0) ?></div>
                </div>
            </div>
            
            <div class="progress-track mb-4"><div class="progress-fill fill-violet" style="width:<?= $pct ?>%;"></div></div>

            <div class="flex-between mb-4 p-3" style="background:rgba(255,255,255,0.03);border-radius:12px;">
                <div>
                    <div class="text-xs text-muted">Need</div>
                    <div class="text-sm font-bold c-rose">$<?= number_format($remaining,0) ?></div>
                </div>
                <div>
                    <div class="text-xs text-muted">Target Date</div>
                    <div class="text-sm font-bold"><?= $goal['target_date'] ? date('M Y', strtotime($goal['target_date'])) : 'Infinity' ?></div>
                </div>
            </div>

            <form method="POST" class="flex gap-2">
                <input type="hidden" name="action" value="add_funds">
                <input type="hidden" name="goal_id" value="<?= $goal['id'] ?>">
                <input type="number" name="amount" class="form-input" placeholder="Add funds..." step="0.01" style="padding:10px;" required>
                <button type="submit" class="btn btn-primary btn-sm">Fund</button>
            </form>
        </div>
        <?php endforeach; ?>

        <?php if (empty($active_goals)): ?>
        <div style="grid-column:1/-1;text-align:center;padding:80px 40px;" class="card">
            <div style="font-size:64px;margin-bottom:20px;">🎯</div>
            <h2 class="section-title mb-2">No active visions.</h2>
            <p class="text-muted mb-6">Chief, a partner without a goal is just wandering. What are we building?</p>
            <button class="btn btn-primary" onclick="document.getElementById('add-goal-modal').classList.remove('hidden')">Create First Goal</button>
        </div>
        <?php endif; ?>
    </div>

</div>
</main>
</div>

<!-- Modal -->
<div id="add-goal-modal" class="modal-overlay hidden" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">New Vision</div>
            <button class="modal-close" onclick="document.getElementById('add-goal-modal').classList.add('hidden')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_goal">
            <div class="form-group">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-input" placeholder="e.g. Dream House, New Car, Empire Fund" required>
            </div>
            <div class="input-row">
                <div class="form-group">
                    <label class="form-label">Target ($)</label>
                    <input type="number" name="target_amount" class="form-input" placeholder="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Starting with ($)</label>
                    <input type="number" name="current_amount" class="form-input" placeholder="0" step="0.01">
                </div>
            </div>
            <div class="input-row">
                <div class="form-group">
                    <label class="form-label">By When</label>
                    <input type="date" name="target_date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="high">High 🔥</option>
                        <option value="medium" selected>Medium 🎯</option>
                        <option value="low">Low 📌</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-xl">Create Goal</button>
        </form>
    </div>
</div>

</body>
</html>
