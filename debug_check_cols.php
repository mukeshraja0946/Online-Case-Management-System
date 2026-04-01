<?php
require_once 'config/db.php';
$res = $conn->query("SHOW COLUMNS FROM users");
$cols = [];
while($row = $res->fetch_assoc()) {
    $cols[] = $row['Field'];
}
file_put_contents('debug_users_cols.txt', implode("\n", $cols));
echo "DONE";
?>
