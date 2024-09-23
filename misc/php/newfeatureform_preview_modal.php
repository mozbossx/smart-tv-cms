<div id="previewModal" class="modal">
    <div class="modal-content-preview">
        <div class="flex-preview-content">
            <div style="display: flex, flex-direction: column; flex: 2; height: auto; overflow: auto">
                <div id="previewContainer" style="height: 60vh; overflow: auto; padding-right: 15px;"></div>
            </div>
            <!-- The container consists of child_background_color, child_font_style, child_font_color-->
            <div class="preview-content" id="previewContent"></div>
        </div>
        <!-- Operation buttons inside the Preview modal -->
        <div class="flex-button-modal">
            <button type="button" class="green-button" id="closeButton" style="background: none; border: 1px solid #264B2B; color: #264B2B; margin-top: 0; margin-right: 5px" onclick="closePreviewModal()">Cancel</button>
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

    function validateAndOpenNewFeaturePreviewModal() {
        if (validateForm()) {
            openPreviewModal();
        }
    }

    function validateForm() {
        const requiredFields = [
            'name_of_feature',
            'number_of_inputs',
            'selectedIcon',
            'content_has_expiration_date'
        ];

        for (const field of requiredFields) {
            if (!document.getElementById(field).value) {
                errorModalMessage(`Please fill in the ${field.replace(/_/g, ' ')}`);
                return false;
            }
        }

        const numberOfInputs = parseInt(document.getElementById('number_of_inputs').value);
        for (let i = 1; i <= numberOfInputs; i++) {
            const inputFields = [
                `name_of_input_${i}`,
                `input_type_${i}`,
                `required_field_${i}`
            ];
            for (const field of inputFields) {
                if (!document.getElementById(field).value) {
                    errorModalMessage(`Please fill in all fields for Input #${i}`);
                    return false;
                }
            }
        }

        const userTypes = document.querySelectorAll('input[name="user_type[]"]:checked');
        if (userTypes.length === 0) {
            errorModalMessage('Please select at least one user type');
            return false;
        }

        return true;
    }

    function getPreviewContainer() {
        const formData = new FormData(document.getElementById('newFeatureForm'));
        let previewContainer = `
            <div class="main-form">
                <h2 style="text-align: center"><i class="fa ${formData.get('selectedIcon')}"></i> ${formData.get('name_of_feature')} Form</h2>
        `;

        const numberOfInputs = parseInt(formData.get('number_of_inputs'));
        for (let i = 1; i <= numberOfInputs; i++) {
            const inputName = formData.get(`name_of_input_${i}`);
            const inputType = formData.get(`input_type_${i}`);
            const isRequired = formData.get(`required_field_${i}`) === 'yes';

            if (inputType === 'text') {
                previewContainer += `
                    <div class="floating-label-container">
                        <div id="quillEditorContainer_${i}" class="quill-editor-container-newfeature">
                            <label for="quillEditorContainer_${i}" style="position: absolute; z-index: 10; top: 50px; left: 16px; color: #264B2B; font-size: 12px; font-weight: bold">${inputName}</label>
                            <div id="preview_input_${i}" style="height: 150px;"></div>
                        </div>
                        <input type="hidden" name="preview_input_${i}" id="preview_input_hidden_${i}">
                    </div>
                `;
            } else if (inputType === 'image') {
                previewContainer += `
                    <input type="file" id="preview_input_${i}" name="preview_input_${i}" accept="image/*"${isRequired ? ' required' : ''}>
                `;
            }

        }

        previewContainer += `<div class="form-row">`;
        // Check if content has expiration date
        if (formData.get('content_has_expiration_date') === 'yes') {
            previewContainer += `
                <div id="expiration_date_time" class="rounded-container-column" style="flex: 1">
                    <p class="input-container-label">Expiration Date & Time</p>
                    <div class="left-flex">
                        <input type="date" id="expiration_date" name="expiration_date" class="input-date">
                    </div>
                    <div class="right-flex">
                        <input type="time" id="expiration_time" name="expiration_time" class="input-time">
                    </div>
                </div>
            `;
        }

        previewContainer += `
                <div class="form-column" style="flex: 1">
                    <div class="floating-label-container" style="flex: 1">
                        <select id="display_time" name="display_time" class="floating-label-input" required style="background: #FFFF; padding-left: 8px">
                            <option value="">Select Display Time</option>
                            <option value="10">10 seconds</option>
                            <option value="11">11 seconds</option>
                            <option value="12">12 seconds</option>
                            <option value="13">13 seconds</option>
                            <option value="14">14 seconds</option>
                            <option value="15">15 seconds</option>
                            <option value="16">16 seconds</option>
                            <option value="17">17 seconds</option>
                            <option value="18">18 seconds</option>
                            <option value="19">19 seconds</option>
                            <option value="20">20 seconds</option>
                            <option value="21">21 seconds</option>
                            <option value="22">22 seconds</option>
                            <option value="23">23 seconds</option>
                            <option value="24">24 seconds</option>
                            <option value="25">25 seconds</option>
                            <option value="26">26 seconds</option>
                            <option value="27">27 seconds</option>
                            <option value="28">28 seconds</option>
                            <option value="29">29 seconds</option>
                            <option value="30">30 seconds</option>
                        </select>
                        <label for="display_time" class="floating-label">Display Time (seconds)</label>
                    </div>
                    <div class="floating-label-container" style="flex: 1">
                        <button type="button" id="tvModalButton" class="floating-label-input" style="background: #FFFF">
                            Select TV Displays
                        </button>
                        <label for="tv_id" class="floating-label">TV Display</label>
                    </div>
                </div>
            </div>
        </div>
        `;

        return previewContainer;
    }

    function openPreviewModal() {
        const previewContainer = getPreviewContainer();
        document.getElementById('previewContainer').innerHTML = previewContainer;

        const numberOfInputs = parseInt(document.getElementById('number_of_inputs').value);
        for (let i = 1; i <= numberOfInputs; i++) {
            const inputType = document.getElementById(`input_type_${i}`).value;
            if (inputType === 'text') {
                new Quill(`#preview_input_${i}`, {
                    theme: 'snow',
                    placeholder: `Enter ${document.getElementById(`name_of_input_${i}`).value}`,
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline'],
                            ['link'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }]
                        ]
                    }
                });
            }
        }

        document.getElementById('previewModal').style.display = 'flex';
    }

    function submitFormViaWebSocket() {
        const form = document.getElementById('newFeatureForm');
        const formData = new FormData(form);

        const data = {
            action: 'post_new_feature',
            name_of_feature: formData.get('name_of_feature'),
            number_of_inputs: formData.get('number_of_inputs'),
            selectedIcon: formData.get('selectedIcon'),
            content_has_expiration_date: formData.get('content_has_expiration_date'),
            department: formData.get('department'),
            user_types: formData.getAll('user_type[]'),
            inputs: []
        };

        // Collect input data
        const numberOfInputs = parseInt(formData.get('number_of_inputs'));
        for (let i = 1; i <= numberOfInputs; i++) {
            data.inputs.push({
                name: formData.get(`name_of_input_${i}`),
                type: formData.get(`input_type_${i}`),
                required: formData.get(`required_field_${i}`)
            });
        }

        console.log('Form Data:', data);
        ws.send(JSON.stringify(data));

        // Listen for messages from the WebSocket server
        ws.onmessage = function(event) {
            const message = JSON.parse(event.data);
            if (message.success) {
                document.getElementById('successMessage').textContent = "New feature was successfully added!";
                document.getElementById('successMessageModal').style.display = 'flex';
                isFormDirty = false;
            } 
        };
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('newFeatureForm');
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

    function showSuccessModal() {
        document.getElementById('successMessage').textContent = 'New feature added successfully!';
        document.getElementById('successMessageModal').style.display = 'flex';
    }

    function resetFormAndGoHome() {
        document.getElementById('newFeatureForm').reset();
        location.href = 'user_home.php?pageid=UserHome&userId=<?php echo $user_id; ?>&fullName=<?php echo $full_name; ?>';
    }

</script>