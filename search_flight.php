<?php
// Start session for user management
session_start();
require_once 'db_connect.php';

// Process the search form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $from = isset($_POST['from']) ? $_POST['from'] : '';
    $to = isset($_POST['to']) ? $_POST['to'] : '';
    $departure = isset($_POST['departure']) ? $_POST['departure'] : '';
    $return_date = isset($_POST['return_date']) ? $_POST['return_date'] : '';
    $trip_type = isset($_POST['trip']) ? $_POST['trip'] : 'oneway';
    $travelers = isset($_POST['travelers']) ? (int)$_POST['travelers'] : 1;

    // Basic validation
    if (empty($from) || empty($to) || empty($departure)) {
        $_SESSION['error_message'] = "Please provide all required flight details.";
        header("Location: dashboard.php");
        exit();
    }

    // Validate that departure and destination are not the same
    if ($from === $to) {
        $_SESSION['error_message'] = "Departure and arrival cities cannot be the same.";
        header("Location: dashboard.php");
        exit();
    }

    // Store search parameters in session for later use
    $_SESSION['flight_search'] = [
        'from' => $from,
        'to' => $to,
        'departure' => $departure,
        'return_date' => $return_date,
        'trip_type' => $trip_type,
        'travelers' => $travelers
    ];

    // Redirect to available.php with query parameters
    $redirect_url = "available.php?from=" . urlencode($from) . 
                  "&to=" . urlencode($to) . 
                  "&departure=" . urlencode($departure);
    
    // Add return date if it's a round trip
    if ($trip_type === 'roundtrip' && !empty($return_date)) {
        $redirect_url .= "&return_date=" . urlencode($return_date);
    }
    
    // Add number of travelers
    $redirect_url .= "&travelers=" . urlencode($travelers);
    
    header("Location: " . $redirect_url);
    exit();
} else {
    // If someone tries to access this file directly without form submission
    header("Location: dashboard.php");
    exit();
}
?>