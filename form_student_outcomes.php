<?php
// Start the session
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// fetch tv data
include 'display_tv_select.php';

// Student and Faculty do not have access to this page
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
    <title>Student Outcomes (SO)</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <h1 class="content-title" style="color: black"><i class="fa fa-sitemap" style="padding-right: 5px"></i>Student Outcomes (SO)</h1>
                    <button type="button" class="back-button" onclick="javascript:history.back()"><i class="fa fa-arrow-left" style="padding-right: 5px"></i>Back</button>
                    <div class="content-form">
                        <form id="soForm" enctype="multipart/form-data">
                            <div class="line-separator"></div>
                            <?php include('error_message.php'); ?>
                            <input type="hidden" name="type" value="so" readonly>
                            <div class="floating-label-container">
                                <textarea name="so_title" rows="1" placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="so_title"></textarea>
                                <label for="so_title" style="background: #FFFF; width: auto; padding: 5px; border-radius: 0" class="floating-label-text-area">SO Title</label>
                            </div>
                            <div class="floating-label-container">
                                <textarea name="so_description" rows="4" placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="so_description"></textarea>
                                <label for="so_description" style="background: #FFFF; width: auto; padding: 5px; border-radius: 0" class="floating-label-text-area">SO Description</label>
                            </div>
                            <div class="input-container">
                                <div class="floating-label-container" style="display: none; flex-direction: row; align-items: center; margin-left: 25px;" id="soSubDescription">
                                    <i class="fa fa-caret-right" aria-hidden="true" style="margin-right: 5px; font-size: 20px"></i>
                                    <textarea name="so_subdescription" rows="2" placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" style="margin-left: 5px" id="so_subdescription"></textarea>
                                    <label for="so_subdescription" style="background: #FFFF; width: auto; padding: 5px; border-radius: 0" class="floating-label-text-area">SO Sub-description</label>
                                </div>
                            </div>
                            <div id="subDescriptionContainerIncrement"></div> <!-- Container for dynamically added input fields -->
                            <div style="display: flex; flex-direction: row">
                                <button class="plus-button" type="button" onclick="addsosubdescription()" id="add_so_button"><i class="fa fa-plus-circle" aria-hidden="true"></i> New SO Sub-description</button>
                                <button class="delete-so-button" type="button" onclick="deletesosubdescription()" style="display: none" id="deletesoSubButton"><i class="fa fa-times" aria-hidden="true"></i> Cancel</button>
                            </div>
                            <div class="form-column" style="flex: 1">
                                    <div class="floating-label-container" style="flex: 1">
                                        <select id="display_time" name="display_time" class="floating-label-input" required style="background: #FFFF">
                                            <option value="">~</option>
                                            <option value="10">10 seconds</option>
                                            <option value="11">11 seconds</option>
                                            <option value="12">12 seconds</option>
                                            <option value="13">13 seconds</option>
                                            <option value="14">14 seconds</option>
                                            <option value="15">15 seconds</option>
                                            <option value="16">16 seconds</option>
                                            <option value="17">17 seconds</option>
                                            <option value="18">18 seconds</option>
                                            <option value="19">19 seconds</option>
                                            <option value="20">20 seconds</option>
                                            <option value="21">21 seconds</option>
                                            <option value="22">22 seconds</option>
                                            <option value="23">23 seconds</option>
                                            <option value="24">24 seconds</option>
                                            <option value="25">25 seconds</option>
                                            <option value="26">26 seconds</option>
                                            <option value="27">27 seconds</option>
                                            <option value="28">28 seconds</option>
                                            <option value="29">29 seconds</option>
                                            <option value="30">30 seconds</option>
                                        </select>
                                        <label for="display_time" class="floating-label">Display Time (seconds)</label>
                                    </div>
                                    <div class="floating-label-container" style="flex: 1">
                                        <select id="tv_account_select" name="tv_display" class="floating-label-input" style="background: #FFFF">
                                            <option value="">~</option>
                                            <?php echo $options_tv;?>
                                            <option value="All Smart TVs">All Smart TVs</option>
                                        </select>
                                        <label for="tv_display" class="floating-label">TV Display</label>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right">
                                <button type="submit" name="post" class="preview-button"  onclick="validateAndOpenPreviewModal()">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <div class="red-bar-vertical">
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-exclamation-circle" aria-hidden="true"></i></h1>
                <p id="errorText"></p>
                <br>
                <div style="align-items: right; text-align: right; right: 0">
                    <button id="okayButton" class="red-button" style="margin: 0" onclick="closeErrorModal()">Okay</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let soCounter = 1; // Initialize a counter for generating unique IDs
        const maxsosubDescriptions = 10;
        // Handle form submission via WebSocket
        const form = document.getElementById('soForm');
        
        // Fetch the WebSocket URL from the PHP file
        fetch('websocket_conn.php')
        .then(response => response.text())
        .then(url => {
            const ws = new WebSocket(url);

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(form);
                const data = {};

                formData.forEach((value, key) => {
                    data[key] = value;
                });

                console.log('Form Data:', data);
                ws.send(JSON.stringify(data));

                // Listen for messages from the WebSocket server
                ws.onmessage = function(event) {
                    const message = JSON.parse(event.data);
                    if (message.success) {
                        // Redirect the user to user_home.php if success
                        window.location.href = "user_home.php?pageid=UserHome?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>";
                    } else {
                        // Display an error modal
                        document.getElementById('errorText').textContent = "Error processing Student Outcome. Try again later";
                        document.getElementById('errorModal').style.display = 'flex';
                    }
                };
            });
        })
        .catch(error => {
            console.error('Error fetching WebSocket URL:', error);
        });

        document.getElementById('cancelButton').addEventListener('click', function() {
            closePreviewModal();
        });

        document.getElementById('okayButton').addEventListener('click', function() {
            closeErrorModal();
        });
        
        function validateAndOpenPreviewModal() {
            var so_title = document.querySelector('[name="so_title"]').value;
            var so_description = document.querySelector('[name="so_description"]').value;

            // Collect all subdescription textareas
            var so_subdescriptions = document.querySelectorAll('#subDescriptionContainerIncrement textarea');
            var allSubDescriptionsFilled = true;

            // Check if any of the subdescription textareas is empty
            so_subdescriptions.forEach(function(textarea) {
                if (textarea.value.trim() === "") {
                    allSubDescriptionsFilled = false;
                }
            });

            // Check if any of the required fields is empty
            if (so_title.trim() === "" || so_description === "" || !allSubDescriptionsFilled) {
                // If conditions are not met, show error message
                errorModalMessage("Please fill the necessary fields.");
            }
        }

        function errorModalMessage(errorMessage) {
            var modal = document.getElementById('errorModal');
            modal.style.display = 'flex';

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }

            // Display error message
            document.getElementById('errorText').textContent = errorMessage;

            // Okay Button click event
            document.getElementById('okayButton').addEventListener('click', function () {
                modal.style.display = 'none';
            });
        }
        
        // Function to close the preview modal
        function closePreviewModal() {
            var modal = document.getElementById('previewModal');
            modal.style.display = 'none';
        }

        // Function to close the preview modal
        function closeErrorModal() {
            var modal = document.getElementById('errorModal');
            modal.style.display = 'none';
        }

        
        function generateUniqueId() {
            return 'so_' + soCounter++;
        }

        function addsosubdescription() {
            if (soCounter <= maxsosubDescriptions) {
                var soSubDescription = document.getElementById("soSubDescription");
                var deletesoSubButton =document.getElementById("deletesoSubButton");
                var clone = soSubDescription.cloneNode(true); // Clone the element

                deletesoSubButton.style.display = "block";

                // Change the id of the cloned element to avoid duplicates
                var newId = generateUniqueId();
                clone.setAttribute("id", newId);

                // Set display to block for the cloned element
                clone.style.display = "flex";

                // Display the ID inside the textarea
                // clone.querySelector('textarea').value = newId;

                var textarea = clone.querySelector('textarea');
                textarea.value = newId;
                textarea.setAttribute("name", newId); // Set the name attribute to the unique ID

                // Append the cloned element to the container
                document.getElementById("subDescriptionContainerIncrement").appendChild(clone);
            } else {
                alert("Maximum of 10 subdescriptions can be added.");
            }
        }

        function deletesosubdescription() {
            // Get the container of the cloned elements
            var container = document.getElementById("subDescriptionContainerIncrement");

            // Remove the last child element (which is the latest cloned textarea)
            container.removeChild(container.lastChild);
            container.value = null;

            --soCounter;

            // If there are no more cloned textareas, hide the Cancel button
            if (container.childElementCount === 0) {
                document.getElementById("deletesoSubButton").style.display = "none";
            }
        }
    </script>
</body>
</html>