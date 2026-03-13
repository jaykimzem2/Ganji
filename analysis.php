<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

// Aggregate stats for analysis
$active_slots = $conn->query("SELECT * FROM trade_slots WHERE user_id=$uid AND status='active'")->fetch_all(MYSQLI_ASSOC);
$closed_slots = $conn->query("SELECT * FROM trade_slots WHERE user_id=$uid AND status='closed' ORDER BY exit_date DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
$opps = $conn->query("SELECT * FROM opportunities WHERE user_id=$uid ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Compute win/loss stats
$wins = array_filter($closed_slots, fn($s) => $s['pnl'] > 0);
$losses = array_filter($closed_slots, fn($s) => $s['pnl'] <= 0);
$win_rate = count($closed_slots) > 0 ? round(count($wins)/count($closed_slots)*100, 1) : 0;
$avg_win = count($wins) > 0 ? array_sum(array_column(array_values($wins), 'pnl')) / count($wins) : 0;
$avg_loss = count($losses) > 0 ? abs(array_sum(array_column(array_values($losses), 'pnl'))) / count($losses) : 0;
$total_pnl = array_sum(array_column($closed_slots, 'pnl'));

// Market distribution
$markets = [];
foreach (array_merge($active_slots, $closed_slots) as $s) {
    $m = $s['market'] ?? 'Other';
    $markets[$m] = ($markets[$m] ?? 0) + 1;
}
arsort($markets);

// Monthly PnL (for chart)
$monthly_pnl = $conn->query("SELECT DATE_FORMAT(exit_date,'%Y-%m') as m, SUM(pnl) as total_pnl, COUNT(*) as trades FROM trade_slots WHERE user_id=$uid AND status='closed' GROUP BY m ORDER BY m DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Analysis – GanjiSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
    <div class="market-ticker"><div class="ticker-inner" id="ticker-inner"></div></div>
    <div class="topbar">
        <div class="topbar-left">
            <h1>📊 Market Analysis</h1>
            <p>Howard Marks meets Jared Dillian. Risk-aware, opportunistic intelligence.</p>
        </div>
        <div class="topbar-right">
            <a href="logout.php" class="topbar-btn">🚪</a>
        </div>
    </div>

    <div class="page-content">
        <!-- Performance Stats -->
        <div class="stat-grid mb-6">
            <div class="stat-card green">
                <div class="stat-icon green">📈</div>
                <div class="stat-label">Win Rate</div>
                <div class="stat-value"><?= $win_rate ?>%</div>
                <div class="stat-change pos"><?= count($wins) ?>W / <?= count($losses) ?>L</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon purple">💰</div>
                <div class="stat-label">Avg Win</div>
                <div class="stat-value">$<?= number_format($avg_win, 0) ?></div>
                <div class="stat-change pos">Per winning trade</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon red">💸</div>
                <div class="stat-label">Avg Loss</div>
                <div class="stat-value">$<?= number_format($avg_loss, 0) ?></div>
                <div class="stat-change neg">Per losing trade</div>
            </div>
            <div class="stat-card <?= $total_pnl >= 0 ? 'green' : 'red' ?>">
                <div class="stat-icon <?= $total_pnl >= 0 ? 'green' : 'red' ?>">🎯</div>
                <div class="stat-label">Total Realized PnL</div>
                <div class="stat-value"><?= $total_pnl >= 0 ? '+' : '' ?>$<?= number_format(abs($total_pnl), 0) ?></div>
                <div class="stat-change <?= $avg_win > $avg_loss ? 'pos' : 'neg' ?>">
                    Ratio: <?= $avg_loss > 0 ? round($avg_win/$avg_loss,2) : 'N/A' ?>:1
                </div>
            </div>
        </div>

        <div class="grid-7-5 mb-6">
            <!-- PnL Chart -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📈 Monthly PnL History</div>
                </div>
                <canvas id="pnlChart" height="220"></canvas>
                <?php if (empty($monthly_pnl)): ?>
                <div style="text-align:center;padding:60px;color:var(--text-muted);">
                    <div style="font-size:40px;margin-bottom:12px;">📊</div>
                    <p>No closed trades yet. Enter your first position!</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Global Market Framework -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">🌍 Market Framework</div>
                </div>
                
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <!-- Howard Marks Pillars -->
                    <div style="background:rgba(108,99,255,0.08);border:1px solid rgba(108,99,255,0.2);border-radius:10px;padding:14px;">
                        <div style="font-size:11px;font-weight:700;color:#a78bfa;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">⚠️ Howard Marks</div>
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="signal-dot active"></div>
                                <span style="font-size:12px;color:var(--text-secondary);">Risk awareness over return chasing</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="signal-dot active"></div>
                                <span style="font-size:12px;color:var(--text-secondary);">Margin of safety enforced</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="signal-dot active"></div>
                                <span style="font-size:12px;color:var(--text-secondary);">Patience until optimal entry</span>
                            </div>
                        </div>
                    </div>

                    <!-- Jared Dillian Pillars -->
                    <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:10px;padding:14px;">
                        <div style="font-size:11px;font-weight:700;color:var(--amber);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">⚡ Jared Dillian</div>
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="signal-dot warning"></div>
                                <span style="font-size:12px;color:var(--text-secondary);">Hunt extreme outlier moves</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="signal-dot warning"></div>
                                <span style="font-size:12px;color:var(--text-secondary);">Macro + momentum confluence</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="signal-dot warning"></div>
                                <span style="font-size:12px;color:var(--text-secondary);">Join in acceleration phase</span>
                            </div>
                        </div>
                    </div>

                    <!-- Current Portfolio Health -->
                    <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:10px;padding:14px;">
                        <div style="font-size:11px;font-weight:700;color:var(--green);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">💎 Portfolio Health</div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:13px;color:var(--text-secondary);">Capital deployed</span>
                            <span style="font-size:14px;font-weight:700;color:#fff;"><?= count($active_slots) ?>/13 slots</span>
                        </div>
                        <div class="progress-bar mt-2">
                            <div class="progress-fill green" style="width:<?= round(count($active_slots)/13*100) ?>%"></div>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">
                            <?= count($active_slots) < 8 ? '✅ Capital available for opportunities' : '⚠️ Portfolio approaching capacity' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Signal Analysis: pre-explosion signals breakdown -->
        <div class="card mb-6">
            <div class="card-header">
                <div class="card-title">⚡ Pre-Explosion Signal Manual</div>
                <div class="card-subtitle">GanjiSmart requires 4+ of these 7 signals before recommending any entry.</div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">
                <?php
                $signals_info = [
                    ['🔥', 'Signal 1: Volume Ignition', '5×–20× average daily volume. Institutional/speculative accumulation.', 'Critical – #1 priority signal', 'var(--red)'],
                    ['⚡', 'Signal 2: Volatility Expansion', 'Daily move exceeds historical: from ~2% to 10–25%. Regime shift.', 'High weight', 'var(--amber)'],
                    ['🚀', 'Signal 3: Breakout Structure', 'Price breaks above multi-week resistance, previous highs, or consolidation.', 'Required for momentum', '#60a5fa'],
                    ['📈', 'Signal 4: Momentum Continuation', 'Price closes above previous highs for multiple consecutive sessions.', 'Demand sustained', 'var(--green)'],
                    ['🏭', 'Signal 5: Sector Alignment', 'Sector has strong inflows/macro catalysts: AI, energy, defense, semis.', 'Context booster', '#a78bfa'],
                    ['💧', 'Signal 6: Liquidity Confirmation', 'Strong bid/ask spreads. Sufficient daily traded value. Active participation.', 'Risk filter', 'var(--cyan)'],
                    ['⚗️', 'Signal 7: Catalyst Event', 'Earnings surprise, gov policy, acquisition, tech breakthrough, FDA approval.', 'L2 outlier catalyst', 'var(--pink)'],
                ];
                foreach ($signals_info as [$icon, $title, $desc, $weight, $color]):
                ?>
                <div style="background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:12px;padding:16px;border-left:3px solid <?= $color ?>;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <span style="font-size:22px;"><?= $icon ?></span>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#fff;"><?= $title ?></div>
                            <div style="font-size:10px;color:<?= $color ?>;font-weight:600;"><?= $weight ?></div>
                        </div>
                    </div>
                    <div style="font-size:12px;color:var(--text-secondary);line-height:1.5;"><?= $desc ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Exit Signals Reference -->
        <div class="card mb-6">
            <div class="card-header">
                <div class="card-title">🚪 Exit Signal Reference</div>
                <div class="card-subtitle">GanjiSmart monitors these continuously. When triggered: "Bigman, momentum done. Exit position."</div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;">
                <?php
                $exit_signals = [
                    ['📉', 'Volume Collapse', 'Volume drops sharply after surge. Buyers leaving the party.', 'var(--red)'],
                    ['🛑', 'Failed Breakout', 'Price attempts higher but repeatedly fails. Momentum weakening.', 'var(--amber)'],
                    ['🌪️', 'Volatility Reversal', 'Price swings become chaotic vs directional. Trend exhaustion.', 'var(--amber)'],
                    ['🐋', 'Distribution Patterns', 'Large sell orders near the top. Institutions taking profit.', 'var(--red)'],
                    ['⏱️', 'Time Exit', 'Max ~1 month holding period reached. Preserve capital.', '#60a5fa'],
                ];
                foreach ($exit_signals as [$icon, $title, $desc, $color]):
                ?>
                <div style="background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.15);border-radius:10px;padding:14px;">
                    <div style="font-size:22px;margin-bottom:8px;"><?= $icon ?></div>
                    <div style="font-size:13px;font-weight:700;color:<?= $color ?>;"><?= $title ?></div>
                    <div style="font-size:11px;color:var(--text-secondary);margin-top:4px;line-height:1.5;"><?= $desc ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Market distribution if trades exist -->
        <?php if (!empty($markets)): ?>
        <div class="card">
            <div class="card-header">
                <div class="card-title">🌐 Your Market Exposure</div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:10px;">
                <?php foreach ($markets as $market => $count): ?>
                <div style="background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:8px;padding:10px 16px;display:flex;align-items:center;gap:8px;">
                    <span style="font-size:14px;font-weight:700;color:#fff;"><?= htmlspecialchars($market) ?></span>
                    <span class="badge badge-purple"><?= $count ?> trade<?= $count > 1 ? 's' : '' ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<div id="notif-stack" style="position:fixed;top:80px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;max-width:400px;"></div>
<script src="assets/js/app.js"></script>
<script>
<?php if (!empty($monthly_pnl)): ?>
const pnlMonths = <?= json_encode(array_reverse(array_column($monthly_pnl, 'm'))) ?>;
const pnlVals = <?= json_encode(array_reverse(array_map(fn($r) => round((float)$r['total_pnl'],2), $monthly_pnl))) ?>;

const pctx = document.getElementById('pnlChart')?.getContext('2d');
if (pctx) {
    new Chart(pctx, {
        type: 'bar',
        data: {
            labels: pnlMonths,
            datasets: [{
                label: 'PnL ($)',
                data: pnlVals,
                backgroundColor: pnlVals.map(v => v >= 0 ? 'rgba(16,185,129,0.7)' : 'rgba(239,68,68,0.7)'),
                borderColor: pnlVals.map(v => v >= 0 ? 'rgba(16,185,129,1)' : 'rgba(239,68,68,1)'),
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: 'rgba(255,255,255,0.4)' } },
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: 'rgba(255,255,255,0.4)', callback: v => '$'+v.toLocaleString() } }
            }
        }
    });
}
<?php endif; ?>
</script>
</body>
</html>
