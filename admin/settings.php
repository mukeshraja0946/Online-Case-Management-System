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
                <a href="cases.php"><i class="fas fa-folder-open"></i> All Cases</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
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
                    <div class="user-profile">
                        <div class="avatar shadow-sm bg-primary text-white d-flex align-items-center justify-content-center">
                            A
                        </div>
                        <div class="d-flex flex-column text-center" style="line-height: 1.2;">
                            <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </span>
                            <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                                Administrator
                            </span>
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
