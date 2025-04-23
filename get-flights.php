<?php
header("Content-Type: application/json");

// Database configuration
$host = "localhost";       // or your database host
$dbname = "airlines_db"; // change to your actual database name
$username = "root";   // change to your DB username
$password = "";   // change to your DB password

// Connect to the database
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Optional: Get number of tickets (from query string or default to 1)
$tickets = isset($_GET['tickets']) ? (int) $_GET['tickets'] : 1;

// Query to fetch all flights
$sql = "SELECT airline, flight_number, from_location, to_location, departure_date, departure_time, arrival_date, arrival_time, price, duration FROM flights";
$result = $conn->query($sql);

$flights = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $flights[] = $row;
    }
}

// Output JSON
echo json_encode([
    "flights" => $flights,
    "tickets" => $tickets
]);

$conn->close();
?>
