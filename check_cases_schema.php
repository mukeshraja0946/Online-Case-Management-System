<?php
require 'd:/Xampp/htdocs/OCMS/config/db.php';
$res = $conn->query('DESCRIBE cases');
$cols = [];
while($row = $res->fetch_assoc()) {
    $cols[] = $row['Field'];
}
echo "COLUMNS: " . implode(", ", $cols) . "\n";

$res = $conn->query("SELECT COUNT(*) as count FROM cases WHERE deleted_by_staff = 0");
$row = $res->fetch_assoc();
echo "TOTAL CASES: " . $row['count'] . "\n";

$res = $conn->query("SELECT status, COUNT(*) as count FROM cases GROUP BY status");
echo "STATUS BREAKDOWN:\n";
while($row = $res->fetch_assoc()) {
    echo "- " . $row['status'] . ": " . $row['count'] . "\n";
}
?>
