<?php
// Start session and enable error reporting
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

// Include database connection
require_once 'db_connect.php';

// Initialize variables
$payment_processed = false;
$payment_method = '';
$error_message = '';

// Get booking details from session - use current_booking_id consistently
$booking_id = (int)($_SESSION['current_booking_id'] ?? 0);
$booking_reference = $_SESSION['booking_reference'] ?? '';
$total_amount = (float)($_SESSION['total_amount'] ?? 0);
$num_passengers = (int)($_SESSION['num_passengers'] ?? 0);

// Validate booking ID
if ($booking_id <= 0) {
    $_SESSION['booking_error'] = "Invalid booking reference";
    header('Location: booking-forms.php');
    exit;
}

// Process payment if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    try {
        $payment_method = trim($_POST['payment_method']);
        
        if (empty($payment_method)) {
            throw new Exception("Please select a payment method");
        }

        // Validate payment method
        $allowed_methods = ['upi', 'card', 'netbanking', 'wallet'];
        if (!in_array($payment_method, $allowed_methods)) {
            throw new Exception("Invalid payment method selected");
        }

        // Begin transaction
        $mysqli->begin_transaction();

        // Update booking payment status
        $sql = "UPDATE bookings SET 
                payment_status = 'completed', 
                payment_method = ?,
                updated_at = NOW() 
                WHERE booking_id = ?";
        
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $mysqli->error);
        }

        // Bind parameters
        if (!$stmt->bind_param("si", $payment_method, $booking_id)) {
            throw new Exception("Failed to bind parameters: " . $stmt->error);
        }

        // Execute update
        if (!$stmt->execute()) {
            throw new Exception("Failed to update booking: " . $stmt->error);
        }

        // Commit transaction
        $mysqli->commit();
        $payment_processed = true;
        
        // Update session
        $_SESSION['payment_status'] = 'completed';
        
    } catch (Exception $e) {
        // Rollback on error
        if (isset($mysqli) && $mysqli instanceof mysqli) {
            $mysqli->rollback();
        }
        $error_message = $e->getMessage();
        error_log("Payment Error: " . $error_message);
        $_SESSION['payment_error'] = $error_message;
    }
}

// Get booking details for display
try {
    $flight_details = [];
    $passenger_details = [];

    // Get flight details
    $sql = "SELECT b.*, f.airline_id, f.flight_number, f.origin_airport, f.destination_airport, 
            f.departure_time, f.arrival_time, f.duration
            FROM bookings b 
            JOIN flights f ON b.flight_id = f.flight_id 
            WHERE b.booking_id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $flight_details = $result->fetch_assoc();
    
    if (empty($flight_details)) {
        throw new Exception("Flight details not found");
    }

    // Get passenger details
    $sql = "SELECT * FROM passengers WHERE booking_id = ? ORDER BY passenger_id";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $passenger_details[] = $row;
    }

} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['booking_error'] = "Error retrieving booking details";
    header('Location: booking-forms.php');
    exit;
}
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
  </style>
</head>
<body class="bg-gray-50 font-sans">

  <!-- Header -->
  <header class="bg-gray-800 text-white shadow-md">
    <div class="container mx-auto px-4 py-4 flex items-center">
      <div class="flex items-center space-x-2">
        <i class="fas fa-plane text-white text-xl"></i>
        <a href="index.php" class="text-xl font-bold">BookMyFlight</a>
      </div>
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

    <!-- Ticket Confirmation -->

          
    <!-- Ticket Confirmation -->
<main class="container mx-auto px-4 py-8 max-w-4xl">
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
        <button onclick="window.location.href='my-bookings.php'" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 flex items-center">
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
  <?php else: ?>
    <!-- Payment Options -->
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
              Complete Payment
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
    // Print ticket function
    function printTicket() {
      window.print();
    }
    
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
  </script>
</body>
</html>