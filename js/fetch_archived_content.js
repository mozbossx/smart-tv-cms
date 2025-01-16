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
        currentTrashDays = data.trash_days;
    } catch (error) {
        console.error('Error fetching trash days:', error);
        return 30; // Default fallback
    }
};

// const checkAndTrashExpiredItems = async () => {
//     try {
//         const contentTypes = ['announcement', 'event', 'news', 'promaterial'];
//         if (userType === 'Admin' || userType === 'Super Admin') {
//             contentTypes.push('peo', 'so');
//         }

//         for (const type of contentTypes) {
//             const response = await fetch(`database/fetch_content.php?type=${type}&archived=true`);
//             const data = await response.json();
            
//             // Filter for archived items that aren't already trashed
//             const archivedItems = data.filter(item => 
//                 item.isArchived === 1 && 
//                 item.isTrashed === 0
//             );

//             // Check each item's remaining days
//             archivedItems.forEach(item => {
//                 const daysRemaining = calculateDaysRemaining(item.datetime_archived);
                
//                 if (daysRemaining <= 0) {
//                     // Automatically trash the item
//                     trashItem(
//                         item[`${type}_id`],
//                         type,
//                         item[`${type}_author_id`],
//                         'System', // System is moving it to trash
//                         item.author_name,
//                         item.author_user_type,
//                         item.author_department,
//                         item.tv_id
//                     );
//                     // fetchAndUpdateAll();
//                 }
//             });
//         }
//     } catch (error) {
//         console.error('Error checking expired items:', error);
//     }
// };

// Function to create archive days modal
const createArchiveDaysModal = () => {
    // Remove existing modal if it exists
    const existingModal = document.getElementById('changeArchiveDaysModal');
    if (existingModal) {
        existingModal.remove();
    }

    const modal = document.createElement('div');
    modal.id = 'changeArchiveDaysModal';
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content" style="padding: 15px;">
            <span class="close" onclick="document.getElementById('changeArchiveDaysModal').style.display='none'">
                <i class="fa fa-times-circle" aria-hidden="true"></i>
            </span>
            <br>
            <h1 style="font-size: 50px"><i class="fa fa-clock-o" aria-hidden="true"></i></h1>
            <p style="text-align: center">Change number of days before archived items are moved to Trash</p>
            <p id="trashMessage2" style="font-size: 13px; color: #6E6E6E;">Minimum number of days is 10.</p>
            <br>
            <div class="floating-label-container">
                <input type="number" id="newArchiveDays" required placeholder=" " class="floating-label-input" min="1">
                <label for="newArchiveDays" class="floating-label">Number of Days</label>
            </div>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" onclick="document.getElementById('changeArchiveDaysModal').style.display='none'">Cancel</button>
                <button type="button" class="green-button" onclick="updateArchiveDays()">Update</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    return modal;
};

const changeNumberOfArchiveDays = () => {
    if (userType !== 'Super Admin') {
        const errorModal = document.getElementById('errorModalVersion2');
        const errorMessage = document.getElementById('errorTextVersion2');
        errorModal.style.display = 'flex';
        errorMessage.textContent = 'Only Super Admin can change the number of archive days.';
        return;
    }

    const modal = createArchiveDaysModal();
    const input = document.getElementById('newArchiveDays');
    input.value = currentArchiveDays;
    modal.style.display = 'flex';
};

// Function to update archive days
const updateArchiveDays = async () => {
    const newDays = parseInt(document.getElementById('newArchiveDays').value);
    
    if (!newDays || newDays < 10) {
        alert('Please enter a valid number of days (minimum 10).');
        return;
    }

    try {
        const response = await fetch('database/update_archive_days.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                archive_days: newDays,
                user_id: user_id
            })
        });

        const data = await response.json();
        
        if (data.success) {
            document.getElementById('changeArchiveDaysModal').style.display = 'none';
            // Update the current archive days and refresh the display
            currentArchiveDays = newDays;
            if (userType === 'Super Admin') {
                document.getElementById('archiveDaysButton').textContent = `${newDays} days`;
            } else {
                document.getElementById('archiveDaysSpan').textContent = `${newDays} days`;
            }
            const successModal = document.getElementById('successMessageModalVersion2');
            const successMessage = document.getElementById('successMessageVersion2');
            successModal.style.display = 'flex';
            successMessage.textContent = `Archive days updated to ${newDays} days.`;
            // Refresh all content to update the days remaining
            fetchAndUpdateAll();
        } else {
            alert('Failed to update archive days: ' + data.message);
        }
    } catch (error) {
        console.error('Error updating archive days:', error);
        alert('Failed to update archive days. Please try again.');
    }
};

