<?php
require 'd:/Xampp/htdocs/OCMS/config/db.php';
$res = $conn->query('DESCRIBE cases');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
echo "\n--- SAMPLE DATA ---\n";
$res = $conn->query("SELECT id, status, created_at, updated_at FROM cases WHERE status IN ('Approved', 'Rejected') LIMIT 5");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
