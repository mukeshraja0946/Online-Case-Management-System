<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$admin_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';
$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $roll_no = ($role == 'student') ? $_POST['roll_no'] : NULL;
    $staff_id = ($role == 'staff') ? $_POST['staff_id'] : NULL;
    $department = ($role == 'admin') ? NULL : $_POST['department'];

    $profile_photo = NULL;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $upload_dir = "../uploads/profile/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_info = pathinfo($_FILES['profile_photo']['name']);
        $ext = strtolower($file_info['extension']);
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $new_file_name = "profile_" . time() . "_" . rand(1000, 9999) . "." . $ext;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_dir . $new_file_name)) {
                $profile_photo = $new_file_name;
            } else {
                $error_msg = "Failed to upload profile picture.";
            }
        } else {
            $error_msg = "Invalid profile picture type. Only JPG, PNG, GIF allowed.";
        }
    }

    // Check if email already exists
    $check_email = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // Validate Staff ID if role is staff
    $staff_valid = true;
    if ($role == 'staff') {
        // Check if Staff ID exists in valid_staff_ids table
        $check_staff = "SELECT id FROM valid_staff_ids WHERE staff_id = ?";
        $stmt_staff = $conn->prepare($check_staff);
        $stmt_staff->bind_param("s", $staff_id);
        $stmt_staff->execute();
        $stmt_staff->store_result();
        
        if ($stmt_staff->num_rows == 0) {
            $staff_valid = false;
            $error_msg = "Invalid Staff ID! Access Denied.";
        } else {
            // Check if Staff ID is already registered
            $check_taken = "SELECT id FROM users WHERE staff_id = ?";
            $stmt_taken = $conn->prepare($check_taken);
            $stmt_taken->bind_param("s", $staff_id);
            $stmt_taken->execute();
            if ($stmt_taken->get_result()->num_rows > 0) {
                $staff_valid = false;
                $error_msg = "Staff ID already registered!";
            }
        }
    }

    if ($stmt->num_rows > 0) {
        $error_msg = "Email already exists!";
    } elseif (!$staff_valid && empty($error_msg)) {
        // Error already set above
    } elseif (empty($error_msg)) {
        $sql = "INSERT INTO users (name, email, password, role, roll_no, staff_id, department, profile_photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $name, $email, $password, $role, $roll_no, $staff_id, $department, $profile_photo);

        if ($stmt->execute()) {
            header("Location: dashboard.php?msg=" . urlencode("User created successfully!"));
            exit();
        } else {
            $error_msg = "Error: " . $stmt->error;
        }
    }
}

