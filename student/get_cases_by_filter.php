<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['user_id'];
$range = $_GET['range'] ?? '6months';

// Security: Validate range input
$allowed_ranges = ['1day', '1week', '1month', '6months', '1year', 'all'];
if (!in_array($range, $allowed_ranges)) {
    $range = '6months';
}

$labels = [];
$case_types = ['Academic', 'Disciplinary', 'Hostel', 'Library', 'Other'];
$datasets = [];
$total_cases = 0;

// Initialize datasets
foreach ($case_types as $type) {
    $datasets[$type] = [
        'label' => $type,
        'data' => [],
        'count' => 0
    ];
}

switch ($range) {
    case '1day':
        // Show hourly data for specified date (default today)
        for ($i = 0; $i < 24; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            $labels[] = date('h A', strtotime("$hour:00"));
            
            foreach ($case_types as $type) {
                $sql = "SELECT COUNT(*) as count FROM cases 
                        WHERE student_id = ? 
                        AND DATE(incident_date) = CURRENT_DATE() 
                        AND HOUR(incident_date) = ? 
                        AND case_type = ? 
                        AND student_my_cases_visible = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iis", $student_id, $i, $type);
                $stmt->execute();
                $count = $stmt->get_result()->fetch_assoc()['count'];
                
                $datasets[$type]['data'][] = (int)$count;
                $datasets[$type]['count'] += (int)$count;
                $total_cases += (int)$count;
            }
        }
        break;

    case '1week':
    case '1month':
        $days = ($range === '1week') ? 6 : 29;
        for ($i = $days; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $display_date = date(($range === '1week' ? 'D' : 'M d'), strtotime($date));
            $labels[] = $display_date;
            
            foreach ($case_types as $type) {
                $sql = "SELECT COUNT(*) as count FROM cases WHERE student_id = ? AND DATE(incident_date) = ? AND case_type = ? AND student_my_cases_visible = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $student_id, $date, $type);
                $stmt->execute();
                $count = $stmt->get_result()->fetch_assoc()['count'];
                
                $datasets[$type]['data'][] = (int)$count;
                $datasets[$type]['count'] += (int)$count;
                $total_cases += (int)$count;
            }
        }
        break;

    case '6months':
    case '1year':
        $months = ($range === '6months') ? 5 : 11;
        for ($i = $months; $i >= 0; $i--) {
            $d = new DateTime('first day of this month');
            $d->modify("-$i months");
            $month_name = $d->format('M');
            $month_val = (int)$d->format('m');
            $year_val = (int)$d->format('Y');
            $labels[] = $month_name;
            
            foreach ($case_types as $type) {
                $sql = "SELECT COUNT(*) as count FROM cases 
                        WHERE student_id = ? 
                        AND MONTH(incident_date) = ? 
                        AND YEAR(incident_date) = ? 
                        AND case_type = ? 
                        AND student_my_cases_visible = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiis", $student_id, $month_val, $year_val, $type);
                $stmt->execute();
                $count = $stmt->get_result()->fetch_assoc()['count'];
                
                $datasets[$type]['data'][] = (int)$count;
                $datasets[$type]['count'] += (int)$count;
                $total_cases += (int)$count;
            }
        }
        break;

    case 'all':
        $sql = "SELECT case_type, incident_date FROM cases 
                WHERE student_id = ? AND student_my_cases_visible = 1 
                ORDER BY incident_date ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        while ($row = $res->fetch_assoc()) {
            $labels[] = date('M d, h:i A', strtotime($row['incident_date']));
            foreach ($case_types as $type) {
                $datasets[$type]['data'][] = ($row['case_type'] === $type) ? 1 : 0;
                if ($row['case_type'] === $type) {
                    $datasets[$type]['count']++;
                }
            }
            $total_cases++;
        }
        break;
}

// Convert to Chart.js dataset format
$final_datasets = [];
foreach ($datasets as $type => $info) {
    if ($info['count'] > 0 || $range !== 'all') { // Keep it clean
        $final_datasets[] = [
            'label' => $info['label'],
            'data' => $info['data']
        ];
    }
}

echo json_encode([
    'labels' => $labels,
    'datasets' => $final_datasets,
    'total' => $total_cases,
    'case_types' => $case_types
]);
