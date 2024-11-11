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

let currentArchiveDays = 120; // Default value, will be updated from database
let currentTrashDays = 30; // Default value, will be updated from database

// Function to fetch archive days
const fetchArchiveDays = async () => {
    try {
        const response = await fetch('database/fetch_archive_days.php');
        const data = await response.json();
        currentArchiveDays = parseInt(data.archive_days);
        if (userType === 'Super Admin') {
            document.getElementById('archiveDaysButton').textContent = `${currentArchiveDays} days`;
        } else {
            document.getElementById('archiveDaysSpan').textContent = `${currentArchiveDays} days`;
        }
        return currentArchiveDays;
    } catch (error) {
        console.error('Error fetching archive days:', error);
        return 120; // Default fallback
    }
};

// Function to fetch trash days
const fetchTrashDays = async () => {
    try {
        const response = await fetch('database/fetch_trash_days.php');
        const data = await response.json();
        currentTrashDays = parseInt(data.trash_days);
        if (userType === 'Super Admin') {
            document.getElementById('trashDaysButton').textContent = `${currentTrashDays} days`;
        } else {
            document.getElementById('trashDaysSpan').textContent = `${currentTrashDays} days`;
        }
        return currentTrashDays;
    } catch (error) {
        console.error('Error fetching trash days:', error);
        return 120; // Default fallback
    }
};

const checkAndTrashExpiredItems = async () => {
    try {
        const contentTypes = ['announcement', 'event', 'news', 'promaterial'];
        if (userType === 'Admin' || userType === 'Super Admin') {
            contentTypes.push('peo', 'so');
        }

        for (const type of contentTypes) {
            const response = await fetch(`database/fetch_content.php?type=${type}&trashed=true`);
            const data = await response.json();
            
            // Filter for trashed items that aren't already trashed
            const trashedItems = data.filter(item => 
                item.isArchived === 0 && 
                item.isTrashed === 1
            );

            // Check each item's remaining days
            trashedItems.forEach(item => {
                const daysRemaining = calculateDaysRemaining(item.datetime_trashed);
                
                if (daysRemaining <= 0) {
                    // Automatically trash the item
                    deleteItem(
                        item[`${type}_id`],
                        type,
                        item[`${type}_author_id`],
                        'System', // System is moving it to trash
                        item.author_name,
                        item.author_user_type,
                        item.author_department,
                        item.tv_id
                    );
                    // fetchAndUpdateAll();
                }
            });
        }
    } catch (error) {
        console.error('Error checking expired items:', error);
    }
};

// Function to create trash days modal
const createTrashDaysModal = () => {
    // Remove existing modal if it exists
    const existingModal = document.getElementById('changeTrashDaysModal');
    if (existingModal) {
        existingModal.remove();
    }

    const modal = document.createElement('div');
    modal.id = 'changeTrashDaysModal';
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content" style="padding: 15px;">
            <span class="close" onclick="document.getElementById('changeTrashDaysModal').style.display='none'" style="color: #264B2B">
                <i class="fa fa-times-circle" aria-hidden="true"></i>
            </span>
            <br>
            <h1 style="color: #264B2B; font-size: 50px"><i class="fa fa-clock-o" aria-hidden="true"></i></h1>
            <p style="text-align: center">Change number of days before trashed items are permanently deleted</p>
            <p id="trashMessage2" style="font-size: 13px; color: #6E6E6E;">Minimum number of days is 10.</p>
            <br>
            <div class="floating-label-container">
                <input type="number" id="newTrashDays" required placeholder=" " class="floating-label-input" min="1">
                <label for="newTrashDays" class="floating-label">Number of Days</label>
            </div>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" onclick="document.getElementById('changeTrashDaysModal').style.display='none'">Cancel</button>
                <button type="button" class="green-button" onclick="updateTrashDays()">Update</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    return modal;
};

