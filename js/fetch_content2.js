// Function to format date to "MM DD YYYY"
function formatDate(dateTimeString) {
    const dateTime = new Date(dateTimeString);
    const options = {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    };
    return new Intl.DateTimeFormat('en-US', options).format(dateTime);
}

// Utility function to create buttons
const createDeleteButton = (text, iconClass, onClick) => {
    const button = document.createElement('button');
    button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
    button.className = 'light-green-button';
    button.style = `
        margin-top: 10px;
        margin-right: 5px;
    `;
    button.onclick = onClick;
    return button;
};

const createArchiveButton = (text, iconClass, onClick) => {
    const button = document.createElement('button');
    button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
    button.className = 'light-green-button';
    button.style = `
        margin-top: 10px;
        margin-right: 5px;
    `;
    button.onclick = onClick;
    return button;
};

const createEditButton = (text, iconClass, onClick) => {
    const button = document.createElement('button');
    button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
    button.className = 'green-button';
    button.onclick = onClick;
    return button;
};

// Function to create content div
const createContentDiv = (data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, type) => {
    let contentHtml = `
        <div class="content-container-con">
            ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
    `;

    // Add type-specific content
    switch(type) {
        case 'announcement':
        case 'event':
        case 'news':
            contentHtml += `<pre class="ann-body" style="word-break: break-word">${data[`${type}_body`]}</pre>`;
            break;
        case 'promaterial':
            break;
        case 'peo':
        case 'so':
            contentHtml += `<pre class="ann-body" style="word-break: break-word"><b>${data[`${type}_title`]}</b></pre>
                            <pre class="ann-body" style="word-break: break-word">${data[`${type}_description`]}</pre>
                            <pre class="ann-body" style="word-break: break-word">${data[`${type}_subdescription`]}</pre>`;
            break;
        default:
            // For new features, dynamically add all fields
            for (let key in data) {
                if (key !== `${type}_id` && key !== 'created_datetime' && key !== 'expiration_datetime' 
                    && key !== 'display_time' && key !== 'status' && key !== 'isCancelled' 
                    && key !== 'tv_id' && key !== `${type}_author_id` && key !== 'type' && key !== 'department' 
                    && key !== 'user_type' && key !== 'category' && key !== 'evaluated_by' && key !== 'evaluated_message' && key !== 'author_name') {
                        contentHtml += `<p>${data[key]}</p>`;
                }
            }
    }

    contentHtml += `
            <div class="line-separator"></div>
            <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.author_name || 'Unknown'} on ${formattedCreatedDateTime}</small></p>
            <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDateTime}</small></p>
            <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs</p>
        </div>
    `;

    return contentHtml;
};

// Function to update UI for announcement, event, news, etc.
const updateUI = (data, type) => {
    if (data.status === 'Pending' || data.status === 'Draft' || data.isCancelled === 1 || !data[`${type}_id`]) return;

    const existingDiv = document.querySelector(`[data-${type}-id="${data[`${type}_id`]}"]`);
    const formattedCreatedDateTime = formatDate(data.created_datetime);
    const formattedExpirationDateTime = formatDate(data.expiration_datetime);

    let mediaContent = '';
    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">` :
            isVideo ? `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/${type}_media/${data.media_path}" type="video/mp4"></video>` : '';
    }

    const contentDiv = createContentDiv(data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, type);
    
    if (existingDiv) {
        existingDiv.querySelector('.content-container-con').innerHTML = contentDiv;
    } else {
        const containerDiv = document.createElement('div');
        const buttonContainer = document.createElement('div');
        containerDiv.dataset[`${type}Id`] = data[`${type}_id`];
        containerDiv.setAttribute(`data-${type}-id`, data[`${type}_id`]);
        containerDiv.innerHTML = contentDiv;

        containerDiv.style = `
            background: #dffce5;
            height: auto;
            width: auto;
            margin-bottom: 10px;
            margin-right: 8px;
            border-radius: 15px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15px;
        `;

        contentDiv.style = `
            border: none;
            border-radius: 15px;
            padding: 20px;;
            color: black;
            height: auto;
            text-align: left;
            bottom: 0;
        `;

        buttonContainer.style = `
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        `;

        const deleteButton = createDeleteButton('Delete', 'fa fa-trash', () => showDeleteModal(data[`${type}_id`], type, data[`${type}_author_id`], full_name));
        const archiveButton = createArchiveButton('Archive', 'fa fa-archive', () => showArchiveModal(data[`${type}_id`], type));
        const editButton = createEditButton('Edit', 'fa fa-pencil-square', () => window.location.href = `edit_${type}.php?${type}_id=${data[`${type}_id`]}?=${data[`${type}_author_id`]}`);

        if (userType === 'Super Admin' || (userType === 'Admin' && data[`user_type`] !== 'Super Admin') || data[`${type}_author_id`] === parseInt(user_id)) {
            buttonContainer.appendChild(deleteButton);
            buttonContainer.appendChild(archiveButton);
            buttonContainer.appendChild(editButton);
        }
        // Append the button container to the main container
        containerDiv.appendChild(buttonContainer);

        document.getElementById(`${type}CarouselContainer`).insertBefore(containerDiv, document.getElementById(`${type}CarouselContainer`).firstChild);
    }
};

