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
$booking_reference = isset($_GET['reference']) ? trim($_GET['reference']) : '';
$step = isset($_GET['step']) ? $_GET['step'] : 'search';

$message = '';
$error = '';
$booking = null;
$show_refund_processing = false;

// Function to add notification
function addNotification($pdo, $user_id, $message, $type = 'info') {
    try {
        $query = "INSERT INTO notifications (user_id, message, notification_type, is_read, created_at) 
                  VALUES (:user_id, :message, :type, 0, NOW())";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);
        return $stmt->execute();
    } catch(Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

// Function to get complete booking details with flight info
function getBookingWithFlightDetails($pdo, $booking_reference, $user_id) {
    $query = "SELECT 
        b.*,
        f.flight_number,
        f.airline_id,
        f.origin_airport,
        f.destination_airport,
        f.departure_time,
        f.arrival_time,
        a.airline_name
    FROM bookings b
    LEFT JOIN flights f ON b.flight_id = f.flight_id
    LEFT JOIN airlines a ON f.airline_id = a.airline_id
    WHERE b.booking_reference = :booking_reference AND b.user_id = :user_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':booking_reference', $booking_reference);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Search for booking by reference number
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_booking'])) {
    $booking_reference = trim($_POST['booking_reference']);
    
    if (empty($booking_reference)) {
        $error = "Please enter a booking reference number.";
    } else {
        try {
            $booking = getBookingWithFlightDetails($pdo, $booking_reference, $user_id);
            
            if (!$booking) {
                $error = "Booking not found or you don't have permission to cancel this booking.";
            } else {
                // Check if booking is already cancelled
                if (strtolower($booking['booking_status']) === 'cancelled') {
                    $error = "This booking has already been cancelled.";
                } else {
                    $step = 'confirm';
                }
            }
        } catch(Exception $e) {
            $error = "Database error occurred while searching for booking. Please try again.";
            error_log("Search booking error: " . $e->getMessage());
        }
    }
}

// Handle cancellation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cancel'])) {
    $booking_reference = $_POST['booking_reference'];
    $cancellation_reason = $_POST['cancellation_reason'] ?? 'User requested cancellation';
    
    // Fetch booking again for security - WITH FULL FLIGHT DETAILS
    try {
        $booking = getBookingWithFlightDetails($pdo, $booking_reference, $user_id);
        
        if ($booking && strtolower($booking['booking_status']) !== 'cancelled') {
            try {
                $pdo->beginTransaction();
                
                // Update booking status to cancelled
                $update_booking = "UPDATE bookings SET 
                    booking_status = 'cancelled',
                    updated_at = NOW()
                    WHERE booking_reference = :booking_reference AND user_id = :user_id";
                
                $stmt = $pdo->prepare($update_booking);
                $stmt->bindParam(':booking_reference', $booking_reference);
                $stmt->bindParam(':user_id', $user_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update booking status");
                }
                
                // Update ticket status to 'refunded'
                $update_tickets = "UPDATE tickets SET 
                    status = 'refunded'
                    WHERE user_id = :user_id AND flight_id = :flight_id";
                
                $stmt = $pdo->prepare($update_tickets);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':flight_id', $booking['flight_id']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update ticket status");
                }
                
                // Add notification about successful cancellation and refund
                $notification_message = "Your booking (Reference: {$booking_reference}) has been successfully cancelled. Refund of ₹" . number_format($booking['total_amount']) . " will be processed and credited to your account within 7-10 business days.";
                
                addNotification($pdo, $user_id, $notification_message, 'success');
                
                $pdo->commit();
                
                $show_refund_processing = true;
                $step = 'processing';
                $message = "Booking cancelled successfully. Refund notification has been sent.";
                
            } catch (Exception $e) {
                $pdo->rollback();
                $error = "An error occurred while cancelling your booking. Please try again or contact support.";
                error_log("Booking cancellation error: " . $e->getMessage());
            }
        } else {
            $error = "Invalid booking reference or booking has already been cancelled.";
        }
    } catch(Exception $e) {
        $error = "Database error occurred during cancellation. Please try again.";
        error_log("Cancellation error: " . $e->getMessage());
    }
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Booking | BookMyFlight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .processing-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .progress-bar {
            animation: progress 3s ease-in-out forwards;
        }
        @keyframes progress {
            from { width: 0%; }
            to { width: 100%; }
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
                <a href="bookings.php" class="hover:text-indigo-200"><i class="fas fa-ticket-alt mr-1"></i> My Bookings</a>
            </nav>
            <a href="bookings.php" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-md transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Bookings
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto px-4 py-8 max-w-4xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Cancel Booking</h1>
            <p class="mt-2 text-gray-600">Enter your booking reference to cancel your flight</p>
        </div>

        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center space-x-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center text-sm font-medium">
                        1
                    </div>
                    <span class="ml-2 text-sm font-medium text-indigo-600">Search</span>
                </div>
                <div class="w-16 h-1 bg-gray-300 <?php echo ($step === 'confirm' || $step === 'processing') ? 'bg-indigo-600' : ''; ?>"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 <?php echo ($step === 'confirm' || $step === 'processing') ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600'; ?> rounded-full flex items-center justify-center text-sm font-medium">
                        2
                    </div>
                    <span class="ml-2 text-sm font-medium <?php echo ($step === 'confirm' || $step === 'processing') ? 'text-indigo-600' : 'text-gray-500'; ?>">Confirm</span>
                </div>
                <div class="w-16 h-1 bg-gray-300 <?php echo $step === 'processing' ? 'bg-indigo-600' : ''; ?>"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 <?php echo $step === 'processing' ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600'; ?> rounded-full flex items-center justify-center text-sm font-medium">
                        3
                    </div>
                    <span class="ml-2 text-sm font-medium <?php echo $step === 'processing' ? 'text-indigo-600' : 'text-gray-500'; ?>">Complete</span>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 fade-in">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 fade-in">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($step === 'search'): ?>
            <!-- Search Form -->
            <div class="bg-white rounded-xl shadow-md p-6 fade-in">
                <h2 class="text-xl font-bold mb-4 text-gray-800">
                    <i class="fas fa-search mr-2 text-indigo-600"></i>Find Your Booking
                </h2>
                
                <form method="POST">
                    <div class="mb-6">
                        <label for="booking_reference" class="block text-sm font-medium text-gray-700 mb-2">
                            Booking Reference Number
                        </label>
                        <input type="text" 
                               name="booking_reference" 
                               id="booking_reference" 
                               value="<?php echo htmlspecialchars($booking_reference); ?>"
                               placeholder="Enter your booking reference (e.g., BF123456)" 
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-center text-lg font-mono tracking-wider"
                               required>
                        <p class="mt-2 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            You can find your booking reference in your confirmation email or booking history.
                        </p>
                    </div>

                    <button type="submit" name="search_booking" 
                            class="w-full bg-indigo-600 text-white py-3 px-6 rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-200 font-medium transition-colors">
                        <i class="fas fa-search mr-2"></i>Search Booking
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($step === 'confirm' && $booking): ?>
            <!-- Booking Details and Confirmation -->
            <div class="space-y-6 fade-in">
                <!-- Booking Details Card -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">
                        <i class="fas fa-ticket-alt mr-2 text-indigo-600"></i>Booking Details
                    </h2>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Flight Information -->
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-3">Flight Information</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Airline:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($booking['airline_name'] ?: 'N/A'); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Flight Number:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($booking['flight_number']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Route:</span>
                                    <span class="font-medium">
                                        <?php echo strtoupper($booking['origin_airport']) . ' → ' . strtoupper($booking['destination_airport']); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">From/To:</span>
                                    <span class="font-medium">
                                        <?php echo getAirportName($booking['origin_airport']) . ' to ' . getAirportName($booking['destination_airport']); ?>
                                    </span>
                                </div>
                                <?php if ($booking['departure_time']): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Departure:</span>
                                    <span class="font-medium">
                                        <?php 
                                        $departure = new DateTime($booking['departure_time']);
                                        echo $departure->format('d M Y, h:i A');
                                        ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Booking Information -->
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-3">Booking Information</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Booking Reference:</span>
                                    <span class="font-medium font-mono"><?php echo htmlspecialchars($booking['booking_reference']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Passengers:</span>
                                    <span class="font-medium"><?php echo $booking['num_passengers']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Amount:</span>
                                    <span class="font-medium text-lg">₹<?php echo number_format($booking['total_amount']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Booking Date:</span>
                                    <span class="font-medium">
                                        <?php 
                                        $booking_date = new DateTime($booking['booking_date']);
                                        echo $booking_date->format('d M Y');
                                        ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Status:</span>
                                    <span class="font-medium capitalize bg-green-100 text-green-800 px-2 py-1 rounded text-xs">
                                        <?php echo $booking['booking_status']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Simple Refund Information -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <h3 class="font-semibold text-blue-800 mb-3">
                        <i class="fas fa-info-circle mr-2"></i>Refund Information
                    </h3>
                    <div class="text-sm text-blue-700 space-y-2">
                        <p><i class="fas fa-check mr-2"></i>Full refund of ₹<?php echo number_format($booking['total_amount']); ?> will be processed</p>
                        <p><i class="fas fa-clock mr-2"></i>Refund processing time: 7-10 business days</p>
                        <p><i class="fas fa-bell mr-2"></i>You'll receive a notification once cancelled</p>
                    </div>
                </div>

                <!-- Confirmation Form -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-times-circle mr-2 text-red-500"></i>Confirm Cancellation
                    </h3>

                    <form method="POST">
                        <input type="hidden" name="booking_reference" value="<?php echo htmlspecialchars($booking['booking_reference']); ?>">
                        
                        <div class="mb-6">
                            <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-comment mr-1"></i>Reason for Cancellation (Optional)
                            </label>
                            <select name="cancellation_reason" id="cancellation_reason" 
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                                <option value="User requested cancellation">Select a reason (optional)</option>
                                <option value="Change of Plans">Change of Plans</option>
                                <option value="Medical Emergency">Medical Emergency</option>
                                <option value="Work Related">Work Related</option>
                                <option value="Family Emergency">Family Emergency</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4">
                            <button type="submit" name="confirm_cancel" value="1" 
                                    class="flex-1 bg-red-600 text-white py-3 px-6 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-200 font-medium transition-colors"
                                    onclick="return confirm('Are you sure you want to cancel this booking?')">
                                <i class="fas fa-times mr-2"></i>Cancel Booking
                            </button>
                            <button type="button" onclick="window.location.href='?'" 
                                   class="flex-1 bg-gray-600 text-white py-3 px-6 rounded-lg hover:bg-gray-700 font-medium transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>Go Back
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($step === 'processing' || $show_refund_processing): ?>
            <!-- Processing and Success -->
            <div class="space-y-6 fade-in">
                <!-- Processing Animation -->
                <div id="processing-section" class="bg-white rounded-xl shadow-md p-8 text-center">
                    <div class="processing-animation">
                        <i class="fas fa-spinner fa-spin text-4xl text-indigo-600 mb-4"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Processing Your Cancellation...</h2>
                    <p class="text-gray-600 mb-4">Please wait while we cancel your booking and process your refund.</p>
                    
                    <div class="bg-gray-200 rounded-full h-2 mb-4">
                        <div class="bg-indigo-600 h-2 rounded-full progress-bar"></div>
                    </div>
                    
                    <div class="text-sm text-gray-500">
                        <div id="status-text">Cancelling booking...</div>
                    </div>
                </div>

                <!-- Success Message (Initially Hidden) -->
                <div id="success-section" class="bg-white rounded-xl shadow-md p-8 text-center" style="display: none;">
                    <div class="mb-6">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check text-2xl text-green-600"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Booking Successfully Cancelled!</h2>
                        <p class="text-gray-600">Your refund notification has been sent to your dashboard.</p>
                    </div>

                    <?php if ($booking && $step === 'processing'): ?>
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 text-left">
        <h4 class="font-semibold text-green-800 mb-2">Cancellation Summary:</h4>
        <div class="text-sm text-green-700 space-y-1">
            <div>• Booking Reference: <span class="font-mono"><?php echo htmlspecialchars($booking['booking_reference'] ?? 'N/A'); ?></span></div>
            <div>• Flight: 
                <?php 
                $flight_display = htmlspecialchars($booking['flight_number'] ?? 'N/A');
                if (isset($booking['origin_airport']) && isset($booking['destination_airport'])) {
                    $flight_display .= ' (' . strtoupper($booking['origin_airport']) . ' → ' . strtoupper($booking['destination_airport']) . ')';
                }
                echo $flight_display;
                ?>
            </div>
            <div>• Refund Amount: ₹<?php echo number_format($booking['total_amount'] ?? 0); ?></div>
            <div>• Notification sent to your dashboard</div>
        </div>
    </div>
<?php endif; ?>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="bookings.php" class="bg-indigo-600 text-white py-3 px-6 rounded-lg hover:bg-indigo-700 font-medium transition-colors">
                            <i class="fas fa-ticket-alt mr-2"></i>View My Bookings
                        </a>
                        <a href="user-dashboard.php" class="bg-gray-600 text-white py-3 px-6 rounded-lg hover:bg-gray-700 font-medium transition-colors">
                            <i class="fas fa-home mr-2"></i>Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <script>
                // Auto-hide processing section and show success after 2 seconds
                setTimeout(function() {
                    document.getElementById('processing-section').style.display = 'none';
                    document.getElementById('success-section').style.display = 'block';
                }, 2000);

                // Update status text during processing
                let statusMessages = [
                    "Cancelling booking...",
                    "Updating ticket status...",
                    "Sending notification...",
                    "Finalizing cancellation..."
                ];
                
                let currentMessage = 0;
                let statusInterval = setInterval(function() {
                    if (currentMessage < statusMessages.length - 1) {
                        currentMessage++;
                        document.getElementById('status-text').textContent = statusMessages[currentMessage];
                    } else {
                        clearInterval(statusInterval);
                    }
                }, 500);
            </script>
        <?php endif; ?>

        <!-- Help Section -->
        <div class="mt-8 bg-gray-50 border border-gray-200 rounded-xl p-6">
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

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-8">
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
        // Auto-focus on booking reference input
        document.addEventListener('DOMContentLoaded', function() {
            const bookingInput = document.getElementById('booking_reference');
            if (bookingInput && bookingInput.value === '') {
                bookingInput.focus();
            }
        });

        // Format booking reference input (uppercase)
        document.addEventListener('DOMContentLoaded', function() {
            const bookingInput = document.getElementById('booking_reference');
            if (bookingInput) {
                bookingInput.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            }
        });
    </script>
</body>
</html>