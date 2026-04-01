<?php
require_once 'config/db.php';
$res = $conn->query("SELECT email FROM users WHERE role = 'student' LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    echo "STUDENT_EMAIL: " . $row['email'];
} else {
    echo "NO_STUDENT_FOUND";
}
?>
