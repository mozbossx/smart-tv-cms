<?php
// Start the session
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// fetch tv data from the select options
include 'misc/php/options_tv.php';

// Student, Faculty, or Staff do not have access to this page
include 'admin_access_only.php';

// Check if we're in edit mode and have media data
$isEditMode = isset($_GET['action']) && $_GET['action'] === 'edit';
$contentData = null;

if ($isEditMode && isset($_GET['peo_id'])) {
    // Fetch the peo data
    $stmt = $conn->prepare("SELECT * FROM peo_tb WHERE peo_id = ? AND tv_id = ?");
    $stmt->bind_param("ii", $_GET['peo_id'], $_GET['tv_id']);
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
    <title><?php echo $isEditMode ? 'Edit Program Educational Objective (PEO)' : 'Create a Program Educational Objective (PEO)'; ?></title>
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
                                <li class="breadcrumb-item"><a href="user_home.php?pageid=UserHome?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Home</a></li>
                                <li class="breadcrumb-item">
                                    <a href="tv_contents.php?tvId=<?php echo $_GET['tv_id']; ?>&isIframe=true">
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
                                    <a href="create_post.php?pageid=CreatePost?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Create Post</a>
                                </li>
                                <li class="breadcrumb-item"><a href="general_info.php?pageid=GeneralInformationForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">General Information</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Program Educational Objectives (PEO) Form</li>
                            <?php endif; ?>
                            </ol>
                        </nav>
                        <form id="peoForm" enctype="multipart/form-data" class="main-form">
                            <?php include('error_message.php'); ?>
                            <input type="hidden" name="type" value="peo" readonly>
                            <?php if ($isEditMode && isset($_GET['peo_id'])): ?>
                                <input type="hidden" name="peo_id" value="<?php echo htmlspecialchars($_GET['peo_id']); ?>">
                                <input type="hidden" name="action_value" value="edit">
                            <?php endif; ?>
                            <h1 style="text-align: center">Program Educational Objectives (PEO) Form</h1>
                            <div class="floating-label-container">
                                <div id="quillPeoTitleEditorContainer">
                                    <label for="quillPeoTitleEditorContainer" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: <?php echo $theme_color; ?>; font-size: 12px; font-weight: bold">PEO Title</label>
                                    <div id="peo_title"></div>
                                </div>
                                <input type="hidden" name="peo_title" id="peoTitleHiddenInput">
                                <?php if ($isEditMode && $contentData): ?>
                                    <input type="hidden" id="existing_peo_content_title" value="<?php echo htmlspecialchars($contentData['peo_title']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="floating-label-container">
                                <div id="quillPeoDescriptionEditorContainer">
                                    <label for="quillPeoDescriptionEditorContainer" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: <?php echo $theme_color; ?>; font-size: 12px; font-weight: bold">PEO Description</label>
                                    <div id="peo_description" style="height: 100px;"></div>
                                </div>
                                <input type="hidden" name="peo_description" id="peoDescriptionHiddenInput">
                                <?php if ($isEditMode && $contentData): ?>
                                    <input type="hidden" id="existing_peo_content_description" value="<?php echo htmlspecialchars($contentData['peo_description']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="floating-label-container">
                                <div id="quillPeoSubdescriptionEditorContainer">
                                    <label for="quillPeoSubdescriptionEditorContainer" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: <?php echo $theme_color; ?>; font-size: 12px; font-weight: bold">PEO Sub-Description (Optional)</label>
                                    <div id="peo_subdescription" style="height: 150px;"></div>
                                </div>
                                <input type="hidden" name="peo_subdescription" id="peoSubdescriptionHiddenInput">
                                <?php if ($isEditMode && $contentData): ?>
                                    <input type="hidden" id="existing_peo_content_subdescription" value="<?php echo htmlspecialchars($contentData['peo_subdescription']); ?>">
                                <?php endif; ?>
                            </div>
                            <?php 
                                $type = 'peo';
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
        const tvNames = <?php echo !empty($tv_names) ? json_encode($tv_names) : '{}'; ?>; 
    </script>
</body>
</html>