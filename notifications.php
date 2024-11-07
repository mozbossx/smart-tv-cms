<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// check if the user is not logged in, redirect to the login page (login.php?pageid=Login)
if (!isset($_SESSION['full_name']) || !isset($_SESSION['email'])) {
    header('location: login.php?pageid=Login');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// fetch user data for the currently logged-in user using prepared statements
$sql = "SELECT full_name, password, department, email, user_type FROM users_tb WHERE full_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $full_name);
$stmt->execute();
$result = $stmt->get_result();

// Check if user data is found
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    header('location: logout.php');
    exit;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
    <link rel="icon" type="image/png" href="images/usc_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Notifications</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <div id="notificationsList" class="content-container" 
                    style="height: auto; max-width: 600px;
                    margin: auto;
                    margin-bottom: 10px;
                    padding: 20px;
                    background-color: #f9f9f9;
                    border-radius: 8px;
                    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);">
                        <div class="scroll-div" style="height: auto; margin-bottom: 10px">
                            <h1 id="notificationsHeader" style="text-align: center;"></h1>
                            <div id="ViewAllNotificationsContainer" class="view-all-notifications-container" style="margin-top: 5px">
                                <!-- Latest notifications will be displayed here -->
                                <div class="notifications-tab" style="overflow-x: auto;">
                                    <button type="button" class="notifications-tablinks active" style="padding: 10px" onclick="openNotificationsTab(event, 'allNotificationsList')">All <span class="all-notifications-count"></span></button>
                                    <button type="button" class="notifications-tablinks" style="padding: 10px" onclick="openNotificationsTab(event, 'pendingNotificationsList')">Pending <span class="pending-notifications-count"></span></button>
                                    <button type="button" class="notifications-tablinks" style="padding: 10px" onclick="openNotificationsTab(event, 'approvedNotificationsList')">Approved <span class="approved-notifications-count"></span></button>
                                    <button type="button" class="notifications-tablinks" style="padding: 10px" onclick="openNotificationsTab(event, 'rejectedNotificationsList')">Rejected <span class="rejected-notifications-count"></span></button>
                                    <button type="button" class="notifications-tablinks" style="padding: 10px" onclick="openNotificationsTab(event, 'restoredNotificationsList')">Restored <span class="restored-notifications-count"></span></button>
                                    <button type="button" class="notifications-tablinks" style="padding: 10px" onclick="openNotificationsTab(event, 'archivedNotificationsList')">Archived <span class="archived-notifications-count"></span></button>
                                    <button type="button" class="notifications-tablinks" style="padding: 10px" onclick="openNotificationsTab(event, 'deletedNotificationsList')">Deleted <span class="deleted-notifications-count"></span></button>
                                    <button type="button" class="notifications-tablinks" style="padding: 10px" onclick="openNotificationsTab(event, 'updatedNotificationsList')">Updated <span class="updated-notifications-count"></span></button>
                                    <button type="button" class="notifications-tablinks" style="padding: 10px" onclick="openNotificationsTab(event, 'trashedNotificationsList')">Trashed <span class="trashed-notifications-count"></span></button>
                                </div>
                                <div id="allNotificationsList" class="notifications-tabcontent" style="display: block; height: 50vh; width: 90%; overflow-y: auto; border: none;"></div>
                                <div id="pendingNotificationsList" class="notifications-tabcontent" style="height: 50vh; width: 90%; overflow-y: auto; border: none;"></div>
                                <div id="approvedNotificationsList" class="notifications-tabcontent" style="height: 50vh; width: 90%; overflow-y: auto; border: none;"></div>
                                <div id="rejectedNotificationsList" class="notifications-tabcontent" style="height: 50vh; width: 90%; overflow-y: auto; border: none;"></div>
                                <div id="restoredNotificationsList" class="notifications-tabcontent" style="height: 50vh; width: 90%; overflow-y: auto; border: none;"></div>
                                <div id="archivedNotificationsList" class="notifications-tabcontent" style="height: 50vh; width: 90%; overflow-y: auto; border: none;"></div>
                                <div id="deletedNotificationsList" class="notifications-tabcontent" style="height: 50vh; width: 90%; overflow-y: auto; border: none;"></div>
                                <div id="updatedNotificationsList" class="notifications-tabcontent" style="height: 50vh; width: 90%; overflow-y: auto; border: none;"></div>
                                <div id="trashedNotificationsList" class="notifications-tabcontent" style="height: 50vh; width: 90%; overflow-y: auto; border: none;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>