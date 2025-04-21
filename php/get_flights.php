<?php
$conn = new mysqli("localhost", "root", "", "your_database_name");

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM flights ORDER BY departure_time";
$result = $conn->query($sql);

$flights = [];

while ($row = $result->fetch_assoc()) {
  $flights[] = $row;
}

header('Content-Type: application/json');
echo json_encode($flights);
?>
