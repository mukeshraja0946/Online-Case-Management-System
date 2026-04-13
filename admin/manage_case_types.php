<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/db.php';

// Check if database connection exists
if (!isset($conn) || $conn->connect_error) {
    die("Database Connection Error: " . ($conn->connect_error ?? "Connection not initialized"));
}

// Self-healing: Ensure table exists
$conn->query("CREATE TABLE IF NOT EXISTS case_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) UNIQUE NOT NULL
)");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Ensure admin name is set
$admin_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';
$admin_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$admin_initial = !empty($admin_name) ? strtoupper($admin_name[0]) : 'A';

$success = "";
$error = "";

// Handle Add Case Type
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_type'])) {
    $type_name = trim($_POST['type_name']);
    if (!empty($type_name)) {
        $stmt = $conn->prepare("INSERT IGNORE INTO case_types (type_name) VALUES (?)");
        if ($stmt) {
            $stmt->bind_param("s", $type_name);
            if ($stmt->execute()) {
                if ($conn->affected_rows > 0) {
                    $success = "Case type added successfully!";
                } else {
                    $error = "Case type already exists.";
                }
            } else {
                $error = "Error adding case type: " . $stmt->error;
            }
        } else {
            $error = "SQL Prepare Error: " . $conn->error;
        }
    }
}

// Handle Delete Case Type
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM case_types WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Case type deleted successfully!";
        } else {
            $error = "Error deleting case type.";
        }
    }
}

// Fetch all case types
$res_types = $conn->query("SELECT * FROM case_types ORDER BY type_name ASC");
$types_list = [];
if ($res_types) {
    while($row = $res_types->fetch_assoc()) {
        $types_list[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Case Types - OCMS</title>
    <link rel="icon" type="image/png" href="../assets/img/OCMS_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="admin-portal">
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar admin-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="../assets/img/ocmslogo.png" alt="Logo" style="height: 55px;">
                </div>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-label">Admin Menu</div>
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                <a href="dashboard.php" class="menu-item"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="bulk_create_users.php" class="menu-item"><i class="fas fa-user-plus"></i> Create Users</a>
                <a href="users.php" class="menu-item"><i class="fas fa-users"></i> Manage Users</a>
                <a href="cases.php" class="menu-item"><i class="fas fa-folder-open"></i> All Cases</a>
                <a href="manage_case_types.php" class="menu-item active"><i class="fas fa-tags"></i> Case Types</a>
                
                <div class="menu-label menu-bottom-section mt-3">Account</div>
                <a href="settings.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
                <a href="../auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main_content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="header-text">
                    <h2>Manage Case Types</h2>
                    <p>Add or remove categories for cases created by staff.</p>
                </div>
                
                <div class="user-nav ms-auto">
                    <div class="user-profile d-flex align-items-center gap-3">
                        <div class="text-end" style="line-height: 1.2;">
                            <div style="font-size: 0.9rem; font-weight: 750; color: #1e293b; font-family: 'Outfit';">
                                <?php echo htmlspecialchars($admin_name); ?>
                            </div>
                        </div>
                        <div class="avatar shadow-sm bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; border-radius: 50%;">
                            <?php echo $admin_initial; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid mt-4">
                <div class="row">
                    <!-- Add Case Type Form -->
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                            <div class="card-header bg-white border-0 py-3">
                                <h5 class="card-title fw-bold m-0"><i class="fas fa-plus-circle me-2 text-primary"></i>Add New Type</h5>
                            </div>
                            <div class="card-body">
                                <?php if($success): ?>
                                    <div class="alert alert-success py-2 small"><?php echo $success; ?></div>
                                <?php endif; ?>
                                <?php if($error): ?>
                                    <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
                                <?php endif; ?>

                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Case Type Name</label>
                                        <input type="text" name="type_name" class="form-control" placeholder="e.g. Academic" required>
                                    </div>
                                    <button type="submit" name="add_type" class="btn btn-primary w-100 fw-bold">Add Category</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Case Types List -->
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                            <div class="card-header bg-white border-0 py-3">
                                <h5 class="card-title fw-bold m-0 text-dark"><i class="fas fa-list me-2 text-primary"></i>Existing Case Categories</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4">#</th>
                                                <th>Category Name</th>
                                                <th class="text-end pe-4">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($types_list)): ?>
                                                <?php $count = 1; foreach($types_list as $row): ?>
                                                    <tr>
                                                        <td class="ps-4 text-muted small"><?php echo $count++; ?></td>
                                                        <td><span class="fw-bold"><?php echo htmlspecialchars($row['type_name']); ?></span></td>
                                                        <td class="text-end pe-4">
                                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this category?')">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="3" class="text-center py-4 text-muted">No categories found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
