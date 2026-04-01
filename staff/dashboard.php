<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit();
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

// Fetch Global Stats
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN DATE(created_at) = CURRENT_DATE() THEN 1 ELSE 0 END) as today
FROM cases WHERE deleted_by_staff = 0";

$result = $conn->query($sql_stats);
$stats = $result->fetch_assoc();

// Fetch Latest Pending Cases (Added student name joining)
$sql_pending = "SELECT c.*, u.name as student_name 
                FROM cases c 
                JOIN users u ON c.student_id = u.id 
                WHERE c.status = 'Pending' AND c.deleted_by_staff = 0 
                ORDER BY c.created_at ASC LIMIT 5";
$pending_cases = $conn->query($sql_pending);

// Fetch Monthly Trends (Last 6 Months) - MOVED TO AJAX
// The chart data is now fetched via get_cases_by_filter.php to match the student dashboard

// Resolution Performance
$avg_resolution_time = "N/A";
$approval_rate = 0;
$total_resolved = $stats['approved'] + $stats['rejected'];

if ($total_resolved > 0) {
    $approval_rate = number_format(($stats['approved'] / $total_resolved) * 100, 2);
    
    // Calculate Last Response Time (Most recent processed case)
    $sql_res_time = "SELECT TIMESTAMPDIFF(SECOND, created_at, updated_at) as last_seconds 
                    FROM cases 
                    WHERE status IN ('Approved', 'Rejected') 
                    AND updated_at IS NOT NULL 
                    AND updated_at > created_at
                    ORDER BY updated_at DESC LIMIT 1";
    $res_time_result = $conn->query($sql_res_time);
    
    if ($res_time_result && $row = $res_time_result->fetch_assoc()) {
        $seconds = $row['last_seconds'];
        if ($seconds) {
            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);
            $minutes = floor(($seconds % 3600) / 60);

            if ($days > 0) {
                $avg_resolution_time = $days . "d " . $hours . "h";
            } elseif ($hours > 0) {
                $avg_resolution_time = $hours . "h " . $minutes . "m";
            } else {
                $avg_resolution_time = $minutes . "m";
            }
        }
    }
}

// Fetch Platform-wide Activity (Last 5 updates system-wide)
$sql_platform_activity = "SELECT c.id, c.case_type, c.status, c.created_at, c.updated_at, u.name as student_name 
                        FROM cases c 
                        JOIN users u ON c.student_id = u.id 
                        WHERE c.deleted_by_staff = 0 
                        ORDER BY c.updated_at DESC LIMIT 5";
$platform_activities = $conn->query($sql_platform_activity);

// Fetch Global Last Update
$sql_last_global = "SELECT MAX(updated_at) as last_activity FROM cases WHERE deleted_by_staff = 0";
$last_global_res = $conn->query($sql_last_global);
$last_global = ($last_global_res && $row = $last_global_res->fetch_assoc()) ? $row['last_activity'] : null;
$last_global_str = $last_global ? date('d M, H:i', strtotime($last_global)) : "No Recent Activity";

