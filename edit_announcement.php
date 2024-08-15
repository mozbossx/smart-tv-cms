<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// Initialize variables to store announcement data
$ann_id = '';
$ann_author = '';
$created_date = '';
$created_time = '';
$ann_body = '';
$expiration_date = '';
$expiration_time = '';
$display_time = '';
$tv_display = '';
$media_path = '';

// Check if ann_id is set in the URL
if (isset($_GET['ann_id'])) {
    $ann_id = $_GET['ann_id'];

    // Fetch announcement data from the database
    $query = "SELECT ann_id, ann_author, created_date, created_time, ann_body, expiration_date, expiration_time, display_time, tv_display, media_path FROM announcements_tb WHERE ann_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $ann_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $announcement = $result->fetch_assoc();
        $ann_id = $announcement['ann_id'];
        $ann_author = $announcement['ann_author'];
        $created_date = $announcement['created_date'];
        $created_time = $announcement['created_time'];
        $ann_body = $announcement['ann_body'];
        $expiration_datetime = new DateTime($announcement['expiration_date'] . ' ' . $announcement['expiration_time']);
        $expiration_date = $expiration_datetime->format('Y-m-d');
        $expiration_time = $expiration_datetime->format('H:i');
        $display_time = $announcement['display_time'];
        $tv_display = $announcement['tv_display'];
        $media_path = $announcement['media_path'];
    } else {
        $media_path = '';
    }
}

// Fetch TV data from the database
$query_tv = "SELECT tv_name, device_id FROM smart_tvs_tb";
$result_tv = $conn->query($query_tv);

$options_tv = '';

