<?php
include("config_connection.php");
// Start the session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
   session_start();
}

// Check if user is admin
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Admin' && $_SESSION['user_type'] !== 'Super Admin')) {
   echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
   exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$minTime = intval($data['min_time']);
$maxTime = intval($data['max_time']);
$displayInterval = intval($data['display_interval']);
// Validate input
if ($minTime >= $maxTime) {
   echo json_encode(['success' => false, 'message' => 'Minimum time must be less than maximum time']);
   exit;
}

// Update the database
$query = "UPDATE display_time_tb SET display_seconds_range_first = ?, display_seconds_range_last = ?, display_interval = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $minTime, $maxTime, $displayInterval);
if ($stmt->execute()) {
   echo json_encode(['success' => true]);
} else {
   echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?>