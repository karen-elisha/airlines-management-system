<?php
session_start();
require_once 'db_connect.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=payment.php');
    exit;
}

// Check if booking ID is set in session
if (!isset($_SESSION['current_booking_id'])) {
    $_SESSION['booking_error'] = "No booking found. Please start again.";
    header('Location: booking-forms.php');
    exit;
}

function generateRandomSeatNumber() {
    $rows = range(1, 30);
    $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
    $row = $rows[array_rand($rows)];
    $letter = $letters[array_rand($letters)];
    return $row . $letter;
}

function generateUniqueSeats($numPassengers, $existingSeats = []) {
    $generatedSeats = [];
    $allExistingSeats = array_merge($existingSeats, $generatedSeats);
    while (count($generatedSeats) < $numPassengers) {
        $seat = generateRandomSeatNumber();
        if (!in_array($seat, $allExistingSeats)) {
            $generatedSeats[] = $seat;
            $allExistingSeats[] = $seat;
        }
    }
    return $generatedSeats;
}

function updatePassengerSeats($mysqli, $booking_id, $seatNumbers) {
    $sql = "SELECT passenger_id FROM passengers WHERE booking_id = ? ORDER BY passenger_id";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $passenger_ids = [];
    while ($row = $result->fetch_assoc()) {
        $passenger_ids[] = $row['passenger_id'];
    }
    for ($i = 0; $i < count($passenger_ids) && $i < count($seatNumbers); $i++) {
        $sql = "UPDATE passengers SET seat_number = ? WHERE passenger_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("si", $seatNumbers[$i], $passenger_ids[$i]);
        if (!$stmt->execute()) {
            throw new Exception("Failed to assign seat: " . $stmt->error);
        }
    }
}

// Initialize variables
$payment_processed = false;
$payment_method = '';
$error_message = '';
$payment_details = '';
$processing_payment = false;

