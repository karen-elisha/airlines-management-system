<?php
session_start();

// Check if user is already logged in
/*if(isset($_SESSION['user_id'])) {
    header("Location: user-dashboard.php");
    exit();
}*/

// Include database connection
require_once 'db_connect.php';

$error = '';

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // Prepare a select statement
        $sql = "SELECT user_id, full_name, email, password FROM users WHERE email = ?";
        
        if ($stmt = $mysqli->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $email);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                // Check if email exists
                if ($stmt->num_rows == 1) {
                    // Bind result variables
                    $stmt->bind_result($user_id, $full_name, $db_email, $hashed_password);
                    if ($stmt->fetch()) {
                        // Verify password
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $user_id;
                            $_SESSION["email"] = $db_email;
                            $_SESSION["full_name"] = $full_name;
                            
                            // Set cookie if remember me is checked
                            if ($remember) {
                                setcookie("user_login", $email, time() + (86400 * 30), "/"); // 30 days
                            }
                            
                            // Redirect user to dashboard
                            header("location: user-dashboard.php");
                            exit();
                        } else {
                            // Password is not valid
                            $error = "Invalid email or password";
                        }
                    }
                } else {
                    // Email doesn't exist
                    $error = "Invalid email or password";
                }
            } else {
                $error = "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            $stmt->close();
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Login | BOOKMYFLIGHT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .login-bg {
      background: url('https://images.unsplash.com/photo-1436491865332-7a61a109cc05') center/cover no-repeat;
    }
    .login-overlay {
      background-color: rgba(0, 0, 0, 0.7);
    }
  </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4">

  <!-- Login Card -->
  <div class="login-overlay rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
    <div class="p-8 md:p-10 text-white">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="flex justify-center mb-4">
          <i class="fas fa-plane-departure text-indigo-400 text-4xl"></i>
        </div>
        <h1 class="text-2xl font-bold">Welcome to BOOKMYFLIGHT</h1>
        <p class="text-gray-300 mt-2">Sign in to your account</p>
      </div>

      <?php if(!empty($error)): ?>
        <div class="bg-red-500 bg-opacity-20 border border-red-400 text-red-300 px-4 py-3 rounded relative mb-4" role="alert">
          <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-5">
        <div>
          <label for="email" class="block text-sm font-medium mb-1">Email Address</label>
          <div class="relative">
            <input type="email" id="email" name="email" required
              class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              value="<?php echo isset($_COOKIE['user_login']) ? $_COOKIE['user_login'] : ''; ?>">
            <i class="fas fa-envelope absolute right-3 top-3.5 text-gray-400"></i>
          </div>
        </div>

        <div>
          <label for="password" class="block text-sm font-medium mb-1">Password</label>
          <div class="relative">
            <input type="password" id="password" name="password" required
              class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <i class="fas fa-lock absolute right-3 top-3.5 text-gray-400"></i>
            <button type="button" onclick="togglePassword()" class="absolute right-10 top-3.5 text-gray-400 hover:text-gray-300">
              <i class="far fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input type="checkbox" id="remember" name="remember" class="h-4 w-4 rounded border-gray-600 bg-gray-700"
                <?php echo isset($_COOKIE['user_login']) ? 'checked' : ''; ?>>
            <label for="remember" class="ml-2 text-sm">Remember me</label>
          </div>
          <a href="forgot-password.php" class="text-sm text-indigo-400 hover:underline">Forgot password?</a>
        </div>

        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-medium transition">
          Sign In
        </button>
      </form>

      <!-- Footer Links -->
      <div class="mt-6 text-center text-sm">
        <p class="text-gray-400">Don't have an account? 
          <a href="signup.php" class="text-indigo-400 hover:underline">Sign up</a>
        </p>
      </div>
    </div>
  </div>

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