<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

include 'get_session.php';
include 'user_theme_color.php';

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Manage Contents</title>
    <style>
        .button-icon {
            background: <?php echo $theme_color; ?>;
        }
    </style>
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
                            <a href="trash.php?pageid=Trash?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-trash"></i></div>
                                <div class="button-text">Trash Contents <span id="trashContentButtonCount" class="trash-content-button-count"></span></div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="archives.php?pageid=Archives?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-archive"></i></div>
                                <div class="button-text">Archived Contents <span id="archivedContentButtonCount" class="archived-content-button-count"></span></div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="view_approved_contents.php?pageid=ViewApprovedContents&userId=<?php echo $user_id; ?>&userType=<?php echo $user_type; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-check"></i></div>
                                <div class="button-text">Approved Contents <span id="approvedContentButtonCount" class="approved-content-button-count"></span></div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="view_rejected_contents.php?pageid=ViewRejectedContents&userId=<?php echo $user_id; ?>&userType=<?php echo $user_type; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-times"></i></div>
                                <div class="button-text">Rejected Contents <span id="rejectedContentButtonCount" class="rejected-content-button-count"></span></div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="view_pending_contents.php?pageid=ViewPendingContents&userId=<?php echo $user_id; ?>&userType=<?php echo $user_type; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-clock-o"></i></div>
                                <div class="button-text">Pending Contents <span id="pendingContentButtonCount" class="pending-content-button-count"></span></div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>