// Update the calculateDaysRemaining function
const calculateDaysRemaining = (archivedDate) => {
    const archived = new Date(archivedDate);
    const expiryDate = new Date(archived.getTime() + (currentArchiveDays * 24 * 60 * 60 * 1000));
    const today = new Date();
    const remaining = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));
    return Math.max(0, remaining);
};

// Function to search archives
function searchArchives() {
    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');
    const keyword = searchInput.value.toLowerCase();
    const contentType = document.getElementById('contentType').value;
    
    // Show/hide clear button
    clearButton.style.display = keyword ? 'block' : 'none';

    const archivedItems = document.querySelectorAll('#archivesContainer > div');
    let hasVisibleItems = false;

    // Remove any existing "no results" message first
    const existingNoResultsMsg = document.getElementById('noResultsMessage');
    if (existingNoResultsMsg) {
        existingNoResultsMsg.remove();
    }

    archivedItems.forEach(item => {
        // Skip the noResultsMessage div if it exists
        if (item.id === 'noResultsMessage') return;

        const textContent = item.textContent.toLowerCase();
        const itemTypeElement = item.querySelector('.content-type-label');
        const itemType = itemTypeElement ? itemTypeElement.textContent.toLowerCase().trim() : '';
        
        // Add debug logging
        console.log('Item type:', itemType);
        console.log('Content type to match:', contentType.toLowerCase());

        const matchesKeyword = keyword === '' || textContent.includes(keyword);
        // Update the type matching logic to handle dynamic feature names
        const matchesType = contentType === 'all' || 
                          itemType.includes(contentType.toLowerCase()) ||
                          itemType.includes(contentType.replace(/_/g, ' ').toLowerCase());
        
        item.style.display = (matchesKeyword && matchesType) ? 'block' : 'none';
        if (matchesKeyword && matchesType) hasVisibleItems = true;
    });

    // Show "no results" message if needed
    if (!hasVisibleItems && archivedItems.length > 0) {
        const msg = document.createElement('div');
        msg.id = 'noResultsMessage';
        msg.style = `
            text-align: center;
            font-size: 1.2em;
            color: #666;
            margin-top: 20px;
        `;
        msg.textContent = 'No archived items match your search';
        document.getElementById('archivesContainer').appendChild(msg);
    }
}

// Function to clear search
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');
    searchInput.value = '';
    clearButton.style.display = 'none';
    searchArchives();
}

