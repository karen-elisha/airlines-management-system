
<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "airlines";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all flights from the database
$flightsQuery = "SELECT f.flight_id, f.flight_number, f.airline_id, 
                a1.airport_name as source, a2.airport_name as destination 
                FROM flights f 
                JOIN airports a1 ON f.origin_airport = a1.airport_id 
                JOIN airports a2 ON f.destination_airport = a2.airport_id
                ORDER BY f.airline_id";
$flightsResult = $conn->query($flightsQuery);

// Initialize variables for form submission
$message = "";
$success = false;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user ID if logged in
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
    
    // Get form data
    $passenger_name = $_POST['passenger_name'];
    $flight_id = $_POST['flight_id'];
    $flight_number = $_POST['flight_number'];
    $journey_date = $_POST['journey_date'];
    $overall_rating = intval(substr($_POST['overall_rating'], 0, 1)); // Extract the number of stars
   $punctuality      = isset($_POST['q1']) ? $_POST['q1'] : null;
$cleanliness      = isset($_POST['q2']) ? $_POST['q2'] : null;
$staff_behavior   = isset($_POST['q3']) ? $_POST['q3'] : null;
$seat_comfort     = isset($_POST['q4']) ? $_POST['q4'] : null;
$cabin_clean      = isset($_POST['q5']) ? $_POST['q5'] : null;
$food_service     = isset($_POST['q6']) ? $_POST['q6'] : null;
$safety           = isset($_POST['q7']) ? $_POST['q7'] : null;
$entertainment    = isset($_POST['q8']) ? $_POST['q8'] : null;
$security_feeling = isset($_POST['q9']) ? $_POST['q9'] : null;
$recommendation   = isset($_POST['q10']) ? $_POST['q10'] : null;

    $additional_feedback = !empty($_POST['additional_feedback']) ? $_POST['additional_feedback'] : '';
    $complaint_type = !empty($_POST['complaint_type']) ? $_POST['complaint_type'] : NULL;
    $complaint_details = !empty($_POST['complaint_details']) ? $_POST['complaint_details'] : NULL;
    $contact_info = !empty($_POST['contact_info']) ? $_POST['contact_info'] : NULL;
    $website_experience = $_POST['website_experience'];
    $website_feedback = !empty($_POST['website_feedback']) ? $_POST['website_feedback'] : '';
    
    // Insert query
    try {
        $sql = "INSERT INTO feedback (
                    passenger_name, flight_id, flight_number, journey_date, 
                    overall_rating, punctuality, cleanliness, staff_behavior, seat_comfort, 
                    cabin_clean, food_service, safety, entertainment, security_feeling, 
                    recommendation, additional_feedback, complaint_type, complaint_details, 
                    contact_info, website_experience, website_feedback
                ) VALUES (
                    '$passenger_name', $flight_id, '$flight_number', '$journey_date', 
                    $overall_rating, '$punctuality', '$cleanliness', '$staff_behavior', '$seat_comfort', 
                    '$cabin_clean', '$food_service', '$safety', '$entertainment', '$security_feeling', 
                    '$recommendation', '$additional_feedback', " . 
                    ($complaint_type ? "'$complaint_type'" : "NULL") . ", " . 
                    ($complaint_details ? "'$complaint_details'" : "NULL") . ", " . 
                    ($contact_info ? "'$contact_info'" : "NULL") . ", " . 
                    "'$website_experience', '$website_feedback'
                )";
                
        if ($conn->query($sql)) {
            $success = true;
            $message = "Thank you for your feedback! Your input helps us improve our services.";
            
            // Send email to admin
            $admin_email = "admin@airlines-team.com";
            $subject = "New Flight Review Submission";
            
            // Get flight details
            $flightQuery = "SELECT flight_number, airline_id FROM flights WHERE flight_id = $flight_id";
            $flightResult = $conn->query($flightQuery);
            $flightRow = $flightResult->fetch_assoc();
            $flight_name = $flightRow['airline_id'] . ' ' . $flightRow['flight_number'];
            
            $email_body = "
                <html>
                <head>
                    <title>New Flight Review Submission</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .container { max-width: 600px; margin: 0 auto; }
                        .header { background: #0044cc; color: white; padding: 10px; text-align: center; }
                        .content { padding: 20px; }
                        .rating { font-weight: bold; }
                        .footer { background: #f1f1f1; padding: 10px; text-align: center; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>New Feedback Received</h2>
                        </div>
                        <div class='content'>
                            <p><strong>Passenger:</strong> $passenger_name</p>
                            <p><strong>Flight:</strong> $flight_name</p>
                            <p><strong>Journey Date:</strong> $journey_date</p>
                            <p><strong>Overall Rating:</strong> <span class='rating'>$overall_rating ⭐</span></p>
                            <p><strong>Additional Feedback:</strong> $additional_feedback</p>
                            " . ($complaint_type ? "<p><strong>Complaint Type:</strong> $complaint_type</p>" : "") . "
                            " . ($complaint_details ? "<p><strong>Complaint Details:</strong> $complaint_details</p>" : "") . "
                            " . ($contact_info ? "<p><strong>Contact Info:</strong> $contact_info</p>" : "") . "
                            <p><strong>Website Experience:</strong> $website_experience</p>
                            <p><strong>Website Feedback:</strong> $website_feedback</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message from Airlines Feedback System</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            // Here you would typically add code to send the email
        }
    } catch (Exception $e) {
        $success = false;
        $message = "Error submitting feedback: " . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Flight Review & Complaint | BookMyFlight</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-800 text-gray-100 min-h-screen flex flex-col">

  <!-- Header -->
  <header class="bg-gray-900 py-4 shadow-md">
    <div class="container mx-auto px-4 flex justify-between items-center">
      <nav class="flex space-x-6">
        <a href="index.php" class="hover:text-indigo-300 transition">Home</a>
        <a href="feedback.php" class="text-indigo-300 font-medium">Flight Review</a>
      </nav>
      <button onclick="login()" class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded transition">
        <i class="fas fa-sign-in-alt mr-2"></i>Login
      </button>
    </div>
  </header>

  <!-- Main Content -->
  <main class="flex-grow container mx-auto px-4 py-8">
    <div class="text-center mb-12">
      <h1 class="text-3xl font-bold mb-2">Flight Review & Complaint</h1>
      <p class="text-gray-300 max-w-2xl mx-auto">Share your experience or raise an issue regarding your recent flight. Your feedback helps us improve.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-8 max-w-6xl mx-auto">
      <!-- Review & Complaint Form -->
      <div class="bg-gray-700 rounded-xl p-6 shadow-lg">
        <h2 class="text-xl font-semibold mb-4 text-indigo-300 flex items-center">
          <i class="fas fa-paper-plane mr-2"></i>Submit Your Review
        </h2>

        <?php if (!empty($message)): ?>
          <div class="mb-4 p-3 rounded <?= $success ? 'bg-green-600' : 'bg-red-600' ?> text-white font-semibold">
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif; ?>

        <form method="post" action="feedback.php" class="space-y-4">
          <div>
            <label for="passenger_name" class="block mb-2 text-sm">Passenger Name</label>
            <input type="text" id="passenger_name" name="passenger_name" required
              class="w-full px-4 py-2 rounded bg-gray-600 focus:bg-gray-500 transition text-gray-100" />
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="flight_id" class="block mb-2 text-sm">Flight</label>
              <select id="flight_id" name="flight_id" required onchange="updateFlightNumber()"
                class="w-full px-4 py-2 rounded bg-gray-600 focus:bg-gray-500 transition text-gray-100">
                <option value="">Select Flight</option>
                <?php
                 if ($flightsResult->num_rows > 0) {
                while($row = $flightsResult->fetch_assoc()) {
                    echo '<option value="' . $row["flight_id"] . '" data-number="' . $row["flight_number"] . '">' 
                        . $row["airline_name"] . ' ' . $row["flight_number"] . ' (' . $row["source"] . ' to ' . $row["destination"] . ')</option>';
                }
            }
            ?>

                  // Output flight options as in your PHP logic
                ?>
              </select>
            </div>
            <div>
              <label for="flight_number" class="block mb-2 text-sm">Flight Number</label>
              <input type="text" id="flight_number" name="flight_number" readonly required
                class="w-full px-4 py-2 rounded bg-gray-600 text-gray-400" />
            </div>
          </div>
          <div>
            <label for="journey_date" class="block mb-2 text-sm">Journey Date</label>
            <input type="date" id="journey_date" name="journey_date" required
              class="w-full px-4 py-2 rounded bg-gray-600 focus:bg-gray-500 transition text-gray-100" />
          </div>

          <div>
            <label for="overall_rating" class="block mb-2 text-sm">Overall Flight Rating</label>
            <select id="overall_rating" name="overall_rating" required
              class="w-full px-4 py-2 rounded bg-gray-600 focus:bg-gray-500 transition text-gray-100">
              <option value="">Select Rating</option>
              <option>⭐</option>
              <option>⭐⭐</option>
              <option>⭐⭐⭐</option>
              <option>⭐⭐⭐⭐</option>
              <option>⭐⭐⭐⭐⭐</option>
            </select>
          </div>

          <!-- Example for one question, repeat for all 10 -->
          <div>
            <label class="block mb-2 text-sm">1. Was the flight punctual?</label>
            <div class="flex space-x-4">
              <label class="flex items-center">
                <input type="radio" name="q1" value="Yes" required class="text-indigo-500 focus:ring-indigo-500" />
                <span class="ml-2">Yes</span>
              </label>
              <label class="flex items-center">
                <input type="radio" name="q1" value="No" class="text-indigo-500 focus:ring-indigo-500" />
                <span class="ml-2">No</span>
              </label>
            </div>
          </div>
          <!-- Repeat similar blocks for q2 to q10 with appropriate options -->

          <div>
            <label for="additional_feedback" class="block mb-2 text-sm">Additional Feedback (optional)</label>
            <textarea id="additional_feedback" name="additional_feedback" rows="3"
              class="w-full px-4 py-2 rounded bg-gray-600 focus:bg-gray-500 transition text-gray-100"></textarea>
          </div>

          <div class="border-t border-gray-600 pt-4 mt-4">
            <h3 class="text-indigo-300 font-semibold mb-2 flex items-center">
              <i class="fas fa-exclamation-circle mr-2"></i>Complaint Box (Optional)
            </h3>
            <div>
              <label for="complaint_type" class="block mb-2 text-sm">Complaint Type</label>
              <select id="complaint_type" name="complaint_type"
                class="w-full px-4 py-2 rounded bg-gray-600 focus:bg-gray-500 transition text-gray-100">
                <option value="">Select Complaint Type</option>
                <option>Flight Delay</option>
                <option>Lost Luggage</option>
                <option>Staff Behavior</option>
                <option>Food Quality</option>
                <option>Safety Concern</option>
                <option>Other</option>
              </select>
            </div>
            <div>
              <label for="complaint_details" class="block mb-2 text-sm">Describe your issue...</label>
              <textarea id="complaint_details" name="complaint_details" rows="2"
                class="w-full px-4 py-2 rounded bg-gray-600 focus:bg-gray-500 transition text-gray-100"></textarea>
            </div>
            <div>
              <label for="contact_info" class="block mb-2 text-sm">Contact (Phone/Email)</label>
              <input type="text" id="contact_info" name="contact_info"
                class="w-full px-4 py-2 rounded bg-gray-600 focus:bg-gray-500 transition text-gray-100" />
            </div>
          </div>

          <div class="border-t border-gray-600 pt-4 mt-4">
            <h3 class="text-indigo-300 font-semibold mb-2 flex items-center">
              <i class="fas fa-laptop mr-2"></i>Website Experience
            </h3>
            <div class="flex space-x-4 mb-2">
              <label class="flex items-center">
                <input type="radio" name="website_experience" value="Excellent" required class="text-indigo-500 focus:ring-indigo-500" />
                <span class="ml-2">Excellent</span>
              </label>
              <label class="flex items-center">
                <input type="radio" name="website_experience" value="Good" class="text-indigo-500 focus:ring-indigo-500" />
                <span class="ml-2">Good</span>
              </label>
              <label class="flex items-center">
                <input type="radio" name="website_experience" value="Average" class="text-indigo-500 focus:ring-indigo-500" />
                <span class="ml-2">Average</span>
              </label>
              <label class="flex items-center">
                <input type="radio" name="website_experience" value="Poor" class="text-indigo-500 focus:ring-indigo-500" />
                <span class="ml-2">Poor</span>
              </label>
            </div>
            <textarea name="website_feedback" rows="2" placeholder="How can we improve our website? (optional)"
              class="w-full px-4 py-2 rounded bg-gray-600 focus:bg-gray-500 transition text-gray-100"></textarea>
          </div>

          <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-500 px-6 py-2 rounded transition w-full font-semibold flex items-center justify-center">
            <i class="fas fa-paper-plane mr-2"></i>Submit Review
          </button>
        </form>
      </div>

      <!-- Contact Info (optional, can be replaced with other content) -->
      <div class="bg-gray-700 rounded-xl p-6 shadow-lg">
        <h2 class="text-xl font-semibold mb-4 text-indigo-300 flex items-center">
          <i class="fas fa-info-circle mr-2"></i>Our Information
        </h2>
        <div class="space-y-5">
          <div class="flex items-start">
            <i class="fas fa-map-marker-alt text-indigo-300 mt-1 mr-3"></i>
            <div>
              <h3 class="font-medium">Headquarters</h3>
              <p class="text-sm text-gray-300">BookMyFlight Pvt. Ltd., Sector 62, Noida, UP – 201309</p>
            </div>
          </div>
          <div class="flex items-start">
            <i class="fas fa-phone-alt text-indigo-300 mt-1 mr-3"></i>
            <div>
              <h3 class="font-medium">Customer Support</h3>
              <p class="text-sm text-gray-300">1800-123-4567 (Toll-free)<br />Mon-Sat, 9AM-7PM</p>
            </div>
          </div>
          <div class="flex items-start">
            <i class="fas fa-envelope text-indigo-300 mt-1 mr-3"></i>
            <div>
              <h3 class="font-medium">Email</h3>
              <p class="text-sm text-gray-300">support@bookmyflight.in</p>
            </div>
          </div>
          <div class="pt-4 border-t border-gray-600">
            <p class="text-xs text-gray-400 italic">
              For urgent travel issues, please call our helpline directly.
            </p>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-900 py-6 text-center text-sm text-gray-400">
    <p>© 2025 BookMyFlight. All rights reserved. <i class="fas fa-plane ml-1"></i></p>
  </footer>

  <script>
    function login() {
      if (confirm("Redirect to login page?")) {
        window.location.href = "login.php";
      }
    }
    function updateFlightNumber() {
      const flightSelect = document.getElementById('flight_id');
      const flightNumberInput = document.getElementById('flight_number');
      if (flightSelect.value) {
        const selectedOption = flightSelect.options[flightSelect.selectedIndex];
        flightNumberInput.value = selectedOption.getAttribute('data-number');
      } else {
        flightNumberInput.value = '';
      }
    }
  </script>
</body>
</html>