if ($result_tv->num_rows > 0) {
    // Loop through each TV and create an option element
    while ($row_tv = $result_tv->fetch_assoc()) {
        $tv_name = $row_tv['tv_name'];
        $device_id = $row_tv['device_id'];
        // Check if this option should be selected
        $selected = ($tv_display == $tv_name) ? 'selected' : '';
        $options_tv .= "<option value='$tv_name' data-device-id='$device_id' $selected>$tv_name</option>";
    }
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
    <title>Edit Announcement</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <h1 class="content-title" style="color: black"><i class="fa fa-pencil-square" style="padding-right: 5px"></i>Edit Announcement</h1>
                    <div class="content-form">
                        <form id="editAnnouncementForm" enctype="multipart/form-data">
                            <div style="display: none; height: 0">
                                <input type="hidden" id="ann_id" name="ann_id" style="display: none" value="<?php echo htmlspecialchars($ann_id); ?>" readonly>
                                <input type="hidden" id="ann_author" name="ann_author" style="display: none" value="<?php echo htmlspecialchars($ann_author); ?>" readonly>
                                <input type="hidden" id="created_date" name="created_date" style="display: none" value="<?php echo htmlspecialchars($created_date); ?>" readonly>
                                <input type="hidden" id="created_time" name="created_time" style="display: none" value="<?php echo htmlspecialchars($created_time); ?>" readonly>
                            </div>
                            <div class="button-flex-space-between">
                                <div class="left-side-button">
                                    <button type="button" class="back-button" onclick="javascript:history.back()"><i class="fa fa-arrow-left" style="padding-right: 5px"></i>Back</button>
                                </div>
                                <input type="hidden" name="type" value="announcement">
                                <div class="right-side-button-preview">
                                    <a href="#" id="schedulePostOption" class="schedule-button" onclick="displaySchedulePostInputs()">Schedule Post<i class="fa fa-clock-o" style="padding-left: 5px"></i></a>
                                    <a href="#" id="cancelSchedulePostOption" style="display: none" class="schedule-button" onclick="cancelSchedulePostInputs()">Cancel Schedule Post<i class="fa fa-times" style="padding-left: 5px"></i></a>
                                </div>
                            </div>
                            <div class="line-separator"></div>
                            <?php include('error_message.php'); ?>
                            <div id="schedulePostInputs" style="display: none;">
                                <div class="rounded-container-column">
                                    <p class="input-container-label">Schedule Post Date & Time (Optional)</p>
                                    <div class="left-flex">
                                        <input type="date" id="schedule_date" name="schedule_date" class="input-date">
                                    </div>
                                    <div class="right-flex">
                                        <input type="time" id="schedule_time" name="schedule_time" class="input-time">
                                    </div>
                                </div>
                            </div>
                            <div class="floating-label-container">
                                <textarea name="ann_body" rows="6" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="ann_body"><?php echo htmlspecialchars($ann_body); ?></textarea>
                                <label for="ann_body" style="background: #FFFF; width: auto; padding: 5px; border-radius: 0" class="floating-label-text-area">Announcement Body</label>
                            </div>
                            <div class="right-flex">
                                <div class="rounded-container-media">
                                    <p class="input-container-label">Upload Media (Optional)</p>
                                    <input type="file" name="media" id="media" accept="video/*, image/*" onchange="previewMedia()" hidden>
                                    <label for="media" class="choose-file-button">Choose File (.mp4, .jpg, .png)</label>
                                    <button type="button" id="cancelMediaButton" class="red-button" onclick="cancelMedia()" style="display: none;">Cancel</button>
                                    <div class="preview-media" style="border: #000 1px solid; border-radius: 5px; background: white; text-align: center; width: 100%; height: 350px; display: none; justify-content: center; align-items: center; margin-top: 15px">
                                        <video id="video-preview" width="100%" height="350px" controls style="display:none; border-radius: 5px; background: #000;"></video>
                                        <img id="image-preview" style="display:none; max-width: 100%; max-height: 100%;">
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="rounded-container-column" style="flex: 1">
                                    <p class="input-container-label">Expiration Date & Time</p>
                                    <div class="left-flex">
                                        <input type="date" id="expiration_date" name="expiration_date" class="input-date" value="<?php echo htmlspecialchars($expiration_date); ?>">
                                    </div>
                                    <div class="right-flex">
                                        <input type="time" id="expiration_time" name="expiration_time" class="input-time" value="<?php echo $expiration_time; ?>">
                                    </div>
                                </div>
                                <div class="form-column" style="flex: 1">
                                    <div class="floating-label-container" style="flex: 1">
                                        <select id="display_time" name="display_time" class="floating-label-input" required style="background: #FFFF">
                                            <option value="">~</option>
                                            <?php 
                                                for ($i = 10; $i <= 30; $i++) {
                                                    $selected = ($display_time == $i) ? 'selected' : '';
                                                    echo "<option value='$i' $selected>$i seconds</option>";
                                                }
                                            ?>
                                        </select>
                                        <label for="display_time" class="floating-label">Display Time (seconds)</label>
                                    </div>
                                    <div class="floating-label-container" style="flex: 1">
                                        <select id="tv_account_select" name="tv_display" class="floating-label-input" style="background: #FFFF">
                                            <option value="">~</option>
                                            <?php echo $options_tv; ?>
                                        </select>
                                        <label for="tv_display" class="floating-label">TV Display</label>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right">
                                <button type="button" name="preview" id="previewButton" class="preview-button" onclick="validateAndOpenPreviewModal()">
                                    <i class="fa fa-eye" style="padding-right: 5px"></i> Preview 
                                </button>
                            </div>
                            <div id="previewModal" class="modal">
                                <div class="modal-content-preview">
                                    <div class="flex-preview-content">
                                        <div class="preview-website" id="externalProjectPreview">
                                            <div class="topbar">
                                                <div class="device-id"></div>
                                                <h1 class="tv-name"></h1>
                                                <div class="date-time">
                                                    <span id="live-clock"></span>
                                                    <span id="live-date"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="preview-content" id="previewContent"></div>
                                    </div>
                                    <!-- Operation buttons inside the Preview modal -->
                                    <div class="flex-button-modal" style="margin-top: 10px">
                                        <button type="button" id="cancelButton" class="close-button" onclick="closePreviewModal()">Cancel</button>
                                        <button type="submit" name="post" class="submit-button" style="width: 175px">Update Announcement</button>
                                    </div>
                                </div>
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
        const form = document.getElementById('editAnnouncementForm');
        
        // Fetch the WebSocket URL from the PHP file
        fetch('websocket_conn.php')
        .then(response => response.text())
        .then(url => {
            const ws = new WebSocket(url);

            // Function to send update message to WebSocket server
            function sendUpdateMessage() {
                const formData = new FormData(form);
                const data = { 
                    action: 'update'
                };

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

                    ws.send(JSON.stringify(data));
                }
                
                // Listen for messages from the WebSocket server
                ws.onmessage = function(event) {
                    const message = JSON.parse(event.data);
                    if (message.success) {
                        // Redirect the user to user_home.php if success
                        window.location.href = "user_home.php?pageid=UserHome?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>";
                    }
                };
            }

            // Handle form submission when "Update Announcement" button is clicked
            const updateButton = document.querySelector('[name="post"]');
            updateButton.addEventListener('click', function(e) {
                e.preventDefault();
                sendUpdateMessage();
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
            var annBody = document.querySelector('[name="ann_body"]').value;
            var displayTime = document.querySelector('[name="display_time"]').value;
            var tvDisplay = document.querySelector('[name="tv_display"]').value;
            var expirationDate = document.querySelector('[name="expiration_date"]').value;
            var expirationTime = document.querySelector('[name="expiration_time"]').value;
            var scheduleDate = document.querySelector('[name="schedule_date"]').value;
            var scheduleTime = document.querySelector('[name="schedule_time"]').value;

            // Check if any of the required fields is empty
            if (annBody.trim() === "" || displayTime === "" || tvDisplay === "" || expirationDate === "" || expirationTime === "") {
                // If conditions are not met, show error message
                errorModalMessage("Please fill the necessary fields.");
            } else {
                // Check if expiration date and time are in the past
                var expirationDateTime = new Date(expirationDate + ' ' + expirationTime);
                var currentDateTime = new Date();

                if (expirationDateTime < currentDateTime) {
                    errorModalMessage("Expiration date and time should not be behind the present time.");
                } else {
                    // Check if schedule date and time are in the past
                    if (scheduleDate !== "" && scheduleTime !== "") {
                        var scheduleDateTime = new Date(scheduleDate + ' ' + scheduleTime);

                        if (scheduleDateTime < currentDateTime) {
                            errorModalMessage("Schedule date and time should not be behind the present time.");
                            return;
                        }
                    }

                    // If conditions are met, enable the button and open the preview modal
                    openPreviewModal();
                }
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
        
        // Function to preview selected video or image
        function previewMedia() {
            var mediaInput = document.getElementById('media');
            var videoPreview = document.getElementById('video-preview');
            var imagePreview = document.getElementById('image-preview');
            var previewMedia = document.querySelector('.preview-media'); 
            var cancelMediaButton = document.getElementById('cancelMediaButton');

            if (mediaInput.files && mediaInput.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    var fileType = mediaInput.files[0].type;

                    if (fileType.startsWith('video/')) {
                        // Display video preview
                        videoPreview.src = e.target.result;
                        videoPreview.style.display = 'block';
                        imagePreview.style.display = 'none';
                    } else if (fileType.startsWith('image/')) {
                        // Display image preview
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                        videoPreview.style.display = 'none';
                    }

                    // Show the preview-media container
                    previewMedia.style.display = 'flex';
                    cancelMediaButton.style.display = 'inline-block';
                };

                reader.readAsDataURL(mediaInput.files[0]);
            }
        }

        // Function to display existing media preview
        function displayExistingMedia(mediaPath) {
            if (!mediaPath) {
                // Exit the function if mediaPath is empty or null
                var previewMedia = document.querySelector('.preview-media');
                previewMedia.style.display = 'none';
            } else if (mediaPath) {
                var videoPreview = document.getElementById('video-preview');
                var imagePreview = document.getElementById('image-preview');
                var previewMedia = document.querySelector('.preview-media');
                var cancelMediaButton = document.getElementById('cancelMediaButton');

                var fileType = mediaPath.split('.').pop().toLowerCase();

                if (fileType === 'mp4') {
                    // Display video preview
                    videoPreview.src = mediaPath;
                    videoPreview.style.display = 'block';
                    imagePreview.style.display = 'none';
                } else if (['jpg', 'jpeg', 'png'].includes(fileType)) {
                    // Display image preview
                    imagePreview.src = mediaPath;
                    imagePreview.style.display = 'block';
                    videoPreview.style.display = 'none';
                }

                // Show the preview-media container
                previewMedia.style.display = 'flex';
                cancelMediaButton.style.display = 'inline-block';
            }
        }

        // On page load, display existing media if available
        window.onload = function() {
            var mediaPath = '<?php echo $media_path ? "servers/announcements_media/" . $media_path : ""; ?>';
            displayExistingMedia(mediaPath);
        };

        // Function to cancel the media upload
        function cancelMedia() {
            var mediaInput = document.getElementById('media');
            var videoPreview = document.getElementById('video-preview');
            var imagePreview = document.getElementById('image-preview');
            var previewMedia = document.querySelector('.preview-media');
            var cancelMediaButton = document.getElementById('cancelMediaButton');

            // Reset the file input
            mediaInput.value = '';

            // Hide the previews and the preview-media container
            videoPreview.style.display = 'none';
            imagePreview.style.display = 'none';
            previewMedia.style.display = 'none';
            
            // Hide the cancel button
            cancelMediaButton.style.display = 'none';
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

        // Function to display the Schedule Post inputs
        function displaySchedulePostInputs() {
            var schedulePostOption = document.getElementById("schedulePostOption");
            schedulePostOption.style.display = "none";

            var cancelSchedulePostOption = document.getElementById("cancelSchedulePostOption");
            cancelSchedulePostOption.style.display = "block";

            // Display the Schedule Post inputs
            var schedulePostInputs = document.getElementById("schedulePostInputs");
            schedulePostInputs.style.display = "block";
        }

        // Function to cancel the Schedule Post inputs
        function cancelSchedulePostInputs() {
            // Hide the dropdown
            var schedulePostOption = document.getElementById("schedulePostOption");
            schedulePostOption.style.display = "block";

            var cancelSchedulePostOption = document.getElementById("cancelSchedulePostOption");
            cancelSchedulePostOption.style.display = "none";

            // Display the Schedule Post inputs
            var schedulePostInputs = document.getElementById("schedulePostInputs");
            schedulePostInputs.style.display = "none";
        }

    </script>
</body>
</html>