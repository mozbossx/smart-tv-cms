<?php
                    // Start the session and include the configuration
                    session_start();
                    include 'config_connection.php';
                    
                    // fetch user data for the currently logged-in user
                    include 'get_session.php';
                    
                    // fetch tv data from the select options
                    include 'misc/php/options_tv.php';
                    ?>
                    
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <!-- Include your standard head content here -->
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
                                                    <li class="breadcrumb-item active" aria-current="page">old announcement Form</li>
                                                </ol>
                                            </nav>
                                            <form id="old announcementForm" enctype="multipart/form-data" class="main-form">
                                                <?php include('error_message.php'); ?>
                                                <input type="hidden" name="type" value="old_announcement">
                                                <h1 style="text-align: center">old announcement Form</h1>
                                                
                <div class="floating-label-container">
                    <div id="quillEditorContainer_hello">
                        <label for="quillEditorContainer_hello" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: #264B2B; font-size: 12px; font-weight: bold">Hello</label>
                        <div id="hello" style="height: 150px;"></div>
                    </div>
                    <input type="hidden" name="hello" id="helloHiddenInput">
                </div>
                                                <?php include('misc/php/upload_preview_media.php')?>
                                                <div class="form-row">
                                                    <?php include('misc/php/expiration_date.php')?>
                                                    <?php include('misc/php/displaytime_tvdisplay.php')?>
                                                </div>
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
                                                <?php include('misc/php/preview_modal.php') ?>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php include('misc/php/error_modal.php') ?>
                        <?php include('misc/php/success_modal.php') ?>
                        <?php include('misc/php/save_draft_modal.php') ?>
                        <script src="misc/js/capitalize_first_letter.js"></script>
                        <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
                        <script src="misc/js/quill_textarea_submission.js"></script>
                        <script src="misc/js/wsform_submission.js"></script>
                        <script>
                            const containers = <?php echo json_encode($containers); ?>;
                            const tvNames = <?php echo json_encode($tv_names); ?>; 
                            const userType = '<?php echo $user_type; ?>';
                        </script>
                    </body>
                    </html>