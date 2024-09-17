const ws = new WebSocket('ws://192.168.1.13:8081');
const userTableContainer = document.getElementById('userTableContainer');

const loggedInUserId = document.getElementById('user-data').getAttribute('data-user-id');

const displayUserTable = (data) => {
    let tableHtml = `
        <table id="usersTable">
            <thead>
                <tr style="user-select: none;">
                    <th onclick="sortTable(0)">User ID</th>
                    <th onclick="sortTable(1)">Full Name</th>
                    <th onclick="sortTable(2)">Email</th>
                    <th onclick="sortTable(3)">Role</th>
                    <th onclick="sortTable(4)">Department</th>
                    <th onclick="sortTable(5)">Status</th>
                    <th onclick="sortTable(6)">Date Registered</th>
                    <th onclick="sortTable(7)">Date Approved</th>
                    <th onclick="sortTable(8)">Evaluated By</th>
                    <th>Reject Message</th>
                    <th>Operations</th>
                </tr>
            </thead>
            <tbody>`;

    data.forEach(row => {
        const { user_id, full_name, email, user_type, department, status, datetime_registered, datetime_approved, evaluated_by, evaluated_message } = row;
        const datetimeRegisteredFormatted = formatDate(datetime_registered);
        const datetimeApprovedFormatted = formatDate(datetime_approved);

        // Dynamically build the status cell based on the status value
        let statusTd;
        if (status === 'Approved') {
            statusTd = `<td style="text-align:center; color: green">${status}</td>`;
        } else if (status === 'Rejected') {
            statusTd = `<td style="text-align:center; color: red">${status}</td>`;
        } else if (status === 'Pending') {
            statusTd = `<td style="text-align:center; color: #0300c4">${status}</td>`;
        } 

        tableHtml += `
            <tr data-user-id="${user_id}">
                <td style="text-align:left;">${user_id}</td>
                <td style="text-align:left;">${full_name}</td>
                <td style="text-align:left;">${email}</td>
                <td style="text-align:center;">${user_type}</td>
                <td style="text-align:center;">${department}</td>
                ${statusTd}
                <td style="text-align:left;">${datetimeRegisteredFormatted}</td>
                <td style="text-align:left;">${datetimeApprovedFormatted}</td>
                <td style="text-align:left;">${evaluated_by}</td>
                <td style="text-align:left;">${evaluated_message}</td>
                <td style="text-align:center;">
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        ${generateOperationsButtons(user_id, status)}
                    </div>
                </td>
            </tr>`;
    });

    tableHtml += `</tbody></table>`;
    userTableContainer.innerHTML = tableHtml;
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true
    });
};

const generateOperationsButtons = (userId, status) => {
    if (String(userId) === String(loggedInUserId)) return '';

    let buttons = '';
    if (status === 'Pending') {
        buttons = `
            <button type="button" class="green-button" style="width: 100%; margin: 5px" onclick="showApproveUserModal(${userId})">Approve</button>
            <button type="button" class="red-button" style="width: 100%; margin: 5px" onclick="showRejectUserModal(${userId})">Reject</button>
        `;
    } else if (status === 'Rejected') {
        buttons = `
            <button type="button" class="green-button" style="width: 100%; margin: 5px" onclick="showApproveUserModal(${userId})">Approve</button>
            <button type="button" class="red-button" style="width: 100%; margin: 5px" onclick="showDeleteUserModal(${userId})">Delete</button>
        `;
    } else {
        buttons = `
            <button type="button" class="green-button" style="width: 100%; margin: 5px" onclick="showEditUserModal(${userId})">Edit</button>
            <button type="button" class="red-button" style="width: 100%; margin: 5px" onclick="showDeleteUserModal(${userId})">Delete</button>
        `;
    }
    return `<div style="display: flex; flex-direction: column; align-items: center;">${buttons}</div>`;
};

const createModal = (id, content) => {
    let modalContainer = document.getElementById(id);
    if (!modalContainer) {
        modalContainer = document.createElement('div');
        modalContainer.id = id;
        modalContainer.className = 'modal';
        document.body.appendChild(modalContainer);
    }
    modalContainer.innerHTML = content;
    return modalContainer;
};

const showModal = (modalId, actionButton, action) => {
    const modal = document.getElementById(modalId);
    modal.style.display = 'flex';

    // Function to close the modal
    const closeModal = () => {
        modal.style.display = 'none';
    };

    // Set up action button
    const actionBtn = document.getElementById(actionButton);
    if (actionBtn) {
        actionBtn.onclick = (e) => {
            e.preventDefault(); // Prevent form submission
            action();
            closeModal();
        };
    }

    // Set up cancel and close buttons
    const cancelBtn = modal.querySelector(`#cancel${modalId}Button`);
    if (cancelBtn) {
        cancelBtn.onclick = closeModal;
    }

    const closeBtn = modal.querySelector(`#close${modalId}Button`);
    if (closeBtn) {
        closeBtn.onclick = closeModal;
    }
};

