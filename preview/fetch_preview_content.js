function isIframe() {
    return window.self !== window.top;
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
    
    // Add type-specific content
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
                         style="max-width: 100%; max-height: 100%; object-fit: contain; display: block;" 
                         alt="Preview image">
                `;
            } else if (isVideo) {
                mediaContent = `
                    <video preload="metadata" muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain; display: block;">
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
                             style="max-width: 100%; max-height: 100%; object-fit: contain; display: block;"
                             alt="Content image">
                    `;
                } else if (isVideo) {
                    mediaContent = `
                        <video preload="metadata" muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain; display: block;">
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
                             style="max-width: 100%; max-height: 100%; object-fit: contain; display: block;"
                             alt="Content image">
                    `;
                } else if (isVideo) {
                    mediaContent = `
                        <video preload="metadata" muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain; display: block;">
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
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedDateTime}</small></p>
                        </div>
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
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
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedDateTime}</small></p>
                        </div>
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
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
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedDateTime}</small></p>
                        </div>
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
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
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div>
                            <p class="author"><small>Posted on ${formattedDateTime}</small></p>
                        </div>
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
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
                        <div style="display: flex; flex-direction: row; margin: 0">
                            <div>
                                <p class="author"><small>Posted on ${formattedDateTime}</small></p>
                            </div>
                            <div style="margin-left: auto; margin-top: auto">
                                <p class="display-time" style="text-align: right">
                                    <i class="fa fa-hourglass-half" aria-hidden="true"></i> 
                                    <span class="time-left">${data.display_time}s</span>
                                </p>
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
                        <div style="display: flex; flex-direction: row; margin: 0">
                            <div>
                                <p class="author"><small>Posted on ${formattedDateTime}</small></p>
                            </div>
                            <div style="margin-left: auto; margin-top: auto">
                                <p class="display-time" style="text-align: right">
                                    <i class="fa fa-hourglass-half" aria-hidden="true"></i> 
                                    <span class="time-left">${data.display_time}s</span>
                                </p>
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
                        <div style="display: flex; flex-direction: row; margin: 0">
                            <div>
                                <p class="author"><small>Posted on ${formattedDateTime}</small></p>
                            </div>
                            <div style="margin-left: auto; margin-top: auto">
                                <p class="display-time" style="text-align: right">
                                    <i class="fa fa-hourglass-half" aria-hidden="true"></i> 
                                    <span class="time-left">${data.display_time}s</span>
                                </p>
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
                        <div style="display: flex; flex-direction: row; margin: 0">
                            <div>
                                <p class="author"><small>Posted on ${formattedDateTime}</small></p>
                            </div>
                            <div style="margin-left: auto; margin-top: auto">
                                <p class="display-time" style="text-align: right">
                                    <i class="fa fa-hourglass-half" aria-hidden="true"></i> 
                                    <span class="time-left">${data.display_time}s</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    } if (type === 'orgchart') {
        divElement.innerHTML = `
            <div class="content-container-con">
                <div id="orgChartParentContainer-preview" class="content-main">
                    <div id="orgChartContainerParent-preview" class="orgchart-container">
                        <div id="orgChartContainer-preview" class="orgchart-container-parent">
                            <div class="orgchart-content"></div>
                        </div>
                    </div>
                </div>
                <div class="content-details">
                    <div style="display: flex; flex-direction: row; margin: 0">
                        <div style="margin-left: auto; margin-top: auto">
                            <p class="display-time" style="text-align: right">
                                <i class="fa fa-hourglass-half" aria-hidden="true"></i> 
                                <span class="time-left">${data.display_time}s</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // First clear the container
        container.innerHTML = '';
        
        // Then append the new element
        container.appendChild(divElement);

        // Create the org chart in the preview
        if (data.chartData && data.chartData.members) {
            setTimeout(() => {
                try {
                    createOrgChart('preview', data.chartData);
                    console.log("Created org chart preview");
                } catch (e) {
                    console.error("Error creating org chart preview:", e);
                }
            }, 100);
        } else {
            console.error("No chart data available for preview");
        }
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
                        <div style="display: flex; flex-direction: row; margin: 0">
                            <div>
                                <p class="author"><small>Posted on ${formattedDateTime}</small></p>
                            </div>
                            <div style="margin-left: auto; margin-top: auto">
                                <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
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
                        <div style="display: flex; flex-direction: row; margin: 0">
                            <div>
                                <p class="author"><small>Posted on ${formattedDateTime}</small></p>
                            </div>
                            <div style="margin-left: auto; margin-top: auto">
                                <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
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
                        <div style="display: flex; flex-direction: row; margin: 0">
                            <div>
                                <p class="author"><small>Posted on ${formattedDateTime}</small></p>
                            </div>
                            <div style="margin-left: auto; margin-top: auto">
                                <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
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
                    <img src="${data.media_path}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                </div>`;
            } else if (typeof data.media_path === 'string' && data.media_path.startsWith('data:video/')) {
                mediaContent = `<div class="media-container" style="margin-bottom: 5px">
                    <video preload="metadata" muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain;">
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
                        <img src="../servers/${type}_media/${data.media_path}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    </div>`;
                } else if (isVideo) {
                    mediaContent = `<div class="media-container" style="margin-bottom: 5px">
                        <video preload="metadata" muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain;">
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
                mediaHtml = `<img src="${data.media_path}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
            } else if (typeof data.media_path === 'string' && data.media_path.startsWith('data:video/')) {
                mediaHtml = `<video preload="metadata" muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    <source src="${data.media_path}">
                    Your browser does not support the video tag.
                </video>`;
            } else {
                const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
                const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
                
                if (isImage) {
                    mediaHtml = `<img src="../servers/${type}_media/${data.media_path}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
                } else if (isVideo) {
                    mediaHtml = `<video preload="metadata" muted autoplay style="max-width: 100%; max-height: 100%; object-fit: contain;">
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
                        <div style="display: flex; flex-direction: row; margin: 0">
                            <div>
                                <p class="author"><small>Posted on ${formattedDateTime}</small></p>
                            </div>
                            <div style="margin-left: auto; margin-top: auto">
                                <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
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
                        <div style="display: flex; flex-direction: row; margin: 0">
                            <div>
                                <p class="author"><small>Posted on ${formattedDateTime}</small></p>
                            </div>
                            <div style="margin-left: auto; margin-top: auto">
                                <p class="display-time" style="text-align: right"><i class="fa fa-hourglass-half" aria-hidden="true"></i> <span class="time-left">${data.display_time}s</span></p>
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

    if (!container) {
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
            }
        },
        nodeStructure: createNodeStructure(chartData)
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
        }, 100); // Small delay for performance
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
    fetchSmartTVDetails();
});

// Call this function after creating preview content
document.addEventListener('previewUpdate', function(e) {
    // Process the event first
    if (e.detail && e.detail.action === 'updatePreview') {
        updatePreviewContent(e.detail);
    }
});