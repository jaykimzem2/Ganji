<?php

require_once 'db.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
$uid = $_SESSION['user_id'];

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'open' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $slot_num = (int)$_POST['slot_number'];
    $layer = $_POST['layer'];
    $ticker = strtoupper(trim($_POST['ticker']));
    $market = $_POST['market'] ?? 'NASDAQ';
    $entry = floatval($_POST['entry_price']);
    $alloc = floatval($_POST['allocated_amount']);
    $catalyst = trim($_POST['catalyst'] ?? '');
    $signals = $_POST['signals'] ?? [];
    $signals_json = json_encode($signals);
    
    // Check available capital
    $user = $conn->query("SELECT available_capital FROM users WHERE id=$uid")->fetch_assoc();
    if ($alloc > $user['available_capital']) {
        echo json_encode(['error' => 'Insufficient capital. Available: $' . number_format($user['available_capital'])]); exit;
    }
    
    $stmt = $conn->prepare("UPDATE trade_slots SET status='active', ticker=?, market=?, entry_price=?, allocated_amount=?, catalyst=?, signals_matched=?, entry_date=NOW() WHERE user_id=? AND slot_number=? AND layer=? AND status='empty'");
    $stmt->bind_param("ssddssiis", $ticker, $market, $entry, $alloc, $catalyst, $signals_json, $uid, $slot_num, $layer);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $conn->query("UPDATE users SET available_capital = available_capital - $alloc WHERE id=$uid");
        
        // Log transaction
        $stmt2 = $conn->prepare("INSERT INTO transactions (user_id, type, category, amount, description, transaction_date) VALUES (?, 'investment', 'Trade', ?, ?, NOW())");
        $desc = "$ticker Entry – $layer Slot $slot_num";
        $stmt2->bind_param("ids", $uid, $alloc, $desc);
        $stmt2->execute();
        
        echo json_encode(['success' => true, 'message' => "Chief, $ticker trade opened. Slot $slot_num deployed."]);
    } else {
        echo json_encode(['error' => 'Failed to open trade. Slot may already be active.']);
    }
    exit;
}

if ($action === 'exit') {
    $id = (int)$_REQUEST['id'];
    $exit_price = floatval($_REQUEST['exit_price']);
    
    $slot = $conn->query("SELECT * FROM trade_slots WHERE id=$id AND user_id=$uid AND status='active'")->fetch_assoc();
    if (!$slot) { echo json_encode(['error' => 'Trade not found or already closed']); exit; }
    
    $pnl = ($exit_price - $slot['entry_price']) / $slot['entry_price'] * $slot['allocated_amount'];
    $returned = $slot['allocated_amount'] + $pnl;
    $pnl_label = ($pnl >= 0 ? '+$' : '-$') . number_format(abs($pnl), 2);
    
    $stmt = $conn->prepare("UPDATE trade_slots SET status='closed', exit_price=?, pnl=?, exit_date=NOW() WHERE id=? AND user_id=?");
    $stmt->bind_param("ddii", $exit_price, $pnl, $id, $uid);
    $stmt->execute();
    
    // Return capital
    $conn->query("UPDATE users SET available_capital = available_capital + $returned WHERE id=$uid");
    
    // Log notification
    $msg = "Bigman, {$slot['ticker']} position closed. PnL: $pnl_label. Capital recycled to pool. 💰";
    $stmt2 = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'exit', ?)");
    $stmt2->bind_param("is", $uid, $msg);
    $stmt2->execute();
    
    echo json_encode(['success' => true, 'pnl' => $pnl, 'message' => "Position closed. $pnl_label. Slot free. 🔄"]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
?>
