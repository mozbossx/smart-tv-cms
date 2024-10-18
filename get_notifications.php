<?php
session_start();
include("config_connection.php");
include 'get_session.php';

$userType = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];
$department = $_SESSION['department'];

$query = "SELECT n.*, COALESCE(u.full_name, n.deleted_user_name) as full_name, 
          COALESCE(u.user_type, 'Deleted') as user_type, 
          COALESCE(u.department, n.deleted_user_department) as department
          FROM notifications_tb n
          LEFT JOIN users_tb u ON n.user_id = u.user_id
          WHERE (u.user_type != 'TBD' OR u.user_type IS NULL) AND (";

// For Admin, show all pending notifications and all content_approved notifications only from their respective departments (if users_tb == department of the Admin)
if ($userType === 'Admin') {
    $query .= "
        (n.status = 'pending' AND u.department = ?)
        OR (n.notification_type = 'content_approved' AND u.department = ?)
        OR (n.notification_type = 'user_approved' AND u.department = ?)
        OR (n.notification_type = 'user_rejected' AND u.department = ?)
        OR (n.notification_type = 'content_post' AND n.status = 'approved' AND u.department = ?)
        OR (n.notification_type = 'content_rejected' AND n.status = 'rejected' AND u.department = ?)
        OR (n.notification_type = 'content_deleted' AND n.status = 'deleted' AND u.department = ?)
        OR (n.notification_type = 'content_deleted_by_admin' AND n.status = 'deleted' AND u.department = ?)
        OR (n.notification_type = 'user_edited' AND n.status = 'edited' AND u.department = ?)
        OR (n.notification_type = 'user_edited_by_admin' AND n.status = 'edited' AND n.user_id = ?)
        OR (n.notification_type = 'user_deleted' AND n.status = 'deleted' AND n.deleted_user_department = ?)
    ";
} else if ($userType === 'Super Admin') {
    $query .= "
        n.status = 'pending'
        OR n.notification_type = 'content_approved'
        OR n.notification_type = 'user_approved'
        OR n.notification_type = 'user_rejected'
        OR (n.notification_type = 'content_post' AND n.status = 'approved')
        OR (n.notification_type = 'content_rejected' AND n.status = 'rejected')
        OR (n.notification_type = 'content_deleted' AND n.status = 'deleted')
        OR (n.notification_type = 'content_deleted_by_admin' AND n.status = 'deleted')
        OR (n.notification_type = 'user_edited' AND n.status = 'edited')
        OR (n.notification_type = 'user_deleted' AND n.status = 'deleted')
        OR (n.notification_type = 'user_edited_by_admin' AND n.status = 'edited' AND n.user_id = ?)
    ";
} else {
    $query .= "
        (n.user_id = ? AND n.status = 'pending') 
        OR (n.notification_type = 'content_approved' AND n.user_id = ?)
        OR (n.notification_type = 'content_approved_by_admin' AND n.user_id = ?)
        OR (n.notification_type = 'content_rejected_by_admin' AND n.user_id = ?)
        OR (n.notification_type = 'content_deleted' AND n.user_id = ?)
        OR (n.notification_type = 'content_deleted_by_admin' AND n.user_id = ?)
        OR (n.notification_type = 'user_approved_by_admin' AND n.user_id = ?)
        OR (n.notification_type = 'user_edited_by_admin' AND n.user_id = ?)
    ";
}

$query .= ") ORDER BY n.created_at DESC";

$stmt = $conn->prepare($query);

if ($userType === 'Admin') {
    $stmt->bind_param("sssssssssis", $department, $department, $department, $department, $department, $department, $department, $department, $department, $user_id, $department);
} else if ($userType === 'Student' || $userType === 'Faculty' || $userType === 'Staff') {
    $stmt->bind_param("iiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
} else if ($userType === 'Super Admin') {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode($notifications);