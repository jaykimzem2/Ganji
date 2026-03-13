<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$uid = $_SESSION['user_id'];

// Handle trade entry
if (isset($_GET['accept_ticker'])) {
    $ticker = $_GET['accept_ticker'];
    $price = $_GET['price'];
    $amount = $_GET['amount'];
    $market = $_GET['market'];
    $layer = $_GET['layer'];
    
    // Find an empty slot
    $slot = $conn->query("SELECT id, slot_number FROM trade_slots WHERE user_id = $uid AND status = 'empty' AND layer = '$layer' ORDER BY slot_number LIMIT 1")->fetch_assoc();
    
    if ($slot) {
        $sid = $slot['id'];
        $stmt = $conn->prepare("UPDATE trade_slots SET ticker = ?, entry_price = ?, allocated_amount = ?, market = ?, status = 'active', entry_date = NOW() WHERE id = ?");
        $stmt->bind_param("sddsi", $ticker, $price, $amount, $market, $sid);
        $stmt->execute();
        
        // Subtract from available capital
        $conn->query("UPDATE users SET available_capital = available_capital - $amount WHERE id = $uid");
        
        // Log transaction
        $desc = "Entered position in $ticker";
        $stmt_tx = $conn->prepare("INSERT INTO transactions (user_id, amount, type, category, description, transaction_date) VALUES (?, ?, 'investment', 'Trading', ?, NOW())");
        $stmt_tx->bind_param("ids", $uid, $amount, $desc);
        $stmt_tx->execute();

        header("Location: dashboard.php?msg=Position Entered");
        exit;
    }
}

// Fetch ONE viable opportunity if it exists
$opp = $conn->query("SELECT * FROM opportunities WHERE user_id = $uid AND status = 'pending' ORDER BY signal_score DESC LIMIT 1")->fetch_assoc();

$greet = ['Chief', 'Bigman', 'Bazu', 'Mkuruu'][date('N') % 4];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>Invest – GanjiSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<div class="app-layout">

<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<div class="page-content">

    <div class="mb-8">
        <h1 class="greeting">Ready to move?</h1>
        <p class="greeting-sub">GanjiSmart only signals when the math makes sense.</p>
    </div>

    <?php if ($opp): ?>
    <div class="invest-decision-card">
        <div class="verdict-badge verdict-enter">
            <span class="ai-dot"></span> GanjiSmart Pick
        </div>
        
        <div class="flex-between">
            <div>
                <div class="invest-ticker"><?= htmlspecialchars($opp['ticker']) ?></div>
                <div class="invest-market"><?= htmlspecialchars($opp['company_name']) ?> · <?= htmlspecialchars($opp['market']) ?></div>
            </div>
            <div class="text-right">
                <div class="wealth-label">Current Price</div>
                <div style="font-size:24px;font-weight:700;">$<?= number_format($opp['current_price'], 2) ?></div>
            </div>
        </div>

        <div class="invest-verdict">
            "<?= $greet ?>, based on current market dynamics, <strong><?= htmlspecialchars($opp['ticker']) ?></strong> is presenting a high-probability entry. I've analyzed the outlier potential and it's structurally sound for a <?= $opp['layer'] ?> position."
        </div>

        <form action="opportunities.php" method="GET">
            <input type="hidden" name="accept_ticker" value="<?= $opp['ticker'] ?>">
            <input type="hidden" name="price" value="<?= $opp['current_price'] ?>">
            <input type="hidden" name="market" value="<?= $opp['market'] ?>">
            <input type="hidden" name="layer" value="<?= $opp['layer'] ?>">
            
            <div class="amount-input-wrap">
                <span class="amount-prefix">$</span>
                <input type="number" name="amount" class="amount-input" value="<?= $opp['suggested_allocation'] ?>" step="0.01" required>
                <div class="text-xs text-muted mt-2" style="margin-left:4px;">Suggested Allocation: $<?= number_format($opp['suggested_allocation'], 2) ?></div>
            </div>

            <div class="invest-actions">
                <button type="submit" class="btn btn-enter btn-lg">Deploy Capital Now</button>
                <a href="dashboard.php" class="btn btn-ghost btn-lg">Wait for Later</a>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="invest-quiet">
        <div class="invest-quiet-emoji">🧘</div>
        <div class="invest-quiet-title">Silence is Profitable</div>
        <div class="invest-quiet-text">
            No high-conviction positions right now. <?= $greet ?>, a partner knows when to stay on the sidelines. We'll signal you when it's time to strike.
        </div>
        <a href="dashboard.php" class="btn btn-ghost mt-6">Back to Dashboard</a>
    </div>
    <?php endif; ?>

</div>
</main>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
