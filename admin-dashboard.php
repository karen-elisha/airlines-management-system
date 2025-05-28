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
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit;
}

// Set default active section
$active_section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Set timezone to Asia/Kolkata
date_default_timezone_set('Asia/Kolkata');
$current_datetime = date('Y-m-d H:i:s');

// Replace your existing flight add/edit section with this improved version

// Handle Flight Add/Edit (Save) - IMPROVED VERSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_flight'])) {
    $flight_id = isset($_POST['flight_id']) && !empty($_POST['flight_id']) ? intval($_POST['flight_id']) : 0;
    $flight_number = $mysqli->real_escape_string(trim($_POST['flight_number']));
    $origin_airport = $mysqli->real_escape_string(trim($_POST['origin_airport']));
    $destination_airport = $mysqli->real_escape_string(trim($_POST['destination_airport']));
    $departure_time = $mysqli->real_escape_string($_POST['departure_time']);
    $arrival_time = $mysqli->real_escape_string($_POST['arrival_time']);
    $base_price = floatval($_POST['base_price']);
    $available_seats = intval($_POST['available_seats']);
    $flight_status = $mysqli->real_escape_string($_POST['flight_status']);

    // Validate times
    if (strtotime($arrival_time) <= strtotime($departure_time)) {
        $_SESSION['error'] = "Arrival time must be after departure time!";
    } elseif (strtotime($departure_time) < time()) {
        $_SESSION['error'] = "Departure time cannot be in the past!";
    } else {
        // Start transaction for data integrity
        $mysqli->begin_transaction();
        
        try {
            if ($flight_id > 0) {
                // Edit: Update existing flight
                $query = "UPDATE flights SET 
                            flight_number = ?,
                            origin_airport = ?,
                            destination_airport = ?,
                            departure_time = ?,
                            arrival_time = ?,
                            base_price = ?,
                            available_seats = ?,
                            flight_status = ?
                          WHERE flight_id = ?";
                
                $stmt = $mysqli->prepare($query);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $mysqli->error);
                }
                
                $stmt->bind_param("sssssdisi", 
                    $flight_number, $origin_airport, $destination_airport,
                    $departure_time, $arrival_time, $base_price, 
                    $available_seats, $flight_status, $flight_id);
                
            } else {
                // Add: First check for duplicate flight number
                $check_stmt = $mysqli->prepare("SELECT flight_id FROM flights WHERE flight_number = ? AND departure_time >= ?");
                $check_stmt->bind_param("ss", $flight_number, $current_datetime);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    throw new Exception("Flight number already exists for future flights!");
                }
                $check_stmt->close();
                
                // Temporarily disable foreign key checks for insert
                $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
                
                // Add: Insert new flight using prepared statement
                $query = "INSERT INTO flights (flight_number, origin_airport, destination_airport, 
                          departure_time, arrival_time, base_price, available_seats, flight_status)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $mysqli->prepare($query);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $mysqli->error);
                }
                
                $stmt->bind_param("sssssdis",
                    $flight_number, $origin_airport, $destination_airport,
                    $departure_time, $arrival_time, $base_price, 
                    $available_seats, $flight_status);
            }

            // Execute the statement
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $stmt->close();
            
            // Re-enable foreign key checks
            $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
            
            // Commit transaction
            $mysqli->commit();
            
            $_SESSION['success'] = "Flight " . ($flight_id > 0 ? "updated" : "added") . " successfully!";
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $mysqli->rollback();
            
            // Re-enable foreign key checks
            $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
            
            $_SESSION['error'] = "Error: " . $e->getMessage();
            
            // Log the error for debugging
            error_log("Flight save error: " . $e->getMessage());
        }
    }
    header("Location: admin-dashboard.php?section=flights");
    exit;
}

// Enhanced error handling for database operations
function executeQuerySafely($mysqli, $query, $error_message = "Database operation failed") {
    $result = $mysqli->query($query);
    if (!$result) {
        error_log("Query failed: " . $mysqli->error . " Query: " . $query);
        $_SESSION['error'] = $error_message . ": " . $mysqli->error;
        return false;
    }
    return $result;
}

