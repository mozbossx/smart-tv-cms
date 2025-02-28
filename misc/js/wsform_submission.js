function getQuillEditorContent(contentType) {
    switch (contentType) {
        case 'announcement':
            return quillEditors.announcementBodyQuill.root.innerHTML;
        case 'event':
            return quillEditors.eventBodyQuill.root.innerHTML;
        case 'news':
            return quillEditors.newsBodyQuill.root.innerHTML;
        case 'peo':
            return {
                title: quillEditors.peoTitleQuill.root.innerHTML,
                description: quillEditors.peoDescriptionQuill.root.innerHTML,
                subdescription: quillEditors.peoSubdescriptionQuill.root.innerHTML
            };
        case 'so':
            return {
                title: quillEditors.soTitleQuill.root.innerHTML,
                description: quillEditors.soDescriptionQuill.root.innerHTML,
                subdescription: quillEditors.soSubdescriptionQuill.root.innerHTML
            };
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
    const isEditMode = form.querySelector('input[name="action_value"]')?.value === 'edit';

    // Use the existing hidden input element to store the editor content
    const quillEditorContent = getQuillEditorContent(contentType);

    if (contentType === 'peo') {
        let titleInput = form.querySelector('input[name="peo_title"]');
        if (!titleInput) {
            titleInput = document.createElement('input');
            titleInput.setAttribute('type', 'hidden');
            titleInput.setAttribute('name', 'peo_title');
            form.appendChild(titleInput);
        }
        titleInput.setAttribute('value', quillEditorContent.title);

        let descriptionInput = form.querySelector('input[name="peo_description"]');
        if (!descriptionInput) {
            descriptionInput = document.createElement('input');
            descriptionInput.setAttribute('type', 'hidden');
            descriptionInput.setAttribute('name', 'peo_description');
            form.appendChild(descriptionInput);
        }
        descriptionInput.setAttribute('value', quillEditorContent.description);

        let subdescriptionInput = form.querySelector('input[name="peo_subdescription"]');
        if (!subdescriptionInput) {
            subdescriptionInput = document.createElement('input');
            subdescriptionInput.setAttribute('type', 'hidden');
            subdescriptionInput.setAttribute('name', 'peo_subdescription');
            form.appendChild(subdescriptionInput);
        }
        subdescriptionInput.setAttribute('value', quillEditorContent.subdescription);
    } else if (contentType === 'so') {
        let titleInput = form.querySelector('input[name="so_title"]');
        if (!titleInput) {
            titleInput = document.createElement('input');
            titleInput.setAttribute('type', 'hidden');
            titleInput.setAttribute('name', 'so_title');
            form.appendChild(titleInput);
        }
        titleInput.setAttribute('value', quillEditorContent.title);

        let descriptionInput = form.querySelector('input[name="so_description"]');
        if (!descriptionInput) {
            descriptionInput = document.createElement('input');
            descriptionInput.setAttribute('type', 'hidden');
            descriptionInput.setAttribute('name', 'so_description');
            form.appendChild(descriptionInput);
        }
        descriptionInput.setAttribute('value', quillEditorContent.description);

        let subdescriptionInput = form.querySelector('input[name="so_subdescription"]');
        if (!subdescriptionInput) {
            subdescriptionInput = document.createElement('input');
            subdescriptionInput.setAttribute('type', 'hidden');
            subdescriptionInput.setAttribute('name', 'so_subdescription');
            form.appendChild(subdescriptionInput);
        }
        subdescriptionInput.setAttribute('value', quillEditorContent.subdescription);
    } else if (contentType !== 'promaterial') {
        let quillHiddenInput = form.querySelector(`input[name="${contentType}_body"]`);
        if (!quillHiddenInput) {
            quillHiddenInput = document.createElement('input');
            quillHiddenInput.setAttribute('type', 'hidden');
            quillHiddenInput.setAttribute('name', `${contentType}_body`);
            form.appendChild(quillHiddenInput);
        }
        quillHiddenInput.setAttribute('value', quillEditorContent);
    }

    const formData = new FormData(form);
    const data = { 
        // Set action based on edit mode
        action: isEditMode ? 'update_content' : 'post_content',
        tv_ids: formData.getAll('tv_id[]')
    };

    // If in edit mode, add the content ID
    if (isEditMode) {
        // Add hidden input for content ID if it doesn't exist
        let contentIdInput = form.querySelector(`input[name="${contentType}_id"]`);
        if (!contentIdInput) {
            contentIdInput = document.createElement('input');
            contentIdInput.setAttribute('type', 'hidden');
            contentIdInput.setAttribute('name', `${contentType}_id`);
            contentIdInput.value = new URLSearchParams(window.location.search).get(`${contentType}_id`);
            form.appendChild(contentIdInput);
        }
        data[`${contentType}_id`] = contentIdInput.value;
    }

    // Check for media input with pre-uploaded file
    const mediaInput = document.getElementById('media');
    const preUploadedFile = window.getPreUploadedFile ? window.getPreUploadedFile() : window.preUploadedFile;
    console.log('Debug - Checking media upload:', {
        hasMediaInput: !!mediaInput,
        hasFiles: mediaInput?.files?.length > 0,
        preUploadedFile: preUploadedFile
    });

    if (mediaInput && mediaInput.files.length > 0 && preUploadedFile) {
        data.preUploadedFile = preUploadedFile;
        data.originalFileName = mediaInput.files[0].name;
        data.mediaType = mediaInput.files[0].type;

        console.log('Debug - Adding pre-uploaded file to data:', preUploadedFile);

        // Add all other form data
        formData.forEach((value, key) => {
            data[key] = value;
        });

        console.log(data);

        sendDataToWebSocket(data, loadingModal);
    } else {
        formData.forEach((value, key) => {
            data[key] = value;
        });
        console.log(data);

        sendDataToWebSocket(data, loadingModal);
    }
}

function sendDataToWebSocket(data, loadingModal) {
    // Update loading message for data processing
    document.getElementById('loadingMessage').textContent = 'Processing your content...';
    
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

    // Listen for messages from the WebSocket server
    ws.onmessage = function(event) {
        const message = JSON.parse(event.data);
        if (message.action === 'post_content' || message.action === 'update_content') {
            if (message.success == true) {
                // Hide loading modal
                loadingModal.style.display = 'none';

                const contentType = document.querySelector('[name="type"]').value;
                const form = document.getElementById(`${contentType}Form`);
                const isEditMode = form.querySelector('input[name="action_value"]')?.value === 'edit';
                const action = isEditMode ? 'updated' : 'processed';
                
                if (contentType === 'peo') {
                    document.getElementById('successMessage').textContent = `Program Educational Objective was successfully ${action}!`;                        
                } else if (contentType === 'so') {
                    document.getElementById('successMessage').textContent = `Student Outcome was successfully ${action}!`;                        
                } else if (userType === 'Student' || userType === 'Faculty' || userType === 'Staff') {
                    document.getElementById('successMessage').textContent = isEditMode 
                        ? `Your post has been updated and will be reviewed by an admin.`
                        : "Your post will still be in pending mode until an admin approves it.";
                } else {
                    document.getElementById('successMessage').textContent = `${capitalizeFirstLetter(contentType)} was successfully ${action}!`;                        
                }
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
        document.getElementById('errorText').textContent = 'Connection error occurred. Please refresh the page and try again.';
        document.getElementById('errorModal').style.display = 'flex';
        tryReconnect();
    };

    // Add error handling for WebSocket connection
    ws.onerror = function(error) {
        loadingModal.style.display = 'none';
        document.getElementById('errorText').textContent = 'Connection error occurred. Please refresh the page and try again.';
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
    const isEditMode = form.querySelector('input[name="action"]')?.value === 'edit';

    if (isEditMode) {
        // If in edit mode, go back to the TV contents page
        const tvId = form.querySelector('input[name="tv_id"]').value;
        location.href = `tv_contents.php?tvId=${tvId}&isIframe=true`;
    } else {
        // If in create mode, go to home page
        form.reset();
        location.href = `user_home.php?pageid=UserHome&userId=${user_id}&fullName=${full_name}`;
    }
}