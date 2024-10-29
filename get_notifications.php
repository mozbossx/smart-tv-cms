<?php
// Turn off error reporting and start output buffering
// error_reporting(0);
ob_start();

session_start();
include("config_connection.php");
include 'get_session.php';

$query = "SELECT n.*, COALESCE(u.full_name, n.deleted_user_name) as full_name, 
          COALESCE(u.user_type, 'Deleted') as user_type, 
          COALESCE(u.department, n.deleted_user_department) as department
          FROM notifications_tb n
          LEFT JOIN users_tb u ON n.user_id = u.user_id
          WHERE (u.user_type != 'TBD' OR u.user_type IS NULL) AND (";

$params = [];
$types = '';

if ($user_type === 'Admin') {
    $query .= "
        (n.status = 'pending' AND u.department = ?) OR
        (n.notification_type = 'content_approved' AND u.department = ?) OR
        (n.notification_type = 'user_approved' AND u.department = ?) OR
        (n.notification_type = 'user_approved_by_admin' AND u.department = ?) OR
        (n.notification_type = 'user_rejected' AND u.department = ?) OR
        (n.notification_type = 'content_post' AND n.status = 'approved' AND u.department = ?) OR
        (n.notification_type = 'content_rejected' AND n.status = 'rejected' AND u.department = ?) OR
        (n.notification_type = 'content_deleted' AND n.status = 'deleted' AND u.department = ?) OR
        (n.notification_type = 'content_deleted_by_admin' AND n.status = 'deleted' AND u.department = ?) OR
        (n.notification_type = 'content_archived' AND n.status = 'archived' AND u.department = ?) OR
        (n.notification_type = 'content_archived_by_admin' AND n.status = 'archived' AND u.department = ?) OR
        (n.notification_type = 'content_restored' AND n.status = 'restored' AND u.department = ?) OR
        (n.notification_type = 'content_restored_by_admin' AND n.status = 'restored' AND u.department = ?) OR
        (n.notification_type = 'user_edited' AND n.status = 'edited' AND u.department = ?) OR
        (n.notification_type = 'user_deleted' AND n.status = 'deleted' AND n.deleted_user_department = ?) OR
        (n.notification_type = 'user_edited_by_admin' AND n.status = 'edited' AND u.department = ?) OR
        (n.notification_type = 'user_added' AND n.status = 'approved' AND u.department = ?) OR
        (n.notification_type = 'user_added_by_admin' AND n.status = 'approved' AND n.user_id = ?)
    ";
    $params = array_fill(0, 17, $department);
    $params[] = $user_id;
    $types = str_repeat('s', 17) . 'i';
} elseif ($user_type === 'Super Admin') {
    $query .= "
        n.status = 'pending' OR
        n.notification_type = 'content_approved' OR
        n.notification_type = 'user_approved' OR
        n.notification_type = 'user_approved_by_admin' OR
        n.notification_type = 'user_rejected' OR
        (n.notification_type = 'content_post' AND n.status = 'approved') OR
        (n.notification_type = 'content_rejected' AND n.status = 'rejected') OR
        (n.notification_type = 'content_deleted' AND n.status = 'deleted') OR
        (n.notification_type = 'content_deleted_by_admin' AND n.status = 'deleted') OR
        (n.notification_type = 'content_archived' AND n.status = 'archived') OR
        (n.notification_type = 'content_archived_by_admin' AND n.status = 'archived') OR
        (n.notification_type = 'content_restored' AND n.status = 'restored') OR
        (n.notification_type = 'content_restored_by_admin' AND n.status = 'restored') OR
        (n.notification_type = 'user_edited' AND n.status = 'edited') OR
        (n.notification_type = 'user_deleted' AND n.status = 'deleted') OR
        (n.notification_type = 'user_edited_by_admin' AND n.status = 'edited' AND n.user_id = ?) OR
        (n.notification_type = 'user_added' AND n.status = 'approved') OR
        (n.notification_type = 'user_added_by_admin' AND n.status = 'approved' AND n.user_id = ?)
    ";
    $params = [$user_id, $user_id];
    $types = 'ii';
} else {
    $query .= "
        (n.user_id = ? AND n.status = 'pending') OR
        (n.notification_type = 'content_approved' AND n.user_id = ?) OR
        (n.notification_type = 'content_approved_by_admin' AND n.user_id = ?) OR
        (n.notification_type = 'content_rejected_by_admin' AND n.user_id = ?) OR
        (n.notification_type = 'content_deleted' AND n.user_id = ?) OR
        (n.notification_type = 'content_deleted_by_admin' AND n.user_id = ?) OR
        (n.notification_type = 'content_archived' AND n.user_id = ?) OR
        (n.notification_type = 'content_archived_by_admin' AND n.user_id = ?) OR
        (n.notification_type = 'content_restored' AND n.user_id = ?) OR
        (n.notification_type = 'content_restored_by_admin' AND n.user_id = ?) OR
        (n.notification_type = 'user_approved_by_admin' AND n.user_id = ?) OR
        (n.notification_type = 'user_edited_by_admin' AND n.user_id = ?) OR
        (n.notification_type = 'user_added_by_admin' AND n.user_id = ?)
    ";
    $params = array_fill(0, 13, $user_id);
    $types = str_repeat('i', 13);
}

$query .= ") ORDER BY n.created_at DESC";

error_log("User Type: " . $user_type);
error_log("User ID: " . $user_id);
error_log("Department: " . $department);
error_log("Query: " . $query);
error_log("Params: " . print_r($params, true));
error_log("Types: " . $types);

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