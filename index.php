<?php
// Start session for user management
session_start();

// Database connection - make sure db_connect.php uses MySQLi
require_once 'db_connect.php';

// Fetch airports for dropdowns
$query = "SELECT city, airport_code FROM airports ORDER BY city";

// Execute query
$result = $mysqli->query($query);

// Check for errors
if (!$result) {
    die("Database error: " . $mysqli->error);
}

// Fetch results
$airports = [];
while ($row = $result->fetch_assoc()) {
    $airports[] = $row;
}

// Free result set
$result->free();

// Set current date as minimum date for departure
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BOOKMYFLIGHT | Flight Booking Portal</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .hero-bg {
      background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1436491865332-7a61a109cc05?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80');
      background-size: cover;
      background-position: center;
    }
    .card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
    }
    .swap-btn:hover {
      transform: scale(1.05);
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans antialiased">

<!-- Navigation -->
<header class="bg-indigo-900 text-white shadow-lg fixed w-full z-50">
  <div class="container mx-auto px-6 py-4 flex justify-between items-center">
    <div class="flex items-center space-x-2">
      <i class="fas fa-plane-departure text-2xl text-indigo-300"></i>
      <span class="text-xl font-bold">BOOKMYFLIGHT</span>
    </div>
    <nav class="hidden md:flex items-center space-x-8">
      <a href="airline-details.html" class="hover:text-indigo-300 transition">Airlines</a>
      <a href="feedback.html" class="hover:text-indigo-300 transition">Feedback</a>
      <a href="#destinations" class="hover:text-indigo-300 transition">Destinations</a>
      <div class="relative">
        
          <button onclick="toggleDropdown()" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-md flex items-center">
            <i class="fas fa-user-circle mr-2"></i> Login
          </button>
          <div id="loginDropdown" class="absolute right-0 mt-2 w-48 bg-white text-gray-800 rounded-md shadow-xl hidden">
            <a href="login-user.php" class="block px-4 py-2 hover:bg-indigo-50 text-indigo-600"><i class="fas fa-user mr-2"></i>User Login</a>
            <a href="login-admin.php" class="block px-4 py-2 hover:bg-indigo-50 text-indigo-600"><i class="fas fa-lock mr-2"></i>Admin Login</a>
          </div>
       
      </div>
    </nav>
    <button class="md:hidden text-2xl" onclick="toggleMobileMenu()">
      <i class="fas fa-bars"></i>
    </button>
  </div>
  <!-- Mobile Menu -->
  <div id="mobileMenu" class="hidden md:hidden bg-indigo-800 px-6 py-4">
    <div class="flex flex-col space-y-4">
      <a href="airline-details.php" class="text-white hover:text-indigo-300">Airlines</a>
      <a href="feedback.php" class="text-white hover:text-indigo-300">Feedback</a>
      <a href="#destinations" class="text-white hover:text-indigo-300">Destinations</a>
      <div class="pt-2 border-t border-indigo-700">
        <?php if(isset($_SESSION['user_id'])): ?>
          <a href="profile.php" class="block py-2 text-white hover:text-indigo-300"><i class="fas fa-id-card mr-2"></i>My Profile</a>
          <a href="my-bookings.php" class="block py-2 text-white hover:text-indigo-300"><i class="fas fa-ticket-alt mr-2"></i>My Bookings</a>
          <a href="logout.php" class="block py-2 text-white hover:text-indigo-300"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
        <?php else: ?>
          <a href="login-user.php" class="block py-2 text-white hover:text-indigo-300"><i class="fas fa-user mr-2"></i>User Login</a>
          <a href="login-admin.php" class="block py-2 text-white hover:text-indigo-300"><i class="fas fa-lock mr-2"></i>Admin Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>

<!-- Hero Section -->
<main class="hero-bg text-white pt-32 pb-20 px-6">
  <div class="container mx-auto max-w-6xl">
    <div class="bg-white bg-opacity-90 backdrop-blur-md rounded-xl shadow-2xl p-8 text-gray-800">
      <h1 class="text-3xl md:text-4xl font-bold mb-2 text-center text-indigo-700">
        <i class="fas fa-search mr-2"></i>Find Your Perfect Flight
      </h1>
      <p class="text-center text-gray-600 mb-8">Compare prices across 50+ airlines for the best deals</p>
      
      <form action="search-flights.php" method="POST" class="space-y-6" onsubmit="return validateForm()">
        <div class="flex justify-center space-x-6">
          <label class="inline-flex items-center">
            <input type="radio" name="trip" value="oneway" checked class="h-5 w-5 text-indigo-600" onchange="toggleReturnDate()">
            <span class="ml-2 text-gray-700">One Way</span>
          </label>
          <label class="inline-flex items-center">
            <input type="radio" name="trip" value="roundtrip" class="h-5 w-5 text-indigo-600" onchange="toggleReturnDate()">
            <span class="ml-2 text-gray-700">Round Trip</span>
          </label>
        </div>

        <div class="flex flex-col md:flex-row gap-4 items-end">
          <div class="flex-1">
            <label class="block text-gray-700 mb-1">From</label>
            <select name="from" id="from" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
              <option value="">Select City</option>
              <?php foreach($airports as $airport): ?>
                <option value="<?php echo $airport['airport_code']; ?>"><?php echo $airport['city'] . ' (' . $airport['airport_code'] . ')'; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="button" onclick="swapLocations()" class="swap-btn bg-indigo-600 text-white p-3 rounded-lg transition duration-200">
            <i class="fas fa-exchange-alt"></i>
          </button>

          <div class="flex-1">
            <label class="block text-gray-700 mb-1">To</label>
            <select name="to" id="to" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
              <option value="">Select City</option>
              <?php foreach($airports as $airport): ?>
                <option value="<?php echo $airport['airport_code']; ?>"><?php echo $airport['city'] . ' (' . $airport['airport_code'] . ')'; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <label class="block text-gray-700 mb-1">Departure</label>
            <input type="date" name="departure_date" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" min="<?php echo $today; ?>" required>
          </div>
          <div class="flex-1">
            <label class="block text-gray-700 mb-1">Return</label>
            <input type="date" name="return_date" id="returnDate" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-gray-100" min="<?php echo $today; ?>" disabled>
          </div>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
          <div class="flex-1">
            <label class="block text-gray-700 mb-1">Passengers</label>
            <div class="flex items-center border border-gray-300 rounded-lg p-1">
              <button type="button" onclick="adjustPassengers(-1)" class="px-3 py-1 text-gray-600 hover:bg-gray-100 rounded">
                <i class="fas fa-minus"></i>
              </button>
              <input type="number" name="tickets" value="1" min="1" max="9" class="w-16 text-center border-0 focus:ring-0">
              <button type="button" onclick="adjustPassengers(1)" class="px-3 py-1 text-gray-600 hover:bg-gray-100 rounded">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
          <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-lg font-medium transition duration-200 w-full md:w-auto">
            <i class="fas fa-search mr-2"></i> Search Flights
          </button>
        </div>
      </form>
    </div>
  </div>
</main>

<!-- Destinations Section -->
<section id="destinations" class="py-16 bg-white">
  <div class="container mx-auto px-6">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-bold text-indigo-800 mb-2">Popular Destinations</h2>
      <p class="text-gray-600 max-w-2xl mx-auto">Explore India's most sought-after travel destinations with our exclusive flight deals</p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
      <div class="card-hover bg-white rounded-xl overflow-hidden shadow-md transition duration-300">
        <div class="relative h-48">
          <img src="https://images.unsplash.com/photo-1587474260584-136574528ed5?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" alt="Delhi" class="w-full h-full object-cover">
          <div class="absolute inset-0 bg-gradient-to-t from-black to-transparent opacity-70"></div>
          <div class="absolute bottom-0 left-0 p-4 text-white">
            <h3 class="font-bold text-xl">Delhi</h3>
            <p class="text-indigo-200">From ₹2,499</p>
          </div>
        </div>
        <div class="p-4">
          <p class="text-gray-600 text-sm">India's capital with rich history and vibrant culture</p>
          <div class="mt-3 flex justify-between items-center">
            <span class="text-xs text-gray-500"><i class="fas fa-clock mr-1"></i> 2h 15m avg</span>
            <span class="text-xs text-gray-500"><i class="fas fa-plane mr-1"></i> 120+ daily</span>
          </div>
        </div>
      </div>

      <div class="card-hover bg-white rounded-xl overflow-hidden shadow-md transition duration-300">
        <div class="relative h-48">
          <img src="https://images.unsplash.com/photo-1566438480900-0609be27a4be?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1498&q=80" alt="Mumbai" class="w-full h-full object-cover">
          <div class="absolute inset-0 bg-gradient-to-t from-black to-transparent opacity-70"></div>
          <div class="absolute bottom-0 left-0 p-4 text-white">
            <h3 class="font-bold text-xl">Mumbai</h3>
            <p class="text-indigo-200">From ₹2,199</p>
          </div>
        </div>
        <div class="p-4">
          <p class="text-gray-600 text-sm">The city of dreams with bustling energy</p>
          <div class="mt-3 flex justify-between items-center">
            <span class="text-xs text-gray-500"><i class="fas fa-clock mr-1"></i> 2h 30m avg</span>
            <span class="text-xs text-gray-500"><i class="fas fa-plane mr-1"></i> 150+ daily</span>
          </div>
        </div>
      </div>

      <div class="card-hover bg-white rounded-xl overflow-hidden shadow-md transition duration-300">
        <div class="relative h-48">
          <img src="https://images.unsplash.com/photo-1529253355930-ddbe423a2ac7?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1527&q=80" alt="Goa" class="w-full h-full object-cover">
          <div class="absolute inset-0 bg-gradient-to-t from-black to-transparent opacity-70"></div>
          <div class="absolute bottom-0 left-0 p-4 text-white">
            <h3 class="font-bold text-xl">Goa</h3>
            <p class="text-indigo-200">From ₹1,999</p>
          </div>
        </div>
        <div class="p-4">
          <p class="text-gray-600 text-sm">Sun, sand and endless beaches</p>
          <div class="mt-3 flex justify-between items-center">
            <span class="text-xs text-gray-500"><i class="fas fa-clock mr-1"></i> 1h 45m avg</span>
            <span class="text-xs text-gray-500"><i class="fas fa-plane mr-1"></i> 80+ daily</span>
          </div>
        </div>
      </div>

      <div class="card-hover bg-white rounded-xl overflow-hidden shadow-md transition duration-300">
        <div class="relative h-48">
          <img src="https://assets.onecompiler.app/42xkraykw/42xktaxnq/JAI.png" alt="Jaipur" class="w-full h-full object-cover">
          <div class="absolute inset-0 bg-gradient-to-t from-black to-transparent opacity-70"></div>
          <div class="absolute bottom-0 left-0 p-4 text-white">
            <h3 class="font-bold text-xl">Jaipur</h3>
            <p class="text-indigo-200">From ₹2,799</p>
          </div>
        </div>
        <div class="p-4">
          <p class="text-gray-600 text-sm">The pink city with royal heritage</p>
          <div class="mt-3 flex justify-between items-center">
            <span class="text-xs text-gray-500"><i class="fas fa-clock mr-1"></i> 1h 15m avg</span>
            <span class="text-xs text-gray-500"><i class="fas fa-plane mr-1"></i> 60+ daily</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Trending Routes -->
<section class="py-16 bg-gray-50">
  <div class="container mx-auto px-6">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-bold text-indigo-800 mb-2">Trending Routes</h2>
      <p class="text-gray-600 max-w-2xl mx-auto">Most popular flight routes booked this week</p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition duration-200">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h4 class="font-semibold">Delhi → Mumbai</h4>
            <p class="text-sm text-gray-500">Indira Gandhi → Chhatrapati Shivaji</p>
          </div>
          <i class="fas fa-plane text-indigo-500"></i>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-indigo-600 font-bold">₹2,499</span>
          <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded">6 flights/day</span>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition duration-200">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h4 class="font-semibold">Bangalore → Goa</h4>
            <p class="text-sm text-gray-500">Kempegowda → Dabolim</p>
          </div>
          <i class="fas fa-plane text-indigo-500"></i>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-indigo-600 font-bold">₹1,999</span>
          <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded">4 flights/day</span>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition duration-200">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h4 class="font-semibold">Hyderabad → Chennai</h4>
            <p class="text-sm text-gray-500">Rajiv Gandhi → Meenambakkam</p>
          </div>
          <i class="fas fa-plane text-indigo-500"></i>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-indigo-600 font-bold">₹2,299</span>
          <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded">5 flights/day</span>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition duration-200">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h4 class="font-semibold">Kolkata → Delhi</h4>
            <p class="text-sm text-gray-500">Netaji Subhash → Indira Gandhi</p>
          </div>
          <i class="fas fa-plane text-indigo-500"></i>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-indigo-600 font-bold">₹2,999</span>
          <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded">7 flights/day</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="bg-indigo-900 text-white pt-12 pb-6">
  <div class="container mx-auto px-6">
    <div class="grid md:grid-cols-4 gap-8 mb-8">
      <div>
        <h3 class="text-xl font-bold mb-4 flex items-center">
          <i class="fas fa-plane-departure mr-2"></i> BOOKMYFLIGHT
        </h3>
        <p class="text-indigo-200">Your trusted partner for affordable and convenient air travel across India.</p>
      </div>
      <div>
        <h4 class="font-semibold mb-4">Quick Links</h4>
        <ul class="space-y-2">
          <li><a href="index.php" class="text-indigo-200 hover:text-white">Home</a></li>
          <li><a href="airline-details.php" class="text-indigo-200 hover:text-white">Airlines</a></li>
          <li><a href="feedback.php" class="text-indigo-200 hover:text-white">Feedback</a></li>
          <li><a href="#destinations" class="text-indigo-200 hover:text-white">Destinations</a></li>
        </ul>
      </div>
      <div>
        <h4 class="font-semibold mb-4">Support</h4>
        <ul class="space-y-2">
          <li><a href="help.php" class="text-indigo-200 hover:text-white">Help Center</a></li>
          <li><a href="contact.php" class="text-indigo-200 hover:text-white">Contact Us</a></li>
          <li><a href="privacy.php" class="text-indigo-200 hover:text-white">Privacy Policy</a></li>
          <li><a href="terms.php" class="text-indigo-200 hover:text-white">Terms of Service</a></li>
        </ul>
      </div>
      <div>
        <h4 class="font-semibold mb-4">Connect With Us</h4>
        <div class="flex space-x-4">
          <a href="#" class="text-indigo-200 hover:text-white text-xl"><i class="fab fa-facebook"></i></a>
          <a href="#" class="text-indigo-200 hover:text-white text-xl"><i class="fab fa-twitter"></i></a>
          <a href="#" class="text-indigo-200 hover:text-white text-xl"><i class="fab fa-instagram"></i></a>
          <a href="#" class="text-indigo-200 hover:text-white text-xl"><i class="fab fa-linkedin"></i></a>
        </div>
        <div class="mt-4">
          <p class="text-indigo-200">Subscribe to our newsletter</p>
          <form action="subscribe.php" method="POST" class="flex mt-2">
            <input type="email" name="email" placeholder="Your email" class="px-3 py-2 rounded-l text-gray-800 w-full" required>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-r">
              <i class="fas fa-paper-plane"></i>
            </button>
          </form>
        </div>
      </div>
    </div>
    <div class="border-t border-indigo-800 pt-6 text-center text-indigo-300">
      <p>© 2025 BOOKMYFLIGHT. All rights reserved. | Academic Project</p>
    </div>
  </div>
</footer>

<script>
  // Mobile menu toggle
  function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    menu.classList.toggle('hidden');
  }

  // Login dropdown toggle
  function toggleDropdown() {
    const dropdown = document.getElementById('loginDropdown');
    dropdown.classList.toggle('hidden');
  }

  // Toggle return date field based on trip type
  function toggleReturnDate() {
    const isRoundTrip = document.querySelector('input[name="trip"]:checked').value === 'roundtrip';
    const returnDateInput = document.getElementById('returnDate');
    returnDateInput.disabled = !isRoundTrip;
    if (isRoundTrip) {
      returnDateInput.classList.remove('bg-gray-100');
    } else {
      returnDateInput.classList.add('bg-gray-100');
      returnDateInput.value = '';
    }
  }

  // Validate form before submission
  function validateForm() {
    const from = document.getElementById('from').value;
    const to = document.getElementById('to').value;
    
    if (!from || !to) {
      alert('Please select both departure and arrival cities.');
      return false;
    }
    
    if (from === to) {
      alert('Departure and arrival cities cannot be the same.');
      return false;
    }
    
    return true;
  }

  // Swap locations
  function swapLocations() {
    const from = document.getElementById('from');
    const to = document.getElementById('to');
    const temp = from.value;
    from.value = to.value;
    to.value = temp;
  }

  // Adjust passenger count
  function adjustPassengers(change) {
    const input = document.querySelector('input[name="tickets"]');
    let value = parseInt(input.value) + change;
    if (value < 1) value = 1;
    if (value > 9) value = 9;
    input.value = value;
  }

  // Set minimum date for departure date picker to today
  document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('input[name="departure_date"]').min = today;
    document.getElementById('returnDate').min = today;
  });
</script>
</body>
</html>