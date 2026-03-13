<?php

require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: /'); exit; }

$uid = $_SESSION['user_id'];

// Fetch Active Positions
$active = $conn->query("SELECT * FROM trade_slots WHERE user_id = $uid AND status = 'active' ORDER BY entry_date DESC")->fetch_all(MYSQLI_ASSOC);

// Fetch Closed History
$history = $conn->query("SELECT * FROM trade_slots WHERE user_id = $uid AND status = 'closed' ORDER BY entry_date DESC")->fetch_all(MYSQLI_ASSOC);

$greet = ['Chief', 'Bigman', 'Bazu', 'Mkuruu'][date('N') % 4];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>Activity History – GanjiSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<div class="app-layout">

<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<div class="page-content">

    <div class="mb-8">
        <h1 class="greeting">Market Moves</h1>
        <p class="greeting-sub">Tracking every entered position and recycling capital.</p>
    </div>

    <!-- Active Positions -->
    <div class="mb-8">
        <div class="section-head">
            <div class="section-title">⚡ Active Positions</div>
            <span class="badge badge-mint"><?= count($active) ?> Active</span>
        </div>
        
        <?php if (!empty($active)): ?>
            <?php foreach ($active as $pos): ?>
            <div class="position-row" onclick="openExitModal(<?= $pos['id'] ?>, '<?= $pos['ticker'] ?>', <?= $pos['entry_price'] ?>)">
                <div class="flex-center gap-4">
                    <div class="logo-icon" style="width:48px;height:48px;border-radius:12px;font-size:18px;box-shadow:none;"><?= substr($pos['ticker'], 0, 1) ?></div>
                    <div>
                        <div class="position-ticker"><?= htmlspecialchars($pos['ticker']) ?></div>
                        <div class="position-meta"><?= htmlspecialchars($pos['market']) ?> · Entry: $<?= number_format($pos['entry_price'], 2) ?></div>
                    </div>
                </div>
                <div>
                    <div class="position-amount">$<?= number_format($pos['allocated_amount'], 2) ?></div>
                    <div class="position-sub">Recycle Capital →</div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card position-empty">
                <div class="position-empty-emoji">🌊</div>
                <p>No active positions right now.<br>Stay liquid, Chief.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Closed History -->
    <div class="mb-8">
        <div class="section-head">
            <div class="section-title">🗂️ Closed History</div>
        </div>

        <?php if (!empty($history)): ?>
            <?php foreach ($history as $h): 
                $win = $h['pnl'] >= 0;
            ?>
            <div class="history-card <?= $win ? 'win' : 'loss' ?>">
                <div class="history-head">
                    <div>
                        <div class="history-ticker"><?= htmlspecialchars($h['ticker']) ?></div>
                        <div class="history-market"><?= htmlspecialchars($h['market']) ?> · <?= date('M d, Y', strtotime($h['entry_date'])) ?></div>
                    </div>
                    <div>
                        <div class="history-pnl <?= $win ? 'c-mint' : 'c-rose' ?>">
                            <?= $win ? '+' : '' ?>$<?= number_format($h['pnl'], 2) ?>
                        </div>
                        <div class="history-pnl-pct"><?= $win ? 'Profit Recycled' : 'Capital Preserved' ?></div>
                    </div>
                </div>
                <div class="history-grid">
                    <div class="history-stat">
                        <div class="history-stat-val">$<?= number_format($h['allocated_amount'], 2) ?></div>
                        <div class="history-stat-label">Invested</div>
                    </div>
                    <div class="history-stat">
                        <div class="history-stat-val">$<?= number_format($h['entry_price'], 2) ?></div>
                        <div class="history-stat-label">Entry</div>
                    </div>
                    <div class="history-stat">
                        <div class="history-stat-val">$<?= number_format($h['exit_price'], 2) ?></div>
                        <div class="history-stat-label">Exit</div>
                    </div>
                </div>
                <?php if ($h['catalyst']): ?>
                <div class="history-catalyst">
                    <strong>GanjiSmart Insight:</strong> <?= htmlspecialchars($h['catalyst']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card position-empty">
                <p>No history yet. Your wins will stack here.</p>
            </div>
        <?php endif; ?>
    </div>

</div>
</main>
</div>

<!-- Exit Modal -->
<div id="exit-modal" class="modal-overlay hidden" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="exit-title">Exit Position</div>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form action="api/trade_action.php" method="POST">
            <input type="hidden" name="action" value="exit">
            <input type="hidden" name="id" id="exit-id">
            
            <p class="text-2 mb-4">Recycling capital from <strong id="exit-ticker-name" class="c-violet"></strong>. Enter the current market price to close.</p>
            
            <div class="form-group">
                <label class="form-label">Exit Price ($)</label>
                <input type="number" name="exit_price" class="form-input" step="0.01" required placeholder="0.00">
            </div>

            <button type="submit" class="btn btn-enter btn-xl mt-2">Recycle Capital</button>
        </form>
    </div>
</div>

<script>
function openExitModal(id, ticker, entry) {
    document.getElementById('exit-id').value = id;
    document.getElementById('exit-ticker-name').textContent = ticker;
    document.getElementById('exit-modal').classList.remove('hidden');
}
function closeModal() {
    document.getElementById('exit-modal').classList.add('hidden');
}
</script>
</body>
</html>
