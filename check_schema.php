<?php
require_once 'config/db.php';
$result = $conn->query("DESCRIBE users");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}
?>
