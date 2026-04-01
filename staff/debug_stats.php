<?php
require_once '../config/db.php';
session_start();

$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
FROM cases WHERE deleted_by_staff = 0 AND deleted_by_student = 0";

$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo "<h3>Debug Stats</h3>";
echo "Total: " . $row['total'] . "<br>";
echo "Pending: " . $row['pending'] . "<br>";
echo "Approved: " . $row['approved'] . "<br>";
echo "Rejected: " . $row['rejected'] . "<br>";
echo "Calculated Resolved: " . ($row['approved'] + $row['rejected']) . "<br>";

echo "<h3>Raw Resolved statuses:</h3>";
$res = $conn->query("SELECT id, status FROM cases WHERE deleted_by_staff = 0 AND deleted_by_student = 0");
while($r = $res->fetch_assoc()) {
    echo "ID: " . $r['id'] . " - Status: " . $r['status'] . "<br>";
}
?>
