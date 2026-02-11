<?php
require_once 'config/db.php';
$stmt = $conn->query("SHOW COLUMNS FROM cases");
while ($row = $stmt->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
