<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch Profile Info
$sql_user = "SELECT name, profile_photo, phone, department, semester, roll_no FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);

if ($stmt_user) {
    $stmt_user->bind_param("i", $student_id);
    $stmt_user->execute();
    $user_info = $stmt_user->get_result()->fetch_assoc();
} else {
    // Fallback
    $user_info = [
        'name' => $_SESSION['name'],
        'profile_photo' => $_SESSION['profile_photo'] ?? NULL,
        'roll_no' => $_SESSION['roll_no'] ?? 'N/A',
        'phone' => $_SESSION['phone'] ?? 'N/A',
        'department' => $_SESSION['department'] ?? 'IT',
        'semester' => $_SESSION['semester'] ?? 'IV'
    ];
}

// Fetch Stats
$sql_stats = "SELECT 
    SUM(CASE WHEN student_my_cases_visible = 1 THEN 1 ELSE 0 END) as total,
    SUM(CASE WHEN status = 'Pending' AND student_my_cases_visible = 1 THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Approved' AND student_approved_visible = 1 THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' AND student_rejected_visible = 1 THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND student_my_cases_visible = 1 THEN 1 ELSE 0 END) as this_month
FROM cases WHERE student_id = ?";

$stmt = $conn->prepare($sql_stats);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Performance Calculations
$avg_resolution_time = "N/A";
$approval_rate = 0;
$total_resolved = (int)$stats['approved'] + (int)$stats['rejected'];
$completion_rate = 0;

if ($stats['total'] > 0) {
    $completion_rate = number_format(($total_resolved / $stats['total']) * 100, 2);
}

if ($total_resolved > 0) {
    // Avg Resolution Time
    $check_col = $conn->query("SHOW COLUMNS FROM cases LIKE 'updated_at'");
    if ($check_col && $check_col->num_rows > 0) {
        $sql_res_time = "SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_time FROM cases WHERE student_id = ? AND status IN ('Approved', 'Rejected')";
        $stmt_res = $conn->prepare($sql_res_time);
        if ($stmt_res) {
            $stmt_res->bind_param("i", $student_id);
            $stmt_res->execute();
            $res_time_res = $stmt_res->get_result();
            if ($res_time_res && $row = $res_time_res->fetch_assoc()) {
                $res_time = $row['avg_time'];
                if ($res_time) {
                    $days = floor($res_time / 86400);
                    $hours = floor(($res_time % 86400) / 3600);
                    $avg_resolution_time = ($days > 0 ? $days . "d " : "") . $hours . "h";
                }
            }
        }
    }
    
    // Approval Rate
    $approval_rate = number_format(($stats['approved'] / $total_resolved) * 100, 2);
}

// Performance Status/Efficiency Score
if ($total_resolved == 0) {
    $perf_status = "New";
    $perf_color = "text-muted";
} elseif ($approval_rate >= 80 && $completion_rate >= 70) {
    $perf_status = "Excellent";
    $perf_color = "text-success";
} elseif ($approval_rate >= 50) {
    $perf_status = "Good";
    $perf_color = "text-primary";
} else {
    $perf_status = "Average";
    $perf_color = "text-warning";
}

// Fetch Recent Cases (from My Cases list)
$sql_recent = "SELECT * FROM cases WHERE student_id = ? AND student_my_cases_visible = 1 AND is_hidden_dashboard = 0 ORDER BY created_at DESC LIMIT 5";
$stmt_recent = $conn->prepare($sql_recent);
$stmt_recent->bind_param("i", $student_id);
$stmt_recent->execute();
$recent_cases = $stmt_recent->get_result();

