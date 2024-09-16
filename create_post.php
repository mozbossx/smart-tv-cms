<?php
// Start the session
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// Fetch all features from the database
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");
$stmtNewFeatures = $pdo->query("SELECT * FROM features_tb");
$featuresNewFeatures = $stmtNewFeatures->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Create Post</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <?php include('error_message.php'); ?>
                    <div class="button-flex">
                        <div class="button-container">
                            <a href="form_announcement.php?pageid=AnnouncementForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-bullhorn"></i></div>
                                <div class="button-text">Announcement</div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="form_event.php?pageid=EventForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-calendar-check-o"></i></div>
                                <div class="button-text">Upcoming Event</div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="form_news.php?pageid=NewsForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-newspaper-o"></i></div>
                                <div class="button-text">News</div>
                            </a>
                        </div>
                        <div class="button-container">
                            <a href="form_promotional_material.php?pageid=PromotionalMaterialsForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                <div class="button-icon"><i class="fa fa-object-group"></i></div>
                                <div class="button-text">Promotional Material</div>
                            </a>
                        </div>
                        <?php foreach ($featuresNewFeatures as $feature): ?>
                            <div class="button-container">
                                <a href="<?php echo $feature['file_name']; ?>?pageid=<?php echo ucfirst($feature['feature_name']); ?>Form?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                    <div class="button-icon"><i class="fa <?php echo $feature['icon']; ?>"></i></div>
                                    <div class="button-text"><?php echo $feature['feature_name']; ?></div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($user_type === 'Admin'){ ?>
                            <div class="button-container">
                                <a href="general_info.php?pageid=GeneralInformationForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                    <div class="button-icon"><i class="fa fa-sitemap"></i></div>
                                    <div class="button-text">General Information</div>
                                </a>
                            </div>
                            <div class="button-container">
                                <a href="form_add_new_feature.php?pageid=AddNewFeatureForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                    <div class="button-icon"><i class="fa fa-plus"></i></div>
                                    <div class="button-text">Add New Feature</div>
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>