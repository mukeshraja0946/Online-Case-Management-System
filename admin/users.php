<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$admin_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';
$admin_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$admin_initial = !empty($admin_name) ? strtoupper($admin_name[0]) : 'A';
$msg = "";
$error = "";

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Delete User
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $user_id = $_POST['user_id'];
        
        // Prevent deleting self (admin)
        if ($user_id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account here.";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $msg = "User deleted successfully.";
            } else {
                $error = "Error deleting user: " . $conn->error;
            }
        }
    }

    // 1.1 Bulk Delete Users
    if (isset($_POST['action']) && $_POST['action'] == 'bulk_delete') {
        $user_ids = $_POST['user_ids']; // Array of IDs
        if (!empty($user_ids)) {
            $ids_placeholder = implode(',', array_fill(0, count($user_ids), '?'));
            $current_admin_id = $_SESSION['user_id'];
            
            // Safety: Filter out admin id if somehow included
            $user_ids = array_filter($user_ids, function($id) use ($current_admin_id) {
                return $id != $current_admin_id;
            });
            
            if (!empty($user_ids)) {
                $stmt = $conn->prepare("DELETE FROM users WHERE id IN ($ids_placeholder)");
                $types = str_repeat('i', count($user_ids));
                $stmt->bind_param($types, ...$user_ids);
                
                if ($stmt->execute()) {
                    $msg = count($user_ids) . " users deleted successfully.";
                } else {
                    $error = "Error performing bulk deletion: " . $conn->error;
                }
            }
        }
    }

    // 2. Edit User Details
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $user_id = $_POST['user_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $role = $_POST['role_type']; // 'student' or 'staff'
        
        $roll_no = ($role == 'student') ? $_POST['identifier'] : NULL;
        $staff_id = ($role == 'staff') ? $_POST['identifier'] : NULL;

        $department = $_POST['department'];
        $year = isset($_POST['year']) ? $_POST['year'] : NULL;
        $batch = isset($_POST['batch']) ? $_POST['batch'] : NULL;
        $semester = isset($_POST['semester']) ? $_POST['semester'] : NULL;
        $joined_date = $_POST['joined_date'];

        // Handle Profile Photo
        $profile_photo = NULL;
        // ... (remaining photo logic same)
        $stmt_fetch = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
        $stmt_fetch->bind_param("i", $user_id);
        $stmt_fetch->execute();
        $user_res = $stmt_fetch->get_result()->fetch_assoc();
        if ($user_res) $profile_photo = $user_res['profile_photo'];

        if (isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1') $profile_photo = NULL;
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $upload_dir = "../uploads/profile/";
            $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $new_file_name = "profile_" . $user_id . "_" . time() . "." . $file_ext;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_dir . $new_file_name)) $profile_photo = $new_file_name;
        }

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, roll_no = ?, staff_id = ?, department = ?, year = ?, batch = ?, semester = ?, created_at = ?, profile_photo = ? WHERE id = ?");
        $stmt->bind_param("sssssssssssi", $name, $email, $role, $roll_no, $staff_id, $department, $year, $batch, $semester, $joined_date, $profile_photo, $user_id);
        
        if ($stmt->execute()) $msg = "User details updated successfully!";
        else $error = "Update Failed: " . $conn->error;
    }
}

// --- SELF-HEALING SCHEMA CHECK ---
$check_year = $conn->query("SHOW COLUMNS FROM users LIKE 'year'");
if ($check_year->num_rows == 0) $conn->query("ALTER TABLE users ADD COLUMN year VARCHAR(50) AFTER department");

$check_batch = $conn->query("SHOW COLUMNS FROM users LIKE 'batch'");
if ($check_batch->num_rows == 0) $conn->query("ALTER TABLE users ADD COLUMN batch VARCHAR(50) AFTER year");

// Fetch Filter Options for Dropdowns
$dept_filter = isset($_GET['dept']) ? $_GET['dept'] : 'All';
$year_filter = isset($_GET['year']) ? $_GET['year'] : 'All';
$batch_filter = isset($_GET['batch']) ? $_GET['batch'] : 'All';

$depts_list = $conn->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
$years_list = $conn->query("SELECT DISTINCT year FROM users WHERE year IS NOT NULL AND year != '' ORDER BY year ASC");
$batch_list = $conn->query("SELECT DISTINCT batch FROM users WHERE batch IS NOT NULL AND batch != '' ORDER BY batch ASC");

// Fetch Users
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'All';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT * FROM users WHERE role != 'admin'"; 

