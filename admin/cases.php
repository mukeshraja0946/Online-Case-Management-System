<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$admin_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';

// Delete Case Logic (Scoped)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_case_scoped') {
    $case_id = $_POST['case_id'];
    
    // Always hide from Admin
    $sql = "UPDATE cases SET is_hidden_admin = 1";
    
    // Optional Scopes
    if (isset($_POST['scope_student']) && $_POST['scope_student'] == 1) {
        $sql .= ", student_my_cases_visible = 0"; // Hide from Student
    }
    if (isset($_POST['scope_staff']) && $_POST['scope_staff'] == 1) {
        $sql .= ", deleted_by_staff = 1"; // Hide from Staff
    }
    
    $sql .= " WHERE id = ?";
    
    $stmt_del = $conn->prepare($sql);
    $stmt_del->bind_param("i", $case_id);
    
    if ($stmt_del->execute()) {
         // Success
    }
    header("Location: cases.php?msg=Case removed successfully");
    exit();
}

// Approve Deletion Request (Hide from Student)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'approve_deletion') {
    $case_id = $_POST['case_id'];
    // Hide from student and reset request flag
    $stmt_approve = $conn->prepare("UPDATE cases SET student_my_cases_visible = 0, deletion_requested = 0 WHERE id = ?");
    $stmt_approve->bind_param("i", $case_id);
    if ($stmt_approve->execute()) {
         // Success
    }
    header("Location: cases.php?msg=Deletion approved for student");
    exit();
}

// Search and Filter Logic
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'All';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT c.*, u.name as student_name, u.email, u.roll_no 
        FROM cases c 
        JOIN users u ON c.student_id = u.id 
        WHERE is_hidden_admin = 0";

if ($status_filter != 'All') {
    $sql .= " AND c.status = '$status_filter'";
}

if (!empty($search_query)) {
    $sql .= " AND (u.name LIKE '%$search_query%' OR u.roll_no LIKE '%$search_query%' OR c.case_type LIKE '%$search_query%')";
}

$sql .= " ORDER BY c.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Cases - Admin OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4><img src="../assets/img/ocmslogo.png" alt="Logo" style="height: 75px;"></h4>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Admin Menu</div>
                <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="bulk_create_users.php"><i class="fas fa-user-plus"></i> Create Users</a>
                <a href="users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="cases.php" class="active"><i class="fas fa-folder-open"></i> All Cases</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="header-text">
                    <h2>All Cases</h2>
                    <p>Manage and review all student cases.</p>
                </div>
                
                <div class="user-nav ms-auto">
                    <div class="user-profile">
                        <div class="avatar shadow-sm bg-primary text-white d-flex align-items-center justify-content-center">
                            A
                        </div>
                        <div class="d-flex flex-column text-center" style="line-height: 1.2;">
                            <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);">
                                <?php echo htmlspecialchars($admin_name); ?>
                            </span>
                            <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                                Administrator
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card mb-4 border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Search by name, roll no, or type..." value="<?php echo htmlspecialchars($search_query); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select bg-light" onchange="this.form.submit()">
                                <option value="All" <?php echo ($status_filter == 'All') ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="Pending" <?php echo ($status_filter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo ($status_filter == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo ($status_filter == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                <option value="DeletionRequested" <?php echo ($status_filter == 'DeletionRequested') ? 'selected' : ''; ?>>Deletion Requests</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                             <a href="cases.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cases Table -->
            <div class="table-card" style="padding: 0; overflow: hidden;">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-center">
                                <th class="py-3 ps-4 text-start">Student Details</th>
                                <th>Case Type</th>
                                <th>Attachment</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr class="text-center border-bottom-0">
                                        <td class="ps-4 text-start">
                                            <div class="d-flex flex-column">
                                                <span style="font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                                <small class="text-muted"><?php echo htmlspecialchars($row['roll_no']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="category-badge cat-<?php echo htmlspecialchars($row['case_type']); ?>">
                                                <?php echo htmlspecialchars($row['case_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['attachment']): ?>
                                                <a href="../uploads/<?php echo $row['attachment']; ?>" target="_blank" class="btn btn-sm btn-light text-primary">
                                                    <i class="fas fa-paperclip"></i> View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td title="<?php echo htmlspecialchars($row['description']); ?>">
                                            <div class="mx-auto" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?php echo htmlspecialchars($row['description']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span style="font-weight: 500;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                                                <small class="text-muted"><?php echo date('h:i A', strtotime($row['created_at'])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($row['deletion_requested']): ?>
                                                <span class="status-badge status-DeletionRequested">
                                                    Deletion Requested
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                                    <?php echo $row['status']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4">
                                            <div class="d-flex flex-column align-items-center gap-1">
                                                <?php if (isset($row['deletion_requested']) && $row['deletion_requested'] == 1): ?>
                                                    <div class="mb-1">
                                                        <span class="badge bg-danger">Requesting Deletion</span>
                                                    </div>
                                                    <form method="POST" action="" onsubmit="return confirm('Approve deletion for student?');" style="display:inline;">
                                                        <input type="hidden" name="action" value="approve_deletion">
                                                        <input type="hidden" name="case_id" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-approve fw-bold mb-1" title="Approve Student Deletion">
                                                            Approve
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <button type="button" class="btn btn-link text-danger text-decoration-underline p-0 small border-0" title="Remove from Admin view" onclick="openDeleteModal('<?php echo $row['id']; ?>')">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-folder-open fa-3x mb-3 opacity-25"></i>
                                        <p class="mb-0">No cases found matching your criteria.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Scope Modal -->
    <div class="modal fade" id="deleteScopeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Delete Case</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="delete_case_scoped">
                        <input type="hidden" name="case_id" id="deleteCaseId">
                        
                        <p class="mb-3">This action will remove the case from your Admin view.</p>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Also remove from:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="scope_student" id="scopeStudent" value="1">
                                <label class="form-check-label" for="scopeStudent">
                                    Student View
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="scope_staff" id="scopeStaff" value="1">
                                <label class="form-check-label" for="scopeStaff">
                                    Staff View
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Confirm Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openDeleteModal(id) {
            document.getElementById('deleteCaseId').value = id;
            var myModal = new bootstrap.Modal(document.getElementById('deleteScopeModal'));
            myModal.show();
        }
    </script>
</body>
</html>
