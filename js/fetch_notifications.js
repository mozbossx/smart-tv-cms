const Ws = new WebSocket('ws://192.168.1.12:8081');
const notificationsContainer = document.getElementById('notificationsContainer');

const updateNotificationUI = (data) => {
    // Check if the status is 'Pending' and return early if true
    if (data.category === 'Announcement' && data.status === 'Pending') {
        showNotification(data);
        return;
    } else if (data.user_type === 'Student') {
        if (data.status === 'Pending'){
            createUserNotification(data);
        } else if (data.status === 'Rejected' || data.status === 'Approved') {
            updateUserNotification(data);
        }
    }
};

const showNotification = (data) => {
    const createdDate = new Date(`${data.created_date}T${data.created_time}`);
    const formattedDate = new Intl.DateTimeFormat('en-PH', { month: 'long', day: '2-digit', year: 'numeric' }).format(createdDate);
    const formattedTime = new Intl.DateTimeFormat('en-PH', { hour: 'numeric', minute: 'numeric', hour12: true }).format(createdDate);
    
    const expirationDate = new Date(`${data.expiration_date}T${data.expiration_time}`);
    const formattedExpirationDate = new Intl.DateTimeFormat('en-PH', { month: 'long', day: '2-digit', year: 'numeric' }).format(expirationDate);
    const formattedExpirationTime = new Intl.DateTimeFormat('en-PH', { hour: 'numeric', minute: 'numeric', hour12: true }).format(expirationDate);
    
    const approveButton = document.createElement('button');
    const rejectButton = document.createElement('button');

    const notificationDiv = document.createElement('div');
    notificationDiv.dataset.annId = data.announcements_id;

    notificationDiv.style = `
        padding: 10px;
        margin-bottom: 5px;
        border: 1px solid black;
        border-radius: 10px;
        background-color: #fff3cd;
    `;

    if (userType == 'Admin') {
        notificationDiv.innerHTML = `
            <p style="margin-bottom: 5px"><strong>Pending Announcement</strong></p>
            <pre class="ann-body">${data.ann_body}</pre>
            <p style="color: #6E6E6E"><small>${data.user_type}</small></p>
            <p style="color: #6E6E6E"><small>Posted by ${data.announcements_author} on ${formattedDate} at ${formattedTime}</small></p>
            <p style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
        `;
    } else {
        notificationDiv.innerHTML = `
            <p style="margin-bottom: 5px"><strong>You have a pending post</strong></p>
            <pre class="ann-body" style="margin-bottom: 15px">${data.ann_body}</pre>
            <p style="color: #6E6E6E"><small>Type: ${data.category}</p>
            <p style="color: #6E6E6E"><small>Posted on ${formattedDate} at ${formattedTime}</small></p>
            <p style="margin-bottom: 10px; color: #6E6E6E"><small>Expires on ${formattedExpirationDate} at ${formattedExpirationTime}</small></p>
        `;
    }
    
    approveButton.style = `
        background-color: #4e7251;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 5px;
        cursor: pointer;
    `;

    rejectButton.style = `
        background-color: rgb(126, 11, 34);
        color: white;
        border: none;
        border-radius: 5px;
        padding: 5px;
        cursor: pointer;
        margin-left: 7px;
    `;

    approveButton.innerHTML  = '<i class="fa fa-check" aria-hidden="true"></i> Approve';
    approveButton.onclick = () => showApproveContentModal(data.announcements_id);

    rejectButton.innerHTML  = '<i class="fa fa-times" aria-hidden="true"></i> Reject';

    if (userType == 'Admin') {
        notificationDiv.appendChild(approveButton);
        notificationDiv.appendChild(rejectButton);
    }

    notificationsContainer.appendChild(notificationDiv);
};

