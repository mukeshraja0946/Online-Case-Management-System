<?php
// Registration is disabled. Only Admins can create accounts via the Create Users tool.
header("Location: login.php");
exit();
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $roll_no = ($role == 'student') ? $_POST['roll_no'] : NULL;
    $staff_id = ($role == 'staff') ? $_POST['staff_id'] : NULL;

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
            $error = "Invalid Staff ID! Access Denied.";
        } else {
            // Check if Staff ID is already registered
            $check_taken = "SELECT id FROM users WHERE staff_id = ?";
            $stmt_taken = $conn->prepare($check_taken);
            $stmt_taken->bind_param("s", $staff_id);
            $stmt_taken->execute();
            if ($stmt_taken->get_result()->num_rows > 0) {
                $staff_valid = false;
                $error = "Staff ID already registered!";
            }
        }
    }

    if ($stmt->num_rows > 0) {
        $error = "Email already exists!";
    } elseif (!$staff_valid) {
        // Error already set above
    } else {
        $sql = "INSERT INTO users (name, email, password, role, roll_no, staff_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $name, $email, $password, $role, $roll_no, $staff_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - OCMS</title>
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

        /* Ambient Background Background Decorations */
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
            /* Refined Symmetrical All-Side Shadow */
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
        .btn-view-more:hover i { transform: translateX(5px); }

        /* Right Side: Form */
        .form-panel {
            flex: 0.8;
            padding: 30px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: hidden;
        }

        .form-header { margin-bottom: 10px; text-align: center; }
        .form-header h2 { font-size: 1.7rem; font-weight: 700; color: var(--text-main); margin-bottom: 5px; }
        .form-header p { color: var(--text-muted); font-size: 0.9rem; }

        .form-label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 5px; display: block; }

        .input-group-custom {
            position: relative;
            margin-bottom: 10px;
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

        /* Segmented Role Selector */
        .role-selector {
            display: flex;
            background: #f1f5f9;
            padding: 2px;
            border-radius: 10px;
            margin-bottom: 15px;
            position: relative;
        }
        .role-option {
            flex: 1; text-align: center; padding: 8px; font-size: 0.85rem; font-weight: 600;
            color: var(--text-muted); cursor: pointer; z-index: 2; transition: var(--transition);
        }
        .role-option.active { color: var(--primary); }
        .role-slider {
            position: absolute; top: 4px; left: 4px; width: calc(50% - 4px); height: calc(100% - 8px);
            background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); z-index: 1;
        }
        .role-selector[data-role="staff"] .role-slider { transform: translateX(100%); }

        .btn-submit {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            margin-top: 5px;
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

        .btn-google {
            width: 100%;
            padding: 8px;
            background: white;
            color: #1f1f1f;
            border: 1px solid #747775;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color .2s, border-color .2s, color .2s;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            font-family: 'Roboto', sans-serif;
        }
        .btn-google:hover {
            background: #f0f4f9;
            border-color: #1f1f1f;
            color: #1f1f1f;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .signup-prompt { text-align: center; font-size: 0.9rem; color: var(--text-muted); }
        .signup-prompt a { color: var(--primary); font-weight: 700; text-decoration: none; }

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
                <h1>Create<br>Account</h1>
                <p>Join the Online Case Management System. Submit and track your requests with ease. Professional tools for students and staff.</p>
                <a href="../index.php" class="btn-view-more">
                    <span>Back to home</span>
                    <i class="fas fa-arrow-left" style="order: -1;"></i>
                </a>
            </div>
        </div>

        <!-- Right Form Side -->
        <div class="form-panel">
            <div class="form-header">
                <h2>Register</h2>
                <p>Join OCMS to manage your cases efficiently. Register to continue</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
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
                <div class="role-selector" id="roleSelector" data-role="student">
                    <div class="role-slider"></div>
                    <div class="role-option active" onclick="updateRole('student')">Student</div>
                    <div class="role-option" onclick="updateRole('staff')">Staff</div>
                </div>
                <input type="hidden" name="role" id="roleInput" value="student">

                <!-- Conditional Fields -->
                <div id="rollNoDiv">
                    <label class="form-label">Roll Number</label>
                    <div class="input-group-custom">
                        <i class="fas fa-id-card"></i>
                        <input type="text" name="roll_no" class="form-control-custom">
                    </div>
                </div>

                <div id="staffIdDiv" style="display:none;">
                    <label class="form-label">Staff ID</label>
                    <div class="input-group-custom">
                        <i class="fas fa-id-badge"></i>
                        <input type="text" name="staff_id" class="form-control-custom">
                    </div>
                </div>

                <label class="form-label">Password</label>
                <div class="input-group-custom">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control-custom" required>
                </div>

                <button type="submit" class="btn-submit">Register Now</button>
                
                <a href="google_login.php" class="btn-google">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20px" height="20px">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                    </svg>
                    Continue with Google
                </a>
                
                <div class="signup-prompt">
                    Already have an account? <a href="login.php">Login</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateRole(role) {
            const selector = document.getElementById('roleSelector');
            const input = document.getElementById('roleInput');
            const options = selector.querySelectorAll('.role-option');
            const rollNoDiv = document.getElementById('rollNoDiv');
            const staffIdDiv = document.getElementById('staffIdDiv');
            
            // Update UI
            selector.setAttribute('data-role', role);
            input.value = role;
            
            // Toggle Fields
            if (role === 'student') {
                rollNoDiv.style.display = 'block';
                staffIdDiv.style.display = 'none';
            } else {
                rollNoDiv.style.display = 'none';
                staffIdDiv.style.display = 'block';
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
