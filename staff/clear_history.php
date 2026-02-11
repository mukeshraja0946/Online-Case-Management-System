<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Soft Delete: Hide processed items ONLY for staff.
    // This will NOT affect the student's view of their history.
    
    $sql = "UPDATE cases SET staff_visible = 0 WHERE status IN ('Approved', 'Rejected')";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Staff history cleared successfully.";
    } else {
        $_SESSION['error'] = "Failed to clear history.";
    }
}

header("Location: history.php");
exit();
?>