// Fetch Monthly Cases Data (Last 6 Months)
$monthly_labels = [];
$monthly_data = [];
for ($i = 5; $i >= 0; $i--) {
    $d = new DateTime('first day of this month');
    $d->modify("-$i months");
    $month_name = $d->format('M');
    $month_val = $d->format('m');
    $year_val = $d->format('Y');
    
    $sql_month = "SELECT COUNT(*) as count FROM cases WHERE student_id = ? AND MONTH(incident_date) = ? AND YEAR(incident_date) = ? AND student_my_cases_visible = 1";
    $stmt_month = $conn->prepare($sql_month);
    $stmt_month->bind_param("iss", $student_id, $month_val, $year_val);
    $stmt_month->execute();
    $month_res = $stmt_month->get_result()->fetch_assoc();
    
    $monthly_labels[] = $month_name;
    $monthly_data[] = $month_res['count'];
}

// Fetch Recent Activity (Status changes or submissions)
$sql_activity = "SELECT id, case_type, status, created_at, updated_at FROM cases WHERE student_id = ? AND student_my_cases_visible = 1 ORDER BY updated_at DESC LIMIT 5";
$stmt_activity = $conn->prepare($sql_activity);

// Fallback if updated_at column is missing
if (!$stmt_activity) {
    $sql_activity = "SELECT id, case_type, status, created_at, created_at as updated_at FROM cases WHERE student_id = ? AND student_my_cases_visible = 1 ORDER BY created_at DESC LIMIT 5";
    $stmt_activity = $conn->prepare($sql_activity);
}

if ($stmt_activity) {
    $stmt_activity->bind_param("i", $student_id);
    $stmt_activity->execute();
    $activities = $stmt_activity->get_result();
} else {
    $activities = false;
}

// Fetch Last Update Time
$sql_last_update = "SELECT MAX(updated_at) as last_upd FROM cases WHERE student_id = ? AND student_my_cases_visible = 1";
$stmt_upd = $conn->prepare($sql_last_update);
$last_update_str = "N/A";
if ($stmt_upd) {
    $stmt_upd->bind_param("i", $student_id);
    $stmt_upd->execute();
    $upd_res = $stmt_upd->get_result()->fetch_assoc();
    if ($upd_res['last_upd']) {
        $last_update_str = date('M d, h:i A', strtotime($upd_res['last_upd']));
    }
}

// Create S.No Mapping for Cases (matches My Cases numbering)
$case_sno_map = [];
$sql_all_ids = "SELECT id FROM cases WHERE student_id = ? AND student_my_cases_visible = 1 ORDER BY created_at ASC";
$stmt_all = $conn->prepare($sql_all_ids);
if ($stmt_all) {
    $stmt_all->bind_param("i", $student_id);
    $stmt_all->execute();
    $all_res = $stmt_all->get_result();
    $rank = 1;
    while($r = $all_res->fetch_assoc()) {
        $case_sno_map[$r['id']] = $rank++;
    }
}

