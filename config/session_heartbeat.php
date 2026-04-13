<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

// Prepare headers for JSON response
header('Content-Type: application/json');

// Ensure settings table exists and has a default value
$conn->query("CREATE TABLE IF NOT EXISTS settings (id INT PRIMARY KEY, session_timeout INT)");
$check_col = $conn->query("SHOW COLUMNS FROM settings LIKE 'session_unit'");
if ($check_col && $check_col->num_rows == 0) {
    $conn->query("ALTER TABLE settings ADD COLUMN session_unit VARCHAR(20) DEFAULT 'minutes'");
}
$conn->query("INSERT IGNORE INTO settings (id, session_timeout, session_unit) VALUES (1, 30, 'minutes')");

$timeout_query = $conn->query("SELECT session_timeout, session_unit FROM settings WHERE id = 1");
$timeout_data = $timeout_query->fetch_assoc();
$timeout_val = $timeout_data ? (int)$timeout_data['session_timeout'] : 30;
$timeout_unit = $timeout_data ? $timeout_data['session_unit'] : 'minutes';

if ($timeout_unit == 'seconds') {
    $timeout_seconds = $timeout_val;
} elseif ($timeout_unit == 'hours') {
    $timeout_seconds = $timeout_val * 3600;
} elseif ($timeout_unit == 'days') {
    $timeout_seconds = $timeout_val * 86400;
} else {
    $timeout_seconds = $timeout_val * 60; // minutes
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'logged_out', 'timeout_seconds' => $timeout_seconds]);
    exit();
}

$last_activity = $_SESSION['last_activity'] ?? time();
$time_since_last = time() - $last_activity;

if ($time_since_last > $timeout_seconds) {
    session_unset();
    session_destroy();
    echo json_encode(['status' => 'expired']);
    exit();
}

// Update last activity if requested (e.g. heartbeat) or just keep session alive
if (isset($_POST['heartbeat'])) {
    $_SESSION['last_activity'] = time();
    $last_activity = $_SESSION['last_activity'];
} else if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
    $last_activity = $_SESSION['last_activity'];
}

// Calculate remaining
$remaining = $timeout_seconds - (time() - $last_activity);

echo json_encode([
    'status' => 'active', 
    'timeout_seconds' => $timeout_seconds, 
    'remaining_seconds' => $remaining
]);
?>
