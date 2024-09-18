<?php
session_start();
include("config_connection.php");
include 'get_session.php';

$userType = $_SESSION['user_type'];

$query = "SELECT COUNT(*) as count FROM notifications_tb WHERE status = 'pending'";

if ($userType !== 'Admin') {
    $query .= " AND user_id = ?";
}

$stmt = $conn->prepare($query);

if ($userType !== 'Admin') {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];

echo json_encode(['count' => $count]);