// Function to populate content type dropdown
function populateContentTypeDropdown() {
    // First fetch all features
    fetch('database/fetch_feature_info.php?type=all')
        .then(response => response.json())
        .then(features => {
            const select = document.getElementById('contentType');
            const contentTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];
            
            // Add dynamic features
            if (Array.isArray(features)) {
                features.forEach(feature => {
                    const featureType = feature.type;
                    if (!contentTypes.includes(featureType)) {
                        contentTypes.push(featureType);
                        // Store the icon for this feature type if needed
                        featureIcons[featureType] = feature.icon;
                    }
                });
            }
            
            // Add admin-only content types
            if (userType === 'Admin' || userType === 'Super Admin') {
                if (!contentTypes.includes('peo')) contentTypes.push('peo');
                if (!contentTypes.includes('so')) contentTypes.push('so');
            }

            // Clear existing options except "All"
            while (select.options.length > 1) {
                select.remove(1);
            }

            // Fetch counts for all content types
            Promise.all(contentTypes.map(type =>
                fetch(`database/fetch_content.php?type=${type}&archived=true`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(`Error fetching ${type}:`, data.error);
                            return { type, count: 0 };
                        }

                        const count = data.filter(item => 
                            item.isArchived === 1 && 
                            item.isTrashed === 0 && 
                            (item.status === 'Approved' || type === 'peo' || type === 'so') &&
                            hasPermissionToView({ ...item, contentType: type })
                        ).length;

                        return { type, count };
                    })
                    .catch(error => {
                        console.error(`Error fetching ${type}:`, error);
                        return { type, count: 0 };
                    })
            ))
            .then(results => {
                let totalCount = 0;

                // Calculate total count and store individual counts
                results.forEach(({ type, count }) => {
                    totalCount += count;
                });

                // Update "All" option with total count
                select.options[0].textContent = `All (${totalCount})`;

                // Add options for each content type with counts
                results.forEach(({ type, count }) => {
                    if (count > 0 || type === 'announcement' || type === 'event' || type === 'news' || type === 'promaterial' || type === 'peo' || type === 'so') {
                        const option = document.createElement('option');
                        option.value = type;
                        option.textContent = `${formatContentType(type)} (${count})`;
                        select.appendChild(option);
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching content type counts:', error);
            });

            // Add change event listener
            select.addEventListener('change', searchArchives);
        })
        .catch(error => {
            console.error('Error fetching features:', error);
        });
}

