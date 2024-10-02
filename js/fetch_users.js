// const ws = new WebSocket('ws://192.168.1.17:8081');
const userTableContainer = document.getElementById('userTableContainer');

const loggedInUserId = document.getElementById('user-data').getAttribute('data-user-id');
const loggedInUserType = document.getElementById('user-data').getAttribute('data-user-type');

const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const year = date.getFullYear();
    let hours = date.getHours();
    const minutes = date.getMinutes().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    const formattedHours = hours.toString().padStart(2, '0');
    return `${month}/${day}/${year} | ${formattedHours}:${minutes} ${ampm}`;
};

let isMultiSelectMode = false;

const displayUserTable = (data) => {
    let tableHtml = `
        <table id="usersTable">
            <thead>
                <tr style="user-select: none;">
                    <th class="checkbox-column" style="display: none; padding: 25px"></th>
                    <th onclick="sortTable(0)">User ID</th>
                    <th onclick="sortTable(1)">Full Name</th>
                    <th onclick="sortTable(2)">Email</th>
                    <th onclick="sortTable(3)">Role</th>
                    <th onclick="sortTable(4)">Department</th>
                    <th onclick="sortTable(5)">Status</th>
                    <th onclick="sortTable(6)">Date Registered (MM/DD/YYYY)</th>
                    <th onclick="sortTable(7)">Date Approved (MM/DD/YYYY)</th>
                    <th onclick="sortTable(8)">Evaluated By</th>
                    <th>Reject Message</th>
                    <th>Operations</th>
                </tr>
            </thead>
            <tbody>`;

    data.forEach(row => {
        const { user_id, full_name, email, user_type, department, status, datetime_registered, datetime_approved, evaluated_by, evaluated_message } = row;
        const datetimeRegisteredFormatted = formatDateTime(datetime_registered);
        const datetimeApprovedFormatted = formatDateTime(datetime_approved);

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
                <td class="checkbox-column" style="display: none;"><input type="checkbox" class="user-checkbox" data-user-id="${user_id}" style="width: 15px; height: 15px"></td>
                <td style="text-align:center;">${user_id}</td>
                <td style="text-align:left;">${full_name}</td>
                <td style="text-align:left;">${email}</td>
                <td style="text-align:center;">${user_type}</td>
                <td style="text-align:left;">${department}</td>
                ${statusTd}
                <td style="text-align:left;">${datetimeRegisteredFormatted}</td>
                <td style="text-align:left;">${datetimeApprovedFormatted}</td>
                <td style="text-align:center;">${evaluated_by}</td>
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

    // Add event listeners for checkboxes
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateDeleteSelectedButton);
    });
};

const toggleMultiSelectMode = () => {
    isMultiSelectMode = !isMultiSelectMode;
    const checkboxColumns = document.querySelectorAll('.checkbox-column');
    const selectMultipleBtn = document.getElementById('selectMultipleBtn');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

    checkboxColumns.forEach(col => {
        col.style.display = isMultiSelectMode ? '' : 'none';
    });

    if (isMultiSelectMode) {
        selectMultipleBtn.innerHTML = '<i class="fa fa-times" style="margin-right: 2px"></i> Cancel';
        selectMultipleBtn.style.backgroundColor = '#334b353b';
        selectMultipleBtn.style.color = 'black';
        deleteSelectedBtn.style.display = '';
        deleteSelectedBtn.style.backgroundColor = 'rgb(126, 11, 34)'; // Red background
        deleteSelectedBtn.style.color = 'white';
    } else {
        selectMultipleBtn.innerHTML = '<i class="fa fa-check-square" style="margin-right: 2px"></i> Select Multiple';
        selectMultipleBtn.style.backgroundColor = ''; // Reset to default
        selectMultipleBtn.style.color = ''; // Reset to default
        deleteSelectedBtn.style.display = 'none';
        deleteSelectedBtn.style.backgroundColor = ''; // Reset to default
        deleteSelectedBtn.style.color = ''; // Reset to default
    }

    // Clear all checkboxes when exiting multi-select mode
    if (!isMultiSelectMode) {
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        updateDeleteSelectedButton();
    }
};

const updateDeleteSelectedButton = () => {
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    deleteSelectedBtn.disabled = checkedBoxes.length === 0;
};

