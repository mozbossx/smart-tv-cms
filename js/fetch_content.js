const Ws = new WebSocket('ws://192.168.1.12:8081');
const annCarouselContainer = document.getElementById('annCarouselContainer');
const eveCarouselContainer = document.getElementById('eveCarouselContainer');
const newsCarouselContainer = document.getElementById('newsCarouselContainer');
const promaterialsCarouselContainer = document.getElementById('promaterialsCarouselContainer');
const peoCarouselContainer = document.getElementById('peoCarouselContainer');
const soCarouselContainer = document.getElementById('soCarouselContainer');

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

/* Announcement Functions ====================================================================== */
// 1. updateAnnouncementUI
const updateAnnouncementUI = (data) => {
    // Check if the status is 'Pending' and return early if true
    if (data.status === 'Pending' || data.isCancelled === 1 || !data.announcements_id) {
        return;
    }

    // Check if the announcement already exists in the DOM
    const existingAnnouncementDiv = document.querySelector(`[data-ann-id="${data.announcements_id}"]`);

    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    const formattedExpirationDate = formatDate(data.expiration_date);
    const formattedExpirationTime = formatTime(data.expiration_time);
    
    if (existingAnnouncementDiv) {
        // Update the existing announcement
        const contentDiv = existingAnnouncementDiv.querySelector('.content-container-con');
        if (contentDiv) {
            let mediaContent = '';
            if (data.media_path) {
                // Determine media type based on file extension
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        
                if (isImage) {
                    mediaContent = `<img src="servers/announcements_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">`;
                } else if (isVideo) {
                    mediaContent = `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/announcements_media/${data.media_path}" type="video/mp4"></video>`;
                }
            }
            
            contentDiv.innerHTML = `
                <div class="content-container-con">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <pre class="ann-body" style="word-break: break-word">${data.ann_body}</pre>
                    <div class="line-separator"></div>
                    <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.announcements_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                    <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                    <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                </div>
            `;
        }
    } else {
        const containerDiv = document.createElement('div');
        const contentDiv = document.createElement('div');
        const postDiv = document.createElement('div');
        const deleteButton = document.createElement('button');
        const archiveButton = document.createElement('button');
        const editButton = document.createElement('button');

        containerDiv.style = `
            height: auto;
            width: auto;
            margin-bottom: 5px;
            margin-right: 8px;
            border: black 1px solid;
            border-radius: 5px;
        `;

        // const isOdd = annCarouselContainer.children.length % 2 !== 0;

        contentDiv.style = `
            background-color: #dffce5;
            border: none;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            padding: 10px;
            color: black;
            height: auto;
            text-align: left;
            bottom: 0;
        `;

        postDiv.style = `
            background-color: #264b2b;
            border: none;
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
            padding: 5px;
            color: black;
            height: auto;
            text-align: right;
        `;

        deleteButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-top: 5px;
        `;

        archiveButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-left: 7px;
        `;

        editButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-left: 7px;
        `;

        if (data.category == "Announcement") {
            containerDiv.dataset.annId = data.announcements_id;
            containerDiv.setAttribute('data-ann-id', data.announcements_id);
            // Check if the announcement has media
            if (data.media_path) {
                // Determine media type based on file extension
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        
                if (isImage) {
                    mediaContent = `<img src="servers/announcements_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">`;
                } else if (isVideo) {
                    mediaContent = `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/announcements_media/${data.media_path}" type="video/mp4"></video>`;
                }
        
                contentDiv.innerHTML = `
                    <div class="content-container-con">
                        ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                        <pre class="ann-body" style="word-break: break-word">${data.ann_body}</pre>
                        <div class="line-separator"></div>
                        <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.announcements_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                        <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                    </div>
                `;
            } else {
                contentDiv.innerHTML = `
                    <div class="content-container-con">
                        <pre class="ann-body" style="word-break: break-word">${data.ann_body}</pre>
                        <div class="line-separator"></div>
                        <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.announcements_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                        <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                    </div>
                `;
            }

            deleteButton.innerHTML  = '<i class="fa fa-trash" aria-hidden="true"></i> Delete';
            deleteButton.onclick = () => showDeleteAnnModal(data.announcements_id);

            archiveButton.innerHTML  = '<i class="fa fa-archive" aria-hidden="true"></i> Archive';
            archiveButton.onclick = () => showArchiveAnnModal(data.announcements_id);

            editButton.innerHTML  = '<i class="fa fa-pencil-square" aria-hidden="true"></i> Edit';
            editButton.onclick = () => {
                window.location.href = `edit_announcement.php?announcements_id=${data.announcements_id}?=${data.announcements_author}`;
            };

            if (userType !== 'Student' && userType !== 'Faculty' || data.announcements_author === full_name) {
                contentDiv.appendChild(deleteButton);
                contentDiv.appendChild(archiveButton);
                contentDiv.appendChild(editButton);
            }
        }
        
        postDiv.innerHTML = `
            
        `;

        // Append 'contentDiv' and 'postDiv' to 'containerDiv'
        containerDiv.appendChild(contentDiv);
        containerDiv.appendChild(postDiv);

        annCarouselContainer.insertBefore(containerDiv, annCarouselContainer.firstChild);
    }
};

