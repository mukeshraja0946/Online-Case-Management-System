<?php
require_once 'config/db.php';

$name = "Mukesh Raja";
$sql = "SELECT id, name, email, profile_photo, LENGTH(profile_photo) as len FROM users WHERE name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "User Found: " . $user['name'] . "\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Profile Photo Length: " . $user['len'] . "\n";
    echo "Profile Photo Value: " . $user['profile_photo'] . "\n";
    
    // Check column type
    $col_info = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");
    $col = $col_info->fetch_assoc();
    echo "Column Type: " . $col['Type'] . "\n";
} else {
    echo "User '$name' not found.\n";
    // List all users to see what's there
    $res = $conn->query("SELECT name FROM users");
    echo "Available users:\n";
    while ($row = $res->fetch_assoc()) {
        echo "- " . $row['name'] . "\n";
    }
}
?>
