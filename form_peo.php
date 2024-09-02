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
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <title>Program Educational Objectives (PEO)</title>
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
                                <li class="breadcrumb-item active" aria-current="page">Program Educational Objectives (PEO)</li>
                            </ol>
                        </nav>
                        <form id="peoForm" enctype="multipart/form-data" class="main-form">
                            <?php include('error_message.php'); ?>
                            <input type="hidden" name="type" value="peo" readonly>
                            <div class="floating-label-container">
                                <textarea name="peo_title" rows="2" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="peo_title"></textarea>
                                <label for="peo_title" style="background: #FFFF; width: auto; padding: 5px; margin-top: 2px; border-radius: 0" class="floating-label-text-area">PEO Title</label>
                            </div>
                            <div class="floating-label-container">
                                <textarea name="peo_description" rows="3" placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="peo_description"></textarea>
                                <label for="peo_description" style="background: #FFFF; width: auto; padding: 5px; margin-top: 2px; border-radius: 0" class="floating-label-text-area">PEO Description</label>
                            </div>
                            <div>
                                <button type="button" id="changeBulletButton" class="preview-button" style="margin-left: 10px" onclick="openModal()">
                                    Change Bullet Symbol
                                </button>
                            </div>
                            <div class="floating-label-container">
                                <textarea name="peo_subdescription" rows="5" placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="peo_subdescription"></textarea>
                                <label for="peo_subdescription" style="background: #FFFF; width: auto; padding: 5px; margin-top: 2px; border-radius: 0" class="floating-label-text-area">PEO Sub-Description</label>
                            </div>
                            <?php include('misc/php/displaytime_tvdisplay.php')?>
                            <div style="display: flex; flex-direction: row; margin-left: auto; margin-top: 10px">
                                <div>
                                    <button type="button" name="preview" id="previewButton" class="preview-button" style="margin-right: 0" onclick="validateAndOpenPreviewModal()">
                                        <i class="fa fa-eye" style="padding-right: 5px"></i> Preview 
                                    </button>
                                </div>
                            </div>
                            <div class="modal" id="bulletSymbolModal" style="display:none;">
                                <div class="modal-content">
                                    <span class="close" onclick="closeModal()">&times;</span>
                                    <h2>Select Bullet Symbol</h2>
                                    <label><input type="checkbox" value="•" onchange="updateBulletSymbol(this)"> •</label><br>
                                    <label><input type="checkbox" value=">" onchange="updateBulletSymbol(this)"> ></label><br>
                                    <button onclick="closeModal()">Close</button>
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
    <script src="misc/js/wsform_submission.js"></script>
    <script src="misc/js/capitalize_first_letter.js"></script>
    <script>
        const containers = <?php echo json_encode($containers); ?>;
        const tvNames = <?php echo json_encode($tv_names); ?>;
        // Add bullet point to the new textarea
        var textarea = document.getElementById('peo_subdescription');
        let bulletSymbol = '• '; // Default bullet symbol
        textarea.value = bulletSymbol;

        function openModal() {
            document.getElementById('bulletSymbolModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('bulletSymbolModal').style.display = 'none';
        }

        function updateBulletSymbol(checkbox) {
            if (checkbox.checked) {
                bulletSymbol = checkbox.value + ' '; // Update bullet symbol
                textarea.value = textarea.value.replace(/^[• >]+/g, bulletSymbol); // Replace existing bullet
            }
        }

        function changeBulletSymbol() {
            const newSymbol = prompt("Enter new bullet symbol (e.g., -, >, abcd):", bulletSymbol.trim());
            if (newSymbol) {
                bulletSymbol = newSymbol + ' '; // Update bullet symbol
                textarea.value = textarea.value.replace(/^[• ]+/g, bulletSymbol); // Replace existing bullet
            }
        }

        // Update the event listener to use the new bullet symbol
        textarea.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Prevent the default newline
                const currentText = textarea.value;
                textarea.value = currentText + '\n' + bulletSymbol; // Add new bullet symbol on new line
            }
        });

        // Prevent deletion of the bullet point
        textarea.addEventListener('keydown', function(event) {
            const currentText = textarea.value;
            const cursorPosition = textarea.selectionStart;

            // Prevent deletion if the cursor is at the start or right after the bullet
            if (event.key === 'Backspace' || event.key === 'Delete') {
                if (cursorPosition <= 2) { // Prevent deletion if at the start or right after the bullet
                    event.preventDefault(); // Prevent deletion
                }
            }
        });    
    </script>
</body>
</html>