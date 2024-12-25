const Ws = new WebSocket('ws://10.0.0.41:8081');

Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    if (data.action === 'new_notification' || data.action === 'update_notification') {
        if (typeof fetchNotifications === 'function') {
            fetchNotifications();
        }
        if (typeof fetchNotificationCount === 'function') {
            fetchNotificationCount();
        }
    }
});