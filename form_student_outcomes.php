<?php
// Start the session
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// fetch tv data from the select options
include 'misc/php/options_tv.php';

// Student or Faculty do not have access to this page
if($user_type == 'Student'|| $user_type == 'Faculty'){
    header("location: user_home.php?pageid=UserHome&userId=$user_id''$full_name");
    exit;
}

// Check if we're in edit mode and have media data
$isEditMode = isset($_GET['action']) && $_GET['action'] === 'edit';
$contentData = null;

if ($isEditMode && isset($_GET['so_id'])) {
    // Fetch the so data
    $stmt = $conn->prepare("SELECT * FROM so_tb WHERE so_id = ? AND tv_id = ?");
    $stmt->bind_param("ii", $_GET['so_id'], $_GET['tv_id']);
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
    <title><?php echo $isEditMode ? 'Edit Student Outcomes (SO)' : 'Create a Student Outcomes (SO)'; ?></title>
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
                                <li class="breadcrumb-item"><a href="general_info.php?pageid=GeneralInformationForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="color: #264B2B">General Information</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Student Outcomes (SO)</li>
                            <?php endif; ?>
                            </ol>
                        </nav>
                        <form id="soForm" enctype="multipart/form-data" class="main-form">
                            <?php include('error_message.php'); ?>
                            <input type="hidden" name="type" value="so" readonly>
                            <?php if ($isEditMode && isset($_GET['so_id'])): ?>
                                <input type="hidden" name="so_id" value="<?php echo htmlspecialchars($_GET['so_id']); ?>">
                                <input type="hidden" name="action_value" value="edit">
                            <?php endif; ?>
                            <h1 style="text-align: center">Student Outcomes (SO) Form</h1>
                            <div class="floating-label-container">
                                <div id="quillSoTitleEditorContainer">
                                    <label for="quillSoTitleEditorContainer" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: #264B2B; font-size: 12px; font-weight: bold">SO Title</label>
                                    <div id="so_title"></div>
                                </div>
                                <input type="hidden" name="so_title" id="soTitleHiddenInput">
                                <?php if ($isEditMode && $contentData): ?>
                                    <input type="hidden" id="existing_so_content_title" value="<?php echo htmlspecialchars($contentData['so_title']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="floating-label-container">
                                <div id="quillSoDescriptionEditorContainer">
                                    <label for="quillSoDescriptionEditorContainer" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: #264B2B; font-size: 12px; font-weight: bold">SO Description</label>
                                    <div id="so_description" style="height: 100px;"></div>
                                </div>
                                <input type="hidden" name="so_description" id="soDescriptionHiddenInput">
                                <?php if ($isEditMode && $contentData): ?>
                                    <input type="hidden" id="existing_so_content_description" value="<?php echo htmlspecialchars($contentData['so_description']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="floating-label-container">
                                <div id="quillSoSubdescriptionEditorContainer">
                                    <label for="quillSoSubdescriptionEditorContainer" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: #264B2B; font-size: 12px; font-weight: bold">SO Sub-Description (Optional)</label>
                                    <div id="so_subdescription" style="height: 150px;"></div>
                                </div>
                                <input type="hidden" name="so_subdescription" id="soSubdescriptionHiddenInput">
                                <?php if ($isEditMode && $contentData): ?>
                                    <input type="hidden" id="existing_so_content_subdescription" value="<?php echo htmlspecialchars($contentData['so_subdescription']); ?>">
                                <?php endif; ?>
                            </div>
                            <?php 
                                $type = 'so';
                            ?>
                            <?php include('misc/php/displaytime_tvdisplay.php')?>
                            <div style="display: flex; flex-direction: row; margin-left: auto; margin-top: 10px">
                                <div>
                                    <button type="button" name="preview" id="previewButton" class="preview-button" style="margin-right: 0" onclick="validateAndOpenPreviewModal()">
                                        <i class="fa fa-eye" style="padding-right: 5px"></i> Preview 
                                    </button>
                                </div>
                            </div>
                            <?php include('misc/php/preview_modal.php') ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('misc/php/error_modal.php') ?>
    <?php include('misc/php/success_modal.php') ?>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <script src="misc/js/quill_textarea_submission.js"></script>
    <script src="misc/js/wsform_submission.js"></script>
    <script src="misc/js/is_form_dirty.js"></script>
    <script>
        const containers = <?php echo json_encode($containers); ?>;
        const tvNames = <?php echo json_encode($tv_names); ?>;
    </script>
</body>
</html>