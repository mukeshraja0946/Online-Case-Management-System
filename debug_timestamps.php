<?php
require_once 'd:/Xampp/htdocs/OCMS/config/db.php';
$res = $conn->query("SELECT id, incident_date, created_at, case_type, status FROM cases ORDER BY created_at DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
