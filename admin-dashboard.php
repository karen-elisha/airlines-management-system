<?php
// Database connection
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "airlines";

// Establish connection
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Initialize session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) && !strpos($_SERVER['REQUEST_URI'], 'login.php')) {
    header("Location: login.php");
    exit;
}

// Set default active section
$active_section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Set timezone to Asia/Kolkata
date_default_timezone_set('Asia/Kolkata');
$current_datetime = date('Y-m-d H:i:s');

// Fetch dashboard statistics
$bookings_count = $mysqli->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$users_count = $mysqli->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$flights_count = $mysqli->query("SELECT COUNT(*) as count FROM flights WHERE departure_time >= '$current_datetime'")->fetch_assoc()['count'];
$airlines_count = $mysqli->query("SELECT COUNT(*) as count FROM airlines")->fetch_assoc()['count'];

// Fetch recent bookings for dashboard
$recent_bookings = $mysqli->query("SELECT b.booking_reference, u.full_name, b.booking_date, b.booking_status 
                                FROM bookings b
                                JOIN users u ON b.user_id = u.user_id
                                ORDER BY b.booking_date DESC
                                LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Fetch airlines
$airlines = $mysqli->query("SELECT * FROM airlines ORDER BY airline_name")->fetch_all(MYSQLI_ASSOC);

// Fetch flights with airline info - only upcoming flights from current time
$flights = $mysqli->query("SELECT f.*, a.airline_name 
                    FROM flights f
                    JOIN airlines a ON f.airline_id = a.airline_id
                    WHERE f.departure_time >= '$current_datetime'
                    ORDER BY f.departure_time")->fetch_all(MYSQLI_ASSOC);

// Fetch users
$users = $mysqli->query("SELECT * FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// [Rest of your existing code for airline management, flight management, etc.]
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | BOOKMYFLIGHT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .sidebar { background: rgba(0, 0, 0, 0.85); }
    .main-content { background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(8px); }
    .card { background: rgba(255, 255, 255, 0.08); transition: all 0.3s ease; }
    .card:hover { background: rgba(255, 255, 255, 0.12); }
    .nav-item { transition: color 0.2s ease; }
    .nav-item:hover { color: #818cf8; }
    .section { display: none; opacity: 0; transition: opacity 0.3s ease; }
    .section.active { display: block; opacity: 1; }
    .table-row:hover { background: rgba(255, 255, 255, 0.05); }
    .badge { @apply px-2 py-1 rounded text-xs; }
    .badge-success { @apply bg-green-900 text-green-300; }
    .badge-warning { @apply bg-yellow-900 text-yellow-300; }
    .badge-danger { @apply bg-red-900 text-red-300; }
    .badge-info { @apply bg-blue-900 text-blue-300; }
    .current-time { 
      background: rgba(59, 130, 246, 0.2);
      border-left: 3px solid #3B82F6;
      padding: 2px 8px;
      border-radius: 4px;
    }
  </style>
</head>
<body class="bg-gray-900 text-white min-h-screen flex">
<!-- Sidebar -->
  <aside class="sidebar w-64 p-6 hidden md:block">
    <div class="flex items-center mb-8">
      <img src="https://img.icons8.com/ios-filled/50/ffffff/airplane-mode-on.png" class="w-8 mr-3">
      <h1 class="text-xl font-bold text-indigo-400">BOOKMYFLIGHT</h1>
    </div>
    
    <nav class="space-y-3">
      <a href="?section=dashboard" class="block nav-item py-2 <?= $active_section === 'dashboard' ? 'text-indigo-400' : '' ?>">
        <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
      </a>
      <a href="?section=bookings" class="block nav-item py-2 <?= $active_section === 'bookings' ? 'text-indigo-400' : '' ?>">
        <i class="fas fa-ticket-alt mr-2"></i> Bookings
      </a>
      <a href="?section=flights" class="block nav-item py-2 <?= $active_section === 'flights' ? 'text-indigo-400' : '' ?>">
        <i class="fas fa-plane mr-2"></i> Flights
      </a>
      <a href="?section=users" class="block nav-item py-2 <?= $active_section === 'users' ? 'text-indigo-400' : '' ?>">
        <i class="fas fa-users mr-2"></i> Users
      </a>
      <a href="?section=airlines" class="block nav-item py-2 <?= $active_section === 'airlines' ? 'text-indigo-400' : '' ?>">
        <i class="fas fa-building mr-2"></i> Airlines
      </a>
      <a href="?logout" class="block nav-item py-2 text-red-400 mt-8">
        <i class="fas fa-sign-out-alt mr-2"></i> Logout
      </a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="main-content flex-1 p-6 overflow-y-auto">
    <header class="flex justify-between items-center mb-8">
      <h2 class="text-2xl font-bold text-indigo-300">
        <?= ucfirst($active_section) ?> <?= $active_section === 'dashboard' ? 'Overview' : '' ?>
      </h2>
      <div class="flex items-center space-x-4">
        <span class="text-sm text-gray-400">
          <i class="far fa-clock mr-1"></i> <?= date('Y-m-d H:i:s') ?>
        </span>
        <span class="text-sm text-gray-400">
          <i class="fas fa-user-shield mr-1"></i> <?= $_SESSION['admin_username'] ?>
        </span>
      </div>
    </header>

    <!-- Dashboard Section -->
    <section id="dashboardSection" class="section <?= $active_section === 'dashboard' ? 'active' : '' ?>">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Stats Cards -->
        <div class="card p-4 rounded-xl">
          <div class="text-gray-400"><i class="fas fa-ticket-alt mr-2"></i> Total Bookings</div>
          <div class="text-2xl font-bold"><?= $bookings_count ?></div>
        </div>
        <div class="card p-4 rounded-xl">
          <div class="text-gray-400"><i class="fas fa-users mr-2"></i> Registered Users</div>
          <div class="text-2xl font-bold"><?= $users_count ?></div>
        </div>
        <div class="card p-4 rounded-xl">
          <div class="text-gray-400"><i class="fas fa-plane mr-2"></i> Active Flights</div>
          <div class="text-2xl font-bold"><?= $flights_count ?></div>
        </div>
        <div class="card p-4 rounded-xl">
          <div class="text-gray-400"><i class="fas fa-building mr-2"></i> Airlines</div>
          <div class="text-2xl font-bold"><?= $airlines_count ?></div>
        </div>
      </div>

        <!-- Recent Bookings Card -->
        <div class="card p-5 rounded-xl lg:col-span-2">
          <h3 class="text-lg font-semibold mb-4"><i class="fas fa-history mr-2"></i> Recent Bookings</h3>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-gray-800 text-left">
                  <th class="p-3">Booking ID</th>
                  <th class="p-3">Customer</th>
                  <th class="p-3">Date</th>
                  <th class="p-3">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_bookings as $booking): ?>
                <tr class="table-row border-b border-gray-800">
                  <td class="p-3"><?= $booking['booking_reference'] ?></td>
                  <td class="p-3"><?= $booking['full_name'] ?></td>
                  <td class="p-3"><?= date('M d, Y', strtotime($booking['booking_date'])) ?></td>
                  <td class="p-3">
                    <span class="badge <?= 
                      $booking['booking_status'] === 'Confirmed' ? 'badge-success' : 
                      ($booking['booking_status'] === 'Pending' ? 'badge-warning' : 'badge-danger')
                    ?>">
                      <?= $booking['booking_status'] ?>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- Bookings Section -->
    <section id="bookingsSection" class="section <?= $active_section === 'bookings' ? 'active' : '' ?>">
      <div class="card p-5 rounded-xl overflow-x-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold"><i class="fas fa-ticket-alt mr-2"></i> All Bookings</h3>
          <div class="relative">
            <input type="text" placeholder="Search bookings..." class="p-2 pl-8 rounded bg-gray-800 border border-gray-700 w-64">
            <i class="fas fa-search absolute left-2 top-3 text-gray-500"></i>
          </div>
        </div>
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-800 text-left">
              <th class="p-3">Booking Ref</th>
              <th class="p-3">Customer</th>
              <th class="p-3">Flight</th>
              <th class="p-3">Date</th>
              <th class="p-3">Passengers</th>
              <th class="p-3">Amount</th>
              <th class="p-3">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $all_bookings = $mysqli->query("SELECT b.*, u.full_name, f.flight_number, f.origin_airport, f.destination_airport 
                                       FROM bookings b
                                       JOIN users u ON b.user_id = u.user_id
                                       JOIN flights f ON b.flight_id = f.flight_id
                                       ORDER BY b.created_at DESC")->fetch_all(MYSQLI_ASSOC);
            
            foreach ($all_bookings as $booking): 
              $flight_info = "{$booking['flight_number']} ({$booking['origin_airport']}-{$booking['destination_airport']})";
            ?>
            <tr class="table-row border-b border-gray-800">
              <td class="p-3"><?= $booking['booking_reference'] ?></td>
              <td class="p-3"><?= $booking['full_name'] ?></td>
              <td class="p-3"><?= $flight_info ?></td>
              <td class="p-3"><?= date('M d, Y', strtotime($booking['travel_date'])) ?></td>
              <td class="p-3"><?= $booking['num_passengers'] ?></td>
              <td class="p-3">$<?= number_format($booking['total_amount'], 2) ?></td>
              <td class="p-3">
                <span class="badge <?= 
                  $booking['booking_status'] === 'Confirmed' ? 'badge-success' : 
                  ($booking['booking_status'] === 'Pending' ? 'badge-warning' : 'badge-danger')
                ?>">
                  <?= $booking['booking_status'] ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

  <!-- [Rest of your HTML remains the same until the flights section] -->

  <!-- Flights Section -->
  <section id="flightsSection" class="section <?= $active_section === 'flights' ? 'active' : '' ?>">
    <div class="card p-5 rounded-xl">
      <div class="flex justify-between items-center mb-4">
        <div>
          <h3 class="text-lg font-semibold"><i class="fas fa-plane mr-2"></i> Flight Management</h3>
          <div class="text-sm text-gray-400 mt-1">
            <i class="far fa-clock mr-1"></i> Current Time (Asia/Kolkata): 
            <span class="current-time" id="currentTime"><?= date('M d, Y H:i:s') ?></span>
          </div>
        </div>
        <button onclick="showFlightForm()" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded transition">
          <i class="fas fa-plus mr-2"></i> Add Flight
        </button>
      </div>

      <!-- [Rest of your flights section HTML remains the same] -->
      <!-- Add/Edit Flight Form (hidden by default) -->
    <div id="flightForm" class="hidden mb-6 card p-4">
      <h4 class="text-md font-semibold mb-3" id="flightFormTitle">
        <i class="fas fa-plane mr-2"></i> Add New Flight
      </h4>
      <form method="POST" id="flightFormElement">
        <input type="hidden" name="flight_id" id="editFlightId">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-building mr-1"></i> Airline</label>
            <select name="airline_id" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
              <option value="">Select Airline</option>
              <?php foreach ($airlines as $airline): ?>
                <option value="<?= $airline['airline_id'] ?>"><?= $airline['airline_name'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-hashtag mr-1"></i> Flight Number</label>
            <input type="text" name="flight_number" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-plane-departure mr-1"></i> Origin Airport</label>
            <input type="text" name="origin_airport" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-plane-arrival mr-1"></i> Destination Airport</label>
            <input type="text" name="destination_airport" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="far fa-clock mr-1"></i> Departure Time</label>
            <input type="datetime-local" name="departure_time" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="far fa-clock mr-1"></i> Arrival Time</label>
            <input type="datetime-local" name="arrival_time" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-money-bill-wave mr-1"></i> Price ($)</label>
            <input type="number" step="0.01" min="0" name="base_price" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-chair mr-1"></i> Available Seats</label>
            <input type="number" min="1" name="available_seats" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
          </div>
          <!-- Add Flight Status Field -->
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-info-circle mr-1"></i> Flight Status</label>
            <select name="flight_status" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
              <option value="Scheduled">Scheduled</option>
              <option value="Delayed">Delayed</option>
              <option value="Cancelled">Cancelled</option>
              <option value="Departed">Departed</option>
              <option value="Arrived">Arrived</option>
            </select>
          </div>
        </div>
        <div class="flex justify-end mt-4 space-x-2">
          <button type="button" onclick="hideFlightForm()" class="px-4 py-2 border border-gray-600 rounded">
            <i class="fas fa-times mr-2"></i> Cancel
          </button>
          <button type="submit" name="save_flight" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded">
            <i class="fas fa-save mr-2"></i> Save Flight
          </button>
        </div>
      </form>
    </div>

      <!-- Flights Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-800 text-left">
              <th class="p-3">Flight No</th>
              <th class="p-3">Airline</th>
              <th class="p-3">Route</th>
              <th class="p-3">Departure</th>
              <th class="p-3">Arrival</th>
              <th class="p-3">Price</th>
              <th class="p-3">Status</th>
              <th class="p-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($flights as $flight): 
              $departure = date('M d, H:i', strtotime($flight['departure_time']));
              $arrival = date('M d, H:i', strtotime($flight['arrival_time']));
              
              // Calculate time remaining
              $now = new DateTime();
              $departure_time = new DateTime($flight['departure_time']);
              $time_remaining = $now->diff($departure_time);
              
              // Format time remaining
              if ($time_remaining->d > 0) {
                  $remaining_text = $time_remaining->d . 'd ' . $time_remaining->h . 'h';
              } elseif ($time_remaining->h > 0) {
                  $remaining_text = $time_remaining->h . 'h ' . $time_remaining->i . 'm';
              } else {
                  $remaining_text = $time_remaining->i . 'm';
              }
            ?>
            <tr class="table-row border-b border-gray-800">
              <td class="p-3"><?= $flight['flight_number'] ?></td>
              <td class="p-3"><?= $flight['airline_name'] ?></td>
              <td class="p-3"><?= "{$flight['origin_airport']} → {$flight['destination_airport']}" ?></td>
              <td class="p-3">
                <?= $departure ?>
                <div class="text-xs text-gray-400">in <?= $remaining_text ?></div>
              </td>
              <td class="p-3"><?= $arrival ?></td>
              <td class="p-3">$<?= number_format($flight['base_price'], 2) ?></td>
              <td class="p-3">
                <span class="badge <?= 
                  $flight['flight_status'] === 'On Time' ? 'badge-success' : 
                  ($flight['flight_status'] === 'Delayed' ? 'badge-warning' : 'badge-danger')
                ?>">
                  <?= $flight['flight_status'] ?? 'On Time' ?>
                </span>
              </td>
              <td class="p-3">
                <button onclick="editFlight(
                  '<?= $flight['flight_id'] ?>',
                  '<?= $flight['airline_id'] ?>',
                  '<?= $flight['flight_number'] ?>',
                  '<?= $flight['origin_airport'] ?>',
                  '<?= $flight['destination_airport'] ?>',
                  '<?= date('Y-m-d\TH:i', strtotime($flight['departure_time'])) ?>',
                  '<?= date('Y-m-d\TH:i', strtotime($flight['arrival_time'])) ?>',
                  '<?= $flight['base_price'] ?>',
                  '<?= $flight['available_seats'] ?>',
                  '<?= $flight['flight_status'] ?>'
                )" class="text-indigo-400 hover:text-indigo-300 mr-2">
                  <i class="fas fa-edit"></i>
                </button>
                <a href="?delete_flight=<?= $flight['flight_id'] ?>" class="text-red-400 hover:text-red-300" onclick="return confirm('Are you sure you want to delete this flight?')">
                  <i class="fas fa-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
<!-- Users Section -->
    <section id="usersSection" class="section <?= $active_section === 'users' ? 'active' : '' ?>">
      <div class="card p-5 rounded-xl overflow-x-auto">
        <h3 class="text-lg font-semibold mb-4"><i class="fas fa-users mr-2"></i> User Management</h3>
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-800 text-left">
              <th class="p-3">User ID</th>
              <th class="p-3">Name</th>
              <th class="p-3">Email</th>
              <th class="p-3">Phone</th>
              <th class="p-3">Registered</th>
              <th class="p-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
            <tr class="table-row border-b border-gray-800">
              <td class="p-3"><?= $user['user_id'] ?></td>
              <td class="p-3"><?= $user['full_name'] ?></td>
              <td class="p-3"><?= $user['email'] ?></td>
              <td class="p-3"><?= $user['phone'] ?? 'N/A' ?></td>
              <td class="p-3"><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
              <td class="p-3">
                <a href="javascript:void(0)" onclick="deleteUser(<?= $user['user_id'] ?>)" class="text-red-400 hover:text-red-300">
                  <i class="fas fa-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>



<!-- Airlines Section - FIXED VERSION -->
<section id="airlinesSection" class="section <?= $active_section === 'airlines' ? 'active' : '' ?>">
      <div class="card p-5 rounded-xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold"><i class="fas fa-building mr-2"></i> Airlines</h3>
          <button onclick="showAirlineForm()" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded transition">
            <i class="fas fa-plus mr-2"></i> Add Airline
          </button>
        </div>

        <!-- Add/Edit Airline Form (hidden by default) -->
<div id="airlineForm" class="hidden mb-6 card p-4">
    <h4 class="text-md font-semibold mb-3" id="airlineFormTitle">
        <i class="fas fa-building mr-2"></i> Add New Airline
    </h4>
    <form method="POST" class="space-y-4" id="airlineFormElement">
        <input type="hidden" name="airline_id" id="editAirlineId">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Airline Name *</label>
                <input type="text" name="airline_name" id="editAirlineName"
                       class="w-full px-3 py-2 rounded-lg bg-gray-800 text-gray-300 border border-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                       required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Customer Care *</label>
                <input type="text" name="customer_care" id="editCustomerCare"
                       class="w-full px-3 py-2 rounded-lg bg-gray-800 text-gray-300 border border-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                       required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Contact URL *</label>
                <input type="url" name="contact_url" id="editContactUrl"
                       class="w-full px-3 py-2 rounded-lg bg-gray-800 text-gray-300 border border-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                       required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Website</label>
                <input type="url" name="website" id="editWebsite"
                       class="w-full px-3 py-2 rounded-lg bg-gray-800 text-gray-300 border border-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Status *</label>
                <select name="active" id="editActive" class="w-full px-3 py-2 rounded-lg bg-gray-800 text-gray-300 border border-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end mt-4 space-x-2">
            <button type="button" onclick="hideAirlineForm()" class="px-4 py-2 border border-gray-600 rounded">
                <i class="fas fa-times mr-2"></i> Cancel
            </button>
            <button type="submit" name="add_airline" id="airlineSubmitBtn"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <i class="fas fa-save mr-2"></i> Add Airline
            </button>
        </div>
    </form>
</div>

        <!-- Airlines Table -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-800 text-left">
                <th class="p-3">Airline ID</th>
                <th class="p-3">Airline Name</th>
                <th class="p-3">Customer Care</th>
                <th class="p-3">Contact URL</th>
                <th class="p-3">Website</th>
                <th class="p-3">Status</th>
                <th class="p-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($airlines as $airline): ?>
              <tr class="table-row border-b border-gray-800">
                <td class="p-3"><?= $airline['airline_id'] ?></td>
                <td class="p-3"><?= $airline['airline_name'] ?></td>
                <td class="p-3"><?= $airline['customer_care'] ?></td>
                <td class="p-3"><?= $airline['contact_url'] ?></td>
                <td class="p-3"><?= $airline['website'] ? '<a href="' . htmlspecialchars($airline['website']) . '" target="_blank">' . htmlspecialchars($airline['website']) . '</a>' : '-' ?></td>
                <td class="p-3">
                  <span class="badge <?= $airline['active'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $airline['active'] ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td class="p-3">
                  <div class="flex space-x-2">
                    <button onclick="editAirline(
                      '<?= $airline['airline_id'] ?>',
                      '<?= htmlspecialchars($airline['airline_name'], ENT_QUOTES) ?>',
                      '<?= htmlspecialchars($airline['customer_care'], ENT_QUOTES) ?>',
                      '<?= htmlspecialchars($airline['contact_url'], ENT_QUOTES) ?>',
                      '<?= htmlspecialchars($airline['website'], ENT_QUOTES) ?>',
                      '<?= $airline['active'] ?>'
                    )" class="text-blue-400 hover:text-blue-300">
                      <i class="fas fa-edit"></i>
                    </button>
                    <a href="?section=airlines&delete_airline=<?= $airline['airline_id'] ?>" 
                       onclick="return confirm('Are you sure you want to delete this airline?')"
                       class="text-red-400 hover:text-red-300">
                      <i class="fas fa-trash"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>


  <!-- [Rest of your HTML remains the same] -->

  <script>
    // Update current time every second
    function updateCurrentTime() {
      const options = {
        timeZone: 'Asia/Kolkata',
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      };
      
      const now = new Date();
      const formatter = new Intl.DateTimeFormat('en-US', options);
      document.getElementById('currentTime').textContent = formatter.format(now);
      
      // Refresh page every 5 minutes to get updated flight data
      const minutes = now.getMinutes();
      if (minutes % 5 === 0 && now.getSeconds() === 0) {
        window.location.reload();
      }
    }
    
    // Update time immediately and then every second
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);

    // [Rest of your JavaScript remains the same]
// Function to handle user deletion
function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        // Show loading indicator
        const button = document.querySelector(`[onclick*="deleteUser(${userId})"]`);
        if (button) {
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
        }

        // Make the request
        window.location.href = '?section=users&delete_user=' + userId;
    }
}

