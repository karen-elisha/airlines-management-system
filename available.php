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

// Check if departure_date is empty or invalid, set to tomorrow if it is
if (empty($departure_date) || !strtotime($departure_date)) {
    $departure_date = date('Y-m-d', strtotime('+1 day')); // Tomorrow
}

// Format date for display
$display_date = date('d F Y', strtotime($departure_date));

// Store search parameters in session
$_SESSION['flight_search'] = [
    'from' => $origin,
    'to' => $destination,
    'departure_date' => $departure_date,
    'return_date' => $return_date,
    'tickets' => $tickets
];

// Handle AJAX request to create a ticket
if(isset($_POST['book_flight']) && isset($_SESSION['user_id'])) {
    $flight_id = $_POST['flight_id'];
    $airline_name = $_POST['airline_name'];
    $user_id = $_SESSION['user_id'];
    $passengers = $_POST['passengers'];
    $base_price = $_POST['base_price'];
    
    // Insert record into tickets table
    $ticket_insert_query = "
        INSERT INTO tickets (user_id, flight_id, airline_name, number_of_passengers, total_price, booking_date, status)
        VALUES (?, ?, ?, ?, ?, NOW(), 'Pending')
    ";
    
    $total_price = $base_price * $passengers;
    
    // Prepare and execute the insert statement
    $stmt = mysqli_prepare($mysqli, $ticket_insert_query);
    mysqli_stmt_bind_param($stmt, 'iisis', $user_id, $flight_id, $airline_name, $passengers, $total_price);
    
    if(mysqli_stmt_execute($stmt)) {
        // Get the ticket ID for reference in the booking process
        $ticket_id = mysqli_insert_id($mysqli);
        
        // Store ticket ID in session for the next page
        $_SESSION['ticket_id'] = $ticket_id;
        
        // Return success response
        echo json_encode(['success' => true, 'ticket_id' => $ticket_id]);
        exit;
    } else {
        // Return error response
        echo json_encode(['success' => false, 'message' => 'Failed to create ticket: ' . mysqli_error($mysqli)]);
        exit;
    }
}

// Fetch flight data from database - Include ticket count in query
$flights_query = "
    SELECT 
        f.flight_id,
        a.airline_name,
        a.airline_id,
        f.flight_number,
        f.departure_time,
        f.arrival_time,
        f.base_price,
        f.flight_status,
        TIMEDIFF(f.arrival_time, f.departure_time) as duration,
        f.available_seats,
        o.airport_code as origin_code,
        d.airport_code as destination_code
    FROM flights f
    JOIN airlines a ON f.airline_id = a.airline_id
    JOIN airports o ON f.origin_airport = o.airport_id
    JOIN airports d ON f.destination_airport = d.airport_id
    WHERE o.airport_code = ? 
    AND d.airport_code = ?
    AND DATE(f.departure_time) = ?
    AND f.available_seats >= ?
    ORDER BY f.departure_time
";

// Prepare and execute the query using prepared statements
$stmt = mysqli_prepare($mysqli, $flights_query);
mysqli_stmt_bind_param($stmt, 'sssi', $origin, $destination, $departure_date, $tickets);
mysqli_stmt_execute($stmt);
$flights_result = mysqli_stmt_get_result($stmt);

