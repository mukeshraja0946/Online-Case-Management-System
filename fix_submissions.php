<?php
require_once 'config/db.php';

$res = $conn->query("SHOW COLUMNS FROM case_submissions LIKE 'staff_remark'");
if($res->num_rows == 0) {
    if($conn->query("ALTER TABLE case_submissions ADD COLUMN staff_remark TEXT AFTER status")) {
        echo "Successfully added 'staff_remark' column to case_submissions.<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
} else {
    echo "'staff_remark' already exists.<br>";
}
?>
