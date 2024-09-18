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

function updateNotificationCount(count) {
    const notificationCount = document.getElementById('notificationCount');
    if (count > 0) {
        notificationCount.textContent = count;
        notificationCount.style.display = 'inline';
    } else {
        notificationCount.style.display = 'none';
    }
}

// Fetch notification count when the page loads
document.addEventListener('DOMContentLoaded', fetchNotificationCount);

// Fetch notification count every 30 seconds
setInterval(fetchNotificationCount, 30000);

// Listen for WebSocket messages
const Ws = new WebSocket('ws://192.168.1.13:8081');

Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    if (data.action === 'new_notification' || data.action === 'update_notification') {
        fetchNotificationCount();
    }
});