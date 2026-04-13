<?php
ob_start();
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    ob_clean();
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['user_id'];
$range = $_GET['range'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// 1. Fetch all Categories from case_types table
$types_res = $conn->query("SELECT type_name FROM case_types ORDER BY type_name");
$categories = [];
while($row = $types_res->fetch_assoc()) {
    $categories[] = $row['type_name'];
}

if (empty($categories)) {
    $categories = ['Academic', 'Hostel', 'Discipline', 'Placement', 'General'];
}

$labels = [];
$counts = [];
$vibrant_colors = [
    '#ff4d4d', // Red
    '#33cc33', // Green
    '#3399ff', // Blue
    '#ffcc00', // Yellow
    '#ff6600', // Orange
    '#7e22ce', // Purple
    '#ec4899', // Pink
    '#06b6d4'  // Cyan
];

$where_clauses = ["s.student_id = ?"];
$params = [$student_id];
$types_str = "i";

// Time filtering
if ($range === 'day') {
    $where_clauses[] = "DATE(s.submitted_at) = CURDATE()";
} elseif ($range === 'week') {
    $where_clauses[] = "s.submitted_at >= DATE_SUB(Now(), INTERVAL 1 WEEK)";
} elseif ($range === 'month') {
    $where_clauses[] = "s.submitted_at >= DATE_SUB(Now(), INTERVAL 1 MONTH)";
} elseif ($range === 'year') {
    $where_clauses[] = "s.submitted_at >= DATE_SUB(Now(), INTERVAL 1 YEAR)";
}

// Status filtering
if ($status_filter !== 'all') {
    $where_clauses[] = "s.status = ?";
    $params[] = $status_filter;
    $types_str .= "s";
}

$where_str = implode(" AND ", $where_clauses);

foreach ($categories as $cat) {
    // Correct logic: Join submissions with cases to filter by category
    $sql = "SELECT COUNT(*) as count 
            FROM case_submissions s 
            JOIN cases c ON s.case_id = c.id 
            WHERE $where_str AND c.case_type = ?";
    
    $stmt = $conn->prepare($sql);
    $all_params = array_merge($params, [$cat]);
    $all_types = $types_str . "s";
    $stmt->bind_param($all_types, ...$all_params);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    
    $labels[] = $cat;
    $counts[] = (int)$res['count'];
}

$out = [
    'labels' => $labels,
    'data' => $counts,
    'colors' => array_slice($vibrant_colors, 0, count($labels)),
    'total' => array_sum($counts)
];

if (ob_get_length()) ob_clean();
echo json_encode($out);
exit();
?>
