<?php
// Database connection parameters
$host = "localhost";
$dbname = "airlines";
$username = "root";
$password = "root";

// Create MySQLi connection
$mysqli = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>