const showDeleteConfirmModal = (userCount) => {
    const modalContent = `
        <div class="modal-content" style="padding: 15px">
            <span class="close" id="closeDeleteConfirmModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: rgb(126, 11, 34); font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
            <p id="deleteConfirmMessage" style="text-align: center">Are you sure you want to delete ${userCount} user(s)?</p>
            <br>
            <div style="text-align: right;">
                <button id="cancelDeleteConfirmButton" class="red-button" style="background: #334b353b; color: black" type="button">Cancel</button>
                <button id="confirmDeleteButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, delete</b></button>
            </div>
        </div>
    `;

    const modal = createModal('deleteConfirmModal', modalContent);
    modal.style.display = 'flex';

    const closeModal = () => {
        modal.style.display = 'none';
    };

    document.getElementById('closeDeleteConfirmModalButton').onclick = closeModal;
    document.getElementById('cancelDeleteConfirmButton').onclick = closeModal;
    document.getElementById('confirmDeleteButton').onclick = () => {
        const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
        const userIds = Array.from(checkedBoxes).map(checkbox => checkbox.dataset.userId);

        userIds.forEach(userId => {
            sendWebSocketMessage({
                action: 'delete_user',
                user_id: userId
            });
        });

        closeModal();
    };
};

const deleteSelectedUsers = () => {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const userCount = checkedBoxes.length;

    if (userCount === 0) {
        const modalContent = `
            <div class="modal-content" style="padding: 15px">
                <span class="close" id="closePleaseSelectUsersModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: rgb(126, 11, 34); font-size: 50px"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></h1>
                <p id="deletePleaseSelectUsersMessage" style="text-align: center">Please select a user</p>
                <br>
                <div style="text-align: right;">
                    <button id="okayPleaseSelectUsersButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Okay</b></button>
                </div>
            </div>
        `;
        const modal = createModal('pleaseSelectUsersModal', modalContent);
        modal.style.display = 'flex';
        const closeModal = () => {
            modal.style.display = 'none';
        };
        document.getElementById('closePleaseSelectUsersModalButton').onclick = closeModal;
        document.getElementById('okayPleaseSelectUsersButton').onclick = closeModal;
    } else {
        showDeleteConfirmModal(userCount);
    }

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
    showModal('ApproveUserModal', 'approveUserButton', () => approveTableUser(userId));
};

const showRejectUserModal = (userId) => {
    insertRejectUserModalContent(userId);
    showModal('RejectUserModal', 'rejectUserButton', () => rejectTableUser(userId));
};

const showDeleteUserModal = (userId) => {
    insertDeleteUserModalContent(userId);
    showModal('DeleteUserModal', 'deleteUserButton', () => deleteTableUser(userId));
};

