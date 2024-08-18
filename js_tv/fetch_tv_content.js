const Ws = new WebSocket('ws://192.168.1.12:8081');
const announcementCarouselContainer = document.getElementById('AnnouncementsCarouselContainer');
const eventCarouselContainer = document.getElementById('EventsCarouselContainer');
const announcementPageNumberContainer = document.getElementById('AnnouncementsPageNumberContainer');
const eventPageNumberContainer = document.getElementById('EventsPageNumberContainer');

let currentIndex = { announcement: 0, event: 0 };
let contents = { announcements: [], events: [] };
let displayTimeIntervals = { announcement: null, event: null };

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

// Unified function to update UI for announcements and events
const updateUI = (data, type) => {
    const isAnnouncement = type === 'announcement';
    const container = isAnnouncement ? announcementCarouselContainer : eventCarouselContainer;
    const pageNumberContainer = isAnnouncement ? announcementPageNumberContainer : eventPageNumberContainer;
    const currentIndexKey = isAnnouncement ? 'announcement' : 'event';
    const contentsArray = isAnnouncement ? contents.announcements : contents.events;

    if (data.status === 'Pending' || data.isCancelled === 1 || !data[`${type}s_id`]) {
        return;
    }

    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    const formattedExpirationDate = formatDate(data.expiration_date);
    const formattedExpirationTime = formatTime(data.expiration_time);

    const existingDiv = document.querySelector(`[data-${type}-id="${data[`${type}s_id`]}"]`);
    let mediaContent = '';

    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}s_media/${data.media_path}">` :
            isVideo ? `<video controls><source src="servers/${type}s_media/${data.media_path}" type="video/mp4"></video>` : '';
    }

    const contentHTML = `
        <div class="content-container-con">
            <div class="content-main">
                ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                <p class="main-message" style="word-break: break-word;">${data.ann_body}</p>
            </div>
            <div class="content-details">
                <div style="display: flex; flex-direction: row; margin: 0">
                    <div>
                        <p class="author"><small>Posted by ${data[`${type}s_author`]} on ${formattedCreatedDate} at ${formattedCreatedTime}</small></p>
                        <p class="expiration-date"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
                    </div>
                    <div style="margin-left: auto; margin-top: auto">
                        <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                    </div>
                </div>
            </div>
        </div>
    `;

    if (existingDiv) {
        const contentDiv = existingDiv.querySelector('.content-container-con');
        if (contentDiv) {
            contentDiv.innerHTML = contentHTML;
        }
        resetCountdown(existingDiv, data.display_time, currentIndexKey);
        updatePageNumber(currentIndexKey);
    } else {
        const containerDiv = document.createElement('div');
        containerDiv.dataset[`${type}Id`] = data[`${type}s_id`];
        containerDiv.classList.add('carousel-item');
        containerDiv.dataset.displayTime = data.display_time;

        const contentDiv = document.createElement('div');

        containerDiv.setAttribute('data-announcement-id', data.announcements_id);

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
        contentsArray.push(containerDiv);
        container.appendChild(containerDiv);
        container.appendChild(pageNumberContainer);

        if (contentsArray.length === 1) {
            containerDiv.classList.add('active');
            updatePageNumber(currentIndexKey);
        } else {
            updatePageNumber(currentIndexKey);
        }
    }
};

// Function to reset countdown for announcements and events
const resetCountdown = (div, newDisplayTime, type) => {
    const timeLeftElement = div.querySelector('.time-left');
    if (timeLeftElement) {
        timeLeftElement.textContent = `${newDisplayTime}s`;
        setCarouselInterval(newDisplayTime, type);
    }
};

// Function to update page number
const updatePageNumber = (type) => {
    const currentIndexKey = type === 'announcement' ? 'announcement' : 'event';
    const pageNumberContainer = type === 'announcement' ? announcementPageNumberContainer : eventPageNumberContainer;
    const contentsArray = type === 'announcement' ? contents.announcements : contents.events;

    if (contentsArray.length > 0) {
        pageNumberContainer.textContent = `${currentIndex[currentIndexKey] + 1} of ${contentsArray.length}`;
    } else {
        pageNumberContainer.textContent = '0 of 0';
    }
};

