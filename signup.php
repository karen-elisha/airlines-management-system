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
$success = '';

// Process signup form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $full_name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $terms = isset($_POST['terms']) ? true : false;
    
    // Validate input
    if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
        $error = "Please fill out all required fields";
    } elseif (strlen($full_name) < 3) {
        $error = "Name must be at least 3 characters";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        $error = "Please enter a valid phone number (10-15 digits)";
    } elseif (strlen($password) < 6 || !preg_match("/[0-9]/", $password)) {
        $error = "Password must be at least 6 characters and contain at least 1 number";
    } elseif (!$terms) {
        $error = "You must agree to the terms and conditions";
    } else {
        // Check if email already exists
        $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "This email is already registered";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_stmt = $mysqli->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $full_name, $email, $phone, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $success = "Account created successfully! You can now log in.";
                // Redirect to login after 2 seconds
                header("Refresh: 2; URL=login.php");
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            
            $insert_stmt->close();
        }
        
        $stmt->close();
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up - BOOKMYFLIGHT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#f0f9ff',
              100: '#e0f2fe',
              200: '#bae6fd',
              300: '#7dd3fc',
              400: '#38bdf8',
              500: '#0ea5e9',
              600: '#0284c7',
              700: '#0369a1',
              800: '#075985',
              900: '#0c4a6e',
            },
            secondary: {
              50: '#f8fafc',
              100: '#f1f5f9',
              200: '#e2e8f0',
              300: '#cbd5e1',
              400: '#94a3b8',
              500: '#64748b',
              600: '#475569',
              700: '#334155',
              800: '#1e293b',
              900: '#0f172a',
            }
          },
          fontFamily: {
            sans: ['Inter', 'ui-sans-serif', 'system-ui']
          }
        }
      }
    }
  </script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    body {
      font-family: 'Inter', sans-serif;
    }
    
    .input-error {
      border-color: #ef4444 !important;
    }
    
    .error-message {
      color: #ef4444;
      font-size: 0.875rem;
      margin-top: 0.25rem;
    }
    
    .password-toggle {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #64748b;
    }
    
    .password-toggle:hover {
      color: #334155;
    }
  </style>
