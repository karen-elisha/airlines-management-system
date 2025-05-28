<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "airlines";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];

// Fetch user's bookings with flight details
$query = "SELECT 
    b.booking_id,
    b.booking_reference,
    b.travel_date,
    b.num_passengers,
    b.total_amount,
    b.booking_status,
    b.payment_status,
    f.flight_number,
    f.airline_id,
    f.origin_airport,
    f.destination_airport,
    f.departure_time,
    f.arrival_time,
    t.airline_name,
    t.ticket_id,
    t.status as ticket_status
FROM bookings b
LEFT JOIN flights f ON b.flight_id = f.flight_id
LEFT JOIN tickets t ON b.user_id = t.user_id AND b.flight_id = t.flight_id
WHERE b.user_id = :user_id
ORDER BY b.travel_date DESC";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get airline logo
function getAirlineLogo($airline_name) {
    $logos = [
        'IndiGo' => 'https://logo.clearbit.com/goindigo.in',
        'Air India' => 'https://logo.clearbit.com/airindia.in',
        'Vistara' => 'https://logo.clearbit.com/airvistara.com',
        'SpiceJet' => 'https://logo.clearbit.com/spicejet.com',
        'GoAir' => 'https://logo.clearbit.com/goair.in',
        'Go First' => 'https://logo.clearbit.com/goair.in'
    ];
    return isset($logos[$airline_name]) ? $logos[$airline_name] : '/api/placeholder/40/40';
}

// Function to format airport codes
function formatRoute($origin, $destination) {
    return strtoupper($origin) . ' → ' . strtoupper($destination);
}

// Function to get full airport names (you can expand this)
function getAirportName($code) {
    $airports = [
        'DEL' => 'Delhi',
        'BLR' => 'Bengaluru',
        'BOM' => 'Mumbai',
        'CCU' => 'Kolkata',
        'HYD' => 'Hyderabad',
        'MAA' => 'Chennai',
        'GOI' => 'Goa',
        'AMD' => 'Ahmedabad'
    ];
    return isset($airports[strtoupper($code)]) ? $airports[strtoupper($code)] : $code;
}

