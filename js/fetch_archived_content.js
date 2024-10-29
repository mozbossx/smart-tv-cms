function formatDateTime(dateTimeString) {
    const dateTime = new Date(dateTimeString);
    const dateOptions = {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    };
    const timeOptions = {
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    };
    const formattedDate = new Intl.DateTimeFormat('en-US', dateOptions).format(dateTime);
    const formattedTime = new Intl.DateTimeFormat('en-US', timeOptions).format(dateTime);
    return `${formattedDate} (${formattedTime})`;
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

const createRestoreButton = (text, iconClass, onClick) => {
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
const createContentDiv = (data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, type, tvDisplay) => {
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
            <p class="ann-author" style="color: #6E6E6E; margin-top: 10px"><small>Posted by ${data.author_name || 'Unknown'} on ${formattedCreatedDateTime}</small></p>
            <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDateTime}</small></p>
            <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs  |  <i class="fa fa-tv" aria-hidden="true" style="margin-left: 5px"></i> ${tvDisplay}</p>
        </div>
    `;

    return contentHtml;
};

// Function to update UI for announcement, event, news, etc.
const updateUI = async (data, type) => {
    if (data.status === 'Pending' || data.status === 'Draft' || data.isCancelled === 0 || !data[`${type}_id`]) return;

    const tvInfo = await fetchTVInfo(type, data[`tv_id`]);
    console.log(tvInfo);

    const existingDiv = document.querySelector(`[data-${type}-id="${data[`${type}_id`]}"]`);
    const formattedCreatedDateTime = formatDateTime(data.created_datetime);
    const formattedExpirationDateTime = formatDateTime(data.expiration_datetime);

    let mediaContent = '';
    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">` :
            isVideo ? `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/${type}_media/${data.media_path}" type="video/mp4"></video>` : '';
    }

    const contentDiv = createContentDiv(data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, type, tvInfo.tv_display);
    
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
        const restoreButton = createRestoreButton('Restore', 'fa fa-refresh', () => showRestoreModal(data[`${type}_id`], type, data[`${type}_author_id`], full_name, data.tv_id, data.expiration_datetime));
        const editButton = createEditButton('Edit', 'fa fa-pencil-square', () => window.location.href = `edit_${type}.php?${type}_id=${data[`${type}_id`]}?=${data[`${type}_author_id`]}`);

        if (userType === 'Super Admin' || (userType === 'Admin' && data[`user_type`] !== 'Super Admin') || data[`${type}_author_id`] === parseInt(user_id)) {
            buttonContainer.appendChild(deleteButton);
            buttonContainer.appendChild(restoreButton);
            buttonContainer.appendChild(editButton);
        }

        // Append the button container to the main container
        containerDiv.appendChild(buttonContainer);

        document.getElementById(`${type}CarouselContainer`).insertBefore(containerDiv, document.getElementById(`${type}CarouselContainer`).firstChild);
    }
};

const fetchTVInfo = async (type, tv_id) => {
    try {
        const response = await fetch(`database/fetch_tv_info.php?type=${type}&tvId=${tv_id}`);
        return await response.json();
    } catch (error) {
        console.error('Error fetching TV info:', error);
        return { tv_display: 'Error fetching TV info' };
    }
};

// Function to restore an item
const restoreItem = (type, id, author_id, full_name, tv_id, isExpired) => {
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
    const modal = document.getElementById(`confirmRestore${capitalizedType}Modal`);
    
    let action = 'restore_content';
    let data = {
        action: action,
        type: type,
        [`${type}_id`]: id,
        user_id: user_id,
        author_id: author_id,
        evaluator_name: full_name,
        evaluator_message: evaluatorMessage,
        content_tv_id: tv_id
    };

    if (isExpired) {
        const newExpirationDateTime = document.getElementById('new_expiration_datetime').value;
        if (!newExpirationDateTime) {
            alert('New expiration date and time is required.');
            return;
        }
        
        // Check if the new expiration date is in the past
        const newExpirationDate = new Date(newExpirationDateTime);
        const currentDate = new Date();
        if (newExpirationDate < currentDate) {
            const errorModal = document.getElementById('errorModalVersion2');
            const errorMessage = document.getElementById('errorTextVersion2');
            errorModal.style.display = 'flex';
            errorMessage.textContent = 'The new expiration date cannot be in the past.';
            return;
        }

        action = 'restore_content_and_update_expiration';
        data.action = action;
        data.expiration_datetime = newExpirationDateTime;
    }

    console.log("Restored Content Data: ", data);
    modal.style.display = 'none';
    Ws.send(JSON.stringify(data));
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
        
        // Restore Modal
        const restoreModal = document.createElement('div');
        restoreModal.id = `confirmRestore${capitalizedType}Modal`;
        restoreModal.className = 'modal';
        restoreModal.innerHTML = `<div id="restore${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(restoreModal);

        // Delete Modal
        const deleteModal = document.createElement('div');
        deleteModal.id = `confirmDelete${capitalizedType}Modal`;
        deleteModal.className = 'modal';
        deleteModal.innerHTML = `<div id="delete${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(deleteModal);
    });

    document.body.appendChild(modalContainer);
};

