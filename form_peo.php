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
                                <textarea name="peo_description" rows="4" placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="peo_description"></textarea>
                                <label for="peo_description" style="background: #FFFF; width: auto; padding: 5px; margin-top: 2px; border-radius: 0" class="floating-label-text-area">PEO Description</label>
                            </div>
                            <div class="input-container">
                                <div class="floating-label-container" style="display: none; flex-direction: row; align-items: center; background: #e4e4e4; margin-top: 5px; padding: 10px; border-radius: 5px; border: 1px solid black" id="peoSubDescription">
                                    <textarea name="peo_subdescription" rows="3" placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" style="margin-left: 5px"></textarea>
                                    <label for="peo_subdescription" style="background: #FFFF; width: auto; padding: 5px; left: 20px; margin-top: 15px; border-radius: 0" class="floating-label-text-area">PEO Sub-description</label>
                                </div>
                            </div>
                            <div id="subDescriptionContainerIncrement"></div> <!-- Container for dynamically added input fields -->
                            <div style="display: flex; flex-direction: row">
                                <button class="plus-button" type="button" onclick="addPEOsubdescription()" style="display: block" id="add_peo_button"><i class="fa fa-plus-circle" aria-hidden="true"></i> New PEO Sub-description</button>
                                <!-- <button class="delete-peo-button" type="button" onclick="deletePEOsubdescription()" style="display: none" id="deletePEOSubButton"><i class="fa fa-times" aria-hidden="true"></i> Cancel</button> -->
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
    <div id="confirmDeleteModal" class="modal">
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="confirmDeleteNo" style="color: #7E0B22"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
                <p>Proceed to delete this subdescription?</p>
                <br>
                <div style="align-items: right; text-align: right; right: 0">
                    <button id="confirmDeleteYes" class="red-button" style="margin-right: 0"><b>Yes, delete sub-description</b></button>
                </div>
            </div>
        </div>
    </div>
    <?php include('misc/php/error_modal.php') ?>
    <?php include('misc/php/success_modal.php') ?>    
    <script src="misc/js/wsform_submission.js"></script>
    <script>
        let peoCounter = 1; // Initialize a counter for generating unique IDs
        const maxPEOsubDescriptions = 11;
        const containers = <?php echo json_encode($containers); ?>;
        const tvNames = <?php echo json_encode($tv_names); ?>;
        
        // function validateAndOpenPreviewModal() {
        //     var peo_title = document.querySelector('[name="peo_title"]').value;
        //     var peo_description = document.querySelector('[name="peo_description"]').value;

        //     // Collect all subdescription textareas
        //     var peo_subdescriptions = document.querySelectorAll('#subDescriptionContainerIncrement textarea');
        //     var allSubDescriptionsFilled = true;

        //     // Check if any of the subdescription textareas is empty
        //     peo_subdescriptions.forEach(function(textarea) {
        //         if (textarea.value.trim() === "") {
        //             allSubDescriptionsFilled = false;
        //         }
        //     });

        //     // Check if any of the required fields is empty
        //     if (peo_title.trim() === "" || peo_description === "" || !allSubDescriptionsFilled) {
        //         // If conditions are not met, show error message
        //         errorModalMessage("Please fill the necessary fields.");
        //     }
        // }
        
        function generateUniqueId() {
            return 'peo_' + peoCounter++;
        }

        function addPEOsubdescription() {
            if (peoCounter < maxPEOsubDescriptions) { // Change this condition
                var peoSubDescription = document.getElementById("peoSubDescription");
                var clone = peoSubDescription.cloneNode(true); // Clone the element

                // Create a new delete button for the cloned textarea
                var deleteButton = document.createElement("button");
                deleteButton.type = "button";
                deleteButton.classList.add('delete-peo-button');
                deleteButton.innerHTML = '<i class="fa fa-times" aria-hidden="true"></i> Delete';
                deleteButton.onclick = function() {
                    deletePEOsubdescription(clone, clone.querySelector('textarea')); // Pass the textarea
                };
                clone.appendChild(deleteButton); // Append the delete button to the cloned element

                // Change the id of the cloned element to avoid duplicates
                var newId = generateUniqueId();
                clone.setAttribute("id", newId);
                console.log(peoCounter);

                // Set display to block for the cloned element
                clone.style.display = "flex";

                var textarea = clone.querySelector('textarea');
                

                // Append the cloned element to the container
                document.getElementById("subDescriptionContainerIncrement").appendChild(clone);
                
                // Check if the maximum has been reached
                if (peoCounter >= maxPEOsubDescriptions) {
                    document.getElementById("add_peo_button").style.display = "none"; // Hide button
                }
            } else {
                alert("Maximum of 10 subdescriptions can be added.");
            }
        }

        function deletePEOsubdescription(clone, textarea) {
            // Check if the textarea is not empty
            if (textarea.value.trim() !== "") {
                // Show confirmation modal
                document.getElementById("confirmDeleteModal").style.display = "flex";

                // Handle confirmation
                document.getElementById("confirmDeleteYes").onclick = function() {
                    var container = document.getElementById("subDescriptionContainerIncrement");
                    container.removeChild(clone); // Remove the specific cloned textarea
                    --peoCounter;

                    // Check if we need to show the button again
                    if (peoCounter < maxPEOsubDescriptions) {
                        document.getElementById("add_peo_button").style.display = "block"; // Show button
                    }

                    document.getElementById("confirmDeleteModal").style.display = "none"; // Hide modal
                };

                document.getElementById("confirmDeleteNo").onclick = function() {
                    document.getElementById("confirmDeleteModal").style.display = "none"; // Hide modal
                };
            } else {
                // Directly delete if textarea is empty
                var container = document.getElementById("subDescriptionContainerIncrement");
                container.removeChild(clone); // Remove the specific cloned textarea
                --peoCounter;

                // Check if we need to show the button again
                if (peoCounter < maxPEOsubDescriptions) {
                    document.getElementById("add_peo_button").style.display = "block"; // Show button
                }
            }
        }
    </script>
</body>
</html>