// Function to archive an item
const archiveItem = (type, id) => {
    const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
    const modal = document.getElementById(`confirmArchive${capitalizedType}Modal`);
    const data = {
        action: 'archive',
        type: type,
        [`${type}_id`]: id // Dynamically set the ID based on the type
    };
    console.log(data);
    modal.style.display = 'none';
    Ws.send(JSON.stringify(data)); // Send the data to the WebSocket server
};

// Function to delete an item
const deleteItem = (type, id, author_id, full_name) => {
    const evaluatorMessageElement = document.getElementById('evaluator_message');
    let evaluatorMessage = null;

    if (evaluatorMessageElement) {
        evaluatorMessage = evaluatorMessageElement.value;
        if (!evaluatorMessage) {
            alert('Evaluator message is required.');
            return;
        }
    }

    const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
    const modal = document.getElementById(`confirmDelete${capitalizedType}Modal`);
    const data = {
        action: 'delete_content',
        type: type,
        [`${type}_id`]: id, // Dynamically set the ID based on the type
        user_id: user_id,
        author_id: author_id,
        evaluator_name: full_name,
        evaluator_message: evaluatorMessage
    };
    console.log("Deleted Content Data: ", data);
    modal.style.display = 'none';
    Ws.send(JSON.stringify(data)); // Send the data to the WebSocket server
};

// Function to display a "No items to be displayed" message
const displayNoMessage = (type) => {
    const messageDiv = document.createElement('div');
    messageDiv.id = `no-${type}-message`;
    messageDiv.style = `
        text-align: center;
        font-size: 1.2em;
        color: #666;
        margin-top: 5px;
    `;
    if (type === 'promaterial') {
        messageDiv.textContent = `No promotional materials to be displayed`;
    } else if (type === 'peo') {
        messageDiv.textContent = `No Program Education Objective (PEO) to be displayed`;
    } else if (type === 'so') {
        messageDiv.textContent = `No Student Outcome (SO) to be displayed`;
    } else {
        messageDiv.textContent = `No ${type} to be displayed`;
    }
    
    const carouselContainer = document.getElementById(`${type}CarouselContainer`);
    if (carouselContainer) {
        carouselContainer.appendChild(messageDiv);
    } else {
        console.error(`Carousel container for ${type} not found.`);
    }
};

// Array of default content types
const defaultContentTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];

// Function to create modals dynamically
const createModals = (contentTypes) => {
    const modalContainer = document.createElement('div');
    modalContainer.id = 'dynamicModals';

    contentTypes.forEach(type => {
        const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
        
        // Archive Modal
        const archiveModal = document.createElement('div');
        archiveModal.id = `confirmArchive${capitalizedType}Modal`;
        archiveModal.className = 'modal';
        archiveModal.innerHTML = `<div id="archive${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(archiveModal);

        // Delete Modal
        const deleteModal = document.createElement('div');
        deleteModal.id = `confirmDelete${capitalizedType}Modal`;
        deleteModal.className = 'modal';
        deleteModal.innerHTML = `<div id="delete${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(deleteModal);
    });

    document.body.appendChild(modalContainer);
};

