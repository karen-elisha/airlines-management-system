<?php
// Start session to store search data
session_start();
require_once 'db_connect.php';
// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $trip_type = $_POST['trip'] ?? 'oneway';
    $from = $_POST['from'] ?? '';
    $to = $_POST['to'] ?? '';
    $departure_date = $_POST['departure'] ?? '';
    $return_date = $_POST['return_date'] ?? '';
    $travelers = (int)($_POST['travelers'] ?? 1);
    
    // Validate input
    $errors = [];
    
    if (empty($from)) {
        $errors[] = "Origin location is required";
    }
    
    if (empty($to)) {
        $errors[] = "Destination location is required";
    }
    
    if ($from === $to) {
        $errors[] = "Origin and destination cannot be the same";
    }
    
    if (empty($departure_date)) {
        $errors[] = "Departure date is required";
    }
    
    // If round trip, validate return date
    if ($trip_type === 'roundtrip' && empty($return_date)) {
        $errors[] = "Return date is required for round trips";
    }
    
    // Check if return date is after departure date for round trips
    if ($trip_type === 'roundtrip' && !empty($departure_date) && !empty($return_date)) {
        if (strtotime($return_date) < strtotime($departure_date)) {
            $errors[] = "Return date must be after departure date";
        }
    }
    
    // If there are validation errors
    if (!empty($errors)) {
        $_SESSION['error_message'] = implode("<br>", $errors);
        // Redirect back to the search form
        header("Location: index.php");
        exit;
    }
    
    // Store search parameters in session
    $_SESSION['flight_search'] = [
        'from' => $from,
        'to' => $to,
        'departure_date' => $departure_date,
        'return_date' => $return_date,
        'tickets' => $travelers,
        'trip_type' => $trip_type
    ];
    
    // Redirect to available.php with parameters
    $redirect_url = "available.php?from=" . urlencode($from) . 
                   "&to=" . urlencode($to) . 
                   "&departure_date=" . urlencode($departure_date) . 
                   "&tickets=" . urlencode($travelers);
    
    // Add return date if it's a round trip
    if ($trip_type === 'roundtrip' && !empty($return_date)) {
        $redirect_url .= "&return_date=" . urlencode($return_date);
    }
    
    header("Location: " . $redirect_url);
    exit;
} else {
    // If someone accesses this file directly without form submission
    $_SESSION['error_message'] = "Invalid access. Please use the search form.";
    header("Location: index.php");
    exit;
}
?>