const createTrashButton = (text, iconClass, onClick) => {
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

// const createEditButton = (text, iconClass, onClick) => {
//     const button = document.createElement('button');
//     button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
//     button.className = 'green-button';
//     button.onclick = onClick;
//     return button;
// };

// Function to create content div
const createContentDiv = (data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, formattedArchivedDateTime, type, tvDisplay) => {
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
            // For dynamic features, display all content fields except metadata
            for (let key in data) {
                if (!key.match(/^(created_datetime|expiration_datetime|display_time|status|isArchived|isTrashed|tv_id|author_id|type|contentType|department|user_type|category|evaluated_by|evaluated_message|author_name|media_path|media_upload_only_zz|schedule_datetime|author_user_type|author_department|datetime_archived|datetime_trashed)$/) 
                    && !key.endsWith('_id')) {
                    contentHtml += `<pre class="ann-body" style="word-break: break-word">${data[key]}</pre>`;
                }
            }
    }

    contentHtml += `
            <div class="line-separator"></div>
            <p class="ann-author" style="color: #6E6E6E; margin-top: 10px"><small>Posted by ${data.author_name || 'Unknown'} on ${formattedCreatedDateTime}</small></p>
            ${formattedExpirationDateTime && !['peo', 'so'].includes(type) ? 
                `<p class="ann-author" style="color: #6E6E6E;"><small>Expires on ${formattedExpirationDateTime}</small></p>` : 
                ''}
            <p class="ann-author" style="color: #6E6E6E; margin-bottom: 10px"><small>Archived on ${formattedArchivedDateTime}</small></p>
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

    if (data.status === 'Pending' || data.status === 'Draft' || data.isArchived === 0 || !data[`${type}_id`]) return;

    const tvInfo = await fetchTVInfo(type, data[`tv_id`]);
    const archivesContainer = document.getElementById('archivesContainer');

    const existingDiv = document.querySelector(`[data-${type}-id="${data[`${type}_id`]}"]`);
    const formattedCreatedDateTime = formatDateTime(data.created_datetime);
    const formattedExpirationDateTime = ['peo', 'so'].includes(type) ? null : formatDateTime(data.expiration_datetime);
    const formattedArchivedDateTime = formatDateTime(data.datetime_archived);

    let mediaContent = '';
    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        mediaContent = isImage ? `<img src="servers/${type}_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">` :
            isVideo ? `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/${type}_media/${data.media_path}" type="video/mp4"></video>` : '';
    }

    const contentDiv = createContentDiv(data, mediaContent, formattedCreatedDateTime, formattedExpirationDateTime, formattedArchivedDateTime, type, tvInfo.tv_display);
    
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

        // Create days remaining label
        const daysRemainingLabel = document.createElement('div');
        daysRemainingLabel.className = 'days-remaining-label';
        daysRemainingLabel.innerHTML = `<i class="fa fa-clock-o" aria-hidden="true"></i> ${calculateDaysRemaining(data.datetime_archived)} days left`;
        daysRemainingLabel.style = `
            display: inline-block;
            padding: 5px 10px;
            color: black;
            border-radius: 15px;
            font-size: 0.8em;
            margin-bottom: 10px;
            margin-left: 10px;
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

        const trashButton = createTrashButton('Trash', 'fa fa-trash', () => showTrashModal(data[`${type}_id`], type, data[`${type}_author_id`], full_name, data.author_name, data.author_user_type, data.author_department, data.tv_id));
        const restoreButton = createRestoreButton('Restore', 'fa fa-refresh', () => showRestoreModal(data[`${type}_id`], type, data[`${type}_author_id`], full_name, data.author_name, data.author_user_type, data.author_department, data.tv_id, data.expiration_datetime));

        if (userType === 'Super Admin' || (userType === 'Admin' && data[`user_type`] !== 'Super Admin') || data[`${type}_author_id`] === parseInt(user_id)) {
            buttonContainer.appendChild(trashButton);
            buttonContainer.appendChild(restoreButton);
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
        containerDiv.appendChild(daysRemainingLabel);
        containerDiv.appendChild(contentContainer);
        containerDiv.appendChild(buttonContainer);
        archivesContainer.appendChild(containerDiv);
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
    // Default icons for built-in types
    const defaultIcons = {
        announcement: 'fa-bullhorn',
        event: 'fa-calendar-check-o',
        news: 'fa-newspaper-o',
        promaterial: 'fa-object-group',
        peo: 'fa-map',
        so: 'fa-graduation-cap'
    };

    // Return the dynamic icon if it exists, otherwise use default or fallback
    return featureIcons[type] || defaultIcons[type] || 'fa-file';
};

// Helper function to format content type for display
const formatContentType = (type) => {
    // Default formats for built-in types
    const formats = {
        announcement: 'Announcement',
        event: 'Event',
        news: 'News',
        promaterial: 'Promotional Material',
        peo: 'PEO',
        so: 'SO'
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
        evaluatorMessage = `Content automatically moved to Trash after ${currentArchiveDays} days in Archives`;
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

// const startExpirationChecker = () => {
//     // Check immediately when the page loads
//     checkAndTrashExpiredItems();

//     // Then check every minute
//     setInterval(checkAndTrashExpiredItems, 60000);
// };

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
        messageDiv.textContent = `No archived promotional materials to be displayed`;
    } else if (type === 'peo') {
        messageDiv.textContent = `No archived Program Education Objective (PEO) to be displayed`;
    } else if (type === 'so') {
        messageDiv.textContent = `No archived Student Outcome (SO) to be displayed`;
    } else {
        messageDiv.textContent = `No archived ${type} to be displayed`;
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
const createModals = async () => {
    try {
        // Get all available features
        const features = await getFeatureInfo('all');
        const contentTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];
        
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
            
            // Create restore, trash, and delete modals for each type
            ['Restore', 'Trash', 'Delete'].forEach(action => {
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
        <span class="close" onclick="document.getElementById('confirmRestore${capitalizedType}Modal').style.display='none'"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="font-size: 50px"><i class="fa fa-refresh" aria-hidden="true"></i></h1>
        <p id="deleteMessage" style="text-align: center">Proceed to restore this ${contentType} to TV: ${tvInfo.tv_display}?</p>
        <p id="deleteMessage2" style="font-size: 13px; color: #316038;">Restored items will be displayed in the TV.</p>
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

// Function to show trash modal
const showTrashModal = (contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) => {
    const capitalizedType = contentType.charAt(0).toUpperCase() + contentType.slice(1);
    const modal = document.getElementById(`confirmTrash${capitalizedType}Modal`);
    const modalContent = document.getElementById(`trash${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    modalContent.style = `
        padding: 15px;
    `;

    const evaluatorMessageHtml = user_id != authorId ? `
        <div class="floating-label-container">
            <textarea name="evaluator_message_trash" id="evaluator_message_trash" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
            <label for="evaluator_message_trash" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User (required)</label>
        </div>
        <br>
    ` : '';
    
    modalContent.innerHTML = `
        <span class="close" onclick="document.getElementById('confirmTrash${capitalizedType}Modal').style.display='none'"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
        <p id="trashMessage" style="text-align: center">Proceed to move to Trash?</p>
        <p id="trashMessage2" style="font-size: 13px; color: #6E6E6E;">Trashed items are stored for ${currentTrashDays} days before being permanently deleted.</p>
        <br>
        ${evaluatorMessageHtml}
        <div style="text-align: right;">
            <!-- <button type="button" class="grey-button" onclick="document.getElementById('confirmTrash${capitalizedType}Modal').style.display='none'">Cancel</button> -->
            <button type="button" class="grey-button" onclick="showDeleteModal(${contentId}, '${contentType}', ${authorId}, '${loggedInFullName}', '${authorName}', '${authorUserType}', '${authorDepartment}', ${tvId})">Delete Forever</button>
            <button type="button" class="red-button" onclick="trashItem(${contentId}, '${contentType}', ${authorId}, '${loggedInFullName}', '${authorName}', '${authorUserType}', '${authorDepartment}', ${tvId})">Yes, move to Trash</button>
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

let featureIcons = {};

// Function to fetch and update content for all types
const fetchAndUpdateAll = async () => {
    try {
        // Get all available features first
        const response = await fetch('database/fetch_feature_info.php?type=all');
        const features = await response.json();
        console.log(features);
        
        // Start with default content types
        const contentTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];
        
        // Add dynamic features and store their icons
        if (Array.isArray(features)) {
            features.forEach(feature => {
                const featureType = feature.type;
                if (!contentTypes.includes(featureType)) {
                    contentTypes.push(featureType);
                    // Store the icon for this feature
                    featureIcons[featureType] = feature.icon;
                }
            });
        }

        // Add admin-only content types
        if (userType === 'Admin' || userType === 'Super Admin') {
            contentTypes.push('peo', 'so');
        }

        const archivesContainer = document.getElementById('archivesContainer');
        if (!archivesContainer) {
            console.error('Archives container not found');
            return;
        }

        // Clear existing content
        archivesContainer.innerHTML = '';

        // Fetch all content types
        const allContent = [];
        for (const type of contentTypes) {
            try {
                const response = await fetch(`database/fetch_content.php?type=${type}&archived=true`);
                const data = await response.json();
                
                if (data.error) {
                    console.error(`Error fetching ${type}:`, data.error);
                    continue;
                }

                const filteredData = data.filter(item => 
                    item.isArchived === 1 && 
                    item.isTrashed === 0 && 
                    (item.status === 'Approved' || type === 'peo' || type === 'so') &&
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

        // Sort all content by archived datetime
        allContent.sort((a, b) => 
            new Date(b.datetime_archived) - new Date(a.datetime_archived)
        );

        if (allContent.length === 0) {
            archivesContainer.innerHTML = `
                <div style="text-align: center; font-size: 1.2em; color: #666; margin-top: 5px;">
                    No archived items to display
                </div>
            `;
            return;
        }

        // Display all content
        for (const item of allContent) {
            await updateUI(item, item.contentType);
        }
    } catch (error) {
        console.error('Error fetching archived content:', error);
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
    // Fetch archive days first
    await fetchArchiveDays();
    await fetchTrashDays();

    // Create modals with dynamic features
    await createModals();

    // Initialize search functionality
    populateContentTypeDropdown();
    
    // Add clear button click handler
    document.getElementById('clearSearch').addEventListener('click', clearSearch);

    // Start the expiration checker
    // startExpirationChecker();

    // Fetch and update all content
    fetchAndUpdateAll();
});