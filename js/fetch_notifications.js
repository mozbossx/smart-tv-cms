// Add a global variable to track connection status
let isConnected = false;
const loadingWebsocketConnectionModal = document.getElementById('loadingWebsocketConnectionModal');
const successWebsocketConnectionModal = document.getElementById('successWebsocketConnectionModal');
const loadingWebsocketConnectionMessage = document.getElementById('loadingWebsocketConnectionMessage');
const successWebsocketConnectionMessage = document.getElementById('successWebsocketConnectionMessage');

function connectWebSocket() {
    console.log('Attempting WebSocket connection...');
    
    // Determine if we're in localhost or production
    const isLocalhost = window.location.hostname === 'localhost' || 
                       window.location.hostname === '127.0.0.1' ||
                       window.location.hostname === '192.168.1.12' ||
                       window.location.hostname === '192.168.1.10';
    
    // Choose WebSocket URL based on environment
    const wsUrl = isLocalhost 
        ? 'ws://192.168.1.10:8081'
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
            }, 10000); // 10 second timeout
        };
        
        ws.onopen = function() {
            // console.log('Client: WebSocket Connected Successfully to:', wsUrl);
            isConnected = true;

            // Client-initiated pings
            clientPingInterval = setInterval(() => {
                if (ws.readyState === WebSocket.OPEN) {
                    // console.log('Client: Sending ping to server');
                    ws.send(JSON.stringify({ action: 'ping' }));
                    heartbeat();
                }
            }, 10000); // 10 second interval

            // if (loadingWebsocketConnectionModal.style.display === 'flex' && successWebsocketConnectionModal) {
            //     loadingWebsocketConnectionModal.style.display = 'none';
            //     successWebsocketConnectionModal.style.display = 'flex';
            //     successWebsocketConnectionMessage.textContent = 'Server Connected Successfully!';
            // }

            // Fetch initial data after connection
            fetchNotifications();
            fetchNotificationCount();
            fetchArchivesCount();
            fetchTrashCount();
            fetchApprovedCount();
            fetchRejectedCount();
            fetchPendingCount();
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
                case 'new_notification':
                case 'update_notification':
                    fetchNotifications();
                    fetchNotificationCount();
                    fetchArchivesCount();
                    fetchTrashCount();
                    fetchApprovedCount();
                    fetchRejectedCount();
                    fetchPendingCount();
                    break;
                case 'delete_notification':
                    fetchNotifications();
                    break;
                case 'reset_notification_count':
                    if (data.success) {
                        document.getElementById('notificationCount').style.display = 'none';
                        document.getElementById('notificationCountMobile').style.display = 'none';
                    }
                    break;
                case 'delete_content':
                    if (data.success) {
                        const notificationElement = document.querySelector(`[data-content-id="${data[`${data.type}_id`]}"][data-content-type="${data.type}"]`);
                        if (notificationElement) {
                            notificationElement.remove();
                        }
                        fetchNotifications();
                    }
                    break;
            }
        };
        
        ws.onclose = function(event) {
            // console.log('Client: WebSocket connection closed');
            isConnected = false;
            clearInterval(clientPingInterval);
            clearTimeout(clientPingTimeout);
            // console.log('Client: Cleared all timers');

            // if (loadingWebsocketConnectionModal) {
            //     loadingWebsocketConnectionModal.style.display = 'flex';
            //     successWebsocketConnectionModal.style.display = 'none';
            //     loadingWebsocketConnectionMessage.textContent = 'Server Disconnected. Reconnecting...';
            // }

            // Attempt to reconnect
            setTimeout(() => {
                console.log('Client: Attempting to reconnect.......');
                Ws = connectWebSocket();
            }, 2500);
        };
        
        return ws;
    } catch (error) {
        // console.error('Client: Error creating WebSocket:', error.message);
        isConnected = false;
        
        loadingWebsocketConnectionModal.style.display = 'none';
        successWebsocketConnectionModal.style.display = 'flex';
        successWebsocketConnectionMessage.textContent = 'There was an error connecting to the server. Please try again later.';
        setTimeout(() => {
            Ws = connectWebSocket();
        }, 2500);
        return null;
    }
}

let Ws = null;

try {
    Ws = connectWebSocket();
} catch (error) {
    // console.error('Error initializing WebSocket:', error);
    // console.log('Attempting reconnection in 5 seconds...');
    setTimeout(() => {
        Ws = connectWebSocket();
    }, 2500);
}

