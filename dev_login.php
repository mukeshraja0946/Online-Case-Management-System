<?php
session_start();
require_once 'config/db.php';

$email = 'mukeshraja.it23@bitsathy.ac.in';
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['roll_no'] = $user['roll_no'] ?? '';
    $_SESSION['email'] = $user['email'];
    header("Location: student/dashboard.php");
} else {
    echo "User not found";
}
?>