// Initialize dashboard and charts
document.addEventListener('DOMContentLoaded', function() {
  // Display alert messages from PHP session
  displaySessionMessages();
  
  // Flight Status Chart
  if (document.getElementById('flightStatusChart')) {
    initializeFlightStatusChart();
  }
  
  // Booking Trends Chart (monthly data)
  if (document.getElementById('bookingTrendsChart')) {
    initializeBookingTrendsChart();
  }
  
  // Initialize DataTables for better table functionality
  initializeDataTables();
  
  // Set up event listeners for all interactive elements
  setupEventListeners();
});

// Handle PHP session messages (success and error)
function displaySessionMessages() {
  const successMsg = document.getElementById('successMessage');
  const errorMsg = document.getElementById('errorMessage');
  
  if (successMsg && successMsg.textContent.trim() !== '') {
    successMsg.classList.remove('hidden');
    setTimeout(() => {
      successMsg.classList.add('hidden');
    }, 5000);
  }
  
  if (errorMsg && errorMsg.textContent.trim() !== '') {
    errorMsg.classList.remove('hidden');
    setTimeout(() => {
      errorMsg.classList.add('hidden');
    }, 5000);
  }
}

// Initialize DataTables plugin for better table management
function initializeDataTables() {
  const tables = [
    '#flightsTable', 
    '#airlinesTable', 
    '#usersTable', 
    '#bookingsTable'
  ];
  
  tables.forEach(tableId => {
    const table = document.querySelector(tableId);
    if (table) {
      $(tableId).DataTable({
        responsive: true,
        pageLength: 10,
        language: {
          search: "Search:",
          lengthMenu: "Show _MENU_ entries",
          info: "Showing _START_ to _END_ of _TOTAL_ entries"
        }
      });
    }
  });
}

