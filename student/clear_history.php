<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['user_id'];
    
    // Soft Delete: Hide history items (Approved/Rejected) for the student.
    // User requested "not approved & rejected also deleted" -> This means don t delete pending, only history.
    
    $sql = "UPDATE cases SET is_hidden_history = 1 WHERE student_id = ? AND status IN ('Approved', 'Rejected')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "History cleared successfully.";
    } else {
        $_SESSION['error'] = "Failed to clear history.";
    }
}

header("Location: case_history.php");
exit();
?>
