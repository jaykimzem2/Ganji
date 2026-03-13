<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$nav = [
    ['id'=>'dashboard',     'icon'=>'🏠', 'label'=>'Home',       'url'=>'dashboard.php'],
    ['id'=>'finance',       'icon'=>'💳', 'label'=>'My Money',   'url'=>'finance.php'],
    ['id'=>'goals',         'icon'=>'🎯', 'label'=>'Goals',      'url'=>'goals.php'],
    ['id'=>'opportunities', 'icon'=>'📈', 'label'=>'Invest',     'url'=>'opportunities.php'],
    ['id'=>'trades',        'icon'=>'🗂️', 'label'=>'History',   'url'=>'trades.php'],
];
$more_nav = [
    ['id'=>'allocation',    'icon'=>'🏛️', 'label'=>'Allocation','url'=>'allocation.php'],
    ['id'=>'notifications', 'icon'=>'🔔', 'label'=>'Alerts',    'url'=>'notifications.php'],
    ['id'=>'settings',      'icon'=>'⚙️', 'label'=>'Settings',  'url'=>'settings.php'],
];
$all_nav = array_merge($nav, $more_nav);
?>

<!-- Desktop Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo">
            <img src="/assets/icons/icon-192.png" alt="GanjiSmart" class="logo-img">
            <div>
                <div class="logo-text">GanjiSmart</div>
                <div class="logo-sub">FINANCIAL PARTNER</div>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <?php foreach ($nav as $item): ?>
        <a href="<?= $item['url'] ?>" class="nav-item <?= $current_page === $item['id'] ? 'active' : '' ?>">
            <span class="nav-icon"><?= $item['icon'] ?></span>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>

        <div class="nav-section-label">More</div>
        <?php foreach ($more_nav as $item): ?>
        <a href="<?= $item['url'] ?>" class="nav-item <?= $current_page === $item['id'] ? 'active' : '' ?>">
            <span class="nav-icon"><?= $item['icon'] ?></span>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>

        <a href="logout.php" class="nav-item mt-4" style="color:var(--rose);">
            <span class="nav-icon">🚪</span> Logout
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card" onclick="location.href='settings.php'">
            <div class="user-avatar">👤</div>
            <div>
                <div class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Chief') ?></div>
                <div class="user-role">Disciplined Investor</div>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Bottom Navigation -->
<nav class="mobile-nav" id="mobile-nav">
    <?php foreach ($nav as $item): ?>
    <a href="<?= $item['url'] ?>" class="mobile-nav-item <?= $current_page === $item['id'] ? 'active' : '' ?>">
        <span class="mobile-nav-icon"><?= $item['icon'] ?></span>
        <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
</nav>
