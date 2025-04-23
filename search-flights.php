<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root"; // your DB username
$password = "root";     // your DB password
$dbname = "airlines_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get form data
$trip = $_POST['trip'];
$from = $_POST['from'];
$to = $_POST['to'];
$departure_date = $_POST['departure_date'];
$return_date = $_POST['return_date'] ?? null;
$tickets = (int)$_POST['tickets'];

// Prepare SQL query
$sql = "SELECT * FROM flights 
        WHERE from_location = ? AND to_location = ? AND departure_date = ? AND seats_available >= ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $from, $to, $departure_date, $tickets);
$stmt->execute();
$result = $stmt->get_result();

// Store results in session
$flights = [];
while ($row = $result->fetch_assoc()) {
    $flights[] = $row;
}

$_SESSION['flights'] = $flights;
$_SESSION['trip'] = $trip;
$_SESSION['from'] = $from;
$_SESSION['to'] = $to;
$_SESSION['departure_date'] = $departure_date;
$_SESSION['return_date'] = $return_date;
$_SESSION['tickets'] = $tickets;

// Redirect to flights.php
header("Location: flights.php");
exit;
?>
