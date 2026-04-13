<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
// Fetch fresh student data from DB
$u_stmt = $conn->prepare("SELECT name, roll_no, profile_photo FROM users WHERE id = ?");
$u_stmt->bind_param("i", $student_id);
$u_stmt->execute();
$u_res = $u_stmt->get_result()->fetch_assoc();

$student_name = $u_res['name'];
$roll_no = $u_res['roll_no'];
$profile_photo = $u_res['profile_photo'];

// Fetch processed cases for history. History is independent of other lists.
$sql = "SELECT * FROM cases WHERE student_id = ? AND status != 'Pending' AND is_hidden_history = 0 ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case History - OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
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
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                <a href="dashboard.php" class="menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="available_cases.php" class="menu-item <?php echo ($current_page == 'available_cases.php') ? 'active' : ''; ?>"><i class="fas fa-list"></i> Available Cases</a>
                <a href="my_cases.php" class="menu-item <?php echo ($current_page == 'my_cases.php') ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> My Submissions</a>
                <a href="approved_cases.php" class="menu-item <?php echo ($current_page == 'approved_cases.php') ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i> Approved Submissions</a>
                <a href="rejected_cases.php" class="menu-item <?php echo ($current_page == 'rejected_cases.php') ? 'active' : ''; ?>"><i class="fas fa-times-circle"></i> Rejected Submissions</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="case_history.php" class="menu-item <?php echo ($current_page == 'case_history.php') ? 'active' : ''; ?>"><i class="fas fa-history"></i> History</a>
                <a href="../auth/profile.php" class="menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
                
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="dashboard-search" placeholder="Search my history...">
                </div>
                
                <div class="user-nav">

                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    
                    <a href="../auth/profile.php?view=1" class="text-decoration-none">
                        <div class="user-profile">
                            <div class="avatar shadow-sm" style="overflow: hidden;">
                                <?php if($profile_photo): ?>
                                    <?php 
                                        $photo = trim($profile_photo);
                                        $pic_src = (strpos($photo, 'http') === 0) 
                                            ? $photo 
                                            : "../uploads/profile/" . $photo;
                                    ?>
                                    <img src="<?php echo htmlspecialchars($pic_src); ?>" style="width: 100%; height: 100%; object-fit: cover;" referrerpolicy="no-referrer">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column text-center" style="line-height: 1.2;">
                                <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);">
                                    <?php echo htmlspecialchars($student_name); ?>
                                </span>
                                <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                                    <?php echo htmlspecialchars($roll_no); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="container mt-2">
                <h4 class="mb-2">Case History (Processed)</h4>
                
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <?php if(isset($success_msg)): ?>
                            <div class="alert alert-success py-2"><?php echo $success_msg; ?></div>
                        <?php endif; ?>
                        <?php if(isset($error_msg)): ?>
                            <div class="alert alert-danger py-2"><?php echo $error_msg; ?></div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr class="text-center">
                                        <th>S.No</th>
                                        <th>Student Name</th>
                                        <th>Roll No</th>
                                        <th>Date & Time</th>
                                        <th>Case Type</th>
                                        <th>Description</th>
                                        <th>Attachment</th>
                                        <th>Remark</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                                            <tr class="text-center">
                                                <td><?php echo $i++; ?></td>
                                                <td><?php echo htmlspecialchars($student_name); ?></td>
                                                <td><?php echo htmlspecialchars($row['roll_no'] ?? '-'); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['case_type']); ?></td>
                                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                                <td>
                                                    <?php if (!empty($row['attachment'])): ?>
                                                        <a href="../uploads/<?php echo htmlspecialchars($row['attachment']); ?>" target="_blank"><i class="fas fa-paperclip"></i> View</a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['staff_remark'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php 
                                                        $badge_class = 'bg-secondary';
                                                        if($row['status'] == 'Approved') $badge_class = 'bg-success';
                                                        elseif($row['status'] == 'Rejected') $badge_class = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $row['status']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="9" class="text-center">No processed cases found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($result->num_rows > 0): ?>
                            <div class="d-flex justify-content-end mt-3">
                                <form action="clear_history.php" method="POST" onsubmit="return confirm('Are you sure you want to delete ALL your case history? This cannot be undone.');">
                                    <button type="submit" class="btn btn-link text-danger text-decoration-underline p-0 border-0">
                                        <i class="fas fa-trash-alt me-1"></i>Clear History
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/search.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
