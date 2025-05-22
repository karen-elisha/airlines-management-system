<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

// Verify MySQLi connection
if (!isset($mysqli) || $mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $response = [];

    // Get total flights count
    $total_flights_query = "SELECT COUNT(*) as total_flights FROM flights";
    $result = $mysqli->query($total_flights_query);
    if ($result) {
        $response['total_flights'] = $result->fetch_assoc()['total_flights'];
    }

    // Get active bookings count for current user
    $active_bookings_query = "SELECT COUNT(*) as active_bookings 
                             FROM bookings b 
                             JOIN flights f ON b.flight_id = f.flight_id 
                             WHERE b.user_id = ? AND f.departure_time > NOW()";
    
    if ($stmt = $mysqli->prepare($active_bookings_query)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $response['active_bookings'] = $result->fetch_assoc()['active_bookings'];
        $stmt->close();
    }

    // Get flight status statistics for today
    $flight_status_query = "SELECT 
                           flight_status,
                           COUNT(*) as count
                           FROM flights 
                           WHERE departure_time BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                           GROUP BY flight_status";
    
    $flight_status_result = $mysqli->query($flight_status_query);
    $flight_status_data = [];
    $total_today_flights = 0;
    
    if ($flight_status_result) {
        while ($row = $flight_status_result->fetch_assoc()) {
            $flight_status_data[$row['flight_status']] = intval($row['count']);
            $total_today_flights += intval($row['count']);
        }
    }

    // Calculate on-time rate
    $on_time_count = 0;
    $on_time_statuses = ['Scheduled', 'Departed', 'Arrived'];
    foreach ($on_time_statuses as $status) {
        if (isset($flight_status_data[$status])) {
            $on_time_count += $flight_status_data[$status];
        }
    }
    
    $on_time_rate = $total_today_flights > 0 ? round(($on_time_count / $total_today_flights) * 100) : 0;

    $response['on_time_rate'] = $on_time_rate;
    $response['flight_status'] = $flight_status_data;
    $response['total_today_flights'] = $total_today_flights;

    // Get unread notifications count
    $notifications_query = "SELECT COUNT(*) as unread_count 
                           FROM notifications 
                           WHERE user_id = ? AND is_read = 0 AND (expires_at IS NULL OR expires_at > NOW())";
    
    if ($stmt = $mysqli->prepare($notifications_query)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $response['unread_notifications'] = $result->fetch_assoc()['unread_count'];
        $stmt->close();
    }

    // Get latest flight updates (for live status)
    $live_flights_query = "SELECT f.flight_number, f.flight_status, f.departure_time
                          FROM flights f
                          WHERE f.departure_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 12 HOUR)
                          ORDER BY f.departure_time ASC
                          LIMIT 5";
    
    $live_flights_result = $mysqli->query($live_flights_query);
    $live_flights = [];
    
    if ($live_flights_result) {
        while ($row = $live_flights_result->fetch_assoc()) {
            $live_flights[] = $row;
        }
    }
    
    $response['live_flights'] = $live_flights;

    // Add timestamp for data freshness
    $response['timestamp'] = date('Y-m-d H:i:s');
    $response['success'] = true;

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred: ' . $e->getMessage(),
        'success' => false
    ]);
    error_log("Dashboard data API error: " . $e->getMessage());
}

$mysqli->close();
?>