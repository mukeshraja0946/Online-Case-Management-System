<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['google_data'])) {
    header("Location: login.php");
    exit();
}

$google_data = $_SESSION['google_data'];
$name = $google_data['name'];
$email = $google_data['email'];
$picture = $google_data['picture'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $roll_no = ($role == 'student') ? $_POST['roll_no'] : NULL;
    $staff_id = ($role == 'staff') ? $_POST['staff_id'] : NULL;

    // Validate Staff ID if role is staff
    $staff_valid = true;
    if ($role == 'staff') {
        $check_staff = "SELECT id FROM valid_staff_ids WHERE staff_id = ?";
        $stmt_staff = $conn->prepare($check_staff);
        $stmt_staff->bind_param("s", $staff_id);
        $stmt_staff->execute();
        $stmt_staff->store_result();
        
        if ($stmt_staff->num_rows == 0) {
            $staff_valid = false;
            $error = "Invalid Staff ID! Access Denied.";
        } else {
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

    if ($staff_valid) {
        $sql = "INSERT INTO users (name, email, password, role, roll_no, staff_id, profile_photo) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $name, $email, $password, $role, $roll_no, $staff_id, $picture);

        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['name'] = $name;
            $_SESSION['role'] = $role;
            $_SESSION['profile_photo'] = $picture;
            
            if ($role == 'student') {
                $_SESSION['roll_no'] = $roll_no;
                header("Location: ../student/dashboard.php");
            } else {
                $_SESSION['staff_id'] = $staff_id;
                header("Location: ../staff/dashboard.php");
            }
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
    <title>Complete Profile - OCMS</title>
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

        body {
            font-family: 'Outfit', sans-serif;
            background: #f1f5f9;
            height: 100vh;
            display: grid;
            place-items: center;
        }

        .auth-card {
            width: 500px;
            max-width: 90vw;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: block;
            border: 3px solid var(--primary-light);
            padding: 3px;
        }

        .form-label { font-weight: 600; color: var(--text-muted); font-size: 0.9rem; margin-bottom: 5px; }
        .form-control { border-radius: 10px; padding: 10px 15px; border: 1px solid #e2e8f0; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); }

        .btn-primary {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            background: var(--primary);
            border: none;
            margin-top: 20px;
        }
        
        /* Role Selector */
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
            position: absolute; top: 2px; left: 2px; width: calc(50% - 2px); height: calc(100% - 4px);
            background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.3s ease; z-index: 1;
        }
        .role-selector[data-role="staff"] .role-slider { transform: translateX(100%); }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="text-center mb-4">
            <img src="<?php echo $picture; ?>" class="profile-img" alt="Profile">
            <h4>Welcome, <?php echo $name; ?>!</h4>
            <p class="text-muted small">Please complete your profile to continue.</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="text" class="form-control" value="<?php echo $email; ?>" readonly>
            </div>

            <label class="form-label">Select Role</label>
            <div class="role-selector" id="roleSelector" data-role="student">
                <div class="role-slider"></div>
                <div class="role-option active" onclick="updateRole('student')">Student</div>
                <div class="role-option" onclick="updateRole('staff')">Staff</div>
            </div>
            <input type="hidden" name="role" id="roleInput" value="student">

            <div id="rollNoDiv" class="mb-3">
                <label class="form-label">Roll Number</label>
                <input type="text" name="roll_no" class="form-control" required>
            </div>

            <div id="staffIdDiv" class="mb-3" style="display:none;">
                <label class="form-label">Staff ID</label>
                <input type="text" name="staff_id" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Create Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Complete Registration</button>
        </form>
    </div>

    <script>
        function updateRole(role) {
            const selector = document.getElementById('roleSelector');
            const input = document.getElementById('roleInput');
            const options = selector.querySelectorAll('.role-option');
            const rollNoDiv = document.getElementById('rollNoDiv');
            const staffIdDiv = document.getElementById('staffIdDiv');
            const rollInput = rollNoDiv.querySelector('input');
            const staffInput = staffIdDiv.querySelector('input');
            
            // Update UI
            selector.setAttribute('data-role', role);
            input.value = role;
            
            // Toggle Fields
            if (role === 'student') {
                rollNoDiv.style.display = 'block';
                staffIdDiv.style.display = 'none';
                rollInput.required = true;
                staffInput.required = false;
            } else {
                rollNoDiv.style.display = 'none';
                staffIdDiv.style.display = 'block';
                rollInput.required = false;
                staffInput.required = true;
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
