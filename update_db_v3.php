<?php
require_once 'config/db.php';

// 1. Create case_types table
$sql_types = "CREATE TABLE IF NOT EXISTS case_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) UNIQUE NOT NULL
)";
if ($conn->query($sql_types)) {
    echo "Table 'case_types' created or already exists.<br>";
    
    // Insert default values if empty
    $check_empty = $conn->query("SELECT COUNT(*) as count FROM case_types");
    $count = $check_empty->fetch_assoc()['count'];
    if ($count == 0) {
        $defaults = ['Academic', 'Hostel', 'Discipline', 'Placement'];
        foreach ($defaults as $type) {
            $conn->query("INSERT IGNORE INTO case_types (type_name) VALUES ('$type')");
        }
        echo "Default case types inserted.<br>";
    }
} else {
    echo "Error creating case_types: " . $conn->error . "<br>";
}

// 2. Add columns to cases table
$columns_to_add = [
    'roll_number' => "VARCHAR(20) AFTER description",
    'case_type' => "VARCHAR(100) AFTER roll_number",
    'case_date' => "DATE AFTER case_type",
    'case_day' => "VARCHAR(20) AFTER case_date",
    'case_time' => "TIME AFTER case_day"
];

foreach ($columns_to_add as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM cases LIKE '$col'");
    if ($check->num_rows == 0) {
        if ($conn->query("ALTER TABLE cases ADD COLUMN $col $definition")) {
            echo "Column '$col' added to cases table.<br>";
        } else {
            echo "Error adding column '$col': " . $conn->error . "<br>";
        }
    } else {
        echo "Column '$col' already exists in cases table.<br>";
    }
}

echo "Database updates completed.";
?>
