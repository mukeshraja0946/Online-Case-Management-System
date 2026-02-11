<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['name'];

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
                <h4><img src="../assets/img/ocms.png" alt="Logo" style="height: 75px;"></h4>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Menu</div>
                <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="add_case.php"><i class="fas fa-plus-circle"></i> Add Cases</a>
                <a href="my_cases.php"><i class="fas fa-file-alt"></i> My Cases</a>
                <a href="approved_cases.php"><i class="fas fa-check-circle"></i> Approved Cases</a>
                <a href="rejected_cases.php"><i class="fas fa-times-circle"></i> Rejected Cases</a>
                <a href="case_history.php" class="active"><i class="fas fa-history"></i> History</a>
                
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

            <div class="container mt-5">
                <h4 class="mb-4">Case History (Processed)</h4>
                
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
                                        <th>Case Type</th>
                                        <th>Description</th>
                                        <th>Attachment</th>
                                        <th>Remark</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                                            <tr class="text-center">
                                                <td><?php echo $i++; ?></td>
                                                <td><?php echo htmlspecialchars($student_name); ?></td>
                                                <td><?php echo htmlspecialchars($row['roll_no'] ?? '-'); ?></td>
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
                                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
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