// 2. insertArchiveAnnModalContent
const insertArchiveAnnModalContent = () => {
    const modalContainer = document.getElementById('confirmArchiveAnnouncementModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeArchiveAnnouncementModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-archive" aria-hidden="true"></i></h1>
                <p id="archiveMessage" style="text-align: center">Proceed to archive this announcement?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelArchiveAnnouncementButton" class="cancel-button" type="button">Cancel</button>
                    <button id="archiveAnnouncementButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to archive</b></button>
                </div>
            </div>
        </div>
    `;
}

// 3. showArchiveAnnModal
const showArchiveAnnModal = (annId) => {
    insertArchiveAnnModalContent();
    const modal = document.getElementById('confirmArchiveAnnouncementModal');
    const archiveAnnouncementButton = document.getElementById('archiveAnnouncementButton');
    const cancelArchiveAnnouncementButton = document.getElementById('cancelArchiveAnnouncementButton');
    const closeArchiveAnnouncementModalButton = document.getElementById('closeArchiveAnnouncementModalButton');

    modal.style.display = 'flex';

    archiveAnnouncementButton.onclick = () => {
        archiveAnnouncement('ann', annId);
        modal.style.display = 'none';
    };

    cancelArchiveAnnouncementButton.onclick = () => {
        modal.style.display = 'none';
    }

    closeArchiveAnnouncementModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 4. archiveAnnouncement
const archiveAnnouncement = (type, id) => {
    const data = {
        action: 'archive',
        type: 'announcement',
        announcements_id : id
    };
    Ws.send(JSON.stringify(data));
};

// 5. insertDeleteAnnModalContent
const insertDeleteAnnModalContent = () => {
    const modalContainer = document.getElementById('confirmDeleteAnnouncementModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeAnnouncementModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Proceed to delete this announcement?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelAnnouncementButton" class="cancel-button" type="button">Cancel</button>
                    <button id="deleteAnnouncementButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

// 6. showDeleteAnnModal
const showDeleteAnnModal = (annId) => {
    insertDeleteAnnModalContent();
    const modal = document.getElementById('confirmDeleteAnnouncementModal');
    const deleteAnnouncementButton = document.getElementById('deleteAnnouncementButton');
    const cancelAnnouncementButton = document.getElementById('cancelAnnouncementButton');
    const closeAnnouncementModalButton = document.getElementById('closeAnnouncementModalButton');

    modal.style.display = 'flex';

    deleteAnnouncementButton.onclick = () => {
        deleteAnnouncement('ann', annId);
        modal.style.display = 'none';
    };

    cancelAnnouncementButton.onclick = () => {
        modal.style.display = 'none';
    }

    closeAnnouncementModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 7. deleteAnnouncement
const deleteAnnouncement = (type, id) => {
    const data = {
        action: 'delete',
        type: 'announcement',
        announcements_id : id
    };
    Ws.send(JSON.stringify(data));
};

// Function to display "No Announcement/s to be displayed" message
const displayNoAnnouncementsMessage = () => {
    const messageDiv = document.createElement('div');
    messageDiv.id = 'no-announcements-message';
    messageDiv.style = `
        text-align: center;
        font-size: 1.2em;
        color: #666;
        margin-top: 5px;
    `;
    messageDiv.textContent = 'No announcements to be displayed';
    annCarouselContainer.appendChild(messageDiv);
};

/* Event Functions ====================================================================== */
// 1. updateEventUI
const updateEventUI = (data) => {
    // Check if the status is 'Pending' and return early if true
    if (data.status === 'Pending' || data.isCancelled === 1 || !data.events_id) {
        return;
    }

    // Check if the event already exists in the DOM
    const existingEventDiv = document.querySelector(`[data-event-id="${data.events_id}"]`);

    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    const formattedExpirationDate = formatDate(data.expiration_date);
    const formattedExpirationTime = formatTime(data.expiration_time);
    
    if (existingEventDiv) {
        // Update the existing event
        const contentDiv = existingEventDiv.querySelector('.content-container-con');
        if (contentDiv) {
            let mediaContent = '';
            if (data.media_path) {
                // Determine media type based on file extension
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        
                if (isImage) {
                    mediaContent = `<img src="servers/events_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">`;
                } else if (isVideo) {
                    mediaContent = `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/events_media/${data.media_path}" type="video/mp4"></video>`;
                }
            }
            
            contentDiv.innerHTML = `
                <div class="content-container-con">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <pre class="eve-body" style="word-break: break-word"><b>${data.event_heading}</b></pre>
                    <p class="field-title">Event Location</p>
                    <pre class="eve-body" style="word-break: break-word"><small>${data.event_location}</small></pre>
                    <p class="field-title">Registration Link</p>
                    <pre class="eve-body" style="word-break: break-word"><small>${data.reg_link}</small></pre>
                    <div class="line-separator"></div>
                    <p class="eve-author" style="color: #6E6E6E"><small>Posted by ${data.event_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                    <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                    <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                </div>
            `;
        }
    } else {
        const containerDiv = document.createElement('div');
        const contentDiv = document.createElement('div');
        const postDiv = document.createElement('div');
        const deleteButton = document.createElement('button');
        const archiveButton = document.createElement('button');
        const editButton = document.createElement('button');

        containerDiv.style = `
            height: auto;
            width: auto;
            margin-bottom: 5px;
            margin-right: 8px;
            border: black 1px solid;
            border-radius: 5px;
        `;

        // const isOdd = eveCarouselContainer.children.length % 2 !== 0;

        contentDiv.style = `
            background-color: #dffce5;
            border: none;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            padding: 10px;
            color: black;
            height: auto;
            text-align: left;
            bottom: 0;
        `;

        postDiv.style = `
            background-color: #264b2b;
            border: none;
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
            padding: 5px;
            color: black;
            height: auto;
            text-align: right;
        `;

        deleteButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-top: 5px;
        `;

        archiveButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-left: 7px;
        `;

        editButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-left: 7px;
        `;

        if (data.category == "Event") {
            containerDiv.dataset.eventId = data.events_id;
            containerDiv.setAttribute('data-event-id', data.events_id);
            // Check if the event has media
            if (data.media_path) {
                // Determine media type based on file extension
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        
                if (isImage) {
                    mediaContent = `<img src="servers/events_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">`;
                } else if (isVideo) {
                    mediaContent = `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/events_media/${data.media_path}" type="video/mp4"></video>`;
                }
        
                contentDiv.innerHTML = `
                    <div class="content-container-con">
                        ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                        <pre class="eve-body" style="word-break: break-word"><b>${data.event_heading}</b></pre>
                        <p class="field-title">Event Location</p>
                        <pre class="eve-body" style="word-break: break-word"><small>${data.event_location}</small></pre>
                        <p class="field-title">Registration Link</p>
                        <pre class="eve-body" style="word-break: break-word"><small>${data.reg_link}</small></pre>
                        <div class="line-separator"></div>
                        <p class="eve-author" style="color: #6E6E6E"><small>Posted by ${data.event_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                        <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                    </div>
                `;
            } else {
                contentDiv.innerHTML = `
                    <div class="content-container-con">
                        <pre class="eve-body" style="word-break: break-word"><b>${data.event_heading}</b></pre>
                        <p class="field-title">Event Location</p>
                        <pre class="eve-body" style="word-break: break-word"><small>${data.event_location}</small></pre>
                        <p class="field-title">Registration Link</p>
                        <pre class="eve-body" style="word-break: break-word"><small>${data.reg_link}</small></pre>
                        <div class="line-separator"></div>
                        <p class="eve-author" style="color: #6E6E6E"><small>Posted by ${data.event_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                        <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                    </div>
                `;
            }

            deleteButton.innerHTML  = '<i class="fa fa-trash" aria-hidden="true"></i> Delete';
            deleteButton.onclick = () => showDeleteEventModal(data.events_id);

            archiveButton.innerHTML  = '<i class="fa fa-archive" aria-hidden="true"></i> Archive';
            archiveButton.onclick = () => showArchiveEventModal(data.events_id);

            editButton.innerHTML  = '<i class="fa fa-pencil-square" aria-hidden="true"></i> Edit';
            editButton.onclick = () => {
                window.location.href = `edit_event.php?events_id=${data.events_id}?=${data.event_author}`;
            };

            if (userType !== 'Student' && userType !== 'Faculty' || data.event_author === full_name) {
                contentDiv.appendChild(deleteButton);
                contentDiv.appendChild(archiveButton);
                contentDiv.appendChild(editButton);
            }
        }
        
        postDiv.innerHTML = `
            
        `;

        // Append 'contentDiv' and 'postDiv' to 'containerDiv'
        containerDiv.appendChild(contentDiv);
        containerDiv.appendChild(postDiv);

        eveCarouselContainer.insertBefore(containerDiv, eveCarouselContainer.firstChild);
    }
};

