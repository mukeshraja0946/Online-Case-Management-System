<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit();
}

$staff_id = $_SESSION['user_id'];

// -- SELF-HEALING DATABASE MIGRATION --
$required_columns = [
    'title' => "VARCHAR(255) AFTER id",
    'description' => "TEXT AFTER title",
    'created_by_staff' => "INT AFTER description",
    'roll_number' => "VARCHAR(50) AFTER created_by_staff",
    'case_type' => "VARCHAR(100) AFTER roll_number",
    'case_date' => "DATE AFTER case_type",
    'case_day' => "VARCHAR(20) AFTER case_date",
    'case_time' => "TIME AFTER case_day",
    'attachment' => "VARCHAR(255) AFTER case_time",
    'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
];

foreach ($required_columns as $col => $definition) {
    if (!$conn->query("SHOW COLUMNS FROM cases LIKE '$col'")->num_rows) {
        $conn->query("ALTER TABLE cases ADD COLUMN $col $definition");
    }
}

// Ensure case_types table exists for the dropdown
$conn->query("CREATE TABLE IF NOT EXISTS case_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) UNIQUE NOT NULL
)");

// Fetch fresh staff data from DB
$u_stmt = $conn->prepare("SELECT name, staff_id, profile_photo FROM users WHERE id = ?");
if ($u_stmt) {
    $u_stmt->bind_param("i", $staff_id);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result()->fetch_assoc();
} else {
    die("Database Logic Error: " . $conn->error);
}