// Initialize Flight Status Chart
function initializeFlightStatusChart() {
  // Fetch actual data from backend via AJAX or use data embedded in page
  const onTimeCount = parseInt(document.getElementById('onTimeCount')?.dataset.count || 75);
  const delayedCount = parseInt(document.getElementById('delayedCount')?.dataset.count || 15);
  const cancelledCount = parseInt(document.getElementById('cancelledCount')?.dataset.count || 10);
  
  new Chart(document.getElementById('flightStatusChart'), {
    type: 'doughnut',
    data: {
      labels: ['On Time', 'Delayed', 'Cancelled'],
      datasets: [{
        data: [onTimeCount, delayedCount, cancelledCount],
        backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
        borderWidth: 0
      }]
    },
    options: { 
      cutout: '70%', 
      plugins: { 
        legend: { 
          position: 'bottom',
          labels: {
            padding: 20,
            font: {
              family: "'Inter', sans-serif"
            }
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.raw || 0;
              const total = context.dataset.data.reduce((acc, curr) => acc + curr, 0);
              const percentage = Math.round((value / total) * 100);
              return `${label}: ${value} (${percentage}%)`;
            }
          }
        }
      }
    }
  });
}

// Initialize Booking Trends Chart
function initializeBookingTrendsChart() {
  // Fetch booking data via AJAX or use data embedded in page
  const ctx = document.getElementById('bookingTrendsChart').getContext('2d');
  
  // Example data - in production you would fetch this from your PHP backend
  const bookingData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    datasets: [{
      label: 'Bookings',
      data: [65, 59, 80, 81, 56, 55, 40, 45, 60, 75, 82, 90],
      borderColor: '#3B82F6',
      backgroundColor: 'rgba(59, 130, 246, 0.5)',
      tension: 0.3,
      fill: true
    }]
  };
  
  new Chart(ctx, {
    type: 'line',
    data: bookingData,
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          mode: 'index',
          intersect: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0
          }
        }
      }
    }
  });
}

