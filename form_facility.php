<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';
include 'user_theme_color.php';

// check if the user is not Super Admin or Admin
include 'admin_access_only.php';

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
    <script src="jquery-textfill-master/dist/jquery.textfill.min.js"></script>
    <script src="jquery-textfill-master/dist/jquery.textfill.js"></script>
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
                            <div class="form-title-container">
                                <h1 class="form-title"><i class="fa fa-building" style="margin-right: 6px"></i>Facilities Form</h1>
                            </div>
                            <div class="form-container">
                                <div class="preview-media" style="border-radius: 5px; background: white; text-align: center; width: 100%; height: 300px; justify-content: center; align-items: center; box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 6px;">
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
                                <div class="line-separator"></div>
                                <div class="floating-label-container">
                                    <div id="quillEditorContainer">
                                        <label for="quillEditorContainer" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: <?php echo $theme_color; ?>; font-size: 12px; font-weight: bold">Facility Body</label>
                                        <div id="facility_body" style="height: 150px;"></div>
                                    </div>
                                    <input type="hidden" name="facility_body" id="facilityBodyHiddenInput">
                                </div>
                                <div class="form-column">
                                    <?php 
                                        $type = 'facility';
                                    ?>
                                    <?php include('misc/php/displaytime_tvdisplay.php')?>
                                </div>
                                <div style="display: flex; flex-direction: row; margin-left: auto; margin-top: 10px; justify-content: flex-end">
                                    <div>
                                        <button type="button" name="preview" id="previewButton" class="preview-button" style="margin-right: 0" onclick="validateAndOpenPreviewModal()">
                                            Preview <i class="fa fa-chevron-right" style="padding-left: 5px"></i> 
                                        </button>
                                    </div>
                                </div>
                                <?php include('misc/php/preview_modal_new.php') ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="misc/js/is_form_dirty.js"></script>
    <?php include('misc/php/error_modal.php') ?>
    <?php include('misc/php/success_modal.php') ?>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <script src="misc/js/quill_textarea_submission.js"></script>
    <script>
        const containers = <?php echo json_encode($containers); ?>;
        const tvNames = <?php echo !empty($tv_names) ? json_encode($tv_names) : '{}'; ?>; 
        const userDepartment = <?php echo json_encode($department); ?>;
    </script>
    <script>
        let currentSlide = 0;
        const uploadedImages = [];

        function handleImageUpload(event) {
            const files = event.target.files;
            const slideshowContainer = document.querySelector('.image-slides');
            const dotContainer = document.querySelector('.dot-indicators');

            // Process all files at once
            const promises = Array.from(files).map(file => {
                return new Promise((resolve) => {
                    if (file.type.startsWith('image/')) {
                        // Create progress bar container
                        const progressContainer = document.createElement('div');
                        progressContainer.className = 'upload-progress-container';
                        progressContainer.style.position = 'absolute';
                        progressContainer.style.top = '50%';
                        progressContainer.style.left = '50%';
                        progressContainer.style.transform = 'translate(-50%, -50%)';
                        progressContainer.style.width = '80%';
                        progressContainer.style.textAlign = 'center';

                        const progressBar = document.createElement('div');
                        progressBar.className = 'progress-bar';
                        progressBar.style.width = '100%';
                        progressBar.style.height = '20px';
                        progressBar.style.backgroundColor = '#f0f0f0';
                        progressBar.style.borderRadius = '10px';
                        progressBar.style.overflow = 'hidden';
                        progressBar.style.marginBottom = '10px';

                        const progressFill = document.createElement('div');
                        progressFill.style.width = '0%';
                        progressFill.style.height = '100%';
                        progressFill.style.backgroundColor = '<?php echo $theme_color; ?>';
                        progressFill.style.transition = 'width 0.3s';

                        const progressText = document.createElement('div');
                        progressText.style.color = 'white';
                        progressText.style.marginTop = '5px';
                        progressText.textContent = 'Uploading: 0%';

                        progressBar.appendChild(progressFill);
                        progressContainer.appendChild(progressBar);
                        progressContainer.appendChild(progressText);

                        // Create temporary container for progress
                        const tempContainer = document.createElement('div');
                        tempContainer.className = 'image-preview-container';
                        tempContainer.style.position = 'relative';
                        tempContainer.style.width = '100%';
                        tempContainer.style.height = '100%';
                        tempContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.1)';
                        tempContainer.appendChild(progressContainer);
                        slideshowContainer.appendChild(tempContainer);

                        // Create FormData for pre-upload
                        const formData = new FormData();
                        formData.append('media', file);

                        // Send to pre-upload handler with progress tracking
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', 'misc/php/handle_pre_upload.php', true);

                        xhr.upload.onprogress = function(e) {
                            if (e.lengthComputable) {
                                const percentComplete = (e.loaded / e.total) * 100;
                                progressFill.style.width = percentComplete + '%';
                                progressText.textContent = `Uploading: ${Math.round(percentComplete)}%`;
                            }
                        };

                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                try {
                                    const data = JSON.parse(xhr.responseText);
                                    if (data.success) {
                                        const reader = new FileReader();
                                        reader.onload = function(e) {
                                            // Remove temporary container
                                            tempContainer.remove();

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
                                            img.style.display = 'block';
                                            img.style.margin = 'auto';

                                            // Store the pre-uploaded file info
                                            img.dataset.preUploadedFile = data.tempFile;
                                            img.dataset.originalFileName = file.name;
                                            img.dataset.mediaType = file.type;

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
                                                e.stopPropagation();
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
                                        tempContainer.remove();
                                        console.error('Pre-upload failed:', data.error);
                                        resolve();
                                    }
                                } catch (error) {
                                    tempContainer.remove();
                                    console.error('Error parsing response:', error);
                                    resolve();
                                }
                            } else {
                                tempContainer.remove();
                                console.error('Upload failed with status:', xhr.status);
                                resolve();
                            }
                        };

                        xhr.onerror = function() {
                            tempContainer.remove();
                            console.error('Upload error:', xhr.statusText);
                            resolve();
                        };

                        xhr.send(formData);
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
            // Show loading modal immediately when submission starts
            const loadingModal = document.getElementById('loadingModal');
            loadingModal.style.display = 'flex';

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

            // Add pre-uploaded files info
            if (uploadedImages.length > 0) {
                data.preUploadedFiles = uploadedImages.map(container => {
                    const img = container.querySelector('img');
                    return {
                        preUploadedFile: img.dataset.preUploadedFile,
                        originalFileName: img.dataset.originalFileName,
                        mediaType: img.dataset.mediaType
                    };
                });
            }

            // Convert FormData to object
            formData.forEach((value, key) => {
                if (key !== 'media[]') { // Skip the file input
                    data[key] = value;
                }
            });

            console.log(data);

            sendDataToWebSocket(data, loadingModal);
        }

        function sendDataToWebSocket(data, loadingModal) {
            // Update loading message for data processing
            document.getElementById('loadingMessage').textContent = 'Processing your content...';
            
            // ws.send(JSON.stringify(data));

            const tryReconnect = () => {
                // Hide loading modal during reconnection
                loadingModal.style.display = 'none';
                document.getElementById('loadingMessage').textContent = 'Reconnecting to server...';
                
                // Fetch the WebSocket URL and attempt to reconnect
                fetch('websocket_conn.php')
                    .then(response => response.text())
                    .then(url => {
                        window.ws = new WebSocket(url);
                        // Once reconnected, retry sending the data
                        window.ws.onopen = function() {
                            loadingModal.style.display = 'flex';
                            document.getElementById('loadingMessage').textContent = 'Processing your content...';
                            sendDataToWebSocket(data, loadingModal);
                        };
                    })
                    .catch(error => {
                        console.error('Error reconnecting:', error);
                        // Retry reconnection after delay
                        setTimeout(tryReconnect, 1500);
                    });
            };

            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify(data));
            } else {
                tryReconnect();
            }

            ws.onmessage = function(event) {
                const message = JSON.parse(event.data);
                if (message.action === 'post_content') {
                    if (message.success == true) {
                        // Hide loading modal
                        loadingModal.style.display = 'none';
                        const action = 'processed';
                        
                        document.getElementById('successMessage').textContent = `${capitalizeFirstLetter(contentType)} was successfully ${action}!`;                        
                        document.getElementById('successMessageModal').style.display = 'flex';
                        isFormDirty = false;
                    } else if (message.success == false) {
                        // Hide loading modal
                        loadingModal.style.display = 'none';
                        // Handle error case if needed
                        document.getElementById('errorText').textContent = message.error || 'An error occurred while processing your request.';
                        document.getElementById('errorModal').style.display = 'flex';
                    }
                }
            };

            ws.onclose = function(event) {
                loadingModal.style.display = 'none';
                document.getElementById('errorText').textContent = 'Connection error occurred.';
                document.getElementById('errorModal').style.display = 'flex';
                tryReconnect();
            };

            ws.onerror = function(error) {
                loadingModal.style.display = 'none';
                document.getElementById('errorText').textContent = 'Connection error occurred.';
                document.getElementById('errorModal').style.display = 'flex';
                tryReconnect();
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

            // Make uploadedImages accessible globally for the preview
            window.uploadedImages = uploadedImages;

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
            
            // Clear interval when modal is closed by clicking outside
            window.onclick = function(event) {
                if (event.target == modal) {
                    closePreviewModal();
                }
            }
        }

        // Add event listener to tv_id select element
        document.addEventListener("DOMContentLoaded", function () {
            const tvCheckboxes = document.querySelectorAll('[name="tv_id[]"]');
            tvCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updatePreviewContent);
            });
        });
    </script>
</body>
</html>