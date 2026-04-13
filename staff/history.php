<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit();
}

$staff_uid = $_SESSION['user_id'];
$u_stmt = $conn->prepare("SELECT name, staff_id, profile_photo FROM users WHERE id = ?");
$u_stmt->bind_param("i", $staff_uid);
$u_stmt->execute();
$u_res = $u_stmt->get_result()->fetch_assoc();

$staff_name = $u_res['name'];
$staff_id = $u_res['staff_id'];
$profile_photo = $u_res['profile_photo'];

// Fetch all processed submissions
$sql = "SELECT s.*, c.title, u.name as student_name, u.roll_no 
        FROM case_submissions s 
        JOIN cases c ON s.case_id = c.id 
        JOIN users u ON s.student_id = u.id 
        WHERE s.status IN ('Approved', 'Rejected') 
        ORDER BY s.submitted_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission History - OCMS</title>
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
                <a href="dashboard.php" class="menu-item"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="add_case.php" class="menu-item"><i class="fas fa-plus-circle"></i> Create Case</a>
                <a href="received_cases.php" class="menu-item"><i class="fas fa-inbox"></i> Received Submissions</a>
                <a href="approved_cases.php" class="menu-item"><i class="fas fa-check-circle"></i> Approved</a>
                <a href="rejected_cases.php" class="menu-item"><i class="fas fa-times-circle"></i> Rejected</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="history.php" class="menu-item active"><i class="fas fa-history"></i> History</a>
                <a href="../auth/profile.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <div class="main_content">
            <div class="topbar">
                <div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Search history..."></div>
                <div class="user-nav">
                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    <a href="../auth/profile.php?view=1" class="text-decoration-none">
                        <div class="user-profile">
                            <div class="avatar shadow-sm" style="overflow: hidden;">
                                <?php if($profile_photo): ?>
                                    <img src="../uploads/profile/<?php echo htmlspecialchars($profile_photo); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($staff_name, 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column text-center">
                                <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);"><?php echo htmlspecialchars($staff_name); ?></span>
                                <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;"><?php echo htmlspecialchars($staff_id); ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="container-fluid py-4 px-4">
                <h4 class="fw-bold mb-4">Submission History</h4>
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3">Student</th>
                                        <th class="py-3">Case</th>
                                        <th class="py-3">Processed On</th>
                                        <th class="py-3">Status</th>
                                        <th class="py-3">Remark</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['submitted_at'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $row['status'] == 'Approved' ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo $row['status']; ?>
                                                    </span>
                                                </td>
                                                <td class="small italic"><?php echo htmlspecialchars($row['staff_remark'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-5 text-muted">No history found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
