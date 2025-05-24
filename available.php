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

// Fetch flight data from database with complete flight information
$flights_query = "
    SELECT 
        f.flight_id,
        f.flight_number,
        f.airline_id,
        a.airline_name,
        f.origin_airport,
        f.destination_airport,
        o.airport_code as origin_code,
        o.airport_name as origin_name,
        o.city as origin_city,
        d.airport_code as destination_code,
        d.airport_name as destination_name,
        d.city as destination_city,
        f.departure_time,
        f.arrival_time,
        f.duration,
        f.base_price,
        f.total_seats,
        f.available_seats,
        f.flight_status,
        TIMEDIFF(f.arrival_time, f.departure_time) as calculated_duration
    FROM flights f
    JOIN airlines a ON f.airline_id = a.airline_id
    JOIN airports o ON f.origin_airport = o.airport_id
    JOIN airports d ON f.destination_airport = d.airport_id
    WHERE o.airport_code = ? 
    AND d.airport_code = ?
    AND DATE(f.departure_time) = ?
    AND f.available_seats >= ?
    AND f.flight_status != 'Cancelled'
    ORDER BY f.departure_time ASC
";

// Prepare and execute the query using prepared statements
$stmt = mysqli_prepare($mysqli, $flights_query);
if (!$stmt) {
    die("Query preparation failed: " . mysqli_error($mysqli));
}

mysqli_stmt_bind_param($stmt, 'sssi', $origin, $destination, $departure_date, $tickets);

if (!mysqli_stmt_execute($stmt)) {
    die("Query execution failed: " . mysqli_stmt_error($stmt));
}

$flights_result = mysqli_stmt_get_result($stmt);

// Fetch all flights into an array
$flights = [];
if ($flights_result && mysqli_num_rows($flights_result) > 0) {
    while ($row = mysqli_fetch_assoc($flights_result)) {
        // Use provided duration or calculate it if not available
        if (empty($row['duration'])) {
            $row['duration'] = $row['calculated_duration'];
        }
        
        // Ensure we have airline name, use airline_id as fallback
        if (empty($row['airline_name'])) {
            $row['airline_name'] = $row['airline_id'];
        }
        
        $flights[] = $row;
    }
} else {
    // If no flights found in database, show message instead of demo data
    $no_flights_found = true;
}

mysqli_stmt_close($stmt);

// Helper function to format flight duration
function formatDuration($duration) {
    if (empty($duration)) {
        echo 'N/A';
        return;
    }

    echo $duration ;
}


function getStatusClass($status) {
    if (empty($status)) {
        echo "<span style='color: black;'>Unknown</span>";
        return;
    }
    
    $status = strtolower(trim($status));
    echo "<span style='color: black;'>" . ucfirst($status) . "</span>";
}

// Helper function to format time
function formatTime($time) {
    return date('H:i', strtotime($time));
}

// Helper function to format price
function formatPrice($price) {
    return '₹' . number_format((float)$price, 0);
}

// Get airport names for display
$airport_query = "SELECT airport_code, airport_name, city FROM airports WHERE airport_code IN (?, ?)";
$stmt = mysqli_prepare($mysqli, $airport_query);
mysqli_stmt_bind_param($stmt, 'ss', $origin, $destination);
mysqli_stmt_execute($stmt);
$airport_result = mysqli_stmt_get_result($stmt);

