<?php
require_once 'config/db.php';
session_start();

// Force user ID 16 (Kumar M) for testing if session not set, otherwise use session
$user_id = 16;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

echo "Target User ID: $user_id<br>";

// 1. Check current value
$sql = "SELECT semester FROM users WHERE id = $user_id";
$res = $conn->query($sql);
$row = $res->fetch_assoc();
echo "Current DB Value: '" . ($row['semester'] ?? 'NULL') . "'<br>";

// 2. Simulate Update to 'VI'
$new_sem = 'VI';
$update_sql = "UPDATE users SET semester = ? WHERE id = ?";
$stmt = $conn->prepare($update_sql);
if ($stmt) {
    $stmt->bind_param("si", $new_sem, $user_id);
    if ($stmt->execute()) {
        echo "Executed UPDATE to '$new_sem'.<br>";
    } else {
        echo "Update failed: " . $stmt->error . "<br>";
    }
} else {
    echo "Prepare failed: " . $conn->error . "<br>";
}

// 3. Check value again
$res = $conn->query($sql);
$row = $res->fetch_assoc();
echo "New DB Value: '" . ($row['semester'] ?? 'NULL') . "'<br>";

?>
