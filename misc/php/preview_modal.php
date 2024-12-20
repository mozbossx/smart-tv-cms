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
            <div style="display: flex, flex-direction: column; flex: 2; height: auto; overflow: auto">
                <div id="previewContainer"></div>
            </div>
            <!-- The container consists of child_background_color, child_font_style, child_font_color-->
            <div class="preview-content" id="previewContent"></div>
        </div>
        <!-- Operation buttons inside the Preview modal -->
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

    // Function to close the preview modal
    function closePreviewModal() {
        var modal = document.getElementById('previewModal');
        modal.style.display = 'none';

        // Clear the interval if it exists
        if (clockInterval) {
            clearInterval(clockInterval);
            clockInterval = null;
        }
    }

    function validateAndOpenPreviewModal() {
        const selectedType = document.querySelector('[name="type"]').value;
        const isEditMode = document.querySelector('input[name="action_value"]')?.value === 'edit';
        let contentBody = "";
        
        // Get the content based on the type
        if (selectedType === 'announcement') {
            contentBody = quillEditors.announcementBodyQuill.root.innerHTML.trim();
        } else if (selectedType === 'event') {
            contentBody = quillEditors.eventBodyQuill.root.innerHTML.trim();
        } else if (selectedType === 'news') {
            contentBody = quillEditors.newsBodyQuill.root.innerHTML.trim();
        } else if (selectedType === 'promaterial') {
            media = document.querySelector('[name="media"]').files[0];
        } else if (selectedType === 'peo') {
            peoTitle = quillEditors.peoTitleQuill.root.innerHTML.trim();
            peoDescription = quillEditors.peoDescriptionQuill.root.innerHTML.trim();
            peoSubdescription = quillEditors.peoSubdescriptionQuill.root.innerHTML.trim();
        } else if (selectedType === 'so') {
            soTitle = quillEditors.soTitleQuill.root.innerHTML.trim();
            soDescription = quillEditors.soDescriptionQuill.root.innerHTML.trim();
            soSubdescription = quillEditors.soSubdescriptionQuill.root.innerHTML.trim();
        }

        const displayTime = document.querySelector('[name="display_time"]').value;
        const tvDisplays = document.querySelectorAll('[name="tv_id[]"]:checked'); // Get all checked TV displays
        
        // If not PEO, validate expiration and schedule dates/times
        let expirationDateTime = null;
        let scheduleDateTime = null;
        
        // If not PEO, SO, or Promotional Material, validate expiration and schedule dates/times
        if (selectedType !== 'peo' && selectedType !== 'so' && selectedType !== 'promaterial') {
            const expirationDateTime = document.querySelector('[name="expiration_datetime"]').value;
            
            // Check if any of the required fields is empty
            if (contentBody === "<p><br></p>" || contentBody === "" || displayTime === "" || tvDisplays.length === 0 || expirationDateTime === "") {
                errorModalMessage("Please fill the necessary fields and select at least one TV display.");
                return;
            }

            // Convert expiration date and time to Date object
            const currentDateTime = new Date();
            const expirationDate = new Date(expirationDateTime);

            if (expirationDate < currentDateTime) {
                errorModalMessage("Expiration date and time should not be in the past.");
                return;
            }

            // Only validate schedule_datetime if NOT in edit mode
            if (!isEditMode) {
                const scheduleDateTime = document.querySelector('[name="schedule_datetime"]').value;
                if (scheduleDateTime) {
                    const scheduleDate = new Date(scheduleDateTime);

                    if (scheduleDate < currentDateTime) {
                        errorModalMessage("Schedule date and time should not be in the past.");
                        return;
                    } else if (expirationDate < scheduleDate) {
                        errorModalMessage("Expiration date and time should not be before the schedule date and time.");
                        return;
                    }
                }
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

            // Only validate schedule_datetime if NOT in edit mode
            if (!isEditMode) {
                const scheduleDateTime = document.querySelector('[name="schedule_datetime"]').value;
                if (scheduleDateTime) {
                    const scheduleDate = new Date(scheduleDateTime);

                    if (scheduleDate < currentDateTime) {
                        errorModalMessage("Schedule date and time should not be in the past.");
                        return;
                    } else if (expirationDate < scheduleDate) {
                        errorModalMessage("Expiration date and time should not be before the schedule date and time.");
                        return;
                    }
                }
            }
        } else if (contentType === 'peo') {
            // For PEO, only check the basic fields
            if (peoTitle === "<p><br></p>" || peoDescription === "<p><br></p>" || displayTime === "" || tvDisplays.length === 0) {
                errorModalMessage("Please fill the necessary fields and select at least one TV display.");
                return;
            }
        } else if (contentType === 'so') {
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

        // Function to format date and time
        function formatDateTime(dateTimeString) {
            const dateTime = new Date(dateTimeString);
            const options = {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            };
            return new Intl.DateTimeFormat('en-US', options).format(dateTime);
        }

        var previewContent = '';

        if (selectedType !== 'peo' && selectedType !== 'so') {
            previewContent += '<p class="preview-input"><strong>Display Time: </strong><br>' + displayTime + ' seconds</p>';
            previewContent += '<p class="preview-input"><strong>Expiration Date & Time: </strong><br>' + formatDateTime(document.querySelector('[name="expiration_datetime"]').value) + '</p>';
            
            // Only show schedule date/time in create mode
            if (!isEditMode) {
                previewContent += '<p class="preview-input"><strong>Schedule Post Date & Time: </strong><br>' + 
                    (document.querySelector('[name="schedule_datetime"]').value ? 
                        formatDateTime(document.querySelector('[name="schedule_datetime"]').value) : 
                        'Not scheduled') + '</p>';
            }
        } else {
            previewContent += '<p class="preview-input"><strong>Display Time: </strong><br>' + document.querySelector('[name="display_time"]').value + ' seconds</p>';
        }

        const selectedTvs = Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => checkbox.value);
        const selectedTvNames = selectedTvs.map(tvId => tvNames[tvId]); // Map tv_id to tv_name
        previewContent += '<p class="preview-input" style="text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><strong>TV Display: </strong><br>' + (selectedTvNames.length > 0 ? selectedTvNames.join(", ") : 'None selected') + '</p>';
        
        return previewContent;
    }

    // Function to open the preview modal
    function openPreviewModal() {
        var modal = document.getElementById('previewModal');
        modal.style.display = 'flex';

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        // Initial call to load the container when the page loads with a pre-selected tv_id
        updatePreviewContent();
        // Display the preview content in the modal
        document.getElementById('previewContent').innerHTML = getPreviewContent();

        // Clear any existing interval
        if (clockInterval) {
            clearInterval(clockInterval);
        }

        // Start clock updates
        updateClock(); // Initial update
        clockInterval = setInterval(updateClock, 1000);
        
        // Clear interval when modal is closed by clicking outside
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

        // Generate media preview HTML if media is selected
        if (mediaInput && mediaInput.files && mediaInput.files[0]) {
            const file = mediaInput.files[0];
            const fileType = file.type;
            const fileURL = URL.createObjectURL(file);
            
            if (fileType.startsWith('video/')) {
                mediaContent = `<video controls style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 5px;">
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
                    mediaContent = `<img src="servers/<?php echo $type; ?>_media/${mediaPath}" 
                            style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 5px;">`;
                } else if (mediaPath.match(/\.(mp4|webm|ogg)$/i)) {
                    mediaContent = `<video controls style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 5px;">
                            <source src="servers/<?php echo $type; ?>_media/${mediaPath}">
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
            default:
                content = 'Unknown content type';
                break;
        }

        // For types other than promaterial, append media content after the main content if it exists
        if (selectedType !== 'promaterial' && mediaContent) {
            content += mediaContent;
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
                const tvName = tvNames[matchingContainer.tv_id] || 'Unknown TV';
                const isSelectedType = matchingContainer.type === selectedType;
                const isVisible = matchingContainer.visible === '1';

                carouselHTML += `
                    <div class="carousel-item ${index === 0 ? 'active' : ''}" style="display: none;" data-tv-id="${matchingContainer.tv_id}">
                        <div class="tv-frame-parent" style="width: auto; height: 100%; background: none; cursor: default;">
                            <div class="tv-frame" id="tvFrame-${matchingContainer.tv_id}" style="position: relative; display: flex; flex-direction: column; align-items: center; overflow: clip; user-select: all">
                                <div class="topbar" id="topbar" style="background: ${matchingContainer.topbar_hex_color}">
                                    <img src="${matchingContainer.department_logo_path ? 
                                    `servers/tv_department_logos/${matchingContainer.tv_id}/${matchingContainer.department_logo_path}` : 
                                    'images/soe_icon.png'}" 
                                    alt="" style="width: 5vh; height: 5vh; margin-left: 5px">
                                    <div class="tv-id" style="color: ${matchingContainer.topbar_tvid_font_color}; font-style: ${matchingContainer.topbar_tvid_font_style}; font-family: ${matchingContainer.topbar_tvid_font_family}; font-size: 2.5vh; padding-left: 10px;">
                                        <p id="tvDepartment_${matchingContainer.tv_id}">${matchingContainer.tv_department}</p>
                                        <p id="tv-id"><i class="fa fa-television" aria-hidden="true"></i>: ${matchingContainer.tv_id}</p>
                                    </div>
                                    <h1 class="tv-name" style="color: ${matchingContainer.topbar_tvname_font_color}; font-style: ${matchingContainer.topbar_tvname_font_style}; font-family: ${matchingContainer.topbar_tvname_font_family};">
                                        ${matchingContainer.tv_name}
                                    </h1>
                                    <div class="date-time">
                                        <span class="live-clock time" style="color: ${matchingContainer.topbar_time_font_color}; font-style: ${matchingContainer.topbar_time_font_style}; font-family: ${matchingContainer.topbar_time_font_family};"></span>
                                        <span class="live-date date" style="color: ${matchingContainer.topbar_date_font_color}; font-style: ${matchingContainer.topbar_date_font_style}; font-family: ${matchingContainer.topbar_date_font_family};"></span>
                                    </div>
                                </div>
                                <div class='tv-screen' style='
                                    height: ${matchingContainer.tv_height_px}px; 
                                    width: ${matchingContainer.tv_width_px}px; 
                                    pointer-events: none; border: none;
                                    border-radius: 0;
                                    ${matchingContainer.isImageBackground === '1' ? 
                                        (matchingContainer.background_image_path ? 
                                            `background-image: url(servers/tv_backgrounds/${matchingContainer.tv_id}/${matchingContainer.background_image_path}); 
                                            background-size: cover; 
                                            background-position: center;` : 
                                            'background-color: #000;'
                                        ) :
                                        (matchingContainer.isStaticBackground === '1' ? 
                                            `background-color: ${matchingContainer.background_hex_color};` : 
                                            'background-color: #000;'
                                        )
                                    }'>
                                    ${matchingContainer.visible === '1' ? 
                                        `<div class="carousel-content" 
                                            style="position: relative;
                                                    transform: translate(${matchingContainer.xaxis}px, ${matchingContainer.yaxis}px);
                                                    width: ${matchingContainer.width_px}px;
                                                    height: ${matchingContainer.height_px}px;
                                                    background-color: ${matchingContainer.parent_background_color};
                                                    padding: 5px;
                                                    border-radius: 5px;">
                                            <h1 style="color: ${matchingContainer.parent_font_color}; 
                                                    font-family: ${matchingContainer.parent_font_family}; 
                                                    font-style: ${matchingContainer.parent_font_style}; 
                                                    margin-bottom: 5px;
                                                    text-overflow: ellipsis;
                                                    overflow: hidden;
                                                    white-space: nowrap;
                                                    width: auto;
                                                    max-width: 100%;
                                                    font-size: 2.5vh">
                                                ${matchingContainer.container_name}
                                            </h1>
                                            <div id="carouselContent-${matchingContainer.tv_id}" style="background-color: ${matchingContainer.child_background_color}; 
                                                    color: ${matchingContainer.child_font_color}; 
                                                    font-style: ${matchingContainer.child_font_style}; 
                                                    font-family: ${matchingContainer.child_font_family}; 
                                                    width: auto; 
                                                    height: calc(100% - 9.5vh); 
                                                    display: flex;
                                                    flex-direction: column;
                                                    padding: 5px; 
                                                    border-radius: 5px">
                                                <div class="carousel-item-2 active" style="width: auto; margin-bottom: 5px; height: 100%; border-radius: 5px;">
                                                    <div style="border: none; border-top-left-radius: 5px; border-top-right-radius: 5px; text-align: justify; height: 100%; bottom: 0;">
                                                        <div class="content-container-con" style="height: 100%">
                                                            <div class="content-main">
                                                                ${selectedType === 'promaterial' ? 
                                                                    mediaContent : 
                                                                    `${mediaContent ? 
                                                                        `<div class="media-container" style="margin-bottom: 5px;">
                                                                            ${mediaContent}
                                                                        </div>` : 
                                                                        ''
                                                                    }
                                                                    <div class="main-message" style="margin: 0; word-break: break-word;">
                                                                        ${selectedType === 'peo' ? 
                                                                            `<h2>${quillEditors.peoTitleQuill.root.innerHTML}</h2>
                                                                            <div>${quillEditors.peoDescriptionQuill.root.innerHTML}</div>
                                                                            <div>${quillEditors.peoSubdescriptionQuill.root.innerHTML}</div>` :
                                                                        selectedType === 'so' ? 
                                                                            `<h2>${quillEditors.soTitleQuill.root.innerHTML}</h2>
                                                                            <div>${quillEditors.soDescriptionQuill.root.innerHTML}</div>
                                                                            <div>${quillEditors.soSubdescriptionQuill.root.innerHTML}</div>` :
                                                                        selectedType === 'announcement' ? quillEditors.announcementBodyQuill.root.innerHTML :
                                                                        selectedType === 'event' ? quillEditors.eventBodyQuill.root.innerHTML :
                                                                        selectedType === 'news' ? quillEditors.newsBodyQuill.root.innerHTML :
                                                                        ''}
                                                                    </div>`
                                                                }
                                                            </div>
                                                            <div class="content-details">
                                                                <div style="display: flex; flex-direction: row; margin: 0">
                                                                    <div>
                                                                        <p class="author" style="margin: 0; font-size: 1.9vh;"><small>Posted on ${new Date().toLocaleString()}</small></p>
                                                                    </div>
                                                                    <div style="margin-left: auto; margin-top: auto">
                                                                        <p class="display-time" style="text-align: right; margin: 0; font-size: 1.9vh;">
                                                                            <i class="fa fa-hourglass-half" aria-hidden="true"></i> 
                                                                            <span class="time-left">${document.querySelector('[name="display_time"]').value}s</span>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>` :
                                        `<div class="carousel-content" 
                                            style="position: relative;
                                                    transform: translate(5px, 5px);
                                                    width: 400px;
                                                    height: 400px;
                                                    background-color: ${matchingContainer.parent_background_color};
                                                    padding: 5px;
                                                    border-radius: 5px;
                                                    display: flex;
                                                    justify-content: center;
                                                    align-items: center;
                                                    flex-direction: column;">
                                                <h1 style="font-size: 35px; color: ${matchingContainer.parent_font_color}"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></h1> 
                                                <h1 style="color: ${matchingContainer.parent_font_color}; 
                                                    font-family: ${matchingContainer.parent_font_family}; 
                                                    font-style: ${matchingContainer.parent_font_style}; 
                                                    margin-bottom: 5px;
                                                    text-align: center;
                                                    align-content: center;">
                                                ${selectedType.charAt(0).toUpperCase() + selectedType.slice(1)} Container not visible in the TV
                                            </h1>
                                        </div>`
                                    }
                                </div>
                                <p id="tvBrand2UserHome_${matchingContainer.tv_id}" style="text-align: center; font-size: 25px; margin-top: auto; color: white; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; width: auto; max-width: 250px;">${matchingContainer.tv_brand}</p>
                                <div class="tv-stand"></div>
                            </div>
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
                setTimeout(() => {
                    updateTvName();
                    fitFrameToParent();
                }, 0);
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
                
                // Force a reflow
                void tvFrame.offsetWidth;
                
                const frameRect = tvFrame.getBoundingClientRect();
                
                // Calculate scale while leaving room for the TV stand
                const scaleX = (parentRect.width) / frameRect.width;
                const scaleY = (parentRect.height - 50) / frameRect.height; // Subtract some pixels for TV stand
                const scale = Math.min(scaleX, scaleY, 0.5);

                // Apply transformations with adjusted position
                tvFrame.style.transform = `scale(${scale})`;
                tvFrame.style.transformOrigin = 'center top';
                tvFrame.style.marginBottom = '50px'; // Add margin to ensure stand visibility
            }

            // Fit frame on load
            fitFrameToParent();

            // Refit on window resize
            // window.addEventListener('resize', fitFrameToParent);
        } else {
            previewContainer.innerHTML = '<p>No container found for the selected TVs.</p>'; // Fallback message
        }
    }

    // Add event listener to tv_id select element
    document.addEventListener("DOMContentLoaded", function () {
        const tvCheckboxes = document.querySelectorAll('[name="tv_id[]"]');
        tvCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updatePreviewContent);
        });
    });

    // Function to update the clock
    function updateClock() {
        const clockElements = document.getElementsByClassName('live-clock');
        const dateElements = document.getElementsByClassName('live-date');
        
        if (clockElements.length > 0 && dateElements.length > 0) {
            const now = new Date();
            const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sept", "Oct", "Nov", "Dec"];
            
            let hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12; // Convert to 12-hour format
            
            const dayOfWeek = daysOfWeek[now.getDay()];
            const month = months[now.getMonth()];
            const day = now.getDate().toString().padStart(2, '0');
            const year = now.getFullYear();

            const timeString = `${hours}:${minutes} ${ampm}`;
            const dateString = `${dayOfWeek}, ${month} ${day}, ${year}`;

            // Update all clock elements
            Array.from(clockElements).forEach(element => {
                element.textContent = timeString;
            });

            // Update all date elements
            Array.from(dateElements).forEach(element => {
                element.textContent = dateString;
            });
        }
    }
</script>