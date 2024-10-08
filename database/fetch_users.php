<?php
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

try {
    // Check if userId parameter is set in the GET request
    if (isset($_GET['userId'])) {
        // Fetch a specific user's data based on userId
        $userId = $_GET['userId'];
        $statement = $conn->prepare("SELECT * FROM users_tb WHERE user_id = ?");
        $statement->bind_param("i", $userId);
        $statement->execute();
        $result = $statement->get_result();
        $user = $result->fetch_assoc();

        sendJsonResponse($user);
    } else {
        // Prepare the SQL query based on user type
        if ($loggedInUserType === 'Super Admin') {
            $sql = "SELECT * FROM users_tb";
            $statement = $conn->prepare($sql);
        } elseif ($loggedInUserType === 'Admin') {
            $sql = "SELECT * FROM users_tb WHERE department = ?";
            $statement = $conn->prepare($sql);
            $statement->bind_param("s", $loggedInUserDepartment);
        } else {
            // For other user types, return an empty array
            sendJsonResponse([]);
        }

        // Execute the query
        $statement->execute();
        $result = $statement->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);

        sendJsonResponse($users);
    }
} catch (Exception $e) {
    sendJsonResponse(['error' => 'An error occurred while fetching users'], 500);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}