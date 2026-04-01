<?php
require_once '../config/db.php';

// Add created_at column if it doesn't exist
$checkSql = "SHOW COLUMNS FROM users LIKE 'created_at'";
$result = $conn->query($checkSql);

if ($result && $result->num_rows == 0) {
    $alterSql = "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    if ($conn->query($alterSql)) {
        echo "Column 'created_at' added successfully.<br>";
    } else {
        echo "Error adding column 'created_at': " . $conn->error . "<br>";
    }
} else {
    echo "Column 'created_at' already exists.<br>";
}

// Ensure department column exists
$checkDept = "SHOW COLUMNS FROM users LIKE 'department'";
$resultDept = $conn->query($checkDept);

if ($resultDept && $resultDept->num_rows == 0) {
    $alterDept = "ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL";
    if ($conn->query($alterDept)) {
        echo "Column 'department' added successfully.<br>";
    } else {
        echo "Error adding column 'department': " . $conn->error . "<br>";
    }
} else {
    echo "Column 'department' already exists.<br>";
}

echo "Schema update complete.";
?>
