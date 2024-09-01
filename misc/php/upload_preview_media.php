<div class="right-flex">
    <div class="rounded-container-media">
        <p class="input-container-label">Upload Media (Optional)</p>
        <br>
        <input type="file" name="media" id="media" accept="video/*, image/*" onchange="previewMedia()" hidden>
        <label for="media" class="choose-file-button">Choose File (.mp4, .jpg, .png)</label>
        <button type="button" id="cancelMediaButton" class="red-button" onclick="cancelMedia()" style="display: none;">Cancel</button>
        <div class="preview-media" style="border: #000 1px solid; border-radius: 5px; background: white; text-align: center; width: 100%; height: 350px; display: none; justify-content: center; align-items: center; margin-top: 15px">
            <video id="video-preview" width="100%" height="350px" controls style="display:none; border-radius: 5px; background: #000;"></video>
            <img id="image-preview" style="display:none; max-width: 100%; max-height: 100%;">
        </div>
    </div>
</div>

<script>
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
</script>