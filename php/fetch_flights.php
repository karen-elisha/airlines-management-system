<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "airlines_management"; // Replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch flight data
$sql = "SELECT id, flight_code, flight_name, status FROM flights"; // Adjust the query based on your table structure
$result = $conn->query($sql);

$flights = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $flights[] = $row; // Push each flight data to the array
    }
}

echo json_encode($flights); // Output the flight data as JSON

$conn->close();
?>
