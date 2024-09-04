<div id="previewModal" class="modal">
    <div class="modal-content-preview">
        <div class="flex-preview-content">
            <?php 
                $sqlContainers = "SELECT * FROM containers_tb";
                $resultContainers = mysqli_query($conn, $sqlContainers);
                $containers = [];
                while ($row = mysqli_fetch_assoc($resultContainers)) {
                    $containers[] = $row; // Store each container in an array
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

    function validateAndOpenPreviewModal() {
        const selectedType = document.querySelector('[name="type"]').value;
        let contentBody = "";
        
        // Get the content based on the type
        if (selectedType === 'announcement') {
            contentBody = announcementBodyQuill.root.innerHTML.trim(); // Get content from Quill editor
        } else if (selectedType === 'event') {
            contentBody = eventBodyQuill.root.innerHTML.trim(); // Get content from Quill editor
        } else if (selectedType === 'news') {
            contentBody = newsBodyQuill.root.innerHTML.trim(); // Get content from Quill editor
        } else if (selectedType === 'peo') {
            peoTitle = peoTitleQuill.root.innerHTML.trim(); // Get content from Quill editor
            peoDescription = peoDescriptionQuill.root.innerHTML.trim(); // Get content from Quill editor
            peoSubdescription = peoSubdescriptionQuill.root.innerHTML.trim(); // Get content from Quill editor
        } else if (selectedType === 'so') {
            soTitle = soTitleQuill.root.innerHTML.trim(); // Get content from Quill editor
            soDescription = soDescriptionQuill.root.innerHTML.trim(); // Get content from Quill editor
            soSubdescription = soSubdescriptionQuill.root.innerHTML.trim(); // Get content from Quill editor
        } 

        const displayTime = document.querySelector('[name="display_time"]').value;
        const tvDisplays = document.querySelectorAll('[name="tv_id[]"]:checked'); // Get all checked TV displays
        
        // If not PEO, validate expiration and schedule dates/times
        let expirationDateTime = null;
        let scheduleDateTime = null;
        
        if (selectedType !== 'peo' && selectedType !== 'so') {
            const expirationDate = document.querySelector('[name="expiration_date"]').value;
            const expirationTime = document.querySelector('[name="expiration_time"]').value;
            const scheduleDate = document.querySelector('[name="schedule_date"]').value;
            const scheduleTime = document.querySelector('[name="schedule_time"]').value;
            
            // <p><br></p>
            // Check if any of the required fields is empty
            if (contentBody === "<p><br></p>" || contentBody === "" || displayTime === "" || tvDisplays.length === 0 || expirationDate === "" 
                || expirationTime === "") {
                // If conditions are not met, show error message and exit
                errorModalMessage("Please fill the necessary fields and select at least one TV display.");
                return;
            }

            // Convert expiration date and time to Date object
            expirationDateTime = new Date(expirationDate + ' ' + expirationTime);
            const currentDateTime = new Date();

            if (expirationDateTime < currentDateTime) {
                errorModalMessage("Expiration date and time should not be in the past.");
                return;
            }

            // If schedule date and time are provided, validate them
            if (scheduleDate !== "" && scheduleTime !== "") {
                scheduleDateTime = new Date(scheduleDate + ' ' + scheduleTime);

                if (scheduleDateTime < currentDateTime) {
                    errorModalMessage("Schedule date and time should not be in the past.");
                    return;
                } else if (expirationDateTime < scheduleDateTime) {
                    errorModalMessage("Expiration date and time should not be before the schedule date and time.");
                    return;
                } else if (scheduleDateTime > expirationDateTime) {
                    errorModalMessage("Schedule date and time should not be after the expiration date and time.");
                    return;
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
        // Function to format date and time
        function formatDateTime(dateString, timeString) {
            const dateTime = new Date(dateString + ' ' + timeString);
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
            previewContent += '<p class="preview-input"><strong>Expiration Date & Time: </strong><br>' + formatDateTime(document.querySelector('[name="expiration_date"]').value, document.querySelector('[name="expiration_time"]').value) + '</p>';
            previewContent += '<p class="preview-input"><strong>Schedule Post Date & Time: </strong><br>' + (document.querySelector('[name="schedule_date"]').value ? formatDateTime(document.querySelector('[name="schedule_date"]').value, document.querySelector('[name="schedule_time"]').value) : 'Not scheduled') + '</p>';
        } else {
            previewContent += '<p class="preview-input"><strong>Display Time: </strong><br>' + document.querySelector('[name="display_time"]').value + ' seconds</p>';
        }

        const selectedTvs = Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => checkbox.value);
        const selectedTvNames = selectedTvs.map(tvId => tvNames[tvId]); // Map tv_id to tv_name
        previewContent += '<p class="preview-input"><strong>TV Display: </strong><br>' + (selectedTvNames.length > 0 ? selectedTvNames.join(", ") : 'None selected') + '</p>';
        
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
                content = announcementBodyQuill.root.innerHTML;
                break;
            case 'event':
                content = eventBodyQuill.root.innerHTML;
                break;
            case 'news':
                content = newsBodyQuill.root.innerHTML;
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

                carouselHTML += `
                    <div class="carousel-item ${index === 0 ? 'active' : ''}" style="display: none;" data-tv-id="${matchingContainer.tv_id}">
                        <div style="background-color: ${matchingContainer.parent_background_color}; padding: 10px; border-radius: 5px; height: ${matchingContainer.height_px}px; width: ${matchingContainer.width_px}px;">
                            <h1 style="color: ${matchingContainer.parent_font_color}; font-family: ${matchingContainer.parent_font_family}; font-style: ${matchingContainer.parent_font_style}; font-size: 2.0vh; margin-bottom: 5px">${matchingContainer.container_name}</h1>
                            <div style="background-color: ${matchingContainer.child_background_color}; color: ${matchingContainer.child_font_color}; font-style: ${matchingContainer.child_font_style}; font-family: ${matchingContainer.child_font_family}; width: auto; height: calc(100% - 6.5vh); font-size: 1.5vh; padding: 10px; border-radius: 5px">
                                <p style="white-space: pre-wrap">${content}</p>
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
                        <p id="carouselTvName">${initialTvName}</p>
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
                items[currentIndex].style.display = 'none'; // Hide current item
                items[currentIndex].classList.remove('active');

                // Update index
                currentIndex += direction;

                // Loop around if at the ends
                if (currentIndex < 0) {
                    currentIndex = items.length - 1;
                } else if (currentIndex >= items.length) {
                    currentIndex = 0;
                }

                items[currentIndex].style.display = 'block'; // Show new item
                items[currentIndex].classList.add('active');

                updateTvName(); // Update the TV name based on the new active item
            };
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