function setupEventListeners() {
  // Flight form buttons
  const addFlightBtn = document.querySelector('.add-flight-btn');
  if (addFlightBtn) {
    addFlightBtn.addEventListener('click', showFlightForm);
  }
  
  const cancelFlightBtn = document.querySelector('.cancel-flight-btn');
  if (cancelFlightBtn) {
    cancelFlightBtn.addEventListener('click', hideFlightForm);
  }

  // Confirm all delete actions
  document.querySelectorAll('a[href*="delete_"]').forEach(link => {
    link.addEventListener('click', function(e) {
      let confirmMessage = 'Are you sure you want to delete this item?';
      
      // Customize message based on what's being deleted
      if (link.href.includes('delete_airline')) {
        confirmMessage = 'Are you sure you want to delete this airline? This cannot be undone.';
      } else if (link.href.includes('delete_flight')) {
        confirmMessage = 'Are you sure you want to delete this flight? This cannot be undone.';
      } else if (link.href.includes('delete_user')) {
        confirmMessage = 'Are you sure you want to delete this user? This cannot be undone.';
      }
      
      if (!confirm(confirmMessage)) {
        e.preventDefault();
      }
    });
  });
  
  // Flight form validation
  const flightForm = document.getElementById('flightFormElement');
  if (flightForm) {
    flightForm.addEventListener('submit', validateFlightForm);
  }
  
  
  // Add date and time validation to departure/arrival inputs
  const departureInput = document.querySelector('input[name="departure_time"]');
  const arrivalInput = document.querySelector('input[name="arrival_time"]');
  
  if (departureInput && arrivalInput) {
    departureInput.addEventListener('change', function() {
      validateFlightTimes();
    });
    
    arrivalInput.addEventListener('change', function() {
      validateFlightTimes();
    });
  }
  
  // Setup tab navigation if present
  setupTabNavigation();
}

