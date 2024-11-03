const Ws = new WebSocket('ws://192.168.1.3:8081');

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

    filterNotifications(tabName);
}

function filterNotifications(tabName) {
    const notifications = JSON.parse(localStorage.getItem('notifications')) || [];
    const notificationsHeader = document.getElementById('notificationsHeader');
    if (notificationsHeader) {
        if (tabName === 'allNotificationsList') {
            document.getElementById('notificationsHeader').textContent = 'All Notifications';
        } else if (tabName === 'pendingNotificationsList') {
            document.getElementById('notificationsHeader').textContent = 'Pending Notifications';
        } else if (tabName === 'approvedNotificationsList') {
            document.getElementById('notificationsHeader').textContent = 'Approved Notifications';
        } else if (tabName === 'rejectedNotificationsList') {
            document.getElementById('notificationsHeader').textContent = 'Rejected Notifications';
        } else if (tabName === 'restoredNotificationsList') {
            document.getElementById('notificationsHeader').textContent = 'Restored Notifications';
        } else if (tabName === 'archivedNotificationsList') {
            document.getElementById('notificationsHeader').textContent = 'Archived Notifications';
        } else if (tabName === 'deletedNotificationsList') {
            document.getElementById('notificationsHeader').textContent = 'Deleted Notifications';
        } else if (tabName === 'updatedNotificationsList') {
            document.getElementById('notificationsHeader').textContent = 'Updated Notifications';
        }
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
            console.log(data);
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function fetchNotificationCount() {
    fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            console.log("Notification Count Data:", data.count);
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
                <div class="notification-buttons">
                    <button type="button" class="green-button" style="margin: 0;" onclick="showConfirmApproveModal(${notification.user_id}, '${notification.action_user_email}', '${notification.action_user_name}', '${notification.action_user_type}', '${full_name}', '${notification.action_user_department}', ${user_id})">Approve</button>
                    <button type="button" class="red-button" style="margin: 0;" onclick="showConfirmRejectModal(${notification.user_id}, '${notification.action_user_email}', '${notification.action_user_name}', '${notification.action_user_type}', '${full_name}', '${notification.action_user_department}', ${user_id})">Reject</button>
                </div>
            `;
        }

        if (notification.notification_type === 'content_post' && notification.status == 'pending') {
            notificationDiv.classList.add('pending-notification');
            content = `
                <div class="notification-details">
                    <p class="content-post-notification">${notification.action_user_name} wants to post a ${notification.content_type} in the TV with ID: ${notification.content_tv_id}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
                <div class="notification-buttons">
                    <button type="button" class="green-button" style="margin: 0;" onclick="showConfirmApproveContentModal(${notification.content_id}, '${notification.content_type}', '${notification.action_user_name}', '${notification.action_user_department}', ${notification.user_id}, ${notification.content_tv_id}, '${full_name}', ${user_id})">Approve</button>
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="showConfirmRejectContentModal(${notification.content_id}, '${notification.content_type}', ${notification.user_id}, '${full_name}')">Reject</button>
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="viewContent(${notification.content_id}, '${notification.content_type}', ${notification.user_id}, '${full_name}')">View</button>
                </div>
            `;
        }

    } else if (userType === 'Student' || userType === 'Faculty' || userType === 'Staff') {
        if (notification.notification_type === 'content_post' && notification.status == 'pending' && notification.user_id == user_id) {
            notificationDiv.classList.add('pending-notification');
            content = `
                <div class="notification-details">
                    <p class="content-post-notification">Your ${notification.content_type} is  pending for approval.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                    <div class="notification-buttons" style="margin-top: 0;"></div>
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
                <p>${notification.notification_id}</p>
                <div class="notification-details">
                    <p class="user-approved-notification">You have been added by ${notification.evaluator_name} as ${notification.action_user_type} in ${notification.action_user_department} department.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    } 
    
    if (notification.notification_type === 'content_post' && notification.user_id == user_id) {
        if (notification.status == 'approved') {
            notificationDiv.classList.add('approved-notification');
            content = `
                <div class="notification-details">
                    <p class="content-post-notification">You posted ${notification.content_type} in TV with ID: ${notification.content_tv_id}.</p>
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
                    <p class="content-approved-notification">You approved ${notification.action_user_name}'s ${notification.content_type} to be posted in the TV with ID: ${notification.content_tv_id}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.evaluator_name != full_name) {
            notificationDiv.classList.add('approved-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">${notification.evaluator_name} approved ${notification.action_user_name}'s ${notification.content_type} to be posted in the TV with ID: ${notification.content_tv_id}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
        
    } 
    
    if (notification.notification_type === 'content_approved_by_admin' && notification.status == 'approved' && notification.user_id == user_id) {
        notificationDiv.classList.add('approved-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your ${notification.content_type} has been approved by ${notification.evaluator_name} to be posted in the TV with ID: ${notification.content_tv_id}.</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    } 
    
    if (notification.notification_type === 'content_rejected' && notification.status == 'rejected' && notification.user_id == user_id) {
        if (notification.evaluator_name == full_name) {
            notificationDiv.classList.add('rejected-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You rejected ${notification.action_user_name}'s ${notification.content_type} to be posted in the TV with ID: ${notification.content_tv_id}.</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    } 

    if (notification.notification_type === 'content_rejected_by_admin' && notification.status == 'rejected' && notification.user_id == user_id) {
        notificationDiv.classList.add('rejected-notification');
        content = `
            <div class="notification-details">
                <p class="content-rejected-notification">Your ${notification.content_type} has been rejected by  ${notification.evaluator_name}.</p>
                <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                <div class="notification-buttons">
                    <!-- <button type="button" class="green-button" style="margin: 0;" onclick="showEditContentContentModal(${notification.content_id}, '${notification.content_type}', ${notification.user_id}, '${full_name}')">Edit</button> -->
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="showConfirmDeleteContentModal(${notification.content_id}, '${notification.content_type}', ${notification.user_id}, '${full_name}')">Delete Content</button>
                </div>
            </div>
        `;
    }
    
    if (notification.notification_type === 'content_deleted' && notification.status == 'deleted' && notification.user_id == user_id) {
        if (notification.action_user_name == full_name && notification.evaluator_name != full_name) {
            notificationDiv.classList.add('deleted-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You deleted your ${notification.content_type}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }

        if (notification.evaluator_name == full_name && notification.action_user_name != full_name) {
            notificationDiv.classList.add('deleted-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You deleted ${notification.action_user_name}'s ${notification.content_type}</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }  
    } 
    
    if (notification.notification_type === 'content_deleted_by_admin' && notification.status == 'deleted' && notification.user_id == user_id) {
        notificationDiv.classList.add('deleted-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your ${notification.content_type} has been deleted by ${notification.action_user_name}.</p>
                <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    } 
    
    if (notification.notification_type === 'content_archived' && notification.status == 'archived' && notification.user_id == user_id) {
        if (notification.action_user_name == full_name && notification.evaluator_name != full_name) {
            notificationDiv.classList.add('archived-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You archived your ${notification.content_type}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
                <div class="view-button-container">
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="window.location.href='archives.php?pageid=Archives?userId=${user_id}%27%27${full_name}';">View</button>
                </div>
            `;
        }

        if (notification.evaluator_name == full_name && notification.action_user_name != full_name) {
            notificationDiv.classList.add('archived-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You archived ${notification.action_user_name}'s ${notification.content_type}</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
            `;
        }
    } 
    
    if (notification.notification_type === 'content_archived_by_admin' && notification.status == 'archived' && notification.user_id == user_id) {
        notificationDiv.classList.add('archived-notification');
        content = `
            <div class="notification-details">
                <p class="content-approved-notification">Your ${notification.content_type} has been archived by ${notification.evaluator_name}.</p>
                <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
            </div>
        `;
    } 
    
    if (notification.notification_type === 'content_restored' && notification.status == 'restored' && notification.user_id == user_id) {
        if (notification.evaluator_name == full_name) {
            notificationDiv.classList.add('restored-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You restored your ${notification.content_type}.</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
                <div class="view-button-container">
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="viewTvContent(${notification.content_tv_id})">View</button>
                </div>
            `;
        }
    } 
    
    if (notification.notification_type === 'content_restored_by_admin' && notification.status == 'restored') {
        if (notification.evaluator_name != full_name) {
            notificationDiv.classList.add('restored-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">Your ${notification.content_type} has been restored by ${notification.evaluator_name}.</p>
                    <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
                <div class="view-button-container">
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="viewTvContent(${notification.content_tv_id})">View</button>
                </div>
            `;
        }

        if (notification.evaluator_name == full_name) {
            notificationDiv.classList.add('restored-notification');
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You restored ${notification.action_user_name}'s ${notification.content_type}</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatRelativeTime(notification.created_at)}</p>
                </div>
                <div class="view-button-container">
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="viewTvContent(${notification.content_tv_id})">View</button>
                </div>
            `;
        }
        
    }

    // If content is still empty, it means no condition was met
    if (content == '') {
        return null;
    }

    // Check if the content includes notification-buttons
    if (!content.includes('notification-buttons')) {
        // Add delete button only if there are no notification-buttons
        content += `<button class="delete-notification" onclick="deleteNotification(${notification.notification_id})"><i class="fa fa-times-circle" aria-hidden="true"></i></button>`;
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
            <span class="close" onclick="closeModal('confirmApproveUser')" style="color: #334b35"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-thumbs-up" aria-hidden="true"></i></h1>
            <p>Proceed to approve this user?</p>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" onclick="closeModal('confirmApproveUser')">Cancel</button>
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
            <span class="close" onclick="closeModal('confirmRejectUser')" style="color: #7E0B22"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-thumbs-down" aria-hidden="true"></i></h1>
            <p>Proceed to reject this user?</p>
            <br>
            <div class="floating-label-container">
                <textarea name="evaluator_message" id="evaluator_message" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
                <label for="evaluator_message" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User (required)</label>
            </div>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" onclick="closeModal('confirmRejectUser')">Cancel</button>
                <button type="button" class="red-button" style="margin-right: 0" onclick="rejectUser(${userId}, '${email}', '${fullName}', '${userType}', '${evaluatorName}', '${department}', ${evaluatorId})">Yes, Reject</button>
            </div>
        </div>
    `;
    modal.style.display = 'flex';
}

function showConfirmApproveContentModal(contentId, contentType, fullName, department, userId, contentTvId, evaluatorName, evaluatorId) {
    const viewContentModal = document.getElementById('viewContentModal');
    viewContentModal.style.display = 'none';
    const modal = document.getElementById('confirmApproveContentModal');
    modal.innerHTML = `
        <div class="modal-content" style="padding: 10px;">
            <span class="close" onclick="closeModal('confirmApproveContent')" style="color: #334b35"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-thumbs-up" aria-hidden="true"></i></h1>
            <p>Proceed to approve this ${contentType}?</p>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" onclick="closeModal('confirmApproveContent')">Cancel</button>
                <button type="button" class="green-button" style="margin-right: 0" onclick="approveContent(${contentId}, '${contentType}', '${fullName}', '${department}', ${userId}, ${contentTvId}, '${evaluatorName}', ${evaluatorId})">Yes, Approve</button>
            </div>
        </div>
    `;
    modal.style.display = 'flex';
}

function showConfirmRejectContentModal(contentId, contentType, userId, fullName) {
    const viewContentModal = document.getElementById('viewContentModal');
    viewContentModal.style.display = 'none';
    const modal = document.getElementById('confirmRejectContentModal');
    modal.innerHTML = `
        <div class="modal-content" style="padding: 10px;">
            <span class="close" onclick="closeModal('confirmRejectContent')" style="color: #7E0B22"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-thumbs-down" aria-hidden="true"></i></h1>
            <p>Proceed to reject this ${contentType}?</p>
            <br>
            <div class="floating-label-container">
                <textarea name="evaluator_message" id="evaluator_message" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area"></textarea>
                <label for="evaluator_message" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User's Content (required)</label>
            </div>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" onclick="closeModal('confirmRejectContent')">Cancel</button>
                <button type="button" class="red-button" style="margin-right: 0" onclick="rejectContent(${contentId}, '${contentType}', ${userId}, '${fullName}')">Yes, Reject</button>
            </div>
        </div>
    `;
    modal.style.display = 'flex';
}

function showConfirmDeleteContentModal(contentId, contentType, userId, fullName) {
    const confirmDeleteContentModal = document.getElementById('confirmDeleteContentModal');
    confirmDeleteContentModal.innerHTML = `
        <div class="modal-content">
            <span class="close" onclick="closeModal('confirmDeleteContent')" style="color: #7E0B22"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
            <p>Proceed to delete this ${contentType}?</p>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" onclick="closeModal('confirmDeleteContent')">Cancel</button>
                <button type="button" class="red-button" style="margin-right: 0" onclick="deleteContent(${contentId}, '${contentType}', ${userId}, '${fullName}')">Yes, Delete</button>
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
    const evaluatorMessage = document.getElementById('evaluator_message').value;
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

function rejectContent(contentId, contentType, userId, fullName) {
    const evaluatorMessage = document.getElementById('evaluator_message').value;
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
        evaluator_message: evaluatorMessage
    }));
    closeModal('confirmRejectContent');
}

function deleteContent(contentId, contentType, userId, fullName) {
    Ws.send(JSON.stringify({
        action: 'delete_content',
        [`${contentType}_id`]: contentId,
        type: contentType,
        user_id: userId,
        evaluator_name: fullName
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
    console.log(modal);
    modal.style.display = 'none';
}

function deleteNotification(notificationId) {
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

Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    switch (data.action) {
        case 'new_notification':
        case 'update_notification':
            fetchNotifications();
            fetchNotificationCount();
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
        case 'view_content':
            if (data.success) {
                console.log("View Content Data: ",data);
                const modal = document.getElementById('viewContentModal');
                const content = data.content;
                console.log("Content: ",content);
                const contentType = data.content_type;
                const formattedExpirationDateTime = formatRelativeTime(content.expiration_datetime);

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
                                && key !== 'display_time' && key !== 'status' && key !== 'isCancelled' 
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
                    <div class="modal-content" style="text-align: left; position: absolute; padding: 10px;">
                        <p style="color: #334b35;">${capitalizeFirstLetter(contentType)}</p>
                        <span class="close" onclick="closeModal('viewContent')" style="color: #334b35; position: absolute; right: 5px; top: 5px"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
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

    // Fix the condition by properly grouping the logical operators
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
    const tabLinksActive = document.querySelector('.notifications-tablinks.active');
    if (tabLinksActive) {
        tabLinksActive.click();
    }
});