<?php
include("config_connection.php");

// Start the session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['full_name']) || !isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    header('location: index.php');
    exit;
}

// Fetch user_type, user_id, and full_name from the session
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Define the query based on user type
if ($user_type == "Admin") {
    $query = "
        SELECT 
            (SELECT COUNT(*) FROM users_tb WHERE (status = 'Pending' OR status = 'Rejected' OR status = 'Approved') AND user_type != 'Admin') +
            (SELECT COUNT(*) FROM announcements_tb WHERE user_type != 'Admin' AND status != 'Approved') AS combined_count
    ";
} else if ($user_type == "Student") {
    $query = "
        SELECT 
            (SELECT COUNT(*) FROM users_tb WHERE (status = 'Pending' OR status = 'Rejected' OR status = 'Approved') AND user_id = ?) +
            (SELECT COUNT(*) FROM announcements_tb WHERE ann_author = ? AND status != 'Approved') AS combined_count
    ";
}

// Prepare and execute the query
$stmt = $conn->prepare($query);

if ($user_type == "Student") {
    $stmt->bind_param("ss", $user_id, $full_name);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    $row = $result->fetch_assoc();
    $combinedCount = $row['combined_count'];

    // Return the combined count as JSON
    echo json_encode(['combinedCount' => $combinedCount]);
} else {
    echo json_encode(['error' => 'Error fetching combined count']);
}

$stmt->close();
?>