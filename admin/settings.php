<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// Fetch current admin data
$stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$admin_name = $user['name'] ?? 'Admin';
$admin_email = $user['email'] ?? '';
$admin_initial = !empty($admin_name) ? strtoupper($admin_name[0]) : 'A';

// Ensure settings table exists
$conn->query("CREATE TABLE IF NOT EXISTS settings (id INT PRIMARY KEY, session_timeout INT)");

// Add session_unit column if it doesn't exist
$check_col = $conn->query("SHOW COLUMNS FROM settings LIKE 'session_unit'");
if ($check_col && $check_col->num_rows == 0) {
    $conn->query("ALTER TABLE settings ADD COLUMN session_unit VARCHAR(20) DEFAULT 'minutes'");
}

$conn->query("INSERT IGNORE INTO settings (id, session_timeout, session_unit) VALUES (1, 30, 'minutes')");

// Fetch current session timeout
$timeout_res = $conn->query("SELECT session_timeout, session_unit FROM settings WHERE id = 1");
$timeout_data = $timeout_res->fetch_assoc();
$current_timeout = $timeout_data['session_timeout'] ?? 30;
$current_unit = $timeout_data['session_unit'] ?? 'minutes';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    
    // Update Name/Email
    $update_sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $name, $email, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['name'] = $name;
        $success = "Profile updated successfully!";
        
        // Handle Password Update
        if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Fetch current password hash
            $pwd_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $pwd_stmt->bind_param("i", $user_id);
            $pwd_stmt->execute();
            $stored_hash = $pwd_stmt->get_result()->fetch_assoc()['password'];

            if (password_verify($current_password, $stored_hash)) {
                if ($new_password === $confirm_password) {
                    if (strlen($new_password) >= 4) {
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_pwd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $update_pwd->bind_param("si", $new_hash, $user_id);
                        if ($update_pwd->execute()) {
                            $success .= " Password updated!";
                        } else {
                            $error = "Failed to update password.";
                        }
                    } else {
                        $error = "New password must be at least 4 digits long.";
                    }
                } else {
                    $error = "New passwords do not match.";
                }
            } else {
                $error = "Incorrect current password.";
            }
        }
        
        // Refresh local user data
        $user['name'] = $name;
        $user['email'] = $email;
        
        // Handle Session Timeout Settings
        if (isset($_POST['session_timeout']) && is_numeric($_POST['session_timeout']) && isset($_POST['session_unit'])) {
            $new_timeout = (int)$_POST['session_timeout'];
            $new_unit = $_POST['session_unit'];
            $allowed_units = ['seconds', 'minutes', 'hours', 'days'];
            
            if ($new_timeout >= 1 && in_array($new_unit, $allowed_units)) {
                $upd_timeout = $conn->prepare("UPDATE settings SET session_timeout = ?, session_unit = ? WHERE id = 1");
                $upd_timeout->bind_param("is", $new_timeout, $new_unit);
                $upd_timeout->execute();
                $current_timeout = $new_timeout;
                $current_unit = $new_unit;
            } else {
                $error = "Timeout must be greater than or equal to 1, and unit must be valid.";
            }
        }
    } else {
        $error = "Update failed: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin OCMS</title>
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
                    <h2>Settings</h2>
                    <p>Manage admin profile and security.</p>
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

            <?php if($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8 mx-auto">
                    <!-- Profile Card -->
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="fw-bold mb-0"><i class="fas fa-user-shield me-2 text-primary"></i> Admin Profile</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control" required>
                                </div>
                                
                                <hr class="my-4">

                                <!-- Global System Settings -->
                                <h5 class="fw-bold mb-3"><i class="fas fa-sliders-h me-2 text-warning"></i> System Settings</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Auto Logout Timeout</label>
                                        <div class="input-group">
                                            <input type="number" name="session_timeout" value="<?php echo $current_timeout; ?>" class="form-control" min="1" required style="max-width: 120px;">
                                            <select name="session_unit" class="form-select" style="max-width: 150px;">
                                                <option value="seconds" <?php if($current_unit == 'seconds') echo 'selected'; ?>>Seconds</option>
                                                <option value="minutes" <?php if($current_unit == 'minutes') echo 'selected'; ?>>Minutes</option>
                                                <option value="hours" <?php if($current_unit == 'hours') echo 'selected'; ?>>Hours</option>
                                                <option value="days" <?php if($current_unit == 'days') echo 'selected'; ?>>Days</option>
                                            </select>
                                        </div>
                                        <small class="text-muted d-block mt-2">Users inactive for this duration will be automatically logged out.</small>
                                    </div>
                                </div>

                                <hr class="my-4">
                                
                                <h5 class="fw-bold mb-3"><i class="fas fa-lock me-2 text-danger"></i> Change Password</h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" name="current_password" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" name="confirm_password" class="form-control">
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
