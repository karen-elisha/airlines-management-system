<?php
// Start session for user management
session_start();

// Database connection
require_once 'db_connect.php';

// Get flight parameters from URL (coming from search-flights.php)
$origin = isset($_GET['from']) ? $_GET['from'] : '';
$destination = isset($_GET['to']) ? $_GET['to'] : '';
$departure_date = isset($_GET['departure_date']) ? $_GET['departure_date'] : '';
$return_date = isset($_GET['return_date']) ? $_GET['return_date'] : '';
$tickets = isset($_GET['tickets']) ? (int)$_GET['tickets'] : 1;

// If no parameters are provided and we have search data in session, use those
if ((empty($origin) || empty($destination)) && isset($_SESSION['flight_search'])) {
    $origin = $_SESSION['flight_search']['from'];
    $destination = $_SESSION['flight_search']['to'];
    $departure_date = $_SESSION['flight_search']['departure_date'];
    $return_date = $_SESSION['flight_search']['return_date'];
    $tickets = $_SESSION['flight_search']['tickets'];
}

// If still no parameters are provided, use default values for demo
if (empty($origin) && empty($destination)) {
    $origin = 'DEL'; // Delhi
    $destination = 'BOM'; // Mumbai
    $departure_date = date('Y-m-d', strtotime('+1 day')); // Tomorrow
    $tickets = 1;
}

// Format date for display
$display_date = date('d F Y', strtotime($departure_date));

// Fetch flight data from database - Include ticket count in query
$flights_query = "
    SELECT 
        a.airline_name,
        a.airline_id,
        f.flight_number,
        f.departure_time,
        f.arrival_time,
        f.base_price,
        f.flight_status,
        TIMEDIFF(f.arrival_time, f.departure_time) as duration,
        f.available_seats
    FROM flights f
    JOIN airlines a ON f.airline_id = a.airline_id
    JOIN airports o ON f.origin_airport = o.airport_id
    JOIN airports d ON f.destination_airport = d.airport_id
    WHERE o.airport_code = '$origin' 
    AND d.airport_code = '$destination'
    AND DATE(f.departure_time) = '$departure_date'
    AND f.available_seats >= $tickets
    ORDER BY f.departure_time
";

$flights_result = mysqli_query($mysqli, $flights_query);


// If database query fails or no results, use demo data
$flights = [];
if ($flights_result && mysqli_num_rows($flights_result) > 0) {
    while ($row = mysqli_fetch_assoc($flights_result)) {
        $flights[] = $row;
    }
} else {
    // Demo data (same as in original JavaScript)
    $flights = [
        [
            'airline_name' => 'IndiGo',
            'airline_id' => '6E',
            'flight_number' => '205',
            'departure_time' => '06:20:00',
            'arrival_time' => '08:45:00',
            'duration' => '02:25:00',
            'status' => 'On Time',
            'price' => 3999
        ],
        [
            'airline_name' => 'Air India',
            'airline_id' => 'AI',
            'flight_number' => '864',
            'departure_time' => '09:10:00',
            'arrival_time' => '11:30:00',
            'duration' => '02:20:00',
            'status' => 'Delayed',
            'price' => 4550
        ],
        [
            'airline_name' => 'Vistara',
            'airline_id' => 'UK',
            'flight_number' => '945',
            'departure_time' => '13:30:00',
            'arrival_time' => '15:50:00',
            'duration' => '02:20:00',
            'status' => 'On Time',
            'price' => 5299
        ],
        [
            'airline_name' => 'SpiceJet',
            'airline_id' => 'SG',
            'flight_number' => '721',
            'departure_time' => '18:00:00',
            'arrival_time' => '20:15:00',
            'duration' => '02:15:00',
            'status' => 'On Time',
            'price' => 3450
        ]
    ];
}

// Helper function to format flight duration
function formatDuration($duration) {
    $parts = explode(':', $duration);
    return $parts[0] . 'h ' . $parts[1] . 'm';
}