// Alternative solution: Create a function to handle flight operations
function addFlightWithoutFK($mysqli, $flight_data) {
    // Disable FK checks temporarily
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
    
    $query = "INSERT INTO flights (flight_number, origin_airport, destination_airport, 
              departure_time, arrival_time, base_price, available_seats, flight_status)
              VALUES ('{$flight_data['flight_number']}', '{$flight_data['origin_airport']}', 
              '{$flight_data['destination_airport']}', '{$flight_data['departure_time']}', 
              '{$flight_data['arrival_time']}', {$flight_data['base_price']}, 
              {$flight_data['available_seats']}, '{$flight_data['flight_status']}')";
    
    $result = $mysqli->query($query);
    
    // Re-enable FK checks
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
    
    return $result;
}

// Quick fix function - add this at the top of your PHP file after database connection
function fixForeignKeyIssues($mysqli) {
    try {
        // Check if there are any orphaned records and clean them
        $mysqli->query("DELETE b FROM bookings b LEFT JOIN flights f ON b.flight_id = f.flight_id WHERE f.flight_id IS NULL");
        $mysqli->query("DELETE fe FROM feedback fe LEFT JOIN flights f ON fe.flight_id = f.flight_id WHERE f.flight_id IS NULL");
        
        return true;
    } catch (Exception $e) {
        error_log("FK cleanup error: " . $e->getMessage());
        return false;
    }
}



// Handle User Deletion
if (isset($_GET['delete_user'])) {
    $user_id = intval($_GET['delete_user']);
    
    // Check if user has any bookings
    $booking_check = $mysqli->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $user_id");
    $booking_count = $booking_check->fetch_assoc()['count'];
    
    if ($booking_count > 0) {
        $_SESSION['error'] = "Cannot delete user with existing bookings! Found $booking_count booking(s).";
    } else {
        $query = "DELETE FROM users WHERE user_id = $user_id";
        
        if ($mysqli->query($query)) {
            $_SESSION['success'] = "User deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting user: " . $mysqli->error;
        }
    }
    
    header("Location: admin-dashboard.php?section=users");
    exit;
}

// Handle Booking Status Update
// Handle Booking Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = $mysqli->real_escape_string($_POST['booking_status']);
    
    $query = "UPDATE bookings SET booking_status = '$new_status' WHERE booking_id = $booking_id";
    
    if ($mysqli->query($query)) {
        $_SESSION['success'] = "Booking status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating booking status: " . $mysqli->error;
    }
    
    header("Location: admin-dashboard.php?section=bookings");
    exit;
}

// Helper function to safely fetch all results
function fetchAllResults($mysqli, $query) {
    $result = $mysqli->query($query);
    if (!$result) {
        error_log("Query failed: " . $mysqli->error . " Query: " . $query);
        return [];
    }
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $result->free();
    return $data;
}

// Helper function to safely fetch single result
function fetchSingleResult($mysqli, $query) {
    $result = $mysqli->query($query);
    if (!$result) {
        error_log("Query failed: " . $mysqli->error . " Query: " . $query);
        return null;
    }
    
    $row = $result->fetch_assoc();
    $result->free();
    return $row;
}

// Fetch dashboard statistics
$bookings_result = fetchSingleResult($mysqli, "SELECT COUNT(*) as count FROM bookings");
$bookings_count = $bookings_result ? $bookings_result['count'] : 0;

$users_result = fetchSingleResult($mysqli, "SELECT COUNT(*) as count FROM users");
$users_count = $users_result ? $users_result['count'] : 0;

$flights_result = fetchSingleResult($mysqli, "SELECT COUNT(*) as count FROM flights WHERE departure_time >= '$current_datetime'");
$flights_count = $flights_result ? $flights_result['count'] : 0;

$reviews_result = fetchSingleResult($mysqli, "SELECT COUNT(*) as count FROM feedback");
$reviews_count = $reviews_result ? $reviews_result['count'] : 0;