</head>
<body class="bg-gradient-to-br from-primary-50 to-primary-100 min-h-screen">

  <!-- Top Navbar -->
  <nav class="bg-primary-900 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <div class="flex items-center">
          <div class="flex-shrink-0 flex items-center">
            <img src="https://img.icons8.com/ios-filled/50/ffffff/airport.png" class="w-8 h-8 mr-2" alt="BookMyFlight Logo" />
            <span class="text-xl font-bold">BookMyFlight</span>
          </div>
        </div>
        <div class="hidden md:block">
          <div class="ml-10 flex items-baseline space-x-4">
            <a href="index.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-primary-800">Home</a>
            <a href="login.php" class="px-3 py-2 rounded-md text-sm font-medium bg-primary-600 hover:bg-primary-700">Login</a>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex flex-col md:flex-row items-center justify-center gap-12">
      <!-- Hero Section -->
      <div class="md:w-1/2 text-center md:text-left">
        <h1 class="text-4xl font-bold text-primary-900 mb-4">Start Your Journey With Us</h1>
        <p class="text-lg text-secondary-600 mb-8">Join over 5 million travelers who trust BookMyFlight for their travel needs. Get exclusive deals and rewards.</p>
        <div class="space-y-4">
          <div class="flex items-center">
            <i class="fas fa-check-circle text-primary-600 mr-3 text-xl"></i>
            <span class="text-secondary-700">Best price guarantee</span>
          </div>
          <div class="flex items-center">
            <i class="fas fa-check-circle text-primary-600 mr-3 text-xl"></i>
            <span class="text-secondary-700">No booking fees</span>
          </div>
          <div class="flex items-center">
            <i class="fas fa-check-circle text-primary-600 mr-3 text-xl"></i>
            <span class="text-secondary-700">24/7 customer support</span>
          </div>
        </div>
      </div>
      
      <!-- Signup Form -->
      <div class="md:w-1/2 w-full max-w-md">
        <div class="bg-white shadow-xl rounded-xl overflow-hidden">
          <div class="bg-primary-700 px-6 py-4">
            <h2 class="text-2xl font-bold text-white">Create Your Account</h2>
          </div>
          
          <?php if(!empty($error)): ?>
            <div class="mt-4 mx-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
              <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
          <?php endif; ?>
          
          <?php if(!empty($success)): ?>
            <div class="mt-4 mx-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
              <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
          <?php endif; ?>
          
          <form id="signupForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="p-6 space-y-6">
            <div>
              <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">Full Name</label>
              <div class="relative">
                <input type="text" id="name" name="name" class="w-full px-4 py-3 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition" placeholder="John Doe" required />
                <i class="fas fa-user absolute right-3 top-3.5 text-secondary-400"></i>
              </div>
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-secondary-700 mb-1">Email Address</label>
              <div class="relative">
                <input type="email" id="email" name="email" class="w-full px-4 py-3 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition" placeholder="john@example.com" required />
                <i class="fas fa-envelope absolute right-3 top-3.5 text-secondary-400"></i>
              </div>
            </div>

            <div>
              <label for="phone" class="block text-sm font-medium text-secondary-700 mb-1">Phone Number</label>
              <div class="relative">
                <input type="tel" id="phone" name="phone" class="w-full px-4 py-3 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition" placeholder="1234567890" required />
                <i class="fas fa-phone absolute right-3 top-3.5 text-secondary-400"></i>
              </div>
            </div>

            <div>
              <label for="password" class="block text-sm font-medium text-secondary-700 mb-1">Password</label>
              <div class="relative">
                <input type="password" id="password" name="password" class="w-full px-4 py-3 border border-secondary-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition pr-10" placeholder="••••••••" required />
                <i id="togglePassword" class="password-toggle fas fa-eye-slash" onclick="togglePasswordVisibility()"></i>
              </div>
              <div class="mt-2 text-xs text-secondary-500">
                <p>Password must contain:</p>
                <ul class="list-disc list-inside">
                  <li id="length-requirement" class="text-secondary-400">At least 6 characters</li>
                  <li id="number-requirement" class="text-secondary-400">At least 1 number</li>
                </ul>
              </div>
            </div>

            <div class="flex items-center">
              <input type="checkbox" id="terms" name="terms" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-secondary-300 rounded" required />
              <label for="terms" class="ml-2 block text-sm text-secondary-700">
                I agree to the <a href="#" class="text-primary-600 hover:underline">Terms of Service</a> and <a href="#" class="text-primary-600 hover:underline">Privacy Policy</a>
              </label>
            </div>

            <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 flex items-center justify-center">
              <span id="submit-text">Create Account</span>
              <span id="submit-spinner" class="hidden ml-2">
                <i class="fas fa-spinner fa-spin"></i>
              </span>
            </button>
          </form>
          
          <div class="px-6 py-4 bg-secondary-50 border-t border-secondary-200">
            <p class="text-center text-sm text-secondary-600">
              Already have an account? <a href="login.php" class="font-medium text-primary-600 hover:underline">Sign in</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    // Form validation
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('signupForm');
      const passwordInput = document.getElementById('password');
      
      // Password requirements validation
      passwordInput.addEventListener('input', function() {
        const password = this.value;
        const lengthElement = document.getElementById('length-requirement');
        const numberElement = document.getElementById('number-requirement');
        
        // Validate length
        const hasLength = password.length >= 6;
        lengthElement.classList.toggle('text-secondary-400', !hasLength);
        lengthElement.classList.toggle('text-green-500', hasLength);
        
        // Validate number
        const hasNumber = /\d/.test(password);
        numberElement.classList.toggle('text-secondary-400', !hasNumber);
        numberElement.classList.toggle('text-green-500', hasNumber);
      });
      
      // Show loading spinner on form submit
      form.addEventListener('submit', function() {
        if (this.checkValidity()) {
          document.getElementById('submit-text').classList.add('hidden');
          document.getElementById('submit-spinner').classList.remove('hidden');
        }
      });
    });
    
    function togglePasswordVisibility() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.getElementById('togglePassword');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      }
    }
  </script>
</body>
</html>