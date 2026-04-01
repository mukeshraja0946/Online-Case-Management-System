<?php
session_start();
require_once '../../config/db.php';

// Set JSON header for responses
header('Content-Type: application/json');

// 1. Security Check: Only Admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access. Only admins can perform this action.']);
    exit();
}

// 2. Initialize counters and error arrays
$success_count = 0;
$failed_count = 0;
$errors = [];
$processed_data = [];

// 3. Handle Data Input (CSV or JSON)
if (isset($_FILES['csv_file'])) {
    $file_error = $_FILES['csv_file']['error'];
    
    if ($file_error == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $file_name = $_FILES['csv_file']['name'];
        $file_size = $_FILES['csv_file']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Size check (20MB)
        if ($file_size > 20 * 1024 * 1024) {
             echo json_encode(['error' => 'File is too large! Maximum allowed size is 20MB.']);
             exit();
        }

        // Extension check
        if (!in_array($file_ext, ['csv', 'pdf', 'doc', 'docx', 'xlsx', 'xls'])) {
            echo json_encode(['error' => "Invalid file format (. $file_ext). Please upload a .csv, .pdf, or Word file."]);
            exit();
        }

        $handle = fopen($file, "r");
        
        // Skip header row
        $header = fgetcsv($handle);
        
        $row_index = 1;
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) < 6) continue; // Basic structure check
            
            $processed_data[] = [
                'row' => $row_index++,
                'name' => trim($data[0]),
                'email' => trim($data[1]),
                'register_no' => trim($data[2]),
                'department' => trim($data[3]),
                'role' => strtolower(trim($data[4])),
                'password' => trim($data[5])
            ];
            
            if (count($processed_data) >= 100) break; // Limit to 100 rows
        }
        fclose($handle);
    } else {
        $error_msg = "Upload failed (Error code: $file_error).";
        if ($file_error == 1 || $file_error == 2) $error_msg = "File is too large for the server (Max 20MB).";
        echo json_encode(['error' => $error_msg]);
        exit();
    }
} elseif (isset($_POST['users_json'])) {
    // B. Manual Form (JSON) Method
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
                'role' => strtolower(trim($user['role'] ?? '')),
                'password' => trim($user['password'] ?? '')
            ];
            if (count($processed_data) >= 100) break;
        }
    }
} else {
    // Check if post_max_size was exceeded
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_FILES) && empty($_POST)) {
         echo json_encode(['error' => 'The uploaded file is too large! Please try a smaller file (Max 20MB).']);
    } else {
         echo json_encode(['error' => 'No data provided. Please upload a CSV or fill out the form.']);
    }
    exit();
}

if (empty($processed_data)) {
    echo json_encode(['error' => 'No valid user data found in the file. Ensure you use the correct CSV template.']);
    exit();
}

// 4. Batch Processing and Validation
foreach ($processed_data as $user) {
    $row = $user['row'];
    
    if (empty($user['name']) || strlen($user['name']) > 255 ||
        empty($user['email']) || !filter_var($user['email'], FILTER_VALIDATE_EMAIL) || strlen($user['email']) > 255 ||
        empty($user['register_no']) || strlen($user['register_no']) > 50 ||
        empty($user['password']) || strlen($user['password']) < 6 || strlen($user['password']) > 255) {
        $errors[] = "Row $row: Missing required fields, invalid format, or length constraint violation (Name max 255, Email max 255 & valid, Reg No max 50, Password min 6 max 255).";
        $failed_count++;
        continue;
    }

    // Role Validation
    if (!in_array($user['role'], ['student', 'staff'])) {
        $errors[] = "Row $row: Invalid role '{$user['role']}'. Must be 'student' or 'staff'.";
        $failed_count++;
        continue;
    }

    // Check Uniqueness (Email or Register No)
    $sql_check = "SELECT id FROM users WHERE email = ? OR roll_no = ? OR staff_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("sss", $user['email'], $user['register_no'], $user['register_no']);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $errors[] = "Row $row: Duplicate entry detected (Email or ID '{$user['register_no']}' already exists).";
        $failed_count++;
        continue;
    }

    // 5. Database Insertion
    $hashed_password = password_hash($user['password'], PASSWORD_BCRYPT);
    $roll_no = ($user['role'] === 'student') ? $user['register_no'] : null;
    $staff_id = ($user['role'] === 'staff') ? $user['register_no'] : null;

    $sql_insert = "INSERT INTO users (name, email, password, role, roll_no, staff_id, department, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("sssssss", $user['name'], $user['email'], $hashed_password, $user['role'], $roll_no, $staff_id, $user['department']);

    if ($stmt_insert->execute()) {
        $success_count++;
    } else {
        $errors[] = "Row $row: Database error - " . $conn->error;
        $failed_count++;
    }
}

// 6. Return Final Summary
echo json_encode([
    'success' => true,
    'success_count' => $success_count,
    'failed_count' => $failed_count,
    'errors' => $errors
], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
