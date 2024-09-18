const Ws = new WebSocket('ws://192.168.1.13:8081');
console.log(user_id);
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

function updateNotificationsUI(notifications) {
    const notificationsContainer = document.getElementById('notificationsContainer');
    notificationsContainer.innerHTML = '';

    if (notifications.length === 0) {
        notificationsContainer.innerHTML = '<p>No new notifications.</p>';
    } else {
        notifications.forEach(notification => {
            const notificationDiv = createNotificationDiv(notification);
            notificationsContainer.appendChild(notificationDiv);
        });
    }

    updateNotificationCount(notifications.length);
}

function createNotificationDiv(notification) {
    const notificationDiv = document.createElement('div');
    notificationDiv.className = 'notification';
    notificationDiv.style = `
        background: #dffce5;
        margin-bottom: 10px;
        border-radius: 15px;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        padding: 15px;
    `;

    let content = '';
    if (notification.notification_type === 'user_registration') {
        content = `
            <p class="user-registration-notification"><strong>${notification.full_name}</strong> has registered as a <strong>${notification.user_type}</strong>.</p>
            <p class="user-registration-notification">${notification.created_at}</p>
        `;
        if (userType === 'Admin') {
            content += `
                <button type="button" class="green-button" onclick="approveUser(${notification.user_id})"><i class="fa fa-check"></i> Approve</button>
                <button type="button" class="red-button" onclick="rejectUser(${notification.user_id})"><i class="fa fa-user-times"></i> Reject</button>
            `;
        }
    } else if (notification.notification_type === 'content_post') {
        if (notification.user_id == user_id) {
            content = `
                <p class="content-post-notification">You posted a new ${notification.content_type}. Waiting for approval.</p>
                <p class="content-post-notification">${notification.created_at}</p>
            `;
        } else {
            content = `
                <p class="content-post-notification"><strong>${notification.full_name}</strong> has posted a new ${notification.content_type}.</p>
                <p class="content-post-notification">${notification.created_at}</p>
            `;
        }
        if (userType === 'Admin') {
            content += `
                <button type="button" class="green-button" onclick="approveContent(${notification.content_id}, '${notification.content_type}', ${notification.user_id})"><i class="fa fa-check"></i> Approve Post</button>
                <button type="button" class="red-button" onclick="rejectContent(${notification.content_id}, '${notification.content_type}', ${notification.user_id})"><i class="fa fa-user-times"></i> Reject Post</button>
            `;
        }
    } else if (notification.notification_type === 'content_approved' && notification.user_id == user_id && notification.status == 'pending') {
        content = `
            <p class="content-approved-notification">Your ${notification.content_type} has been approved.</p>
            <p class="content-approved-notification">${notification.created_at}</p>
        `;
    }
    notificationDiv.innerHTML = content;
    return notificationDiv;
}

function updateNotificationCount(count) {
    const notificationCount = document.getElementById('notificationCount');
    notificationCount.textContent = count;
}

function approveUser(userId) {
    Ws.send(JSON.stringify({
        action: 'approve_user',
        user_id: userId
    }));
}

function rejectUser(userId) {
    Ws.send(JSON.stringify({
        action: 'reject_user',
        user_id: userId
    }));
}

function approveContent(contentId, contentType, userId) {
    console.log(contentId, contentType, userId);
    Ws.send(JSON.stringify({
        action: 'approve_post',
        content_id: contentId,
        content_type: contentType,
        user_id: userId
    }));
}

function rejectContent(contentId, contentType) {
    Ws.send(JSON.stringify({
        action: 'reject_post',
        content_id: contentId,
        content_type: contentType
    }));
}

Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    if (data.action === 'new_notification') {
        fetchNotifications();
    } else if (data.action === 'update_notification') {
        fetchNotifications();
    }
});

// Fetch notifications when the page loads
document.addEventListener('DOMContentLoaded', fetchNotifications);