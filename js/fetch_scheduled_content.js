function formatDateTime(dateTimeString) {
    if (!dateTimeString) {
        return 'No date specified';
    }

    try {
        const dateTime = new Date(dateTimeString);
        
        // Check if date is invalid
        if (isNaN(dateTime.getTime())) {
            return 'Invalid date';
        }

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
    } catch (error) {
        console.error('Error formatting date:', error, 'for value:', dateTimeString);
        return 'Invalid date format';
    }
}

// Function to search scheduled posts
function searchScheduledPost() {
    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');
    const keyword = searchInput.value.toLowerCase();
    const contentType = document.getElementById('contentType').value;
    
    // Show/hide clear button
    clearButton.style.display = keyword ? 'block' : 'none';

    const scheduledItems = document.querySelectorAll('#scheduledPostsContainer > div');
    let hasVisibleItems = false;

    // Remove any existing "no results" message first
    const existingNoResultsMsg = document.getElementById('noResultsMessage');
    if (existingNoResultsMsg) {
        existingNoResultsMsg.remove();
    }

    scheduledItems.forEach(item => {
        // Skip the noResultsMessage div if it exists
        if (item.id === 'noResultsMessage') return;

        const textContent = item.textContent.toLowerCase();
        const itemType = item.querySelector('.content-type-label')?.textContent.toLowerCase();
        
        if (!itemType) return; // Skip if not a content item

        const matchesKeyword = keyword === '' || textContent.includes(keyword);
        const matchesType = contentType === 'all' || itemType.includes(contentType.toLowerCase());
        
        item.style.display = (matchesKeyword && matchesType) ? 'block' : 'none';
        if (matchesKeyword && matchesType) hasVisibleItems = true;
    });

    // Show "no results" message if needed
    if (!hasVisibleItems && scheduledItems.length > 0) {
        const msg = document.createElement('div');
        msg.id = 'noResultsMessage';
        msg.style = `
            text-align: center;
            font-size: 1.2em;
            color: #666;
            margin-top: 20px;
        `;
        msg.textContent = 'No scheduled items match your search';
        document.getElementById('scheduledPostsContainer').appendChild(msg);
    }
}

// Function to clear search
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');
    searchInput.value = '';
    clearButton.style.display = 'none';
    searchScheduledPost();
}

// Function to populate content type dropdown
function populateContentTypeDropdown() {
    const select = document.getElementById('contentType');
    const contentTypes = ['announcement', 'event', 'news', 'promaterial'];
    
    // Clear existing options except "All"
    while (select.options.length > 1) {
        select.remove(1);
    }

    // Fetch counts for all content types
    Promise.all(contentTypes.map(type =>
        fetch(`database/fetch_content.php?type=${type}&scheduled=true`)
            .then(response => response.json())
    ))
    .then(results => {
        let totalCount = 0;
        const typeCounts = {};

        // Calculate counts for each type
        results.forEach((data, index) => {
            const type = contentTypes[index];
            const count = data.filter(item => 
                item.schedule_datetime && 
                item.isArchived === 0 && 
                item.isTrashed === 0 && 
                (item.status === 'Approved') &&
                hasPermissionToView({ ...item, contentType: type })
            ).length;
            
            typeCounts[type] = count;
            totalCount += count;
        });

        // Update "All" option with total count
        select.options[0].textContent = `All (${totalCount})`;

        // Add options for each content type with counts
        contentTypes.forEach(type => {
            const option = document.createElement('option');
            option.value = type;
            option.textContent = `${formatContentType(type)} (${typeCounts[type]})`;
            select.appendChild(option);
        });
    })
    .catch(error => {
        console.error('Error fetching content type counts:', error);
    });

    // Add change event listener
    select.addEventListener('change', searchScheduledPost);
}

