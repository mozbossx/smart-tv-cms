let isConnectedToWebSocket = false;

function isIframe() {
    return window.self !== window.top;
}

function updateTvStatus(status) {
    const urlParams = new URLSearchParams(window.location.search);
    const currentTvId = urlParams.get('tvId');
    Ws.send(JSON.stringify({ action: 'update_tv_status', status: status, tvId: currentTvId }));
}

function connectWebSocket() {
    console.log('Attempting WebSocket connection...');
    
    // Determine if we're in localhost or production
    const isLocalhost = window.location.hostname === 'localhost' || 
                       window.location.hostname === '127.0.0.1' ||
                       window.location.hostname === '192.168.1.12' ||
                       window.location.hostname === '192.168.1.5';
    
    // Choose WebSocket URL based on environment
    const wsUrl = isLocalhost 
        ? 'ws://192.168.1.5:8081'
        : 'wss://usccms.online/ws';
    
    try {
        const ws = new WebSocket(wsUrl);
        let clientPingInterval;
        let clientPingTimeout;

        const heartbeat = () => {
            clearTimeout(clientPingTimeout);
            clientPingTimeout = setTimeout(() => {
                // console.log('Client: Connection lost - no pong received');
                ws.close();
            }, 20000); // 20 second timeout
        };
        
        ws.onopen = function() {
            // console.log('Client: WebSocket Connected Successfully to:', wsUrl);
            isConnectedToWebSocket = true;

            if (!isIframe()) {
                console.log('Client: Updating TV status to active');
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
                    // console.log('Client: Sending ping to server');
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
                    // console.log('Client: Received ping from server, sending pong!');
                    ws.send(JSON.stringify({ action: 'pong' }));
                    return;
                case 'pong':
                    // Reset client's heartbeat timeout
                    // console.log('Client: Received pong from server, resetting heartbeat timeout!');
                    heartbeat();
                    return;
            }
        };
        
        ws.onclose = function(event) {
            // console.log('Client: WebSocket connection closed');
            isConnectedToWebSocket = false;
            clearInterval(clientPingInterval);
            clearTimeout(clientPingTimeout);

            updateTvStatus('inactive');
            // console.log('Client: Cleared all timers');

            if (loadingWebsocketConnectionModal) {
                loadingWebsocketConnectionModal.style.display = 'flex';
                successWebsocketConnectionModal.style.display = 'none';
                loadingWebsocketConnectionMessage.textContent = 'Server Disconnected. Reconnecting...';
            }

            // Attempt to reconnect
            setTimeout(() => {
                console.log('Client: Attempting to reconnect.......');
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
    console.error('Error initializing WebSocket:', error);
    console.log('Attempting reconnection in 5 seconds...');
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

// Unified function to update UI
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
        if (data.status !== 'Approved' || data.isArchived === 1 || data.isTrashed === 1 || !data[`${type}_id`]) {
            return;
        }
    }
    
    const formattedCreatedDateTime = type !== 'orgchart' ? formatDateTime(data.created_datetime) : '';
    const id = data[`${type}_id`]; 

    // const existingDiv = document.querySelector(`[data-${type}-id="${data[`${type}_id`]}"]`);
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
            setupOrgChartResizeObserver(existingDiv.querySelector('.orgchart-container'));
        } else {
            contentsArray.push(containerDiv);
            carouselContainer.appendChild(containerDiv);

            if (contentsArray.length === 1) {
                containerDiv.classList.add('active');
                // Render the first chart immediately
                setTimeout(() => {
                    createOrgChart(id, data.chartData);
                    setupOrgChartResizeObserver(containerDiv.querySelector('.orgchart-container'));
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
                return '<i class="fas fa-user-secret" title="Super Admin" style="font-size: 1rem; background-color: #9393933b; padding: 5px; border-radius: 100%;"></i>';
            case 'Admin':
                return '<i class="fas fa-user-tie" title="Admin" style="font-size: 1rem; background-color: #9393933b; padding: 5px; border-radius: 100%;"></i>';
            case 'Faculty':
                return '<i class="fas fa-chalkboard-teacher" title="Faculty" style="font-size: 1rem; background-color: #9393933b; padding: 5px; border-radius: 100%;"></i>';
            case 'Staff':
                return '<i class="fas fa-user-gear" title="Staff" style="font-size: 1rem; background-color: #9393933b; padding: 5px; border-radius: 100%;"></i>';
            case 'Student':
                return '<i class="fas fa-user-graduate" title="Student" style="font-size: 1rem; background-color: #9393933b; padding: 5px; border-radius: 100%;"></i>';
            default:
                return '<i class="fas fa-user" title="User" style="font-size: 1rem; background-color: #9393933b; padding: 5px; border-radius: 100%;"></i>';
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
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
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
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
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
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
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
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
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
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
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
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
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
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
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
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
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
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
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
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
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
            updatePageNumber(currentIndexKey);
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

        // containerDiv.setAttribute('data-announcement-id', data.announcement_id);

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
        // carouselContainer.appendChild(pageNumberContainer);

        if (contentsArray.length === 1) {
            containerDiv.classList.add('active');
            updatePageNumber(currentIndexKey);
        } else {
            updatePageNumber(currentIndexKey);
        }

        // Apply text fitting after updating content
        setTimeout(() => initializeTextFitting(type, id), 0);
    }
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


// function adjustFontSize(type) {
//     const carouselContainer = document.getElementById(`${type}CarouselContainer`);
    
//     if (!carouselContainer) {
//         console.warn(`${type}CarouselContainer not found`);
//         return;
//     }

//     const textContainers = carouselContainer.querySelectorAll('.main-message');
//     if (!textContainers.length) {
//         return; // No text containers found, exit quietly
//     }

//     textContainers.forEach((textContainer) => {
//         // Get container dimensions
//         const containerWidth = carouselContainer.offsetWidth;
//         const containerHeight = carouselContainer.offsetHeight;

//         // Reset styles to get proper measurements
//         textContainer.style.fontSize = '10px';
//         textContainer.style.lineHeight = '1.2';

//         // Incrementally adjust font size
//         let fontSize = 10;
//         const maxFontSize = 24; // Maximum font size
        
//         while (
//             fontSize < maxFontSize &&
//             textContainer.scrollHeight <= containerHeight * 0.9 && // Use 90% of container height
//             textContainer.scrollWidth <= containerWidth * 0.9 // Use 90% of container width
//         ) {
//             fontSize++;
//             textContainer.style.fontSize = `${fontSize}px`;
//         }

//         // Step back one size to ensure fit
//         fontSize = Math.max(10, fontSize - 1);
//         textContainer.style.fontSize = `${fontSize}px`;

//         // Adjust line height for readability
//         textContainer.style.lineHeight = '1.4';
//     });
// }

// Set up a MutationObserver to watch for changes in a specific carousel container
// function setupFontSizeObserver(type) {
//     const observer = new MutationObserver((mutations) => {
//         mutations.forEach((mutation) => {
//             if (mutation.type === 'childList' || mutation.type === 'characterData') {
//                 adjustFontSize(type);
//             }
//         });
//     });

//     const carouselContainer = document.getElementById(`${type}CarouselContainer`);
//     if (carouselContainer) {
//         observer.observe(carouselContainer, { childList: true, subtree: true, characterData: true });
//     }
// }

// Function to fetch org chart data
// const fetchOrgChartData = () => {
//     return fetch('database/fetch_orgchart.php')
//         .then(response => response.json())
//         .catch(error => {
//             console.error('Error fetching org chart data:', error);
//             return {};
//         });
// };

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

            if (!treant) {
                console.error(`Treant not found for orgchart ${orgchartId}`);
                return;
            }

            if (!svg) {
                console.error(`SVG not found for orgchart ${orgchartId}`);
                return;
            }

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
        });
    } catch (error) {
        console.error('Error creating org chart:', error);
        contentDiv.innerHTML = '<p style="text-align: center; color: #264B2B; font-size: 1.2em; padding: 20px;">Error loading organizational chart.</p>';
    }
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

// Add resize observer to handle container resizing
const setupOrgChartResizeObserver = (container) => {
    let resizeTimeout;
    const resizeObserver = new ResizeObserver((entries) => {
        // Clear any existing timeout
        if (resizeTimeout) {
            clearTimeout(resizeTimeout);
        }

        // Set a new timeout to debounce the resize event
        resizeTimeout = setTimeout(() => {
            for (const entry of entries) {
                const activeItem = entry.target.closest('.carousel-item');
                if (activeItem && activeItem.classList.contains('active')) {
                    const chartData = activeItem.chartData;
                    if (chartData) {
                        createOrgChart(activeItem.dataset.orgchartId, chartData);
                    }
                }
            }
        }, 1000); // Small delay for performance
    });

    resizeObserver.observe(container);
    return resizeObserver;
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

        // Add active class to the new current item
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

                // Add ended event listener
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
                }, 100);
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
    const previousIndex = currentIndex[type]; // Save the current index
    contents[type + 's'] = [];
    const { carouselContainer, pageNumberContainer } = getContainerElements(type); // Get the containers dynamically
    carouselContainer.innerHTML = ''; // Clear the container
    pageNumberContainer.innerHTML = ''; // Clear the container

    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId');

    // List of predefined content types
    const predefinedTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so', 'orgchart'];

    if (tvId && predefinedTypes.includes(type)) {
        fetch(`database/fetch_smart_tvs.php?tvId=${tvId}`)
            .then(response => response.json())
            .then(data => {
                const tvName = data.tv_name;
                const tvDepartment = data.tv_department;
                if (type === 'orgchart') {
                    fetch(`database/fetch_orgchart.php?tvId=${tvId}`)
                        .then(response => response.json())
                        .then(data => {
                            let hasMatchingOrgchart = false;
                            Object.entries(data).forEach(([orgchartId, orgchartData]) => {
                                // updateUI({
                                //     orgchart_id: orgchartId,
                                //     chartData: orgchartData,
                                //     display_time: orgchartData.display_time
                                // }, 'orgchart');
                                // Check if the orgchart's department matches the TV's department
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

                            // Show "no message" if no matching orgcharts were found
                            if (!hasMatchingOrgchart) {
                                displayNoMessage('orgchart');
                            } else {
                                currentIndex['orgchart'] = Math.min(currentIndex['orgchart'], contents['orgcharts'].length - 1);
                                startCarousel('orgchart');
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching orgcharts:', error);
                            displayNoMessage('orgchart');
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

                            // OLD CODE
                            // filteredData.forEach(item => updateUI(item, type));
                            // if (contents[type + 's'].length > 0) {
                            //     // Set the current index to 0 to start from the first item
                            //     currentIndex[type] = Math.min(currentIndex[type], contents[type + 's'].length - 1); // Starts at the first item
                            //     startCarousel(type);
                            // } else {
                            //     displayNoMessage(type); // Call displayNoMessage if no content
                            // }

                            // NEW CODE
                            // Sort by user type priority and then by created_datetime
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

                            // OLD CODE
                            // filteredData.forEach(item => updateUI(item, type));
                            // if (contents[type + 's'].length > 0) {
                            //     // Set the current index to 0 to start from the first item
                            //     currentIndex[type] = Math.min(currentIndex[type], contents[type + 's'].length - 1); // Starts at the first item
                            //     startCarousel(type);
                            // } else {
                            //     displayNoMessage(type); // Call displayNoMessage if no content
                            // }

                            // NEW CODE
                            // Sort by user type priority and then by created_datetime
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
                        // adjustFontSize(type);
                    })
                    .catch(error => console.error(`Error fetching ${type}s:`, error));
                }
            })
            .catch(error => console.error('Error fetching TV data:', error));
    } else if (tvId && !predefinedTypes.includes(type)) {
        fetch(`database/fetch_smart_tvs.php?tvId=${tvId}`)
            .then(response => response.json())
            .then(data => {
                const tvName = data.tv_name;
                const tvDepartment = data.tv_department;
                // Use fetch_content.php for new features
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
                            // Sort by user type priority and then by created_datetime
                            filteredData.sort((a, b) => {
                                const priorityDiff = getUserTypePriority(b.user_type) - getUserTypePriority(a.user_type);
                                if (priorityDiff !== 0) return priorityDiff;
                                return new Date(b.created_datetime) - new Date(a.created_datetime);
                            });
                            
                            filteredData.forEach(item => updateUI(item, type));
                            
                            // filteredData.forEach(item => updateUI(item, type));
                            
                            // Previous working code below
                            // data.forEach(item => {
                            //     if (item.tv_id === parseInt(tvId, 10)) {
                            //         updateUI(item, type);
                            //     }
                            // });
                        } else if (data && typeof data === 'object' && 
                                data.tv_id === parseInt(tvId, 10) &&
                                data.department === tvDepartment) {
                            updateUI(data, type);
                        }

                        // Start carousel if there's content
                        if (contents[`${type}s`].length > 0) {
                            // currentIndex[type] = 0;
                            currentIndex[type] = Math.min(currentIndex[type], contents[type + 's'].length - 1); // Starts at the first item
                            // showNextContent(type); // Initialize first content
                            resetDisplayTime(type); // Start the countdown timer
                        } else {
                            displayNoMessage(type);
                        }
                    })
                    .catch(error => console.error(`Error fetching new feature ${type}:`, error));
            })
            .catch(error => console.error('Error fetching TV data:', error));
    } else {
        console.error('TV ID not found in URL');
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
        // Skip types that don't use expiration
        if (type === 'orgchart' || type === 'facility') return;

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

// Add periodic check for expired content (every minute)
setInterval(checkAllContentForExpiration, 60000);

Ws.addEventListener('message', function (event) {
    const data = JSON.parse(event.data);

    if (data.action === 'delete_content' || data.action === 'archive_content' || data.action === 'trash_content') {
        const type = data.type;
        const id = data[`${type}_id`]; 

        if (type == 'orgchart') {
            // Refresh TV since there's an issue for the orgchart when posting it
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
        } else {
            // For new features 
            // Refresh TV since there's an issue for the new feature when posting it
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
                location.reload();
            }
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
        } else if (data.type === 'facility') {
            // Refresh TV since there's an issue for the facility when posting it
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
    } else if (data.action === 'approve_post') {
        const type = data.content_type || data.type;
        
        // Fetch and update the content immediately
        setTimeout(() => {
            fetchAndUpdateContents(type);
        }, 500);
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
    } else if (data.action === 'update_department_logo') {
        if (data.success) {
            const departmentLogoTopbar = document.getElementById('department_logo_topbar');
            departmentLogoTopbar.src = '';
            departmentLogoTopbar.src = data.department_logo_path;
        }
    } else if (data.action === 'update_background_image') {
        if (data.success) {
            const backgroundElement = document.getElementById('tvBackgroundColor');
            backgroundElement.style.backgroundImage = '';
            backgroundElement.style.backgroundImage = `url('servers/tv_backgrounds/${data.tv_id}/${data.background_image_path}')`;
        }
    } else if (data.action === 'update_background_type') {
        if (data.success) {
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
        const urlParams = new URLSearchParams(window.location.search);
        const tvId = urlParams.get('tvId');
        if (parseInt(tvId, 10) === parseInt(data.tv_id, 10)) {
            location.reload();
        }
        // TODO: Fix this
        // if (data.success) {
        //     const containers = data.containers;
        //     const mainContainer = document.getElementById('main-container');
            
        //     containers.forEach(container => {
        //         const containerElement = document.querySelector(`[data-container-id="${container.container_id}"]`);
        //         const type = container.type;
                
        //         if (container.visible == 0 && containerElement) {
        //             // Remove container from DOM if it's being hidden
        //             containerElement.remove();
                    
        //             // Clear its content array
        //             contents[`${type}s`] = [];
                    
        //             // Clear any existing intervals
        //             if (displayTimeIntervals[type]) {
        //                 clearInterval(displayTimeIntervals[type]);
        //                 displayTimeIntervals[type] = null;
        //             }
        //         } else if (container.visible == 1 && !containerElement) {
        //             // Create and add the new container
        //             const newContainer = createContainerElement(container);
        //             mainContainer.appendChild(newContainer);
                    
        //             // Initialize the content for this container
        //             currentIndex[type] = 0;
        //             contents[`${type}s`] = [];
        //             displayTimeIntervals[type] = null;
                    
        //             // Fetch and load content for the new container
        //             fetchAndUpdateContents(type);
        //         }
        //     });
        // }
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
    } else if (data.action === 'save_layout') {
        if (data.success) {
            const urlParams = new URLSearchParams(window.location.search);
            const tvId = urlParams.get('tvId');
            const message = JSON.stringify({ action: 'load_layout', tv_id: tvId });
            Ws.send(message);
        }
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
        // Fetch content for each type
        fetchAndUpdateContents(type);
    });

    checkAllContentForExpiration();
    // initializeOrgCharts();
    // const types = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so', 'orgchart'];
    // types.forEach(type => {
    //     adjustFontSize(type);
    //     setupFontSizeObserver(type);
    // });
    fetchSmartTVDetails();
});