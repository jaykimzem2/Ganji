<?php
// GanjiSmart – DB Connection & Session Engine (High Stability)

// 1. Database Credentials
$host = getenv('DB_HOST') ?: 'shuttle.proxy.rlwy.net';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'buxkvoIlogKqjkDEMoFtERKKhMjldCwe';
$name = getenv('DB_NAME') ?: 'railway';
$port = (int)(getenv('DB_PORT') ?: 53870);

// 2. Initial Connection
mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
// Add socket options for stability in serverless
$conn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

@$conn->real_connect($host, $user, $pass, $name, $port);

if ($conn->connect_error) {
    if (getenv('VERCEL')) {
        header('Content-Type: application/json');
        http_response_code(500);
        die(json_encode(['error' => 'Database Offline', 'debug' => $conn->connect_error]));
    }
}
$conn->set_charset('utf8mb4');

// 3. Database-Back Session Handler (Fix for Vercel Loop)
class DatabaseSessionHandler implements SessionHandlerInterface {
    private $db;
    public function __construct($db) { $this->db = $db; }
    public function open($path, $name): bool { return true; }
    public function close(): bool { return true; }
    public function read($id): string {
        $stmt = $this->db->prepare("SELECT data FROM sessions WHERE id = ?");
        if (!$stmt) return "";
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = ($row = $result->fetch_assoc()) ? $row['data'] : "";
        $stmt->close();
        return (string)$data;
    }
    public function write($id, $data): bool {
        $access = time();
        $stmt = $this->db->prepare("REPLACE INTO sessions (id, data, last_access) VALUES (?, ?, ?)");
        if (!$stmt) return false;
        $stmt->bind_param("ssi", $id, $data, $access);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    public function destroy($id): bool {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("s", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    public function gc($maxlifetime): int|false {
        $old = time() - $maxlifetime;
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE last_access < ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $old);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }
}

// 4. Start Session
if (session_status() === PHP_SESSION_NONE) {
    $handler = new DatabaseSessionHandler($conn);
    session_set_save_handler($handler, true);
    
    // Set cookie parameters for cross-instance stability
    session_set_cookie_params([
        'lifetime' => 2592000, // 30 days
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}
?>