const createDeleteButton = (text, iconClass, onClick) => {
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

const createPostNowButton = (text, iconClass, onClick) => {
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

// const createEditButton = (text, iconClass, onClick) => {
//     const button = document.createElement('button');
//     button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
//     button.className = 'green-button';
//     button.onclick = onClick;
//     return button;
// };

// Function to create content div
const createContentDiv = (data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, formattedScheduleDateTime, type, tvDisplay) => {
    let contentHtml = `
        <div class="content-container-con">
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
        case 'peo':
        case 'so':
            contentHtml += `<pre class="ann-body" style="word-break: break-word"><b>${data[`${type}_title`]}</b></pre>
                            <pre class="ann-body" style="word-break: break-word">${data[`${type}_description`]}</pre>
                            <pre class="ann-body" style="word-break: break-word">${data[`${type}_subdescription`]}</pre>`;
            break;
        default:
            // For new features, dynamically add all fields
            for (let key in data) {
                if (key !== `${type}_id` && key !== 'created_datetime' && key !== 'expiration_datetime' 
                    && key !== 'display_time' && key !== 'status' && key !== 'isArchived' 
                    && key !== 'tv_id' && key !== `${type}_author_id` && key !== 'type' && key !== 'department' 
                    && key !== 'user_type' && key !== 'category' && key !== 'evaluated_by' && key !== 'evaluated_message' && key !== 'author_name') {
                        contentHtml += `<p>${data[key]}</p>`;
                }
            }
    }

    contentHtml += `
            <div class="line-separator"></div>
            <p class="ann-author" style="color: #6E6E6E; margin-top: 10px"><small>Posted by ${data.author_name || 'Unknown'} on ${formattedCreatedDateTime}</small></p>
            <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDateTime}</small></p>
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

    // if (data.status === 'Pending' || data.status === 'Draft' || data.isArchived === 0 || !data[`${type}_id`]) return;
    if (!data.schedule_datetime || data.isArchived === 1 || data.isTrashed === 1) return;

    const tvInfo = await fetchTVInfo(type, data[`tv_id`]);
    const scheduledPostsContainer = document.getElementById('scheduledPostsContainer');

    const existingDiv = document.querySelector(`[data-${type}-id="${data[`${type}_id`]}"]`);
    const formattedCreatedDateTime = formatDateTime(data.created_datetime);
    const formattedExpirationDateTime = formatDateTime(data.expiration_datetime);
    const formattedScheduleDateTime = formatDateTime(data.schedule_datetime);

    let mediaContent = '';
    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">` :
            isVideo ? `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/${type}_media/${data.media_path}" type="video/mp4"></video>` : '';
    }

    const contentDiv = createContentDiv(data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, formattedScheduleDateTime, type, tvInfo.tv_display);
    
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
        typeLabel.innerHTML = `<i class="fa ${getTypeIcon(type)}" aria-hidden="true"></i> ${formatContentType(type)}`;
        typeLabel.style = `
            display: inline-block;
            padding: 5px 10px;
            border: 1px solid black;
            color: black;
            border-radius: 15px;
            font-size: 0.8em;
            margin-bottom: 10px;
        `;

        // Add scheduled datetime information
        const scheduleInfo = document.createElement('div');
        scheduleInfo.className = 'schedule-info';
        scheduleInfo.innerHTML = `
            <div style="color: black; margin-bottom: 10px;">
                <p style="font-weight: bold"><i class="fa fa-clock-o" aria-hidden="true"></i> 
                Scheduled for: ${formatDateTime(data.schedule_datetime)}</p>
            </div>
        `;

        // Create content container
        const contentContainer = document.createElement('div');
        contentContainer.className = 'content-container-con';
        contentContainer.innerHTML = contentDiv;
        contentContainer.style = `
            border-radius: 15px;
            padding: 15px;
            color: black;
            height: auto;
            text-align: left;
        `;

        // Create button container
        const buttonContainer = document.createElement('div');
        buttonContainer.style = `
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        `;

        const deleteButton = createDeleteButton('Delete', 'fa fa-trash', () => showDeleteModal(data[`${type}_id`], type, data[`${type}_author_id`], full_name, data.author_name, data.author_user_type, data.author_department, data.tv_id));
        const postNowButton = createPostNowButton('Post Now', 'fa fa-share', () => showPostNowModal(data[`${type}_id`], type, data[`${type}_author_id`], full_name, data.author_name, data.author_user_type, data.author_department, data.tv_id));

        if (userType === 'Super Admin' || (userType === 'Admin' && data[`user_type`] !== 'Super Admin') || data[`${type}_author_id`] === parseInt(user_id)) {
            buttonContainer.appendChild(deleteButton);
            buttonContainer.appendChild(postNowButton);
        }

        // Set main container styles
        containerDiv.style = `
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 15px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        `;

        // Append elements in the correct order
        containerDiv.appendChild(typeLabel);
        containerDiv.appendChild(scheduleInfo);
        containerDiv.appendChild(contentContainer);
        containerDiv.appendChild(buttonContainer);
        scheduledPostsContainer.appendChild(containerDiv);
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

// Helper function to get icon for content type
const getTypeIcon = (type) => {
    const icons = {
        announcement: 'fa-bullhorn',
        event: 'fa-calendar-check-o',
        news: 'fa-newspaper-o',
        promaterial: 'fa-object-group',
        peo: 'fa-map',
        so: 'fa-graduation-cap'
    };
    return icons[type] || 'fa-file';
};

// Helper function to format content type for display
const formatContentType = (type) => {
    const formats = {
        announcement: 'Announcement',
        event: 'Event',
        news: 'News',
        promaterial: 'Promotional Material',
        peo: 'PEO',
        so: 'SO'
    };
    return formats[type] || type;
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

// Function to post now an item
const postNowItem = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    const evaluatorMessageElement = document.getElementById('evaluator_message');
    let evaluatorMessage = null;

    if (evaluatorMessageElement) {
        evaluatorMessage = evaluatorMessageElement.value;
        if (!evaluatorMessage) {
            alert('Evaluator message is required.');
            return;
        }
    }

    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const modal = document.getElementById(`confirmPostNow${capitalizedType}Modal`);
    
    let action = 'post_now_content';
    let data = {
        action: action,
        type: contentType,
        [`${contentType}_id`]: contentId, // Dynamically set the ID based on the type
        user_id: user_id,
        author_id: authorId,
        author_name: authorName,
        author_user_type: authorUserType,
        author_department: authorDepartment,
        evaluator_name: loggedInFullName,
        evaluator_message: evaluatorMessage,
        tv_id: tvId,
        schedule_datetime: null // Override the schedule datetime to null
    };

    modal.style.display = 'none';
    Ws.send(JSON.stringify(data));
};

// Function to delete an item
const deleteItem = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    const evaluatorMessageDeleteForever = document.getElementById('evaluator_message_delete');
    let evaluatorMessage = null;

    if (evaluatorMessageDeleteForever) {
        evaluatorMessage = evaluatorMessageDeleteForever.value;
        if (!evaluatorMessage) {
            alert('Evaluator message is required.');
            return;
        }
    }

    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const confirmDeleteModal = document.getElementById(`confirmDelete${capitalizedType}Modal`);
    const data = {
        action: 'delete_content',
        type: contentType,
        [`${contentType}_id`]: contentId, // Dynamically set the ID based on the type
        user_id: user_id,
        author_id: authorId,
        author_name: authorName,
        author_user_type: authorUserType,
        author_department: authorDepartment,
        evaluator_name: loggedInFullName,
        evaluator_message: evaluatorMessage,
        tv_id: tvId
    };
    confirmDeleteModal.style.display = 'none';
    Ws.send(JSON.stringify(data)); // Send the data to the WebSocket server
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
        messageDiv.textContent = `No scheduled promotional materials to be displayed`;
    } else if (type === 'peo') {
        messageDiv.textContent = `No scheduled Program Education Objective (PEO) to be displayed`;
    } else if (type === 'so') {
        messageDiv.textContent = `No scheduled Student Outcome (SO) to be displayed`;
    } else {
        messageDiv.textContent = `No scheduled ${type} to be displayed`;
    }
    
    const carouselContainer = document.getElementById(`${type}CarouselContainer`);
    if (carouselContainer) {
        carouselContainer.appendChild(messageDiv);
    } else {
        console.error(`Carousel container for ${type} not found.`);
    }
};

