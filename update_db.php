<?php
require_once 'config/db.php';

// Check if column exists
$checkSql = "SHOW COLUMNS FROM cases LIKE 'is_hidden'";
$result = $conn->query($checkSql);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE cases ADD COLUMN is_hidden TINYINT(1) DEFAULT 0";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'is_hidden' added successfully.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'is_hidden' already exists.";
}

$conn->close();
?>
