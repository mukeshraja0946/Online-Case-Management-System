<?php
require 'd:/Xampp/htdocs/OCMS/config/db.php';
$res = $conn->query('DESCRIBE cases');
$cols = [];
while($row = $res->fetch_assoc()) {
    $cols[] = $row['Field'];
}
file_put_contents('d:/Xampp/htdocs/OCMS/cases_cols.txt', implode("\n", $cols));
echo "SAVED TO cases_cols.txt";
?>
