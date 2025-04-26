<?php
require_once 'db_connect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Get search parameters
    $origin = $_GET['from'] ?? '';
    $destination = $_GET['to'] ?? '';
    $departure_date = $_GET['departure'] ?? '';
    $travelers = $_GET['travelers'] ?? '1 Adult';
    
    // Validate data
    if (empty($origin) || empty($destination) || empty($departure_date)) {
        echo json_encode(['success' => false, 'message' => 'All search fields are required']);
        exit;
    }
    
    // Extract just the city codes from the form values (e.g., "Delhi (DEL)" -> "DEL")
    preg_match('/\(([^)]+)\)/', $origin, $matches_origin);
    $origin_code = $matches_origin[1] ?? $origin;
    
    preg_match('/\(([^)]+)\)/', $destination, $matches_dest);
    $destination_code = $matches_dest[1] ?? $destination;
    
    try {
        // Prepare the query to search for flights
        $stmt = $conn->prepare("
            SELECT 
                f.flight_id,
                f.airline,
                f.origin,
                f.destination,
                f.departure_datetime,
                f.arrival_datetime,
                f.base_price,
                f.status,
                o.city_name as origin_city,
                d.city_name as destination_city
            FROM flights f
            JOIN cities o ON f.origin = o.city_code
            JOIN cities d ON f.destination = d.city_code
            WHERE f.origin = :origin
            AND f.destination = :destination
            AND DATE(f.departure_datetime) = :departure_date
            AND f.status != 'Cancelled'
            ORDER BY f.departure_datetime ASC
        ");
        
        $stmt->bindParam(':origin', $origin_code);
        $stmt->bindParam(':destination', $destination_code);
        $stmt->bindParam(':departure_date', $departure_date);
        $stmt->execute();
        
        $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process number of travelers
        $num_travelers = 1;
        if (strpos($travelers, '2') === 0) {
            $num_travelers = 2;
        } else if (strpos($travelers, 'Family') === 0) {
            $num_travelers = 4;
        }
        
        // Add passenger count and total price to each flight
        foreach ($flights as &$flight) {
            $flight['passenger_count'] = $num_travelers;
            $flight['total_price'] = $flight['base_price'] * $num_travelers;
        }
        
        echo json_encode(['success' => true, 'flights' => $flights, 'search' => [
            'origin' => $origin,
            'destination' => $destination,
            'departure_date' => $departure_date,
            'travelers' => $travelers,
            'passenger_count' => $num_travelers
        ]]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
    // Close connection
    $conn = null;
} else {
    // Not a GET request
    header("Location: user-dashboard.php");
    exit;
}
?>