// 2. insertArchiveEventModalContent
const insertArchiveEventModalContent = () => {
    const modalContainer = document.getElementById('confirmArchiveEventModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeArchiveEventModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-archive" aria-hidden="true"></i></h1>
                <p id="archiveMessage" style="text-align: center">Proceed to archive this event?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelArchiveEventButton" class="cancel-button" type="button">Cancel</button>
                    <button id="archiveEventButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to archive</b></button>
                </div>
            </div>
        </div>
    `;
}

// 3. showArchiveEventModal
const showArchiveEventModal = (eventId) => {
    insertArchiveEventModalContent();
    const modal = document.getElementById('confirmArchiveEventModal');
    const archiveEventButton = document.getElementById('archiveEventButton');
    const cancelArchiveEventButton = document.getElementById('cancelArchiveEventButton');
    const closeArchiveEventModalButton = document.getElementById('closeArchiveEventModalButton');

    modal.style.display = 'flex';

    archiveEventButton.onclick = () => {
        archiveEvent('event', eventId);
        modal.style.display = 'none';
    };

    cancelArchiveEventButton.onclick = () => {
        modal.style.display = 'none';
    }

    closeArchiveEventModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 4. archiveEvent
const archiveEvent = (type, id) => {
    const data = {
        action: 'archive',
        type: 'event',
        events_id : id
    };
    Ws.send(JSON.stringify(data));
};

// 5. insertDeleteEventModalContent
const insertDeleteEventModalContent = () => {
    const modalContainer = document.getElementById('confirmDeleteEventModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeEventModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Proceed to delete this event?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelEventButton" class="cancel-button" type="button">Cancel</button>
                    <button id="deleteEventButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

// 6. showDeleteEventModal
const showDeleteEventModal = (eventId) => {
    insertDeleteEventModalContent();
    const modal = document.getElementById('confirmDeleteEventModal');
    const deleteEventButton = document.getElementById('deleteEventButton');
    const cancelEventButton = document.getElementById('cancelEventButton');
    const closeEventModalButton = document.getElementById('closeEventModalButton');

    modal.style.display = 'flex';

    deleteEventButton.onclick = () => {
        deleteEvent('event', eventId);
        modal.style.display = 'none';
    };

    cancelEventButton.onclick = () => {
        modal.style.display = 'none';
    };

    closeEventModalButton.onclick = () => {
        modal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 7. deleteEvent
const deleteEvent = (type, id) => {
    const data = {
        action: 'delete',
        type: 'event',
        events_id: id
    };
    Ws.send(JSON.stringify(data));
};

// Function to display "No Event/s to be displayed" message
const displayNoEventsMessage = () => {
    const messageDiv = document.createElement('div');
    messageDiv.id = 'no-events-message';
    messageDiv.style = `
        text-align: center;
        font-size: 1.2em;
        color: #666;
        margin-top: 5px;
    `;
    messageDiv.textContent = 'No upcoming events to be displayed';
    eveCarouselContainer.appendChild(messageDiv);
};

/* News Functions ====================================================================== */
// 1. updateNewsUI
const updateNewsUI = (data) => {
    // Check if the status is 'Pending' and return early if true
    if (data.status === 'Pending' || data.isCancelled === 1 || !data.news_id) {
        return;
    }

    // Check if the news already exists in the DOM
    const existingNewsDiv = document.querySelector(`[data-news-id="${data.news_id}"]`);

    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    const formattedExpirationDate = formatDate(data.expiration_date);
    const formattedExpirationTime = formatTime(data.expiration_time);
    
    if (existingNewsDiv) {
        // Update the existing news
        const contentDiv = existingNewsDiv.querySelector('.content-container-con');
        if (contentDiv) {
            let mediaContent = '';
            if (data.media_path) {
                // Determine media type based on file extension
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        
                if (isImage) {
                    mediaContent = `<img src="servers/news_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">`;
                } else if (isVideo) {
                    mediaContent = `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/news_media/${data.media_path}" type="video/mp4"></video>`;
                }
            }
            
            contentDiv.innerHTML = `
                <div class="content-container-con">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <pre class="news-body" style="word-break: break-word">${data.news_heading}</pre>
                    <div class="line-separator"></div>
                    <p class="news-author" style="color: #6E6E6E"><small>Posted by ${data.news_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                    <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                    <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                </div>
            `;
        }
    } else {
        const containerDiv = document.createElement('div');
        const contentDiv = document.createElement('div');
        const postDiv = document.createElement('div');
        const deleteButton = document.createElement('button');
        const archiveButton = document.createElement('button');
        const editButton = document.createElement('button');

        containerDiv.style = `
            height: auto;
            width: auto;
            margin-bottom: 5px;
            margin-right: 8px;
            border: black 1px solid;
            border-radius: 5px;
        `;

        // const isOdd = newsCarouselContainer.children.length % 2 !== 0;

        contentDiv.style = `
            background-color: #dffce5;
            border: none;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            padding: 10px;
            color: black;
            height: auto;
            text-align: left;
            bottom: 0;
        `;

        postDiv.style = `
            background-color: #264b2b;
            border: none;
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
            padding: 5px;
            color: black;
            height: auto;
            text-align: right;
        `;

        deleteButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-top: 5px;
        `;

        archiveButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-left: 7px;
        `;

        editButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-left: 7px;
        `;

        if (data.category == "News") {
            containerDiv.dataset.newsId = data.news_id;
            containerDiv.setAttribute('data-news-id', data.news_id);
            // Check if the news has media
            if (data.media_path) {
                // Determine media type based on file extension
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        
                if (isImage) {
                    mediaContent = `<img src="servers/news_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">`;
                } else if (isVideo) {
                    mediaContent = `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/news_media/${data.media_path}" type="video/mp4"></video>`;
                }
        
                contentDiv.innerHTML = `
                    <div class="content-container-con">
                        ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                        <pre class="news-body" style="word-break: break-word">${data.news_heading}</pre>
                        <div class="line-separator"></div>
                        <p class="news-author" style="color: #6E6E6E"><small>Posted by ${data.news_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                        <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                    </div>
                `;
            } else {
                contentDiv.innerHTML = `
                    <div class="content-container-con">
                        <pre class="news-body" style="word-break: break-word">${data.news_heading}</pre>
                        <div class="line-separator"></div>
                        <p class="news-author" style="color: #6E6E6E"><small>Posted by ${data.news_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                        <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                    </div>
                `;
            }

            deleteButton.innerHTML  = '<i class="fa fa-trash" aria-hidden="true"></i> Delete';
            deleteButton.onclick = () => showDeleteNewsModal(data.news_id);

            archiveButton.innerHTML  = '<i class="fa fa-archive" aria-hidden="true"></i> Archive';
            archiveButton.onclick = () => showArchiveNewsModal(data.news_id);

            editButton.innerHTML  = '<i class="fa fa-pencil-square" aria-hidden="true"></i> Edit';
            editButton.onclick = () => {
                window.location.href = `edit_news.php?news_id=${data.news_id}?=${data.news_author}`;
            };

            if (userType !== 'Student' && userType !== 'Faculty' || data.news_author === full_name) {
                contentDiv.appendChild(deleteButton);
                contentDiv.appendChild(archiveButton);
                contentDiv.appendChild(editButton);
            }
        }
        
        postDiv.innerHTML = `
            
        `;

        // Append 'contentDiv' and 'postDiv' to 'containerDiv'
        containerDiv.appendChild(contentDiv);
        containerDiv.appendChild(postDiv);

        newsCarouselContainer.insertBefore(containerDiv, newsCarouselContainer.firstChild);
    }
};

