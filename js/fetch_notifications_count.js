// Function to update the notification count
function updateNotificationCount() {
    fetch('fetch_pending_users_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
            } else {
                const combinedCount = data.combinedCount;
                document.getElementById('notificationCount').textContent = combinedCount;
            }
        })
        .catch(error => console.error('Error fetching combined count:', error));
}

document.addEventListener('DOMContentLoaded', () => {
    fetch('fetch_pending_users_count.php')
        .then(response => response.json())
        .then(data => {
             updateNotificationCount();
        })
        .catch(error => console.error('Error fetching notifications:', error));
});