function hideFlightForm() {
  const form = document.getElementById('flightForm');
  if (form) form.classList.add('hidden');
}

function showFlightForm() {
  // Reset the form
  document.getElementById('flightFormElement').reset();
  document.getElementById('editFlightId').value = '';
  document.getElementById('flightFormTitle').innerHTML = '<i class="fas fa-plus mr-2"></i> Add New Flight';
  
  // Show the form
  document.getElementById('flightForm').classList.remove('hidden');
  
  // Scroll to the form
  document.getElementById('flightForm').scrollIntoView({ behavior: 'smooth' });
}

function editFlight(flightId, airlineId, flightNumber, origin, destination, departure, arrival, price, seats, status) {
  showFlightForm();
  const form = document.getElementById('flightFormElement');
  if (form) {
    document.getElementById('flightFormTitle').innerHTML = '<i class="fas fa-edit mr-2"></i> Edit Flight';
    document.getElementById('editFlightId').value = flightId;
    form.querySelector('select[name="airline_id"]').value = airlineId;
    form.querySelector('input[name="flight_number"]').value = flightNumber;
    form.querySelector('input[name="origin_airport"]').value = origin;
    form.querySelector('input[name="destination_airport"]').value = destination;
    form.querySelector('input[name="departure_time"]').value = departure;
    form.querySelector('input[name="arrival_time"]').value = arrival;
    form.querySelector('input[name="base_price"]').value = price;
    form.querySelector('input[name="available_seats"]').value = seats;
    form.querySelector('select[name="flight_status"]').value = status;
    document.getElementById('flightForm').scrollIntoView({ behavior: 'smooth' });
  }
}
// FIXED Airline Form Functions
function showAirlineForm() {
    const form = document.getElementById('airlineForm');
    const formElement = document.getElementById('airlineFormElement');
    const submitBtn = document.getElementById('airlineSubmitBtn');
    
    if (form && formElement) {
        // Show form
        form.classList.remove('hidden');
        
        // Reset form title and button for adding new airline
        document.getElementById('airlineFormTitle').innerHTML = '<i class="fas fa-building mr-2"></i> Add New Airline';
        
        // Reset form fields
        formElement.reset();
        document.getElementById('editAirlineId').value = '';
        
        // Update submit button for adding
        if (submitBtn) {
            submitBtn.setAttribute('name', 'add_airline');
            submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Add Airline';
        }
        
        // Scroll to form
        form.scrollIntoView({ behavior: 'smooth' });
    }
}

