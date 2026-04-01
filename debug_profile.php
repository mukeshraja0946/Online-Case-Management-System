<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Please log in first.");
}

$user_id = $_SESSION['user_id'];

// Fetch from DB
$stmt = $conn->prepare("SELECT id, name, email, profile_photo FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo "<h1>Debug Info</h1>";
echo "<h3>Session Data:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>Database Data:</h3>";
echo "<pre>" . print_r($user, true) . "</pre>";

if ($user['profile_photo']) {
    echo "<h3>Profile Photo Analysis:</h3>";
    echo "Value: " . htmlspecialchars($user['profile_photo']) . "<br>";
    echo "Length: " . strlen($user['profile_photo']) . "<br>";
    echo "Starts with http: " . (strpos($user['profile_photo'], 'http') === 0 ? 'Yes' : 'No') . "<br>";
    echo "Image Preview:<br>";
    echo "<img src='" . $user['profile_photo'] . "' alt='Profile Photo' style='width:100px; height:100px; border:1px solid red;'>";
} else {
    echo "<h3>No Profile Photo in DB</h3>";
}
?>
