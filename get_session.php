<?php

// fetch user data for the currently logged-in user
// $user_id = $_SESSION['user_id'];
// $full_name = $_SESSION['full_name'];
// $department = $_SESSION['department'];
// $user_type = $_SESSION['user_type'];
// $email = $_SESSION['email'];

// Check if session variables exist before accessing them
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : null;
$department = isset($_SESSION['department']) ? $_SESSION['department'] : null;
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
$email = isset($_SESSION['email']) ? $_SESSION['email'] : null;

// check if the user is not logged in, redirect to the login page (login.php?pageid=Login)
if (!isset($_SESSION['full_name']) || !isset($_SESSION['email'])) {
    header('location: login.php?pageid=Login');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

$sql = "SELECT user_id, full_name, password, department, email, user_type FROM users_tb WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    header('location: login.php?pageid=Login');
    exit;
}

$stmt->close();

?>