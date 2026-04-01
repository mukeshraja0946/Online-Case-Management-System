<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Ensure admin name is set
$admin_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';

// Fetch Admin Stats
// 1. Total Cases
$sql_total_cases = "SELECT COUNT(*) as total FROM cases";
$result_total = $conn->query($sql_total_cases);
$total_cases = $result_total->fetch_assoc()['total'];

// 2. Pending Cases
$sql_pending = "SELECT COUNT(*) as pending FROM cases WHERE status = 'Pending'";
$result_pending = $conn->query($sql_pending);
$pending_cases_count = $result_pending->fetch_assoc()['pending'];

// 3. Total Students
$sql_students = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
$result_students = $conn->query($sql_students);
$total_students = $result_students->fetch_assoc()['total'];

// 4. Total Staff
$sql_staff = "SELECT COUNT(*) as total FROM users WHERE role = 'staff'";
$result_staff = $conn->query($sql_staff);
$total_staff = $result_staff->fetch_assoc()['total'];

// 5. Resolved Cases (Approved + Rejected)
$sql_resolved = "SELECT COUNT(*) as total FROM cases WHERE status IN ('Approved', 'Rejected')";
$result_resolved = $conn->query($sql_resolved);
$total_resolved = $result_resolved->fetch_assoc()['total'];

// PHP logic for performance metrics removed

// Fetch Recent Cases (Limit 5)
$sql_recent = "SELECT c.*, u.name as student_name, u.email 
               FROM cases c 
               JOIN users u ON c.student_id = u.id 
               ORDER BY c.created_at DESC LIMIT 5";
$recent_cases = $conn->query($sql_recent);

// Fetch Latest Login Times
$recent_logins = [];

// Fetch up to 4 most recent logins (ONLY Student and Staff)
$res_logins = $conn->query("SELECT name, role, roll_no, staff_id, last_login FROM users WHERE role IN ('student', 'staff') AND last_login IS NOT NULL ORDER BY last_login DESC LIMIT 4");

if (!$res_logins && strpos($conn->error, 'Unknown column \'last_login\'') !== false) {
    // Auto-migration if column is missing
    $alterSql = "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL";
    if ($conn->query($alterSql)) {
        // Success! Retry the query
        $res_logins = $conn->query("SELECT name, role, roll_no, staff_id, last_login FROM users WHERE last_login IS NOT NULL ORDER BY last_login DESC LIMIT 4");
    }
}