function hideAirlineForm() {
    const form = document.getElementById('airlineForm');
    if (form) {
        form.classList.add('hidden');
    }
}

function editAirline(airlineId, airlineName, customerCare, contactUrl, website, active) {
    const form = document.getElementById('airlineForm');
    const formElement = document.getElementById('airlineFormElement');
    const submitBtn = document.getElementById('airlineSubmitBtn');
    
    if (form && formElement) {
        // Show form
        form.classList.remove('hidden');
        
        // Update form title for editing
        document.getElementById('airlineFormTitle').innerHTML = '<i class="fas fa-edit mr-2"></i> Edit Airline';
        
        // Populate form fields
        document.getElementById('editAirlineId').value = airlineId;
        document.getElementById('editAirlineName').value = airlineName;
        document.getElementById('editCustomerCare').value = customerCare;
        document.getElementById('editContactUrl').value = contactUrl;
        document.getElementById('editWebsite').value = website || '';
        document.getElementById('editActive').value = active;
        
        // Update submit button for editing
        if (submitBtn) {
            submitBtn.setAttribute('name', 'edit_airline');
            submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Update Airline';
        }
        
        // Scroll to form
        form.scrollIntoView({ behavior: 'smooth' });
    }
}

// FIXED Form validation for airlines
function validateAirlineForm(e) {
    const form = e.target;
    let isValid = true;
    
    // Required fields
    const requiredFields = ['airline_name', 'customer_care', 'contact_url'];
    
    requiredFields.forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (!input || !input.value.trim()) {
            isValid = false;
            highlightError(input, 'This field is required');
        } else {
            removeError(input);
        }
    });
    
    // Validate URL format for contact_url and website
    const contactUrl = form.querySelector('[name="contact_url"]');
    if (contactUrl && contactUrl.value.trim()) {
        const urlRegex = /^https?:\/\/.+/;
        if (!urlRegex.test(contactUrl.value.trim())) {
            isValid = false;
            highlightError(contactUrl, 'Please enter a valid URL starting with http:// or https://');
        }
    }
    
    const website = form.querySelector('[name="website"]');
    if (website && website.value.trim()) {
        const urlRegex = /^https?:\/\/.+/;
        if (!urlRegex.test(website.value.trim())) {
            isValid = false;
            highlightError(website, 'Please enter a valid URL starting with http:// or https://');
        }
    }
    
    if (!isValid) {
        e.preventDefault();
    }
    
    return isValid;
}

