<div id="newFeaturePreviewModal" class="modal">
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
                <div id="newFeaturePreviewContainer"></div>
            </div>
            <div class="preview-content" id="newFeaturePreviewContent"></div>
        </div>
        <div class="flex-button-modal">
            <button type="button" class="green-button" id="newFeatureCloseButton" style="background: none; border: 1px solid #264B2B; color: #264B2B; margin-top: 0; margin-right: 5px" onclick="closeNewFeaturePreviewModal()">Cancel</button>
            <button type="button" name="post" class="green-button" style="margin-top: 0; margin-right: 0" onclick="submitFormViaWebSocket()">Submit</button>
        </div>
    </div>
</div>

<script>
document.getElementById('newFeatureCloseButton').addEventListener('click', function() {
    closeNewFeaturePreviewModal();
});

function closeNewFeaturePreviewModal() {
    var modal = document.getElementById('newFeaturePreviewModal');
    modal.style.display = 'none';
}

function validateAndOpenNewFeaturePreviewModal() {
    const selectedType = document.querySelector('[name="type"]').value;
    const form = document.getElementById(`${selectedType}Form`);
    const inputs = form.querySelectorAll('input, select, textarea');
    
    let isValid = true;
    let contentBody = {};

    // Validate regular inputs
    inputs.forEach(input => {
        if (input.required && !input.value) {
            isValid = false;
        }
        if (input.type !== 'file') {
            contentBody[input.name] = input.value;
        }
    });

    // Validate Quill editors
    const quillEditors = document.querySelectorAll('.quill-editor-container-newfeature .ql-container');
    quillEditors.forEach(editorContainer => {
        const quillEditor = Quill.find(editorContainer);
        const quillContent = quillEditor.root.innerHTML.trim();
        const quillPlainText = quillEditor.getText().trim();
        const editorName = editorContainer.closest('.quill-editor-container-newfeature').id.replace('quillEditorContainer_', '');
        
        if (quillPlainText === '' || quillContent === '<p><br></p>') {
            isValid = false;
            editorContainer.classList.add('quill-error');
        } else {
            editorContainer.classList.remove('quill-error');
            contentBody[editorName] = quillContent;
        }
    });

    // Check for file inputs
    const fileInputs = form.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        if (input.required && input.files.length === 0) {
            isValid = false;
        }
        if (input.files.length > 0) {
            contentBody[input.name] = input.files[0].name; // Store file name for preview
        }
    });

    // Validate TV displays
    const tvDisplays = document.querySelectorAll('[name="tv_id[]"]:checked');
    if (tvDisplays.length === 0) {
        isValid = false;
    }

    // Validate expiration dates/times if they exist
    const expirationDateTime = document.querySelector('[name="expiration_datetime"]');
    if (expirationDateTime) {
        const expirationDate = new Date(expirationDateTime.value);
        const currentDateTime = new Date();

        if (expirationDate < currentDateTime) {
            errorModalMessage("Expiration date and time should not be in the past.");
            return;
        }
    }

    if (!isValid) {
        errorModalMessage("Please fill all required fields and select at least one TV display.");
        return;
    }

    // If all conditions are met, open the preview modal
    openNewFeaturePreviewModal(contentBody);
}

function openNewFeaturePreviewModal(contentBody) {
    var modal = document.getElementById('newFeaturePreviewModal');
    modal.style.display = 'flex';

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    updateNewFeaturePreviewContent(contentBody);
    document.getElementById('newFeaturePreviewContent').innerHTML = getNewFeaturePreviewContent(contentBody);
}

function updateNewFeaturePreviewContent(contentBody) {
    const selectedTvs = Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => parseInt(checkbox.value, 10));
    const selectedType = document.querySelector('[name="type"]').value;
    const previewContainer = document.getElementById('newFeaturePreviewContainer');

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
                            ${generateNewFeaturePreviewContent(contentBody)}
                        </div>
                    </div>
                </div>
            `;
        });
        carouselHTML += '</div>';

        // Add navigation buttons if there's more than one container
        if (matchingContainers.length > 1) {
            const initialTvName = tvNames[matchingContainers[0].tv_id] || 'Unknown TV';
            carouselHTML += `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" class="carousel-control prev" onclick="moveNewFeatureCarousel(-1)"><i class="fa fa-angle-left" aria-hidden="true"></i> Previous</button>
                    <p id="newFeatureCarouselTvName">${initialTvName}</p>
                    <button type="button" class="carousel-control next" onclick="moveNewFeatureCarousel(1)">Next <i class="fa fa-angle-right" aria-hidden="true"></i></button>
                </div>
            `;
        }

        previewContainer.innerHTML = carouselHTML;

        // Show the first item
        const items = document.querySelectorAll('.carousel-item');
        items[0].style.display = 'block';

        // Initialize the current index
        let currentIndex = 0;

        // Function to move carousel
        window.moveNewFeatureCarousel = function(direction) {
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

            // Update the TV name
            const activeItem = items[currentIndex];
            const activeTvId = activeItem.getAttribute('data-tv-id');
            const activeTvName = tvNames[activeTvId] || 'Unknown TV';
            document.getElementById('newFeatureCarouselTvName').textContent = activeTvName;
        };
    } else {
        previewContainer.innerHTML = '<p>No container found for the selected TVs.</p>';
    }
}

function generateNewFeaturePreviewContent(contentBody) {
    let previewHTML = '';
    for (const [key, value] of Object.entries(contentBody)) {
        if (typeof value === 'string' && value.trim() !== '') {
            previewHTML += `<p><strong>${key}:</strong> ${value}</p>`;
        }
    }
    return previewHTML;
}

function getNewFeaturePreviewContent(contentBody) {
    let previewContent = '';

    // Display Time
    if (contentBody.display_time) {
        previewContent += `<p class="preview-input"><strong>Display Time: </strong><br>${contentBody.display_time} seconds</p>`;
    }

    // Expiration Date & Time
    if (contentBody.expiration_datetime && contentBody.expiration_time) {
        previewContent += `<p class="preview-input"><strong>Expiration Date & Time: </strong><br>${formatDateTime(contentBody.expiration_datetime, contentBody.expiration_time)}</p>`;
    }

    // TV Display
    const selectedTvs = Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => checkbox.value);
    const selectedTvNames = selectedTvs.map(tvId => tvNames[tvId]);
    previewContent += `<p class="preview-input"><strong>TV Display: </strong><br>${selectedTvNames.length > 0 ? selectedTvNames.join(", ") : 'None selected'}</p>`;

    return previewContent;
}

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

// Add event listener to tv_id checkboxes
document.addEventListener("DOMContentLoaded", function () {
    const tvCheckboxes = document.querySelectorAll('[name="tv_id[]"]');
    tvCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (document.getElementById('newFeaturePreviewModal').style.display === 'flex') {
                validateAndOpenNewFeaturePreviewModal();
            }
        });
    });
});
</script>