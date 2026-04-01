<?php
require_once 'config/db.php';
session_start();

$user_id = 16; // Kumar M's ID from previous step
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

echo "Testing query for User ID: $user_id<br>";

$sql = "SELECT name, roll_no, staff_id, profile_photo, role, email, semester, department FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    echo "Prepare successful!<br>";
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    echo "Fetched User: " . print_r($user, true) . "<br>";
    echo "Semester: " . ($user['semester'] ?? "NULL") . "<br>";
} else {
    echo "Prepare FAILED!<br>";
    echo "Error: " . $conn->error . "<br>";
}
?>