// 2. insertArchiveNewsModalContent
const insertArchiveNewsModalContent = () => {
    const modalContainer = document.getElementById('confirmArchiveNewsModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeArchiveNewsModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-archive" aria-hidden="true"></i></h1>
                <p id="archiveMessage" style="text-align: center">Proceed to archive this news?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelArchiveNewsButton" class="cancel-button" type="button">Cancel</button>
                    <button id="archiveNewsButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to archive</b></button>
                </div>
            </div>
        </div>
    `;
}

// 3. showArchiveNewsModal
const showArchiveNewsModal = (newsId) => {
    insertArchiveNewsModalContent();
    const modal = document.getElementById('confirmArchiveNewsModal');
    const archiveNewsButton = document.getElementById('archiveNewsButton');
    const cancelArchiveNewsButton = document.getElementById('cancelArchiveNewsButton');
    const closeArchiveNewsModalButton = document.getElementById('closeArchiveNewsModalButton');

    modal.style.display = 'flex';

    archiveNewsButton.onclick = () => {
        archiveNews('news', newsId);
        modal.style.display = 'none';
    };

    cancelArchiveNewsButton.onclick = () => {
        modal.style.display = 'none';
    }

    closeArchiveNewsModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 4. archiveNews
const archiveNews = (type, id) => {
    const data = {
        action: 'archive',
        type: 'news',
        news_id : id
    };
    Ws.send(JSON.stringify(data));
};

// 5. insertDeleteNewsModalContent
const insertDeleteNewsModalContent = () => {
    const modalContainer = document.getElementById('confirmDeleteNewsModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeNewsModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Proceed to delete this news?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelNewsButton" class="cancel-button" type="button">Cancel</button>
                    <button id="deleteNewsButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

// 6. showDeleteNewsModal
const showDeleteNewsModal = (newsId) => {
    insertDeleteNewsModalContent();
    const modal = document.getElementById('confirmDeleteNewsModal');
    const deleteNewsButton = document.getElementById('deleteNewsButton');
    const cancelNewsButton = document.getElementById('cancelNewsButton');
    const closeNewsModalButton = document.getElementById('closeNewsModalButton');

    modal.style.display = 'flex';

    deleteNewsButton.onclick = () => {
        deleteNews('news', newsId);
        modal.style.display = 'none';
    };

    cancelNewsButton.onclick = () => {
        modal.style.display = 'none';
    }

    closeNewsModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 7. deleteNews
const deleteNews = (type, id) => {
    const data = {
        action: 'delete',
        type: 'news',
        news_id : id
    };
    Ws.send(JSON.stringify(data));
};

// Function to display "No News to be displayed" message
const displayNoNewsMessage = () => {
    const messageDiv = document.createElement('div');
    messageDiv.id = 'no-news-message';
    messageDiv.style = `
        text-align: center;
        font-size: 1.2em;
        color: #666;
        margin-top: 5px;
    `;
    messageDiv.textContent = 'No news to be displayed';
    newsCarouselContainer.appendChild(messageDiv);
};

/* Promaterial Functions ====================================================================== */
// 1. updatePromaterialUI
const updatePromaterialUI = (data) => {
    // Check if the status is 'Pending' and return early if true
    if (data.status === 'Pending' || data.isCancelled === 1 || !data.promaterials_id) {
        return;
    }

    // Check if the announcement already exists in the DOM
    const existingPromaterialDiv = document.querySelector(`[data-promaterials-id="${data.promaterials_id}"]`);

    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    const formattedExpirationDate = formatDate(data.expiration_date);
    const formattedExpirationTime = formatTime(data.expiration_time);
    
    if (existingPromaterialDiv) {
        // Update the existing announcement
        const contentDiv = existingPromaterialDiv.querySelector('.content-container-con');
        if (contentDiv) {
            let mediaContent = '';
            if (data.media_path) {
                // Determine media type based on file extension
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        
                if (isImage) {
                    mediaContent = `<img src="servers/promaterials_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">`;
                } else if (isVideo) {
                    mediaContent = `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/promaterials_media/${data.media_path}" type="video/mp4"></video>`;
                }
            }
            
            contentDiv.innerHTML = `
                <div class="content-container-con">
                    <div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>
                    <div class="line-separator"></div>
                    <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.promaterials_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                    <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                    <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                </div>
            `;
        }
    } else {
        const containerDiv = document.createElement('div');
        const contentDiv = document.createElement('div');
        const postDiv = document.createElement('div');
        const deleteButton = document.createElement('button');
        const archiveButton = document.createElement('button');
        const editButton = document.createElement('button');

        containerDiv.style = `
            height: auto;
            width: auto;
            margin-bottom: 5px;
            margin-right: 8px;
            border: black 1px solid;
            border-radius: 5px;
        `;

        // const isOdd = annCarouselContainer.children.length % 2 !== 0;

        contentDiv.style = `
            background-color: #dffce5;
            border: none;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            padding: 10px;
            color: black;
            height: auto;
            text-align: left;
            bottom: 0;
        `;

        postDiv.style = `
            background-color: #264b2b;
            border: none;
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
            padding: 5px;
            color: black;
            height: auto;
            text-align: right;
        `;

        deleteButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-top: 5px;
        `;

        archiveButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-left: 7px;
        `;

        editButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-left: 7px;
        `;

        if (data.category == "Promotional Materials") {
            containerDiv.dataset.promaterialId = data.promaterials_id;
            containerDiv.setAttribute('data-promaterials-id', data.promaterials_id);
            // Check if the announcement has media
            if (data.media_path) {
                // Determine media type based on file extension
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        
                if (isImage) {
                    mediaContent = `<img src="servers/promaterials_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">`;
                } else if (isVideo) {
                    mediaContent = `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/promaterials_media/${data.media_path}" type="video/mp4"></video>`;
                }
        
                contentDiv.innerHTML = `
                    <div class="content-container-con">
                        <div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>
                        <div class="line-separator"></div>
                        <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.promaterials_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                        <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                    </div>
                `;
            }

            deleteButton.innerHTML  = '<i class="fa fa-trash" aria-hidden="true"></i> Delete';
            deleteButton.onclick = () => showDeletePromaterialModal(data.promaterials_id);

            archiveButton.innerHTML  = '<i class="fa fa-archive" aria-hidden="true"></i> Archive';
            archiveButton.onclick = () => showArchivePromaterialModal(data.promaterials_id);

            editButton.innerHTML  = '<i class="fa fa-pencil-square" aria-hidden="true"></i> Edit';
            editButton.onclick = () => {
                window.location.href = `edit_promaterial.php?promaterials_id=${data.promaterials_id}?=${data.promaterials_author}`;
            };

            if (userType !== 'Student' && userType !== 'Faculty' || data.promaterials_id === full_name) {
                contentDiv.appendChild(deleteButton);
                contentDiv.appendChild(archiveButton);
                contentDiv.appendChild(editButton);
            }
        }
        
        postDiv.innerHTML = `
            
        `;

        // Append 'contentDiv' and 'postDiv' to 'containerDiv'
        containerDiv.appendChild(contentDiv);
        containerDiv.appendChild(postDiv);

        promaterialsCarouselContainer.insertBefore(containerDiv, promaterialsCarouselContainer.firstChild);
    }
};

