function getQuillEditorContent(contentType) {
    switch (contentType) {
        case 'announcement':
            return announcementBodyQuill.root.innerHTML;
        case 'event':
            return eventBodyQuill.root.innerHTML;
        case 'news':
            return newsBodyQuill.root.innerHTML;
        case 'peo':
            return {
                title: peoTitleQuill.root.innerHTML,
                description: peoDescriptionQuill.root.innerHTML,
                subdescription: peoSubdescriptionQuill.root.innerHTML
            };
        case 'so':
            return {
                title: soTitleQuill.root.innerHTML,
                description: soDescriptionQuill.root.innerHTML,
                subdescription: soSubdescriptionQuill.root.innerHTML
            };
        default:
            return '';
    }
}

function submitFormViaWebSocket() {
    const contentType = document.querySelector('[name="type"]').value;
    const form = document.getElementById(`${contentType}Form`);

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
    } else {
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
        action: 'post_content',
        tv_ids: formData.getAll('tv_id[]') // Collect all tv_id[] values
    };

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
            if (contentType === 'peo') {
                document.getElementById('successMessage').textContent = "Program Educational Objective was successfully processed!";                        
            } else if (contentType === 'so') {
                document.getElementById('successMessage').textContent = "Student Outcome was successfully processed!";                        
            } else {
                document.getElementById('successMessage').textContent = capitalizeFirstLetter(contentType) + " was successfully processed!";                        
            }
            document.getElementById('successMessageModal').style.display = 'flex';
            isFormDirty = false;
        } 
        // else {
        //     // Display an error modal
        //     document.getElementById('errorText').textContent = "Error processing " + contentType + ". Try again later";
        //     document.getElementById('errorModal').style.display = 'flex';
        // }
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
    // Get the form by content type
    const contentType = document.querySelector('[name="type"]').value;
    const form = document.getElementById(`${contentType}Form`);

    // Reset all the form fields to null
    form.reset();

    // Navigate to the home page
    location.href = 'user_home.php?pageid=UserHome&userId=<?php echo $user_id; ?>&fullName=<?php echo $full_name; ?>';
}