// Helper function to get status class
function getStatusClass($status) {
    switch($status) {
        case 'On Time': return 'bg-green-100 text-green-800';
        case 'Delayed': return 'bg-yellow-100 text-yellow-800';
        case 'Cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Available Flights | BookMyFlight</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .flight-row:hover { background-color: #f8fafc; }
  </style>
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
        <a href="feedback.php" class="hover:text-indigo-400 transition">Feedback</a>
        <?php if(isset($_SESSION['user_id'])): ?>
          <div class="relative">
            
            <div id="loginDropdown" class="absolute right-0 mt-2 w-48 bg-white text-gray-800 rounded-md shadow-xl hidden z-50">
              <a href="profile.php" class="block px-4 py-2 hover:bg-indigo-50 text-indigo-600"><i class="fas fa-id-card mr-2"></i>My Profile</a>
              <a href="my-bookings.php" class="block px-4 py-2 hover:bg-indigo-50 text-indigo-600"><i class="fas fa-ticket-alt mr-2"></i>My Bookings</a>
              <a href="logout.php" class="block px-4 py-2 hover:bg-indigo-50 text-indigo-600"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            </div>
          </div>
        <?php else: ?>
          <button onclick="window.location.href='login.php'" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded transition">
            <i class="fas fa-user mr-2"></i> Login
          </button>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <!-- Main Content -->
  <main class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
      <h2 class="text-2xl font-bold"><?php echo $origin; ?> to <?php echo $destination; ?></h2>
      <div class="text-gray-300"><?php echo $display_date; ?></div>
    </div>

    <!-- Flight Table -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
      <table class="w-full text-left text-gray-800">
        <thead class="bg-gray-200">
          <tr>
            <th class="p-4">Airline</th>
            <th class="p-4">Departure</th>
            <th class="p-4">Arrival</th>
            <th class="p-4">Duration</th>
            <th class="p-4">Status</th>
            <th class="p-4">Price</th>
            <th class="p-4">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($flights as $flight): ?>
            <tr class="flight-row border-b border-gray-200">
              <td class="p-4 font-medium">
                <?php echo $flight['airline_name']; ?><br>
                <span class="text-sm text-gray-500"><?php echo $flight['airline_id'] . '-' . $flight['flight_number']; ?></span>
              </td>
              <td class="p-4">
                <span class="font-medium"><?php echo substr($flight['departure_time'], 0, 5); ?></span><br>
                <span class="text-sm text-gray-500"><?php echo $origin; ?></span>
              </td>
              <td class="p-4">
                <span class="font-medium"><?php echo substr($flight['arrival_time'], 0, 5); ?></span><br>
                <span class="text-sm text-gray-500"><?php echo $destination; ?></span>
              </td>
              <td class="p-4"><?php echo formatDuration($flight['duration']); ?></td>
              <td class="p-4">
                <span class="px-2 py-1 rounded-full text-xs <?php echo getStatusClass($flight['status']); ?>"><?php echo $flight['status']; ?></span>
              </td>
              <td class="p-4 font-semibold">₹<?php echo number_format($flight['price']); ?></td>
              <td class="p-4">
                <button onclick="bookFlight('<?php echo $flight['airline_id'] . '-' . $flight['flight_number']; ?>')" 
                  class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded text-sm transition">
                  Book Now
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
          
          <?php if(count($flights) == 0): ?>
            <tr>
              <td colspan="7" class="p-8 text-center text-gray-500">
                No flights found for this route and date. Please try different dates or routes.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    
    <!-- Booking Options -->
    <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
      <h3 class="text-xl font-bold text-gray-800 mb-4">Booking Options</h3>
      <div class="grid md:grid-cols-3 gap-6">
        <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition">
          <div class="flex items-center space-x-3 mb-2">
            <i class="fas fa-ticket-alt text-indigo-600"></i>
            <h4 class="font-medium text-gray-800">Flexible Booking</h4>
          </div>
          <p class="text-sm text-gray-600">Free date changes available on most flights</p>
        </div>
        <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition">
          <div class="flex items-center space-x-3 mb-2">
            <i class="fas fa-credit-card text-indigo-600"></i>
            <h4 class="font-medium text-gray-800">No Hidden Fees</h4>
          </div>
          <p class="text-sm text-gray-600">Know exactly what you're paying for</p>
        </div>
        <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition">
          <div class="flex items-center space-x-3 mb-2">
            <i class="fas fa-headset text-indigo-600"></i>
            <h4 class="font-medium text-gray-800">24/7 Support</h4>
          </div>
          <p class="text-sm text-gray-600">Get help anytime via chat, email or phone</p>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-900 text-center p-6 text-gray-300 mt-12">
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

  <script>
    // Toggle login dropdown
    function toggleDropdown() {
      const dropdown = document.getElementById('loginDropdown');
      dropdown.classList.toggle('hidden');
    }

    // Book flight action
    function bookFlight(flightNumber) {
      <?php if(isset($_SESSION['user_id'])): ?>
        if (confirm(`Confirm booking for flight ${flightNumber}?`)) {
          window.location.href = `payment.php?flight=${encodeURIComponent(flightNumber)}&from=<?php echo $origin; ?>&to=<?php echo $destination; ?>&date=<?php echo $departure_date; ?>`;
        }
      <?php else: ?>
        alert('Please login to book a flight');
        window.location.href = `login.php?redirect=available.php?from=<?php echo $origin; ?>&to=<?php echo $destination; ?>&departure_date=<?php echo $departure_date; ?>`;
      <?php endif; ?>
    }

    // Close dropdown when clicking outside
    window.addEventListener('click', function(event) {
      const dropdown = document.getElementById('loginDropdown');
      if (dropdown && !dropdown.contains(event.target) && !event.target.closest('button[onclick="toggleDropdown()"]')) {
        dropdown.classList.add('hidden');
      }
    });
  </script>
</body>
</html>