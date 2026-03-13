<?php
// GanjiSmart – DB Connection
// Support for both Local XAMPP and Remote Serverless (Vercel)

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$name = getenv('DB_NAME') ?: 'ganjismart';

// For cloud databases that require SSL (like PlanetScale or Tidb)
$conn = mysqli_init();
if (getenv('DB_SSL')) {
    $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
}

// Suppress error reporting for constructors to catch exceptions manually
mysqli_report(MYSQLI_REPORT_OFF);

$conn->real_connect($host, $user, $pass, $name);

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
