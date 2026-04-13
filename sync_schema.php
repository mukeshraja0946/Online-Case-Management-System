<?php
require_once 'config/db.php';

echo "Adding year and batch columns to users table...\n";

$queries = [
    "ALTER TABLE users ADD COLUMN year VARCHAR(50) AFTER department",
    "ALTER TABLE users ADD COLUMN batch VARCHAR(50) AFTER year"
];

foreach ($queries as $query) {
    if ($conn->query($query)) {
        echo "SUCCESS: $query\n";
    } else {
        echo "INFO: $query - " . $conn->error . "\n";
    }
}

echo "Migration finished.\n";
?>
