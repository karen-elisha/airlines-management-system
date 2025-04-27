<?php
// Start session for user management
session_start();

// Send appropriate headers for JSON response
header('Content-Type: application/json');

// Check if search results exist in session
if (isset($_SESSION['search_results']) && isset($_SESSION['search_params'])) {
    // Return search results and parameters as JSON
    echo json_encode([
        'flights' => $_SESSION['search_results'],
        'params' => $_SESSION['search_params']
    ]);
} else {
    // No search results found
    echo json_encode([
        'error' => 'No search results found. Please perform a search first.'
    ]);
}
?>