// Function to show next content
const showNextContent = (type) => {
    const contentsArray = type === 'announcement' ? contents.announcements : contents.events;
    const currentIndexKey = type === 'announcement' ? 'announcement' : 'event';

    if (contentsArray.length > 0) {
        // Remove active class from the current item
        if (contentsArray[currentIndex[currentIndexKey]]) {
            contentsArray[currentIndex[currentIndexKey]].classList.remove('active');
        }

        // Increment the index and wrap around if necessary
        currentIndex[currentIndexKey] = (currentIndex[currentIndexKey] + 1) % contentsArray.length;

        // Add active class to the new current item
        if (contentsArray[currentIndex[currentIndexKey]]) {
            contentsArray[currentIndex[currentIndexKey]].classList.add('active');
            updatePageNumber(currentIndexKey);
            resetDisplayTime(type);
        }
    } else {
        clearInterval(displayTimeIntervals[currentIndexKey]);
    }
};

// Function to reset display time
const resetDisplayTime = (type) => {
    const currentContent = type === 'announcement' ? contents.announcements[currentIndex.announcement] : contents.events[currentIndex.event];
    const displayTimeElement = currentContent.querySelector('.display-time');
    const originalDisplayTime = currentContent.dataset.displayTime;

    if (displayTimeElement && originalDisplayTime) {
        let remainingTime = parseInt(originalDisplayTime, 10);
        displayTimeElement.innerHTML = `<i class="fa fa-hourglass-half" aria-hidden="true"></i> ${remainingTime}s`;

        if (displayTimeIntervals[type]) {
            clearInterval(displayTimeIntervals[type]);
        }

        displayTimeIntervals[type] = setInterval(() => {
            remainingTime -= 1;
            displayTimeElement.innerHTML = `<i class="fa fa-hourglass-half" aria-hidden="true"></i> ${remainingTime}s`;
            if (remainingTime < 0) {
                clearInterval(displayTimeIntervals[type]);
                showNextContent(type);
            }
        }, 1000);
    }
};

// Function to set carousel interval
const setCarouselInterval = (displayTime, type) => {
    if (displayTimeIntervals[type]) {
        clearInterval(displayTimeIntervals[type]);
    }
    let timeLeft = displayTime;

    displayTimeIntervals[type] = setInterval(() => {
        const displayTimeElement = type === 'announcement' ? contents.announcements[currentIndex.announcement].querySelector('.time-left') : contents.events[currentIndex.event].querySelector('.time-left');
        if (timeLeft > 0) {
            timeLeft -= 1;
            displayTimeElement.textContent = `${timeLeft}s`;
        } else {
            clearInterval(displayTimeIntervals[type]);
            showNextContent(type);
        }
    }, 1000);
};

const startAnnouncementCarousel = () => {
    console.log('Starting carousel with announcements:', announcements);
    console.log('Current index:', announcementCurrentIndex);
    if (announcements.length > 0) {
        // Remove active class from all announcements
        announcements.forEach(ann => ann.classList.remove('active'));

        // Set the current announcement as active
        if (announcements[announcementCurrentIndex]) {
            announcements[announcementCurrentIndex].classList.add('active');
            resetAnnouncementDisplayTime();
        } else {
            console.error("Invalid current announcement index.");
        }
    } else {
        console.error("Cannot start carousel: No valid announcements available.");
        // Optionally handle this scenario
    }
};

const startEventCarousel = () => {
    console.log('Starting carousel with events:', events);
    console.log('Current index:', eventCurrentIndex);
    if (events.length > 0) {
        // Remove active class from all event
        events.forEach(event => event.classList.remove('active'));

        // Set the current event as active
        if (events[eventCurrentIndex]) {
            events[eventCurrentIndex].classList.add('active');
            resetEventDisplayTime();
        } else {
            console.error("Invalid current event index.");
        }
    } else {
        console.error("Cannot start carousel: No valid events available.");
        // Optionally handle this scenario
    }
};

