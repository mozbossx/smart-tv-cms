const Ws = new WebSocket('ws://192.168.1.13:8081');

// Function to format date to "MM DD YYYY"
const formatDate = (dateString) => {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: '2-digit' };
    return date.toLocaleDateString('en-US', options);
};

// Function to format time to "01:00pm"
const formatTime = (timeString) => {
    const time = new Date(`1970-01-01T${timeString}Z`);
    const options = { hour: '2-digit', minute: '2-digit' };
    return time.toLocaleTimeString('en-US', options).replace(/(:\d{2})$/, '').toLowerCase();
};

// Utility function to create buttons
const createButton = (text, iconClass, onClick) => {
    const button = document.createElement('button');
    button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
    button.style = `
        background-color: #316038;
        color: #fff;
        padding: 8px 16px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s;
        margin-top: 10px;
        margin-right: 5px;
    `;
    button.onclick = onClick;
    return button;
};

// Function to create content div
const createContentDiv = (data, mediaContent, formattedCreatedDate, formattedCreatedTime, formattedExpirationDate, formattedExpirationTime, type) => {
    if (type === 'peo' || type === 'so') {
        return `
            <div class="content-container-con">
                ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                <pre class="ann-body" style="word-break: break-word">${data[`${type}_body`]}</pre>
                <div class="line-separator"></div>
                <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data[`${type}_author_id`]} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs</p>
            </div>
        `;
    } else {
        return `
            <div class="content-container-con">
                ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                <pre class="ann-body" style="word-break: break-word">${data[`${type}_body`]}</pre>
                <div class="line-separator"></div>
                <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data[`${type}_author_id`]} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs</p>
                </div>
            `;
    }
};

// Function to update UI for announcement, event, news, etc.
const updateUI = (data, type) => {
    if (data.status === 'Pending' || data.status === 'Draft' || data.isCancelled === 1 || !data[`${type}_id`]) return;

    const existingDiv = document.querySelector(`[data-${type}-id="${data[`${type}_id`]}"]`);
    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    const formattedExpirationDate = formatDate(data.expiration_date);
    const formattedExpirationTime = formatTime(data.expiration_time);

    let mediaContent = '';
    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">` :
            isVideo ? `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/${type}_media/${data.media_path}" type="video/mp4"></video>` : '';
    }

    const contentDiv = createContentDiv(data, mediaContent, formattedCreatedDate, formattedCreatedTime, formattedExpirationDate, formattedExpirationTime, type);
    
    if (existingDiv) {
        existingDiv.querySelector('.content-container-con').innerHTML = contentDiv;
    } else {
        const containerDiv = document.createElement('div');
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

        const deleteButton = createButton('Delete', 'fa fa-trash', () => showDeleteModal(data[`${type}_id`], type));
        const archiveButton = createButton('Archive', 'fa fa-archive', () => showArchiveModal(data[`${type}_id`], type));
        const editButton = createButton('Edit', 'fa fa-pencil-square', () => window.location.href = `edit_${type}.php?${type}_id=${data[`${type}_id`]}?=${data[`${type}_author_id`]}`);

        if (userType !== 'Student' && userType !== 'Faculty' || data[`${type}_author_id`] === userId) {
            containerDiv.appendChild(deleteButton);
            containerDiv.appendChild(archiveButton);
            containerDiv.appendChild(editButton);
        }

        document.getElementById(`${type}CarouselContainer`).insertBefore(containerDiv, document.getElementById(`${type}CarouselContainer`).firstChild);
    }
};

// Function to show the archive modal
const showArchiveModal = (id, type) => {
    // Implement the logic to show the archive modal
    insertArchiveModalContent(type); // Assuming you have a function to insert modal content
    const modal = document.getElementById(`confirmArchive${capitalizeFirstLetter(type)}Modal`);
    const archiveButton = document.getElementById(`archive${capitalizeFirstLetter(type)}Button`);
    const closeButton = document.getElementById(`closeArchive${capitalizeFirstLetter(type)}ModalButton`);

    modal.style.display = 'flex';

    archiveButton.onclick = () => {
        archiveItem(type, id); // Assuming you have a function to handle archiving
        modal.style.display = 'none';
    };

    closeButton.onclick = () => {
        modal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };
};

// Function to show the delete modal
const showDeleteModal = (id, type) => {
    // Implement the logic to show the delete modal
    insertDeleteModalContent(type); // Assuming you have a function to insert modal content
    const modal = document.getElementById(`confirmDelete${capitalizeFirstLetter(type)}Modal`);
    const deleteButton = document.getElementById(`delete${capitalizeFirstLetter(type)}Button`);
    const closeButton = document.getElementById(`close${capitalizeFirstLetter(type)}ModalButton`);

    modal.style.display = 'flex';

    deleteButton.onclick = () => {
        deleteItem(type, id); // Assuming you have a function to handle deletion
        modal.style.display = 'none';
    };

    closeButton.onclick = () => {
        modal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };
};

// Helper function to capitalize the first letter of a string
const capitalizeFirstLetter = (string) => {
    return string.charAt(0).toUpperCase() + string.slice(1);
};

