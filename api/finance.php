<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_transaction') {
        $type = $_POST['type'];
        $category = trim($_POST['category']);
        $amount = floatval($_POST['amount']);
        $desc = trim($_POST['description']);
        $date = $_POST['transaction_date'];
        $is_planned = isset($_POST['is_planned']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, category, amount, description, is_planned, transaction_date) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issdsss", $uid, $type, $category, $amount, $desc, $is_planned, $date);
        $stmt->execute();
        
        if ($type === 'income') {
            $conn->query("UPDATE users SET monthly_income = monthly_income + $amount WHERE id = $uid");
        }
        
        header('Location: finance.php?added=1');
        exit;
    }
}

$month = $_GET['month'] ?? date('Y-m');
$transactions = $conn->query("SELECT * FROM transactions WHERE user_id=$uid AND DATE_FORMAT(transaction_date,'%Y-%m')='$month' ORDER BY transaction_date DESC")->fetch_all(MYSQLI_ASSOC);

$totals = [];
foreach ($transactions as $t) { $totals[$t['type']] = ($totals[$t['type']] ?? 0) + $t['amount']; }

$income = $totals['income'] ?? 0;
$expenses = ($totals['expense'] ?? 0) + ($totals['discretionary'] ?? 0);
$savings = $totals['savings'] ?? 0;
$invested = $totals['investment'] ?? 0;
$tithe = $totals['tithe'] ?? 0;
$surplus = $income - $expenses - $savings - $invested - $tithe;

$months = $conn->query("SELECT DISTINCT DATE_FORMAT(transaction_date,'%Y-%m') as m FROM transactions WHERE user_id=$uid ORDER BY m DESC")->fetch_all(MYSQLI_ASSOC);

