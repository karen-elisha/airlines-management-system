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
    <main class="container mx-auto px-4 py-8 max-w-md">
      <div class="bg-white rounded-lg shadow-md p-6">
        <div class="text-center mb-4">
          <i class="fas fa-check-circle text-green-500 text-4xl mb-2"></i>
          <h3 class="text-lg font-semibold text-gray-800">Payment Successful!</h3>
          <p class="text-sm text-gray-600 mt-1">Your booking is confirmed</p>
        </div>
        
        <div class="border-t border-b py-4 mb-4">
          <div class="flex justify-between mb-2">
            <span class="text-gray-600">Booking Reference:</span>
            <span class="font-medium"><?php echo htmlspecialchars($booking_reference); ?></span>
          </div>
          <?php if(!empty($flight_details)): ?>
          <div class="flex justify-between mb-2">
            <span class="text-gray-600">Flight No:</span>
            <span class="font-medium"><?php echo htmlspecialchars($flight_details['airline_id'] . '-' . $flight_details['flight_number']); ?></span>
          </div>
          <div class="flex justify-between mb-2">
            <span class="text-gray-600">Route:</span>
            <span class="font-medium"><?php echo htmlspecialchars($flight_details['origin_airport'] . ' → ' . $flight_details['destination_airport']); ?></span>
          </div>
          <div class="flex justify-between mb-2">
            <span class="text-gray-600">Date:</span>
            <span class="font-medium"><?php echo date('M d, Y • H:i A', strtotime($flight_details['departure_time'])); ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Passengers:</span>
            <span class="font-medium"><?php echo htmlspecialchars($flight_details['num_passengers']); ?></span>
          </div>
          <?php endif; ?>
        </div>
        
        <?php if(!empty($passenger_details)): ?>
        <div class="mb-4">
          <h4 class="font-semibold mb-2">Passenger Details:</h4>
          <?php foreach($passenger_details as $index => $passenger): ?>
          <div class="text-sm mb-1">
            <?php echo ($index + 1) . '. ' . htmlspecialchars($passenger['first_name'] . ' ' . $passenger['last_name']); ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <button onclick="printTicket()" class="w-full py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
          <i class="fas fa-print mr-2"></i> Print Ticket
        </button>
        <button onclick="window.location.href='my-bookings.php'" class="w-full py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 mt-2">
          View My Bookings
        </button>
      </div>
    </main>
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