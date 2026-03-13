<?php
// GanjiSmart – Database Auto-Setup
require_once 'db.php';

echo "<h2>🧠 GanjiSmart DB Setup</h2>";

$sql = file_get_contents('schema.sql');
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
} else {
    echo "<h3>⚠️ Setup finished with $errorCount errors.</h3>";
    echo "<p>Most likely some tables already existed. Try logging in.</p>";
}
?>
