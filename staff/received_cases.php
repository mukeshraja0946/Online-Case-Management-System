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

// Self-healing for staff_remark column
$check_rem = $conn->query("SHOW COLUMNS FROM case_submissions LIKE 'staff_remark'");
if ($check_rem && $check_rem->num_rows == 0) {
    $conn->query("ALTER TABLE case_submissions ADD COLUMN staff_remark TEXT AFTER status");
}

$staff_name = $u_res['name'];
$staff_id = $u_res['staff_id'];
$profile_photo = $u_res['profile_photo'];

// Handle Action (Approve/Reject)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submission_id'])) {
    $submission_id = $_POST['submission_id'];
    $status = $_POST['status'];
    $remark = $_POST['remark']; // Staff can optionally add remark if UI allows, but req says just Approve/Reject

    $sql = "UPDATE case_submissions SET status = ?, staff_remark = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $status, $remark, $submission_id);
    
    if ($stmt->execute()) {
        $success = "Submission marked as " . $status;
        
        // Notify student
        $get_student = $conn->prepare("SELECT s.student_id, c.title FROM case_submissions s JOIN cases c ON s.case_id = c.id WHERE s.id = ?");
        $get_student->bind_param("i", $submission_id);
        $get_student->execute();
        $st_data = $get_student->get_result()->fetch_assoc();
        
        if ($st_data) {
            $notif_msg = "Your submission for '" . $st_data['title'] . "' has been " . strtolower($status);
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'submission_status')");
            $notif_stmt->bind_param("is", $st_data['student_id'], $notif_msg);
            $notif_stmt->execute();
        }
    } else {
        $error = "Error updating submission.";
    }
}

// Fetch Pending Submissions
$sql = "SELECT s.*, c.title, c.description as case_desc, u.name as student_name, u.roll_no 
        FROM case_submissions s 
        JOIN cases c ON s.case_id = c.id 
        JOIN users u ON s.student_id = u.id 
        WHERE s.status = 'Pending' 
        ORDER BY s.submitted_at ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Received Submissions - OCMS</title>
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
                <a href="received_cases.php" class="menu-item active"><i class="fas fa-inbox"></i> Received Submissions</a>
                <a href="approved_cases.php" class="menu-item"><i class="fas fa-check-circle"></i> Approved Submissions</a>
                <a href="rejected_cases.php" class="menu-item"><i class="fas fa-times-circle"></i> Rejected Submissions</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="history.php" class="menu-item"><i class="fas fa-history"></i> History</a>
                <a href="../auth/profile.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <div class="main_content">
            <div class="topbar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="dashboard-search" placeholder="Search student submissions...">
                </div>
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

            <div class="container-fluid py-4">
                <h4 class="fw-bold mb-4 px-3">Student Submissions (Pending Review)</h4>
                
                <div class="row px-3">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 shadow-sm border-0" style="border-radius: 12px; border-top: 4px solid var(--primary-color);">
                                    <div class="card-body p-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-hashtag me-1"></i> <?php echo htmlspecialchars($row['title']); ?></h6>
                                            <span class="badge bg-warning-soft text-warning" style="font-size: 0.7rem; padding: 5px 10px; border-radius: 6px;">PENDING</span>
                                        </div>
                                        
                                        <div class="student-info mb-3 p-2 bg-light rounded-3 d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.8rem; font-weight: 700;">
                                                <?php echo strtoupper(substr($row['student_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold" style="font-size: 0.85rem; color: var(--text-color);"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;">Roll: <?php echo htmlspecialchars($row['roll_no']); ?></div>
                                            </div>
                                        </div>

                                        <p class="card-text text-muted small mb-3" style="line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                            <strong>Reason:</strong> <?php echo htmlspecialchars($row['reason']); ?>
                                        </p>

                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <span class="small text-muted"><i class="far fa-clock me-1"></i> <?php echo date('M d, h:i A', strtotime($row['submitted_at'])); ?></span>
                                            <?php if($row['file']): ?>
                                                <a href="../uploads/<?php echo $row['file']; ?>" target="_blank" class="text-primary fw-bold text-decoration-none small"><i class="fas fa-file-download me-1"></i> View Attachment</a>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-grid gap-2 d-flex">
                                            <button class="btn btn-success btn-sm w-50 fw-bold py-2" onclick="openActionModal(<?php echo $row['id']; ?>, 'Approved', '<?php echo addslashes($row['student_name']); ?>', '<?php echo addslashes($row['title']); ?>')"> <i class="fas fa-check me-1"></i> Approve</button>
                                            <button class="btn btn-danger btn-sm w-50 fw-bold py-2" onclick="openActionModal(<?php echo $row['id']; ?>, 'Rejected', '<?php echo addslashes($row['student_name']); ?>', '<?php echo addslashes($row['title']); ?>')"> <i class="fas fa-times me-1"></i> Reject</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 py-5 text-center">
                            <img src="../assets/img/empty-cases.svg" alt="Empty" style="width: 150px; opacity: 0.5;" onerror="this.src='https://illustrations.popsy.co/gray/not-found.svg'">
                            <p class="text-muted mt-3">No pending submissions found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Remark Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 12px;">
                <form method="POST">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="modalTitle">Process Submission</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body py-4">
                        <input type="hidden" name="submission_id" id="modalSubId">
                        <input type="hidden" name="status" id="modalStatus">
                        
                        <p id="modalMsg" class="mb-3"></p>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Add Final Remark (Mandatory)</label>
                            <textarea name="remark" id="remarkField" class="form-control" rows="3" placeholder="Provide a reason for this decision..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn fw-bold px-4" id="modalSubmitBtn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const actionModal = new bootstrap.Modal(document.getElementById('actionModal'));
        
        function openActionModal(id, status, student, title) {
            document.getElementById('modalSubId').value = id;
            document.getElementById('modalStatus').value = status;
            document.getElementById('modalTitle').innerText = status + ' Submission';
            document.getElementById('modalMsg').innerHTML = `Are you sure you want to <strong>${status}</strong> the submission from <strong>${student}</strong> for <strong>${title}</strong>?`;
            
            const btn = document.getElementById('modalSubmitBtn');
            btn.className = status === 'Approved' ? 'btn btn-success fw-bold px-4' : 'btn btn-danger fw-bold px-4';
            btn.innerText = 'Confirm ' + status;
            
            document.getElementById('remarkField').value = '';
            actionModal.show();
        }
    </script>
    <style>
        .bg-warning-soft { background-color: rgba(255, 193, 7, 0.15); }
    </style>
</body>
</html>
