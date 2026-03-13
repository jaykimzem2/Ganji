<?php
// GanjiSmart – Database Auto-Setup
require_once 'db.php';

echo "<h2>🧠 GanjiSmart DB Setup</h2>";

$sql = <<<'SQL'
-- GanjiSmart Database Schema (Railway Context)

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    total_capital DECIMAL(15,2) DEFAULT 0.00,
    available_capital DECIMAL(15,2) DEFAULT 0.00,
    monthly_income DECIMAL(15,2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT 'USD',
    persona VARCHAR(30) DEFAULT 'gen_z_bro',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Capital allocations (Babylon principle)
CREATE TABLE IF NOT EXISTS capital_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    essentials_pct DECIMAL(5,2) DEFAULT 50.00,
    savings_pct DECIMAL(5,2) DEFAULT 10.00,
    investments_pct DECIMAL(5,2) DEFAULT 20.00,
    tithe_pct DECIMAL(5,2) DEFAULT 10.00,
    discretionary_pct DECIMAL(5,2) DEFAULT 10.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Trade slots (13 total: 11 Layer1 + 2 Layer2)
CREATE TABLE IF NOT EXISTS trade_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    slot_number INT NOT NULL,
    layer ENUM('L1','L2') NOT NULL,
    status ENUM('empty','active','closed') DEFAULT 'empty',
    ticker VARCHAR(20),
    market VARCHAR(50),
    market_tier TINYINT,
    entry_price DECIMAL(15,4),
    exit_price DECIMAL(15,4),
    allocated_amount DECIMAL(15,2),
    pnl DECIMAL(15,2) DEFAULT 0.00,
    entry_date TIMESTAMP NULL,
    exit_date TIMESTAMP NULL,
    signals_matched TEXT,
    notes TEXT,
    catalyst TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Opportunity notifications / AI signals
CREATE TABLE IF NOT EXISTS opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ticker VARCHAR(20) NOT NULL,
    company_name VARCHAR(100),
    market VARCHAR(50),
    market_tier TINYINT,
    layer ENUM('L1','L2') NOT NULL,
    signal_score INT DEFAULT 0,
    signals_triggered TEXT,
    catalyst TEXT,
    suggested_allocation DECIMAL(15,2),
    entry_price DECIMAL(15,4),
    current_price DECIMAL(15,4),
    volume_ratio DECIMAL(8,2),
    volatility_pct DECIMAL(8,2),
    sector VARCHAR(50),
    status ENUM('pending','accepted','dismissed','expired') DEFAULT 'pending',
    notification_sent TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Exit signals
CREATE TABLE IF NOT EXISTS exit_signals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    trade_slot_id INT NOT NULL,
    signal_type ENUM('volume_collapse','failed_breakout','volatility_reversal','distribution','time_exit') NOT NULL,
    signal_strength INT DEFAULT 1,
    notes TEXT,
    actioned TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trade_slot_id) REFERENCES trade_slots(id) ON DELETE CASCADE
);

-- Income & expense tracking
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('income','expense','savings','investment','tithe','discretionary') NOT NULL,
    category VARCHAR(50),
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    is_planned TINYINT DEFAULT 1,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Goals
CREATE TABLE IF NOT EXISTS goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    target_amount DECIMAL(15,2) NOT NULL,
    current_amount DECIMAL(15,2) DEFAULT 0.00,
    target_date DATE,
    priority ENUM('high','medium','low') DEFAULT 'medium',
    status ENUM('active','achieved','paused') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications log
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('entry','exit','income','overspend','motivation','compounding') NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Monthly performance snapshots
CREATE TABLE IF NOT EXISTS monthly_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    month_year VARCHAR(7) NOT NULL,
    total_income DECIMAL(15,2) DEFAULT 0.00,
    total_spent DECIMAL(15,2) DEFAULT 0.00,
    total_saved DECIMAL(15,2) DEFAULT 0.00,
    total_invested DECIMAL(15,2) DEFAULT 0.00,
    total_tithe DECIMAL(15,2) DEFAULT 0.00,
    total_pnl DECIMAL(15,2) DEFAULT 0.00,
    closed_trades INT DEFAULT 0,
    winning_trades INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sample demo user
