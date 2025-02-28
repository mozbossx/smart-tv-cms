<?php

$mediaPath = ($isEditMode && isset($contentData['media_path'])) ? $contentData['media_path'] : '';

?>

<!-- progress bar container -->
<div id="uploadProgressContainer" style="display: none; margin-top: 10px; width: 100%;">
    <div style="width: 100%; background-color: #f0f0f0; border-radius: 4px;">
        <div id="uploadProgressBar" style="width: 0%; height: 20px; background-color: #4CAF50; border-radius: 4px; transition: width 0.3s;">
            <div id="uploadProgressText" style="color: white; text-align: center; line-height: 20px; font-size: 12px;">0%</div>
        </div>
    </div>
</div>

<!-- upload status message -->
<div id="uploadStatus" style="margin-top: 5px; font-size: 12px;"></div>
<div class="right-flex">
    <div class="rounded-container-media">
        <p class="input-container-label">Upload Media (.mp4, .jpg, .png)</p>
        <br>
        <input type="file" name="media" id="media" accept="video/*, image/*" onchange="handleMediaSelect()" hidden>
        <label for="media" class="green-button">Choose File</label>

        <?php 
        // Only show cancel button if NOT in promaterial edit mode
        if (!($isEditMode && isset($_GET['type']) && $_GET['type'] === 'promaterial')): 
        ?>
            <button type="button" id="cancelMediaButton" class="red-button" onclick="cancelMedia()" style="display: none;">
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
        <?php endif; ?>
        <div class="preview-media" style="border: #000 1px solid; border-radius: 5px; background: white; text-align: center; width: 100%; height: 350px; display: none; justify-content: center; align-items: center; margin-top: 15px">
            <video id="video-preview" width="100%" height="350px" controls style="display:none; border-radius: 5px; background: #000;"></video>
            <img id="image-preview" style="display:none; max-width: 100%; max-height: 100%;">
        </div>
        <?php if ($isEditMode && $mediaPath): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const previewMedia = document.querySelector('.preview-media');
            const videoPreview = document.getElementById('video-preview');
            const imagePreview = document.getElementById('image-preview');
            if (document.getElementById('cancelMediaButton')) {
                const cancelMediaButton = document.getElementById('cancelMediaButton');
                cancelMediaButton.style.display = 'inline-block';
            }
           
            previewMedia.style.display = 'flex';
            
            <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $mediaPath)): ?>
            imagePreview.src = 'servers/<?php echo $_GET['type']; ?>_media/<?php echo $mediaPath; ?>';
            imagePreview.style.display = 'block';
            <?php else: ?>
            videoPreview.src = 'servers/<?php echo $_GET['type']; ?>_media/<?php echo $mediaPath; ?>';
            videoPreview.style.display = 'block';
            <?php endif; ?>
        });
        </script>
        <?php endif; ?>
    </div>
</div>

