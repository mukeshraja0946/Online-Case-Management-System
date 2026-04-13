<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "Hello from Student Debug Test!<br>";
session_start();
echo "Session started.<br>";
require_once '../config/db.php';
echo "DB Config included.<br>";
if (isset($conn)) {
    echo "DB Connection exists.<br>";
} else {
    echo "DB Connection MISSING!<br>";
}
?>
