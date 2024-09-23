Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    if (data.action === 'user_edited' && data.user_id === user_id) {
        alert(data.message);
        window.location.href = 'logout.php'; // Redirect to logout script
    }
});