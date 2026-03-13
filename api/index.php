<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
header('Location: /dashboard');
exit;
?>