const showAddUserModal = (userId) => {
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
                    <div id="userTypeContainerAddUser" class="floating-label-container">
                        <!-- User Type will be dynamically inserted here -->
                    </div>
                    <div id="departmentContainerAddUser" class="floating-label-container">
                        <!-- Department field will be dynamically inserted here -->
                    </div>
                </div>
                <div id="MultipleUsers" class="tabcontent">
                    <div class="floating-label-container">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required class="floating-label-input-text-area">
                        <label for="csv_file" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Upload CSV File</label>
                    </div>
                    <br>
                    <div style="max-height: 150px; overflow: auto">
                        <p style="font-size: 12px; color: #666; text-align: left; margin-top: 10px">CSV format example:</p>
                        <div class="table-container">
                            <table id="usersTable" style="font-size: 12px">
                                <tr style="user-select: none;">
                                    <th>Carlo Mozar</th>
                                    <th>20101951@usc.edu.ph</th>
                                    <th>Student</th>
                                    <th>COMPUTER ENGINEERING</th>
                                </tr>
                                <tr style="user-select: none;">
                                    <th>Cyrus Noel Caranoo</th>
                                    <th>19105903@usc.edu.ph</th>
                                    <th>Student</th>
                                    <th>COMPUTER ENGINEERING</th>
                                </tr>
                                <tr style="user-select: none;">
                                    <th>Victorigen Solon</th>
                                    <th>20103449@usc.edu.ph</th>
                                    <th>Student</th>
                                    <th>COMPUTER ENGINEERING</th>
                                </tr>
                            </table>
                        </div>
                        <br>
                        <ul style="font-size: 12px; color: #666; text-align: left; padding-left: 16px">
                            <li>Column 1: Full Name</li>
                            <li>Column 2: Email</li>
                            <li>Column 3: Role (only one per row)
                                <ul style="padding-left: 16px">
                                    <li>Super Admin</li>
                                    <li>Admin</li>
                                    <li>Faculty</li>
                                    <li>Student</li>
                                </ul>
                            </li>
                            <li>Column 4: Department (only one per row)
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

    // Fetch user data based on userId
    fetch(`database/fetch_users.php?userId=${userId}`)
        .then(response => response.json())
        .then(user => {
            if (user) {
                // Create the department field based on the logged-in user's type
                const departmentContainerAddUser = document.getElementById('departmentContainerAddUser');
                const userTypeContainerAddUser = document.getElementById('userTypeContainerAddUser');
                if (loggedInUserType === 'Admin') {
                    departmentContainerAddUser.innerHTML = `
                        <input type="text" name="department" id="add_department" placeholder=" " class="floating-label-input-text-area" style="background: none; box-shadow: none; border: none; pointer-events: none" readonly>
                        <label for="add_department" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Department</label>
                    `;
                    userTypeContainerAddUser.innerHTML = `
                        <select name="user_type" id="add_user_type" required class="floating-label-input-text-area">
                            <option value="">Select Role</option>
                            <option value="Admin">Admin</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Student">Student</option>
                        </select>
                        <label for="add_user_type" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Role</label>
                    `;
                    document.getElementById('add_department').value = user.department || '';
                } else if (loggedInUserType === 'Super Admin') {
                    departmentContainerAddUser.innerHTML = `
                        <select name="department" id="add_department" class="floating-label-input">
                            <option value="">~</option>
                            <option value="COMPUTER ENGINEERING">Department of Computer Engineering</option>
                            <option value="CHEMICAL ENGINEERING">Department of Chemical Engineering</option>
                            <option value="CIVIL ENGINEERING">Department of Civil Engineering</option>
                            <option value="INDUSTRIAL ENGINEERING">Department of Industrial Engineering</option>
                            <option value="ELECTRICAL ENGINEERING">Department of Electrical Engineering</option>
                            <option value="MECHANICAL ENGINEERING">Department of Mechanical Engineering</option>
                            <option value="ELECTRONICS ENGINEERING">Department of Electronics Engineering</option>
                        </select>
                        <label for="add_department" class="floating-label">Department</label>
                    `;
                    userTypeContainerAddUser.innerHTML = `
                        <select name="user_type" id="add_user_type" required class="floating-label-input-text-area">
                            <option value="">Select Role</option>
                            <option value="Admin">Admin</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Student">Student</option>
                            <option value="Super Admin">Super Admin</option>
                        </select>
                        <label for="add_user_type" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Role</label>
                    `;
                    document.getElementById('add_department').value = user.department || '';
                }
            } else {
                console.error('User data is null or undefined');
            }
        })
        .catch(error => console.error('Error fetching user data:', error));
    
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
        // Client-side validation
        const fullName = formData.get('full_name');
        const email = formData.get('email');
        const userType = formData.get('user_type');
        const department = formData.get('department');

        if (!fullName || !email || !userType || !department) {
            document.getElementById('errorTextVersion2').textContent = "Please fill up all fields.";
            document.getElementById('AddUserModal').style.display = 'none';
            document.getElementById('errorModalVersion2').style.display = 'flex';
            return;
        }

        if (!/^[a-zA-Z\s]+$/.test(fullName)) {
            document.getElementById('errorTextVersion2').textContent = "Invalid full name. Please use only letters and spaces.";
            document.getElementById('AddUserModal').style.display = 'none';
            document.getElementById('errorModalVersion2').style.display = 'flex';
            return;
        }

        if (!/@usc\.edu\.ph$/.test(email)) {
            document.getElementById('errorTextVersion2').textContent = "Invalid email. Please use a @usc.edu.ph email address.";
            document.getElementById('AddUserModal').style.display = 'none';
            document.getElementById('errorModalVersion2').style.display = 'flex';
            return;
        }
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
            const Ws = new WebSocket(url);

            Ws.onopen = function () {
                Ws.send(JSON.stringify(Object.fromEntries(formData)));
            };

            Ws.onmessage = function (event) {
                const message = JSON.parse(event.data);
                if (message.action === 'add_user') {
                    if (message.success == true) {
                        document.getElementById('successMessageVersion2').textContent = `User added successfully! The temporary password is sent to the user's email.`;
                        document.getElementById('AddUserModal').style.display = 'none';
                        document.getElementById('successMessageModalVersion2').style.display = 'flex';
                    } else if (message.success == false) {
                        document.getElementById('errorTextVersion2').textContent = message.message;
                        document.getElementById('AddUserModal').style.display = 'none';
                        document.getElementById('errorModalVersion2').style.display = 'flex';
                    }
                } else if (message.action === 'add_multiple_users') {
                    if (message.success == true) {
                        let successMessage = `${message.addedCount} users added successfully! ${message.failedCount} users failed to add.`;
                        if (message.errorMessages && message.errorMessages.length > 0) {
                            successMessage += "<p>Errors:</p><ul style='text-align: left; color: red; font-size: 14px'>" + 
                                message.errorMessages.map(error => `<li>• ${error}</li>`).join('') + 
                                "</ul>";
                        }
                        document.getElementById('successMessageVersion2').innerHTML = successMessage;
                        document.getElementById('AddUserModal').style.display = 'none';
                        document.getElementById('successMessageModalVersion2').style.display = 'flex';
                    } else if (message.success == false){
                        document.getElementById('errorTextVersion2').innerHTML = message.message;
                        document.getElementById('AddUserModal').style.display = 'none';
                        document.getElementById('errorModalVersion2').style.display = 'flex';
                    }
                } else if (message.action === 'edit_user') {
                    document.getElementById('successMessageVersion2').textContent = 'User updated successfully!';
                    document.getElementById('EditUserModal').style.display = 'none';
                    document.getElementById('successMessageModalVersion2').style.display = 'flex';
                } 
                refreshUserTable();
            };

            Ws.onclose = function () {
                console.log('WebSocket connection closed');
            };

            Ws.onerror = function (error) {
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

const approveTableUser = (userId) => performUserAction('approve_user', userId);
const rejectTableUser = (userId) => performUserAction('reject_user', userId);
const deleteTableUser = (userId) => performUserAction('delete_user', userId);

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
                        <option value="Super Admin">Super Admin</option>
                    </select>
                    <label for="edit_user_type" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Role</label>
                </div>
                <div id="departmentContainer" class="floating-label-container">
                    <!-- Department field will be dynamically inserted here -->
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
            if (user) {
                document.getElementById('edit_full_name').value = user.full_name || '';
                document.getElementById('edit_email').value = user.email || '';
                document.getElementById('edit_user_type').value = user.user_type || '';

                // Create the department field based on the logged-in user's type
                const departmentContainer = document.getElementById('departmentContainer');
                if (loggedInUserType === 'Admin') {
                    departmentContainer.innerHTML = `
                        <input type="text" name="department" id="edit_department" placeholder=" " class="floating-label-input-text-area" style="background: none; box-shadow: none; border: none; pointer-events: none" readonly>
                        <label for="edit_department" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Department</label>
                    `;
                    document.getElementById('edit_department').value = user.department || '';
                } else if (loggedInUserType === 'Super Admin') {
                    departmentContainer.innerHTML = `
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
                    `;
                    document.getElementById('edit_department').value = user.department || '';
                }
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
    formData.append('user_id', userId);
    formData.append('action', 'edit_user');
    formData.append('full_name', full_name);

    sendFormData(formData);
};

const sendWebSocketMessage = (data) => {
    fetch('websocket_conn.php')
        .then(response => response.text())
        .then(url => {
            const Ws = new WebSocket(url);
            Ws.onopen = () => Ws.send(JSON.stringify(data));
            Ws.onmessage = (event) => {
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
                } 
            };
            Ws.onclose = () => console.log('WebSocket connection closed');
            Ws.onerror = (error) => console.error('WebSocket error:', error);
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

Ws.addEventListener('message', function (event) {
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

    const selectMultipleBtn = document.getElementById('selectMultipleBtn');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

    selectMultipleBtn.addEventListener('click', toggleMultiSelectMode);
    deleteSelectedBtn.addEventListener('click', deleteSelectedUsers);
});
