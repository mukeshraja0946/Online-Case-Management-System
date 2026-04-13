<?php
require_once 'config/db.php';

// Check if cases_old already exists
$check_old = $conn->query("SHOW TABLES LIKE 'cases_old'");
if ($check_old->num_rows == 0) {
    // Rename current cases table to cases_old
    $conn->query("RENAME TABLE cases TO cases_old");
    echo "Renamed cases to cases_old<br>";
}

// Create new cases table
$create_cases = "CREATE TABLE IF NOT EXISTS cases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_by_staff INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_staff) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($create_cases)) {
    echo "Table 'cases' created successfully.<br>";
} else {
    echo "Error creating table 'cases': " . $conn->error . "<br>";
}

// Create case_submissions table
$create_submissions = "CREATE TABLE IF NOT EXISTS case_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id INT NOT NULL,
    student_id INT NOT NULL,
    reason TEXT NOT NULL,
    file VARCHAR(255) DEFAULT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($create_submissions)) {
    echo "Table 'case_submissions' created successfully.<br>";
} else {
    echo "Error creating table 'case_submissions': " . $conn->error . "<br>";
}
?>