$type_cats = [
    'income' => ['Salary','Freelance','Business','Other'],
    'expense' => ['Rent','Utilities','Food','Transport','Health','Other'],
    'savings' => ['Emergency','SACCO','Crypto','Other'],
    'investment' => ['Trade','ETF','Bond','Other'],
    'tithe' => ['Church','Charity','Other'],
    'discretionary' => ['Dining','Travel','Shopping','Other'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>My Money – GanjiSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-content">

    <div class="flex-between mb-8">
        <div>
            <h1 class="greeting">My Money</h1>
            <p class="greeting-sub">Tracked every coin. Discipline is the only way.</p>
        </div>
        <div class="flex gap-3">
            <button class="btn btn-primary btn-sm" onclick="document.getElementById('add-tx-modal').classList.remove('hidden')">➕ Add Move</button>
            <form method="GET">
                <select name="month" class="form-select" style="padding:10px 14px;font-size:13px;border-radius:12px;cursor:pointer;" onchange="this.form.submit()">
                    <?php if (empty($months)): ?>
                        <option value="<?= date('Y-m') ?>"><?= date('F Y') ?></option>
                    <?php endif; ?>
                    <?php foreach ($months as $m): ?>
                    <option value="<?= $m['m'] ?>" <?= $m['m'] === $month ? 'selected' : '' ?>><?= date('F Y', strtotime($m['m'].'-01')) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Monthly Pulse -->
    <div class="month-row mb-6">
        <div class="month-stat">
            <div class="month-stat-val c-mint">$<?= number_format($income, 0) ?></div>
            <div class="month-stat-label">Income</div>
        </div>
        <div class="month-stat">
            <div class="month-stat-val c-rose">$<?= number_format($expenses, 0) ?></div>
            <div class="month-stat-label">Expenses</div>
        </div>
        <div class="month-stat">
            <div class="month-stat-val" style="color:var(--sky);">$<?= number_format($savings, 0) ?></div>
            <div class="month-stat-label">Saved</div>
        </div>
        <div class="month-stat">
            <div class="month-stat-val c-violet">$<?= number_format($invested, 0) ?></div>
            <div class="month-stat-label">Invested</div>
        </div>
    </div>

    <div class="grid-7-5 mb-6">
        <!-- Breakdown -->
        <div class="card">
            <div class="section-title mb-4">📊 Monthly Breakdown</div>
            <?php if ($income > 0): ?>
            <div class="flex-col gap-4">
                <?php
                $bars = [
                    ['Essentials', $expenses, $income, 'var(--rose)'],
                    ['Savings', $savings, $income, 'var(--sky)'],
                    ['Investments', $invested, $income, 'var(--violet)'],
                    ['Giving', $tithe, $income, 'var(--gold)'],
                    ['Fun', $totals['discretionary'] ?? 0, $income, 'var(--rose)']
                ];
                foreach ($bars as [$l, $v, $m, $c]): $p = ($m>0)?min(100,($v/$m)*100):0; ?>
                <div>
                    <div class="flex-between mb-2">
                        <span class="text-sm text-2"><?= $l ?></span>
                        <span class="text-sm font-bold">$<?= number_format($v,0) ?></span>
                    </div>
                    <div class="progress-track"><div class="progress-fill" style="width:<?= $p ?>%;background:<?= $c ?>;"></div></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="text-center text-muted" style="padding:40px;">No moves recorded yet this month.</div>
            <?php endif; ?>
        </div>

        <!-- Pulse Ring -->
        <div class="card text-center">
            <div class="section-title mb-4">🥧 Money Split</div>
            <div style="height:180px;display:flex;justify-content:center;"><canvas id="finChart"></canvas></div>
            <div class="mt-4 p-4" style="background:rgba(255,255,255,0.03);border-radius:18px;">
                <div class="text-xs text-muted mb-1">Surplus / Deficit</div>
                <div class="wealth-pill-val <?= $surplus>=0?'c-mint':'c-rose' ?>" style="font-size:24px;">$<?= number_format($surplus,0) ?></div>
            </div>
        </div>
    </div>

    <!-- Activity -->
    <div class="card" style="padding:0;">
        <div class="section-title p-6" style="border-bottom:1px solid var(--border);">📋 Activity Records</div>
        <div class="table-wrap">
            <table style="width:100%;">
                <thead>
                    <tr><th>Date</th><th>Type</th><th>Category</th><th>Details</th><th class="text-right">Amount</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="5" class="text-center p-8 text-muted">Silence. No activity recorded.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($transactions as $t): 
                        $is_in = $t['type'] === 'income';
                        $bc = ['income'=>'badge-mint','expense'=>'badge-rose','savings'=>'badge-sky','investment'=>'badge-violet','tithe'=>'badge-gold','discretionary'=>'badge-muted'][$t['type']];
                    ?>
                    <tr>
                        <td class="text-muted"><?= date('M d', strtotime($t['transaction_date'])) ?></td>
                        <td><span class="badge <?= $bc ?>"><?= ucfirst($t['type']) ?></span></td>
                        <td class="text-2"><?= htmlspecialchars($t['category']) ?></td>
                        <td class="td-primary"><?= htmlspecialchars($t['description']) ?></td>
                        <td class="text-right <?= $is_in?'c-mint':'td-primary' ?> font-bold"><?= $is_in?'+':'-' ?>$<?= number_format($t['amount'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</main>
</div>

<!-- Modal -->
<div id="add-tx-modal" class="modal-overlay hidden" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Record a Move</div>
            <button class="modal-close" onclick="document.getElementById('add-tx-modal').classList.add('hidden')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_transaction">
            <div class="input-row">
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" onchange="updateCats(this.value)" required>
                        <option value="income">Income 💵</option>
                        <option value="expense">Expense 💸</option>
                        <option value="savings">Savings 🏦</option>
                        <option value="investment">Investment 📈</option>
                        <option value="tithe">Giving 🙏</option>
                        <option value="discretionary">Fun 🎉</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" id="tx-cat" class="form-select" required></select>
                </div>
            </div>
            <div class="input-row">
                <div class="form-group">
                    <label class="form-label">Amount ($)</label>
                    <input type="number" name="amount" class="form-input" step="0.01" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="transaction_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-input" placeholder="Notes for the partner...">
            </div>
            <button type="submit" class="btn btn-primary btn-xl">Save Move</button>
        </form>
    </div>
</div>

<script>
const cats = <?= json_encode($type_cats) ?>;
function updateCats(t) {
    const s = document.getElementById('tx-cat');
    s.innerHTML = '';
    (cats[t]||['Other']).forEach(c => {
        const o = document.createElement('option'); o.value=c; o.textContent=c; s.appendChild(o);
    });
}
updateCats('income');

const fctx = document.getElementById('finChart')?.getContext('2d');
if (fctx) {
    new Chart(fctx, {
        type: 'doughnut',
        data: {
            labels: ['Expenses','Savings','Investments','Tithe','Surplus'],
            datasets: [{
                data: [<?= $expenses ?>, <?= $savings ?>, <?= $invested ?>, <?= $tithe ?>, <?= max(0, $surplus) ?>],
                backgroundColor: ['#f05278','#4da5e0','#7b6fee','#e8a030','#0fcfac'],
                borderColor: 'transparent',
                hoverOffset: 10
            }]
        },
        options: { cutout: '75%', plugins: { legend: { display: false } } }
    });
}
</script>
</body>
</html>
