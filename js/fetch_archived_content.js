const Ws = new WebSocket('ws://192.168.1.19:8081');
const annArchivedContainer = document.getElementById('annArchivedContainer');
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

/* Announcement Archive Functions ====================================================================== */
// 1. updateAnnouncementUI
const updateAnnouncementUI = (data) => {
    // Check if the status is 'Pending' and return early if true
    if (data.status === 'Pending' || data.isCancelled === 0 || !data.announcement_id) {
        return;
    }

    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    const formattedExpirationDate = formatDate(data.expiration_date);
    const formattedExpirationTime = formatTime(data.expiration_time);

    // Check if the announcement already exists in the DOM
    const existingAnnouncementDiv = document.querySelector(`[data-ann-id="${data.announcement_id}"]`);

    if (existingAnnouncementDiv) {
        // Update the existing announcement
        const contentDiv = existingAnnouncementDiv.querySelector('.content-container-con');
        if (contentDiv) {
            let mediaContainerHTML = '';
            if (data.media_path) {
                // Determine media type based on file extension
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);

                if (isImage) {
                    mediaContainerHTML = `<img src="servers/announcements_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">`;
                } else if (isVideo) {
                    mediaContainerHTML = `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/announcements_media/${data.media_path}" type="video/mp4"></video>`;
                }
            }

            contentDiv.innerHTML = `
                ${mediaContainerHTML ? `<div class="media-container" style="margin-bottom: 5px">${mediaContainerHTML}</div>` : ''}
                <div class="content-container-con">
                    <pre class="ann-body" style="word-break: break-word">${data.announcement_body}</pre>
                    <div class="line-separator"></div>
                    <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.announcement_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                    <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                    <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                </div>
            `;
        }
        console.log("Existing Announcement");
    } else {
        // Create a new announcement element
        const containerDiv = document.createElement('div');
        const contentDiv = document.createElement('div');
        const postDiv = document.createElement('div');
        const deleteButton = document.createElement('button');
        const unarchiveButton = document.createElement('button');
        const editButton = document.createElement('button');

        containerDiv.style = `
            height: auto;
            width: auto;
            margin-bottom: 5px;
            margin-right: 8px;
            border: black 1px solid;
            border-radius: 5px;
        `;

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

        unarchiveButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-top: 5px;
        `;

        deleteButton.style = `
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

        if (data.category === "Announcement") {
            containerDiv.dataset.annId = data.announcement_id;
            containerDiv.setAttribute('data-ann-id', data.announcement_id);

            // Check if the announcement has media
            if (data.media_path) {
                // Determine media type based on file extension
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);

                let mediaContainerHTML = '';
                if (isImage) {
                    mediaContainerHTML = `<img src="servers/announcements_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">`;
                } else if (isVideo) {
                    mediaContainerHTML = `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/announcements_media/${data.media_path}" type="video/mp4"></video>`;
                }

                contentDiv.innerHTML = `
                    <div class="media-container" style="margin-bottom: 5px">
                        ${mediaContainerHTML}
                    </div>
                    <div class="content-container-con">
                        <pre class="ann-body" style="word-break: break-word">${data.announcement_body}</pre>
                        <div class="line-separator"></div>
                        <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.announcement_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                        <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                    </div>
                `;
            } else {
                contentDiv.innerHTML = `
                    <div class="content-container-con">
                        <pre class="ann-body" style="word-break: break-word">${data.announcement_body}</pre>
                        <div class="line-separator"></div>
                        <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.announcement_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                        <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                    </div>
                `;
            }

            deleteButton.innerHTML = '<i class="fa fa-trash" aria-hidden="true"></i> Delete';
            deleteButton.onclick = () => showDeleteAnnModal(data.announcement_id);

            unarchiveButton.innerHTML = '<i class="fa fa-refresh" aria-hidden="true"></i> Unarchive';
            unarchiveButton.onclick = () => showUnarchiveAnnModal(data.announcement_id, data.expiration_date, data.expiration_time);

            editButton.innerHTML = '<i class="fa fa-pencil-square" aria-hidden="true"></i> Edit';
            editButton.onclick = () => {
                window.location.href = `edit_announcement.php?announcement_id=${data.announcement_id}&announcement_author=${data.announcement_author}`;
            };

            if (userType !== 'Student' && userType !== 'Faculty' || data.announcement_author === full_name) {
                contentDiv.appendChild(unarchiveButton);
                contentDiv.appendChild(deleteButton);
                contentDiv.appendChild(editButton);
            }
        }

        postDiv.innerHTML = ``;

        // Append 'contentDiv' and 'postDiv' to 'containerDiv'
        containerDiv.appendChild(contentDiv);
        containerDiv.appendChild(postDiv);

        annArchivedContainer.insertBefore(containerDiv, annArchivedContainer.firstChild);
        console.log("NOT Existing Announcement");
    }
};

