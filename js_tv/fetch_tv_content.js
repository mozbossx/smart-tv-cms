let isConnectedToWebSocket = false;

// Check if we're in an iframe - move this function to the top
function isIframe() {
    return window.self !== window.top;
}

function updateTvStatus(status) {
    const urlParams = new URLSearchParams(window.location.search);
    const currentTvId = urlParams.get('tvId');
    Ws.send(JSON.stringify({ action: 'update_tv_status', status: status, tvId: currentTvId }));
}

function connectWebSocket() {    
    // Determine if we're in localhost or production
    const isLocalhost = window.location.hostname === 'localhost' || 
                       window.location.hostname === '127.0.0.1' ||
                       window.location.hostname === '192.168.1.12' ||
                       window.location.hostname === '192.168.10.249';
    
    // Choose WebSocket URL based on environment
    const wsUrl = isLocalhost 
        ? 'ws://192.168.10.249:8081'
        : 'wss://usccms.online/ws';
    
    try {
        const ws = new WebSocket(wsUrl);
        let clientPingInterval;
        let clientPingTimeout;

        const heartbeat = () => {
            clearTimeout(clientPingTimeout);
            clientPingTimeout = setTimeout(() => {
                ws.close();
            }, 20000); // 20 second timeout
        };
        
        ws.onopen = function() {
            isConnectedToWebSocket = true;

            if (!isIframe()) {
                // Periodic ping to update last_ping
                setInterval(() => {
                    if (isConnectedToWebSocket && Ws && Ws.readyState === WebSocket.OPEN) {
                        updateTvStatus('active');
                    }
                }, 15000); // Send ping every 15 seconds
            }

            // Client-initiated pings
            clientPingInterval = setInterval(() => {
                if (ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({ action: 'ping' }));
                    heartbeat();
                }
            }, 15000); // 15 second interval

            if (loadingWebsocketConnectionModal.style.display === 'flex' && successWebsocketConnectionModal) {
                loadingWebsocketConnectionModal.style.display = 'none';
                successWebsocketConnectionModal.style.display = 'flex';
                successWebsocketConnectionMessage.textContent = 'Server Connected Successfully! Reloading in 3 seconds...';
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            }
        };

        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            
            // Handle ping/pong messages
            switch(data.action) {
                case 'ping':
                    // Respond to server's ping
                    ws.send(JSON.stringify({ action: 'pong' }));
                    return;
                case 'pong':
                    // Reset client's heartbeat timeout
                    heartbeat();
                    return;
            }
        };
        
        ws.onclose = function(event) {
            isConnectedToWebSocket = false;
            clearInterval(clientPingInterval);
            clearTimeout(clientPingTimeout);

            updateTvStatus('inactive');

            if (loadingWebsocketConnectionModal) {
                loadingWebsocketConnectionModal.style.display = 'flex';
                successWebsocketConnectionModal.style.display = 'none';
                loadingWebsocketConnectionMessage.textContent = 'Server Disconnected. Reconnecting...';
            }

            // Attempt to reconnect
            setTimeout(() => {
                Ws = connectWebSocket();
            }, 5000);
        };
        
        return ws;
    } catch (error) {
        console.error('Client: Error creating WebSocket:', error.message);
        isConnectedToWebSocket = false;
        
        loadingWebsocketConnectionModal.style.display = 'none';
        successWebsocketConnectionModal.style.display = 'flex';
        successWebsocketConnectionMessage.textContent = 'There was an error connecting to the WebSocket. Please try again later.';
        setTimeout(() => {
            Ws = connectWebSocket();
        }, 5000);
        return null;
    }
}

let Ws = null;

try {
    Ws = connectWebSocket();
} catch (error) {
    setTimeout(() => {
        Ws = connectWebSocket();
    }, 5000);
}

// Function to get the containers based on type
const getContainerElements = (type) => {
    const carouselContainer = document.getElementById(`${type}CarouselContainer`);
    const pageNumberContainer = document.getElementById(`${type}PageNumberContainer`);
    const containerName = document.getElementById(`${type}ContainerName`);

    // Check if the containers are found
    if (!carouselContainer) {
        console.error(`Carousel container for ${type} not found from getContainerElements in fetch_tv_content.js.`);
    }
    if (!pageNumberContainer) {
        console.error(`Page number container for ${type} not found from getContainerElements in fetch_tv_content.js.`);
    }

    if (!containerName) {
        console.error(`Container name for ${type} not found from getContainerElements in fetch_tv_content.js.`);
    }

    return {
        carouselContainer,
        pageNumberContainer,
        containerName
    };
};

let currentIndex = { 
    announcement: 0, 
    event: 0, 
    news: 0, 
    promaterial: 0,
    peo: 0, 
    so: 0,
    orgchart: 0,
    facility: 0
};

let contents = { 
    announcements: [], 
    events: [], 
    news: [], 
    promaterial: [],
    peo: [],
    so: [],
    orgcharts: [],
    facilitys: []
};

let displayTimeIntervals = { 
    announcement: null, 
    event: null, 
    news: null,
    promaterial: null,
    peo: null,
    so: null,
    orgchart: null,
    facility: null
};

// Function to format date to "MM DD YYYY"
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

// Function to format time to "01:00pm"
const formatTime = (timeString) => {
    const time = new Date(`1970-01-01T${timeString}Z`);
    const options = { hour: '2-digit', minute: '2-digit' };
    return time.toLocaleTimeString('en-US', options).replace(/(:\d{2})$/, '').toLowerCase();
};

// Function to get user type priority (higher number = higher priority)
const getUserTypePriority = (userType) => {
    switch(userType) {
        case 'Super Admin':
            return 5;
        case 'Admin':
            return 4;
        case 'Faculty':
            return 3;
        case 'Staff':
            return 2;
        case 'Student':
            return 1;
        default:
            return 0;
    }
};

