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
    const contentType = document.querySelector('[name="type"]').value;
    const form = document.getElementById(`${contentType}Form`);

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
        action: 'post_content',
        tv_ids: Array.from(formData.getAll('tv_id[]'))
    };

    // Check for file input
    const fileInputs = form.querySelectorAll('input[type="file"]');
    let filePromises = [];

    fileInputs.forEach(fileInput => {
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const promise = new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const base64Data = e.target.result;
                    formData.set(fileInput.name, base64Data);
                    resolve();
                };
                reader.readAsDataURL(file);
            });
            filePromises.push(promise);
        }
    });

    Promise.all(filePromises).then(() => {
        formData.forEach((value, key) => {
            data[key] = value;
        });

        console.log('Form Data:', data);
        ws.send(JSON.stringify(data));
    });

    // Listen for messages from the WebSocket server
    ws.onmessage = function(event) {
        const message = JSON.parse(event.data);
        if (message.success) {
            document.getElementById('successMessageVersion2').textContent = message.message || `${capitalizeFirstLetter(contentType)} was successfully processed!`;
            document.getElementById('successMessageModalVersion2').style.display = 'flex';
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

