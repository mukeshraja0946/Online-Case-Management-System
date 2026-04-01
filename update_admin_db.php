<?php
require_once 'config/db.php';

// 1. Update ENUM definition for 'role' column in 'users' table
$alter_sql = "ALTER TABLE users MODIFY COLUMN role ENUM('student', 'staff', 'admin') NOT NULL";
if ($conn->query($alter_sql) === TRUE) {
    echo "Table 'users' altered successfully to include 'admin' role.<br>";
} else {
    echo "Error altering table: " . $conn->error . "<br>";
}

// 2. Insert default admin user
$admin_email = 'admin@ocms.com';
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_name = 'Administrator';
$admin_role = 'admin';

// Check if admin already exists
$check_sql = "SELECT id FROM users WHERE email = '$admin_email'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    $insert_sql = "INSERT INTO users (name, email, password, role) VALUES ('$admin_name', '$admin_email', '$admin_password', '$admin_role')";
    if ($conn->query($insert_sql) === TRUE) {
        echo "Default admin user created successfully.<br>";
        echo "Email: $admin_email<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Error creating admin user: " . $conn->error . "<br>";
    }
} else {
    echo "Admin user already exists.<br>";
}

$conn->close();
?>