const changeNumberOfTrashDays = () => {
    if (userType !== 'Super Admin') {
        const errorModal = document.getElementById('errorModalVersion2');
        const errorMessage = document.getElementById('errorTextVersion2');
        errorModal.style.display = 'flex';
        errorMessage.textContent = 'Only Super Admin can change the number of trash days.';
        return;
    }

    const modal = createTrashDaysModal();
    const input = document.getElementById('newTrashDays');
    input.value = currentTrashDays;
    modal.style.display = 'flex';
};

// Function to update trash days
const updateTrashDays = async () => {
    const newDays = parseInt(document.getElementById('newTrashDays').value);
    
    if (!newDays || newDays < 10) {
        alert('Please enter a valid number of days (minimum 10).');
        return;
    }

    try {
        const response = await fetch('database/update_trash_days.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                trash_days: newDays,
                user_id: user_id
            })
        });

        const data = await response.json();
        
        if (data.success) {
            document.getElementById('changeTrashDaysModal').style.display = 'none';
            // Update the current trash days and refresh the display
            currentTrashDays = newDays;
            if (userType === 'Super Admin') {
                document.getElementById('trashDaysButton').textContent = `${newDays} days`;
            } else {
                document.getElementById('trashDaysSpan').textContent = `${newDays} days`;
            }
            const successModal = document.getElementById('successMessageModalVersion2');
            const successMessage = document.getElementById('successMessageVersion2');
            successModal.style.display = 'flex';
            successMessage.textContent = `Trash days updated to ${newDays} days.`;
            // Refresh all content to update the days remaining
            fetchAndUpdateAll();
        } else {
            alert('Failed to update trash days: ' + data.message);
        }
    } catch (error) {
        console.error('Error updating trash days:', error);
        alert('Failed to update trash days. Please try again.');
    }
};

// Update the calculateDaysRemaining function
const calculateDaysRemaining = (trashedDate) => {
    const trashed = new Date(trashedDate);
    const expiryDate = new Date(trashed.getTime() + (currentTrashDays * 24 * 60 * 60 * 1000));
    const today = new Date();
    const remaining = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));
    return Math.max(0, remaining);
};

