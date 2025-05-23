<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DB connection setup
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

    // Prepare and sanitize input
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : NULL;
    $name = $conn->real_escape_string(trim($_POST['name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $rating = intval($_POST['rating']);
    $message = $conn->real_escape_string(trim($_POST['message']));
    $created_at = date('Y-m-d H:i:s');

    // Debugging: Print the values to check they're correct
    /*
    echo "<pre>";
    print_r([
        'user_id' => $user_id,
        'name' => $name,
        'email' => $email,
        'rating' => $rating,
        'message' => $message,
        'created_at' => $created_at
    ]);
    echo "</pre>";
    */

    // Insert query - modified to handle NULL user_id
    $sql = "INSERT INTO feedback (user_id, name, email, rating, message, created_at) 
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssis", $user_id, $name, $email, $rating, $message, $created_at);

    if ($stmt->execute()) {
        // Redirect on success
        header("Location: index.php");
        exit();
    } else {
        $feedback_msg = "Error: " . $stmt->error;
        // For debugging - show the full error:
        // $feedback_msg = "Error: " . $stmt->error . " SQL: " . $sql;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Contact Us | BookMyFlight</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  />
</head>
<body class="bg-gray-800 text-gray-100 min-h-screen flex flex-col">
  <!-- Header -->
  <header class="bg-gray-900 py-4 shadow-md">
    <div class="container mx-auto px-4 flex justify-between items-center">
      <nav class="flex space-x-6">
        <a href="index.php" class="hover:text-indigo-300 transition">Home</a>
        <a href="feedback.html" class="text-indigo-300 font-medium">Feedback</a>
      </nav>
      <button onclick="login()" class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded transition">
        <i class="fas fa-sign-in-alt mr-2"></i>Login
      </button>
    </div>
  </header>

  <!-- Main Content -->
  <main class="flex-grow container mx-auto px-4 py-8">
    <div class="text-center mb-12">
      <h1 class="text-3xl font-bold mb-2">Contact Our Team</h1>
      <p class="text-gray-300 max-w-2xl mx-auto">We're here to help with any questions about your flights or bookings.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-8 max-w-6xl mx-auto">
      <!-- Contact Form -->
      <div class="bg-gray-700 rounded-xl p-6 shadow-lg">
        <h2 class="text-xl font-semibold mb-4 text-indigo-300 flex items-center">
          <i class="fas fa-comment-alt mr-2"></i>Send Message
        </h2>

        <?php if (!empty($feedback_msg)): ?>
          <div class="mb-4 p-3 rounded bg-red-600 text-white font-semibold">
            <?= htmlspecialchars($feedback_msg) ?>
          </div>
        <?php endif; ?>

        <form id="contactForm" class="space-y-4" method="POST" action="">
          <div>
            <label for="name" class="block mb-2 text-sm">Full Name</label>
            <input
              type="text"
              id="name"
              name="name"
              class="w-full px-4 py-2 rounded bg-gray-600 focus:bg-gray-500 transition"
              required
              value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
            />
          </div>
          <div>
            <label for="email" class="block mb-2 text-sm">Email Address</label>
            <input
              type="email"
              id="email"
              name="email"
              class="w-full px-4 py-2 rounded bg-gray-600 focus:bg-gray-500 transition"
              required
              value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
            />
          </div>
          <div>
            <label for="rating" class="block mb-2 text-sm">Rating (1 to 5)</label>
            <select
              id="rating"
              name="rating"
              class="w-full px-4 py-2 rounded bg-gray-600 focus:bg-gray-500 transition"
              required
            >
              <option value="" disabled <?= !isset($_POST['rating']) ? 'selected' : '' ?>>Select rating</option>
              <?php
                for ($i=1; $i<=5; $i++) {
                  $selected = (isset($_POST['rating']) && $_POST['rating'] == $i) ? 'selected' : '';
                  echo "<option value='$i' $selected>$i</option>";
                }
              ?>
            </select>
          </div>
          <div>
            <label for="message" class="block mb-2 text-sm">Your Message</label>
            <textarea
              id="message"
              name="message"
              rows="4"
              class="w-full px-4 py-2 rounded bg-gray-600 focus:bg-gray-500 transition"
              required
            ><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
          </div>
          <button
            type="submit"
            class="bg-indigo-600 hover:bg-indigo-500 px-6 py-2 rounded transition w-full"
          >
            <i class="fas fa-paper-plane mr-2"></i>Submit
          </button>
        </form>
      </div>

      <!-- Contact Info -->
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
  </script>
</body>
</html>