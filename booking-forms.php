<?php
// Start session for user management
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.php?redirect=booking.php');
    exit;
}

// Include database connection
require_once 'db_connect.php';

// User ID from session
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Passenger Details | BookMyFlight</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-b from-gray-800 to-gray-700 min-h-screen text-white">
  <!-- Header -->
  <header class="bg-gray-900 p-4 shadow-md">
    <div class="container mx-auto flex justify-between items-center">
      <a href="index.php" class="text-xl font-bold flex items-center">
        <i class="fas fa-plane-departure mr-2 text-indigo-400"></i>
        BookMyFlight
      </a>
      <nav class="flex items-center space-x-6">
        <a href="index.php" class="hover:text-indigo-400 transition">Home</a>
        <a href="feedback.html" class="hover:text-indigo-400 transition">Feedback</a>
        <?php if(isset($_SESSION['user_id'])): ?>
          <div class="relative">
            <button onclick="toggleDropdown()" class="flex items-center hover:text-indigo-400 transition">
              <i class="fas fa-user-circle mr-2"></i>
              <?php echo $_SESSION['username'] ?? 'My Account'; ?>
              <i class="fas fa-chevron-down ml-1 text-xs"></i>
            </button>
            <div id="loginDropdown" class="absolute right-0 mt-2 w-48 bg-white text-gray-800 rounded-md shadow-xl hidden z-50">
              <a href="profile.php" class="block px-4 py-2 hover:bg-indigo-50 text-indigo-600"><i class="fas fa-id-card mr-2"></i>My Profile</a>
              <a href="my-bookings.php" class="block px-4 py-2 hover:bg-indigo-50 text-indigo-600"><i class="fas fa-ticket-alt mr-2"></i>My Bookings</a>
              <a href="logout.php" class="block px-4 py-2 hover:bg-indigo-50 text-indigo-600"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            </div>
          </div>
        <?php else: ?>
          <button onclick="window.location.href='login.php'" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded transition">
            <i class="fas fa-user mr-2"></i> Login
          </button>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <!-- Main Content -->
  <main class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6 text-gray-800">
      <!-- Step Indicator -->
      <div class="mb-6">
        <div class="flex items-center justify-center">
          <div class="flex items-center text-indigo-600">
            <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white">1</div>
            <div class="mx-2 text-sm font-medium">Flight Selection</div>
          </div>
          <div class="w-12 h-1 bg-indigo-600"></div>
          <div class="flex items-center text-indigo-600">
            <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white">2</div>
            <div class="mx-2 text-sm font-medium">Passenger Details</div>
          </div>
          <div class="w-12 h-1 bg-gray-300"></div>
          <div class="flex items-center text-gray-400">
            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-white">3</div>
            <div class="mx-2 text-sm font-medium">Payment</div>
          </div>
        </div>
      </div>
      
      <!-- Flight Details Summary -->
      <div id="flightSummary" class="mb-6 border-b border-gray-200 pb-6">
        <h3 class="text-lg font-semibold mb-4">Flight Details</h3>
        <div id="flightDetailsContainer" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Flight details will be populated by JavaScript -->
          <div class="flex items-center">
            <i class="fas fa-plane-departure text-indigo-600 mr-3 text-lg"></i>
            <div>
              <div class="text-gray-500">Airline</div>
              <div id="airlineName" class="font-medium"></div>
            </div>
          </div>
          <div class="flex items-center">
            <i class="fas fa-route text-indigo-600 mr-3 text-lg"></i>
            <div>
              <div class="text-gray-500">Route</div>
              <div id="flightRoute" class="font-medium"></div>
            </div>
          </div>
          <div class="flex items-center">
            <i class="fas fa-calendar-alt text-indigo-600 mr-3 text-lg"></i>
            <div>
              <div class="text-gray-500">Date</div>
              <div id="departureDate" class="font-medium"></div>
            </div>
          </div>
          <div class="flex items-center">
            <i class="fas fa-users text-indigo-600 mr-3 text-lg"></i>
            <div>
              <div class="text-gray-500">Passengers</div>
              <div id="passengerCount" class="font-medium"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Passenger Information Form -->
      <div class="mb-6">
        <h3 class="text-lg font-semibold mb-4">Passenger Information</h3>
        <form id="bookingForm" method="post" action="complete-booking.php">
          <!-- Hidden fields to store flight data -->
          <input type="hidden" id="flight_id" name="flight_id">
          <input type="hidden" id="departure_date" name="departure_date">
          <input type="hidden" id="num_passengers" name="num_passengers">
          <input type="hidden" id="total_price" name="total_price">
          
          <div id="passengerForms" class="space-y-6">
            <!-- Passenger forms will be dynamically generated by JavaScript -->
          </div>
          
          <!-- Add Passenger Button (when applicable) -->
          <div class="mt-4 mb-6" id="addPassengerContainer">
            <button type="button" onclick="addPassenger()" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
              <i class="fas fa-plus-circle mr-2"></i> Add Another Passenger
            </button>
          </div>
          
          <!-- Contact Details -->
          <div class="mt-6 border-t border-gray-200 pt-6">
            <h4 class="font-semibold mb-4">Contact Details</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="contact_email" class="block text-gray-700 mb-1">Email Address</label>
                <input type="email" id="contact_email" name="contact_email" class="w-full border border-gray-300 rounded px-3 py-2" required>
              </div>
              <div>
                <label for="contact_phone" class="block text-gray-700 mb-1">Mobile Number</label>
                <input type="tel" id="contact_phone" name="contact_phone" class="w-full border border-gray-300 rounded px-3 py-2" required>
              </div>
            </div>
          </div>
          
          <!-- Payment Summary -->
          <div class="mt-6 border-t border-gray-200 pt-6">
            <h4 class="font-semibold mb-4">Payment Summary</h4>
            <div class="flex justify-between items-center mb-2">
              <span>Base Fare</span>
              <span id="baseFare">₹0</span>
            </div>
            <div class="flex justify-between items-center mb-2">
              <span>Taxes & Fees</span>
              <span id="taxesFees">₹0</span>
            </div>
            <div class="flex justify-between items-center font-semibold text-lg mt-2 pt-2 border-t">
              <span>Total Amount</span>
              <span id="totalAmount">₹0</span>
            </div>
          </div>
          
          <!-- Submit Button -->
          <div class="mt-6 flex justify-between items-center">
            <button type="button" onclick="history.back()" class="px-5 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">
              <i class="fas fa-arrow-left mr-2"></i> Back
            </button>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded transition">
              <i class="fas fa-credit-card mr-2"></i> Proceed to Payment
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-900 text-center p-6 text-gray-300 mt-12">
    <div class="container mx-auto">
      <div class="flex flex-col md:flex-row justify-between items-center">
        <div class="mb-4 md:mb-0">
          <a href="index.php" class="flex items-center justify-center md:justify-start">
            <i class="fas fa-plane-departure mr-2 text-indigo-400"></i>
            <span class="font-bold">BookMyFlight</span>
          </a>
        </div>
        <div class="flex space-x-4">
          <a href="#" class="hover:text-indigo-400 transition"><i class="fab fa-facebook"></i></a>
          <a href="#" class="hover:text-indigo-400 transition"><i class="fab fa-twitter"></i></a>
          <a href="#" class="hover:text-indigo-400 transition"><i class="fab fa-instagram"></i></a>
        </div>
      </div>
      <div class="mt-4">
        <p>© 2025 BookMyFlight. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <script>
    // Toggle login dropdown
    function toggleDropdown() {
      const dropdown = document.getElementById('loginDropdown');
      dropdown.classList.toggle('hidden');
    }

    // Close dropdown when clicking outside
    window.addEventListener('click', function(event) {
      const dropdown = document.getElementById('loginDropdown');
      if (dropdown && !dropdown.contains(event.target) && !event.target.closest('button[onclick="toggleDropdown()"]')) {
        dropdown.classList.add('hidden');
      }
    });

    // Format date for display
    function formatDate(dateString) {
      const options = { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' };
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', options);
    }
    
    // Variable to track passenger count
    let passengerCount = 0;
    let maxPassengers = 0;
    
    // Add passenger function
    function addPassenger() {
      if (passengerCount >= maxPassengers) {
        alert('You have reached the maximum number of passengers for this booking.');
        return;
      }
      
      passengerCount++;
      generatePassengerForm(passengerCount);
      
      // Hide the add button if we've reached the maximum
      if (passengerCount >= maxPassengers) {
        document.getElementById('addPassengerContainer').classList.add('hidden');
      }
      
      // Update passenger count display
      document.getElementById('passengerCount').textContent = `${passengerCount} passenger${passengerCount > 1 ? 's' : ''}`;
      document.getElementById('num_passengers').value = passengerCount;
      
      // Update payment summary
      updatePaymentSummary();
    }
    
    // Generate passenger form
    function generatePassengerForm(index) {
      const passengerForms = document.getElementById('passengerForms');
      const passengerForm = document.createElement('div');
      passengerForm.className = 'border border-gray-200 rounded-lg p-4';
      passengerForm.innerHTML = `
        <h4 class="font-medium mb-3 flex items-center">
          <i class="fas fa-user text-indigo-600 mr-2"></i> Passenger ${index}
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label for="passenger_title_${index}" class="block text-gray-700 mb-1">Title</label>
            <select id="passenger_title_${index}" name="passenger_title[]" class="w-full border border-gray-300 rounded px-3 py-2" required>
              <option value="">Select</option>
              <option value="Mr">Mr</option>
              <option value="Mrs">Mrs</option>
              <option value="Ms">Ms</option>
            </select>
          </div>
          <div>
            <label for="passenger_firstname_${index}" class="block text-gray-700 mb-1">First Name</label>
            <input type="text" id="passenger_firstname_${index}" name="passenger_firstname[]" class="w-full border border-gray-300 rounded px-3 py-2" required>
          </div>
          <div>
            <label for="passenger_lastname_${index}" class="block text-gray-700 mb-1">Last Name</label>
            <input type="text" id="passenger_lastname_${index}" name="passenger_lastname[]" class="w-full border border-gray-300 rounded px-3 py-2" required>
          </div>
          <div>
            <label for="passenger_dob_${index}" class="block text-gray-700 mb-1">Date of Birth</label>
            <input type="date" id="passenger_dob_${index}" name="passenger_dob[]" class="w-full border border-gray-300 rounded px-3 py-2" required>
          </div>
          <div>
            <label for="passenger_gender_${index}" class="block text-gray-700 mb-1">Gender</label>
            <select id="passenger_gender_${index}" name="passenger_gender[]" class="w-full border border-gray-300 rounded px-3 py-2" required>
              <option value="">Select</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label for="passenger_mobile_${index}" class="block text-gray-700 mb-1">Mobile</label>
            <input type="tel" id="passenger_mobile_${index}" name="passenger_mobile[]" class="w-full border border-gray-300 rounded px-3 py-2">
          </div>
          <div>
            <label for="passenger_class_${index}" class="block text-gray-700 mb-1">Class</label>
            <select id="passenger_class_${index}" name="passenger_class[]" class="w-full border border-gray-300 rounded px-3 py-2" required>
              <option value="economy">Economy</option>
              <option value="business">Business</option>
              <option value="first">First Class</option>
            </select>
          </div>
          <div>
            <label for="passenger_seat_${index}" class="block text-gray-700 mb-1">Seat Preference</label>
            <input type="text" id="passenger_seat_${index}" name="passenger_seat[]" placeholder="e.g. 12A, Window, Aisle" class="w-full border border-gray-300 rounded px-3 py-2">
          </div>
        </div>
      `;
      passengerForms.appendChild(passengerForm);
    }
    
    // Update payment summary based on passenger count and flight details
    function updatePaymentSummary() {
      // Get stored flight details
      const flightDetailsJson = localStorage.getItem('flightDetails');
      if (!flightDetailsJson) return;
      
      const flightDetails = JSON.parse(flightDetailsJson);
      
      // Calculate costs
      const baseFare = flightDetails.base_price * passengerCount;
      const taxesFees = Math.round(baseFare * 0.18); // 18% tax
      const totalAmount = baseFare + taxesFees;
      
      // Update display
      document.getElementById('baseFare').textContent = `₹${baseFare.toLocaleString()}`;
      document.getElementById('taxesFees').textContent = `₹${taxesFees.toLocaleString()}`;
      document.getElementById('totalAmount').textContent = `₹${totalAmount.toLocaleString()}`;
      
      // Update hidden field
      document.getElementById('total_price').value = totalAmount;
    }

    // Load flight details from localStorage
    document.addEventListener('DOMContentLoaded', function() {
      // Get flight details from localStorage
      const flightDetailsJson = localStorage.getItem('flightDetails');
      
      if (!flightDetailsJson) {
        // No flight details found, redirect to home
        alert('No flight details found. Please search for flights again.');
        window.location.href = 'index.php';
        return;
      }
      
      const flightDetails = JSON.parse(flightDetailsJson);
      
      // Update hidden form fields
      document.getElementById('flight_id').value = flightDetails.flight_id;
      document.getElementById('departure_date').value = flightDetails.departure_date;
      document.getElementById('num_passengers').value = flightDetails.passengers;
      document.getElementById('total_price').value = flightDetails.total_price;
      
      // Update flight summary
      document.getElementById('airlineName').textContent = `${flightDetails.airline_name} (${flightDetails.airline_id}-${flightDetails.flight_number})`;
      document.getElementById('flightRoute').textContent = `${flightDetails.origin} to ${flightDetails.destination}`;
      document.getElementById('departureDate').textContent = formatDate(flightDetails.departure_date);
      document.getElementById('passengerCount').textContent = `${flightDetails.passengers} passenger${flightDetails.passengers > 1 ? 's' : ''}`;
      
      // Set max passengers and current count
      maxPassengers = parseInt(flightDetails.passengers);
      passengerCount = 0;
      
      // Create first passenger form
      addPassenger();
    });
    
    // Form validation before submission
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
      const requiredFields = this.querySelectorAll('[required]');
      let valid = true;
      
      requiredFields.forEach(field => {
        if (!field.value) {
          valid = false;
          field.classList.add('border-red-500');
        } else {
          field.classList.remove('border-red-500');
        }
      });
      
      if (!valid) {
        e.preventDefault();
        alert('Please fill in all required fields.');
      }
    });
  </script>
</body>
</html>