if ($role_filter != 'All') $sql .= " AND role = '$role_filter'";
if ($dept_filter != 'All') $sql .= " AND department = '" . $conn->real_escape_string($dept_filter) . "'";
if ($year_filter != 'All') $sql .= " AND year = '" . $conn->real_escape_string($year_filter) . "'";
if ($batch_filter != 'All') $sql .= " AND batch = '" . $conn->real_escape_string($batch_filter) . "'";
if (!empty($search_query)) $sql .= " AND (name LIKE '%$search_query%' OR email LIKE '%$search_query%' OR roll_no LIKE '%$search_query%' OR staff_id LIKE '%$search_query%')";

$sql .= " ORDER BY id DESC";
$result = $conn->query($sql);
if (!$result) $result = (object) ['num_rows' => 0]; // Safety fallback to avoid crash if query fails
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin OCMS</title>
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
<body class="admin-portal">
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar admin-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="../assets/img/ocmslogo.png" alt="Logo" style="height: 55px;">
                </div>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Admin Menu</div>
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                <a href="dashboard.php" class="menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="bulk_create_users.php" class="menu-item <?php echo ($current_page == 'bulk_create_users.php') ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> Create Users</a>
                <a href="users.php" class="menu-item <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Manage Users</a>
                <a href="cases.php" class="menu-item <?php echo ($current_page == 'cases.php') ? 'active' : ''; ?>"><i class="fas fa-folder-open"></i> All Cases</a>
                <a href="manage_case_types.php" class="menu-item <?php echo ($current_page == 'manage_case_types.php') ? 'active' : ''; ?>"><i class="fas fa-tags"></i> Case Types</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="settings.php" class="menu-item <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="header-text">
                    <h2>Manage Users</h2>
                    <p>Edit details or reset passwords for students and staff.</p>
                </div>
                
                <div class="user-nav ms-auto">
                    <div class="user-profile d-flex align-items-center gap-3">
                        <div class="text-end" style="line-height: 1.2;">
                            <div style="font-size: 0.9rem; font-weight: 750; color: #1e293b; font-family: 'Outfit';">
                                <?php echo ($_SESSION['role'] === 'admin' ? 'Admin | ' : '') . htmlspecialchars($admin_name); ?>
                            </div>
                            <div style="font-size: 0.75rem; color: #64748b; font-weight: 500;">
                                <?php echo htmlspecialchars($admin_email); ?>
                            </div>
                        </div>
                        <div class="avatar shadow-sm bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; border-radius: 50%;">
                            <?php echo $admin_initial; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Filter & Bulk Actions Section -->
            <div class="card mb-4 border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-9">
                            <form method="GET" action="" class="row g-2 align-items-center">
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <select name="role" class="form-select bg-light" onchange="this.form.submit()">
                                        <option value="All" <?php echo ($role_filter == 'All') ? 'selected' : ''; ?>>All Roles</option>
                                        <option value="student" <?php echo ($role_filter == 'student') ? 'selected' : ''; ?>>Student</option>
                                        <option value="staff" <?php echo ($role_filter == 'staff') ? 'selected' : ''; ?>>Staff</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="dept" class="form-select bg-light" onchange="this.form.submit()">
                                        <option value="All">All Depts</option>
                                        <?php while($d = $depts_list->fetch_assoc()): ?>
                                            <option value="<?php echo $d['department']; ?>" <?php echo ($dept_filter == $d['department']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($d['department']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="year" class="form-select bg-light" onchange="this.form.submit()">
                                        <option value="All">All Years</option>
                                        <?php while($y = $years_list->fetch_assoc()): ?>
                                            <option value="<?php echo $y['year']; ?>" <?php echo ($year_filter == $y['year']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($y['year']); ?> yr
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="batch" class="form-select bg-light" onchange="this.form.submit()">
                                        <option value="All">All Batches</option>
                                        <?php while($b = $batch_list->fetch_assoc()): ?>
                                            <option value="<?php echo $b['batch']; ?>" <?php echo ($batch_filter == $b['batch']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($b['batch']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                     <a href="users.php" class="btn btn-secondary w-100" title="Reset Filters"><i class="fas fa-undo"></i></a>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-3 text-end" id="bulkActions" style="display: none;">
                            <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
                                <i class="fas fa-trash-alt me-2"></i> Delete Selected (<span id="selectedCount">0</span>)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-card" style="padding: 0; overflow: hidden;">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light text-center">
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th style="width: 50px;">S.No</th>
                                <th class="py-3 ps-4 text-start">User</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Year</th>
                                <th>Batch</th>
                                <th>Joined</th>
                                <th>...</th>
                                <th class="pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php $sno = 1; while($row = $result->fetch_assoc()): ?>
                                    <tr class="text-center border-bottom-0">
                                        <td>
                                            <input type="checkbox" class="form-check-input user-checkbox" value="<?php echo $row['id']; ?>">
                                        </td>
                                        <td><?php echo $sno++; ?></td>
                                        <td class="ps-4 text-start">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar shadow-sm" style="overflow: hidden; width: 40px; height: 40px; border-radius: 50%; border: 2px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--primary-color);">
                                                    <?php if($row['profile_photo']): ?>
                                                        <?php 
                                                            $photo = trim($row['profile_photo']);
                                                            $pic = (strpos($photo, 'http') === 0) ? $photo : "../uploads/profile/" . $photo;
                                                            $pic .= (strpos($pic, '?') === false ? '?' : '&') . 'v=' . time();
                                                        ?>
                                                        <img src="<?php echo htmlspecialchars($pic); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                    <?php else: ?>
                                                        <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="ms-3" style="font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($row['name']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($row['role'] == 'student'): ?>
                                                <span class="badge bg-soft-blue text-blue">Student</span>
                                            <?php else: ?>
                                                <span class="badge bg-soft-purple text-purple">Staff</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small></td>
                                        <td><small class="text-muted fw-bold"><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></small></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['year'] ?? '-'); ?> yr</span></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($row['batch'] ?? '-'); ?></small></td>
                                        <td><small class="text-muted"><?php echo date('d M, Y', strtotime($row['created_at'])); ?></small></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($row['role'] == 'student' ? $row['roll_no'] : $row['staff_id']); ?>
                                            </span>
                                        </td>
                                        <td class="pe-4">
                                            <div class="d-flex justify-content-center gap-3">
                                                <a href="#" class="text-primary text-decoration-underline small" 
                                                   data-bs-toggle="modal" 
                                                   data-bs-target="#editUserModal"
                                                   data-id="<?php echo $row['id']; ?>"
                                                   data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                                   data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                                   data-department="<?php echo htmlspecialchars($row['department'] ?? ''); ?>"
                                                   data-year="<?php echo htmlspecialchars($row['year'] ?? ''); ?>"
                                                   data-batch="<?php echo htmlspecialchars($row['batch'] ?? ''); ?>"
                                                   data-semester="<?php echo htmlspecialchars($row['semester'] ?? ''); ?>"
                                                   data-role="<?php echo $row['role']; ?>"
                                                   data-photo="<?php echo htmlspecialchars($row['profile_photo'] ?? ''); ?>"
                                                   data-joined="<?php echo date('Y-m-d', strtotime($row['created_at'])); ?>"
                                                   data-identifier="<?php echo htmlspecialchars($row['role'] == 'student' ? $row['roll_no'] : $row['staff_id']); ?>">
                                                    Edit
                                                </a>
                                                <a href="#" class="text-success text-decoration-underline small" 
                                                   data-bs-toggle="modal" 
                                                   data-bs-target="#resetPasswordModal"
                                                   data-id="<?php echo $row['id']; ?>"
                                                   data-name="<?php echo htmlspecialchars($row['name']); ?>">
                                                    Reset Password
                                                </a>
                                                <a href="#" class="text-danger text-decoration-underline small" 
                                                   data-bs-toggle="modal" 
                                                   data-bs-target="#deleteUserModal"
                                                   data-id="<?php echo $row['id']; ?>"
                                                   data-name="<?php echo htmlspecialchars($row['name']); ?>">
                                                    Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <input type="hidden" name="remove_photo" id="edit_remove_photo" value="0">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4 text-center">
                            <div class="position-relative d-inline-block">
                                <img id="edit_photo_preview" src="" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #f1f5f9; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                                <label for="edit_photo_input" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; cursor: pointer; border: 2px solid #fff;">
                                    <i class="fas fa-camera" style="font-size: 12px;"></i>
                                </label>
                                <input type="file" name="profile_photo" id="edit_photo_input" hidden accept="image/*" onchange="previewEditImage(this)">
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-link btn-sm text-danger text-decoration-none fw-bold" id="remove_edit_photo_btn" onclick="removeEditPhoto()">
                                    <i class="fas fa-trash-alt me-1"></i> Remove Photo
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role_type" id="edit_role_type" class="form-select" required>
                                <option value="student">Student</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" id="edit_department" class="form-control">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year</label>
                                <input type="text" name="year" id="edit_year" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Batch</label>
                                <input type="text" name="batch" id="edit_batch" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3" id="semester_field_group">
                            <label class="form-label">Semester</label>
                            <input type="text" name="semester" id="edit_semester" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Joined Date</label>
                            <input type="date" name="joined_date" id="edit_joined_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" id="edit_identifier_label">Roll No / Staff ID</label>
                            <input type="text" name="identifier" id="edit_identifier" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Reset Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Reset password for <strong id="reset_user_name"></strong>?</p>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="text" name="new_password" class="form-control" required minlength="4" placeholder="Enter new password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Deleting User Confirmation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-danger">Are you sure you want to delete <strong id="delete_user_name"></strong>?</p>
                        <p class="small text-muted">This action cannot be undone. All associated cases might also be deleted or orphaned.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Modal -->
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="bulkDeleteForm" method="POST">
                    <input type="hidden" name="action" value="bulk_delete">
                    <div id="bulk_ids_container"></div>
                    
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Deleting User Confirmation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="fw-bold">Are you sure you want to delete <span id="bulkDeleteCount" class="text-danger">0</span> selected users?</p>
                        <p class="small text-muted">This action is permanent and will delete all selected accounts. Confirm with caution.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmBulkDelete">Delete Users</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit Modal Logic
        const editUserModal = document.getElementById('editUserModal');
        editUserModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const email = button.getAttribute('data-email');
            const department = button.getAttribute('data-department');
            const year = button.getAttribute('data-year');
            const batch = button.getAttribute('data-batch');
            const role = button.getAttribute('data-role');
            const identifier = button.getAttribute('data-identifier');
            
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_department').value = department;
            document.getElementById('edit_year').value = year;
            document.getElementById('edit_batch').value = batch;
            document.getElementById('edit_semester').value = button.getAttribute('data-semester');
            document.getElementById('edit_joined_date').value = button.getAttribute('data-joined');
            document.getElementById('edit_role_type').value = role;
            document.getElementById('edit_identifier').value = identifier;
            
            // Photo Preview Logic
            const photo = button.getAttribute('data-photo');
            const preview = document.getElementById('edit_photo_preview');
            const removeBtn = document.getElementById('remove_edit_photo_btn');
            document.getElementById('edit_remove_photo').value = '0';

            if (photo && photo.trim() !== '') {
                const picSrc = (photo.startsWith('http')) ? photo : "../uploads/profile/" + photo;
                preview.src = picSrc;
                removeBtn.style.display = 'inline-block';
            } else {
                preview.src = `https://ui-avatars.com/api/?name=${name}&background=random&size=100`;
                removeBtn.style.display = 'none';
            }
            
            const label = document.getElementById('edit_identifier_label');
            const semesterGroup = document.getElementById('semester_field_group');
            const updateUI = (r) => {
                label.textContent = r === 'student' ? 'Roll Number' : 'Staff ID';
                semesterGroup.style.display = r === 'student' ? 'block' : 'none';
            };
            updateUI(role);

            // Add change listener to role select
            document.getElementById('edit_role_type').onchange = function() {
                updateUI(this.value);
            };
        });

        function previewEditImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('edit_photo_preview').src = e.target.result;
                    document.getElementById('edit_remove_photo').value = '0';
                    document.getElementById('remove_edit_photo_btn').style.display = 'inline-block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeEditPhoto() {
            const preview = document.getElementById('edit_photo_preview');
            const name = document.getElementById('edit_name').value;
            preview.src = `https://ui-avatars.com/api/?name=${name}&background=random&size=100`;
            document.getElementById('edit_remove_photo').value = '1';
            document.getElementById('edit_photo_input').value = '';
            document.getElementById('remove_edit_photo_btn').style.display = 'none';
        }

        // Reset Password Modal Logic
        const resetPasswordModal = document.getElementById('resetPasswordModal');
        resetPasswordModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            
            document.getElementById('reset_user_id').value = id;
            document.getElementById('reset_user_name').textContent = name;
        });

        // Delete Modal Logic
        const deleteUserModal = document.getElementById('deleteUserModal');
        deleteUserModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            
            document.getElementById('delete_user_id').value = id;
            document.getElementById('delete_user_name').textContent = name;
        });

        // Bulk Deletion Toggle Logic
        const selectAll = document.getElementById('selectAll');
        const userCheckboxes = document.querySelectorAll('.user-checkbox');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        const bulkDeleteCount = document.getElementById('bulkDeleteCount');
        const bulkDeleteForm = document.getElementById('bulkDeleteForm');
        const bulkIdsContainer = document.getElementById('bulk_ids_container');

        const updateBulkUI = () => {
            const checked = document.querySelectorAll('.user-checkbox:checked');
            selectedCount.textContent = checked.length;
            bulkDeleteCount.textContent = checked.length;
            bulkActions.style.display = checked.length > 0 ? 'block' : 'none';
        };

        selectAll.addEventListener('change', function() {
            userCheckboxes.forEach(cb => cb.checked = this.checked);
            updateBulkUI();
        });

        userCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (!this.checked) selectAll.checked = false;
                if (document.querySelectorAll('.user-checkbox:checked').length === userCheckboxes.length) selectAll.checked = true;
                updateBulkUI();
            });
        });

        // Bulk Delete Preparation
        document.getElementById('confirmBulkDelete').addEventListener('click', function() {
            const checkedIds = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
            bulkIdsContainer.innerHTML = '';
            checkedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'user_ids[]';
                input.value = id;
                bulkIdsContainer.appendChild(input);
            });
            bulkDeleteForm.submit();
        });
    </script>
</body>
</html>
