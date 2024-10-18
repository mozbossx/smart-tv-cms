Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    if ((data.action === 'user_edited' || data.action === 'edit_user') && data.user_id === user_id) {
        alert("Your account has been updated. You will be logged out now.");
        window.location.href = 'logout.php'; // Redirect to logout script
    } else if ((data.action === 'user_deleted' || data.action === 'delete_user') && data.user_id === user_id) {
        alert("Your account has been deleted by an admin. You will be logged out now.");
        // Perform the delete_user action
        window.location.href = 'logout.php'; // Redirect to logout script
    }
});

// function handleUserSessionMessage(data) {
//     if (data.action === 'user_edited' && data.user_id === user_id) {
//         alert(data.message);
//         window.location.href = 'logout.php'; // Redirect to logout script
//     } else if ((data.action === 'user_deleted' || data.action === 'delete_user') && data.user_id === user_id) {
//         alert(data.message);
//         window.location.href = 'logout.php'; // Redirect to logout script
//     }
// }