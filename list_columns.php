<?php
require_once 'config/db.php';
$result = $conn->query("DESCRIBE cases");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