// Function to display "No Announcement/s to be displayed" message
const displayNoAnnouncementsMessage = () => {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('no-announcements-message');
    messageDiv.textContent = 'No announcements to be displayed';
    announcementCarouselContainer.appendChild(messageDiv);
};

// Function to display "No Event/s to be displayed" message
const displayNoEventsMessage = () => {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('no-events-message');
    messageDiv.textContent = 'No events to be displayed';
    eventCarouselContainer.appendChild(messageDiv);
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

// Function to fetch and update contents
const fetchAndUpdateContents = (type) => {
    contents[type + 's'] = [];
    const container = type === 'announcement' ? announcementCarouselContainer : eventCarouselContainer;
    container.innerHTML = '';

    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId');

    if (tvId) {
        fetch(`database/fetch_smart_tvs.php?tvId=${tvId}`)
            .then(response => response.json())
            .then(data => {
                const tvName = data.tv_name;
                fetch(`database/fetch_${type}s.php`)
                    .then(response => response.json())
                    .then(data => {
                        const filteredData = data.filter(item =>
                            item.status !== 'Pending' &&
                            item.tv_display === tvName &&
                            item.isCancelled === 0
                        );
                        filteredData.forEach(item => updateUI(item, type));
                        if (contents[type + 's'].length > 0) {
                            // Set the current index to 0 to start from the first item
                            currentIndex[type] = 0; // Ensure it starts at the first item
                            // Set the first item as active
                            contents[type + 's'][currentIndex[type]].classList.add('active');
                            updatePageNumber(type); // Update the page number to 1
                            resetDisplayTime(type); // Start the display time countdown
                        }
                    })
                    .catch(error => console.error(`Error fetching ${type}s:`, error));
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
            const announcementDiv = document.querySelector(`[data-announcement-id="${data.announcements_id}"]`);
            if (announcementDiv) {
                contents.announcements = contents.announcements.filter(ann => ann.dataset.announcementId !== data.announcements_id);
                announcementDiv.remove();
                fetchAndUpdateContents('announcement');
            }
        }
    } else if (data.action === 'unarchive' || data.action === 'unarchive_and_update_expiration') {
        if (data.type === 'announcement') {
            fetchAndUpdateContents('announcement');
        }
    } else if (data.action === 'update') {
        if (data.type === 'announcement') {
            fetchAndUpdateContents('announcement');
        }
    } else if (data.action === 'post_content') {
        if (data.type === 'announcement') {
            fetchAndUpdateContents('announcement');
        }
    } else if (data.action === 'edit_smart_tv') {
        fetchSmartTVName();
        location.reload();
    } else if (data.action === 'update_container_dimensions' || data.action === 'show_hide_content') {
        const urlParams = new URLSearchParams(window.location.search);
        const tvId = urlParams.get('tvId');
        if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
            location.reload();
        }
    } else if (data.action === 'update_background_color') {
        if (data.success) {
            const backgroundElement = document.getElementById('tvBackgroundColor');
            backgroundElement.style.backgroundColor = data.background_hex_color;
        }
    } else if (data.action === 'update_container_colors') {
        if (data.success) {
            const containers = data.containers;
            containers.forEach(container => {
                const containerElement = document.querySelector(`[data-container-id="${container.container_id}"]`);
                if (containerElement) {
                    containerElement.style.backgroundColor = container.parent_background_color;
                    containerElement.querySelector('.content-title').style.color = container.parent_font_color;
                }
            });
        }
    } else if (data.action === 'update_topbar_color') {
        if (data.success) {
            const topbarElement = document.getElementById('topbar');
            if (topbarElement) {
                topbarElement.style.backgroundColor = data.topbar_hex_color;
            }
        }
    } else if (data.action === 'save_layout') {
        if (data.success) {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            const message = JSON.stringify({ action: 'load_layout', tv_id: tvId });
            Ws.send(message);
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    fetchAndUpdateContents('announcement');
    fetchAndUpdateContents('event');
    fetchSmartTVName();
});