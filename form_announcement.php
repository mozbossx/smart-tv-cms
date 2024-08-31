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
    $options_tv .= '<label style="display: block; margin-bottom: 7px; padding: 10px; background: #f3f3f3; border-radius: 5px">';
    $options_tv .= '<input type="checkbox" name="tv_id[]" value="' . $row['tv_id'] . '">';
    $options_tv .= ' ' . $row['tv_name'];
    $options_tv .= '</label>';

    // Add to TV name mapping
    $tv_names[$row['tv_id']] = $row['tv_name'];
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
                            <?php include('upload_preview_media.php')?>
                            <?php include('expiration_date.php')?>
                            <?php include('displaytime_tvdisplay.php')?>
                            <?php include('schedule_post.php')?>
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
                            <div id="previewModal" class="modal">
                                <div class="modal-content-preview">
                                    <div class="flex-preview-content">
                                        <?php 
                                            $sqlContainers = "SELECT * FROM containers_tb";
                                            $resultContainers = mysqli_query($conn, $sqlContainers);
                                            $containers = [];
                                            while ($row = mysqli_fetch_assoc($resultContainers)) {
                                                $containers[] = $row; // Store each container in an array
                                            }
                                        ?>
                                        <div style="display: flex, flex-direction: column; flex: 2; height: auto; overflow: auto">
                                            <div id="previewContainer"></div>
                                        </div>
                                        <!-- The container consists of child_background_color, child_font_style, child_font_color-->
                                        <div class="preview-content" id="previewContent"></div>
                                    </div>
                                    <!-- Operation buttons inside the Preview modal -->
                                    <div class="flex-button-modal">
                                        <button type="button" class="green-button" id="closeButton" style="background: none; border: 1px solid #264B2B; color: #264B2B; margin-top: 0; margin-right: 5px" onclick="closePreviewModal()">Cancel</button>
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
        const tvNames = <?php echo json_encode($tv_names); ?>;

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
            // Get the tv_name from the tv_id using the tvNames mapping
            const tvName = tvNames[matchingContainer.tv_id] || 'Unknown TV';

            carouselHTML += `
                <div class="carousel-item ${index === 0 ? 'active' : ''}" style="display: none;" data-tv-id="${matchingContainer.tv_id}">
                    <div style="background-color: ${matchingContainer.parent_background_color}; padding: 10px; border-radius: 5px; height: ${matchingContainer.height_px}px; width: ${matchingContainer.width_px}px;">
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
            const initialTvName = tvNames[matchingContainers[0].tv_id] || 'Unknown TV';
            carouselHTML += `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" class="carousel-control prev" onclick="moveCarousel(-1)"><i class="fa fa-angle-left" aria-hidden="true"></i> Previous</button>
                    <p id="carouselTvName">${initialTvName}</p>
                    <button type="button" class="carousel-control next" onclick="moveCarousel(1)">Next <i class="fa fa-angle-right" aria-hidden="true"></i></button>
                </div>
            `;
        }

        previewContainer.innerHTML = carouselHTML;

        // Show the first item
        const items = document.querySelectorAll('.carousel-item');
        items[0].style.display = 'block';

        // Initialize the current index
        let currentIndex = 0;

        // Function to update the TV name when navigating
        function updateTvName() {
            const activeItem = items[currentIndex];
            const activeTvId = activeItem.getAttribute('data-tv-id');
            const activeTvName = tvNames[activeTvId] || 'Unknown TV';
            document.getElementById('carouselTvName').textContent = activeTvName;
        }

        // Function to move carousel
        window.moveCarousel = function(direction) {
            items[currentIndex].style.display = 'none'; // Hide current item
            items[currentIndex].classList.remove('active');

            // Update index
            currentIndex += direction;

            // Loop around if at the ends
            if (currentIndex < 0) {
                currentIndex = items.length - 1;
            } else if (currentIndex >= items.length) {
                currentIndex = 0;
            }

            items[currentIndex].style.display = 'block'; // Show new item
            items[currentIndex].classList.add('active');

            updateTvName(); // Update the TV name based on the new active item
        };
    } else {
        previewContainer.innerHTML = '<p>No container found for the selected TVs.</p>'; // Fallback message
    }
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
                // Convert expiration date and time to Date object
                var expirationDateTime = new Date(expirationDate + ' ' + expirationTime);
                var currentDateTime = new Date();

                if (expirationDateTime < currentDateTime) {
                    errorModalMessage("Expiration date and time should not be behind the present time.");
                } else {
                    // If schedule date and time are provided, validate them
                    if (scheduleDate !== "" && scheduleTime !== "") {
                        var scheduleDateTime = new Date(scheduleDate + ' ' + scheduleTime);

                        if (scheduleDateTime < currentDateTime) {
                            errorModalMessage("Schedule date and time should not be behind the present time.");
                            return;
                        } else if (expirationDateTime < scheduleDateTime) {
                            errorModalMessage("Expiration date and time should not be before the schedule date and time.");
                            return;
                        } else if (scheduleDateTime > expirationDateTime) {
                            errorModalMessage("Schedule date and time should not be after the expiration date and time.");
                            return;
                        }
                    }

                    // If all conditions are met, open the preview modal
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
            const selectedTvNames = selectedTvs.map(tvId => tvNames[tvId]); // Map tv_id to tv_name
            previewContent += '<p class="preview-input"><strong>TV Display: </strong><br>' + (selectedTvNames.length > 0 ? selectedTvNames.join(", ") : 'None selected') + '</p>';
            
            return previewContent;
        }
    </script>
</body>
</html>