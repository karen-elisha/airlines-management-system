<?php
session_start();

// Database connection settings
$host = "localhost";
$user = "root";
$password = "";
$database = "airlines";

// Create MySQLi connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $conn->begin_transaction();

        // Generate a unique booking reference
        $booking_reference = 'BK-' . strtoupper(uniqid());

        // Insert into booking table
        $booking_sql = "INSERT INTO booking (
            booking_reference, user_id, flight_id, booking_date, 
            travel_date, num_passengers, total_amount, contact_email, 
            contact_phone, booking_status, payment_status, payment_method
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $booking_stmt = $conn->prepare($booking_sql);
        if (!$booking_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $booking_stmt->bind_param(
            'siissdssssss',
            $booking_reference,
            $_SESSION['user_id'],
            $_POST['flight_id'],
            date('Y-m-d H:i:s'),
            $_POST['departure_date'],
            $_POST['num_passengers'],
            $_POST['total_price'],
            $_POST['contact_email'],
            $_POST['contact_phone'],
            'confirmed',
            'pending',
            'credit_card'
        );

        if (!$booking_stmt->execute()) {
            throw new Exception("Execute failed: " . $booking_stmt->error);
        }
        
        $booking_id = $conn->insert_id;
        $booking_stmt->close();

        // Insert passenger data
        $passenger_sql = "INSERT INTO passenger (
            booking_id, first_name, last_name, gender, 
            age, seat_number
        ) VALUES (?, ?, ?, ?, ?, ?)";

        $passenger_stmt = $conn->prepare($passenger_sql);
        if (!$passenger_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Process each passenger
        for ($i = 0; $i < count($_POST['passenger_firstname']); $i++) {
            // Calculate age from date of birth
            $dob = new DateTime($_POST['passenger_dob'][$i]);
            $now = new DateTime();
            $age = $now->diff($dob)->y;

            $seat_number = $_POST['passenger_seat'][$i] ?? 'TBA';

            $passenger_stmt->bind_param(
                'isssis',
                $booking_id,
                $_POST['passenger_firstname'][$i],
                $_POST['passenger_lastname'][$i],
                $_POST['passenger_gender'][$i],
                $age,
                $seat_number
            );

            if (!$passenger_stmt->execute()) {
                throw new Exception("Execute failed: " . $passenger_stmt->error);
            }
        }
        $passenger_stmt->close();

        // Commit transaction
        $conn->commit();

        // Store booking ID in session for payment page
        $_SESSION['current_booking_id'] = $booking_id;
        $_SESSION['booking_reference'] = $booking_reference;
        $_SESSION['total_amount'] = $_POST['total_price'];

        // Redirect to payment page
        header('Location: payment.php');
        exit;

    } catch (Exception $e) {
        // Roll back transaction on error
        if (isset($conn) && get_class($conn) === 'mysqli') {
            $conn->rollback();
        }
        
        // Log error and show message
        error_log("Booking Error: " . $e->getMessage());
        $_SESSION['booking_error'] = "An error occurred while processing your booking. Please try again. Error: " . $e->getMessage();
        header('Location: booking-forms.php');
        exit;
    }
} else {
    // If not a POST request, redirect back
    header('Location: booking-forms.php');
    exit;
}
?>