$booking_id = (int)($_SESSION['current_booking_id'] ?? 0);
$booking_reference = $_SESSION['booking_reference'] ?? '';
$total_amount = (float)($_SESSION['total_amount'] ?? 0);
$num_passengers = (int)($_SESSION['num_passengers'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($booking_id <= 0) {
    $_SESSION['booking_error'] = "Invalid booking reference";
    header('Location: booking-forms.php');
    exit;
}

// Process payment if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['payment_method']) && !isset($_POST['payment_details_submitted'])) {
            $payment_method = trim($_POST['payment_method']);
            if (empty($payment_method)) {
                throw new Exception("Please select a payment method");
            }
            $allowed_methods = ['upi', 'card', 'netbanking', 'wallet'];
            if (!in_array($payment_method, $allowed_methods)) {
                throw new Exception("Invalid payment method selected");
            }
            $_SESSION['payment_method'] = $payment_method;
            $processing_payment = false;
        } else if (isset($_POST['payment_details_submitted'])) {
            $payment_method = $_SESSION['payment_method'] ?? '';
            if (empty($payment_method)) {
                throw new Exception("Payment method not found");
            }
            switch ($payment_method) {
                case 'upi':
                    if (empty($_POST['upi_id'])) throw new Exception("UPI ID is required");
                    $payment_details = $_POST['upi_id'];
                    break;
                case 'card':
                    if (empty($_POST['card_number']) || empty($_POST['card_expiry']) || empty($_POST['card_cvv'])) {
                        throw new Exception("All card details are required");
                    }
                    $card_number = preg_replace('/\s+/', '', $_POST['card_number']);
                    $last_four = substr($card_number, -4);
                    $payment_details = "xxxx-xxxx-xxxx-" . $last_four;
                    break;
                case 'netbanking':
                    if (empty($_POST['bank_name'])) throw new Exception("Bank selection is required");
                    $payment_details = $_POST['bank_name'];
                    break;
                case 'wallet':
                    if (empty($_POST['wallet_name'])) throw new Exception("Wallet selection is required");
                    $payment_details = $_POST['wallet_name'];
                    break;
                default:
                    throw new Exception("Invalid payment method");
            }

            $mysqli->begin_transaction();

            // Generate and assign seat numbers if not already assigned
            if (!isset($_SESSION['seats_assigned'])) {
                $seatNumbers = generateUniqueSeats($num_passengers);
                updatePassengerSeats($mysqli, $booking_id, $seatNumbers);
                $_SESSION['seats_assigned'] = true;
            }

            // Get flight_id for updating seats
            $sql = "SELECT flight_id FROM bookings WHERE booking_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $flight_id = $row['flight_id'];

            // Update booking payment status
            $sql = "UPDATE bookings SET 
                    payment_status = 'completed', 
                    payment_method = ?,
                    payment_details = ?,
                    updated_at = NOW() 
                    WHERE booking_id = ?";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) throw new Exception("Database error: " . $mysqli->error);
            if (!$stmt->bind_param("ssi", $payment_method, $payment_details, $booking_id)) {
                throw new Exception("Failed to bind parameters: " . $stmt->error);
            }
            if (!$stmt->execute()) throw new Exception("Failed to update booking: " . $stmt->error);

            // Update available seats
            $sql_update_seats = "UPDATE flights SET available_seats = available_seats - ? WHERE flight_id = ?";
            $stmt_update_seats = $mysqli->prepare($sql_update_seats);
            if (!$stmt_update_seats) throw new Exception("Database error: " . $mysqli->error);
            if (!$stmt_update_seats->bind_param("ii", $num_passengers, $flight_id)) {
                throw new Exception("Failed to bind parameters: " . $stmt_update_seats->error);
            }
            if (!$stmt_update_seats->execute()) throw new Exception("Failed to update available seats: " . $stmt_update_seats->error);
            $stmt_update_seats->close();

            // --- NEW: Generate and store ticket in generated_tickets table ---
            // Get flight details
            $sql = "SELECT b.*, f.airline_id, f.flight_number, f.origin_airport, f.destination_airport, 
                    f.departure_time, f.arrival_time, f.duration, f.flight_id, b.travel_date
                    FROM bookings b 
                    JOIN flights f ON b.flight_id = f.flight_id 
                    WHERE b.booking_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $flight_details = $result->fetch_assoc();
            if (empty($flight_details)) throw new Exception("Flight details not found");

            // Get passenger details
            $sql = "SELECT * FROM passengers WHERE booking_id = ? ORDER BY passenger_id";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $passenger_details = [];
            while ($row = $result->fetch_assoc()) {
                $passenger_details[] = $row;
            }

            // Generate ticket HTML for storage
            ob_start();
            ?>
            <div>
              <h2>BookMyFlight Ticket</h2>
              <strong>Booking Reference:</strong> <?php echo htmlspecialchars($booking_reference); ?><br>
              <strong>Flight:</strong> <?php echo htmlspecialchars($flight_details['airline_id'] . '-' . $flight_details['flight_number']); ?><br>
              <strong>Date:</strong> <?php echo date('d M Y', strtotime($flight_details['travel_date'])); ?><br>
              <strong>From:</strong> <?php echo htmlspecialchars($flight_details['origin_airport']); ?> 
              <strong>To:</strong> <?php echo htmlspecialchars($flight_details['destination_airport']); ?><br>
              <strong>Departure:</strong> <?php echo date('H:i', strtotime($flight_details['departure_time'])); ?> 
              <strong>Arrival:</strong> <?php echo date('H:i', strtotime($flight_details['arrival_time'])); ?><br>
              <strong>Passengers:</strong>
              <ul>
                <?php foreach($passenger_details as $p): ?>
                  <li><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?> (Seat: <?php echo htmlspecialchars($p['seat_number']); ?>)</li>
                <?php endforeach; ?>
              </ul>
            </div>
            <?php
            $ticket_html = ob_get_clean();

            // Insert into generated_tickets table
            $sql = "INSERT INTO generated_tickets 
                (booking_id, user_id, booking_reference, ticket_html, flight_details, passenger_details, status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) throw new Exception("Database error: " . $mysqli->error);
            $flight_details_json = json_encode($flight_details);
            $passenger_details_json = json_encode($passenger_details);
            $stmt->bind_param(
                "iissss",
                $booking_id,
                $user_id,
                $booking_reference,
                $ticket_html,
                $flight_details_json,
                $passenger_details_json
            );
            if (!$stmt->execute()) throw new Exception("Failed to store generated ticket: " . $stmt->error);

            // Commit transaction
            $mysqli->commit();

            $payment_processed = true;
            $processing_payment = true;
            $_SESSION['payment_status'] = 'completed';
            unset($_SESSION['payment_method']);
        }
    } catch (Exception $e) {
        if (isset($mysqli) && $mysqli instanceof mysqli) {
            $mysqli->rollback();
        }
        $error_message = $e->getMessage();
        error_log("Payment Error: " . $error_message);
        $_SESSION['payment_error'] = $error_message;
    }
}

