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

// Fetch student approved submissions
$sql = "SELECT s.*, c.title as case_title 
        FROM case_submissions s 
        JOIN cases c ON s.case_id = c.id 
        WHERE s.student_id = ? AND s.status = 'Approved' 
        ORDER BY s.submitted_at DESC";
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
    <title>Approved Submissions - OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
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
                <a href="available_cases.php" class="menu-item"><i class="fas fa-list"></i> Available Cases</a>
                <a href="my_cases.php" class="menu-item"><i class="fas fa-file-alt"></i> My Submissions</a>
                <a href="approved_cases.php" class="menu-item active"><i class="fas fa-check-circle"></i> Approved Submissions</a>
                <a href="rejected_cases.php" class="menu-item"><i class="fas fa-times-circle"></i> Rejected Submissions</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="case_history.php" class="menu-item"><i class="fas fa-history"></i> History</a>
                <a href="../auth/profile.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <div class="main_content">
            <div class="topbar">
                <div class="search-bar"><i class="fas fa-search"></i><input type="text" placeholder="Search..."></div>
                <div class="user-nav">
                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    <a href="../auth/profile.php?view=1" class="text-decoration-none">
                        <div class="user-profile">
                            <div class="avatar shadow-sm" style="overflow: hidden;">
                                <?php if($profile_photo): ?>
                                    <img src="../uploads/profile/<?php echo htmlspecialchars($profile_photo); ?>" style="width: 100%; height: 100%; object-fit: cover;" referrerpolicy="no-referrer">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column text-center">
                                <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);"><?php echo htmlspecialchars($student_name); ?></span>
                                <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;"><?php echo htmlspecialchars($roll_no); ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="container mt-2">
                <h4 class="mb-4 fw-bold">Approved Case Submissions</h4>
                <div class="card shadow-sm border-0" style="border-radius: 12px; border-top: 5px solid #10b981;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3">Case Title</th>
                                        <th class="py-3">Submitted At</th>
                                        <th class="py-3">Staff Remark</th>
                                        <th class="py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars($row['case_title']); ?></td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($row['submitted_at'])); ?></td>
                                                <td class="small text-success fw-bold"><?php echo htmlspecialchars($row['staff_remark'] ?? '-'); ?></td>
                                                <td><span class="status-badge status-Approved">Approved</span></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center py-5 text-muted">No approved submissions found.</td></tr>
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
