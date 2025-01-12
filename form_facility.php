<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';
include 'user_theme_color.php';

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
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <title>Create a Facility</title>
    <style>
        .image-slideshow-container {
            position: relative;
            overflow: hidden;
        }

        .nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 8px 16px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            z-index: 2;
        }

        .prev {
            left: 10px;
        }

        .next {
            right: 10px;
        }

        #add-more-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: <?php echo $theme_color; ?>;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        #add-more-btn:hover {
            background: <?php echo $dark_theme_color_50_percent; ?>;
            transition: background 0.3s;
        }

        .image-preview {
            max-width: 100%;
            max-height: 100%;
            display: none;
        }

        .image-preview.active {
            display: block;
        }

        .dot-indicators {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 8px;
            z-index: 2;
        }

        .dot {
            width: 10px;
            height: 10px;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            cursor: pointer;
            text-align: center;
            font-size: 8px;
            place-content: center;
        }

        .dot.active {
            text-align: center;
            font-size: 8px;
            color: white;
            font-weight: bold;
            place-content: center;
        }

        .delete-image-btn {
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .delete-image-btn:hover {
            opacity: 1;
        }

        .image-preview-container {
            position: relative;
            width: 100%;
            height: 100%;
        }
    </style>
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
                                <li class="breadcrumb-item"><a href="create_post.php?pageid=CreatePost?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Create Post</a></li>
                                <li class="breadcrumb-item"><a href="general_info.php?pageid=GeneralInformationForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">General Information</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Facilities Form</li>
                            </ol>
                        </nav>
                        <form id="facilityForm" enctype="multipart/form-data" class="main-form">
                            <?php include('error_message.php'); ?>
                            <input type="hidden" name="type" value="facility">
                            <h1 style="text-align: center">Facilities Form</h1>
                            <!-- <div class="floating-label-container">
                                <textarea name="facility_body" rows="6" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="facility_body"></textarea>
                                <label for="facility_body" style="background: #FFFF; width: auto; padding: 5px; margin-top: 2px; border-radius: 0" class="floating-label-text-area">facility Body</label>
                            </div> -->
                            <div class="preview-media" style="border-radius: 5px; background: white; text-align: center; width: 100%; height: 300px; justify-content: center; align-items: center; margin-top: 15px; box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 6px;">
                                <div class="image-slideshow-container" style="width: 100%; height: 100%; position: relative;">
                                    <div class="image-slides" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                        <input type="file" name="media[]" id="media" accept="image/*" onchange="handleImageUpload(event)" hidden multiple>
                                        <label for="media" id="upload-label" style="cursor: pointer; font-size: 15px; font-weight: bold; color: <?php echo $theme_color; ?>; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center">
                                            <i class="fa fa-upload" style="padding-right: 5px"></i> Add Image (.jpg, .png)
                                        </label>
                                    </div>
                                    <button type="button" class="nav-btn prev" onclick="changeSlide(-1)" style="display: none;">❮</button>
                                    <button type="button" class="nav-btn next" onclick="changeSlide(1)" style="display: none;">❯</button>
                                    <button type="button" id="add-more-btn" onclick="document.getElementById('media').click()" style="display: none;"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="dot-indicators"></div>
                            <br><br>
                            <div class="floating-label-container">
                                <div id="quillEditorContainer">
                                    <label for="quillEditorContainer" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: <?php echo $theme_color; ?>; font-size: 12px; font-weight: bold">Facility Body</label>
                                    <div id="facility_body" style="height: 150px;"></div>
                                </div>
                                <input type="hidden" name="facility_body" id="facilityBodyHiddenInput">
                            </div>
                            <div class="form-row">
                                <?php 
                                    $type = 'facility';
                                ?>
                                <?php include('misc/php/displaytime_tvdisplay.php')?>
                            </div>
                            <div style="display: flex; flex-direction: row; margin-left: auto; margin-top: 10px">
                                <div>
                                    <button type="button" name="preview" id="previewButton" class="preview-button" style="margin-right: 0" onclick="validateAndOpenPreviewModal()">
                                        <i class="fa fa-eye" style="padding-right: 5px"></i> Preview 
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Preview Modal -->
    <div id="previewModal" class="modal">
        <div class="modal-content-preview">
            <div class="flex-preview-content">
                <?php 
                    $sqlContainers = "SELECT c.*, t.width_px as tv_width_px, t.height_px as tv_height_px, t.tv_brand as tv_brand, t.tv_name as tv_name, t.tv_department as tv_department,
                        b.background_image_path, b.background_hex_color, b.isImageBackground, b.isStaticBackground,
                        tb.topbar_hex_color, tb.department_logo_path,
                        tb.topbar_tvname_font_color, tb.topbar_tvname_font_style, tb.topbar_tvname_font_family, 
                        tb.topbar_tvid_font_color, tb.topbar_tvid_font_style, tb.topbar_tvid_font_family, 
                        tb.topbar_time_font_color, tb.topbar_time_font_style, tb.topbar_time_font_family, 
                        tb.topbar_date_font_color, tb.topbar_date_font_style, tb.topbar_date_font_family
                    FROM containers_tb c 
                    JOIN smart_tvs_tb t ON c.tv_id = t.tv_id
                    LEFT JOIN background_tv_tb b ON c.tv_id = b.tv_id
                    LEFT JOIN topbar_tv_tb tb ON c.tv_id = tb.tv_id";
                    $resultContainers = mysqli_query($conn, $sqlContainers);
                    $containers = [];
                    while ($row = mysqli_fetch_assoc($resultContainers)) {
                        $containers[] = $row;
                    }
                ?>
                <div style="display: flex, flex-direction: column; flex: 2; height: auto; overflow: auto">
                    <div id="previewContainer">
                    </div>
                </div>
                <!-- The container consists of child_background_color, child_font_style, child_font_color-->
                <div class="preview-content" id="previewContent"></div>
            </div>
            <!-- Operation buttons inside the Preview modal -->
            <div class="flex-button-modal">
                <button type="button" class="grey-button" id="closeButton" style="margin-top: 0; margin-right: 5px" onclick="closePreviewModal()">Cancel</button>
                <button type="button" name="post" class="green-button" style="margin-top: 0; margin-right: 0" onclick="submitFormViaWebSocket()">Submit</button>
            </div>
        </div>
    </div>
    <?php 
        $topbarPositions = [];
        $topbarQuery = "SELECT tv_id, topbar_position FROM topbar_tv_tb";
        $resultTopbar = mysqli_query($conn, $topbarQuery);
        while ($row = mysqli_fetch_assoc($resultTopbar)) {
            $topbarPositions[$row['tv_id']] = $row['topbar_position'];
        }
    ?>
    <script src="misc/js/is_form_dirty.js"></script>
    <?php include('misc/php/error_modal.php') ?>
    <?php include('misc/php/success_modal.php') ?>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <script src="misc/js/quill_textarea_submission.js"></script>
    <script>
        const containers = <?php echo json_encode($containers); ?>;
        const tvNames = <?php echo !empty($tv_names) ? json_encode($tv_names) : '{}'; ?>; 
    </script>
    <script>
        let currentSlide = 0;
        const uploadedImages = [];
        let clockInterval;

        function handleImageUpload(event) {
            const files = event.target.files;
            const slideshowContainer = document.querySelector('.image-slides');
            const dotContainer = document.querySelector('.dot-indicators');
            
            // Process all files at once
            const promises = Array.from(files).map(file => {
                return new Promise((resolve) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const imgContainer = document.createElement('div');
                            imgContainer.className = 'image-preview-container';
                            imgContainer.style.position = 'relative';
                            imgContainer.style.width = '100%';
                            imgContainer.style.height = '100%';
                            imgContainer.style.display = 'none';

                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'image-preview';
                            img.style.maxWidth = '100%';
                            img.style.maxHeight = '100%';
                            img.style.display = 'block'; // Make sure the image is visible
                            img.style.margin = 'auto'; // Center the image

                            // Add delete button
                            const deleteBtn = document.createElement('button');
                            deleteBtn.className = 'delete-image-btn';
                            deleteBtn.innerHTML = '<i class="fa fa-trash"></i>';
                            deleteBtn.style.position = 'absolute';
                            deleteBtn.style.top = '10px';
                            deleteBtn.style.right = '10px';
                            deleteBtn.style.background = '#ff4444';
                            deleteBtn.style.color = 'white';
                            deleteBtn.style.border = 'none';
                            deleteBtn.style.borderRadius = '4px';
                            deleteBtn.style.padding = '8px';
                            deleteBtn.style.cursor = 'pointer';
                            deleteBtn.onclick = (e) => {
                                e.stopPropagation(); // Prevent slide change when clicking delete
                                deleteImage(imgContainer);
                            };

                            imgContainer.appendChild(img);
                            imgContainer.appendChild(deleteBtn);
                            uploadedImages.push(imgContainer);
                            slideshowContainer.appendChild(imgContainer);
                            resolve();
                        };
                        reader.readAsDataURL(file);
                    } else {
                        resolve();
                    }
                });
            });

            // After all images are processed
            Promise.all(promises).then(() => {
                // Hide upload label after first upload
                document.getElementById('upload-label').style.display = 'none';
                
                // Set the current slide to the last uploaded image
                currentSlide = uploadedImages.length - 1;
                
                // Update image visibility
                uploadedImages.forEach((container, index) => {
                    container.style.display = index === currentSlide ? 'block' : 'none';
                });

                // Show navigation buttons if there are multiple images
                const navBtns = document.querySelectorAll('.nav-btn');
                navBtns.forEach(btn => {
                    btn.style.display = uploadedImages.length > 1 ? 'block' : 'none';
                });

                // Show add more button
                document.getElementById('add-more-btn').style.display = 'block';
                
                updateDotIndicators();
            });
        }

        // Function to handle image deletion
        function deleteImage(imgContainer) {
            const index = uploadedImages.indexOf(imgContainer);
            if (index > -1) {
                uploadedImages.splice(index, 1);
                imgContainer.remove();

                // If there are no images left, show the upload label and reset the input
                if (uploadedImages.length === 0) {
                    const uploadLabel = document.getElementById('upload-label');
                    const mediaInput = document.getElementById('media');
                    
                    // Reset the file input
                    mediaInput.value = '';
                    
                    // Show upload label and restore its functionality
                    uploadLabel.style.display = 'flex';
                    uploadLabel.style.cursor = 'pointer';
                    // uploadLabel.onclick = () => mediaInput.click();
                    
                    // Hide navigation buttons
                    document.querySelector('.nav-btn.prev').style.display = 'none';
                    document.querySelector('.nav-btn.next').style.display = 'none';
                    document.getElementById('add-more-btn').style.display = 'none';
                    currentSlide = 0;
                } else {
                    // Adjust currentSlide if necessary
                    if (currentSlide >= uploadedImages.length) {
                        currentSlide = uploadedImages.length - 1;
                    }
                    // Show the new current slide
                    uploadedImages.forEach((container, i) => {
                        container.style.display = i === currentSlide ? 'block' : 'none';
                    });
                }
                updateDotIndicators();
                updateNavigationButtons();
            }
        }

        function updateDotIndicators() {
            const dotContainer = document.querySelector('.dot-indicators');
            dotContainer.innerHTML = ''; // Clear existing dots
            
            // Only show dots if there are multiple images
            if (uploadedImages.length > 1) {
                uploadedImages.forEach((_, index) => {
                    const dot = document.createElement('div');
                    dot.className = 'dot' + (index === currentSlide ? ' active' : '');
                    dot.onclick = () => goToSlide(index);
                    dotContainer.appendChild(dot);
                    
                    // Place page number on the dot
                    dot.textContent = index + 1;
                });
            }
        }

        function goToSlide(index) {
            uploadedImages[currentSlide].style.display = 'none';
            currentSlide = index;
            uploadedImages[currentSlide].style.display = 'block';
            updateDotIndicators();
        }

        function changeSlide(direction) {
            if (uploadedImages.length <= 1) return;
            
            uploadedImages[currentSlide].style.display = 'none';
            currentSlide = (currentSlide + direction + uploadedImages.length) % uploadedImages.length;
            uploadedImages[currentSlide].style.display = 'block';
            updateDotIndicators();
        }

        function updateNavigationButtons() {
            const prevBtn = document.querySelector('.nav-btn.prev');
            const nextBtn = document.querySelector('.nav-btn.next');
            
            if (uploadedImages.length >= 2) {
                prevBtn.style.display = 'block';
                nextBtn.style.display = 'block';
            } else {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            }
        }

        function getQuillEditorContent(contentType) {
            switch (contentType) {
                case 'facility':
                    return quillEditors.facilityBodyQuill.root.innerHTML;
                default:
                    return '';
            }
        }

        function submitFormViaWebSocket() {
            isFormDirty = false;
            const contentType = document.querySelector('[name="type"]').value;
            const form = document.getElementById(`${contentType}Form`);

            // Use the existing hidden input element to store the editor content
            const quillEditorContent = getQuillEditorContent(contentType);

            let quillHiddenInput = form.querySelector(`input[name="${contentType}_body"]`);
            if (!quillHiddenInput) {
                quillHiddenInput = document.createElement('input');
                quillHiddenInput.setAttribute('type', 'hidden');
                quillHiddenInput.setAttribute('name', `${contentType}_body`);
                form.appendChild(quillHiddenInput);
            }
            quillHiddenInput.setAttribute('value', quillEditorContent);

            const formData = new FormData(form);
            const data = { 
                action: 'post_content',
                tv_ids: formData.getAll('tv_id[]')
            };

            // Handle multiple images
            if (uploadedImages.length > 0) {
                // Get base64 data from all uploaded images
                data.media = uploadedImages.map(container => {
                    const img = container.querySelector('img');
                    return img.src;  // This will contain the base64 data
                });
            }

            // Convert FormData to object, excluding empty schedule_datetime
            formData.forEach((value, key) => {
                if (key === 'schedule_datetime' && (!value || value.trim() === '')) {
                    return; // Skip empty schedule_datetime
                }
                if (key !== 'media[]') { // Skip the file input
                    data[key] = value;
                }
            });

            console.log('Form Data:', data);
            ws.send(JSON.stringify(data));

            // Listen for messages from the WebSocket server
            ws.onmessage = function(event) {
                const message = JSON.parse(event.data);
                if (message.success) {
                    const action = 'processed';
                    document.getElementById('successMessage').textContent = `${capitalizeFirstLetter(contentType)} was successfully ${action}!`;                        
                    document.getElementById('successMessageModal').style.display = 'flex';
                    isFormDirty = false;
                } 
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            const contentType = document.querySelector('[name="type"]').value;
            const form = document.getElementById(`${contentType}Form`);

            // Fetch the WebSocket URL from the PHP file
            fetch('websocket_conn.php')
                .then(response => response.text())
                .then(url => {
                    const ws = new WebSocket(url);
                    console.log(url);
                    // Attach the WebSocket to the window object for global access
                    window.ws = ws;

                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        submitFormViaWebSocket();
                    });
                })
                .catch(error => {
                    console.error('Error fetching WebSocket URL:', error);
                });
        });

        function resetFormAndGoHome() {
            const contentType = document.querySelector('[name="type"]').value;
            const form = document.getElementById(`${contentType}Form`);

            form.reset();
            location.href = `user_home.php?pageid=UserHome&userId=${user_id}&fullName=${full_name}`; 
        }

        document.getElementById('closeButton').addEventListener('click', function() {
            closePreviewModal();
        });

        // Function to close the preview modal
        function closePreviewModal() {
            var modal = document.getElementById('previewModal');
            modal.style.display = 'none';
        }

        function validateAndOpenPreviewModal() {
            const displayTime = document.querySelector('[name="display_time"]').value;
            const tvDisplays = document.querySelectorAll('[name="tv_id[]"]:checked'); // Get all checked TV displays
    
            // Check if no images have been uploaded (uploadedImages array is empty)
            if (uploadedImages.length === 0) {
                errorModalMessage("Please add at least one image.");
                return;
            }

            if (displayTime === "" || tvDisplays.length === 0) {
                errorModalMessage("Please fill in all required fields and select at least one TV display.");
                return;
            }

            openPreviewModal();
        }

        // Function to get the preview content
        function getPreviewContent() {
            const selectedType = document.querySelector('[name="type"]').value;
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

            const selectedTvs = Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => checkbox.value);
            const selectedTvNames = selectedTvs.map(tvId => tvNames[tvId]); // Map tv_id to tv_name
            previewContent += '<p class="preview-input"><strong>TV Display: </strong><br>' + (selectedTvNames.length > 0 ? selectedTvNames.join(", ") : 'None selected') + '</p>';
            
            return previewContent;
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

            // Clear any existing interval
            if (clockInterval) {
                clearInterval(clockInterval);
            }

            // Start clock updates
            updateClock(); // Initial update
            clockInterval = setInterval(updateClock, 1000);
            
            // Clear interval when modal is closed by clicking outside
            window.onclick = function(event) {
                if (event.target == modal) {
                    closePreviewModal();
                }
            }
        }

        function updatePreviewContent() {
            const selectedTvs = Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => parseInt(checkbox.value, 10));
            const selectedType = document.querySelector('[name="type"]').value;
            const previewContainer = document.getElementById('previewContainer');

            let content = '';
            // Get only the first uploaded image for preview
            let mediaContent = uploadedImages.length > 0 ? 
                `<img src="${uploadedImages[0].src}" alt="Preview" style="max-width: 100%; max-height: 200px; object-fit: contain;">` : 
                '';

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
                    const tvName = tvNames[matchingContainer.tv_id] || 'Unknown TV';
                    const isSelectedType = matchingContainer.type === selectedType;
                    const isVisible = matchingContainer.visible === '1';
                    const topbarPositions = <?php echo json_encode($topbarPositions); ?>;

                    carouselHTML += `
                        <div class="carousel-item ${index === 0 ? 'active' : ''}" style="display: none;" data-tv-id="${matchingContainer.tv_id}">
                            <div class="tv-frame-parent" style="width: auto; height: 100%; background: none; cursor: default;">
                                <div class="tv-frame" id="tvFrame-${matchingContainer.tv_id}" style="position: relative; display: flex; flex-direction: column; align-items: center; overflow: clip;">
                                    ${topbarPositions[matchingContainer.tv_id] === 'top' ? `
                                        <div class="topbar" id="topbar" style="background: ${matchingContainer.topbar_hex_color}">
                                            <img src="${matchingContainer.department_logo_path ? 
                                            `servers/tv_department_logos/${matchingContainer.tv_id}/${matchingContainer.department_logo_path}` : 
                                            'images/soe_icon.png'}" 
                                            alt="" style="width: 5vh; height: 5vh; margin-left: 5px">
                                            <div class="tv-id" style="color: ${matchingContainer.topbar_tvid_font_color}; font-style: ${matchingContainer.topbar_tvid_font_style}; font-family: ${matchingContainer.topbar_tvid_font_family}; font-size: 2.5vh; padding-left: 10px;">
                                                <p id="tvDepartment_${matchingContainer.tv_id}">${matchingContainer.tv_department}</p>
                                                <p id="tv-id"><i class="fa fa-television" aria-hidden="true"></i>: ${matchingContainer.tv_id}</p>
                                            </div>
                                            <h1 class="tv-name" style="color: ${matchingContainer.topbar_tvname_font_color}; font-style: ${matchingContainer.topbar_tvname_font_style}; font-family: ${matchingContainer.topbar_tvname_font_family};">
                                                ${matchingContainer.tv_name}
                                            </h1>
                                            <div class="date-time">
                                                <span class="live-clock time" style="font-weight: bold; color: ${matchingContainer.topbar_time_font_color}; font-style: ${matchingContainer.topbar_time_font_style}; font-family: ${matchingContainer.topbar_time_font_family};"></span>
                                                <span class="live-date date" style="color: ${matchingContainer.topbar_date_font_color}; font-style: ${matchingContainer.topbar_date_font_style}; font-family: ${matchingContainer.topbar_date_font_family};"></span>
                                            </div>
                                        </div>
                                    ` : ''}
                                    <div class='tv-screen' style='
                                        height: ${matchingContainer.tv_height_px}px; 
                                        width: ${matchingContainer.tv_width_px}px; 
                                        border: none;
                                        border-radius: 0;
                                        ${matchingContainer.isImageBackground === '1' ? 
                                            (matchingContainer.background_image_path ? 
                                                `background-image: url(servers/tv_backgrounds/${matchingContainer.tv_id}/${matchingContainer.background_image_path}); 
                                                background-size: cover; 
                                                background-position: center;` : 
                                                'background-color: #000;'
                                            ) :
                                            (matchingContainer.isStaticBackground === '1' ? 
                                                `background-color: ${matchingContainer.background_hex_color};` : 
                                                'background-color: #000;'
                                            )
                                        }'>
                                        ${matchingContainer.visible === '1' ? 
                                            `<div class="carousel-content" 
                                                style="position: relative;
                                                        transform: translate(${matchingContainer.xaxis}px, ${matchingContainer.yaxis}px);
                                                        width: ${matchingContainer.width_px}px;
                                                        height: ${matchingContainer.height_px}px;
                                                        background-color: ${matchingContainer.parent_background_color};
                                                        padding: 5px;
                                                        border-radius: 5px;">
                                                <h1 style="color: ${matchingContainer.parent_font_color}; 
                                                        font-family: ${matchingContainer.parent_font_family}; 
                                                        font-style: ${matchingContainer.parent_font_style}; 
                                                        margin-bottom: 5px;
                                                        text-overflow: ellipsis;
                                                        overflow: hidden;
                                                        white-space: nowrap;
                                                        width: auto;
                                                        max-width: 100%;
                                                        font-size: 2.5vh">
                                                    ${matchingContainer.container_name}
                                                </h1>
                                                <div id="carouselContent-${matchingContainer.tv_id}" style="background-color: ${matchingContainer.child_background_color}; 
                                                        color: ${matchingContainer.child_font_color}; 
                                                        font-style: ${matchingContainer.child_font_style}; 
                                                        font-family: ${matchingContainer.child_font_family}; 
                                                        width: auto; 
                                                        height: calc(100% - 9.5vh); 
                                                        display: flex;
                                                        flex-direction: column;
                                                        padding: 5px; 
                                                        border-radius: 5px">
                                                    <div class="carousel-item-2 active" style="width: auto; margin-bottom: 5px; height: 100%; border-radius: 5px;">
                                                        <div style="border: none; border-top-left-radius: 5px; border-top-right-radius: 5px; text-align: justify; height: 100%; bottom: 0;">
                                                            <div class="content-container-con" style="height: 100%;">
                                                                <div class="content-main">
                                                                    ${selectedType === 'promaterial' ? 
                                                                        mediaContent : 
                                                                        `${mediaContent ? 
                                                                            `<div class="media-container" style="margin-bottom: 5px; max-height: 80%">
                                                                                ${mediaContent}
                                                                            </div>` : 
                                                                            ''
                                                                        }
                                                                        <div class="main-message" style="margin: 0; word-break: break-word;">
                                                                            ${selectedType === 'peo' ? 
                                                                                `<h2>${quillEditors.peoTitleQuill.root.innerHTML}</h2>
                                                                                <div>${quillEditors.peoDescriptionQuill.root.innerHTML}</div>
                                                                                <div>${quillEditors.peoSubdescriptionQuill.root.innerHTML}</div>` :
                                                                            selectedType === 'so' ? 
                                                                                `<h2>${quillEditors.soTitleQuill.root.innerHTML}</h2>
                                                                                <div>${quillEditors.soDescriptionQuill.root.innerHTML}</div>
                                                                                <div>${quillEditors.soSubdescriptionQuill.root.innerHTML}</div>` :
                                                                            selectedType === 'announcement' ? quillEditors.announcementBodyQuill.root.innerHTML :
                                                                            selectedType === 'event' ? quillEditors.eventBodyQuill.root.innerHTML :
                                                                            selectedType === 'news' ? quillEditors.newsBodyQuill.root.innerHTML :
                                                                            selectedType === 'facility' ? quillEditors.facilityBodyQuill.root.innerHTML :
                                                                            ''}
                                                                        </div>`
                                                                    }
                                                                </div>
                                                                <div class="content-details">
                                                                    <div style="display: flex; flex-direction: row; margin: 0">
                                                                        <div>
                                                                            <p class="author" style="margin: 0; font-size: 1.9vh;"><small>Posted on ${new Date().toLocaleString()}</small></p>
                                                                        </div>
                                                                        <div style="margin-left: auto; margin-top: auto">
                                                                            <p class="display-time" style="text-align: right; margin: 0; font-size: 1.9vh;">
                                                                                <i class="fa fa-hourglass-half" aria-hidden="true"></i> 
                                                                                <span class="time-left">${document.querySelector('[name="display_time"]').value}s</span>
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>` :
                                            `<div class="carousel-content" 
                                                style="position: relative;
                                                        transform: translate(5px, 5px);
                                                        width: 400px;
                                                        height: 400px;
                                                        background-color: ${matchingContainer.parent_background_color};
                                                        padding: 5px;
                                                        border-radius: 5px;
                                                        display: flex;
                                                        justify-content: center;
                                                        align-items: center;
                                                        flex-direction: column;">
                                                    <h1 style="font-size: 35px; color: ${matchingContainer.parent_font_color}"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></h1> 
                                                    <h1 style="color: ${matchingContainer.parent_font_color}; 
                                                        font-family: ${matchingContainer.parent_font_family}; 
                                                        font-style: ${matchingContainer.parent_font_style}; 
                                                        margin-bottom: 5px;
                                                        text-align: center;
                                                        align-content: center;">
                                                    ${selectedType.charAt(0).toUpperCase() + selectedType.slice(1)} Container not visible in the TV
                                                </h1>
                                            </div>`
                                        }
                                    </div>
                                    ${topbarPositions[matchingContainer.tv_id] === 'bottom' ? `
                                        <div class="topbar" id="topbar" style="background: ${matchingContainer.topbar_hex_color}">
                                            <img src="${matchingContainer.department_logo_path ? 
                                            `servers/tv_department_logos/${matchingContainer.tv_id}/${matchingContainer.department_logo_path}` : 
                                            'images/soe_icon.png'}" 
                                            alt="" style="width: 5vh; height: 5vh; margin-left: 5px">
                                            <div class="tv-id" style="color: ${matchingContainer.topbar_tvid_font_color}; font-style: ${matchingContainer.topbar_tvid_font_style}; font-family: ${matchingContainer.topbar_tvid_font_family}; font-size: 2.5vh; padding-left: 10px;">
                                                <p id="tvDepartment_${matchingContainer.tv_id}">${matchingContainer.tv_department}</p>
                                                <p id="tv-id"><i class="fa fa-television" aria-hidden="true"></i>: ${matchingContainer.tv_id}</p>
                                            </div>
                                            <h1 class="tv-name" style="color: ${matchingContainer.topbar_tvname_font_color}; font-style: ${matchingContainer.topbar_tvname_font_style}; font-family: ${matchingContainer.topbar_tvname_font_family};">
                                                ${matchingContainer.tv_name}
                                            </h1>
                                            <div class="date-time">
                                                <span class="live-clock time" style="font-weight: bold; color: ${matchingContainer.topbar_time_font_color}; font-style: ${matchingContainer.topbar_time_font_style}; font-family: ${matchingContainer.topbar_time_font_family};"></span>
                                                <span class="live-date date" style="color: ${matchingContainer.topbar_date_font_color}; font-style: ${matchingContainer.topbar_date_font_style}; font-family: ${matchingContainer.topbar_date_font_family};"></span>
                                            </div>
                                        </div>
                                    ` : ''}
                                    <p id="tvBrand2UserHome_${matchingContainer.tv_id}" style="text-align: center; font-size: 25px; margin-top: auto; color: white; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; width: auto; max-width: 250px;">${matchingContainer.tv_brand}</p>
                                    <div class="tv-stand"></div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                carouselHTML += '</div>';

                // Left and Right Navigation buttons
                if (matchingContainers.length > 1) {
                    const initialTvName = tvNames[matchingContainers[0].tv_id] || 'Unknown TV';
                    carouselHTML += `
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <button type="button" class="carousel-control prev" onclick="moveCarousel(-1)"><i class="fa fa-angle-left" aria-hidden="true"></i> Previous</button>
                            <p id="carouselTvName" style="text-overflow: ellipsis; overflow: hidden; white-space: nowrap; width: auto; max-width: 200px;">${initialTvName}</p>
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
                    // Hide current item
                    items[currentIndex].style.display = 'none';
                    items[currentIndex].classList.remove('active');

                    // Update index
                    currentIndex += direction;

                    // Loop around if at the ends
                    if (currentIndex < 0) {
                        currentIndex = items.length - 1;
                    } else if (currentIndex >= items.length) {
                        currentIndex = 0;
                    }

                    // Reset transform on all frames before showing new one
                    items.forEach(item => {
                        const tvId = item.getAttribute('data-tv-id');
                        const frame = document.getElementById(`tvFrame-${tvId}`);
                        if (frame) {
                            frame.style.transform = 'none';
                        }
                    });

                    // Show new item
                    items[currentIndex].style.display = 'block';
                    items[currentIndex].classList.add('active');

                    // Small delay to ensure DOM updates before fitting
                    setTimeout(() => {
                        updateTvName();
                        fitFrameToParent();
                    }, 0);
                };


                const carouselContent = document.querySelector('.carousel-content');

                // Modified fit frame function to maintain aspect ratio
                function fitFrameToParent() {
                    const activeItem = document.querySelector('.carousel-item.active');
                    if (!activeItem) return;
                    
                    const tvId = activeItem.getAttribute('data-tv-id');
                    const tvFrame = document.getElementById(`tvFrame-${tvId}`);
                    if (!tvFrame) return;

                    const parent = tvFrame.parentElement;
                    const parentRect = parent.getBoundingClientRect();
                    
                    // Reset any existing transformations
                    tvFrame.style.transform = 'none';
                    
                    // Force a reflow
                    void tvFrame.offsetWidth;
                    
                    const frameRect = tvFrame.getBoundingClientRect();
                    
                    // Calculate scale while leaving room for the TV stand
                    const scaleX = (parentRect.width) / frameRect.width;
                    const scaleY = (parentRect.height - 50) / frameRect.height; // Subtract some pixels for TV stand
                    const scale = Math.min(scaleX, scaleY, 0.5);

                    // Apply transformations with adjusted position
                    tvFrame.style.transform = `scale(${scale})`;
                    tvFrame.style.transformOrigin = 'center top';
                    tvFrame.style.marginBottom = '50px'; // Add margin to ensure stand visibility
                }

                // Fit frame on load
                fitFrameToParent();

                // Refit on window resize
                // window.addEventListener('resize', fitFrameToParent);
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

        // Function to update the clock
        function updateClock() {
            const clockElements = document.getElementsByClassName('live-clock');
            const dateElements = document.getElementsByClassName('live-date');
            
            if (clockElements.length > 0 && dateElements.length > 0) {
                const now = new Date();
                const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
                const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sept", "Oct", "Nov", "Dec"];
                
                let hours = now.getHours();
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12 || 12; // Convert to 12-hour format
                
                const dayOfWeek = daysOfWeek[now.getDay()];
                const month = months[now.getMonth()];
                const day = now.getDate().toString().padStart(2, '0');
                const year = now.getFullYear();

                const timeString = `${hours}:${minutes} ${ampm}`;
                const dateString = `${dayOfWeek}, ${month} ${day}, ${year}`;

                // Update all clock elements
                Array.from(clockElements).forEach(element => {
                    element.textContent = timeString;
                });

                // Update all date elements
                Array.from(dateElements).forEach(element => {
                    element.textContent = dateString;
                });
            }
        }
    </script>
</body>
</html>