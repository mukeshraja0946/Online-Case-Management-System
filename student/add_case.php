<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
// Fetch fresh student data from DB
$u_stmt = $conn->prepare("SELECT name, roll_no, profile_photo FROM users WHERE id = ?");
$u_stmt->bind_param("i", $student_id);
$u_stmt->execute();
$u_res = $u_stmt->get_result()->fetch_assoc();

$student_name = $u_res['name'];
$roll_no = $u_res['roll_no'];
$profile_photo = $u_res['profile_photo'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $case_type = $_POST['case_type'];
    $description = $_POST['description'];
    $incident_date = $_POST['incident_date'];
    $incident_time = $_POST['incident_time'];
    $full_incident_date = $incident_date . ' ' . $incident_time . ':00';

    // File Upload Logic
    $attachment = NULL;
    $max_file_size = 20 * 1024 * 1024; // 20MB

    if (isset($_FILES['attachment'])) {
        $file_error = $_FILES['attachment']['error'];
        
        if ($file_error == 0) {
            $upload_dir = "../uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_size = $_FILES['attachment']['size'];
            $file_name = time() . "_" . basename($_FILES['attachment']['name']);
            $target_file = $upload_dir . $file_name;
            $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = array("jpg", "jpeg", "png", "pdf", "doc", "docx", "xls", "xlsx", "csv", "gif", "webp");

            if (!in_array($file_type, $allowed_types)) {
                $error = "File type not supported. Allowed: Images, PDF, Word, Excel, CSV.";
            } elseif ($file_size > $max_file_size) {
                $error = "File is too large! Maximum allowed size is 20MB.";
            } else {
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
                    $attachment = $file_name;
                } else {
                    $error = "Error saving uploaded file. Please check directory permissions.";
                }
            }
        } elseif ($file_error != 4) { // Error 4 means no file chose, which is fine as it's optional
            switch($file_error) {
                case 1:
                case 2:
                    $error = "File is too large for the server. Max 20MB allowed.";
                    break;
                case 3:
                    $error = "File was only partially uploaded.";
                    break;
                case 6:
                case 7:
                    $error = "Server storage error. Please contact admin.";
                    break;
                default:
                    $error = "File upload failed with error code: $file_error";
            }
        }
    }

    if (!isset($error)) {
        $sql = "INSERT INTO cases (student_id, student_name, roll_no, incident_date, case_type, description, attachment, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
             $error = "Database Error: " . $conn->error;
        } else {
            $stmt->bind_param("issssss", $student_id, $student_name, $roll_no, $full_incident_date, $case_type, $description, $attachment);

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
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
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
                <h4><img src="../assets/img/ocmslogo.png" alt="Logo" style="height: 75px;"></h4>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Menu</div>
                <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="add_case.php" class="active"><i class="fas fa-plus-circle"></i> Add Cases</a>
                <a href="my_cases.php"><i class="fas fa-file-alt"></i> My Cases</a>
                <a href="approved_cases.php"><i class="fas fa-check-circle"></i> Approved Cases</a>
                <a href="rejected_cases.php"><i class="fas fa-times-circle"></i> Rejected Cases</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="case_history.php"><i class="fas fa-history"></i> History</a>
                <a href="../auth/profile.php"><i class="fas fa-cog"></i> Settings</a>
                
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="search-bar" style="visibility: hidden;">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search...">
                </div>
                
                <div class="user-nav">

                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    
                    <a href="../auth/profile.php?view=1" class="text-decoration-none">
                        <div class="user-profile">
                            <div class="avatar shadow-sm" style="overflow: hidden;">
                                <?php if($profile_photo): ?>
                                    <?php 
                                        $photo = trim($profile_photo);
                                        $pic_src = (strpos($photo, 'http') === 0) 
                                            ? $photo 
                                            : "../uploads/profile/" . $photo;
                                    ?>
                                    <img src="<?php echo htmlspecialchars($pic_src); ?>" style="width: 100%; height: 100%; object-fit: cover;" referrerpolicy="no-referrer">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column text-center" style="line-height: 1.2;">
                                <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);">
                                    <?php echo htmlspecialchars($student_name); ?>
                                </span>
                                <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                                    <?php echo htmlspecialchars($roll_no); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="container mt-2">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card shadow-lg border-0" style="border-radius: 15px; overflow: hidden;">
                            <div class="card-header text-white text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 10px; border-radius: 15px 15px 0 0;">
                                <h5 class="mb-0 fw-bold">Add New Case</h5>
                            </div>
                            <div class="card-body p-3">
                                <?php if(isset($success)): ?>
                                    <div class="alert alert-success py-2 mb-2"><?php echo $success; ?></div>
                                <?php endif; ?>
                                <?php if(isset($error)): ?>
                                    <div class="alert alert-danger py-2 mb-2"><?php echo $error; ?></div>
                                <?php endif; ?>

                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="mb-1">
                                        <label class="form-label fw-bold small text-muted mb-0">Student Name</label>
                                        <input type="text" name="student_name" value="<?php echo htmlspecialchars($student_name); ?>" class="form-control form-control-sm" readonly>
                                    </div>

                                    <div class="mb-1">
                                        <label class="form-label fw-bold small text-muted mb-0">Roll Number</label>
                                        <input type="text" name="roll_no" value="<?php echo htmlspecialchars($roll_no); ?>" class="form-control form-control-sm" readonly>
                                    </div>
                                    
                                    <div class="mb-1">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="form-label fw-bold small text-muted mb-0">Date</label>
                                                <input type="date" name="incident_date" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-bold small text-muted mb-0">Time</label>
                                                <input type="time" name="incident_time" class="form-control form-control-sm" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-1">
                                        <label class="form-label fw-bold small text-muted mb-0">Case Type</label>
                                        <select name="case_type" class="form-select form-select-sm" required>
                                            <option value="Academic">Academic</option>
                                            <option value="Disciplinary">Disciplinary</option>
                                            <option value="Hostel">Hostel</option>
                                            <option value="Library">Library</option>
                                            <option value="Other">Placement</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <div class="mb-1">
                                        <label class="form-label fw-bold small text-muted mb-0">Upload File (Optional)</label>
                                        <input type="file" name="attachment" class="form-control form-control-sm" accept=".pdf, .jpg, .jpeg, .png, .gif, .webp, .doc, .docx, .xls, .xlsx, .csv">
                                        <small class="text-muted" style="font-size: 0.7rem;">Allowed: Images (JPG, PNG, GIF, WebP), PDF, Word, Excel, CSV (Max 20 MB)</small>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label fw-bold small text-muted mb-0">Description</label>
                                        <textarea name="description" class="form-control form-control-sm" rows="3" required></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-sm text-white fw-bold w-100 py-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 10px; box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4);">Submit Case</button>
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
