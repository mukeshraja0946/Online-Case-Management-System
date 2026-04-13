<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['case_id'])) {
    header("Location: available_cases.php");
    exit();
}

// -- SELF-HEALING DATABASE MIGRATION --
$conn->query("CREATE TABLE IF NOT EXISTS case_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    student_id INT NOT NULL,
    reason TEXT NOT NULL,
    file VARCHAR(255),
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    staff_remark TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Ensure notifications table exists
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'general',
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$student_id = $_SESSION['user_id'];
$case_id = $_GET['case_id'];

// Fetch case details
$stmt = $conn->prepare("SELECT c.*, u.name as staff_name FROM cases c JOIN users u ON c.created_by_staff = u.id WHERE c.id = ?");
if (!$stmt) {
    die("Database Error (Case Fetch): " . $conn->error);
}
$stmt->bind_param("i", $case_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();

if (!$case) {
    header("Location: available_cases.php");
    exit();
}

// Check if student already submitted to this case
$check_stmt = $conn->prepare("SELECT id FROM case_submissions WHERE case_id = ? AND student_id = ?");
$check_stmt->bind_param("ii", $case_id, $student_id);
$check_stmt->execute();
$existing_submission = $check_stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reason = $_POST['reason'];
    
    // File Upload Logic (Mandatory supporting file as per requirements check...)
    // Req: "Student cannot submit without reason/file"
    $file_name = NULL;
    $max_file_size = 20 * 1024 * 1024; // 20MB

    if (isset($_FILES['supporting_file']) && $_FILES['supporting_file']['error'] == 0) {
        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . "_student_" . basename($_FILES['supporting_file']['name']);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = array("jpg", "jpeg", "png", "pdf", "doc", "docx", "xls", "xlsx", "csv", "gif", "webp");

        if (!in_array($file_type, $allowed_types)) {
            $error = "File type not supported.";
        } elseif ($_FILES['supporting_file']['size'] > $max_file_size) {
            $error = "File is too large! Maximum 20MB.";
        } else {
            if (move_uploaded_file($_FILES['supporting_file']['tmp_name'], $target_file)) {
                // Success
            } else {
                $error = "Error saving uploaded file.";
            }
        }
    } else {
        $error = "Supporting file is required.";
    }

    if (!isset($error)) {
        if ($existing_submission) {
            $error = "You have already submitted a response for this case.";
        } else {
            $sql = "INSERT INTO case_submissions (case_id, student_id, reason, file, status) VALUES (?, ?, ?, ?, 'Pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiss", $case_id, $student_id, $reason, $file_name);

            if ($stmt->execute()) {
                $success = "Submission sent successfully!";
                
                // Notify Staff who created the case
                $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'new_submission')");
                $notif_msg = "New student submission for case: " . $case['title'];
                $notif_stmt->bind_param("is", $case['created_by_staff'], $notif_msg);
                $notif_stmt->execute();
                
            } else {
                $error = "Database Error: " . $conn->error;
            }
        }
    }
}

// Fetch student info for display
$u_stmt = $conn->prepare("SELECT name, roll_no, profile_photo FROM users WHERE id = ?");
$u_stmt->bind_param("i", $student_id);
$u_stmt->execute();
$u_res = $u_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respond to Case - OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <a href="dashboard.php" class="menu-item"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="available_cases.php" class="menu-item active"><i class="fas fa-list"></i> Available Cases</a>
                <a href="my_cases.php" class="menu-item"><i class="fas fa-file-alt"></i> My Submissions</a>
                <a href="approved_cases.php" class="menu-item"><i class="fas fa-check-circle"></i> Approved Submissions</a>
                <a href="rejected_cases.php" class="menu-item"><i class="fas fa-times-circle"></i> Rejected Submissions</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="case_history.php" class="menu-item"><i class="fas fa-history"></i> History</a>
                <a href="../auth/profile.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <div class="topbar">
                <div class="search-bar" style="visibility: hidden;"><i class="fas fa-search"></i><input type="text"></div>
                <div class="user-nav">
                    <button class="nav-icon-btn"><i class="fas fa-bell"></i></button>
                    <a href="../auth/profile.php?view=1" class="text-decoration-none">
                        <div class="user-profile">
                            <div class="avatar shadow-sm" style="overflow: hidden;">
                                <?php if($u_res['profile_photo']): ?>
                                    <img src="../uploads/profile/<?php echo htmlspecialchars($u_res['profile_photo']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($u_res['name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column text-center">
                                <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);"><?php echo htmlspecialchars($u_res['name']); ?></span>
                                <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;"><?php echo htmlspecialchars($u_res['roll_no']); ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="container mt-4">
                <div class="row justify-content-center">
                    <div class="col-md-9">
                        <div class="card shadow-lg border-0 mb-4" style="border-radius: 12px; border-left: 5px solid var(--primary-color);">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-3 text-primary"><?php echo htmlspecialchars($case['title']); ?></h5>
                                <p class="mb-3 text-muted"><?php echo nl2br(htmlspecialchars($case['description'])); ?></p>
                                <div class="d-flex gap-4 small text-muted">
                                    <span><i class="fas fa-user-tie me-1"></i> Posted by: <strong><?php echo htmlspecialchars($case['staff_name']); ?></strong></span>
                                    <span><i class="far fa-calendar-alt me-1"></i> Date: <?php echo date('M d, Y', strtotime($case['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-lg border-0" style="border-radius: 15px;">
                            <div class="card-header text-white text-center" style="background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%); padding: 15px;">
                                <h5 class="mb-0 fw-bold">Submit Your Explanation</h5>
                            </div>
                            <div class="card-body p-4">
                                <?php if(isset($success)): ?>
                                    <div class="alert alert-success"><?php echo $success; ?></div>
                                    <div class="text-center mt-3"><a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a></div>
                                <?php elseif($existing_submission): ?>
                                    <div class="alert alert-info py-3 text-center">
                                        <i class="fas fa-info-circle me-2"></i> You have already submitted a response for this case.
                                        <br>Status: <strong><?php echo $existing_submission['status'] ?? 'Pending'; ?></strong>
                                    </div>
                                    <div class="text-center mt-3"><a href="my_cases.php" class="btn btn-primary">Check Submission Status</a></div>
                                <?php else: ?>
                                    <?php if(isset($error)): ?>
                                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                    <?php endif; ?>

                                    <form method="POST" action="" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted">Reason / Explanation</label>
                                            <textarea name="reason" class="form-control" rows="5" placeholder="Enter your detailed explanation here..." required></textarea>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label fw-bold small text-muted">Supporting File (Compulsory)</label>
                                            <input type="file" name="supporting_file" class="form-control" required>
                                            <small class="text-muted">Upload any evidence, medical certificate, or related document (Max 20MB).</small>
                                        </div>
                                        <button type="submit" class="btn text-white fw-bold w-100 py-3" style="background: var(--primary-color); border-radius: 10px; font-size: 1.1rem;">Submit Response</button>
                                    </form>
                                <?php endif; ?>
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