$airport_info = [];
while ($row = mysqli_fetch_assoc($airport_result)) {
    $airport_info[$row['airport_code']] = $row;
}
mysqli_stmt_close($stmt);
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
    .flight-row:hover { 
      background-color: #f8fafc; 
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      transition: all 0.2s ease;
    }
    .airline-logo {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 12px;
    }
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
         <a href="user-dashboard.php" class="hover:text-indigo-200">
          <i class="fas fa-home mr-1"></i> Dashboard
        </a>
    
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
    <!-- Search Summary -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8 text-gray-800">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
        <div class="mb-4 md:mb-0">
          <h2 class="text-2xl font-bold mb-2">
            <?php echo isset($airport_info[$origin]) ? $airport_info[$origin]['city'] : $origin; ?> 
            <i class="fas fa-arrow-right text-indigo-600 mx-2"></i> 
            <?php echo isset($airport_info[$destination]) ? $airport_info[$destination]['city'] : $destination; ?>
          </h2>
          <div class="text-gray-600">
            <span class="text-sm">
              <?php echo isset($airport_info[$origin]) ? $airport_info[$origin]['airport_name'] : $origin; ?> 
              → 
              <?php echo isset($airport_info[$destination]) ? $airport_info[$destination]['airport_name'] : $destination; ?>
            </span>
          </div>
        </div>
        <div class="text-right">
          <div class="text-lg font-semibold"><?php echo $display_date; ?></div>
          <div class="text-gray-600"><?php echo $tickets; ?> passenger<?php echo $tickets > 1 ? 's' : ''; ?></div>
        </div>
      </div>
    </div>

    <!-- Flights Results -->
    <?php if(isset($no_flights_found) && $no_flights_found): ?>
      <div class="bg-white rounded-lg shadow-lg p-8 text-center text-gray-800">
        <i class="fas fa-plane-slash text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-bold mb-2">No Flights Found</h3>
        <p class="text-gray-600 mb-4">
          Sorry, we couldn't find any flights from <?php echo $origin; ?> to <?php echo $destination; ?> on <?php echo $display_date; ?>.
        </p>
        <div class="space-y-2 text-sm text-gray-500">
          <p>• Try selecting different dates</p>
          <p>• Check if the route is available</p>
          <p>• Consider nearby airports</p>
        </div>
        <button onclick="history.back()" class="mt-6 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded transition">
          <i class="fas fa-arrow-left mr-2"></i>Modify Search
        </button>
      </div>
    <?php else: ?>
      <!-- Flight Results Header -->
      <div class="bg-white rounded-t-lg shadow-lg p-4 text-gray-800">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-semibold">Available Flights (<?php echo count($flights); ?>)</h3>
          <div class="text-sm text-gray-500">
            Prices shown are per person
          </div>
        </div>
      </div>

      <!-- Flight Table -->
      <div class="bg-white rounded-b-lg shadow-lg overflow-hidden">
        <table class="w-full text-left text-gray-800">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="p-4 font-semibold">Airline & Flight</th>
              <th class="p-4 font-semibold">Departure</th>
              <th class="p-4 font-semibold">Arrival</th>
              <th class="p-4 font-semibold">Duration</th>
              <th class="p-4 font-semibold">Status</th>
              <th class="p-4 font-semibold">Available Seats</th>
              <th class="p-4 font-semibold">Price</th>
              <th class="p-4 font-semibold">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($flights as $flight): ?>
              <tr class="flight-row border-b border-gray-100 hover:bg-gray-50 transition-all duration-200">
                <td class="p-4">
                  <div class="flex items-center space-x-3">
                    <div class="airline-logo">
                      <?php echo substr($flight['airline_id'], 0, 2); ?>
                    </div>
                    <div>
                      <div class="font-semibold"><?php echo htmlspecialchars($flight['airline_name']); ?></div>
                      <div class="text-sm text-gray-500"><?php echo htmlspecialchars($flight['airline_id'] . '-' . $flight['flight_number']); ?></div>
                    </div>
                  </div>
                </td>
                <td class="p-4">
                  <div class="font-semibold text-lg"><?php echo formatTime($flight['departure_time']); ?></div>
                  <div class="text-sm text-gray-500"><?php echo $flight['origin_code']; ?></div>
                </td>
                <td class="p-4">
                  <div class="font-semibold text-lg"><?php echo formatTime($flight['arrival_time']); ?></div>
                  <div class="text-sm text-gray-500"><?php echo $flight['destination_code']; ?></div>
                </td>
                <td class="p-4 font-medium"><?php echo formatDuration($flight['duration']); ?> minutes</td>
                <td class="p-4">
                  <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo getStatusClass($flight['flight_status']); ?>">
                    <?php echo htmlspecialchars($flight['flight_status']); ?>
                  </span>
                </td>
                <td class="p-4">
                  <div class="text-center">
                    <div class="font-semibold"><?php echo $flight['available_seats']; ?></div>
                    <div class="text-xs text-gray-500">of <?php echo $flight['total_seats']; ?></div>
                  </div>
                </td>
                <td class="p-4">
                  <div class="font-bold text-lg text-indigo-600"><?php echo formatPrice($flight['base_price']); ?></div>
                  <?php if($tickets > 1): ?>
                    <div class="text-sm text-gray-500">Total: <?php echo formatPrice($flight['base_price'] * $tickets); ?></div>
                  <?php endif; ?>
                </td>
                <td class="p-4">
                  <?php if($flight['available_seats'] >= $tickets && strtolower($flight['flight_status']) !== 'cancelled'): ?>
                    <button onclick="bookFlight(<?php echo $flight['flight_id']; ?>, '<?php echo addslashes($flight['airline_name']); ?>', '<?php echo $flight['airline_id']; ?>', '<?php echo $flight['flight_number']; ?>', '<?php echo $departure_date; ?>', <?php echo $tickets; ?>, <?php echo $flight['base_price']; ?>)" 
                      class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded font-medium transition transform hover:scale-105">
                      <i class="fas fa-ticket-alt mr-1"></i>Book Now
                    </button>
                  <?php else: ?>
                    <button disabled class="bg-gray-400 text-white px-4 py-2 rounded font-medium cursor-not-allowed">
                      Not Available
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
    
    <!-- Booking Information -->
    <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
      <h3 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-info-circle text-indigo-600 mr-2"></i>Booking Information
      </h3>
      <div class="grid md:grid-cols-3 gap-6">
        <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition">
          <div class="flex items-center space-x-3 mb-2">
            <i class="fas fa-shield-alt text-green-600"></i>
            <h4 class="font-medium text-gray-800">Secure Booking</h4>
          </div>
          <p class="text-sm text-gray-600">Your payment and personal information are protected with industry-standard encryption</p>
        </div>
        <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition">
          <div class="flex items-center space-x-3 mb-2">
            <i class="fas fa-clock text-blue-600"></i>
            <h4 class="font-medium text-gray-800">Instant Confirmation</h4>
          </div>
          <p class="text-sm text-gray-600">Get your e-ticket immediately after successful payment</p>
        </div>
        <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition">
          <div class="flex items-center space-x-3 mb-2">
            <i class="fas fa-headset text-purple-600"></i>
            <h4 class="font-medium text-gray-800">24/7 Support</h4>
          </div>
          <p class="text-sm text-gray-600">Get help anytime via chat, email or phone support</p>
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

    // Auto-refresh flight status every 5 minutes
    setInterval(function() {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('auto_refresh') !== 'false') {
        // Only refresh if there are flights displayed
        const flightRows = document.querySelectorAll('.flight-row');
        if (flightRows.length > 0) {
          console.log('Auto-refreshing flight status...');
          window.location.reload();
        }
      }
    }, 300000); // 5 minutes
  </script>
</body>
</html>