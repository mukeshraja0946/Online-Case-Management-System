<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($conn) || !$conn) {
    die("Error: MySQL database connection is not initialized. Please check your configuration.");
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Fetch Profile Info
$sql_user = "SELECT name, profile_photo, staff_id FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$staff_name = $_SESSION['name'];
$staff_id_val = $_SESSION['staff_id'] ?? 'N/A';
$profile_photo = $_SESSION['profile_photo'] ?? NULL;

if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user_data = $stmt_user->get_result()->fetch_assoc();
    if ($user_data) {
        $staff_name = $user_data['name'];
        $staff_id_val = $user_data['staff_id'] ?? 'N/A';
        $profile_photo = $user_data['profile_photo'];
    }
}

// Fetch Staff Specific Stats (Cases created by this staff)
$sql_total_cases = "SELECT COUNT(*) as total FROM cases WHERE created_by_staff = ?";
$stmt_total = $conn->prepare($sql_total_cases);
$total_cases_created = 0;
if ($stmt_total) {
    $stmt_total->bind_param("i", $user_id);
    $stmt_total->execute();
    $total_res = $stmt_total->get_result()->fetch_assoc();
    $total_cases_created = $total_res['total'] ?? 0;
}

// Fetch Student Submissions Stats (Global for staff to see all student responses to their cases or all cases)
$sql_sub_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
FROM case_submissions";
$sub_query = $conn->query($sql_sub_stats);
$sub_stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

if ($sub_query) {
    $fetched_stats = $sub_query->fetch_assoc();
    $sub_stats['total'] = (int)($fetched_stats['total'] ?? 0);
    $sub_stats['pending'] = (int)($fetched_stats['pending'] ?? 0);
    $sub_stats['approved'] = (int)($fetched_stats['approved'] ?? 0);
    $sub_stats['rejected'] = (int)($fetched_stats['rejected'] ?? 0);
}

// Fetch Latest Pending Submissions
$sql_pending = "SELECT s.*, c.title, u.name as student_name 
                FROM case_submissions s 
                JOIN cases c ON s.case_id = c.id 
                JOIN users u ON s.student_id = u.id 
                WHERE s.status = 'Pending' 
                ORDER BY s.submitted_at ASC LIMIT 5";
$pending_submissions_query = $conn->query($sql_pending);
$pending_submissions_list = [];
if ($pending_submissions_query) {
    while($row = $pending_submissions_query->fetch_assoc()) {
        $pending_submissions_list[] = $row;
    }
}

// Calculate Platform Stats
$total_resolved = $sub_stats['approved'] + $sub_stats['rejected'];
$approval_rate = ($total_resolved > 0) ? number_format(($sub_stats['approved'] / $total_resolved) * 100, 2) : 0;

// Fetch Recent Activity (Submissions)
$sql_act = "SELECT s.*, c.title, u.name as student_name 
            FROM case_submissions s 
            JOIN cases c ON s.case_id = c.id 
            JOIN users u ON s.student_id = u.id 
            ORDER BY s.submitted_at DESC LIMIT 5";
$activities_query = $conn->query($sql_act);
$activities_list = [];
if ($activities_query) {
    while($row = $activities_query->fetch_assoc()) {
        $activities_list[] = $row;
    }
}

// Initial Categorical Analytics (Global)
$labels = []; $data = [];
$cat_sql = "SELECT c.case_type, COUNT(s.id) as total 
           FROM case_submissions s 
           JOIN cases c ON s.case_id = c.id 
           GROUP BY c.case_type";
$cat_res = $conn->query($cat_sql);
if ($cat_res) {
    while($row = $cat_res->fetch_assoc()) {
        $labels[] = $row['case_type'];
        $data[] = (int)$row['total'];
    }
}
if(empty($labels)) {
    $res_all = $conn->query("SELECT type_name FROM case_types LIMIT 5");
    while($row = $res_all->fetch_assoc()) { $labels[] = $row['type_name']; $data[] = 0; }
}