// Function to show restore modal
const showRestoreModal = (id, type, author_id, full_name, tv_id, expirationDateTime) => {
    const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
    const modal = document.getElementById(`confirmRestore${capitalizedType}Modal`);
    const modalContent = document.getElementById(`restore${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    modalContent.style = `
        padding: 15px;
    `;

    const currentDate = new Date();
    const expirationDate = new Date(expirationDateTime);
    const isExpired = expirationDate < currentDate;

    const evaluatorMessageHtml = user_id != author_id ? `
        <div class="floating-label-container">
            <textarea name="evaluator_message" id="evaluator_message" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
            <label for="evaluator_message" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User (required)</label>
        </div>
        <br>
    ` : '';

    const expirationDateHtml = isExpired ? `
        <div class="floating-label-container">
            <input type="datetime-local" id="new_expiration_datetime" name="new_expiration_datetime" required class="floating-label-input">
            <label for="new_expiration_datetime" class="floating-label">New Expiration Date and Time</label>
        </div>
        <br>
    ` : '';
    
    modalContent.innerHTML = `
        <span class="close" onclick="document.getElementById('confirmRestore${capitalizedType}Modal').style.display='none'" style="color: #dc7d09"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="color: #dc7d09; font-size: 50px"><i class="fa fa-arrow-circle-o-up" aria-hidden="true"></i></h1>
        <p id="deleteMessage" style="text-align: center">Proceed to restore?</p>
        <br>
        ${evaluatorMessageHtml}
        ${expirationDateHtml}
        <div style="text-align: right;">
            <button type="button" class="grey-button" onclick="document.getElementById('confirmRestore${capitalizedType}Modal').style.display='none'">Cancel</button>
            <button type="button" class="yellow-button" onclick="restoreItem('${type}', '${id}', '${author_id}', '${full_name}', '${tv_id}', ${isExpired})">Yes, Restore</button>
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
const fetchAndUpdate = async (type) => {
    try {
        let requireApproval = true; // Default to requiring approval

        // Only check for require_approval if it's not a default content type
        if (!defaultContentTypes.includes(type)) {
            const featureInfo = await getFeatureInfo(type);
            if (featureInfo) {
                requireApproval = featureInfo.require_approval === 'yes';
            }
        }

        const response = await fetch(`database/fetch_content.php?type=${type}&archived=true`);
        const data = await response.json();

        const filteredData = data.filter(item => {
            const baseConditions = item.isCancelled === 1;
            if (type === 'peo' || type === 'so') {
                return baseConditions;
            } else {
                return requireApproval ? (baseConditions && item.status === 'Approved') : baseConditions;
            }
        });

        const carouselContainer = document.getElementById(`${type}CarouselContainer`);
        if (carouselContainer) {
            carouselContainer.innerHTML = '';
            if (filteredData.length === 0) {
                displayNoMessage(type);
            } else {
                filteredData.forEach(item => updateUI(item, type));
            }
        } else {
            console.error(`Element with ID ${type}CarouselContainer not found.`);
        }
    } catch (error) {
        console.error(`Error fetching ${type}:`, error);
    }
};

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

        case 'archive_content':
            // Handle unarchiving confirmation
            console.log(`${data.type} with ID ${data[`${data.type}_id`]} has been archived.`);
            fetchAndUpdate(data.type);
            break;
            

        case 'update':
            // Handle updates to existing items
            const updatedDiv = document.querySelector(`[data-${data.type}-id="${data[`${data.type}_id`]}"]`);
            if (updatedDiv) {
                // Update the content of the existing item
                fetchAndUpdate(data.type);
                updatedDiv.querySelector('.content-container-con').innerHTML = createContentDiv(data, /* mediaContent */ '', formatDateTime(data.created_datetime), formatDateTime(data.expiration_datetime));
            } else {
                fetchAndUpdate(data.type);
                console.log(`Received update for non-existing ${data.type} with ID ${data[`${data.type}_id`]}.`);
            }
            break;

        case 'restore_content':
            // Handle archiving confirmation
            const restoredDiv = document.querySelector(`[data-${data.type}-id="${data[`${data.type}_id`]}"]`);
            if (restoredDiv) {
                restoredDiv.remove(); // Remove the restored item from the UI
            }
            console.log(`${data.type} with ID ${data[`${data.type}_id`]} has been restored.`);
            break;
        
        case 'restore_content_and_update_expiration':
            // Handle unarchiving confirmation
            console.log(`${data.type} with ID ${data[`${data.type}_id`]} has been restored.`);
            fetchAndUpdate(data.type);
            break;

        case 'post_content':
            // Handle new content being posted
            console.log(`New ${data.type} posted:`, data);
            fetchAndUpdate(data.type);
            break;

        default:
            console.warn(`Unknown action: ${data.action}`);
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