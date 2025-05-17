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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Register | TUXIMO</title>
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

      <!-- Registration Form -->
      <div class="p-8">
        <div class="text-center mb-8">
          <h1 class="text-2xl font-bold text-gray-800">Register New Admin</h1>
          <p class="text-gray-600 mt-2">Fill in details to create an account</p>
        </div>

        <?php if (!empty($error_message)): ?>
          <div class="mb-4 text-red-600 font-medium"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
          <input type="hidden" name="register" value="1" />

          <div>
            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
            <input type="text" name="full_name" required class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
          </div>

          <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
            <input type="text" name="username" required class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
          </div>

          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" required class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
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
            Register
          </button>
        </form>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-white py-6 border-t">
    <div class="container mx-auto px-6 text-center text-gray-500 text-sm">
      &copy; 2025 TUXIMO. All rights reserved.
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