// Function to get status class
function getStatusClass($status) {
    switch(strtolower($status)) {
        case 'confirmed':
        case 'booked':
            return 'status-confirmed';
        case 'pending':
            return 'status-pending';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return 'status-pending';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Bookings | BookMyFlight</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .flight-row:hover {
      background-color: #f3f4f6;
      transform: translateY(-2px);
      transition: all 0.2s ease;
    }
    .status-confirmed {
      background-color: #dcfce7;
      color: #166534;
    }
    .status-pending {
      background-color: #fef9c3;
      color: #854d0e;
    }
    .status-cancelled {
      background-color: #fee2e2;
      color: #991b1b;
    }
    .cancel-btn {
      background-color: #dc2626;
      color: white;
      padding: 4px 12px;
      border-radius: 4px;
      font-size: 12px;
      text-decoration: none;
      transition: background-color 0.2s;
      display: inline-block;
      text-align: center;
    }
    .cancel-btn:hover {
      background-color: #b91c1c;
    }
    .cancel-btn.disabled {
      background-color: #9ca3af;
      cursor: not-allowed;
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen flex flex-col">

  <!-- Navigation Bar -->
  <header class="bg-indigo-900 text-white shadow-lg">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
      <div class="flex items-center space-x-3">
        <i class="fas fa-plane-departure text-2xl"></i>
        <h1 class="text-xl font-bold">BookMyFlight</h1>
      </div>
      <nav class="hidden md:flex space-x-6">
        <a href="user-dashboard.php" class="hover:text-indigo-200"><i class="fas fa-home mr-1"></i> Dashboard</a>
        <a href="mytickets.php" class="text-indigo-300 font-medium"><i class="fas fa-ticket-alt mr-1"></i> My Tickets</a>
      </nav>
     
    </div>
  </header>

  <!-- Main Content -->
  <main class="flex-1 container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="text-center mb-10">
      <h1 class="text-3xl md:text-4xl font-bold text-gray-800">Your Flight Bookings</h1>
      <p class="mt-3 text-gray-600 max-w-2xl mx-auto">
        View and manage your upcoming flights. You can request cancellation for any booking.
      </p>
    </div>

    <!-- Bookings Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <!-- Table Header -->
      <div class="grid grid-cols-12 bg-gray-800 text-white p-4 font-medium">
        <div class="col-span-3 md:col-span-2">Airline</div>
        <div class="col-span-4 md:col-span-3">Route</div>
        <div class="col-span-3 md:col-span-2">Date & Time</div>
        <div class="hidden md:block md:col-span-1">Fare</div>
        <div class="hidden md:block md:col-span-2">Status</div>
        <div class="col-span-2 md:col-span-2">Action</div>
      </div>

      <!-- Flight Items -->
      <div id="flightList">
        <?php if (empty($bookings)): ?>
          <!-- Empty State -->
          <div class="p-12 text-center">
            <i class="fas fa-plane-slash text-4xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-medium text-gray-700">No Bookings Found</h3>
            <p class="text-gray-500 mt-2">You haven't booked any flights yet. Start your journey with us!</p>
            <a href="user-dashboard.php" class="mt-4 inline-block bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
              Book a Flight
            </a>
          </div>
        <?php else: ?>
          <?php foreach ($bookings as $booking): ?>
            <?php
            $travel_date = new DateTime($booking['travel_date']);
            $departure_time = new DateTime($booking['departure_time']);
            ?>
            <div class="grid grid-cols-12 items-center p-4 border-b flight-row">
              <!-- Airline -->
              <div class="col-span-3 md:col-span-2 flex items-center">
                <img src="<?php echo getAirlineLogo($booking['airline_name']); ?>" alt="<?php echo htmlspecialchars($booking['airline_name']); ?>" class="h-8 mr-3">
                <div>
                  <span class="hidden md:inline font-medium"><?php echo htmlspecialchars($booking['airline_name']); ?></span>
                  <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                </div>
              </div>
              
              <!-- Route -->
              <div class="col-span-4 md:col-span-3">
                <div class="font-medium"><?php echo formatRoute($booking['origin_airport'], $booking['destination_airport']); ?></div>
                <div class="text-sm text-gray-500">
                  <?php echo getAirportName($booking['origin_airport']) . ' to ' . getAirportName($booking['destination_airport']); ?>
                </div>
                <div class="text-xs text-gray-400">
                  Passengers: <?php echo $booking['num_passengers']; ?>
                </div>
              </div>
              
              <!-- Date & Time -->
              <div class="col-span-3 md:col-span-2">
                <div class="font-medium"><?php echo $travel_date->format('d M Y'); ?></div>
                <div class="text-sm text-gray-500"><?php echo $departure_time->format('h:i A'); ?></div>
                <div class="text-xs text-gray-400">
                  Ref: <?php echo htmlspecialchars($booking['booking_reference']); ?>
                </div>
              </div>
              
              <!-- Fare -->
              <div class="hidden md:block md:col-span-1 font-semibold">
                ₹<?php echo number_format($booking['total_amount']); ?>
              </div>
              
              <!-- Status -->
              <div class="hidden md:block md:col-span-2">
                <span class="<?php echo getStatusClass($booking['booking_status']); ?> px-3 py-1 rounded-full text-xs font-medium">
                  <?php echo ucfirst($booking['booking_status']); ?>
                </span>
                <?php if ($booking['payment_status']): ?>
                  <div class="text-xs text-gray-500 mt-1">
                    Payment: <?php echo ucfirst($booking['payment_status']); ?>
                  </div>
                <?php endif; ?>
              </div>
              
              <!-- Action -->
              <div class="col-span-2 md:col-span-2">
                <a href="ticket-cancellation.php?booking_id=<?php echo $booking['booking_id']; ?>&ticket_id=<?php echo $booking['ticket_id']; ?>" 
                   class="cancel-btn <?php echo (strtolower($booking['booking_status']) === 'cancelled') ? 'disabled' : ''; ?>">
                  <i class="fas fa-times mr-1"></i>
                  <?php echo (strtolower($booking['booking_status']) === 'cancelled') ? 'Cancelled' : 'Cancel'; ?>
                </a>
                <div class="mt-1">
                  <a href="mytickets.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                     class="text-xs text-indigo-600 hover:text-indigo-800">
                    <i class="fas fa-eye mr-1"></i>View Details
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Booking Summary -->
    <?php if (!empty($bookings)): ?>
    <div class="mt-8 bg-white rounded-xl shadow-md p-6">
      <h3 class="text-lg font-bold mb-4">Booking Summary</h3>
      <div class="grid md:grid-cols-4 gap-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-indigo-600"><?php echo count($bookings); ?></div>
          <div class="text-sm text-gray-600">Total Bookings</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600">
            <?php echo count(array_filter($bookings, function($b) { return strtolower($b['booking_status']) === 'confirmed'; })); ?>
          </div>
          <div class="text-sm text-gray-600">Confirmed</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-yellow-600">
            <?php echo count(array_filter($bookings, function($b) { return strtolower($b['booking_status']) === 'pending'; })); ?>
          </div>
          <div class="text-sm text-gray-600">Pending</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-red-600">
            <?php echo count(array_filter($bookings, function($b) { return strtolower($b['booking_status']) === 'cancelled'; })); ?>
          </div>
          <div class="text-sm text-gray-600">Cancelled</div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-900 text-gray-400 py-8">
    <div class="container mx-auto px-4">
      <div class="flex flex-col md:flex-row justify-between items-center">
        <div class="mb-4 md:mb-0">
          <div class="flex items-center space-x-2">
            <i class="fas fa-plane-departure text-2xl text-indigo-400"></i>
            <span class="text-xl font-bold text-white">BookMyFlight</span>
          </div>
          <p class="mt-2 text-sm">Travel with comfort, book with confidence.</p>
        </div>
        <div class="flex space-x-6">
          <a href="#" class="hover:text-white"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="hover:text-white"><i class="fab fa-twitter"></i></a>
          <a href="#" class="hover:text-white"><i class="fab fa-instagram"></i></a>
          <a href="#" class="hover:text-white"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>
      <div class="border-t border-gray-800 mt-6 pt-6 text-sm text-center">
        <p>© 2025 BookMyFlight. All rights reserved. | <a href="#" class="hover:text-white">Privacy Policy</a> | <a href="#" class="hover:text-white">Terms of Service</a></p>
      </div>
    </div>
  </footer>

</body>
</html>