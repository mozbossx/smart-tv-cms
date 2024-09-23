<?php
session_start();
include 'config_connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'];

$query = "UPDATE users_tb SET new_notifications = 0 WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$stmt->close();
$conn->close();