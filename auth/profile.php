<?php
session_start();
file_put_contents('../profile_debug.txt', date('Y-m-d H:i:s') . " - Profile.php started\n", FILE_APPEND);
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// Fetch current user data
// Fetch current user data
$stmt = $conn->prepare("SELECT name, roll_no, staff_id, profile_photo, role, email, semester, department FROM users WHERE id = ?");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
} else {
    // If prepare fails, show error for debugging
    die("Database error: " . $conn->error);
}

// Initial values for form
$name = $user['name'];
$roll_no = $user['roll_no'];
$semester = $user['semester'];
$department = $user['department'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    
    // DEBUG LOGGING
    $log_entry = date('Y-m-d H:i:s') . " - POST Data: " . print_r($_POST, true) . "\n";
    file_put_contents('../profile_debug.txt', $log_entry, FILE_APPEND);
    // END DEBUG LOGGING

    // For read-only fields
    $roll_no = isset($_POST['roll_no']) ? $_POST['roll_no'] : $user['roll_no'];
    $semester = isset($_POST['semester']) ? $_POST['semester'] : $user['semester'];
    $department = isset($_POST['department']) ? $_POST['department'] : $user['department'];
    
    // Handle Profile Photo Upload (Cropped)
    $profile_photo = $user['profile_photo'];

    // Handle Photo Removal
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1') {
        $profile_photo = NULL;
    }

    if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {
        $upload_dir = "../uploads/profile/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $data = $_POST['cropped_image'];
        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);

        $new_file_name = "profile_" . $user_id . "_" . time() . ".png";
        $target_file = $upload_dir . $new_file_name;

        if (file_put_contents($target_file, $data)) {
            $profile_photo = $new_file_name;
        } else {
            $error = "Failed to save cropped image.";
        }
    }
    
    if (empty($error)) {
        // If user is student, we restrict updating personal details and photo
        $is_student = ($user['role'] == 'student');
        
        if (!$is_student) {
            // Try updating with all fields for staff/admin
            $update_sql = "UPDATE users SET name = ?, roll_no = ?, profile_photo = ?, semester = ?, department = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            
            if ($update_stmt) {
                $update_stmt->bind_param("sssssi", $name, $roll_no, $profile_photo, $semester, $department, $user_id);
                if ($update_stmt->execute()) {
                    $_SESSION['name'] = $name;
                    $_SESSION['profile_photo'] = $profile_photo;
                    if($user['role'] == 'student') {
                        $_SESSION['roll_no'] = $roll_no;
                        $_SESSION['semester'] = $semester;
                        $_SESSION['department'] = $department;
                    }
                    $success = "Profile updated successfully!";
                } else {
                    $error = "Update failed: " . $conn->error;
                }
            } else {
                die("Update prepare failed: " . $conn->error);
            }
        } else {
            // Student can only update password and theme (handled separately or via JS)
            // We skip the personal details update for students
            $success = "Settings saved!";
        }
        
        if (empty($error)) {
            // Handle Password Update if fields are filled (Both student and staff can change password)
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
                                $success = "Password updated successfully!";
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
            
            // Refresh local user data if not error
            if (empty($error)) {
                $stmt = $conn->prepare("SELECT name, roll_no, staff_id, profile_photo, role, email, semester, department FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            }
        }
    }
}

$dashboard_link = ($user['role'] == 'student') ? '../student/dashboard.php' : '../staff/dashboard.php';
$is_view_mode = isset($_GET['view']) && $_GET['view'] == '1';
$page_title = $is_view_mode ? 'My Profile' : 'Settings';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Cropper.js CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <!-- CSS -->
    <link href="../assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        .settings-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .settings-tabs {
            display: flex;
            gap: 30px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }
        .settings-tab {
            padding-bottom: 10px;
            color: var(--text-muted);
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }
        .settings-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            font-weight: 600;
        }
        .settings-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            padding: 20px 25px;
            margin-bottom: 20px;
        }
        .profile-split-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 40px;
        }
        .profile-left-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding-right: 30px;
            border-right: 1px solid #f1f5f9;
        }
        .profile-img-lg {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 5px solid #f8fafc;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .btn-change-photo {
            background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-change-photo:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            color: white;
        }
        .form-group-custom {
            margin-bottom: 12px;
        }
        .form-group-custom label {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 8px;
            display: block;
        }
        .form-control-custom {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 15px;
            width: 100%;
            font-size: 0.95rem;
            color: var(--text-color);
            transition: all 0.3s;
        }
        .form-control-custom:focus {
            background: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(186, 230, 253, 0.3);
            outline: none;
        }
        .btn-save-custom {
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            float: right;
            margin-top: 10px;
            transition: all 0.3s;
        }
        .btn-save-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
            color: white;
        }
        
        /* Dark theme overrides */
        body.dark-theme .profile-left-col {
            border-right-color: var(--border-color);
        }
        body.dark-theme .form-control-custom {
            background: #0f172a;
            border-color: #475569;
        }
        body.dark-theme .form-control-custom:focus {
            background: #0f172a;
            border-color: var(--primary-color);
        }
        body.dark-theme .input-group-text {
            background-color: #0f172a !important;
            border-color: #475569 !important;
            color: #94a3b8 !important;
        }
        body.dark-theme .input-group-text i {
            color: #94a3b8 !important;
        }

        .cropping-container {
            max-height: 500px;
            overflow: hidden;
            background-color: #1a1a1a;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #image-to-crop {
            max-width: 100%;
        }

        @media (max-width: 768px) {
            .profile-split-layout {
                grid-template-columns: 1fr;
            }
            .profile-left-col {
                border-right: none;
                border-bottom: 1px solid #f1f5f9;
                padding-right: 0;
                padding-bottom: 30px;
                margin-bottom: 30px;
            }
        }
    </style>
