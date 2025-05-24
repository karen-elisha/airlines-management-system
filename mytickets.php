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

// Fetch user information
$user_query = "SELECT full_name, email FROM users WHERE user_id = :user_id";
$user_stmt = $pdo->prepare($user_query);
$user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$user_stmt->execute();
$user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch tickets with complete flight and airline information
$tickets_query = "
    SELECT 
        gt.*,
        b.booking_reference,
        b.travel_date,
        b.num_passengers,
        b.total_amount,
        b.booking_status,
        b.contact_email,
        b.contact_phone,
        f.flight_number,
        f.departure_time,
        f.arrival_time,
        f.duration,
        f.base_price,
        al.airline_name,
        al.logo_url,
        orig.airport_name as origin_airport_name,
        orig.airport_code as origin_code,
        orig.city as origin_city,
        dest.airport_name as destination_airport_name,
        dest.airport_code as destination_code,
        dest.city as destination_city
    FROM generated_tickets gt
    JOIN bookings b ON gt.booking_id = b.booking_id
    JOIN flights f ON b.flight_id = f.flight_id
    JOIN airlines al ON f.airline_id = al.airline_id
    JOIN airports orig ON f.origin_airport = orig.airport_id
    JOIN airports dest ON f.destination_airport = dest.airport_id
    WHERE gt.user_id = :user_id
    ORDER BY gt.created_at DESC
";