// ... (rest of your HTML and frontend code remains unchanged)
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Method | BookMyFlight</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .payment-card { transition: all 0.2s ease; }
    .payment-card:hover { transform: translateY(-2px); }
    .modal-content { max-width: 28rem; }
    
    /* Payment Processing Animation */
    .payment-processing {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.9);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      z-index: 9999;
    }
    
    .spinner {
      width: 50px;
      height: 50px;
      border: 5px solid #e0e0e0;
      border-top: 5px solid #4f46e5;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    
    .progress-bar {
      width: 300px;
      height: 6px;
      background-color: #e0e0e0;
      border-radius: 3px;
      margin: 20px 0;
      overflow: hidden;
    }
    
    .progress {
      height: 100%;
      width: 0%;
      background-color: #4f46e5;
      transition: width 0.1s ease;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body class="bg-gray-50 font-sans">

  <!-- Header -->
 <header class="bg-gray-800 text-white shadow-md">
  <div class="container mx-auto px-4 py-4 flex items-center justify-between">
    <div class="flex items-center space-x-2">
      <i class="fas fa-plane text-white text-xl"></i>
      <a href="index.php" class="text-xl font-bold">BookMyFlight</a>
    </div>
    <a href="user-dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center">
      <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
    </a>
  </div>
</header>
      <div class="ml-auto">
        <?php if(isset($_SESSION['username'])): ?>
        <div class="text-sm">
          <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <?php if ($payment_processed): ?>
    <!-- Payment Processing Animation -->
    <div id="paymentProcessing" class="payment-processing">
      <div class="spinner"></div>
      <div class="progress-bar">
        <div id="progressBar" class="progress"></div>
      </div>
      <div id="processingStatus" class="text-center mt-4">
        <p class="font-medium text-gray-700">Processing your payment...</p>
        <p class="text-sm text-gray-500">Please do not refresh the page</p>
      </div>
    </div>
    

          
    <!-- Ticket Confirmation -->
<main id="ticketConfirmation" class="container mx-auto px-4 py-8 max-w-4xl" style="display: none;">
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Ticket Header -->
        <div class="bg-gradient-to-r from-indigo-800 to-blue-600 text-white p-6">
          <div class="flex justify-between items-center">
            <div class="flex items-center">
              <img src="https://img.icons8.com/ios-filled/50/ffffff/airport.png" alt="Airport Logo" class="h-10 w-10 mr-3">
              <div>
                <h1 class="text-2xl font-bold">BookMyFlight</h1>
                <p class="text-sm font-light">Your journey begins here</p>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-light">Booking Reference</div>
              <div class="text-xl font-bold"><?php echo htmlspecialchars($booking_reference); ?></div>
            </div>
          </div>
        </div>
    
    <!-- Ticket Content -->
    <div class="p-6">
      <div class="flex flex-col lg:flex-row">
        <!-- Left Column -->
        <div class="flex-1 mb-6 lg:mb-0 lg:mr-6">
          <!-- Flight Info -->
          <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">Flight Information</h2>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="text-sm text-gray-500">Flight Number</span>
                <p class="font-semibold text-lg"><?php echo htmlspecialchars($flight_details['airline_id'] . '-' . $flight_details['flight_number']); ?></p>
              </div>
              <div>
                <span class="text-sm text-gray-500">Travel Date</span>
                <p class="font-semibold"><?php echo date('d M Y', strtotime($flight_details['travel_date'])); ?></p>
              </div>
              <div class="col-span-2">
                <div class="flex items-center my-3">
                  <div class="text-center mr-4">
                    <div class="text-sm font-medium"><?php echo htmlspecialchars($flight_details['origin_airport']); ?></div>
                    <div class="text-xl font-bold"><?php echo date('H:i', strtotime($flight_details['departure_time'])); ?></div>
                  </div>
                  <div class="flex-1 px-4">
                    <div class="relative">
                      <div class="border-t-2 border-gray-300 border-dashed"></div>
                      <div class="absolute inset-0 flex justify-center items-center">
                        <div class="bg-white px-2">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                          </svg>
                        </div>
                      </div>
                    </div>
                    <div class="text-center text-xs text-gray-500 mt-1">Duration: <?php echo htmlspecialchars($flight_details['duration']); ?></div>
                  </div>
                  <div class="text-center ml-4">
                    <div class="text-sm font-medium"><?php echo htmlspecialchars($flight_details['destination_airport']); ?></div>
                    <div class="text-xl font-bold"><?php echo date('H:i', strtotime($flight_details['arrival_time'])); ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Passenger Details -->
          <div>
            <h2 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">Passenger Details</h2>
            <div class="overflow-auto">
              <table class="min-w-full">
                <thead>
                  <tr class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <th class="py-2 px-3 text-left">Name</th>
                    <th class="py-2 px-3 text-left">Gender</th>
                    <th class="py-2 px-3 text-left">Age</th>
                    <th class="py-2 px-3 text-left">Seat</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($passenger_details as $index => $passenger): ?>
                  <tr class="border-b">
                    <td class="py-3 px-3">
                      <div class="font-medium"><?php echo htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?></div>
                    </td>
                    <td class="py-3 px-3"><?php echo htmlspecialchars($passenger['gender']); ?></td>
                    <td class="py-3 px-3"><?php echo htmlspecialchars($passenger['age']); ?></td>
                    <td class="py-3 px-3"><?php echo htmlspecialchars($passenger['seat_number']); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        <!-- Right Column - Boarding Information -->
        <div class="w-full lg:w-80 border-t lg:border-t-0 lg:border-l pt-6 lg:pt-0 lg:pl-6">
          <h2 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">Boarding Information</h2>
          
          <!-- Barcode -->
          <div class="flex justify-center mb-6">
            <svg id="barcode" class="h-24"></svg>
          </div>
          
          <div class="space-y-4">
            <div>
              <span class="text-sm text-gray-500 block">Check-in Opens</span>
              <p class="font-medium"><?php echo date('d M Y • H:i', strtotime($flight_details['departure_time'] . ' -3 hours')); ?></p>
            </div>
            
            <div>
              <span class="text-sm text-gray-500 block">Gate Closes</span>
              <p class="font-medium"><?php echo date('d M Y • H:i', strtotime($flight_details['departure_time'] . ' -30 minutes')); ?></p>
            </div>
            
            <div>
              <span class="text-sm text-gray-500 block">Baggage Allowance</span>
              <p class="font-medium">Check-in: 20kg | Cabin: 7kg</p>
            </div>
            
            <div>
              <span class="text-sm text-gray-500 block">Terminal</span>
              <p class="font-medium">T3 International</p>
            </div>
            
            <div class="bg-gray-50 p-3 rounded-md mt-6">
              <div class="text-sm text-center text-gray-500">
                <p><strong>Important:</strong> Please arrive at the airport at least 3 hours before departure for international flights and 2 hours for domestic flights.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Ticket Footer -->
    <div class="bg-gray-50 p-6 flex flex-col lg:flex-row justify-between items-center">
      <div class="text-center lg:text-left mb-4 lg:mb-0">
        <p class="text-gray-500 text-sm">This is an electronic ticket. Please show this ticket along with a government-issued photo ID at the airport.</p>
      </div>
      <div class="flex space-x-2">
        <button onclick="printTicket()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          Print Ticket
        </button>
        <button onclick="window.location.href='mytickets.php'" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          View All Bookings
        </button>
      </div>
    </div>
  </div>
</main>

<!-- Add JsBarcode for generating the barcode -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
  // Generate barcode using booking reference
  JsBarcode("#barcode", "<?php echo $booking_reference; ?>", {
    format: "CODE128",
    lineColor: "#4F46E5",
    width: 2,
    height: 50,
    displayValue: false
  });
  
  // Print ticket function
  function printTicket() {
    window.print();
  }
</script>

<!-- Add print media styles -->
<style>
  @media print {
    body * {
      visibility: hidden;
    }
    main, main * {
      visibility: visible;
    }
    main {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
    }
    button {
      display: none !important;
    }
  }
</style>
  <?php elseif (isset($payment_method) && !empty($payment_method) && !$processing_payment): ?>
    <!-- Payment Details Form -->
    <main class="container mx-auto px-4 py-8 max-w-md">
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-center text-gray-800 mb-6">Enter Payment Details</h2>
        
        <!-- Booking Summary -->
        <div class="mb-6 p-4 bg-gray-50 rounded-md">
          <h3 class="font-semibold mb-2">Booking Summary</h3>
          <div class="flex justify-between text-sm mb-1">
            <span>Booking Reference:</span>
            <span class="font-medium"><?php echo htmlspecialchars($booking_reference); ?></span>
          </div>
          <div class="flex justify-between text-sm mb-1">
            <span>Passengers:</span>
            <span class="font-medium"><?php echo htmlspecialchars($num_passengers); ?></span>
          </div>
          <div class="flex justify-between text-sm mb-1">
            <span>Payment Method:</span>
            <span class="font-medium capitalize"><?php echo htmlspecialchars($payment_method); ?></span>
          </div>
          <div class="flex justify-between font-semibold text-base border-t border-gray-200 pt-2 mt-2">
            <span>Total Amount:</span>
            <span>₹<?php echo number_format($total_amount, 2); ?></span>
          </div>
        </div>
        
        <!-- Error message if any -->
        <?php if (!empty($error_message)): ?>
          <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-md text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error_message); ?>
          </div>
        <?php endif; ?>
        
        <form method="post" action="" id="paymentDetailsForm">
          <input type="hidden" name="payment_details_submitted" value="1">
          
          <?php if ($payment_method === 'upi'): ?>
            <!-- UPI Payment Form -->
            <div class="mb-4">
              <label for="upi_id" class="block text-sm font-medium text-gray-700 mb-1">UPI ID</label>
              <div class="flex">
                <input type="text" id="upi_id" name="upi_id" placeholder="username@upi" 
                       class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
              </div>
              <p class="text-xs text-gray-500 mt-1">Example: yourname@okaxis, yourname@ybl</p>
            </div>
          
          <?php elseif ($payment_method === 'card'): ?>
            <!-- Credit/Debit Card Form -->
            <div class="mb-4">
              <label for="card_number" class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
              <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" 
                     class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                     maxlength="19" required>
            </div>
            
            <div class="flex justify-between mb-4">
              <div class="w-1/2 pr-2">
                <label for="card_expiry" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY" 
                       class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       maxlength="5" required>
              </div>
              <div class="w-1/2 pl-2">
                <label for="card_cvv" class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                <input type="password" id="card_cvv" name="card_cvv" placeholder="123" 
                       class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       maxlength="4" required>
              </div>
            </div>
            
            <div class="mb-4">
              <label for="card_name" class="block text-sm font-medium text-gray-700 mb-1">Name on Card</label>
              <input type="text" id="card_name" name="card_name" placeholder="John Doe" 
                     class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
            </div>
          
          <?php elseif ($payment_method === 'netbanking'): ?>
            <!-- Net Banking Form -->
            <div class="mb-4">
              <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">Select Bank</label>
              <select id="bank_name" name="bank_name" 
                      class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                <option value="">Select a Bank</option>
                <option value="SBI">State Bank of India</option>
                <option value="HDFC">HDFC Bank</option>
                <option value="ICICI">ICICI Bank</option>
                <option value="AXIS">Axis Bank</option>
                <option value="PNB">Punjab National Bank</option>
                <option value="BOB">Bank of Baroda</option>
                <option value="Canara">Canara Bank</option>
                <option value="Others">Other Banks</option>
              </select>
            </div>
          
          <?php elseif ($payment_method === 'wallet'): ?>
            <!-- Digital Wallet Form -->
            <div class="mb-4">
              <label for="wallet_name" class="block text-sm font-medium text-gray-700 mb-1">Select Wallet</label>
              <select id="wallet_name" name="wallet_name" 
                      class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                <option value="">Select a Wallet</option>
                <option value="Paytm">Paytm</option>
                <option value="Amazon Pay">Amazon Pay</option>
                <option value="PhonePe">PhonePe</option>
                <option value="Google Pay">Google Pay</option>
                <option value="MobiKwik">MobiKwik</option>
              </select>
            </div>
          <?php endif; ?>
          
          <div class="mt-6 flex items-center justify-between">
            <a href="payment.php" class="text-indigo-600 hover:text-indigo-800">
              <i class="fas fa-arrow-left mr-1"></i> Change Method
            </a>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
              Pay ₹<?php echo number_format($total_amount, 2); ?>
            </button>
          </div>
          
          <div class="mt-6 text-center text-xs text-gray-500">
            <i class="fas fa-lock mr-1"></i> Secure payment gateway
          </div>
        </form>
      </div>
    </main>
  <?php else: ?>
    <!-- Payment Method Selection - This part remains unchanged -->
    <main class="container mx-auto px-4 py-8 max-w-md">
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-center text-gray-800 mb-6">Select Payment Method</h2>
        
        <!-- Booking Summary -->
        <div class="mb-6 p-4 bg-gray-50 rounded-md">
          <h3 class="font-semibold mb-2">Booking Summary</h3>
          <div class="flex justify-between text-sm mb-1">
            <span>Booking Reference:</span>
            <span class="font-medium"><?php echo htmlspecialchars($booking_reference); ?></span>
          </div>
          <div class="flex justify-between text-sm mb-1">
            <span>Passengers:</span>
            <span class="font-medium"><?php echo htmlspecialchars($num_passengers); ?></span>
          </div>
          <div class="flex justify-between font-semibold text-base border-t border-gray-200 pt-2 mt-2">
            <span>Total Amount:</span>
            <span>₹<?php echo number_format($total_amount, 2); ?></span>
          </div>
        </div>
        
        <!-- Error message if any -->
        <?php if (!empty($error_message)): ?>
          <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-md text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error_message); ?>
          </div>
        <?php endif; ?>
        
        <form method="post" action="">
          <div class="space-y-3">
            <label class="payment-card block cursor-pointer">
              <input type="radio" name="payment_method" value="upi" class="hidden" required>
              <div class="w-full p-3 bg-white border-2 border-gray-300 hover:border-indigo-600 rounded-md flex items-center justify-between">
                <div class="flex items-center">
                  <i class="fas fa-mobile-alt text-indigo-600 mr-2"></i> UPI Payment
                </div>
                <i class="fas fa-check-circle text-indigo-600 opacity-0 check-icon"></i>
              </div>
            </label>
            
            <label class="payment-card block cursor-pointer">
              <input type="radio" name="payment_method" value="card" class="hidden">
              <div class="w-full p-3 bg-white border-2 border-gray-300 hover:border-indigo-600 rounded-md flex items-center justify-between">
                <div class="flex items-center">
                  <i class="far fa-credit-card text-indigo-600 mr-2"></i> Credit/Debit Card
                </div>
                <i class="fas fa-check-circle text-indigo-600 opacity-0 check-icon"></i>
              </div>
            </label>
            
            <label class="payment-card block cursor-pointer">
              <input type="radio" name="payment_method" value="netbanking" class="hidden">
              <div class="w-full p-3 bg-white border-2 border-gray-300 hover:border-indigo-600 rounded-md flex items-center justify-between">
                <div class="flex items-center">
                  <i class="fas fa-university text-indigo-600 mr-2"></i> Net Banking
                </div>
                <i class="fas fa-check-circle text-indigo-600 opacity-0 check-icon"></i>
              </div>
            </label>
            
            <label class="payment-card block cursor-pointer">
              <input type="radio" name="payment_method" value="wallet" class="hidden">
              <div class="w-full p-3 bg-white border-2 border-gray-300 hover:border-indigo-600 rounded-md flex items-center justify-between">
                <div class="flex items-center">
                  <i class="fas fa-wallet text-indigo-600 mr-2"></i> Digital Wallets
                </div>
                <i class="fas fa-check-circle text-indigo-600 opacity-0 check-icon"></i>
              </div>
            </label>
          </div>

          <div class="mt-6 flex items-center justify-between">
            <a href="javascript:history.back()" class="text-indigo-600 hover:text-indigo-800">
              <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
              Continue
            </button>
          </div>

          <div class="mt-6 text-center text-xs text-gray-500">
            <i class="fas fa-lock mr-1"></i> Secure payment gateway
          </div>
        </form>
      </div>
    </main>
  <?php endif; ?>

  <!-- Footer -->
  <footer class="text-center text-sm text-gray-500 mt-8 py-4 border-t">
    © 2025 BookMyFlight. All rights reserved.
  </footer>

  <script>
    // Card formatting
    document.addEventListener('DOMContentLoaded', function() {
      // Format card number with spaces
      const cardNumberInput = document.getElementById('card_number');
      if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
          let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
          let formattedValue = '';
          
          for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
              formattedValue += ' ';
            }
            formattedValue += value[i];
          }
          
          e.target.value = formattedValue;
        });
      }
      
      // Format expiry date with slash
      const expiryInput = document.getElementById('card_expiry');
      if (expiryInput) {
        expiryInput.addEventListener('input', function(e) {
          let value = e.target.value.replace(/\D/g, '');
          
          if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
          }
          
          e.target.value = value;
        });
      }
    });
    
    // Payment card selection
    document.querySelectorAll('input[name="payment_method"]').forEach(input => {
      input.addEventListener('change', function() {
        // Reset all cards
        document.querySelectorAll('.payment-card .check-icon').forEach(icon => {
          icon.classList.add('opacity-0');
        });
        document.querySelectorAll('.payment-card div').forEach(card => {
          card.classList.remove('border-indigo-600');
          card.classList.add('border-gray-300');
        });
        
        // Highlight selected card
        if (this.checked) {
          const card = this.parentElement.querySelector('div');
          const icon = this.parentElement.querySelector('.check-icon');
          card.classList.remove('border-gray-300');
          card.classList.add('border-indigo-600');
          icon.classList.remove('opacity-0');
        }
      });
    });
    
    // Payment processing animation
