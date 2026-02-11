<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle Action
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $case_id = $_POST['case_id'];
    $status = $_POST['status'];
    $remark = $_POST['remark'];
    $processed_by = $_SESSION['name'];

    $sql = "UPDATE cases SET status = ?, staff_remark = ?, processed_by = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("sssi", $status, $remark, $processed_by, $case_id);
    
    if ($stmt->execute()) {
        $success = "Case updated successfully!";
        
        // Notify student
        $get_student = $conn->prepare("SELECT student_id, case_type FROM cases WHERE id = ?");
        $get_student->bind_param("i", $case_id);
        $get_student->execute();
        $student_data = $get_student->get_result()->fetch_assoc();
        
        if ($student_data) {
            $notif_msg = "Your case (" . $student_data['case_type'] . ") has been " . strtolower($status);
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'case_status')");
            $notif_stmt->bind_param("is", $student_data['student_id'], $notif_msg);
            $notif_stmt->execute();
        }
    } else {
        $error = "Error updating case.";
    }
}

// Fetch Pending Cases
$sql = "SELECT * FROM cases WHERE status = 'Pending' AND deleted_by_staff = 0 AND deleted_by_student = 0 ORDER BY created_at ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Received Cases - OCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h4><img src="../assets/img/ocms.png" alt="Logo" style="height: 75px;"></h4>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Menu</div>
                <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="received_cases.php" class="active"><i class="fas fa-inbox"></i> Received Cases</a>
                <a href="approved_cases.php"><i class="fas fa-check-circle"></i> Approved</a>
                <a href="rejected_cases.php"><i class="fas fa-times-circle"></i> Rejected</a>
                <a href="history.php"><i class="fas fa-history"></i> History</a>
                
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <div class="main_content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="dashboard-search" placeholder="Search received cases...">
                </div>
                
                <div class="user-nav">
                    <button class="nav-icon-btn" id="theme-toggle"><i class="fas fa-moon"></i></button>
                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    
                    <div class="user-profile">
                        <div class="avatar" style="background: var(--secondary-color);"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                        <div style="font-size: 0.9rem; font-weight: 600; padding-right: 10px;">
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </div>
                    </div>
                </div>
            </div>

    <div class="container mt-5">
        <h4 class="mb-4">Pending Cases</h4>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

<div class="row">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <div class="col-12 mb-4">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body p-4">
                                            <form method="POST">
                                                <input type="hidden" name="case_id" value="<?php echo $row['id']; ?>">
                                                
                                                <!-- Case Header -->
                                                <div class="mb-2">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="fw-bold mb-1" style="font-size: 1.1rem; color: var(--text-color);"><?php echo htmlspecialchars($row['case_type']); ?></h6>
                                                            <p class="text-secondary mb-0" style="font-size: 0.95rem;"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                                                        </div>
                                                        <?php if (!empty($row['attachment'])): ?>
                                                            <a href="../uploads/<?php echo htmlspecialchars($row['attachment']); ?>" target="_blank" class="btn btn-sm btn-outline-primary flex-shrink-0 ms-2">
                                                                <i class="fas fa-paperclip me-1"></i> View Attachment
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- Comments Section -->
                                                <div class="mb-3 mt-3 d-flex flex-column">
                                                    <label class="form-label fw-bold mb-2" style="color: var(--text-color);">Add comments</label>
                                                    <textarea name="remark" class="form-control w-100" rows="3" placeholder="Here is a custom multiple line control for task owners to input comments." required style="resize: none; font-size: 0.95rem; display: block;"></textarea>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="d-flex gap-3 mt-4">
                                                    <button type="submit" name="status" value="Approved" class="btn btn-success rounded-1 px-4 py-2 fw-medium" style="min-width: 120px;">Approve</button>
                                                    <button type="submit" name="status" value="Rejected" class="btn btn-danger rounded-1 px-4 py-2 fw-medium" style="min-width: 120px;">Reject</button>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        <!-- Footer Info -->
                                        <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-start align-items-center gap-4 mt-3">
                                            <div class="small" style="color: var(--text-color);">
                                                Submission Date <span class="fw-normal ms-1"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></span>
                                            </div>
                                            <div class="small" style="color: var(--text-color);">
                                                Status <span class="text-warning fw-medium ms-1">In process</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info text-center py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                                    <h5>No Pending Cases</h5>
                                    <p class="text-muted">You have updated all received cases.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </div>
</div>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/search.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
