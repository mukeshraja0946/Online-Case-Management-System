<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

if ($action == 'status_distribution') {
    $sql = "SELECT status, COUNT(*) as count FROM cases GROUP BY status";
    $result = $conn->query($sql);
    
    $data = [
        'labels' => [],
        'data' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['status'];
        $data['data'][] = $row['count'];
    }
    
    echo json_encode($data);

} elseif ($action == 'monthly_trends') {
    // Get last 6 months
    $data = [
        'labels' => [],
        'data' => []
    ];
    
    for ($i = 5; $i >= 0; $i--) {
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end = date('Y-m-t', strtotime("-$i months"));
        $month_label = date('M', strtotime("-$i months"));
        
        $sql = "SELECT COUNT(*) as count FROM cases WHERE created_at BETWEEN '$month_start 00:00:00' AND '$month_end 23:59:59'";
        $result = $conn->query($sql);
        $count = $result->fetch_assoc()['count'];
        
        $data['labels'][] = $month_label;
        $data['data'][] = $count;
    }
    
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>
