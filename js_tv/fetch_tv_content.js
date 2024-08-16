const Ws = new WebSocket('ws://192.168.1.12:8081');
const annCarouselContainer = document.getElementById('AnnouncementsCarouselContainer');
const mainContainer = document.getElementById('main-container');
const pageNumberContainer = document.createElement('div'); // Container for the page number

pageNumberContainer.classList.add('page-number-container')

let currentAnnIndex = 0;
let announcements = [];
let carouselInterval; // Variable to hold the interval ID
let displayTimeInterval; // Variable to hold the interval ID for display time

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

const updateAnnouncementUI = (data) => {
    // Check if the status is 'Pending' or if the announcement is canceled
    if (data.status === 'Pending' || data.isCancelled === 1 || !data.ann_id) {
        return;
    }

    // Format dates and times
    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    const formattedExpirationDate = formatDate(data.expiration_date);
    const formattedExpirationTime = formatTime(data.expiration_time);

    // Check if the announcement already exists in the DOM
    const existingAnnouncementDiv = document.querySelector(`[data-ann-id="${data.ann_id}"]`);

    let mediaContent = '';
    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);

        if (isImage) {
            mediaContent = `<img src="servers/announcements_media/${data.media_path}">`;
        } else if (isVideo) {
            mediaContent = `<video controls><source src="servers/announcements_media/${data.media_path}" type="video/mp4"></video>`;
        }
    }

    const contentHTML = `
        <div class="content-container-con">
            <div class="content-main">
                ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                <pre class="main-message" style="word-break: break-word; font-family: ${childFontFamily}">${data.ann_body}</pre>
            </div>
            <div class="content-details">
                <div class="line-separator"></div>
                <div style="display: flex; flex-direction: row; margin: 0">
                    <div>
                        <p class="author"><small>Posted by ${data.ann_author} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date" style="margin-bottom: 10px;"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                    </div>
                    <div style="margin-left: auto">
                        <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                    </div>
                </div>
            </div>
        </div>
    `;

    if (existingAnnouncementDiv) {
        // Update the existing announcement
        const contentDiv = existingAnnouncementDiv.querySelector('.content-container-con');
        if (contentDiv) {
            contentDiv.innerHTML = contentHTML;
        }

        // Reset the countdown for this announcement
        resetCountdown(existingAnnouncementDiv, data.display_time);
        updatePageNumber();
        console.log("existingAnnouncementDiv called");
    } else {
        // Create and append a new announcement
        const containerDiv = document.createElement('div');
        containerDiv.dataset.annId = data.ann_id;
        containerDiv.classList.add('carousel-item');
        containerDiv.dataset.displayTime = data.display_time; // Store the original display time

        const contentDiv = document.createElement('div');

        containerDiv.setAttribute('data-ann-id', data.ann_id);

        containerDiv.style = `
            width: auto;
            margin-bottom: 5px;
            height: 100%;
            border-radius: 5px;
        `;

        contentDiv.style = `
            border: none;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            padding: 10px;
            text-align: justify;
            height: calc(100% - 20px);
            bottom: 0;
        `;

        contentDiv.innerHTML = contentHTML;
        containerDiv.appendChild(contentDiv);
        
        // Only add the announcement to the array and DOM if it's not cancelled (archived)
        if (data.isCancelled !== 1) {
            announcements.push(containerDiv);
            annCarouselContainer.appendChild(containerDiv);
            annCarouselContainer.appendChild(pageNumberContainer);

            if (announcements.length === 1) {
                containerDiv.classList.add('active');
                updatePageNumber();
            } else {
                updatePageNumber();
            }
        }
        
        console.log("existingAnnouncement NOT CALLED");
    }
};

// Function to reset the countdown for an existing announcement
const resetCountdown = (announcementDiv, newDisplayTime) => {
    const timeLeftElement = announcementDiv.querySelector('.time-left');
    if (timeLeftElement) {
        timeLeftElement.textContent = `${newDisplayTime}s`;
        setCarouselInterval(newDisplayTime);  // Start countdown with the new display time
    }
};

const updatePageNumber = () => {
    if (announcements.length > 0) {
        pageNumberContainer.textContent = `${currentAnnIndex + 1} of ${announcements.length}`;
    } else {
        pageNumberContainer.textContent = '0 of 0';
    }
};

const showNextAnnouncement = () => {
    if (announcements.length > 0) {
        // Remove active class from the current announcement
        if (announcements[currentAnnIndex]) {
            announcements[currentAnnIndex].classList.remove('active');
        }

        // Move to the next announcement
        currentAnnIndex = (currentAnnIndex + 1) % announcements.length;

        // Set the new announcement as active
        if (announcements[currentAnnIndex]) {
            announcements[currentAnnIndex].classList.add('active');
            updatePageNumber();
            resetDisplayTime();
        } else {
            console.error("Invalid current announcement index.");
        }
    } else {
        pageNumberContainer.textContent = '';
        clearInterval(carouselInterval); // Stop carousel if no announcements
    }
};

