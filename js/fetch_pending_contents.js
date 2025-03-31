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

const createApproveButton = (text, iconClass, onClick) => {
    const button = document.createElement('button');
    button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
    button.className = 'green-button';
    button.style = `
        margin-top: 10px;
        margin-right: 5px;
    `;
    button.onclick = onClick;
    return button;
};

const createRejectButton = (text, iconClass, onClick) => {
    const button = document.createElement('button');
    button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
    button.className = 'light-green-button';
    button.style = `
        margin-top: 10px;
        margin-right: 5px;
    `;
    button.onclick = onClick;
    return button;
};

// Function to create content div
const createContentDiv = (data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, type, tvDisplay) => {
    let contentHtml = `
        <div class="content-container-con" style="padding: 0">
            ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
    `;

    // Add type-specific content
    switch(type) {
        case 'announcement':
        case 'event':
        case 'news':
            contentHtml += `<pre class="ann-body" style="word-break: break-word">${data[`${type}_body`]}</pre>`;
            break;
        case 'promaterial':
            break;
        default:
            // For dynamic features, display all content fields except metadata
            for (let key in data) {
                if (!key.match(/^(created_datetime|expiration_datetime|display_time|status|isArchived|isTrashed|tv_id|author_id|type|contentType|department|user_type|category|evaluated_by|evaluated_message|author_name|media_path|media_upload_only_zz|author_user_type|author_department|datetime_archived|datetime_trashed)$/) 
                    && !key.endsWith('_id')) {
                    contentHtml += `<pre class="ann-body" style="word-break: break-word">${data[key]}</pre>`;
                }
            }
    }

    contentHtml += `
            <div class="line-separator"></div>
            <p class="expiration-date" style="color: #6E6E6E"><small>Expires on ${formattedExpirationDateTime}</small></p>
            <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Posted on ${formattedCreatedDateTime}</small></p>
            <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs  |  <i class="fa fa-tv" aria-hidden="true" style="margin-left: 5px"></i> ${tvDisplay}</p>
        </div>
    `;

    return contentHtml;
};

// Function to update UI for announcement, event, news, etc.
const updateUI = async (data, type) => {
    if (!hasPermissionToView(data)) {
        return;
    }

    if (data.status === 'Approved' || data.status === 'Rejected' || !data[`${type}_id`]) return;

    const tvInfo = await fetchTVInfo(type, data[`tv_id`]);
    const pendingContentsContainer = document.getElementById('pendingContentsContainer');

    const existingDiv = document.querySelector(`[data-${type}-id="${data[`${type}_id`]}"]`);
    const formattedCreatedDateTime = formatDateTime(data.created_datetime);
    const formattedExpirationDateTime = formatDateTime(data.expiration_datetime);

    let mediaContent = '';
    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}_media/${data.media_path}" style="width: auto; height: auto; max-width: 50%; max-height: 375px; display: block; margin: 0 auto; border-radius: 5px">` :
            isVideo ? `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/${type}_media/${data.media_path}" type="video/mp4"></video>` : '';
    }

    const contentDiv = createContentDiv(data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, type, tvInfo.tv_display);
    
    if (existingDiv) {
        existingDiv.querySelector('.content-container-con').innerHTML = contentDiv;
    } else {
        const containerDiv = document.createElement('div');
        containerDiv.dataset[`${type}Id`] = data[`${type}_id`];
        containerDiv.setAttribute(`data-${type}-id`, data[`${type}_id`]);
        containerDiv.classList.add('content-container-main');

        // Create type label
        const typeLabel = document.createElement('div');
        typeLabel.className = 'content-type-label';
        typeLabel.innerHTML = `${formatContentType(type)}`;
        typeLabel.style = `
            display: none;
        `;
        
        // Create content container
        const contentContainer = document.createElement('div');
        contentContainer.className = 'content-container-con';
        // if author_id is equal to user_id, display "You" instead of own name
        contentContainer.innerHTML = `<p class="pending-content-message" style="margin-bottom: 15px; font-size: 15px; font-weight: bold;"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> ${data.author_name} (${data.author_user_type}) wants to post <b> ${type} </b> on TV: ${tvInfo.tv_display}</p>` + contentDiv;

        contentContainer.style = `
            border-radius: 15px;
            color: black;
            height: auto;
            text-align: left;
            padding: 0;
        `;

        // Create button container
        const buttonContainer = document.createElement('div');
        buttonContainer.style = `
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        `;

        // approveButton onclick="showConfirmApproveContentModal(${notification.content_id}, '${notification.content_type}', '${notification.action_user_name}', '${notification.action_user_department}', ${notification.user_id}, ${notification.content_tv_id}, '${full_name}', ${user_id})"
        const approveButton = createApproveButton('Approve', '', () => 
            showConfirmApproveContentModal(
                data[`${type}_id`], 
                type,
                data.author_name,
                data.author_department,
                data[`${type}_author_id`],
                data.tv_id,
                full_name,
                user_id
            )
        );

        const rejectButton = createRejectButton('Reject', '', () => 
            showConfirmRejectContentModal(
                data[`${type}_id`], 
                type,
                data.author_name,
                data.author_department,
                data[`${type}_author_id`],
                data.tv_id,
                full_name,
                user_id
            )
        );

        if (userType === 'Super Admin' || (userType === 'Admin' && data[`user_type`] !== 'Super Admin')) {
            buttonContainer.appendChild(approveButton);
            buttonContainer.appendChild(rejectButton);
        }

        containerDiv.style = `
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        `;

        // Append elements in the correct order
        containerDiv.appendChild(typeLabel);
        containerDiv.appendChild(contentContainer);
        containerDiv.appendChild(buttonContainer);
        pendingContentsContainer.appendChild(containerDiv);
    }
};

