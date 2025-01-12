<?php
session_start();
include("config_connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $email = $_POST['email'];
    $theme_color = $_POST['theme_color'];

    // Update the theme color in the database
    $query = "UPDATE users_tb SET theme_color = ? WHERE user_id = ? AND email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sis", $theme_color, $user_id, $email);

    if ($stmt->execute()) {
        // Success
        header("Location: profile.php?success=Theme updated successfully!");
    } else {
        // Error
        header("Location: profile.php?error=Failed to update theme");
    }
    
    $stmt->close();
} else {
    header("Location: profile.php?error=Invalid request");
}

$conn->close();
?>