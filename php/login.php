<?php
// Start session
session_start();

// Database credentials
$servername = "localhost";
$username = "root"; // Replace with your DB username
$password = "root"; // Replace with your DB password
$dbname = "airlines_db"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get email and password from POST request
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare and execute query to check if email exists
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email); // "s" means the email is a string
    $stmt->execute();
    $stmt->store_result();

    // If the email exists in the database
    if ($stmt->num_rows > 0) {
        // Bind result
        $stmt->bind_result($user_id, $stored_password);
        $stmt->fetch();

        // Check if the entered password matches the stored password (hashed)
        if (password_verify($password, $stored_password)) {
            // Start user session and redirect to the user dashboard
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            header("Location: user-dashboard.php"); // Redirect to the dashboard page
            exit();
        } else {
            // Incorrect password - redirect with error message
            $_SESSION['error'] = "Incorrect password!";
            header("Location: login.php");
            exit();
        }
    } else {
        // Email not found in the database - redirect with error message
        $_SESSION['error'] = "No account found with that email address.";
        header("Location: login.php");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>
