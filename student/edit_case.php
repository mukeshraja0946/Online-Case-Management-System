<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: my_cases.php");
    exit();
}

// Fetch case details
$stmt = $conn->prepare("SELECT * FROM cases WHERE id = ? AND student_id = ? AND status = 'Pending'");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Case not found or cannot be edited.");
}
$case = $result->fetch_assoc();

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_name = $_POST['student_name'];
    $roll_no = $_POST['roll_no'];
    $case_type = $_POST['case_type'];
    $description = $_POST['description'];

    $update_sql = "UPDATE cases SET student_name = ?, roll_no = ?, case_type = ?, description = ? WHERE id = ? AND student_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssii", $student_name, $roll_no, $case_type, $description, $id, $_SESSION['user_id']);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Case updated successfully!";
        header("Location: my_cases.php");
        exit();
    } else {
        $error = "Error updating case.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Case - OCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header text-white" style="background: linear-gradient(135deg, #0EA5E9 0%, #38BDF8 100%);">
                        <h4 class="mb-0">Edit Case</h4>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label>Student Name</label>
                                <input type="text" name="student_name" class="form-control" value="<?php echo htmlspecialchars($case['student_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>Roll Number</label>
                                <input type="text" name="roll_no" class="form-control" value="<?php echo htmlspecialchars($case['roll_no']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>Case Type</label>
                                <select name="case_type" class="form-select" required>
                                    <option value="Academic" <?php echo ($case['case_type'] == 'Academic') ? 'selected' : ''; ?>>Academic</option>
                                    <option value="Disciplinary" <?php echo ($case['case_type'] == 'Disciplinary') ? 'selected' : ''; ?>>Disciplinary</option>
                                    <option value="Hostel" <?php echo ($case['case_type'] == 'Hostel') ? 'selected' : ''; ?>>Hostel</option>
                                    <option value="Library" <?php echo ($case['case_type'] == 'Library') ? 'selected' : ''; ?>>Library</option>
                                    <option value="Other" <?php echo ($case['case_type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($case['description']); ?></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn text-white px-4" style="background: linear-gradient(135deg, #0EA5E9 0%, #38BDF8 100%); border: none; font-weight: 600;">Update Case</button>
                                <a href="my_cases.php" class="btn btn-danger px-4" style="font-weight: 600;">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
