<?php
session_start();
require_once '../config/google_auth.php';

// Generate a random state variable to prevent CSRF
$_SESSION['oauth2state'] = bin2hex(random_bytes(16));

// Google OAuth 2.0 Endpoint
$authorizeURL = 'https://accounts.google.com/o/oauth2/v2/auth';

$params = [
    'response_type' => 'code',
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'scope' => 'email profile',
    'state' => $_SESSION['oauth2state'],
    'access_type' => 'online',
    'prompt' => 'select_account'
];

// Redirect user to Google
header('Location: ' . $authorizeURL . '?' . http_build_query($params));
exit();
?>