// Permission check function
const hasPermissionToView = (content) => {
    // Super Admin can see everything
    if (userType === 'Super Admin') {
        return true;
    }

    // Admin can see content from their department
    if (userType === 'Admin') {
        return content.author_department === department;
    }

    // Students, Faculty, and Staff can only see their own content
    if (['Student', 'Faculty', 'Staff'].includes(userType)) {
        return parseInt(content[`${content.contentType}_author_id`]) === parseInt(user_id);
    }

    return false;
};

// Helper function to format content type for display
const formatContentType = (type) => {
    // Default formats for built-in types
    const formats = {
        announcement: 'Announcement',
        event: 'Event',
        news: 'News',
        promaterial: 'Promotional Material'
    };

    // If it's a built-in type, use the predefined format
    if (formats[type]) {
        return formats[type];
    }

    // For dynamic features, capitalize each word
    return type
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
};

// Include tv_name
const fetchTVInfo = async (type, tv_id) => {
    try {
        const response = await fetch(`database/fetch_tv_info.php?type=${type}&tvId=${tv_id}`);
        const data = await response.json();
        return {
            tv_display: data.tv_display,
            tv_name: data.tv_name // Add this line
        };
    } catch (error) {
        console.error('Error fetching TV info:', error);
        return { 
            tv_display: 'Error fetching TV info',
            tv_name: 'Unknown TV' // Add this line
        };
    }
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
        messageDiv.textContent = `No pending Promotional Materials to be displayed`;
    } else {
        messageDiv.textContent = `No pending ${type} to be displayed`;
    }
    
    const carouselContainer = document.getElementById(`${type}CarouselContainer`);
    if (carouselContainer) {
        carouselContainer.appendChild(messageDiv);
    } else {
        console.error(`Carousel container for ${type} not found.`);
    }
};

