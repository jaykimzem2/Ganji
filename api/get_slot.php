<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
$uid = $_SESSION['user_id'];

header('Content-Type: application/json');

$id = (int)$_GET['id'];
$slot = $conn->query("SELECT * FROM trade_slots WHERE id=$id AND user_id=$uid")->fetch_assoc();

if (!$slot) { echo json_encode(['error' => 'Slot not found']); exit; }

echo json_encode($slot);
?>
