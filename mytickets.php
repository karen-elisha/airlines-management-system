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

// Fetch tickets from generated_tickets table
$query = "SELECT * FROM generated_tickets WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to safely decode JSON
function safeJsonDecode($json_string, $default = []) {
    if (empty($json_string)) {
        return $default;
    }
    $decoded = json_decode($json_string, true);
    return $decoded !== null ? $decoded : $default;
}

// Function to get status class
function getStatusClass($status) {
    switch(strtolower($status)) {
        case 'active':
            return 'status-confirmed';
        case 'cancelled':
            return 'status-cancelled';
        case 'expired':
            return 'status-expired';
        default:
            return 'status-pending';
    }
}

// Function to get passenger details for a booking
function getPassengerDetails($pdo, $booking_reference) {
    $query = "SELECT * FROM passengers WHERE booking_id = :booking_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':booking_id', $booking_reference, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Tickets | BookMyFlight</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .ticket-card {
      transition: all 0.2s ease;
      border-left: 4px solid transparent;
      position: relative;
    }
    .ticket-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
      border-left-color: #4f46e5;
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
    .status-expired {
      background-color: #e5e7eb;
      color: #4b5563;
    }
    .ticket-preview {
      max-height: 300px;
      overflow-y: auto;
      font-size: 12px;
      line-height: 1.4;
    }
    .print-btn {
      background-color: #4f46e5;
      color: white;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 14px;
      transition: background-color 0.2s;
    }
    .print-btn:hover {
      background-color: #4338ca;
    }
    .passenger-badge {
      display: inline-flex;
      align-items: center;
      padding: 2px 8px;
      border-radius: 9999px;
      font-size: 12px;
      margin-right: 4px;
      margin-bottom: 4px;
    }
    .passenger-male {
      background-color: #dbeafe;
      color: #1e40af;
    }
    .passenger-female {
      background-color: #fce7f3;
      color: #9d174d;
    }
    .passenger-other {
      background-color: #e5e7eb;
      color: #4b5563;
    }
    .route-line {
      position: relative;
      padding-left: 24px;
    }
    .route-line:before {
      content: "";
      position: absolute;
      left: 8px;
      top: 0;
      height: 100%;
      width: 2px;
      background-color: #4f46e5;
    }
    .route-dot {
      position: absolute;
      left: 4px;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: #4f46e5;
    }
    .route-dot.start {
      top: 0;
    }
    .route-dot.end {
      bottom: 0;
    }
    @media print {
      body * {
        visibility: hidden;
      }
      .printable-ticket, .printable-ticket * {
        visibility: visible;
      }
      .printable-ticket {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen flex flex-col">

  <!-- Navigation Bar -->
  <header class="bg-indigo-900 text-white shadow-lg no-print">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
      <div class="flex items-center space-x-3">
        <i class="fas fa-plane-departure text-2xl"></i>
        <h1 class="text-xl font-bold">BookMyFlight</h1>
      </div>
      <nav class="hidden md:flex space-x-6">
        <a href="user-dashboard.php" class="hover:text-indigo-200"><i class="fas fa-home mr-1"></i> Dashboard</a>
        <a href="my-bookings.php" class="hover:text-indigo-200"><i class="fas fa-suitcase mr-1"></i> My Bookings</a>
        <a href="my-tickets.php" class="text-indigo-200 font-medium"><i class="fas fa-ticket-alt mr-1"></i> My Tickets</a>
      </nav>
      <a href="user-dashboard.php" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-md transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
      </a>
    </div>
  </header>

  <!-- Main Content -->
  <main class="flex-1 container mx-auto px-4 py-8 no-print">
    <!-- Page Header -->
    <div class="text-center mb-10">
      <h1 class="text-3xl md:text-4xl font-bold text-gray-800">Your Flight Tickets</h1>
      <p class="mt-3 text-gray-600 max-w-2xl mx-auto">
        View and print all your flight e-tickets in one place.
      </p>
    </div>

    <!-- Tickets Grid -->
    <?php if (empty($tickets)): ?>
      <!-- Empty State -->
      <div class="p-12 text-center bg-white rounded-xl shadow-md">
        <i class="fas fa-ticket-alt text-4xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-medium text-gray-700">No E-Tickets Found</h3>
        <p class="text-gray-500 mt-2">You don't have any e-tickets issued yet.</p>
        <a href="search-flights.php" class="mt-4 inline-block bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 transition-colors">
          Book a Flight
        </a>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <?php foreach ($tickets as $ticket): ?>
          <?php
          $flight_details = safeJsonDecode($ticket['flight_details']);
          $passenger_details = safeJsonDecode($ticket['passenger_details']);
          $created_at = new DateTime($ticket['created_at']);
          $db_passengers = getPassengerDetails($pdo, $ticket['booking_reference']);
          ?>
          <div class="bg-white rounded-lg shadow-md overflow-hidden ticket-card">
            
            <!-- Ticket status badge -->
            <div class="<?php echo getStatusClass($ticket['status']); ?> px-3 py-1 absolute top-4 right-4 rounded-full text-xs font-medium">
              <?php echo ucfirst($ticket['status']); ?>
            </div>
            
            <!-- Ticket header -->
            <div class="bg-indigo-50 p-4 pt-12">
              <h3 class="font-bold text-gray-800">E-Ticket</h3>
              <p class="text-sm text-gray-600">Booking Reference: <?php echo htmlspecialchars($ticket['booking_reference']); ?></p>
            </div>
            
            <!-- Ticket content -->
            <div class="p-4">
              <!-- Flight route visualization -->
              <div class="mb-4">
                <div class="route-line">
                  <div class="route-dot start"></div>
                  <div class="flex justify-between">
                    <div>
                      <p class="text-gray-500 text-xs">FROM</p>
                      <p class="font-bold text-lg"><?php echo htmlspecialchars(strtoupper($flight_details['origin'] ?? '')); ?></p>
                      <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($flight_details['departure_time'] ?? ''); ?></p>
                    </div>
                    <div class="text-center">
                      <div class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full text-xs">
                        <i class="fas fa-plane"></i> <?php echo htmlspecialchars($flight_details['flight_number'] ?? 'N/A'); ?>
                      </div>
                    </div>
                    <div class="text-right">
                      <p class="text-gray-500 text-xs">TO</p>
                      <p class="font-bold text-lg"><?php echo htmlspecialchars(strtoupper($flight_details['destination'] ?? '')); ?></p>
                      <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($flight_details['arrival_time'] ?? ''); ?></p>
                    </div>
                  </div>
                  <div class="route-dot end"></div>
                </div>
              </div>
              
              <!-- Airline and duration -->
              <div class="flex justify-between items-center mb-4">
                <div class="flex items-center">
                  <div class="bg-indigo-100 p-2 rounded-full mr-2">
                    <i class="fas fa-plane text-indigo-600"></i>
                  </div>
                  <div>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($flight_details['airline'] ?? 'N/A'); ?></p>
                    <p class="text-xs text-gray-500">Flight Duration: <?php echo htmlspecialchars($flight_details['duration'] ?? 'N/A'); ?></p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium"><?php echo htmlspecialchars($flight_details['class'] ?? 'Economy'); ?></p>
                  <p class="text-xs text-gray-500"><?php echo count($db_passengers); ?> Passenger(s)</p>
                </div>
              </div>
              
              <!-- Passenger details from database -->
              <?php if (!empty($db_passengers)): ?>
                <div class="mb-4">
                  <p class="text-gray-500 text-sm mb-2">Passenger Details:</p>
                  <div class="flex flex-wrap">
                    <?php foreach($db_passengers as $passenger): ?>
                      <div class="passenger-badge <?php echo 'passenger-' . strtolower($passenger['gender']); ?>">
                        <i class="fas fa-user mr-1"></i>
                        <?php echo htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?>
                        <span class="ml-1 text-xs">(<?php echo htmlspecialchars($passenger['age']); ?>)</span>
                        <?php if (!empty($passenger['seat_number'])): ?>
                          <span class="ml-1 font-bold">• <?php echo htmlspecialchars($passenger['seat_number']); ?></span>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
              
              <!-- Ticket metadata -->
              <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                <div>
                  <p class="text-gray-500">Issued On:</p>
                  <p class="font-medium"><?php echo $created_at->format('d M Y, h:i A'); ?></p>
                </div>
                <div>
                  <p class="text-gray-500">Ticket ID:</p>
                  <p class="font-medium"><?php echo htmlspecialchars($ticket['ticket_id']); ?></p>
                </div>
              </div>
              
              <!-- Action buttons -->
              <div class="flex justify-between items-center border-t pt-4">
                <details class="text-indigo-600 hover:text-indigo-800 text-sm cursor-pointer">
                  <summary><i class="fas fa-eye mr-1"></i> View Ticket</summary>
                  <div class="mt-2 p-3 bg-gray-50 border rounded ticket-preview">
                    <?php echo $ticket['ticket_html']; ?>
                  </div>
                </details>
                <button onclick="printTicket('<?php echo $ticket['ticket_id']; ?>')" class="print-btn">
                  <i class="fas fa-print mr-1"></i> Print Ticket
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <!-- Hidden printable ticket container -->
  <div id="printable-ticket-container" class="hidden"></div>

  <!-- Footer -->
  <footer class="bg-gray-900 text-gray-400 py-8 no-print">
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

  <script>
    // Function to print the ticket
    function printTicket(ticketId) {
      // Find the ticket HTML (this would need to be adapted based on your actual ticket structure)
      const ticketHtml = document.querySelector(`[data-ticket-id="${ticketId}"] .ticket-preview`)?.innerHTML;
      
      if (ticketHtml) {
        // Create a printable version
        const printableContainer = document.getElementById('printable-ticket-container');
        printableContainer.innerHTML = `
          <div class="printable-ticket p-8">
            ${ticketHtml}
            <div class="text-center mt-8 text-sm text-gray-500">
              <p>Thank you for flying with BookMyFlight</p>
              <p>Booking Reference: ${ticketId}</p>
              <p>Printed on: ${new Date().toLocaleString()}</p>
            </div>
          </div>
        `;
        
        // Make the container visible for printing
        printableContainer.classList.remove('hidden');
        
        // Print the ticket
        window.print();
        
        // Hide the container again
        printableContainer.classList.add('hidden');
      } else {
        alert('Ticket content not found');
      }
    }

    // Add event listeners to all print buttons
    document.querySelectorAll('.print-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const ticketId = this.getAttribute('data-ticket-id');
        printTicket(ticketId);
      });
    });
  </script>
</body>
</html>