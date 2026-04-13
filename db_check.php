<?php
require_once 'config/db.php';
$res = $conn->query("DESCRIBE users");
$cols = [];
while($row = $res->fetch_assoc()) {
    $cols[] = $row['Field'];
}
echo json_encode($cols);
?>
