<div id="previewModal" class="modal">
    <div class="modal-content-preview">
        <div class="flex-preview-content">
            <?php 
                $sqlContainers = "SELECT c.*, t.width_px as tv_width_px, t.height_px as tv_height_px, t.tv_brand as tv_brand, t.tv_name as tv_name, t.tv_department as tv_department,
                    b.background_image_path, b.background_hex_color, b.isImageBackground, b.isStaticBackground,
                    tb.topbar_hex_color, tb.department_logo_path,
                    tb.topbar_tvname_font_color, tb.topbar_tvname_font_style, tb.topbar_tvname_font_family, 
                    tb.topbar_tvid_font_color, tb.topbar_tvid_font_style, tb.topbar_tvid_font_family, 
                    tb.topbar_time_font_color, tb.topbar_time_font_style, tb.topbar_time_font_family, 
                    tb.topbar_date_font_color, tb.topbar_date_font_style, tb.topbar_date_font_family
                FROM containers_tb c 
                JOIN smart_tvs_tb t ON c.tv_id = t.tv_id
                LEFT JOIN background_tv_tb b ON c.tv_id = b.tv_id
                LEFT JOIN topbar_tv_tb tb ON c.tv_id = tb.tv_id";
                $resultContainers = mysqli_query($conn, $sqlContainers);
                $containers = [];
                while ($row = mysqli_fetch_assoc($resultContainers)) {
                    $containers[] = $row;
                }
            ?>
            <div style="display: flex; flex-direction: column; flex: 2; height: auto; overflow: auto">
                <div id="previewContainer">
                    <div class="carousel">
                        <div class="carousel-item active" style="display: block; width: auto; margin-bottom: 5px; height: 100%; border-radius: 5px;">
                            <div style="border: none; border-top-left-radius: 5px; border-top-right-radius: 5px; padding: 10px; text-align: justify; height: calc(100% - 20px); bottom: 0;">
                                <div class="content-container-con">
                                    <div class="content-main">
                                        <!-- Dynamic content will be inserted here -->
                                    </div>
                                    <div class="content-details">
                                        <div style="display: flex; flex-direction: row; margin: 0">
                                            <div>
                                                <p class="author"><small>Posted on <span id="previewDateTime"></span></small></p>
                                            </div>
                                            <div style="margin-left: auto; margin-top: auto">
                                                <p class="display-time" style="text-align: right">
                                                    <i class="fa fa-hourglass-half" aria-hidden="true"></i> 
                                                    <span class="time-left"></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="preview-content" id="previewContent"></div>
        </div>
        <div class="flex-button-modal">
            <button type="button" class="grey-button" id="closeButton" style="margin-top: 0; margin-right: 5px" onclick="closePreviewModal()">Cancel</button>
            <button type="button" name="post" class="green-button" style="margin-top: 0; margin-right: 0" onclick="submitFormViaWebSocket()">Submit</button>
        </div>
    </div>
</div>

