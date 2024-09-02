const ws = new WebSocket('ws://192.168.1.19:8081');
const userTableContainer = document.getElementById('userTableContainer');

const loggedInUserId = document.getElementById('user-data').getAttribute('data-user-id');
const displayUserTable = (data) => {
    let tableHtml = `
        <table id="usersTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">Full Name</th>
                    <th onclick="sortTable(1)">Email</th>
                    <th onclick="sortTable(2)">Role</th>
                    <th onclick="sortTable(3)">Department</th>
                    <th onclick="sortTable(4)">Status</th>
                    <th onclick="sortTable(5)">Date Registered</th>
                    <th onclick="sortTable(6)">Date Approved</th>
                    <th onclick="sortTable(7)">Evaluated By</th>
                    <th onclick="sortTable(8)">Message</th>
                    <th>Operations</th>
                </tr>
            </thead>
            <tbody>`;

    data.forEach(row => {
        const datetimeRegistered = new Date(row.datetime_registered);
        const datetimeRegisteredFormatted = datetimeRegistered.toLocaleString('en-US', {
            month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true
        });

        const datetimeApproved = new Date(row.datetime_approved);
        const datetimeApprovedFormatted = datetimeApproved.toLocaleString('en-US', {
            month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true
        });

        const statusTd = row.status === 'Approved' ? 
            `<td style="text-align:center; color: green">${row.status}</td>` : 
            `<td style="text-align:center; color: red">${row.status}</td>`;

        tableHtml += `<tr data-user-id="${row.user_id}">
            <td style="text-align:left;">${row.full_name}</td>
            <td style="text-align:left;">${row.email}</td>
            <td style="text-align:center;">${row.user_type}</td>
            <td style="text-align:center;">${row.department}</td>
            ${statusTd}
            <td style="text-align:left;">${datetimeRegisteredFormatted}</td>
            <td style="text-align:left;">${datetimeApprovedFormatted}</td>
            <td style="text-align:left;">${row.evaluated_by}</td>
            <td style="text-align:left;">${row.evaluated_message}</td>
            <td style="text-align:center;">
                ${String(row.user_id) !== String(loggedInUserId) ? `
                    ${row.status === 'Pending' ? `
                        <button type="button" class="green-button" onclick="showApproveUserModal(${row.user_id})">Approve</button>
                        <br>
                        <button type="button" class="red-button" onclick="showRejectUserModal(${row.user_id})">Reject</button>
                    ` : row.status === 'Rejected' ? `
                        <button type="button" class="green-button" onclick="showApproveUserModal(${row.user_id})">Approve</button>
                        <br>
                        <button type="button" class="red-button" onclick="showDeleteUserModal(${row.user_id})">Delete</button>
                    ` : `
                        <button type="button" class="green-button" onclick="showEditUserModal(${row.user_id})">Edit</button>
                        <br>
                        <button type="button" class="red-button" onclick="showDeleteUserModal(${row.user_id})">Delete</button>
                    `}
                ` : ''}
            </td>
        </tr>`;
    });

    tableHtml += `</tbody></table>`;
    userTableContainer.innerHTML = tableHtml;
};

