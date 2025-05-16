/* This is a simple error page to handle booking failures */

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking Error | BookMyFlight</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-b from-gray-800 to-gray-700 min-h-screen text-white">
  <!-- Header -->
  <header class="bg-gray-900 p-4 shadow-md">
    <div class="container mx-auto flex justify-between items-center">
      <a href="index.php" class="text-xl font-bold flex items-center">
        <i class="fas fa-plane-departure mr-2 text-indigo-400"></i>
        BookMyFlight
      </a>
      <nav class="flex items-center space-x-6">
        <a href="index.php" class="hover:text-indigo-400 transition">Home</a>
        <a href="feedback.html" class="hover:text-indigo-400 transition">Feedback</a>
        <a href="login.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded transition">
          <i class="fas fa-user mr-2"></i> Login
        </a>
      </nav>
    </div>
  </header>

  <!-- Main Content -->
  <main class="container mx-auto px-4 py-16 flex flex-col items-center">
    <div class="bg-white rounded-lg shadow-lg p-8 text-gray-800 max-w-md w-full">
      <div class="text-center mb-6">
        <i class="fas fa-exclamation-triangle text-red-500 text-5xl mb-4"></i>
        <h2 class="text-2xl font-bold">Booking Failed</h2>
        <p class="text-gray-600 mt-2">
          We encountered an error while processing your booking. This could be due to one of the following reasons:
        </p>
      </div>
      
      <ul class="text-gray-600 space-y-2 mb-6 list-disc pl-5">
        <li>Database connection issue</li>
        <li>Invalid passenger information</li>
        <li>Flight no longer available</li>
        <li>System maintenance</li>
      </ul>
      
      <div class="mt-8 flex flex-col space-y-3">
        <a href="booking-forms.php" class="bg-indigo-600 text-white text-center py-3 rounded-md hover:bg-indigo-700 transition">
          <i class="fas fa-redo mr-2"></i> Try Again
        </a>
        <a href="index.php" class="border border-gray-300 text-gray-700 text-center py-3 rounded-md hover:bg-gray-100 transition">
          <i class="fas fa-home mr-2"></i> Return to Home
        </a>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-900 text-center p-6 text-gray-300 mt-auto">
    <div class="container mx-auto">
      <div class="flex flex-col md:flex-row justify-between items-center">
        <div class="mb-4 md:mb-0">
          <a href="index.php" class="flex items-center justify-center md:justify-start">
            <i class="fas fa-plane-departure mr-2 text-indigo-400"></i>
            <span class="font-bold">BookMyFlight</span>
          </a>
        </div>
        <div class="flex space-x-4">
          <a href="#" class="hover:text-indigo-400 transition"><i class="fab fa-facebook"></i></a>
          <a href="#" class="hover:text-indigo-400 transition"><i class="fab fa-twitter"></i></a>
          <a href="#" class="hover:text-indigo-400 transition"><i class="fab fa-instagram"></i></a>
        </div>
      </div>
      <div class="mt-4">
        <p>© 2025 BookMyFlight. All rights reserved.</p>
      </div>
    </div>
  </footer>
</body>
</html>