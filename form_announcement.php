<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// fetch tv data
$options_tv = '';
$sql = "SELECT tv_id, tv_name FROM smart_tvs_tb";
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $options_tv .= '<label style="display: block; margin-bottom: 5px;">';
    $options_tv .= '<input type="checkbox" style="padding: 10px; border-radius: 5px; background: #f3f3f3;" name="tv_id[]" value="' . $row['tv_id'] . '">';
    $options_tv .= ' ' . $row['tv_name'];
    $options_tv .= '</label>';
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
                        <form id="announcementForm" enctype="multipart/form-data">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="javascript:history.back()" style="color: #264B2B">Create Post</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Announcement Form</li>
                                </ol>
                            </nav>
                            <?php include('schedule_post.php')?>
                            <?php include('error_message.php'); ?>
                            <input type="hidden" name="type" value="announcement">
                            <div class="floating-label-container">
                                <textarea name="ann_body" rows="6" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="ann_body"></textarea>
                                <label for="ann_body" style="background: #FFFF; width: auto; padding: 5px; margin-top: 2px; border-radius: 0" class="floating-label-text-area">Announcement Body</label>
                            </div>
                            <?php include('upload_preview_media.php')?>
                            <?php include('expiration_date.php')?>
                            <?php
                            echo '<div class="form-column" style="flex: 1">';
                                echo '<div class="floating-label-container" style="flex: 1">';
                                    echo '<select id="display_time" name="display_time" class="floating-label-input" required style="background: #FFFF">';
                                        echo '<option value="">~</option>';
                                        echo '<option value="10">10 seconds</option>';
                                        echo '<option value="11">11 seconds</option>';
                                        echo '<option value="12">12 seconds</option>';
                                        echo '<option value="13">13 seconds</option>';
                                        echo '<option value="14">14 seconds</option>';
                                        echo '<option value="15">15 seconds</option>';
                                        echo '<option value="16">16 seconds</option>';
                                        echo '<option value="17">17 seconds</option>';
                                        echo '<option value="18">18 seconds</option>';
                                        echo '<option value="19">19 seconds</option>';
                                        echo '<option value="20">20 seconds</option>';
                                        echo '<option value="21">21 seconds</option>';
                                        echo '<option value="22">22 seconds</option>';
                                        echo '<option value="23">23 seconds</option>';
                                        echo '<option value="24">24 seconds</option>';
                                        echo '<option value="25">25 seconds</option>';
                                        echo '<option value="26">26 seconds</option>';
                                        echo '<option value="27">27 seconds</option>';
                                        echo '<option value="28">28 seconds</option>';
                                        echo '<option value="29">29 seconds</option>';
                                        echo '<option value="30">30 seconds</option>';
                                    echo '</select>';
                                    echo '<label for="display_time" class="floating-label">Display Time (seconds)</label>';
                                echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            ?>
                            <div class="form-column" style="flex: 1">
                                <div class="floating-label-container" style="flex: 1">
                                    <button type="button" id="tvModalButton" class="floating-label-input" style="background: #FFFF">
                                        Select TV Displays
                                    </button>
                                    <label for="tv_id" class="floating-label">TV Display</label>
                                </div>
                            </div>
                            <!-- TV Modal -->
                            <div id="tvModal" class="modal">
                                <div class="modal-content">
                                    <h1>Select TV Displays</h1>
                                    <div id="tvCheckboxList">
                                        <?php echo $options_tv; ?>
                                    </div>
                                    <button type="button" id="closeTvModal">Cancel</button>
                                    <button type="button" id="saveTvSelection">Save</button>
                                </div>
                            </div>
                            <div style="text-align: right">
                                <button type="button" name="preview" id="previewButton" class="preview-button" onclick="validateAndOpenPreviewModal()">
                                    <i class="fa fa-eye" style="padding-right: 5px"></i> Preview 
                                </button>
                            </div>
                            <div id="previewModal" class="modal">
                                <div class="modal-content-preview">
                                    <div style="display: flex; flex-direction: row-reverse">
                                        <span class="close" id="closeButton" style="color: rgb(49, 96, 56)" onclick="closePreviewModal()"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                                    </div>
                                    <div class="flex-preview-content">
                                        <?php 
                                            $sqlContainers = "SELECT * FROM containers_tb";
                                            $resultContainers = mysqli_query($conn, $sqlContainers);
                                            $containers = [];
                                            while ($row = mysqli_fetch_assoc($resultContainers)) {
                                                $containers[] = $row; // Store each container in an array
                                            }
                                        ?>
                                        <div id="previewContainer" style="flex: 2; height: auto"></div>
                                        <!-- The container consists of child_background_color, child_font_style, child_font_color-->
                                        <div class="preview-content" id="previewContent"></div>
                                    </div>
                                    <!-- Operation buttons inside the Preview modal -->
                                    <div class="flex-button-modal">
                                        <button type="submit" name="post" class="green-button" style="margin-top: 0; margin-right: 0">Submit</button>
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
        // Handle form submission via WebSocket
        const form = document.getElementById('announcementForm');
        const containers = <?php echo json_encode($containers); ?>;

        document.addEventListener("DOMContentLoaded", function () {
            const tvModalButton = document.getElementById("tvModalButton");
            const tvModal = document.getElementById("tvModal");
            const closeTvModal = document.getElementById("closeTvModal");
            const saveTvSelection = document.getElementById("saveTvSelection");

            // Open the TV Modal
            tvModalButton.addEventListener("click", function () {
                tvModal.style.display = "flex";
            });

            // Close the TV Modal
            closeTvModal.addEventListener("click", function () {
                tvModal.style.display = "none";
            });

            // Save the selected TV displays
            saveTvSelection.addEventListener("click", function () {
                const selectedTvs = [];
                document.querySelectorAll('#tvCheckboxList input[type="checkbox"]:checked').forEach(function (checkedBox) {
                    selectedTvs.push(checkedBox.value);
                });
                tvModalButton.textContent = selectedTvs.length > 0 ? selectedTvs.join(", ") : "Select TV Displays";
                tvModal.style.display = "none";
            });

            // Close the modal if the user clicks outside of it
            window.onclick = function (event) {
                if (event.target == tvModal) {
                    tvModal.style.display = "none";
                }
            };
        });

        function updatePreviewContent() {
            const selectedTvs = Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => parseInt(checkbox.value, 10));
            const selectedType = document.querySelector('[name="type"]').value;
            const annBody = document.querySelector('[name="ann_body"]').value;
            const previewContainer = document.getElementById('previewContainer');

            // Clear previous content
            previewContainer.innerHTML = '';

            // Find the matching container for each selected TV
            const matchingContainers = containers.filter(container => 
                selectedTvs.includes(parseInt(container.tv_id, 10)) && container.type === selectedType
            );

            if (matchingContainers.length > 0) {
                // Create carousel structure
                let carouselHTML = '<div class="carousel">';
                matchingContainers.forEach((matchingContainer, index) => {
                    carouselHTML += `
                        <div class="carousel-item ${index === 0 ? 'active' : ''}" style="display: none;">
                            <p>${matchingContainer.tv_id}</p>
                            <div style="background-color: ${matchingContainer.parent_background_color}; padding: 10px; border-radius: 5px; height: 100%">
                                <h1 style="color: ${matchingContainer.parent_font_color}; font-family: ${matchingContainer.parent_font_family}; font-style: ${matchingContainer.parent_font_style}; font-size: 2.0vh; margin-bottom: 5px">${matchingContainer.container_name}</h1>
                                <div style="background-color: ${matchingContainer.child_background_color}; color: ${matchingContainer.child_font_color}; font-style: ${matchingContainer.child_font_style}; font-family: ${matchingContainer.child_font_family}; width: auto; height: calc(100% - 6.5vh); font-size: 1.5vh; padding: 10px; border-radius: 5px">
                                    <p>${annBody}</p>
                                </div>
                            </div>
                        </div>
                    `;
                });
                carouselHTML += '</div>';

                // Only show navigation buttons if more than one item
                if (matchingContainers.length > 1) {
                    carouselHTML += `
                        <button type="button" class="carousel-control prev" onclick="moveCarousel(-1)">Previous</button>
                        <button type="button" class="carousel-control next" onclick="moveCarousel(1)">Next</button>
                    `;
                }

                previewContainer.innerHTML = carouselHTML;

                // Show the first item
                const items = document.querySelectorAll('.carousel-item');
                items[0].style.display = 'block';
            } else {
                previewContainer.innerHTML = '<p>No container found for the selected TVs.</p>'; // Fallback message
            }
        }

        let currentIndex = 0;

        function moveCarousel(direction) {
            const items = document.querySelectorAll('.carousel-item');
            items[currentIndex].style.display = 'none'; // Hide current item
            currentIndex += direction;

            // Loop around if at the ends
            if (currentIndex < 0) {
                currentIndex = items.length - 1;
            } else if (currentIndex >= items.length) {
                currentIndex = 0;
            }

            items[currentIndex].style.display = 'block'; // Show new item
        }

        // Add event listener to tv_id select element
        document.addEventListener("DOMContentLoaded", function () {
            const tvCheckboxes = document.querySelectorAll('[name="tv_id[]"]');
            tvCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updatePreviewContent);
            });
        });
        
        // Fetch the WebSocket URL from the PHP file
        fetch('websocket_conn.php')
        .then(response => response.text())
        .then(url => {
            const ws = new WebSocket(url);

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(form);
                const data = { 
                    action: 'post_content',
                    tv_ids: formData.getAll('tv_id[]') // Collect all tv_id[] values
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

                    console.log('Form Data:', data);
                    ws.send(JSON.stringify(data));
                }

                // Listen for messages from the WebSocket server
                ws.onmessage = function(event) {
                    const message = JSON.parse(event.data);
                    if (message.success) {
                        // Redirect the user to user_home.php if success
                        // window.location.href = "user_home.php?pageid=UserHome?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>";
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

        document.getElementById('closeButton').addEventListener('click', function() {
            closePreviewModal();
        });

        document.getElementById('okayButton').addEventListener('click', function() {
            closeErrorModal();
        });
        
        function validateAndOpenPreviewModal() {
            var annBody = document.querySelector('[name="ann_body"]').value;
            var displayTime = document.querySelector('[name="display_time"]').value;
            var tvDisplays = document.querySelectorAll('[name="tv_id[]"]:checked'); // Get all checked TV displays
            var expirationDate = document.querySelector('[name="expiration_date"]').value;
            var expirationTime = document.querySelector('[name="expiration_time"]').value;
            var scheduleDate = document.querySelector('[name="schedule_date"]').value;
            var scheduleTime = document.querySelector('[name="schedule_time"]').value;

            // Check if any of the required fields is empty
            if (annBody.trim() === "" || displayTime === "" || tvDisplays.length === 0 || expirationDate === "" || expirationTime === "") {
                // If conditions are not met, show error message
                errorModalMessage("Please fill the necessary fields and select at least one TV display.");
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

        // Function to cancel the media upload
        function cancelMedia() {
            var mediaInput = document.getElementById('media');
            var videoPreview = document.getElementById('video-preview');
            var imagePreview = document.getElementById('image-preview');
            var previewMedia = document.querySelector('.preview-media'); // Selecting the preview-media element
            var cancelMediaButton = document.getElementById('cancelMediaButton'); // Get the cancel button

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

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
            // Initial call to load the container when the page loads with a pre-selected tv_id
            updatePreviewContent();
            // Display the preview content in the modal
            document.getElementById('previewContent').innerHTML = getPreviewContent();
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
            const selectedTvs = Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => checkbox.value);
            previewContent += '<p class="preview-input"><strong>TV Display: </strong><br>' + (selectedTvs.length > 0 ? selectedTvs.join(", ") : 'None selected') + '</p>';
            return previewContent;
        }
    </script>
</body>
</html>