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

// Fetch all available cases and check if student has already responded
$sql = "SELECT c.*, u.name as staff_name, s.id as submission_id 
        FROM cases c 
        JOIN users u ON c.created_by_staff = u.id 
        LEFT JOIN case_submissions s ON c.id = s.case_id AND s.student_id = ?
        ORDER BY c.created_at DESC";
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
    <title>Available Cases - OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <a href="dashboard.php" class="menu-item"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="available_cases.php" class="menu-item active"><i class="fas fa-list"></i> Available Cases</a>
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
                <div class="search-bar" style="visibility: hidden;"><i class="fas fa-search"></i><input type="text"></div>
                <div class="user-nav">
                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    <a href="../auth/profile.php?view=1" class="text-decoration-none">
                        <div class="user-profile">
                            <div class="avatar shadow-sm" style="overflow: hidden; position: relative;">
                                <?php 
                                $student_photo_path = !empty($profile_photo) ? "../uploads/profile/" . $profile_photo : null;
                                if($student_photo_path && file_exists(__DIR__ . "/../uploads/profile/" . $profile_photo)): ?>
                                    <img src="<?php echo $student_photo_path; ?>?v=<?php echo time(); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-blue-gradient text-white fw-bold" style="font-size: 1.2rem;">
                                        <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                                    </div>
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

            <div class="container mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0">Available Cases</h4>
                    <span class="badge bg-light text-dark border p-2 px-3 rounded-pill" style="font-size: 0.8rem;"><?php echo $result->num_rows; ?> Total Cases</span>
                </div>
                <div class="row g-4">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 shadow-sm border-0 bg-white" style="border-radius: 16px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden;">
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <span class="badge bg-soft-primary text-primary px-3 py-2" style="font-size: 0.7rem; border-radius: 8px;">Case #<?php echo $row['id']; ?></span>
                                            <span class="text-muted" style="font-size: 0.75rem;"><i class="far fa-calendar-alt me-1"></i> <?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                                        </div>
                                        <h5 class="card-title fw-bold mb-2 text-dark" style="font-size: 1.1rem;"><?php echo htmlspecialchars($row['title']); ?></h5>
                                        <p class="card-text text-muted mb-4" style="font-size: 0.85rem; line-height: 1.6; flex-grow: 1;">
                                            <?php echo htmlspecialchars(substr($row['description'], 0, 90)) . (strlen($row['description']) > 90 ? '...' : ''); ?>
                                        </p>
                                        <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3" style="border: 1px dashed #e2e8f0;">
                                            <div class="avatar-xs bg-white rounded-circle text-center me-2 shadow-sm d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; min-width: 28px;">
                                                <i class="fas fa-user-tie text-primary" style="font-size: 0.8rem;"></i>
                                            </div>
                                            <div class="d-flex flex-column">
                                                <small class="text-muted" style="font-size: 0.65rem; text-transform: uppercase; font-weight: 600;">Managed By</small>
                                                <span class="fw-bold text-dark" style="font-size: 0.85rem;"><?php echo htmlspecialchars($row['staff_name']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <?php if($row['submission_id']): ?>
                                            <button class="btn btn-light w-100 py-2 fw-bold text-success border-success-subtle" style="border-radius: 12px; font-size: 0.9rem; cursor: default; background: #f0fdf4;" disabled>
                                                <i class="fas fa-check-circle me-2"></i> Case Responded
                                            </button>
                                        <?php else: ?>
                                            <a href="submit_case.php?case_id=<?php echo $row['id']; ?>" class="btn btn-primary w-100 py-2 fw-bold" style="border-radius: 12px; font-size: 0.9rem; box-shadow: 0 4px 15px rgba(67, 97, 238, 0.25);">
                                                Respond Now <i class="fas fa-arrow-right ms-2" style="font-size: 0.75rem;"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3 opacity-25"></i>
                            <p class="text-muted">No cases available at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
    <style>
        .bg-soft-primary { background-color: rgba(67, 97, 238, 0.1); }
        .card:hover { transform: translateY(-5px); }
    </style>
</body>
</html>
