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
    const mediaInput = document.getElementById('media');
    if (mediaInput && mediaInput.files.length > 0) {
        const mediaFile = mediaInput.files[0];
        const reader = new FileReader();

        reader.onload = function (e) {
            const base64Data = e.target.result;
            formData.set('media', base64Data);

            formData.forEach((value, key) => {
                data[key] = value;
            });

            sendDataToWebSocket(data, loadingModal, contentType, isEditMode);
        };

        reader.readAsDataURL(mediaFile);
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

    ws.send(JSON.stringify(data));

    // Listen for messages from the WebSocket server
    ws.onmessage = function(event) {
        const message = JSON.parse(event.data);
        if (message.success === true) {
            loadingModal.style.display = 'none';
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
    };

    ws.onclose = function(event) {
        loadingModal.style.display = 'none';
        document.getElementById('errorText').textContent = 'Connection error occurred. Please refresh the page and try again.';
        document.getElementById('errorModal').style.display = 'flex';
    };

    // Add error handling for WebSocket connection
    ws.onerror = function(error) {
        loadingModal.style.display = 'none';
        document.getElementById('errorText').textContent = 'Connection error occurred. Please refresh the page and try again.';
        document.getElementById('errorModal').style.display = 'flex';
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

