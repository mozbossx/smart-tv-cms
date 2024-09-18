<?php
session_start();
include("config_connection.php");
include 'get_session.php';

$userType = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

$query = "SELECT n.*, u.full_name, u.user_type 
          FROM notifications_tb n
          LEFT JOIN users_tb u ON n.user_id = u.user_id
          WHERE n.status = 'pending'";

if ($userType !== 'Admin') {
    $query .= " AND (n.user_id = ? OR n.notification_type = 'content_approved')";
}

$query .= " ORDER BY n.created_at DESC";

$stmt = $conn->prepare($query);

if ($userType !== 'Admin') {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode($notifications);