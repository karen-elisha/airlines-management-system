<?php
session_start();
require_once 'db_connect.php'; // Your database connection file

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin-login.php");
    exit();
}

// Handle flight operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_flight'])) {
        // Add or update flight
        $flight_id = $_POST['flight_id'] ?? null;
        $data = [
            'airline_id' => $_POST['airline_id'],
            'flight_number' => $_POST['flight_number'],
            'origin_airport' => $_POST['origin_airport'],
            'destination_airport' => $_POST['destination_airport'],
            'departure_time' => $_POST['departure_time'],
            'arrival_time' => $_POST['arrival_time'],
            'base_price' => $_POST['base_price'],
            'available_seats' => $_POST['available_seats']
        ];

        if ($flight_id) {
            // Update existing flight
            $stmt = $mysqli->prepare("UPDATE flights SET 
                airline_id = ?, 
                flight_number = ?, 
                origin_airport = ?, 
                destination_airport = ?, 
                departure_time = ?, 
                arrival_time = ?, 
                base_price = ?, 
                available_seats = ? 
                WHERE flight_id = ?");
            $stmt->bind_param("isssssdii", 
                $data['airline_id'],
                $data['flight_number'],
                $data['origin_airport'],
                $data['destination_airport'],
                $data['departure_time'],
                $data['arrival_time'],
                $data['base_price'],
                $data['available_seats'],
                $flight_id
            );
        } else {
            // Insert new flight
            $stmt = $mysqli->prepare("INSERT INTO flights 
                (airline_id, flight_number, origin_airport, destination_airport, departure_time, arrival_time, price, available_seats) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssdi", 
                $data['airline_id'],
                $data['flight_number'],
                $data['origin_airport'],
                $data['destination_airport'],
                $data['departure_time'],
                $data['arrival_time'],
                $data['base_price'],
                $data['available_seats']
            );
        }
        $stmt->execute();
        $stmt->close();
        
        // Refresh to show changes
        header("Location: admin-dashboard.php");
        exit();
    }
    
    if (isset($_POST['update_flight_status'])) {
        // Update flight status
        $flight_id = $_POST['flight_id'];
        $status = $_POST['flight_status'];
        
        $stmt = $mysqli->prepare("UPDATE flights SET flight_status = ? WHERE flight_id = ?");
        $stmt->bind_param("si", $flight_status, $flight_id);
        $stmt->execute();
        $stmt->close();
        
        // Refresh to show changes
        header("Location: admin-dashboard.php");
        exit();
    }
    
    if (isset($_POST['add_airline'])) {
        // Add new airline
        $airline_name = $_POST['airline_name'];
        $logo_url = $_POST['logo_url'] ?? '';
        $website = $_POST['website'] ?? '';
        $customer_care = $_POST['customer_care'] ?? '';
        $contact_url = $_POST['contact_url'] ?? '';
        
        $stmt = $mysqli->prepare("INSERT INTO airlines 
            (airline_name, logo_url, website, customer_care, contact_url) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $airline_name, $logo_url, $website, $customer_care, $contact_url);
        $stmt->execute();
        $stmt->close();
        
        // Refresh to show changes
        header("Location: admin-dashboard.php?section=airlines");
        exit();
    }
}

