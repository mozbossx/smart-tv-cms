<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

include 'get_session.php';

include 'admin_access_only.php';

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
                            <a href="manage_templates.php?pageid=ManageTemplates?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-window-restore"></i></div>
                                <div class="button-text">Manage Templates</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/fetch_user_session.js"></script>
</body>
</html>