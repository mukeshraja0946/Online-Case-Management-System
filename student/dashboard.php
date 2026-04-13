<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($conn) || !$conn) {
    die("Error: MySQL database connection is not initialized. Please check your configuration.");
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$student_id = $_SESSION['user_id'];

// Fetch Profile Info
$user_info = ['name' => 'Student', 'roll_no' => '', 'profile_photo' => null];
$sql_user = "SELECT name, profile_photo, roll_no, department, semester, year, batch FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
if ($stmt_user) {
    $stmt_user->bind_param("i", $student_id);
    $stmt_user->execute();
    $res_user = $stmt_user->get_result();
    if ($res_user && $res_user->num_rows > 0) {
        $user_info = $res_user->fetch_assoc();
    }
}

// Fetch Combined Stats
// 1. Available Cases (Staff Created) - Excluding already submitted ones
$available_count = 0;
$sql_available = "SELECT COUNT(*) as available FROM cases c 
                  WHERE NOT EXISTS (SELECT 1 FROM case_submissions s WHERE s.case_id = c.id AND s.student_id = ?)";
$stmt_avail = $conn->prepare($sql_available);
if ($stmt_avail) {
    $stmt_avail->bind_param("i", $student_id);
    $stmt_avail->execute();
    $available_count = $stmt_avail->get_result()->fetch_assoc()['available'] ?? 0;
}

// 2. Student Submission Stats
$stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
$sql_sub_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
FROM case_submissions WHERE student_id = ?";
$stmt_stats = $conn->prepare($sql_sub_stats);
if ($stmt_stats) {
    $stmt_stats->bind_param("i", $student_id);
    $stmt_stats->execute();
    $res_stats = $stmt_stats->get_result();
    if ($res_stats) {
        $stats = $res_stats->fetch_assoc();
    }
}

// Recent Available Cases - Excluding already submitted ones
$recent_available_list = [];
$sql_recent_avail = "SELECT c.*, u.name as staff_name, s.id as submission_id 
                     FROM cases c 
                     JOIN users u ON c.created_by_staff = u.id 
                     LEFT JOIN case_submissions s ON c.id = s.case_id AND s.student_id = ?
                     ORDER BY c.created_at DESC LIMIT 3";
$stmt_recent_avail = $conn->prepare($sql_recent_avail);
if ($stmt_recent_avail) {
    $stmt_recent_avail->bind_param("i", $student_id);
    $stmt_recent_avail->execute();
    $recent_avail_res = $stmt_recent_avail->get_result();
    if ($recent_avail_res) {
        while($row = $recent_avail_res->fetch_assoc()) {
            $recent_available_list[] = $row;
        }
    }
}

// Initial Categorical Analytics (New)
$labels = []; $data = [];
$cat_sql = "SELECT c.case_type, COUNT(s.id) as total 
           FROM case_submissions s 
           JOIN cases c ON s.case_id = c.id 
           WHERE s.student_id = ? 
           GROUP BY c.case_type";
$stmt_cat = $conn->prepare($cat_sql);
if ($stmt_cat) {
    $stmt_cat->bind_param("i", $student_id); $stmt_cat->execute();
    $res_cat = $stmt_cat->get_result();
    while($row = $res_cat->fetch_assoc()) {
        $labels[] = $row['case_type'];
        $data[] = (int)$row['total'];
    }
}
// If empty, fetch all types anyway
if(empty($labels)) {
    $res_all_types = $conn->query("SELECT type_name FROM case_types LIMIT 5");
    while($row = $res_all_types->fetch_assoc()) { $labels[] = $row['type_name']; $data[] = 0; }
}

// Recent Submissions (Rest of your original code...)
$recent_submissions_list = [];
$sql_recent_subs = "SELECT s.*, c.title FROM case_submissions s JOIN cases c ON s.case_id = c.id WHERE s.student_id = ? ORDER BY s.submitted_at DESC LIMIT 5";
$stmt_recent = $conn->prepare($sql_recent_subs);
if ($stmt_recent) {
    $stmt_recent->bind_param("i", $student_id);
    $stmt_recent->execute();
    $res_recent = $stmt_recent->get_result();
    if ($res_recent) {
        while($row = $res_recent->fetch_assoc()) {
            $recent_submissions_list[] = $row;
        }
    }
}

function get_time_ago($timestamp) {
    // ... (Keep existing get_time_ago function)
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes = round($seconds / 60);           
    $hours = round($seconds / 3600);         
    $days = round($seconds / 86400);        
    if($seconds <= 60) return "Just Now";
    else if($minutes <= 60) return ($minutes==1) ? "1m ago" : "$minutes m ago";
    else if($hours <= 24) return ($hours==1) ? "1h ago" : "$hours h ago";
    else return ($days==1) ? "yesterday" : "$days d ago";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4><img src="../assets/img/ocmslogo.png" alt="Logo" style="height: 75px;"></h4>
            </div>
            <div class="sidebar-menu">
                <div class="menu-label">Menu</div>
                <a href="dashboard.php" class="menu-item active"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="available_cases.php" class="menu-item"><i class="fas fa-list"></i> Available Cases</a>
                <a href="my_cases.php" class="menu-item"><i class="fas fa-file-alt"></i> My Submissions</a>
                <a href="approved_cases.php" class="menu-item"><i class="fas fa-check-circle"></i> Approved Submissions</a>
                <a href="rejected_cases.php" class="menu-item"><i class="fas fa-times-circle"></i> Rejected Submissions</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="case_history.php" class="menu-item"><i class="fas fa-history"></i> History</a>
                <a href="../auth/profile.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <div class="topbar">
                <div class="header-text">
                    <h2>Welcome, <?php echo htmlspecialchars($user_info['name']); ?></h2>
                    <p>Track your submitted cases and available tasks.</p>
                </div>
                <div class="user-nav">
                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    <a href="../auth/profile.php?view=1" class="text-decoration-none">
                        <div class="user-profile">
                            <div class="avatar shadow-sm" style="overflow: hidden; position: relative;">
                                <?php 
                                $student_photo_path = !empty($user_info['profile_photo']) ? "../uploads/profile/" . $user_info['profile_photo'] : null;
                                if($student_photo_path && file_exists(__DIR__ . "/../uploads/profile/" . $user_info['profile_photo'])): ?>
                                    <img src="<?php echo $student_photo_path; ?>?v=<?php echo time(); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-blue-gradient text-white fw-bold" style="font-size: 1.2rem;">
                                        <?php echo strtoupper(substr($user_info['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column text-center">
                                <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);"><?php echo htmlspecialchars($user_info['name']); ?></span>
                                <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;"><?php echo htmlspecialchars($user_info['roll_no']); ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-blue-gradient"><i class="fas fa-list-check"></i></div>
                    <div class="stat-title">Available Cases</div>
                    <div class="stat-value text-blue"><?php echo $available_count; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-orange-gradient"><i class="fas fa-clock"></i></div>
                    <div class="stat-title">Pending Subs</div>
                    <div class="stat-value text-orange"><?php echo $stats['pending'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-green-gradient"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-title">Approved</div>
                    <div class="stat-value text-green"><?php echo $stats['approved'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-purple-gradient"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-title">Rejected</div>
                    <div class="stat-value text-purple"><?php echo $stats['rejected'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-teal-gradient"><i class="fas fa-folder-open"></i></div>
                    <div class="stat-title">Total Submissions</div>
                    <div class="stat-value text-teal"><?php echo $stats['total'] ?? 0; ?></div>
                </div>
            </div>
            
            <!-- Left and Right Layout -->
            <div class="row mt-4 px-3 align-items-start">
                <!-- Left Side: Graph and Case Feeds -->
                <div class="col-lg-8">
                    <!-- Analytics Card -->
                    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; background: #ffffff; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                             <h6 class="fw-bold mb-0" style="color: #1e293b; font-size: 1.1rem;">Submission Analytics</h6>
                             <div class="d-flex align-items-center gap-2">
                                 <div class="filter-group d-flex bg-light p-1 rounded-3" style="border: 1px solid #e2e8f0;">
                                     <select id="statusFilter" class="form-select form-select-sm border-0 bg-transparent py-1" style="width: auto; min-width: 110px; font-size: 0.8rem; box-shadow: none; cursor:pointer;">
                                         <option value="all">All Status</option>
                                         <option value="Pending">Pending</option>
                                         <option value="Approved">Approved</option>
                                         <option value="Rejected">Rejected</option>
                                     </select>
                                     <div class="vr mx-2 my-1" style="opacity: 0.1;"></div>
                                     <select id="timeRangeFilter" class="form-select form-select-sm border-0 bg-transparent py-1" style="width: auto; min-width: 110px; font-size: 0.8rem; box-shadow: none; cursor:pointer;">
                                         <option value="day">Today</option>
                                         <option value="week">This Week</option>
                                         <option value="month" selected>This Month</option>
                                         <option value="year">Yearly</option>
                                         <option value="all">All Time</option>
                                     </select>
                                 </div>
                             </div>
                        </div>
                        <div class="card-body px-4 pb-4 pt-1">
                            <div style="height: 240px; position: relative; width: 100%;">
                                <canvas id="submissionChart" height="100" style="height: 240px !important; width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- New Column for Available Cases Preview -->
                    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; background: #f8fafc;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4">New Available Cases</h5>
                            <div class="row">
                                <?php if(!empty($recent_available_list)): ?>
                                    <?php foreach($recent_available_list as $c): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="p-3 bg-white border rounded-3 h-100 shadow-sm">
                                                <h6 class="fw-bold mb-2 text-truncate"><?php echo htmlspecialchars($c['title']); ?></h6>
                                                <p class="small text-muted mb-3" style="font-size: 0.75rem; line-height: 1.3; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                                    <?php echo htmlspecialchars($c['description']); ?>
                                                </p>
                                                <?php if(!empty($c['submission_id'])): ?>
                                                    <button class="btn btn-sm btn-light w-100 py-2 fw-bold text-success" style="font-size: 0.7rem;" disabled>Responded</button>
                                                <?php else: ?>
                                                    <a href="submit_case.php?case_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-primary w-100 py-2" style="font-size: 0.7rem; font-weight: 700;">Respond</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted small text-center w-100">No cases available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="table-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">My Recent Submissions</h5>
                            <a href="my_cases.php" class="btn btn-sm btn-outline-primary px-3">View History</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Case Title</th>
                                        <th>Submitted On</th>
                                        <th>File</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_submissions_list)): ?>
                                        <?php foreach($recent_submissions_list as $row): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars($row['title']); ?></td>
                                                <td><?php echo date('M d, h:i A', strtotime($row['submitted_at'])); ?></td>
                                                <td>
                                                    <?php if($row['file']): ?>
                                                        <a href="../uploads/<?php echo $row['file']; ?>" target="_blank" class="text-primary"><i class="fas fa-file"></i></a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center text-muted py-4">No submissions yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Profile Details -->
                <div class="col-lg-4">
                    <div class="activity-card p-3 shadow-sm border-0" style="border-radius: 12px; margin-bottom: 15px;">
                        <h6 class="fw-bold mb-3 px-1">Profile Details</h6>
                        <div class="student-info-sidebar text-center">
                             <div class="avatar mx-auto mb-2" style="width: 70px; height: 70px; overflow: hidden; border: 3px solid #f0f9ff; box-shadow: 0 4px 10px rgba(0,0,0,0.05); position: relative;">
                                <?php 
                                $photo_path = !empty($user_info['profile_photo']) ? "../uploads/profile/" . $user_info['profile_photo'] : null;
                                if($photo_path && file_exists(__DIR__ . "/../uploads/profile/" . $user_info['profile_photo'])): ?>
                                    <img src="<?php echo $photo_path; ?>?v=<?php echo time(); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-blue-gradient text-white fw-bold" style="font-size: 1.5rem;">
                                        <?php echo strtoupper(substr($user_info['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h6 class="fw-bold mb-0" style="font-size: 1rem; color: #1e293b;"><?php echo htmlspecialchars($user_info['name']); ?></h6>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars($user_info['roll_no']); ?></p>
                            
                            <div class="profile-metadata-grid">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="p-2 border rounded-2 bg-light-soft">
                                            <small class="text-muted d-block" style="font-size: 0.6rem; text-transform: uppercase;">Dept</small>
                                            <strong style="font-size: 0.8rem; color: #334155;"><?php echo !empty($user_info['department']) ? htmlspecialchars($user_info['department']) : 'IT'; ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 border rounded-2 bg-light-soft">
                                            <small class="text-muted d-block" style="font-size: 0.6rem; text-transform: uppercase;">Sem</small>
                                            <strong style="font-size: 0.8rem; color: #334155;"><?php echo !empty($user_info['semester']) ? htmlspecialchars($user_info['semester']) : 'VI'; ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 border rounded-2 bg-light-soft">
                                            <small class="text-muted d-block" style="font-size: 0.6rem; text-transform: uppercase;">Year</small>
                                            <strong style="font-size: 0.8rem; color: #334155;"><?php echo !empty($user_info['year']) ? htmlspecialchars($user_info['year']) : '3'; ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 border rounded-2 bg-light-soft">
                                            <small class="text-muted d-block" style="font-size: 0.6rem; text-transform: uppercase;">Batch</small>
                                            <strong style="font-size: 0.8rem; color: #334155;"><?php echo !empty($user_info['batch']) ? htmlspecialchars($user_info['batch']) : '2023-27'; ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <style>
                        .bg-light-soft { background-color: #f8fafc; }
                    </style>
                    <div class="feature-card p-3" style="height: auto; min-width: 0;">
                         <h6 class="fw-bold mb-2">Need Help?</h6>
                         <p class="small opacity-75 mb-3" style="font-size: 0.75rem;">Check available cases or contact your staff coordinator.</p>
                         <a href="available_cases.php" class="btn btn-sm btn-light w-100 fw-bold" style="border-radius: 8px;">Browse Cases</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        // PASS DATA TO JAVASCRIPT (Step 4)
        let labels = <?php echo json_encode($labels); ?>;
        let data = <?php echo json_encode($data); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const chartCanvas = document.getElementById('submissionChart');
            if(!chartCanvas) return;
            const ctx = chartCanvas.getContext('2d');
            let myChart;

            // Step 5: CREATE BAR CHART
            myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'My Submissions',
                        data: data,
                        backgroundColor: ['#ff4d4d','#33cc33','#3399ff','#ffcc00','#ff6600','#7e22ce','#ec4899','#06b6d4'],
                        borderRadius: 6,
                        barThickness: 'flex',
                        maxBarThickness: 50
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 1200, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: '#1e293b', padding: 10, displayColors: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            suggestedMax: 5,
                            grid: { color: '#f1f5f9', drawBorder: false },
                            ticks: { stepSize: 1, color: '#94a3b8' },
                            border: { color: '#7e22ce', width: 2 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#475569', font: { family: 'Outfit', weight: '600' } },
                            border: { color: '#7e22ce', width: 2 }
                        }
                    }
                }
            });

            // Keep AJAX functionality for filter (Customized)
            function updateChart(range, status) {
                chartCanvas.style.opacity = '0.5';
                fetch(`get_cases_by_filter.php?range=${range}&status=${status}`)
                    .then(res => res.json())
                    .then(data => {
                        chartCanvas.style.opacity = '1';
                        if (myChart) {
                            myChart.data.labels = data.labels;
                            myChart.data.datasets[0].data = data.data;
                            myChart.data.datasets[0].backgroundColor = data.colors;
                            myChart.data.datasets[0].hoverBackgroundColor = data.colors;
                            myChart.options.scales.y.suggestedMax = Math.max(...data.data, 5);
                            myChart.update();
                        }
                    })
                    .catch(e => {
                        console.error('Chart Load Failed:', e);
                        chartCanvas.style.opacity = '1';
                    });
            }

            const tFilter = document.getElementById('timeRangeFilter');
            const sFilter = document.getElementById('statusFilter');

            if(tFilter && sFilter) {
                tFilter.addEventListener('change', () => updateChart(tFilter.value, sFilter.value));
                sFilter.addEventListener('change', () => updateChart(tFilter.value, sFilter.value));
            }
        });
    </script>
</body>
</html>