// 2. insertArchivePromaterialModalContent
const insertArchivePromaterialModalContent = () => {
    const modalContainer = document.getElementById('confirmArchivePromaterialModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeArchivePromaterialModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-archive" aria-hidden="true"></i></h1>
                <p id="archiveMessage" style="text-align: center">Proceed to archive this promotional material?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelArchivePromaterialButton" class="cancel-button" type="button">Cancel</button>
                    <button id="archivePromaterialButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to archive</b></button>
                </div>
            </div>
        </div>
    `;
}

// 3. showArchivePromaterialModal
const showArchivePromaterialModal = (promaterialId) => {
    insertArchivePromaterialModalContent();
    const modal = document.getElementById('confirmArchivePromaterialModal');
    const archivePromaterialButton = document.getElementById('archivePromaterialButton');
    const cancelArchivePromaterialButton = document.getElementById('cancelArchivePromaterialButton');
    const closeArchivePromaterialModalButton = document.getElementById('closeArchivePromaterialModalButton');

    modal.style.display = 'flex';

    archivePromaterialButton.onclick = () => {
        archivePromaterial('promaterial', promaterialId);
        modal.style.display = 'none';
    };

    cancelArchivePromaterialButton.onclick = () => {
        modal.style.display = 'none';
    }

    closeArchivePromaterialModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 4. archivePromaterial
const archivePromaterial = (type, id) => {
    const data = {
        action: 'archive',
        type: 'promaterial',
        promaterials_id : id
    };
    Ws.send(JSON.stringify(data));
};

// 5. insertDeletePromaterialModalContent
const insertDeletePromaterialModalContent = () => {
    const modalContainer = document.getElementById('confirmDeletePromaterialModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closePromaterialModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Proceed to delete this promotional material?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelPromaterialButton" class="cancel-button" type="button">Cancel</button>
                    <button id="deletePromaterialButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

// 6. showDeletePromaterialModal
const showDeletePromaterialModal = (promaterialId) => {
    insertDeletePromaterialModalContent();
    const modal = document.getElementById('confirmDeletePromaterialModal');
    const deletePromaterialButton = document.getElementById('deletePromaterialButton');
    const cancelPromaterialButton = document.getElementById('cancelPromaterialButton');
    const closePromaterialModalButton = document.getElementById('closePromaterialModalButton');

    modal.style.display = 'flex';

    deletePromaterialButton.onclick = () => {
        deletePromaterial('promaterial', promaterialId);
        modal.style.display = 'none';
    };

    cancelPromaterialButton.onclick = () => {
        modal.style.display = 'none';
    }

    closePromaterialModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 7. deletePromaterial
const deletePromaterial = (type, id) => {
    const data = {
        action: 'delete',
        type: 'promaterial',
        promaterials_id : id
    };
    Ws.send(JSON.stringify(data));
};

// Function to display "No Promaterial/s to be displayed" message
const displayNoPromaterialMessage = () => {
    const messageDiv = document.createElement('div');
    messageDiv.id = 'no-promaterial-message';
    messageDiv.style = `
        text-align: center;
        font-size: 1.2em;
        color: #666;
        margin-top: 5px;
    `;
    messageDiv.textContent = 'No promotional materials to be displayed';
    promaterialsCarouselContainer.appendChild(messageDiv);
};

/* PEO Functions ====================================================================== */
// 1. updatePEOUI
const updatePEOUI = (data) => {
    // Check if the necessary fields are present and non-empty
    if (data.isCancelled === 1 || !data.peo_id) {
        return;
    }

    // Check if the peo already exists in the DOM
    const existingPEODiv = document.querySelector(`[data-peo-id="${data.peo_id}"]`);

    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    
    if (existingPEODiv) {
        // Update the existing PEO
        const contentDiv = existingPEODiv.querySelector('.content-container-con');
        if (contentDiv) {
            let peoContent = `
                <div class="content-container-con">
                    <pre class="ann-body" style="word-break: break-word"><b>${data.peo_title}</b></pre>
                    <pre class="ann-body" style="word-break: break-word">${data.peo_description}</pre>
            `;

            for (let i = 1; i <= 10; i++) {
                let peoValue = data[`peo_${i}`];
                if (peoValue) {
                    peoContent += `<pre class="ann-body" style="margin-left: 15px; word-break: break-word"><i class="fa fa-caret-right" aria-hidden="true"></i> ${peoValue}</pre>`;
                }
            }

            peoContent += `
                <div class="line-separator"></div>
                <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.peo_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                </div>
            `;

            contentDiv.innerHTML = peoContent;
        }
    } else {
        const containerDiv = document.createElement('div');
        const contentDiv = document.createElement('div');
        const postDiv = document.createElement('div');
        const deleteButton = document.createElement('button');
        const archiveButton = document.createElement('button');
        const editButton = document.createElement('button');

        containerDiv.style = `
            height: auto;
            width: auto;
            margin-bottom: 5px;
            margin-right: 8px;
            border: black 1px solid;
            border-radius: 5px;
        `;

        // const isOdd = peoCarouselContainer.children.length % 2 !== 0;

        contentDiv.style = `
            background-color: #dffce5;
            border: none;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            padding: 10px;
            color: black;
            height: auto;
            text-align: left;
            bottom: 0;
        `;

        postDiv.style = `
            background-color: #264b2b;
            border: none;
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
            padding: 5px;
            color: black;
            height: auto;
            text-align: right;
        `;

        deleteButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-top: 5px;
        `;

        archiveButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-left: 7px;
        `;

        editButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-left: 7px;
        `;

        if (data.category == "PEO") {
            containerDiv.dataset.peoId = data.peo_id;
            containerDiv.setAttribute('data-peo-id', data.peo_id);

            let peoContent = `
                <div class="content-container-con">
                    <pre class="ann-body" style="word-break: break-word"><b>${data.peo_title}</b></pre>
                    <pre class="ann-body" style="word-break: break-word">${data.peo_description}</pre>
            `;

            for (let i = 1; i <= 10; i++) {
                let peoValue = data[`peo_${i}`];
                if (peoValue) {
                    peoContent += `<pre class="ann-body" style="margin-left: 15px; word-break: break-word"><i class="fa fa-caret-right" aria-hidden="true"></i> ${peoValue}</pre>`;
                }
            }

            peoContent += `
                <div class="line-separator"></div>
                <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.peo_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                </div>
            `;

            contentDiv.innerHTML = peoContent;

            deleteButton.innerHTML  = '<i class="fa fa-trash" aria-hidden="true"></i> Delete';
            deleteButton.onclick = () => showDeletePEOModal(data.peo_id);

            archiveButton.innerHTML  = '<i class="fa fa-archive" aria-hidden="true"></i> Archive';
            archiveButton.onclick = () => showArchivePEOModal(data.peo_id);

            editButton.innerHTML  = '<i class="fa fa-pencil-square" aria-hidden="true"></i> Edit';
            editButton.onclick = () => {
                window.location.href = `edit_peo.php?peo_id=${data.peo_id}?=${data.peo_author}`;
            };

            if (userType !== 'Student' && userType !== 'Faculty' || data.peo_author === full_name) {
                contentDiv.appendChild(deleteButton);
                contentDiv.appendChild(archiveButton);
                contentDiv.appendChild(editButton);
            }
        }
        
        postDiv.innerHTML = `
            
        `;

        // Append 'contentDiv' and 'postDiv' to 'containerDiv'
        containerDiv.appendChild(contentDiv);
        containerDiv.appendChild(postDiv);

        peoCarouselContainer.insertBefore(containerDiv, peoCarouselContainer.firstChild);
    }
};

