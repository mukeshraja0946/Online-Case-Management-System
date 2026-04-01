<?php
require_once 'config/db.php';

$sql = "SHOW COLUMNS FROM users LIKE 'last_login'";
$result = $conn->query($sql);

if ($result && $result->num_rows == 0) {
    $alterSql = "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL";
    if ($conn->query($alterSql)) {
        echo "Column 'last_login' added successfully.<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'last_login' already exists.<br>";
}

echo "Database migration complete.";
?>
