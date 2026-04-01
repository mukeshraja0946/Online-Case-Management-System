<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit();
}

$staff_uid = $_SESSION['user_id'];
// Fetch fresh staff data from DB
$u_stmt = $conn->prepare("SELECT name, staff_id, profile_photo FROM users WHERE id = ?");
$u_stmt->bind_param("i", $staff_uid);
$u_stmt->execute();
$u_res = $u_stmt->get_result()->fetch_assoc();

$staff_name = $u_res['name'];
$staff_id = $u_res['staff_id'];
$profile_photo = $u_res['profile_photo'];

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    // Individual delete disabled as per request
}

$sql = "SELECT * FROM cases WHERE status IN ('Approved', 'Rejected') AND staff_visible = 1 AND deleted_by_staff = 0 AND deleted_by_student = 0 ORDER BY created_at ASC";
$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case History - OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h4><img src="../assets/img/ocmslogo.png" alt="Logo" style="height: 75px;"></h4>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Menu</div>
                <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="received_cases.php"><i class="fas fa-inbox"></i> Received Cases</a>
                <a href="approved_cases.php"><i class="fas fa-check-circle"></i> Approved</a>
                <a href="rejected_cases.php"><i class="fas fa-times-circle"></i> Rejected</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="history.php" class="active"><i class="fas fa-history"></i> History</a>
                <a href="../auth/profile.php"><i class="fas fa-cog"></i> Settings</a>
                
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <div class="main_content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="dashboard-search" placeholder="Search history...">
                </div>
                
                <div class="user-nav">

                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    
                    <a href="../auth/profile.php?view=1" class="text-decoration-none">
                        <div class="user-profile">
                            <div class="avatar shadow-sm" style="overflow: hidden; background: var(--secondary-color);">
                                <?php if($profile_photo): ?>
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
                                    <?php echo htmlspecialchars($staff_id ?? ''); ?>
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
                <?php if(isset($success)): ?>
                    <div class="alert alert-success py-2"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger py-2"><?php echo $error; ?></div>
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
                                        <td class="text-nowrap"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['roll_no']); ?></td>
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
                                <tr>
                                    <td colspan="9" class="text-center">No processed cases found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($result->num_rows > 0): ?>
                    <div class="d-flex justify-content-end mt-3">
                        <form action="clear_history.php" method="POST" onsubmit="return confirm('Are you sure you want to delete ALL processed case history? This cannot be undone.');">
                            <button type="submit" class="btn btn-link text-danger text-decoration-underline p-0 border-0"><i class="fas fa-trash-alt me-1"></i>Clear History</button>
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
