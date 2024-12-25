<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// Check if user has access to this feature
include 'new_features/newfeature_check_feature_access.php';

// fetch tv data from the select options
include 'misc/php/options_tv.php';

// Check if we're in edit mode and have media data
$isEditMode = isset($_GET['action']) && $_GET['action'] === 'edit';
$contentData = null;

if ($isEditMode && isset($_GET['new_feature_id'])) {
    // Fetch the content data
    $stmt = $conn->prepare("SELECT * FROM new_feature_tb WHERE new_feature_id = ? AND tv_id = ?");
    $stmt->bind_param("ii", $_GET['new_feature_id'], $_GET['tv_id']);
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
    <title><?php echo $isEditMode ? 'Edit New Feature' : 'Create a New Feature'; ?></title>
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
                                <li class="breadcrumb-item active" aria-current="page">New Feature Form</li>
                            <?php endif; ?>
                            </ol>
                        </nav>
                        <form id="new_featureForm" enctype="multipart/form-data" class="main-form">
                            <?php include('error_message.php'); ?>
                            <input type="hidden" name="type" value="new_feature">
                            <?php if ($isEditMode && isset($_GET['new_feature_id'])): ?>
                                <input type="hidden" name="new_feature_id" value="<?php echo htmlspecialchars($_GET['new_feature_id']); ?>">
                                <input type="hidden" name="action_value" value="edit">
                            <?php endif; ?>
                            <h1 style="text-align: center">New Feature Form</h1>
                            
                <div class="floating-label-container">
                    <div id="quillEditorContainer_test" class="quill-editor-container-newfeature">
                        <label for="quillEditorContainer_test" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: #264B2B; font-size: 12px; font-weight: bold">Test</label>
                        <div id="test" style="height: 150px;"></div>
                    </div>
                    <input type="hidden" name="test" id="testHiddenInput">
                    <?php if ($isEditMode && $contentData): ?>
                        <input type="hidden" id="existing_test_content" value="<?php echo htmlspecialchars($contentData['test']); ?>">
                    <?php endif; ?>
                </div>
                            <div class="form-row">
                            <?php 
                                $type = 'new_feature';
                            ?>

                            <?php include('misc/php/expiration_date.php')?>

                                <?php include('misc/php/displaytime_tvdisplay.php')?>
                            </div>
                            <div style="display: flex; flex-direction: row; margin-left: auto; margin-top: 10px">
                                <div>
                                    <button type="button" name="preview" id="previewButton" class="preview-button" style="margin-right: 0" onclick="validateAndOpenNewFeaturePreviewModal()">
                                        <i class="fa fa-eye" style="padding-right: 5px"></i> Preview 
                                    </button>
                                </div>
                            </div>
                            <?php include('new_features/newfeature_preview_modal.php') ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('new_features/newfeature_error_modal.php') ?>
    <?php include('new_features/newfeature_success_modal.php') ?>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <script src="new_features/newfeature_wsform_submission.js"></script>
    <script>
        const containers = <?php echo json_encode($containers); ?>;
        const tvNames = <?php echo json_encode($tv_names); ?>; 

        document.addEventListener('DOMContentLoaded', function() {
            
                                        var testQuill = new Quill('#test', {
                                            theme: 'snow',
                                            placeholder: 'Enter Test',
                                            modules: {
                                                toolbar: [
                                                    ['bold', 'italic', 'underline'],
                                                    ['link'],
                                                    [{ 'list': 'ordered'}, { 'list': 'bullet' }]
                                                ]
                                            }
                                        });
            // If edit mode, populate the editor
            const existingContent = document.querySelector('#existing_test_content');
            if (existingContent) {
                testQuill.root.innerHTML = existingContent.value;
            }
                                    

            const form = document.getElementById('new_featureForm');
            form.onsubmit = function(e) {
                e.preventDefault();
                submitFormViaWebSocket();
            };
        });
    </script>
</body>
</html>