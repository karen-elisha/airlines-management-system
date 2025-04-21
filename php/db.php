<?php
$host = "localhost";
$user = "root";
$password = "root";
$dbname = "airlines_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
