<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query user by email ONLY to get role and other details
    $sql = "SELECT id, name, password, role, roll_no, staff_id, profile_photo FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $verify = password_verify($password, $user['password']);
        file_put_contents('../debug_login.txt', date('Y-m-d H:i:s') . " - Login: Email=$email. Verify=" . ($verify ? 'TRUE' : 'FALSE') . ". Role=" . $user['role'] . "\n", FILE_APPEND);
        
        if ($verify) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $email;
            $_SESSION['profile_photo'] = $user['profile_photo'];

            // Update last login timestamp
            $login_update = $conn->query("UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);

            if ($user['role'] == 'student') {
                $_SESSION['roll_no'] = $user['roll_no'];
                $redirect = "../student/dashboard.php";
            } elseif ($user['role'] == 'staff') {
                $_SESSION['staff_id'] = $user['staff_id'];
                $redirect = "../staff/dashboard.php";
            } elseif ($user['role'] == 'admin') {
                $redirect = "../admin/dashboard.php";
            } else {
                $error = "Unauthorized role!";
            }

            if (isset($redirect)) {
                header("Location: " . $redirect);
                exit();
            }
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OCMS</title>
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
            height: auto;
            min-height: 600px;
            max-height: 90vh;
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
            padding: 20px 40px 40px 40px;
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

        .hero-content { 
            position: relative; 
            z-index: 2; 
            display: flex; 
            flex-direction: column; 
            align-items: flex-start;
        }
        
        .logo-img { 
            height: 40px; 
            width: auto; 
            margin-bottom: 60px;
            margin-top: 0;
            margin-left: -25px;
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
            padding: 40px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .form-content-wrapper {
            margin: auto 0;
            width: 100%;
        }

        .form-header { margin-bottom: 25px; text-align: center; }
        .form-header h2 { font-size: 1.8rem; font-weight: 700; color: var(--text-main); margin-bottom: 8px; }
        .form-header p { color: var(--text-muted); font-size: 0.95rem; }

        .form-label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; display: block; }

        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
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
            padding: 3px;
            border-radius: 10px;
            margin-bottom: 25px;
            position: relative;
        }
        .role-option {
            flex: 1; text-align: center; padding: 8px; font-size: 0.85rem; font-weight: 600;
            color: var(--text-muted); cursor: pointer; z-index: 2; transition: var(--transition);
        }
        .role-option.active { color: var(--primary); }
        .role-slider {
            position: absolute; top: 4px; left: 4px; width: calc(33.33% - 4px); height: calc(100% - 8px);
            background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); z-index: 1;
        }
        .role-selector[data-role="staff"] .role-slider { transform: translateX(100%); }
        .role-selector[data-role="admin"] .role-slider { transform: translateX(200%); }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 20px;
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
            padding: 14px; border-radius: 12px; margin-bottom: 25px; font-size: 0.9rem;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
        
        .hero-back-btn {
            font-size: 1.25rem;
            color: #4e5153ff;
            text-decoration: none;
            margin-bottom: 10px;
            margin-left: -20px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            position: relative;
            z-index: 10;
            opacity: 0.6;
        }
        
        .hero-back-btn:hover {
            transform: translateX(-5px);
            color: var(--primary);
            opacity: 1;
        }

        @media (max-width: 992px) {
            .auth-card { width: 450px; height: auto; flex-direction: column; }
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
                <a href="../index.php" class="hero-back-btn" title="Back to Home">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <img src="../assets/img/ocmslogo.png" alt="Logo" class="logo-img">
                <h1>Hello,<br>welcome!</h1>
                <p>Submit your academic requests in just a few clicks. Track your case progress anytime, anywhere. Simple, secure, and built for your convenience.</p>
            </div>
        </div>

        <!-- Right Form Side -->
        <div class="form-panel">
            <div class="form-content-wrapper">
            <div class="form-header">
                <h2>Login</h2>
                <p>Enter your credentials to access your account.
                   Login to continue</p>
            </div>

            <?php
            if(isset($_SESSION['success'])) {
                echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> '.$_SESSION['success'].'</div>';
                unset($_SESSION['success']);
            }
            if(isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> '.$_SESSION['error'].'</div>';
                unset($_SESSION['error']);
            }
            if(isset($error)) {
                echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> '.$error.'</div>';
            }
            ?>

            <form method="POST" action="">
                <label class="form-label">Email address</label>
                <div class="input-group-custom">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control-custom" autocomplete="off" required>
                </div>

                <label class="form-label">Password</label>
                <div class="input-group-custom">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control-custom" required>
                </div>

                <button type="submit" class="btn-submit">Login</button>
                
                <a href="google_login.php" class="btn-google">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20px" height="20px">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                    </svg>
                    Continue with Google
                </a>
                
                <!-- Registration disabled: Only admin can create accounts -->

            </form>
            </div>
        </div>
    </div>

</body>
</html>