/* ============== APPROVE USER ============== */
// Modal content insertion
const insertApproveUserModalContent = (userId) => createModal('ApproveUserModal', `
    <div class="modal-content" style="padding: 15px">
        <span class="close" id="closeApproveUserModalButton" style="color: #334b35"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
        <p id="approveMessage" style="text-align: center">Proceed to approve this user?</p>
        <br>
        <div style="text-align: right;">
            <button id="cancelApproveUserModalButton" class="green-button" style="background: #334b353b; color: black" type="button">Cancel</button>
            <button id="approveUserButton" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to approve</b></button>
        </div>
    </div>
`);

const insertRejectUserModalContent = (userId) => createModal('RejectUserModal', `
    <div class="modal-content" style="padding: 15px">
        <span class="close" id="closeRejectUserModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="color: rgb(126, 11, 34); font-size: 50px"><i class="fa fa-user-times" aria-hidden="true"></i></h1>
        <p id="rejectMessage" style="text-align: center">Proceed to reject this user?</p>
        <p id="deleteMessage" style="text-align: center; font-size: 13px; color: #6E6E6E">This message will be sent thru the user's email.</p>
        <div class="floating-label-container">
            <textarea name="evaluated_message" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area" id="evaluated_message"></textarea>
            <label for="evaluated_message" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User</label>
        </div>
        <br>
        <div style="text-align: right;">
            <button id="cancelRejectUserModalButton" class="red-button" style="background: #334b353b; color: black" type="button">Cancel</button>
            <button id="rejectUserButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to reject</b></button>
        </div>
    </div>
`);

const insertDeleteUserModalContent = (userId) => createModal('DeleteUserModal', `
    <div class="modal-content" style="padding: 15px">
        <span class="close" id="closeDeleteUserModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="color: rgb(126, 11, 34); font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
        <p id="deleteMessage" style="text-align: center">Proceed to delete this user?</p>
        <p id="deleteMessage" style="text-align: center; font-size: 13px; color: #6E6E6E">This message will be sent thru the user's email.</p>
        <div class="floating-label-container">
            <textarea name="evaluated_message" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area" id="evaluated_message"></textarea>
            <label for="evaluated_message" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User</label>
        </div>
        <br>
        <div style="text-align: right;">
            <button id="cancelDeleteUserModalButton" class="red-button" style="background: #334b353b; color: black" type="button">Cancel</button>
            <button id="deleteUserButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
        </div>
    </div>
`);

const showApproveUserModal = (userId) => {
    insertApproveUserModalContent(userId);
    showModal('ApproveUserModal', 'approveUserButton', () => approveUser(userId));
};

const showRejectUserModal = (userId) => {
    insertRejectUserModalContent(userId);
    showModal('RejectUserModal', 'rejectUserButton', () => rejectUser(userId));
};

const showDeleteUserModal = (userId) => {
    insertDeleteUserModalContent(userId);
    showModal('DeleteUserModal', 'deleteUserButton', () => deleteUser(userId));
};

