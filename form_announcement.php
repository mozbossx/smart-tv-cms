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
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <title>Create an Announcement</title>
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
                                <li class="breadcrumb-item active" aria-current="page">Announcement Form</li>
                            </ol>
                        </nav>
                        <form id="announcementForm" enctype="multipart/form-data" class="main-form">
                            <?php include('error_message.php'); ?>
                            <input type="hidden" name="type" value="announcement">
                            <h1 style="text-align: center">Announcement Form</h1>
                            <div class="floating-label-container">
                                <textarea name="ann_body" rows="6" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="ann_body"></textarea>
                                <label for="ann_body" style="background: #FFFF; width: auto; padding: 5px; margin-top: 2px; border-radius: 0" class="floating-label-text-area">Announcement Body</label>
                            </div>
                            <?php include('misc/php/upload_preview_media.php')?>
                            <div class="form-row">
                                <?php include('misc/php/expiration_date.php')?>
                                <?php include('misc/php/displaytime_tvdisplay.php')?>
                            </div>
                            <?php include('misc/php/schedule_post.php')?>
                            <div style="display: flex; flex-direction: row; margin-left: auto; margin-top: 10px">
                                <div>
                                    <button type="button" id="saveDraftButton" class="preview-button" style="display: none; background: none; color: #316038; border: #316038 solid 1px">
                                        <i class="fa fa-file" style="padding-right: 5px"></i> Save Draft 
                                    </button>
                                </div>
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
    <div id="unsavedChangesModal" class="modal">
        <div class="modal-content">
            <div class="green-bar-vertical">
                <h1 style="color: #264B2B; font-size: 50px"><i class="fa fa-file" aria-hidden="true"></i></h1>
                <p>There are unsaved changes. Proceed to save as draft?</p>
                <br>
                <div style="align-items: right; text-align: right; right: 0">
                    <button type="button" id="cancelSaveDraftButton" class="green-button" style="background: none; color: #264B2B; border: 1px solid #264B2B">Cancel</button>
                    <button type="submit" id="confirmSaveDraftButton" class="green-button" style="margin-right: 0">Yes, Save Draft</button>
                </div>
            </div>
        </div>
    </div>
    <?php include('misc/php/error_modal.php') ?>
    <?php include('misc/php/success_modal.php') ?>    
    <script src="misc/js/wsform_submission.js"></script>
    <script>
        const containers = <?php echo json_encode($containers); ?>;
        const tvNames = <?php echo json_encode($tv_names); ?>; 
        const ws = new WebSocket('ws://192.168.1.19:8081?full_name=<?php echo urlencode($full_name); ?>&user_type=<?php echo urlencode($user_type); ?>&department=<?php echo urlencode($department); ?>');

        let isFormDirty = false;

        // Mark the form as dirty when any input changes
        document.querySelectorAll('.content-form input, .content-form textarea').forEach(input => {
            input.addEventListener('input', () => {
                isFormDirty = true;
                document.getElementById('saveDraftButton').style.display = "block";
            });
        });

        // Show confirmation dialog on page unload
        window.addEventListener('beforeunload', (event) => {
            if (isFormDirty) {
                const message = "There are unsaved changes. Proceed to go to a different page?";
                event.returnValue = message; // For most browsers
                return message; // For some older browsers
            }
        });

        function saveDraft() {
            const contentType = document.querySelector('[name="type"]').value;
            const form = document.getElementById(`${contentType}Form`);
            console.log("saveDraft() called");

            const formData = new FormData(form);
            const data = { 
                action: 'save_draft',
                tv_ids: formData.getAll('tv_id[]') // Collect all tv_id[] values
            };

            // Check for file input
            const mediaInput = document.getElementById('media');
            if (mediaInput && mediaInput.files.length > 0) {
                const mediaFile = mediaInput.files[0];
                const reader = new FileReader();

                reader.onload = function (e) {
                    const base64Data = e.target.result;
                    formData.set('media', base64Data);
                    formData.forEach((value, key) => {
                        data[key] = value;
                    });

                    ws.send(JSON.stringify(data));
                };

                reader.readAsDataURL(mediaFile);
            } else {
                formData.forEach((value, key) => {
                    data[key] = value;
                });

                console.log('Form Data:', data);
                ws.send(JSON.stringify(data));
            }

            // Listen for messages from the WebSocket server
            ws.onmessage = function(event) {
                const message = JSON.parse(event.data);
                if (message.success) {
                    document.getElementById('successMessage').textContent = contentType + " was drafted!";                        
                    document.getElementById('successMessageModal').style.display = 'flex';
                } else {
                    document.getElementById('errorText').textContent = "Error processing " + contentType + ". Try again later";
                    document.getElementById('errorModal').style.display = 'flex';
                }
            };

            ws.onerror = function (event) {
                console.error('WebSocket error:', event);
                document.getElementById('errorText').textContent = "Error processing " + contentType + ". Please try again later.";
                document.getElementById('errorModal').style.display = 'flex';
            };
        }

        document.getElementById('saveDraftButton').addEventListener('click', (event) => {
            if (isFormDirty) {
                event.preventDefault(); // Prevent default action
                const unsavedChangesModal = document.getElementById('unsavedChangesModal');
                
                // Show the modal
                unsavedChangesModal.style.display = "flex";

                // Add event listeners only once using one-time event handlers
                const confirmSaveDraftButton = document.getElementById('confirmSaveDraftButton');
                const cancelSaveDraftButton = document.getElementById('cancelSaveDraftButton');

                confirmSaveDraftButton.onclick = function() {
                    saveDraft();
                    unsavedChangesModal.style.display = "none"; // Close the modal after saving
                };

                cancelSaveDraftButton.onclick = function() {
                    unsavedChangesModal.style.display = "none";
                };

            } else {
                saveDraft(); // Directly save if no unsaved changes
            }
        });
    </script>
</body>
</html>