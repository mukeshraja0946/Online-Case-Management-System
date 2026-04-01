<?php
require_once 'config/db.php';

$email = 'kumar@gmail.com';
$new_sem = '5';

// Check current value
$sql = "SELECT id, name, semester FROM users WHERE email = '$email'";
$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo "User found: " . $row['name'] . " (ID: " . $row['id'] . ")<br>";
    echo "Current Semester: " . ($row['semester'] ?? "NULL") . "<br>";
    
    // Update
    $update = "UPDATE users SET semester = '$new_sem' WHERE id = " . $row['id'];
    if ($conn->query($update) === TRUE) {
        echo "Updated semester to '$new_sem'.<br>";
    } else {
        echo "Update failed: " . $conn->error . "<br>";
    }
    
    // Check again
    $res2 = $conn->query($sql);
    $row2 = $res2->fetch_assoc();
    echo "New Semester: " . ($row2['semester'] ?? "NULL") . "<br>";
} else {
    echo "User kumar@gmail.com not found.<br>";
    // List all users to see valid emails
    $all = $conn->query("SELECT email FROM users LIMIT 5");
    while($r = $all->fetch_assoc()) { echo $r['email'] . "<br>"; }
}
?>