// Fetch recent bookings for dashboard
$recent_bookings = fetchAllResults($mysqli, "SELECT b.booking_reference, u.full_name, b.booking_date, b.booking_status 
                                FROM bookings b
                                JOIN users u ON b.user_id = u.user_id
                                ORDER BY b.booking_date DESC
                                LIMIT 5");

// Fetch flights - only upcoming flights from current time
$flights = fetchAllResults($mysqli, "SELECT * FROM flights
                    WHERE departure_time >= '$current_datetime'
                    ORDER BY departure_time");

// Fetch users
$users = fetchAllResults($mysqli, "SELECT * FROM users ORDER BY created_at DESC");

// Fetch recent reviews
// Fetch recent reviews (top 2 most recent)
$recent_reviews = fetchAllResults($mysqli, "SELECT 
    f.feedback_id,
    f.passenger_name,
    f.flight_id,
    f.flight_number,
    f.journey_date,
    f.overall_rating,
    f.punctuality,
    f.additional_feedback,
    f.complaint_type,
    f.complaint_details,
    f.website_experience,
    f.website_feedback,
    f.submission_date,
    fl.origin_airport,
    fl.destination_airport
FROM feedback f
LEFT JOIN flights fl ON f.flight_id = fl.flight_id
ORDER BY f.submission_date DESC
LIMIT 2");

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin-login.php");
    exit;
}
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
        .error-message { color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; }
        .star-rating { color: gold; }
        .form-error { border-color: #ef4444 !important; }
        .success-animation { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
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
            <a href="?section=reviews" class="block nav-item py-2 <?= $active_section === 'reviews' ? 'text-indigo-400' : '' ?>">
                <i class="fas fa-star mr-2"></i> Reviews
            </a>
            <a href="?logout" class="block nav-item py-2 text-red-400 mt-8" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content flex-1 p-6 overflow-y-auto">
        <!-- Display success/error messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-900 text-green-300 p-3 rounded mb-4 success-animation">
                <i class="fas fa-check-circle mr-2"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-900 text-red-300 p-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <header class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-bold text-indigo-300">
                <?= ucfirst($active_section) ?> <?= $active_section === 'dashboard' ? 'Overview' : '' ?>
            </h2>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-400">
                    <i class="far fa-clock mr-1"></i> <span id="headerTime"><?= date('Y-m-d H:i:s') ?></span>
                </span>
                <span class="text-sm text-gray-400">
                    <i class="fas fa-user-shield mr-1"></i> <?= $_SESSION['admin_username'] ?? 'Admin' ?>
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
                    <div class="text-gray-400"><i class="fas fa-star mr-2"></i> Customer Reviews</div>
                    <div class="text-2xl font-bold"><?= $reviews_count ?></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Recent Bookings Card -->
                <div class="card p-5 rounded-xl">
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
                                <?php if (empty($recent_bookings)): ?>
                                    <tr>
                                        <td colspan="4" class="p-3 text-center text-gray-400">No bookings found</td>
                                    </tr>
                                <?php else: ?>
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
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Reviews Card -->
                <div class="card p-5 rounded-xl">
    <h3 class="text-lg font-semibold mb-4"><i class="fas fa-star mr-2"></i> Recent Feedback</h3>
    <div class="space-y-4">
        <?php if (empty($recent_reviews)): ?>
            <p class="text-center text-gray-400">No feedback found</p>
        <?php else: ?>
            <?php foreach ($recent_reviews as $review): ?>
                <div class="border-b border-gray-800 pb-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="font-medium"><?= htmlspecialchars($review['passenger_name']) ?></span>
                            <span class="text-sm text-gray-400 ml-2">on Flight #<?= htmlspecialchars($review['flight_number']) ?></span>
                            <div class="flex items-center mt-1">
                                <div class="star-rating mr-3">
                                    <?= str_repeat('★', $review['overall_rating']) . str_repeat('☆', 5 - $review['overall_rating']) ?>
                                </div>
                                <?php if (!empty($review['punctuality'])): ?>
                                    <span class="text-xs bg-gray-700 px-2 py-1 rounded">Punctuality: <?= htmlspecialchars($review['punctuality']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-gray-400 block"><?= date('M d, Y', strtotime($review['submission_date'])) ?></span>
                            <span class="text-xs text-gray-500">Journey: <?= date('M d', strtotime($review['journey_date'])) ?></span>
                        </div>
                    </div>
                    
                    <p class="mt-2 text-gray-300 text-sm">
                        <?= htmlspecialchars(substr($review['additional_feedback'], 0, 100)) ?>
                        <?= strlen($review['additional_feedback']) > 100 ? '...' : '' ?>
                    </p>
                    
                    <?php if (!empty($review['complaint_type'])): ?>
                        <div class="mt-2">
                            <span class="text-xs bg-red-900/50 px-2 py-1 rounded">Complaint: <?= htmlspecialchars($review['complaint_type']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($review['website_feedback'])): ?>
                        <div class="mt-1 text-xs text-gray-400">
                            <i class="fas fa-globe mr-1"></i> Website: <?= htmlspecialchars(substr($review['website_feedback'], 0, 50)) ?>...
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
        </section>

        <!-- Bookings Section -->
        <section id="bookingsSection" class="section <?= $active_section === 'bookings' ? 'active' : '' ?>">
            <div class="card p-5 rounded-xl overflow-x-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold"><i class="fas fa-ticket-alt mr-2"></i> All Bookings</h3>
                    <div class="relative">
                        <input type="text" id="bookingSearch" placeholder="Search bookings..." class="p-2 pl-8 rounded bg-gray-800 border border-gray-700 w-64">
                        <i class="fas fa-search absolute left-2 top-3 text-gray-500"></i>
                    </div>
                </div>
                <table class="w-full" id="bookingsTable">
                    <thead>
                        <tr class="border-b border-gray-800 text-left">
                            <th class="p-3">Booking Ref</th>
                            <th class="p-3">Customer</th>
                            <th class="p-3">Flight</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Passengers</th>
                            <th class="p-3">Amount</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $all_bookings = fetchAllResults($mysqli, "SELECT b.*, u.full_name, f.flight_number, f.origin_airport, f.destination_airport 
                                                   FROM bookings b
                                                   JOIN users u ON b.user_id = u.user_id
                                                   JOIN flights f ON b.flight_id = f.flight_id
                                                   ORDER BY b.created_at DESC");
                        
                        if (empty($all_bookings)): ?>
                            <tr>
                                <td colspan="8" class="p-3 text-center text-gray-400">No bookings found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_bookings as $booking): 
                                $flight_info = "{$booking['flight_number']} ({$booking['origin_airport']}-{$booking['destination_airport']})";
                            ?>
                            <tr class="table-row border-b border-gray-800">
                                <td class="p-3"><?= $booking['booking_reference'] ?></td>
                                <td class="p-3"><?= $booking['full_name'] ?></td>
                                <td class="p-3"><?= $flight_info ?></td>
                                <td class="p-3"><?= date('M d, Y', strtotime($booking['travel_date'])) ?></td>
                                <td class="p-3"><?= $booking['num_passengers'] ?></td>
                                <td class="p-3">₹<?= number_format($booking['total_amount'], 2) ?></td>
                                <td class="p-3">
                                    <span class="badge <?= 
                                        $booking['booking_status'] === 'Confirmed' ? 'badge-success' : 
                                        ($booking['booking_status'] === 'Pending' ? 'badge-warning' : 'badge-danger')
                                    ?>">
                                        <?= $booking['booking_status'] ?>
                                    </span>
                                </td>
                                <td class="p-3">
                                    <button onclick="updateBookingStatus(<?= $booking['booking_id'] ?>, '<?= $booking['booking_status'] ?>')" 
                                            class="text-indigo-400 hover:text-indigo-300 mr-2" title="Update Status">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

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

                <!-- Add/Edit Flight Form (hidden by default) -->
                <div id="flightForm" class="hidden mb-6 card p-4">
                    <h4 class="text-md font-semibold mb-3" id="flightFormTitle">
                        <i class="fas fa-plane mr-2"></i> Add New Flight
                    </h4>
                    <form method="POST" id="flightFormElement" onsubmit="return validateFlightForm()">
                        <input type="hidden" name="flight_id" id="editFlightId">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-hashtag mr-1"></i> Flight Number <span class="text-red-400">*</span></label>
                                <input type="text" name="flight_number" id="flight_number" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
                                <div id="flight_number_error" class="error-message hidden"></div>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-plane-departure mr-1"></i> Origin Airport <span class="text-red-400">*</span></label>
                                <input type="text" name="origin_airport" id="origin_airport" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-plane-arrival mr-1"></i> Destination Airport <span class="text-red-400">*</span></label>
                                <input type="text" name="destination_airport" id="destination_airport" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1"><i class="far fa-clock mr-1"></i> Departure Time <span class="text-red-400">*</span></label>
                                <input type="datetime-local" name="departure_time" id="departure_time" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
                                <div id="departure_time_error" class="error-message hidden"></div>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1"><i class="far fa-clock mr-1"></i> Arrival Time <span class="text-red-400">*</span></label>
                                <input type="datetime-local" name="arrival_time" id="arrival_time" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
                                <div id="arrival_time_error" class="error-message hidden"></div>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-money-bill-wave mr-1"></i> Price (₹) <span class="text-red-400">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="base_price" id="base_price" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-chair mr-1"></i> Available Seats <span class="text-red-400">*</span></label>
                                <input type="number" min="1" max="500" name="available_seats" id="available_seats" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1"><i class="fas fa-info-circle mr-1"></i> Flight Status <span class="text-red-400">*</span></label>
                                <select name="flight_status" id="flight_status" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
                                    <option value="">Select Status</option>
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="On Time">On Time</option>
                                    <option value="Delayed">Delayed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-2 mt-4">
                            <button type="button" onclick="hideFlightForm()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded transition">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </button>
                            <button type="submit" name="save_flight" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded transition">
                                <i class="fas fa-save mr-1"></i> Save Flight
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Flights Table -->
                <div class="overflow-x-auto">
                    <table class="w-full" id="flightsTable">
                        <thead>
                            <tr class="border-b border-gray-800 text-left">
                                <th class="p-3">Flight #</th>
                                <th class="p-3">Route</th>
                                <th class="p-3">Departure</th>
                                <th class="p-3">Arrival</th>
                                <th class="p-3">Price</th>
                                <th class="p-3">Seats</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($flights)): ?>
                                <tr>
                                    <td colspan="8" class="p-3 text-center text-gray-400">No upcoming flights found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($flights as $flight): ?>
                                <tr class="table-row border-b border-gray-800">
                                    <td class="p-3 font-mono"><?= $flight['flight_number'] ?></td>
                                    <td class="p-3"><?= $flight['origin_airport'] ?> → <?= $flight['destination_airport'] ?></td>
                                    <td class="p-3"><?= date('M d, Y H:i', strtotime($flight['departure_time'])) ?></td>
                                    <td class="p-3"><?= date('M d, Y H:i', strtotime($flight['arrival_time'])) ?></td>
                                    <td class="p-3">₹<?= number_format($flight['base_price'], 2) ?></td>
                                    <td class="p-3"><?= $flight['available_seats'] ?></td>
                                    <td class="p-3">
                                        <span class="badge <?= 
                                            $flight['flight_status'] === 'On Time' || $flight['flight_status'] === 'Scheduled' ? 'badge-success' : 
                                            ($flight['flight_status'] === 'Delayed' ? 'badge-warning' : 'badge-danger')
                                        ?>">
                                            <?= $flight['flight_status'] ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <button onclick="editFlight(<?= htmlspecialchars(json_encode($flight), ENT_QUOTES, 'UTF-8') ?>)" 
                                                class="text-indigo-400 hover:text-indigo-300 mr-2" title="Edit Flight">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                       
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Users Section -->
        <section id="usersSection" class="section <?= $active_section === 'users' ? 'active' : '' ?>">
            <div class="card p-5 rounded-xl overflow-x-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold"><i class="fas fa-users mr-2"></i> User Management</h3>
                    <div class="relative">
                        <input type="text" id="userSearch" placeholder="Search users..." class="p-2 pl-8 rounded bg-gray-800 border border-gray-700 w-64">
                        <i class="fas fa-search absolute left-2 top-3 text-gray-500"></i>
                    </div>
                </div>
                <table class="w-full" id="usersTable">
                    <thead>
                        <tr class="border-b border-gray-800 text-left">
                            <th class="p-3">User ID</th>
                            <th class="p-3">Full Name</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">Phone</th>
                            <th class="p-3">Registered</th>
                            <th class="p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="p-3 text-center text-gray-400">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr class="table-row border-b border-gray-800">
                                <td class="p-3"><?= $user['user_id'] ?></td>
                                <td class="p-3"><?= $user['full_name'] ?></td>
                                <td class="p-3"><?= $user['email'] ?></td>
                                <td class="p-3"><?= $user['phone'] ?></td>
                                <td class="p-3"><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td class="p-3">
                                    <a href="?delete_user=<?= $user['user_id'] ?>&section=users"
onclick="return confirm('Are you sure you want to delete this user?')"
class="text-red-400 hover:text-red-300" title="Delete User">
<i class="fas fa-trash"></i>
</a>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
</section>    <!-- Reviews Section -->
<section id="reviewsSection" class="section <?= $active_section === 'reviews' ? 'active' : '' ?>">
    <div class="card p-5 rounded-xl">
        <h3 class="text-lg font-semibold mb-4"><i class="fas fa-star mr-2"></i> Customer Feedback</h3>
        <div class="overflow-x-auto">
            <table class="w-full" id="reviewsTable">
                <thead>
                    <tr class="border-b border-gray-800 text-left">
                        <th class="p-3">Passenger</th>
                        <th class="p-3">Flight</th>
                        <th class="p-3">Date</th>
                        <th class="p-3">Rating</th>
                        <th class="p-3">Punctuality</th>
                        <th class="p-3">Feedback</th>
                        <th class="p-3">Complaint Type</th>
                        <th class="p-3">Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $all_reviews = fetchAllResults($mysqli, "SELECT f.*, fl.flight_number 
                                          FROM feedback f
                                          JOIN flights fl ON f.flight_id = fl.flight_id
                                          ORDER BY f.submission_date DESC");
                    
                    if (empty($all_reviews)): ?>
                        <tr>
                            <td colspan="8" class="p-3 text-center text-gray-400">No feedback found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($all_reviews as $review): ?>
                        <tr class="table-row border-b border-gray-800">
                            <td class="p-3"><?= htmlspecialchars($review['passenger_name']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($review['flight_number']) ?></td>
                            <td class="p-3"><?= date('M d, Y', strtotime($review['journey_date'])) ?></td>
                            <td class="p-3">
                                <div class="star-rating">
                                    <?= str_repeat('★', $review['overall_rating']) . str_repeat('☆', 5 - $review['overall_rating']) ?>
                                </div>
                            </td>
                            <td class="p-3"><?= htmlspecialchars($review['punctuality']) ?></td>
                            <td class="p-3">
                                <div class="text-sm text-gray-300">
                                    <?= htmlspecialchars(substr($review['additional_feedback'], 0, 50)) ?><?= strlen($review['additional_feedback']) > 50 ? '...' : '' ?>
                                    <?php if (!empty($review['website_feedback'])): ?>
                                        <br><span class="text-xs">(Website: <?= htmlspecialchars(substr($review['website_feedback'], 0, 30)) ?>...)</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-3"><?= !empty($review['complaint_type']) ? htmlspecialchars($review['complaint_type']) : 'N/A' ?></td>
                            <td class="p-3 text-sm"><?= date('M d, Y', strtotime($review['submission_date'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
</main>

<!-- Booking Status Modal -->
<div id="bookingStatusModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-semibold mb-4">Update Booking Status</h3>
        <form method="POST" id="bookingStatusForm">
            <input type="hidden" name="booking_id" id="modalBookingId">
            <div class="mb-4">
                <label class="block text-sm text-gray-400 mb-2">Select New Status</label>
                <select name="booking_status" id="modalBookingStatus" class="w-full p-2 rounded bg-gray-700 border border-gray-600">
                    <option value="Confirmed">Confirmed</option>
                    <option value="Pending">Pending</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('bookingStatusModal').classList.add('hidden')" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded transition">
                    Cancel
                </button>
                <button type="submit" name="update_booking_status" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded transition">
                    Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Update time every second
    function updateTime() {
        const now = new Date();
        const timeStr = now.toLocaleString('en-US', { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit', 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit',
            hour12: false 
        }).replace(/\//g, '-');
        
        document.getElementById('headerTime').textContent = timeStr;
        document.getElementById('currentTime').textContent = timeStr;
    }
    setInterval(updateTime, 1000);
    updateTime(); // Initial call

    // Show/hide flight form
    function showFlightForm() {
        document.getElementById('flightForm').classList.remove('hidden');
        document.getElementById('flightFormElement').reset();
        document.getElementById('editFlightId').value = '';
        document.getElementById('flightFormTitle').innerHTML = '<i class="fas fa-plane mr-2"></i> Add New Flight';
        document.getElementById('flightForm').scrollIntoView({ behavior: 'smooth' });
    }

    function hideFlightForm() {
        document.getElementById('flightForm').classList.add('hidden');
    }

    // Edit flight
    function editFlight(flight) {
        document.getElementById('flightForm').classList.remove('hidden');
        document.getElementById('flightFormTitle').innerHTML = '<i class="fas fa-edit mr-2"></i> Edit Flight #' + flight.flight_number;
        
        // Convert times to local datetime-local format
        const departureTime = new Date(flight.departure_time);
        const arrivalTime = new Date(flight.arrival_time);
        
        // Format for datetime-local input (YYYY-MM-DDTHH:MM)
        const formatForInput = (date) => {
            return date.getFullYear() + '-' + 
                   String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(date.getDate()).padStart(2, '0') + 'T' + 
                   String(date.getHours()).padStart(2, '0') + ':' + 
                   String(date.getMinutes()).padStart(2, '0');
        };

        document.getElementById('editFlightId').value = flight.flight_id;
        document.getElementById('flight_number').value = flight.flight_number;
        document.getElementById('origin_airport').value = flight.origin_airport;
        document.getElementById('destination_airport').value = flight.destination_airport;
        document.getElementById('departure_time').value = formatForInput(departureTime);
        document.getElementById('arrival_time').value = formatForInput(arrivalTime);
        document.getElementById('base_price').value = flight.base_price;
        document.getElementById('available_seats').value = flight.available_seats;
        document.getElementById('flight_status').value = flight.flight_status;
        
        document.getElementById('flightForm').scrollIntoView({ behavior: 'smooth' });
    }

    // Validate flight form
    function validateFlightForm() {
        let isValid = true;
        
        // Clear previous errors
        document.querySelectorAll('.error-message').forEach(el => {
            el.classList.add('hidden');
            el.textContent = '';
        });
        document.querySelectorAll('.form-error').forEach(el => {
            el.classList.remove('form-error');
        });

        // Flight number validation
        const flightNumber = document.getElementById('flight_number').value.trim();
        if (!/^[A-Za-z0-9]+$/.test(flightNumber)) {
            const errorEl = document.getElementById('flight_number_error');
            errorEl.textContent = 'Flight number can only contain letters and numbers';
            errorEl.classList.remove('hidden');
            document.getElementById('flight_number').classList.add('form-error');
            isValid = false;
        }

        // Time validation
        const departureTime = new Date(document.getElementById('departure_time').value);
        const arrivalTime = new Date(document.getElementById('arrival_time').value);
        const now = new Date();

        if (departureTime >= arrivalTime) {
            const errorEl = document.getElementById('arrival_time_error');
            errorEl.textContent = 'Arrival time must be after departure time';
            errorEl.classList.remove('hidden');
            document.getElementById('arrival_time').classList.add('form-error');
            isValid = false;
        }

        if (departureTime < now) {
            const errorEl = document.getElementById('departure_time_error');
            errorEl.textContent = 'Departure time cannot be in the past';
            errorEl.classList.remove('hidden');
            document.getElementById('departure_time').classList.add('form-error');
            isValid = false;
        }

        return isValid;
    }

    // Booking status modal
    function updateBookingStatus(bookingId, currentStatus) {
        document.getElementById('bookingStatusModal').classList.remove('hidden');
        document.getElementById('modalBookingId').value = bookingId;
        document.getElementById('modalBookingStatus').value = currentStatus;
    }

    // Search functionality
    document.getElementById('bookingSearch')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#bookingsTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    document.getElementById('userSearch')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#usersTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Close modal when clicking outside
    document.getElementById('bookingStatusModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });

    // Initialize charts (if needed)
    document.addEventListener('DOMContentLoaded', function() {
        // You can add chart initialization here if needed
    });
</script>
</body>
</html>