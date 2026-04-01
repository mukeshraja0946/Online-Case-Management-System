<?php
require_once 'config/db.php';
$sql_res_time = "SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_time FROM cases WHERE status IN ('Approved', 'Rejected')";
$res_time_result = $conn->query($sql_res_time);
if (!$res_time_result) {
    echo "Query Error: " . $conn->error;
} else {
    $row = $res_time_result->fetch_assoc();
    print_r($row);
}
?>
