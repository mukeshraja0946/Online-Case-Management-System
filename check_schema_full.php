<?php
require_once 'config/db.php';
$result = $conn->query("DESCRIBE cases");
$columns = [];
while($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}
echo implode(", ", $columns);
?>