// 2. insertArchivePEOModalContent
const insertArchivePEOModalContent = () => {
    const modalContainer = document.getElementById('confirmArchivePEOModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeArchivePEOModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-archive" aria-hidden="true"></i></h1>
                <p id="archiveMessage" style="text-align: center">Proceed to archive this PEO?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelArchivePEOButton" class="cancel-button" type="button">Cancel</button>
                    <button id="archivePEOButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to archive</b></button>
                </div>
            </div>
        </div>
    `;
}

// 3. showArchivePEOModal
const showArchivePEOModal = (peoId) => {
    insertArchivePEOModalContent();
    const modal = document.getElementById('confirmArchivePEOModal');
    const archivePEOButton = document.getElementById('archivePEOButton');
    const cancelArchivePEOButton = document.getElementById('cancelArchivePEOButton');
    const closeArchivePEOModalButton = document.getElementById('closeArchivePEOModalButton');

    modal.style.display = 'flex';

    archivePEOButton.onclick = () => {
        archivePEO('peo', peoId);
        modal.style.display = 'none';
    };

    cancelArchivePEOButton.onclick = () => {
        modal.style.display = 'none';
    }

    closeArchivePEOModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 4. archivePEO
const archivePEO = (type, id) => {
    const data = {
        action: 'archive',
        type: 'peo',
        peo_id : id
    };
    Ws.send(JSON.stringify(data));
};

// 5. insertDeletePEOModalContent
const insertDeletePEOModalContent = () => {
    const modalContainer = document.getElementById('confirmDeletePEOModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closePEOModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Proceed to delete this PEO?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelPEOButton" class="cancel-button" type="button">Cancel</button>
                    <button id="deletePEOButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

// 6. showDeletePEOModal
const showDeletePEOModal = (peoId) => {
    insertDeletePEOModalContent();
    const modal = document.getElementById('confirmDeletePEOModal');
    const deletePEOButton = document.getElementById('deletePEOButton');
    const cancelPEOButton = document.getElementById('cancelPEOButton');
    const closePEOModalButton = document.getElementById('closePEOModalButton');

    modal.style.display = 'flex';

    deletePEOButton.onclick = () => {
        deletePEO('peo', peoId);
        modal.style.display = 'none';
    };

    cancelPEOButton.onclick = () => {
        modal.style.display = 'none';
    }

    closePEOModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 7. deletePEO
const deletePEO = (type, id) => {
    const data = {
        action: 'delete',
        type: 'peo',
        peo_id : id
    };
    Ws.send(JSON.stringify(data));
};

// Function to display "No PEO/s to be displayed" message
const displayNoPEOMessage = () => {
    const messageDiv = document.createElement('div');
    messageDiv.id = 'no-peo-message';
    messageDiv.style = `
        text-align: center;
        font-size: 1.2em;
        color: #666;
        margin-top: 5px;
    `;
    messageDiv.textContent = 'No PEO to be displayed';
    peoCarouselContainer.appendChild(messageDiv);
};

/* SO Functions ====================================================================== */
// 1. updateSOUI
const updateSOUI = (data) => {
    // Check if the necessary fields are present and non-empty
    if (data.isCancelled === 1 || !data.so_id) {
        return;
    }

    // Check if the so already exists in the DOM
    const existingSODiv = document.querySelector(`[data-so-id="${data.so_id}"]`);

    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    
    if (existingSODiv) {
        // Update the existing SO
        const contentDiv = existingSODiv.querySelector('.content-container-con');
        if (contentDiv) {
            let soContent = `
                <div class="content-container-con">
                    <pre class="ann-body" style="word-break: break-word"><b>${data.so_title}</b></pre>
                    <pre class="ann-body" style="word-break: break-word">${data.so_description}</pre>
            `;

            for (let i = 1; i <= 10; i++) {
                let soValue = data[`so_${i}`];
                if (soValue) {
                    soContent += `<pre class="ann-body" style="margin-left: 15px; word-break: break-word"><i class="fa fa-caret-right" aria-hidden="true"></i> ${soValue}</pre>`;
                }
            }

            soContent += `
                <div class="line-separator"></div>
                <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.so_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                </div>
            `;

            contentDiv.innerHTML = peoContent;
        }
    } else {
        const containerDiv = document.createElement('div');
        const contentDiv = document.createElement('div');
        const postDiv = document.createElement('div');
        const deleteButton = document.createElement('button');
        const archiveButton = document.createElement('button');
        const editButton = document.createElement('button');

        containerDiv.style = `
            height: auto;
            width: auto;
            margin-bottom: 5px;
            margin-right: 8px;
            border: black 1px solid;
            border-radius: 5px;
        `;

        // const isOdd = soCarouselContainer.children.length % 2 !== 0;

        contentDiv.style = `
            background-color: #dffce5;
            border: none;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            padding: 10px;
            color: black;
            height: auto;
            text-align: left;
            bottom: 0;
        `;

        postDiv.style = `
            background-color: #264b2b;
            border: none;
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
            padding: 5px;
            color: black;
            height: auto;
            text-align: right;
        `;

        deleteButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-top: 5px;
        `;

        archiveButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-left: 7px;
        `;

        editButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-left: 7px;
        `;

        if (data.category == "SO") {
            containerDiv.dataset.soId = data.so_id;
            containerDiv.setAttribute('data-so-id', data.so_id);

            let soContent = `
                <div class="content-container-con">
                    <pre class="ann-body" style="word-break: break-word"><b>${data.so_title}</b></pre>
                    <pre class="ann-body" style="word-break: break-word">${data.so_description}</pre>
            `;

            for (let i = 1; i <= 10; i++) {
                let soValue = data[`so_${i}`];
                if (soValue) {
                    soContent += `<pre class="ann-body" style="margin-left: 15px; word-break: break-word"><i class="fa fa-caret-right" aria-hidden="true"></i> ${soValue}</pre>`;
                }
            }

            soContent += `
                <div class="line-separator"></div>
                <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.so_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                </div>
            `;

            contentDiv.innerHTML = soContent;

            deleteButton.innerHTML  = '<i class="fa fa-trash" aria-hidden="true"></i> Delete';
            deleteButton.onclick = () => showDeleteSOModal(data.so_id);

            archiveButton.innerHTML  = '<i class="fa fa-archive" aria-hidden="true"></i> Archive';
            archiveButton.onclick = () => showArchiveSOModal(data.so_id);

            editButton.innerHTML  = '<i class="fa fa-pencil-square" aria-hidden="true"></i> Edit';
            editButton.onclick = () => {
                window.location.href = `edit_so.php?so_id=${data.so_id}?=${data.so_author}`;
            };

            if (userType !== 'Student' && userType !== 'Faculty' || data.so_author === full_name) {
                contentDiv.appendChild(deleteButton);
                contentDiv.appendChild(archiveButton);
                contentDiv.appendChild(editButton);
            }
        }
        
        postDiv.innerHTML = `
            
        `;

        // Append 'contentDiv' and 'postDiv' to 'containerDiv'
        containerDiv.appendChild(contentDiv);
        containerDiv.appendChild(postDiv);

        soCarouselContainer.insertBefore(containerDiv, soCarouselContainer.firstChild);
    }
};