const showAddUserModal = () => {
    const modalContent = `
        <div class="modal-content" style="padding: 15px">
            <span class="close" id="closeAddUserModalButton" style="color: #334b35"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-user-plus" aria-hidden="true"></i></h1>
            <p id="addMessage" style="text-align: center">Add New User(s)</p>
            <form id="addUserForm" style="margin-top: 10px">
                <div class="tab">
                    <button type="button" class="tablinks active" onclick="openTab(event, 'SingleUser')">Single User</button>
                    <button type="button" class="tablinks" onclick="openTab(event, 'MultipleUsers')">Multiple Users</button>
                </div>
                <div id="SingleUser" class="tabcontent" style="display: block;">
                    <div class="floating-label-container">
                        <input type="text" name="full_name" id="add_full_name" required placeholder=" " class="floating-label-input-text-area">
                        <label for="add_full_name" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Full Name</label>
                    </div>
                    <div class="floating-label-container">
                        <input type="email" name="email" id="add_email" required placeholder=" " class="floating-label-input-text-area">
                        <label for="add_email" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">USC Email</label>
                    </div>
                    <div class="floating-label-container">
                        <select name="user_type" id="add_user_type" required class="floating-label-input-text-area">
                            <option value="">Select Role</option>
                            <option value="Admin">Admin</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Student">Student</option>
                        </select>
                        <label for="add_user_type" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Role</label>
                    </div>
                    <div class="floating-label-container">
                        <select name="department" id="add_department" required class="floating-label-input">
                            <option value="">Select Department</option>
                            <option value="COMPUTER ENGINEERING">Department of Computer Engineering</option>
                            <option value="CHEMICAL ENGINEERING">Department of Chemical Engineering</option>
                            <option value="CIVIL ENGINEERING">Department of Civil Engineering</option>
                            <option value="INDUSTRIAL ENGINEERING">Department of Industrial Engineering</option>
                            <option value="ELECTRICAL ENGINEERING">Department of Electrical Engineering</option>
                            <option value="MECHANICAL ENGINEERING">Department of Mechanical Engineering</option>
                            <option value="ELECTRONICS ENGINEERING">Department of Electronics Engineering</option>
                        </select>
                        <label for="add_department" class="floating-label">Department</label>
                    </div>
                </div>

                <div id="MultipleUsers" class="tabcontent">
                    <div class="floating-label-container">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required class="floating-label-input-text-area">
                        <label for="csv_file" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Upload CSV File</label>
                    </div>
                    <p style="font-size: 12px; color: #666; text-align: left; margin-top: 10px">CSV format:</p>
                    <ul style="font-size: 12px; color: #666; text-align: left; padding-left: 16px">
                        <li>full_name</li>
                        <li>email</li>
                        <li>user_type (only one per row)
                            <ul style="padding-left: 16px">
                                <li>Super Admin</li>
                                <li>Admin</li>
                                <li>Faculty</li>
                                <li>Student</li>
                            </ul>
                        </li>
                        <li>department (only one per row)
                            <ul style="padding-left: 16px">
                                <li>COMPUTER ENGINEERING</li>
                                <li>CHEMICAL ENGINEERING</li>
                                <li>CIVIL ENGINEERING</li>
                                <li>INDUSTRIAL ENGINEERING</li>
                                <li>ELECTRICAL ENGINEERING</li>
                                <li>MECHANICAL ENGINEERING</li>
                                <li>ELECTRONICS ENGINEERING</li>
                            </ul>
                        </li>
                    </ul>  
                </div>
                
                <br>
                <div style="text-align: right;">
                    <button type="button" id="cancelAddUserModalButton" class="green-button" style="margin: 0; background: #334b353b; color: black">Cancel</button>
                    <button type="submit" id="addUserButton" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Add User(s)</b></button>
                </div>
            </form>
        </div>
    `;

    createModal('AddUserModal', modalContent);
    showModal('AddUserModal', 'addUserButton', addUser);
};

const openTab = (evt, tabName) => {
    const tabcontent = document.getElementsByClassName("tabcontent");
    for (let i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    const tablinks = document.getElementsByClassName("tablinks");
    for (let i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
};

const addUser = () => {
    const form = document.getElementById('addUserForm');
    const formData = new FormData(form);
    
    if (document.querySelector('.tablinks.active').textContent === 'Multiple Users') {
        formData.append('action', 'add_multiple_users');
        const csvFile = document.getElementById('csv_file').files[0];
        if (csvFile) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const base64Data = btoa(e.target.result);
                formData.append('csv_file', base64Data);
                sendFormData(formData);
            };
            reader.readAsBinaryString(csvFile);
        } else {
            alert('Please select a CSV file.');
            return;
        }
    } else {
        formData.append('action', 'add_user');
        // Generate a random password for single user
        const password = Math.random().toString(36).slice(-8);
        formData.append('password', password);
        sendFormData(formData);
    }
};

const sendFormData = (formData) => {
    fetch('websocket_conn.php')
        .then(response => response.text())
        .then(url => {
            const ws = new WebSocket(url);

            ws.onopen = function () {
                ws.send(JSON.stringify(Object.fromEntries(formData)));
            };

            ws.onmessage = function (event) {
                const message = JSON.parse(event.data);
                if (message.success) {
                    if (message.action === 'add_user') {
                        document.getElementById('successMessageVersion2').textContent = `User added successfully! The temporary password is sent to the user's email.`;
                    } else if (message.action === 'add_multiple_users') {
                        document.getElementById('successMessageVersion2').textContent = `${message.addedCount} users added successfully! ${message.failedCount} users failed to add.`;
                    } else if (message.action === 'edit_user') {
                        document.getElementById('successMessageVersion2').textContent = 'User updated successfully!';
                    }
                    document.getElementById('AddUserModal').style.display = 'none';
                    document.getElementById('successMessageModalVersion2').style.display = 'flex';;
                    refreshUserTable();
                } else {
                    document.getElementById('errorTextVersion2').textContent = message.message;
                    document.getElementById('AddUserModal').style.display = 'none';
                    document.getElementById('errorModalVersion2').style.display = 'flex';
                }
            };

            ws.onclose = function () {
                console.log('WebSocket connection closed');
            };

            ws.onerror = function (error) {
                console.error('WebSocket error:', error);
            };
        })
        .catch(error => {
            console.error('Error fetching WebSocket URL:', error);
        });
};

