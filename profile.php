<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in', 'redirect' => 'login-user.html']);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Action handler
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            updateProfile($mysqli, $user_id);
            break;
            
        case 'change_password':
            changePassword($mysqli, $user_id);
            break;
            
        case 'delete_account':
            deleteAccount($mysqli, $user_id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    // GET request - fetch user profile data
    try {
        $stmt = $mysqli->prepare("SELECT user_id, full_name, email, phone, member_since, loyalty_tier, loyalty_points 
                               FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to update user profile
function updateProfile($mysqli, $user_id) {
    // Get form data
    $full_name = filter_input(INPUT_POST, 'fullName', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    
    // Validate data
    if (empty($full_name) || empty($email) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    try {
        // Check if email is already used by another user
        $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already registered']);
            return;
        }
        
        // Update user profile
        $stmt = $mysqli->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? 
                               WHERE user_id = ?");
        
        $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
        $stmt->execute();
        
        // Update session data
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_email'] = $email;
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to change password
function changePassword($mysqli, $user_id) {
    // Get form data
    $current_password = $_POST['currentPassword'] ?? '';
    $new_password = $_POST['newPassword'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';
    
    // Validate data
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
        return;
    }
    
    if (strlen($new_password) < 6 || !preg_match('/\d/', $new_password)) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters with at least one number']);
        return;
    }
    
    try {
        // Get current password hash
        $stmt = $mysqli->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify current password
            if (password_verify($current_password, $user['password'])) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to delete account
function deleteAccount($mysqli, $user_id) {
    try {
        // First check if user has active bookings
        $stmt = $mysqli->prepare("SELECT booking_id FROM bookings 
                               WHERE user_id = ? AND status = 'Confirmed'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete account with active bookings']);
            return;
        }
        
        // Delete user account
        $stmt = $mysqli->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Destroy session
        session_destroy();
        
        echo json_encode(['success' => true, 'message' => 'Account deleted successfully', 'redirect' => 'index.php']);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Close connection
$mysqli->close();
?>