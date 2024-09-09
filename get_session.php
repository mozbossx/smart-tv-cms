<?php

// fetch user data for the currently logged-in user
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$department = $_SESSION['department'];
$user_type = $_SESSION['user_type'];
$email = $_SESSION['email'];

// check if the user is not logged in, redirect to the login page (index.php)
if (!isset($_SESSION['full_name']) || !isset($_SESSION['email'])) {
    header('location: index.php');
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
    header('location: index.php');
    exit;
}

$stmt->close();

?>