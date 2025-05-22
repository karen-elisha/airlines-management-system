<?php
session_start();

// Replace these with your actual DB credentials
$host = "localhost";
$user = "root";
$password = "";
$database = "airlines";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $password_input = $_POST['password'];

  $stmt = $conn->prepare("SELECT admin_id, password FROM admins WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows === 1) {
    $stmt->bind_result($admin_id, $hashed_password);
    $stmt->fetch();

    if (password_verify($password_input, $hashed_password)) {
      // Successful login
      $_SESSION['admin_id'] = $admin_id;
      $_SESSION['admin_username'] = $username;
      header("Location: admin-dashboard.php");
      exit;
    } else {
      $error_message = "Incorrect password.";
    }
  } else {
    $error_message = "No admin found with that username.";
  }

  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login | TUXIMO</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100 font-sans antialiased">

  <!-- Header -->
  <header class="bg-white shadow-sm">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
      <div class="flex items-center space-x-2">
        <i class="fas fa-shield-alt text-indigo-600 text-2xl"></i>
        <span class="text-xl font-semibold">Admin Portal</span>
      </div>
      <a href="index.html" class="text-gray-600 hover:text-indigo-600 transition">Back to Home</a>
    </div>
  </header>

  <!-- Main Content -->
  <main class="container mx-auto px-4 py-12 flex justify-center">
    <div class="bg-white rounded-xl shadow-md w-full max-w-md overflow-hidden">

      <!-- Login Form -->
      <div class="p-8">
        <div class="text-center mb-8">
          <h1 class="text-2xl font-bold text-gray-800">Admin Login</h1>
          <p class="text-gray-600 mt-2">Enter credentials to access your account</p>
        </div>

        <?php if (!empty($error_message)): ?>
          <div class="mb-4 text-red-600 font-medium"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
          <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
            <input type="text" name="username" required class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <div class="relative">
              <input type="password" name="password" id="password" required class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
              <button type="button" onclick="togglePassword()" class="absolute right-3 top-3.5 text-gray-500">
                <i class="far fa-eye"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700 transition">
            Login
          </button>
        </form>

        <!-- Registration Link -->
        <div class="mt-6 text-center">
          <p class="text-gray-600 text-sm">
            New admin? 
            <a href="admin-register.php" class="text-indigo-600 hover:text-indigo-800 font-medium transition">
              Register here
            </a>
          </p>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-white py-6 border-t">
    <div class="container mx-auto px-6 text-center text-gray-500 text-sm">
      &copy; © 2025 BOOKMYFLIGHT. All rights reserved. | Academic Project
    </div>
  </footer>

  <script>
    function togglePassword() {
      const pwd = document.getElementById('password');
      pwd.type = pwd.type === 'password' ? 'text' : 'password';
    }
  </script>
</body>
</html>