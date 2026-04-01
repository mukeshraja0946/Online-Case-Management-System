<?php
require_once 'config/db.php';
$result = $conn->query("SHOW COLUMNS FROM cases");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "<br>";
}
?>
