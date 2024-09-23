<?php
session_start();
include("config_connection.php");
include 'get_session.php';

$userType = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

$query = "SELECT n.*, u.full_name, u.user_type 
          FROM notifications_tb n
          LEFT JOIN users_tb u ON n.user_id = u.user_id
          WHERE 1=1";  // Changed from n.status = 'pending' to 1=1 to include all notifications

if ($userType === 'Admin') {
    // For Admin, show all pending notifications and all content_approved notifications
    $query .= " AND ((n.status = 'pending' OR n.notification_type = 'content_approved')
                OR (n.notification_type = 'user_approved' OR n.notification_type = 'user_rejected')
                OR (n.notification_type = 'content_post' AND n.status = 'approved')
                OR (n.notification_type = 'content_rejected' AND n.status = 'rejected')
                OR (n.notification_type = 'content_deleted' AND n.status = 'deleted')
                OR (n.notification_type = 'user_edited' AND n.status = 'edited'))";
} else {
    // For non-Admin users, show their own notifications and content_approved notifications for their posts
    $query .= " AND ((n.user_id = ? AND n.status = 'pending') 
                OR (n.notification_type = 'content_approved' AND n.user_id = ?)
                OR (n.notification_type = 'content_approved_by_admin' AND n.user_id = ?)
                OR (n.notification_type = 'content_rejected_by_admin' AND n.user_id = ?)
                OR (n.notification_type = 'user_approved_by_admin' AND n.user_id = ?)
                OR (n.notification_type = 'user_edited_by_admin' AND n.user_id = ?))";
}

$query .= " ORDER BY n.created_at DESC";

$stmt = $conn->prepare($query);

if ($userType !== 'Admin') {
    $stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
} 

$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode($notifications);