// 2. insertArchiveSOModalContent
const insertArchiveSOModalContent = () => {
    const modalContainer = document.getElementById('confirmArchiveSOModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeArchiveSOModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-archive" aria-hidden="true"></i></h1>
                <p id="archiveMessage" style="text-align: center">Proceed to archive this SO?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelArchiveSOButton" class="cancel-button" type="button">Cancel</button>
                    <button id="archiveSOButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to archive</b></button>
                </div>
            </div>
        </div>
    `;
}

// 3. showArchiveSOModal
const showArchiveSOModal = (soId) => {
    insertArchiveSOModalContent();
    const modal = document.getElementById('confirmArchiveSOModal');
    const archiveSOButton = document.getElementById('archiveSOButton');
    const cancelArchiveSOButton = document.getElementById('cancelArchiveSOButton');
    const closeArchiveSOModalButton = document.getElementById('closeArchiveSOModalButton');

    modal.style.display = 'flex';

    archiveSOButton.onclick = () => {
        archiveSO('so', soId);
        modal.style.display = 'none';
    };

    cancelArchiveSOButton.onclick = () => {
        modal.style.display = 'none';
    }

    closeArchiveSOModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 4. archiveSO
const archiveSO = (type, id) => {
    const data = {
        action: 'archive',
        type: 'so',
        so_id : id
    };
    Ws.send(JSON.stringify(data));
};

// 5. insertDeleteSOModalContent
const insertDeleteSOModalContent = () => {
    const modalContainer = document.getElementById('confirmDeleteSOModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeSOModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Proceed to delete this SO?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelSOButton" class="cancel-button" type="button">Cancel</button>
                    <button id="deleteSOButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

// 6. showDeleteSOModal
const showDeleteSOModal = (soId) => {
    insertDeleteSOModalContent();
    const modal = document.getElementById('confirmDeleteSOModal');
    const deleteSOButton = document.getElementById('deleteSOButton');
    const cancelSOButton = document.getElementById('cancelSOButton');
    const closeSOModalButton = document.getElementById('closeSOModalButton');

    modal.style.display = 'flex';

    deleteSOButton.onclick = () => {
        deleteSO('so', soId);
        modal.style.display = 'none';
    };

    cancelSOButton.onclick = () => {
        modal.style.display = 'none';
    }

    closeSOModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 7. deleteSO
const deleteSO = (type, id) => {
    const data = {
        action: 'delete',
        type: 'so',
        so_id : id
    };
    Ws.send(JSON.stringify(data));
};

// Function to display "No SO/s to be displayed" message
const displayNoSOMessage = () => {
    const messageDiv = document.createElement('div');
    messageDiv.id = 'no-so-message';
    messageDiv.style = `
        text-align: center;
        font-size: 1.2em;
        color: #666;
        margin-top: 5px;
    `;
    messageDiv.textContent = 'No student outcomes to be displayed';
    soCarouselContainer.appendChild(messageDiv);
};

// Fetch and update functions for different content types
const fetchAndUpdateAnnouncements = () => {
    fetch('database/fetch_announcements.php')
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(announcement =>
                announcement.status !== 'Pending' &&
                announcement.isCancelled === 0
            );
            // Clear existing announcements
            annCarouselContainer.innerHTML = '';
            announcements = [];

            if (filteredData.length === 0) {
                displayNoAnnouncementsMessage();
            } else {
                // Remove the "No announcements to be displayed" message if it exists
                const noAnnouncementsMessage = document.getElementById('no-announcements-message');
                if (noAnnouncementsMessage) {
                    noAnnouncementsMessage.remove();
                }

                filteredData.forEach(announcement => updateAnnouncementUI(announcement));
            }

            // data.forEach(announcement => updateAnnouncementUI(announcement));
        })
        .catch(error => console.error('Error fetching announcements:', error));
};

const fetchAndUpdateEvents = () => {
    fetch('database/fetch_events.php')
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(event =>
                event.status !== 'Pending' &&
                event.isCancelled === 0
            );
            // Clear existing events
            eveCarouselContainer.innerHTML = '';
            events = [];

            if (filteredData.length === 0) {
                displayNoEventsMessage();
            } else {
                // Remove the "No upcoming events to be displayed" message if it exists
                const noEventsMessage = document.getElementById('no-events-message');
                if (noEventsMessage) {
                    noEventsMessage.remove();
                }

                filteredData.forEach(event => updateEventUI(event));
            }

            // data.forEach(event => updateEventUI(event));
        })
        .catch(error => console.error('Error fetching events:', error));
};

const fetchAndUpdateNews = () => {
    fetch('database/fetch_news.php')
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(news =>
                news.status !== 'Pending' &&
                news.isCancelled === 0
            );
            // Clear existing news
            newsCarouselContainer.innerHTML = '';
            news = [];

            if (filteredData.length === 0) {
                displayNoNewsMessage();
            } else {
                // Remove the "No news to be displayed" message if it exists
                const noNewsMessage = document.getElementById('no-news-message');
                if (noNewsMessage) {
                    noNewsMessage.remove();
                }

                filteredData.forEach(news => updateNewsUI(news));
            }

            // data.forEach(news => updateNewsUI(news));
        })
        .catch(error => console.error('Error fetching news:', error));
};

const fetchAndUpdatePromaterials = () => {
    fetch('database/fetch_promaterials.php')
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(promaterial =>
                promaterial.status !== 'Pending' &&
                promaterial.isCancelled === 0
            );
            // Clear existing promaterial
            promaterialsCarouselContainer.innerHTML = '';
            promaterial = [];

            if (filteredData.length === 0) {
                displayNoPromaterialMessage();
            } else {
                // Remove the "No promotional materials to be displayed" message if it exists
                const noPromaterialMessage = document.getElementById('no-promaterial-message');
                if (noPromaterialMessage) {
                    noPromaterialMessage.remove();
                }

                filteredData.forEach(promaterial => updatePromaterialUI(promaterial));
            }

            // data.forEach(promaterial => updatePromaterialUI(promaterial));
        })
        .catch(error => console.error('Error fetching promotional material:', error));
};

const fetchAndUpdatePEOs = () => {
    fetch('database/fetch_peo.php')
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(peo =>
                peo.isCancelled === 0
            );
            // Clear existing peo
            peoCarouselContainer.innerHTML = '';
            peo = [];

            if (filteredData.length === 0) {
                displayNoPEOMessage();
            } else {
                // Remove the "No PEO to be displayed" message if it exists
                const noPEOMessage = document.getElementById('no-peo-message');
                if (noPEOMessage) {
                    noPEOMessage.remove();
                }

                filteredData.forEach(peo => updatePEOUI(peo));
            }

            // data.forEach(peo => updatePEOUI(peo));
        })
        .catch(error => console.error('Error fetching PEO:', error));
};

const fetchAndUpdateSOs = () => {
    fetch('database/fetch_so.php')
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(so =>
                so.isCancelled === 0
            );
            // Clear existing so
            soCarouselContainer.innerHTML = '';
            so = [];

            if (filteredData.length === 0) {
                displayNoSOMessage();
            } else {
                // Remove the "No student outcomes to be displayed" message if it exists
                const noSOMessage = document.getElementById('no-so-message');
                if (noSOMessage) {
                    noSOMessage.remove();
                }

                filteredData.forEach(so => updateSOUI(so));
            }

            // data.forEach(so => updateSOUI(so));
        })
        .catch(error => console.error('Error fetching SO:', error));
};

/* Web-Socket Server Connection */
Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    if (data.action === 'delete' || data.action === 'archive') {
        if (data.type === 'announcement' && data.success) {
            const announcementDiv = document.querySelector(`[data-ann-id="${data.announcements_id}"]`);
            if (announcementDiv) {
                announcementDiv.remove();
                fetchAndUpdateAnnouncements();
            }
        } else if (data.type === 'event' && data.success) {
            const eventDiv = document.querySelector(`[data-event-id="${data.events_id}"]`);
            if (eventDiv) {
                eventDiv.remove();
                fetchAndUpdateEvents();
            }
        } else if (data.type === 'news' && data.success) {
            const newsDiv = document.querySelector(`[data-news-id="${data.news_id}"]`);
            if (newsDiv) {
                newsDiv.remove();
                fetchAndUpdateNews();
            }
        } else if (data.type === 'promaterial' && data.success) {
            const promaterialDiv = document.querySelector(`[data-promaterials-id="${data.promaterials_id}"]`);
            if (promaterialDiv) {
                promaterialDiv.remove();
                fetchAndUpdatePromaterials();
            }
        } else if (data.type === 'peo' && data.success) {
            const peoDiv = document.querySelector(`[data-peo-id="${data.peo_id}"]`);
            if (peoDiv) {
                peoDiv.remove();
                fetchAndUpdatePEOs();
            }
        } else if (data.type === 'so' && data.success) {
            const soDiv = document.querySelector(`[data-so-id="${data.so_id}"]`);
            if (soDiv) {
                soDiv.remove();
                fetchAndUpdateSOs();
            }
        }
    } else if (data.action === 'update') {
        if (data.type === 'announcement') {
            fetchAndUpdateAnnouncements();
        } else if (data.type === 'event') {
            fetchAndUpdateEvents();
        } else if (data.type === 'news') {
            fetchAndUpdateNews();
        } else if (data.type === 'promaterial') {
            fetchAndUpdatePromaterials();
        } else if (data.type === 'peo') {
            fetchAndUpdatePEOs();
        } else if (data.type === 'so') {
            fetchAndUpdateSOs();
        }
    } else if (data.action === 'unarchive' || data.action === 'unarchive_and_update_expiration') {
        if (data.type === 'announcement') {
            fetchAndUpdateAnnouncements();
        } else if (data.type === 'event') {
            fetchAndUpdateEvents();
        } else if (data.type === 'news') {
            fetchAndUpdateNews();
        } else if (data.type === 'promaterial') {
            fetchAndUpdatePromaterials();
        } else if (data.type === 'peo') {
            fetchAndUpdatePEOs();
        } else if (data.type === 'so') {
            fetchAndUpdateSOs();
        }
    } else if (data.action === 'approve_post' && data.success && data.announcement) {
        const announcementDiv = document.querySelector(`[data-ann-id="${data.announcement.announcements_id}"]`);
        if (announcementDiv) {
            announcementDiv.classList.add('approved');
        }
        fetchAndUpdateAnnouncements();
    } else if (data.status === 'Pending') {
        // Content is pending, do not display the content container
        return;
    } else if (data.action === 'post_content') {
        if (data.type === 'announcement') {
            fetchAndUpdateAnnouncements();
        } else if (data.type === 'event') {
            fetchAndUpdateEvents();
        } else if (data.type === 'news') {
            fetchAndUpdateNews();
        } else if (data.type === 'promaterial') {
            fetchAndUpdatePromaterials();
        } else if (data.type === 'peo') {
            fetchAndUpdatePEOs();
        } else if (data.type === 'so') {
            fetchAndUpdateSOs();
        }
    }
});

// Refresh all data on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    fetchAndUpdateAnnouncements();
    fetchAndUpdateEvents();
    fetchAndUpdateNews();
    fetchAndUpdatePromaterials();
    fetchAndUpdatePEOs();
    fetchAndUpdateSOs();
});
