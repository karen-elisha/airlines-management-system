<?php
// Start the session
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

// Verify MySQLi connection
if (!isset($mysqli) || $mysqli->connect_error) {
    die("Database connection failed: " . 
       (isset($mysqli) ? $mysqli->connect_error : "No connection object"));
}

$error = '';
$success = '';

try {
    // Get user data
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT full_name, email, phone, member_since, loyalty_points, loyalty_tier FROM users WHERE user_id = ?";
    
    if (!$stmt = $mysqli->prepare($user_query)) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    if (!$stmt->bind_param("i", $user_id)) {
        throw new Exception("Bind param failed: " . $stmt->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Set default values if user data is not found
    if (!$user) {
        $user = [
            'full_name' => 'Guest User',
            'email' => 'guest@example.com',
            'phone' => 'N/A',
            'member_since' => date('Y-m-d'),
            'loyalty_points' => 0,
            'loyalty_tier' => 'Bronze'
        ];
    }

    // Get user's initial for avatar
    $initial = substr($user['full_name'], 0, 1);

    // Get upcoming bookings
    $upcoming_query = "SELECT b.booking_id, b.user_id, b.booking_date, b.total_amount, b.booking_status,
                      f.flight_number, f.airline_id, f.departure_time, f.arrival_time,
                      origin.city AS origin_city, dest.city AS destination_city,
                      a.airline_name
                      FROM bookings b
                      JOIN flights f ON b.flight_id = f.flight_id
                      JOIN airports origin ON f.origin_airport = origin.airport_id
                      JOIN airports dest ON f.destination_airport = dest.airport_id
                      JOIN airlines a ON f.airline_id = a.airline_id
                      WHERE b.user_id = ? AND f.departure_time > NOW()
                      ORDER BY f.departure_time ASC";
    
    if (!$stmt = $mysqli->prepare($upcoming_query)) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $upcoming_result = $stmt->get_result();
    $upcoming_bookings = [];
    while ($row = $upcoming_result->fetch_assoc()) {
        $upcoming_bookings[] = $row;
    }

    // Get booking history
    $history_query = "SELECT b.booking_id, b.booking_date, b.total_amount, b.booking_status, 
                     f.flight_number, f.departure_time, f.arrival_time, 
                     origin.city AS origin_city, dest.city AS destination_city,
                     a.airline_name
                     FROM bookings b
                     JOIN flights f ON b.flight_id = f.flight_id
                     JOIN airports origin ON f.origin_airport = origin.airport_id
                     JOIN airports dest ON f.destination_airport = dest.airport_id
                     JOIN airlines a ON f.airline_id = a.airline_id
                     WHERE b.user_id = ? AND f.departure_time < NOW()
                     ORDER BY f.departure_time DESC
                     LIMIT 5";

    if (!$stmt = $mysqli->prepare($history_query)) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $history_result = $stmt->get_result();
    $booking_history = [];
    while ($row = $history_result->fetch_assoc()) {
        $booking_history[] = $row;
    }

    // Get live flight schedule
    $live_flights_query = "SELECT f.flight_number, f.departure_time, f.arrival_time, 
                          origin.airport_id AS origin_code, dest.airport_id AS dest_code,
                          origin.city AS origin_city, dest.city AS destination_city,
                          f.flight_status
                          FROM flights f
                          JOIN airports origin ON f.origin_airport = origin.airport_id
                          JOIN airports dest ON f.destination_airport = dest.airport_id
                          WHERE f.departure_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 12 HOUR)
                          ORDER BY f.departure_time ASC
                          LIMIT 5";
                          
    if (!$live_flights_result = $mysqli->query($live_flights_query)) {
        throw new Exception("Query failed: " . $mysqli->error);
    }
    
    $live_flights = [];
    while ($row = $live_flights_result->fetch_assoc()) {
        $live_flights[] = $row;
    }

    // Get notifications
    $notifications_query = "SELECT notification_id, message, notification_type, is_read, created_at
                           FROM notifications
                           WHERE user_id = ?
                           ORDER BY created_at DESC
                           LIMIT 5";
                           
    if (!$stmt = $mysqli->prepare($notifications_query)) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $notifications_result = $stmt->get_result();
    $notifications = [];
    $unread_count = 0;
    while ($row = $notifications_result->fetch_assoc()) {
        $notifications[] = $row;
        if (!$row['is_read']) {
            $unread_count++;
        }
    }

    // Get airports for search form
    $airports_query = "SELECT airport_id, airport_name, city FROM airports ORDER BY city";
    if (!$airports_result = $mysqli->query($airports_query)) {
        throw new Exception("Query failed: " . $mysqli->error);
    }
    
    $airports = [];
    while ($row = $airports_result->fetch_assoc()) {
        $airports[] = $row;
    }

    // Format time for display
    function formatTime($datetime) {
        return date('h:i A', strtotime($datetime));
    }

    // Format date for display
    function formatDate($datetime) {
        return date('M d, Y', strtotime($datetime));
    }

    // Calculate progress to next tier
    $points_to_next_tier = 0;
    $progress_percentage = 0;

    if ($user['loyalty_tier'] == 'Bronze') {
        $points_to_next_tier = 1000 - $user['loyalty_points'];
        $progress_percentage = ($user['loyalty_points'] / 1000) * 100;
    } else if ($user['loyalty_tier'] == 'Silver') {
        $points_to_next_tier = 3000 - $user['loyalty_points'];
        $progress_percentage = ($user['loyalty_points'] / 3000) * 100;
    } else if ($user['loyalty_tier'] == 'Gold') {
        $points_to_next_tier = 5000 - $user['loyalty_points'];
        $progress_percentage = ($user['loyalty_points'] / 5000) * 100;
    }

    // Cap percentage at 100
    $progress_percentage = min($progress_percentage, 100);

    // Calculate next tier name
    $next_tier = 'Platinum';
    if ($user['loyalty_tier'] == 'Bronze') {
        $next_tier = 'Silver';
    } else if ($user['loyalty_tier'] == 'Silver') {
        $next_tier = 'Gold';
    }

} catch (Exception $e) {
    $error = "An error occurred: " . $e->getMessage();
    error_log($e->getMessage());
}

// Close connection
if (isset($mysqli)) {
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Airlines Dashboard | BOOKMYFLIGHT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    .sidebar-link.active {
      @apply text-indigo-400 bg-gray-700;
    }
    .status-on-time { @apply text-green-400; }
    .status-delayed { @apply text-yellow-300; }
    .status-cancelled { @apply text-red-400; }
    .card-hover { @apply transition-transform duration-300 hover:scale-[1.02]; }
    
    /* Added styles for dropdowns */
    .dropdown-menu {
      display: none;
      position: absolute;
      right: 0;
      top: 100%;
      margin-top: 0.5rem;
      z-index: 50;
    }
    .dropdown-menu.show {
      display: block;
    }
    .hidden {
      display: none;
    }
  </style>
</head>
<body class="bg-gray-900 text-white font-sans antialiased">

<div class="min-h-screen flex">
  <!-- Sidebar -->
  <aside class="w-64 bg-gray-800 p-4 hidden md:block border-r border-gray-700">
    <div class="text-2xl font-bold text-indigo-400 mb-4 flex items-center">
      <i class="fa-solid fa-plane-departure mr-3"></i>BOOKMYFLIGHT
    </div>
    <nav class="space-y-2">
      <a href="#" class="sidebar-link flex items-center p-3 rounded-lg hover:bg-gray-700 active" onclick="showSection('dashboard')">
        <i class="fas fa-chart-line mr-3 w-5 text-center"></i> Dashboard
      </a>
      <a href="#" class="sidebar-link flex items-center p-3 rounded-lg hover:bg-gray-700" onclick="showSection('bookings')">
        <i class="fas fa-suitcase-rolling mr-3 w-5 text-center"></i> Bookings
      </a>
      <a href="#" class="sidebar-link flex items-center p-3 rounded-lg hover:bg-gray-700" onclick="showSection('tickets')">
        <i class="fas fa-ticket-alt mr-3 w-5 text-center"></i> Tickets
      </a>
      <a href="#" class="sidebar-link flex items-center p-3 rounded-lg hover:bg-gray-700" onclick="showSection('profile')">
  <i class="fas fa-user-circle mr-3 w-5 text-center"></i> Profile
</a>
      <a href="myflight.html" class="flex items-center p-3 rounded-lg hover:bg-gray-700">
        <i class="fas fa-plane mr-3 w-5 text-center"></i> My Flights
      </a>
      <a href="logout.php" class="flex items-center p-3 rounded-lg text-red-400 hover:bg-gray-700 mt-8">
        <i class="fas fa-sign-out-alt mr-3 w-5 text-center"></i> Logout
      </a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-6 overflow-y-auto bg-gray-950">
    <!-- Header -->
    <header class="flex justify-between items-center mb-8">
      <h1 class="text-2xl font-bold text-indigo-300" id="pageTitle">Dashboard</h1>
      <div class="flex items-center space-x-4">
        <!-- Notification Icon and Dropdown -->
        <div class="relative">
          <i class="fas fa-bell text-gray-400 hover:text-white cursor-pointer" id="notificationIcon"></i>
          <?php if ($unread_count > 0): ?>
            <span class="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-red-500"></span>
          <?php endif; ?>
          
          <!-- Notification Dropdown -->
          <div id="notificationDropdown" class="dropdown-menu bg-gray-800 rounded-lg shadow-lg w-72">
            <div class="p-3 border-b border-gray-700">
              <div class="flex justify-between items-center">
                <h3 class="font-medium">Notifications</h3>
                <?php if ($unread_count > 0): ?>
                  <span class="text-xs bg-red-500 text-white px-2 py-1 rounded-full"><?php echo $unread_count; ?> new</span>
                <?php endif; ?>
              </div>
            </div>
            <div class="divide-y divide-gray-700 max-h-80 overflow-y-auto">
              <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notification): ?>
                  <div class="p-3 hover:bg-gray-700">
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($notification['notification_type']); ?></p>
                    <p class="text-xs text-gray-400"><?php echo htmlspecialchars($notification['message']); ?></p>
                    <p class="text-xs text-gray-500 mt-1">
                      <?php 
                        $time_diff = time() - strtotime($notification['created_at']);
                        if ($time_diff < 60) {
                          echo "Just now";
                        } elseif ($time_diff < 3600) {
                          echo floor($time_diff / 60) . " min ago";
                        } elseif ($time_diff < 86400) {
                          echo floor($time_diff / 3600) . " hours ago";
                        } else {
                          echo floor($time_diff / 86400) . " days ago";
                        }
                      ?>
                    </p>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="p-3">
                  <p class="text-sm text-center text-gray-400">No notifications</p>
                </div>
              <?php endif; ?>
            </div>
            <div class="p-2 border-t border-gray-700 text-center">
              <a href="notifications.php" class="text-xs text-indigo-400 hover:text-indigo-300">View all notifications</a>
            </div>
          </div>
        </div>
        
        <!-- Profile Icon and Dropdown -->
        <div class="relative">
          <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center cursor-pointer" id="profileIcon">
            <span id="userInitial"><?php echo htmlspecialchars($initial); ?></span>
          </div>
          
          <!-- Profile Dropdown -->
          <div id="profileDropdown" class="dropdown-menu bg-gray-800 rounded-lg shadow-lg w-64">
            <div class="p-3 border-b border-gray-700">
              <h3 class="font-medium"><?php echo htmlspecialchars($user['full_name']); ?></h3>
              <p class="text-xs text-gray-400"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <div class="py-1">
              <a href="#" onclick="showSection('profile'); closeAllDropdowns();" class="block px-4 py-2 text-sm hover:bg-gray-700">
                <i class="fas fa-user-circle mr-2"></i> My Profile
              </a>
              <a href="settings.php" class="block px-4 py-2 text-sm hover:bg-gray-700">
                <i class="fas fa-cog mr-2"></i> Settings
              </a>
              <a href="support.php" class="block px-4 py-2 text-sm hover:bg-gray-700">
                <i class="fas fa-question-circle mr-2"></i> Help & Support
              </a>
            </div>
            <div class="border-t border-gray-700 py-1">
              <a href="logout.php" class="block px-4 py-2 text-sm text-red-400 hover:bg-gray-700">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Dashboard Section -->
    <section id="dashboard" class="section-content">
    
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <?php
        // Get stats from database (in a real app, these would be retrieved)
        $total_flights = 178; // Example value
        $active_bookings = count($upcoming_bookings);
        $on_time_rate = 91; // Example value
        ?>
        <div class="bg-gray-800 p-6 rounded-xl shadow-lg card-hover">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-400">Total Flights</p>
              <p class="text-3xl font-bold text-green-400"><?php echo $total_flights; ?></p>
            </div>
            <i class="fas fa-plane text-green-400 text-xl"></i>
          </div>
          <p class="text-sm text-gray-400 mt-2">+12% from yesterday</p>
        </div>
        
        <div class="bg-gray-800 p-6 rounded-xl shadow-lg card-hover">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-400">Active Bookings</p>
              <p class="text-3xl font-bold text-yellow-300"><?php echo $active_bookings; ?></p>
            </div>
            <i class="fas fa-suitcase text-yellow-300 text-xl"></i>
          </div>
          <p class="text-sm text-gray-400 mt-2"><?php echo min($active_bookings, 5); ?> upcoming</p>
        </div>
        
        <div class="bg-gray-800 p-6 rounded-xl shadow-lg card-hover">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-400">On-Time Rate</p>
              <p class="text-3xl font-bold text-blue-400"><?php echo $on_time_rate; ?>%</p>
            </div>
            <i class="fas fa-clock text-blue-400 text-xl"></i>
          </div>
          <p class="text-sm text-gray-400 mt-2">Industry avg: 85%</p>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
  <!-- Flight Status Overview (Left) -->
  <div class="bg-gray-800 p-6 rounded-2xl shadow-lg">
    <h2 class="text-2xl font-semibold mb-4 text-white">Flight Status Overview</h2>
    <div class="flex justify-center">
      <div class="w-full max-w-xs mx-auto h-64">
        <canvas id="flightPieChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Top Routes (Right) -->
  <div class="bg-gray-800 p-6 rounded-xl shadow-lg">
    <h2 class="text-xl font-semibold mb-4">Top Routes</h2>
    <div class="space-y-4">
      <?php
      // In a real app, these would come from database
      $top_routes = [
          ['route' => 'Delhi → Mumbai', 'flights' => 45, 'change' => '+8%'],
          ['route' => 'Bangalore → Hyderabad', 'flights' => 32, 'change' => '+5%'],
          ['route' => 'Mumbai → Chennai', 'flights' => 27, 'change' => '-2%']
      ];
      
      foreach ($top_routes as $route):
      ?>
      <div class="flex justify-between items-center">
        <div class="flex items-center">
          <span class="bg-indigo-600 p-2 rounded-lg mr-3">
            <i class="fas fa-route text-sm"></i>
          </span>
          <div>
            <p class="font-medium"><?php echo htmlspecialchars($route['route']); ?></p>
            <p class="text-sm text-gray-400"><?php echo $route['flights']; ?> flights daily</p>
          </div>
        </div>
        <span class="<?php echo strpos($route['change'], '+') !== false ? 'text-green-400' : 'text-red-400'; ?> text-sm">
          <?php echo htmlspecialchars($route['change']); ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Live Flight Schedule (Below) -->
<div class="bg-gray-800 p-6 rounded-xl shadow-lg mt-6">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-semibold">Live Flight Schedule</h2>
    <button class="text-sm text-indigo-400 hover:text-indigo-300" onclick="window.location.reload()">
      <i class="fas fa-sync-alt mr-1"></i> Refresh
    </button>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="text-indigo-400 border-b border-gray-600">
        <tr>
          <th class="text-left py-3 px-4">Flight</th>
          <th class="text-left py-3 px-4">Route</th>
          <th class="text-left py-3 px-4">Departure</th>
          <th class="text-left py-3 px-4">Arrival</th>
          <th class="text-left py-3 px-4">Status</th>
        </tr>
      </thead>
      <tbody class="text-white divide-y divide-gray-700">
        <?php if (count($live_flights) > 0): ?>
          <?php foreach ($live_flights as $flight): ?>
            <tr class="hover:bg-gray-700">
              <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($flight['flight_number']); ?></td>
              <td class="py-3 px-4"><?php echo htmlspecialchars($flight['origin_code']) . " → " . htmlspecialchars($flight['dest_code']); ?></td>
              <td class="py-3 px-4"><?php echo formatTime($flight['departure_time']); ?></td>
              <td class="py-3 px-4"><?php echo formatTime($flight['arrival_time']); ?></td>
              <td class="py-3 px-4 <?php 
                if ($flight['flight_status'] == 'Scheduled' || $flight['flight_status'] == 'Departed' || $flight['flight_status'] == 'Arrived') {
                  echo 'status-on-time';
                } elseif ($flight['flight_status'] == 'Delayed') {
                  echo 'status-delayed';
                } else {
                  echo 'status-cancelled';
                }
              ?>"><?php echo htmlspecialchars($flight['flight_status']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="py-3 px-4 text-center">No flights scheduled in the next 12 hours</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
        
      <!-- Dashboard content remains the same as before -->
      <!-- ... -->
    </section>

    <!-- Bookings Section -->
      <section id="bookings" class="section-content hidden">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-gray-800 p-6 rounded-xl shadow-lg">
          <h2 class="text-xl font-semibold mb-4">Upcoming Trips</h2>
          <div class="space-y-4">
            <?php if (count($upcoming_bookings) > 0): ?>
              <?php foreach ($upcoming_bookings as $booking): ?>
                <div class="border-l-4 border-indigo-500 pl-4 py-2">
                  <div class="flex justify-between">
                    <h3 class="font-medium"><?php echo htmlspecialchars($booking['origin_city']) . " to " . htmlspecialchars($booking['destination_city']); ?></h3>
                    <span class="text-sm bg-indigo-900 text-indigo-300 px-2 py-1 rounded"><?php echo htmlspecialchars($booking['booking_status']); ?></span>
                  </div>
                  <p class="text-sm text-gray-400">
                    <?php echo htmlspecialchars($booking['airline_name']) . " " . htmlspecialchars($booking['flight_number']); ?> • 
                    <?php echo formatDate($booking['departure_time']); ?>
                  </p>
                  <p class="text-sm mt-1">Departure: <?php echo formatTime($booking['departure_time']); ?></p>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="text-center text-gray-400">No upcoming trips</p>
              <div class="text-center mt-4">
                <a href="#" onclick="showSection('tickets')" class="bg-indigo-600 hover:bg-indigo-500 text-white py-2 px-4 rounded-lg transition-colors">
                  Book a Flight
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>
        
        <div class="bg-gray-800 p-6 rounded-xl shadow-lg">
          <h2 class="text-xl font-semibold mb-4">Booking History</h2>
          <div class="space-y-4">
            <?php if (count($booking_history) > 0): ?>
              <?php foreach ($booking_history as $booking): ?>
                <div class="border-l-4 border-gray-600 pl-4 py-2">
                  <div class="flex justify-between">
                    <h3 class="font-medium"><?php echo htmlspecialchars($booking['origin_city']) . " to " . htmlspecialchars($booking['destination_city']); ?></h3>
                    <span class="text-sm bg-gray-700 text-gray-300 px-2 py-1 rounded">Completed</span>
                  </div>
                  <p class="text-sm text-gray-400">
                    <?php echo htmlspecialchars($booking['airline_name']) . " " . htmlspecialchars($booking['flight_number']); ?> • 
                    <?php echo formatDate($booking['departure_time']); ?>
                  </p>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="text-center text-gray-400">No booking history</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
      <!-- ... -->


    <!-- Tickets Section -->
    <section id="tickets" class="section-content hidden">
    
      <div class="max-w-2xl mx-auto bg-gray-800 p-6 rounded-xl shadow-lg">
        <h2 class="text-xl font-semibold mb-6 text-center">Search Flights</h2>
        <form class="space-y-4" action="flight-search.php" method="GET">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">From</label>
              <select name="from" class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required>
                <option value="" selected disabled>Select city</option>
                <?php foreach ($airports as $airport): ?>
                  <option value="<?php echo htmlspecialchars($airport['airport_id']); ?>">
                    <?php echo htmlspecialchars($airport['city']) . " (" . htmlspecialchars($airport['airport_id']) . ")"; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">To</label>
              <select name="to" class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required>
                <option value="" selected disabled>Select city</option>
                <?php foreach ($airports as $airport): ?>
                  <option value="<?php echo htmlspecialchars($airport['airport_id']); ?>">
                    <?php echo htmlspecialchars($airport['city']) . " (" . htmlspecialchars($airport['airport_id']) . ")"; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Departure</label>
              <input type="date" name="departure" min="<?php echo date('Y-m-d'); ?>" class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Travelers</label>
              <select name="travelers" class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="1">1 Adult</option>
                <option value="2">2 Adults</option>
                <option value="3">3 Adults</option>
                <option value="4">Family (2+2)</option>
              </select>
            </div>
          </div>
          
          <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-3 rounded-lg font-medium transition-colors">
            Search Flights <i class="fas fa-search ml-2"></i>
          </button>
        </form>
      </div>
    
 <!-- Tickets content remains the same as before -->
      <!-- ... -->
    </section>

    <!-- Profile Section -->
    <section id="profile" class="section-content hidden">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-gray-800 p-6 rounded-xl shadow-lg">
          <h2 class="text-xl font-semibold mb-6">Profile Information</h2>
          
          <!-- Profile form - initially hidden -->
          <form id="profileEditForm" class="hidden" action="update-profile.php" method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-sm font-medium mb-1">Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-sm font-medium mb-1">Phone</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <input type="password" name="password" placeholder="Leave blank to keep current" class="w-full p-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
              </div>
            </div>

            <div class="flex justify-end space-x-3">
              <button type="button" onclick="toggleProfileEdit()" class="px-4 py-2 text-gray-400 bg-gray-700 hover:bg-gray-600 rounded-lg">Cancel</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg">Save Changes</button>
            </div>
          </form>

          <!-- Profile view - initially visible -->
          <div id="profileView">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <p class="text-gray-400 text-sm">Full Name</p>
                <p class="font-medium"><?php echo htmlspecialchars($user['full_name']); ?></p>
              </div>
              <div>
                <p class="text-gray-400 text-sm">Email</p>
                <p class="font-medium"><?php echo htmlspecialchars($user['email']); ?></p>
              </div>
              <div>
                <p class="text-gray-400 text-sm">Phone</p>
                <p class="font-medium"><?php echo htmlspecialchars($user['phone']); ?></p>
              </div>
              <div>
                <p class="text-gray-400 text-sm">Member Since</p>
                <p class="font-medium"><?php echo formatDate($user['member_since']); ?></p>
              </div>
            </div>
            
            <button onclick="toggleProfileEdit()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg">
              <i class="fas fa-edit mr-2"></i> Edit Profile
            </button>
          </div>
        </div>

        <div class="bg-gray-800 p-6 rounded-xl shadow-lg">
          <h2 class="text-xl font-semibold mb-4">Loyalty Program</h2>
          <div class="flex items-center mb-4">
            <div class="h-16 w-16 rounded-full bg-gradient-to-br <?php 
              if ($user['loyalty_tier'] == 'Bronze') echo 'from-amber-700 to-amber-500';
              elseif ($user['loyalty_tier'] == 'Silver') echo 'from-gray-300 to-gray-100';
              elseif ($user['loyalty_tier'] == 'Gold') echo 'from-yellow-500 to-yellow-300';
              else echo 'from-purple-600 to-purple-400';
            ?> flex items-center justify-center mr-4">
              <i class="fas <?php 
                if ($user['loyalty_tier'] == 'Bronze') echo 'fa-award';
                elseif ($user['loyalty_tier'] == 'Silver') echo 'fa-medal';
                elseif ($user['loyalty_tier'] == 'Gold') echo 'fa-trophy';
                else echo 'fa-crown';
              ?> text-2xl text-white"></i>
            </div>
            <div>
              <h3 class="font-medium text-lg"><?php echo htmlspecialchars($user['loyalty_tier']); ?> Member</h3>
              <p class="text-sm text-gray-400"><?php echo number_format($user['loyalty_points']); ?> points</p>
            </div>
          </div>
          
          <?php if ($user['loyalty_tier'] != 'Platinum'): ?>
          <div class="mb-4">
            <div class="flex justify-between text-sm mb-1">
              <span><?php echo number_format($user['loyalty_points']); ?> points</span>
              <span>Next tier: <?php echo htmlspecialchars($next_tier); ?></span>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-2.5">
              <div class="bg-indigo-600 h-2.5 rounded-full" style="width: <?php echo $progress_percentage; ?>%"></div>
            </div>
            <p class="text-sm text-gray-400 mt-2">
              <?php echo number_format($points_to_next_tier); ?> more points needed to reach <?php echo htmlspecialchars($next_tier); ?>
            </p>
          </div>
          <?php endif; ?>
          
          <h3 class="font-medium mb-2 mt-6">Benefits</h3>
          <ul class="space-y-2 text-sm">
            <?php if ($user['loyalty_tier'] == 'Bronze' || $user['loyalty_tier'] == 'Silver' || $user['loyalty_tier'] == 'Gold' || $user['loyalty_tier'] == 'Platinum'): ?>
            <li class="flex items-center">
              <i class="fas fa-check text-green-400 mr-2"></i> Priority check-in
            </li>
            <?php endif; ?>
            
            <?php if ($user['loyalty_tier'] == 'Silver' || $user['loyalty_tier'] == 'Gold' || $user['loyalty_tier'] == 'Platinum'): ?>
            <li class="flex items-center">
              <i class="fas fa-check text-green-400 mr-2"></i> Free seat selection
            </li>
            <?php endif; ?>
            
            <?php if ($user['loyalty_tier'] == 'Gold' || $user['loyalty_tier'] == 'Platinum'): ?>
            <li class="flex items-center">
              <i class="fas fa-check text-green-400 mr-2"></i> Lounge access
            </li>
            <?php endif; ?>
            
            <?php if ($user['loyalty_tier'] == 'Platinum'): ?>
            <li class="flex items-center">
              <i class="fas fa-check text-green-400 mr-2"></i> Complimentary upgrades
            </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </section>

    <!-- JavaScript -->
    <script>
      // Initialize charts
      const ctx = document.getElementById('flightPieChart').getContext('2d');
      const flightChart = new Chart(ctx, {
        type: 'pie',
        data: {
          labels: ['On Time', 'Delayed', 'Cancelled'],
          datasets: [{
            label: 'Flight Status',
            data: [2, 2, 1],
            backgroundColor: [
              'rgb(34, 197, 94)',    // Green
              'rgb(253, 224, 71)',    // Yellow
              'rgb(239, 68, 68)'      // Red
            ],
            borderColor: 'rgb(31, 41, 55)',
            borderWidth: 2,
            hoverOffset: 8
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: { 
                color: 'white',
                font: {
                  size: 12
                }
              }
            }
          }
        }
      });

      // Handle dropdowns
      document.getElementById('notificationIcon').addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('notificationDropdown').classList.toggle('show');
        document.getElementById('profileDropdown').classList.remove('show');
      });
      
      document.getElementById('profileIcon').addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('profileDropdown').classList.toggle('show');
        document.getElementById('notificationDropdown').classList.remove('show');
      });
      
      // Close dropdowns when clicking elsewhere
      document.addEventListener('click', function() {
        closeAllDropdowns();
      });

      // Function to show selected section
    // Function to show selected section
