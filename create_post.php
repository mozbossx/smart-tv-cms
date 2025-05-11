<?php
// Start the session
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';
include 'user_theme_color.php';

// Fetch all features from the database
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
$stmtNewFeatures = $pdo->prepare("
    SELECT DISTINCT f.*, 
           GROUP_CONCAT(fut.user_type) as allowed_user_types 
    FROM features_tb f
    LEFT JOIN feature_user_types fut ON f.feature_id = fut.feature_id
    WHERE (f.department = :department AND (fut.user_type = :user_type OR :user_type = 'Admin'))
       OR :user_type = 'Super Admin'
    GROUP BY f.feature_id
");
$stmtNewFeatures->execute([':department' => $department, ':user_type' => $user_type]);
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
    <style>
        .button-icon {
            background: <?php echo $theme_color; ?>;
        }
    </style>
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
                        <?php if ($user_type === 'Admin' || $user_type === 'Super Admin'){ ?>
                            <div class="button-container">
                                <a href="general_info.php?pageid=GeneralInformationForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                    <div class="button-icon"><i class="fa fa-university"></i></div>
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
                    <div class="line-separator" style="margin-bottom: 20px"></div>
                    <?php 
                        if (count($featuresNewFeatures) == 0){
                            echo '<p style="text-align: left; font-size: 20px; color: <?php echo $dark_theme_color_40_percent; ?>;">No new features added.</p>';
                        }
                    ?>
                    <div class="button-flex" style="margin-top: 10px;">
                        <?php foreach ($featuresNewFeatures as $feature): ?>
                            <div class="button-container" style="height: auto; min-height: 90px" data-feature-id="<?php echo $feature['feature_id']; ?>">
                                <?php if ($user_type === 'Super Admin' || $user_type === 'Admin'){ ?>
                                <button class="delete-feature-button"
                                    style="display: block; position: relative; margin-left: 5px; margin-bottom: -32px; background: <?php echo $light_theme_color_70_percent?>; border: 1px black solid; cursor: pointer; color: <?php echo $dark_theme_color_40_percent; ?>; font-size: 15px; z-index: 5; padding: 5px; border-radius: 100%" 
                                    onclick="deleteFeatureModal(<?php echo $feature['feature_id']; ?>)"><i class="fa fa-trash"></i>
                                </button>
                                <?php } ?>
                                <a href="<?php echo $feature['file_name']; ?>?pageid=<?php echo ucfirst($feature['feature_name']); ?>Form?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" class="content-button">
                                    <div class="button-icon"><i class="fa <?php echo $feature['icon']; ?>"></i></div>
                                    <div style="display: flex; flex-direction: column">
                                    <div style="font-size: 10px; font-weight: bold; text-align: left; color: <?php echo $theme_color ?>; padding-left: 10px; padding-top: 10px"><?php echo $feature['department']; ?></div>
                                    <div class="button-text"><?php echo $feature['feature_name']; ?></div>
                                        <div class="user-type-pills">
                                            <?php 
                                            $userTypes = explode(',', $feature['allowed_user_types']);
                                            foreach ($userTypes as $type): ?>
                                                <span class="user-type-pill"><?php echo trim($type); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="confirmDeleteFeatureModal" class="modal">
        <div class="modal-content" style="padding: 15px;">
            <span class="close" onclick="document.getElementById('confirmDeleteFeatureModal').style.display='none'"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <h1 style="text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-trash"></i> Delete Feature</h1>
            <p style="text-align: left; font-weight: 500; font-size: 16px; color: #6E6E6E;">Proceed to delete this feature? Deleted features cannot be recovered.</p>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" style="margin-right: 5px;" onclick="document.getElementById('confirmDeleteFeatureModal').style.display='none'">Cancel</button>
                <button type="button" class="red-button" onclick="deleteFeature(document.getElementById('confirmDeleteFeatureModal').getAttribute('data-feature-id'))">Yes, delete</button>
            </div>
        </div>
    </div>
    <?php include('misc/php/success_modal.php') ?>
    <?php include('misc/php/error_modal.php') ?>
    <script>
        function deleteFeatureModal(featureId) {
            document.getElementById('confirmDeleteFeatureModal').setAttribute('data-feature-id', featureId);
            document.getElementById('confirmDeleteFeatureModal').style.display = 'flex';
        }

        function deleteFeature(featureId) {
            const data = {
                action: 'delete_feature',
                feature_id: featureId
            };
            
            // Send the data
            Ws.send(JSON.stringify(data));
        }

        Ws.onmessage = function(event) {
            const response = JSON.parse(event.data);
            
            if (response.action === 'delete_feature') {
                if (response.success == true) {
                    // Close the modal
                    document.getElementById('confirmDeleteFeatureModal').style.display = 'none';

                    // Remove the feature button
                    const featureButton = document.querySelector(`[data-feature-id="${response.feature_id}"]`);
                    if (featureButton) {
                        featureButton.remove();
                    }
                    
                    // Show success message
                    document.getElementById('successMessageModalVersion2').style.display = 'flex';
                    document.getElementById('successMessageVersion2').textContent = 'Feature was successfully deleted!';
                } else if (response.success == false) {
                    // Close delete modal
                    document.getElementById('confirmDeleteFeatureModal').style.display = 'none';
                    
                    // Show error message
                    document.getElementById('errorModalVersion2').style.display = 'flex';
                    document.getElementById('errorTextVersion2').textContent = response.message || 'Failed to delete feature';
                }
            }
        };
    </script>
</body>
</html>