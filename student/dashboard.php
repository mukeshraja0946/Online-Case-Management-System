<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch Stats
$sql_stats = "SELECT 
    SUM(CASE WHEN student_my_cases_visible = 1 THEN 1 ELSE 0 END) as total,
    SUM(CASE WHEN status = 'Pending' AND student_my_cases_visible = 1 THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Approved' AND student_approved_visible = 1 THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' AND student_rejected_visible = 1 THEN 1 ELSE 0 END) as rejected
FROM cases WHERE student_id = ?";

$stmt = $conn->prepare($sql_stats);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Fetch Recent Cases (from My Cases list)
$sql_recent = "SELECT * FROM cases WHERE student_id = ? AND student_my_cases_visible = 1 AND is_hidden_dashboard = 0 ORDER BY created_at DESC LIMIT 5";
$stmt_recent = $conn->prepare($sql_recent);
$stmt_recent->bind_param("i", $student_id);
$stmt_recent->execute();
$recent_cases = $stmt_recent->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - OCMS</title>
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
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4><img src="../assets/img/ocms.png" alt="Logo" style="height: 75px;"></h4>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Menu</div>
                <a href="dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="add_case.php"><i class="fas fa-plus-circle"></i> Add Cases</a>
                <a href="my_cases.php"><i class="fas fa-file-alt"></i> My Cases</a>
                <a href="approved_cases.php"><i class="fas fa-check-circle"></i> Approved Cases</a>
                <a href="rejected_cases.php"><i class="fas fa-times-circle"></i> Rejected Cases</a>
                <a href="case_history.php"><i class="fas fa-history"></i> History</a>
                
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="dashboard-search" placeholder="Search for cases...">
                </div>
                
                <div class="user-nav">
                    <button class="nav-icon-btn" id="theme-toggle"><i class="fas fa-moon"></i></button>
                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    
                    <div class="user-profile">
                        <div class="avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                        <div style="font-size: 0.9rem; font-weight: 600; padding-right: 10px;">
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </div>
                    </div>
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
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent Cases Table -->
                <div class="table-card">
                    <div class="table-header">
                        <h5>Recent Cases</h5>
                        <a href="my_cases.php" style="font-size: 0.85rem; color: var(--primary-color);">View All</a>
                    </div>
                    
                    <table class="table align-middle">
                        <thead>
                            <tr class="text-center">
                                <th>Case Type</th>
                                <th>Uploaded File</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_cases->num_rows > 0): ?>
                                <?php while($row = $recent_cases->fetch_assoc()): ?>
                                    <tr class="text-center">
                                        <td style="font-weight: 600; color: var(--text-color);">
                                            <?php echo htmlspecialchars($row['case_type']); ?>
                                        </td>
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
                                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
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

                <!-- Action Card -->
                <div class="feature-card">
                    <h3>Create New<br>Request</h3>
                    <div class="card-label">Fast & Secure</div>
                    <a href="add_case.php" class="feature-btn">Add now <i class="fas fa-arrow-right ml-2"></i></a>
                    
                    <!-- Decorative Circles similar to reference -->
                    <div style="position: absolute; top: -30px; right: -30px; width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%;"></div>
                    <div style="position: absolute; bottom: -20px; left: -20px; width: 80px; height: 80px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/search.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