function get_platform_time_ago($timestamp) {
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
    <title>Staff Dashboard - OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="../assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
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
                <a href="add_case.php" class="menu-item"><i class="fas fa-plus-circle"></i> Create Case</a>
                <a href="received_cases.php" class="menu-item"><i class="fas fa-inbox"></i> Received Submissions</a>
                <a href="approved_cases.php" class="menu-item"><i class="fas fa-check-circle"></i> Approved</a>
                <a href="rejected_cases.php" class="menu-item"><i class="fas fa-times-circle"></i> Rejected</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="history.php" class="menu-item"><i class="fas fa-history"></i> History</a>
                <a href="../auth/profile.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <div class="topbar">
                <div class="header-text">
                    <h2>Staff Dashboard</h2>
                    <p>Manage cases and review student responses.</p>
                </div>
                <div class="user-nav">
                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    <a href="../auth/profile.php?view=1" class="text-decoration-none">
                        <div class="user-profile">
                            <div class="avatar shadow-sm" style="overflow: hidden;">
                                <?php 
                                $staff_photo_path = !empty($profile_photo) ? "../uploads/profile/" . $profile_photo : null;
                                if($staff_photo_path && file_exists(__DIR__ . "/../uploads/profile/" . $profile_photo)): ?>
                                    <img src="<?php echo $staff_photo_path; ?>?v=<?php echo time(); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-blue-gradient text-white fw-bold" style="font-size: 1.2rem;">
                                        <?php echo strtoupper(substr($staff_name, 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column text-center">
                                <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);"><?php echo htmlspecialchars($staff_name); ?></span>
                                <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;"><?php echo htmlspecialchars($staff_id_val); ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-orange-gradient"><i class="fas fa-folder-plus"></i></div>
                    <div class="stat-title">Cases Created</div>
                    <div class="stat-value text-orange"><?php echo $total_cases_created; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-teal-gradient"><i class="fas fa-file-invoice"></i></div>
                    <div class="stat-title">Total Submissions</div>
                    <div class="stat-value text-teal"><?php echo $sub_stats['total']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-blue-gradient"><i class="fas fa-inbox"></i></div>
                    <div class="stat-title">Pending</div>
                    <div class="stat-value text-blue"><?php echo $sub_stats['pending']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-green-gradient"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-title">Approved</div>
                    <div class="stat-value text-green"><?php echo $sub_stats['approved']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-purple-gradient"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-title">Rejected</div>
                    <div class="stat-value text-purple"><?php echo $sub_stats['rejected']; ?></div>
                </div>
            </div>

            <!-- Layout Shift -->
            <div class="row mt-4 px-3 align-items-start">
                <div class="col-lg-8">
                    <!-- Analytics Card -->
                    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; background: #ffffff; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                             <h6 class="fw-bold mb-0" style="color: #1e293b; font-size: 1.1rem;">Overall Submission Trends</h6>
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

                    <div class="table-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Recent Student Submissions</h5>
                            <a href="received_cases.php" class="btn btn-sm btn-outline-primary px-3">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Case Title</th>
                                        <th>Submitted On</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pending_submissions_list)): ?>
                                        <?php foreach ($pending_submissions_list as $row): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                                <td><?php echo date('M d, h:i A', strtotime($row['submitted_at'])); ?></td>
                                                <td><span class="status-badge status-Pending">Pending</span></td>
                                                <td><a href="received_cases.php" class="btn btn-sm btn-primary px-3 rounded-pill">Review</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center text-muted py-4">No pending submissions</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Sidebar Info -->
                <div class="col-lg-4">
                    <div class="activity-card p-4 mb-4">
                        <h5 class="fw-bold mb-4">Live Activity Feed</h5>
                        <div class="activity-list">
                            <?php if (!empty($activities_list)): ?>
                                <?php foreach ($activities_list as $act): ?>
                                    <div class="activity-item d-flex gap-3 mb-4">
                                        <div class="activity-icon bg-light rounded-circle p-2 text-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                                            <i class="fas fa-user-edit" style="font-size: 0.9rem;"></i>
                                        </div>
                                        <div class="activity-info">
                                            <h6 class="mb-1" style="font-size: 0.9rem; font-weight: 700;"><?php echo htmlspecialchars($act['student_name']); ?></h6>
                                            <p class="mb-0 text-muted" style="font-size: 0.8rem; line-height: 1.2;">Responded to "<?php echo htmlspecialchars($act['title']); ?>"</p>
                                            <small class="text-muted" style="font-size: 0.7rem;"><?php echo get_platform_time_ago($act['submitted_at']); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center pt-4">No recent activity detected.</p>
                            <?php endif; ?>
                        </div>
                        <a href="received_cases.php" class="btn btn-light w-100 btn-sm fw-bold mt-2">View History</a>
                    </div>
                    
                    <div class="feature-card p-4">
                        <h6 class="fw-bold mb-2">Need a faster way?</h6>
                        <p class="small opacity-75 mb-3" style="font-size: 0.75rem;">You can quickly add new case categories or student groups in the settings panel.</p>
                        <a href="add_case.php" class="btn btn-sm btn-light w-100 fw-bold rounded-pill">Create New Case</a>
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
            let staffChart;

            // Step 5: CREATE BAR CHART
            staffChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Submissions',
                        data: data,
                        backgroundColor: ['#ff4d4d','#33cc33','#3399ff','#ffcc00','#ff6600','#7e22ce','#128f17','#5856d6'],
                        borderRadius: 6,
                        barThickness: 'flex',
                        maxBarThickness: 60
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

            function updateStaffChart(range, status) {
                chartCanvas.style.opacity = '0.5';
                fetch(`get_cases_by_filter.php?range=${range}&status=${status}`)
                    .then(res => res.json())
                    .then(data => {
                        chartCanvas.style.opacity = '1';
                        if (staffChart) {
                            staffChart.data.labels = data.labels;
                            staffChart.data.datasets[0].data = data.data;
                            staffChart.data.datasets[0].backgroundColor = data.colors;
                            staffChart.data.datasets[0].hoverBackgroundColor = data.colors;
                            staffChart.options.scales.y.suggestedMax = Math.max(...data.data, 5);
                            staffChart.update();
                        }
                    })
                    .catch(e => {
                        console.error('Error loading chart:', e);
                        chartCanvas.style.opacity = '1';
                    });
            }

            const tFilter = document.getElementById('timeRangeFilter');
            const sFilter = document.getElementById('statusFilter');

            if(tFilter && sFilter) {
                tFilter.addEventListener('change', () => updateStaffChart(tFilter.value, sFilter.value));
                sFilter.addEventListener('change', () => updateStaffChart(tFilter.value, sFilter.value));
            }
        });
    </script>
</body>
</html>