const performUserAction = (action, userId) => {
    if (action === 'approve_user') {
        sendWebSocketMessage({
            action: action,
            user_id: userId,
        });
    } else {
        const evaluatedMessage = document.getElementById('evaluated_message').value;
        sendWebSocketMessage({
            action: action,
            user_id: userId,
            evaluated_message: evaluatedMessage
        });
    }
};

const approveUser = (userId) => performUserAction('approve_user', userId);
const rejectUser = (userId) => performUserAction('reject_user', userId);
const deleteUser = (userId) => performUserAction('delete_user', userId);

/* ============== EDIT USER DETAILS ============== */
// Function to populate the edit user modal with user data
const populateEditUserModal = (userId) => {
    const modalContent = `
        <div class="modal-content" style="padding: 15px">
            <span class="close" id="closeEditUserModalButton" style="color: #334b35"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-pencil" aria-hidden="true"></i></h1>
            <p id="editMessage" style="text-align: center">Edit User</p>
            <form id="editUserForm">
                <div class="floating-label-container">
                    <input type="text" name="full_name" id="edit_full_name" placeholder=" " class="floating-label-input-text-area" style="background: none; border: none; box-shadow: none; pointer-events: none" readonly>
                    <label for="edit_full_name" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Full Name</label>
                </div>
                <div class="floating-label-container">
                    <input type="email" name="email" id="edit_email" placeholder=" " class="floating-label-input-text-area" style="background: none; box-shadow: none; border: none; pointer-events: none" readonly>
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
                    <button type="button" id="cancelEditUserModalButton" class="green-button" style="margin: 0; background: #334b353b; color: black">Cancel</button>
                    <button type="submit" id="editUserButton" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Update</b></button>
                </div>
            </form>
        </div>
    `;

    createModal('EditUserModal', modalContent);

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
    showModal('EditUserModal', 'editUserButton', () => editUser(userId));
};

const editUser = (userId) => {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    const editUserModal = document.getElementById('EditUserModal');
    formData.append('user_id', userId);
    formData.append('action', 'edit_user');

    sendFormData(formData);
};

const sendWebSocketMessage = (data) => {
    fetch('websocket_conn.php')
        .then(response => response.text())
        .then(url => {
            const ws = new WebSocket(url);
            ws.onopen = () => ws.send(JSON.stringify(data));
            ws.onmessage = (event) => {
                const message = JSON.parse(event.data);
                if (message.success) {
                    if (message.action === 'approve_user') {
                        document.getElementById('successMessageVersion2').textContent = `User approved successfully!`;
                        document.getElementById('ApproveUserModal').style.display = 'none';
                    } else if (message.action === 'reject_user') {
                        document.getElementById('successMessageVersion2').textContent = `User rejected successfully!`;
                        document.getElementById('RejectUserModal').style.display = 'none';
                    } else if (message.action === 'delete_user') {
                        document.getElementById('successMessageVersion2').textContent = `User deleted successfully!`;
                        document.getElementById('DeleteUserModal').style.display = 'none';
                    } 
                    document.getElementById('successMessageModalVersion2').style.display = 'flex';
                    refreshUserTable();
                } else {
                    document.getElementById('errorTextVersion2').textContent = message.message;
                    document.getElementById('errorModalVersion2').style.display = 'flex';
                }
            };
            ws.onclose = () => console.log('WebSocket connection closed');
            ws.onerror = (error) => console.error('WebSocket error:', error);
        })
        .catch(error => console.error('Error fetching WebSocket URL:', error));
};

const refreshUserTable = () => {
    fetch('database/fetch_users.php')
        .then(response => response.json())
        .then(data => {
            displayUserTable(data);
            updateNotificationCount();
        })
        .catch(error => console.error('Error fetching updated users:', error));
};

ws.addEventListener('message', function (event) {
    const data = JSON.parse(event.data);
    if (data.success && ['approve_user', 'reject_user', 'delete_user', 'edit_user', 'add_user'].includes(data.action)) {
        refreshUserTable();
    }
})

document.addEventListener('DOMContentLoaded', () => {
    fetch('database/fetch_users.php')
        .then(response => response.json())
        .then(displayUserTable)
        .catch(error => console.error('Error fetching users:', error));
});