// Function to insert content into the archive modal
const insertArchiveModalContent = (type) => {
    const modalContent = document.getElementById(`archive${capitalizeFirstLetter(type)}ModalContent`);
    if (modalContent) {
        let archiveMessage = '';

        if (type === 'peo') {
            archiveMessage = 'Proceed to archive this Program Education Objective (PEO)?';
        } else if (type === 'so') {
            archiveMessage = 'Proceed to archive this Student Outcome (SO)?';
        } else if (type === 'promaterial') {
            archiveMessage = 'Proceed to archive this promotional material?';
        } else {
            archiveMessage = `Proceed to archive this ${type}?`;
        }

        modalContent.innerHTML = `
            <div class="modal-content">
                <div class="red-bar-vertical">
                    <span class="close" id="closeArchive${capitalizeFirstLetter(type)}ModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                    <br>
                    <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-archive" aria-hidden="true"></i></h1>
                    <p id="archiveMessage" style="text-align: center">${archiveMessage}</p>
                    <br>
                    <div style="text-align: right;">
                        <button id="archive${capitalizeFirstLetter(type)}Button" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, archive ${type}</b></button>
                    </div>
                </div>
            </div>
        `;
    }
};

// Function to insert content into the delete modal
const insertDeleteModalContent = (type) => {
    const modalContent = document.getElementById(`delete${capitalizeFirstLetter(type)}ModalContent`);
    if (modalContent) {
        let deleteMessage = '';

        if (type === 'peo') {
            deleteMessage = 'Proceed to delete this Program Education Objective (PEO)?';
        } else if (type === 'so') {
            deleteMessage = 'Proceed to delete this Student Outcome (SO)?';
        } else if (type === 'promaterial') {
            deleteMessage = 'Proceed to delete this promotional material?';
        } else {
            deleteMessage = `Proceed to delete this ${type}?`;
        }
        modalContent.innerHTML = `
            <div class="modal-content">
                <div class="red-bar-vertical">
                    <span class="close" id="close${capitalizeFirstLetter(type)}ModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                    <br>
                    <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
                    <p id="deleteMessage" style="text-align: center">${deleteMessage}?</p>
                    <br>
                    <div style="text-align: right;">
                        <button id="delete${capitalizeFirstLetter(type)}Button" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, delete ${type}</b></button>
                    </div>
                </div>
            </div>
        `;
    }
};

// Function to archive an item
const archiveItem = (type, id) => {
    const data = {
        action: 'archive',
        type: type,
        [`${type}_id`]: id // Dynamically set the ID based on the type
    };
    console.log(data);
    Ws.send(JSON.stringify(data)); // Send the data to the WebSocket server
};

// Function to delete an item
const deleteItem = (type, id) => {
    const data = {
        action: 'delete',
        type: type,
        [`${type}_id`]: id // Dynamically set the ID based on the type
    };
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

const updateAnnouncementUI = (data) => updateUI(data, 'announcement');
const updateEventUI = (data) => updateUI(data, 'event');
const updateNewsUI = (data) => updateUI(data, 'news');
const updatePromaterialUI = (data) => updateUI(data, 'promaterial');
const updatePEOUI = (data) => updateUI(data, 'peo');
const updateSOUI = (data) => updateUI(data, 'so');

// Fetch and update functions for different content types
const fetchAndUpdate = (type) => {
    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId'); // Get the tvId from the URL

    fetch(`database/fetch_${type}.php`)
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(item => 
                item.status === 'Approved' &&
                item.tv_id === parseInt(tvId, 10) &&
                item.isCancelled === 0
            );
            const carouselContainer = document.getElementById(`${type}CarouselContainer`);
            if (carouselContainer) {
                carouselContainer.innerHTML = ''; // Only set innerHTML if the element exists
            } else {
                console.error(`Element with ID ${type}CarouselContainer not found.`);
            }

            if (filteredData.length === 0) {
                displayNoMessage(type);
            } else {
                filteredData.forEach(item => {
                    if (type === 'announcement') updateAnnouncementUI(item);
                    else if (type === 'event') updateEventUI(item);
                    else if (type === 'news') updateNewsUI(item);
                    else if (type === 'promaterial') updatePromaterialUI(item);
                    else if (type === 'peo') updatePEOUI(item);
                    else if (type === 'so') updateSOUI(item);
                });
            }
        })
        .catch(error => console.error(`Error fetching ${type}:`, error));
};

/* Web-Socket Server Connection */
Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    switch (data.action) {
        case 'delete':
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
                updatedDiv.querySelector('.content-container-con').innerHTML = createContentDiv(data, /* mediaContent */ '', formatDate(data.created_date), formatTime(data.created_time), formatDate(data.expiration_date), formatTime(data.expiration_time));
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

        default:
            console.warn(`Unknown action: ${data.action}`);
            break;
    }
});

// Call fetchAndUpdate for each type
document.addEventListener('DOMContentLoaded', () => {
    ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'].forEach(type => fetchAndUpdate(type));
});