// Function to show archive modal
const showArchiveModal = (id, type) => {
    const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
    const modal = document.getElementById(`confirmArchive${capitalizedType}Modal`);
    const modalContent = document.getElementById(`archive${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    
    modalContent.innerHTML = `
        <div class="yellow-bar-vertical">
            <span class="close" onclick="document.getElementById('confirmArchive${capitalizedType}Modal').style.display='none'" style="color: #dc7d09"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #dc7d09; font-size: 50px"><i class="fa fa-archive" aria-hidden="true"></i></h1>
            <p id="deleteMessage" style="text-align: center">Proceed to archive?</p>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" onclick="document.getElementById('confirmArchive${capitalizedType}Modal').style.display='none'">Cancel</button>
                <button type="button" class="yellow-button" onclick="archiveItem('${type}', '${id}')">Yes, Archive</button>
            </div>
        </div>
    `;
    
    modal.style.display = 'flex';
};

// Function to show delete modal
const showDeleteModal = (id, type, author_id, full_name) => {
    const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
    const modal = document.getElementById(`confirmDelete${capitalizedType}Modal`);
    const modalContent = document.getElementById(`delete${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    modalContent.style = `
        padding: 15px;
    `;

    const evaluatorMessageHtml = user_id != author_id ? `
        <div class="floating-label-container">
            <textarea name="evaluator_message" id="evaluator_message" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
            <label for="evaluator_message" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User (required)</label>
        </div>
        <br>
    ` : '';
    
    modalContent.innerHTML = `
        <span class="close" onclick="document.getElementById('confirmDelete${capitalizedType}Modal').style.display='none'" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
        <p id="deleteMessage" style="text-align: center">Proceed to delete?</p>
        <br>
        ${evaluatorMessageHtml}
        <div style="text-align: right;">
            <button type="button" class="grey-button" onclick="document.getElementById('confirmDelete${capitalizedType}Modal').style.display='none'">Cancel</button>
            <button type="button" class="red-button" onclick="deleteItem('${type}', '${id}', '${author_id}', '${full_name}')">Yes, Delete</button>
        </div>
    `;
    
    modal.style.display = 'flex';
};

// Function to fetch feature information
const getFeatureInfo = async (type) => {
    try {
        const response = await fetch(`database/fetch_feature_info.php?type=${type}`);
        const featureInfo = await response.json();
        return featureInfo;
    } catch (error) {
        console.error(`Error fetching feature info for ${type}:`, error);
        return null;
    }
};

// Function to fetch and update content for all types
function fetchAndUpdate(type) {
    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId');

    // If we're not on a TV contents page, don't try to update the UI
    if (!tvId) return;

    fetch(`database/fetch_content.php?type=${type}&tvId=${tvId}`)
        .then(response => response.json())
        .then(data => {
            let filteredData;
            if (Array.isArray(data)) {
                filteredData = data.filter(item => {
                    return item.tv_id === parseInt(tvId, 10) && item.isCancelled === 0 && item.status === 'Approved';
                });
            } else if (typeof data === 'object' && data !== null) {
                // If data is a single object, wrap it in an array
                filteredData = [data].filter(item => {
                    return item.tv_id === parseInt(tvId, 10) && item.isCancelled === 0 && item.status === 'Approved';
                });
            } else {
                console.error(`Unexpected data format for ${type}:`, data);
                return;
            }

            const carouselContainer = document.getElementById(`${type}CarouselContainer`);
            if (carouselContainer) {
                carouselContainer.innerHTML = '';
                if (filteredData.length === 0) {
                    displayNoMessage(type);
                } else {
                    filteredData.forEach(item => updateUI(item, type));
                }
            }
        })
        .catch(error => {
            console.error(`Error fetching ${type} of TV ${tvId}:`, error);
        });
}

/* Web-Socket Server Connection */
Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    switch (data.action) {
        case 'delete_content':
            // Handle deletion confirmation
            const deleteMessageDiv = document.getElementById(`no-${data.type}-message`);
            if (deleteMessageDiv) {
                deleteMessageDiv.remove(); // Remove the "No items" message if it exists
            }
            const deletedDiv = document.querySelector(`[data-${data.type}-id="${data[`${data.type}_id`]}"]`);
            if (deletedDiv) {
                deletedDiv.remove(); // Remove the deleted item from the UI
            }
            console.log(`${data.type} with ID ${data[`${data.type}_id`]} has been deleted.`);
            break;

        case 'archive':
            // Handle archiving confirmation
            const archivedDiv = document.querySelector(`[data-${data.type}-id="${data[`${data.type}_id`]}"]`);
            if (archivedDiv) {
                archivedDiv.remove(); // Remove the archived item from the UI
            }
            console.log(`${data.type} with ID ${data[`${data.type}_id`]} has been archived.`);
            break;

        case 'update':
            // Handle updates to existing items
            const updatedDiv = document.querySelector(`[data-${data.type}-id="${data[`${data.type}_id`]}"]`);
            if (updatedDiv) {
                // Update the content of the existing item
                fetchAndUpdate(data.type);
                updatedDiv.querySelector('.content-container-con').innerHTML = createContentDiv(data, /* mediaContent */ '', formatDate(data.created_datetime), formatDate(data.expiration_datetime));
            } else {
                fetchAndUpdate(data.type);
                console.log(`Received update for non-existing ${data.type} with ID ${data[`${data.type}_id`]}.`);
            }
            break;

        case 'unarchive':
            // Handle unarchiving confirmation
            console.log(`${data.type} with ID ${data[`${data.type}_id`]} has been unarchived.`);
            fetchAndUpdate(data.type);
            break;
        
        case 'unarchive_and_update_expiration':
            // Handle unarchiving confirmation
            console.log(`${data.type} with ID ${data[`${data.type}_id`]} has been unarchived.`);
            fetchAndUpdate(data.type);
            break;

        case 'post_content':
            // Handle new content being posted
            console.log(`New ${data.type} posted:`, data);
            fetchAndUpdate(data.type);
            break;
        
        case 'approve_post':
            // Handle new approved content being posted
            if (data.content_type) {
                console.log(`New Approved ${data.content_type} posted:`, data);
                fetchAndUpdate(data.content_type);
            } else if (data.type) {
                console.log(`New Approved ${data.type} posted:`, data);
                fetchAndUpdate(data.type);
            }
            break;
    }
});

// Call fetchAndUpdate for each type
document.addEventListener('DOMContentLoaded', () => {
    // Get all content types from the DOM
    const contentTypes = Array.from(document.querySelectorAll('.content-container'))
        .map(container => container.id.replace('List', ''));
    
    // Create modals for all content types
    createModals(contentTypes);

    // Fetch and update content for each type
    contentTypes.forEach(type => fetchAndUpdate(type));
});