<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
$alloc = $conn->query("SELECT * FROM capital_allocations WHERE user_id = $uid")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_alloc') {
        $e = floatval($_POST['essentials_pct']);
        $s = floatval($_POST['savings_pct']);
        $inv = floatval($_POST['investments_pct']);
        $t = floatval($_POST['tithe_pct']);
        $d = floatval($_POST['discretionary_pct']);
        $total = $e + $s + $inv + $t + $d;
        
        if (abs($total - 100) < 0.01) {
            $stmt = $conn->prepare("UPDATE capital_allocations SET essentials_pct=?, savings_pct=?, investments_pct=?, tithe_pct=?, discretionary_pct=? WHERE user_id=?");
            $stmt->bind_param("dddddi", $e, $s, $inv, $t, $d, $uid);
            $stmt->execute();
            header('Location: allocation.php?saved=1');
        } else {
            $error = "Chief, percentages must total 100%. Currently: $total%";
        }
        exit;
    }
    
    if ($action === 'update_income') {
        $income = floatval($_POST['monthly_income']);
        $capital = floatval($_POST['total_capital']);
        $currency = $_POST['currency'];
        $conn->query("UPDATE users SET monthly_income=$income, total_capital=$capital, currency='$currency' WHERE id=$uid");
        header('Location: allocation.php?income_saved=1');
        exit;
    }
}

$income = $user['monthly_income'];
$alloc_vals = [
    'essentials' => ['Essentials & Bills', $alloc['essentials_pct'], $income * $alloc['essentials_pct']/100, '#6c63ff', '🏠', 'Rent, utilities, food, transport'],
    'savings' => ['Savings', $alloc['savings_pct'], $income * $alloc['savings_pct']/100, '#10b981', '🏦', 'Emergency fund, SACCO, FD'],
    'investments' => ['Investments', $alloc['investments_pct'], $income * $alloc['investments_pct']/100, '#3b82f6', '📈', 'Stocks, ETFs, trade capital'],
    'tithe' => ['Tithe / Giving', $alloc['tithe_pct'], $income * $alloc['tithe_pct']/100, '#f59e0b', '🙏', 'Church, charity, family support'],
    'discretionary' => ['Discretionary', $alloc['discretionary_pct'], $income * $alloc['discretionary_pct']/100, '#ec4899', '🎉', 'Entertainment, dining, lifestyle'],
];

