<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// fetch tv data from the select options
include 'misc/php/options_tv.php';

// Check if we're in edit mode and have media data
$isEditMode = isset($_GET['action']) && $_GET['action'] === 'edit';
$contentData = null;

if ($isEditMode && isset($_GET['facility_id'])) {
    // Fetch the facility data
    $stmt = $conn->prepare("SELECT * FROM facility_tb WHERE facility_id = ? AND tv_id = ?");
    $stmt->bind_param("ii", $_GET['facility_id'], $_GET['tv_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $contentData = $result->fetch_assoc();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <title><?php echo $isEditMode ? 'Edit facility' : 'Create an facility'; ?></title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <div class="content-form">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb" style="background: none">
                            <?php if ($isEditMode): ?>
                                <li class="breadcrumb-item"><a href="user_home.php?pageid=UserHome?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="color: #264B2B">Home</a></li>
                                <li class="breadcrumb-item">
                                    <a href="tv_contents.php?tvId=<?php echo $_GET['tv_id']; ?>&isIframe=true" style="color: #264B2B">
                                        <?php 
                                        // Fetch TV name based on tv_id
                                        $stmt = $conn->prepare("SELECT tv_name FROM smart_tvs_tb WHERE tv_id = ?");
                                        $stmt->bind_param("i", $_GET['tv_id']);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $tv = $result->fetch_assoc();
                                        echo htmlspecialchars($tv['tv_name']); 
                                        ?>
                                    </a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Edit</li>
                            <?php else: ?>
                                <li class="breadcrumb-item">
                                    <a href="create_post.php?pageid=CreatePost?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="color: #264B2B">Create Post</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Facility Form</li>
                            <?php endif; ?>
                            </ol>
                        </nav>
                        <form id="facilityForm" enctype="multipart/form-data" class="main-form">
                            <?php include('error_message.php'); ?>
                            <input type="hidden" name="type" value="facility">
                            <!--    const isEditMode = form.querySelector('input[name="action"]')?.value === 'edit'; -->
                            <?php if ($isEditMode && isset($_GET['facility_id'])): ?>
                                <input type="hidden" name="facility_id" value="<?php echo htmlspecialchars($_GET['facility_id']); ?>">
                                <input type="hidden" name="action_value" value="edit">
                            <?php endif; ?>
                            <h1 style="text-align: center">Facility Form</h1>
                            <!-- <div class="floating-label-container">
                                <textarea name="facility_body" rows="6" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="facility_body"></textarea>
                                <label for="facility_body" style="background: #FFFF; width: auto; padding: 5px; margin-top: 2px; border-radius: 0" class="floating-label-text-area">facility Body</label>
                            </div> -->
                            <div class="preview-media" style="border: #000 1px solid; border-radius: 5px; background: white; text-align: center; width: 100%; height: 350px; justify-content: center; align-items: center; margin-top: 15px">
                                <video id="video-preview" width="100%" height="350px" controls style="display:none; border-radius: 5px; background: #000;"></video>
                                <img id="image-preview" style="display:none; max-width: 100%; max-height: 100%;">
                            </div>
                            <div class="floating-label-container">
                                <div id="quillEditorContainer">
                                    <label for="quillEditorContainer" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: #264B2B; font-size: 12px; font-weight: bold">Facility Body</label>
                                    <div id="facility_body" style="height: 150px;"></div>
                                </div>
                                <input type="hidden" name="facility_body" id="facilityBodyHiddenInput">
                                <?php if ($isEditMode && $contentData): ?>
                                    <input type="hidden" id="existing_facility_content" value="<?php echo htmlspecialchars($contentData['facility_body']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="form-row">
                                <?php 
                                    $type = 'facility';
                                ?>
                                <?php include('misc/php/expiration_date.php')?>
                                <?php include('misc/php/displaytime_tvdisplay.php')?>
                            </div>
                            <?php if (!$isEditMode): ?>
                            <?php include('misc/php/schedule_post.php')?>
                            <div style="display: flex; flex-direction: row; margin-left: auto; margin-top: 10px">
                                <div>
                                    <button type="button" id="schedulePostButton" class="preview-button" style="background: none; color: #316038; border: #316038 solid 1px">
                                        <i class="fa fa-calendar" style="padding-right: 5px"></i> Schedule Post 
                                    </button>
                                </div>
                                <div>
                                    <button type="button" name="preview" id="previewButton" class="preview-button" style="margin-right: 0" onclick="validateAndOpenPreviewModal()">
                                        <i class="fa fa-eye" style="padding-right: 5px"></i> Preview 
                                    </button>
                                </div>
                            </div>
                            <?php else: ?>
                            <div style="display: flex; flex-direction: row; margin-left: auto; margin-top: 10px">
                                <div>
                                    <button type="button" name="preview" id="previewButton" class="preview-button" style="margin-right: 0" onclick="validateAndOpenPreviewModal()">
                                        <i class="fa fa-eye" style="padding-right: 5px"></i> Preview 
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php include('misc/php/preview_modal.php') ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="misc/js/is_form_dirty.js"></script>
    <?php include('misc/php/error_modal.php') ?>
    <?php include('misc/php/success_modal.php') ?>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <script src="misc/js/quill_textarea_submission.js"></script>
    <script src="misc/js/wsform_submission.js"></script>
    <script>
        const containers = <?php echo json_encode($containers); ?>;
        const tvNames = <?php echo !empty($tv_names) ? json_encode($tv_names) : '{}'; ?>; 
    </script>
</body>
</html>