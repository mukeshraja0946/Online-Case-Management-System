<?php
require 'd:/Xampp/htdocs/OCMS/config/db.php';

// Add updated_at if not exists
$check = $conn->query("SHOW COLUMNS FROM cases LIKE 'updated_at'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE cases ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at")) {
        echo "Column 'updated_at' added successfully.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column 'updated_at' already exists.\n";
}

// Update existing resolved cases to have an updated_at (approximate)
$conn->query("UPDATE cases SET updated_at = created_at WHERE updated_at IS NULL OR updated_at = '0000-00-00 00:00:00'");
echo "Existing records updated.\n";
?>
