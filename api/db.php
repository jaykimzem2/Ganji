<?php
// GanjiSmart – DB Connection
// Support for both Local XAMPP and Remote Serverless (Vercel)
// Updated for Railway deployment

$host = getenv('DB_HOST') ?: 'shuttle.proxy.rlwy.net';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'buxkvoIlogKqjkDEMoFtERKKhMjldCwe';
$name = getenv('DB_NAME') ?: 'railway'; // Railway default DB name is often 'railway' or the project name
$port = getenv('DB_PORT') ?: 47648; // Common railway port, if not provided it might fail

// Suppress error reporting for constructors to catch exceptions manually
mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_init();
$conn->real_connect($host, $user, $pass, $name, $port);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode([
        'error' => 'Database connection failed',
        'suggestion' => 'Chief, setup your Environment Variables on Vercel.',
        'debug' => $conn->connect_error
    ]));
}

$conn->set_charset('utf8mb4');
?>