if ($res_logins) {
    while ($row = $res_logins->fetch_assoc()) {
        $recent_logins[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - OCMS</title>
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
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .stat-card.admin-card {
            min-height: 140px;
        }
        .admin-sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4><img src="../assets/img/ocmslogo.png" alt="Logo" style="height: 75px;"></h4>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Admin Menu</div>
                <a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="bulk_create_users.php"><i class="fas fa-user-plus"></i> Create Users</a>
                <a href="users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="cases.php"><i class="fas fa-folder-open"></i> All Cases</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="header-text">
                    <h2>Admin Dashboard</h2>
                    <p>Welcome back, Admin! Overview of system performance.</p>
                </div>
                
                <div class="user-nav ms-auto">
                    <div class="user-profile">
                        <div class="avatar shadow-sm bg-primary text-white d-flex align-items-center justify-content-center">
                            A
                        </div>
                        <div class="d-flex flex-column text-center" style="line-height: 1.2;">
                            <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);">
                                <?php echo htmlspecialchars($admin_name); ?>
                            </span>
                            <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                                Administrator
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="dashboard-grid">
                <div class="stat-card admin-card">
                    <div class="stat-icon bg-blue-gradient"><i class="fas fa-folder"></i></div>
                    <div class="stat-title">Total Cases</div>
                    <div class="stat-value text-blue"><?php echo $total_cases; ?></div>
                </div>
                
                <div class="stat-card admin-card">
                    <div class="stat-icon bg-orange-gradient"><i class="fas fa-clock"></i></div>
                    <div class="stat-title">Pending Cases</div>
                    <div class="stat-value text-orange"><?php echo $pending_cases_count; ?></div>
                </div>

                <div class="stat-card admin-card">
                    <div class="stat-icon bg-teal-gradient"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-title">Resolved Cases</div>
                    <div class="stat-value text-teal"><?php echo $total_resolved; ?></div>
                </div>
                
                <div class="stat-card admin-card">
                    <div class="stat-icon bg-green-gradient"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-title">Total Students</div>
                    <div class="stat-value text-green"><?php echo $total_students; ?></div>
                </div>
                
                <div class="stat-card admin-card">
                    <div class="stat-icon bg-purple-gradient"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="stat-title">Total Staff</div>
                    <div class="stat-value text-purple"><?php echo $total_staff; ?></div>
                </div>
            </div>

            <!-- Main Content Layout -->
            <div class="row mt-4">
                <!-- Left Column (Main Data) -->
                <div class="col-lg-9">
                    
                    <!-- Charts & Lists Row -->
                    <div class="row mb-4">
                        <!-- Department Stats -->
                        <div class="col-md-5 mb-4 mb-md-0">
                            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                                <div class="card-header bg-white border-0 py-3" style="border-radius: 15px 15px 0 0;">
                                    <h5 class="card-title fw-bold m-0"><i class="fas fa-university me-2 text-primary"></i>Departments</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Fetch Department Stats
                                    $sql_dept = "SELECT department, COUNT(*) as count FROM users WHERE role = 'student' AND department IS NOT NULL AND department != '' GROUP BY department ORDER BY count DESC LIMIT 5";
                                    $res_dept = $conn->query($sql_dept);
                                    $total_students_for_calc = $total_students > 0 ? $total_students : 1;
                                    ?>
                                    
                                    <?php if ($res_dept->num_rows > 0): ?>
                                        <div class="d-flex flex-column gap-3">
                                            <?php while($row = $res_dept->fetch_assoc()): 
                                                $percent = ($row['count'] / $total_students_for_calc) * 100;
                                            ?>
                                                <div>
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="fw-medium text-dark"><?php echo htmlspecialchars($row['department']); ?></span>
                                                        <span class="text-muted small"><?php echo $row['count']; ?></span>
                                                    </div>
                                                    <div class="progress" style="height: 6px; border-radius: 4px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $percent; ?>%" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center py-4">No data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Users -->
                        <div class="col-md-7">
                            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center" style="border-radius: 15px 15px 0 0;">
                                    <h5 class="card-title fw-bold m-0"><i class="fas fa-user-plus me-2 text-success"></i>New Users</h5>
                                    <a href="users.php" class="btn btn-sm btn-light rounded-pill px-3">View All</a>
                                </div>
                                <div class="card-body p-0">
                                    <?php
                                    // Fetch Recent Users
                                    $sql_users = "SELECT name, email, role, created_at, department FROM users ORDER BY created_at DESC LIMIT 4";
                                    $res_users = $conn->query($sql_users);
                                    ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">User</th>
                                                    <th>Role</th>
                                                    <th>Joined</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($res_users->num_rows > 0): ?>
                                                    <?php while($u = $res_users->fetch_assoc()): ?>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="d-flex flex-column">
                                                                    <span class="fw-semibold text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($u['name']); ?></span>
                                                                    <small class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($u['email']); ?></small>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $u['role'] == 'admin' ? 'danger' : ($u['role'] == 'staff' ? 'warning' : 'info'); ?> bg-opacity-10 text-<?php echo $u['role'] == 'admin' ? 'danger' : ($u['role'] == 'staff' ? 'warning' : 'info'); ?>" style="font-size: 0.7rem;">
                                                                    <?php echo ucfirst($u['role']); ?>
                                                                </span>
                                                            </td>
                                                            <td class="text-muted small"><?php echo $u['created_at'] ? date('M d', strtotime($u['created_at'])) : '-'; ?></td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr><td colspan="3" class="text-center py-4 text-muted">No users found.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Cases Table -->
                    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center" style="border-radius: 15px 15px 0 0;">
                            <h5 class="card-title fw-bold m-0 text-dark">Recent Activity</h5>
                            <a href="cases.php" class="btn btn-sm btn-primary rounded-pill px-3">All Cases</a>
                        </div>
                        <div class="card-body p-0">
                             <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr class="text-center">
                                            <th>Student</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recent_cases->num_rows > 0): ?>
                                            <?php while($row = $recent_cases->fetch_assoc()): ?>
                                                <tr class="text-center">
                                                    <td class="text-start ps-3">
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                                            <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="category-badge cat-<?php echo htmlspecialchars($row['case_type']); ?>">
                                                            <?php echo htmlspecialchars($row['case_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td title="<?php echo htmlspecialchars($row['description']); ?>">
                                                        <div class="mx-auto text-muted small" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                            <?php echo htmlspecialchars($row['description']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $row['status']; ?>">
                                                            <?php echo $row['status']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="cases.php" class="btn btn-sm btn-light text-primary"><i class="fas fa-eye"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center text-muted py-4">No cases found</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Sidebar -->
                <div class="col-lg-3">
                    <!-- System Overview -->
                    <div class="card border-0 shadow-sm mb-3" style="border-radius: 15px;">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-2 text-uppercase small text-muted">System Overview</h6>
                            
                            <div class="d-flex flex-column gap-2">
                                <div class="p-2 px-3 rounded-4 bg-light border-0 shadow-none">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted small fw-bold">User Composition</span>
                                    </div>
                                    <div class="d-flex align-items-end gap-2">
                                        <h4 class="fw-bold m-0"><?php echo $total_students + $total_staff; ?></h4>
                                        <span class="text-muted small">Total Users</span>
                                    </div>
                                    <div class="progress mt-2" style="height: 6px;">
                                        <?php 
                                            $total_u = ($total_students + $total_staff) > 0 ? ($total_students + $total_staff) : 1;
                                            $s_p = ($total_students / $total_u) * 100;
                                        ?>
                                        <div class="progress-bar bg-primary" style="width: <?php echo $s_p; ?>%"></div>
                                        <div class="progress-bar bg-info" style="width: <?php echo 100 - $s_p; ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1" style="font-size: 0.65rem;">
                                        <span class="text-primary fw-bold"><?php echo $total_students; ?> Students</span>
                                        <span class="text-info fw-bold"><?php echo $total_staff; ?> Staff</span>
                                    </div>
                                </div>

                                <div class="p-3 rounded-4 bg-light border-0 shadow-none">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted small fw-bold">Resolution Rate</span>
                                    </div>
                                    <?php 
                                        $solved = $total_resolved > 0 ? $total_resolved : 0;
                                        $total_c = $total_cases > 0 ? $total_cases : 1;
                                        $rate = round(($solved / $total_c) * 100);
                                    ?>
                                    <div class="d-flex align-items-end gap-2">
                                        <h4 class="fw-bold m-0"><?php echo $rate; ?>%</h4>
                                        <span class="text-muted small">Cases Handled</span>
                                    </div>
                                    <div class="progress mt-2" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $rate; ?>%"></div>
                                    </div>
                                </div>

                                <div class="p-3 rounded-4 bg-light border-0 shadow-none">
                                    <span class="text-muted small d-block fw-bold">Pending Cases</span>
                                    <span class="text-primary small fw-bold"><i class="fas fa-clock me-1" style="font-size: 0.5rem;"></i> Active: <?php echo $pending_cases_count; ?> Cases</span>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Login Tracking -->
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 text-uppercase small text-muted">Recent Logins</h6>
                            
                            <?php if (empty($recent_logins)): ?>
                                <p class="text-muted small text-center my-3">No logins yet</p>
                            <?php else: ?>
                                <?php foreach ($recent_logins as $index => $login): ?>
                                    <div class="d-flex align-items-center gap-2 p-1 <?php echo ($index < count($recent_logins) - 1) ? 'border-bottom pb-2 mb-2' : ''; ?>">
                                        <div class="bg-<?php echo ($login['role'] == 'student' ? 'info' : 'warning'); ?> bg-opacity-10 text-<?php echo ($login['role'] == 'student' ? 'info' : 'warning'); ?> rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="fas fa-<?php echo ($login['role'] == 'student' ? 'user-graduate' : 'user-tie'); ?> small"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-0">
                                                <small class="text-muted" style="font-size: 0.65rem; font-weight: 600;">
                                                    <?php echo ucfirst($login['role']); ?> Login
                                                </small>
                                                <span class="badge bg-light text-<?php echo ($login['role'] == 'student' ? 'primary' : 'warning'); ?> border" style="font-size: 0.6rem;">
                                                    <?php echo ($login['role'] == 'student' ? $login['roll_no'] : $login['staff_id']); ?>
                                                </span>
                                            </div>
                                            <div class="d-flex flex-column">
                                                <span style="font-weight: 700; font-size: 0.85rem; color: var(--text-color);">
                                                    <?php echo $login['name']; ?>
                                                </span>
                                                <span class="text-muted" style="font-size: 0.7rem;">
                                                    <i class="far fa-clock me-1"></i><?php echo date('d M, h:i A', strtotime($login['last_login'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