$tickets_stmt = $pdo->prepare($tickets_query);
$tickets_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$tickets_stmt->execute();
$tickets = $tickets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get passengers for a booking
function getPassengers($pdo, $booking_id) {
    $query = "SELECT * FROM passengers WHERE booking_id = :booking_id ORDER BY passenger_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to format status class based on booking_status
function getStatusClass($status) {
    switch(strtolower($status)) {
        case 'confirmed':
            return 'status-active';
        case 'cancelled':
            return 'status-cancelled';
        case 'completed':
            return 'status-expired';
        default:
            return 'status-active';
    }
}

// Function to format time
function formatTime($datetime) {
    return date('h:i A', strtotime($datetime));
}

// Function to format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Function to calculate flight duration in readable format
function formatDuration($duration) {
    if (is_numeric($duration)) {
        $hours = floor($duration / 60);
        $minutes = $duration % 60;
        return $hours . 'h ' . $minutes . 'm';
    }
    return $duration;
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
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .ticket-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1);
    }
    
    .status-active {
      background-color: #dcfce7;
      color: #166534;
    }
    .status-cancelled {
      background-color: #fee2e2;
      color: #991b1b;
    }
    .status-expired {
      background-color: #f3f4f6;
      color: #6b7280;
    }
    
    .route-arrow {
      background: linear-gradient(90deg, #4338ca 0%, #4338ca 100%);
      height: 2px;
      width: 60px;
      position: relative;
    }
    .route-arrow::after {
      content: '';
      position: absolute;
      right: -6px;
      top: -3px;
      width: 0;
      height: 0;
      border-left: 6px solid #4338ca;
      border-top: 4px solid transparent;
      border-bottom: 4px solid transparent;
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
        background: white;
      }
      .no-print {
        display: none !important;
      }
    }
    
    .passenger-tag {
      padding: 4px 8px;
      border-radius: 20px;
      font-size: 12px;
      margin: 2px;
      display: inline-block;
    }
    
    .passenger-male {
      background: #dbeafe;
      color: #1e40af;
    }
    
    .passenger-female {
      background: #fce7f3;
      color: #9d174d;
    }
    
    .passenger-other {
      background: #e5e7eb;
      color: #4b5563;
    }

    .fade-in {
      animation: fadeIn 0.5s ease-in;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
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
        <a href="user-dashboard.php" class="hover:text-indigo-200">
          <i class="fas fa-home mr-1"></i> Dashboard
        </a>
        <a href="myflight.php" class="hover:text-indigo-200">
          <i class="fas fa-plane mr-1"></i> My Flights
        </a>
        <a href="mytickets.php" class="text-indigo-200 font-medium">
          <i class="fas fa-ticket-alt mr-1"></i> My Tickets
        </a>
      </nav>
      <div class="flex items-center space-x-4">
        <span class="text-indigo-200 text-sm">Welcome, <?php echo htmlspecialchars($user_info['full_name']); ?></span>
        
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="flex-1 container mx-auto px-4 py-8 max-w-7xl no-print">
    <!-- Page Title -->
    <div class="text-center mb-8 fade-in">
      <h1 class="text-3xl font-bold text-gray-800 mb-2">My E-Tickets</h1>
      <p class="text-gray-600">View and print your flight tickets (<?php echo count($tickets); ?> tickets found)</p>
    </div>

    <?php if (empty($tickets)): ?>
      <!-- Empty State -->
      <div class="bg-white rounded-xl shadow-md p-12 text-center fade-in">
        <i class="fas fa-ticket-alt text-6xl text-gray-300 mb-6"></i>
        <h3 class="text-2xl font-medium text-gray-700 mb-4">No E-Tickets Found</h3>
        <p class="text-gray-500 mb-6">You don't have any e-tickets issued yet. Book a flight to get started!</p>
        <a href="search-flights.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
          <i class="fas fa-search mr-2"></i>
          Search Flights
        </a>
      </div>
    <?php else: ?>
      <!-- Tickets Grid -->
      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        
        <?php foreach ($tickets as $ticket): ?>
          <?php
          $passengers = getPassengers($pdo, $ticket['booking_id']);
          $is_cancelled = strtolower($ticket['booking_status']) === 'cancelled';
          ?>
          
          <div class="bg-white rounded-xl shadow-md overflow-hidden ticket-card fade-in">
            <!-- Status Badge -->
            <div class="relative">
              <div class="<?php echo getStatusClass($ticket['booking_status']); ?> absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-medium z-10">
                <?php echo ucfirst($ticket['booking_status']); ?>
              </div>
              
              <!-- Header -->
              <div class="bg-indigo-600 text-white p-6 pb-4">
                <div class="flex items-center justify-between">
                  <div>
                    <h3 class="font-bold text-lg">E-Ticket</h3>
                    <p class="text-indigo-100 text-sm"><?php echo htmlspecialchars($ticket['booking_reference']); ?></p>
                  </div>
                  <i class="fas fa-ticket-alt text-2xl opacity-80"></i>
                </div>
              </div>
            </div>

            <!-- Flight Route -->
            <div class="p-6">
              <div class="flex items-center justify-between mb-6">
                <div class="text-center">
                  <div class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($ticket['origin_code']); ?></div>
                  <div class="text-sm text-gray-500"><?php echo htmlspecialchars($ticket['origin_city']); ?></div>
                  <div class="text-sm font-medium"><?php echo formatTime($ticket['departure_time']); ?></div>
                </div>
                
                <div class="flex flex-col items-center">
                  <div class="route-arrow"></div>
                  <div class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full text-xs mt-2">
                    <?php echo htmlspecialchars($ticket['flight_number']); ?>
                  </div>
                  <div class="text-xs text-gray-500 mt-1"><?php echo formatDuration($ticket['duration']); ?></div>
                </div>
                
                <div class="text-center">
                  <div class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($ticket['destination_code']); ?></div>
                  <div class="text-sm text-gray-500"><?php echo htmlspecialchars($ticket['destination_city']); ?></div>
                  <div class="text-sm font-medium"><?php echo formatTime($ticket['arrival_time']); ?></div>
                </div>
              </div>

              <!-- Flight Details -->
              <div class="border-t pt-4 mb-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <span class="text-gray-500">Airline:</span>
                    <div class="font-medium"><?php echo htmlspecialchars($ticket['airline_name']); ?></div>
                  </div>
                  <div>
                    <span class="text-gray-500">Amount:</span>
                    <div class="font-medium">₹<?php echo number_format($ticket['total_amount'], 2); ?></div>
                  </div>
                  <div>
                    <span class="text-gray-500">Travel Date:</span>
                    <div class="font-medium"><?php echo formatDate($ticket['travel_date']); ?></div>
                  </div>
                  <div>
                    <span class="text-gray-500">Passengers:</span>
                    <div class="font-medium"><?php echo count($passengers); ?></div>
                  </div>
                </div>
              </div>

              <!-- Passengers -->
              <?php if (!empty($passengers)): ?>
              <div class="mb-4">
                <div class="text-sm text-gray-500 mb-2">Passengers:</div>
                <div>
                  <?php foreach ($passengers as $passenger): ?>
                    <span class="passenger-tag passenger-<?php echo strtolower($passenger['gender']); ?>">
                      <?php echo htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?> 
                      (<?php echo $passenger['age']; ?>)
                      <?php if (!empty($passenger['seat_number'])): ?>
                        - <?php echo htmlspecialchars($passenger['seat_number']); ?>
                      <?php endif; ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>

              <!-- Action Button -->
              <?php if (!$is_cancelled): ?>
                <button onclick="printTicket('<?php echo $ticket['ticket_id']; ?>')" 
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 px-4 rounded-lg font-medium transition-colors flex items-center justify-center">
                  <i class="fas fa-print mr-2"></i>
                  Print E-Ticket
                </button>
              <?php else: ?>
                <button class="w-full bg-gray-400 text-white py-3 px-4 rounded-lg font-medium cursor-not-allowed flex items-center justify-center" disabled>
                  <i class="fas fa-ban mr-2"></i>
                  Ticket Cancelled
                </button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Help Section -->
    <div class="mt-8 bg-gray-50 border border-gray-200 rounded-xl p-6 fade-in">
      <h3 class="font-semibold text-gray-800 mb-3">
        <i class="fas fa-question-circle mr-2 text-gray-600"></i>Need Help?
      </h3>
      <div class="grid md:grid-cols-3 gap-4 text-sm">
        <div class="text-center">
          <i class="fas fa-phone text-2xl text-gray-600 mb-2"></i>
          <h4 class="font-medium text-gray-700">Call Support</h4>
          <p class="text-gray-600">1800-123-4567</p>
        </div>
        <div class="text-center">
          <i class="fas fa-envelope text-2xl text-gray-600 mb-2"></i>
          <h4 class="font-medium text-gray-700">Email Us</h4>
          <p class="text-gray-600">support@bookmyflight.com</p>
        </div>
        <div class="text-center">
          <i class="fas fa-comments text-2xl text-gray-600 mb-2"></i>
          <h4 class="font-medium text-gray-700">Live Chat</h4>
          <p class="text-gray-600">Available 24/7</p>
        </div>
      </div>
    </div>
  </main>

  <!-- Hidden Printable Ticket Container -->
  <div id="printable-ticket-container" class="hidden"></div>

  <!-- Footer -->
  <footer class="bg-gray-800 text-white py-6 mt-8 no-print">
    <div class="container mx-auto px-4 text-center">
      <p>&copy; 2024 BookMyFlight. All rights reserved.</p>
      <div class="mt-2 space-x-4">
        <a href="#" class="text-gray-400 hover:text-white text-sm">Terms & Conditions</a>
        <a href="#" class="text-gray-400 hover:text-white text-sm">Privacy Policy</a>
        <a href="#" class="text-gray-400 hover:text-white text-sm">Refund Policy</a>
      </div>
    </div>
  </footer>

  <script>
    // Ticket data for printing
    const ticketData = {
      <?php foreach ($tickets as $ticket): ?>
        <?php $passengers = getPassengers($pdo, $ticket['booking_id']); ?>
        '<?php echo $ticket['ticket_id']; ?>': {
          ticketId: '<?php echo $ticket['ticket_id']; ?>',
          bookingRef: '<?php echo htmlspecialchars($ticket['booking_reference']); ?>',
          from: '<?php echo htmlspecialchars($ticket['origin_code']); ?>',
          fromFull: '<?php echo htmlspecialchars($ticket['origin_airport_name']); ?>',
          fromCity: '<?php echo htmlspecialchars($ticket['origin_city']); ?>',
          to: '<?php echo htmlspecialchars($ticket['destination_code']); ?>',
          toFull: '<?php echo htmlspecialchars($ticket['destination_airport_name']); ?>',
          toCity: '<?php echo htmlspecialchars($ticket['destination_city']); ?>',
          flightNo: '<?php echo htmlspecialchars($ticket['flight_number']); ?>',
          airline: '<?php echo htmlspecialchars($ticket['airline_name']); ?>',
          date: '<?php echo formatDate($ticket['travel_date']); ?>',
          depTime: '<?php echo formatTime($ticket['departure_time']); ?>',
          arrTime: '<?php echo formatTime($ticket['arrival_time']); ?>',
          duration: '<?php echo formatDuration($ticket['duration']); ?>',
          amount: '₹<?php echo number_format($ticket['total_amount'], 2); ?>',
          status: '<?php echo ucfirst($ticket['booking_status']); ?>',
          contactEmail: '<?php echo htmlspecialchars($ticket['contact_email']); ?>',
          contactPhone: '<?php echo htmlspecialchars($ticket['contact_phone']); ?>',
          passengers: [
            <?php foreach($passengers as $passenger): ?>
            {
              name: '<?php echo htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?>',
              age: <?php echo $passenger['age']; ?>,
              gender: '<?php echo htmlspecialchars($passenger['gender']); ?>',
              seat: '<?php echo htmlspecialchars($passenger['seat_number'] ?? 'Not Assigned'); ?>'
            },
            <?php endforeach; ?>
          ]
        },
      <?php endforeach; ?>
    };

    function printTicket(ticketId) {
      const ticket = ticketData[ticketId];
      if (!ticket) {
        alert('Ticket not found');
        return;
      }
      
      // Prevent printing cancelled tickets
      if (ticket.status.toLowerCase() === 'cancelled') {
        alert('Cannot print a cancelled ticket');
        return;
      }
      
      // Build passenger rows
      let passengerRows = '';
      ticket.passengers.forEach((passenger, index) => {
        const bgClass = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
        passengerRows += `
          <tr class="${bgClass}">
            <td class="p-4 font-medium">${passenger.name}</td>
            <td class="p-4">${passenger.age}</td>
            <td class="p-4 capitalize">${passenger.gender}</td>
            <td class="p-4 font-bold text-indigo-600">${passenger.seat}</td>
          </tr>
        `;
      });

      const printableHtml = `
        <div class="printable-ticket p-8 max-w-4xl mx-auto bg-white">
          <!-- Header with Logo -->
          <div class="border-b-2 border-indigo-600 pb-6 mb-6">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-indigo-600 rounded-xl flex items-center justify-center">
                  <i class="fas fa-plane text-white text-2xl"></i>
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-gray-900">BookMyFlight</h1>
                  <p class="text-gray-600">Your Travel Partner</p>
                </div>
              </div>
              <div class="text-right">
                <h2 class="text-2xl font-bold text-indigo-600">E-TICKET</h2>
                <p class="text-gray-600">Booking Reference: ${ticket.bookingRef}</p>
                <p class="text-gray-600">Ticket ID: ${ticket.ticketId}</p>
              </div>
            </div>
          </div>

          <!-- Flight Information -->
          <div class="bg-gray-50 rounded-lg p-6 mb-6">
            <h3 class="text-xl font-bold mb-4 text-gray-800">Flight Details</h3>
            
            <!-- Route -->
            <div class="flex items-center justify-between mb-6">
              <div class="text-center">
                <div class="text-4xl font-bold text-indigo-600">${ticket.from}</div>
                <div class="text-lg text-gray-700">${ticket.fromFull}</div>
                <div class="text-md text-gray-600">${ticket.fromCity}</div>
                <div class="text-lg font-semibold mt-2">${ticket.depTime}</div>
                <div class="text-sm text-gray-500">Departure</div>
              </div>
              
              <div class="flex flex-col items-center px-8">
                <div class="w-24 h-1 bg-indigo-600 relative">
                  <div class="absolute -right-2 -top-2 w-0 h-0 border-l-8 border-l-indigo-600 border-t-4 border-t-transparent border-b-4 border-b-transparent"></div>
                </div>
                <div class="bg-indigo-600 text-white px-4 py-2 rounded-full font-bold mt-3">
                  ${ticket.flightNo}
                </div>
                <div class="text-gray-600 mt-2">${ticket.duration}</div>
              </div>
              
              <div class="text-center">
                <div class="text-4xl font-bold text-indigo-600">${ticket.to}</div>
                <div class="text-lg text-gray-700">${ticket.toFull}</div>
                <div class="text-md text-gray-600">${ticket.toCity}</div>
                <div class="text-lg font-semibold mt-2">${ticket.arrTime}</div>
                <div class="text-sm text-gray-500">Arrival</div>
              </div>
            </div>

            <!-- Flight Info Grid -->
            <div class="grid grid-cols-4 gap-6 border-t pt-4">
              <div>
                <div class="text-gray-600 text-sm">Airline</div>
                <div class="font-bold text-lg">${ticket.airline}</div>
              </div>
              <div>
                <div class="text-gray-600 text-sm">Travel Date</div>
                <div class="font-bold text-lg">${ticket.date}</div>
              </div>
              <div>
                <div class="text-gray-600 text-sm">Total Amount</div>
                <div class="font-bold text-lg">${ticket.amount}</div>
              </div>
              <div>
                <div class="text-gray-600 text-sm">Status</div>
                <div class="font-bold text-lg text-green-600">${ticket.status}</div>
              </div>
            </div>
          </div>

          <!-- Passenger Details -->
          <div class="mb-6">
            <h3 class="text-xl font-bold mb-4 text-gray-800">Passenger Details</h3>
            <div class="border rounded-lg overflow-hidden">
              <table class="w-full">
                <thead class="bg-indigo-50">
                  <tr>
                    <th class="text-left p-4 font-semibold">Passenger Name</th>
                    <th class="text-left p-4 font-semibold">Age</th>
                    <th class="text-left p-4 font-semibold">Gender</th>
                    <th class="text-left p-4 font-semibold">Seat Number</th>
                  </tr>
                </thead>
                <tbody>
                  ${passengerRows}
                </tbody>
              </table>
            </div>
          </div>

          <!-- Contact Information -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h4 class="font-bold text-blue-800 mb-2">Contact Information:</h4>
            <div class="grid grid-cols-2 gap-4 text-sm text-blue-700">
              <div>Email: ${ticket.contactEmail}</div>
              <div>Phone: ${ticket.contactPhone}</div>
            </div>
          </div>

          <!-- Important Information -->
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <h4 class="font-bold text-yellow-800 mb-2">Important Information:</h4>
            <ul class="text-sm text-yellow-700 space-y-1">
              <li>• Please arrive at the airport at least 2 hours before domestic flights and 3 hours before international flights</li>
              <li>• Carry a valid photo ID and this e-ticket for check-in</li>
              <li>• Baggage allowances and restrictions apply as per airline policy</li>
              <li>• Check-in online to save time at the airport</li>
              <li>• This is a computer-generated ticket and does not require a signature</li>
            </ul>
          </div>

          <!-- Footer -->
          <div class="border-t pt-6 text-center text-gray-600">
            <div class="flex items-center justify-center space-x-4 mb-4">
              <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-plane text-white"></i>
              </div>
              <span class="font-bold text-gray-800">BookMyFlight</span>
            </div>
            <p class="text-sm">Thank you for choosing BookMyFlight. Have a pleasant journey!</p>
            <p class="text-xs text-gray-500 mt-2">Printed on: ${new Date().toLocaleString()}</p>
          </div>
        </div>
      `;

      // Create and show printable version
      const container = document.getElementById('printable-ticket-container');
      container.innerHTML = printableHtml;
      container.classList.remove('hidden');
      
      // Print
      window.print();
      
      // Hide container after printing
      setTimeout(() => {
        container.classList.add('hidden');
      }, 100);
    }
  </script>
</body>
</html>