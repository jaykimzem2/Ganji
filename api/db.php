<?php
// GanjiSmart – DB Connection (Hardened for Railway + Vercel)

// 1. Get credentials from Env Vars (Vercel) or Defaults
$host = getenv('DB_HOST') ?: 'shuttle.proxy.rlwy.net';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'buxkvoIlogKqjkDEMoFtERKKhMjldCwe';
$name = getenv('DB_NAME') ?: 'railway';
$port = (int)(getenv('DB_PORT') ?: 3306); // Default to 3306 if not set

// 2. Suppress warnings for a clean UI experience
mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_init();

// 3. Connect with a short timeout to prevent hanging
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

@$conn->real_connect($host, $user, $pass, $name, $port);

if ($conn->connect_error) {
    // If we're on Vercel, return a clean JSON error
    if (getenv('VERCEL')) {
        header('Content-Type: application/json');
        http_response_code(500);
        die(json_encode([
            'error' => 'Partner logic disconnected',
            'status' => 'waiting_for_db_config',
            'hint' => 'Chief, check your Vercel variables. Ensure DB_PORT matches Railway.'
        ]));
    }
}

$conn->set_charset('utf8mb4');
?>
