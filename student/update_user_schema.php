<?php
require_once '../config/db.php';
session_start();

// Add columns if they don't exist
$columns = [
    'phone' => "VARCHAR(20) DEFAULT NULL",
    'department' => "VARCHAR(100) DEFAULT NULL",
    'semester' => "VARCHAR(20) DEFAULT NULL"
];

foreach ($columns as $col => $definition) {
    $checkSql = "SHOW COLUMNS FROM users LIKE '$col'";
    $result = $conn->query($checkSql);
    if ($result && $result->num_rows == 0) {
        $alterSql = "ALTER TABLE users ADD COLUMN $col $definition";
        if ($conn->query($alterSql)) {
            echo "Column '$col' added successfully.<br>";
        } else {
            echo "Error adding column '$col': " . $conn->error . "<br>";
        }
    } else {
        echo "Column '$col' already exists.<br>";
    }
}

// Populate dummy data for the current user if they are a student
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
    $user_id = $_SESSION['user_id'];
    $updateSql = "UPDATE users SET 
        phone = NULL, 
        department = 'IT', 
        semester = 'IV' 
        WHERE id = ? AND (phone IS NULL OR department IS NULL OR semester IS NULL)";
    
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        echo "Dummy profile info updated for current user.<br>";
    } else {
        echo "Error updating dummy data: " . $conn->error . "<br>";
    }
}

echo "Database schema update complete.";
?>
