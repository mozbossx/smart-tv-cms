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
    <title>Department Organizational Chart</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <h1 class="content-title" style="color: black"><i class="fa fa-university" style="padding-right: 5px"></i>Department Organizational Chart</h1>
                    <button type="button" class="back-button" onclick="javascript:history.back()"><i class="fa fa-arrow-left" style="padding-right: 5px"></i>Back</button>
                    <div class="content-form">
                        <br>
                        <form id="departmentOrgForm" enctype="multipart/form-data" style="border: 1px solid black; border-radius: 5px; padding: 10px; align-items: center">
                            <div class="input-container">
                                <div class="level-container" id="departmentOrgLevel" style="display: none">
                                    <i class="fa fa-plus-circle" aria-hidden="true" style="font-size: 50px"></i>
                                </div>
                            </div>
                            <div id="departmentOrgLevelContainerIncrement"></div> <!-- Container for dynamically added input fields -->
                            <div style="display: flex; flex-direction: row">
                                <button class="plus-button" onclick="addDepartmentOrgLevel()"><i class="fa fa-plus-circle" aria-hidden="true"></i> New Level</button>
                                <button class="delete-so-button" onclick="deleteDepartmentOrgLevel()" style="display: none" id="deleteDepartmentOrgLevelButton"><i class="fa fa-times" aria-hidden="true"></i> Delete 1 Level</button>
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
        // Handle form submission via WebSocket
        const form = document.getElementById('departmentOrgForm');
        
        // Fetch the WebSocket URL from the PHP file
        fetch('websocket_conn.php')
        .then(response => response.text())
        .then(url => {
            const ws = new WebSocket(url);

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(form);
                const data = {};

                // Check for file input
                const mediaInput = document.getElementById('media');
                if (mediaInput.files.length > 0) {
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
                        // Redirect the user to user_home.php if success
                        window.location.href = "user_home.php?pageid=UserHome?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>";
                    } else {
                        // Display an error modal
                        document.getElementById('errorText').textContent = "Error processing announcement. Try again later";
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
        
        // Function to open the preview modal
        function openPreviewModal() {
            var modal = document.getElementById('previewModal');
            modal.style.display = 'flex';

            // Get the selected tv_name and device_id from the dropdown
            var selectedOption = document.querySelector('[name="tv_display"]');
            var selectedTvName = selectedOption.value;
            var selectedDeviceId = selectedOption.options[selectedOption.selectedIndex].getAttribute('data-device-id');

            // Display the tv_name and device_id in the modal
            document.querySelector('.tv-name').textContent = selectedTvName;

            // Clear previous content and append the new content for Device ID
            var deviceIdContainer = document.querySelector('.device-id');
            deviceIdContainer.innerHTML = ''; // Clear previous content

            // Add a new paragraph for "Device ID: "
            var deviceLabelText = document.createElement('p');
            deviceLabelText.textContent = 'Device ID: ';
            deviceIdContainer.appendChild(deviceLabelText);

            // Append the device_id after the text
            var deviceIdParagraph = document.createElement('p');
            deviceIdParagraph.textContent = selectedDeviceId;
            deviceIdContainer.appendChild(deviceIdParagraph);

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }

            // Display the preview content in the modal
            document.getElementById('previewContent').innerHTML = getPreviewContent();

            // Start the clock when modal opens
            updateClock();
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

        // Function to get the preview content
        function getPreviewContent() {
            // Function to format date and time
            function formatDateTime(dateString, timeString) {
                const dateTime = new Date(dateString + ' ' + timeString);
                const options = {
                    weekday: 'short',
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    hour12: true
                };
                return new Intl.DateTimeFormat('en-US', options).format(dateTime);
            }

            var previewContent = '';
            previewContent += '<p class="preview-input"><strong>Display Time: </strong><br>' + document.querySelector('[name="display_time"]').value + ' seconds</p>';
            previewContent += '<p class="preview-input"><strong>Expiration Date & Time: </strong><br>' + formatDateTime(document.querySelector('[name="expiration_date"]').value, document.querySelector('[name="expiration_time"]').value) + '</p>';
            previewContent += '<p class="preview-input"><strong>Schedule Post Date & Time: </strong><br>' + (document.querySelector('[name="schedule_date"]').value ? formatDateTime(document.querySelector('[name="schedule_date"]').value, document.querySelector('[name="schedule_time"]').value) : 'Not scheduled') + '</p>';
            previewContent += '<p class="preview-input"><strong>TV Display: </strong><br>' + document.querySelector('[name="tv_display"]').value + '</p>';
            return previewContent;
        }
        
        // Function to update the clock
        function updateClock() {
            const now = new Date();
            const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sept", "Oct", "Nov", "Dec"];
            let hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12; // Convert to 12-hour format
            const dayOfWeek = daysOfWeek[now.getDay()]; // Get day of the week
            const month = months[now.getMonth()]; // Get full month name
            const day = now.getDate().toString().padStart(2, '0');
            const year = now.getFullYear();

            document.getElementById('live-clock').textContent = hours + ':' + minutes + ' ' + ampm;
            document.getElementById('live-date').textContent = dayOfWeek + ', ' + month + ' ' + day + ', ' + year;
        }

        // Update the clock every second
        setInterval(updateClock, 1000);
        updateClock(); // Initial update

 
        function addDepartmentOrgLevel() {
            var departmentOrgLevel = document.getElementById("departmentOrgLevel");
            var deleteDepartmentOrgLevel =document.getElementById("deleteDepartmentOrgLevelButton");
            var clone = departmentOrgLevel.cloneNode(true); // Clone the element

            deleteDepartmentOrgLevel.style.display = "block";

            // Change the id of the cloned element to avoid duplicates
            var newId = "departmentOrgLevel_" + Date.now(); // Use current timestamp to generate a unique id
            clone.setAttribute("id", newId);

            // Set display to block for the cloned element
            clone.style.display = "block";

            // Display the ID inside the textarea
            // clone.querySelector('textarea').value = newId;

            // Append the cloned element to the container
            document.getElementById("departmentOrgLevelContainerIncrement").appendChild(clone);
        }

        function deleteDepartmentOrgLevel() {
            // Get the container of the cloned elements
            var container = document.getElementById("departmentOrgLevelContainerIncrement");

            // Remove the last child element (which is the latest cloned textarea)
            container.removeChild(container.lastChild);

            // If there are no more cloned textareas, hide the Cancel button
            if (container.childElementCount === 0) {
                document.getElementById("deleteDepartmentOrgLevelButton").style.display = "none";
            }
        }


    </script>
</body>
</html>