<?php
// Start session for user management
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.php?redirect=booking.php');
    exit;
}

// Include database connection
require_once 'db_connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Get form data
    $flight_id = $_POST['flight_id'] ?? 0;
    $departure_date = $_POST['departure_date'] ?? '';
    $num_passengers = $_POST['num_passengers'] ?? 0;
    $total_price = $_POST['total_price'] ?? 0;
    $contact_email = $_POST['contact_email'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';
    
    // Generate a unique booking reference (alphanumeric, 8 characters)
    $booking_reference = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    
    try {
        // Start transaction
        $mysqli->begin_transaction();
        
        // Insert booking record
        $sql = "INSERT INTO bookings (user_id, booking_date, total_amount, payment_status, 
                booking_status, flight_id, number_of_passengers, booking_reference, created_at, updated_at) 
                VALUES (?, NOW(), ?, 'Pending', 'Confirmed', ?, ?, ?, NOW(), NOW())";
                
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("idiis", $user_id, $total_price, $flight_id, $num_passengers, $booking_reference);
        $stmt->execute();
        
        // Get the booking ID
        $booking_id = $mysqli->insert_id;
        
        // Process passenger data
        $passenger_titles = $_POST['passenger_title'] ?? [];
        $passenger_firstnames = $_POST['passenger_firstname'] ?? [];
        $passenger_lastnames = $_POST['passenger_lastname'] ?? [];
        $passenger_dobs = $_POST['passenger_dob'] ?? [];
        $passenger_genders = $_POST['passenger_gender'] ?? [];
        $passenger_mobiles = $_POST['passenger_mobile'] ?? [];
        $passenger_classes = $_POST['passenger_class'] ?? [];
        $passenger_seats = $_POST['passenger_seat'] ?? [];
        
        // Insert passenger records
        for ($i = 0; $i < count($passenger_firstnames); $i++) {
            // Calculate age from DOB
            $dob = new DateTime($passenger_dobs[$i]);
            $now = new DateTime();
            $age = $now->diff($dob)->y;
            
            $sql = "INSERT INTO passengers (booking_id, first_name, last_name, gender, age, seat_number) 
                    VALUES (?, ?, ?, ?, ?, ?)";
                    
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("isssis", $booking_id, $passenger_firstnames[$i], $passenger_lastnames[$i], 
                               $passenger_genders[$i], $age, $passenger_seats[$i]);
            $stmt->execute();
        }
        
        // Commit transaction
        $mysqli->commit();
        
        // Store booking info in session for the payment page
        $_SESSION['booking_id'] = $booking_id;
        $_SESSION['booking_reference'] = $booking_reference;
        $_SESSION['total_amount'] = $total_price;
        $_SESSION['num_passengers'] = $num_passengers;
        
        // Redirect to payment page
        header('Location: payment.php');
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $mysqli->rollback();
        // Log the error for debugging
        error_log("Database error in booking: " . $e->getMessage() . " - " . print_r($_POST, true));
        
        // Redirect to error page
        header('Location: error.php?msg=booking_failed');
        exit;
    } finally {
        // Close any open statements
        if (isset($stmt)) {
            $stmt->close();
        }
    }
} else {
    // If not POST request, redirect to booking page
    header('Location: booking-forms.php');
    exit;
}
?>