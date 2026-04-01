<?php
require_once '../config/db.php';

// Check if updated_at exists
$checkSql = "SHOW COLUMNS FROM cases LIKE 'updated_at'";
$result = $conn->query($checkSql);

if ($result && $result->num_rows == 0) {
    // Column does not exist, add it
    $alterSql = "ALTER TABLE cases ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    if ($conn->query($alterSql) === TRUE) {
        echo "Database updated successfully. The 'updated_at' column has been added.\n";
    } else {
        echo "Error updating database: " . $conn->error . "\n";
    }
} else {
    echo "The 'updated_at' column already exists.\n";
}
?>