</head>
<body>
    <?php if ($is_view_mode): ?>
    <!-- Standalone Profile View -->
    <div class="profile-view-wrapper">
        <div class="profile-card-standalone">
            <div class="profile-card-header">
                <a href="<?php echo $dashboard_link; ?>" class="profile-back-btn"><i class="fas fa-arrow-left"></i></a>
                
                <div class="profile-name-lg"><?php echo htmlspecialchars($user['name']); ?></div>
                <div class="profile-role-sm">
                    <?php 
                        echo ($user['role'] == 'student') 
                            ? 'Roll No: ' . htmlspecialchars($user['roll_no']) . ' • Sem: ' . htmlspecialchars($user['semester'] ?? 'N/A')
                            : 'Staff ID: ' . htmlspecialchars($user['staff_id']); 
                    ?>
                </div>
            </div>
            
            <div class="profile-card-avatar">
                <?php if($user['profile_photo']): ?>
                    <?php 
                        $photo = trim($user['profile_photo']);
                        $pic_src = (strpos($photo, 'http') === 0) ? $photo : "../uploads/profile/" . $photo;
                    ?>
                    <img src="<?php echo htmlspecialchars($pic_src); ?>" style="width: 100%; height: 100%; object-fit: cover;" referrerpolicy="no-referrer">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=random&size=150" style="width: 100%; height: 100%; object-fit: cover;">
                <?php endif; ?>
            </div>
            
            <div class="profile-card-body">
                <div class="mb-3">
                    <label class="profile-field-label">Full Name</label>
                    <div class="profile-field-value"><?php echo htmlspecialchars($user['name']); ?></div>
                </div>
                
                <div class="mb-3">
                    <label class="profile-field-label">Email Address</label>
                    <div class="profile-field-value" style="font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>


            </div>
        </div>
    </div>
    
    <!-- Minimal Scripts for View Mode -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js?v=<?php echo time(); ?>"></script>
    
    <?php else: ?>
    <!-- Dashboard Layout (Edit Mode) -->
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4><img src="../assets/img/ocmslogo.png" alt="Logo" style="height: 75px;"></h4>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Menu</div>
                <?php if ($user['role'] == 'student'): ?>
                    <a href="../student/dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                    <a href="../student/add_case.php"><i class="fas fa-plus-circle"></i> Add Cases</a>
                    <a href="../student/my_cases.php"><i class="fas fa-file-alt"></i> My Cases</a>
                    <a href="../student/approved_cases.php"><i class="fas fa-check-circle"></i> Approved Cases</a>
                    <a href="../student/rejected_cases.php"><i class="fas fa-times-circle"></i> Rejected Cases</a>
                <?php else: ?>
                    <a href="../staff/dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                    <a href="../staff/received_cases.php"><i class="fas fa-inbox"></i> Received Cases</a>
                    <a href="../staff/approved_cases.php"><i class="fas fa-check-circle"></i> Approved</a>
                    <a href="../staff/rejected_cases.php"><i class="fas fa-times-circle"></i> Rejected</a>
                <?php endif; ?>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <?php if ($user['role'] == 'student'): ?>
                    <a href="../student/case_history.php"><i class="fas fa-history"></i> History</a>
                <?php else: ?>
                    <a href="../staff/history.php"><i class="fas fa-history"></i> History</a>
                <?php endif; ?>
                <a href="profile.php" class="<?php echo !$is_view_mode ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
                
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content" style="padding: 0px 40px;">
            <!-- Topbar -->
            <div class="topbar" style="margin-bottom: 5px; margin-top: 10px;">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="dashboard-search" placeholder="Search settings...">
                </div>
                
                <div class="user-nav">

                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    
                    <a href="profile.php?view=1" class="text-decoration-none">
                        <div class="user-profile">
                            <div class="avatar shadow-sm" style="overflow: hidden; <?php echo ($user['role'] == 'staff') ? 'background: var(--secondary-color);' : ''; ?>">
                                <?php if($user['profile_photo']): ?>
                                    <?php 
                                        $photo = trim($user['profile_photo']);
                                        $pic_src = (strpos($photo, 'http') === 0) ? $photo : "../uploads/profile/" . $photo;
                                    ?>
                                    <img src="<?php echo htmlspecialchars($pic_src); ?>" style="width: 100%; height: 100%; object-fit: cover;" referrerpolicy="no-referrer">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column text-center" style="line-height: 1.2;">
                                <span class="fw-bold text-dark small" style="font-size: 0.85rem;"><?php echo htmlspecialchars($user['name']); ?></span>
                                <span class="text-muted" style="font-size: 0.75rem;"><?php echo ($user['role'] == 'student') ? 'Student' : 'Staff'; ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="settings-header" style="margin-bottom: 15px;">
                <div>
                    <h2 style="font-size: 1.75rem; color: var(--text-color); margin-bottom: 5px; letter-spacing: -0.5px;">Account Settings</h2>
                    <p style="color: var(--text-muted); margin-bottom: 0;">Manage your profile information and security preferences.</p>
                </div>
            </div>
            
            <?php if($success): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert" style="background-color: #ecfdf5; color: #065f46;">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert" style="background-color: #fef2f2; color: #991b1b;">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="profile.php" method="POST" enctype="multipart/form-data" id="profile-form">
                <div class="row g-4">
                    <!-- Left Column: Profile Card -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100" style="background: var(--card-bg); border-radius: 20px;">
                            <div class="card-body p-4 text-center">
                                <div class="position-relative d-inline-block mb-3">
                                    <div class="profile-img-container mx-auto" style="width: 140px; height: 140px; border: 4px solid var(--bg-color); border-radius: 50%; box-shadow: 0 5px 15px rgba(0,0,0,0.08); overflow: hidden;">
                                        <?php if($user['profile_photo']): ?>
                                            <?php 
                                                $photo = trim($user['profile_photo']);
                                                $pic_src = (strpos($photo, 'http') === 0) ? $photo : "../uploads/profile/" . $photo;
                                            ?>
                                            <img src="<?php echo htmlspecialchars($pic_src); ?>" id="preview" referrerpolicy="no-referrer" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=random&size=150" id="preview" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Floating Edit Button -->
                                    <?php if($user['role'] != 'student'): ?>
                                    <label for="photo-input" class="d-flex align-items-center justify-content-center shadow-sm" 
                                           style="position: absolute; bottom: 5px; right: 5px; width: 35px; height: 35px; background: #10b981; border-radius: 50%; border: 3px solid var(--card-bg); cursor: pointer; z-index: 10; transition: transform 0.2s;">
                                        <i class="fas fa-pen text-white" style="font-size: 12px;"></i>
                                    </label>

                                    <input type="file" name="profile_photo" id="photo-input" hidden accept="image/*">
                                    <?php endif; ?>
                                    <input type="hidden" name="cropped_image" id="cropped-image-input">
                                    <input type="hidden" name="remove_photo" id="remove-photo-input" value="0">
                                </div>
                                
                                <!-- Remove Photo Button -->
                                <?php if($user['profile_photo'] && $user['role'] != 'student'): ?>
                                <div class="mt-2 mb-2">
                                    <button type="button" id="remove-photo-btn" class="btn btn-sm btn-link text-danger text-decoration-none fw-bold" style="font-size: 0.75rem;">
                                        <i class="fas fa-trash-alt me-1"></i> Remove Photo
                                    </button>
                                </div>
                                <?php endif; ?>
                                
                                
                                <h5 class="fw-bold mb-1" style="color: var(--text-color);"><?php echo htmlspecialchars($user['name']); ?></h5>
                                <p class="text-muted mb-3"><?php echo ($user['role'] == 'student') ? 'Student' : 'Staff Member'; ?></p>
                                
                                <div class="d-flex justify-content-center gap-2 mb-4">
                                    <span class="badge rounded-pill bg-soft-primary text-primary px-3 py-2">
                                        <?php echo ($user['role'] == 'student') ? htmlspecialchars($user['roll_no']) : htmlspecialchars($user['staff_id']); ?>
                                    </span>
                                </div>
                                
                                
                                <hr style="opacity: 0.1;">
                                
                                <div class="text-start mt-4">
                                    <label class="form-label text-uppercase small text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Email Address</label>
                                    <div class="d-flex align-items-center p-3 rounded-3" style="background: var(--bg-color);">
                                        <i class="fas fa-envelope text-muted me-3"></i>
                                        <span style="color: var(--text-color); font-weight: 500; font-size: 0.9rem; word-break: break-all;"><?php echo htmlspecialchars($user['email']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Settings Forms -->
                    <div class="col-lg-8">
                        <!-- Personal Details -->
                        <div class="card border-0 shadow-sm mb-4" style="background: var(--card-bg); border-radius: 20px;">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                <h5 class="fw-bold mb-0" style="color: var(--text-color);"><i class="fas fa-user-circle me-2 text-primary"></i> Personal Details</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="form-control" required <?php echo ($user['role'] == 'student') ? 'readonly style="background-color: var(--bg-color);"' : ''; ?>>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo ($user['role'] == 'student') ? 'Roll Number' : 'Staff ID'; ?></label>
                                        <input type="text" name="roll_no" value="<?php echo ($user['role'] == 'student') ? htmlspecialchars($user['roll_no']) : htmlspecialchars($user['staff_id']); ?>" class="form-control" readonly style="background-color: var(--bg-color);">
                                    </div>
                                    
                                    <?php if ($user['role'] == 'student'): ?>
                                    <div class="col-md-6">
                                        <label class="form-label">Semester</label>
                                        <input type="text" name="semester" value="<?php echo htmlspecialchars($user['semester'] ?? ''); ?>" class="form-control" <?php echo ($user['role'] == 'student') ? 'readonly style="background-color: var(--bg-color);"' : ''; ?>>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Department</label>
                                        <input type="text" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" class="form-control" <?php echo ($user['role'] == 'student') ? 'readonly style="background-color: var(--bg-color);"' : ''; ?>>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Appearance -->
                        <div class="card border-0 shadow-sm mb-4" style="background: var(--card-bg); border-radius: 20px;">
                            <div class="card-header bg-transparent border-0 pt-3 px-3 pb-0">
                                <h5 class="fw-bold mb-0" style="color: var(--text-color);"><i class="fas fa-paint-brush me-2 text-warning"></i> Appearance</h5>
                            </div>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-start align-items-center text-center py-1 gap-4 ms-3">
                                    <div class="theme-option">
                                        <label class="fw-bold mb-2 d-block small" for="theme-light" style="color: var(--text-color); cursor: pointer;">Light</label>
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input theme-radio" type="radio" name="theme" id="theme-light" value="light" style="transform: scale(1.1); cursor: pointer;">
                                        </div>
                                    </div>
                                    <div class="theme-option">
                                        <label class="fw-bold mb-2 d-block small" for="theme-dark" style="color: var(--text-color); cursor: pointer;">Dark</label>
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input theme-radio" type="radio" name="theme" id="theme-dark" value="dark" style="transform: scale(1.1); cursor: pointer;">
                                        </div>
                                    </div>
                                    <!-- System option removed -->
                                </div>
                                <style>
                                    /* Custom Radio Styles match screenshot */
                                    .theme-radio:checked {
                                        background-color: #ef4444 !important; /* Red per reference */
                                        border-color: #ef4444 !important;
                                        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3cpath stroke='%23fff' stroke-width='1.5' d='M-2.5 0.5l1.5 1.5 3.5 -3.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3e%3c/svg%3e");
                                    }
                                    .theme-radio {
                                        border: 2px solid #cbd5e1;
                                        width: 1.5em;
                                        height: 1.5em;
                                        margin-top: 0;
                                    }
                                    .theme-option:hover label {
                                        opacity: 0.8;
                                    }
                                    
                                    /* Dark Theme Adjustments */
                                    body.dark-theme .theme-radio {
                                        border-color: #475569;
                                        background-color: transparent;
                                    }
                                    body.dark-theme .theme-radio:checked {
                                        background-color: #ef4444 !important;
                                        border-color: #ef4444 !important;
                                    }
                                </style>
                            </div>
                        </div>

                        <!-- Security -->
                        <div class="card border-0 shadow-sm mb-4" style="background: var(--card-bg); border-radius: 20px;">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                <h5 class="fw-bold mb-0" style="color: var(--text-color);"><i class="fas fa-lock me-2 text-danger"></i> Security</h5>
                            </div>
                            <div class="card-body p-4">
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
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="text-end mb-4">
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold" style="border-radius: 12px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Cropping Modal -->
    <div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cropperModalLabel">Crop Profile Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="cropping-container">
                        <img id="image-to-crop" src="">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="crop-btn">Crop & Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Remove Photo Confirmation Modal -->
    <div class="modal fade" id="removePhotoModal" tabindex="-1" aria-labelledby="removePhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="removePhotoModalLabel">Remove Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-0 text-muted">Are you sure you want to remove your profile picture?</p>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4 gap-2">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" style="border-radius: 12px; font-weight: 600;">Cancel</button>
                    <button type="button" class="btn btn-danger px-4" id="confirm-remove-btn" style="border-radius: 12px; font-weight: 600;">Remove</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script src="../assets/js/theme.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/search.js"></script>
    <!-- Notifications.js might need the bell, checking for existence is safer or we'll just let it fail silently in view mode as bell isn't there -->
    <script src="../assets/js/notifications.js"></script>
    <script>
        // Check for elements before initializing cropper to avoid errors in view mode
        const photoInput = document.getElementById('photo-input');
        if (photoInput) {
            let cropper;
            const imageToCrop = document.getElementById('image-to-crop');
            const cropperModal = new bootstrap.Modal(document.getElementById('cropperModal'));
            const cropBtn = document.getElementById('crop-btn');
            const previewImg = document.getElementById('preview');
            const croppedInput = document.getElementById('cropped-image-input');

            photoInput.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        imageToCrop.src = event.target.result;
                        cropperModal.show();
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });

            const cropperModalEl = document.getElementById('cropperModal');
            cropperModalEl.addEventListener('shown.bs.modal', function() {
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1,
                    viewMode: 2,
                    autoCropArea: 1,
                });
            });

            cropperModalEl.addEventListener('hidden.bs.modal', function() {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            });

            cropBtn.addEventListener('click', function() {
                if (cropper) {
                    const canvas = cropper.getCroppedCanvas({
                        width: 400,
                        height: 400,
                    });
                    const croppedData = canvas.toDataURL('image/png');
                    previewImg.src = croppedData;
                    croppedInput.value = croppedData;
                    cropperModal.hide();
                }
            });
        }

        // Handle Photo Removal - Moved outside to ensure robustness
        const removePhotoModalEl = document.getElementById('removePhotoModal');
        if (removePhotoModalEl) {
            const removePhotoModal = new bootstrap.Modal(removePhotoModalEl);
            const removePhotoBtn = document.getElementById('remove-photo-btn');
            const confirmRemoveBtn = document.getElementById('confirm-remove-btn');
            const removePhotoInput = document.getElementById('remove-photo-input');
            const profileForm = document.getElementById('profile-form');

            if (removePhotoBtn) {
                removePhotoBtn.addEventListener('click', function() {
                    console.log('Remove photo button clicked');
                    removePhotoModal.show();
                });
            }

            if (confirmRemoveBtn) {
                confirmRemoveBtn.addEventListener('click', function() {
                    console.log('Confirm remove photo clicked');
                    if (removePhotoInput && profileForm) {
                        removePhotoInput.value = '1';
                        console.log('Set remove_photo to 1, submitting form...');
                        profileForm.submit();
                    } else {
                        console.error('Missing removePhotoInput or profileForm');
                    }
                });
            }
        } else {
            console.error('removePhotoModal element not found');
        }

    </script>
    <?php endif; ?>
</body>
</html>
