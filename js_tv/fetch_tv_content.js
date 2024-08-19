const Ws = new WebSocket('ws://192.168.1.12:8081');

// Function to get the containers based on type
const getContainerElements = (type) => {
    const carouselContainer = document.getElementById(`${type}sCarouselContainer`);
    const pageNumberContainer = document.getElementById(`${type}sPageNumberContainer`);

    // Check if the containers are found
    if (!carouselContainer) {
        console.error(`Carousel container for ${type} not found.`);
    }
    if (!pageNumberContainer) {
        console.error(`Page number container for ${type} not found.`);
    }

    return {
        carouselContainer,
        pageNumberContainer
    };
};

let currentIndex = { announcement: 0, event: 0, news: 0 };
let contents = { announcements: [], events: [], news: [] };
let displayTimeIntervals = { announcement: null, event: null, news: null };

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
    const { carouselContainer, pageNumberContainer } = getContainerElements(type);
    const currentIndexKey = type;
    const contentsArray = contents[`${type}s`];

    if (data.status === 'Pending' || data.isCancelled === 1 || !data[`${type}s_id`]) {
        return;
    }

    const formattedCreatedDate = formatDate(data.created_date);
    const formattedCreatedTime = formatTime(data.created_time);
    const formattedExpirationDate = formatDate(data.expiration_date);
    const formattedExpirationTime = formatTime(data.expiration_time);
    const id = data[`${type}s_id`]; // Assuming the ID of the item to delete/archive is sent in the message

    // const existingDiv = document.querySelector(`[data-${type}-id="${data[`${type}s_id`]}"]`);
    const existingDiv = document.querySelector(`[data-${type}-id="${id}"]`);
    let mediaContent = '';

    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}s_media/${data.media_path}">` :
            isVideo ? `<video controls><source src="servers/${type}s_media/${data.media_path}" type="video/mp4"></video>` : '';
    }

    let contentHTML = '';

    if (type === 'announcement') {
        contentHTML = `
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
    } else if (type === 'event') {
        contentHTML = `
            <div class="content-container-con">
                <div class="content-main">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <p class="main-message" style="word-break: break-word;">${data.event_heading}</p>
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
    } else if (type === 'new') {
        contentHTML = `
            <div class="content-container-con">
                <div class="content-main">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <p class="main-message" style="word-break: break-word;">${data.news_heading}</p>
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
    }

    if (existingDiv) {
        const contentDiv = existingDiv.querySelector('.content-container-con');
        if (contentDiv) {
            contentDiv.innerHTML = contentHTML;
        }
        resetCountdown(existingDiv, data.display_time, currentIndexKey);
        updatePageNumber(currentIndexKey);

        console.log("existingDiv called");
    } else {
        const containerDiv = document.createElement('div');
        containerDiv.dataset[`${type}Id`] = data[`${type}s_id`];
        containerDiv.classList.add('carousel-item');
        containerDiv.dataset.displayTime = data.display_time;

        const contentDiv = document.createElement('div');

        // containerDiv.setAttribute('data-announcement-id', data.announcements_id);

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
        carouselContainer.appendChild(containerDiv);
        // carouselContainer.appendChild(pageNumberContainer);

        if (contentsArray.length === 1) {
            containerDiv.classList.add('active');
            updatePageNumber(currentIndexKey);
        } else {
            updatePageNumber(currentIndexKey);
        }

        console.log("existingDiv NOT called");
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
    const { pageNumberContainer } = getContainerElements(type); // Get the page number container dynamically
    const currentIndexKey = type;
    const contentsArray = contents[`${type}s`];

    if (contentsArray.length > 0) {
        pageNumberContainer.textContent = `${currentIndex[currentIndexKey] + 1} of ${contentsArray.length}`;
    } else {
        pageNumberContainer.textContent = '0 of 0';
    }
};

// Function to show next content
const showNextContent = (type) => {
    const currentIndexKey = type;
    const contentsArray = contents[`${type}s`];

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
    const currentContent = contents[`${type}s`][currentIndex[type]];
    // const currentContent = type === 'announcement' ? contents.announcements[currentIndex.announcement] : contents.events[currentIndex.event];
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
        const displayTimeElement = contents[`${type}s`][currentIndex[type]].querySelector('.time-left');
        // const displayTimeElement = type === 'announcement' ? contents.announcements[currentIndex.announcement].querySelector('.time-left') : contents.events[currentIndex.event].querySelector('.time-left');
        if (timeLeft > 0) {
            timeLeft -= 1;
            displayTimeElement.textContent = `${timeLeft}s`;
        } else {
            clearInterval(displayTimeIntervals[type]);
            showNextContent(type);
        }
    }, 1000);
};

// const startAnnouncementCarousel = () => {
//     console.log('Starting carousel with announcements:', announcements);
//     console.log('Current index:', announcementCurrentIndex);
//     if (announcements.length > 0) {
//         // Remove active class from all announcements
//         announcements.forEach(ann => ann.classList.remove('active'));

//         // Set the current announcement as active
//         if (announcements[announcementCurrentIndex]) {
//             announcements[announcementCurrentIndex].classList.add('active');
//             resetAnnouncementDisplayTime();
//         } else {
//             console.error("Invalid current announcement index.");
//         }
//     } else {
//         console.error("Cannot start carousel: No valid announcements available.");
//         // Optionally handle this scenario
//     }
// };

// const startEventCarousel = () => {
//     console.log('Starting carousel with events:', events);
//     console.log('Current index:', eventCurrentIndex);
//     if (events.length > 0) {
//         // Remove active class from all event
//         events.forEach(event => event.classList.remove('active'));

//         // Set the current event as active
//         if (events[eventCurrentIndex]) {
//             events[eventCurrentIndex].classList.add('active');
//             resetEventDisplayTime();
//         } else {
//             console.error("Invalid current event index.");
//         }
//     } else {
//         console.error("Cannot start carousel: No valid events available.");
//         // Optionally handle this scenario
//     }
// }; 

// const startNewsCarousel = () => {
//     console.log('Starting carousel with events:', events);
//     console.log('Current index:', eventCurrentIndex);
//     if (events.length > 0) {
//         // Remove active class from all event
//         events.forEach(event => event.classList.remove('active'));

//         // Set the current event as active
//         if (events[eventCurrentIndex]) {
//             events[eventCurrentIndex].classList.add('active');
//             resetEventDisplayTime();
//         } else {
//             console.error("Invalid current event index.");
//         }
//     } else {
//         console.error("Cannot start carousel: No valid events available.");
//         // Optionally handle this scenario
//     }
// };

// Function to display "No Announcement/s to be displayed" message
// const displayNoAnnouncementsMessage = () => {
//     const messageDiv = document.createElement('div');
//     messageDiv.classList.add('no-announcements-message');
//     messageDiv.textContent = 'No announcements to be displayed';
//     announcementCarouselContainer.appendChild(messageDiv);
// };

// Function to display "No Event/s to be displayed" message
// const displayNoEventsMessage = () => {
//     const messageDiv = document.createElement('div');
//     messageDiv.classList.add('no-events-message');
//     messageDiv.textContent = 'No events to be displayed';
//     eventCarouselContainer.appendChild(messageDiv);
// };

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
    const { carouselContainer, pageNumberContainer } = getContainerElements(type); // Get the containers dynamically
    carouselContainer.innerHTML = ''; // Clear the container
    pageNumberContainer.innerHTML = ''; // Clear the container

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
        const type = data.type;
        const id = data[`${type}s_id`]; 

        const removeContent = (type, id) => {
            const contentDiv = document.querySelector(`[data-${type}-id="${id}"]`);
            if (contentDiv) {
                const indexToRemove = contents[`${type}s`].findIndex(item => item.dataset[`${type}Id`] === id);
                if (indexToRemove !== 0) {
                    contents[`${type}s`].splice(indexToRemove, 1); // Remove from the array
                    currentIndex[type] = Math.max(0, currentIndex[type] - 1); // Adjust the current index
                }
                contentDiv.remove(); // Remove from DOM
                fetchAndUpdateContents(type);
            }
        };

        if (data.success) {
            removeContent(type, id); // Call the helper function
        }
        
    } else if (data.action === 'unarchive' || data.action === 'unarchive_and_update_expiration') {
        
    } else if (data.action === 'update') {
        
    } else if (data.action === 'post_content') {
        
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
                    const titleElement = containerElement.querySelector('.content-title');
                    if (titleElement) {
                        titleElement.style.color = container.parent_font_color;
                        titleElement.style.fontFamily = container.parent_font_family;
                        titleElement.style.fontStyle = container.parent_font_style;
                    }

                    // Update the carousel container styles
                    const { carouselContainer } = getContainerElements(container.type); // Get the carousel container
                    if (carouselContainer) {
                        carouselContainer.style.backgroundColor = container.child_background_color; // Change the background color
                        carouselContainer.style.color = container.child_font_color; // Change the text color
                        carouselContainer.style.fontFamily = container.child_font_family; // Change the font family
                        carouselContainer.style.fontStyle = container.child_font_style; // Change the font style
                    } else {
                        console.error(`Carousel container not found for type: ${container.type}`);
                    }
                    
                    // Update the page number container styles
                    const { pageNumberContainer } = getContainerElements(container.type);
                    if (pageNumberContainer) {
                        pageNumberContainer.style.color = container.parent_font_color; // Change the color
                        pageNumberContainer.style.fontFamily = container.parent_font_family; // Change the font family
                        pageNumberContainer.style.fontStyle = container.parent_font_style; // Change the font style
                    } else {
                        console.error(`Page number container not found for type: ${container.type}`);
                    }
                } else {
                    console.error(`Container not found for ID: ${container.container_id}`);
                }
            });
        } else {
            console.error('Failed to update container colors:', data.message);
        }
    } else if (data.action === 'update_topbar_color') {
        if (data.success) {
            const topbarElement = document.getElementById('topbar');
            if (topbarElement) {
                topbarElement.style.backgroundColor = data.topbar_hex_color;
                topbarElement.querySelector('.tv-name').style.color = data.topbar_tvname_font_color;
                topbarElement.querySelector('.device-id').style.color = data.topbar_deviceid_font_color;
                topbarElement.querySelector('.time').style.color = data.topbar_time_font_color;
                topbarElement.querySelector('.date').style.color = data.topbar_date_font_color;
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
    fetchAndUpdateContents('new');
    fetchSmartTVName();
});