// Function to update UI
const updateUI = (data, type) => {
    const { carouselContainer, pageNumberContainer } = getContainerElements(type);
    const currentIndexKey = type;
    const contentsArray = contents[`${type}s`];

    // Check status based on content type
    if (type === 'orgchart') {
        // No status check needed for orgchart
        if (!data[`${type}_id`]) {
            return;
        }
    } else if (type === 'peo' || type === 'so' || type === 'facility') {
        if (data.isArchived === 1 || data.isTrashed === 1 || !data[`${type}_id`]) {
            return;
        }
    } else {
        if (data.status !== 'Approved' && data.is_urgent_post !== 1 || data.isArchived === 1 || data.isTrashed === 1 || !data[`${type}_id`]) {
            return;
        }
    }
    
    const formattedCreatedDateTime = type !== 'orgchart' ? formatDateTime(data.created_datetime) : '';
    const id = data[`${type}_id`]; 

    // Check if content with this ID already exists
    const existingDiv = document.querySelector(`[data-${type}-id="${id}"]`);
    
    let mediaContent = '';
    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}_media/${data.media_path}">` :
            isVideo ? `<video preload="metadata" muted autoplay><source src="servers/${type}_media/${data.media_path}" type="video/mp4"></video>` : '';
    }

    // Special handling for orgchart type
    if (type === 'orgchart') {
        const containerDiv = document.createElement('div');
        containerDiv.dataset.orgchartId = id;
        containerDiv.classList.add('carousel-item');
        containerDiv.dataset.displayTime = data.display_time;
        
        // Store the chart data for later use
        containerDiv.chartData = data.chartData;

        const contentHTML = `
            <div class="content-container-con" id="orgChartParentContainer-${id}">
                <div id="orgChartContainerParent-${id}" class="orgchart-container">
                    <div id="orgChartContainer-${id}" class="orgchart-container-parent">
                        <div class="orgchart-content"></div>
                    </div>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                        <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                            <p class="display-time" style="text-align: right">
                                <i class="fa-solid fa-clock" aria-hidden="true"></i> 
                                <span class="time-left">${data.display_time}s</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        containerDiv.innerHTML = contentHTML;

        if (existingDiv) {
            existingDiv.innerHTML = contentHTML;
            existingDiv.chartData = data.chartData;
            resetCountdown(existingDiv, data.display_time, currentIndexKey);
            setTimeout(() => {
                createOrgChart(id, data.chartData);
            }, 100);
        } else {
            contentsArray.push(containerDiv);
            carouselContainer.appendChild(containerDiv);

            if (contentsArray.length === 1) {
                containerDiv.classList.add('active');
                // Render the first chart immediately
                setTimeout(() => {
                    createOrgChart(id, data.chartData);
                }, 100);
            }
        }

        updatePageNumber(currentIndexKey);
        return;
    }

    let contentHTML = '';

    const getUserTypeIcon = (userType) => {
        switch(userType) {
            case 'Super Admin':
                return '<span style="font-size: 0.85rem; background-color: #9393933b; padding: 5px; border-radius: 10px; margin-right: 5px;"><i class="fas fa-crown" title="Super Admin" style="font-size: 0.85rem; padding-top: 5px; padding-bottom: 5px; margin-right: 3px;"></i> S-Admin</span>';
            case 'Admin':
                return '<span style="font-size: 0.85rem; background-color: #9393933b; padding: 5px; border-radius: 10px; margin-right: 5px;"><i class="fas fa-user-gear" title="Admin" style="font-size: 0.85rem; padding-top: 5px; padding-bottom: 5px; margin-right: 3px;"></i> Admin</span>';
            case 'Faculty':
                return '<span style="font-size: 0.85rem; background-color: #9393933b; padding: 5px; border-radius: 10px; margin-right: 5px;"><i class="fas fa-chalkboard-user" title="Faculty" style="font-size: 0.85rem; padding-top: 5px; padding-bottom: 5px; margin-right: 3px;"></i> Faculty</span>';
            case 'Staff':
                return '<span style="font-size: 0.85rem; background-color: #9393933b; padding: 5px; border-radius: 10px; margin-right: 5px;"><i class="fas fa-clipboard" title="Staff" style="font-size: 0.85rem; padding-top: 5px; padding-bottom: 5px; margin-right: 3px;"></i> Staff</span>';
            case 'Student':
                return '<span style="font-size: 0.85rem; background-color: #9393933b; padding: 5px; border-radius: 10px; margin-right: 5px;"><i class="fas fa-user-graduate" title="Student" style="font-size: 0.85rem; padding-top: 5px; padding-bottom: 5px; margin-right: 3px;"></i> Student</span>';
            default:
                return '<span style="font-size: 0.85rem; background-color: #9393933b; padding: 5px; border-radius: 10px; margin-right: 5px;"><i class="fas fa-user" title="User" style="font-size: 0.85rem; padding-top: 5px; padding-bottom: 5px; margin-right: 3px;"></i> User</span>';
        }
    };

    if (type === 'announcement') {
        contentHTML = `
            <div class="content-container-con" id="${type}ContentConContainer-${id}">
                <div class="content-main" id="${type}ContentMain-${id}">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <div class="message-wrapper" id="${type}MessageWrapper-${id}">
                        <div class="main-message" id="${type}Message-${id}" style="word-break: break-word;">
                            ${data.announcement_body}
                        </div>
                    </div>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; padding-top: 5px; white-space: nowrap;">
                            <p class="author"><small>${getUserTypeIcon(data.user_type)} ${formattedCreatedDateTime}</small></p>
                        </div>
                        <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                            <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'event') {
        contentHTML = `
            <div class="content-container-con" id="${type}ContentConContainer-${id}">
                <div class="content-main" id="${type}ContentMain-${id}">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <div class="message-wrapper" id="${type}MessageWrapper-${id}">
                        <div class="main-message" id="${type}Message-${id}" style="word-break: break-word;">
                            ${data.event_body}
                        </div>
                    </div>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; padding-top: 5px; white-space: nowrap;">
                            <p class="author"><small>${getUserTypeIcon(data.user_type)} ${formattedCreatedDateTime}</small></p>
                        </div>
                        <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                            <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'news') {
        contentHTML = `
            <div class="content-container-con" id="${type}ContentConContainer-${id}">
                <div class="content-main" id="${type}ContentMain-${id}">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                    <div class="message-wrapper" id="${type}MessageWrapper-${id}">
                        <div class="main-message" id="${type}Message-${id}" style="word-break: break-word;">
                            ${data.news_body}
                        </div>
                    </div>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; padding-top: 5px; white-space: nowrap;">
                            <p class="author"><small>${getUserTypeIcon(data.user_type)} ${formattedCreatedDateTime}</small></p>
                        </div>
                        <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                            <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'promaterial') {
        contentHTML = `
            <div class="content-container-con" id="${type}ContentConContainer-${id}">
                <div class="content-main" id="${type}ContentMain-${id}">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: auto; margin-top: auto; max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 5px;">${mediaContent}</div>` : ''}
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; padding-top: 5px; white-space: nowrap;">
                            <p class="author"><small>${getUserTypeIcon(data.user_type)} ${formattedCreatedDateTime}</small></p>
                        </div>
                        <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                            <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'peo') {
        contentHTML = `
            <div class="content-container-con" id="${type}ContentConContainer-${id}">
                <div class="content-main" id="${type}ContentMain-${id}">
                    <div class="message-wrapper" id="${type}MessageWrapper-${id}">
                        <div class="main-message" id="${type}Message-${id}" style="word-break: break-word;">
                            ${data.peo_title}
                            ${data.peo_description}
                            ${data.peo_subdescription}
                        </div>
                    </div>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; padding-top: 5px; white-space: nowrap;">
                            <p class="author"><small>${getUserTypeIcon(data.user_type)} ${formattedCreatedDateTime}</small></p>
                        </div>
                        <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                            <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'so') {
        contentHTML = `
            <div class="content-container-con" id="${type}ContentConContainer-${id}">
                <div class="content-main" id="${type}ContentMain-${id}">
                    <div class="message-wrapper" id="${type}MessageWrapper-${id}">
                        <div class="main-message" id="${type}Message-${id}" style="word-break: break-word;">
                            ${data.so_title}
                            ${data.so_description}
                            ${data.so_subdescription}
                        </div>
                    </div>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; padding-top: 5px; white-space: nowrap;">
                            <p class="author"><small>${getUserTypeIcon(data.user_type)} ${formattedCreatedDateTime}</small></p>
                        </div>
                        <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                            <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'facility') {
        const facilityBody = data.facility_body;
        const images = data.facility_images_array.split(',');
        if (facilityBody !== '<p><br></p>') {
            contentHTML = `
                <div class="content-container-con" id="facilityContentConContainer-${id}">
                    <div class="content-main" id="facilityContentMain-${id}">
                        <div class="media-container" style="margin-bottom: 5px; height: 70%; max-height: 70%;">
                            <img src="servers/facility_media/${images[0]}" class="facility-image" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <div class="message-wrapper" id="${type}MessageWrapper-${id}">
                            <div class="main-message" id="${type}Message-${id}" style="word-break: break-word;">
                                ${facilityBody}
                            </div>
                        </div>
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; padding-top: 5px; white-space: nowrap;">
                                <p class="author"><small>${getUserTypeIcon(data.user_type)} ${formattedCreatedDateTime}</small></p>
                            </div>
                            <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                                <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            contentHTML = `
                <div class="content-container-con" id="facilityContentConContainer-${id}">
                    <div class="content-main" id="facilityContentMain-${id}">
                        <div class="media-container" style="margin-bottom: 5px; height: 100%; max-height: 100%;">
                            <img src="servers/facility_media/${images[0]}" class="facility-image" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; padding-top: 5px; white-space: nowrap;">
                                <p class="author"><small>${getUserTypeIcon(data.user_type)} ${formattedCreatedDateTime}</small></p>
                            </div>
                            <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                                <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    } else {
        // Generic handler for new features
        // 25 filtered keys
        const contentKeys = Object.entries(data).filter(([key]) => 
            !['id', 'created_datetime', 'display_time', 'status', 'expiration_datetime', 
            'isArchived', 'isTrashed', 'tv_id', 'author_id', 'type', 
            'department', 'user_type', 'category', 'evaluated_by', `${type}_author_id`, `${type}_id`,
            'evaluated_message', 'author_name', 
            'author_user_type', 'author_department', 'datetime_archived', 
            'datetime_trashed', 'media_path'].includes(key)
        );

        // Check if there's only media content
        const hasOnlyMedia = mediaContent && contentKeys.length === 1;

        if (hasOnlyMedia == true) {
            contentHTML = `
                <div class="content-container-con" id="${type}ContentConContainer-${id}">
                    <div class="content-main" id="${type}ContentMain-${id}">
                        ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px; height: 100%; max-height: 100%; object-fit: contain; border-radius: 5px;">${mediaContent}</div>` : ''}
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; padding-top: 5px; white-space: nowrap;">
                                <p class="author"><small>${getUserTypeIcon(data.user_type)} ${formattedCreatedDateTime}</small></p>
                            </div>
                            <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                                <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            contentHTML = `
                <div class="content-container-con" id="${type}ContentConContainer-${id}">
                    <div class="content-main" id="${type}ContentMain-${id}">
                        ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                        <div class="message-wrapper" id="${type}MessageWrapper-${id}">
                            <div class="main-message" id="${type}Message-${id}" style="word-break: break-word;">
                                ${Object.entries(data)
                                    .filter(([key]) => !['id', 'created_datetime', 'display_time', 'status', 'expiration_datetime', 
                                        'isArchived', 'isTrashed', 'tv_id', 'author_id', 'type', 
                                        'department', 'user_type', 'category', 'evaluated_by', `${type}_author_id`, `${type}_id`,
                                        'evaluated_message', 'author_name',  
                                        'author_user_type', 'author_department', 'datetime_archived', 
                                        'datetime_trashed', 'media_path', 'media_upload_only'].includes(key))
                                    .map(([key, value]) => `${value}`)
                                    .join('')}
                            </div>
                        </div>
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; padding-top: 5px; white-space: nowrap;">
                                <p class="author"><small>${getUserTypeIcon(data.user_type)} ${formattedCreatedDateTime}</small></p>
                            </div>
                            <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                                <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    if (existingDiv) {
        if (type === 'facility') {
            existingDiv.innerHTML = contentHTML;
            resetCountdown(existingDiv, data.display_time, currentIndexKey);
        } else {
            const contentDiv = existingDiv.querySelector('.content-container-con');
            if (contentDiv) {
                contentDiv.innerHTML = contentHTML;
            }
            resetCountdown(existingDiv, data.display_time, currentIndexKey);
            
            // Only update page number if no urgent post is in progress
            if (!urgentPostsInProgress[type]) {
                updatePageNumber(currentIndexKey);
            }
        }
        // Apply text fitting after updating content
        setTimeout(() => initializeTextFitting(type, id), 0);
    } else {
        const containerDiv = document.createElement('div');
        containerDiv.dataset[`${type}Id`] = data[`${type}_id`];
        containerDiv.classList.add('carousel-item');
        containerDiv.dataset.displayTime = data.display_time;

        if (data.expiration_datetime) {
            containerDiv.dataset.expirationDatetime = data.expiration_datetime;
        }

        if (type === 'facility') {
            const images = data.facility_images_array.split(',');
            containerDiv.dataset.images = images.join(',');
            containerDiv.dataset.currentImageIndex = 0;
        }

        const contentDiv = document.createElement('div');

        containerDiv.style = `
            width: auto;
            height: 100%;
            border-radius: 5px;
        `;

        contentDiv.style = `
            border: none;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            text-align: left;
            height: 100%;
            bottom: 0;
        `;

        contentDiv.innerHTML = contentHTML;
        containerDiv.appendChild(contentDiv);
        contentsArray.push(containerDiv);
        carouselContainer.appendChild(containerDiv);

        if (contentsArray.length === 1) {
            containerDiv.classList.add('active');
            
            // Only update page number if no urgent post is in progress
            if (!urgentPostsInProgress[type]) {
                updatePageNumber(currentIndexKey);
            }
        } else {
            // Only update page number if no urgent post is in progress
            if (!urgentPostsInProgress[type]) {
                updatePageNumber(currentIndexKey);
            }
        }

        // Apply text fitting after updating content
        setTimeout(() => initializeTextFitting(type, id), 0);
    }
    
    // Return the index of the newly added content
    return contentsArray.length - 1;
};

function initializeTextFitting(type, id) {
    const containerDiv = document.getElementById(`${type}ContentMain-${id}`);
    
    if (!containerDiv) {
        console.warn(`Container element not found for ${type}ContentMain-${id}`);
        return;
    }

    // Find all message elements within the container
    const messageElements = containerDiv.querySelectorAll('.main-message');
    if (!messageElements.length) {
        console.warn(`No message elements found for ${type} with id ${id}`);
        return;
    }

    // Apply text fitting to each message element
    messageElements.forEach((messageElement, index) => {
        const messageId = `${type}Message${index}-${id}`;
        
        // Get the original content and format lists
        let formattedContent = messageElement.innerHTML
            .replace(
                /<li data-list="ordered">/g, 
                '<li style="list-style-type: decimal; margin-left: 35px;">'
            )
            .replace(
                /<li data-list="bullet">/g, 
                '<li style="list-style-type: disc; margin-left: 35px;">'
            )
            .replace(
                /<span class="ql-ui" contenteditable="false"><\/span>/g, 
                ''
            );
        
        // Clear the message element
        messageElement.innerHTML = '';
        
        // Create a new wrapper div
        const wrapperDiv = document.createElement('div');
        wrapperDiv.className = 'message-content-wrapper';
        wrapperDiv.style.width = '100%';
        wrapperDiv.style.height = '100%';
        
        
        // Create a new paragraph element
        const paragraph = document.createElement('p');
        paragraph.id = messageId;
        
        // Set the formatted HTML content
        paragraph.innerHTML = formattedContent;
        
        // Append elements
        wrapperDiv.appendChild(paragraph);
        messageElement.appendChild(wrapperDiv);
        
        // Apply text fitting
        fitText(messageId);
    });

    // Set up resize observer for continuous monitoring
    const resizeObserver = new ResizeObserver(() => {
        requestAnimationFrame(() => {
            messageElements.forEach((messageElement, index) => {
                const messageId = `${type}Message${index}-${id}`;
                fitText(messageId);
            });
        });
    });

    resizeObserver.observe(containerDiv);

    // Observe parent container as well
    const parentContainer = containerDiv.closest('.content-container');
    if (parentContainer) {
        resizeObserver.observe(parentContainer);
    }
}

function fitText(messageId) {
    const messageElement = $(`#${messageId}`);
    if (!messageElement.length) {
        console.warn(`Message element not found for ID: ${messageId}`);
        return;
    }

    const messageWrapper = messageElement.parent();
    if (!messageWrapper.length) {
        console.warn(`Message wrapper not found for ID: ${messageId}`);
        return;
    }

    try {
        messageWrapper.textfill({
            maxFontPixels: 40, // 0 means no maximum size
            minFontPixels: 10,
            widthOnly: false,
            explicitWidth: messageWrapper.width(),
            explicitHeight: messageWrapper.height(),
            changeLineHeight: true,
            innerTag: 'p'
        });
    } catch (error) {
        console.error(`Error fitting text for ${messageId}:`, error);
    }
}

// Function to show next image for facility
const showNextFacilityImage = (facilityDiv) => {
    const images = facilityDiv.dataset.images.split(',');
    let currentImageIndex = parseInt(facilityDiv.dataset.currentImageIndex, 10);
    currentImageIndex = (currentImageIndex + 1) % images.length;
    facilityDiv.dataset.currentImageIndex = currentImageIndex;

    const imageElement = facilityDiv.querySelector('.facility-image');
    imageElement.src = `servers/facility_media/${images[currentImageIndex]}`;
};

// Override showNextContent for facility
const showNextFacilityContent = () => {
    const currentIndexKey = 'facility';
    const contentsArray = contents.facilitys;

    if (contentsArray.length > 0) {
        const currentFacility = contentsArray[currentIndex[currentIndexKey]];
        showNextFacilityImage(currentFacility);

        if (parseInt(currentFacility.dataset.currentImageIndex, 10) === 0) {
            // Move to next facility if all images have been shown
            currentFacility.classList.remove('active');
            currentIndex[currentIndexKey] = (currentIndex[currentIndexKey] + 1) % contentsArray.length;
            contentsArray[currentIndex[currentIndexKey]].classList.add('active');
            updatePageNumber(currentIndexKey);
            resetDisplayTime('facility');
        } else {
            // Reset the countdown for the next image
            resetDisplayTime('facility');
        }
    }
};

// Function to create org chart
const createOrgChart = (orgchartId, chartData) => {
    const containerId = `orgChartContainer-${orgchartId}`;
    const containerParentId = `orgChartContainerParent-${orgchartId}`;

    const container = document.getElementById(containerId);
    const containerParent = document.getElementById(containerParentId);

    if (!container) {
        console.error(`Container not found for orgchart ${orgchartId}`);
        return;
    }

    // Clear any existing chart
    const contentDiv = document.querySelector('.orgchart-content');
    if (!contentDiv) {
        return;
    }

    contentDiv.innerHTML = '';

    // Create a unique chart ID
    const uniqueChartId = `treant-${orgchartId}`;
    contentDiv.id = uniqueChartId;

    // Update chart configuration with dynamic spacing
    const chartConfig = {
        chart: {
            container: `#${containerId}`,
            nodeAlign: "BOTTOM",
            levelSeparation: 50, // Dynamic level separation
            siblingSeparation: 50, // Dynamic sibling separation
            subTeeSeparation: 50, // Dynamic subtree separation
            connectors: {
                type: 'step',
                style: {
                    'stroke-width': '4px'
                }
            },
            node: {
                HTMLclass: 'nodeExample1'
            }
        },
        nodeStructure: createNodeStructure(chartData.members)
    };

    try {
        // Create the Treant instance
        new Treant(chartConfig, () => {
            const treant = document.querySelector(`#${containerId}`).closest('.Treant');
            const svg = treant ? treant.querySelector('svg') : null;

            if (!treant || !svg) {
                return;
            }

            // Apply initial scaling
            applyOrgChartScaling(treant, svg, containerParent);
            
            // Set up resize observer for this specific chart
            setupOrgChartResizeObserver(containerParent, treant, svg);
        });
    } catch (error) {
        console.error('Error creating org chart:', error);
        contentDiv.innerHTML = '<p style="text-align: center; color: #264B2B; font-size: 1.2em; padding: 20px;">Error loading organizational chart.</p>';
    }
};

// Function to apply scaling to the org chart
const applyOrgChartScaling = (treant, svg, containerParent) => {
    if (!treant || !svg || !containerParent) return;
    
    // First, make the Treant match the SVG dimensions
    const svgWidth = svg.width.baseVal.value;
    const svgHeight = svg.height.baseVal.value;
    
    // Set both width and height of Treant to match SVG
    treant.style.width = `${svgWidth}px`;
    treant.style.height = `${svgHeight}px`;

    // Reset transform to get true dimensions
    treant.style.transform = 'none';
    
    // Calculate scale ratios
    const containerWidth = containerParent.offsetWidth;
    const containerHeight = containerParent.offsetHeight;
    const scale = Math.min(
        containerWidth / svgWidth,
        containerHeight / svgHeight,
        1
    ) * 0.9;

    // Apply the transform
    treant.style.zoom = `${scale}`;
    treant.style.transformOrigin = 'center center';
};

// resize observer to handle container resizing
const setupOrgChartResizeObserver = (container, treant, svg) => {
    if (!container || !treant || !svg) {
        console.error('Cannot setup resize observer: Missing required elements');
        return;
    }

    // Create a resize observer that will apply scaling when the container resizes
    const resizeObserver = new ResizeObserver(debounce(() => {
        applyOrgChartScaling(treant, svg, container);
    }, 100));

    // Start observing the container
    resizeObserver.observe(container);
    
    // Observe the parent container as well (for layout changes)
    const parentContainer = container.closest('.content-container');
    if (parentContainer) {
        resizeObserver.observe(parentContainer);
    }
    
    return resizeObserver;
};

// Debounce function to avoid excessive recalculations
const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

// Helper function to create node structure with dynamic sizing
const createNodeStructure = (members) => {
    const nodeMap = new Map();
    
    // First pass: create all nodes with dynamic sizing
    members.forEach(member => {
        nodeMap.set(member.parent_node_id, {
            text: {
                name: member.name,
                title: member.title,
            },
            image: member.picture,
            innerHTML: `
                <div class="node-content" style="width: auto">
                    ${member.picture ? 
                        `<img src="servers/orgchart_media/${member.picture}" class="node-image" alt="${member.name}" style="width: 40px; height: 40px;">` : 
                        `<img src="images/profile_picture.png" class="node-image" alt="Default Profile" style="width: 40px; height: 40px;">`
                    }
                    <div class="node-details" style="width: auto; max-width: 500px;">
                        <div class="node-name" style="font-size: 25px;">${member.name}</div>
                        <div class="node-title" style="font-size: 18px;">${member.title}</div>
                    </div>
                </div>
            `,
            HTMLclass: "custom-node",
            children: []
        });
    });

    // Second pass: build hierarchy
    const rootNodes = [];
    members.forEach(member => {
        const node = nodeMap.get(member.parent_node_id);
        if (member.parent_id && member.parent_id !== 'NULL') {
            const parentNode = nodeMap.get(member.parent_id);
            if (parentNode) {
                parentNode.children.push(node);
            } else {
                rootNodes.push(node);
            }
        } else {
            rootNodes.push(node);
        }
    });

    return rootNodes.length === 1 ? rootNodes[0] : {
        text: { name: "Department" },
        HTMLclass: "hidden-node",
        children: rootNodes
    };
};

const renderVisibleOrgChart = () => {
    const activeItem = document.querySelector('.carousel-item.active');
    if (activeItem) {
        const orgChartContainer = activeItem.querySelector('.orgchart-container-parent');
        if (orgChartContainer && orgChartContainer.chartConfig) {
            new Treant(orgChartContainer.chartConfig);
            // Remove the config after rendering to avoid re-rendering
            delete orgChartContainer.chartConfig;
        }
    }
};

// Function to initialize org charts
// const initializeOrgCharts = () => {
//     fetchOrgChartData().then(groupedData => {
//         Object.keys(groupedData).forEach(orgchartId => {
//             const containerId = `orgChartContainer-${orgchartId}`;
//             const containerDiv = document.createElement('div');
//             containerDiv.id = containerId;
//             containerDiv.style.width = '100%';
//             containerDiv.style.height = '100%';
//             document.getElementById('orgchartCarouselContainer').appendChild(containerDiv);

//             createOrgChart(groupedData[orgchartId], containerId);
//         });
//     });
// };

// Function to reset countdown for each content
const resetCountdown = (div, newDisplayTime, type) => {
    const timeLeftElement = div.querySelector('.time-left');
    if (timeLeftElement) {
        timeLeftElement.textContent = `${newDisplayTime}s`;
        setCarouselInterval(newDisplayTime, type);
    }
};

// Track urgent posts status
let urgentPostsInProgress = {};
// Add queue for urgent posts
let urgentPostsQueue = {};
// Store the original content index to return to after urgent posts
let originalContentIndices = {};

// Function to handle urgent posts
const handleUrgentPost = (type, postData) => {
    // Check if we're already processing an urgent post for this type
    const alreadyInProgress = urgentPostsInProgress[type];
    
    // Ensure content ID is a number
    const contentId = parseInt(postData[`${type}_id`]);
    console.log(`Handling urgent post for ${type} with ID ${contentId}`);
    
    // Initialize the queue for this type if it doesn't exist
    if (!urgentPostsQueue[type]) {
        urgentPostsQueue[type] = [];
    }
    
    // If there's already an urgent post in progress, add this one to the queue and return
    if (alreadyInProgress) {
        console.log(`Urgent post for ${type} with ID ${contentId} queued - waiting for current urgent post to finish`);
        
        // Check if this post is already in the queue to avoid duplicates
        const alreadyQueued = urgentPostsQueue[type].some(item => 
            parseInt(item[`${type}_id`]) === contentId);
        
        if (!alreadyQueued) {
            urgentPostsQueue[type].push(postData);
        }
        return;
    }
    
    // Mark that we're now processing an urgent post
    urgentPostsInProgress[type] = true;
    
    // Save current state to resume later - but only if this is the first urgent post
    // (not if we're coming from another urgent post in the queue)
    if (originalContentIndices[type] === undefined) {
        originalContentIndices[type] = currentIndex[type];
        console.log(`Saving original content index: ${originalContentIndices[type]} for type ${type}`);
    }
    
    const currentItem = contents[`${type}s`][currentIndex[type]];
    
    // Clear existing interval
    if (displayTimeIntervals[type]) {
        clearInterval(displayTimeIntervals[type]);
        displayTimeIntervals[type] = null;
    }
    
    // Remove active class from current item and handle video cleanup
    if (currentItem) {
        const currentVideo = currentItem.querySelector('video');
        if (currentVideo) {
            currentVideo.pause();
            currentVideo.removeAttribute('src');
            currentVideo.load();
            currentVideo.replaceWith(currentVideo.cloneNode(true));
        }
        currentItem.classList.remove('active');
    }
    
    // Check if this content already exists in the array or add it at the appropriate position
    let urgentPostIndex = -1;
    for (let i = 0; i < contents[`${type}s`].length; i++) {
        if (parseInt(contents[`${type}s`][i].dataset[`${type}Id`]) === contentId) {
            urgentPostIndex = i;
            break;
        }
    }
    
    if (urgentPostIndex === -1) {
        // Add the content to the array at the appropriate position based on priority
        urgentPostIndex = updateUI(postData, type);
        console.log(`Urgent post added at position ${urgentPostIndex} based on priority`);
    }
    
    // Set current index to the urgent post
    currentIndex[type] = urgentPostIndex;
    
    // Make the urgent post active
    contents[`${type}s`].forEach(item => item.classList.remove('active'));
    contents[`${type}s`][urgentPostIndex].classList.add('active');
    
    // Store the original page number text so we can restore it later
    const { pageNumberContainer } = getContainerElements(type);
    const originalPageNumber = pageNumberContainer.textContent;
    pageNumberContainer._originalPageNumber = originalPageNumber;
    
    // Change the page number to display "URGENT" in red
    pageNumberContainer.innerHTML = '<span style="font-weight: bolder;">URGENT <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span>';
    
    // Handle display time manually for urgent posts
    const displayTime = parseInt(postData.display_time, 10);
    const urgentItem = contents[`${type}s`][urgentPostIndex];
    const timeLeftElement = urgentItem.querySelector('.time-left');
    
    // Make sure the display time is correctly set
    urgentItem.dataset.displayTime = displayTime;
    
    if (timeLeftElement) {
        let remainingTime = displayTime;
        timeLeftElement.textContent = `${remainingTime}s`;
        
        // Set up a new interval just for this urgent post
        displayTimeIntervals[type] = setInterval(() => {
            remainingTime--;

            // Only proceed if remaining time is 0 or less, not before
            if (remainingTime < 0) {
                clearInterval(displayTimeIntervals[type]);
                
                // Handle the transition after urgent post completes
                handleUrgentPostComplete();
            } else {
                // Update the display with current remaining time
                timeLeftElement.textContent = `${remainingTime}s`;
            }
        }, 1000);
    } else {
        // Fallback if time element isn't found
        setTimeout(handleUrgentPostComplete, displayTime * 1000);
    }
    
    // Function to handle what happens when urgent post completes
    function handleUrgentPostComplete() {
        // Clear urgent flag by sending message to the server
        if (isConnectedToWebSocket && Ws) {
            Ws.send(JSON.stringify({
                action: 'clear_urgent_post',
                type: type,
                content_id: contentId
            }));
        }
        
        console.log(`Urgent post for ${type} ID ${contentId} completed. Queue length: ${urgentPostsQueue[type] ? urgentPostsQueue[type].length : 0}`);
        
        // Check if there are more urgent posts in the queue
        if (urgentPostsQueue[type] && urgentPostsQueue[type].length > 0) {
            // Get the next urgent post from the queue
            const nextUrgentPost = urgentPostsQueue[type].shift();
            console.log(`Processing next urgent post in queue for ${type} with ID ${nextUrgentPost[`${type}_id`]}`);
            
            // We need to set the flag to false before processing the next post,
            // otherwise the next urgent post will be queued instead of displayed
            urgentPostsInProgress[type] = false;
            
            // Use setTimeout to create a small delay between posts
            // This ensures the current post cleanup is complete before showing the next one
            setTimeout(() => {
                // Process the next urgent post immediately
                handleUrgentPost(type, nextUrgentPost);
            }, 100);
        } else {
            // No more urgent posts in queue, resume normal display
            urgentPostsInProgress[type] = false;
            
            // Get the original content index from our global store
            const returnToIndex = originalContentIndices[type];
            console.log(`Attempting to return to original content at index ${returnToIndex} for type ${type}`);
            
            // Reset the original content index for next time
            originalContentIndices[type] = undefined;

            // Restore the original page number display
            const { pageNumberContainer } = getContainerElements(type);
            if (pageNumberContainer._originalPageNumber) {
                pageNumberContainer.textContent = pageNumberContainer._originalPageNumber;
                delete pageNumberContainer._originalPageNumber;
            }
            
            // If the original content is still valid, return to it
            if (typeof returnToIndex === 'number' && returnToIndex < contents[`${type}s`].length) {
                currentIndex[type] = returnToIndex;
                console.log(`Returning to original content at index ${returnToIndex}`);
            } else {
                // Otherwise just use the first item
                currentIndex[type] = 0;
                console.log(`Could not return to original index ${returnToIndex}, using index 0 instead`);
            }
            
            // Show the content based on the restored index
            const contentToShow = contents[`${type}s`][currentIndex[type]];
            if (contentToShow) {
                // Clear active class from all items
                contents[`${type}s`].forEach(item => item.classList.remove('active'));
                
                // Activate the post we want to resume with
                contentToShow.classList.add('active');
                updatePageNumber(type);
                
                // Use a small delay before resetting display time to ensure UI is updated
                setTimeout(() => {
                    resetDisplayTime(type);
                }, 50);
            }
        }
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
    const contentsArray = contents[type + 's'];

    if (contentsArray.length > 0) {
        // Remove active class from the current item and handle video cleanup
        if (contentsArray[currentIndex[currentIndexKey]]) {
            const currentItem = contentsArray[currentIndex[currentIndexKey]];
            const currentVideo = currentItem.querySelector('video');
            if (currentVideo) {
                currentVideo.pause();
                currentVideo.removeAttribute('src'); // Remove source
                currentVideo.load(); // Reset video element
                // Remove any existing event listeners
                currentVideo.replaceWith(currentVideo.cloneNode(true));
            }
            currentItem.classList.remove('active');
        }

        // Increment the index and wrap around if necessary
        currentIndex[currentIndexKey] = (currentIndex[currentIndexKey] + 1) % contentsArray.length;

        // active class to the new current item
        if (contentsArray[currentIndex[currentIndexKey]]) {
            const newItem = contentsArray[currentIndex[currentIndexKey]];
            newItem.classList.add('active');
            updatePageNumber(currentIndexKey);

            // Handle video if present
            const newVideo = newItem.querySelector('video');
            if (newVideo) {
                // Reset video state
                newVideo.currentTime = 0;
                
                // Clear any existing interval first
                if (displayTimeIntervals[currentIndexKey]) {
                    clearInterval(displayTimeIntervals[currentIndexKey]);
                    displayTimeIntervals[currentIndexKey] = null;
                }

                // Set up new video
                const playVideo = () => {
                    const playPromise = newVideo.play();
                    if (playPromise !== undefined) {
                        playPromise
                            .then(() => {
                                // Video started playing successfully
                                const videoDuration = Math.ceil(newVideo.duration);
                                newItem.dataset.displayTime = videoDuration;
                                resetDisplayTime(type);
                            })
                            .catch(error => {
                                console.error("Error playing video:", error);
                                // If video fails to play, use default display time
                                resetDisplayTime(type);
                            });
                    }
                };

                // Set up ended event listener
                const handleVideoEnd = () => {
                    if (displayTimeIntervals[currentIndexKey]) {
                        clearInterval(displayTimeIntervals[currentIndexKey]);
                        displayTimeIntervals[currentIndexKey] = null;
                    }
                    // Remove the event listener to prevent memory leaks
                    newVideo.removeEventListener('ended', handleVideoEnd);
                    showNextContent(type);
                };

                // ended event listener
                newVideo.addEventListener('ended', handleVideoEnd, { once: true });

                // Start playing the video
                playVideo();
            } else {
                // No video, use normal display time
                resetDisplayTime(type);
            }

            // For orgchart, ensure proper rendering after transition
            if (type === 'orgchart') {
                // Clear the previous chart
                const currentChart = contentsArray[currentIndex[currentIndexKey]];
                const chartContainer = currentChart.querySelector('.orgchart-content');
                if (chartContainer) {
                    chartContainer.innerHTML = '';
                }
                
                // Re-render the chart with a delay to ensure DOM is ready
                setTimeout(() => {
                    if (currentChart.chartData) {
                        createOrgChart(currentChart.dataset.orgchartId, currentChart.chartData);
                }
            }, 1000);
            }
        }
        } else {
        clearInterval(displayTimeIntervals[currentIndexKey]);
    }
};

// Function to reset display time
const resetDisplayTime = (type) => {
    // // Special handling for orgchart
    // if (type === 'orgchart') {
    //     const currentContent = contents[type + 's'][currentIndex[type]];
    //     if (!currentContent) {
    //         console.warn(`No orgchart content found at index ${currentIndex[type]}`);
    //         return;
    //     }

    //     // Find the display time element within the orgchart structure
    //     const displayTimeElement = currentContent.querySelector('.content-container-con .time-left');
    //     if (!displayTimeElement) {
    //         console.warn('Display time element not found in orgchart');
    //         return;
    //     }

    //     const originalDisplayTime = currentContent.dataset.displayTime;
    //     if (!originalDisplayTime) {
    //         console.warn('No display time set for orgchart');
    //         return;
    //     }

    //     let remainingTime = parseInt(originalDisplayTime, 10);
    //     displayTimeElement.innerHTML = `<i class="fa-solid fa-clock" aria-hidden="true"></i> ${remainingTime}s`;

    //     // Clear existing interval
    //     if (displayTimeIntervals[type]) {
    //         clearInterval(displayTimeIntervals[type]);
    //     }

    //     // Set new interval
    //     displayTimeIntervals[type] = setInterval(() => {
    //         remainingTime -= 1;
    //         if (displayTimeElement) {
    //             displayTimeElement.innerHTML = `<i class="fa-solid fa-clock" aria-hidden="true"></i> ${remainingTime}s`;
    //         }
    //         if (remainingTime < 0) {
    //             clearInterval(displayTimeIntervals[type]);
    //             showNextContent(type);
    //         }
    //     }, 1000);

    //     return; // Exit early for orgchart
    // }
    
    const currentContent = contents[`${type}s`][currentIndex[type]];
    // const currentContent = type === 'announcement' ? contents.announcements[currentIndex.announcement] : contents.events[currentIndex.event];
    const displayTimeElement = currentContent.querySelector('.display-time');
    const originalDisplayTime = currentContent.dataset.displayTime;

    if (displayTimeElement && originalDisplayTime) {
        let remainingTime = parseInt(originalDisplayTime, 10);
        displayTimeElement.innerHTML = `<i class="fa-solid fa-clock" aria-hidden="true"></i> ${remainingTime}s`;

            if (displayTimeIntervals[type]) {
                clearInterval(displayTimeIntervals[type]);
            }
            
            displayTimeIntervals[type] = setInterval(() => {
                remainingTime -= 1;
            displayTimeElement.innerHTML = `<i class="fa-solid fa-clock" aria-hidden="true"></i> ${remainingTime}s`;
            if (remainingTime < 0) {
                clearInterval(displayTimeIntervals[type]);
                if (type === 'facility') {
                    showNextFacilityContent();
                } else {
                    showNextContent(type);
                }
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

const fetchSmartTVDetails = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId');

    if (tvId) {
        fetch(`database/fetch_smart_tvs.php?tvId=${tvId}`)
            .then(response => response.json())
            .then(data => {
                const tvName = data.tv_name;
                const tvBrand = data.tv_brand;
                const tvDepartment = data.tv_department;
                updateTvDetails(tvName, tvBrand, tvDepartment, tvId);
            })
            .catch(error => console.error('Error fetching TV name:', error));
    } else {
        console.error('TV ID not found in URL');
    }
};

const updateTvDetails = (tvName, tvBrand, tvDepartment, tvId) => {
    const tvNameElement = document.querySelector('.tv-name');
    if (tvNameElement) {
        tvNameElement.textContent = tvName;
    }
    const tvBrandElement = document.querySelector('.tv-brand');
    if (tvBrandElement) {
        tvBrandElement.textContent = tvBrand;
    }
    const tvDepartmentElement = document.getElementById(`tvDepartment_${tvId}`);
    if (tvDepartmentElement) {
        tvDepartmentElement.textContent = tvDepartment;
    }
};

const startCarousel = (type) => {
    // Check specifically for orgchart type
    if (type === 'orgchart') {
        if (contents[type + 's'].length > 0) {
            if (contents[type + 's'][currentIndex[type]]) {
                contents[type + 's'][currentIndex[type]].classList.add('active');
            }
            resetDisplayTime(type);
            renderVisibleOrgChart(); // Ensure the orgchart is rendered
        } else {
            console.error("No orgchart content available");
            displayNoMessage(type);
        }
        return;
    }

    if (contents[type + 's'].length > 0) {
        // Remove active class from all contents
        contents[type + 's'].forEach(content => content.classList.remove('active'));

        // Set the current content as active
        if (contents[type + 's'][currentIndex[type]]) { // Check if the index is valid
            contents[type + 's'][currentIndex[type]].classList.add('active');
            resetDisplayTime(type);
        } else {
            console.error("Invalid current content index.");
        }
    } else {
        console.error("Cannot start carousel: No valid content available.", type);
    }
}

// Function to display a message when no content is available
const displayNoMessage = (type) => {
    const { carouselContainer } = getContainerElements(type);
    carouselContainer.innerHTML = `<p style="text-align: center; font-size: 1.8em; margin-top: auto; margin-bottom: auto; font-weight: bold;">No content available</p>`; // Display no content message
};

// Function to fetch and update contents
const fetchAndUpdateContents = (type) => {
    // Set loading status to true when starting to fetch
    contentLoadingStatus[type] = true;
    
    const previousIndex = currentIndex[type]; // Save the current index
    contents[type + 's'] = [];
    const { carouselContainer, pageNumberContainer } = getContainerElements(type); // Get the containers dynamically
    carouselContainer.innerHTML = ''; // Clear the container
    pageNumberContainer.innerHTML = ''; // Clear the container

    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId');

    // List of predefined content types
    const predefinedTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so', 'orgchart'];

    const finishLoading = () => {
        contentLoadingStatus[type] = false;        
        // Check if all content types have finished loading
        const allContentLoaded = Object.values(contentLoadingStatus).every(status => status === false);
        if (allContentLoaded) {
            // small delay before checking containers to ensure DOM is fully updated
            setTimeout(() => {
                checkContainersForContent();
            }, 1000);
        }
    };

    if (tvId && predefinedTypes.includes(type)) {
        fetch(`database/fetch_smart_tvs.php?tvId=${tvId}`)
            .then(response => response.json())
            .then(data => {
                const tvName = data.tv_name;
                const tvDepartment = data.tv_department;
                if (type === 'orgchart') {
                    // Orgchart handling stays the same
                    fetch(`database/fetch_orgchart.php?tvId=${tvId}`)
                        .then(response => response.json())
                        .then(data => {
                            let hasMatchingOrgchart = false;
                            Object.entries(data).forEach(([orgchartId, orgchartData]) => {
                                if (orgchartData.members && orgchartData.members.length > 0 && 
                                    orgchartData.members[0].department === tvDepartment) {
                                    hasMatchingOrgchart = true;
                                    updateUI({
                                        orgchart_id: orgchartId,
                                        chartData: orgchartData,
                                        display_time: orgchartData.display_time
                                    }, 'orgchart');
                                }
                            });

                            if (!hasMatchingOrgchart) {
                                displayNoMessage('orgchart');
                            } else {
                                currentIndex['orgchart'] = Math.min(currentIndex['orgchart'], contents['orgcharts'].length - 1);
                                startCarousel('orgchart');
                            }
                            finishLoading();
                        })
                        .catch(error => {
                            console.error('Error fetching orgcharts:', error);
                            displayNoMessage('orgchart');
                            finishLoading();
                        });
                } else if (type !== 'orgchart') {
                    fetch(`database/fetch_${type}.php`)
                        .then(response => response.json())
                        .then(data => {
                            if (type === 'peo' || type === 'so') {
                                const filteredData = data.filter(item =>
                                    item.tv_id === parseInt(tvId, 10) &&
                                    item.isArchived === 0 &&
                                    item.isTrashed === 0 && 
                                    item.department === tvDepartment
                                );

                                filteredData.sort((a, b) => {
                                    const priorityDiff = getUserTypePriority(b.user_type) - getUserTypePriority(a.user_type);
                                    if (priorityDiff !== 0) return priorityDiff;
                                    return new Date(b.created_datetime) - new Date(a.created_datetime);
                                });
                                
                                filteredData.forEach(item => updateUI(item, type));
                                if (contents[type + 's'].length > 0) {
                                    currentIndex[type] = Math.min(currentIndex[type], contents[type + 's'].length - 1);
                                    startCarousel(type);
                                } else {
                                    displayNoMessage(type);
                                }
                            } else {
                                const filteredData = data.filter(item =>
                                    item.status === 'Approved' &&
                                    item.tv_id === parseInt(tvId, 10) &&
                                    item.isArchived === 0 &&
                                    item.isTrashed === 0 && 
                                    item.department === tvDepartment
                                );

                                filteredData.sort((a, b) => {
                                    const priorityDiff = getUserTypePriority(b.user_type) - getUserTypePriority(a.user_type);
                                    if (priorityDiff !== 0) return priorityDiff;
                                    return new Date(b.created_datetime) - new Date(a.created_datetime);
                                });
                                
                                filteredData.forEach(item => updateUI(item, type));
                                if (contents[type + 's'].length > 0) {
                                    currentIndex[type] = Math.min(currentIndex[type], contents[type + 's'].length - 1);
                                    startCarousel(type);
                                } else {
                                    displayNoMessage(type);
                                }
                            }
                            finishLoading();
                        })
                        .catch(error => {
                            console.error(`Error fetching ${type}s:`, error);
                            finishLoading();
                        });
                }
            })
            .catch(error => {
                console.error('Error fetching TV data:', error);
                finishLoading();
            });
    } else if (tvId && !predefinedTypes.includes(type)) {
        fetch(`database/fetch_smart_tvs.php?tvId=${tvId}`)
            .then(response => response.json())
            .then(data => {
                const tvName = data.tv_name;
                const tvDepartment = data.tv_department;
                fetch(`database/fetch_content.php?type=${type}&tvId=${tvId}`)
                    .then(response => response.json())
                    .then(data => {
                        const { carouselContainer } = getContainerElements(type);
                        if (carouselContainer) {
                            carouselContainer.innerHTML = '';
                        }

                        if (Array.isArray(data) && data.length > 0) {
                            const filteredData = data.filter(item => 
                                item.tv_id === parseInt(tvId, 10) &&
                                item.department === tvDepartment
                            );
                            filteredData.sort((a, b) => {
                                const priorityDiff = getUserTypePriority(b.user_type) - getUserTypePriority(a.user_type);
                                if (priorityDiff !== 0) return priorityDiff;
                                return new Date(b.created_datetime) - new Date(a.created_datetime);
                            });
                            
                            filteredData.forEach(item => updateUI(item, type));
                        } else if (data && typeof data === 'object' && 
                                data.tv_id === parseInt(tvId, 10) &&
                                data.department === tvDepartment) {
                            updateUI(data, type);
                        }

                        if (contents[`${type}s`].length > 0) {
                            currentIndex[type] = Math.min(currentIndex[type], contents[type + 's'].length - 1);
                            resetDisplayTime(type);
                        } else {
                            displayNoMessage(type);
                        }
                        finishLoading();
                    })
                    .catch(error => {
                        console.error(`Error fetching new feature ${type}:`, error);
                        finishLoading();
                    });
            })
            .catch(error => {
                console.error('Error fetching TV data:', error);
                finishLoading();
            });
    } else {
        console.error('TV ID not found in URL');
        finishLoading();
    }
};

// Function to check all visible content for expiration
const checkAllContentForExpiration = () => {
    // Get all content types from the containers
    const containers = document.querySelectorAll('[id$="CarouselContainer"]');
    const contentTypes = Array.from(containers).map(container => 
        container.id.replace('CarouselContainer', '')
    );
    
    contentTypes.forEach(type => {
        const containers = document.querySelectorAll(`[data-${type}-id]`);
        containers.forEach(container => {
            if (container.dataset.expirationDatetime) {
                const expirationDate = new Date(container.dataset.expirationDatetime);
                const now = new Date();
                
                if (now >= expirationDate) {
                    // Prepare and send the trash request
                    const trashData = {
                        action: 'trash_content',
                        type: type,
                        [`${type}_id`]: container.dataset[`${type}Id`]
                    };
                    
                    if (isConnectedToWebSocket && Ws) {
                        Ws.send(JSON.stringify(trashData));
                    }
                }
            }
        });
    });
};

// periodic check for expired content (every minute)
setInterval(checkAllContentForExpiration, 60000);

// Function to check all containers for empty content
const checkContainersForContent = () => {
    // Get the TV ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId');
    
    if (!tvId) {
        console.error('TV ID not found in URL');
        return;
    }

    // Get all content types from the containers
    const containers = document.querySelectorAll('[id$="CarouselContainer"]');
    const contentTypes = Array.from(containers).map(container => 
        container.id.replace('CarouselContainer', '')
    );
    
    // Create an object to store container states
    const containerVisibilityMap = {};
    let hasEmptyContainers = false;
    
    // Create a map of container IDs to content status
    const containerContentMap = {};
    
    contentTypes.forEach(type => {
        // Get the container element
        const containerElement = document.querySelector(`[data-type="${type}"]`);
        if (containerElement) {
            const containerId = containerElement.dataset.containerId;
            const isVisible = containerElement.style.display !== 'none';
            
            // Check if there's any content for this type
            const hasContent = contents[`${type}s`] && contents[`${type}s`].length > 0;
            
            // Store content status for each container
            if (containerId) {
                containerContentMap[containerId] = hasContent;
            }
            
            // Only hide the container if:
            // 1. It's currently visible
            // 2. It has no content
            // 3. Content loading for this type is complete
            // 4. We have a valid container ID
            if (isVisible && !hasContent && !contentLoadingStatus[type] && containerId) {
                // Map container ID to visibility state (0 for hidden)
                containerVisibilityMap[containerId] = 0;
                hasEmptyContainers = true;
            }
        }
    });
    
    // Send container content status to parent window if in iframe
    if (isIframe()) {
        window.parent.postMessage({
            action: 'containerContentStatus',
            containerContentMap: containerContentMap
        }, '*');
    }
    
    // If we found containers that need to be hidden, send the update and auto-tile
    if (Object.keys(containerVisibilityMap).length > 0 && isConnectedToWebSocket && Ws) {
        const message = {
            action: 'show_hide_content',
            tv_id: parseInt(tvId, 10),
            containers: containerVisibilityMap
        };
        
        Ws.send(JSON.stringify(message));

        // After hiding empty containers, apply auto-tiling
        if (hasEmptyContainers) {
            setTimeout(() => {
                autoTileContainers(tvId);
            }, 1000);
        }
    }
};

// Function to auto-tile containers
const autoTileContainers = (tvId) => {
    const mainContainer = document.querySelector('.main-container');
    const containers = document.querySelectorAll('.content-container');
    
    if (!mainContainer || containers.length === 0) return;
    
    const GAP = 3.5; // Gap between containers and from edges
    const mainRect = mainContainer.getBoundingClientRect();
    const containerArray = Array.from(containers).filter(container => 
        container.style.display !== 'none'
    );
    
    const numContainers = containerArray.length;
    if (numContainers === 0) return;

    // Calculate available space considering gaps
    const availableWidth = mainRect.width - (GAP * 2); // Account for left and right edge gaps
    const availableHeight = mainRect.height - (GAP * 2); // Account for top and bottom edge gaps

    // Handle different layout cases based on number of visible containers
    if (numContainers === 1) {
        // Single container - fill entire space with gaps
        const container = containerArray[0];
        container.style.width = `${availableWidth}px`;
        container.style.height = `${availableHeight}px`;
        container.style.transform = `translate(${GAP}px, ${GAP}px)`;
        container.setAttribute('data-x', GAP);
        container.setAttribute('data-y', GAP);
    } 
    else if (numContainers === 2) {
        // Two containers - side by side horizontally
        const containerWidth = (availableWidth - GAP) / 2; // Account for gap between containers
        const containerHeight = availableHeight;
        
        containerArray.forEach((container, index) => {
            container.style.width = `${containerWidth}px`;
            container.style.height = `${containerHeight}px`;
            const x = GAP + (index * (containerWidth + GAP));
            container.style.transform = `translate(${x}px, ${GAP}px)`;
            container.setAttribute('data-x', x);
            container.setAttribute('data-y', GAP);
        });
    }
    else if (numContainers === 3) {
        // Layout: 2 containers on top row, 1 container on bottom row (full width)
        const topContainerWidth = (availableWidth - GAP) / 2; // Width for top row containers
        const topContainerHeight = availableHeight / 2; // Half height for top row
        const bottomContainerWidth = availableWidth; // Full width for bottom container
        const bottomContainerHeight = availableHeight / 2 - GAP; // Half height for bottom container
        
        // Place the first two containers side by side on top
        for (let i = 0; i < 2; i++) {
            const container = containerArray[i];
            container.style.width = `${topContainerWidth}px`;
            container.style.height = `${topContainerHeight}px`;
            const x = GAP + (i * (topContainerWidth + GAP));
            container.style.transform = `translate(${x}px, ${GAP}px)`;
            container.setAttribute('data-x', x);
            container.setAttribute('data-y', GAP);
        }
        
        // Place the third container at the bottom, full width
        const bottomContainer = containerArray[2];
        bottomContainer.style.width = `${bottomContainerWidth}px`;
        bottomContainer.style.height = `${bottomContainerHeight}px`;
        bottomContainer.style.transform = `translate(${GAP}px, ${GAP + topContainerHeight + GAP}px)`;
        bottomContainer.setAttribute('data-x', GAP);
        bottomContainer.setAttribute('data-y', GAP + topContainerHeight + GAP);
    }
    else if (numContainers === 4) {
        // Layout: 2x2 grid
        const containerWidth = (availableWidth - GAP) / 2; // Width accounting for gap
        const containerHeight = (availableHeight - GAP) / 2; // Height accounting for gap
        
        containerArray.forEach((container, index) => {
            const row = Math.floor(index / 2);
            const col = index % 2;
            
            container.style.width = `${containerWidth}px`;
            container.style.height = `${containerHeight}px`;
            
            const x = GAP + (col * (containerWidth + GAP));
            const y = GAP + (row * (containerHeight + GAP));
            
            container.style.transform = `translate(${x}px, ${y}px)`;
            container.setAttribute('data-x', x);
            container.setAttribute('data-y', y);
        });
    }
    else if (numContainers === 5) {
        // Layout: 3 containers on top row, 2 containers on bottom row
        const topContainerWidth = (availableWidth - (GAP * 2)) / 3; // Width for top row (3 containers)
        const bottomContainerWidth = (availableWidth - GAP) / 2; // Width for bottom row (2 containers)
        const containerHeight = (availableHeight - GAP) / 2; // Height for all containers
        
        // Top row - 3 containers
        for (let i = 0; i < 3; i++) {
            const container = containerArray[i];
            container.style.width = `${topContainerWidth}px`;
            container.style.height = `${containerHeight}px`;
            const x = GAP + (i * (topContainerWidth + GAP));
            container.style.transform = `translate(${x}px, ${GAP}px)`;
            container.setAttribute('data-x', x);
            container.setAttribute('data-y', GAP);
        }
        
        // Bottom row - 2 containers
        for (let i = 0; i < 2; i++) {
            const container = containerArray[i + 3];
            container.style.width = `${bottomContainerWidth}px`;
            container.style.height = `${containerHeight}px`;
            const x = GAP + (i * (bottomContainerWidth + GAP));
            const y = GAP + containerHeight + GAP;
            container.style.transform = `translate(${x}px, ${y}px)`;
            container.setAttribute('data-x', x);
            container.setAttribute('data-y', y);
        }
    }
    else if (numContainers === 6) {
        // Layout: 3x2 grid
        const containerWidth = (availableWidth - (GAP * 2)) / 3; // Width for all containers
        const containerHeight = (availableHeight - GAP) / 2; // Height for all containers
        
        containerArray.forEach((container, index) => {
            const row = Math.floor(index / 3);
            const col = index % 3;
            
            container.style.width = `${containerWidth}px`;
            container.style.height = `${containerHeight}px`;
            
            const x = GAP + (col * (containerWidth + GAP));
            const y = GAP + (row * (containerHeight + GAP));
            
            container.style.transform = `translate(${x}px, ${y}px)`;
            container.setAttribute('data-x', x);
            container.setAttribute('data-y', y);
        });
    }
    else if (numContainers === 7) {
        // Layout: 3 containers on top row, 3 containers on middle row, 1 full-width container on bottom row
        const topContainerWidth = (availableWidth - (GAP * 2)) / 3; // Width for top and middle rows
        const rowHeight = (availableHeight - (GAP * 2)) / 3; // Height for each row
        
        // Top and middle rows - 3 containers each
        for (let i = 0; i < 6; i++) {
            const container = containerArray[i];
            const row = Math.floor(i / 3);
            const col = i % 3;
            
            container.style.width = `${topContainerWidth}px`;
            container.style.height = `${rowHeight}px`;
            
            const x = GAP + (col * (topContainerWidth + GAP));
            const y = GAP + (row * (rowHeight + GAP));
            
            container.style.transform = `translate(${x}px, ${y}px)`;
            container.setAttribute('data-x', x);
            container.setAttribute('data-y', y);
        }
        
        // Bottom row - 1 full-width container
        const bottomContainer = containerArray[6];
        bottomContainer.style.width = `${availableWidth}px`;
        bottomContainer.style.height = `${rowHeight}px`;
        const y = GAP + (2 * (rowHeight + GAP));
        bottomContainer.style.transform = `translate(${GAP}px, ${y}px)`;
        bottomContainer.setAttribute('data-x', GAP);
        bottomContainer.setAttribute('data-y', y);
    }
    else if (numContainers === 8) {
        // Layout: 3 containers on top row, 3 containers on middle row, 2 containers on bottom row
        const topContainerWidth = (availableWidth - (GAP * 2)) / 3; // Width for top and middle rows
        const bottomContainerWidth = (availableWidth - GAP) / 2; // Width for bottom row
        const rowHeight = (availableHeight - (GAP * 2)) / 3; // Height for each row
        
        // Top and middle rows - 3 containers each
        for (let i = 0; i < 6; i++) {
            const container = containerArray[i];
            const row = Math.floor(i / 3);
            const col = i % 3;
            
            container.style.width = `${topContainerWidth}px`;
            container.style.height = `${rowHeight}px`;
            
            const x = GAP + (col * (topContainerWidth + GAP));
            const y = GAP + (row * (rowHeight + GAP));
            
            container.style.transform = `translate(${x}px, ${y}px)`;
            container.setAttribute('data-x', x);
            container.setAttribute('data-y', y);
        }
        
        // Bottom row - 2 containers
        for (let i = 0; i < 2; i++) {
            const container = containerArray[i + 6];
            container.style.width = `${bottomContainerWidth}px`;
            container.style.height = `${rowHeight}px`;
            const x = GAP + (i * (bottomContainerWidth + GAP));
            const y = GAP + (2 * (rowHeight + GAP));
            container.style.transform = `translate(${x}px, ${y}px)`;
            container.setAttribute('data-x', x);
            container.setAttribute('data-y', y);
        }
    }
    else if (numContainers === 9) {
        // Layout: 3x3 grid
        const containerWidth = (availableWidth - (GAP * 2)) / 3; // Width for all containers
        const containerHeight = (availableHeight - (GAP * 2)) / 3; // Height for all containers
        
        containerArray.forEach((container, index) => {
            const row = Math.floor(index / 3);
            const col = index % 3;
            
            container.style.width = `${containerWidth}px`;
            container.style.height = `${containerHeight}px`;
            
            const x = GAP + (col * (containerWidth + GAP));
            const y = GAP + (row * (containerHeight + GAP));
            
            container.style.transform = `translate(${x}px, ${y}px)`;
            container.setAttribute('data-x', x);
            container.setAttribute('data-y', y);
        });
    }
    else {
        // For more than 9 containers, use a standard grid layout
        const numColumns = 3; // Maximum 3 columns
        const numRows = Math.ceil(numContainers / numColumns);
        
        const containerWidth = (availableWidth - (GAP * (numColumns - 1))) / numColumns;
        const containerHeight = (availableHeight - (GAP * (numRows - 1))) / numRows;
        
        containerArray.forEach((container, index) => {
            const row = Math.floor(index / numColumns);
            const col = index % numColumns;
            
            container.style.width = `${containerWidth}px`;
            container.style.height = `${containerHeight}px`;
            
            const x = GAP + (col * (containerWidth + GAP));
            const y = GAP + (row * (containerHeight + GAP));
            
            container.style.transform = `translate(${x}px, ${y}px)`;
            container.setAttribute('data-x', x);
            container.setAttribute('data-y', y);
        });
    }

    // Send the updated positions to the server
    const positions = Array.from(containerArray).map(container => ({
        id: container.dataset.containerId,
        x: parseFloat(container.getAttribute('data-x')),
        y: parseFloat(container.getAttribute('data-y')),
        width: parseFloat(container.style.width),
        height: parseFloat(container.style.height)
    }));

                    if (isConnectedToWebSocket && Ws) {
                        Ws.send(JSON.stringify({
            action: 'update_container_positions',
            tv_id: parseInt(tvId, 10),
            positions: positions
                        }));
                    }
};

// a function to specifically send content status without affecting visibility
const updateContainerContentStatus = () => {
    // Get the TV ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId');
    
    if (!tvId) {
        console.error('TV ID not found in URL');
        return;
    }

    // Get all content types from the containers
    const containers = document.querySelectorAll('[data-container-id]');
    
    // Create a map of container IDs to content status
    const containerContentMap = {};
    
    containers.forEach(container => {
        const containerId = container.dataset.containerId;
        const type = container.dataset.type;
        
        if (containerId && type) {
            // Check if there's any content for this type
            const hasContent = contents[`${type}s`] && contents[`${type}s`].length > 0;
                        
            // Store content status for each container
            containerContentMap[containerId] = hasContent;
        }
    });
        
    // Send container content status to parent window if in iframe
    if (isIframe()) {
        window.parent.postMessage({
            action: 'containerContentStatus',
            containerContentMap: containerContentMap
        }, '*');
    }
};

Ws.addEventListener('message', function (event) {
    const data = JSON.parse(event.data);

    if (data.action === 'delete_content' || data.action === 'archive_content' || data.action === 'trash_content') {
        const type = data.type;
        const id = data[`${type}_id`]; 

        if (type == 'orgchart') {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
                location.reload();
            }
                    } else {
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
        } 
    } else if (data.action === 'restore_content' || data.action === 'restore_content_and_update_expiration') {
        const type = data.type;
        let shouldAutoTile = false;
        
        // Check if the container for this type is currently hidden
        const containerForType = document.querySelector(`[data-type="${type}"]`);
        if (containerForType && containerForType.style.display === 'none') {
            shouldAutoTile = true;
        }

        if (type === 'announcement') {
            fetchAndUpdateContents('announcement');
        } else if (type === 'event') {
            fetchAndUpdateContents('event');
        } else if (type === 'news') {
            fetchAndUpdateContents('news');
        } else if (type === 'promaterial') {
            fetchAndUpdateContents('promaterial');
        } else if (type === 'peo') {
            fetchAndUpdateContents('peo');
        } else if (type === 'so') {
            fetchAndUpdateContents('so');
        } else {
            // For new features 
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
                location.reload();
            }
        }
        
        // Only auto-tile if the container was previously hidden
        if (shouldAutoTile) {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            
            // First make the container visible by sending a show_hide_content message
            if (containerForType && isConnectedToWebSocket && Ws) {
                const containerId = containerForType.dataset.containerId;
                const containerVisibilityMap = {};
                containerVisibilityMap[containerId] = 1; // Set visibility to true (1)
                
                const message = {
                    action: 'show_hide_content',
                    tv_id: parseInt(tvId, 10),
                    containers: containerVisibilityMap
                };
                
                Ws.send(JSON.stringify(message));
                
                // Make it visible immediately in the UI as well
                containerForType.style.display = 'block';
            }
            
            // Then auto-tile after a short delay
            setTimeout(() => {
                autoTileContainers(tvId);
            }, 1000);
        }
    } else if (data.action === 'update_content') {
        if (data.type === 'announcement') {
            fetchAndUpdateContents('announcement');
        } else if (data.type === 'event') {
            fetchAndUpdateContents('event');
        } else if (data.type === 'news') {
            fetchAndUpdateContents('news');
        } else if (data.type === 'promaterial') {
            fetchAndUpdateContents('promaterial');
        } else if (data.type === 'peo') {
            fetchAndUpdateContents('peo');
        } else if (data.type === 'so') {
            fetchAndUpdateContents('so');
        } else if (data.type === 'orgchart') {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');

            if (parseInt(tvId, 10) === parseInt(data.tv_id, 10) || parseInt(tvId, 10) === parseInt(data.tv_ids, 10)) {
                location.reload();
            }
        } else {
            // For new features 
            const type = data.type;
            fetchAndUpdateContents(type);
        }
    } else if (data.action === 'post_content') {
        const type = data.type;
        let shouldAutoTile = false;
        
        // Check if the container for this type is currently hidden
        const containerForType = document.querySelector(`[data-type="${type}"]`);
        // Check if the data.status is Approved
        if (containerForType && containerForType.style.display === 'none' && data.status === 'Approved') {
            shouldAutoTile = true;
        }
        // Check if this is an urgent post and don't immediately display posts from Faculty and Staff since they need to be approved
        if (data.is_urgent_post === 1 && data.user_type !== 'Faculty' && data.user_type !== 'Staff') {
            // Get the content ID and ensure it's an integer
            const contentId = parseInt(data[`${type}_id`]);
            console.log(`Received urgent post for ${type} with ID ${contentId}`);
            
            // First try to find the content in the existing array if it's already been loaded
            let foundContent = null;
            if (contents[`${type}s`] && contents[`${type}s`].length > 0) {
                for (let i = 0; i < contents[`${type}s`].length; i++) {
                    const item = contents[`${type}s`][i];
                    if (parseInt(item.dataset[`${type}Id`]) === contentId) {
                        foundContent = item._data; // Access stored data if available
                        break;
                    }
                }
            }
            
            if (foundContent) {
                // Use the content already in memory
                handleUrgentPost(type, foundContent);
            } else {
                // Fetch the content if it's not already loaded
                // Use the full fetch_all endpoint to get all data, because the individual fetch may not be implemented
                fetch(`database/fetch_${type}.php`)
                    .then(response => response.json())
                    .then(allContentData => {
                        // Find the urgent post in all content
                        const contentData = Array.isArray(allContentData) ? 
                            allContentData.find(item => parseInt(item[`${type}_id`]) === contentId) : 
                            null;
                            
                        if (contentData) {
                            // Update or add the urgent post to the content array
                            updateUI(contentData, type);
                            
                            // Immediately handle the urgent post display
                            handleUrgentPost(type, contentData);
                        } else {
                            console.error(`Could not find ${type} with ID ${contentId} in data`);
                            fetchAndUpdateContents(type);
                        }
                    })
                    .catch(error => {
                        console.error(`Error fetching urgent ${type}:`, error);
                        // Fall back to regular fetching if we can't get the urgent post
                        fetchAndUpdateContents(type);
                    });
            }
        } else {
            // Handle regular post content as before
            if (type === 'announcement') {
                fetchAndUpdateContents('announcement');
            } else if (type === 'event') {
                fetchAndUpdateContents('event');
            } else if (type === 'news') {
                fetchAndUpdateContents('news');
            } else if (type === 'promaterial') {
                fetchAndUpdateContents('promaterial');
            } else if (type === 'peo') {
                fetchAndUpdateContents('peo');
            } else if (type === 'so') {
                fetchAndUpdateContents('so');
            } else if (type === 'orgchart') {
                const urlParams = new URLSearchParams(window.location.search);
                const tvId = urlParams.get('tvId');

                if (parseInt(tvId, 10) === parseInt(data.tv_id, 10) || parseInt(tvId, 10) === parseInt(data.tv_ids, 10)) {
                    shouldAutoTile = true;
                    // Add a small delay to ensure the content is loaded
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            } else if (type === 'facility') {
                const urlParams = new URLSearchParams(window.location.search);
                const tvId = urlParams.get('tvId');

                if (parseInt(tvId, 10) === parseInt(data.tv_id, 10) || parseInt(tvId, 10) === parseInt(data.tv_ids, 10)) {
                    shouldAutoTile = true;
                    // Add a small delay to ensure the content is loaded
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            } else {
                // For new features 
                fetchAndUpdateContents(type);
            }
        }
        
        // Only auto-tile if the container was previously hidden
        if (shouldAutoTile) {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            
            // First make the container visible by sending a show_hide_content message
            if (containerForType && isConnectedToWebSocket && Ws) {
                const containerId = containerForType.dataset.containerId;
                const containerVisibilityMap = {};
                containerVisibilityMap[containerId] = 1; // Set visibility to true (1)
                
                const message = {
                    action: 'show_hide_content',
                    tv_id: parseInt(tvId, 10),
                    containers: containerVisibilityMap
                };
                
                Ws.send(JSON.stringify(message));
                
                // Make it visible immediately in the UI as well
                containerForType.style.display = 'block';
            }
            
            // Then auto-tile after a short delay
            setTimeout(() => {
                autoTileContainers(tvId);
            }, 1000);
        }
    } else if (data.action === 'approve_post') {
        const type = data.content_type || data.type;
        let shouldAutoTile = false;
        
        // First, check if the container for this type is currently hidden
        const containerForType = document.querySelector(`[data-type="${type}"]`);
        if (containerForType && containerForType.style.display === 'none') {
            shouldAutoTile = true;
        }
        
        // When approving a post, we need to fetch the content to check if it's an urgent post
        // because the server response may not have the correct urgent flag
        const contentId = parseInt(data.content_id);
        console.log(`Post approved for ${type} with ID ${contentId}`);
        
        if (contentId) {
            // Fetch the content to check if it's an urgent post
            fetch(`database/fetch_${type}.php`)
                .then(response => response.json())
                .then(allContentData => {
                    // Find the content by ID
                    let contentData = null;
                    if (Array.isArray(allContentData)) {
                        contentData = allContentData.find(item => parseInt(item[`${type}_id`]) === contentId);
                    }
                    
                    if (contentData && contentData.is_urgent_post === 1) {
                        console.log(`Approved content is an urgent post: ${type} with ID ${contentId}`);
                        
                        // First try to find the content in the existing array
                        let foundContent = false;
                        if (contents[`${type}s`] && contents[`${type}s`].length > 0) {
                            for (let i = 0; i < contents[`${type}s`].length; i++) {
                                const item = contents[`${type}s`][i];
                                if (parseInt(item.dataset[`${type}Id`]) === contentId) {
                                    foundContent = true;
                                    break;
                                }
                            }
                        }
                        
                        // Update or add the urgent post
                        updateUI(contentData, type);
                        
                        // Handle it as an urgent post
                        handleUrgentPost(type, contentData);
                    } else {
                        // Regular approved post, just fetch and update
                        fetchAndUpdateContents(type);
                    }
                })
                .catch(error => {
                    console.error(`Error fetching approved ${type}:`, error);
                    // Fall back to regular fetching
                    fetchAndUpdateContents(type);
                });
        } else {
            // No content ID, just do a regular update
            fetchAndUpdateContents(type);
        }
        
        // Only auto-tile if the container was previously hidden
        if (shouldAutoTile) {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            
            // First make the container visible by sending a show_hide_content message
            if (containerForType && isConnectedToWebSocket && Ws) {
                const containerId = containerForType.dataset.containerId;
                const containerVisibilityMap = {};
                containerVisibilityMap[containerId] = 1; // Set visibility to true (1)
                
                const message = {
                    action: 'show_hide_content',
                    tv_id: parseInt(tvId, 10),
                    containers: containerVisibilityMap
                };
                
                Ws.send(JSON.stringify(message));
                
                // Make it visible immediately in the UI as well
                containerForType.style.display = 'block';
            }
            
            // Then auto-tile after a short delay
            setTimeout(() => {
                autoTileContainers(tvId);
            }, 1000);
        }
    } else if (data.action === 'edit_smart_tv' || data.action === 'delete_user' || data.action === 'user_deleted' || data.action === 'edit_user' || data.action === 'user_edited') {
        location.reload();
        // fetchSmartTVDetails();
    } else if (data.action === 'delete_feature') {
        // When a feature is deleted, reload all TV screens
        location.reload();
    } else if (data.action === 'delete_smart_tv') {
        const urlParams = new URLSearchParams(window.location.search);
        const tvId = urlParams.get('tvId');
        if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
            location.reload();
        }
    } else if (data.action === 'update_topbar_position') {
        if (data.success) {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
                const topbarElement = document.getElementById('topbar');
                const mainContainer = document.getElementById('main-container');
                
                // Remove topbar from its current position
                topbarElement.remove();
                
                if (data.topbar_position === 'top') {
                    // Insert before main container
                    mainContainer.parentNode.insertBefore(topbarElement, mainContainer);
                } else {
                    // Insert after main container
                    mainContainer.parentNode.insertBefore(topbarElement, mainContainer.nextSibling);
                }
            }
        }
    } else if (data.action === 'update_department_logo') {
        if (data.success) {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
                const departmentLogoTopbar = document.getElementById('department_logo_topbar');
                departmentLogoTopbar.src = '';
                departmentLogoTopbar.src = data.department_logo_path;
            }
        }
    } else if (data.action === 'update_background_image') {
        if (data.success) {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
                const backgroundElement = document.getElementById('tvBackgroundColor');
                backgroundElement.style.backgroundImage = '';
                backgroundElement.style.backgroundImage = `url('servers/tv_backgrounds/${data.tv_id}/${data.background_image_path}')`;
            }
        }
    } else if (data.action === 'update_background_type') {
        if (data.success) {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
                const backgroundElement = document.getElementById('tvBackgroundColor');
                if (data.isStaticBackground == 1) {
                    // Handle static background color
                    backgroundElement.style.background = data.background_hex_color;
                    // Clear any existing background image
                    backgroundElement.style.backgroundImage = '';
                    backgroundElement.style.backgroundSize = '';
                    backgroundElement.style.backgroundPosition = '';
                    backgroundElement.style.backgroundRepeat = '';
                } else if (data.isImageBackground == 1) {
                    // Handle background image
                    backgroundElement.style.backgroundImage = `url('servers/tv_backgrounds/${data.tv_id}/${data.background_image_path}')`;
                    backgroundElement.style.backgroundSize = 'cover';
                    backgroundElement.style.backgroundPosition = 'center';
                    backgroundElement.style.backgroundRepeat = 'no-repeat';
                    // Clear any existing background color
                }
            }
        }
    } else if (data.action === 'update_container_positions') {
        if (data.success) {
            const containers = data.containers;
            containers.forEach(container => {
                const containerElement = document.querySelector(`[data-container-id="${container.container_id}"]`);
                if (containerElement) {
                    containerElement.style.transform = `translate(${container.xaxis}px, ${container.yaxis}px)`;
                    containerElement.style.height = `${container.height_px}px`;
                    containerElement.style.width = `${container.width_px}px`;
                }
            });
        }
    } else if (data.action === 'show_hide_content') {
        if (data.success == true) {
            const containers = data.containers;
            containers.forEach(container => {
                const containerElement = document.querySelector(`[data-container-id="${container.container_id}"]`);
                if (containerElement) {
                    const currentVisibility = containerElement.style.display !== 'none';
                    const newVisibility = container.visible == 1;
                    const type = containerElement.dataset.type;
                                        
                    // Only update if visibility actually changed
                    if (currentVisibility !== newVisibility) {
                        containerElement.style.display = newVisibility ? 'block' : 'none';
                        
                        // If hiding, clear content and intervals
                        if (!newVisibility) {
                            if (type) {
                                // Don't clear the content array - just keep track of visibility
                                // This is important to accurately report content status
                                
                                if (displayTimeIntervals[type]) {
                                    clearInterval(displayTimeIntervals[type]);
                                    displayTimeIntervals[type] = null;
                                }
                            }
                        } else {
                            // If showing, fetch and update content only if we don't already have content
                            if (type && (!contents[`${type}s`] || contents[`${type}s`].length === 0)) {
                                fetchAndUpdateContents(type);
                            }
                        }
                    }
                }
            });
            
            // Always update container content status after visibility changes
            setTimeout(() => {
                updateContainerContentStatus();
            }, 1000); // Small delay to allow content to load/unload
        }
    } else if (data.action === 'update_background_color') {
        if (data.success) {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
                const backgroundElement = document.getElementById('tvBackgroundColor');
                backgroundElement.style.backgroundColor = data.background_hex_color;
            }
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
                    const { pageNumberContainer, containerName } = getContainerElements(container.type);
                    if (pageNumberContainer) {
                        pageNumberContainer.style.color = container.parent_font_color; // Change the color
                        pageNumberContainer.style.fontFamily = container.parent_font_family; // Change the font family
                        pageNumberContainer.style.fontStyle = container.parent_font_style; // Change the font style
                        containerName.style.textAlign = container.container_name_text_align;
                    } else {
                        console.error(`Page number container not found for type: ${container.type}`);
                    }
                } else {
                    // console.error(`Container not found for ID: ${container.container_id}`);
                }
            });
        }
    } else if (data.action === 'update_topbar_color') {
        if (data.success) {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
                const topbarElement = document.getElementById('topbar');
                if (topbarElement) {
                    topbarElement.style.backgroundColor = data.topbar_hex_color;
                    topbarElement.querySelector('.tv-name').style.color = data.topbar_tvname_font_color;
                    topbarElement.querySelector('.tv-name').style.fontStyle = data.topbar_tvname_font_style;
                    topbarElement.querySelector('.tv-name').style.fontFamily = data.topbar_tvname_font_family;

                    topbarElement.querySelector('.tv-id').style.color = data.topbar_tvid_font_color;
                    topbarElement.querySelector('.tv-id').style.fontStyle = data.topbar_tvid_font_style;
                    topbarElement.querySelector('.tv-id').style.fontFamily = data.topbar_tvid_font_family;

                    topbarElement.querySelector('.time').style.color = data.topbar_time_font_color;
                    topbarElement.querySelector('.time').style.fontStyle = data.topbar_time_font_style;
                    topbarElement.querySelector('.time').style.fontFamily = data.topbar_time_font_family;

                    topbarElement.querySelector('.date').style.color = data.topbar_date_font_color;
                    topbarElement.querySelector('.date').style.fontStyle = data.topbar_date_font_style;
                    topbarElement.querySelector('.date').style.fontFamily = data.topbar_date_font_family;
                }
            }
        }
    } else if (data.action === 'save_layout') {
        if (data.success) {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            const message = JSON.stringify({ action: 'load_layout', tv_id: tvId });
            Ws.send(message);
        }
    } else if (data.action === 'refresh_content' || data.action === 'manual_refresh_tv') {
        const urlParams = new URLSearchParams(window.location.search);
        const tvId = urlParams.get('tvId');
        if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
            location.reload();
        }
    } else if (data.action === 'clear_urgent_post') {
        // When server confirms the urgent post is cleared, no additional action needed
        // as our client-side timeout will handle the transition back to normal content
        console.log(`Urgent post cleared for ${data.type}`);
    }
});

