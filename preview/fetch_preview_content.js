let isConnectedToWebSocket = false;

function isIframe() {
    return window.self !== window.top;
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

// Modify the preview update event listener
document.addEventListener('previewUpdate', function(e) {
    // console.log("Preview update event received:", e.detail);
    
    // Process the preview data using our standard function
    if (e.detail && e.detail.action === 'updatePreview') {
        updatePreviewContent(e.detail);
        
        // Also try a direct override approach as a fallback
        const { type, content, displayTime } = e.detail;
        if (type && content) {
            const container = document.getElementById(`${type}CarouselContainer`);
            if (container) {
                // Direct HTML injection as a final fallback
                container.innerHTML = `
                    <div class="carousel-item preview-item active" style="display: block; height: 100%;">
                        <div class="content-container-con">
                            <div class="content-main">
                                <div class="message-wrapper">
                                    <div class="main-message" style="word-break: break-word;">
                                        ${content}
                                    </div>
                                </div>
                            </div>
                            <div class="content-details">
                                <div style="display: flex; flex-direction: row; margin: 0">
                                    <div>
                                        <p class="author"><small>Preview</small></p>
                                    </div>
                                    <div style="margin-left: auto; margin-top: auto">
                                        <p class="display-time" style="text-align: right">
                                            <i class="fa fa-hourglass-half" aria-hidden="true"></i>
                                            <span class="time-left">${displayTime}s</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }
    }
});

// Keep the existing window.addEventListener for postMessage as a fallback
window.addEventListener('message', function(event) {   
    // Only process if not already handled by the custom event
    if (event.data && event.data.action === 'updatePreview') {
        // Create a custom event as a more reliable way to pass the data
        const previewEvent = new CustomEvent('previewUpdate', { 
            detail: event.data 
        });
        document.dispatchEvent(previewEvent);
    }
});

// Function to process preview data and update UI
function updatePreviewContent(previewData) {    
    const { type, content, media, displayTime } = previewData;
    
    if (!type) {
        console.error("Missing type in preview data");
        return;
    }

    // Debug log for content structure
    console.log(`Preview data for ${type}:`, {
        type,
        contentType: typeof content, 
        contentIsObject: content && typeof content === 'object',
        contentKeys: content && typeof content === 'object' ? Object.keys(content) : null,
        content
    });
    
    // Check if container exists for this type
    const container = document.getElementById(`${type}CarouselContainer`);
    if (!container) {
        console.error(`Container for type '${type}' not found`);
        return;
    }
        
    // Create a mock data object
    const mockData = {
        [`${type}_id`]: 'preview-item',
        status: 'Approved',
        isArchived: 0,
        isTrashed: 0,
        display_time: displayTime || '5',
        created_datetime: new Date().toISOString(),
        media_path: media || null
    };
    
    // type-specific content
    switch(type) {
        case 'announcement':
            mockData.announcement_body = content || '<p>Preview content</p>';
            break;
        case 'event':
            mockData.event_body = content || '<p>Preview content</p>';
            break;
        case 'news':
            mockData.news_body = content || '<p>Preview content</p>';
            break;
        case 'promaterial':
            // Content is mainly media for promotional materials
            break;
        case 'peo':
            // Check if content is a pre-formatted HTML string (from edit mode)
            if (typeof content === 'string' && (content.includes('<div class=') || content.includes('<h2>') || content.includes('<div>'))) {
                // Already formatted HTML - pass it directly in the title field
                // The createPreviewContent function will detect this and use it directly
                mockData.peo_title = content;
            } else if (content && typeof content === 'object') {
                // Normal object with separate fields - store all fields
                mockData.peo_title = content.title || '<p>Preview title</p>';
                mockData.peo_description = content.description || '<p>Preview description</p>';
                mockData.peo_subdescription = content.subdescription || '<p>Preview subdescription</p>';
            } else {
                // Fallback in case content is not in expected format
                console.warn("PEO content not in expected format", content);
                // Create empty fields to avoid undefined errors
                mockData.peo_title = '<p>Preview title</p>';
                mockData.peo_description = '<p>Preview description</p>';
                mockData.peo_subdescription = '<p>Preview subdescription</p>';
            }
            break;
        case 'so':
            // Check if content is a pre-formatted HTML string (from edit mode)
            if (typeof content === 'string' && (content.includes('<div class=') || content.includes('<h2>') || content.includes('<div>'))) {
                // Already formatted HTML - pass it directly in the title field
                // The createPreviewContent function will detect this and use it directly
                mockData.so_title = content;
                mockData.so_description = content;
                mockData.so_subdescription = content;
            } else if (content && typeof content === 'object') {
                // Normal object with separate fields - store all fields
                mockData.so_title = content.title || '<p>Preview title</p>';
                mockData.so_description = content.description || '<p>Preview description</p>';
                mockData.so_subdescription = content.subdescription || '<p>Preview subdescription</p>';
            } else {
                // Fallback in case content is not in expected format
                console.warn("SO content not in expected format", content);
                // Create empty fields to avoid undefined errors
                mockData.so_title = '<p>Preview title</p>';
                mockData.so_description = '<p>Preview description</p>';
                mockData.so_subdescription = '<p>Preview subdescription</p>';
            }
        case 'orgchart':
            // For orgchart, use the content directly as chartData
            mockData.chartData = content;
            console.log("Processed org chart data:", mockData.chartData);
            break;
        case 'facility':
            mockData.facility_body = content || '<p>Preview content</p>';
            
            // Handle array of images for facility
            if (Array.isArray(media)) {
                // For facility preview, use only the first image from the array
                mockData.facility_images_array = media.length > 0 ? media[0] : null;
                console.log("Using first image from array for facility preview:", mockData.facility_images_array);
            } else if (media) {
                // If it's a single media item, use it directly
                mockData.facility_images_array = media;
            }
            break;
        default:
            // Handle new feature types generically
            console.log(`Handling preview for new feature type: ${type}`);
            
            // If content is an object with specific fields
            if (content && typeof content === 'object') {
                Object.entries(content).forEach(([key, value]) => {
                    mockData[`${type}_${key}`] = value;
                });
            } else {
                // Try to determine the most appropriate field name
                // Check common naming patterns first
                if (typeof content === 'string') {
                    mockData[`${type}_body`] = content;
                    mockData[`${type}_text`] = content;
                }
            }
            
            // Handle media for new feature types
            if (media) {
                mockData.media_path = media;
            }
            
            break;
    }    
    // Create preview content manually without using updateUI
    createPreviewContent(mockData, type);
}

// Update the createPreviewContent function for better media handling
function createPreviewContent(data, type) {   
    if (type === 'peo' || type === 'so') {
        console.log(`Creating preview for ${type} with data:`, {
            title: data[`${type}_title`],
            description: data[`${type}_description`],
            subdescription: data[`${type}_subdescription`],
            isFormattedHTML: typeof data[`${type}_title`] === 'string' && 
                (data[`${type}_title`].includes('<div class=') || 
                 data[`${type}_title`].includes('<div class=') || 
                 data[`${type}_title`].includes('<div>'))
        });
    }
    
    const container = document.getElementById(`${type}CarouselContainer`);
    if (!container) {
        console.error(`Container for type '${type}' not found in createPreviewContent`);
        return;
    }
    
    // Clear any existing preview items
    const existingPreviews = container.querySelectorAll('.preview-item');
    existingPreviews.forEach(item => item.remove());
    
    // Format date for display
    const formattedDateTime = formatDateTime(data.created_datetime);
    
    // Process media content - enhanced for data URLs
    let mediaContent = '';
    if (data.media_path && type != 'facility') {        
        if (typeof data.media_path === 'string' && data.media_path.startsWith('data:')) {
            // Data URL (preview)
            const isImage = data.media_path.startsWith('data:image/');
            const isVideo = data.media_path.startsWith('data:video/');
            
            if (isImage) {
                mediaContent = `
                    <img src="${data.media_path}" 
                         style="max-width: 100%; max-height: 100%; object-fit: contain; display: block;user-select: none; pointer-events: none" 
                         alt="Preview image">
                `;
            } else if (isVideo) {
                mediaContent = `
                    <video preload="metadata" muted style="max-width: 100%; max-height: 100%; object-fit: contain; display: block; user-select: none; pointer-events: none">
                        <source src="${data.media_path}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                `;
            }
        } else {
            // Regular file path
            const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
            const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
            
            // Check if the path already includes servers/ prefix to prevent duplication
            let mediaPath = data.media_path;
            if (mediaPath.startsWith('servers/')) {
                // Already has the correct path, use it directly
                if (isImage) {
                    mediaContent = `
                        <img src="${mediaPath}" 
                             style="max-width: 100%; max-height: 100%; object-fit: contain; display: block; user-select: none; pointer-events: none"
                             alt="Content image">
                    `;
                } else if (isVideo) {
                    mediaContent = `
                        <video preload="metadata" muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain; display: block; user-select: none; pointer-events: none">
                            <source src="${mediaPath}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    `;
                }
            } else {
                // Need to add the path prefix
                if (isImage) {
                    mediaContent = `
                        <img src="../servers/${type}_media/${mediaPath}" 
                             style="max-width: 100%; max-height: 100%; object-fit: contain; display: block; user-select: none; pointer-events: none"
                             alt="Content image">
                    `;
                } else if (isVideo) {
                    mediaContent = `
                        <video preload="metadata" muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain; display: block; user-select: none; pointer-events: none">
                            <source src="../servers/${type}_media/${mediaPath}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    `;
                }
            }
        }
    }
    
    // Create the container div for the content
    const divElement = document.createElement('div');
    divElement.className = 'carousel-item preview-item active';
    divElement.dataset[`${type}Id`] = 'preview';
    divElement.dataset.displayTime = data.display_time;
    divElement.dataset.preview = 'true';
    
    // Create content HTML based on type - using the most direct approach
    if (type === 'announcement') {
        divElement.innerHTML = `
            <div class="content-container-con">
                <div class="content-main" id="${type}ContentMain-preview">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px; display: block;">${mediaContent}</div>` : ''}
                    <div class="message-wrapper" id="${type}MessageWrapper-preview">
                        <div class="main-message" id="${type}Message-preview" style="word-break: break-word;">
                            ${data.announcement_body}
                        </div>
                    </div>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                            <p class="author"><small>${formattedDateTime}</small></p>
                        </div>
                        <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                            <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'event') {
        divElement.innerHTML = `
            <div class="content-container-con">
                <div class="content-main" id="${type}ContentMain-preview">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px; display: block;">${mediaContent}</div>` : ''}
                    <div class="message-wrapper" id="${type}MessageWrapper-preview">
                        <div class="main-message" id="${type}Message-preview" style="word-break: break-word;">
                            ${data.event_body}
                        </div>
                    </div>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                            <p class="author"><small>${formattedDateTime}</small></p>
                        </div>
                        <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                            <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'news') {
        divElement.innerHTML = `
            <div class="content-container-con">
                <div class="content-main" id="${type}ContentMain-preview">
                    ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px; display: block;">${mediaContent}</div>` : ''}
                    <div class="message-wrapper" id="${type}MessageWrapper-preview">
                        <div class="main-message" id="${type}Message-preview" style="word-break: break-word;">
                            ${data.news_body}
                        </div>
                    </div>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                            <p class="author"><small>${formattedDateTime}</small></p>
                        </div>
                        <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                            <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'promaterial') {
        divElement.innerHTML = `
            <div class="content-container-con">
                <div class="content-main" id="${type}ContentMain-preview" style="height: 100%;">
                    ${mediaContent ? `<div class="media-container" style="height: 100%; display: flex; align-items: center; justify-content: center; max-height: 100%">${mediaContent}</div>` : '<div style="text-align: center; padding: 20px;">No media content</div>'}
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                        <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                            <p class="author"><small>${formattedDateTime}</small></p>
                        </div>
                        <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                            <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (type === 'peo') {
        // Check if peo_title is a string and if it contains HTML with div class (indicating formatted content)
        if (typeof data.peo_title === 'string' && (data.peo_title.includes('<div class=') || data.peo_title.includes('<div class=') || data.peo_title.includes('<div>'))) {
            // This is already formatted HTML from edit mode - use it directly
            divElement.innerHTML = `
                <div class="content-container-con">
                    <div class="content-main" id="${type}ContentMain-preview">
                        <div class="message-wrapper" id="${type}MessageWrapper-preview">
                            <div class="main-message" id="${type}Message-preview" style="word-break: break-word;">
                                ${data.peo_title}
                                ${data.peo_description}
                                ${data.peo_subdescription}
                            </div>
                        </div>
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                <p class="author"><small>${formattedDateTime}</small></p>
                            </div>
                            <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                                <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Regular PEO content, format it individually
            divElement.innerHTML = `
                <div class="content-container-con">
                    <div class="content-main" id="${type}ContentMain-preview">
                        <div class="message-wrapper" id="${type}MessageWrapper-preview">
                            <div class="main-message" id="${type}Message-preview" style="word-break: break-word;">
                                <div class="peo-title">
                                    ${data.peo_title || ''}
                                </div>
                                <div class="peo-description">
                                    ${data.peo_description || ''}
                                </div>
                                ${data.peo_subdescription ? `
                                    <div class="peo-subdescription">
                                        ${data.peo_subdescription}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                <p class="author"><small>${formattedDateTime}</small></p>
                            </div>
                            <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                                <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    } else if (type === 'so') {
        // Check if so_title is a string and if it contains HTML with div class (indicating formatted content)
        if (typeof data.so_title === 'string' && (data.so_title.includes('<div class=') || data.so_title.includes('<div class='))) {
            // This is already formatted HTML from edit mode - use it directly
            divElement.innerHTML = `
                <div class="content-container-con">
                    <div class="content-main" id="${type}ContentMain-preview">
                        <div class="message-wrapper" id="${type}MessageWrapper-preview">
                            <div class="main-message" id="${type}Message-preview" style="word-break: break-word;">
                                ${data.so_title}
                            </div>
                        </div>
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                <p class="author"><small>${formattedDateTime}</small></p>
                            </div>
                            <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                                <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Regular SO content, format it individually
            divElement.innerHTML = `
                <div class="content-container-con">
                    <div class="content-main" id="${type}ContentMain-preview">
                        <div class="message-wrapper" id="${type}MessageWrapper-preview">
                            <div class="main-message" id="${type}Message-preview" style="word-break: break-word;">
                                ${data.so_title}
                                ${data.so_description}
                                ${data.so_subdescription}
                            </div>
                        </div>
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                <p class="author"><small>${formattedDateTime}</small></p>
                            </div>
                            <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                                <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    } if (type === 'orgchart') {
        // Create a unique container for this preview
        const previewId = `preview-${Date.now()}`;
        const chartContainerId = `orgChartContainer-${previewId}`;
        const chartContainerParentId = `orgChartContainerParent-${previewId}`;
        
        const previewHtml = `
            <div class="carousel-item preview-item active" style="display: block; height: 100%;">
                <div class="content-container-con">
                    <div class="content-main">
                        <div id="${chartContainerParentId}" class="orgchart-container-parent" style="width: 100%; height: 100%; position: relative;">
                            <div id="${chartContainerId}" class="orgchart-container" style="width: 100%; height: 100%;"></div>
                        </div>
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                <p class="author"><small>${formattedDateTime}</small></p>
                            </div>
                            <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                                <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = previewHtml;
        
        // Initialize the org chart after a short delay to ensure container is ready
        setTimeout(() => {
            if (data.chartData && data.chartData.members) {
                createOrgChart(previewId, data.chartData);
            }
        }, 100);
        
        return;
    } else if (type === 'facility') {
        // Special handling for facility type
        const facilityBody = data.facility_body;
        let facilityImage = '';
        
        // Handle facility images - WITH SAFE ERROR HANDLING ADDED
        if (data.facility_images_array) {
            // For preview, we only use the first image
            let imageSrc = '';
            
            // SAFE HANDLING: Ensure the value is a string before using string methods
            const imageValue = data.facility_images_array;
            const isString = typeof imageValue === 'string';
            
            if (isString && imageValue.startsWith('data:')) {
                // It's a data URL
                imageSrc = imageValue;
                console.log("Using data URL for facility preview");
            } else {
                // It's a file path or not a string - safest approach is to just use it as is
                try {
                    imageSrc = `../servers/facility_media/${imageValue}`;
                    console.log("Using server path for facility preview:", imageValue);
                } catch (e) {
                    console.error("Error processing facility image:", e);
                    // Fallback to a default image or empty string
                    imageSrc = '';
                }
            }
            
            if (imageSrc) {
                facilityImage = `<img src="${imageSrc}" class="facility-image" style="width: 100%; height: 100%; object-fit: contain;">`;
            } else {
                console.error("No valid image source found for facility preview");
            }
        } else {
            console.warn("No facility image data available for preview");
        }
        
        // Check if facility body is empty or just a placeholder
        if (facilityBody && facilityBody !== '<p><br></p>') {
            divElement.innerHTML = `
                <div class="content-container-con" id="facilityContentConContainer-preview">
                    <div class="content-main" id="facilityContentMain-preview">
                        <div class="media-container" style="margin-bottom: 5px; height: 70%; max-height: 70%;">
                            ${facilityImage}
                        </div>
                        <div class="message-wrapper" id="${type}MessageWrapper-preview">
                            <div class="main-message" id="${type}Message-preview" style="word-break: break-word;">
                                ${facilityBody}
                            </div>
                        </div>
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                <p class="author"><small>${formattedDateTime}</small></p>
                            </div>
                            <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                                <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // No facility body, only show image
            divElement.innerHTML = `
                <div class="content-container-con" id="facilityContentConContainer-preview">
                    <div class="content-main" id="facilityContentMain-preview">
                        <div class="media-container" style="margin-bottom: 5px; height: 100%; max-height: 100%;">
                            ${facilityImage}
                        </div>
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                <p class="author"><small>${formattedDateTime}</small></p>
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
        // Generic handler for new or custom content types
        // Find relevant content in the data object
        let contentBody = '';

        // First check if we have pre-formatted HTML content
        let hasPreFormattedHtml = false;
        for (const key in data) {
            if (typeof data[key] === 'string' && 
                (data[key].includes('<div class=') || data[key].includes('<div class=')) && 
                !key.includes('media_path')) {
                contentBody = data[key];
                hasPreFormattedHtml = true;
                break;
            }
        }

        // If we found pre-formatted HTML content, use it directly
        if (hasPreFormattedHtml) {
            divElement.innerHTML = `
                <div class="content-container-con" id="${type}ContentConContainer-preview">
                    <div class="content-main" id="${type}ContentMain-preview">
                        <div class="message-wrapper" id="${type}MessageWrapper-preview">
                            <div class="main-message" id="${type}Message-preview" style="word-break: break-word;">
                                ${contentBody}
                            </div>
                        </div>
                        ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px; display: block;">${mediaContent}</div>` : ''}
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                <p class="author"><small>${formattedDateTime}</small></p>
                            </div>
                            <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                                <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Look for content in the data object
            // First check for a field named after the type
            if (data[`${type}_body`]) {
                contentBody = data[`${type}_body`];
            } else if (data[`${type}_text`]) {
                contentBody = data[`${type}_text`];
            } else {
                // Look for any field containing HTML that might be the content
                for (const key in data) {
                    if (typeof data[key] === 'string' && 
                        data[key].includes('<') && 
                        data[key].includes('>') && 
                        key !== 'media_path' && 
                        !key.includes('datetime') &&
                        !key.includes('time') &&
                        !key.includes('id') &&
                        !key.includes('status')) {
                        contentBody = data[key];
                        break;
                    }
                }
            }
        }
        
        // Handle media if present
        if (data.media_path) {
            if (typeof data.media_path === 'string' && data.media_path.startsWith('data:image/')) {
                mediaContent = `<div class="media-container" style="margin-bottom: 5px">
                    <img src="${data.media_path}" style="max-width: 100%; max-height: 100%; object-fit: contain; user-select: none; pointer-events: none">
                </div>`;
            } else if (typeof data.media_path === 'string' && data.media_path.startsWith('data:video/')) {
                mediaContent = `<div class="media-container" style="margin-bottom: 5px">
                    <video preload="metadata" muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain; user-select: none; pointer-events: none">
                        <source src="${data.media_path}">
                        Your browser does not support the video tag.
                    </video>
                </div>`;
            } else {
                // Check file extension for regular file paths
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
                
                if (isImage) {
                    mediaContent = `<div class="media-container" style="margin-bottom: 5px">
                        <img src="../servers/${type}_media/${data.media_path}" style="max-width: 100%; max-height: 100%; object-fit: contain; user-select: none; pointer-events: none">
                    </div>`;
                } else if (isVideo) {
                    mediaContent = `<div class="media-container" style="margin-bottom: 5px">
                        <video preload="metadata" muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain; user-select: none; pointer-events: none">
                            <source src="../servers/${type}_media/${data.media_path}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>`;
                }
            }
        }

        const hasOnlyMedia = mediaContent && (!contentBody || contentBody === '<p><br></p>' || contentBody === '');

        if (hasOnlyMedia) {
            // Create media content with proper container
            let mediaHtml = '';
            if (typeof data.media_path === 'string' && data.media_path.startsWith('data:image/')) {
                mediaHtml = `<img src="${data.media_path}" style="max-width: 100%; max-height: 100%; object-fit: contain; user-select: none; pointer-events: none">`;
            } else if (typeof data.media_path === 'string' && data.media_path.startsWith('data:video/')) {
                mediaHtml = `<video preload="metadata" muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain; user-select: none; pointer-events: none">
                    <source src="${data.media_path}">
                    Your browser does not support the video tag.
                </video>`;
            } else {
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
                
                if (isImage) {
                    mediaHtml = `<img src="../servers/${type}_media/${data.media_path}" style="max-width: 100%; max-height: 100%; object-fit: contain; user-select: none; pointer-events: none">`;
                } else if (isVideo) {
                    mediaHtml = `<video preload="metadata" muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain; user-select: none; pointer-events: none">
                        <source src="../servers/${type}_media/${data.media_path}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>`;
                }
            }

            divElement.innerHTML = `
                <div class="content-container-con" id="${type}ContentConContainer-preview">
                    <div class="content-main" id="${type}ContentMain-preview">
                        <div class="media-container" style="height: 100%; max-height: 100%; display: flex; align-items: center; justify-content: center;">
                            ${mediaHtml}
                        </div>
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                <p class="author"><small>${formattedDateTime}</small></p>
                            </div>
                            <div style="background: #9393933b; padding: 5px; border-radius: 10px;">
                                <p class="display-time" style="text-align: right"><i class="fa-solid fa-clock" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Create a generic layout with the content and media (if any)
            divElement.innerHTML = `
                <div class="content-container-con" id="${type}ContentConContainer-preview">
                    <div class="content-main" id="${type}ContentMain-preview">
                        ${mediaContent}
                        <div class="message-wrapper" id="${type}MessageWrapper-preview">
                            <div class="main-message" id="${type}Message-preview" style="word-break: break-word;">
                                ${contentBody}
                            </div>
                        </div>
                    </div>
                    <div class="content-details">
                        <div style="display: flex; flex-direction: row; margin: 0; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                <p class="author"><small>${formattedDateTime}</small></p>
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
    
    divElement.style.cssText = `
        width: auto;
        margin-bottom: 5px;
        height: 100%;
        border-radius: 5px;
        display: block; /* visible */
    `;
    
    // First clear the container
    container.innerHTML = '';
    
    // Then append the new element
    container.appendChild(divElement);
        
    // Initialize text fitting
    setTimeout(() => {
        try {
            applyTextFitting(type, 'preview');
        } catch (e) {
            console.error("Error applying text fitting:", e);
        }
    }, 100);
}

// New function specifically for preview text fitting
function applyTextFitting(type, id) {    
    const containerDiv = document.getElementById(`${type}ContentMain-${id}`);
    if (!containerDiv) {
        console.error(`Container element not found for ${type}ContentMain-${id}`);
        return;
    }
    
    // Find all message elements within the container
    const messageElements = containerDiv.querySelectorAll('.main-message');
    if (!messageElements.length) {
        console.error(`No message elements found for ${type} with id ${id}`);
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

// Function to get the containers based on type
const getContainerElements = (type) => {
    const carouselContainer = document.getElementById(`${type}CarouselContainer`);
    const containerName = document.getElementById(`${type}ContainerName`);

    // Check if the containers are found
    if (!carouselContainer) {
        console.error(`Carousel container for ${type} not found from getContainerElements in fetch_tv_content.js.`);
    }

    if (!containerName) {
        console.error(`Container name for ${type} not found from getContainerElements in fetch_tv_content.js.`);
    }

    return {
        carouselContainer,
        containerName
    };
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

// Function to create org chart
const createOrgChart = (orgchartId, chartData) => {
    console.log("Creating org chart with data:", chartData);
    const containerId = `orgChartContainer-${orgchartId}`;
    const containerParentId = `orgChartContainerParent-${orgchartId}`;

    const container = document.getElementById(containerId);
    const containerParent = document.getElementById(containerParentId);

    if (!container || !containerParent) {
        console.error(`Container not found for orgchart ${orgchartId}`);
        return;
    }

    // Clear any existing chart
    container.innerHTML = '';

    // Create chart configuration
    const chartConfig = {
        chart: {
            container: `#${containerId}`,
            nodeAlign: "BOTTOM",
            levelSeparation: 50,
            siblingSeparation: 50,
            subTeeSeparation: 50,
            connectors: {
                type: 'step',
                style: {
                    'stroke-width': '4px'
                }
            },
            node: {
                HTMLclass: 'node'
            },
            callback: {
                onTreeLoaded: function() {
                    // After tree is loaded, apply scaling
                    const treant = document.querySelector(`#${containerId}`).closest('.Treant');
                    const svg = treant ? treant.querySelector('svg') : null;

                    if (!treant || !svg) {
                        console.error(`Treant or SVG not found for orgchart ${orgchartId}`);
                        return;
                    }

                    // Get the natural dimensions of the chart
                    const svgWidth = svg.width.baseVal.value;
                    const svgHeight = svg.height.baseVal.value;
                    
                    // Get the container dimensions
                    const containerWidth = containerParent.clientWidth;
                    const containerHeight = containerParent.clientHeight;

                    // Calculate scale to fit
                    const scaleX = containerWidth / svgWidth;
                    const scaleY = containerHeight / svgHeight;
                    const scale = Math.min(scaleX, scaleY, 1) * 0.9; // 0.9 to leave some margin

                    // Apply scaling
                    treant.style.transform = `scale(${scale})`;
                    treant.style.transformOrigin = 'center center';
                    treant.style.width = `${svgWidth}px`;
                    treant.style.height = `${svgHeight}px`;
                    treant.style.position = 'absolute';
                    treant.style.left = '50%';
                    treant.style.top = '50%';
                    treant.style.marginLeft = `-${svgWidth / 2}px`;
                    treant.style.marginTop = `-${svgHeight / 2}px`;

                    // Store the chart data for potential reinitialization
                    containerParent.chartData = chartData;
                }
            }
        },
        nodeStructure: createNodeStructure(chartData)
    };

    try {
        // Create the Treant instance
        new Treant(chartConfig);
        console.log("Org chart created successfully");

    } catch (error) {
        console.error("Error creating org chart:", error);
        container.innerHTML = '<p style="text-align: center; color: #264B2B; font-size: 1.2em; padding: 20px;">Error loading organizational chart.</p>';
    }
};

// Helper function to create node structure with dynamic sizing
function createNodeStructure(data) {
    console.log("Creating node structure with data:", data);
    
    if (!data || !data.members || !Array.isArray(data.members)) {
        console.error("Invalid data format:", data);
        return null;
    }

    const members = data.members;
    const nodes = {};
    
    // First pass: create all nodes
    members.forEach(member => {
        nodes[member.id] = {
            text: {
                name: member.name,
                title: member.title
            },
            HTMLclass: 'custom-node',
            innerHTML: `
                    <div class="node-content">
                    ${member.picture ? 
                        // Check if the picture is a file path or base64 data
                        `<img src="${member.picture.startsWith('data:image') ? member.picture : '../' + member.picture}" class="node-image" alt="${member.name}">` : 
                        `<img src="../images/profile_picture.png" class="node-image" alt="Default Profile">`
                    }
                    <div class="node-details">
                        <div class="node-name">${member.name}</div>
                        <div class="node-title">${member.title}</div>
                    </div>
                </div>
            `,
            children: []
        };
    });

    // Second pass: build hierarchy
    const rootNodes = [];
    members.forEach(member => {
        const node = nodes[member.id];
        if (!node) return;

        if (member.parent_id === null || member.parent_id === 'null' || member.parent_id === '') {
            rootNodes.push(node);
        } else {
            const parentNode = nodes[member.parent_id];
            if (parentNode) {
                parentNode.children.push(node);
            } else {
                rootNodes.push(node);
            }
        }
    });

    if (rootNodes.length === 0) {
        console.error("No root nodes found in data");
        return null;
    }

    // If there's only one root node, return it directly
    if (rootNodes.length === 1) {
        return rootNodes[0];
    }

    // If there are multiple root nodes, create a hidden root node
    return {
        text: { name: "Department" },
        HTMLclass: "hidden-node",
        children: rootNodes
    };
}

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

const fetchSmartTVDetails = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tvId = urlParams.get('tvId');

    if (tvId) {
        fetch(`../database/fetch_smart_tvs.php?tvId=${tvId}`)
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

    // Assemble the container
    containerDiv.appendChild(titleDiv);
    containerDiv.appendChild(carouselDiv);

    return containerDiv;
}

Ws.addEventListener('message', function (event) {
    const data = JSON.parse(event.data);

    if (data.action === 'edit_smart_tv') {
        location.reload();
    } else if (data.action === 'delete_feature') {
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

document.addEventListener('DOMContentLoaded', () => {
    fetchSmartTVDetails();
});

// Call this function after creating preview content
document.addEventListener('previewUpdate', function(e) {
    // Process the event first
    if (e.detail && e.detail.action === 'updatePreview') {
        updatePreviewContent(e.detail);
    }
});