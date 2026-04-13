<?php
require_once 'config/db.php';
$dummy = [
    'name' => 'Dummy Test',
    'email' => 'dummy@example.com',
    'password' => password_hash('password123', PASSWORD_BCRYPT),
    'role' => 'student',
    'roll_no' => 'ROLL_TEST',
    'staff_id' => null,
    'department' => 'IT',
    'year' => '1',
    'batch' => '2025',
    'profile_photo' => null
];

$sql = "INSERT INTO users (name, email, password, role, roll_no, staff_id, department, year, batch, profile_photo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ssssssssss", $dummy['name'], $dummy['email'], $dummy['password'], $dummy['role'], $dummy['roll_no'], $dummy['staff_id'], $dummy['department'], $dummy['year'], $dummy['batch'], $dummy['profile_photo']);
if ($stmt->execute()) {
    echo "SUCCESS: Dummy user created.\n";
    $conn->query("DELETE FROM users WHERE email = 'dummy@example.com'");
} else {
    echo "ERROR: " . $stmt->error . "\n";
}
?>
