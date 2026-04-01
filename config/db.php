<?php
// Automatic Environment Detection
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
    // LOCALHOST XAMPP DETAILS
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "ocms";
} else {
    // INFINITYFREE LIVE DETAILS
    $host = "sql112.infinityfree.com";        
    $user = "if0_41488393";            
    
    // 👇 PUT YOUR REAL JUMBLED PASSWORD IN HERE, NOT Mukesh0904! 👇
    $pass = "Mukesh0904";       
    
    // 👇 MAKE SURE THIS EXACTLY MATCHES YOUR CPANEL DB NAME! 👇
    $dbname = "if0_41488393_ocms";     
}

// Temporary fix to show the REAL error instead of a 500 crash!
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("DATABASE CONNECTION ERROR: " . $conn->connect_error);
}
?>
