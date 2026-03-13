<?php
// GanjiSmart – DB Connection (Final Railway Config)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

$host = getenv('DB_HOST') ?: 'shuttle.proxy.rlwy.net';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'buxkvoIlogKqjkDEMoFtERKKhMjldCwe';
$name = getenv('DB_NAME') ?: 'railway';
$port = (int)(getenv('DB_PORT') ?: 53870); // Using your Railway port 53870

mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

@$conn->real_connect($host, $user, $pass, $name, $port);

if ($conn->connect_error) {
    if (getenv('VERCEL')) {
        header('Content-Type: application/json');
        http_response_code(500);
        die(json_encode([
            'error' => 'Partner logic disconnected',
            'hint' => 'Chief, ensure the Railway DB is active and Vercel Env Vars match.'
        ]));
    }
}

$conn->set_charset('utf8mb4');
?>