// Handle deletions
if (isset($_GET['delete_flight'])) {
    $flight_id = $_GET['delete_flight'];
    $stmt = $mysqli->prepare("DELETE FROM flights WHERE flight_id = ?");
    $stmt->bind_param("i", $flight_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin-dashboard.php?section=flights");
    exit();
}

if (isset($_GET['delete_airline'])) {
    $airline_id = $_GET['delete_airline'];
    $stmt = $mysqli->prepare("DELETE FROM airlines WHERE airline_id = ?");
    $stmt->bind_param("i", $airline_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin-dashboard.php?section=airlines");
    exit();
}

if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    $stmt = $mysqli->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin-dashboard.php?section=users");
    exit();
}

// Get counts for dashboard
$bookings_count = $mysqli->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
$users_count = $mysqli->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$flights_count = $mysqli->query("SELECT COUNT(*) FROM flights")->fetch_row()[0];
$airlines_count = $mysqli->query("SELECT COUNT(*) FROM airlines")->fetch_row()[0];

// Get recent bookings
$recent_bookings_result = $mysqli->query("SELECT b.*, u.full_name 
                                      FROM bookings b 
                                      JOIN users u ON b.user_id = u.user_id 
                                      ORDER BY b.created_at DESC LIMIT 10");
$recent_bookings = $recent_bookings_result->fetch_all(MYSQLI_ASSOC);

// Get all users
$users_result = $mysqli->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Get all airlines
$airlines_result = $mysqli->query("SELECT * FROM airlines");
$airlines = $airlines_result->fetch_all(MYSQLI_ASSOC);

// Get all flights with airline names
$flights_result = $mysqli->query("SELECT f.*, a.airline_name 
                              FROM flights f
                              JOIN airlines a ON f.airline_id = a.airline_id
                              ORDER BY f.departure_time DESC");
$flights = $flights_result->fetch_all(MYSQLI_ASSOC);


// Get active section from URL or default to dashboard
$active_section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
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

 <!-- Flights Section -->
<section id="flightsSection" class="section <?= $active_section === 'flights' ? 'active' : '' ?>">
  <div class="card p-5 rounded-xl">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold"><i class="fas fa-plane mr-2"></i> Flight Management</h3>
      <button onclick="showFlightForm()" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded transition">
        <i class="fas fa-plus mr-2"></i> Add Flight
      </button>
    </div>

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
          ?>
          <tr class="table-row border-b border-gray-800">
            <td class="p-3"><?= $flight['flight_number'] ?></td>
            <td class="p-3"><?= $flight['airline_name'] ?></td>
            <td class="p-3"><?= "{$flight['origin_airport']} → {$flight['destination_airport']}" ?></td>
            <td class="p-3"><?= $departure ?></td>
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
                '<?= $flight['available_seats'] ?>'
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
                <a href="?delete_user=<?= $user['user_id'] ?>" class="text-red-400 hover:text-red-300" onclick="return confirm('Are you sure you want to delete this user?')">
                  <i class="fas fa-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Airlines Section -->
    <section id="airlinesSection" class="section <?= $active_section === 'airlines' ? 'active' : '' ?>">
      <div class="card p-5 rounded-xl">
        <h3 class="text-lg font-semibold mb-4"><i class="fas fa-building mr-2"></i> Manage Airlines</h3>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-signature mr-1"></i> Airline Name</label>
            <input type="text" name="airline_name" placeholder="Airline Name" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-image mr-1"></i> Logo URL</label>
            <input type="text" name="logo_url" placeholder="Logo URL" class="w-full p-2 rounded bg-gray-800 border border-gray-700">
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-globe mr-1"></i> Website</label>
            <input type="text" name="website" placeholder="Website" class="w-full p-2 rounded bg-gray-800 border border-gray-700">
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-phone mr-1"></i> Customer Care</label>
            <input type="text" name="customer_care" placeholder="Customer Care" class="w-full p-2 rounded bg-gray-800 border border-gray-700">
          </div>
          <div>
            <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-link mr-1"></i> Contact URL</label>
            <input type="text" name="contact_url" placeholder="Contact URL" class="w-full p-2 rounded bg-gray-800 border border-gray-700">
          </div>
          <div class="flex items-end">
            <button type="submit" name="add_airline" class="bg-indigo-600 hover:bg-indigo-700 py-2 px-4 rounded transition w-full">
              <i class="fas fa-plus mr-2"></i> Add Airline
            </button>
          </div>
        </form>
        
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-800 text-left">
                <th class="p-3">Airline</th>
                <th class="p-3">Website</th>
                <th class="p-3">Customer Care</th>
                <th class="p-3">Status</th>
                <th class="p-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($airlines as $airline): ?>
              <tr class="table-row border-b border-gray-800">
                <td class="p-3 flex items-center">
                  <?php if ($airline['logo_url']): ?>
                  <img src="<?= $airline['logo_url'] ?>" class="w-8 h-8 mr-2 rounded-full">
                  <?php endif; ?>
                  <?= $airline['airline_name'] ?>
                </td>
                <td class="p-3">
                  <?php if ($airline['website']): ?>
                  <a href="<?= $airline['website'] ?>" target="_blank" class="text-indigo-400 hover:underline">
                    <?= parse_url($airline['website'], PHP_URL_HOST) ?>
                  </a>
                  <?php else: ?>
                  N/A
                  <?php endif; ?>
                </td>
                <td class="p-3"><?= $airline['customer_care'] ?: 'N/A' ?></td>
                <td class="p-3">
                  <span class="badge <?= $airline['active'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $airline['active'] ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td class="p-3">
                  <a href="?delete_airline=<?= $airline['airline_id'] ?>" class="text-red-400 hover:text-red-300" onclick="return confirm('Are you sure you want to delete this airline?')">
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
  </main>

  <script>
    // Initialize charts
    document.addEventListener('DOMContentLoaded', function() {
      // Flight Status Chart
      if (document.getElementById('flightStatusChart')) {
        new Chart(document.getElementById('flightStatusChart'), {
          type: 'doughnut',
          data: {
            labels: ['On Time', 'Delayed', 'Cancelled'],
            datasets: [{
              data: [<?= $flight_stats['on_time'] ?>, <?= $flight_stats['delayed'] ?>, <?= $flight_stats['cancelled'] ?>],
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
                  color: '#fff',
                  font: {
                    family: "'Inter', sans-serif"
                  }
                }
              } 
            }
                  }
    });
  }
});

// Flight Form Functions
function showFlightForm() {
  document.getElementById('flightForm').classList.remove('hidden');
  document.getElementById('flightFormTitle').textContent = 'Add New Flight';
  document.getElementById('flightFormElement').reset();
  document.getElementById('editFlightId').value = '';
}

function hideFlightForm() {
  document.getElementById('flightForm').classList.add('hidden');
}

function editFlight(flightId, airlineId, flightNumber, origin, destination, departure, arrival, price, seats) {
  showFlightForm();
  document.getElementById('flightFormTitle').textContent = 'Edit Flight';
  document.getElementById('editFlightId').value = flightId;
  document.querySelector('select[name="airline_id"]').value = airlineId;
  document.querySelector('input[name="flight_number"]').value = flightNumber;
  document.querySelector('input[name="origin_airport"]').value = origin;
  document.querySelector('input[name="destination_airport"]').value = destination;
  document.querySelector('input[name="departure_time"]').value = departure;
  document.querySelector('input[name="arrival_time"]').value = arrival;
  document.querySelector('input[name="price"]').value = price;
  document.querySelector('input[name="available_seats"]').value = seats;
}

// Mobile menu toggle (if needed)
function toggleMobileMenu() {
  const sidebar = document.querySelector('.sidebar');
  sidebar.classList.toggle('hidden');
}
// Flight Form Functions
function showFlightForm() {
  document.getElementById('flightForm').classList.remove('hidden');
  document.getElementById('flightFormTitle').textContent = 'Add New Flight';
  document.getElementById('flightFormElement').reset();
  document.getElementById('editFlightId').value = '';
}

function hideFlightForm() {
  document.getElementById('flightForm').classList.add('hidden');
}

function editFlight(flightId, airlineId, flightNumber, origin, destination, departure, arrival, price, seats) {
  showFlightForm();
  document.getElementById('flightFormTitle').textContent = 'Edit Flight';
  document.getElementById('editFlightId').value = flightId;
  document.querySelector('select[name="airline_id"]').value = airlineId;
  document.querySelector('input[name="flight_number"]').value = flightNumber;
  document.querySelector('input[name="origin_airport"]').value = origin;
  document.querySelector('input[name="destination_airport"]').value = destination;
  document.querySelector('input[name="departure_time"]').value = departure;
  document.querySelector('input[name="arrival_time"]').value = arrival;
  document.querySelector('input[name="base_price"]').value = price;
  document.querySelector('input[name="available_seats"]').value = seats;
}
</script>
</body>
</html>