$compounding_rates = [0.08, 0.12, 0.15, 0.20];
$years = [1, 3, 5, 10, 15, 20];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capital Allocation – GanjiSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
    <div class="market-ticker"><div class="ticker-inner" id="ticker-inner"></div></div>
    <div class="topbar">
        <div class="topbar-left">
            <h1>🏛️ Capital Allocation</h1>
            <p>Babylon principle: every coin has a purpose before you spend it.</p>
        </div>
        <div class="topbar-right">
            <a href="logout.php" class="topbar-btn">🚪</a>
        </div>
    </div>

    <div class="page-content">
        <?php if (isset($_GET['saved'])): ?>
        <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:12px;padding:14px 18px;margin-bottom:20px;color:var(--green);">
            ✅ Allocation updated! The Babylon system is calibrated, Chief.
        </div>
        <?php endif; ?>
        <?php if (isset($_GET['income_saved'])): ?>
        <div style="background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.3);border-radius:12px;padding:14px 18px;margin-bottom:20px;color:#60a5fa;">
            ✅ Income & capital updated. Recalculating allocation amounts.
        </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:12px;padding:14px 18px;margin-bottom:20px;color:var(--red);">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="grid-7-5 mb-6">

            <!-- Allocation Editor -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">⚙️ Edit Monthly Allocation</div>
                        <div class="card-subtitle">Must total 100%. Based on Babylon's Golden Rule.</div>
                    </div>
                    <span id="total-badge" class="badge badge-green">Total: 100%</span>
                </div>

                <form method="POST" id="alloc-form">
                    <input type="hidden" name="action" value="update_alloc">
                    
                    <div style="display:flex;flex-direction:column;gap:20px;">
                        <?php foreach ($alloc_vals as $key => [$name, $pct, $amt, $color, $icon, $desc]): ?>
                        <div>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <span style="font-size:20px;"><?= $icon ?></span>
                                    <div>
                                        <div style="font-size:14px;font-weight:600;color:#fff;"><?= $name ?></div>
                                        <div style="font-size:11px;color:var(--text-muted);"><?= $desc ?></div>
                                    </div>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <span id="amt-<?= $key ?>" style="font-size:13px;color:var(--text-muted);">$<?= number_format($amt,0) ?>/mo</span>
                                    <div style="display:flex;align-items:center;gap:4px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;padding:4px 8px;">
                                        <input type="number" name="<?= $key ?>_pct" id="pct-<?= $key ?>"
                                               class="form-input" value="<?= $pct ?>" min="0" max="100" step="1"
                                               oninput="updateAlloc()" required
                                               style="width:50px;padding:4px;background:transparent;border:none;text-align:right;font-size:16px;font-weight:700;color:#fff;">
                                        <span style="color:var(--text-muted);font-size:14px;">%</span>
                                    </div>
                                </div>
                            </div>
                            <div style="position:relative;">
                                <div class="progress-bar">
                                    <div id="bar-<?= $key ?>" class="progress-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
                                </div>
                                <!-- Babylon reference lines -->
                                <?php $babylon = ['essentials'=>50,'savings'=>10,'investments'=>20,'tithe'=>10,'discretionary'=>10]; ?>
                                <div style="position:absolute;top:-2px;left:<?= $babylon[$key] ?>%;width:2px;height:10px;background:rgba(255,255,255,0.4);border-radius:1px;" title="Babylon target: <?= $babylon[$key] ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top:24px;background:rgba(255,255,255,0.04);border-radius:10px;padding:14px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:14px;color:var(--text-secondary);">Total allocated</span>
                        <span id="total-pct" style="font-size:22px;font-weight:900;font-family:'Space Grotesk',sans-serif;color:var(--green);">100%</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-full btn-lg mt-4">💾 Save Allocation</button>
                </form>
            </div>

            <!-- Visual Summary + Income settings -->
            <div style="display:flex;flex-direction:column;gap:20px;">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📊 Allocation Breakdown</div>
                    </div>
                    <canvas id="allocChart2" height="200"></canvas>
                    <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px;">
                        <?php foreach ($alloc_vals as $key => [$name, $pct, $amt, $color]): ?>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:10px;height:10px;border-radius:50%;background:<?= $color ?>;flex-shrink:0;"></div>
                            <div style="flex:1;font-size:13px;color:var(--text-secondary);"><?= $name ?></div>
                            <div style="font-size:13px;font-weight:700;color:#fff;"><?= $pct ?>%</div>
                            <div style="font-size:12px;color:var(--text-muted);min-width:60px;text-align:right;">$<?= number_format($amt,0) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">💵 Income & Capital</div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_income">
                        <div class="form-group">
                            <label class="form-label">Monthly Income ($)</label>
                            <input type="number" name="monthly_income" class="form-input" value="<?= $user['monthly_income'] ?>" step="0.01" oninput="recalcAmounts(this.value)">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Total Capital ($)</label>
                            <input type="number" name="total_capital" class="form-input" value="<?= $user['total_capital'] ?>" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Currency</label>
                            <select name="currency" class="form-select">
                                <?php foreach (['USD','KES','EUR','GBP','NGN','ZAR','INR'] as $c): ?>
                                <option <?= $user['currency'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-full">💾 Update Income</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Babylon Compounding Table -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">🌳 Compounding Projection Table</div>
                    <div class="card-subtitle">Babylon principle: profits become children, children become grandchildren.</div>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Years</th>
                            <?php foreach ($compounding_rates as $r): ?>
                            <th><?= $r*100 ?>% Returns</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($years as $y):
                            $colors = ['var(--text-secondary)', '#60a5fa', 'var(--green)', 'var(--amber)'];
                        ?>
                        <tr>
                            <td class="td-primary font-bold"><?= $y ?> year<?= $y > 1 ? 's' : '' ?></td>
                            <?php foreach ($compounding_rates as $i => $r): ?>
                            <td style="color:<?= $colors[$i] ?>;font-weight:<?= $y >= 10 ? '700' : '400' ?>;">
                                $<?= number_format($user['total_capital'] * pow(1+$r, $y), 0) ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;background:rgba(108,99,255,0.08);border:1px solid rgba(108,99,255,0.2);border-radius:10px;padding:14px;">
                <p style="font-size:13px;color:var(--text-secondary);">
                    💎 <strong style="color:#a78bfa;">Babylon Wisdom:</strong> "Gold laboreth diligently and contentedly for the wise owner who finds for it profitable employment." 
                    <span style="color:var(--text-muted);">— Starting capital: $<?= number_format($user['total_capital'],0) ?></span>
                </p>
            </div>
        </div>
    </div>
</div>
</div>

<div id="notif-stack" style="position:fixed;top:80px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;max-width:400px;"></div>
<script src="/assets/js/app.js"></script>
<script>
const income = <?= $user['monthly_income'] ?>;
const allocKeys = ['essentials', 'savings', 'investments', 'tithe', 'discretionary'];
const allocColors = <?= json_encode(array_values(array_map(fn($v) => $v[3], $alloc_vals))) ?>;
const allocNames = <?= json_encode(array_values(array_map(fn($v) => $v[0], $alloc_vals))) ?>;

function updateAlloc() {
    let total = 0;
    const vals = [];
    allocKeys.forEach(k => {
        const v = parseFloat(document.getElementById('pct-'+k)?.value) || 0;
        vals.push(v);
        total += v;
        const bar = document.getElementById('bar-'+k);
        if (bar) bar.style.width = Math.min(100,v) + '%';
        const amt = document.getElementById('amt-'+k);
        if (amt) amt.textContent = '$' + Math.round(income * v / 100).toLocaleString() + '/mo';
    });
    
    const tBadge = document.getElementById('total-badge');
    const tPct = document.getElementById('total-pct');
    const rounded = Math.round(total * 10) / 10;
    if (tPct) tPct.textContent = rounded + '%';
    if (tBadge) {
        if (Math.abs(rounded - 100) < 0.5) {
            tBadge.className = 'badge badge-green';
            tBadge.textContent = '✓ Total: 100%';
        } else {
            tBadge.className = 'badge badge-red';
            tBadge.textContent = 'Total: ' + rounded + '% (need 100%)';
        }
    }
    if (tPct) tPct.style.color = Math.abs(rounded-100) < 0.5 ? 'var(--green)' : 'var(--red)';
    
    // Update chart
    if (window.allocChart2) {
        window.allocChart2.data.datasets[0].data = vals;
        window.allocChart2.update();
    }
}

function recalcAmounts(newIncome) {
    const inc = parseFloat(newIncome) || 0;
    allocKeys.forEach(k => {
        const v = parseFloat(document.getElementById('pct-'+k)?.value) || 0;
        const amt = document.getElementById('amt-'+k);
        if (amt) amt.textContent = '$' + Math.round(inc * v / 100).toLocaleString() + '/mo';
    });
}

// Init chart
const actx = document.getElementById('allocChart2')?.getContext('2d');
if (actx) {
    window.allocChart2 = new Chart(actx, {
        type: 'doughnut',
        data: {
            labels: allocNames,
            datasets: [{
                data: <?= json_encode(array_values(array_map(fn($v) => $v[1], $alloc_vals))) ?>,
                backgroundColor: allocColors.map(c => c + 'cc'),
                borderColor: 'transparent',
                hoverOffset: 6
            }]
        },
        options: {
            cutout: '65%',
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ` ${c.label}: ${c.parsed}%` } } }
        }
    });
}
</script>
</body>
</html>
