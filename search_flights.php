<?php
// Start session for user management
session_start();
require_once 'db_connect.php';

// Process the search form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $from = isset($_POST['from']) ? htmlspecialchars($_POST['from']) : '';
    $to = isset($_POST['to']) ? htmlspecialchars($_POST['to']) : '';
    $departure_date = isset($_POST['departure_date']) ? htmlspecialchars($_POST['departure_date']) : '';
    $return_date = isset($_POST['return_date']) ? htmlspecialchars($_POST['return_date']) : '';
    $trip_type = isset($_POST['trip']) ? htmlspecialchars($_POST['trip']) : 'oneway';
    $tickets = isset($_POST['tickets']) ? (int)$_POST['tickets'] : 1;

    // Basic validation
    if (empty($from) || empty($to) || empty($departure_date)) {
        $_SESSION['error_message'] = "Please provide all required flight details.";
        header("Location: index.php");
        exit();
    }

    // Validate that departure and destination are not the same
    if ($from === $to) {
        $_SESSION['error_message'] = "Departure and arrival cities cannot be the same.";
        header("Location: index.php");
        exit();
    }

    // Store search parameters in session for later use
    $_SESSION['flight_search'] = [
        'from' => $from,
        'to' => $to,
        'departure_date' => $departure_date,
        'return_date' => $return_date,
        'trip_type' => $trip_type,
        'tickets' => $tickets
    ];

    // Redirect to available.php with query parameters
    $redirect_url = "available.php?from=" . urlencode($from) . 
                  "&to=" . urlencode($to) . 
                  "&departure_date=" . urlencode($departure_date);
    
    // Add return date if it's a round trip
    if ($trip_type === 'roundtrip' && !empty($return_date)) {
        $redirect_url .= "&return_date=" . urlencode($return_date);
    }
    
    // Add number of tickets
    $redirect_url .= "&tickets=" . urlencode($tickets);
    
    header("Location: " . $redirect_url);
    exit();
} else {
    // If someone tries to access this file directly without form submission
    header("Location: index.php");
    exit();
}
?>