<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$uid = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
$alloc = $conn->query("SELECT * FROM capital_allocations WHERE user_id = $uid")->fetch_assoc();

// Active trades
$active_trades = $conn->query("SELECT * FROM trade_slots WHERE user_id = $uid AND status='active'")->fetch_all(MYSQLI_ASSOC);
$deployed = array_sum(array_column($active_trades, 'allocated_amount'));

// Monthly stats
$month = date('Y-m');
$mi = $conn->query("SELECT COALESCE(SUM(amount),0) t FROM transactions WHERE user_id=$uid AND type='income' AND DATE_FORMAT(transaction_date,'%Y-%m')='$month'")->fetch_assoc()['t'];
$ms = $conn->query("SELECT COALESCE(SUM(amount),0) t FROM transactions WHERE user_id=$uid AND type IN('expense','discretionary') AND DATE_FORMAT(transaction_date,'%Y-%m')='$month'")->fetch_assoc()['t'];
$msv = $conn->query("SELECT COALESCE(SUM(amount),0) t FROM transactions WHERE user_id=$uid AND type='savings' AND DATE_FORMAT(transaction_date,'%Y-%m')='$month'")->fetch_assoc()['t'];
$minv = $conn->query("SELECT COALESCE(SUM(amount),0) t FROM transactions WHERE user_id=$uid AND type='investment' AND DATE_FORMAT(transaction_date,'%Y-%m')='$month'")->fetch_assoc()['t'];
$savings_rate = $mi > 0 ? round(($msv / $mi) * 100, 1) : 0;

