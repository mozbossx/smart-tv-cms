const Ws = new WebSocket('ws://192.168.1.13:8081');

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

    notificationsContainer.innerHTML = '';

    if (notifications.length === 0) {
        notificationsContainer.innerHTML = '<p>No new notifications.</p>';
    } else {
        notifications.forEach(notification => {
            const notificationDiv = createNotificationDiv(notification);
            if (notificationDiv) {
                notificationsContainer.appendChild(notificationDiv);
            }
        });
    }
}

function createNotificationDiv(notification) {
    const notificationDiv = document.createElement('div');
    notificationDiv.className = 'notification';

    let content = '';

    if (userType === 'Admin') {
        if (notification.notification_type === 'user_registration' && notification.status == 'pending') {
            content = `
                <div class="notification-details">
                    <p class="user-registration-notification"><strong>${notification.full_name}</strong> has registered as a <strong>${notification.user_type}</strong>.</p>
                    <p class="created-at-notification">${notification.created_at}</p>
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
                    <p class="created-at-notification">${notification.created_at}</p>
                </div>
            `;
        } else if (notification.notification_type === 'user_rejected' && notification.status == 'rejected' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="user-approved-notification">You <strong>rejected</strong> ${notification.full_name}'s registration.</p>
                    <p class="created-at-notification">${notification.created_at}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_post' && notification.status == 'pending') {
            content = `
                <div class="notification-details">
                    <p class="content-post-notification"><strong>${notification.full_name}</strong> wants to post a <strong>new ${notification.content_type}</strong>.</p>
                    <p class="created-at-notification">${notification.created_at}</p>
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
                    <p class="created-at-notification">${notification.created_at}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_approved' && notification.status == 'approved' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You <strong>approved</strong> ${notification.full_name}'s ${notification.content_type}.</p>
                    <p class="created-at-notification">${notification.created_at}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_rejected' && notification.status == 'rejected' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You <strong>rejected</strong> ${notification.full_name}'s ${notification.content_type}.</p>
                    <p class="created-at-notification">${notification.created_at}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_deleted' && notification.status == 'deleted' && notification.evaluator_name == full_name) {
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">You <strong>deleted</strong> ${notification.full_name}'s ${notification.content_type}.</p>
                    <p class="created-at-notification">${notification.created_at}</p>
                </div>
            `;
        }
    } else if (userType === 'Student' || userType === 'Faculty') {
        if (notification.notification_type === 'content_post' && notification.status == 'pending' && notification.user_id == user_id) {
            content = `
            <div class="notification-details">
                <p class="content-post-notification">Your new ${notification.content_type} is <strong> waiting for approval.</strong></p>
                <p class="created-at-notification">${notification.created_at}</p>
            </div>
            `;
        } else if (notification.notification_type === 'content_approved_by_admin' && notification.status == 'approved' && notification.user_id == user_id) {
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">Your ${notification.content_type} has been <strong>approved by </strong> ${notification.evaluator_name}.</p>
                    <p class="created-at-notification">${notification.created_at}</p>
                </div>
            `;
        } else if (notification.notification_type === 'content_rejected_by_admin' && notification.status == 'rejected' && notification.user_id == user_id) {
            content = `
                <div class="notification-details">
                    <p class="content-rejected-notification">Your ${notification.content_type} has been <strong>rejected by </strong> ${notification.evaluator_name}.</p>
                    <p class="created-at-notification">${notification.created_at}</p>
                </div>
            `;
        } else if (notification.notification_type === 'user_approved_by_admin' && notification.status == 'approved' && notification.user_id == user_id) {
            content = `
                <div class="notification-details">
                    <p class="content-approved-notification">Your registration has been <strong>approved by </strong> ${notification.evaluator_name}.</p>
                    <p class="created-at-notification">${notification.created_at}</p>
                </div>
            `;
        } else if (notification.notification_type === 'user_rejected_by_admin' && notification.status == 'rejected' && notification.user_id == user_id) {
            content = `
                <div class="notification-details">  
                    <p class="content-approved-notification">Your <strong>${notification.content_type}</strong> has been <strong>approved</strong> by ${notification.evaluator_name}.</p>
                    <p class="created-at-notification">${notification.created_at}</p>
                </div>
            `;
        }
    }

    // If content is still empty, it means no condition was met
    if (content == '') {
        return null;
    }

    // delete button
    content += `<button class="delete-notification" onclick="deleteNotification(${notification.notification_id})"><i class="fa fa-times-circle" aria-hidden="true"></i></button>`;
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
            <h2>Confirm Approval</h2>
            <p>Are you sure you want to approve this user?</p>
            <button onclick="approveUser(${userId}, '${fullName}')">Yes, Approve</button>
            <button onclick="closeModal('confirmApprove')">Cancel</button>
        </div>
    `;
    modal.style.display = 'flex';
}

function showConfirmRejectModal(userId, fullName) {
    const modal = document.getElementById('confirmRejectUserModal');
    modal.innerHTML = `
        <div class="modal-content">
            <h2>Confirm Rejection</h2>
            <p>Are you sure you want to reject this user?</p>
            <button onclick="rejectUser(${userId}, '${fullName}')">Yes, Reject</button>
            <button onclick="closeModal('confirmReject')">Cancel</button>
        </div>
    `;
    modal.style.display = 'flex';
}

function showConfirmApproveContentModal(contentId, contentType, userId, fullName) {
    const modal = document.getElementById('confirmApproveContentModal');
    modal.innerHTML = `
        <div class="modal-content">
            <h2>Confirm Content Approval</h2>
            <p>Are you sure you want to approve this ${contentType}?</p>
            <button onclick="approveContent(${contentId}, '${contentType}', ${userId}, '${fullName}')">Yes, Approve</button>
            <button onclick="closeModal('confirmApproveContent')">Cancel</button>
        </div>
    `;
    modal.style.display = 'flex';
}

function showConfirmRejectContentModal(contentId, contentType, userId, fullName) {
    const modal = document.getElementById('confirmRejectContentModal');
    modal.innerHTML = `
        <div class="modal-content">
            <h2>Confirm Content Rejection</h2>
            <p>Are you sure you want to reject this ${contentType}?</p>
            <button onclick="rejectContent(${contentId}, '${contentType}', ${userId}, '${fullName}')">Yes, Reject</button>
            <button onclick="closeModal('confirmRejectContent')">Cancel</button>
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
    closeModal('confirmApprove');
}

function rejectUser(userId, fullName) {
    Ws.send(JSON.stringify({
        action: 'reject_user',
        user_id: userId,
        full_name: fullName
    }));
    closeModal('confirmReject');
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
    Ws.send(JSON.stringify({
        action: 'reject_post',
        content_id: contentId,
        content_type: contentType,
        user_id: userId,
        full_name: fullName
    }));
    closeModal('confirmRejectContent');
}

function closeModal(modalId) {
    var modal = document.getElementById(modalId + 'Modal');
    modal.style.display = 'none';
}

function deleteNotification(notificationId) {
    Ws.send(JSON.stringify({
        action: 'delete_notification',
        notification_id: notificationId
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
        default:
            console.log('Unknown action:', data.action);
    }
});

// Fetch notifications when the page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchNotifications();
    fetchNotificationCount();
});