// Helper function to create a new container element
function createContainerElement(container) {
    const containerDiv = document.createElement('div');
    containerDiv.className = 'content-container';
    containerDiv.id = container.container_name;
    containerDiv.setAttribute('data-container-id', container.container_id);
    containerDiv.style.transform = `translate(${container.xaxis}px, ${container.yaxis}px)`;
    containerDiv.style.height = `${container.height_px}px`;
    containerDiv.style.width = `${container.width_px}px`;
    containerDiv.style.backgroundColor = container.parent_background_color;
    containerDiv.setAttribute('data-x', container.xaxis);
    containerDiv.setAttribute('data-y', container.yaxis);

    // Create title element
    const titleDiv = document.createElement('div');
    titleDiv.className = 'content-title';
    titleDiv.textContent = container.container_name;
    titleDiv.style.color = container.parent_font_color;
    titleDiv.style.fontFamily = container.parent_font_family;
    titleDiv.style.fontStyle = container.parent_font_style;

    // Create carousel container
    const carouselDiv = document.createElement('div');
    carouselDiv.id = `${container.type}CarouselContainer`;
    carouselDiv.className = 'carousel-container';
    carouselDiv.style.backgroundColor = container.child_background_color;
    carouselDiv.style.color = container.child_font_color;
    carouselDiv.style.fontFamily = container.child_font_family;
    carouselDiv.style.fontStyle = container.child_font_style;

    // Create page number container
    const pageNumberDiv = document.createElement('div');
    pageNumberDiv.id = `${container.type}PageNumberContainer`;
    pageNumberDiv.className = 'page-number';
    pageNumberDiv.style.color = container.parent_font_color;
    pageNumberDiv.style.fontFamily = container.parent_font_family;
    pageNumberDiv.style.fontStyle = container.parent_font_style;

    // Assemble the container
    containerDiv.appendChild(titleDiv);
    containerDiv.appendChild(carouselDiv);
    containerDiv.appendChild(pageNumberDiv);

    return containerDiv;
}

let contentLoadingStatus = {};

document.addEventListener('DOMContentLoaded', () => {
    // Get all content types from the containers
    const containers = document.querySelectorAll('[id$="CarouselContainer"]');
    containers.forEach(container => {
        const type = container.id.replace('CarouselContainer', '');
        // Initialize arrays for new types if they don't exist
        if (!currentIndex[type]) {
                        currentIndex[type] = 0;
                    }
        if (!contents[`${type}s`]) {
            contents[`${type}s`] = [];
        }
        if (!displayTimeIntervals[type]) {
            displayTimeIntervals[type] = null;
        }
        // Initialize loading status for each type
        contentLoadingStatus[type] = false;
        // Fetch content for each type
        fetchAndUpdateContents(type);
    });

    checkAllContentForExpiration();
    fetchSmartTVDetails();
});

// a window message listener to handle parent window requests
if (isIframe()) {
    window.addEventListener('message', function(event) {
        // Check the origin for security
        if (event.data.action === 'requestContentStatus') {
            // Send the current content status back to the parent
            updateContainerContentStatus();
        }
    });
}