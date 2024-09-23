<?php
session_start();
include("config_connection.php");
include 'get_session.php';

// Check if the user is not logged in, redirect to the login page
if (!isset($_SESSION['full_name']) || !isset($_SESSION['email'])) {
    header('location: index.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate passwords
    if ($new_password !== $confirm_password) {
        header("Location: profile.php?error=Passwords do not match");
        exit;
    }

    if (strlen($new_password) < 6) {
        header("Location: profile.php?error=Password must be at least 6 characters long");
        exit;
    }

    // Hash the new password
    $hashed_password = md5($new_password);

    // Update the password in the database
    $query = "UPDATE users_tb SET password = ? WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: profile.php?success=Password updated successfully");
        exit;
    } else {
        $stmt->close();
        header("Location: profile.php?error=Failed to update password");
        exit;
    }
} else {
    // If accessed directly without POST data, redirect to profile page
    header("Location: profile.php?pageid=Profile");
    exit;
}
?>