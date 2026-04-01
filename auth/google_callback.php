<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../config/db.php';
require_once '../config/google_auth.php';

if (isset($_GET['code'])) {
    
    // Verify state to prevent CSRF
    if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
        die('Invalid state');
    }

    $tokenURL = 'https://oauth2.googleapis.com/token';
    
    $postData = [
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenURL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // START FIX: Important for free hosting like InfinityFree to ignore strict SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // END FIX

    $response = curl_exec($ch);
    $curl_err = curl_error($ch);
    curl_close($ch);

    $token = json_decode($response, true);

    if (isset($token['access_token'])) {
        // Get user info
        $google_oauth = 'https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $token['access_token'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $google_oauth);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Add SSL fix here too
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $userInfo = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $email = $userInfo['email'];
        $name = $userInfo['name'];
        $picture = $userInfo['picture'];

        // Check if user exists
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            die("<h1>MySQL Database Error:</h1><br><b>" . $conn->error . "</b><br><br>The connection to the database worked, but the query failed. Have you fully exported your local XAMPP database and imported it into InfinityFree phpMyAdmin yet?");
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User exists
            $user = $result->fetch_assoc();
            $selected_role = $_SESSION['login_role'] ?? 'student';
            
            if ($user['role'] !== $selected_role) {
                $_SESSION['error'] = "No user found for the selected role. Please select the correct role to continue.";
                header("Location: login.php");
                exit();
            }

            // Log them in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_photo'] = $user['profile_photo'];

            // Update last login timestamp
            $login_update = $conn->query("UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);
            if (!$login_update) {
                file_put_contents('../debug_login.txt', date('Y-m-d H:i:s') . " - Error updating last_login (Google): " . $conn->error . "\n", FILE_APPEND);
            }

            // Update profile picture if it's new and we don't have a custom one yet
            // Only update if current photo is empty OR if it's already a Google URL
            $current_photo = $user['profile_photo'] ?? '';
            $is_google_photo = (strpos($current_photo, 'googleusercontent.com') !== false);
            
            if (empty($current_photo) || $is_google_photo) {
                if ($current_photo !== $picture) {
                    $update_pic = "UPDATE users SET profile_photo = ? WHERE id = ?";
                    $stmt_pic = $conn->prepare($update_pic);
                    $stmt_pic->bind_param("si", $picture, $user['id']);
                    $stmt_pic->execute();
                    $_SESSION['profile_photo'] = $picture;
                }
            }

            // Role-based redirection
            if ($user['role'] == 'admin') {
                header("Location: ../admin/dashboard.php");
            } elseif ($user['role'] == 'staff') {
                $_SESSION['staff_id'] = $user['staff_id'];
                header("Location: ../staff/dashboard.php");
            } else {
                $_SESSION['roll_no'] = $user['roll_no'];
                header("Location: ../student/dashboard.php");
            }
            exit();

        } else {
            // User does not exist in our database
            $_SESSION['error'] = "User not registered.";
            header("Location: ../auth/login.php");
            exit();
        }

    } else {
        die('Error fetching access token. cURL msg: ' . $curl_err . ' | Google msg: ' . $response);
    }
} else {
    header("Location: login.php");
    exit();
}
?>