// Function to create modals dynamically
const createModals = async () => {
    try {
        // Get all available features
        const features = await getFeatureInfo('all');
        const contentTypes = ['announcement', 'event', 'news', 'promaterial'];
        
        // Add dynamic features
        if (features && Array.isArray(features)) {
            features.forEach(feature => {
                if (!contentTypes.includes(feature.type)) {
                    contentTypes.push(feature.type);
                }
            });
        }

        const modalContainer = document.createElement('div');
        modalContainer.id = 'dynamicModals';

        // Create modals for all content types
        contentTypes.forEach(type => {
            const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
            
            // Create delete modals for each type
            ['Delete'].forEach(action => {
                const modal = document.createElement('div');
                modal.id = `confirm${action}${capitalizedType}Modal`;
                modal.className = 'modal';
                modal.innerHTML = `<div id="${action.toLowerCase()}${capitalizedType}ModalContent"></div>`;
                modalContainer.appendChild(modal);
            });
        });

        document.body.appendChild(modalContainer);
    } catch (error) {
        console.error('Error creating modals:', error);
    }
};

// Function to fetch feature information
const getFeatureInfo = async (type) => {
    try {
        const response = await fetch(`database/fetch_feature_info.php?type=${type}`);
        const featureInfo = await response.json();
        return featureInfo;
    } catch (error) {
        console.error(`Error fetching feature info for ${type}:`, error);
        return null;
    }
};

// Function to fetch and update content for all types
const fetchAndUpdateAll = async () => {
    try {
        // Get all available features first
        const response = await fetch('database/fetch_feature_info.php?type=all');
        const features = await response.json();
        console.log(features);
        
        // Start with default content types
        const contentTypes = ['announcement', 'event', 'news', 'promaterial'];
        
        // Add dynamic features and store their icons
        if (Array.isArray(features)) {
            features.forEach(feature => {
                const featureType = feature.type;
                if (!contentTypes.includes(featureType)) {
                    contentTypes.push(featureType);
                }
            });
        }

        const pendingContentsContainer = document.getElementById('pendingContentsContainer');
        if (!pendingContentsContainer) {
            console.error('Pending container not found');
            return;
        }

        // Clear existing content
        pendingContentsContainer.innerHTML = '';

        // Fetch all content types
        const allContent = [];
        for (const type of contentTypes) {
            try {
                const response = await fetch(`database/fetch_pending_contents.php?type=${type}&pending=true`);
                const data = await response.json();
                
                if (data.error) {
                    console.error(`Error fetching ${type}:`, data.error);
                    continue;
                }

                const filteredData = data.filter(item => 
                    item.isArchived === 0 && 
                    item.isTrashed === 0 && 
                    item.status === 'Pending' &&
                    hasPermissionToView({ ...item, contentType: type.toLowerCase() })
                );
                
                // Add content type to each item
                filteredData.forEach(item => {
                    item.contentType = type;
                    allContent.push(item);
                });
            } catch (error) {
                console.error(`Error fetching ${type}:`, error);
            }
        }

        // Sort all content by created datetime
        allContent.sort((a, b) => 
            new Date(b.created_datetime) - new Date(a.created_datetime)
        );

        if (allContent.length === 0) {
            pendingContentsContainer.innerHTML = `
                <div style="text-align: center; font-size: 1.2em; color: #666; margin-top: 5px;">
                    No pending items to display
                </div>
            `;
            return;
        }

        // Display all content
        for (const item of allContent) {
            await updateUI(item, item.contentType);
        }
    } catch (error) {
        console.error('Error fetching pending content:', error);
    }
};

// Update WebSocket event listener
Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    switch (data.action) {
        case 'approve_post':
        case 'reject_post':
        case 'delete_content':
        case 'archive_content':
        case 'restore_content':
        case 'restore_content_and_update_expiration':
        case 'trash_content':
        case 'post_content':
        case 'update_archive_days':
            // Refresh all content for any change
            console.log('Action received:', data.action);
            // populateContentTypeDropdown();
            fetchAndUpdateAll();
            break;

        case 'update':
            // Handle updates to existing items
            fetchAndUpdateAll();
            // populateContentTypeDropdown();
            break;
    }
});

// Update the document ready event listener
document.addEventListener('DOMContentLoaded', async () => {
    // Create modals with dynamic features
    await createModals();

    // Fetch and update all content
    fetchAndUpdateAll();
});