/* ============== APPROVE USER ============== */
// Modal content insertion
const insertApproveUserModalContent = (userId) => {
    const modalContainer = document.getElementById('confirmApproveUserModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="green-bar-vertical">
                <span class="close" id="closeApproveUserModalButton" style="color: #334b35"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-check" aria-hidden="true"></i></h1>
                <p id="approveMessage" style="text-align: center">Are you sure you want to approve this user?</p>
                <div class="floating-label-container">
                    <textarea name="evaluated_message" rows="6" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="evaluated_message"></textarea>
                    <label for="evaluated_message" style="background: #FFFF; width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User</label>
                </div>
                <br>
                <div style="text-align: right;">
                    <button id="cancelApproveUserModalButton" class="cancel-button" type="button">Cancel</button>
                    <button id="approveUserButton" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to approve</b></button>
                </div>
            </div>
        </div>
    `;
}

// Show approve user modal
const showApproveUserModal = (userId) => {
    insertApproveUserModalContent(userId);
    const modal = document.getElementById('confirmApproveUserModal');
    const approveUserButton = document.getElementById('approveUserButton');
    const cancelApproveUserModalButton = document.getElementById('cancelApproveUserModalButton');
    const closeApproveUserModalButton = document.getElementById('closeApproveUserModalButton');

    modal.style.display = 'flex';

    approveUserButton.onclick = () => {
        // Approve user logic here
        approveUser(userId);
        modal.style.display = 'none';
    };

    cancelApproveUserModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    closeApproveUserModalButton.onclick = () => {
        modal.style.display = 'none';
    }
};

// Function to approve user
const approveUser = (user_id) => {
    const evaluatedMessage = document.getElementById('evaluated_message').value;

    // Fetch the WebSocket URL from PHP script
    fetch('websocket_conn.php')
        .then(response => response.text())
        .then(url => {
            const ws = new WebSocket(url);

            // Data to send to WebSocket server
            const data = {
                action: 'approve_user',
                user_id: user_id,
                evaluated_message: evaluatedMessage
            };

            // Send data to WebSocket server
            ws.onopen = function () {
                ws.send(JSON.stringify(data));
            };

            // Handle messages from WebSocket server
            ws.onmessage = function (event) {
                const message = JSON.parse(event.data);
                if (message.success) {
                    // Optional: Handle success, if needed
                    console.log('User approved successfully');
                } else {
                    console.error('Failed to approve user');
                }
            };

            // Close WebSocket connection
            ws.onclose = function () {
                console.log('WebSocket connection closed');
            };

            // Handle errors
            ws.onerror = function (error) {
                console.error('WebSocket error:', error);
            };
        })
        .catch(error => {
            console.error('Error fetching WebSocket URL:', error);
        });
};

/* ============== REJECT USER ============== */
// Modal content insertion
const insertRejectUserModalContent = (userId) => {
    const modalContainer = document.getElementById('confirmRejectUserModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeRejectUserModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: rgb(126, 11, 34); font-size: 50px"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></h1>
                <p id="rejectMessage" style="text-align: center">Are you sure you want to reject this user?</p>
                <div class="floating-label-container">
                    <textarea name="evaluated_message" rows="6" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="evaluated_message"></textarea>
                    <label for="evaluated_message" style="background: #FFFF; width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User</label>
                </div>
                <br>
                <div style="text-align: right;">
                    <button id="cancelRejectUserModalButton" class="cancel-button" type="button">Cancel</button>
                    <button id="rejectUserButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to reject</b></button>
                </div>
            </div>
        </div>
    `;
}

// Show reject user modal
const showRejectUserModal = (userId) => {
    insertRejectUserModalContent(userId);
    const modal = document.getElementById('confirmRejectUserModal');
    const rejectUserButton = document.getElementById('rejectUserButton');
    const cancelRejectUserModalButton = document.getElementById('cancelRejectUserModalButton');
    const closeRejectUserModalButton = document.getElementById('closeRejectUserModalButton');

    modal.style.display = 'flex';

    rejectUserButton.onclick = () => {
        // Reject user logic here
        rejectUser(userId);
        modal.style.display = 'none';
    };

    cancelRejectUserModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    closeRejectUserModalButton.onclick = () => {
        modal.style.display = 'none';
    }
};

// Function to reject user
const rejectUser = (user_id) => {
    const evaluatedMessage = document.getElementById('evaluated_message').value;

    // Fetch the WebSocket URL from PHP script
    fetch('websocket_conn.php')
        .then(response => response.text())
        .then(url => {
            const ws = new WebSocket(url);

            // Data to send to WebSocket server
            const data = {
                action: 'reject_user',
                user_id: user_id,
                evaluated_message: evaluatedMessage
            };

            // Send data to WebSocket server
            ws.onopen = function () {
                ws.send(JSON.stringify(data));
            };

            // Handle messages from WebSocket server
            ws.onmessage = function (event) {
                const message = JSON.parse(event.data);
                if (message.success) {
                    // Optional: Handle success, if needed
                    console.log('User rejectd successfully');
                } else {
                    console.error('Failed to reject user');
                }
            };

            // Close WebSocket connection
            ws.onclose = function () {
                console.log('WebSocket connection closed');
            };

            // Handle errors
            ws.onerror = function (error) {
                console.error('WebSocket error:', error);
            };
        })
        .catch(error => {
            console.error('Error fetching WebSocket URL:', error);
        });
};

/* ============== DELETE USER ============== */
// Modal content insertion
const insertDeleteUserModalContent = (userId) => {
    const modalContainer = document.getElementById('confirmDeleteUserModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeDeleteUserModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: rgb(126, 11, 34); font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Are you sure you want to delete this user?</p>
                <div class="floating-label-container">
                    <textarea name="evaluated_message" rows="6" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area" id="evaluated_message"></textarea>
                    <label for="evaluated_message" style="background: #FFFF; width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User</label>
                </div>
                <br>
                <div style="text-align: right;">
                    <button id="cancelDeleteUserModalButton" class="cancel-button" type="button">Cancel</button>
                    <button id="deleteUserButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

// Show delete user modal
const showDeleteUserModal = (userId) => {
    insertDeleteUserModalContent(userId);
    const modal = document.getElementById('confirmDeleteUserModal');
    const deleteUserButton = document.getElementById('deleteUserButton');
    const cancelDeleteUserModalButton = document.getElementById('cancelDeleteUserModalButton');
    const closeDeleteUserModalButton = document.getElementById('closeDeleteUserModalButton');

    modal.style.display = 'flex';

    deleteUserButton.onclick = () => {
        // Delete user logic here
        deleteUser(userId);
        modal.style.display = 'none';
    };

    cancelDeleteUserModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    closeDeleteUserModalButton.onclick = () => {
        modal.style.display = 'none';
    }
};

// Function to Delete user
const deleteUser = (user_id) => {
    const evaluatedMessage = document.getElementById('evaluated_message').value;

    // Fetch the WebSocket URL from PHP script
    fetch('websocket_conn.php')
        .then(response => response.text())
        .then(url => {
            const ws = new WebSocket(url);

            // Data to send to WebSocket server
            const data = {
                action: 'delete_user',
                user_id: user_id,
                evaluated_message: evaluatedMessage
            };

            // Send data to WebSocket server
            ws.onopen = function () {
                ws.send(JSON.stringify(data));
            };

            // Handle messages from WebSocket server
            ws.onmessage = function (event) {
                const message = JSON.parse(event.data);
                if (message.success) {
                    // Optional: Handle success, if needed
                    console.log('User deleted successfully');
                } else {
                    console.error('Failed to delete user');
                }
            };

            // Close WebSocket connection
            ws.onclose = function () {
                console.log('WebSocket connection closed');
            };

            // Handle errors
            ws.onerror = function (error) {
                console.error('WebSocket error:', error);
            };
        })
        .catch(error => {
            console.error('Error fetching WebSocket URL:', error);
        });
};

/* ============== EDIT USER DETAILS ============== */
// Function to populate the edit user modal with user data
const populateEditUserModal = (userId) => {
    const modalContainer = document.getElementById('editUserModal');
    modalContainer.innerHTML = `
        <div class="modal-content">
            <div class="green-bar-vertical">
                <span class="close" id="closeEditUserModalButton" style="color: #334b35"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-pencil" aria-hidden="true"></i></h1>
                <p id="editMessage" style="text-align: center">Edit User</p>
                <form id="editUserForm">
                    <div class="floating-label-container">
                        <input type="text" name="full_name" id="edit_full_name" placeholder=" " class="floating-label-input-text-area" style="background: none; box-shadow: none; pointer-events: none" readonly>
                        <label for="edit_full_name" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Full Name</label>
                    </div>
                    <div class="floating-label-container">
                        <input type="email" name="email" id="edit_email" placeholder=" " class="floating-label-input-text-area" style="background: none; box-shadow: none; pointer-events: none" readonly>
                        <label for="edit_email" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">USC Email</label>
                    </div>
                    <div class="floating-label-container">
                        <select name="user_type" id="edit_user_type" class="floating-label-input-text-area">
                            <option value="Admin">Admin</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Student">Student</option>
                        </select>
                        <label for="edit_user_type" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Role</label>
                    </div>
                    <div class="floating-label-container">
                        <select name="department" id="edit_department" class="floating-label-input">
                            <option value="">~</option>
                            <option value="COMPUTER ENGINEERING">Department of Computer Engineering</option>
                            <option value="CHEMICAL ENGINEERING">Department of Chemical Engineering</option>
                            <option value="CIVIL ENGINEERING">Department of Civil Engineering</option>
                            <option value="INDUSTRIAL ENGINEERING">Department of Industrial Engineering</option>
                            <option value="ELECTRICAL ENGINEERING">Department of Electrical Engineering</option>
                            <option value="MECHANICAL ENGINEERING">Department of Mechanical Engineering</option>
                            <option value="ELECTRONICS ENGINEERING">Department of Electronics Engineering</option>
                        </select>
                        <label for="edit_department" class="floating-label">Department</label>
                    </div>
                    <br>
                    <div style="text-align: right;">
                        <button type="button" id="cancelEditUserModalButton" class="cancel-button">Cancel</button>
                        <button type="submit" id="editUserButton" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Update</b></button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // Fetch user data based on userId
    fetch(`database/fetch_users.php?userId=${userId}`)
        .then(response => response.json())
        .then(user => {
            console.log('Fetched user data:', user); // Log fetched user data to check if it's correct
            if (user) {
                document.getElementById('edit_full_name').value = user.full_name || '';
                document.getElementById('edit_email').value = user.email || '';
                document.getElementById('edit_user_type').value = user.user_type || '';
                document.getElementById('edit_department').value = user.department || '';
            } else {
                console.error('User data is null or undefined');
            }
        })
        .catch(error => console.error('Error fetching user data:', error));
};

// Show edit user modal
const showEditUserModal = (userId) => {
    populateEditUserModal(userId);
    const modal = document.getElementById('editUserModal');
    const cancelEditUserModalButton = document.getElementById('cancelEditUserModalButton');
    const closeEditUserModalButton = document.getElementById('closeEditUserModalButton');

    modal.style.display = 'flex';

    // Handle form submission
    const editUserForm = document.getElementById('editUserForm');
    editUserForm.onsubmit = function(event) {
        event.preventDefault(); // Prevent the default form submission
        const fullName = document.getElementById('edit_full_name').value;
        const email = document.getElementById('edit_email').value;
        const userType = document.getElementById('edit_user_type').value;
        const department = document.getElementById('edit_department').value;

        editUser(userId, fullName, email, userType, department);
        modal.style.display = 'none';
    };
  
    cancelEditUserModalButton.onclick = function() {
        modal.style.display = 'none';
    };

    closeEditUserModalButton.onclick = function() {
        modal.style.display = 'none';
    };

    // Close modal when clicking outside the modal content
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };
};

const editUser = (user_id, full_name, email, user_type, department) => {
    // input field values ?

    // Fetch the WebSocket URL from PHP script
    fetch('websocket_conn.php')
        .then(response => response.text())
        .then(url => {
            const ws = new WebSocket(url);

            // Data to send to WebSocket server
            const data = {
                action: 'edit_user',
                user_id: user_id,
                full_name: full_name,
                email: email,
                user_type: user_type,
                department: department
            };

            // Send data to WebSocket server
            ws.onopen = function () {
                ws.send(JSON.stringify(data));
            };

            // Handle messages from WebSocket server
            ws.onmessage = function (event) {
                const message = JSON.parse(event.data);
                if (message.success) {
                    // Optional: Handle success, if needed
                    console.log('User edited successfully');
                } else {
                    console.error('Failed to edit user');
                }
            };

            // Close WebSocket connection
            ws.onclose = function () {
                console.log('WebSocket connection closed');
            };

            // Handle errors
            ws.onerror = function (error) {
                console.error('WebSocket error:', error);
            };
        })
        .catch(error => {
            console.error('Error fetching WebSocket URL:', error);
        });
};

ws.addEventListener('message', function (event) {
    const data = JSON.parse(event.data);
    if (data.action === 'approve_user' && data.success) {
        // Fetch all users and refresh the table
        fetch('database/fetch_users.php')
            .then(response => response.json())
            .then(data => {
                displayUserTable(data);
                updateNotificationCount();
            })
            .catch(error => console.error('Error fetching updated users:', error));
    } else if (data.action === 'reject_user' && data.success) {
        // Fetch all users and refresh the table
        fetch('database/fetch_users.php')
            .then(response => response.json())
            .then(data => {
                displayUserTable(data);
                updateNotificationCount();
            })
            .catch(error => console.error('Error fetching updated users:', error));
    } else if (data.action === 'delete_user' && data.success) {
        // Fetch all users and refresh the table
        fetch('database/fetch_users.php')
            .then(response => response.json())
            .then(data => {
                displayUserTable(data);
                updateNotificationCount();
            })
            .catch(error => console.error('Error fetching updated users:', error));
    } else if (data.action === 'edit_user' && data.success) {
        // Fetch all users and refresh the table
        fetch('database/fetch_users.php')
            .then(response => response.json())
            .then(data => {
                displayUserTable(data);
                updateNotificationCount();
            })
            .catch(error => console.error('Error fetching updated users:', error));
    } 
});

document.addEventListener('DOMContentLoaded', () => {
    fetch('database/fetch_users.php')
        .then(response => response.json())
        .then(data => {
            displayUserTable(data);
        })
        .catch(error => console.error('Error fetching users:', error));
});
