const Ws = new WebSocket('ws://192.168.1.17:8081');

function fetchNotifications() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            updateNotificationsUI(data);
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function fetchNotificationCount() {
    fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            console.log("Notification Count Data:", data);
            updateNotificationCount(data.count);
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function formatDate(dateTimeString) {
    const dateTime = new Date(dateTimeString);
    const options = {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    };
    return new Intl.DateTimeFormat('en-US', options).format(dateTime);
}

function toggleNotificationsDropdown() {
    var notificationsDropdown = document.getElementById("notificationsDropdown");
    var triangle = document.querySelector('.triangle');
    if (notificationsDropdown.style.display === 'none' || notificationsDropdown.style.display === '') {
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

function updateNotificationsUI(notifications) {
    const notificationsContainer = document.getElementById('notificationsContainer');
    const viewAllNotificationsContainer = document.getElementById('ViewAllNotificationsContainer');

    // Function to populate a container with notifications
    const populateContainer = (container) => {
        container.innerHTML = '';
        if (notifications.length === 0) {
            container.innerHTML = '<p>No new notifications.</p>';
        } else {
            notifications.forEach(notification => {
                const notificationDiv = createNotificationDiv(notification);
                if (notificationDiv) {
                    container.appendChild(notificationDiv.cloneNode(true));
                }
            });
        }
    };

    // Update the dropdown notifications
    if (notificationsContainer) {
        populateContainer(notificationsContainer);
    }

    // Update the main notifications page
    if (viewAllNotificationsContainer) {
        populateContainer(viewAllNotificationsContainer);
    }
}

function createNotificationDiv(notification) {
    const notificationDiv = document.createElement('div');
    notificationDiv.className = 'notification';

    let content = '';

    if (userType === 'Super Admin') {
        if (notification.notification_type === 'user_registration' && notification.status == 'pending') {
            content = `
                <div class="notification-details">
                    <p class="user-registration-notification"><strong>${notification.full_name}</strong> has registered as a <strong>${notification.user_type}</strong>.</p>
                    <p class="department-notification">This user is from <strong>${notification.department}</strong> department.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
                <div class="notification-buttons">
                    <button type="button" class="green-button" style="margin: 0;" onclick="showConfirmApproveModal(${notification.user_id}, '${full_name}')">Approve</button>
                    <button type="button" class="red-button" style="margin: 0;" onclick="showConfirmRejectModal(${notification.user_id}, '${full_name}')">Reject</button>
                </div>
            `;
        } else if (notification.notification_type === 'user_approved' && notification.status == 'approved' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You <strong>approved</strong> ${notification.full_name}'s registration.</p>
                    <p class="department-notification">This user is from <strong>${notification.department}</strong> department.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'user_rejected' && notification.status == 'rejected' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You <strong>rejected</strong> ${notification.full_name}'s registration.</p>
                    <p class="department-notification">This user is from <strong>${notification.department}</strong> department.</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'user_edited' && notification.status == 'edited' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You <strong>edited</strong> ${notification.full_name}'s profile.</p>
                    <p class="department-notification">This user is from <strong>${notification.department}</strong> department.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_post' && notification.status == 'pending') {
            content = `
                <div class="notification-details">
                    <p class="content-post-notification"><strong>${notification.full_name}</strong> wants to post a <strong>${notification.content_type}</strong>.</p>
                    <p class="department-notification">This user is from <strong>${notification.department}</strong> department.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
                <div class="notification-buttons">
                    <button type="button" class="green-button" style="margin: 0;" onclick="showConfirmApproveContentModal(${notification.content_id}, '${notification.content_type}', ${notification.user_id}, '${full_name}')">Approve</button>
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="showConfirmRejectContentModal(${notification.content_id}, '${notification.content_type}', ${notification.user_id}, '${full_name}')">Reject</button>
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="viewContent(${notification.content_id}, '${notification.content_type}', ${notification.user_id}, '${full_name}')">View</button>
                </div>
            `;
        } else if (notification.notification_type === 'content_post' && notification.status == 'approved' && notification.user_id == user_id) {
            content = `
                <div class="notification-details">
                    <p class="content-post-notification">You posted <strong>${notification.content_type}</strong>.</p>
                    <p class="department-notification">This user is from <strong>${notification.department}</strong> department.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_approved' && notification.status == 'approved' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You <strong>approved</strong> ${notification.full_name}'s ${notification.content_type}.</p>
                    <p class="department-notification">This user is from <strong>${notification.department}</strong> department.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_rejected' && notification.status == 'rejected' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You <strong>rejected</strong> ${notification.full_name}'s ${notification.content_type}.</p>
                    <p class="department-notification">This user is from <strong>${notification.department}</strong> department.</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_deleted' && notification.status == 'deleted' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You <strong>deleted</strong> ${notification.full_name}'s ${notification.content_type}.</p>
                    <p class="department-notification">This user is from <strong>${notification.department}</strong> department.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        }
    } else if (userType === 'Admin') {
        if (notification.notification_type === 'user_registration' && notification.status == 'pending') {
            content = `
                <div class="notification-details">
                    <p class="user-registration-notification"><strong>${notification.full_name}</strong> has registered as a <strong>${notification.user_type}</strong>.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
                <div class="notification-buttons">
                    <button type="button" class="green-button" style="margin: 0;" onclick="showConfirmApproveModal(${notification.user_id}, '${full_name}')">Approve</button>
                    <button type="button" class="red-button" style="margin: 0;" onclick="showConfirmRejectModal(${notification.user_id}, '${full_name}')">Reject</button>
                </div>
            `;
        } else if (notification.notification_type === 'user_approved' && notification.status == 'approved' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You <strong>approved</strong> ${notification.full_name}'s registration.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'user_rejected' && notification.status == 'rejected' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You <strong>rejected</strong> ${notification.full_name}'s registration.</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'user_edited' && notification.status == 'edited' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You <strong>edited</strong> ${notification.full_name}'s profile.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_post' && notification.status == 'pending') {
            content = `
                <div class="notification-details">
                    <p class="content-post-notification"><strong>${notification.full_name}</strong> wants to post a <strong>${notification.content_type}</strong>.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
                <div class="notification-buttons">
                    <button type="button" class="green-button" style="margin: 0;" onclick="showConfirmApproveContentModal(${notification.content_id}, '${notification.content_type}', ${notification.user_id}, '${full_name}')">Approve</button>
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="showConfirmRejectContentModal(${notification.content_id}, '${notification.content_type}', ${notification.user_id}, '${full_name}')">Reject</button>
                    <button type="button" class="light-green-button" style="margin: 0;" onclick="viewContent(${notification.content_id}, '${notification.content_type}', ${notification.user_id}, '${full_name}')">View</button>
                </div>
            `;
        } else if (notification.notification_type === 'content_post' && notification.status == 'approved' && notification.user_id == user_id) {
            content = `
                <div class="notification-details">
                    <p class="content-post-notification">You posted <strong>${notification.content_type}</strong>.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_approved' && notification.status == 'approved' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You <strong>approved</strong> ${notification.full_name}'s ${notification.content_type}.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_rejected' && notification.status == 'rejected' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You <strong>rejected</strong> ${notification.full_name}'s ${notification.content_type}.</p>
                    <p class="evaluator-message-notification">Your Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_deleted' && notification.status == 'deleted' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You <strong>deleted</strong> ${notification.full_name}'s ${notification.content_type}.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        }
    } else if (userType === 'Student' || userType === 'Faculty') {
        if (notification.notification_type === 'content_post' && notification.status == 'pending' && notification.user_id == user_id) {
            content = `
            <div class="notification-details">
                <p class="content-post-notification">Your ${notification.content_type} is <strong> pending for approval.</strong></p>
                <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                <div class="notification-buttons"></div>
            </div>
            `;
        } else if (notification.notification_type === 'content_approved_by_admin' && notification.status == 'approved' && notification.user_id == user_id) {
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">Your ${notification.content_type} has been <strong>approved </strong> by ${notification.evaluator_name}.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_rejected_by_admin' && notification.status == 'rejected' && notification.user_id == user_id) {
            content = `
                <div class="notification-details">
                    <p class="content-rejected-notification">Your ${notification.content_type} has been <strong>rejected by </strong> ${notification.evaluator_name}.</p>
                    <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'user_approved_by_admin' && notification.status == 'approved' && notification.user_id == user_id) {
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">Your registration has been <strong>approved by </strong> ${notification.evaluator_name}.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'user_rejected_by_admin' && notification.status == 'rejected' && notification.user_id == user_id) {
            content = `
                <div class="notification-details">  
                    <p class="content-approved-notification">Your <strong>${notification.content_type}</strong> has been <strong>approved</strong> by ${notification.evaluator_name}.</p>
                    <p class="evaluator-message-notification">Evaluator's Message: "${notification.evaluator_message}"</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
                </div>
            `;
        } else if (notification.notification_type === 'user_edited_by_admin' && notification.status == 'edited' && notification.user_id == user_id) {
            content = `
                <div class="notification-details">  
                    <p class="content-approved-notification">Your profile has been <strong>${notification.status}</strong> by ${notification.evaluator_name}.</p>
                    <p class="created-at-notification">${formatDate(notification.created_at)}</p>
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

function updateNotificationCount(count) {
    const notificationCount = document.getElementById('notificationCount');
    if (count > 0) {
        notificationCount.style.display = 'block';
    } else {
        notificationCount.style.display = 'none';
    }
    notificationCount.textContent = count;
}

function showConfirmApproveModal(userId, fullName) {
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
                <button type="button" class="green-button" style="margin-right: 0" onclick="approveUser(${userId}, '${fullName}')">Yes, Approve</button>
            </div>
        </div>
    `;
    modal.style.display = 'flex';
}

function showConfirmRejectModal(userId, fullName) {
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
                <button type="button" class="red-button" style="margin-right: 0" onclick="rejectUser(${userId}, '${fullName}')">Yes, Reject</button>
            </div>
        </div>
    `;
    modal.style.display = 'flex';
}

function showConfirmApproveContentModal(contentId, contentType, userId, fullName) {
    const viewContentModal = document.getElementById('viewContentModal');
    viewContentModal.style.display = 'none';
    const modal = document.getElementById('confirmApproveContentModal');
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close" onclick="closeModal('confirmApproveContent')" style="color: #334b35"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-thumbs-up" aria-hidden="true"></i></h1>
            <p>Proceed to approve this ${contentType}?</p>
            <br>
            <div style="text-align: right;">
                <button type="button" class="grey-button" onclick="closeModal('confirmApproveContent')">Cancel</button>
                <button type="button" class="green-button" style="margin-right: 0" onclick="approveContent(${contentId}, '${contentType}', ${userId}, '${fullName}')">Yes, Approve</button>
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
        <div class="modal-content">
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

function approveUser(userId, fullName) {
    Ws.send(JSON.stringify({
        action: 'approve_user',
        user_id: userId,
        full_name: fullName
    }));
    closeModal('confirmApproveUser');
}

function rejectUser(userId, fullName) {
    const evaluatorMessage = document.getElementById('evaluator_message').value;
    if (!evaluatorMessage) {
        alert('Evaluator message is required.');
        return;
    }
    Ws.send(JSON.stringify({
        action: 'reject_user',
        user_id: userId,
        full_name: fullName,
        evaluator_message: evaluatorMessage
    }));
    closeModal('confirmRejectUser');
}

function approveContent(contentId, contentType, userId, fullName) {
    Ws.send(JSON.stringify({
        action: 'approve_post',
        content_id: contentId,
        content_type: contentType,
        user_id: userId,
        full_name: fullName
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
            fetchNotifications();
            fetchNotificationCount();
            break;
        case 'update_notification':
            fetchNotifications();
            break;
        case 'delete_notification':
            fetchNotifications();
            break;
        case 'reset_notification_count':
            if (data.success) {
                document.getElementById('notificationCount').style.display = 'none';
            }
            break;
        case 'view_content':
            if (data.success) {
                console.log("View Content Data: ",data);
                const modal = document.getElementById('viewContentModal');
                const content = data.content;
                console.log("Content: ",content);
                const contentType = data.content_type;
                const formattedExpirationDateTime = formatDate(content.expiration_datetime);

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
                    <div class="modal-content" style="text-align: left; position: absolute">
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
        default:
    }
});

// Fetch notifications when the page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchNotifications();
    fetchNotificationCount();
});