// Updated setupEventListeners function to include airline form events
function setupEventListeners() {
  // Flight form buttons
  const addFlightBtn = document.querySelector('button[onclick="showFlightForm()"]');
  if (addFlightBtn) {
    addFlightBtn.addEventListener('click', showFlightForm);
  }
  
 
  
  // Confirm all delete actions
  document.querySelectorAll('a[href*="delete_"]').forEach(link => {
    link.addEventListener('click', function(e) {
      let confirmMessage = 'Are you sure you want to delete this item?';
      
      // Customize message based on what's being deleted
      if (link.href.includes('delete_airline')) {
        confirmMessage = 'Are you sure you want to delete this airline? This cannot be undone.';
      } else if (link.href.includes('delete_flight')) {
        confirmMessage = 'Are you sure you want to delete this flight? This cannot be undone.';
      } else if (link.href.includes('delete_user')) {
        confirmMessage = 'Are you sure you want to delete this user? This cannot be undone.';
      }
      
      if (!confirm(confirmMessage)) {
        e.preventDefault();
      }
    });
  });
  
  // Flight form validation
  const flightForm = document.getElementById('flightFormElement');
  if (flightForm) {
    flightForm.addEventListener('submit', validateFlightForm);
  }
  

  
  // Add date and time validation to departure/arrival inputs
  const departureInput = document.querySelector('input[name="departure_time"]');
  const arrivalInput = document.querySelector('input[name="arrival_time"]');
  
  if (departureInput && arrivalInput) {
    departureInput.addEventListener('change', function() {
      validateFlightTimes();
    });
    
    arrivalInput.addEventListener('change', function() {
      validateFlightTimes();
    });
  }
}
// Form Validation
function validateFlightForm(e) {
  const form = e.target;
  let isValid = true;
  
  // Required fields
  const requiredFields = [
    'airline_id', 'flight_number', 'origin_airport', 
    'destination_airport', 'departure_time', 'arrival_time',
    'base_price', 'available_seats'
  ];
  
  requiredFields.forEach(field => {
    const input = form.querySelector(`[name="${field}"]`);
    if (!input || !input.value.trim()) {
      isValid = false;
      highlightError(input, 'This field is required');
    } else {
      removeError(input);
    }
  });
  
  // Validate flight number format (e.g., AB123)
  const flightNumberInput = form.querySelector('[name="flight_number"]');
  if (flightNumberInput && flightNumberInput.value.trim()) {
    const flightNumberRegex = /^[A-Z0-9]{2,8}$/;
    if (!flightNumberRegex.test(flightNumberInput.value.trim())) {
      isValid = false;
      highlightError(flightNumberInput, 'Flight number should be 2-8 alphanumeric characters');
    }
  }
  
  // Make sure origin and destination are different
  const origin = form.querySelector('[name="origin_airport"]').value.trim();
  const destination = form.querySelector('[name="destination_airport"]').value.trim();
  
  if (origin && destination && origin.toLowerCase() === destination.toLowerCase()) {
    isValid = false;
    highlightError(form.querySelector('[name="destination_airport"]'), 
      'Origin and destination cannot be the same');
  }
  
  // Validate departure/arrival times
  if (!validateFlightTimes()) {
    isValid = false;
  }
  
  // Validate numeric fields
  const basePrice = form.querySelector('[name="base_price"]');
  if (basePrice && basePrice.value.trim() && (isNaN(basePrice.value) || parseFloat(basePrice.value) <= 0)) {
    isValid = false;
    highlightError(basePrice, 'Base price must be a positive number');
  }
  
  const availableSeats = form.querySelector('[name="available_seats"]');
  if (availableSeats && availableSeats.value.trim() && 
      (isNaN(availableSeats.value) || parseInt(availableSeats.value) <= 0)) {
    isValid = false;
    highlightError(availableSeats, 'Available seats must be a positive integer');
  }
  
  if (!isValid) {
    e.preventDefault();
  }
  
  return isValid;
}

