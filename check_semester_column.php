<?php
require_once 'config/db.php';

$result = $conn->query("SHOW COLUMNS FROM users LIKE 'semester'");
if ($result && $result->num_rows > 0) {
    echo "Column 'semester' exists.";
    
    // Check if current user has semester set
    session_start();
    if(isset($_SESSION['user_id'])) {
        $uid = $_SESSION['user_id'];
        $res = $conn->query("SELECT semester FROM users WHERE id = $uid");
        $row = $res->fetch_assoc();
        echo "\nCurrent User Semester: " . ($row['semester'] ? $row['semester'] : "NULL");
    }
} else {
    echo "Column 'semester' DOES NOT exist.";
}
?>