<script>
    // Store original display time options when page loads
    let originalDisplayTimeOptions = '';
    let preUploadedFile = null;

    document.addEventListener('DOMContentLoaded', function() {
        const displayTimeSelect = document.getElementById('display_time');
        if (displayTimeSelect) {
            // Store original options for later use
            originalDisplayTimeOptions = displayTimeSelect.innerHTML;
            
            // If in edit mode and there's a video, set display time to video duration
            <?php if ($isEditMode && isset($contentData['media_path']) && preg_match('/\.(mp4|mov|wmv|avi|mkv)$/i', $contentData['media_path'])): ?>
                const videoElement = document.getElementById('video-preview');
                if (videoElement) {
                    videoElement.onloadedmetadata = function() {
                        const videoDuration = Math.ceil(this.duration);
                        displayTimeSelect.innerHTML = `<option value="${videoDuration}">${videoDuration} seconds</option>`;
                        displayTimeSelect.value = videoDuration;
                        displayTimeSelect.style.color = 'black';
                    };
                }
            <?php endif; ?>
        }
    });

    window.preUploadedFile = null;
    function handleMediaSelect() {
        const mediaInput = document.getElementById('media');
        const progressContainer = document.getElementById('uploadProgressContainer');
        const progressBar = document.getElementById('uploadProgressBar');
        const progressText = document.getElementById('uploadProgressText');
        const uploadStatus = document.getElementById('uploadStatus');

        if (mediaInput.files && mediaInput.files[0]) {
            const file = mediaInput.files[0];
            console.log('Debug - File selected:', file.name);
            
            // Show progress container
            progressContainer.style.display = 'block';
            uploadStatus.textContent = 'Preparing upload...';

            // Create FormData for upload
            const formData = new FormData();
            formData.append('media', file);

            // Perform pre-upload
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'misc/php/handle_pre_upload.php', true);

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                    progressText.textContent = Math.round(percentComplete) + '%';
                }
            };

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        console.log('Debug - Pre-upload response:', response);
                        if (response.success) {
                            // Explicitly set as a global variable
                            window.preUploadedFile = response.tempFile;
                            console.log('Debug - Stored preUploadedFile:', window.preUploadedFile);
                            uploadStatus.textContent = 'File pre-uploaded successfully!';
                            // Store in sessionStorage as backup
                            sessionStorage.setItem('preUploadedFile', response.tempFile);
                            previewMedia();
                        } else {
                            uploadStatus.textContent = 'Error: ' + response.error;
                            resetUpload();
                        }
                    } catch (e) {
                        console.error('Debug - JSON parse error:', e);
                        uploadStatus.textContent = 'Error processing server response';
                        resetUpload();
                    }
                } else {
                    uploadStatus.textContent = 'Upload failed. Please try again.';
                    resetUpload();
                }
            };

            xhr.onerror = function() {
                console.error('Debug - Upload error:', xhr.statusText);
                uploadStatus.textContent = 'Upload failed. Please try again.';
                resetUpload();
            };

            xhr.send(formData);
        }
    }

    function resetUpload() {
        const progressContainer = document.getElementById('uploadProgressContainer');
        const progressBar = document.getElementById('uploadProgressBar');
        const progressText = document.getElementById('uploadProgressText');
        const uploadStatus = document.getElementById('uploadStatus');
        
        progressContainer.style.display = 'none';
        uploadStatus.style.display = 'none';
        progressBar.style.width = '0%';
        progressText.textContent = '0%';
        preUploadedFile = null;
    }

    // Add this function to check preUploadedFile status
    function getPreUploadedFile() {
        return window.preUploadedFile || sessionStorage.getItem('preUploadedFile');
    }

    // Function to preview selected video or image
    function previewMedia() {
        var mediaInput = document.getElementById('media');
        var videoPreview = document.getElementById('video-preview');
        var imagePreview = document.getElementById('image-preview');
        var previewMedia = document.querySelector('.preview-media'); 
        var displayTimeSelect = document.getElementById('display_time');
        if (document.getElementById('cancelMediaButton')) {
            var cancelMediaButton = document.getElementById('cancelMediaButton');
        }

        if (mediaInput.files && mediaInput.files[0]) {
            var reader = new FileReader();
            var fileType = mediaInput.files[0].type;

            reader.onload = function (e) {
                // If it's a video file
                if (fileType.startsWith('video/')) {
                    // Create a temporary video element to get duration
                    var tempVideo = document.createElement('video');
                    tempVideo.src = e.target.result;
                    
                    tempVideo.onloadedmetadata = function() {
                        // Round up the duration to nearest second
                        const videoDuration = Math.ceil(tempVideo.duration);
                        
                        // Set the video duration as display time
                        if (displayTimeSelect) {                           
                            displayTimeSelect.innerHTML = `<option value="${videoDuration}">${videoDuration} seconds</option>`;
                            displayTimeSelect.value = videoDuration;
                            displayTimeSelect.style.color = 'black';
                        }
                    };

                    // Display video preview
                    videoPreview.src = e.target.result;
                    videoPreview.style.display = 'block';
                    imagePreview.style.display = 'none';
                } 
                
                // If it's an image file
                else if (fileType.startsWith('image/')) {
                    // For images, enable display time selection
                    if (displayTimeSelect) {
                        displayTimeSelect.innerHTML = originalDisplayTimeOptions;
                        displayTimeSelect.style.color = '';
                    }

                    // Display image preview
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    videoPreview.style.display = 'none';
                }

                // Show the preview-media container
                previewMedia.style.display = 'flex';
                if (cancelMediaButton) {
                    cancelMediaButton.style.display = 'inline-block';
                }
            };

            reader.readAsDataURL(mediaInput.files[0]);
        }
    }

    // Function to cancel the media upload
    function cancelMedia() {
        var mediaInput = document.getElementById('media');
        var videoPreview = document.getElementById('video-preview');
        var imagePreview = document.getElementById('image-preview');
        var previewMedia = document.querySelector('.preview-media');
        if (document.getElementById('cancelMediaButton')) {
            var cancelMediaButton = document.getElementById('cancelMediaButton');
        }
        // Re-enable display time selection and restore original options
        var displayTimeSelect = document.getElementById('display_time');
        if (displayTimeSelect) {
            displayTimeSelect.innerHTML = originalDisplayTimeOptions;
            displayTimeSelect.style.color = '';
        }

        // If in edit mode and there's an existing media path, delete it via AJAX
        <?php if ($isEditMode && isset($contentData['media_path'])): ?>
        if (confirm('Are you sure you want to remove this media?')) {
            fetch('misc/php/delete_media.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: '<?php echo $_GET['type']; ?>',
                    media_path: '<?php echo $contentData['media_path']; ?>',
                    content_id: '<?php echo $_GET[$_GET['type'] . '_id']; ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Media deleted successfully');
                } else {
                    console.error('Error deleting media:', data.error);
                }
            })
            .catch(error => console.error('Error:', error));
        } else {
            return; // Don't proceed with clearing if user cancels
        }
        <?php endif; ?>

        // Reset the file input
        mediaInput.value = '';

        // Clear the file input
        if (mediaInput.value) {
            mediaInput.type = 'text';
            mediaInput.type = 'file';
        }

        // Hide the previews and the preview-media container
        videoPreview.style.display = 'none';
        videoPreview.src = '';
        imagePreview.style.display = 'none';
        imagePreview.src = '';
        previewMedia.style.display = 'none';
        
        // Hide the cancel button
        if (cancelMediaButton) {
            cancelMediaButton.style.display = 'none';
        }
        resetUpload();
    }
</script>