function validateFlightTimes() {
  const departureInput = document.querySelector('input[name="departure_time"]');
  const arrivalInput = document.querySelector('input[name="arrival_time"]');
  
  if (!departureInput || !arrivalInput) return true;
  
  const departure = new Date(departureInput.value);
  const arrival = new Date(arrivalInput.value);
  
  if (departure && arrival && departure >= arrival) {
    highlightError(arrivalInput, 'Arrival time must be after departure time');
    return false;
  } else {
    removeError(departureInput);
    removeError(arrivalInput);
    return true;
  }
}


// Form helper functions
function highlightError(input, message) {
  if (!input) return;
  
  input.classList.add('border-red-500');
  
  // Create or update error message
  let errorElement = input.nextElementSibling;
  if (!errorElement || !errorElement.classList.contains('error-message')) {
    errorElement = document.createElement('p');
    errorElement.className = 'error-message text-red-500 text-sm mt-1';
    input.parentNode.insertBefore(errorElement, input.nextSibling);
  }
  
  errorElement.textContent = message;
}

function removeError(input) {
  if (!input) return;
  
  input.classList.remove('border-red-500');
  
  // Remove error message if exists
  const errorElement = input.nextElementSibling;
  if (errorElement && errorElement.classList.contains('error-message')) {
    errorElement.remove();
  }
}

// Tab navigation system (if needed)
function setupTabNavigation() {
  const tabLinks = document.querySelectorAll('.tab-link');
  const tabContents = document.querySelectorAll('.tab-content');
  
  if (tabLinks.length && tabContents.length) {
    tabLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Get tab ID from data attribute
        const tabId = this.getAttribute('data-tab');
        
        // Remove active class from all tabs and contents
        tabLinks.forEach(tab => tab.classList.remove('active'));
        tabContents.forEach(content => content.classList.add('hidden'));
        
        // Add active class to current tab and content
        this.classList.add('active');
        document.getElementById(tabId).classList.remove('hidden');
        
        // Update URL without reloading (for bookmarking)
        history.pushState(null, null, `?section=${tabId}`);
      });
    });
  }
}

// Mobile menu toggle
function toggleMobileMenu() {
  const sidebar = document.querySelector('.sidebar');
  const mainContent = document.querySelector('.main-content');
  
  if (sidebar) {
    sidebar.classList.toggle('hidden');
    sidebar.classList.toggle('md:block');
    
    if (mainContent) {
      mainContent.classList.toggle('md:ml-64');
    }
  }
}

// Function to fetch flight details using AJAX (for populating modals, etc.)
function fetchFlightDetails(flightId) {
  fetch(`get_flight_details.php?flight_id=${flightId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Populate modal or form with flight details
        populateFlightDetailModal(data.flight);
      } else {
        alert('Error loading flight details: ' + data.error);
      }
    })
    .catch(error => {
      console.error('Error:', error);
    });
}

// Function to display flight details in a modal
function populateFlightDetailModal(flight) {
  const modal = document.getElementById('flightDetailModal');
  if (!modal) return;
  
  // Populate modal content with flight details
  modal.querySelector('.modal-title').textContent = `Flight ${flight.flight_number}`;
  modal.querySelector('.modal-airline').textContent = flight.airline_name;
  modal.querySelector('.modal-route').textContent = `${flight.origin_airport} to ${flight.destination_airport}`;
  modal.querySelector('.modal-departure').textContent = formatDateTime(flight.departure_time);
  modal.querySelector('.modal-arrival').textContent = formatDateTime(flight.arrival_time);
  modal.querySelector('.modal-price').textContent = `$${parseFloat(flight.base_price).toFixed(2)}`;
  modal.querySelector('.modal-seats').textContent = flight.available_seats;
  modal.querySelector('.modal-status').textContent = flight.flight_status;
  
  // Show the modal
  modal.classList.remove('hidden');
}

// Helper function to format date and time
function formatDateTime(dateTimeStr) {
  const options = { 
    weekday: 'short',
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  };
  
  return new Date(dateTimeStr).toLocaleString(undefined, options);
}

// Close modal when clicking outside or on X button
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-backdrop') || 
      e.target.classList.contains('close-modal')) {
    document.querySelectorAll('.modal').forEach(modal => {
      modal.classList.add('hidden');
    });
  }
});

// Export bookings to CSV
function exportBookingsToCSV() {
  // Fetch data via AJAX or use table data
  const table = document.getElementById('bookingsTable');
  if (!table) return;
  
  let csvContent = "data:text/csv;charset=utf-8,";
  
  // Add headers
  const headers = [];
  table.querySelectorAll('thead th').forEach(th => {
    headers.push(th.textContent.trim());
  });
  csvContent += headers.join(',') + '\n';
  
  // Add rows
  table.querySelectorAll('tbody tr').forEach(row => {
    const rowData = [];
    row.querySelectorAll('td').forEach(cell => {
      // Clean and quote the data to handle commas
      rowData.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
    });
    csvContent += rowData.join(',') + '\n';
  });
  
  // Create download link
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', 'bookings_export.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// Add event listener for export functionality if the button exists
document.addEventListener('DOMContentLoaded', function() {
  const exportBtn = document.getElementById('exportBookingsBtn');
  if (exportBtn) {
    exportBtn.addEventListener('click', exportBookingsToCSV);
  }
});
  </script>
</body>
</html>