// Array of default content types
// const defaultContentTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];

// Function to create modals dynamically
const createModals = (contentTypes) => {
    const modalContainer = document.createElement('div');
    modalContainer.id = 'dynamicModals';

    contentTypes.forEach(type => {
        const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
        
        // Post Now Modal
        const postNowModal = document.createElement('div');
        postNowModal.id = `confirmPostNow${capitalizedType}Modal`;
        postNowModal.className = 'modal';
        postNowModal.innerHTML = `<div id="postNow${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(postNowModal);

        // Delete Modal
        const deleteModal = document.createElement('div');
        deleteModal.id = `confirmDelete${capitalizedType}Modal`;
        deleteModal.className = 'modal';
        deleteModal.innerHTML = `<div id="delete${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(deleteModal);
    });

    document.body.appendChild(modalContainer);
};

// Function to show postNow modal
const showPostNowModal = async (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    // Fetch TV info first
    const tvInfo = await fetchTVInfo(contentType, tvId);

    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const modal = document.getElementById(`confirmPostNow${capitalizedType}Modal`);
    const modalContent = document.getElementById(`postNow${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    modalContent.style = `
        padding: 15px;
    `;

    const evaluatorMessageHtml = user_id != authorId ? `
        <div class="floating-label-container">
            <textarea name="evaluator_message_post_now" id="evaluator_message_post_now" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
            <label for="evaluator_message_post_now" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User (required)</label>
        </div>
        <br>
    ` : '';
    
    modalContent.innerHTML = `
        <span class="close" onclick="document.getElementById('confirmPostNow${capitalizedType}Modal').style.display='none'"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="font-size: 50px"><i class="fa fa-share" aria-hidden="true"></i></h1>
        <p id="deleteMessage" style="text-align: center">Proceed to post this ${contentType} to TV: ${tvInfo.tv_display} even if it has not yet reached its scheduled date and time?</p>
        <p id="deleteMessage2" style="font-size: 13px; color: #316038;">Posting now will override the scheduled date and time.</p>
        <br>
        ${evaluatorMessageHtml}
        <div style="text-align: right;">
            <button type="button" class="grey-button" onclick="document.getElementById('confirmPostNow${capitalizedType}Modal').style.display='none'">Cancel</button>
            <button type="button" class="green-button" onclick="postNowItem(${contentId}, '${contentType}', ${authorId}, '${loggedInFullName}', '${authorName}', '${authorUserType}', '${authorDepartment}', ${tvId})">Yes, Post Now</button>
        </div>
    `;
    
    modal.style.display = 'flex';
};

// Function to show delete modal
const showDeleteModal = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const modal = document.getElementById(`confirmDelete${capitalizedType}Modal`);
    const modalContent = document.getElementById(`delete${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    modalContent.style = `
        padding: 15px;
    `;

    const evaluatorMessageHtml = user_id != authorId ? `
        <div class="floating-label-container">
            <textarea name="evaluator_message_delete" id="evaluator_message_delete" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
            <label for="evaluator_message_delete" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User (required)</label>
        </div>
        <br>
    ` : '';
    
    modalContent.innerHTML = `
        <span class="close" onclick="document.getElementById('confirmDelete${capitalizedType}Modal').style.display='none'"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
        <p id="deleteMessage" style="text-align: center">Proceed to delete forever?</p>
        <p id="deleteMessage2" style="font-size: 13px; color: #6E6E6E;">Permanently deleted items cannot be recovered.</p>
        <br>
        ${evaluatorMessageHtml}
        <div style="text-align: right;">
            <button type="button" class="grey-button" onclick="document.getElementById('confirmDelete${capitalizedType}Modal').style.display='none'">Cancel</button>
            <button type="button" class="red-button" onclick="deleteItem(${contentId}, '${contentType}', ${authorId}, '${loggedInFullName}', '${authorName}', '${authorUserType}', '${authorDepartment}', ${tvId})">Yes, delete forever</button>
        </div>
    `;
    
    modal.style.display = 'flex';
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
    const contentTypes = ['announcement', 'event', 'news', 'promaterial'];

    try {
        const scheduledPostsContainer = document.getElementById('scheduledPostsContainer');
        if (!scheduledPostsContainer) {
            console.error('Scheduled Posts container not found');
            return;
        }

        // Clear existing content
        scheduledPostsContainer.innerHTML = '';

        // Fetch all content types
        const allContent = [];
        for (const type of contentTypes) {
            const response = await fetch(`database/fetch_content.php?type=${type}&scheduled=true`);
            const data = await response.json();
            const filteredData = data.filter(item => 
                item.schedule_datetime &&
                item.isArchived === 0 && 
                item.isTrashed === 0 && 
                (item.status === 'Approved') &&
                hasPermissionToView({ ...item, contentType: type })
            );
            
            // Add content type to each item
            filteredData.forEach(item => {
                item.contentType = type;
                allContent.push(item);
            });
        }

        // Sort all content by scheduled datetime
        allContent.sort((a, b) => 
            new Date(b.schedule_datetime) - new Date(a.schedule_datetime)
        );

        if (allContent.length === 0) {
            scheduledPostsContainer.innerHTML = `
                <div style="text-align: center; font-size: 1.2em; color: #666; margin-top: 5px;">
                    No scheduled items to display
                </div>
            `;
            return;
        }

        // Display all content
        for (const item of allContent) {
            await updateUI(item, item.contentType);
        }
    } catch (error) {
        console.error('Error fetching scheduled content:', error);
    }
};

// Update WebSocket event listener
Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    switch (data.action) {
        case 'delete_content':
        case 'archive_content':
        case 'trash_content':
        case 'approve_content':
        case 'post_content':
        case 'post_now_content':
        case 'update_archive_days':
            // Refresh all content for any change
            console.log('Action received:', data.action);
            populateContentTypeDropdown();
            fetchAndUpdateAll();
            break;

        case 'update':
            // Handle updates to existing items
            fetchAndUpdateAll();
            populateContentTypeDropdown();
            break;
    }
});

// Update the document ready event listener
document.addEventListener('DOMContentLoaded', async () => {
    // Create modals for all content types
    const contentTypes = ['announcement', 'event', 'news', 'promaterial'];
    createModals(contentTypes);

    // Initialize search functionality
    populateContentTypeDropdown();
    
    // Add clear button click handler
    document.getElementById('clearSearch').addEventListener('click', clearSearch);

    // Fetch and update all content
    fetchAndUpdateAll();
});