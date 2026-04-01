<?php
require_once 'config/db.php';

// Add semester column
$sql1 = "ALTER TABLE users ADD COLUMN semester VARCHAR(50) DEFAULT NULL AFTER roll_no";
if ($conn->query($sql1) === TRUE) {
    echo "Column 'semester' added successfully.<br>";
} else {
    echo "Error adding 'semester': " . $conn->error . "<br>";
}

// Add department column
$sql2 = "ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL AFTER semester";
if ($conn->query($sql2) === TRUE) {
    echo "Column 'department' added successfully.<br>";
} else {
    echo "Error adding 'department': " . $conn->error . "<br>";
}

echo "Database updated!";
?>