// Get Role from URL
$preselected_role = isset($_GET['role']) && in_array($_GET['role'], ['student', 'staff', 'admin']) ? $_GET['role'] : 'student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Admin OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #eff6ff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100vh;
            width: 100vw;
            font-family: 'Outfit', sans-serif;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        /* Background Decorations */
        .bg-decoration {
            position: fixed;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            filter: blur(100px);
            z-index: -1;
            opacity: 0.5;
        }
        .bg-1 { top: -100px; left: -100px; background: #dbeafe; }
        .bg-2 { bottom: -100px; right: -100px; background: #e0e7ff; }

        .auth-card {
            display: flex;
            width: 950px;
            max-width: 90vw;
            height: 750px;
            max-height: 94vh;
            background: white;
            border-radius: 30px;
            overflow: hidden;
            border: none;
            backdrop-filter: blur(10px);
            box-shadow: 
                0 0 1px rgba(0, 0, 0, 0.12),
                0 0 15px rgba(0, 0, 0, 0.06),
                0 0 30px rgba(0, 0, 0, 0.08);
        }

        /* Left Side: Brand/Hero */
        .hero-panel {
            flex: 1;
            background: linear-gradient(135deg, #e0f2fe 0%, #ffffff 100%);
            padding: 40px;
            color: #0f172a;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            isolation: isolate;
        }

        .hero-panel::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%232563eb' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2v-4h4v-2h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2v-4h4v-2H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .hero-content { position: relative; z-index: 2; }
        
        .logo-img { 
            height: 40px; 
            width: auto; 
            margin-bottom: 200px;
            margin-top: -25px;
            margin-left: -20px;
            object-fit: contain;
            mix-blend-mode: multiply;
        }

        .hero-content h1 { font-size: 2.8rem; font-weight: 700; margin-bottom: 15px; line-height: 1.1; }
        .hero-content p { font-size: 0.95rem; line-height: 1.6; opacity: 0.9; margin-bottom: 25px; }

        .btn-view-more {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition);
            margin-top: 10px;
        }
        .btn-view-more i { font-size: 0.9rem; transition: transform 0.3s ease; }
        .btn-view-more:hover { color: #1d4ed8; }
        .btn-view-more:hover i { transform: translateX(-5px); }

        /* Right Side: Form */
        .form-panel {
            flex: 0.8;
            padding: 30px 40px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            overflow-y: auto;
        }

        .form-header { margin-bottom: 20px; text-align: center; }
        .form-header h2 { font-size: 1.7rem; font-weight: 700; color: var(--text-main); margin-bottom: 5px; }
        .form-header p { color: var(--text-muted); font-size: 0.9rem; }

        .form-label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 5px; display: block; }

        .input-group-custom {
            position: relative;
            margin-bottom: 15px;
        }

        .input-group-custom i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1rem;
        }

        .form-control-custom {
            width: 100%;
            padding: 10px 12px 10px 40px;
            background: #f1f5f9;
            border: 2px solid transparent;
            border-radius: 10px;
            font-size: 0.9rem;
            color: var(--text-main);
            transition: var(--transition);
        }

        .form-control-custom:focus {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .file-custom {
            padding: 9px 12px 9px 40px;
            font-size: 0.85rem;
        }
        
        .file-custom::file-selector-button {
            border: none;
            background: var(--primary-light);
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-family: inherit;
            margin-right: 12px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .file-custom::file-selector-button:hover {
            background: #dbeafe;
        }

        /* Segmented Role Selector */
        .role-selector {
            display: flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 12px;
            margin-bottom: 20px;
            position: relative;
            height: 45px;
        }
        .role-option {
            flex: 1; 
            text-align: center; 
            padding: 8px; 
            font-size: 0.9rem; 
            font-weight: 600;
            color: var(--text-muted); 
            cursor: pointer; 
            z-index: 2; 
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .role-option.active { color: var(--primary); }
        .role-slider {
            position: absolute; 
            top: 4px; 
            left: 4px; 
            width: calc(33.33% - 4px); 
            height: calc(100% - 8px);
            background: white; 
            border-radius: 10px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            z-index: 1;
        }
        .role-selector[data-role="staff"] .role-slider { transform: translateX(100%); }
        .role-selector[data-role="admin"] .role-slider { transform: translateX(200%); }

        .btn-submit {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            margin-top: 10px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
        }
        .btn-submit:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -6px rgba(37, 99, 235, 0.5);
        }

        .alert {
            padding: 10px 14px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }

        @media (max-width: 992px) {
            .auth-card { width: 450px; height: auto; flex-direction: column; overflow-y: auto; }
            .hero-panel { display: none; }
            .form-panel { padding: 40px; }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <!-- Left Hero Side -->
        <div class="hero-panel">
            <div class="hero-content">
                <img src="../assets/img/ocmslogo.png" alt="Logo" class="logo-img">
                <h1>Add New<br>User</h1>
                <p>Manually create accounts for students and staff. Ensure all details are correct before submission.</p>
                <a href="dashboard.php" class="btn-view-more">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Right Form Side -->
        <div class="form-panel">
            <div class="form-header">
                <h2>Create Account</h2>
                <p>Register a new user in the system.</p>
            </div>

            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <label class="form-label">Full Name</label>
                <div class="input-group-custom">
                    <i class="fas fa-user"></i>
                    <input type="text" name="name" class="form-control-custom" autocomplete="off" required>
                </div>

                <label class="form-label">Email Address</label>
                <div class="input-group-custom">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control-custom" autocomplete="off" required>
                </div>

                <label class="form-label">Select Role</label>
                <div class="role-selector" id="roleSelector" data-role="<?php echo $preselected_role; ?>">
                    <div class="role-slider"></div>
                    <div class="role-option <?php echo $preselected_role == 'student' ? 'active' : ''; ?>" onclick="updateRole('student')">Student</div>
                    <div class="role-option <?php echo $preselected_role == 'staff' ? 'active' : ''; ?>" onclick="updateRole('staff')">Staff</div>
                    <div class="role-option <?php echo $preselected_role == 'admin' ? 'active' : ''; ?>" onclick="updateRole('admin')">Admin</div>
                </div>
                <input type="hidden" name="role" id="roleInput" value="<?php echo $preselected_role; ?>">

                <!-- Conditional Fields -->
                <div id="rollNoDiv" style="display: <?php echo $preselected_role == 'student' ? 'block' : 'none'; ?>;">
                    <label class="form-label">Roll Number</label>
                    <div class="input-group-custom">
                        <i class="fas fa-id-card"></i>
                        <input type="text" name="roll_no" class="form-control-custom">
                    </div>
                </div>

                <div id="staffIdDiv" style="display: <?php echo $preselected_role == 'staff' ? 'block' : 'none'; ?>;">
                    <label class="form-label">Staff ID</label>
                    <div class="input-group-custom">
                        <i class="fas fa-id-badge"></i>
                        <input type="text" name="staff_id" class="form-control-custom">
                    </div>
                </div>

                <div id="deptDiv" style="display: <?php echo $preselected_role != 'admin' ? 'block' : 'none'; ?>;">
                    <label class="form-label">Department</label>
                    <div class="input-group-custom">
                        <i class="fas fa-building"></i>
                        <input type="text" name="department" class="form-control-custom" placeholder="e.g. Computer Science">
                    </div>
                </div>

                <label class="form-label">Password</label>
                <div class="input-group-custom">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control-custom" required>
                </div>

                <label class="form-label">Profile Picture (Optional)</label>
                <div class="input-group-custom">
                    <i class="fas fa-image"></i>
                    <input type="file" name="profile_photo" class="form-control-custom file-custom" accept="image/*">
                </div>

                <button type="submit" class="btn-submit">All Done, Create User</button>
            </form>
        </div>
    </div>

    <script>
        function updateRole(role) {
            const selector = document.getElementById('roleSelector');
            const input = document.getElementById('roleInput');
            const options = selector.querySelectorAll('.role-option');
            const rollNoDiv = document.getElementById('rollNoDiv');
            const deptDiv = document.getElementById('deptDiv');
            
            // Update UI
            selector.setAttribute('data-role', role);
            input.value = role;
            
            // Toggle Fields
            if (role === 'student') {
                rollNoDiv.style.display = 'block';
                staffIdDiv.style.display = 'none';
                deptDiv.style.display = 'block';
            } else if (role === 'staff') {
                rollNoDiv.style.display = 'none';
                staffIdDiv.style.display = 'block';
                deptDiv.style.display = 'block';
            } else {
                rollNoDiv.style.display = 'none';
                staffIdDiv.style.display = 'none';
                deptDiv.style.display = 'none';
            }
            
            // Update active class
            options.forEach(opt => {
                if(opt.innerText.toLowerCase() === role) {
                    opt.classList.add('active');
                } else {
                    opt.classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>