// 2. insertUnarchiveAnnModalContent
const insertUnarchiveAnnModalContent = () => {
    const modalContainer = document.getElementById('confirmUnarchiveAnnouncementModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeUnarchiveAnnouncementModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-refresh" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Proceed to unarchive this announcement?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelUnarchiveAnnouncementButton" class="cancel-button" type="button">Cancel</button>
                    <button id="unarchiveAnnouncementButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to unarchive</b></button>
                </div>
            </div>
        </div>
    `;
}

// 3. showUnarchiveAnnModal
const showUnarchiveAnnModal = (annId, expirationDate, expirationTime) => {
    const now = new Date();
    const expirationDateTime = new Date(`${expirationDate}T${expirationTime}`);

    if (expirationDateTime < now) {
        showUpdateExpirationAnnModal(annId, expirationDate, expirationTime);
    } else {
        insertUnarchiveAnnModalContent();
        const modal = document.getElementById('confirmUnarchiveAnnouncementModal');
        const cancelUnarchiveAnnouncementButton = document.getElementById('cancelUnarchiveAnnouncementButton');
        const unarchiveAnnouncementButton = document.getElementById('unarchiveAnnouncementButton');
        const closeUnarchiveAnnouncementModalButton = document.getElementById('closeUnarchiveAnnouncementModalButton');
    
        modal.style.display = 'flex';
    
        cancelUnarchiveAnnouncementButton.onclick = () => {
            modal.style.display = 'none';
        };
    
        unarchiveAnnouncementButton.onclick = () => {
            unarchiveAnnouncement('ann', annId);
            modal.style.display = 'none';
        }
    
        closeUnarchiveAnnouncementModalButton.onclick = () => {
            modal.style.display = 'none';
        }
    
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    }
};

// 4. showUpdateExpirationAnnModal
const showUpdateExpirationAnnModal = (annId, expirationDate, expirationTime) => {
    const modalContainer = document.getElementById('updateExpirationModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeUpdateExpirationModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-refresh" aria-hidden="true"></i></h1>
                <p style="text-align: center">The expiration date and time for this announcement are outdated. Please set new expiration date and time.</p>
                <div class="rounded-container-column">
                    <p class="input-container-label">Expiration Date & Time</p>
                    <div class="left-flex">
                        <input type="date" id="newExpirationDate" name="newExpirationDate" class="input-date" value="${expirationDate}" required>
                    </div>
                    <div class="right-flex">
                        <input type="time" id="newExpirationTime" name="newExpirationTime" class="input-time" value="${expirationTime}" required>
                    </div>
                </div>
                <br>
                <div style="text-align: right;">
                    <button id="cancelUpdateExpirationButton" class="cancel-button" type="button">Cancel</button>
                    <button id="updateExpirationButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Update</b></button>
                </div>
            </div>
        </div>
    `;

    const modal = document.getElementById('updateExpirationModal');
    const cancelUpdateExpirationButton = document.getElementById('cancelUpdateExpirationButton');
    const updateExpirationButton = document.getElementById('updateExpirationButton');
    const closeUpdateExpirationModalButton = document.getElementById('closeUpdateExpirationModalButton');

    modal.style.display = 'flex';

    cancelUpdateExpirationButton.onclick = () => {
        modal.style.display = 'none';
    };

    updateExpirationButton.onclick = () => {
        const newExpirationDate = document.getElementById('newExpirationDate').value;
        const newExpirationTime = document.getElementById('newExpirationTime').value;

        updateExpirationAnnouncement('ann', annId, newExpirationDate, newExpirationTime);
        modal.style.display = 'none';
    };

    closeUpdateExpirationModalButton.onclick = () => {
        modal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 5. unarchiveAnnouncement
const unarchiveAnnouncement = (type, id) => {
    const data = {
        action: 'unarchive',
        type: 'announcement',
        announcement_id: id
    };

    Ws.send(JSON.stringify(data));
};

// 6. updateExpirationAnnouncement
const updateExpirationAnnouncement = (type, id, expirationDate, expirationTime) => {
    const data = {
        action: 'unarchive_and_update_expiration',
        type: 'announcement',
        announcement_id: id,
        expiration_date: expirationDate,
        expiration_time: expirationTime
    };
    
    Ws.send(JSON.stringify(data));
};

// 7. insertDeleteAnnModalContent
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
                    <button id="deleteAnnouncementButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

// 8. showDeleteAnnModal
const showDeleteAnnModal = (annId) => {
    insertDeleteAnnModalContent();
    const modal = document.getElementById('confirmDeleteAnnouncementModal');
    const deleteAnnouncementButton = document.getElementById('deleteAnnouncementButton');
    const closeAnnouncementModalButton = document.getElementById('closeAnnouncementModalButton');

    modal.style.display = 'flex';

    deleteAnnouncementButton.onclick = () => {
        deleteAnnouncement('ann', annId);
        modal.style.display = 'none';
    };

    closeAnnouncementModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 9. deleteAnnouncement
const deleteAnnouncement = (type, id) => {
    const data = {
        action: 'delete',
        type: 'announcement',
        announcement_id : id
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
    annArchivedContainer.appendChild(messageDiv);
};

/* Event Archive ====================================================================== */
const updateEventUI = (data) => {
    // Check if the status is 'Pending' and return early if true
    if (data.status === 'Pending' || data.isCancelled === 0 || !data.event_id) {
        return;
    }

    // Check if the event already exists in the DOM
    const existingEventDiv = document.querySelector(`[data-event-id="${data.event_id}"]`);

    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    const formattedExpirationDate = formatDate(data.expiration_date);
    const formattedExpirationTime = formatTime(data.expiration_time);
    
    if (existingEventDiv) {
        // Update the existing event
        const contentDiv = existingEventDiv.querySelector('.content-container-con');
        if (contentDiv) {
            let mediaContainerHTML = '';
            if (data.media_path) {
                // Determine media type based on file extension
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        
                if (isImage) {
                    mediaContainerHTML = `<img src="servers/events_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">`;
                } else if (isVideo) {
                    mediaContainerHTML = `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/events_media/${data.media_path}" type="video/mp4"></video>`;
                }
            }
            
            contentDiv.innerHTML = `
                ${mediaContainerHTML ? `<div class="media-container" style="margin-bottom: 5px">${mediaContainerHTML}</div>` : ''}
                <div class="content-container-con">
                    <pre class="eve-body" style="word-break: break-word"><b>${data.event_body}</b></pre>
                    <p class="field-title">Event Location</p>
                    <pre class="eve-body" style="word-break: break-word"><small>${data.event_body}</small></pre>
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
        const unarchiveButton = document.createElement('button');
        const deleteButton = document.createElement('button');
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

        unarchiveButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-top: 5px;
        `;

        deleteButton.style = `
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
            containerDiv.dataset.eventId = data.event_id;
            containerDiv.setAttribute('data-event-id', data.event_id);
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
                    <div class="media-container" style="margin-bottom: 5px">
                        ${mediaContent}
                    </div>
                    <div class="content-container-con">
                        <pre class="eve-body" style="word-break: break-word"><b>${data.event_body}</b></pre>
                        <p class="field-title">Event Location</p>
                        <pre class="eve-body" style="word-break: break-word"><small>${data.event_body}</small></pre>
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
                        <pre class="eve-body" style="word-break: break-word"><b>${data.event_body}</b></pre>
                        <p class="field-title">Event Location</p>
                        <pre class="eve-body" style="word-break: break-word"><small>${data.event_body}</small></pre>
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
            deleteButton.onclick = () => showDeleteEventModal(data.event_id);

            unarchiveButton.innerHTML  = '<i class="fa fa-refresh" aria-hidden="true"></i> Unarchive';
            unarchiveButton.onclick = () => showUnarchiveEventModal(data.event_id);

            editButton.innerHTML  = '<i class="fa fa-pencil-square" aria-hidden="true"></i> Edit';
            editButton.onclick = () => {
                window.location.href = `edit_event.php?event_id=${data.event_id}?=${data.event_author}`;
            };

            if (userType !== 'Student' && userType !== 'Faculty' || data.event_author === full_name) {
                contentDiv.appendChild(unarchiveButton);
                contentDiv.appendChild(deleteButton);
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

// 2. insertUnarchiveEventModalContent
const insertUnarchiveEventModalContent = () => {
    const modalContainer = document.getElementById('confirmUnarchiveEventModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeUnarchiveEventModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-refresh" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Proceed to unarchive this event?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelUnarchiveEventButton" class="cancel-button" type="button">Cancel</button>
                    <button id="unarchiveEventButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to unarchive</b></button>
                </div>
            </div>
        </div>
    `;
}

// 3. showUnarchiveEventModal
const showUnarchiveEventModal = (eventId, expirationDate, expirationTime) => {
    const now = new Date();
    const expirationDateTime = new Date(`${expirationDate}T${expirationTime}`);

    if (expirationDateTime < now) {
        showUpdateExpirationEventModal(eventId, expirationDate, expirationTime);
    } else {
        insertUnarchiveEventModalContent();
        const modal = document.getElementById('confirmUnarchiveEventModal');
        const cancelUnarchiveEventButton = document.getElementById('cancelUnarchiveEventButton');
        const unarchiveEventButton = document.getElementById('unarchiveEventButton');
        const closeUnarchiveEventModalButton = document.getElementById('closeUnarchiveEventModalButton');
    
        modal.style.display = 'flex';
    
        cancelUnarchiveEventButton.onclick = () => {
            modal.style.display = 'none';
        };
    
        unarchiveEventButton.onclick = () => {
            unarchiveEvent('event', eventId);
            modal.style.display = 'none';
        }
    
        closeUnarchiveEventModalButton.onclick = () => {
            modal.style.display = 'none';
        }
    
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    }
};

// 4. showUpdateExpirationEventModal
const showUpdateExpirationEventModal = (eventId, expirationDate, expirationTime) => {
    const modalContainer = document.getElementById('updateExpirationModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeUpdateExpirationModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-refresh" aria-hidden="true"></i></h1>
                <p style="text-align: center">The expiration date and time for this event are outdated. Please set new expiration date and time.</p>
                <div class="rounded-container-column">
                    <p class="input-container-label">Expiration Date & Time</p>
                    <div class="left-flex">
                        <input type="date" id="newExpirationDate" name="newExpirationDate" class="input-date" value="${expirationDate}" required>
                    </div>
                    <div class="right-flex">
                        <input type="time" id="newExpirationTime" name="newExpirationTime" class="input-time" value="${expirationTime}" required>
                    </div>
                </div>
                <br>
                <div style="text-align: right;">
                    <button id="cancelUpdateExpirationButton" class="cancel-button" type="button">Cancel</button>
                    <button id="updateExpirationButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Update</b></button>
                </div>
            </div>
        </div>
    `;

    const modal = document.getElementById('updateExpirationModal');
    const cancelUpdateExpirationButton = document.getElementById('cancelUpdateExpirationButton');
    const updateExpirationButton = document.getElementById('updateExpirationButton');
    const closeUpdateExpirationModalButton = document.getElementById('closeUpdateExpirationModalButton');

    modal.style.display = 'flex';

    cancelUpdateExpirationButton.onclick = () => {
        modal.style.display = 'none';
    };

    updateExpirationButton.onclick = () => {
        const newExpirationDate = document.getElementById('newExpirationDate').value;
        const newExpirationTime = document.getElementById('newExpirationTime').value;

        updateExpirationEvent('event', eventId, newExpirationDate, newExpirationTime);
        modal.style.display = 'none';
    };

    closeUpdateExpirationModalButton.onclick = () => {
        modal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 5. unarchiveEvent
const unarchiveEvent = (type, id) => {
    const data = {
        action: 'unarchive',
        type: 'event',
        event_id : id
    };
    Ws.send(JSON.stringify(data));
};

// 6. updateExpirationEvent
const updateExpirationEvent = (type, id, expirationDate, expirationTime) => {
    const data = {
        action: 'unarchive_and_update_expiration',
        type: 'event',
        event_id: id,
        expiration_date: expirationDate,
        expiration_time: expirationTime
    };
    
    Ws.send(JSON.stringify(data));
};

// 7. insertDeleteEventModalContent
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
                    <button id="deleteEventButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

// 8. showDeleteEventModal
const showDeleteEventModal = (eventId) => {
    insertDeleteEventModalContent();
    const modal = document.getElementById('confirmDeleteEventModal');
    const deleteEventButton = document.getElementById('deleteEventButton');
    const closeEventModalButton = document.getElementById('closeEventModalButton');

    modal.style.display = 'flex';

    deleteEventButton.onclick = () => {
        deleteEvent('event', eventId);
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

// 9. deleteEvent
const deleteEvent = (type, id) => {
    const data = {
        action: 'delete',
        type: 'event',
        event_id: id
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

/* News Archive ====================================================================== */
const updateNewsUI = (data) => {
    // Check if the status is 'Pending' and return early if true
    if (data.status === 'Pending' || data.isCancelled === 0 || !data.news_id) {
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
            let mediaContainerHTML = '';
            if (data.media_path) {
                // Determine media type based on file extension
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        
                if (isImage) {
                    mediaContainerHTML = `<img src="servers/news_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">`;
                } else if (isVideo) {
                    mediaContainerHTML = `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/news_media/${data.media_path}" type="video/mp4"></video>`;
                }
            }
            
            contentDiv.innerHTML = `
                ${mediaContainerHTML ? `<div class="media-container" style="margin-bottom: 5px">${mediaContainerHTML}</div>` : ''}
                <div class="content-container-con">
                    <pre class="news-body" style="word-break: break-word">${data.news_body}</pre>
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
        const unarchiveButton = document.createElement('button');
        const deleteButton = document.createElement('button');
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

        unarchiveButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-top: 5px;
        `;

        deleteButton.style = `
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
                    <div class="media-container" style="margin-bottom: 5px">
                        ${mediaContent}
                    </div>
                    <div class="content-container-con">
                        <pre class="news-body" style="word-break: break-word">${data.news_body}</pre>
                        <div class="line-separator"></div>
                        <p class="news-author" style="color: #6E6E6E"><small>Posted by ${data.news_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                        <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                    </div>
                `;
            } else {
                contentDiv.innerHTML = `
                    <div class="content-container-con">
                        <pre class="news-body" style="word-break: break-word">${data.news_body}</pre>
                        <div class="line-separator"></div>
                        <p class="news-author" style="color: #6E6E6E"><small>Posted by ${data.news_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                        <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                    </div>
                `;
            }

            deleteButton.innerHTML  = '<i class="fa fa-trash" aria-hidden="true"></i> Delete';
            deleteButton.onclick = () => showDeleteNewsModal(data.news_id);

            unarchiveButton.innerHTML  = '<i class="fa fa-refresh" aria-hidden="true"></i> Unarchive';
            unarchiveButton.onclick = () => showUnarchiveNewsModal(data.news_id);

            editButton.innerHTML  = '<i class="fa fa-pencil-square" aria-hidden="true"></i> Edit';
            editButton.onclick = () => {
                window.location.href = `edit_news.php?news_id=${data.news_id}?=${data.news_author}`;
            };

            if (userType !== 'Student' && userType !== 'Faculty' || data.news_author === full_name) {
                contentDiv.appendChild(unarchiveButton);
                contentDiv.appendChild(deleteButton);
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

// 2. insertUnarchiveNewsModalContent
const insertUnarchiveNewsModalContent = () => {
    const modalContainer = document.getElementById('confirmUnarchiveNewsModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeUnarchiveNewsModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-refresh" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Proceed to unarchive this news?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelUnarchiveNewsButton" class="cancel-button" type="button">Cancel</button>
                    <button id="unarchiveNewsButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to unarchive</b></button>
                </div>
            </div>
        </div>
    `;
}

// 3. showUnarchiveNewsModal
const showUnarchiveNewsModal = (newsId, expirationDate, expirationTime) => {
    const now = new Date();
    const expirationDateTime = new Date(`${expirationDate}T${expirationTime}`);

    if (expirationDateTime < now) {
        showUpdateExpirationNewsModal(newsId, expirationDate, expirationTime);
    } else {
        insertUnarchiveNewsModalContent();
        const modal = document.getElementById('confirmUnarchiveNewsModal');
        const cancelUnarchiveNewsButton = document.getElementById('cancelUnarchiveNewsButton');
        const unarchiveNewsButton = document.getElementById('unarchiveNewsButton');
        const closeUnarchiveNewsModalButton = document.getElementById('closeUnarchiveNewsModalButton');
    
        modal.style.display = 'flex';
    
        cancelUnarchiveNewsButton.onclick = () => {
            modal.style.display = 'none';
        };
    
        unarchiveNewsButton.onclick = () => {
            unarchiveNews('news', newsId);
            modal.style.display = 'none';
        }
    
        closeUnarchiveNewsModalButton.onclick = () => {
            modal.style.display = 'none';
        }
    
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    }
};

// 4. showUpdateExpirationNewsModal
const showUpdateExpirationNewsModal = (newsId, expirationDate, expirationTime) => {
    const modalContainer = document.getElementById('updateExpirationModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeUpdateExpirationModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-refresh" aria-hidden="true"></i></h1>
                <p style="text-align: center">The expiration date and time for this news are outdated. Please set new expiration date and time.</p>
                <div class="rounded-container-column">
                    <p class="input-container-label">Expiration Date & Time</p>
                    <div class="left-flex">
                        <input type="date" id="newExpirationDate" name="newExpirationDate" class="input-date" value="${expirationDate}" required>
                    </div>
                    <div class="right-flex">
                        <input type="time" id="newExpirationTime" name="newExpirationTime" class="input-time" value="${expirationTime}" required>
                    </div>
                </div>
                <br>
                <div style="text-align: right;">
                    <button id="cancelUpdateExpirationButton" class="cancel-button" type="button">Cancel</button>
                    <button id="updateExpirationButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Update</b></button>
                </div>
            </div>
        </div>
    `;

    const modal = document.getElementById('updateExpirationModal');
    const cancelUpdateExpirationButton = document.getElementById('cancelUpdateExpirationButton');
    const updateExpirationButton = document.getElementById('updateExpirationButton');
    const closeUpdateExpirationModalButton = document.getElementById('closeUpdateExpirationModalButton');

    modal.style.display = 'flex';

    cancelUpdateExpirationButton.onclick = () => {
        modal.style.display = 'none';
    };

    updateExpirationButton.onclick = () => {
        const newExpirationDate = document.getElementById('newExpirationDate').value;
        const newExpirationTime = document.getElementById('newExpirationTime').value;

        updateExpirationNews('news', newsId, newExpirationDate, newExpirationTime);
        modal.style.display = 'none';
    };

    closeUpdateExpirationModalButton.onclick = () => {
        modal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 5. unarchiveNews
const unarchiveNews = (type, id) => {
    const data = {
        action: 'unarchive',
        type: 'news',
        news_id : id
    };
    Ws.send(JSON.stringify(data));
};

// 6. updateExpirationNews
const updateExpirationNews = (type, id, expirationDate, expirationTime) => {
    const data = {
        action: 'unarchive_and_update_expiration',
        type: 'news',
        news_id: id,
        expiration_date: expirationDate,
        expiration_time: expirationTime
    };
    
    Ws.send(JSON.stringify(data));
};

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
                    <button id="deleteNewsButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

const showDeleteNewsModal = (newsId) => {
    insertDeleteNewsModalContent();
    const modal = document.getElementById('confirmDeleteNewsModal');
    const deleteNewsButton = document.getElementById('deleteNewsButton');
    const closeNewsModalButton = document.getElementById('closeNewsModalButton');

    modal.style.display = 'flex';

    deleteNewsButton.onclick = () => {
        deleteNews('news', newsId);
        modal.style.display = 'none';
    };

    closeNewsModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

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

/* Promaterial Archive ====================================================================== */
const updatePromaterialUI = (data) => {
    // Check if the status is 'Pending' and return early if true
    if (data.status === 'Pending' || data.isCancelled === 0 || !data.promaterial_id) {
        return;
    }

    // Check if the promaterial already exists in the DOM
    const existingPromaterialDiv = document.querySelector(`[data-promaterials-id="${data.promaterial_id}"]`);

    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    const formattedExpirationDate = formatDate(data.expiration_date);
    const formattedExpirationTime = formatTime(data.expiration_time);
    
    if (existingPromaterialDiv) {
        // Update the existing promaterial
        const contentDiv = existingPromaterialDiv.querySelector('.content-container-con');
        if (contentDiv) {
            let mediaContainerHTML = '';
            if (data.media_path) {
                // Determine media type based on file extension
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        
                if (isImage) {
                    mediaContainerHTML = `<img src="servers/promaterials_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">`;
                } else if (isVideo) {
                    mediaContainerHTML = `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/promaterials_media/${data.media_path}" type="video/mp4"></video>`;
                }
            }
            
            contentDiv.innerHTML = `
                <div class="content-container-con">
                    <div class="media-container" style="margin-bottom: 5px">${mediaContainerHTML}</div>
                    <div class="line-separator"></div>
                    <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.promaterial_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                    <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                    <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                </div>
            `;
        }
    } else {
        const containerDiv = document.createElement('div');
        const contentDiv = document.createElement('div');
        const postDiv = document.createElement('div');
        const unarchiveButton = document.createElement('button');
        const deleteButton = document.createElement('button');
        const editButton = document.createElement('button');

        containerDiv.style = `
            height: auto;
            width: auto;
            margin-bottom: 5px;
            margin-right: 8px;
            border: black 1px solid;
            border-radius: 5px;
        `;

        // const isOdd = annArchivedContainer.children.length % 2 !== 0;

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
        
        unarchiveButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-top: 5px;
        `;

        deleteButton.style = `
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
            containerDiv.dataset.promaterialId = data.promaterial_id;
            containerDiv.setAttribute('data-promaterials-id', data.promaterial_id);
            // Check if the promaterial has media
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
                        <div class="media-container" style="margin-bottom: 5px">
                            ${mediaContent}
                        </div>
                        <div class="line-separator"></div>
                        <p class="ann-author" style="color: #6E6E6E"><small>Posted by ${data.promaterial_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                        <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs | <i class="fa fa-television" aria-hidden="true"></i> ${data.tv_display}</p>
                    </div>
                `;
            }

            deleteButton.innerHTML  = '<i class="fa fa-trash" aria-hidden="true"></i> Delete';
            deleteButton.onclick = () => showDeletePromaterialModal(data.promaterial_id);

            unarchiveButton.innerHTML  = '<i class="fa fa-refresh" aria-hidden="true"></i> Unarchive';
            unarchiveButton.onclick = () => showUnarchivePromaterialModal(data.promaterial_id);

            editButton.innerHTML  = '<i class="fa fa-pencil-square" aria-hidden="true"></i> Edit';
            editButton.onclick = () => {
                window.location.href = `edit_promaterial.php?promaterial_id=${data.promaterial_id}?=${data.promaterial_author}`;
            };

            if (userType !== 'Student' && userType !== 'Faculty' || data.promaterial_id === full_name) {
                contentDiv.appendChild(unarchiveButton);
                contentDiv.appendChild(deleteButton);
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

// 2. insertUnarchivePromaterialModalContent
const insertUnarchivePromaterialModalContent = () => {
    const modalContainer = document.getElementById('confirmUnarchivePromaterialModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeUnarchivePromaterialModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-refresh" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Proceed to unarchive this promotional material?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelUnarchivePromaterialButton" class="cancel-button" type="button">Cancel</button>
                    <button id="unarchivePromaterialButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to unarchive</b></button>
                </div>
            </div>
        </div>
    `;
}

// 3. showUnarchivePromaterialModal
const showUnarchivePromaterialModal = (promaterialId, expirationDate, expirationTime) => {
    const now = new Date();
    const expirationDateTime = new Date(`${expirationDate}T${expirationTime}`);

    if (expirationDateTime < now) {
        showUpdateExpirationPromaterialModal(promaterialId, expirationDate, expirationTime);
    } else {
        insertUnarchivePromaterialModalContent();
        const modal = document.getElementById('confirmUnarchivePromaterialModal');
        const cancelUnarchivePromaterialButton = document.getElementById('cancelUnarchivePromaterialButton');
        const unarchivePromaterialButton = document.getElementById('unarchivePromaterialButton');
        const closeUnarchivePromaterialModalButton = document.getElementById('closeUnarchivePromaterialModalButton');
    
        modal.style.display = 'flex';
    
        cancelUnarchivePromaterialButton.onclick = () => {
            modal.style.display = 'none';
        };
    
        unarchivePromaterialButton.onclick = () => {
            unarchivePromaterial('promaterial', promaterialId);
            modal.style.display = 'none';
        }
    
        closeUnarchivePromaterialModalButton.onclick = () => {
            modal.style.display = 'none';
        }
    
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    }
};

// 4. showUpdateExpirationPromaterialModal
const showUpdateExpirationPromaterialModal = (promaterialId, expirationDate, expirationTime) => {
    const modalContainer = document.getElementById('updateExpirationModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeUpdateExpirationModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-refresh" aria-hidden="true"></i></h1>
                <p style="text-align: center">The expiration date and time for this promaterial are outdated. Please set new expiration date and time.</p>
                <div class="rounded-container-column">
                    <p class="input-container-label">Expiration Date & Time</p>
                    <div class="left-flex">
                        <input type="date" id="newExpirationDate" name="newExpirationDate" class="input-date" value="${expirationDate}" required>
                    </div>
                    <div class="right-flex">
                        <input type="time" id="newExpirationTime" name="newExpirationTime" class="input-time" value="${expirationTime}" required>
                    </div>
                </div>
                <br>
                <div style="text-align: right;">
                    <button id="cancelUpdateExpirationButton" class="cancel-button" type="button">Cancel</button>
                    <button id="updateExpirationButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Update</b></button>
                </div>
            </div>
        </div>
    `;

    const modal = document.getElementById('updateExpirationModal');
    const cancelUpdateExpirationButton = document.getElementById('cancelUpdateExpirationButton');
    const updateExpirationButton = document.getElementById('updateExpirationButton');
    const closeUpdateExpirationModalButton = document.getElementById('closeUpdateExpirationModalButton');

    modal.style.display = 'flex';

    cancelUpdateExpirationButton.onclick = () => {
        modal.style.display = 'none';
    };

    updateExpirationButton.onclick = () => {
        const newExpirationDate = document.getElementById('newExpirationDate').value;
        const newExpirationTime = document.getElementById('newExpirationTime').value;

        updateExpirationPromaterial('promaterial', promaterialId, newExpirationDate, newExpirationTime);
        modal.style.display = 'none';
    };

    closeUpdateExpirationModalButton.onclick = () => {
        modal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 5. unarchivePromaterial
const unarchivePromaterial = (type, id) => {
    const data = {
        action: 'unarchive',
        type: 'promaterial',
        promaterial_id : id
    };
    Ws.send(JSON.stringify(data));
};

// 6. updateExpirationPromaterial
const updateExpirationPromaterial = (type, id, expirationDate, expirationTime) => {
    const data = {
        action: 'unarchive_and_update_expiration',
        type: 'promaterial',
        promaterial_id: id,
        expiration_date: expirationDate,
        expiration_time: expirationTime
    };
    
    Ws.send(JSON.stringify(data));
};

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
                    <button id="deletePromaterialButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

const showDeletePromaterialModal = (promaterialId) => {
    insertDeletePromaterialModalContent();
    const modal = document.getElementById('confirmDeletePromaterialModal');
    const deletePromaterialButton = document.getElementById('deletePromaterialButton');
    const closePromaterialModalButton = document.getElementById('closePromaterialModalButton');

    modal.style.display = 'flex';

    deletePromaterialButton.onclick = () => {
        deletePromaterial('promaterial', promaterialId);
        modal.style.display = 'none';
    };

    closePromaterialModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

const deletePromaterial = (type, id) => {
    const data = {
        action: 'delete',
        type: 'promaterial',
        promaterial_id : id
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

/* PEO Archive ====================================================================== */
const updatePEOUI = (data) => {
    if (data.isCancelled === 0) {
        return;
    }
    
    // Check if the necessary fields are present and non-empty
    if (!data.peo_id) {
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
        const unarchiveButton = document.createElement('button');
        const deleteButton = document.createElement('button');
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

        unarchiveButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-top: 5px;
        `;

        deleteButton.style = `
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

            unarchiveButton.innerHTML  = '<i class="fa fa-refresh" aria-hidden="true"></i> Unarchive';
            unarchiveButton.onclick = () => showUnarchivePEOModal(data.peo_id);

            editButton.innerHTML  = '<i class="fa fa-pencil-square" aria-hidden="true"></i> Edit';
            editButton.onclick = () => {
                window.location.href = `edit_peo.php?peo_id=${data.peo_id}?=${data.peo_author}`;
            };

            if (userType !== 'Student' && userType !== 'Faculty' || data.peo_author === full_name) {
                contentDiv.appendChild(unarchiveButton);
                contentDiv.appendChild(deleteButton);
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

// 2. insertUnarchivePEOModalContent
const insertUnarchivePEOModalContent = () => {
    const modalContainer = document.getElementById('confirmUnarchivePEOModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeUnarchivePEOModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-refresh" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Proceed to unarchive this PEO?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelUnarchivePEOButton" class="cancel-button" type="button">Cancel</button>
                    <button id="unarchivePEOButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to unarchive</b></button>
                </div>
            </div>
        </div>
    `;
}

// 3. showUnarchivePEOModal
const showUnarchivePEOModal = (peoId, expirationDate, expirationTime) => {
    insertUnarchivePEOModalContent();
    const modal = document.getElementById('confirmUnarchivePEOModal');
    const cancelUnarchivePEOButton = document.getElementById('cancelUnarchivePEOButton');
    const unarchivePEOButton = document.getElementById('unarchivePEOButton');
    const closeUnarchivePEOModalButton = document.getElementById('closeUnarchivePEOModalButton');

    modal.style.display = 'flex';

    cancelUnarchivePEOButton.onclick = () => {
        modal.style.display = 'none';
    };

    unarchivePEOButton.onclick = () => {
        unarchivePEO('peo', peoId);
        modal.style.display = 'none';
    }

    closeUnarchivePEOModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 4. unarchivePEO
const unarchivePEO = (type, id) => {
    const data = {
        action: 'unarchive',
        type: 'peo',
        peo_id : id
    };
    Ws.send(JSON.stringify(data));
};

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
                    <button id="deletePEOButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

const showDeletePEOModal = (peoId) => {
    insertDeletePEOModalContent();
    const modal = document.getElementById('confirmDeletePEOModal');
    const deletePEOButton = document.getElementById('deletePEOButton');
    const closePEOModalButton = document.getElementById('closePEOModalButton');

    modal.style.display = 'flex';

    deletePEOButton.onclick = () => {
        deletePEO('peo', peoId);
        modal.style.display = 'none';
    };

    closePEOModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

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

/* SO Archive ====================================================================== */
const updateSOUI = (data) => {
    if (data.isCancelled === 0) {
        return;
    }

    // Check if the necessary fields are present and non-empty
    if (!data.so_id) {
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

            contentDiv.innerHTML = soContent;
        }
    } else {
        const containerDiv = document.createElement('div');
        const contentDiv = document.createElement('div');
        const postDiv = document.createElement('div');
        const unarchiveButton = document.createElement('button');
        const deleteButton = document.createElement('button');
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

        unarchiveButton.style = `
            background-color: #4e7251;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
            margin-top: 5px;
        `;

        deleteButton.style = `
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

            unarchiveButton.innerHTML  = '<i class="fa fa-refresh" aria-hidden="true"></i> Unarchive';
            unarchiveButton.onclick = () => showUnarchiveSOModal(data.so_id);

            editButton.innerHTML  = '<i class="fa fa-pencil-square" aria-hidden="true"></i> Edit';
            editButton.onclick = () => {
                window.location.href = `edit_so.php?so_id=${data.so_id}?=${data.so_author}`;
            };

            if (userType !== 'Student' && userType !== 'Faculty' || data.so_author === full_name) {
                contentDiv.appendChild(unarchiveButton);
                contentDiv.appendChild(deleteButton);
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

// 2. insertUnarchiveSOModalContent
const insertUnarchiveSOModalContent = () => {
    const modalContainer = document.getElementById('confirmUnarchiveSOModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeUnarchiveSOModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-refresh" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Proceed to unarchive this Student Outcome?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelUnarchiveSOButton" class="cancel-button" type="button">Cancel</button>
                    <button id="unarchiveSOButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to unarchive</b></button>
                </div>
            </div>
        </div>
    `;
}

// 3. showUnarchiveSOModal
const showUnarchiveSOModal = (soId, expirationDate, expirationTime) => {
    insertUnarchiveSOModalContent();
    const modal = document.getElementById('confirmUnarchiveSOModal');
    const cancelUnarchiveSOButton = document.getElementById('cancelUnarchiveSOButton');
    const unarchiveSOButton = document.getElementById('unarchiveSOButton');
    const closeUnarchiveSOModalButton = document.getElementById('closeUnarchiveSOModalButton');

    modal.style.display = 'flex';

    cancelUnarchiveSOButton.onclick = () => {
        modal.style.display = 'none';
    };

    unarchiveSOButton.onclick = () => {
        unarchiveSO('so', soId);
        modal.style.display = 'none';
    }

    closeUnarchiveSOModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

// 5. unarchiveSO
const unarchiveSO = (type, id) => {
    const data = {
        action: 'unarchive',
        type: 'so',
        so_id : id
    };
    Ws.send(JSON.stringify(data));
};

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
                    <button id="deleteSOButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

const showDeleteSOModal = (soId) => {
    insertDeleteSOModalContent();
    const modal = document.getElementById('confirmDeleteSOModal');
    const deleteSOButton = document.getElementById('deleteSOButton');
    const closeSOModalButton = document.getElementById('closeSOModalButton');

    modal.style.display = 'flex';

    deleteSOButton.onclick = () => {
        deleteSO('so', soId);
        modal.style.display = 'none';
    };

    closeSOModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
};

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
const fetchAndUpdateArchivedAnnouncements = () => {
    fetch('database/fetch_announcement.php')
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(announcement =>
                announcement.status !== 'Pending' &&
                announcement.isCancelled === 1
            );
            // Clear existing announcements
            annArchivedContainer.innerHTML = '';
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
        })
        .catch(error => console.error('Error fetching announcements:', error));
};

const fetchAndUpdateArchivedEvents = () => {
    fetch('database/fetch_event.php')
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(event =>
                event.status !== 'Pending' &&
                event.isCancelled === 1
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

const fetchAndUpdateArchivedNews = () => {
    fetch('database/fetch_news.php')
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(news =>
                news.status !== 'Pending' &&
                news.isCancelled === 1
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

const fetchAndUpdateArchivedPromaterials = () => {
    fetch('database/fetch_promaterial.php')
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(promaterial =>
                promaterial.status !== 'Pending' &&
                promaterial.isCancelled === 1
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

const fetchAndUpdateArchivedPEOs = () => {
    fetch('database/fetch_peo.php')
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(peo =>
                peo.isCancelled === 1
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

const fetchAndUpdateArchivedSOs = () => {
    fetch('database/fetch_so.php')
        .then(response => response.json())
        .then(data => {
            const filteredData = data.filter(so =>
                so.isCancelled === 1
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
    if (data.action === 'delete' || data.action === 'unarchive' || data.action === 'unarchive_and_update_expiration') {
        if (data.type === 'announcement' && data.success) {
            const announcementDiv = document.querySelector(`[data-ann-id="${data.announcement_id}"]`);
            if (announcementDiv) {
                announcementDiv.remove();
                fetchAndUpdateArchivedAnnouncements();
            }
        } else if (data.type === 'event' && data.success) {
            const eventDiv = document.querySelector(`[data-event-id="${data.event_id}"]`);
            if (eventDiv) {
                eventDiv.remove();
                fetchAndUpdateArchivedEvents();
            }
        } else if (data.type === 'news' && data.success) {
            const newsDiv = document.querySelector(`[data-news-id="${data.news_id}"]`);
            if (newsDiv) {
                newsDiv.remove();
                fetchAndUpdateArchivedNews();
            }
        } else if (data.type === 'promaterial' && data.success) {
            const promaterialDiv = document.querySelector(`[data-promaterials-id="${data.promaterial_id}"]`);
            if (promaterialDiv) {
                promaterialDiv.remove();
                fetchAndUpdateArchivedPromaterials();
            }
        } else if (data.type === 'peo' && data.success) {
            const peoDiv = document.querySelector(`[data-peo-id="${data.peo_id}"]`);
            if (peoDiv) {
                peoDiv.remove();
                fetchAndUpdateArchivedPEOs();
            }
        } else if (data.type === 'so' && data.success) {
            const soDiv = document.querySelector(`[data-so-id="${data.so_id}"]`);
            if (soDiv) {
                soDiv.remove();
                fetchAndUpdateArchivedSOs();
            }
        }
    } else if (data.action === 'archive' && data.success) {
        if (data.type === 'announcement') {
            fetchAndUpdateArchivedAnnouncements();
        } else if (data.type === 'event') {
            fetchAndUpdateArchivedEvents();
        } else if (data.type === 'news') {
            fetchAndUpdateArchivedNews();
        } else if (data.type === 'promaterial') {
            fetchAndUpdateArchivedPromaterials();
        } else if (data.type === 'peo') {
            fetchAndUpdateArchivedPEOs();
        } else if (data.type === 'so') {
            fetchAndUpdateArchivedSOs();
        }
    } else if (data.action === 'update') {
        if (data.type === 'announcement') {
            fetchAndUpdateArchivedAnnouncements();
        } else if (data.type === 'event') {
            fetchAndUpdateArchivedEvents();
        } else if (data.type === 'news') {
            fetchAndUpdateArchivedNews();
        } else if (data.type === 'promaterial') {
            fetchAndUpdateArchivedPromaterials();
        } else if (data.type === 'peo') {
            fetchAndUpdateArchivedPEOs();
        } else if (data.type === 'so') {
            fetchAndUpdateArchivedSOs();
        }
    } else if (data.action === 'approve_post' && data.success && data.announcement) {
        const announcementDiv = document.querySelector(`[data-ann-id="${data.announcement.announcement_id}"]`);
        if (announcementDiv) {
            announcementDiv.classList.add('approved');
            fetchAndUpdateArchivedAnnouncements();
        }
        // updateAnnouncementUI(data.announcement);
    } 
});

document.addEventListener('DOMContentLoaded', () => {
    fetchAndUpdateArchivedAnnouncements();
    fetchAndUpdateArchivedEvents();
    fetchAndUpdateArchivedNews();
    fetchAndUpdateArchivedPromaterials();
    fetchAndUpdateArchivedPEOs();
    fetchAndUpdateArchivedSOs();
});
