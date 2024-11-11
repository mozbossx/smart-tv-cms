<?php
// Turn off error reporting and start output buffering
// error_reporting(0);
ob_start();

session_start();
include("config_connection.php");
include 'get_session.php';

$query = "SELECT n.*, 
          COALESCE(u.full_name, n.action_user_name) as full_name, 
          COALESCE(u.user_type, 'Deleted') as user_type, 
          COALESCE(u.department, n.action_user_department) as department,
          COALESCE(tv.tv_name, CAST(n.content_tv_id AS CHAR)) as content_tv_name
          FROM notifications_tb n
          LEFT JOIN users_tb u ON n.user_id = u.user_id
          LEFT JOIN smart_tvs_tb tv ON n.content_tv_id = tv.tv_id
          WHERE (u.user_type != 'TBD' OR u.user_type IS NULL) AND (";

$params = [];
$types = '';

if ($user_type === 'Admin' || $user_type === 'Faculty' || $user_type === 'Student' || $user_type === 'Staff') {
    $query .= "
        (n.status = 'pending' AND u.department = ?) OR
        (n.notification_type = 'user_approved' AND u.department = ?) OR
        (n.notification_type = 'user_approved_by_admin' AND u.department = ?) OR
        (n.notification_type = 'user_rejected' AND u.department = ?) OR
        (n.notification_type = 'user_rejected_by_admin' AND u.department = ?) OR
        (n.notification_type = 'user_edited' AND n.status = 'edited' AND u.department = ?) OR
        (n.notification_type = 'user_edited_by_admin' AND n.status = 'edited' AND u.department = ?) OR
        (n.notification_type = 'user_deleted' AND n.status = 'deleted' AND n.action_user_department = ?) OR
        (n.notification_type = 'user_deleted_by_admin' AND n.status = 'deleted' AND n.action_user_department = ?) OR
        (n.notification_type = 'user_deleted_by_system' AND n.status = 'deleted' AND n.action_user_department = ?) OR
        (n.notification_type = 'user_added' AND n.status = 'approved' AND u.department = ?) OR
        (n.notification_type = 'user_added_by_admin' AND n.status = 'approved' AND u.department = ?) OR
        (n.notification_type = 'content_post' AND n.status = 'approved' AND u.department = ?) OR
        (n.notification_type = 'content_scheduled' AND n.status = 'approved' AND u.department = ?) OR
        (n.notification_type = 'content_scheduled_posted' AND n.status = 'approved' AND u.department = ?) OR
        (n.notification_type = 'content_approved' AND u.department = ?) OR
        (n.notification_type = 'content_approved_by_admin' AND u.department = ?) OR
        (n.notification_type = 'content_rejected' AND n.status = 'rejected' AND u.department = ?) OR
        (n.notification_type = 'content_rejected_by_admin' AND n.status = 'rejected' AND u.department = ?) OR
        (n.notification_type = 'content_deleted' AND n.status = 'deleted' AND u.department = ?) OR
        (n.notification_type = 'content_deleted_by_admin' AND n.status = 'deleted' AND u.department = ?) OR
        (n.notification_type = 'content_deleted_by_system' AND n.status = 'deleted' AND u.department = ?) OR
        (n.notification_type = 'content_archived' AND n.status = 'archived' AND u.department = ?) OR
        (n.notification_type = 'content_archived_by_admin' AND n.status = 'archived' AND u.department = ?) OR
        (n.notification_type = 'content_trashed' AND n.status = 'trashed' AND u.department = ?) OR
        (n.notification_type = 'content_trashed_by_admin' AND n.status = 'trashed' AND u.department = ?) OR
        (n.notification_type = 'content_trashed_by_system' AND n.status = 'trashed' AND u.department = ?) OR
        (n.notification_type = 'content_restored' AND n.status = 'restored' AND u.department = ?) OR
        (n.notification_type = 'content_restored_by_admin' AND n.status = 'restored' AND u.department = ?)
    ";
    $params = array_fill(0, 29, $department);
    $types = str_repeat('s', 29);
} else if ($user_type === 'Super Admin') {
    $query .= "
        n.status = 'pending' OR
        (n.notification_type = 'user_approved' AND n.status = 'approved') OR
        (n.notification_type = 'user_approved_by_admin' AND n.status = 'approved') OR
        (n.notification_type = 'user_rejected' AND n.status = 'rejected') OR
        (n.notification_type = 'user_rejected_by_admin' AND n.status = 'rejected') OR
        (n.notification_type = 'user_edited' AND n.status = 'edited') OR
        (n.notification_type = 'user_edited_by_admin' AND n.status = 'edited') OR
        (n.notification_type = 'user_deleted' AND n.status = 'deleted') OR
        (n.notification_type = 'user_deleted_by_admin' AND n.status = 'deleted') OR
        (n.notification_type = 'user_deleted_by_system' AND n.status = 'deleted') OR
        (n.notification_type = 'user_added' AND n.status = 'approved') OR
        (n.notification_type = 'user_added_by_admin' AND n.status = 'approved') OR
        (n.notification_type = 'content_post' AND n.status = 'approved') OR
        (n.notification_type = 'content_scheduled' AND n.status = 'approved') OR
        (n.notification_type = 'content_scheduled_posted' AND n.status = 'approved') OR
        (n.notification_type = 'content_approved' AND n.status = 'approved') OR
        (n.notification_type = 'content_approved_by_admin' AND n.status = 'approved') OR
        (n.notification_type = 'content_rejected' AND n.status = 'rejected') OR
        (n.notification_type = 'content_rejected_by_admin' AND n.status = 'rejected') OR
        (n.notification_type = 'content_deleted' AND n.status = 'deleted') OR
        (n.notification_type = 'content_deleted_by_admin' AND n.status = 'deleted') OR
        (n.notification_type = 'content_deleted_by_system' AND n.status = 'deleted') OR
        (n.notification_type = 'content_archived' AND n.status = 'archived') OR
        (n.notification_type = 'content_archived_by_admin' AND n.status = 'archived') OR
        (n.notification_type = 'content_trashed' AND n.status = 'trashed') OR
        (n.notification_type = 'content_trashed_by_admin' AND n.status = 'trashed') OR
        (n.notification_type = 'content_trashed_by_system' AND n.status = 'trashed') OR
        (n.notification_type = 'content_restored' AND n.status = 'restored') OR
        (n.notification_type = 'content_restored_by_admin' AND n.status = 'restored')
    ";
    $params = [];
    $types = '';
} 
$query .= ") ORDER BY n.created_at DESC";

try {
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

} catch (Exception $e) {
    error_log("Error in get_notifications.php: " . $e->getMessage());
    $notifications = ['error' => $e->getMessage()];
} finally {
    // Capture any output
    $output = ob_get_clean();

    // If there was any output, log it
    if (!empty($output)) {
        error_log("Unexpected output in get_notifications.php: " . $output);
    }

    // Set the content type to JSON
    header('Content-Type: application/json');

    // Encode and output the notifications
    echo json_encode($notifications);
    exit;
}