document.addEventListener('DOMContentLoaded', function() {
  // For payment processing animation
  const paymentProcessing = document.getElementById('paymentProcessing');
  const progressBar = document.getElementById('progressBar');
  const processingStatus = document.getElementById('processingStatus');
  const ticketConfirmation = document.getElementById('ticketConfirmation');
  
  if (paymentProcessing && progressBar && processingStatus) {
    let progress = 0;
    const interval = setInterval(() => {
      progress += 5;
      progressBar.style.width = progress + '%';
      
      if (progress === 30) {
        processingStatus.innerHTML = '<p class="font-medium text-gray-700">Verifying payment details...</p><p class="text-sm text-gray-500">Please wait</p>';
      } else if (progress === 60) {
        processingStatus.innerHTML = '<p class="font-medium text-gray-700">Payment successful!</p><p class="text-sm text-gray-500">Generating your ticket...</p>';
      } else if (progress >= 100) {
        clearInterval(interval);
        setTimeout(() => {
          if (paymentProcessing) paymentProcessing.style.display = 'none';
          if (ticketConfirmation) ticketConfirmation.style.display = 'block';
        }, 500);
      }
    }, 100);
  }
  
  // Payment method selection highlighting
  document.querySelectorAll('input[name="payment_method"]').forEach(input => {
    input.addEventListener('change', function() {
      // Reset all cards
      document.querySelectorAll('.payment-card .check-icon').forEach(icon => {
        icon.classList.add('opacity-0');
      });
      document.querySelectorAll('.payment-card div').forEach(card => {
        card.classList.remove('border-indigo-600');
        card.classList.add('border-gray-300');
      });
      
      // Highlight selected card
      if (this.checked) {
        const card = this.parentElement.querySelector('div');
        const icon = this.parentElement.querySelector('.check-icon');
        if (card && icon) {
          card.classList.remove('border-gray-300');
          card.classList.add('border-indigo-600');
          icon.classList.remove('opacity-0');
        }
      }
    });
  });
  
  // Add initial check for preselected radio buttons
  const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked');
  if (selectedPaymentMethod) {
    const card = selectedPaymentMethod.parentElement.querySelector('div');
    const icon = selectedPaymentMethod.parentElement.querySelector('.check-icon');
    if (card && icon) {
      card.classList.remove('border-gray-300');
      card.classList.add('border-indigo-600');
      icon.classList.remove('opacity-0');
    }
  }

  // Format card inputs if they exist
  const cardNumberInput = document.getElementById('card_number');
  if (cardNumberInput) {
    cardNumberInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
      let formattedValue = '';
      
      for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 4 === 0) {
          formattedValue += ' ';
        }
        formattedValue += value[i];
      }
      
      e.target.value = formattedValue;
    });
  }
  
  // Format expiry date with slash
  const expiryInput = document.getElementById('card_expiry');
  if (expiryInput) {
    expiryInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      
      if (value.length > 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
      }
      
      e.target.value = value;
    });
  }
  
  // Print ticket function
  window.printTicket = function() {
    window.print();
  };
});
  </script>
</body>
</html>