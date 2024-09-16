<div id="saveDraftModal" class="modal">
    <div class="modal-content">
        <div class="green-bar-vertical">
            <h1 style="color: #264B2B; font-size: 50px"><i class="fa fa-file" aria-hidden="true"></i></h1>
            <p>Proceed to save as draft?</p>
            <br>
            <div style="align-items: right; text-align: right; right: 0">
                <button type="button" id="cancelSaveDraftButton" class="green-button" style="background: none; color: #264B2B; border: 1px solid #264B2B">Cancel</button>
                <button type="button" id="confirmSaveDraftButton" class="green-button" style="margin-right: 0">Yes, Save Draft</button>
            </div>
        </div>
    </div>
</div>

<script>
    const ws = new WebSocket('ws://192.168.1.13:8081?user_id=<?php echo urlencode($user_id)?>&full_name=<?php echo urlencode($full_name); ?>&user_type=<?php echo urlencode($user_type); ?>&department=<?php echo urlencode($department); ?>&email=<?php echo urlencode($email)?>');

    let isFormDirty = false;

    // Mark the form as dirty when any input changes
    document.querySelectorAll('.content-form input, .content-form textarea, .content-form select').forEach(input => {
        input.addEventListener('input', () => {
            isFormDirty = true;
            document.getElementById('saveDraftButton').style.display = "block";
        });
    });

    if (isFormDirty === true) {
        // Show confirmation dialog on page unload
        window.addEventListener('beforeunload', (event) => {
            const message = "There are unsaved changes. Proceed to go to a different page?";
            event.returnValue = message; // For most browsers
            return message; // For some older browsers
        });
    }

    function getQuillEditorContent(contentType) {
        switch (contentType) {
            case 'announcement':
                return announcementBodyQuill.root.innerHTML;
            case 'event':
                return eventBodyQuill.root.innerHTML;
            case 'news':
                return newsBodyQuill.root.innerHTML;
            default:
                return '';
        }
    }

    function saveDraft() {
        const contentType = document.querySelector('[name="type"]').value;
        const form = document.getElementById(`${contentType}Form`);
        console.log("saveDraft() called");

        // Use the existing hidden input element to store the editor content
        const quillEditorContent = getQuillEditorContent(contentType);

        // Create a hidden input element to store the editor content
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
            action: 'save_draft',
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
                document.getElementById('successMessage').textContent = capitalizeFirstLetter(contentType) + " was drafted!";                        
                document.getElementById('successMessageModal').style.display = 'flex';
            } else {
                document.getElementById('errorText').textContent = "Error processing " + contentType + ". Try again later";
                document.getElementById('errorModal').style.display = 'flex';
            }
        };

        ws.onerror = function (event) {
            console.error('WebSocket error:', event);
            document.getElementById('errorText').textContent = "Error processing " + contentType + ". Please try again later.";
            document.getElementById('errorModal').style.display = 'flex';
        };
    }

    document.getElementById('saveDraftButton').addEventListener('click', (event) => {
        isFormDirty = true;
        if (isFormDirty) {
            event.preventDefault(); // Prevent default action
            const saveDraftModal = document.getElementById('saveDraftModal');
            
            // Show the modal
            saveDraftModal.style.display = "flex";

            // Add event listeners only once using one-time event handlers
            const confirmSaveDraftButton = document.getElementById('confirmSaveDraftButton');
            const cancelSaveDraftButton = document.getElementById('cancelSaveDraftButton');

            confirmSaveDraftButton.onclick = function() {
                saveDraft();
                isFormDirty = false;
                saveDraftModal.style.display = "none"; // Close the modal after saving
            };

            cancelSaveDraftButton.onclick = function() {
                saveDraftModal.style.display = "none";
            };

        } else {
            saveDraft(); // Directly save if no unsaved changes
        }
    });
</script>