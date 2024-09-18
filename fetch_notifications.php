<?php
session_start();
include 'config_connection.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_type = $_SESSION['user_type'] ?? null;

if (!$user_id) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$query = "SELECT * FROM notifications_tb WHERE user_id = :user_id";
$params = [':user_id' => $user_id];

if ($user_type === 'Admin') {
    $query = "SELECT * FROM notifications_tb WHERE type IN ('pending_user', 'pending_post') OR user_id = :user_id";
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($notifications);