function get_time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes      = round($seconds / 60);           // value 60 is seconds  
    $hours        = round($seconds / 3600);         // value 3600 is 60 minutes * 60 sec  
    $days         = round($seconds / 86400);        // value 86400 is 24 hours * 60 minutes * 60 sec  
    $weeks        = round($seconds / 604800);       // value 604800 is 7 days * 24 hours * 60 minutes * 60 sec  
    $months       = round($seconds / 2629440);      // value 2629440 is ((365+365+366+365)/4/12)*24*60*60  
    $years        = round($seconds / 31553280);     // value 31553280 is ((365+365+366+365)/4)*24*60*60  

    if($seconds <= 60) {
        return "Just now";
    } else if($minutes <= 60) {
        if($minutes == 1) return "1 minute ago";
        else return "$minutes minutes ago";
    } else if($hours <= 24) {
        if($hours == 1) return "1 hour ago";
        else return "$hours hours ago";
    } else if($days <= 7) {
        if($days == 1) return "Yesterday";
        else return "$days days ago";
    } else if($weeks <= 4.3) {
        if($weeks == 1) return "1 week ago";
        else return "$weeks weeks ago";
    } else if($months <= 12) {
        if($months == 1) return "1 month ago";
        else return "$months months ago";
    } else {
        if($years == 1) return "1 year ago";
        else return "$years years ago";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - OCMS</title>
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
                <a href="add_case.php"><i class="fas fa-plus-circle"></i> Add Cases</a>
                <a href="my_cases.php"><i class="fas fa-file-alt"></i> My Cases</a>
                <a href="approved_cases.php"><i class="fas fa-check-circle"></i> Approved Cases</a>
                <a href="rejected_cases.php"><i class="fas fa-times-circle"></i> Rejected Cases</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="case_history.php"><i class="fas fa-history"></i> History</a>
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
                    <p>Welcome back, <?php echo htmlspecialchars($user_info['name']); ?>! Here's a summary of your cases.</p>
                </div>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="dashboard-search" placeholder="Search for cases...">
                </div>
                
                <div class="user-nav">

                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    
                    <a href="../auth/profile.php?view=1" class="text-decoration-none">
                        <div class="user-profile">
                            <div class="avatar shadow-sm" style="overflow: hidden;">
                                <?php if(isset($user_info['profile_photo']) && $user_info['profile_photo']): ?>
                                    <?php 
                                        $photo = trim($user_info['profile_photo']);
                                        $pic_src = (strpos($photo, 'http') === 0) 
                                            ? $photo 
                                            : "../uploads/profile/" . $photo;
                                        $pic_src .= (strpos($pic_src, '?') === false ? '?' : '&') . 'v=' . time();
                                    ?>
                                    <img src="<?php echo htmlspecialchars($pic_src); ?>" style="width: 100%; height: 100%; object-fit: cover;" referrerpolicy="no-referrer">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($user_info['name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column text-center" style="line-height: 1.2;">
                                <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);">
                                    <?php echo htmlspecialchars($user_info['name']); ?>
                                </span>
                                <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                                    <?php echo htmlspecialchars($user_info['roll_no'] ?? $_SESSION['roll_no']); ?>
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
                    <div class="stat-title">Pending</div>
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
                    <div class="stat-title">This Month</div>
                    <div class="stat-value text-blue"><?php echo $stats['this_month']; ?> <span style="font-size: 0.7rem; color: var(--success-color);">Cases</span></div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid mt-4">
                <!-- Main Content Area -->
                <div class="main-content-area">
                    <!-- Monthly Cases Chart -->
                    <div class="chart-container mb-4">
                        <div class="chart-header">
                            <div class="d-flex align-items-center gap-3">
                                <h5 id="chartTitle" class="mb-0">Monthly Cases</h5>
                                <div id="chartTotalContainer" style="font-size: 0.8rem; color: #64748b; font-weight: 500; display: none;">
                                    Total: <span id="chartTotalValue" style="color: var(--primary-color); font-weight: 700;">0</span> Cases
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="chartFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.75rem; border-radius: 8px;">
                                    6 Months
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="chartFilterDropdown" style="font-size: 0.8rem;">
                                    <li><a class="dropdown-item" href="#" data-period="1day">1 Day</a></li>
                                    <li><a class="dropdown-item" href="#" data-period="1week">1 Week</a></li>
                                    <li><a class="dropdown-item" href="#" data-period="1month">1 Month</a></li>
                                    <li><a class="dropdown-item active" href="#" data-period="6months">6 Months</a></li>
                                    <li><a class="dropdown-item" href="#" data-period="1year">1 Year</a></li>
                                    <li><a class="dropdown-item" href="#" data-period="all">All Cases</a></li>
                                </ul>
                            </div>
                        </div>
                        <div style="height: 220px; position: relative;">
                            <div id="chartLoading" class="position-absolute top-50 start-50 translate-middle" style="display: none; z-index: 10;">
                                <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <div id="noDataMessage" class="position-absolute top-50 start-50 translate-middle text-muted" style="display: none; font-size: 0.9rem; font-weight: 500;">
                                No Cases Available
                            </div>
                            <canvas id="monthlyCasesChart" 
                                data-labels='<?php echo json_encode($monthly_labels); ?>'
                                data-values='<?php echo json_encode($monthly_data); ?>'></canvas>
                        </div>
                    </div>

                    <!-- Recent Cases Table -->
                    <div class="table-card">
                        <div class="table-header">
                            <h5>Recent Cases</h5>
                            <a href="my_cases.php" style="font-size: 0.85rem; color: var(--primary-color);">View All</a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr class="text-center">
                                        <th>Case Type</th>
                                        <th>File</th>
                                        <th>Description</th>
                                        <th>Updated Date & Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_cases->num_rows > 0): ?>
                                        <?php while($row = $recent_cases->fetch_assoc()): ?>
                                            <tr class="text-center">
                                                <td style="font-weight: 600; color: var(--text-color);">
                                                    <span class="category-badge cat-<?php echo htmlspecialchars($row['case_type']); ?>">
                                                        <?php echo htmlspecialchars($row['case_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($row['attachment']): ?>
                                                        <a href="../uploads/<?php echo $row['attachment']; ?>" target="_blank" class="link-offset-2" style="font-size: 0.9rem; text-decoration: none; color: #0ea5e9; font-weight: 600;">
                                                            <i class="fas fa-file-alt"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted"><i class="fas fa-times"></i></span>
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
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center text-muted">No recent cases found</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Activity & Profile Sidebar -->
                <div class="sidebar-content-area">
                    <!-- New Request Box (Relocated for alignment) -->
                    <div class="feature-card d-flex flex-column mb-4" style="padding: 18px; min-height: 175px;">
                        <span class="card-label" style="font-size: 0.8rem; margin-bottom: 5px;">Fast Track</span>
                        <h3 style="font-size: 1.35rem; margin-bottom: 8px;">New Request</h3>
                        <p style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 12px; line-height: 1.4;">Submit a new case for immediate review and tracking.</p>
                        <a href="add_case.php" class="feature-btn" style="padding: 10px 20px; font-size: 0.9rem; border-radius: 10px;">Add now</a>
                        
                        <!-- Decorative icon for the box -->
                        <i class="fas fa-paper-plane" style="position: absolute; bottom: -5px; right: -5px; font-size: 3.5rem; opacity: 0.1; transform: rotate(-15deg);"></i>
                    </div>

                    <!-- Profile Info Card -->
                    <div class="activity-card mb-4" style="padding: 20px;">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar shadow-sm" style="width: 60px; height: 60px; overflow: hidden; border: 3px solid var(--primary-color);">
                                <?php if(isset($user_info['profile_photo']) && $user_info['profile_photo']): ?>
                                    <?php 
                                        $photo = trim($user_info['profile_photo']);
                                        $pic_src = (strpos($photo, 'http') === 0) ? $photo : "../uploads/profile/" . $photo;
                                    ?>
                                    <img src="<?php echo htmlspecialchars($pic_src); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($user_info['name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h5 class="mb-0" style="font-weight: 700;"><?php echo htmlspecialchars($user_info['name']); ?></h5>
                                <p class="mb-0 text-muted" style="font-size: 0.85rem;"><?php echo htmlspecialchars($user_info['roll_no'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Approval Rate Bar -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1" style="font-size: 0.75rem; font-weight: 600;">
                                <span>System Approval Rate</span>
                                <span class="text-green"><?php echo $approval_rate; ?>%</span>
                            </div>
                            <div class="progress" style="height: 6px; border-radius: 10px; background: rgba(0,0,0,0.05);">
                                <div class="progress-bar bg-green-gradient" role="progressbar" style="width: <?php echo $approval_rate; ?>%; border-radius: 10px;"></div>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <div class="p-2 border rounded-3 d-flex align-items-center gap-2" style="background: rgba(0,0,0,0.01);">
                                    <i class="fas fa-building text-blue" style="font-size: 0.8rem;"></i>
                                    <div style="line-height: 1;">
                                        <small class="text-muted d-block" style="font-size: 0.65rem;">Dept</small>
                                        <small style="font-size: 0.75rem; font-weight: 700;"><?php echo htmlspecialchars($user_info['department'] ?? 'IT'); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded-3 d-flex align-items-center gap-2" style="background: rgba(0,0,0,0.01);">
                                    <i class="fas fa-graduation-cap text-purple" style="font-size: 0.8rem;"></i>
                                    <div style="line-height: 1;">
                                        <small class="text-muted d-block" style="font-size: 0.65rem;">Sem</small>
                                        <small style="font-size: 0.75rem; font-weight: 700;"><?php echo htmlspecialchars($user_info['semester'] ?? 'IV'); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity Card -->
                    <div class="activity-card mb-4">
                        <div class="activity-header">
                            <h5>Recent Activity</h5>
                        </div>
                        <div class="activity-list">
                            <?php if ($activities && $activities->num_rows > 0): ?>
                                <?php while($act = $activities->fetch_assoc()): ?>
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
                                        
                                        $activity_type = ($act['created_at'] == $act['updated_at']) ? 'submitted' : strtolower($act['status']);
                                        $time_ago = get_time_ago($act['updated_at']);
                                    ?>
                                    <a href="my_cases.php?highlight_id=<?php echo $act['id']; ?>" class="activity-item text-decoration-none">
                                        <div class="activity-icon" style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>;">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="activity-info">
                                            <h6>Case #<?php echo isset($case_sno_map[$act['id']]) ? $case_sno_map[$act['id']] : $act['id']; ?> <?php echo $activity_type; ?></h6>
                                            <p><?php echo $time_ago; ?></p>
                                        </div>
                                        <div class="activity-arrow">
                                            <i class="fas fa-chevron-right"></i>
                                        </div>
                                    </a>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted text-center" style="font-size: 0.85rem;">No activity yet</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Performance Card -->
                    <div class="activity-card" style="padding: 24px;">
                        <div class="activity-header d-flex justify-content-between align-items-center mb-4 p-0">
                            <h5 class="m-0" style="font-weight: 700; color: var(--text-color); letter-spacing: -0.5px;">System Performance</h5>
                            <span class="badge rounded-pill <?php echo str_replace('text-', 'bg-', $perf_color); ?>-soft <?php echo $perf_color; ?>" style="font-size: 0.7rem; font-weight: 700; padding: 5px 12px; letter-spacing: 0.5px; text-transform: uppercase;">
                                <?php echo $perf_status; ?>
                            </span>
                        </div>
                        
                        <!-- Performance Metrics List -->
                        <div class="performance-list d-flex flex-column gap-4">
                            <!-- Overall Stats -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-soft-blue text-blue rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                        <i class="fas fa-check-double" style="font-size: 1rem;"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block mb-1" style="font-size: 0.7rem; font-weight: 500;">Total Resolved</small>
                                        <span style="font-weight: 800; font-size: 1.1rem; color: var(--text-color);"><?php echo $total_resolved; ?></span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block mb-1" style="font-size: 0.7rem; font-weight: 500;">Last Activity</small>
                                    <span style="font-weight: 600; font-size: 0.85rem; color: var(--text-color); opacity: 0.8;"><?php echo $last_update_str; ?></span>
                                </div>
                            </div>

                            <!-- Approval Rate with Progress -->
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-percentage text-green" style="font-size: 0.75rem;"></i>
                                        <small class="text-muted" style="font-size: 0.75rem; font-weight: 600;">% System Approval Rate</small>
                                    </div>
                                    <span style="font-weight: 700; font-size: 0.85rem; color: var(--success-color);"><?php echo $approval_rate; ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px; border-radius: 10px; background: rgba(0,0,0,0.05);">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $approval_rate; ?>%; border-radius: 10px;"></div>
                                </div>
                            </div>

                            <!-- Completion with Progress -->
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-tasks text-primary" style="font-size: 0.75rem;"></i>
                                        <small class="text-muted" style="font-size: 0.75rem; font-weight: 600;">Completion</small>
                                    </div>
                                    <span style="font-weight: 700; font-size: 0.85rem; color: #0284c7;"><?php echo $completion_rate; ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px; border-radius: 10px; background: rgba(0,0,0,0.05);">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $completion_rate; ?>%; border-radius: 10px; background-color: #0284c7 !important;"></div>
                                </div>
                            </div>
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
    <script src="../assets/js/dashboard-charts.js?v=<?php echo time(); ?>"></script>
</body>
</html>
