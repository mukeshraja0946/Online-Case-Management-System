<?php
require_once 'config/db.php';
$result = $conn->query("DESCRIBE cases");
$cols = "";
while($row = $result->fetch_assoc()) {
    $cols .= $row['Field'] . "\n";
}
file_put_contents('cases_columns.txt', $cols);
?>