INSERT INTO users (username, email, password_hash, full_name, total_capital, available_capital, monthly_income, currency) 
VALUES ('demo', 'demo@ganjismart.ai', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Chief', 10000.00, 6000.00, 5000.00, 'USD')
ON DUPLICATE KEY UPDATE id=id;

-- Capital allocation for demo user
INSERT INTO capital_allocations (user_id, essentials_pct, savings_pct, investments_pct, tithe_pct, discretionary_pct)
SELECT id, 50, 10, 20, 10, 10 FROM users WHERE username='demo'
ON DUPLICATE KEY UPDATE essentials_pct=50;

-- Sample trade slots
INSERT INTO trade_slots (user_id, slot_number, layer, status, ticker, market, market_tier, entry_price, allocated_amount, entry_date, catalyst, signals_matched)
SELECT u.id, 1, 'L1', 'active', 'NVDA', 'NASDAQ', 1, 478.50, 1000.00, '2026-02-15', 'AI GPU demand surge + earnings beat', '["volume_ignition","breakout_structure","sector_momentum","catalyst"]'
FROM users u WHERE u.username='demo' LIMIT 1;

INSERT INTO trade_slots (user_id, slot_number, layer, status, ticker, market, market_tier, entry_price, allocated_amount, entry_date, catalyst, signals_matched)
SELECT u.id, 2, 'L1', 'active', 'ARM', 'NASDAQ', 1, 142.30, 800.00, '2026-02-20', 'Semiconductor cycle recovery', '["volume_ignition","momentum_continuation","sector_momentum"]'
FROM users u WHERE u.username='demo' LIMIT 1;

INSERT INTO trade_slots (user_id, slot_number, layer, status, ticker, market, market_tier, entry_price, allocated_amount, entry_date, catalyst, signals_matched)
SELECT u.id, 3, 'L2', 'active', 'SMCI', 'NASDAQ', 1, 34.80, 500.00, '2026-03-01', 'Outlier acceleration detected: +180% in 12 days', '["volume_ignition","volatility_expansion","momentum_continuation","breakout_structure","catalyst"]'
FROM users u WHERE u.username='demo' LIMIT 1;

-- Sample opportunities
INSERT INTO opportunities (user_id, ticker, company_name, market, market_tier, layer, signal_score, signals_triggered, catalyst, suggested_allocation, entry_price, volume_ratio, volatility_pct, sector, status)
SELECT u.id, 'PLTR', 'Palantir Technologies', 'NYSE', 1, 'L1', 5, '["volume_ignition","breakout_structure","sector_momentum","momentum_continuation","catalyst"]', 'Government AI contract expansion Q1 2026', 800.00, 92.40, 8.5, 15.3, 'AI/Defense', 'pending'
FROM users u WHERE u.username='demo' LIMIT 1;

INSERT INTO opportunities (user_id, ticker, company_name, market, market_tier, layer, signal_score, signals_triggered, catalyst, suggested_allocation, entry_price, volume_ratio, volatility_pct, sector, status)
SELECT u.id, 'TSM', 'Taiwan Semiconductor', 'NYSE', 2, 'L1', 4, '["volume_ignition","breakout_structure","sector_momentum","liquidity_confirmation"]', 'Advanced packaging demand surge', 700.00, 189.20, 6.2, 11.8, 'Semiconductors', 'pending'
FROM users u WHERE u.username='demo' LIMIT 1;

INSERT INTO opportunities (user_id, ticker, company_name, market, market_tier, layer, signal_score, signals_triggered, catalyst, suggested_allocation, entry_price, volume_ratio, volatility_pct, sector, status)
SELECT u.id, 'CERE', 'Cerevel Therapeutics', 'NASDAQ', 1, 'L2', 6, '["volume_ignition","volatility_expansion","breakout_structure","momentum_continuation","sector_momentum","catalyst"]', 'FDA approval catalyst + institutional accumulation', 300.00, 4.20, 18.4, 42.5, 'Biotech', 'pending'
FROM users u WHERE u.username='demo' LIMIT 1;

-- Sample transactions
INSERT INTO transactions (user_id, type, category, amount, description, is_planned, transaction_date)
SELECT u.id, 'income', 'Salary', 5000.00, 'Monthly salary', 1, '2026-03-01' FROM users u WHERE u.username='demo' LIMIT 1;

INSERT INTO transactions (user_id, type, category, amount, description, is_planned, transaction_date)
SELECT u.id, 'expense', 'Rent', 1200.00, 'Monthly rent', 1, '2026-03-01' FROM users u WHERE u.username='demo' LIMIT 1;

INSERT INTO transactions (user_id, type, category, amount, description, is_planned, transaction_date)
SELECT u.id, 'savings', 'Emergency Fund', 500.00, 'Auto savings', 1, '2026-03-01' FROM users u WHERE u.username='demo' LIMIT 1;

INSERT INTO transactions (user_id, type, category, amount, description, is_planned, transaction_date)
SELECT u.id, 'investment', 'Trade', 1000.00, 'NVDA position', 1, '2026-02-15' FROM users u WHERE u.username='demo' LIMIT 1;

INSERT INTO transactions (user_id, type, category, amount, description, is_planned, transaction_date)
SELECT u.id, 'discretionary', 'Entertainment', 350.00, 'Weekend out', 0, '2026-03-08' FROM users u WHERE u.username='demo' LIMIT 1;

-- Sample goals
INSERT INTO goals (user_id, title, description, target_amount, current_amount, target_date, priority)
SELECT u.id, 'Emergency Fund (6 months)', 'Build 6-month expense buffer', 15000.00, 4200.00, '2026-12-31', 'high' FROM users u WHERE u.username='demo' LIMIT 1;

INSERT INTO goals (user_id, title, description, target_amount, current_amount, target_date, priority)
SELECT u.id, 'Investment Portfolio', 'Grow investment capital to $50K', 50000.00, 10000.00, '2027-12-31', 'high' FROM users u WHERE u.username='demo' LIMIT 1;

INSERT INTO goals (user_id, title, description, target_amount, current_amount, target_date, priority)
SELECT u.id, 'MacBook Pro', 'New workstation upgrade', 4000.00, 800.00, '2026-08-01', 'medium' FROM users u WHERE u.username='demo' LIMIT 1;

SQL;
if (!$sql) {
    die("❌ Error: Could not find schema.sql");
}

// Split the schema into individual queries
$queries = explode(';', $sql);
$successCount = 0;
$errorCount = 0;

foreach ($queries as $query) {
    $q = trim($query);
    if (!empty($q)) {
        if ($conn->query($q)) {
            $successCount++;
        } else {
            // Ignore "Table already exists" errors
            if ($conn->errno != 1050) {
                echo "⚠️ Query Error: " . $conn->error . "<br>";
                $errorCount++;
            }
        }
    }
}

echo "<hr>";
if ($errorCount === 0) {
    echo "<h3>✅ Setup Complete, Chief!</h3>";
    echo "<p>$successCount operations successful.</p>";
    echo "<p><b>Demo Account Ready:</b> demo / password</p>";
    echo "<a href='login' style='padding:10px 20px; background:#6c63ff; color:#fff; text-decoration:none; border-radius:8px;'>Go to Login →</a>";
    session_destroy();
} else {
    echo "<h3>⚠️ Setup finished with $errorCount errors.</h3>";
    echo "<p>Most likely some tables already existed. Try logging in.</p>";
}
?>

