<?php
session_start();
include("config_connection.php");
include 'get_session.php';

$user_id = $_SESSION['user_id'];

$query = "SELECT notification_count FROM users_tb WHERE user_id = ? AND notification_count > 0";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $notification_count = $row['notification_count'];
    echo json_encode(['count' => $notification_count]);
} else {
    echo json_encode(['error' => 'User not found']);
}

$stmt->close();
$conn->close();
?>