const Ws = new WebSocket('wss://usccms.online:8081');

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