<script>
    let clockInterval;

    document.getElementById('closeButton').addEventListener('click', function() {
        closePreviewModal();
    });

    function closePreviewModal() {
        var modal = document.getElementById('previewModal');
        modal.style.display = 'none';

        if (clockInterval) {
            clearInterval(clockInterval);
            clockInterval = null;
        }
    }

    function validateAndOpenPreviewModal() {
        const selectedType = document.querySelector('[name="type"]').value;
        const isEditMode = document.querySelector('input[name="action_value"]')?.value === 'edit';
        const displayTime = document.querySelector('[name="display_time"]').value;
        const tvDisplays = document.querySelectorAll('[name="tv_id[]"]:checked');
        
        // Validate based on content type
        if (selectedType === 'promaterial') {
            const media = document.querySelector('[name="media"]').files[0];
            if (!media || displayTime === "" || tvDisplays.length === 0) {
                errorModalMessage("Please fill the necessary fields and select at least one TV display.");
                return;
            }
        } else if (selectedType === 'peo') {
            const peoTitle = quillEditors.peoTitleQuill.root.innerHTML.trim();
            const peoDescription = quillEditors.peoDescriptionQuill.root.innerHTML.trim();
            if (peoTitle === "<p><br></p>" || peoDescription === "<p><br></p>" || displayTime === "" || tvDisplays.length === 0) {
                errorModalMessage("Please fill the necessary fields and select at least one TV display.");
                return;
            }
        } else if (selectedType === 'so') {
            const soTitle = quillEditors.soTitleQuill.root.innerHTML.trim();
            const soDescription = quillEditors.soDescriptionQuill.root.innerHTML.trim();
            if (soTitle === "<p><br></p>" || soDescription === "<p><br></p>" || displayTime === "" || tvDisplays.length === 0) {
                errorModalMessage("Please fill the necessary fields and select at least one TV display.");
                return;
            }
        } else {
            // For announcement, event, and news
            const contentBody = quillEditors[selectedType + 'BodyQuill'].root.innerHTML.trim();
            const expirationDateTime = document.querySelector('[name="expiration_datetime"]').value;
            
            if (contentBody === "<p><br></p>" || contentBody === "" || displayTime === "" || tvDisplays.length === 0 || expirationDateTime === "") {
                errorModalMessage("Please fill the necessary fields and select at least one TV display.");
                return;
            }

            // Validate dates
            const currentDateTime = new Date();
            const expirationDate = new Date(expirationDateTime);

            if (expirationDate < currentDateTime) {
                errorModalMessage("Expiration date and time should not be in the past.");
                return;
            }

            if (!isEditMode) {
                const scheduleDateTime = document.querySelector('[name="schedule_datetime"]').value;
                if (scheduleDateTime) {
                    const scheduleDate = new Date(scheduleDateTime);
                    if (scheduleDate < currentDateTime) {
                        errorModalMessage("Schedule date and time should not be in the past.");
                        return;
                    }
                    if (expirationDate < scheduleDate) {
                        errorModalMessage("Expiration date and time should not be before the schedule date and time.");
                        return;
                    }
                }
            }
        }

        // If validation passes, update preview and open modal
        updatePreviewContent();
        openPreviewModal();
    }

    function updatePreviewContent() {
        const selectedType = document.querySelector('[name="type"]').value;
        const contentMain = document.querySelector('.content-main');
        const timeLeft = document.querySelector('.time-left');
        const previewDateTime = document.getElementById('previewDateTime');
        
        // Set display time
        const displayTime = document.querySelector('[name="display_time"]').value;
        timeLeft.textContent = `${displayTime}s`;

        // Set current datetime
        const now = new Date();
        const options = {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
        };
        previewDateTime.textContent = new Intl.DateTimeFormat('en-US', options).format(now);

        // Set content based on type
        let content = '';
        const mediaFile = document.querySelector('[name="media"]')?.files[0];
        const mediaPreview = mediaFile ? getMediaPreview(mediaFile) : '';

        switch (selectedType) {
            case 'announcement':
            case 'event':
            case 'news':
                content = `
                    ${mediaPreview ? `<div class="media-container" style="margin-bottom: 5px">${mediaPreview}</div>` : ''}
                    <p style="white-space: pre-wrap">${quillEditors[selectedType + 'BodyQuill'].root.innerHTML}</p>
                `;
                break;
                
            case 'promaterial':
                if (mediaFile) {
                    content = `<div class="media-container" style="margin-bottom: 5px">${mediaPreview}</div>`;
                }
                break;
                
            case 'peo':
                content = `
                    <h2>${quillEditors.peoTitleQuill.root.innerHTML}</h2>
                    <div>${quillEditors.peoDescriptionQuill.root.innerHTML}</div>
                    <div>${quillEditors.peoSubdescriptionQuill.root.innerHTML}</div>
                `;
                break;
                
            case 'so':
                content = `
                    <h2>${quillEditors.soTitleQuill.root.innerHTML}</h2>
                    <div>${quillEditors.soDescriptionQuill.root.innerHTML}</div>
                    <div>${quillEditors.soSubdescriptionQuill.root.innerHTML}</div>
                `;
                break;
        }
        
        contentMain.innerHTML = content;

        // Update the preview details (TV displays, dates, etc.)
        document.getElementById('previewContent').innerHTML = getPreviewContent();
    }

    function getMediaPreview(file) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(file.name);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(file.name);
        
        if (isImage) {
            return `<img src="${URL.createObjectURL(file)}" alt="Preview Image">`;
        } else if (isVideo) {
            return `<video controls><source src="${URL.createObjectURL(file)}" type="video/mp4"></video>`;
        }
        return '';
    }

    function openPreviewModal() {
        var modal = document.getElementById('previewModal');
        modal.style.display = 'flex';

        // Update clock
        updateClock();
        if (clockInterval) {
            clearInterval(clockInterval);
        }
        clockInterval = setInterval(updateClock, 1000);

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                closePreviewModal();
            }
        }
    }

    // Rest of your existing functions (updateClock, getPreviewContent, etc.)...
</script>