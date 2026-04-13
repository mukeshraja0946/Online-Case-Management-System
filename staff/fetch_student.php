<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db.php';

if(isset($_POST['roll'])){
    $roll = mysqli_real_escape_string($conn, $_POST['roll']);

    // Note: Column in users table is roll_no
    $query = mysqli_query($conn, "SELECT name, department, year FROM users WHERE roll_no='$roll'");

    if($query && mysqli_num_rows($query) > 0){
        echo json_encode(mysqli_fetch_assoc($query));
    } else {
        echo json_encode(["error" => "Not found"]);
    }
}
?>
