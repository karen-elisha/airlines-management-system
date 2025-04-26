<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to book flights', 'redirect' => 'login-user.html']);
    exit;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get booking parameters
    $flight_id = $_POST['flight_id'] ?? '';
    $passenger_count = intval($_POST['passenger_count'] ?? 1);
    $total_price = floatval($_POST['total_price'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    // Validate data
    if (empty($flight_id) || $passenger_count < 1 || $total_price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking parameters']);
        exit;
    }
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Check if flight exists and is available
        $stmt = $conn->prepare("SELECT * FROM flights WHERE flight_id = :flight_id AND status != 'Cancelled'");
        $stmt->bindParam(':flight_id', $flight_id);
        $stmt->execute();
        
        if ($stmt->rowCount() != 1) {
            echo json_encode(['success' => false, 'message' => 'Flight not available']);
            exit;
        }
        
        // Create booking record
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, flight_id, passenger_count, total_price) 
                               VALUES (:user_id, :flight_id, :passenger_count, :total_price)");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':flight_id', $flight_id);
        $stmt->bindParam(':passenger_count', $passenger_count);
        $stmt->bindParam(':total_price', $total_price);
        
        $stmt->execute();
        $booking_id = $conn->lastInsertId();
        
        // Calculate loyalty points (1 point per 100 currency units spent)
        $points_earned = floor($total_price / 100);
        
        // Update user's loyalty points
        $stmt = $conn->prepare("UPDATE users SET loyalty_points = loyalty_points + :points 
                               WHERE user_id = :user_id");
        
        $stmt->bindParam(':points', $points_earned);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // Update user's loyalty status based on total points
        $stmt = $conn->prepare("SELECT loyalty_points FROM users WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_points = $user['loyalty_points'];
        
        // Determine new loyalty status
        $loyalty_status = 'Standard';
        if ($total_points >= 5000) {
            $loyalty_status = 'Platinum';
        } elseif ($total_points >= 3000) {
            $loyalty_status = 'Gold';
        } elseif ($total_points >= 1000) {
            $loyalty_status = 'Silver';
        }
        
        // Update user's loyalty status if needed
        $stmt = $conn->prepare("UPDATE users SET loyalty_status = :status 
                               WHERE user_id = :user_id AND loyalty_status != :status");
        
        $stmt->bindParam(':status', $loyalty_status);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Flight booked successfully!', 
            'booking_id' => $booking_id,
            'points_earned' => $points_earned,
            'redirect' => 'user-dashboard.html#bookings'
        ]);
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
    // Close connection
    $conn = null;
} else {
    // Not a POST request
    header("Location: user-dashboard.html");
    exit;
}
?>