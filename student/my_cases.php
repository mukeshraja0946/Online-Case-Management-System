<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['name'];

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $del_stmt = $conn->prepare("UPDATE cases SET student_my_cases_visible = 0 WHERE id = ? AND student_id = ?");
    $del_stmt->bind_param("ii", $delete_id, $student_id);
    if ($del_stmt->execute()) {
        $success = "Case removed from this list.";
    } else {
        $error = "Error removing case.";
    }
}

$sql = "SELECT * FROM cases WHERE student_id = ? AND student_my_cases_visible = 1 ORDER BY created_at DESC";
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
    <title>My Cases - OCMS</title>
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
                <a href="my_cases.php" class="active"><i class="fas fa-file-alt"></i> My Cases</a>
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
                    <input type="text" id="dashboard-search" placeholder="Search my cases...">
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
                <h4 class="mb-4">My Submitted Cases</h4>
                
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <?php if(isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
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
                                        <th>Actions</th>
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
                                                        elseif($row['status'] == 'Pending') $badge_class = 'bg-warning text-dark';
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $row['status']; ?></span>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                                <td>
                                                    <?php if($row['status'] == 'Pending'): ?>
                                                        <a href="edit_case.php?id=<?php echo $row['id']; ?>" class="text-primary me-2 text-decoration-underline">Edit</a>
                                                    <?php endif; ?>
                                                    <form method="POST" onsubmit="return confirm('Are you sure?');" style="display:inline;">
                                                        <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" class="text-danger border-0 bg-transparent p-0 text-decoration-underline">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="10" class="text-center">No cases found.</td></tr>
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
    <script src="../assets/js/search.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
