<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $sql = "SELECT id, name, password, role, roll_no FROM users WHERE email = ? AND role = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            if ($user['role'] == 'student') {
                $_SESSION['roll_no'] = $user['roll_no'];
                $redirect = "../student/dashboard.php";
            } else {
                $redirect = "../staff/dashboard.php";
            }



            header("Location: " . $redirect);
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found or role mismatch!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }
        .container-fluid, .row {
            height: 100%;
        }
        .left-panel {
            background: linear-gradient(135deg, #0d6efd 0%, #004e92 100%);
            color: white;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 10%;
            overflow: hidden;
        }
        /* Decorative lines similar to the image */
        .left-panel::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(45deg, rgba(255, 255, 255, 0.05) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.05) 50%, rgba(255, 255, 255, 0.05) 75%, transparent 75%, transparent);
            background-size: 100px 100px;
            opacity: 0.3;
        }
        .left-content {
            z-index: 2;
        }
        .left-title {
            font-size: 4rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 20px;
        }
        .left-desc {
            font-size: 1.1rem;
            opacity: 0.8;
            margin-bottom: 30px;
        }
        .btn-view-more {
            background: white;
            color: #0d6efd;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            width: fit-content;
            text-decoration: none;
            transition: transform 0.3s;
        }
        .btn-view-more:hover {
            transform: translateX(5px);
            color: #0d6efd;
        }
        
        /* Right Panel */
        .right-panel {
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5%;
        }
        .login-form-container {
            width: 100%;
            max-width: 400px;
        }
        .form-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
        }
        .input-group-text {
            background: #f1f3f5;
            border: none;
            color: #adb5bd;
        }
        .form-control, .form-select {
            background-color: #f1f3f5;
            border: none;
            padding: 12px;
            font-size: 1rem;
        }
        .form-control:focus, .form-select:focus {
            background: #e9ecef;
            box-shadow: none;
        }
        .btn-login {
            background: white;
            color: #0d6efd;
            border: 1px solid #dee2e6;
            padding: 12px;
            font-weight: 700;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }
        .btn-login:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        .btn-signup-main {
            background: linear-gradient(90deg, #0d6efd 0%, #004e92 100%);
            color: white;
            padding: 15px;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
            border: none;
            margin-top: 20px;
            display: block;
            text-align: center;
            text-decoration: none;
        }
        .btn-signup-main:hover {
            opacity: 0.9;
            color: white;
        }
        .forgot-link {
            font-size: 0.85rem;
            text-decoration: none;
            color: #6c757d;
        }
        .forgot-link:hover {
            color: #0d6efd;
        }
        .role-selection {
            background-color: #f1f3f5;
            border-radius: 50px;
            padding: 5px;
            display: flex;
            position: relative;
        }
        .role-option {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
            z-index: 1;
        }
        .btn-check:checked + .role-option {
            background-color: white;
            color: #0d6efd;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .role-option i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Left Side -->
            <div class="col-md-6 left-panel d-none d-md-flex">
                <div class="left-content">
                    <div class="mb-4">
                        <img src="../assets/img/ocms.png" alt="Logo" style="height: 150px; margin-left: -30px;" class="me-2"> <span class="h4 align-middle"></span>
                    </div>
                    <h1 class="left-title">Hello,<br>welcome!</h1>
                    <p class="left-desc">Streamline your academic requests with our digital case management system.</p>
                    <a href="../index.php" class="btn-view-more">View more</a>
                </div>
            </div>

            <!-- Right Side -->
            <div class="col-md-6 right-panel">
                <div class="login-form-container">
                    
                    <?php
                    if(isset($_SESSION['success'])) {
                        echo '<div class="alert alert-success py-2 text-center small"><i class="fas fa-check-circle me-1"></i> '.$_SESSION['success'].'</div>';
                        unset($_SESSION['success']);
                    }
                    if(isset($error)) {
                        echo '<div class="alert alert-danger py-2 text-center small"><i class="fas fa-exclamation-circle me-1"></i> '.$error.'</div>';
                    }
                    ?>

                    <form method="POST" action="" class="mb-4">
                        <div class="mb-3">
                            <label class="form-label">Email address</label>
                            <input type="email" name="email" class="form-control rounded-3" autocomplete="off" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control rounded-3" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label d-block mb-2">Role</label>
                            <div class="role-selection">
                                <input type="radio" class="btn-check" name="role" id="role-student" value="student" required checked>
                                <label class="role-option" for="role-student">
                                    <i class="fas fa-user-graduate"></i> Student
                                </label>

                                <input type="radio" class="btn-check" name="role" id="role-staff" value="staff">
                                <label class="role-option" for="role-staff">
                                    <i class="fas fa-chalkboard-teacher"></i> Staff
                                </label>
                            </div>
                        </div>



                        <button type="submit" class="btn btn-login w-100">Login</button>
                    </form>

                    <div class="text-center">
                        <small class="text-muted d-block mb-2">Not a member yet?</small>
                        <a href="register.php" class="btn-signup-main">Sign up</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