// Function to search trash
function searchTrash() {
    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');
    const keyword = searchInput.value.toLowerCase();
    const contentType = document.getElementById('contentType').value;
    
    // Show/hide clear button
    clearButton.style.display = keyword ? 'block' : 'none';

    const trashedItems = document.querySelectorAll('#trashContainer > div');
    let hasVisibleItems = false;

    // Remove any existing "no results" message first
    const existingNoResultsMsg = document.getElementById('noResultsMessage');
    if (existingNoResultsMsg) {
        existingNoResultsMsg.remove();
    }

    trashedItems.forEach(item => {
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
    if (!hasVisibleItems && trashedItems.length > 0) {
        const msg = document.createElement('div');
        msg.id = 'noResultsMessage';
        msg.style = `
            text-align: center;
            font-size: 1.2em;
            color: #666;
            margin-top: 20px;
        `;
        msg.textContent = 'No trashed items match your search';
        document.getElementById('trashContainer').appendChild(msg);
    }
}

// Function to clear search
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');
    searchInput.value = '';
    clearButton.style.display = 'none';
    searchTrash();
}

// Function to populate content type dropdown
function populateContentTypeDropdown() {
    const select = document.getElementById('contentType');
    const contentTypes = ['announcement', 'event', 'news', 'promaterial'];
    
    if (userType === 'Admin' || userType === 'Super Admin') {
        contentTypes.push('peo', 'so');
    }

    // Clear existing options except "All"
    while (select.options.length > 1) {
        select.remove(1);
    }

    // Fetch counts for all content types
    Promise.all(contentTypes.map(type =>
        fetch(`database/fetch_content.php?type=${type}&trashed=true`)
            .then(response => response.json())
    ))
    .then(results => {
        let totalCount = 0;
        const typeCounts = {};

        // Calculate counts for each type
        results.forEach((data, index) => {
            const type = contentTypes[index];
            const count = data.filter(item => 
                item.isArchived === 0 && 
                item.isTrashed === 1 && 
                (type === 'peo' || type === 'so' || item.status === 'Approved') &&
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
    select.addEventListener('change', searchTrash);
}

const createRestoreButton = (text, iconClass, onClick) => {
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

const createArchiveButton = (text, iconClass, onClick) => {
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

// const createEditButton = (text, iconClass, onClick) => {
//     const button = document.createElement('button');
//     button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
//     button.className = 'green-button';
//     button.onclick = onClick;
//     return button;
// };

// Function to create content div
const createContentDiv = (data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, formattedTrashDateTime, type, tvDisplay) => {
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

    if (data.status === 'Pending' || data.status === 'Draft' || data.isTrashed === 0 || !data[`${type}_id`]) return;

    const tvInfo = await fetchTVInfo(type, data[`tv_id`]);
    const trashContainer = document.getElementById('trashContainer');

    const existingDiv = document.querySelector(`[data-${type}-id="${data[`${type}_id`]}"]`);
    const formattedCreatedDateTime = formatDateTime(data.created_datetime);
    const formattedExpirationDateTime = formatDateTime(data.expiration_datetime);
    const formattedTrashDateTime = formatDateTime(data.datetime_trashed);

    let mediaContent = '';
    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">` :
            isVideo ? `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/${type}_media/${data.media_path}" type="video/mp4"></video>` : '';
    }

    const contentDiv = createContentDiv(data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, formattedTrashDateTime, type, tvInfo.tv_display);
    
    if (existingDiv) {
        existingDiv.querySelector('.content-container-con').innerHTML = contentDiv;
    } else {
        const containerDiv = document.createElement('div');
        containerDiv.dataset[`${type}Id`] = data[`${type}_id`];
        containerDiv.setAttribute(`data-${type}-id`, data[`${type}_id`]);
        
        // Create type label
        const typeLabel = document.createElement('div');
        typeLabel.className = 'content-type-label';
        typeLabel.innerHTML = `<i class="fa ${getTypeIcon(type)}" aria-hidden="true"></i> ${formatContentType(type)}`;
        typeLabel.style = `
            display: inline-block;
            padding: 5px 10px;
            background-color: rgb(227 227 227);
            border: 1px solid black;
            color: black;
            border-radius: 15px;
            font-size: 0.8em;
            margin-bottom: 10px;
        `;

        // Create days remaining label
        const daysRemainingLabel = document.createElement('div');
        daysRemainingLabel.className = 'days-remaining-label';
        daysRemainingLabel.innerHTML = `<i class="fa fa-clock-o" aria-hidden="true"></i> ${calculateDaysRemaining(data.datetime_trashed)} days left`;
        daysRemainingLabel.style = `
            display: inline-block;
            padding: 5px 10px;
            background-color: rgb(227 227 227);
            color: black;
            border-radius: 15px;
            font-size: 0.8em;
            margin-bottom: 10px;
            margin-left: 10px;
            background: #aad3b0;
        `;

        // Create content container
        const contentContainer = document.createElement('div');
        contentContainer.className = 'content-container-con';
        contentContainer.innerHTML = contentDiv;
        contentContainer.style = `
            background: #dffce5;
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
        const archiveButton = createArchiveButton('Archive', 'fa fa-archive', () => showArchiveModal(data[`${type}_id`], type, data[`${type}_author_id`], full_name, data.author_name, data.author_user_type, data.author_department, data.tv_id));
        const restoreButton = createRestoreButton('Restore', 'fa fa-refresh', () => showRestoreModal(data[`${type}_id`], type, data[`${type}_author_id`], full_name, data.author_name, data.author_user_type, data.author_department, data.tv_id, data.expiration_datetime));

        if (userType === 'Super Admin' || (userType === 'Admin' && data[`user_type`] !== 'Super Admin') || data[`${type}_author_id`] === parseInt(user_id)) {
            buttonContainer.appendChild(deleteButton);
            buttonContainer.appendChild(archiveButton);
            buttonContainer.appendChild(restoreButton);
        }

        // Set main container styles
        containerDiv.style = `
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 15px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            background: rgb(223, 252, 229);
        `;

        // Append elements in the correct order
        containerDiv.appendChild(typeLabel);
        containerDiv.appendChild(daysRemainingLabel);
        containerDiv.appendChild(contentContainer);
        containerDiv.appendChild(buttonContainer);
        trashContainer.appendChild(containerDiv);
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

// Function to restore an item
const restoreItem = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId, isExpired) => {
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
    const modal = document.getElementById(`confirmRestore${capitalizedType}Modal`);
    
    let action = 'restore_content';
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
        tv_id: tvId
    };

    if (isExpired) {
        const newExpirationDateTime = document.getElementById('new_expiration_datetime').value;
        if (!newExpirationDateTime) {
            alert('New expiration date and time is required.');
            return;
        }
        
        // Check if the new expiration date is in the past
        const newExpirationDate = new Date(newExpirationDateTime);
        const currentDate = new Date();
        if (newExpirationDate < currentDate) {
            const errorModal = document.getElementById('errorModalVersion2');
            const errorMessage = document.getElementById('errorTextVersion2');
            errorModal.style = `
                display: flex;
                z-index: 100000;
            `;
            errorMessage.textContent = 'The new expiration date cannot be in the past.';
            return;
        }

        action = 'restore_content_and_update_expiration';
        data.action = action;
        data.expiration_datetime = newExpirationDateTime;
    }

    modal.style.display = 'none';
    Ws.send(JSON.stringify(data));
};

// Function to trash an item
const trashItem = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    let evaluatorMessage;

    if (loggedInFullName === 'System') {
        // Automatic system-generated message
        evaluatorMessage = `Content automatically moved to Trash after ${currentTrashDays} days in Trash`;
    } else {
        // Manual trash by user
        const evaluatorMessageTrash = document.getElementById('evaluator_message_trash');
        if (evaluatorMessageTrash) {
            evaluatorMessage = evaluatorMessageTrash.value;
            if (!evaluatorMessage) {
                alert('Evaluator message is required.');
                return;
            }
        }
    }

    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const confirmTrashModal = document.getElementById(`confirmTrash${capitalizedType}Modal`);
    const data = {
        action: 'trash_content',
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
    confirmTrashModal.style.display = 'none';
    Ws.send(JSON.stringify(data)); // Send the data to the WebSocket server
};

const startExpirationChecker = () => {
    // Check immediately when the page loads
    checkAndTrashExpiredItems();

    // Then check every minute
    setInterval(checkAndTrashExpiredItems, 60000);
};

// Function to delete an item
const deleteItem = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    let evaluatorMessage;

    if (loggedInFullName === 'System') {
        // Automatic system-generated message
        evaluatorMessage = `Content automatically deleted after ${currentTrashDays} days in Trash`;
    } else {
        // Manual trash by user
        const evaluatorMessageDeleteForever = document.getElementById('evaluator_message_delete');
        if (evaluatorMessageDeleteForever) {
            evaluatorMessage = evaluatorMessageDeleteForever.value;
            if (!evaluatorMessage) {
                alert('Evaluator message is required.');
                return;
            }
        }
    }

    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const confirmTrashModal = document.getElementById(`confirmTrash${capitalizedType}Modal`);
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
    confirmTrashModal.style.display = 'none';
    confirmDeleteModal.style.display = 'none';
    Ws.send(JSON.stringify(data)); // Send the data to the WebSocket server
};

// Function to archive an item
const archiveItem = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    const evaluatorMessageArchive = document.getElementById('evaluator_message_archive');
    let evaluatorMessage = null;

    if (evaluatorMessageArchive) {
        evaluatorMessage = evaluatorMessageArchive.value;
        if (!evaluatorMessage) {
            alert('Evaluator message is required.');
            return;
        }
    }

    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const modal = document.getElementById(`confirmArchive${capitalizedType}Modal`);
    const data = {
        action: 'archive_content',
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
    modal.style.display = 'none';
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
        messageDiv.textContent = `No trashed promotional materials to be displayed`;
    } else if (type === 'peo') {
        messageDiv.textContent = `No trashed Program Education Objective (PEO) to be displayed`;
    } else if (type === 'so') {
        messageDiv.textContent = `No trashed Student Outcome (SO) to be displayed`;
    } else {
        messageDiv.textContent = `No trashed ${type} to be displayed`;
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
        
        // Restore Modal
        const restoreModal = document.createElement('div');
        restoreModal.id = `confirmRestore${capitalizedType}Modal`;
        restoreModal.className = 'modal';
        restoreModal.innerHTML = `<div id="restore${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(restoreModal);

        // Trash Modal
        const trashModal = document.createElement('div');
        trashModal.id = `confirmTrash${capitalizedType}Modal`;
        trashModal.className = 'modal';
        trashModal.innerHTML = `<div id="trash${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(trashModal);

        // Delete Modal
        const deleteModal = document.createElement('div');
        deleteModal.id = `confirmDelete${capitalizedType}Modal`;
        deleteModal.className = 'modal';
        deleteModal.innerHTML = `<div id="delete${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(deleteModal);

        // Archive Modal
        const archiveModal = document.createElement('div');
        archiveModal.id = `confirmArchive${capitalizedType}Modal`;
        archiveModal.className = 'modal';
        archiveModal.innerHTML = `<div id="archive${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(archiveModal);
    });

    document.body.appendChild(modalContainer);
};

// Function to show restore modal
const showRestoreModal = async (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId, expirationDateTime) => {
    // Fetch TV info first
    const tvInfo = await fetchTVInfo(contentType, tvId);

    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const modal = document.getElementById(`confirmRestore${capitalizedType}Modal`);
    const modalContent = document.getElementById(`restore${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    modalContent.style = `
        padding: 15px;
    `;

    const currentDate = new Date();
    const expirationDate = new Date(expirationDateTime);
    const isExpired = expirationDate < currentDate;

    const evaluatorMessageHtml = user_id != authorId ? `
        <div class="floating-label-container">
            <textarea name="evaluator_message_restore" id="evaluator_message_restore" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
            <label for="evaluator_message_restore" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User (required)</label>
        </div>
        <br>
    ` : '';

    const expirationDateHtml = isExpired ? `
        <div class="floating-label-container">
            <input type="datetime-local" id="new_expiration_datetime" name="new_expiration_datetime" required class="floating-label-input">
            <label for="new_expiration_datetime" class="floating-label">New Expiration Date and Time</label>
        </div>
        <br>
    ` : '';
    
    modalContent.innerHTML = `
        <span class="close" onclick="document.getElementById('confirmRestore${capitalizedType}Modal').style.display='none'" style="color: #316038"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="color: #316038; font-size: 50px"><i class="fa fa-refresh" aria-hidden="true"></i></h1>
        <p id="deleteMessage" style="text-align: center">Proceed to restore this ${contentType} to TV: ${tvInfo.tv_display}?</p>
        <p id="deleteMessage2" style="font-size: 13px; color: #6E6E6E;">Restored items will be displayed in the TV.</p>
        <br>
        ${evaluatorMessageHtml}
        ${expirationDateHtml}
        <div style="text-align: right;">
            <button type="button" class="grey-button" onclick="document.getElementById('confirmRestore${capitalizedType}Modal').style.display='none'">Cancel</button>
            <button type="button" class="green-button" onclick="restoreItem(${contentId}, '${contentType}', ${authorId}, '${loggedInFullName}', '${authorName}', '${authorUserType}', '${authorDepartment}', ${tvId}, ${isExpired})">Yes, Restore</button>
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
        <span class="close" onclick="document.getElementById('confirmDelete${capitalizedType}Modal').style.display='none'" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
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

// Function to show archive modal
const showArchiveModal = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const modal = document.getElementById(`confirmArchive${capitalizedType}Modal`);
    const modalContent = document.getElementById(`archive${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    modalContent.style = `
        padding: 15px;
    `;

    const evaluatorMessageHtml = user_id != authorId ? `
        <div class="floating-label-container">
            <textarea name="evaluator_message_archive" id="evaluator_message_archive" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
            <label for="evaluator_message_archive" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User (required)</label>
        </div>
        <br>
    ` : '';
    
    modalContent.innerHTML = `
        <span class="close" onclick="document.getElementById('confirmArchive${capitalizedType}Modal').style.display='none'" style="color: #dc7d09"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="color: #dc7d09; font-size: 50px"><i class="fa fa-archive" aria-hidden="true"></i></h1>
        <p id="trashMessage" style="text-align: center">Proceed to archive?</p>
        <p id="trashMessage2" style="font-size: 13px; color: #6E6E6E;">Archived items are stored for ${currentArchiveDays} days before being moved to Trash.</p>
        <br>
        ${evaluatorMessageHtml}
        <div style="text-align: right;">
            <button type="button" class="grey-button" onclick="document.getElementById('confirmArchive${capitalizedType}Modal').style.display='none'">Cancel</button>
            <button type="button" class="yellow-button" onclick="archiveItem(${contentId}, '${contentType}', ${authorId}, '${loggedInFullName}', '${authorName}', '${authorUserType}', '${authorDepartment}', ${tvId})">Yes, Archive</button>
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
    if (userType === 'Admin' || userType === 'Super Admin') {
        contentTypes.push('peo', 'so');
    }

    try {
        const trashContainer = document.getElementById('trashContainer');
        if (!trashContainer) {
            console.error('Trash container not found');
            return;
        }

        // Clear existing content
        trashContainer.innerHTML = '';

        // Fetch all content types
        const allContent = [];
        for (const type of contentTypes) {
            const response = await fetch(`database/fetch_content.php?type=${type}&trashed=true`);
            const data = await response.json();
            const filteredData = data.filter(item => 
                item.isArchived === 0 && 
                item.isTrashed === 1 && 
                (type === 'peo' || type === 'so' || item.status === 'Approved') &&
                hasPermissionToView({ ...item, contentType: type })
            );
            
            // Add content type to each item
            filteredData.forEach(item => {
                item.contentType = type;
                allContent.push(item);
            });
        }

        // Sort all content by trashed datetime
        allContent.sort((a, b) => 
            new Date(b.datetime_trashed) - new Date(a.datetime_trashed)
        );

        if (allContent.length === 0) {
            trashContainer.innerHTML = `
                <div style="text-align: center; font-size: 1.2em; color: #666; margin-top: 5px;">
                    No trashed items to display
                </div>
            `;
            return;
        }

        // Display all content
        for (const item of allContent) {
            await updateUI(item, item.contentType);
        }
    } catch (error) {
        console.error('Error fetching trashed content:', error);
    }
};

// Update WebSocket event listener
Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    switch (data.action) {
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
    // Fetch trash days first
    await fetchTrashDays();
    await fetchArchiveDays();

    // Create modals for all content types
    const contentTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];
    createModals(contentTypes);

    // Initialize search functionality
    populateContentTypeDropdown();
    
    // Add clear button click handler
    document.getElementById('clearSearch').addEventListener('click', clearSearch);

    // Start the expiration checker
    startExpirationChecker();

    // Fetch and update all content
    fetchAndUpdateAll();
});