<div id="previewModal" class="modal">
    <div class="modal-content-preview" style="padding: 15px">
        <div class="flex-preview-content">
            <?php 
                $sqlContainers = "SELECT c.*, t.*
                FROM containers_tb c 
                JOIN smart_tvs_tb t ON c.tv_id = t.tv_id";
                $resultContainers = mysqli_query($conn, $sqlContainers);
                $containers = [];
                while ($row = mysqli_fetch_assoc($resultContainers)) {
                    $containers[] = $row;
                }
            ?>
            <div style="display: flex, flex-direction: column; flex: 2; height: auto; overflow: auto">
                <div id="previewContainer"></div>
            </div>
            <!-- The container consists of child_background_color, child_font_style, child_font_color-->
            <div class="preview-content" id="previewContent"></div>
        </div>
        <!-- Operation buttons inside the Preview modal -->
        <div class="flex-button-modal" style="margin-top: 10px">
            <button type="button" class="grey-button" id="closeButton" style="margin-top: 0; margin-right: 10px" onclick="closePreviewModal()">Cancel</button>
            <button type="button" name="post" class="green-button" style="margin-top: 0; margin-right: 0" onclick="submitFormViaWebSocket()">Submit</button>
        </div>
    </div>
</div>

<script>
    document.getElementById('closeButton').addEventListener('click', function() {
        closePreviewModal();
    });

    // Function to close the preview modal
    function closePreviewModal() {
        var modal = document.getElementById('previewModal');
        modal.style.display = 'none';
    }

    function validateAndOpenPreviewModal() {
        const selectedType = document.querySelector('[name="type"]').value;
        const isEditMode = document.querySelector('input[name="action_value"]')?.value === 'edit';
        let contentBody = "";

        // Check if quillEditors is defined before trying to access it
        const hasQuillEditors = typeof window.quillEditors !== 'undefined';
        
        // Get the content based on the type
        if (selectedType === 'announcement') {
            contentBody = quillEditors.announcementBodyQuill.root.innerHTML.trim();
            const textContent = contentBody.replace(/<[^>]*>/g, '').trim();
            // Validate announcement requires text content
            if (contentBody === "" || contentBody === "<p><br></p>" || !textContent) {
                errorModalMessage("Please provide the announcement text content.");
                return;
            }
        } else if (selectedType === 'event') {
            contentBody = quillEditors.eventBodyQuill.root.innerHTML.trim();
            const textContent = contentBody.replace(/<[^>]*>/g, '').trim();
            // Validate announcement requires text content
            if (contentBody === "" || contentBody === "<p><br></p>" || !textContent) {
                errorModalMessage("Please provide the event text content.");
                return;
            }
        } else if (selectedType === 'news') {
            contentBody = quillEditors.newsBodyQuill.root.innerHTML.trim();
            const textContent = contentBody.replace(/<[^>]*>/g, '').trim();
            // Validate announcement requires text content
            if (contentBody === "" || contentBody === "<p><br></p>" || !textContent) {
                errorModalMessage("Please provide the news text content.");
                return;
            }
        } else if (selectedType === 'promaterial') {
            media = document.querySelector('[name="media"]').files[0];
        } else if (selectedType === 'peo') {
            peoTitle = quillEditors.peoTitleQuill.root.innerHTML.trim();
            peoDescription = quillEditors.peoDescriptionQuill.root.innerHTML.trim();
            peoSubdescription = quillEditors.peoSubdescriptionQuill.root.innerHTML.trim();

            // Validate PEO content
            const peoTitleText = peoTitle.replace(/<[^>]*>/g, '').trim();
            const peoDescriptionText = peoDescription.replace(/<[^>]*>/g, '').trim();

            if (!peoTitleText || peoTitle === "<p><br></p>") {
                errorModalMessage("Please provide the PEO title.");
                return;
            }
            if (!peoDescriptionText || peoDescription === "<p><br></p>") {
                errorModalMessage("Please provide the PEO description.");
                return;
            }

        } else if (selectedType === 'so') {
            soTitle = quillEditors.soTitleQuill.root.innerHTML.trim();
            soDescription = quillEditors.soDescriptionQuill.root.innerHTML.trim();
            soSubdescription = quillEditors.soSubdescriptionQuill.root.innerHTML.trim();

            // Validate SO content
            const soTitleText = soTitle.replace(/<[^>]*>/g, '').trim();
            const soDescriptionText = soDescription.replace(/<[^>]*>/g, '').trim();

            if (!soTitleText || soTitle === "<p><br></p>") {
                errorModalMessage("Please provide the SO title.");
                return;
            }
            if (!soDescriptionText || soDescription === "<p><br></p>") {
                errorModalMessage("Please provide the SO description.");
                return;
            }
        } else {
            // Handle new feature types
            // First look for any Quill editor in the window.quillEditors object that isn't a standard type
            if (hasQuillEditors) {
                const quillEditorKeys = Object.keys(window.quillEditors);
                console.log("Looking for input field Quill editors in:", quillEditorKeys);
                
                // Find all Quill editors that don't match standard types and end with "Quill"
                const customEditors = quillEditorKeys.filter(key => 
                    key.endsWith('Quill') && 
                    !['announcementBodyQuill', 'eventBodyQuill', 'newsBodyQuill', 'peoTitleQuill', 
                     'peoDescriptionQuill', 'peoSubdescriptionQuill', 'soTitleQuill', 'soDescriptionQuill', 
                     'soSubdescriptionQuill', 'facilityBodyQuill'].includes(key)
                );
                
                if (customEditors.length > 0) {
                    console.log(`Found ${customEditors.length} custom Quill editors:`, customEditors);
                    
                    // Check if all editors have content
                    const emptyEditors = customEditors.filter(editorKey => {
                        const content = window.quillEditors[editorKey].root.innerHTML.trim();
                        return content === "" || content === "<p><br></p>";
                    });
                    
                    if (emptyEditors.length > 0) {
                        errorModalMessage(`Please fill in all required fields: ${emptyEditors.map(key => key.replace('Quill', '')).join(', ')}`);
                        return;
                    }
                } else {
                    // No custom editors found, check for media input
                    const mediaInput = document.querySelector('[name="media"]');
                    if (mediaInput && mediaInput.files && mediaInput.files[0]) {
                        // Media exists, validation passed
                        contentBody = "";
                    } else {
                        errorModalMessage("Please upload a media file.");
                        return;
                    }
                }
            } else {
                // No Quill editors available, check for media
                const mediaInput = document.querySelector('[name="media"]');
                if (mediaInput && mediaInput.files && mediaInput.files[0]) {
                    // Media exists, validation passed
                    contentBody = "";
                } else {
                    errorModalMessage("Please upload a media file.");
                    return;
                }
            }
        }

        const displayTime = document.querySelector('[name="display_time"]').value;
        const tvDisplays = document.querySelectorAll('[name="tv_id[]"]:checked'); // Get all checked TV displays
        
        // If not PEO, validate expiration
        let expirationDateTime = null;
        
        // If not PEO, SO, or Promotional Material, validate expiration dates/times
        if (selectedType !== 'peo' && selectedType !== 'so' && selectedType !== 'promaterial') {
            const expirationDateTime = document.querySelector('[name="expiration_datetime"]').value;
            
            // Check if any of the required fields is empty
            // if (contentBody === "<p><br></p>" || contentBody === "" || displayTime === "" || tvDisplays.length === 0 || expirationDateTime === "") {
            //     errorModalMessage("Please fill the necessary fields and select at least one TV display.");
            //     return;
            // }

            // First check for media content for media-only features
            const mediaInput = document.querySelector('[name="media"]');
            const hasMedia = mediaInput && mediaInput.files && mediaInput.files[0];

            // Check if any of the required fields is empty
            if (displayTime === "" || tvDisplays.length === 0 || expirationDateTime === "") {
                errorModalMessage("Please fill the necessary fields and select at least one TV display.");
                return;
            }

            // For new features, we've already validated the Quill editors or media
            if (!hasQuillEditors || !Object.keys(window.quillEditors).some(key => 
                key.endsWith('Quill') && 
                !['announcementBodyQuill', 'eventBodyQuill', 'newsBodyQuill', 'peoTitleQuill', 
                 'peoDescriptionQuill', 'peoSubdescriptionQuill', 'soTitleQuill', 'soDescriptionQuill', 
                 'soSubdescriptionQuill', 'facilityBodyQuill'].includes(key)
            )) {
                // For non-standard types without Quill editors:
                if (selectedType !== 'announcement' && selectedType !== 'event' && selectedType !== 'news') {
                    // If we have media, validation passed
                    if (hasMedia) {
                        // If all conditions are met, open the preview modal
                        openPreviewModal();
                        return;
                    }
                    
                    // For non-Quill features, check contentBody
                    // If no media, then check contentBody
                    if (contentBody === "<p><br></p>" || contentBody === "") {
                        errorModalMessage("Please fill the necessary fields and select at least one TV display.");
                        return;
                    }
                }
                
                // For non-Quill features, check contentBody
                // If no media, then check contentBody
                if (contentBody === "<p><br></p>" || contentBody === "") {
                    errorModalMessage("Please fill the necessary fields and select at least one TV display.");
                    return;
                }
            }

            // Convert expiration date and time to Date object
            const currentDateTime = new Date();
            const expirationDate = new Date(expirationDateTime);

            if (expirationDate < currentDateTime) {
                errorModalMessage("Expiration date and time should not be in the past.");
                return;
            }
        } else if (selectedType === 'promaterial') {
            const mediaInput = document.querySelector('[name="media"]');
            const expirationDateTime = document.querySelector('[name="expiration_datetime"]').value;

            <?php if ($isEditMode && isset($contentData['media_path'])): ?>
                // In edit mode with existing media
                if (displayTime === "" || tvDisplays.length === 0 || expirationDateTime === "") {
                    errorModalMessage("Please select at least one TV display and fill in all required fields.");
                    return;
                }

                // For standard types in edit mode, also check if there is content in the Quill editor
                if (selectedType === 'announcement' || selectedType === 'event' || selectedType === 'news') {
                    const editorKey = `${selectedType}BodyQuill`;
                    if (hasQuillEditors && quillEditors[editorKey]) {
                        const editorContent = quillEditors[editorKey].root.innerHTML.trim();
                        if (editorContent === "" || editorContent === "<p><br></p>") {
                            errorModalMessage(`Please provide the ${selectedType} text content.`);
                            return;
                        }
                    }
                }
            <?php else: ?>
                // New post or edit mode without existing media
                if (!mediaInput.files[0] || displayTime === "" || tvDisplays.length === 0 || expirationDateTime === "") {
                    errorModalMessage("Please upload a media file and select at least one TV display.");
                    return;
                }
            <?php endif; ?>

            // Convert expiration date and time to Date object
            const currentDateTime = new Date();
            const expirationDate = new Date(expirationDateTime);

            if (expirationDate < currentDateTime) {
                errorModalMessage("Expiration date and time should not be in the past.");
                return;
            }
        } else if (selectedType === 'peo') {
            // For PEO, only check the basic fields
            if (peoTitle === "<p><br></p>" || peoDescription === "<p><br></p>" || displayTime === "" || tvDisplays.length === 0) {
                errorModalMessage("Please fill the necessary fields and select at least one TV display.");
                return;
            }
        } else if (selectedType === 'so') {
            // For SO, only check the basic fields
            if (soTitle === "<p><br></p>" || soDescription === "<p><br></p>" || displayTime === "" || tvDisplays.length === 0) {
                errorModalMessage("Please fill the necessary fields and select at least one TV display.");
                return;
            }
        }

        // If all conditions are met, open the preview modal
        openPreviewModal();
    }

    // Function to get the preview content
    function getPreviewContent() {
        const displayTime = document.querySelector('[name="display_time"]').value;
        const selectedType = document.querySelector('[name="type"]').value;
        const isEditMode = document.querySelector('input[name="action_value"]')?.value === 'edit';

        var previewContent = '';

        if (selectedType !== 'peo' && selectedType !== 'so') {
            previewContent += '<p class="preview-input"><strong>Display Time: </strong><br>' + displayTime + ' seconds</p>';
        } else {
            previewContent += '<p class="preview-input"><strong>Display Time: </strong><br>' + document.querySelector('[name="display_time"]').value + ' seconds</p>';
        }

        const selectedTvs = Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => checkbox.value);
        const selectedTvNames = selectedTvs.map(tvId => tvNames[tvId]); // Map tv_id to tv_name
        previewContent += '<p class="preview-input" style="text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><strong>TV Display: </strong><br>' + (selectedTvNames.length > 0 ? selectedTvNames.join(", ") : 'None selected') + '</p>';
        
        return previewContent;
    }

    // Function to safely send messages to iframes
    function sendPreviewDataToIframe() {        
        // Get all iframes
        const iframes = document.querySelectorAll('iframe.tv-screen');
        if (!iframes.length) {
            const allIframes = document.querySelectorAll('iframe');
            return;
        }
        
        // Collect the form data
        const selectedType = document.querySelector('[name="type"]').value;
        const displayTime = document.querySelector('[name="display_time"]').value;
        const isEditMode = document.querySelector('input[name="action_value"]')?.value === 'edit';

        // Get the content based on content type
        let content;

        // Check if quillEditors is defined before trying to access it
        const hasQuillEditors = typeof window.quillEditors !== 'undefined';
        
        console.log("Preview mode: " + (isEditMode ? "Edit" : "Create"));
        console.log("Content type: " + selectedType);

        switch(selectedType) {
            case 'announcement':
                content = quillEditors.announcementBodyQuill.root.innerHTML;
                break;
            case 'event':
                content = quillEditors.eventBodyQuill.root.innerHTML;
                break;
            case 'news':
                content = quillEditors.newsBodyQuill.root.innerHTML;
                break;
            case 'promaterial':
                content = null; // Main content is the media
                break;
            case 'peo':
                if (isEditMode) {
                    // For edit mode, create a properly formatted HTML string with all editors
                    content = `
                        <div class="peo-title">${quillEditors.peoTitleQuill.root.innerHTML}</div>
                        <div class="peo-description">${quillEditors.peoDescriptionQuill.root.innerHTML}</div>
                        <div class="peo-subdescription">${quillEditors.peoSubdescriptionQuill.root.innerHTML}</div>
                    `;
                } else {
                    // For normal mode, ensure we use the correct property names that match what fetch_preview_content.js expects
                    content = {
                        title: quillEditors.peoTitleQuill.root.innerHTML,
                        description: quillEditors.peoDescriptionQuill.root.innerHTML,
                        subdescription: quillEditors.peoSubdescriptionQuill.root.innerHTML
                    };
                }
                break;
            case 'so':
                if (isEditMode) {
                    // For edit mode, create a properly formatted HTML string with all editors
                    content = `
                        <div class="so-title">${quillEditors.soTitleQuill.root.innerHTML}</div>
                        <div class="so-description">${quillEditors.soDescriptionQuill.root.innerHTML}</div>
                        <div class="so-subdescription">${quillEditors.soSubdescriptionQuill.root.innerHTML}</div>
                    `;
                } else {
                    content = `
                        <div class="so-title">${quillEditors.soTitleQuill.root.innerHTML}</div>
                        <div class="so-description">${quillEditors.soDescriptionQuill.root.innerHTML}</div>
                        <div class="so-subdescription">${quillEditors.soSubdescriptionQuill.root.innerHTML}</div>
                    `;
                }
                break;
            case 'orgchart':
                // Use the global orgChartData array and wrap it in the members property
                content = {
                    members: orgChartData
                };
                break;
            case 'facility':
                content = quillEditors.facilityBodyQuill.root.innerHTML;
                break;
            default:
                // Handle new features or custom content types
                content = '';
                console.log(`Looking for editors for type: ${selectedType}`);
                
                // First check for direct input name Quill variables
                // Get all registered Quill editors in the quillEditors object
                const quillEditorKeys = hasQuillEditors ? Object.keys(window.quillEditors) : [];
                console.log("Available Quill editors:", quillEditorKeys);
                
                // Look for ALL editors that might be custom input fields
                const standardEditors = ['announcementBodyQuill', 'eventBodyQuill', 'newsBodyQuill', 
                                         'peoTitleQuill', 'peoDescriptionQuill', 'peoSubdescriptionQuill', 
                                         'soTitleQuill', 'soDescriptionQuill', 'soSubdescriptionQuill', 
                                         'facilityBodyQuill'];
                
                // Find all custom Quill editors
                const customEditors = quillEditorKeys.filter(key => 
                    key.endsWith('Quill') && !standardEditors.includes(key)
                );
                
                if (customEditors.length > 0) {
                    // // We found custom editors - collect content from all of them
                    // console.log(`Found ${customEditors.length} custom Quill editors:`, customEditors);
                    
                    // // If multiple editors, build formatted HTML with all editor contents
                    // if (customEditors.length > 1) {
                    //     let formattedContent = '';
                    //     customEditors.forEach((editorKey, index) => {
                    //         // Get friendly name by removing "Quill" suffix
                    //         const editorName = editorKey.replace('Quill', '');
                    //         const editorLabel = editorName.replace(/_/g, ' ');
                    //         const editorContent = window.quillEditors[editorKey].root.innerHTML;
                            
                    //         // Wrap each editor content in a section with heading
                    //         formattedContent += `
                    //             ${editorContent}
                    //         `;
                    //     });
                        
                    //     content = formattedContent;
                    //     console.log("Created formatted content with multiple editors");
                    // } else {
                    //     // Just one editor - use its content directly
                    //     content = window.quillEditors[customEditors[0]].root.innerHTML;
                    //     console.log(`Using content from ${customEditors[0]}`);
                    // }

                    // We found custom editors - collect content from all of them
                    console.log(`Found ${customEditors.length} custom Quill editors:`, customEditors);
                    
                    // Always build formatted HTML with all editor contents
                    let formattedContent = '';
                    
                    // Always include all editors, even if there's just one
                    customEditors.forEach((editorKey) => {
                        const editorContent = window.quillEditors[editorKey].root.innerHTML;
                        formattedContent += editorContent;
                    });
                    
                    content = formattedContent;
                    console.log("Created formatted content from all custom editors");
                } 
                // Fallback to type-based naming patterns if no custom editors found
                else if (typeof window[`${selectedType}Quill`] !== 'undefined') {
                    // If there's a direct Quill editor variable with this name
                    content = window[`${selectedType}Quill`].root.innerHTML;
                    console.log(`Found direct ${selectedType}Quill variable`);
                } else if (hasQuillEditors && window.quillEditors[`${selectedType}Quill`]) {
                    // Try the text naming pattern first (common in new features)
                    content = quillEditors[`${selectedType}Quill`].root.innerHTML;
                    console.log(`Found ${selectedType}Quill in quillEditors`);
                } else if (hasQuillEditors && window.quillEditors[`${selectedType}BodyQuill`]) {
                    // Try standard body Quill naming pattern
                    content = quillEditors[`${selectedType}BodyQuill`].root.innerHTML;
                    console.log(`Found ${selectedType}BodyQuill in quillEditors`);
                } else {
                    // No editors found - check for media content
                    console.warn(`No Quill editor found for type: ${selectedType}`);
                    const mediaInput = document.getElementById('media');
                    if (mediaInput && mediaInput.files && mediaInput.files[0]) {
                        // Media exists, use it as content
                        content = "";
                        console.log(`Using media content for type: ${selectedType}`);
                    }
                }
                break;
        }
        
        // Prepare the preview data
        const previewData = {
            action: 'updatePreview',
            type: selectedType,
            content: content,
            media: null, // Will be updated if there's media
            displayTime: displayTime
        };

        // Handle media uploads based on content type
        if (selectedType === 'facility') {
            // For facility type, we need to handle multiple images
            const uploadedImages = window.uploadedImages || [];
            if (uploadedImages.length > 0) {
                // Collect all image data URLs
                const imageUrls = [];
                const imagePromises = uploadedImages.map((container, index) => {
                    return new Promise((resolve) => {
                        const img = container.querySelector('img');
                        if (img && img.dataset.preUploadedFile) {
                            // Already uploaded image
                            console.log(`Image ${index + 1} has preUploaded file`);
                            imageUrls.push(img.src);
                            resolve();
                        } else if (img && img.src) {
                            // Use direct src (which could be a data URL or a file path)
                            console.log(`Using direct src for image ${index + 1}`);
                            imageUrls.push(img.src);
                            resolve();
                        } else {
                            // No valid image source found
                            console.warn(`No valid source found for image ${index + 1}`);
                            resolve();
                        }
                    });
                });

                // When all images are processed, send to iframes
                Promise.all(imagePromises).then(() => {
                    if (imageUrls.length > 0) {
                        console.log(`Sending ${imageUrls.length} images for preview`);
                        previewData.media = imageUrls;
                        sendToIframes(previewData, iframes);
                    } else {
                        console.warn("No valid images found for facility preview");
                        sendToIframes(previewData, iframes);
                    }
                });
            } else {
                console.warn("No uploaded images found for facility");
                sendToIframes(previewData, iframes);
            }
        } else {
            // Handle media uploads
            let mediaHandled = false;
            const mediaInput = document.getElementById('media');

            // First check if new media is uploaded
            if (mediaInput && mediaInput.files && mediaInput.files[0]) {
                mediaHandled = true;
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewData.media = e.target.result;
                    sendToIframes(previewData, iframes);
                };
                reader.readAsDataURL(mediaInput.files[0]);
            } 
            // Then check for existing media in edit mode
            else if (isEditMode) {
                console.log("Checking for existing media in edit mode");
                
                // First check for direct hidden input with existing media path
                const existingMediaInput = document.getElementById('media_path');
                if (existingMediaInput && existingMediaInput.value) {
                    mediaHandled = true;
                    const mediaPath = existingMediaInput.value;
                    console.log("Found existing media path:", mediaPath);
                    
                    // Check if the path already includes the servers/ prefix
                    if (mediaPath.startsWith('servers/')) {
                        previewData.media = mediaPath;
                    } else {
                        previewData.media = `${mediaPath}`;
                    }
                    
                    console.log("Setting preview media to:", previewData.media);
                    sendToIframes(previewData, iframes);
                }
                // Next check for video preview element 
                else {
                    const videoPreview = document.getElementById('video-preview');
                    if (videoPreview && videoPreview.style.display !== 'none' && videoPreview.src) {
                        mediaHandled = true;
                        console.log("Found existing video:", videoPreview.src);
                        previewData.media = videoPreview.src;
                        sendToIframes(previewData, iframes);
                    }
                    // Then check for image preview element
                    else {
                        const imagePreview = document.getElementById('image-preview');
                        if (imagePreview && imagePreview.style.display !== 'none' && imagePreview.src) {
                            mediaHandled = true;
                            console.log("Found existing image:", imagePreview.src);
                            previewData.media = imagePreview.src;
                            sendToIframes(previewData, iframes);
                        }
                    }
                }
            }
            
            // If no media was handled yet, send preview without media
            if (!mediaHandled) {
                console.log("No media found, sending preview without media");
                sendToIframes(previewData, iframes);
            }
        }
    }

    // Update the openPreviewModal function
    function openPreviewModal() {
        var modal = document.getElementById('previewModal');
        modal.style.display = 'flex';

        // Give a brief moment for the modal to display
        setTimeout(() => {
            // Initial call to load the container
        updatePreviewContent();
            
        // Display the preview content in the modal
        document.getElementById('previewContent').innerHTML = getPreviewContent();
        
            // Wait for iframes to be created and loaded before sending data
            setTimeout(sendPreviewDataToIframe, 1000);
        }, 100);
        
        // Close when clicking outside
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
        const mediaInput = document.getElementById('media');

        let content = '';
        let mediaContent = '';

        // Check if quillEditors is defined before trying to access it
        const hasQuillEditors = typeof window.quillEditors !== 'undefined';

        // Generate media preview HTML if media is selected
        if (mediaInput && mediaInput.files && mediaInput.files[0]) {
            const file = mediaInput.files[0];
            const fileType = file.type;
            const fileURL = URL.createObjectURL(file);
            
            if (fileType.startsWith('video/')) {
                mediaContent = `<video muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 5px;">
                        <source src="${fileURL}" type="${fileType}">
                        Your browser does not support the video tag.
                    </video>`;
            } else if (fileType.startsWith('image/')) {
                mediaContent = `<img src="${fileURL}" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 5px;">`;
            }
        }
        // Only show existing media if no new media is selected
        <?php if ($isEditMode && isset($contentData['media_path'])): ?>
        else {
            const mediaPath = '<?php echo $contentData['media_path']; ?>';
            if (mediaPath) {
                if (mediaPath.match(/\.(jpg|jpeg|png|gif)$/i)) {
                    // Check if the path already includes servers/ prefix
                    const imagePath = mediaPath.startsWith('servers/') 
                        ? mediaPath 
                        : `${mediaPath}`;
                    
                    mediaContent = `<img src="${imagePath}" 
                            style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 5px;">`;
                } else if (mediaPath.match(/\.(mp4|webm|ogg)$/i)) {
                    // Check if the path already includes servers/ prefix
                    const videoPath = mediaPath.startsWith('servers/') 
                        ? mediaPath 
                        : `${mediaPath}`;
                    
                    mediaContent = `<video muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 5px;">
                            <source src="${videoPath}">
                            Your browser does not support the video tag.
                        </video>`;
                }
            }
        }
        <?php endif; ?>

        // Determine the content based on the selected type
        switch (selectedType) {
            case 'announcement':
                content = quillEditors.announcementBodyQuill.root.innerHTML;
                break;
            case 'event':
                content = quillEditors.eventBodyQuill.root.innerHTML;
                break;
            case 'news':
                content = quillEditors.newsBodyQuill.root.innerHTML;
                break;
            case 'promaterial':
                content = mediaContent; 
                break;
            case 'peo':
                content = `
                    <div class="peo-title">${quillEditors.peoTitleQuill.root.innerHTML}</div>
                    <div class="peo-description">${quillEditors.peoDescriptionQuill.root.innerHTML}</div>
                    <div class="peo-subdescription">${quillEditors.peoSubdescriptionQuill.root.innerHTML}</div>
                `;
                break;
            case 'so':
                content = `
                    <div class="so-title">${quillEditors.soTitleQuill.root.innerHTML}</div>
                    <div class="so-description">${quillEditors.soDescriptionQuill.root.innerHTML}</div>
                    <div class="so-subdescription">${quillEditors.soSubdescriptionQuill.root.innerHTML}</div>
                `;
                break;
            default:
                // Handle custom feature types with multiple Quill editors
                if (hasQuillEditors) {
                    // Look for any Quill editor that isn't a standard type
                    const quillEditorKeys = Object.keys(window.quillEditors);
                    
                    // Define standard editor names to exclude
                    const standardEditors = ['announcementBodyQuill', 'eventBodyQuill', 'newsBodyQuill', 
                                             'peoTitleQuill', 'peoDescriptionQuill', 'peoSubdescriptionQuill', 
                                             'soTitleQuill', 'soDescriptionQuill', 'soSubdescriptionQuill', 
                                             'facilityBodyQuill'];
                    
                    // Find all custom Quill editors
                    const customEditors = quillEditorKeys.filter(key => 
                        key.endsWith('Quill') && !standardEditors.includes(key)
                    );
                    
                    if (customEditors.length > 0) {
                        // We found custom editors - use them all
                        console.log(`Found ${customEditors.length} custom editors:`, customEditors);
                        
                        // Build content from all editors with proper formatting
                        content = '';
                        customEditors.forEach((editorKey, index) => {
                            const editorContent = window.quillEditors[editorKey].root.innerHTML;
                            content += `
                                ${editorContent}
                            `;
                        });
                    } else {
                        // Check for media content for custom features
                        if (mediaContent) {
                            content = '';
                        } else {
                            content = '';
                        }
                    }
                } else {
                    // Check for media content before showing no content message
                    if (mediaContent) {
                        content = '';
                    } else {
                        content = '';
                    }
                }
                break;
        }

        // For types other than promaterial, append media content after the main content if it exists
        if (selectedType !== 'promaterial' && mediaContent) {
            // Check if this is a media-only feature
            const hasOnlyMedia = (!content || content === '<p><br></p>' || content === '');
            if (hasOnlyMedia) {
                // For media-only features, wrap the media in a container with 100% height
                content = `
                    <div style="height: 100%; max-height: 100%; display: flex; align-items: center; justify-content: center;">
                        ${mediaContent}
                    </div>
                `;
            } else {
                content += mediaContent;
            }
        }

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
                    <div class="carousel-item ${index === 0 ? 'active' : ''}" style="display: none;" data-tv-id="${matchingContainer.tv_id}">
                        <div class="tv-frame-parent" style="width: auto; height: 100%; background: none; cursor: default;">
                            ${matchingContainer.visible !== '1' ? `
                                <div style="display: flex; position: absolute; justify-content: center; align-items: center; height: auto; width: auto; text-align: center; padding: 20px; background: #0000007a; z-index: 1000; border-radius: 15px">
                                    <h3 style="background: <?php echo $dark_theme_color_20_percent ?>; padding: 10px; color: white; padding: 10px; border-radius: 10px; border: white solid 2px">${selectedType} container not visible</h3>
                                </div>
                                <div class="tv-frame" id="tvFrame-${matchingContainer.tv_id}" style="position: relative; display: flex; flex-direction: column; align-items: center;">
                                    <iframe id='tv-iframe' frameborder='0' src='preview/tv_preview.php?tvId=${matchingContainer.tv_id}&isIframe=true' class='tv-screen' style='height: ${matchingContainer.height_px}px; width: ${matchingContainer.width_px}px; border: none;'></iframe>
                                </div>
                            ` : `
                                <div class="tv-frame" id="tvFrame-${matchingContainer.tv_id}" style="position: relative; display: flex; flex-direction: column; align-items: center;">
                                    <iframe id='tv-iframe' frameborder='0' src='preview/tv_preview.php?tvId=${matchingContainer.tv_id}&isIframe=true' class='tv-screen' style='height: ${matchingContainer.height_px}px; width: ${matchingContainer.width_px}px; border: none;'></iframe>
                                </div>
                            `}
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
                // setTimeout(() => {
                //     updateTvName();
                //     fitFrameToParent();
                // }, 100);

                setTimeout(() => {
                    updateTvName();
                    fitFrameToParent();

                    // Get the current content type
                    const selectedType = document.querySelector('[name="type"]').value;
                    
                    // After switching TV, check if this is an org chart and reinitialize
                    if (selectedType === 'orgchart') {
                        const activeItem = items[currentIndex];
                        const tvId = activeItem.getAttribute('data-tv-id');
                        const iframe = document.querySelector(`#tvFrame-${tvId} iframe`);
                    
                        if (iframe && iframe.contentWindow) {
                            const previewData = {
                                action: 'updatePreview',
                                type: 'orgchart',
                                content: {
                                    members: orgChartData,
                                    reinitialize: true // Flag to indicate this is a reinitialization
                                },
                                displayTime: document.querySelector('[name="display_time"]').value
                            };
                            iframe.contentWindow.postMessage(previewData, '*');
                        }
                    }
                }, 100);
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
                
                const frameRect = tvFrame.getBoundingClientRect();
                
                const scaleX = parentRect.width / frameRect.width;
                const scaleY = parentRect.height / frameRect.height;
                const scale = Math.min(scaleX, scaleY, 1); // Don't scale up if already smaller

                // Apply transformations
                tvFrame.style.transform = `scale(${scale})`;
                tvFrame.style.transformOrigin = 'center top';
            }

            // Fit frame on load
            fitFrameToParent();

            // Refit on window resize
            // window.addEventListener('resize', fitFrameToParent);
        } else {
            previewContainer.innerHTML = '<p>No container found for the selected TVs.</p>'; // Fallback message
        }
        setTimeout(sendPreviewDataToIframe, 1000);
    }
    
    // Also update existing event listeners to handle live preview updates
    document.addEventListener("DOMContentLoaded", function() {
        // live update for QuillEditor content
        if (typeof quillEditors !== 'undefined') {
            // For each type of editor, add a text-change event
            if (quillEditors.announcementBodyQuill) {
                quillEditors.announcementBodyQuill.on('text-change', function() {
                    // If preview modal is open, update the preview
                    if (document.getElementById('previewModal').style.display === 'flex') {
                        sendPreviewDataToIframe();
                    }
                });
            }
            
            // Do the same for other editor types...
            // [Add similar handlers for other editor types]
        }
        
        // live update for media uploads
        const mediaInput = document.getElementById('media');
        if (mediaInput) {
            mediaInput.addEventListener('change', function() {
                // If preview modal is open, update the preview
                if (document.getElementById('previewModal').style.display === 'flex') {
                    sendPreviewDataToIframe();
                }
            });
        }
        
        // live update for display time changes
        const displayTimeInput = document.querySelector('[name="display_time"]');
        if (displayTimeInput) {
            displayTimeInput.addEventListener('input', function() {
                // If preview modal is open, update the preview
                if (document.getElementById('previewModal').style.display === 'flex') {
                    sendPreviewDataToIframe();
                }
            });
        }
        
        // Update the TV selection change handler
        const tvCheckboxes = document.querySelectorAll('[name="tv_id[]"]');
        tvCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updatePreviewContent();
                // After the iframe has been updated, send new preview data
                setTimeout(sendPreviewDataToIframe, 1000);
            });
        });
    });

    // Update the function for better media handling
    function sendToIframes(previewData, iframes) {
        // If media is a data URL, log its type and size
        // if (previewData.media && previewData.media.startsWith('data:')) {
        //     const mediaType = previewData.media.split(';')[0].split(':')[1];
        //     const mediaSize = Math.round(previewData.media.length / 1024);
        // }

        // Handle media logging and validation
        if (previewData.media) {
            // Check if media is an array (for facility type)
            if (Array.isArray(previewData.media)) {
                console.log("Sending array of media files:", previewData.media.length);
            } 
            // Check if media is a data URL string
            else if (typeof previewData.media === 'string' && previewData.media.startsWith('data:')) {
                const mediaType = previewData.media.split(';')[0].split(':')[1];
                const mediaSize = Math.round(previewData.media.length / 1024);
                console.log("Sending media of type:", mediaType, "size:", mediaSize, "KB");
            }
        }
        
        iframes.forEach((iframe, index) => {            
            try {
                // First, try the simple postMessage approach
                if (iframe.contentWindow) {
                    iframe.contentWindow.postMessage(previewData, '*');
                }
                
                // Try to access iframe document for more direct approach
                try {
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    if (iframeDoc) {                        
                        // Create a dynamic element to signal the iframe
                        const signalElement = iframeDoc.createElement('div');
                        signalElement.id = `preview-signal-${Date.now()}`;
                        signalElement.style.display = 'none';
                        signalElement.dataset.type = previewData.type;
                        signalElement.dataset.hasMedia = !!previewData.media;
                        
                        // the element to trigger any observers
                        iframeDoc.body.appendChild(signalElement);
                        
                        // Remove after a moment
                        setTimeout(() => {
                            try {
                                signalElement.remove();
                            } catch(e) {}
                        }, 1000);
                    }
                } catch (e) {
                    console.error(`Could not access iframe document:`, e);
                }
            } catch (e) {
                console.error(`Error sending to iframe ${index + 1}:`, e);
            }
        });
    }
</script>