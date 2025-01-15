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

// Function to search pending contents
function searchPendingContents() {
    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');
    const keyword = searchInput.value.toLowerCase();
    const contentType = document.getElementById('contentType').value;
    
    // Show/hide clear button
    clearButton.style.display = keyword ? 'block' : 'none';

    const pendingItems = document.querySelectorAll('#pendingContentsContainer > div');
    let hasVisibleItems = false;

    // Remove any existing "no results" message first
    const existingNoResultsMsg = document.getElementById('noResultsMessage');
    if (existingNoResultsMsg) {
        existingNoResultsMsg.remove();
    }

    pendingItems.forEach(item => {
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
    if (!hasVisibleItems && pendingItems.length > 0) {
        const msg = document.createElement('div');
        msg.id = 'noResultsMessage';
        msg.style = `
            text-align: center;
            font-size: 1.2em;
            color: #666;
            margin-top: 20px;
        `;
        msg.textContent = 'No pending items match your search';
        document.getElementById('pendingContentsContainer').appendChild(msg);
    }
}

// Function to clear search
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');
    searchInput.value = '';
    clearButton.style.display = 'none';
    searchPendingContents();
}

// Function to populate content type dropdown
function populateContentTypeDropdown() {
    const select = document.getElementById('contentType');
    const pendingContentsCount = document.getElementById('pendingContentsCount');
    const contentTypes = ['announcement', 'event', 'news', 'promaterial'];

    // Clear existing options except "All"
    while (select.options.length > 1) {
        select.remove(1);
    }

    // Fetch counts for all content types
    Promise.all(contentTypes.map(type =>
        fetch(`database/fetch_pending_contents.php?type=${type}&pending=true`)
            .then(response => response.json())
    ))
    .then(results => {
        let totalCount = 0;
        const typeCounts = {};

        // Calculate counts for each type
        results.forEach((data, index) => {
            const type = contentTypes[index];
            const count = data.filter(item => 
                item.status === 'Pending' &&
                hasPermissionToView({ ...item, contentType: type })
            ).length;
            
            typeCounts[type] = count;
            totalCount += count;
        });

        // Update "All" option with total count
        select.options[0].textContent = `All (${totalCount})`;
        pendingContentsCount.textContent = `(${totalCount})`;

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
    select.addEventListener('change', searchPendingContents);
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

const createDeleteButton = (text, iconClass, onClick) => {
    const button = document.createElement('button');
    button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
    button.className = 'green-button';
    button.style = `margin-top: 10px; margin-right: 5px;`;
    button.onclick = onClick;
    return button;
};

// Function to create content div
const createContentDiv = (data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, type, tvDisplay) => {
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
            <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDateTime}</small></p>
            <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${data.display_time} secs  |  <i class="fa fa-tv" aria-hidden="true" style="margin-left: 5px"></i> ${tvDisplay}</p>
        </div>
    `;

    return contentHtml;
};

// Function to show delete modal
const showDeleteModal = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const modal = document.getElementById(`confirmDelete${capitalizedType}Modal`);
    console.log(modal);
    const modalContent = document.getElementById(`delete${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    modalContent.style = `
        padding: 15px;
    `;

    let evaluatorMessageHtml = '';

    if (contentType !== 'orgchart') {
        evaluatorMessageHtml = user_id != authorId ? `
            <div class="floating-label-container">
                <textarea name="evaluator_message_delete" id="evaluator_message_delete" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
                <label for="evaluator_message_delete" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User (required)</label>
            </div>
            <br>
        ` : '';
    }
    
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
    const formattedScheduleDateTime = formatDateTime(data.schedule_datetime);

    let mediaContent = '';
    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">` :
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
        if (data[`${type}_author_id`] === parseInt(user_id) && data.schedule_datetime === null) {
            contentContainer.innerHTML = `<p class="ann-author"><i class="fa fa-clock-o" aria-hidden="true"></i> Your <b> ${formatContentType(type)} </b> is pending approval to be posted on TV: ${tvInfo.tv_display}</p>` + contentDiv;
        } else if (data[`${type}_author_id`] === parseInt(user_id) && data.schedule_datetime !== null) {
            contentContainer.innerHTML = `<p class="ann-author"><i class="fa fa-clock-o" aria-hidden="true"></i> Your scheduled <b> ${formatContentType(type)} </b> to be posted on ${formattedScheduleDateTime} on TV: ${tvInfo.tv_display} is pending approval.</p>` + contentDiv;
        }
        else {
            contentContainer.innerHTML = `<p class="ann-author">${data.author_name} (${data.author_user_type}) wants to post <b> ${formatContentType(type)} </b> on TV: ${tvInfo.tv_display}</p>` + contentDiv;
        }
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
        const approveButton = createApproveButton('Approve', 'fa fa-check', () => 
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

        const rejectButton = createRejectButton('Reject', 'fa fa-times', () => 
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

        const deleteButton = createDeleteButton('Delete', 'fa fa-trash', () => 
            showDeleteModal(
                data[`${type}_id`], 
                type, 
                data[`${type}_author_id`], 
                full_name, 
                data.author_name, 
                data.author_user_type, 
                data.author_department, 
                data.tv_id
            )
        );


        if (userType === 'Super Admin' || (userType === 'Admin' && data[`user_type`] !== 'Super Admin')) {
            buttonContainer.appendChild(approveButton);
            buttonContainer.appendChild(rejectButton);
        }

        if (data[`${type}_author_id`] === parseInt(user_id)) {
            buttonContainer.appendChild(deleteButton);
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
        author_id: authorId ? authorId : null,
        author_name: authorName ? authorName : null,
        author_user_type: authorUserType ? authorUserType : null,
        author_department: authorDepartment ? authorDepartment : null,
        evaluator_name: loggedInFullName ? loggedInFullName : null,
        evaluator_message: evaluatorMessage ? evaluatorMessage : null,
        tv_id: tvId ? tvId : null
    };
    confirmDeleteModal.style.display = 'none';
    Ws.send(JSON.stringify(data)); // Send the data to the WebSocket server
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
        promaterial: 'Promotional Material'
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
const createModals = (contentTypes) => {
    const modalContainer = document.createElement('div');
    modalContainer.id = 'dynamicModals';

    contentTypes.forEach(type => {
        const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
        
        // Delete Modal
        const deleteModal = document.createElement('div');
        deleteModal.id = `confirmDelete${capitalizedType}Modal`;
        deleteModal.className = 'modal';
        deleteModal.innerHTML = `<div id="delete${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(deleteModal);
    });

    document.body.appendChild(modalContainer);
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
        const pendingContentsContainer = document.getElementById('pendingContentsContainer');
        if (!pendingContentsContainer) {
            console.error('Pending contents container not found');
            return;
        }

        // Clear existing content
        pendingContentsContainer.innerHTML = '';

        // Fetch all content types
        const allContent = [];
        for (const type of contentTypes) {
            const response = await fetch(`database/fetch_pending_contents.php?type=${type}&pending=true`);
            const data = await response.json();
            const filteredData = data.filter(item => 
                item.status === 'Pending' &&
                hasPermissionToView({ ...item, contentType: type })
            );
            
            // Add content type to each item
            filteredData.forEach(item => {
                item.contentType = type;
                allContent.push(item);
            });
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