$staff_name = $u_res['name'] ?? 'Staff User';
$staff_sid = $u_res['staff_id'] ?? 'S000';
$profile_photo = $u_res['profile_photo'] ?? NULL;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $roll_number = $_POST['roll_number'];
    $title = $_POST['title'];
    $case_type = $_POST['case_type'];
    $case_date = $_POST['case_date'];
    $case_day = $_POST['case_day'];
    $case_time = $_POST['case_time'];
    $description = $_POST['description'];

    // File Upload Logic
    $attachment = NULL;
    $max_file_size = 20 * 1024 * 1024; // 20MB

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . "_" . basename($_FILES['attachment']['name']);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = array("jpg", "jpeg", "png", "pdf", "doc", "docx", "xls", "xlsx", "csv", "gif", "webp");

        if (!in_array($file_type, $allowed_types)) {
            $error = "File type not supported.";
        } elseif ($_FILES['attachment']['size'] > $max_file_size) {
            $error = "File is too large! Maximum 20MB.";
        } else {
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
                $attachment = $file_name;
            } else {
                $error = "Error saving uploaded file.";
            }
        }
    }

    if (!isset($error)) {
        // Find internal student ID from users table using roll number
        $st_find = $conn->prepare("SELECT id FROM users WHERE roll_no = ? AND role = 'student' LIMIT 1");
        $st_find->bind_param("s", $roll_number);
        $st_find->execute();
        $st_res = $st_find->get_result();
        
        if ($st_res->num_rows > 0) {
            $student_data = $st_res->fetch_assoc();
            $student_id = $student_data['id'];

            // sql: student_id, title, description, created_by_staff, roll_number, case_type, case_date, case_day, case_time, attachment
            $sql = "INSERT INTO cases (student_id, title, description, created_by_staff, roll_number, case_type, case_date, case_day, case_time, attachment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ississssss", $student_id, $title, $description, $staff_id, $roll_number, $case_type, $case_date, $case_day, $case_time, $attachment);

                if ($stmt->execute()) {
                    $success = "Case created successfully!";
                } else {
                    $error = "Execution Error: " . $stmt->error;
                }
            } else {
                $error = "Database Error (SQL Prepare): " . $conn->error;
            }
        } else {
            $error = "Student with roll number '$roll_number' not found in database.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Case - OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
                <a href="add_case.php" class="menu-item active"><i class="fas fa-plus-circle"></i> Create Case</a>
                <a href="received_cases.php" class="menu-item"><i class="fas fa-inbox"></i> Received Cases</a>
                <a href="approved_cases.php" class="menu-item"><i class="fas fa-check-circle"></i> Approved</a>
                <a href="rejected_cases.php" class="menu-item"><i class="fas fa-times-circle"></i> Rejected</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="../auth/profile.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <div class="topbar">
                <div class="header-text">
                    <h2 class="mb-0">Create Case</h2>
                </div>
                <div class="user-nav">
                    <div class="user-profile">
                        <div class="avatar shadow-sm" style="overflow: hidden;">
                            <?php if($profile_photo): ?>
                                <img src="../uploads/profile/<?php echo htmlspecialchars($profile_photo); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <?php echo strtoupper(substr($staff_name, 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex flex-column text-center">
                            <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-color);"><?php echo htmlspecialchars($staff_name); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container mt-4">
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="card shadow-lg border-0" style="border-radius: 15px;">
                            <div class="card-header text-white text-center py-3" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); border-radius: 15px 15px 0 0;">
                                <h5 class="mb-0 fw-bold">Enter Case Details</h5>
                            </div>
                            <div class="card-body p-4">
                                <?php if(isset($success)): ?>
                                    <div class="alert alert-success"><?php echo $success; ?></div>
                                <?php endif; ?>
                                <?php if(isset($error)): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>

                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-12">
                                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fas fa-user-graduate me-2"></i>Student Details</h6>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold">Roll Number</label>
                                            <input type="text" id="roll" name="roll_number" class="form-control" placeholder="Enter Roll Number" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold">Student Name</label>
                                            <input type="text" id="name" class="form-control" placeholder="Fetch Automatically" readonly>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold">Department</label>
                                            <input type="text" id="dept" class="form-control" placeholder="Fetch Automatically" readonly>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold">Year</label>
                                            <input type="text" id="year" class="form-control" placeholder="Fetch Automatically" readonly>
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-12">
                                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fas fa-file-invoice me-2"></i>Case Details</h6>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Case Title</label>
                                            <input type="text" name="title" class="form-control" placeholder="Enter Title" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Case Type</label>
                                            <select name="case_type" class="form-select" required>
                                                <option value="">Select Case Type</option>
                                                <?php
                                                $q = mysqli_query($conn, "SELECT * FROM case_types");
                                                while($row = mysqli_fetch_assoc($q)){
                                                    echo "<option value='".$row['type_name']."'>".$row['type_name']."</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Upload File (Optional)</label>
                                            <input type="file" name="attachment" class="form-control">
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Date</label>
                                            <input type="date" id="date" name="case_date" class="form-control" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Day</label>
                                            <input type="text" id="day" name="case_day" class="form-control" placeholder="Auto-filled" readonly required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Time</label>
                                            <input type="time" name="case_time" class="form-control" required>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label small fw-bold">Description</label>
                                        <textarea name="description" class="form-control" rows="4" placeholder="Briefly describe the case..." required></textarea>
                                    </div>

                                    <div class="text-center pt-2">
                                        <button type="submit" name="submit" class="btn btn-primary px-5 py-2 fw-bold" style="border-radius: 10px;">
                                            <i class="fas fa-plus-circle me-2"></i>Create Case
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
    <script>
        // Date to Day conversion
        document.getElementById("date").addEventListener("change", function() {
            const days = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
            let date = new Date(this.value);
            if(!isNaN(date.getTime())){
                document.getElementById("day").value = days[date.getDay()];
            }
        });

        // AJAX Auto Fetch Student Details
        document.getElementById("roll").addEventListener("blur", function() {
            let roll = this.value.trim();
            if(roll === "") return;

            fetch("fetch_student.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "roll=" + encodeURIComponent(roll)
            })
            .then(res => res.json())
            .then(data => {
                if(data.error){
                    alert("Student not found for roll: " + roll);
                    document.getElementById("name").value = "";
                    document.getElementById("dept").value = "";
                    document.getElementById("year").value = "";
                } else {
                    document.getElementById("name").value = data.name || "";
                    document.getElementById("dept").value = data.department || "";
                    document.getElementById("year").value = (data.year) ? data.year + " Year" : "";
                }
            })
            .catch(err => console.error("Fetch error:", err));
        });
    </script>
</body>
</html>
