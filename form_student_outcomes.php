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
    <title>Student Outcomes (SO)</title>
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
                                <li class="breadcrumb-item"><a href="create_post.php?pageid=CreatePost?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="color: #264B2B">Create Post</a></li>
                                <li class="breadcrumb-item"><a href="general_info.php?pageid=GeneralInformationForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="color: #264B2B">General Information</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Student Outcomes (SO)</li>
                            </ol>
                        </nav>
                        <form id="soForm" enctype="multipart/form-data" class="main-form">
                            <?php include('error_message.php'); ?>
                            <h1 style="text-align: center">Student Outcomes (SO) Form</h1>
                            <input type="hidden" name="type" value="so" readonly>
                            <div class="floating-label-container">
                                <div id="quillSoTitleEditorContainer">
                                    <label for="quillSoTitleEditorContainer" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: #264B2B; font-size: 12px; font-weight: bold">SO Title</label>
                                    <div id="so_title"></div>
                                </div>
                                <input type="hidden" name="so_title" id="soTitleHiddenInput">
                            </div>
                            <div class="floating-label-container">
                                <div id="quillSoDescriptionEditorContainer">
                                    <label for="quillSoDescriptionEditorContainer" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: #264B2B; font-size: 12px; font-weight: bold">SO Description</label>
                                    <div id="so_description" style="height: 100px;"></div>
                                </div>
                                <input type="hidden" name="so_description" id="soDescriptionHiddenInput">
                            </div>
                            <div class="floating-label-container">
                                <div id="quillSoSubdescriptionEditorContainer">
                                    <label for="quillSoSubdescriptionEditorContainer" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: #264B2B; font-size: 12px; font-weight: bold">SO Sub-Description (Optional)</label>
                                    <div id="so_subdescription" style="height: 150px;"></div>
                                </div>
                                <input type="hidden" name="so_subdescription" id="soSubdescriptionHiddenInput">
                            </div>
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
    <script src="misc/js/capitalize_first_letter.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <script src="misc/js/quill_textarea_submission.js"></script>
    <script src="misc/js/wsform_submission.js"></script>
    <script>
        const containers = <?php echo json_encode($containers); ?>;
        const tvNames = <?php echo json_encode($tv_names); ?>;
    </script>
</body>
</html>