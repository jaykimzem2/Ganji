<?php
// GanjiSmart – DB Connection (Final Railway Config)

// 1. Session Security & Persistence for Vercel
if (session_status() === PHP_SESSION_NONE) {
    // Serverless environments need a writable temp directory for sessions
    if (getenv('VERCEL')) {
        session_save_path('/tmp');
    }
    
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// 2. Database Credentials
$host = getenv('DB_HOST') ?: 'shuttle.proxy.rlwy.net';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'buxkvoIlogKqjkDEMoFtERKKhMjldCwe';
$name = getenv('DB_NAME') ?: 'railway';
$port = (int)(getenv('DB_PORT') ?: 53870);

// 3. MySQL Connection
mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

@$conn->real_connect($host, $user, $pass, $name, $port);

if ($conn->connect_error) {
    if (getenv('VERCEL')) {
        header('Content-Type: application/json');
        http_response_code(500);
        die(json_encode([
            'error' => 'Partner logic disconnected',
            'hint' => 'Chief, ensure the Railway DB is active and Vercel Env Vars match.',
            'debug' => $conn->connect_error
        ]));
    }
}

$conn->set_charset('utf8mb4');
?>
