<?php
// For manage_smart_tvs.php only
// Disable error reporting to prevent PHP errors from breaking JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

session_start();
include '../config_connection.php';

// Function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    ob_clean(); // Clear output buffer
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['error' => 'User not logged in'], 401);
}

$loggedInUserId = $_SESSION['user_id'];
$loggedInUserType = $_SESSION['user_type'];
$loggedInUserDepartment = $_SESSION['department'];

// Get tvId from the query string
$tvId = isset($_GET['tvId']) ? intval($_GET['tvId']) : 0;

try {
    if ($tvId > 0) {
        $statement = $conn->prepare("SELECT * FROM smart_tvs_tb WHERE tv_id = ?");
        $statement->bind_param("i", $tvId);
        $statement->execute();
        $result = $statement->get_result();
        $smart_tv = $result->fetch_assoc();

        sendJsonResponse($smart_tv);
    } else {
        // Prepare the SQL query based on user type
        if ($loggedInUserType === 'Super Admin') {
            $sql = "SELECT * FROM smart_tvs_tb";
            $statement = $conn->prepare($sql);
        } elseif ($loggedInUserType === 'Admin') {
            $sql = "SELECT * FROM smart_tvs_tb WHERE tv_department = ? OR tv_department = 'Unknown'";
            $statement = $conn->prepare($sql);
            $statement->bind_param("s", $loggedInUserDepartment);
        } else {
            // For other user types, return an empty array
            sendJsonResponse([]);
        }

        // Execute the query
        $statement->execute();
        $result = $statement->get_result();
        $smart_tv = $result->fetch_all(MYSQLI_ASSOC);

        sendJsonResponse($smart_tv);
    }
} catch (Exception $e) {
    sendJsonResponse(['error' => 'An error occurred while fetching tvs'], 500);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}