<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['name']; // Use session data for reliability
$roll_no = $_SESSION['roll_no'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $case_type = $_POST['case_type'];
    $description = $_POST['description'];

    // File Upload Logic
    $attachment = NULL;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . "_" . basename($_FILES['attachment']['name']);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $allowed_types = array("jpg", "jpeg", "png", "pdf");
        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
                $attachment = $file_name;
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error = "File type not supported (Only JPG, JPEG, PNG, PDF)";
        }
    }

    if (!isset($error)) {
        $sql = "INSERT INTO cases (student_id, student_name, roll_no, case_type, description, attachment, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
             $error = "Database Error: " . $conn->error;
        } else {
            $stmt->bind_param("isssss", $student_id, $student_name, $roll_no, $case_type, $description, $attachment);

            if ($stmt->execute()) {
                $success = "Case submitted successfully!";
                
                // Add notification for staff members
                $case_id = $conn->insert_id;
                $notif_msg = "New case submitted by " . $student_name . " (Type: " . $case_type . ")";
                
                // Fetch all staff members
                $staff_sql = "SELECT id FROM users WHERE role = 'staff'";
                $staff_res = $conn->query($staff_sql);
                
                if ($staff_res) {
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'new_case')");
                    while ($staff = $staff_res->fetch_assoc()) {
                        $notif_stmt->bind_param("is", $staff['id'], $notif_msg);
                        $notif_stmt->execute();
                    }
                }
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Case - OCMS</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS -->
    <link href="../assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">

</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4><img src="../assets/img/ocms.png" alt="Logo" style="height: 75px;"></h4>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Menu</div>
                <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="add_case.php" class="active"><i class="fas fa-plus-circle"></i> Add Cases</a>
                <a href="my_cases.php"><i class="fas fa-file-alt"></i> My Cases</a>
                <a href="approved_cases.php"><i class="fas fa-check-circle"></i> Approved Cases</a>
                <a href="rejected_cases.php"><i class="fas fa-times-circle"></i> Rejected Cases</a>
                <a href="case_history.php"><i class="fas fa-history"></i> History</a>
                
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-9">
                        <div class="card shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">
                            <div class="card-header text-white text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 20px 20px 0 0;">
                                <h4 class="mb-0 fw-bold">Add New Case</h4>
                            </div>
                            <div class="card-body">
                                <?php if(isset($success)): ?>
                                    <div class="alert alert-success"><?php echo $success; ?></div>
                                <?php endif; ?>
                                <?php if(isset($error)): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>

                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label">Student Name</label>
                                        <input type="text" name="student_name" value="<?php echo htmlspecialchars($student_name); ?>" class="form-control" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Roll Number</label>
                                        <input type="text" name="roll_no" value="<?php echo htmlspecialchars($roll_no); ?>" class="form-control" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Case Type</label>
                                        <select name="case_type" class="form-select" required>
                                            <option value="">Select Type</option>
                                            <option value="Academic">Academic</option>
                                            <option value="Disciplinary">Disciplinary</option>
                                            <option value="Hostel">Hostel</option>
                                            <option value="Library">Library</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Upload File (Optional)</label>
                                        <input type="file" name="attachment" class="form-control" accept=".pdf, .jpg, .jpeg, .png">
                                        <small class="text-muted">Allowed: JPG, PNG, PDF</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="5" required></textarea>
                                    </div>

                                    <button type="submit" class="btn text-white fw-bold px-4 py-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">Submit Case</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