const resetDisplayTime = () => {
    const currentAnnouncement = announcements[currentAnnIndex];
    const displayTimeElement = currentAnnouncement.querySelector('.display-time');
    const originalDisplayTime = currentAnnouncement.dataset.displayTime;

    if (displayTimeElement && originalDisplayTime) {
        let remainingTime = parseInt(originalDisplayTime, 10);
        displayTimeElement.innerHTML = `<i class="fa fa-hourglass-half" aria-hidden="true"></i> ${remainingTime}s`;

        if (displayTimeInterval) {
            clearInterval(displayTimeInterval);
        }

        displayTimeInterval = setInterval(() => {
            remainingTime -= 1;
            displayTimeElement.innerHTML = `<i class="fa fa-hourglass-half" aria-hidden="true"></i> ${remainingTime}s`;
            if (remainingTime < 0) {
                clearInterval(displayTimeInterval);
                showNextAnnouncement();
            }
        }, 1000);
    }
};

const setCarouselInterval = (displayTime) => {
    if (displayTimeInterval) {
        clearInterval(displayTimeInterval);
    }
    let timeLeft = displayTime;

    displayTimeInterval = setInterval(() => {
        const displayTimeElement = announcements[currentAnnIndex].querySelector('.time-left');
        if (timeLeft > 0) {
            timeLeft -= 1;
            displayTimeElement.textContent = `${timeLeft}s`;
        } else {
            clearInterval(displayTimeInterval);
            showNextAnnouncement();
        }
    }, 1000);
};

const startCarousel = () => {
    console.log('Starting carousel with announcements:', announcements);
    console.log('Current index:', currentAnnIndex);
    if (announcements.length > 0) {
        // Remove active class from all announcements
        announcements.forEach(ann => ann.classList.remove('active'));

        // Set the current announcement as active
        if (announcements[currentAnnIndex]) {
            announcements[currentAnnIndex].classList.add('active');
            resetDisplayTime();
        } else {
            console.error("Invalid current announcement index.");
        }
    } else {
        console.error("Cannot start carousel: No valid announcements available.");
        // Optionally handle this scenario
    }
};

// Function to display "No Announcement/s to be displayed" message
const displayNoAnnouncementsMessage = () => {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('no-announcements-message');
    messageDiv.textContent = 'No announcements to be displayed';
    annCarouselContainer.appendChild(messageDiv);
};

const fetchSmartTVName = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId');

    if (tvId) {
        fetch(`database/fetch_smart_tvs.php?tvId=${tvId}`)
            .then(response => response.json())
            .then(data => {
                const tvName = data.tv_name;
                updateTvName(tvName);
            })
            .catch(error => console.error('Error fetching TV name:', error));
    } else {
        console.error('TV ID not found in URL');
    }
};

const updateTvName = (newTvName) => {
    const tvNameElement = document.querySelector('.tv-name');
    if (tvNameElement) {
        tvNameElement.textContent = newTvName;
    }
};

const fetchAndUpdateAnnouncements = () => {
    // Clear existing announcements
    announcements = [];
    annCarouselContainer.innerHTML = '';

    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId');

    if (tvId) {
        fetch(`database/fetch_smart_tvs.php?tvId=${tvId}`)
            .then(response => response.json())
            .then(data => {
                const tvName = data.tv_name;
                // Update the TV name in the DOM
                // document.getElementById('tvName').textContent = tvName;
                fetch('database/fetch_announcements.php')
                    .then(response => response.json())
                    .then(announcementData => {
                        const filteredData = announcementData.filter(announcement =>
                            announcement.status !== 'Pending' &&
                            announcement.tv_display === tvName &&
                            announcement.isCancelled === 0
                        );
                        filteredData.forEach((announcement) => {
                            updateAnnouncementUI(announcement);
                        });

                        // Check if there are announcements
                        if (announcements.length > 0) {
                            // Ensure the current index is valid
                            currentAnnIndex = Math.min(currentAnnIndex, announcements.length - 1);
                            startCarousel();
                        } else if (announcements.length == 0) {
                            displayNoAnnouncementsMessage();
                            clearInterval(carouselInterval); // Ensure carousel interval is cleared
                        }
                        updatePageNumber();
                    })
                    .catch(error => console.error('Error fetching announcements:', error));
            })
            .catch(error => console.error('Error fetching TV data:', error));
    } else {
        console.error('TV ID not found in URL');
    }
};

