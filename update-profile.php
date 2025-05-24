<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit("Unauthorized access");
}

$user_id = $_SESSION['user_id'];
$full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

// Validate inputs
if (empty($full_name) || empty($email) || empty($phone)) {
    header("HTTP/1.1 400 Bad Request");
    exit("All fields are required");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("HTTP/1.1 400 Bad Request");
    exit("Invalid email format");
}

try {
    // Check if email already exists for another user
    $check_email = $mysqli->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $check_email->bind_param("si", $email, $user_id);
    $check_email->execute();
    $check_email->store_result();
    
    if ($check_email->num_rows > 0) {
        header("HTTP/1.1 400 Bad Request");
        exit("Email already in use by another account");
    }
    
    // Update user profile
    $stmt = $mysqli->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
    $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
    
    if ($stmt->execute()) {
        // Update session with new values
        $_SESSION['user_fullname'] = $full_name;
        $_SESSION['user_email'] = $email;
        
        // Set success message in session
        $_SESSION['success_message'] = 'Profile updated successfully';
        
        // Redirect to user dashboard
        header("Location: user-dashboard.php");
        exit();
    } else {
        header("HTTP/1.1 500 Internal Server Error");
        exit("Error updating profile");
    }
} catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    exit("Database error: " . $e->getMessage());
}
?>