// If database query fails or no results, use demo data
$flights = [];
if ($flights_result && mysqli_num_rows($flights_result) > 0) {
    while ($row = mysqli_fetch_assoc($flights_result)) {
        $flights[] = $row;
    }
} else {
    // Demo data with flight_id included
    $flights = [
        [
            'flight_id' => 1001,
            'airline_name' => 'IndiGo',
            'airline_id' => '6E',
            'flight_number' => '205',
            'departure_time' => '06:20:00',
            'arrival_time' => '08:45:00',
            'duration' => '02:25:00',
            'flight_status' => 'On Time',
            'base_price' => 3999,
            'available_seats' => 62,
            'origin_code' => $origin,
            'destination_code' => $destination
        ],
        [
            'flight_id' => 1002,
            'airline_name' => 'Air India',
            'airline_id' => 'AI',
            'flight_number' => '864',
            'departure_time' => '09:10:00',
            'arrival_time' => '11:30:00',
            'duration' => '02:20:00',
            'flight_status' => 'Delayed',
            'base_price' => 4550,
            'available_seats' => 48,
            'origin_code' => $origin,
            'destination_code' => $destination
        ],
        [
            'flight_id' => 1003,
            'airline_name' => 'Vistara',
            'airline_id' => 'UK',
            'flight_number' => '945',
            'departure_time' => '13:30:00',
            'arrival_time' => '15:50:00',
            'duration' => '02:20:00',
            'flight_status' => 'On Time',
            'base_price' => 5299,
            'available_seats' => 35,
            'origin_code' => $origin,
            'destination_code' => $destination
        ],
        [
            'flight_id' => 1004,
            'airline_name' => 'SpiceJet',
            'airline_id' => 'SG',
            'flight_number' => '721',
            'departure_time' => '18:00:00',
            'arrival_time' => '20:15:00',
            'duration' => '02:15:00',
            'flight_status' => 'On Time',
            'base_price' => 3450,
            'available_seats' => 56,
            'origin_code' => $origin,
            'destination_code' => $destination
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
            <button onclick="toggleDropdown()" class="flex items-center hover:text-indigo-400 transition">
              <i class="fas fa-user-circle mr-2"></i>
              <?php echo $_SESSION['username'] ?? 'My Account'; ?>
              <i class="fas fa-chevron-down ml-1 text-xs"></i>
            </button>
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

  <!-- Error/Success Messages -->
  <?php if(isset($_SESSION['error_message'])): ?>
    <div class="container mx-auto px-4 mt-4">
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
        <p><?php echo $_SESSION['error_message']; ?></p>
      </div>
    </div>
    <?php unset($_SESSION['error_message']); ?>
  <?php endif; ?>

  <?php if(isset($_SESSION['success_message'])): ?>
    <div class="container mx-auto px-4 mt-4">
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded" role="alert">
        <p><?php echo $_SESSION['success_message']; ?></p>
      </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
  <?php endif; ?>

  <!-- Loading overlay -->
  <div id="loading-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-70 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg text-gray-800 text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500 mx-auto mb-4"></div>
      <p class="text-lg font-medium">Processing your booking...</p>
      <p class="text-sm text-gray-500">Please do not close this window.</p>
    </div>
  </div>

  <!-- Main Content -->
  <main class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
      <h2 class="text-2xl font-bold"><?php echo $origin; ?> to <?php echo $destination; ?></h2>
      <div class="text-gray-300"><?php echo $display_date; ?> • <?php echo $tickets; ?> passenger<?php echo $tickets > 1 ? 's' : ''; ?></div>
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
                <span class="text-sm text-gray-500"><?php echo $flight['origin_code']; ?></span>
              </td>
              <td class="p-4">
                <span class="font-medium"><?php echo substr($flight['arrival_time'], 0, 5); ?></span><br>
                <span class="text-sm text-gray-500"><?php echo $flight['destination_code']; ?></span>
              </td>
              <td class="p-4"><?php echo formatDuration($flight['duration']); ?></td>
              <td class="p-4">
                <span class="px-2 py-1 rounded-full text-xs <?php echo getStatusClass($flight['flight_status']); ?>"><?php echo $flight['flight_status']; ?></span>
              </td>
              <td class="p-4 font-semibold">₹<?php echo number_format($flight['base_price']); ?></td>
              <td class="p-4">
                <button onclick="bookFlight(<?php echo $flight['flight_id']; ?>, '<?php echo $flight['airline_name']; ?>', '<?php echo $flight['airline_id']; ?>', '<?php echo $flight['flight_number']; ?>', '<?php echo $departure_date; ?>', <?php echo $tickets; ?>, <?php echo $flight['base_price']; ?>)" 
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
    function bookFlight(flightId, airlineName, airlineId, flightNumber, departureDate, passengers, basePrice) {
      <?php if(isset($_SESSION['user_id'])): ?>
        // Show loading overlay
        document.getElementById('loading-overlay').classList.remove('hidden');
        
        // First store the flight details in the ticket table
        const formData = new FormData();
        formData.append('book_flight', 1);
        formData.append('flight_id', flightId);
        formData.append('airline_name', airlineName);
        formData.append('passengers', passengers);
        formData.append('base_price', basePrice);
        
        // Use AJAX to submit the form data
        fetch('available.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Store flight details to browser's localStorage
            const flightDetails = {
              flight_id: flightId,
              ticket_id: data.ticket_id,
              airline_name: airlineName,
              airline_id: airlineId,
              flight_number: flightNumber,
              origin: '<?php echo $origin; ?>',
              destination: '<?php echo $destination; ?>',
              departure_date: departureDate,
              passengers: passengers,
              base_price: basePrice,
              total_price: basePrice * passengers
            };
            
            // Store data in localStorage
            localStorage.setItem('flightDetails', JSON.stringify(flightDetails));
            
            // Redirect to booking forms page
            window.location.href = 'booking-forms.php';
          } else {
            // Hide loading overlay
            document.getElementById('loading-overlay').classList.add('hidden');
            
            // Show error message
            alert('Failed to book flight: ' + (data.message || 'Unknown error'));
          }
        })
        .catch(error => {
          // Hide loading overlay
          document.getElementById('loading-overlay').classList.add('hidden');
          
          // Show error message
          alert('Error booking flight: ' + error.message);
        });
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