Ws.addEventListener('message', function (event) {
    const data = JSON.parse(event.data);
    if (data.action === 'delete' || data.action === 'archive') {
        if (data.type === 'announcement' && data.success) {
            // Remove announcement from DOM and update index
            const announcementDiv = document.querySelector(`[data-ann-id="${data.ann_id}"]`);
            if (announcementDiv) {
                const indexToRemove = announcements.findIndex(ann => ann.dataset.annId === data.ann_id);
                if (indexToRemove !== -1) {
                    announcements.splice(indexToRemove, 1);
                    // Adjust the current index
                    currentAnnIndex = Math.max(0, currentAnnIndex - 1);
                }
                announcementDiv.remove();

                fetchAndUpdateAnnouncements();
            }
        }
    } else if (data.action === 'unarchive' || data.action === 'unarchive_and_update_expiration') {
        if (data.type === 'announcement') {
            fetchAndUpdateAnnouncements();
            // Ensure the current index is valid
            if (announcements.length > 0) {
                currentAnnIndex = Math.min(currentAnnIndex, announcements.length - 1);
            }
        }
    } else if (data.action === 'update') {
        if (data.type === 'announcement') {
            fetchAndUpdateAnnouncements();
        }
    } else if (data.action === 'post_content') {
        if (data.type === 'announcement') {
            fetchAndUpdateAnnouncements();
        }
    } else if (data.action === 'edit_smart_tv') {
        fetchSmartTVName();
        location.reload();
    } else if (data.action === 'update_container_dimensions' || data.action === 'show_hide_content') {
        const urlParams = new URLSearchParams(window.location.search);
        const tvId = urlParams.get('tvId');
        
        // convert to base 10 decimal int
        if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
            console.log(data.tv_id); // Debug logging
            location.reload();
        } else {
            console.log('No reload required. Current tvId:', tvId, 'Received tvId:', data.tv_id); // Debug logging
            // No reload in this branch as per the logic
        }
    } else if (data.action === 'update_background_color') {
        // Update tv background without refreshing the entire page
        if (data.success) {
            const backgroundElement = document.getElementById('tvBackgroundColor');
            // Update the <body> background color
            backgroundElement.style.backgroundColor = data.background_hex_color;
        } else {
            console.error('Failed to update background color:', data.message);
        }
        
    } else if (data.action === 'update_container_colors') {
        // Update container colors without refreshing the entire page
        if (data.success) {
            const containers = data.containers;
            
            containers.forEach(container => {
                const containerElement = document.querySelector(`[data-container-id="${container.container_id}"]`);
                if (containerElement) {
                    // Update the container's background and font colors
                    containerElement.style.backgroundColor = container.parent_background_color;
                    containerElement.querySelector('.content-title').style.color = container.parent_font_color;
                    containerElement.querySelector('.content-title').style.fontStyle = container.parent_font_style;
                    containerElement.querySelector('.content-title').style.fontFamily = container.parent_font_family;

                    const carouselContainer = containerElement.querySelector('.carousel-container');
                    if (carouselContainer) {
                        // Update the carousel container's background and font colors
                        carouselContainer.style.backgroundColor = container.child_background_color;
                        carouselContainer.style.color = container.child_font_color;
                        carouselContainer.style.fontStyle = container.child_font_style;
                    }
                }
            });
        } else {
            console.error('Failed to update container colors:', data.message);
        }
    } else if (data.action === 'update_topbar_color') {
        // Update topbar colors and styles without refreshing the entire page
        if (data.success) {
            const topbarElement = document.getElementById('topbar');
            if (topbarElement) {
                topbarElement.style.backgroundColor = data.topbar_hex_color;
                const tvNameElement = topbarElement.querySelector('.tv-name');
                const deviceIdElement = topbarElement.querySelector('.device-id');
                const timeElement = topbarElement.querySelector('.time');
                const dateElement = topbarElement.querySelector('.date');
    
                if (tvNameElement) {
                    tvNameElement.style.color = data.topbar_tvname_font_color;
                    tvNameElement.style.fontStyle = data.topbar_tvname_font_style;
                    tvNameElement.style.fontFamily = data.topbar_tvname_font_family;
                }
                if (deviceIdElement) {
                    deviceIdElement.style.color = data.topbar_deviceid_font_color;
                    deviceIdElement.style.fontStyle = data.topbar_deviceid_font_style;
                    deviceIdElement.style.fontFamily = data.topbar_deviceid_font_family;
                }
                if (timeElement) {
                    timeElement.style.color = data.topbar_time_font_color;
                    timeElement.style.fontStyle = data.topbar_time_font_style;
                    timeElement.style.fontFamily = data.topbar_time_font_family;
                }
                if (dateElement) {
                    dateElement.style.color = data.topbar_date_font_color;
                    dateElement.style.fontStyle = data.topbar_date_font_style;
                    dateElement.style.fontFamily = data.topbar_date_font_family;
                }
            }
        }
    } else if (data.action === 'save_layout') {
        // update the grid in real-time without refreshing the whole page
        if (data.success) {
            // Re-fetch and apply the layout to update the grid in real-time
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');

            const message = JSON.stringify({
                action: 'load_layout',
                tv_id: tvId
            });

            Ws.send(message); // Request the updated layout
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    fetchAndUpdateAnnouncements();
    fetchSmartTVName();
});

