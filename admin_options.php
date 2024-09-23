<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// Fetch announcements and events content and assign it to $result
// header("Cache-Control: no-cache");
header("Connection: keep-alive");

// Add cache control headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if the user is not logged in, redirect to the login page
if (!isset($_SESSION['full_name'])) {
    header('location: index.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
// After successfully logging out
$_SESSION['logged_out'] = true;

// Get the user's department from the session
$department = $_SESSION['department'];

// Fetch user data for the currently logged-in user
$full_name = $_SESSION['full_name'];
$sql = "SELECT full_name AS full_name, password, department, email, user_type FROM users_tb WHERE full_name = '$full_name'";
$result = mysqli_query($conn, $sql);

// Check if user data is found
if (mysqli_num_rows($result) > 0) {
    $user_data = mysqli_fetch_assoc($result);
} else {
    $error[] = "No user data found";
}

// Check if the user is an Admin
if ($user_data['user_type'] === 'Admin') {
    $loggedInUserIsAdmin = true;
} else {
    $loggedInUserIsAdmin = false;
}
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
    <title>Admin Options</title>
</head>
<body>
<div class="main-section" id="all-content">
        <?php include('error_message.php'); ?>
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <h1 class="content-title" style="color: black"><i class="fa fa-user-secret" style="padding-right: 5px"></i>Admin Options</h1>
                    <div class="button-flex">
                        <div class="button-container">
                            <a href="manage_users.php?pageid=ManageUsers?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-users"></i></div>
                                <div class="button-text">Manage Users</div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="manage_smart_tvs.php?pageid=ManageSmartTVs?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-tv"></i></div>
                                <div class="button-text">Manage Smart TVs</div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="manage_posts.php?pageid=ManagePosts?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-newspaper-o"></i></div>
                                <div class="button-text">Manage Posts</div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="manage_templates.php?pageid=ManageTemplates?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-window-restore"></i></div>
                                <div class="button-text">Manage Templates</div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="trash.php?pageid=Trash?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-trash"></i></div>
                                <div class="button-text">Trash</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/fetch_notifications_count.js"></script>
    <script src="js/fetch_user_session.js"></script>
</body>
</html>