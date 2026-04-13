<?php
// Automatic Environment Detection
if (php_sapi_name() === 'cli' || (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1'))) {
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

// Session Timeout Injection Logic
function inject_session_timeout() {
    if (session_status() === PHP_SESSION_NONE || !isset($_SESSION['user_id'])) {
        return;
    }
    
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $is_api = strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
    $is_logout = strpos($_SERVER['REQUEST_URI'], 'logout.php') !== false;
    
    if (!$is_ajax && !$is_api && !$is_logout) {
        $base = str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath(__DIR__ . '/..'));
        $base = str_replace('\\', '/', $base);
        $v = time();
        
        echo "\n<script src=\"{$base}/assets/js/session_timeout.js?v={$v}\"></script>\n";
    }
}
register_shutdown_function('inject_session_timeout');
?>
