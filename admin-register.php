<?php
// Direct MySQLi connection (replace with your real credentials)
$host = "localhost";
$user = "root";
$password = "";
$database = "airlines";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$success_message = $error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
  $full_name = $_POST['full_name'];
  $username = $_POST['username'];

  // Check for existing user
  $check = $conn->prepare("SELECT admin_id FROM admins WHERE email = ? OR username = ?");
  $check->bind_param("ss", $email, $username);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    $error_message = "An admin with this email or username already exists.";
  } else {
    $stmt = $conn->prepare("INSERT INTO admins (username, password, email, full_name, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $username, $password, $email, $full_name);

    if ($stmt->execute()) {
      // Redirect to login page after successful registration
      header("Location: admin-login.php");
      exit;
    } else {
      $error_message = "Error: " . $stmt->error;
    }

    $stmt->close();
  }

  $check->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Register | BOOKMYFLIGHT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center p-4">

  <!-- Register Card -->
  <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
    <div class="p-8 md:p-10 text-white">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="flex justify-center items-center mb-4">
          <i class="fas fa-plane-departure text-indigo-400 text-3xl mr-3"></i>
          <div>
            <h1 class="text-2xl font-bold text-indigo-400">BOOKMYFLIGHT</h1>
            <div class="flex items-center justify-center mt-1">
              <i class="fas fa-shield-alt text-gray-400 text-sm mr-1"></i>
              <span class="text-gray-400 text-sm">Admin Portal</span>
            </div>
          </div>
        </div>
        <p class="text-gray-300 mt-2">Create your admin account</p>
      </div>

      <?php if(!empty($error_message)): ?>
        <div class="bg-red-500 bg-opacity-20 border border-red-400 text-red-300 px-4 py-3 rounded relative mb-4" role="alert">
          <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
      <?php endif; ?>

      <!-- Registration Form -->
      <form method="POST" class="space-y-5">
        <input type="hidden" name="register" value="1" />

        <div>
          <label for="full_name" class="block text-sm font-medium mb-1">Full Name</label>
          <div class="relative">
            <input type="text" id="full_name" name="full_name" required
              class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-white"
              value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            <i class="fas fa-user-tie absolute right-3 top-3.5 text-gray-400"></i>
          </div>
        </div>

        <div>
          <label for="username" class="block text-sm font-medium mb-1">Username</label>
          <div class="relative">
            <input type="text" id="username" name="username" required
              class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-white"
              value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            <i class="fas fa-user absolute right-3 top-3.5 text-gray-400"></i>
          </div>
        </div>

        <div>
          <label for="email" class="block text-sm font-medium mb-1">Email Address</label>
          <div class="relative">
            <input type="email" id="email" name="email" required
              class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-white"
              value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <i class="fas fa-envelope absolute right-3 top-3.5 text-gray-400"></i>
          </div>
        </div>

        <div>
          <label for="password" class="block text-sm font-medium mb-1">Password</label>
          <div class="relative">
            <input type="password" id="password" name="password" required
              class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-white">
            <i class="fas fa-lock absolute right-3 top-3.5 text-gray-400"></i>
            <button type="button" onclick="togglePassword()" class="absolute right-10 top-3.5 text-gray-400 hover:text-gray-300">
              <i class="far fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between pt-2">
          <a href="index.php" class="inline-flex items-center px-3 py-1 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white rounded-md transition">
            <i class="fas fa-home mr-1"></i>
            Back to Home
          </a>
          <a href="admin-login.php" class="text-sm text-indigo-400 hover:underline">
            Already have an account?
          </a>
        </div>

        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-medium transition">
          Create Admin Account
        </button>
      </form>
    </div>
  </div>

  <!-- Footer -->
  <footer class="fixed bottom-0 left-0 right-0 bg-gray-800 py-4 border-t border-gray-700">
    <div class="container mx-auto px-6 text-center text-gray-400 text-sm">
      &copy; 2025 BOOKMYFLIGHT. All rights reserved. | Academic Project
    </div>
  </footer>

  <script>
    // Toggle password visibility
    function togglePassword() {
      const password = document.getElementById('password');
      const icon = document.querySelector('#password ~ button i');
      if (password.type === 'password') {
        password.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        password.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    }
  </script>
</body>
</html>