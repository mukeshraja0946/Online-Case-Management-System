<?php
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e0eaec 0%, #cbdadb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 500px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h3 {
            font-weight: 700;
            color: #2c3e50;
        }
        .form-control, .form-select {
            padding: 11px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
        }
        .input-group-text {
            background: white;
            border-right: none;
            color: #95a5a6;
        }
        .form-control {
            border-left: none;
        }
        .btn-register {
            background: #0d6efd;
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-register:hover {
            background: #0b5ed7;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="header">
            <h3>Create Account</h3>
            <p class="text-muted small">Join OCMS to manage your cases efficiently</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger py-2 text-center small"><i class="fas fa-exclamation-circle me-1"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="name" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">I am a...</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-users-cog"></i></span>
                    <select name="role" id="role" class="form-select" onchange="toggleRollNo()" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="student">Student</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
            </div>

            <div class="mb-3" id="rollNoDiv">
                <label class="form-label text-muted small fw-bold">Roll Number</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                    <input type="text" name="roll_no" class="form-control">
                </div>
            </div>

            <div class="mb-4" id="staffIdDiv" style="display:none;">
                <label class="form-label text-muted small fw-bold">Staff ID</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                    <input type="text" name="staff_id" class="form-control">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-register w-100">Register Now</button>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted">Already have an account? <a href="login.php" class="text-decoration-none fw-bold">Login</a></small>
        </div>
    </div>

    <script>
        function toggleRollNo() {
            var role = document.getElementById('role').value;
            var rollNoDiv = document.getElementById('rollNoDiv');
            var staffIdDiv = document.getElementById('staffIdDiv');
            
            if (role === 'staff') {
                rollNoDiv.style.display = 'none';
                staffIdDiv.style.display = 'block';
            } else {
                rollNoDiv.style.display = 'block';
                staffIdDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>
