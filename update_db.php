<?php
require_once 'config/db.php';

// Check if profile_photo column exists, if not add it, if yes modify it
$check_col = "SHOW COLUMNS FROM users LIKE 'profile_photo'";
$result = $conn->query($check_col);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE users ADD COLUMN profile_photo TEXT DEFAULT NULL";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'profile_photo' added successfully.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    // Column exists, modify it to TEXT to hold long URLs
    $sql = "ALTER TABLE users MODIFY COLUMN profile_photo TEXT DEFAULT NULL";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'profile_photo' updated to TEXT type successfully.";
    } else {
        echo "Error updating column: " . $conn->error;
    }
}

$conn->close();
?>
