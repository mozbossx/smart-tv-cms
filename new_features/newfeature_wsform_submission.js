function getNewFeatureQuillEditorContent(form) {
    const content = {};
    const quillContainers = form.querySelectorAll('.quill-editor-container-newfeature');

    quillContainers.forEach(container => {
        const editorId = container.querySelector('div[id]').id;
        const editorName = editorId;
        const quillInstance = Quill.find(document.getElementById(editorId));
        if (quillInstance) {
            content[editorName] = quillInstance.root.innerHTML;
        }
    });

    return content;
}

function submitFormViaWebSocket() {
    const loadingModal = document.getElementById('loadingModal');
    loadingModal.style.display = 'flex';

    const contentType = document.querySelector('[name="type"]').value;
    const contentTypeWithNoUnderscores = contentType
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
    const form = document.getElementById(`${contentType}Form`);
    const isEditMode = form.querySelector('input[name="action_value"]')?.value === 'edit';

    // Get content from all Quill editors
    const quillEditorContent = getNewFeatureQuillEditorContent(form);

    // Add hidden inputs for Quill editor content
    Object.keys(quillEditorContent).forEach(key => {
        let hiddenInput = form.querySelector(`input[name="${key}"]`);
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', key);
            form.appendChild(hiddenInput);
        }
        hiddenInput.value = quillEditorContent[key];
    });

    const formData = new FormData(form);
    const data = { 
        action: isEditMode ? 'update_content' : 'post_content',
        tv_ids: Array.from(formData.getAll('tv_id[]'))
    };

    // If in edit mode, add the content ID
    if (isEditMode) {
        const contentId = new URLSearchParams(window.location.search).get(`${contentType}_id`);
        if (contentId) {
            data[`${contentType}_id`] = contentId;
        }
    }

    // Check for file input
    // const mediaInput = document.getElementById('media');
    // if (mediaInput && mediaInput.files.length > 0) {
    //     const mediaFile = mediaInput.files[0];
    //     const reader = new FileReader();

    //     reader.onload = function (e) {
    //         const base64Data = e.target.result;
    //         formData.set('media', base64Data);

    //         formData.forEach((value, key) => {
    //             data[key] = value;
    //         });

    //         sendDataToWebSocket(data, loadingModal, contentType, isEditMode);
    //     };

    //     reader.readAsDataURL(mediaFile);
    // } 


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

        sendDataToWebSocket(data, loadingModal, contentType, isEditMode);
    } else {
        formData.forEach((value, key) => {
            data[key] = value;
        });

        console.log('Form Data:', data);
        sendDataToWebSocket(data, loadingModal, contentType, isEditMode);
    }

    // Listen for messages from the WebSocket server
    // ws.onmessage = function(event) {
    //     const message = JSON.parse(event.data);
    //     if (message.success) {
    //         document.getElementById('successMessageVersion2').textContent = message.message || 
    //             `${contentTypeWithNoUnderscores} was successfully ${isEditMode ? 'updated' : 'processed'}!`;
    //         document.getElementById('successMessageModalVersion2').style.display = 'flex';
    //         isFormDirty = false;
    //     } 
    // };
}

function sendDataToWebSocket(data, loadingModal, contentTypeWithNoUnderscores, isEditMode) {
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
                    sendDataToWebSocket(data, loadingModal, contentTypeWithNoUnderscores, isEditMode);
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
            if (message.success === true) {
                loadingModal.style.display = 'none';

                const contentType = document.querySelector('[name="type"]').value;

                // Send email notification for new posts from Student, Faculty, or Staff
                if (userType === 'Student' || userType === 'Faculty' || userType === 'Staff') {
                    // Send email notification
                    const emailData = new FormData();
                    emailData.append('content_type', contentType);
                    emailData.append('author_name', full_name);
                    emailData.append('author_type', userType);
                    emailData.append('author_department', userDepartment);

                    fetch('send_pending_content_email.php', {
                        method: 'POST',
                        body: emailData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Email notification status:', data);
                    })
                    .catch(error => {
                        console.error('Error sending email notification:', error);
                    });
                }
                
                if (userType === 'Student' || userType === 'Faculty' || userType === 'Staff') {
                    document.getElementById('successMessageVersion2').textContent = isEditMode 
                        ? `Your post has been updated and will be reviewed by an admin.`
                        : "Your post will still be in pending mode until an admin approves it.";
                } else {
                    document.getElementById('successMessageVersion2').textContent = message.message || 
                    `${contentTypeWithNoUnderscores} was successfully ${isEditMode ? 'updated' : 'processed'}!`;
                }
                document.getElementById('successMessageModalVersion2').style.display = 'flex';
                isFormDirty = false;
            } else if (message.success === false) {
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

    // Reset all the form fields
    form.reset();

    // Reset Quill editors
    const quillEditors = form.querySelectorAll('[id$="Quill"]');
    quillEditors.forEach(editor => {
        editor.querySelector('.ql-editor').innerHTML = '';
    });

    // Navigate to the home page
    location.href = 'user_home.php?pageid=UserHome&userId=<?php echo $user_id; ?>&fullName=<?php echo $full_name; ?>';
}

