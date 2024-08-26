<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// Initialize variables to store peo data
$peo_id = '';
$peo_author = '';
$created_date = '';
$created_time = '';
$peo_title = '';
$peo_description = '';
$peo_1 = '';
$peo_2 = '';
$peo_3 = '';
$peo_4 = '';
$peo_5 = '';
$peo_6 = '';
$peo_7 = '';
$peo_8 = '';
$peo_9 = '';
$peo_10 = '';
$display_time = '';
$tv_display = '';

// Check if peo_id is set in the URL
if (isset($_GET['peo_id'])) {
    $peo_id = $_GET['peo_id'];

    // Fetch peo data from the database
    $query = "SELECT peo_id, peo_author, created_date, created_time, peo_title, peo_description, peo_1, peo_2, peo_3, peo_4, peo_5, peo_6, peo_7, peo_8, peo_9, peo_10, display_time, tv_display FROM peo_tb WHERE peo_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $peo_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $peo = $result->fetch_assoc();
        $peo_id = $peo['peo_id'];
        $peo_author = $peo['peo_author'];
        $created_date = $peo['created_date'];
        $created_time = $peo['created_time'];
        $peo_title = $peo['peo_title'];
        $peo_description = $peo['peo_description'];
        $peo_1 = $peo['peo_1'];
        $peo_2 = $peo['peo_2'];
        $peo_3 = $peo['peo_3'];
        $peo_4 = $peo['peo_4'];
        $peo_5 = $peo['peo_5'];
        $peo_6 = $peo['peo_6'];
        $peo_7 = $peo['peo_7'];
        $peo_8 = $peo['peo_8'];
        $peo_9 = $peo['peo_9'];
        $peo_10 = $peo['peo_10'];
        $display_time = $peo['display_time'];
        $tv_display = $peo['tv_display'];
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
    <title>Edit Program Education Objectives (PEO)</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <div class="content-form">
                        <form id="editPeoForm" enctype="multipart/form-data">
                            <div style="display: none; height: 0">
                                <input type="hidden" id="peo_id" name="peo_id" style="display: none" value="<?php echo htmlspecialchars($peo_id); ?>" readonly>
                                <input type="hidden" id="peo_author" name="peo_author" style="display: none" value="<?php echo htmlspecialchars($peo_author); ?>" readonly>
                                <input type="hidden" id="created_date" name="created_date" style="display: none" value="<?php echo htmlspecialchars($created_date); ?>" readonly>
                                <input type="hidden" id="created_time" name="created_time" style="display: none" value="<?php echo htmlspecialchars($created_time); ?>" readonly>
                                <input type="hidden" name="type" value="peo">
                            </div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="user_home.php?pageid=UserHome?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="color: #264B2B">Home</a></li>
                                    <li class="breadcrumb-item"><a href="javascript:history.back()" style="color: #264B2B">View Contents</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Edit PEO</li>
                                </ol>
                            </nav>
                            <div class="line-separator"></div>
                            <?php include('error_message.php'); ?>
                            <div class="floating-label-container">
                                <textarea name="peo_title" rows="6" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="peo_title"><?php echo htmlspecialchars($peo_title); ?></textarea>
                                <label for="peo_title" style="background: #FFFF; width: auto; padding: 5px; border-radius: 0" class="floating-label-text-area">PEO Title</label>
                            </div>
                            <div class="floating-label-container">
                                <textarea name="peo_description" rows="4" placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="peo_description"><?php echo htmlspecialchars($peo_description); ?></textarea>
                                <label for="peo_description" style="background: #FFFF; width: auto; padding: 5px; border-radius: 0" class="floating-label-text-area">PEO Description</label>
                            </div>
                            <?php
                            $last_peo_index = 0;
                            $any_peo_exists = false;
                            for ($i = 1; $i <= 10; $i++) {
                                $peo_value = ${'peo_' . $i};
                                if (!empty($peo_value)) {
                                    echo '<div>';
                                    echo '<div class="floating-label-container" style="display: flex; flex-direction: row; align-items: center; margin-left: 25px;" id="peoSubDescription">';
                                    echo '<i class="fa fa-caret-right" aria-hidden="true" style="margin-right: 5px; font-size: 20px"></i>';
                                    echo '<textarea name="peo_' . $i . '" rows="2" placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" required style="margin-left: 5px" id="peo_' . $i . '">' . htmlspecialchars($peo_value) . '</textarea>';
                                    echo '<label for="peo_' . $i . '" style="background: #FFFF; width: auto; padding: 5px; border-radius: 0" class="floating-label-text-area">PEO Sub-description</label>';
                                    echo '</div>';
                                    echo '</div>';
                                    $last_peo_index = $i; // Track the last index used
                                    $any_peo_exists = true; 
                                }
                            }
                            ?>
                            <div class="input-container">
                                <div class="floating-label-container" style="display: none; flex-direction: row; align-items: center; margin-left: 25px;" id="peoSubDescription">
                                    <i class="fa fa-caret-right" aria-hidden="true" style="margin-right: 5px; font-size: 20px"></i>
                                    <textarea name="peo_subdescription" rows="2" placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" style="margin-left: 5px" required></textarea>
                                    <label for="peo_subdescription" style="background: #FFFF; width: auto; padding: 5px; border-radius: 0" class="floating-label-text-area">PEO Sub-description</label>
                                </div>
                            </div>
                            <div id="subDescriptionContainerIncrement"></div> <!-- Container for dynamically added input fields -->
                            <div style="display: flex; flex-direction: row">
                                <button class="plus-button" type="button" onclick="addPEOsubdescription()" id="add_peo_button">
                                    <i class="fa fa-plus-circle" aria-hidden="true"></i> New PEO Sub-description
                                </button>
                                <button class="delete-peo-button" type="button" onclick="deletePEOsubdescription()" style="display: none" id="deletePEOSubButton">
                                    <i class="fa fa-times" aria-hidden="true"></i> Cancel
                                </button>
                            </div>

                            <div class="form-row">
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
                                        <button type="submit" name="post" class="submit-button" style="width: 175px">Update PEO</button>
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
        var peoCounter = <?php echo $last_peo_index; ?>;
        var maxPEOsubDescriptions = 10;
        var anyPEOExists = <?php echo $any_peo_exists ? 'true' : 'false'; ?>;

        if (anyPEOExists) {
            document.getElementById("deletePEOSubButton").style.display = "block";
        }

        const form = document.getElementById('editPeoForm');
        
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

                formData.forEach((value, key) => {
                    data[key] = value;
                });

                ws.send(JSON.stringify(data));
                
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
            var peoBody = document.querySelector('[name="peo_title"]').value;
            var displayTime = document.querySelector('[name="display_time"]').value;
            var tvDisplay = document.querySelector('[name="tv_display"]').value;

            // Check if any of the required fields is empty
            if (peoBody.trim() === "" || displayTime === "" || tvDisplay === "") {
                // If conditions are not met, show error message
                errorModalMessage("Please fill the necessary fields.");
            } else {
                openPreviewModal();
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

        function generateUniqueId() {
            return 'peo_' + (++peoCounter);
        }

        function addPEOsubdescription() {
            if (peoCounter < maxPEOsubDescriptions) {
                var peoSubDescription = document.getElementById("peoSubDescription");
                var deletePEOSubButton = document.getElementById("deletePEOSubButton");
                var clone = peoSubDescription.cloneNode(true); // Clone the element

                deletePEOSubButton.style.display = "block";

                // Change the id of the cloned element to avoid duplicates
                var newId = generateUniqueId();
                clone.setAttribute("id", newId);

                // Set display to block for the cloned element
                clone.style.display = "flex";

                // Display the ID inside the textarea
                var textarea = clone.querySelector('textarea');
                textarea.value = '';
                textarea.setAttribute("name", newId); // Set the name attribute to the unique ID

                // Append the cloned element to the container
                document.getElementById("subDescriptionContainerIncrement").appendChild(clone);
                
                // Check if max limit reached after adding
                if (peoCounter >= maxPEOsubDescriptions) {
                    document.getElementById("add_peo_button").style.display = "none";
                }
            } else {
                alert("Maximum of 10 subdescriptions can be added.");
            }
        }

        function deletePEOsubdescription() {
            var container = document.getElementById("subDescriptionContainerIncrement");
            if (container.childElementCount > 0) {
                container.removeChild(container.lastChild);
                peoCounter--;

                // Since we're deleting, ensure "New PEO Sub-description" button is visible
                document.getElementById("add_peo_button").style.display = "block";

                if (container.childElementCount === 0 && peoCounter < <?php echo $last_peo_index; ?>) {
                    document.getElementById("deletePEOSubButton").style.display = "none";
                }
            } else {
                // If there are no dynamically added elements, remove the existing ones
                for (var i = <?php echo $last_peo_index; ?>; i > 0; i--) {
                    var existingPeo = document.getElementById('peo_' + i);
                    if (existingPeo) {
                        existingPeo.closest('.floating-label-container').remove(); // Remove the entire parent container
                        peoCounter--;
                        break;
                    }
                }

                // If no existing PEOs are left, hide the Cancel button
                if (peoCounter <= 0) {
                    document.getElementById("deletePEOSubButton").style.display = "none";
                }
            }
        }

    </script>
</body>
</html>