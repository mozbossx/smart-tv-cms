<?php
echo '<div class="right-flex">';
echo '<div class="rounded-container-media">';
    echo '<p class="input-container-label">Upload Media (Optional)</p>';
    echo '<br>';
    echo '<input type="file" name="media" id="media" accept="video/*, image/*" onchange="previewMedia()" hidden>';
    echo '<label for="media" class="choose-file-button">Choose File (.mp4, .jpg, .png)</label>';
    echo '<button type="button" id="cancelMediaButton" class="red-button" onclick="cancelMedia()" style="display: none;">Cancel</button>';
    echo '<div class="preview-media" style="border: #000 1px solid; border-radius: 5px; background: white; text-align: center; width: 100%; height: 350px; display: none; justify-content: center; align-items: center; margin-top: 15px">';
        echo '<video id="video-preview" width="100%" height="350px" controls style="display:none; border-radius: 5px; background: #000;"></video>';
        echo '<img id="image-preview" style="display:none; max-width: 100%; max-height: 100%;">';
    echo '</div>';
echo '</div>';
echo '</div>';

?>