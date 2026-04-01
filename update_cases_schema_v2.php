<?php
require_once 'config/db.php';

function addColumnIfNotExists($conn, $table, $column, $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($conn->query($sql)) {
            echo "Added column '$column' to '$table'.<br>";
        } else {
            echo "Error adding column '$column': " . $conn->error . "<br>";
        }
    } else {
        echo "Column '$column' already exists in '$table'.<br>";
    }
}

echo "<h3>Updating Cases Schema...</h3>";

// Add is_hidden_admin
addColumnIfNotExists($conn, 'cases', 'is_hidden_admin', "TINYINT(1) DEFAULT 0");

// Add deletion_requested
addColumnIfNotExists($conn, 'cases', 'deletion_requested', "TINYINT(1) DEFAULT 0");

echo "<br>Schema update completed.";
?>
