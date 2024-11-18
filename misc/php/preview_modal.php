<div id="previewModal" class="modal">
    <div class="modal-content-preview">
        <div class="flex-preview-content">
            <?php 
                $sqlContainers = "SELECT c.*, t.width_px as tv_width_px, t.height_px as tv_height_px, t.tv_brand as tv_brand,
                    b.background_image_path, b.background_hex_color, b.isImageBackground, b.isStaticBackground
                FROM containers_tb c 
                JOIN smart_tvs_tb t ON c.tv_id = t.tv_id
                LEFT JOIN background_tv_tb b ON c.tv_id = b.tv_id";
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
        
        // If not PEO/SO, validate expiration and schedule dates/times
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
            const expirationDateTime = document.querySelector('[name="expiration_datetime"]').value;
            
            if (!media || displayTime === "" || tvDisplays.length === 0 || expirationDateTime === "") {
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
            previewContent += '<p class="preview-input"><strong>Display Time: </strong><br>' + document.querySelector('[name="display_time"]').value + ' seconds</p>';
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
    }

    function updatePreviewContent() {
        const selectedTvs = Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => parseInt(checkbox.value, 10));
        const selectedType = document.querySelector('[name="type"]').value;
        const previewContainer = document.getElementById('previewContainer');

        let content = '';

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
                            <div class="tv-frame" id="tvFrame-${matchingContainer.tv_id}" style="display: flex; flex-direction: column; align-items: center;">
                                <div class='tv-screen' style='
                                    height: ${matchingContainer.tv_height_px}px; 
                                    width: ${matchingContainer.tv_width_px}px; 
                                    pointer-events: none; border: none;
                                    ${matchingContainer.isImageBackground ? 
                                        `background-image: url(servers/tv_backgrounds/${matchingContainer.tv_id}/${matchingContainer.background_image_path}); background-size: cover; background-position: center;` : 
                                        (matchingContainer.isStaticBackground ? 
                                            `background-color: ${matchingContainer.background_hex_color};` : 
                                            'background-color: #000;'
                                        )
                                    }'>
                                    ${matchingContainer.visible === '1' ? 
                                        `<div class="carousel-content" 
                                            style="position: absolute;
                                                    left: ${matchingContainer.xaxis}px;
                                                    top: ${matchingContainer.yaxis}px;
                                                    width: ${matchingContainer.width_px}px;
                                                    height: ${matchingContainer.height_px}px;
                                                    background-color: ${matchingContainer.parent_background_color};
                                                    padding: 10px;
                                                    border-radius: 5px;">
                                            <h1 style="color: ${matchingContainer.parent_font_color}; 
                                                    font-family: ${matchingContainer.parent_font_family}; 
                                                    font-style: ${matchingContainer.parent_font_style}; 
                                                    margin-bottom: 5px;
                                                    text-overflow: ellipsis;
                                                    overflow: hidden;
                                                    white-space: nowrap;
                                                    width: auto;
                                                    max-width: 100%;">
                                                ${matchingContainer.container_name}
                                            </h1>
                                            <div style="background-color: ${matchingContainer.child_background_color}; 
                                                    color: ${matchingContainer.child_font_color}; 
                                                    font-style: ${matchingContainer.child_font_style}; 
                                                    font-family: ${matchingContainer.child_font_family}; 
                                                    width: auto; 
                                                    height: calc(100% - 11.5vh); 
                                                    padding: 10px; 
                                                    border-radius: 5px">
                                                <p style="white-space: pre-wrap">${content}</p>
                                            </div>
                                        </div>` :
                                        `<div class="carousel-content" 
                                            style="position: absolute;
                                                    left: ${matchingContainer.xaxis}px;
                                                    top: ${matchingContainer.yaxis}px;
                                                    width: ${matchingContainer.width_px}px;
                                                    height: ${matchingContainer.height_px}px;
                                                    background-color: ${matchingContainer.parent_background_color};
                                                    padding: 10px;
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
                
                const scaleX = parentRect.width / frameRect.width;
                const scaleY = parentRect.height / frameRect.height;
                const scale = Math.min(scaleX, scaleY, 0.5);

                // Apply transformations
                tvFrame.style.transform = `scale(${scale})`;
                tvFrame.style.transformOrigin = 'center center';
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

</script>