// Goals
$goals = $conn->query("SELECT * FROM goals WHERE user_id=$uid AND status='active' ORDER BY priority DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Recent transactions
$txns = $conn->query("SELECT * FROM transactions WHERE user_id=$uid ORDER BY created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

// Unread notifications
$notif_count = $conn->query("SELECT COUNT(*) c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc()['c'];

// Pending invest opportunities
$opp_count = $conn->query("SELECT COUNT(*) c FROM opportunities WHERE user_id=$uid AND status='pending'")->fetch_assoc()['c'];

// Greetings
$hour = (int)date('G');
$tod = $hour < 12 ? 'Morning' : ($hour < 17 ? 'Afternoon' : 'Evening');
$slangs = ['Chief', 'Bigman', 'Bazu', 'Mkuruu'];
$greet = $slangs[date('N') % 4]; // deterministic by day
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#07080e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Home – GanjiSmart</title>
    <link rel="manifest" href="manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<div class="app-layout">

<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<div class="page-content">

    <!-- ── HERO: Greeting + AI Insight ── -->
    <div class="card-hero mb-6">
        <div class="flex-between mb-4" style="position:relative;z-index:1;">
            <div>
                <div class="greeting">Good <?= $tod ?>, <?= $greet ?> 👋</div>
                <div class="greeting-sub"><?= date('l, F j, Y') ?> · Your money's being watched.</div>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <?php if ($notif_count > 0): ?>
                <a href="notifications.php" class="btn btn-ghost btn-sm" style="position:relative;">
                    🔔 <span style="background:var(--rose);color:#fff;font-size:10px;padding:1px 5px;border-radius:10px;"><?= $notif_count ?></span>
                </a>
                <?php endif; ?>
                <a href="settings.php" class="btn btn-ghost btn-sm">⚙️</a>
            </div>
        </div>

        <div class="ai-insight-box" style="position:relative;z-index:1;">
            <div class="ai-insight-label">
                <span class="ai-dot"></span> GanjiSmart AI
            </div>
            <div class="ai-insight-text" id="ai-insight">
                <span class="loading-text">Pulling your financial pulse...</span>
            </div>
        </div>
    </div>

    <!-- ── WEALTH OVERVIEW ── -->
    <div class="mb-6">
        <div class="section-head">
            <div class="section-title">💎 Your Wealth</div>
            <span class="badge badge-muted"><?= date('M Y') ?></span>
        </div>
        <div class="card" style="padding:28px 28px 24px;">
            <div>
                <div class="wealth-label">Total Capital</div>
                <div class="wealth-number">$<?= number_format($user['total_capital'], 0) ?></div>
            </div>
            <div class="wealth-row mt-4">
                <div class="wealth-pill">
                    <div class="wealth-pill-val c-mint">$<?= number_format($user['available_capital'], 0) ?></div>
                    <div class="wealth-pill-label">Available</div>
                </div>
                <div class="wealth-pill">
                    <div class="wealth-pill-val c-gold">$<?= number_format($deployed, 0) ?></div>
                    <div class="wealth-pill-label">Deployed</div>
                </div>
                <div class="wealth-pill">
                    <div class="wealth-pill-val c-violet"><?= count($active_trades) ?>/13</div>
                    <div class="wealth-pill-label">Slots Used</div>
                </div>
                <?php if ($savings_rate > 0): ?>
                <div class="wealth-pill">
                    <div class="wealth-pill-val" style="color:var(--gold-light);"><?= $savings_rate ?>%</div>
                    <div class="wealth-pill-label">Savings Rate</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── THIS MONTH ── -->
    <div class="mb-6">
        <div class="section-head">
            <div class="section-title">📅 This Month</div>
            <a href="finance.php" class="btn btn-ghost btn-sm">See all →</a>
        </div>
        <div class="month-row">
            <div class="month-stat">
                <div class="month-stat-val c-mint">$<?= number_format($mi, 0) ?></div>
                <div class="month-stat-label">💵 Income</div>
            </div>
            <div class="month-stat">
                <div class="month-stat-val c-rose">$<?= number_format($ms, 0) ?></div>
                <div class="month-stat-label">💸 Spent</div>
            </div>
            <div class="month-stat">
                <div class="month-stat-val" style="color:var(--sky);">$<?= number_format($msv, 0) ?></div>
                <div class="month-stat-label">🏦 Saved</div>
            </div>
            <div class="month-stat">
                <div class="month-stat-val c-violet">$<?= number_format($minv, 0) ?></div>
                <div class="month-stat-label">📈 Invested</div>
            </div>
        </div>
    </div>

    <!-- ── LIFE GOALS ── -->
    <?php if (!empty($goals)): ?>
    <div class="mb-6">
        <div class="section-head">
            <div class="section-title">🎯 Life Goals</div>
            <a href="goals.php" class="btn btn-ghost btn-sm">Manage →</a>
        </div>
        <div class="card">
            <?php
            $fill_classes = ['fill-violet','fill-gold','fill-mint','fill-violet','fill-gold'];
            $pct_colors = ['c-violet','c-gold','c-mint','c-violet','c-gold'];
            foreach ($goals as $i => $goal):
                $pct = $goal['target_amount'] > 0 ? min(100, ($goal['current_amount'] / $goal['target_amount']) * 100) : 0;
                $fc = $fill_classes[$i % count($fill_classes)];
                $pc = $pct_colors[$i % count($pct_colors)];
            ?>
            <div class="goal-item">
                <div class="goal-head">
                    <div>
                        <div class="goal-name"><?= htmlspecialchars($goal['title']) ?></div>
                        <div class="goal-amounts text-xs text-muted">$<?= number_format($goal['current_amount'],0) ?> of $<?= number_format($goal['target_amount'],0) ?></div>
                    </div>
                    <div class="goal-pct <?= $pc ?>"><?= round($pct,0) ?>%</div>
                </div>
                <div class="progress-track">
                    <div class="progress-fill <?= $fc ?>" style="width:<?= $pct ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── INVEST PROMPT (only if there are opportunities) ── -->
    <?php if ($opp_count > 0): ?>
    <div class="mb-6">
        <div class="card" style="background:rgba(15,207,172,0.06);border-color:rgba(15,207,172,0.2);padding:20px 24px;">
            <div class="flex-between">
                <div>
                    <div style="font-size:14px;font-weight:600;color:var(--mint);">📈 Ready to invest?</div>
                    <div style="font-size:13px;color:var(--text-3);margin-top:3px;">GanjiSmart has <?= $opp_count ?> position<?= $opp_count>1?'s':'' ?> it wants to show you.</div>
                </div>
                <a href="opportunities.php" class="btn btn-enter btn-sm" style="flex:0 0 auto;">Let's go →</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── RECENT ACTIVITY ── -->
    <?php if (!empty($txns)): ?>
    <div class="mb-6">
        <div class="section-head">
            <div class="section-title">🧾 Recent Activity</div>
            <a href="finance.php" class="btn btn-ghost btn-sm">All →</a>
        </div>
        <div class="card" style="padding:0;">
            <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $type_badges = [
                    'income'=>'badge-mint','expense'=>'badge-rose','savings'=>'badge-sky',
                    'investment'=>'badge-violet','tithe'=>'badge-gold','discretionary'=>'badge-muted'
                ];
                foreach ($txns as $tx):
                    $bc = $type_badges[$tx['type']] ?? 'badge-muted';
                    $is_in = $tx['type'] === 'income';
                ?>
                <tr>
                    <td class="text-muted"><?= date('M d', strtotime($tx['transaction_date'])) ?></td>
                    <td class="td-primary"><?= htmlspecialchars($tx['description'] ?? '—') ?></td>
                    <td><span class="badge <?= $bc ?>"><?= ucfirst($tx['type']) ?></span></td>
                    <td style="text-align:right;" class="<?= $is_in ? 'td-mint' : 'td-primary' ?>"><?= $is_in ? '+' : '' ?>$<?= number_format($tx['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="mb-6">
        <div class="section-head">
            <div class="section-title">🧾 Activity</div>
            <a href="finance.php" class="btn btn-primary btn-sm">+ Add Transaction</a>
        </div>
        <div class="card text-center" style="padding:40px;">
            <div style="font-size:36px;margin-bottom:12px;">📭</div>
            <div style="color:var(--text-3);font-size:14px;line-height:1.7;">No transactions yet. Start recording your money moves, Chief.</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── QUICK ACTIONS ── -->
    <div class="mb-6">
        <div class="section-head"><div class="section-title">⚡ Quick Actions</div></div>
        <div class="grid-3">
            <a href="finance.php" class="btn btn-ghost" style="justify-content:center;padding:16px;">💳 Record Money</a>
            <a href="goals.php" class="btn btn-ghost" style="justify-content:center;padding:16px;">🎯 Update Goals</a>
            <a href="allocation.php" class="btn btn-ghost" style="justify-content:center;padding:16px;">🏛️ Allocation</a>
        </div>
    </div>

</div><!-- /page-content -->
</main>
</div><!-- /app-layout -->

<!-- Chat FAB -->
<button class="chat-fab" onclick="openChat()" title="Ask GanjiSmart">🧠</button>

<!-- Chat Modal -->
<div id="chat-modal" class="modal-overlay hidden" onclick="if(event.target===this)closeChat()">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title">🧠 Ask GanjiSmart</div>
            <button class="modal-close" onclick="closeChat()">×</button>
        </div>
        <div class="chat-messages" id="chat-messages">
            <div class="chat-msg ai">Hey <?= htmlspecialchars($greet) ?>! What's on your financial mind? Ask me anything — budgets, goals, trades, strategy.</div>
        </div>
        <div class="chat-input-row">
            <input type="text" class="chat-input" id="chat-input" placeholder="Ask anything..." onkeydown="if(event.key==='Enter')sendChat()">
            <button class="btn btn-primary" onclick="sendChat()">→</button>
        </div>
    </div>
</div>

<!-- Notif Stack -->
<div id="notif-stack" style="position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;max-width:400px;"></div>

<script src="assets/js/app.js"></script>
<script>
// Load AI daily insight
fetch('api/ai.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({type:'daily_insight'})
})
.then(r => r.json())
.then(d => {
    document.getElementById('ai-insight').innerHTML = `<span>${d.response || "Markets open. Money moving. Stay disciplined, Chief."}</span>`;
})
.catch(() => {
    document.getElementById('ai-insight').textContent = "GanjiSmart is watching your portfolio. Stay disciplined today. 🎯";
});

// Chat
function openChat() { document.getElementById('chat-modal').classList.remove('hidden'); document.getElementById('chat-input').focus(); }
function closeChat() { document.getElementById('chat-modal').classList.add('hidden'); }

async function sendChat() {
    const input = document.getElementById('chat-input');
    const msg = input.value.trim();
    if (!msg) return;
    input.value = '';
    const msgs = document.getElementById('chat-messages');
    msgs.innerHTML += `<div class="chat-msg user">${msg}</div>`;
    msgs.innerHTML += `<div class="chat-msg ai" id="ai-typing"><span class="loading-text">Thinking...</span></div>`;
    msgs.scrollTop = msgs.scrollHeight;

    try {
        const r = await fetch('api/ai.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({type:'chat', message: msg})
        });
        const d = await r.json();
        document.getElementById('ai-typing').innerHTML = d.response || 'Let me think on that one.';
    } catch(e) {
        document.getElementById('ai-typing').textContent = 'Connection hiccup. Try again, Chief.';
    }
    msgs.scrollTop = msgs.scrollHeight;
}
</script>
</body>
</html>