// Function to format the datetime
const formatDateTime = (datetime) => {
    const date = new Date(datetime);
    return date.toLocaleString('en-US', {
        month: 'long',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

// Function to create pending user notifications
const createUserNotification = (data) => {
    const containerDiv = document.createElement('div');
    containerDiv.dataset.userId = data.user_id;

    const contentDiv = document.createElement('div');
    const postDiv = document.createElement('div');
    const approveButton = document.createElement('button');
    const rejectButton = document.createElement('button');

    containerDiv.style = `
        padding: 10px;
        margin-bottom: 5px;
        border: 1px solid black;
        border-radius: 10px;
        background-color: #fff3cd;
        user-select: none;
        -moz-user-select: none;
        -webkit-user-drag: none;
        -webkit-user-select: none;
        -ms-user-select: none;
    `;

    rejectButton.style = `
        background-color: rgb(126, 11, 34);
        color: white;
        border: none;
        border-radius: 5px;
        padding: 5px;
        cursor: pointer;
    `;
    approveButton.style = `
        background-color: #4e7251;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 5px;
        cursor: pointer;
        margin-right: 7px;
    `;

    contentDiv.style = `
        border: none;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
        color: black;
        height: auto;
        text-align: justify;
        bottom: 0;
    `;

    const formattedDateTime = formatDateTime(data.datetime_registered);

    contentDiv.innerHTML = `
        <div style="margin-bottom: 10px">
            <div class="content-container-con">
                <pre class="peo-body" style="overflow: auto; white-space: pre-wrap"><b>${data.full_name}</b> (${data.user_type}) wants to join the CMS platform!</pre>
                <p style="color: #6E6E6E"><small>${formattedDateTime}</small></p>
                <p style="color: #6E6E6E"><small></small></p>
                <p style="color: #6E6E6E"><small>USC Email: ${data.email}</small></p>
                <p style="color: #6E6E6E"><small>Course: ${data.department}</small></p>
                <p style="color: #6E6E6E"><small>Status: <b>${data.status}</b></small></p>
            </div>
        </div>
    `;

    rejectButton.innerHTML  = '<i class="fa fa-times" aria-hidden="true"></i> Reject';
    rejectButton.onclick = () => showRejectModal(data.user_id);
    approveButton.innerHTML  = '<i class="fa fa-check" aria-hidden="true"></i> Approve';
    approveButton.onclick = () => showApproveModal(data.user_id);

    contentDiv.appendChild(approveButton);
    contentDiv.appendChild(rejectButton);
        
    containerDiv.appendChild(contentDiv);
    containerDiv.appendChild(postDiv);
    notificationsContainer.insertBefore(containerDiv, notificationsContainer.firstChild);
};

// Function to update user notification (for rejected or approved users)
const updateUserNotification = (data) => {
    const existingNotification = document.querySelector(`[data-user-id="${data.user_id}"]`);
    if (existingNotification) {
        existingNotification.remove();
    }

    const containerDiv = document.createElement('div');
    containerDiv.dataset.userId = data.user_id;

    const contentDiv = document.createElement('div');
    const approveButton = document.createElement('button');
    const rejectButton = document.createElement('button');

    rejectButton.style = `
        background-color: rgb(126, 11, 34);
        color: white;
        border: none;
        border-radius: 5px;
        padding: 5px;
        cursor: pointer;
    `;
    approveButton.style = `
        background-color: #4e7251;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 5px;
        cursor: pointer;
        margin-right: 7px;
    `;

    if (data.status === "Rejected") {
        containerDiv.style = `
            background-color: #ffcccc};
            padding: 10px;
            margin-bottom: 5px;
            border: 1px solid black;
            border-radius: 10px;
            user-select: none;
            -moz-user-select: none;
            -webkit-user-drag: none;
            -webkit-user-select: none;
            -ms-user-select: none;
        `;

        contentDiv.style = `
            border: none;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            color: black;
            height: auto;
            text-align: justify;
            bottom: 0;
        `;
        if (userType == 'Admin'){
            contentDiv.innerHTML = `
                <div class="content-container-con" style="margin-bottom: 10px">
                    <pre class="peo-body" style="overflow: auto; white-space: pre-wrap">You rejected <b>${data.full_name}</b>.</pre>
                </div>
            `;
            approveButton.innerHTML  = '<i class="fa fa-check" aria-hidden="true"></i> Approve Back';
            approveButton.onclick = () => showApproveModal(data.user_id);

            contentDiv.appendChild(approveButton);
            containerDiv.appendChild(contentDiv);
            notificationsContainer.insertBefore(containerDiv, notificationsContainer.firstChild);
        } else {
            // Display no notification
        }
    } else if (data.status === "Approved") {
        containerDiv.style = `
            background-color: #dffce5};
            padding: 10px;
            margin-bottom: 5px;
            border: 1px solid black;
            border-radius: 10px;
            user-select: none;
            -moz-user-select: none;
            -webkit-user-drag: none;
            -webkit-user-select: none;
            -ms-user-select: none;
        `;
        contentDiv.style = `
            border: none;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            color: black;
            height: auto;
            text-align: justify;
            bottom: 0;
        `;

        if (userType == 'Admin'){
            contentDiv.innerHTML = `
                <div class="content-container-con" style="margin-bottom: 10px">
                    <pre class="peo-body" style="overflow: auto; white-space: pre-wrap"><i class="fa fa-check-circle" aria-hidden="true"></i> You approved <b>${data.full_name}</b>.</pre>
                </div>
            `;
            containerDiv.appendChild(contentDiv);
            notificationsContainer.insertBefore(containerDiv, notificationsContainer.firstChild);
        } else if (userType != 'Admin' && data.full_name == full_name){
            contentDiv.innerHTML = `
                <div class="content-container-con" style="margin-bottom: 10px">
                    <pre class="peo-body" style="overflow: auto; white-space: pre-wrap"><i class="fa fa-check-circle" aria-hidden="true"></i> Your registration got <b>approved</b>!</pre>
                </div>
            `;
            containerDiv.appendChild(contentDiv);
            notificationsContainer.insertBefore(containerDiv, notificationsContainer.firstChild);
        }
    }
};

const insertApproveModalContent = () => {
    const modalContainer = document.getElementById('confirmApproveModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="green-bar-vertical">
                <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
                <p id="approveMessage" style="text-align: center">Are you sure to approve this user?</p>
                <br>
                <div style="text-align: right;">
                    <button class="cancel-button" type="button" onclick="closeModal('confirmApprove')">Cancel</button>
                    <button id="proceedBtn" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to approve</b></button>
                </div>
            </div>
        </div>
    `;
};

const insertApproveContentModalContent = () => {
    const modalContainer = document.getElementById('confirmApproveContentModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="green-bar-vertical">
                <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
                <p id="approveMessage" style="text-align: center">Are you sure to approve this Announcement?</p>
                <br>
                <div style="text-align: right;">
                    <button class="cancel-button" type="button" onclick="closeModal('confirmApproveContent')">Cancel</button>
                    <button id="proceedBtn_ann" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to approve</b></button>
                </div>
            </div>
        </div>
    `;
};

const insertRejectModalContent = () => {
    const modalContainer = document.getElementById('confirmRejectModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-times-circle" aria-hidden="true"></i></h1>
                <p id="approveMessage" style="text-align: center">Are you sure to reject this user?</p>
                <br>
                <div style="text-align: right;">
                    <button class="cancel-button" type="button" onclick="closeModal('confirmReject')">Cancel</button>
                    <button id="proceedBtn1" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to reject</b></button>
                </div>
            </div>
        </div>
    `;
};

const showApproveModal = (userId) => {
    insertApproveModalContent(); 
    const modal = document.getElementById('confirmApproveModal');
    const proceedBtn = document.getElementById('proceedBtn');

    modal.style.display = 'flex';

    proceedBtn.onclick = () => {
        approveUser(userId);
        modal.style.display = 'none';
    };
};

const showApproveContentModal = (annId) => {
    insertApproveContentModalContent(); 
    const modal = document.getElementById('confirmApproveContentModal');
    const proceedBtn = document.getElementById('proceedBtn_ann');

    modal.style.display = 'flex';

    proceedBtn.onclick = () => {
        approveContent(annId);
        modal.style.display = 'none';
    };
};

const approveUser = (user_id) => {
    const approveUserData = { 
        action: 'approve_user', 
        user_id: user_id 
    };
    Ws.send(JSON.stringify(approveUserData));
};

const approveContent = (announcements_id) => {
    const approveContentData = { 
        action: 'approve_post', 
        announcements_id: announcements_id 
    };
    Ws.send(JSON.stringify(approveContentData));
};

const showRejectModal = (userId) => {
    insertRejectModalContent();
    const modal = document.getElementById('confirmRejectModal');
    const proceedBtn = document.getElementById('proceedBtn1');

    modal.style.display = 'flex';

    proceedBtn.onclick = () => {
        rejectUser(userId);
        modal.style.display = 'none';
    };
};

const rejectUser = (user_id) => {
    const rejectUserData = { 
        action: 'reject', 
        user_id: user_id 
    };
    Ws.send(JSON.stringify(rejectUserData));
};

// Handle WebSocket messages
Ws.addEventListener('message', function (event) {
    const data = JSON.parse(event.data);
    console.log("Received WebSocket message:", data);  // Debugging line

    if (data.action === 'approve_user') {
        if (data.success) {
            const pendingUserDiv = document.querySelector(`[data-user-id="${data.user_id}"]`);
            if (pendingUserDiv) {
                pendingUserDiv.remove();
            }
            updateNotificationCount();
        } else {
            alert('Error approving user.');
        }
    } else if (data.action === 'approve_post') {
        console.log("Processing approve_post action:", data);  // Debugging line

        if (data.success && data.announcement && data.announcement.announcements_id) {
            const announcements_id = data.announcement.announcements_id;
            const pendingContentDiv = document.querySelector(`[data-ann-id="${announcements_id}"]`);

            console.log("Looking for pendingContentDiv with announcements_id:", announcements_id);  // Debugging line
            console.log("Found pendingContentDiv:", pendingContentDiv);  // Debugging line

            if (pendingContentDiv) {
                console.log("Removing pendingContentDiv:", pendingContentDiv);  // Debugging line
                pendingContentDiv.remove();
            } else {
                console.error("No pendingContentDiv found for announcements_id:", announcements_id);  // Debugging line
            }
        } else {
            console.error("Invalid data or announcements_id missing in response:", data);  // Debugging line
        }

        updateNotificationCount();
    } else if (data.action === 'reject') {
        if (data.success) {
            const rejectedUserDiv = document.querySelector(`[data-user-id="${data.user_id}"]`);
            if (rejectedUserDiv) {
                rejectedUserDiv.remove();
            }
            updateNotificationCount();
        } else {
            alert('Error rejecting user.');
        }
    } else {
        // Handling other actions and default case
        console.log("Handling other action or default case:", data);  // Debugging line
        
        if (!data.full_name) {
            console.warn("Received data without full_name:", data);  // Debugging line
            data.full_name = "Unknown User";  // Provide a default value for full_name
        }

        if (data.category === 'Announcement' && data.status === 'Pending') {
            showNotification(data);
        } else if (data.user_type === 'Student') {
            if (data.status === 'Pending') {
                createUserNotification(data);
            } else if (data.status === 'Rejected' || data.status === 'Approved') {
                updateUserNotification(data);
            }
        }

        updateNotificationCount();
    }
});

// Fetch announcements and users on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    fetch('database/fetch_announcements.php')
        .then(response => response.json())
        .then(data => 
            data.forEach(announcement => 
                updateNotificationUI({ ...announcement, 
                    type: 'announcement' })))
        .catch(error => console.error('Error fetching announcements:', error));

    fetch('database/fetch_users.php')
        .then(response => response.json())
        .then(data => {
            data.forEach(user => updateNotificationUI({ ...user, type: 'user' }));
            updateNotificationCount();
        })
        .catch(error => console.error('Error fetching users:', error));
});

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