// Helper function for time ago (Staff version)
if (!function_exists('get_platform_time_ago')) {
    function get_platform_time_ago($timestamp) {
        $time_ago = strtotime($timestamp);
        $current_time = time();
        $time_difference = $current_time - $time_ago;
        $seconds = $time_difference;
        $minutes      = round($seconds / 60);           // value 60 is seconds  
        $hours        = round($seconds / 3600);         //value 3600 is 60 minutes * 60 sec  
        $days         = round($seconds / 86400);        //86400 = 24 * 60 * 60;  
        $weeks        = round($seconds / 604800);       // 7*24*60*60;  
        $months       = round($seconds / 2629440);      //((365+365+365+365+366)/5/12)*24*60*60  
        $years        = round($seconds / 31553280);     //(365+365+365+365+366)/5 * 24 * 60 * 60  
        
        if($seconds <= 60) return "Just Now";
        else if($minutes <= 60) return ($minutes==1) ? "1m ago" : "$minutes m ago";
        else if($hours <= 24) return ($hours==1) ? "1h ago" : "$hours h ago";
        else if($days <= 7) return ($days==1) ? "yesterday" : "$days d ago";
        else if($weeks <= 4.3) return ($weeks==1) ? "1w ago" : "$weeks w ago";
        else if($months <= 12) return ($months==1) ? "1 month ago" : "$months months ago";
        else return ($years==1) ? "1 year ago" : "$years years ago";
    }
}

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $del_stmt = $conn->prepare("UPDATE cases SET deleted_by_staff = 1 WHERE id = ?");
    $del_stmt->bind_param("i", $delete_id);
    if ($del_stmt->execute()) {
        header("Location: dashboard.php?msg=deleted");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS -->
    <link href="../assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Chart.js -->
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
                <a href="dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="received_cases.php"><i class="fas fa-inbox"></i> Received Cases</a>
                <a href="approved_cases.php"><i class="fas fa-check-circle"></i> Approved</a>
                <a href="rejected_cases.php"><i class="fas fa-times-circle"></i> Rejected</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="history.php"><i class="fas fa-history"></i> History</a>
                <a href="../auth/profile.php"><i class="fas fa-cog"></i> Settings</a>
                
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="header-text">
                    <h2>Online Case Management</h2>
                    <p>Welcome back, <?php echo htmlspecialchars($staff_name); ?>! Here's a summary of student cases.</p>
                </div>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="dashboard-search" placeholder="Search student cases...">
                </div>
                
                <div class="user-nav">

                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    
                    <a href="../auth/profile.php?view=1" class="text-decoration-none">
                        <div class="user-profile">
                            <div class="avatar shadow-sm" style="overflow: hidden; background: var(--secondary-color);">
                                <?php if(isset($profile_photo) && $profile_photo): ?>
                                    <?php 
                                        $photo = trim($profile_photo);
                                        $pic_src = (strpos($photo, 'http') === 0) 
                                            ? $photo 
                                            : "../uploads/profile/" . $photo;
                                    ?>
                                    <img src="<?php echo htmlspecialchars($pic_src); ?>" style="width: 100%; height: 100%; object-fit: cover;" referrerpolicy="no-referrer">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($staff_name, 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column text-center" style="line-height: 1.2;">
                                <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);">
                                    <?php echo htmlspecialchars($staff_name); ?>
                                </span>
                                <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                                    <?php echo htmlspecialchars($staff_id_val); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-orange-gradient"><i class="fas fa-folder-open"></i></div>
                    <div class="stat-title">Total Cases</div>
                    <div class="stat-value text-orange"><?php echo $stats['total']; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-blue-gradient"><i class="fas fa-clock"></i></div>
                    <div class="stat-title">New Request</div>
                    <div class="stat-value text-blue"><?php echo $stats['pending']; ?></div>
                </div>
                
                <a href="approved_cases.php" class="stat-card text-decoration-none">
                    <div class="stat-icon bg-green-gradient"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-title">Approved</div>
                    <div class="stat-value text-green"><?php echo $stats['approved']; ?></div>
                </a>
                
                <a href="rejected_cases.php" class="stat-card text-decoration-none">
                    <div class="stat-icon bg-purple-gradient"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-title">Rejected</div>
                    <div class="stat-value text-purple"><?php echo $stats['rejected']; ?></div>
                </a>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #0EA5E9 0%, #38BDF8 100%);"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-title">Today</div>
                    <div class="stat-value text-blue"><?php echo $stats['today']; ?> <span style="font-size: 0.7rem; color: var(--success-color);">Cases</span></div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid mt-4">
                <!-- Main Content Area -->
                <div class="main-content-area">
                    <!-- Monthly Case Trends Chart -->
                    <div class="chart-container mb-4">
                        <div class="chart-header">
                            <div class="d-flex align-items-center gap-3">
                                <h5 class="mb-0">Monthly Case Trends</h5>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="chartFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.75rem; border-radius: 8px;">
                                    Last 6 Months
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="chartFilterDropdown" style="font-size: 0.8rem;">
                                    <li><a class="dropdown-item chart-filter" href="#" data-range="1day">Last 1 Day</a></li>
                                    <li><a class="dropdown-item chart-filter" href="#" data-range="1week">Last 1 Week</a></li>
                                    <li><a class="dropdown-item chart-filter" href="#" data-range="1month">Last 1 Month</a></li>
                                    <li><a class="dropdown-item chart-filter active" href="#" data-range="6months">Last 6 Months</a></li>
                                    <li><a class="dropdown-item chart-filter" href="#" data-range="1year">Last 1 Year</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item chart-filter" href="#" data-range="all">All Time</a></li>
                                </ul>
                            </div>
                        </div>
                        <div style="height: 220px;">
                            <canvas id="monthlyCasesChart"></canvas>
                        </div>
                    </div>

                    <!-- Recent Pending Cases Table -->
                    <div class="table-card">
                        <div class="table-header">
                            <h5>Latest Pending Requests</h5>
                            <a href="received_cases.php" style="font-size: 0.85rem; color: var(--primary-color);">View All</a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr class="text-center">
                                        <th>Student</th>
                                        <th>Case Type</th>
                                        <th>Uploaded File</th>
                                        <th>Description</th>
                                        <th>Updated Date & Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($pending_cases->num_rows > 0): ?>
                                        <?php while($row = $pending_cases->fetch_assoc()): ?>
                                            <tr class="text-center">
                                                <td style="font-weight: 600; color: var(--text-color);">
                                                    <?php echo htmlspecialchars($row['student_name']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['case_type']); ?></td>
                                                <td>
                                                    <?php if ($row['attachment']): ?>
                                                        <a href="../uploads/<?php echo $row['attachment']; ?>" target="_blank" class="link-offset-2" style="font-size: 0.9rem; text-decoration: none; color: #0ea5e9; font-weight: 600;">
                                                            <i class="fas fa-file-download me-1"></i> View
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No File</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td title="<?php echo htmlspecialchars($row['description']); ?>">
                                                    <div class="mx-auto" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        <?php echo htmlspecialchars($row['description']); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo date('M d, h:i A', strtotime($row['incident_date'])); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                                        <?php echo $row['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="received_cases.php" class="text-primary text-decoration-underline" style="font-size: 0.9rem;">Review</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center text-muted">No pending cases</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Content Area -->
                <div class="sidebar-content-area">
                    <!-- Action Card -->
                    <div class="feature-card mb-4" style="height: auto; min-height: 180px;">
                        <h3>Review Pending<br>Cases</h3>
                        <div class="card-label">Quick Processing</div>
                        <a href="received_cases.php" class="feature-btn">Review Now <i class="fas fa-arrow-right ml-2"></i></a>
                        
                        <!-- Decorative icon -->
                        <i class="fas fa-search-plus" style="position: absolute; bottom: -5px; right: -5px; font-size: 3.5rem; opacity: 0.1; transform: rotate(-15deg);"></i>
                    </div>

                    <!-- System Performance -->
                    <div class="activity-card mb-4" style="padding: 24px;">
                        <div class="activity-header d-flex justify-content-between align-items-center mb-4 p-0">
                            <h5 class="m-0" style="font-weight: 700; color: var(--text-color); letter-spacing: -0.5px;">System Performance</h5>
                            <span class="badge rounded-pill bg-success-soft text-success" style="font-size: 0.7rem; font-weight: 700; padding: 5px 12px; letter-spacing: 0.5px; text-transform: uppercase;">
                                Optimal
                            </span>
                        </div>
                        
                        <div class="performance-list d-flex flex-column gap-4">
                            <!-- Overall Stats -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-soft-purple text-purple rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                        <i class="fas fa-check-double" style="font-size: 1rem;"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block mb-1" style="font-size: 0.7rem; font-weight: 500;">Total Resolved</small>
                                        <span style="font-weight: 800; font-size: 1.1rem; color: var(--text-color);"><?php echo $total_resolved; ?></span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block mb-1" style="font-size: 0.7rem; font-weight: 500;">Last Activity</small>
                                    <span style="font-weight: 600; font-size: 0.85rem; color: var(--text-color); opacity: 0.8;"><?php echo $last_global_str; ?></span>
                                </div>
                            </div>

                            <!-- Approval Rate with Progress -->
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-percentage text-green" style="font-size: 0.75rem;"></i>
                                        <small class="text-muted" style="font-size: 0.75rem; font-weight: 600;">System Approval Rate</small>
                                    </div>
                                    <span style="font-weight: 700; font-size: 0.85rem; color: var(--success-color);"><?php echo $approval_rate; ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px; border-radius: 10px; background: rgba(0,0,0,0.05);">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $approval_rate; ?>%; border-radius: 10px;"></div>
                                </div>
                            </div>

                            <!-- Response Time -->
                            <div class="p-3 border rounded-4 d-flex align-items-center gap-3" style="background: rgba(0,0,0,0.01);">
                                <div class="bg-soft-blue text-blue rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="fas fa-bolt" style="font-size: 0.8rem;"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block" style="font-size: 0.65rem; font-weight: 600;">Avg. Response Time</small>
                                    <span style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);"><?php echo $avg_resolution_time; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Platform Activity -->
                    <div class="activity-card">
                        <div class="activity-header mb-4">
                            <h5 class="m-0" style="font-weight: 700; color: var(--text-color); letter-spacing: -0.5px;">Recent Activities</h5>
                        </div>
                        <div class="activity-list">
                            <?php if ($platform_activities && $platform_activities->num_rows > 0): ?>
                                <?php while($act = $platform_activities->fetch_assoc()): ?>
                                    <?php 
                                        $icon = 'fa-file-alt';
                                        $color = 'var(--primary-color)';
                                        $bg = 'rgba(186, 230, 253, 0.2)';
                                        
                                        if($act['status'] == 'Approved') {
                                            $icon = 'fa-check-circle';
                                            $color = 'var(--success-color)';
                                            $bg = 'rgba(51, 198, 159, 0.1)';
                                        } else if($act['status'] == 'Rejected') {
                                            $icon = 'fa-times-circle';
                                            $color = 'var(--danger-color)';
                                            $bg = 'rgba(255, 91, 91, 0.1)';
                                        }
                                        
                                        $activity_type = ($act['created_at'] == $act['updated_at']) ? 'submitted a case' : 'case was '.strtolower($act['status']);
                                        $time_ago = get_platform_time_ago($act['updated_at']);
                                    ?>
                                    <div class="activity-item">
                                        <div class="activity-icon" style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>;">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="activity-info">
                                            <h6 style="font-size: 0.85rem;"><?php echo htmlspecialchars($act['student_name']); ?></h6>
                                            <p style="font-size: 0.7rem; color: var(--text-muted);"><?php echo $activity_type; ?> • <?php echo $time_ago; ?></p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted text-center" style="font-size: 0.85rem;">No activity yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/search.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script>
        // Dynamic Chart implementation for Staff Dashboard
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('monthlyCasesChart').getContext('2d');
            let myChart = null;

            const colors = {
                'Academic': '#60A5FA', // Soft Blue
                'Disciplinary': '#F87171', // Soft Red
                'Hostel': '#34D399', // Soft Green
                'Library': '#FBBF24', // Soft Amber
                'Other': '#A78BFA' // Soft Purple
            };

            function initChart() {
                myChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: []
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'left',
                                labels: {
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    padding: 15,
                                    font: { size: 11, weight: '500' },
                                    color: '#64748b'
                                }
                            },
                        },
                        scales: {
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                grid: { drawBorder: false, color: '#f1f5f9' },
                                ticks: { font: { size: 10 } }
                            },
                            x: {
                                stacked: true,
                                grid: { display: false },
                                ticks: { 
                                    font: { size: 10 },
                                    autoSkip: false,
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        }
                    }
                });
            }

            function fetchChartData(range) {
                fetch(`get_cases_by_filter.php?range=${range}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.labels) {
                            myChart.data.labels = data.labels;
                            myChart.data.datasets = data.datasets.map(ds => ({
                                ...ds,
                                backgroundColor: colors[ds.label] || '#94a3b8',
                                borderColor: 'transparent',
                                borderWidth: 0,
                                borderRadius: 4,
                                barThickness: range === '1day' ? 30 : (range === '1week' ? 60 : (range === '1month' ? 40 : (range === 'all' ? 35 : 45)))
                            }));
                            myChart.update();
                        }
                    })
                    .catch(error => console.error('Error fetching chart data:', error));
            }

            initChart();
            fetchChartData('6months');

            // Handle Filter Changes
            document.querySelectorAll('.chart-filter').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const range = this.getAttribute('data-range');
                    const rangeText = this.innerText;
                    
                    // Update UI
                    document.querySelectorAll('.chart-filter').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    document.getElementById('chartFilterDropdown').innerText = rangeText;

                    // Fetch Data
                    fetchChartData(range);
                });
            });
        });
    </script>
</body>
</html>
