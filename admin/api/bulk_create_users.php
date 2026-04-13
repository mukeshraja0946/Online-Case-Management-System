<?php
session_start();
ob_start(); // Buffer output to prevent breaking JSON with warnings
require_once '../../config/db.php';

// Set JSON header for responses
header('Content-Type: application/json');

// 1. Security Check: Only Admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access. Only admins can perform this action.']);
    exit();
}

$success_count = 0;
$failed_count = 0;
$errors = [];
$processed_data = [];

try {
    // 3. Handle Data Input (CSV or JSON)
    if (isset($_FILES['csv_file'])) {
        $file_error = $_FILES['csv_file']['error'];
        if ($file_error == 0) {
            $file = $_FILES['csv_file']['tmp_name'];
            $file_ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($file_ext, ['csv'])) {
                throw new Exception("Invalid file format. Please upload a .csv file.");
            }
            $handle = fopen($file, "r");
            $header = fgetcsv($handle);
            $row_index = 1;
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) < 6) continue;
                $processed_data[] = [
                    'row' => $row_index++,
                    'name' => trim($data[0]),
                    'email' => trim($data[1]),
                    'register_no' => trim($data[2]),
                    'department' => trim($data[3]),
                    'role' => strtolower(trim($data[4])),
                    'password' => trim($data[5]),
                    'year' => trim($data[6] ?? ''), 
                    'batch' => trim($data[7] ?? '')
                ];
                if (count($processed_data) >= 100) break;
            }
            fclose($handle);
        } else {
             throw new Exception("Upload failed (Error code: $file_error).");
        }
    } elseif (isset($_POST['users_json'])) {
        $json_users = json_decode($_POST['users_json'], true);
        if ($json_users) {
            $row_index = 1;
            foreach ($json_users as $user) {
                $processed_data[] = [
                    'row' => $row_index++,
                    'name' => trim($user['name'] ?? ''),
                    'email' => trim($user['email'] ?? ''),
                    'register_no' => trim($user['register_no'] ?? ''),
                    'department' => trim($user['department'] ?? ''),
                    'year' => trim($user['year'] ?? ''),
                    'batch' => trim($user['batch'] ?? ''),
                    'role' => strtolower(trim($user['role'] ?? '')),
                    'password' => trim($user['password'] ?? ''),
                    'profile_photo' => $user['profile_photo'] ?? null
                ];
                if (count($processed_data) >= 100) break;
            }
        }
    } else {
        throw new Exception("No data provided.");
    }

    if (empty($processed_data)) throw new Exception("No valid user data found.");

    // 4. Batch Processing with Self-Healing Schema Check
    $conn->begin_transaction();

    // Check if year and batch columns exist
    $check_year = $conn->query("SHOW COLUMNS FROM users LIKE 'year'");
    if ($check_year->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN year VARCHAR(50) AFTER department");
    }
    $check_batch = $conn->query("SHOW COLUMNS FROM users LIKE 'batch'");
    if ($check_batch->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN batch VARCHAR(50) AFTER year");
    }

    // Prepare statements once
    $sql_check = "SELECT id FROM users WHERE email = ? OR roll_no = ? OR staff_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    
    $sql_insert = "INSERT INTO users (name, email, password, role, roll_no, staff_id, department, year, batch, profile_photo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);

    if (!$stmt_check || !$stmt_insert) {
        throw new Exception("Failed to prepare database statements: " . $conn->error);
    }

    foreach ($processed_data as $user) {
        $row = $user['row'];
        
        // Basic Validation
        if (empty($user['name']) || empty($user['email']) || empty($user['register_no']) || empty($user['password'])) {
            $errors[] = "Row $row: Missing required fields.";
            $failed_count++;
            continue;
        }

        // Uniqueness
        $stmt_check->bind_param("sss", $user['email'], $user['register_no'], $user['register_no']);
        $stmt_check->execute();
        $check_res = $stmt_check->get_result();
        if ($check_res->num_rows > 0) {
            $errors[] = "Row $row: Email or ID '{$user['register_no']}' already exists.";
            $failed_count++;
            continue;
        }

        // Photo Handling
        $profile_photo = null;
        if (!empty($user['profile_photo']) && strpos($user['profile_photo'], 'data:image/') === 0) {
            $upload_dir = "../../uploads/profile/";
            if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);
            
            list(, $data) = explode(',', $user['profile_photo']);
            $data = base64_decode($data);
            $new_file_name = "profile_" . time() . "_" . rand(1000, 9999) . ".jpg";
            if (@file_put_contents($upload_dir . $new_file_name, $data)) {
                $profile_photo = $new_file_name;
            }
        }

        $hashed_password = password_hash($user['password'], PASSWORD_BCRYPT);
        $roll_no = ($user['role'] === 'student') ? $user['register_no'] : null;
        $staff_id = ($user['role'] === 'staff') ? $user['register_no'] : null;

        $stmt_insert->bind_param("ssssssssss", 
            $user['name'], 
            $user['email'], 
            $hashed_password, 
            $user['role'], 
            $roll_no, 
            $staff_id, 
            $user['department'], 
            $user['year'], 
            $user['batch'], 
            $profile_photo
        );

        if ($stmt_insert->execute()) {
            $success_count++;
        } else {
            $errors[] = "Row $row: Database error - " . $stmt_insert->error;
            $failed_count++;
        }
    }
    
    $conn->commit();
    
    // Clear buffer and return JSON
    ob_end_clean(); 
    echo json_encode([
        'success' => true,
        'success_count' => $success_count,
        'failed_count' => $failed_count,
        'errors' => $errors
    ]);

} catch (Throwable $e) {
    if (isset($conn) && $conn->ping() && $conn->in_transaction) $conn->rollback();
    if (ob_get_length()) ob_end_clean();
    echo json_encode(['error' => $e->getMessage()]);
}
?>