function showSection(sectionId) {
  // Hide all sections
   console.log('Showing section:', sectionId);
  console.log('Section element:', document.getElementById(sectionId));
  document.querySelectorAll('.section-content').forEach(function(section) {
    section.classList.add('hidden');
  });
  
  // Show selected section
  const section = document.getElementById(sectionId);
  if (section) {
    section.classList.remove('hidden');
  } else {
    console.error('Section not found:', sectionId);
  }
  
  // Update page title
  document.getElementById('pageTitle').textContent = 
    sectionId.charAt(0).toUpperCase() + sectionId.slice(1);
  
  // Update active sidebar link - fix this part
  document.querySelectorAll('.sidebar-link').forEach(function(link) {
    link.classList.remove('active');
    const onclickValue = link.getAttribute('onclick');
    if (onclickValue && onclickValue.includes(`showSection('${sectionId}')`)) {
      link.classList.add('active');
    }
  });
  
  // Close any open dropdowns
  closeAllDropdowns();
}

      // Function to toggle profile edit form
      function toggleProfileEdit() {
        const profileView = document.getElementById('profileView');
        const profileEditForm = document.getElementById('profileEditForm');
        
        if (profileView && profileEditForm) {
          profileView.classList.toggle('hidden');
          profileEditForm.classList.toggle('hidden');
        }
      }
      // Ensure all sections are properly initialized
document.addEventListener('DOMContentLoaded', function() {
  // Make dashboard active by default
  showSection('dashboard');
  
  // Add direct click handlers to sidebar links as a backup
  document.querySelectorAll('.sidebar-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const section = this.getAttribute('onclick').match(/showSection\('([^']+)'\)/)[1];
      showSection(section);
    });
  });
});
    </script>
  </main>
</div>
</body>
</html>