function formatDateTime2(dateTimeString) {
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

function openNotificationsTab(evt, tabName) {
    const tabcontent = document.getElementsByClassName("notifications-tabcontent");
    for (let i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    const tablinks = document.getElementsByClassName("notifications-tablinks");
    for (let i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";

    // Get the display name from the data attribute
    const displayName = evt.currentTarget.getAttribute('data-display-name');
    document.getElementById('notificationsHeader').textContent = displayName;

    filterNotifications(tabName);
}

function filterNotifications(tabName) {
    const notifications = JSON.parse(localStorage.getItem('notifications')) || [];
    // Get the active tab and its display name
    const activeTab = document.querySelector('.notifications-tablinks.active');
    const displayName = activeTab ? activeTab.getAttribute('data-display-name') : 'All Notifications';
    
    // Set the header text without using the if-else conditions below
    const notificationsHeader = document.getElementById('notificationsHeader');
    if (notificationsHeader) {
        notificationsHeader.textContent = displayName;
    }

    let filteredNotifications = [];
    const counts = {
        all: 0,
        pending: 0,
        approved: 0,
        rejected: 0,
        restored: 0,
        archived: 0,
        deleted: 0,
        edited: 0,
        trashed: 0,
    };

    notifications.forEach(notification => {
        const notificationDiv = createNotificationDiv(notification);
        if (notificationDiv) {
            counts.all++;
            
            if (notificationDiv.classList.contains('pending-notification')) counts.pending++;
            else if (notificationDiv.classList.contains('approved-notification')) counts.approved++;
            else if (notificationDiv.classList.contains('rejected-notification')) counts.rejected++;
            else if (notificationDiv.classList.contains('restored-notification')) counts.restored++;
            else if (notificationDiv.classList.contains('archived-notification')) counts.archived++;
            else if (notificationDiv.classList.contains('deleted-notification')) counts.deleted++;
            else if (notificationDiv.classList.contains('edited-notification')) counts.edited++;
            else if (notificationDiv.classList.contains('trashed-notification')) counts.trashed++;

            switch (tabName) {
                case 'allNotificationsList':
                    filteredNotifications.push(notification);
                    break;
                case 'pendingNotificationsList':
                    if (notificationDiv.classList.contains('pending-notification')) filteredNotifications.push(notification);
                    break;
                case 'approvedNotificationsList':
                    if (notificationDiv.classList.contains('approved-notification')) filteredNotifications.push(notification);
                    break;
                case 'rejectedNotificationsList':
                    if (notificationDiv.classList.contains('rejected-notification')) filteredNotifications.push(notification);
                    break;
                case 'restoredNotificationsList':
                    if (notificationDiv.classList.contains('restored-notification')) filteredNotifications.push(notification);
                    break;
                case 'archivedNotificationsList':
                    if (notificationDiv.classList.contains('archived-notification')) filteredNotifications.push(notification);
                    break;
                case 'deletedNotificationsList':
                    if (notificationDiv.classList.contains('deleted-notification')) filteredNotifications.push(notification);
                    break;
                case 'updatedNotificationsList':
                    if (notificationDiv.classList.contains('edited-notification')) filteredNotifications.push(notification);
                    break;
                case 'trashedNotificationsList':
                    if (notificationDiv.classList.contains('trashed-notification')) filteredNotifications.push(notification);
                    break;
            }
        }
    });

    updateNotificationsUI(filteredNotifications, tabName);
    updateTabCounts(counts);
}

function updateTabCounts(counts) {
    // Update tab counts if they exist
    const allCount = document.querySelector('.all-notifications-count');
    if (allCount) allCount.textContent = `(${counts.all})`;
    
    const pendingCount = document.querySelector('.pending-notifications-count');
    if (pendingCount) pendingCount.textContent = `(${counts.pending})`;
    
    const approvedCount = document.querySelector('.approved-notifications-count');
    if (approvedCount) approvedCount.textContent = `(${counts.approved})`;
    
    const rejectedCount = document.querySelector('.rejected-notifications-count');
    if (rejectedCount) rejectedCount.textContent = `(${counts.rejected})`;
    
    const restoredCount = document.querySelector('.restored-notifications-count');
    if (restoredCount) restoredCount.textContent = `(${counts.restored})`;
    
    const archivedCount = document.querySelector('.archived-notifications-count');
    if (archivedCount) archivedCount.textContent = `(${counts.archived})`;
    
    const deletedCount = document.querySelector('.deleted-notifications-count');
    if (deletedCount) deletedCount.textContent = `(${counts.deleted})`;
    
    const updatedCount = document.querySelector('.updated-notifications-count');
    if (updatedCount) updatedCount.textContent = `(${counts.edited})`;

    const trashedCount = document.querySelector('.trashed-notifications-count');
    if (trashedCount) trashedCount.textContent = `(${counts.trashed})`;
    
    // Always update the all-notifications-count-2 in the top header
    const allNotificationsCount2 = document.querySelector('.all-notifications-count-2');
    const allNotificationsCountMobile = document.querySelector('.all-notifications-count-mobile');
    if (allNotificationsCount2) {
        allNotificationsCount2.textContent = `(${counts.all})`;
    }
    if (allNotificationsCountMobile) {
        allNotificationsCountMobile.textContent = `(${counts.all})`;
    }
}

function updateNotificationCount(count) {
    const notificationCount = document.getElementById('notificationCount');
    const notificationCountMobile = document.getElementById('notificationCountMobile');
    if (count > 0) {
        notificationCount.style.display = 'block';
        notificationCountMobile.style.display = 'block';
        notificationCount.textContent = count;
        notificationCountMobile.textContent = count;
    } else {
        notificationCount.style.display = 'none';
        notificationCountMobile.style.display = 'none';
    }
}

function fetchNotifications() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            localStorage.setItem('notifications', JSON.stringify(data)); // Store notifications
            updateNotificationsUI(data);
            filterNotifications('allNotificationsList'); // This will update all counts, including all-notifications-count-2
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function fetchNotificationCount() {
    fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            updateNotificationCount(data.count);
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function formatRelativeTime(dateTimeString) {
    const now = new Date();
    const past = new Date(dateTimeString);
    const diffInSeconds = Math.floor((now - past) / 1000);

    if (diffInSeconds == 0) {
        return "Just now";
    } else if (diffInSeconds < 60) {
        return `${diffInSeconds}s`;
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes}m`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours}h`;
    } else {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days}d`;
    }
}

function toggleNotificationsDropdown(isMobile = false) {
    var notificationsDropdown = document.getElementById(isMobile ? "notificationsDropdownMobile" : "notificationsDropdown");
    var triangle = document.querySelector(isMobile ? '.triangle-mobile' : '.triangle');
    const isVisible = notificationsDropdown.style.display === 'block';

    if (!isVisible) {
        notificationsDropdown.style.display = 'block';
        triangle.style.display = 'block'; // Show the triangle
        resetNotificationCount(); // Reset the notification count
    } else {
        notificationsDropdown.style.display = 'none';
        triangle.style.display = 'none'; // Hide the triangle when closing the dropdown
    }
}

function resetNotificationCount() {
    Ws.send(JSON.stringify({
        action: 'reset_notification_count',
        user_id: user_id
    }));
}

function updateNotificationsUI(notifications, tabName) {
    const notificationsContainer = document.getElementById('notificationsContainer');
    const notificationsContainerMobile = document.getElementById('notificationsContainerMobile');
    const tabContainer = document.getElementById(tabName);
    
    // Get all notifications from localStorage
    const allNotifications = JSON.parse(localStorage.getItem('notifications')) || [];
    
    // Update the main notifications container with all notifications
    if (notificationsContainer) {
        notificationsContainer.innerHTML = '';
        allNotifications.forEach(notification => {
            const notificationDiv = createNotificationDiv(notification);
            if (notificationDiv) {
                notificationsContainer.appendChild(notificationDiv.cloneNode(true));
            }
        });
    }

    if (notificationsContainerMobile) {
        notificationsContainerMobile.innerHTML = '';
        allNotifications.forEach(notification => {
            const notificationDiv = createNotificationDiv(notification);
            if (notificationDiv) {
                notificationsContainerMobile.appendChild(notificationDiv.cloneNode(true));
            }
        });
    }

    // Update the tab-specific container
    if (tabContainer) {
        tabContainer.innerHTML = '';
        if (notifications.length === 0) {
            tabContainer.innerHTML = '<p>No notifications in this category.</p>';
        } else {
            notifications.forEach(notification => {
                const notificationDiv = createNotificationDiv(notification);
                if (notificationDiv) {
                    tabContainer.appendChild(notificationDiv);
                }
            });
        }
    }
}

function toSentenceCase(str) {
    // Split by space and handle each word
    return str.toLowerCase().split(' ').map(word => {
        // Special handling for 'of' to keep it lowercase
        if (word === 'of') return word;
        // Capitalize first letter of other words
        return word.charAt(0).toUpperCase() + word.slice(1);
    }).join(' ');
}

function createNotificationDiv(notification) {
    const notificationDiv = document.createElement('div');
    notificationDiv.className = 'notification';

    // Add data attributes here
    notificationDiv.setAttribute('data-content-id', notification.content_id);
    notificationDiv.setAttribute('data-content-type', notification.content_type);

    // Convert department to sentence case whenever it's used
    if (notification.action_user_department) {
        notification.action_user_department = toSentenceCase(notification.action_user_department);
    }

    let content = '';

    if (userType === 'Super Admin' || userType === 'Admin') {
        if (notification.notification_type === 'user_registration' && notification.status == 'pending') {
            notificationDiv.classList.add('pending-notification');
            content = `
                <div class="notification-details">
                    <p class="user-registration-notification">${notification.action_user_name} wants to join as a ${notification.action_user_type} in ${notification.action_user_department} department.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
                <div class="notification-buttons-non-deletable">
                    <button type="button" class="green-button" style="margin: 0;" onclick="showConfirmApproveModal(${notification.user_id}, '${notification.action_user_email}', '${notification.action_user_name}', '${notification.action_user_type}', '${full_name}', '${notification.action_user_department}', ${user_id})">Approve</button>
                    <button type="button" class="red-button" style="margin: 0;" onclick="showConfirmRejectModal(${notification.user_id}, '${notification.action_user_email}', '${notification.action_user_name}', '${notification.action_user_type}', '${full_name}', '${notification.action_user_department}', ${user_id})">Reject</button>
                </div>
            `;
        }

        if (notification.notification_type === 'content_post' && notification.status == 'pending') {
            notificationDiv.classList.add('pending-notification');
            content = `
                <div class="notification-details">
                    <p class="content-post-notification">${notification.action_user_name} wants to post ${notification.content_type} on the TV: ${notification.content_tv_name}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
                <div class="notification-buttons-non-deletable">
                    <button type="button" class="green-button" style="margin: 0;" onclick="showConfirmApproveContentModal(${notification.content_id}, '${notification.content_type}', '${notification.action_user_name}', '${notification.action_user_department}', ${notification.user_id}, ${notification.content_tv_id}, '${full_name}', ${user_id})">Approve</button>
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="showConfirmRejectContentModal(${notification.content_id}, '${notification.content_type}', '${notification.action_user_name}', '${notification.action_user_department}', ${notification.user_id}, ${notification.content_tv_id}, '${full_name}', ${user_id})">Reject</button>
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="window.location.href='view_pending_contents.php?pageid=ViewPendingContents&userId=${user_id}&userType=${userType}'">View</button>
                </div>
            `;
        }
    } else if (userType === 'Student' || userType === 'Faculty' || userType === 'Staff') {
        if (notification.notification_type === 'content_post' && notification.status == 'pending' && notification.user_id == user_id) {
            notificationDiv.classList.add('pending-notification');
            content = `
                <div class="notification-details">
                    <p class="content-post-notification"><i class="fa fa-clock-o"></i> Your ${notification.content_type} is pending for approval to be posted on TV: ${notification.content_tv_name}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                    <div class="notification-buttons-non-deletable">
                        <button type="button" class="green-button" style="margin: 0;" onclick="window.location.href='view_pending_contents.php?pageid=ViewPendingContents&userId=${user_id}&userType=${userType}'">View</button>
                    </div>
                </div>
            `;
        } 
    }

    if (notification.notification_type === 'user_approved' && notification.status == 'approved' && notification.user_id == user_id) {
        if (notification.evaluator_name == full_name) {
            notificationDiv.classList.add('approved-notification');
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You approved ${notification.action_user_name}'s registration in ${notification.action_user_department} department.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
        if (notification.evaluator_name != full_name) {
            notificationDiv.classList.add('approved-notification');
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">${notification.evaluator_name} approved ${notification.action_user_name}'s registration in ${notification.action_user_department} department.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    }

    if (notification.notification_type === 'user_approved_by_admin' && notification.status == 'approved' && notification.user_id == user_id) {
        notificationDiv.classList.add('approved-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your registration has been approved by  ${notification.evaluator_name}.</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    }

    if (notification.notification_type === 'user_rejected' && notification.status == 'rejected' && notification.user_id == user_id) {
        if (notification.evaluator_name == full_name) {
            notificationDiv.classList.add('rejected-notification');
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You rejected ${notification.action_user_name}'s registration in ${notification.action_user_department} department.</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.evaluator_name != full_name) {
            notificationDiv.classList.add('rejected-notification');
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">${notification.evaluator_name} rejected ${notification.action_user_name}'s registration in ${notification.action_user_department} department.</p>
                    <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    }
    
    if (notification.notification_type === 'user_rejected_by_admin' && notification.status == 'rejected' && notification.user_id == user_id) {
        notificationDiv.classList.add('rejected-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your registration has been rejected by  ${notification.evaluator_name}.</p>
                <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    }

    if (notification.notification_type === 'user_edited' && notification.status == 'edited' && notification.user_id == user_id) {
        if (notification.evaluator_name == full_name && notification.action_user_type != null && notification.action_user_department == null) {
            notificationDiv.classList.add('edited-notification');
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You updated ${notification.action_user_name}'s role to ${notification.action_user_type}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.evaluator_name == full_name && notification.action_user_type == null && notification.action_user_department != null) {
            notificationDiv.classList.add('edited-notification');
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You updated ${notification.action_user_name}'s department to ${notification.action_user_department}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.evaluator_name == full_name && notification.action_user_type != null && notification.action_user_department != null) {
            notificationDiv.classList.add('edited-notification');
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You updated ${notification.action_user_name}'s role to ${notification.action_user_type} and department to ${notification.action_user_department}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.evaluator_name != full_name && notification.action_user_type != null && notification.action_user_department == null) {
            notificationDiv.classList.add('edited-notification');
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">${notification.evaluator_name} updated ${notification.action_user_name}'s role to ${notification.action_user_type}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.evaluator_name != full_name && notification.action_user_type == null && notification.action_user_department != null) {
            notificationDiv.classList.add('edited-notification');
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">${notification.evaluator_name} updated ${notification.action_user_name}'s department to ${notification.action_user_department}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.evaluator_name != full_name && notification.action_user_type != null && notification.action_user_department != null) {
            notificationDiv.classList.add('edited-notification');
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">${notification.evaluator_name} updated ${notification.action_user_name}'s role to ${notification.action_user_type} and department to ${notification.action_user_department}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    }

    if (notification.notification_type === 'user_edited_by_admin' && notification.status == 'edited' && notification.user_id == user_id) {
        if (notification.action_user_type != null && notification.action_user_department == null) {
            notificationDiv.classList.add('edited-notification');
            content = `
                <div class="notification-details">  
                    <p class="content-approved-notification">Your role has been updated to ${notification.action_user_type} by ${notification.evaluator_name}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.action_user_type == null && notification.action_user_department != null) {
            notificationDiv.classList.add('edited-notification');
            content = `
                <div class="notification-details">  
                    <p class="content-approved-notification">Your department has been updated to ${notification.action_user_department} by ${notification.evaluator_name}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.action_user_type != null && notification.action_user_department != null) {
            notificationDiv.classList.add('edited-notification');
            content = `
                <div class="notification-details">  
                    <p class="content-approved-notification">Your role has been updated to ${notification.action_user_type} and department to ${notification.action_user_department} by ${notification.evaluator_name} .</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    }
    
    if (notification.notification_type === 'user_deleted' && notification.status == 'deleted' && notification.user_id == user_id) {
        if (notification.evaluator_name == full_name) {
            notificationDiv.classList.add('deleted-notification');
            content = `
                <div class="notification-details">
                    <p class="user-deleted-notification">You deleted ${notification.action_user_name}'s account from ${notification.action_user_department} department.</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.evaluator_name != full_name) {
            notificationDiv.classList.add('deleted-notification');
            content = `
                <div class="notification-details">
                    <p class="user-deleted-notification">${notification.evaluator_name} deleted ${notification.action_user_name}'s account from ${notification.action_user_department} department.</p>
                    <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    } 

    if (notification.notification_type === 'user_deleted_by_system' && notification.status == 'deleted' && notification.user_id == user_id) {
        notificationDiv.classList.add('deleted-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">The System deleted ${notification.action_user_name}'s account from ${notification.action_user_department} department due to inactivity.</p>
                <p class="evaluator-message-notification">System-generated Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    }
    
    if (notification.notification_type === 'user_added' && notification.status == 'approved' && notification.user_id == user_id) {
        if (notification.evaluator_name == full_name) {
            notificationDiv.classList.add('approved-notification');
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You added a new user</p>
                    <small style="color: #6E6E6E">
                        <p class="user-approved-notification">Name: ${notification.action_user_name}</p>
                        <p class="user-approved-notification">Role: ${notification.action_user_type}</p>
                        <p class="user-approved-notification">Department: ${notification.action_user_department}</p>
                    </small>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
        
        if (notification.evaluator_name != full_name) {
            notificationDiv.classList.add('approved-notification');
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">${notification.evaluator_name} added a new user</p>
                    <small style="color: #6E6E6E">
                        <p class="user-approved-notification">Name: ${notification.action_user_name}</p>
                        <p class="user-approved-notification">Role: ${notification.action_user_type}</p>
                        <p class="user-approved-notification">Department: ${notification.action_user_department}</p>
                    </small>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    } 
    
    if (notification.notification_type === 'user_added_by_admin' && notification.status == 'approved' && notification.user_id == user_id) {
        if (notification.action_user_name == full_name) {
            notificationDiv.classList.add('approved-notification');
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You have been added by ${notification.evaluator_name} as ${notification.action_user_type} in ${notification.action_user_department} department.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    } 
    
    if (notification.notification_type === 'content_post' && notification.user_id == user_id) {
        if (notification.status == 'approved' && notification.action_user_name == full_name) {
            notificationDiv.classList.add('approved-notification');
            content = `
                <div class="notification-details">
                    <p class="content-post-notification">You posted ${notification.content_type} in the TV: ${notification.content_tv_name}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.status == 'approved' && notification.action_user_name != full_name) {
            notificationDiv.classList.add('approved-notification');
            content = `
                <div class="notification-details">
                    <p class="content-post-notification">${notification.action_user_name} (${notification.action_user_type}) posted ${notification.content_type} in the TV: ${notification.content_tv_name}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    } 
    
    if (notification.notification_type === 'content_approved' && notification.status == 'approved' && notification.user_id == user_id) {
        if (notification.evaluator_name == full_name) {
            notificationDiv.classList.add('approved-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You approved ${notification.action_user_name}'s ${notification.content_type} on the TV: ${notification.content_tv_name}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                    <div class="notification-buttons-deletable">
                        <button type="button" class="green-button" style="margin: 0;" onclick="window.location.href='view_approved_contents.php?pageid=ViewApprovedContents&userId=${user_id}&userType=${userType}'">View</button>
                    </div>
                </div>
            `;
        }

        if (notification.evaluator_name != full_name) {
            notificationDiv.classList.add('approved-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">${notification.evaluator_name} approved ${notification.action_user_name}'s ${notification.content_type} on the TV: ${notification.content_tv_name}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                    <div class="notification-buttons-deletable">
                        <button type="button" class="green-button" style="margin: 0;" onclick="window.location.href='view_approved_contents.php?pageid=ViewApprovedContents&userId=${user_id}&userType=${userType}'">View</button>
                    </div>
                </div>
            `;
        }
    } 
    
    if (notification.notification_type === 'content_approved_by_admin' && notification.status == 'approved' && notification.user_id == user_id) {
        notificationDiv.classList.add('approved-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your ${notification.content_type} has been approved by ${notification.evaluator_name} to be posted on TV: ${notification.content_tv_name}.</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                <div class="notification-buttons-deletable">
                    <button type="button" class="green-button" style="margin: 0;" onclick="window.location.href='view_approved_contents.php?pageid=ViewApprovedContents&userId=${user_id}&userType=${userType}'">View</button>
                </div>
            </div>
        `;
    } 
    
    if (notification.notification_type === 'content_rejected' && notification.status == 'rejected' && notification.user_id == user_id) {
        if (notification.evaluator_name == full_name) {
            notificationDiv.classList.add('rejected-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You rejected ${notification.action_user_name}'s ${notification.content_type} to be posted in the TV: ${notification.content_tv_name}.</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                    <div class="notification-buttons-deletable">
                        <button type="button" class="green-button" style="margin: 0;" onclick="window.location.href='view_rejected_contents.php?pageid=ViewRejectedContents&userId=${user_id}&userType=${userType}'">View</button>
                    </div>
                </div>
            `;
        }

        if (notification.evaluator_name != full_name) {
            notificationDiv.classList.add('rejected-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">${notification.evaluator_name} rejected ${notification.action_user_name}'s ${notification.content_type} to be posted in the TV: ${notification.content_tv_name}.</p>
                    <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                    <div class="notification-buttons-deletable">
                        <button type="button" class="green-button" style="margin: 0;" onclick="window.location.href='view_rejected_contents.php?pageid=ViewRejectedContents&userId=${user_id}&userType=${userType}'">View</button>
                    </div>
                </div>
            `;
        }
    } 

    if (notification.notification_type === 'content_rejected_by_admin' && notification.status == 'rejected' && notification.user_id == user_id) {
        notificationDiv.classList.add('rejected-notification');
        const queryParams = new URLSearchParams({
            [`${notification.content_type}_id`]: notification.content_id,
            type: notification.content_type,
            tv_id: notification.content_tv_id,
            action: 'edit'
        }).toString();
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your ${notification.content_type} has been rejected by ${notification.evaluator_name}.</p>
                <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                <div class="notification-buttons-non-deletable">
                    <button type="button" class="green-button" style="margin: 0;" onclick="window.location.href='view_rejected_contents.php?pageid=ViewRejectedContents&userId=${user_id}&userType=${userType}'">View</button>
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="window.location.href = 'form_${notification.content_type}.php?${queryParams}'">Edit</button>
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="showConfirmDeleteContentModal(${notification.content_id}, '${notification.content_type}', ${notification.user_id}, '${full_name}', '${notification.action_user_name}', '${notification.action_user_type}', '${notification.action_user_department}', ${notification.content_tv_id})">Delete</button>
                </div>
            </div>
        `;
    }
    
    if (notification.notification_type === 'content_deleted' && notification.status == 'deleted' && notification.user_id == user_id) {
        if (notification.action_user_name == full_name && notification.evaluator_name == full_name && notification.evaluator_name != "System") {
            notificationDiv.classList.add('deleted-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You deleted your ${notification.content_type} in the TV: ${notification.content_tv_name}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        } 

        if (notification.action_user_name != full_name && notification.evaluator_name == full_name && notification.evaluator_name != "System") {
            notificationDiv.classList.add('deleted-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You deleted ${notification.action_user_name}'s ${notification.content_type} in the TV: ${notification.content_tv_name}.</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.action_user_name != full_name && notification.evaluator_name != full_name && notification.evaluator_name != notification.action_user_name && notification.evaluator_name != "System") {
            notificationDiv.classList.add('deleted-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">${notification.evaluator_name} deleted ${notification.action_user_name}'s ${notification.content_type} in the TV: ${notification.content_tv_name}.</p>
                    <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.action_user_name != full_name && notification.evaluator_name != full_name && notification.evaluator_name != notification.action_user_name && notification.evaluator_name == "System") {
            notificationDiv.classList.add('trashed-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">The System deleted ${notification.action_user_name}'s ${notification.content_type}.</p>
                    <p class="evaluator-message-notification">System-generated Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.action_user_name != full_name && notification.evaluator_name != full_name && notification.evaluator_name == notification.action_user_name && notification.evaluator_name != "System") {
            notificationDiv.classList.add('deleted-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">${notification.evaluator_name} deleted his/her ${notification.content_type} in the TV: ${notification.content_tv_name}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    } 
    
    if (notification.notification_type === 'content_deleted_by_admin' && notification.status == 'deleted' && notification.user_id == user_id) {
        notificationDiv.classList.add('deleted-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your ${notification.content_type} has been deleted by ${notification.evaluator_name} in the TV: ${notification.content_tv_name}.</p>
                <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    }

    if (notification.notification_type === 'content_deleted_by_system' && notification.status == 'deleted' && notification.user_id == user_id && notification.evaluator_name == "System") {
        notificationDiv.classList.add('deleted-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your ${notification.content_type} has been moved to Trash by the System.</p>
                <p class="evaluator-message-notification">System-generated Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    }

    if (notification.notification_type === 'rejected_content_deleted_by_system' && notification.status == 'deleted' && notification.user_id == user_id && notification.evaluator_name == "System") {
        notificationDiv.classList.add('deleted-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your rejected ${notification.content_type} has been permanently deleted by the System.</p>
                <p class="evaluator-message-notification">System-generated Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    }

    if (notification.notification_type === 'pending_content_deleted_by_system' && notification.status == 'deleted' && notification.user_id == user_id && notification.evaluator_name == "System") {
        notificationDiv.classList.add('deleted-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your pending ${notification.content_type} has been permanently deleted by the System.</p>
                <p class="evaluator-message-notification">System-generated Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    }

    if (notification.notification_type === 'trash_content_deleted_by_system' && notification.status == 'deleted' && notification.user_id == user_id && notification.evaluator_name == "System") {
        notificationDiv.classList.add('deleted-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your ${notification.content_type} in Trash has been permanently deleted by the System.</p>
                <p class="evaluator-message-notification">System-generated Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    }
    
    
    
    
    
    if (notification.notification_type === 'content_archived' && notification.status == 'archived' && notification.user_id == user_id) {
        if (notification.action_user_name == full_name && notification.evaluator_name == full_name) {
            notificationDiv.classList.add('archived-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You archived your ${notification.content_type}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        } 

        if (notification.action_user_name != full_name && notification.evaluator_name == full_name) {
            notificationDiv.classList.add('archived-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You archived ${notification.action_user_name}'s ${notification.content_type}.</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.action_user_name != full_name && notification.evaluator_name != full_name && notification.evaluator_name != notification.action_user_name) {
            notificationDiv.classList.add('archived-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">${notification.evaluator_name} archived ${notification.action_user_name}'s ${notification.content_type} in the TV: ${notification.content_tv_name}.</p>
                    <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.action_user_name != full_name && notification.evaluator_name != full_name && notification.evaluator_name == notification.action_user_name) {
            notificationDiv.classList.add('archived-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">${notification.evaluator_name} archived his/her ${notification.content_type} in the TV: ${notification.content_tv_name}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    } 
    
    if (notification.notification_type === 'content_archived_by_admin' && notification.status == 'archived' && notification.user_id == user_id) {
        notificationDiv.classList.add('archived-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your ${notification.content_type} in TV: ${notification.content_tv_name} has been archived by ${notification.evaluator_name}.</p>
                <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    }

    if (notification.notification_type === 'content_trashed' && notification.status == 'trashed' && notification.user_id == user_id) {
        if (notification.action_user_name == full_name && notification.evaluator_name == full_name && notification.evaluator_name != "System") {
            notificationDiv.classList.add('trashed-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You moved your ${notification.content_type} to Trash.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        } 

        if (notification.action_user_name != full_name && notification.evaluator_name == full_name && notification.evaluator_name != "System") {
            notificationDiv.classList.add('trashed-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You moved ${notification.action_user_name}'s ${notification.content_type} to Trash.</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.action_user_name != full_name && notification.evaluator_name != full_name && notification.evaluator_name != notification.action_user_name && notification.evaluator_name != "System") {
            notificationDiv.classList.add('trashed-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">${notification.evaluator_name} moved ${notification.action_user_name}'s ${notification.content_type} to Trash.</p>
                    <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.action_user_name != full_name && notification.evaluator_name != full_name && notification.evaluator_name != notification.action_user_name && notification.evaluator_name == "System") {
            notificationDiv.classList.add('trashed-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">The System moved ${notification.action_user_name}'s ${notification.content_type} to Trash.</p>
                    <p class="evaluator-message-notification">System-generated Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.action_user_name != full_name && notification.evaluator_name != full_name && notification.evaluator_name == notification.action_user_name && notification.evaluator_name != "System") {
            notificationDiv.classList.add('trashed-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">${notification.evaluator_name} moved his/her ${notification.content_type} to Trash.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    } 
    
    if (notification.notification_type === 'content_trashed_by_admin' && notification.status == 'trashed' && notification.user_id == user_id) {
        notificationDiv.classList.add('trashed-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your ${notification.content_type} has been moved to Trash by ${notification.evaluator_name}.</p>
                <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    }

    if (notification.notification_type === 'content_trashed_by_system' && notification.status == 'trashed' && notification.user_id == user_id && notification.evaluator_name == "System") {
        notificationDiv.classList.add('trashed-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your ${notification.content_type} has been moved to Trash by the System.</p>
                <p class="evaluator-message-notification">System-generated Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    }

    // CONTENT RESTORED
    if (notification.notification_type === 'content_restored' && notification.status == 'restored' && notification.user_id == user_id) {
        if (notification.action_user_name == full_name && notification.evaluator_name == full_name) {
            notificationDiv.classList.add('restored-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You restored your ${notification.content_type} in the TV: ${notification.content_tv_name}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        } 

        if (notification.action_user_name != full_name && notification.evaluator_name == full_name) {
            notificationDiv.classList.add('restored-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You restored ${notification.action_user_name}'s ${notification.content_type} in the TV: ${notification.content_tv_name}.</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.action_user_name != full_name && notification.evaluator_name != full_name && notification.evaluator_name != notification.action_user_name) {
            notificationDiv.classList.add('restored-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">${notification.evaluator_name} restored ${notification.action_user_name}'s ${notification.content_type} in the TV: ${notification.content_tv_name}.</p>
                    <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.action_user_name != full_name && notification.evaluator_name != full_name && notification.evaluator_name == notification.action_user_name) {
            notificationDiv.classList.add('restored-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">${notification.evaluator_name} restored his/her ${notification.content_type} in the TV: ${notification.content_tv_name}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    } 
    
    if (notification.notification_type === 'content_restored_by_admin' && notification.status == 'restored' && notification.user_id == user_id) {
        notificationDiv.classList.add('restored-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your ${notification.content_type} has been restored by ${notification.evaluator_name} in the TV: ${notification.content_tv_name}.</p>
                <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    }
    
    // If content is still empty, it means no condition was met
    if (content == '') {
        return null;
    }

    // Check if the content includes notification-buttons
    if (!content.includes('notification-buttons-non-deletable')) {
        // Add delete button only if there are no notification-buttons
        content += `<button type="button" class="delete-notification" onclick="deleteNotification(${notification.notification_id})"><i class="fa fa-times-circle" aria-hidden="true"></i></button>`;
    }

    notificationDiv.innerHTML = content;
    return notificationDiv;
}

function viewTvContent(tvId) {
    window.location.href = `tv_contents.php?tvId=${tvId}&isIframe=true`;
}

function showConfirmApproveModal(userId, email, fullName, userType, evaluatorName, department, evaluatorId) {
    const modal = document.getElementById('confirmApproveUserModal');
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close" onclick="closeModal('confirmApproveUser')"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <h1 style="text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-thumbs-up"></i> Approve User</h1>
            <p style="text-align: left; font-weight: 500; font-size: 16px; color: #6E6E6E;">Proceed to approve this user?</p>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" style="margin-right: 5px" onclick="closeModal('confirmApproveUser')">Cancel</button>
                <button type="button" class="green-button" style="margin-right: 0" onclick="approveUser(${userId}, '${email}', '${fullName}', '${userType}', '${evaluatorName}', '${department}', ${evaluatorId})">Yes, Approve</button>
            </div>
        </div>
    `;
    modal.style.display = 'flex';
}

function showConfirmRejectModal(userId, email, fullName, userType, evaluatorName, department, evaluatorId) {
    const modal = document.getElementById('confirmRejectUserModal');
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close" onclick="closeModal('confirmRejectUser')"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <h1 style="text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-thumbs-down"></i> Reject User</h1>
            <p style="text-align: left; font-weight: 500; font-size: 16px; color: #6E6E6E;">Proceed to reject this user?</p>
            <br>
            <div class="floating-label-container">
                <textarea name="evaluator_message_reject_user" id="evaluator_message_reject_user" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
                <label for="evaluator_message_reject_user" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Reason (required)</label>
            </div>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" style="margin-right: 5px" onclick="closeModal('confirmRejectUser')">Cancel</button>
                <button type="button" class="red-button" style="margin-right: 0" onclick="rejectUser(${userId}, '${email}', '${fullName}', '${userType}', '${evaluatorName}', '${department}', ${evaluatorId})">Yes, Reject</button>
            </div>
        </div>
    `;
    modal.style.display = 'flex';
}

function showConfirmApproveContentModal(contentId, contentType, fullName, department, userId, contentTvId, evaluatorName, evaluatorId) {
    const modal = document.getElementById('confirmApproveContentModal');
    modal.innerHTML = `
        <div class="modal-content" style="padding: 15px;">
            <span class="close" onclick="closeModal('confirmApproveContent')"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <h1 style="text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-thumbs-up"></i> Approve Content</h1>
            <p style="text-align: left; font-weight: 500; font-size: 16px; color: #6E6E6E;">Proceed to approve this ${contentType}?</p>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" style="margin-right: 5px" onclick="closeModal('confirmApproveContent')">Cancel</button>
                <button type="button" class="green-button" style="margin-right: 0" onclick="approveContent(${contentId}, '${contentType}', '${fullName}', '${department}', ${userId}, ${contentTvId}, '${evaluatorName}', ${evaluatorId})">Yes, Approve</button>
            </div>
        </div>
    `;
    modal.style.display = 'flex';
}

function showConfirmRejectContentModal(contentId, contentType, fullName, department, userId, contentTvId, evaluatorName, evaluatorId) {
    const modal = document.getElementById('confirmRejectContentModal');
    modal.innerHTML = `
        <div class="modal-content" style="padding: 15px;">
            <span class="close" onclick="closeModal('confirmRejectContent')"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <h1 style="text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-thumbs-down"></i> Reject Content</h1>
            <p style="text-align: left; font-weight: 500; font-size: 16px; color: #6E6E6E;">Proceed to reject this ${contentType}?</p>
            <br>
            <div class="floating-label-container">
                <textarea name="evaluator_message_reject_content" id="evaluator_message_reject_content" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
                <label for="evaluator_message_reject_content" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Reason (required)</label>
            </div>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" style="margin-right: 5px" onclick="closeModal('confirmRejectContent')">Cancel</button>
                <button type="button" class="red-button" style="margin-right: 0" onclick="rejectContent(${contentId}, '${contentType}', '${fullName}', '${department}', ${userId}, ${contentTvId}, '${evaluatorName}', ${evaluatorId})">Yes, Reject</button>
            </div>
        </div>
    `;
    modal.style.display = 'flex';
}

function showConfirmDeleteContentModal(contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) {
    const confirmDeleteContentModal = document.getElementById('confirmDeleteContentModal');

    const evaluatorMessageHtml = user_id != authorId ? `
        <div class="floating-label-container">
            <textarea name="evaluator_message_delete_content_notification" id="evaluator_message_delete_content_notification" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
            <label for="evaluator_message_delete_content_notification" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Reason (required)</label>
        </div>
        <br>
    ` : '';

    confirmDeleteContentModal.innerHTML = `
        <div class="modal-content">
            <span class="close" onclick="closeModal('confirmDeleteContent')"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <h1 style="text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-trash"></i> Delete Content</h1>
            <p style="text-align: left; font-weight: 500; font-size: 16px; color: #6E6E6E;">Proceed to delete this ${contentType}?</p>
            <br>
            ${evaluatorMessageHtml}
            <div style="text-align: right;">
                <button type="button" class="grey-button" style="margin-right: 5px" onclick="closeModal('confirmDeleteContent')">Cancel</button>
                <button type="button" class="red-button" style="margin-right: 0" onclick="deleteContent(${contentId}, '${contentType}', ${authorId}, '${loggedInFullName}', '${authorName}', '${authorUserType}', '${authorDepartment}', ${tvId})">Yes, Delete</button>
            </div>
        </div>
    `;
    confirmDeleteContentModal.style.display = 'flex';
}

function approveUser(userId, email, fullName, userType, evaluatorName, department, evaluatorId) {
    sendApprovedUserEmail(email, fullName, department, evaluatorName);
    Ws.send(JSON.stringify({
        action: 'approve_user',
        user_id: userId,
        email: email,
        full_name: fullName,
        user_type: userType,
        evaluator_name: evaluatorName,
        department: department,
        evaluator_id: evaluatorId
    }));
    closeModal('confirmApproveUser');
}

function rejectUser(userId, email, fullName, userType, evaluatorName, department, evaluatorId) {
    const evaluatorMessage = document.getElementById('evaluator_message_reject_user').value;
    if (!evaluatorMessage) {
        alert('Evaluator message is required.');
        return;
    }
    sendRejectedUserEmail(email, fullName, department, evaluatorName, evaluatorMessage);
    Ws.send(JSON.stringify({
        action: 'reject_user',
        user_id: userId,
        email: email,
        full_name: fullName,
        user_type: userType,
        evaluator_name: evaluatorName,
        department: department,
        evaluator_id: evaluatorId,
        evaluator_message: evaluatorMessage
    }));
    closeModal('confirmRejectUser');
}

function approveContent(contentId, contentType, fullName, department, userId, contentTvId, evaluatorName, evaluatorId) {
    Ws.send(JSON.stringify({
        action: 'approve_post',
        content_id: contentId,
        content_type: contentType,
        user_id: userId,
        full_name: fullName,
        department: department,
        content_tv_id: contentTvId,
        evaluator_name: evaluatorName,
        evaluator_id: evaluatorId
    }));
    closeModal('confirmApproveContent');
}

function rejectContent(contentId, contentType, fullName, department, userId, contentTvId, evaluatorName, evaluatorId) {
    const evaluatorMessage = document.getElementById('evaluator_message_reject_content').value;
    if (!evaluatorMessage) {
        alert('Evaluator message is required.');
        return;
    }
    Ws.send(JSON.stringify({
        action: 'reject_post',
        content_id: contentId,
        content_type: contentType,
        user_id: userId,
        full_name: fullName,
        department: department,
        content_tv_id: contentTvId,
        evaluator_name: evaluatorName,
        evaluator_id: evaluatorId, 
        evaluator_message: evaluatorMessage
    }));
    closeModal('confirmRejectContent');
}

function deleteContent(contentId, contentType, authorId, loggedInFullName, authorName, authorUserType, authorDepartment, tvId) {
    const evaluatorMessageElement = document.getElementById('evaluator_message_delete_content_notification');
    let evaluatorMessage = null;

    if (evaluatorMessageElement) {
        evaluatorMessage = evaluatorMessageElement.value;
        if (!evaluatorMessage) {
            alert('Evaluator message is required.');
            return;
        }
    }
    
    Ws.send(JSON.stringify({
        // action: 'delete_content',
        // [`${contentType}_id`]: contentId,
        // type: contentType,
        // user_id: userId,
        // evaluator_name: fullName
        action: 'delete_content',
        type: contentType,
        [`${contentType}_id`]: contentId, // Dynamically set the ID based on the type
        user_id: user_id,
        author_id: authorId,
        author_name: authorName,
        author_user_type: authorUserType,
        author_department: authorDepartment,
        evaluator_name: loggedInFullName,
        tv_id: tvId
    }));
    closeModal('confirmDeleteContent');
}

function sendApprovedUserEmail(email, fullName, department, evaluatorName) {
    fetch('send_approved_user_email.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `email=${encodeURIComponent(email)}&full_name=${encodeURIComponent(fullName)}&department=${encodeURIComponent(department)}&evaluator_name=${encodeURIComponent(evaluatorName)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Approved user email sent successfully');
        } else {
            console.error('Failed to send approved user email:', data.message);
        }
    })
    .catch(error => {
        console.error('Error sending approved user email:', error);
    });
}

function sendRejectedUserEmail(email, fullName, department, evaluatorName, evaluatorMessage) {
    fetch('send_rejected_user_email.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `email=${encodeURIComponent(email)}&full_name=${encodeURIComponent(fullName)}&department=${encodeURIComponent(department)}&evaluator_name=${encodeURIComponent(evaluatorName)}&evaluator_message=${encodeURIComponent(evaluatorMessage)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Rejected user email sent successfully');
        } else {
            console.error('Failed to send rejected user email:', data.message);
        }
    })
    .catch(error => {
        console.error('Error sending rejected user email:', error);
    });
}

function closeModal(modalId) {
    var modal = document.getElementById(modalId + 'Modal');
    modal.style.display = 'none';
}

function deleteNotification(notificationId) {
    // First, remove the notification from localStorage
    const notifications = JSON.parse(localStorage.getItem('notifications')) || [];
    const updatedNotifications = notifications.filter(notification => notification.notification_id !== notificationId);
    localStorage.setItem('notifications', JSON.stringify(updatedNotifications));

    // Get the currently active tab
    const activeTab = document.querySelector('.notifications-tablinks.active');
    const activeTabName = activeTab ? activeTab.textContent.trim().split(' ')[0].toLowerCase() + 'NotificationsList' : 'allNotificationsList';
    
    // Get the display name from the active tab
    const displayName = activeTab ? activeTab.getAttribute('data-display-name') : 'All Notifications';
    
    // Update the UI for the current tab
    filterNotifications(activeTabName);
    
    const notificationsHeader = document.getElementById('notificationsHeader');
    if (notificationsHeader) {
        notificationsHeader.textContent = displayName;
    }

    // Send websocket message
    Ws.send(JSON.stringify({
        action: 'delete_notification',
        notification_id: notificationId
    }));
}

function viewContent(contentId, contentType, userId, fullName) {
    Ws.send(JSON.stringify({
        action: 'view_content',
        content_id: contentId,
        content_type: contentType,
        user_id: userId,
        full_name: fullName
    }));
}

// Function to update archives count
function updateArchivesCount() {
    // First fetch all features
    fetch('database/fetch_feature_info.php?type=all')
        .then(response => response.json())
        .then(features => {
            // Start with default content types
            const contentTypes = ['announcement', 'event', 'news', 'promaterial'];
            
            // Add dynamic features
            if (Array.isArray(features)) {
                features.forEach(feature => {
                    const featureType = feature.type;
                    if (!contentTypes.includes(featureType)) {
                        contentTypes.push(featureType);
                    }
                });
            }

            // Add admin-only content types
            if (userType === 'Admin' || userType === 'Super Admin') {
                if (!contentTypes.includes('peo')) contentTypes.push('peo');
                if (!contentTypes.includes('so')) contentTypes.push('so');
            }

            // Create array of promises for all content type fetches
            const fetchPromises = contentTypes.map(type =>
                fetch(`database/fetch_content.php?type=${type}&archived=true`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(`Error fetching ${type}:`, data.error);
                            return 0;
                        }

                        return data.filter(item => {
                            // Basic archive conditions
                            const basicConditions = item.isArchived === 1 && 
                                item.isTrashed === 0 && 
                                (type === 'peo' || type === 'so' || item.status === 'Approved');

                            // Permission conditions
                            let hasPermission = false;
                            if (userType === 'Super Admin') {
                                hasPermission = true;
                            } else if (userType === 'Admin') {
                                hasPermission = item.author_department === department;
                            } else if (['Student', 'Faculty', 'Staff'].includes(userType)) {
                                hasPermission = parseInt(item[`${type}_author_id`]) === parseInt(user_id);
                            }

                            return basicConditions && hasPermission;
                        }).length;
                    })
                    .catch(error => {
                        console.error(`Error fetching ${type} archives count:`, error);
                        return 0;
                    })
            );

            // Wait for all fetches to complete and sum the counts
            Promise.all(fetchPromises)
                .then(counts => {
                    const totalCount = counts.reduce((sum, count) => sum + count, 0);
                    
                    // Update UI elements
                    const archivesCountElement = document.getElementById('archivesContentCount');
                    let archivesCountButtonElement;
                    
                    if (archivesCountElement) {
                        archivesCountElement.textContent = totalCount > 0 ? totalCount : '0';
                        archivesCountElement.style.display = 'inline';
                    }
                    if (archivesCountButtonElement = document.getElementById('archivedContentButtonCount')) {
                        archivesCountButtonElement.textContent = totalCount > 0 ? totalCount : '0';
                        archivesCountButtonElement.style.display = 'inline';
                    }
                })
                .catch(error => {
                    console.error('Error calculating total archives count:', error);
                });
        })
        .catch(error => {
            console.error('Error fetching features:', error);
        });
}

// Function to fetch archives count
function fetchArchivesCount() {
    updateArchivesCount();
}

// Function to update trash count
function updateTrashCount() {
    // First fetch all features
    fetch('database/fetch_feature_info.php?type=all')
        .then(response => response.json())
        .then(features => {
            // Start with default content types
            const contentTypes = ['announcement', 'event', 'news', 'promaterial'];
            
            // Add dynamic features
            if (Array.isArray(features)) {
                features.forEach(feature => {
                    const featureType = feature.type;
                    if (!contentTypes.includes(featureType)) {
                        contentTypes.push(featureType);
                    }
                });
            }

            // Add admin-only content types
            if (userType === 'Admin' || userType === 'Super Admin') {
                if (!contentTypes.includes('peo')) contentTypes.push('peo');
                if (!contentTypes.includes('so')) contentTypes.push('so');
            }

            // Create array of promises for all content type fetches
            const fetchPromises = contentTypes.map(type =>
                fetch(`database/fetch_content.php?type=${type}&trashed=true`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(`Error fetching ${type}:`, data.error);
                            return 0;
                        }

                        return data.filter(item => {
                            // Basic archive conditions
                            const basicConditions = item.isArchived === 0 && 
                                item.isTrashed === 1 && 
                                (type === 'peo' || type === 'so' || item.status === 'Approved');

                            // Permission conditions
                            let hasPermission = false;
                            if (userType === 'Super Admin') {
                                hasPermission = true;
                            } else if (userType === 'Admin') {
                                hasPermission = item.author_department === department;
                            } else if (['Student', 'Faculty', 'Staff'].includes(userType)) {
                                hasPermission = parseInt(item[`${type}_author_id`]) === parseInt(user_id);
                            }

                            return basicConditions && hasPermission;
                        }).length;
                    })
                    .catch(error => {
                        console.error(`Error fetching ${type} archives count:`, error);
                        return 0;
                    })
            );

            // Wait for all fetches to complete and sum the counts
            Promise.all(fetchPromises)
                .then(counts => {
                    const totalCount = counts.reduce((sum, count) => sum + count, 0);
                    
                    // Update UI elements
                    const trashCountElement = document.getElementById('trashContentCount');
                    let trashCountButtonElement;
                    
                    if (trashCountElement) {
                        trashCountElement.textContent = totalCount > 0 ? totalCount : '0';
                        trashCountElement.style.display = 'inline';
                    }
                    if (trashCountButtonElement = document.getElementById('trashContentButtonCount')) {
                        trashCountButtonElement.textContent = totalCount > 0 ? totalCount : '0';
                        trashCountButtonElement.style.display = 'inline';
                    }
                })
                .catch(error => {
                    console.error('Error calculating total trash count:', error);
                });
        })
        .catch(error => {
            console.error('Error fetching features:', error);
        });
}

// Function to fetch trash count
function fetchTrashCount() {
    updateTrashCount();
}

function updateApprovedCount() {
    // First fetch all features
    fetch('database/fetch_feature_info.php?type=all')
        .then(response => response.json())
        .then(features => {
            // Start with default content types
            const contentTypes = ['announcement', 'event', 'news', 'promaterial'];
            
            // Add dynamic features
            if (Array.isArray(features)) {
                features.forEach(feature => {
                    const featureType = feature.type;
                    if (!contentTypes.includes(featureType)) {
                        contentTypes.push(featureType);
                    }
                });
            }

            // Add admin-only content types
            if (userType === 'Admin' || userType === 'Super Admin') {
                if (!contentTypes.includes('peo')) contentTypes.push('peo');
                if (!contentTypes.includes('so')) contentTypes.push('so');
            }

            // Create array of promises for all content type fetches
            const fetchPromises = contentTypes.map(type =>
                fetch(`database/fetch_content.php?type=${type}&approved=true`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(`Error fetching ${type}:`, data.error);
                            return 0;
                        }

                        return data.filter(item => {
                            // Basic archive conditions
                            const basicConditions = item.isArchived === 0 && 
                                item.isTrashed === 0 && 
                                item.status === 'Approved';

                            // Permission conditions
                            let hasPermission = false;
                            if (userType === 'Super Admin') {
                                hasPermission = true;
                            } else if (userType === 'Admin') {
                                hasPermission = item.author_department === department;
                            } else if (['Student', 'Faculty', 'Staff'].includes(userType)) {
                                hasPermission = parseInt(item[`${type}_author_id`]) === parseInt(user_id);
                            }

                            return basicConditions && hasPermission;
                        }).length;
                    })
                    .catch(error => {
                        console.error(`Error fetching ${type} approved count:`, error);
                        return 0;
                    })
            );

            // Wait for all fetches to complete and sum the counts
            Promise.all(fetchPromises)
                .then(counts => {
                    const totalCount = counts.reduce((sum, count) => sum + count, 0);
                    
                    // Update UI elements
                    const approvedCountElement = document.getElementById('approvedContentCount');
                    let approvedCountButtonElement;
                    
                    if (approvedCountElement) {
                        approvedCountElement.textContent = totalCount > 0 ? totalCount : '0';
                        approvedCountElement.style.display = 'inline';
                    }
                    if (approvedCountButtonElement = document.getElementById('approvedContentButtonCount')) {
                        approvedCountButtonElement.textContent = totalCount > 0 ? totalCount : '0';
                        approvedCountButtonElement.style.display = 'inline';
                    }
                })
                .catch(error => {
                    console.error('Error calculating total approved count:', error);
                });
        })
        .catch(error => {
            console.error('Error fetching features:', error);
        });

}

function fetchApprovedCount() {
    updateApprovedCount();
}

function updateRejectedCount() {
    // First fetch all features
    fetch('database/fetch_feature_info.php?type=all')
        .then(response => response.json())
        .then(features => {
            // Start with default content types
            const contentTypes = ['announcement', 'event', 'news', 'promaterial'];
            
            // Add dynamic features
            if (Array.isArray(features)) {
                features.forEach(feature => {
                    const featureType = feature.type;
                    if (!contentTypes.includes(featureType)) {
                        contentTypes.push(featureType);
                    }
                });
            }

            // Add admin-only content types
            if (userType === 'Admin' || userType === 'Super Admin') {
                if (!contentTypes.includes('peo')) contentTypes.push('peo');
                if (!contentTypes.includes('so')) contentTypes.push('so');
            }

            // Create array of promises for all content type fetches
            const fetchPromises = contentTypes.map(type =>
                fetch(`database/fetch_content.php?type=${type}&rejected=true`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(`Error fetching ${type}:`, data.error);
                            return 0;
                        }

                        return data.filter(item => {
                            // Basic archive conditions
                            const basicConditions = item.isArchived === 0 && 
                                item.isTrashed === 0 && 
                                item.status === 'Rejected';

                            // Permission conditions
                            let hasPermission = false;
                            if (userType === 'Super Admin') {
                                hasPermission = true;
                            } else if (userType === 'Admin') {
                                hasPermission = item.author_department === department;
                            } else if (['Student', 'Faculty', 'Staff'].includes(userType)) {
                                hasPermission = parseInt(item[`${type}_author_id`]) === parseInt(user_id);
                            }

                            return basicConditions && hasPermission;
                        }).length;
                    })
                    .catch(error => {
                        console.error(`Error fetching ${type} rejected count:`, error);
                        return 0;
                    })
            );

            // Wait for all fetches to complete and sum the counts
            Promise.all(fetchPromises)
                .then(counts => {
                    const totalCount = counts.reduce((sum, count) => sum + count, 0);
                    
                    // Update UI elements
                    const rejectedCountElement = document.getElementById('rejectedContentCount');
                    let rejectedCountButtonElement;
                    
                    if (rejectedCountElement) {
                        rejectedCountElement.textContent = totalCount > 0 ? totalCount : '0';
                        rejectedCountElement.style.display = 'inline';
                    }
                    if (rejectedCountButtonElement = document.getElementById('rejectedContentButtonCount')) {
                        rejectedCountButtonElement.textContent = totalCount > 0 ? totalCount : '0';
                        rejectedCountButtonElement.style.display = 'inline';
                    }
                })
                .catch(error => {
                    console.error('Error calculating total rejected count:', error);
                });
        })
        .catch(error => {
            console.error('Error fetching features:', error);
        });
}

function fetchRejectedCount() {
    updateRejectedCount();
}

function updatePendingCount() {
    // First fetch all features
    fetch('database/fetch_feature_info.php?type=all')
        .then(response => response.json())
        .then(features => {
            // Start with default content types
            const contentTypes = ['announcement', 'event', 'news', 'promaterial'];
            
            // Add dynamic features
            if (Array.isArray(features)) {
                features.forEach(feature => {
                    const featureType = feature.type;
                    if (!contentTypes.includes(featureType)) {
                        contentTypes.push(featureType);
                    }
                });
            }

            // Add admin-only content types
            if (userType === 'Admin' || userType === 'Super Admin') {
                if (!contentTypes.includes('peo')) contentTypes.push('peo');
                if (!contentTypes.includes('so')) contentTypes.push('so');
            }

            // Create array of promises for all content type fetches
            const fetchPromises = contentTypes.map(type =>
                fetch(`database/fetch_content.php?type=${type}&pending=true`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(`Error fetching ${type}:`, data.error);
                            return 0;
                        }

                        return data.filter(item => {
                            // Basic archive conditions
                            const basicConditions = item.isArchived === 0 && 
                                item.isTrashed === 0 && 
                                item.status === 'Pending';

                            // Permission conditions
                            let hasPermission = false;
                            if (userType === 'Super Admin') {
                                hasPermission = true;
                            } else if (userType === 'Admin') {
                                hasPermission = item.author_department === department;
                            } else if (['Student', 'Faculty', 'Staff'].includes(userType)) {
                                hasPermission = parseInt(item[`${type}_author_id`]) === parseInt(user_id);
                            }

                            return basicConditions && hasPermission;
                        }).length;
                    })
                    .catch(error => {
                        console.error(`Error fetching ${type} pending count:`, error);
                        return 0;
                    })
            );

            // Wait for all fetches to complete and sum the counts
            Promise.all(fetchPromises)
                .then(counts => {
                    const totalCount = counts.reduce((sum, count) => sum + count, 0);
                    
                    // Update UI elements
                    const pendingCountElement = document.getElementById('pendingContentCount');
                    let pendingCountButtonElement;
                    
                    if (pendingCountElement) {
                        pendingCountElement.textContent = totalCount > 0 ? totalCount : '0';
                        pendingCountElement.style.display = 'inline';
                    }
                    if (pendingCountButtonElement = document.getElementById('pendingContentButtonCount')) {
                        pendingCountButtonElement.textContent = totalCount > 0 ? totalCount : '0';
                        pendingCountButtonElement.style.display = 'inline';
                    }
                })
                .catch(error => {
                    console.error('Error calculating total pending count:', error);
                });
        })
        .catch(error => {
            console.error('Error fetching features:', error);
        });
}


function fetchPendingCount() {
    updatePendingCount();
}

Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    switch (data.action) {
        case 'new_notification':
        case 'update_notification':
            fetchNotifications();
            fetchNotificationCount();
            fetchArchivesCount();
            fetchTrashCount();
            fetchApprovedCount();
            fetchRejectedCount();
            fetchPendingCount();
            break;
        case 'delete_notification':
            fetchNotifications();
            break;
        case 'reset_notification_count':
            if (data.success) {
                document.getElementById('notificationCount').style.display = 'none';
                document.getElementById('notificationCountMobile').style.display = 'none';
            }
            break;
        case 'delete_content':
            if (data.success) {
                // Remove the deleted notification from the UI
                const notificationElement = document.querySelector(`[data-content-id="${data[`${data.type}_id`]}"][data-content-type="${data.type}"]`);
                if (notificationElement) {
                    notificationElement.remove();
                }
                // Refresh the notifications
                fetchNotifications();
            }
            break;
        case 'update_tv_status':
            console.log("update_tv_status called: ", data.tvId);
            // Get the status element for this specific TV
            const activeTvStatus = document.getElementById(`activeTvStatus_${data.tvId}`);
            if (activeTvStatus) {
                activeTvStatus.innerHTML = data.status === 'active' ? '<i class="fa fa-circle" style="color: green;"></i>' : '<i class="fa fa-circle" style="color: red;"></i>';
            }
            break;
        case 'view_content':
            if (data.success) {
                const modal = document.getElementById('viewContentModal');
                const content = data.content;
                const contentType = data.content_type;
                const formattedExpirationDateTime = formatDateTime2(content.expiration_datetime);

                let mediaContent = '';
                if (content.media_path) {
                    const isImage = /\.(jpg|jpeg|png|gif)$/i.test(content.media_path);
                    const isVideo = /\.(mp4|webm|ogg)$/i.test(content.media_path);
                    mediaContent = isImage ? `<img src="servers/${contentType}_media/${content.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">` :
                        isVideo ? `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/${contentType}_media/${content.media_path}" type="video/mp4"></video>` : '';
                }

                let contentHtml = `
                    <div class="content-container-con">
                        ${mediaContent ? `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>` : ''}
                `;

                // Add type-specific content
                switch(contentType) {
                    case 'announcement':
                    case 'event':
                    case 'news':
                        contentHtml += `<pre class="ann-body" style="word-break: break-word">${content[`${contentType}_body`]}</pre>`;
                        break;
                    case 'promaterial':
                        break;
                    case 'peo':
                    case 'so':
                        contentHtml += `<pre class="ann-body" style="word-break: break-word"><b>${content[`${contentType}_title`]}</b></pre>
                                        <pre class="ann-body" style="word-break: break-word">${content[`${contentType}_description`]}</pre>
                                        <pre class="ann-body" style="word-break: break-word">${content[`${contentType}_subdescription`]}</pre>`;
                        break;
                    default:
                        // For new features, dynamically add all fields
                        for (let key in content) {
                            if (key !== `${contentType}_id` && key !== 'created_datetime' && key !== 'expiration_datetime' 
                                && key !== 'display_time' && key !== 'status' && key !== 'isArchived' 
                                && key !== 'tv_id' && key !== `${contentType}_author_id` && key !== 'type' && key !== 'department' 
                                && key !== 'user_type' && key !== 'category' && key !== 'evaluated_by' && key !== 'evaluated_message' && key !== 'author_name') {
                                    contentHtml += `<p>${content[key]}</p>`;
                            }
                        }
                }

                contentHtml += `
                        <div class="line-separator"></div>
                        <p class="expiration-date" style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDateTime}</small></p>
                        <p class="display-time"><i class="fa fa-hourglass-half" aria-hidden="true"></i> ${content.display_time} secs | <i class="fa fa-tv" aria-hidden="true"></i> ${data.tv_display}</p>
                    </div>
                `;

                modal.innerHTML = `
                    <div class="modal-content" style="text-align: left; position: absolute; padding: 15px;">
                        <p>${capitalizeFirstLetter(contentType)}</p>
                        <span class="close" onclick="closeModal('viewContent')" style="position: absolute; right: 5px; top: 5px"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                        <br>
                        ${contentHtml}
                        <br>
                        <div style="text-align: right;">
                            <button type="button" class="green-button" style="margin: 0;" onclick="showConfirmApproveContentModal(${content[`${contentType}_id`]}, '${contentType}', ${content[`${contentType}_author_id`]}, '${full_name}')">Approve</button>
                            <button type="button" class="light-green-button" style="margin: 0;" onclick="showConfirmRejectContentModal(${content[`${contentType}_id`]}, '${contentType}', ${content[`${contentType}_author_id`]}, '${full_name}')">Reject</button>
                            <button type="button" class="grey-button" onclick="closeModal('viewContent')">Close</button>
                        </div>
                    </div>
                `;
                modal.style.display = 'flex';
            } 
            break;
        case 'edit_smart_tv':
            if (data.success) {
                const tvNameContent = document.getElementById('tvNameContent');
                const tvNameUserHome = document.getElementById(`tvNameUserHome_${data.tv_id}`);
                const tvDepartmentUserHome = document.getElementById(`tvDepartmentUserHome_${data.tv_id}`);
                const tvBrandUserHome = document.getElementById(`tvBrandUserHome_${data.tv_id}`);
                const tvBrand2UserHome = document.getElementById(`tvBrand2UserHome_${data.tv_id}`);
                
                if (tvNameContent && data.tv_id == tvIdFromUrl) {
                    tvNameContent.textContent = data.tv_name;
                } 
                if (tvNameUserHome) {
                    tvNameUserHome.innerHTML = `<i class="fa fa-tv" style="margin-right: 6px" aria-hidden="true"></i>${data.tv_name}`;
                }
                if (tvDepartmentUserHome) {
                    tvDepartmentUserHome.innerHTML = data.tv_department;
                }
                if (tvBrandUserHome) {
                    tvBrandUserHome.innerHTML = `| ${data.tv_brand}`;
                }
                if (tvBrand2UserHome) {
                    tvBrand2UserHome.innerHTML = data.tv_brand;
                }
            }
            break;
        default:
    }
});

// Close the notifications dropdown if the user clicks outside of it
document.addEventListener('click', function(event) {
    const notificationsDropdown = document.getElementById("notificationsDropdown");
    const notificationsDropdownMobile = document.getElementById("notificationsDropdownMobile");
    const notificationsButton = document.getElementById("notifications-button");
    const notificationsButtonMobile = document.getElementById("notifications-button-mobile");
    const triangle = document.getElementById('triangle');
    const triangleMobile = document.getElementById('triangleMobile');

    // Add check for delete button clicks
    if (event.target.closest('.delete-notification') || event.target.classList.contains('delete-notification')) {
        return; // Exit early if clicking the delete button (does not close the dropdown if it is open)
    }

    if ((notificationsDropdown.style.display === 'block' || notificationsDropdownMobile.style.display === 'block') && 
        !notificationsDropdown.contains(event.target) && 
        !notificationsDropdownMobile.contains(event.target) && 
        !notificationsButtonMobile.contains(event.target) && 
        !notificationsButton.contains(event.target)) {
            
        notificationsDropdown.style.display = 'none';
        notificationsDropdownMobile.style.display = 'none';
        triangle.style.display = 'none';
        triangleMobile.style.display = 'none';
    }
});

// Fetch notifications when the page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchNotifications();
    fetchNotificationCount();
    fetchArchivesCount();
    fetchTrashCount();
    fetchApprovedCount();
    fetchRejectedCount();
    fetchPendingCount();
    const tabLinksActive = document.querySelector('.notifications-tablinks.active');
    if (tabLinksActive) {
        tabLinksActive.click();
    }
});