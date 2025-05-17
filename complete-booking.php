<?php

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

session_start();
require_once 'db_connect.php';
// Debug: Log session and post data
error_log("Session data: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['booking_error'] = "Please login to book flights";
    header('Location: login.php');
    exit;
}

// Only proceed if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['booking_error'] = "Invalid request method";
    header('Location: booking-forms.php');
    exit;
}

// Validate required fields
$required_fields = [
    'flight_id', 'departure_date', 'num_passengers', 'total_price',
    'contact_email', 'contact_phone', 'passenger_firstname', 
    'passenger_lastname', 'passenger_gender', 'passenger_dob'
];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $_SESSION['booking_error'] = "Missing required field: $field";
        header('Location: booking-forms.php');
        exit;
    }
}

// Database connection
$mysqli = new mysqli("localhost", "root", "", "airlines");
if ($mysqli->connect_error) {
    $_SESSION['booking_error'] = "Database connection failed";
    error_log("Connection failed: " . $mysqli->connect_error);
    header('Location: booking-forms.php');
    exit;
}

try {
    // Start transaction (CORRECTED SPELLING)
    $mysqli->begin_transaction();
    error_log("Transaction started");

    // Generate booking reference
    $booking_reference = 'BK-' . strtoupper(uniqid());
    error_log("Generated booking reference: $booking_reference");

    // Insert booking
    $booking_sql = "INSERT INTO bookings (
        booking_reference, user_id, flight_id, booking_date, 
        travel_date, num_passengers, total_amount, contact_email, 
        contact_phone, booking_status, payment_status, payment_method
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $booking_stmt = $mysqli->prepare($booking_sql);
    if (!$booking_stmt) {
        throw new Exception("Booking prepare failed: " . $mysqli->error);
    }

    $booking_date = date('Y-m-d H:i:s');
    $booking_status = 'confirmed';
    $payment_status = 'pending';
    $payment_method = 'credit_card';

    $booking_stmt->bind_param(
        'siissdssssss',
        $booking_reference,
        $_SESSION['user_id'],
        $_POST['flight_id'],
        $booking_date,
        $_POST['departure_date'],
        $_POST['num_passengers'],
        $_POST['total_price'],
        $_POST['contact_email'],
        $_POST['contact_phone'],
        $booking_status,
        $payment_status,
        $payment_method
    );

    if (!$booking_stmt->execute()) {
        throw new Exception("Booking execute failed: " . $booking_stmt->error);
    }
    
    $booking_id = $mysqli->insert_id;
    $booking_stmt->close();
    error_log("Booking inserted, ID: $booking_id");

    // Insert passengers
    $passenger_sql = "INSERT INTO passengers(
        booking_id, first_name, last_name, gender, age, seat_number
    ) VALUES (?, ?, ?, ?, ?, ?)";

    $passenger_stmt = $mysqli->prepare($passenger_sql);
    if (!$passenger_stmt) {
        throw new Exception("Passenger prepare failed: " . $mysqli->error);
    }

    for ($i = 0; $i < count($_POST['passenger_firstname']); $i++) {
        $dob = new DateTime($_POST['passenger_dob'][$i]);
        $age = $dob->diff(new DateTime())->y;
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
            throw new Exception("Passenger execute failed: " . $passenger_stmt->error);
        }
        error_log("Passenger $i inserted");
    }
    $passenger_stmt->close();

    // Commit transaction
    $mysqli->commit();
    error_log("Transaction committed");

    // Store booking info in session
    $_SESSION['current_booking_id'] = $booking_id;
    $_SESSION['booking_reference'] = $booking_reference;
    $_SESSION['total_amount'] = $_POST['total_price'];

    $mysqli->close();

    // Successful redirect
    header('Location: payment.php');
    exit;

} catch (Exception $e) {
    // Rollback on error
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->rollback();
        $mysqli->close();
    }
    
    error_log("ERROR: " . $e->getMessage());
    $_SESSION['booking_error'] = "Booking failed: " . $e->getMessage();
    header('Location: booking-forms.php');
    exit;
}