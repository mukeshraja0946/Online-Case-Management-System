<?php
require_once '../config/db.php';

// Check if column exists
$checkSql = "SHOW COLUMNS FROM cases LIKE 'attachment'";
$result = $conn->query($checkSql);

if ($result && $result->num_rows == 0) {
    // Column does not exist, add it
    $alterSql = "ALTER TABLE cases ADD COLUMN attachment VARCHAR(255) DEFAULT NULL";
    if ($conn->query($alterSql) === TRUE) {
        echo "Database updated successfully. The 'attachment' column has been added.\n";
    } else {
        echo "Error updating database: " . $conn->error . "\n";
    }
} else {
    echo "The 'attachment' column already exists.\n";
}
?>
