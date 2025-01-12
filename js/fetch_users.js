const userTableContainer = document.getElementById('userTableContainer');

const loggedInUserId = document.getElementById('user-data').getAttribute('data-user-id');
const loggedInUserType = document.getElementById('user-data').getAttribute('data-user-type');
const loggedInUserDepartment = document.getElementById('user-data').getAttribute('data-user-department');

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

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${month}/${day}/${year}`;
};

let isMultiSelectMode = false;

const showUserTableSummary = () => {
    const table = document.getElementById('usersTable');
    if (!table) {
        console.error('Users table not found');
        return;
    }

    const rows = table.getElementsByTagName('tr');
    const summary = {
        roles: {
            'Super Admin': 0,
            'Admin': 0,
            'Student': 0,
            'Faculty': 0,
            'Staff': 0
        },
        departments: {
            'COMPUTER ENGINEERING': 0,
            'CHEMICAL ENGINEERING': 0,
            'CIVIL ENGINEERING': 0,
            'INDUSTRIAL ENGINEERING': 0,
            'ELECTRICAL ENGINEERING': 0,
            'MECHANICAL ENGINEERING': 0,
            'ELECTRONICS ENGINEERING': 0
        },
        status: {
            'Approved': 0,
            'Rejected': 0,
            'Pending': 0
        }
    };

    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        const role = cells[4].textContent.trim();
        const department = cells[5].textContent.trim();
        const status = cells[6].textContent.trim();

        if (summary.roles.hasOwnProperty(role)) {
            summary.roles[role]++;
        }
        if (summary.departments.hasOwnProperty(department)) {
            summary.departments[department]++;
        }
        if (summary.status.hasOwnProperty(status)) {
            summary.status[status]++;
        }
    }

    let summaryHtml = `
        <div style="height: auto; max-height: 300px; overflow-y: auto; padding-right: 10px">
            <table class="summary-table">
                <tr>
                    <th colspan="2" style="text-align: left">Roles</th>
                </tr>
                ${Object.entries(summary.roles).map(([role, count]) => `
                    <tr>
                        <td style="text-align: left">${role}</td>
                        <td>${count}</td>
                    </tr>
                `).join('')}
                <tr>
                    <th colspan="2" style="text-align: left">Departments</th>
                </tr>
                ${Object.entries(summary.departments).map(([dept, count]) => `
                    <tr>
                        <td style="text-align: left">${dept}</td>
                        <td>${count}</td>
                    </tr>
                `).join('')}
                <tr>
                    <th colspan="2" style="text-align: left">Status</th>
                </tr>
                ${Object.entries(summary.status).map(([status, count]) => `
                    <tr>
                        <td style="text-align: left">${status}</td>
                        <td>${count}</td>
                    </tr>
                `).join('')}
            </table>
        </div>
    `;

    const modal = createModal('userTableSummaryModal', `
        <div class="modal-content" style="padding: 25px;">
            <span class="close" onclick="closeModal('userTableSummary')"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="font-size: 50px"><i class="fa fa-table" aria-hidden="true"></i></h1>
            <p style="text-align: center">Users Table Summary</p>
            ${summaryHtml}
        </div>
    `);

    modal.style.display = 'flex';
};

const displayUserTable = (data) => {
    let tableHtml = `
        <table id="usersTable">
            <thead>
                <tr style="user-select: none;">
                    <th class="checkbox-column" style="display: none; padding: 25px"></th>
                    <th onclick="sortTable(1)">User ID</th>
                    <th onclick="sortTable(2)">Full Name</th>
                    <th onclick="sortTable(3)">Email</th>
                    <th onclick="sortTable(4)">Role</th>
                    <th onclick="sortTable(5)">Department</th>
                    <th onclick="sortTable(6)">Status</th>
                    <th onclick="sortTable(7)">Date Registered (MM/DD/YYYY)</th>
                    <th onclick="sortTable(8)">Date Approved (MM/DD/YYYY)</th>
                    <th onclick="sortTable(9)">Last Date of Activity (MM/DD/YYYY)</th>
                    <th onclick="sortTable(10)">Approved/Rejected By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>`;

    data.forEach(row => {
        const { user_id, full_name, email, user_type, department, status, datetime_registered, datetime_approved, last_post_date, evaluated_by, evaluator_message } = row;
        const datetimeRegisteredFormatted = formatDateTime(datetime_registered);
        const datetimeApprovedFormatted = formatDateTime(datetime_approved);
        const lastDateOfActivityFormatted = formatDate(last_post_date);

        // Dynamically build the status cell based on the status value
        let statusTd;
        if (status === 'Approved') {
            statusTd = `<td style="text-align:center; color: green">${status}</td>`;
        } else if (status === 'Rejected') {
            statusTd = `<td style="text-align:center; color: red">${status}</td>`;
        } else if (status === 'Pending') {
            statusTd = `<td style="text-align:center; color: #0300c4">${status}</td>`;
        } 

        // Determine if checkbox should be displayed
        const showCheckbox = user_id !== loggedInUserId && !(loggedInUserType === 'Admin' && user_type === 'Super Admin');

        tableHtml += `
            <tr data-user-id="${user_id}">
                <td class="checkbox-column" style="display: none;">
                    ${showCheckbox ? `<input type="checkbox" class="user-checkbox" data-user-id="${user_id}" style="width: 15px; height: 15px">` : ''}
                </td>
                <td style="text-align:center;">${user_id}</td>
                <td style="text-align:left;">${full_name}</td>
                <td style="text-align:left;">${email}</td>
                <td style="text-align:center;">${user_type}</td>
                <td style="text-align:left;">${department}</td>
                ${statusTd}
                <td style="text-align:left;">${datetimeRegisteredFormatted}</td>
                <td style="text-align:left;">${datetimeApprovedFormatted}</td>
                <td style="text-align:left;">${lastDateOfActivityFormatted}</td>
                <td style="text-align:center;">${evaluated_by}</td>
                <td style="text-align:center;">
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        ${generateOperationsButtons(user_id, email, full_name, department, status, user_type, loggedInUserType)}
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
    if (isMultiSelectMode) {
        resetMultiSelectMode();
    } else {
        isMultiSelectMode = true;
        const checkboxColumns = document.querySelectorAll('.checkbox-column');
        const selectMultipleBtn = document.getElementById('selectMultipleBtn');
        const selectMultipleBtnMobile = document.getElementById('selectMultipleBtnMobile');
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
        const deleteSelectedBtnMobile = document.getElementById('deleteSelectedBtnMobile');

        checkboxColumns.forEach(col => {
            col.style.display = ''; // Display all checkbox columns
            const checkbox = col.querySelector('.user-checkbox');
            if (checkbox) {
                const userId = checkbox.getAttribute('data-user-id');
                if (userId !== loggedInUserId) {
                    checkbox.style.display = ''; // Only show checkboxes for non-logged-in users
                } else {
                    checkbox.style.display = 'none'; // Hide checkbox for logged-in user
                }
            }
        });

        selectMultipleBtn.innerHTML = '<i class="fa fa-times" style="margin-right: 2px"></i> Cancel';

        selectMultipleBtnMobile.innerHTML = '<i class="fa fa-times" style="margin-right: 2px"></i> Cancel';
        
        deleteSelectedBtn.style.display = '';
        deleteSelectedBtnMobile.style.display = '';
    }
    updateDeleteSelectedButton();
};

const updateDeleteSelectedButton = () => {
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const deleteSelectedBtnMobile = document.getElementById('deleteSelectedBtnMobile');
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    deleteSelectedBtn.disabled = checkedBoxes.length === 0;
    deleteSelectedBtnMobile.disabled = checkedBoxes.length === 0;
};

const showDeleteConfirmModal = (userCount, evaluatorName, selectedUsers) => {
    const modalContent = `
        <div class="modal-content" style="padding: 15px">
            <span class="close" id="closeDeleteConfirmModalButton"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
            <p id="deleteConfirmMessage" style="text-align: center">Proceed to delete ${userCount} user(s)?</p>
            <p id="deleteMessage" style="text-align: center; font-size: 13px; color: #6E6E6E">This message will be sent thru the user's email.</p>
            <div class="floating-label-container">
                <textarea name="evaluator_message" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area" id="evaluator_message"></textarea>
                <label for="evaluator_message" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User</label>
            </div>
            <br>
            <br>
            <div style="text-align: right;">
                <button id="cancelDeleteConfirmButton" class="grey-button" type="button">Cancel</button>
                <button id="confirmDeleteButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, delete</b></button>
            </div>
        </div>
    `;

    const modal = createModal('DeleteUserModal', modalContent);
    modal.style.display = 'flex';

    const closeModal = () => {
        modal.style.display = 'none';
    };

    document.getElementById('closeDeleteConfirmModalButton').onclick = closeModal;
    document.getElementById('cancelDeleteConfirmButton').onclick = closeModal;
    document.getElementById('confirmDeleteButton').onclick = () => {
        // Log each user being processed
        selectedUsers.forEach(user => {
            console.log("Processing user:", user);
            sendWebSocketMessage({
                action: 'delete_user',
                user_id: user.userId,
                email: user.email,
                full_name: user.fullName,
                user_type: user.userType,
                department: user.department,
                evaluator_name: evaluatorName,
                evaluator_id: loggedInUserId
            });
        });
        // showMultiDeleteSuccessModal(deletedCount);
        refreshUserTable();
        closeModal();
        resetMultiSelectMode();
    };
};

const resetMultiSelectMode = () => {
    isMultiSelectMode = false;
    const checkboxColumns = document.querySelectorAll('.checkbox-column');
    const selectMultipleBtn = document.getElementById('selectMultipleBtn');
    const selectMultipleBtnMobile = document.getElementById('selectMultipleBtnMobile');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const deleteSelectedBtnMobile = document.getElementById('deleteSelectedBtnMobile');

    checkboxColumns.forEach(col => {
        col.style.display = 'none';
        const checkbox = col.querySelector('.user-checkbox');
        if (checkbox) {
            checkbox.style.display = 'none';
            checkbox.checked = false;
        }
    });

    selectMultipleBtn.innerHTML = '<i class="fa fa-check-square" style="margin-right: 2px"></i> Select Multiple';
    selectMultipleBtn.style.backgroundColor = ''; // Reset to default
    selectMultipleBtn.style.color = ''; // Reset to default
    selectMultipleBtnMobile.innerHTML = '<i class="fa fa-check-square" style="margin-right: 2px"></i> Select Multiple';
    selectMultipleBtnMobile.style.backgroundColor = '';
    selectMultipleBtnMobile.style.color = '';
    deleteSelectedBtn.style.display = 'none';
    deleteSelectedBtnMobile.style.display = 'none';

    // Clear all checkboxes
    // document.querySelectorAll('.user-checkbox').forEach(checkbox => {
    //     checkbox.checked = false;
    // });
};

// const showMultiDeleteSuccessModal = (count) => {
//     refreshUserTable();
//     const modalContent = `
//         <div class="modal-content" style="padding: 15px">
//             <span class="close" id="closeMultiDeleteSuccessModalButton" style="color: #334b35"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
//             <br>
//             <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
//             <p id="multiDeleteSuccessMessage" style="text-align: center">${count} user(s) have been successfully deleted.</p>
//             <br>
//             <div style="text-align: right;">
//                 <button id="okayMultiDeleteSuccessButton" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Okay</b></button>
//             </div>
//         </div>
//     `;

//     const modal = createModal('multiDeleteSuccessModal', modalContent);
//     modal.style.display = 'flex';

//     const closeModal = () => {
//         modal.style.display = 'none';
//     };

//     document.getElementById('closeMultiDeleteSuccessModalButton').onclick = closeModal;
//     document.getElementById('okayMultiDeleteSuccessButton').onclick = closeModal;
// };

const deleteSelectedUsers = () => {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const userCount = checkedBoxes.length;
    const evaluatorName = full_name;
    const evaluatorId = user_id;
    const userType = loggedInUserType;
    const department = loggedInUserDepartment;

    console.log("evaluatorName: " + evaluatorName);
    console.log("evaluatorId: " + evaluatorId);
    console.log("userType: " + userType);
    console.log("department: " + department);

    // Get user details from the table rows
    const selectedUsers = Array.from(checkedBoxes).map(checkbox => {
        const row = checkbox.closest('tr');
        // Log the row data for debugging
        console.log("Row data:", {
            userId: checkbox.getAttribute('data-user-id'),
            fullName: row.querySelector('td:nth-child(3)').textContent.trim(),
            email: row.querySelector('td:nth-child(4)').textContent.trim(),
            userType: row.querySelector('td:nth-child(5)').textContent.trim(),
            department: row.querySelector('td:nth-child(6)').textContent.trim()
        });
        
        return {
            userId: checkbox.getAttribute('data-user-id'),
            fullName: row.querySelector('td:nth-child(3)').textContent.trim(),
            email: row.querySelector('td:nth-child(4)').textContent.trim(),
            userType: row.querySelector('td:nth-child(5)').textContent.trim(),
            department: row.querySelector('td:nth-child(6)').textContent.trim()
        };
    });
            
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
        showDeleteConfirmModal(userCount, evaluatorName, selectedUsers);
    }

};

const generateOperationsButtons = (userId, email, fullName, department, status, userType, loggedInUserType) => {
    if (String(userId) === String(loggedInUserId)) return '';

    // If logged-in user is Admin and the user in the row is Super Admin, don't show edit/delete buttons
    if (loggedInUserType === 'Admin' && userType === 'Super Admin') {
        return '';
    }

    let buttons = '';
    if (status === 'Pending') {
        buttons = `
            <button type="button" class="green-button" style="width: 100%; margin: 5px" onclick="showApproveUserModal(${userId}, '${email}', '${fullName}', '${userType}', '${full_name}', '${department}', ${user_id})">Approve</button>
            <button type="button" class="red-button" style="width: 100%; margin: 5px" onclick="showRejectUserModal(${userId}, '${email}', '${fullName}', '${userType}', '${full_name}', '${department}', ${user_id})">Reject</button>
        `;
    } else if (status === 'Rejected') {
        buttons = `
            <button type="button" class="green-button" style="width: 100%; margin: 5px" onclick="showApproveUserModal(${userId}, '${email}', '${fullName}', '${userType}', '${full_name}', '${department}', ${user_id})">Approve</button>
            <button type="button" class="red-button" style="width: 100%; margin: 5px" onclick="showDeleteUserModal(${userId}, '${email}', '${fullName}', '${userType}', '${full_name}', '${department}', ${user_id})">Delete</button>
        `;
    } else {
        buttons = `
            <button type="button" class="green-button" style="width: 100%; margin: 5px" onclick="showEditUserModal(${userId}, '${email}', '${fullName}', '${userType}', '${full_name}', '${department}', ${user_id})">Edit</button>
            <button type="button" class="red-button" style="width: 100%; margin: 5px" onclick="showDeleteUserModal(${userId}, '${email}', '${fullName}', '${userType}', '${full_name}', '${department}', ${user_id})">Delete</button>
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
            <button id="cancelApproveUserModalButton" class="grey-button" type="button">Cancel</button>
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
            <textarea name="evaluator_message" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area" id="evaluator_message"></textarea>
            <label for="evaluator_message" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User</label>
        </div>
        <br>
        <div style="text-align: right;">
            <button id="cancelRejectUserModalButton" class="grey-button" type="button">Cancel</button>
            <button id="rejectUserButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to reject</b></button>
        </div>
    </div>
`);

const insertDeleteUserModalContent = (userId) => createModal('DeleteUserModal', `
    <div class="modal-content" style="padding: 15px">
        <span class="close" id="closeDeleteUserModalButton"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <br>
        <h1 style="font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
        <p id="deleteMessage" style="text-align: center">Proceed to delete this user?</p>
        <p id="deleteMessage" style="text-align: center; font-size: 13px; color: #6E6E6E">This message will be sent thru the user's email.</p>
        <div class="floating-label-container">
            <textarea name="evaluator_message" rows="6" required placeholder=" " style="width: 100%" class="floating-label-input-text-area" id="evaluator_message"></textarea>
            <label for="evaluator_message" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Message to this User</label>
        </div>
        <br>
        <div style="text-align: right;">
            <button id="cancelDeleteUserModalButton" class="grey-button" type="button">Cancel</button>
            <button id="deleteUserButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
        </div>
    </div>
`);

const showApproveUserModal = (userId, email, fullName, userType, evaluatorName, department, evaluatorId) => {
    insertApproveUserModalContent(userId, email, fullName, userType, evaluatorName, department, evaluatorId);
    showModal('ApproveUserModal', 'approveUserButton', () => approveTableUser(userId, email, fullName, userType, evaluatorName, department, evaluatorId));
};

const showRejectUserModal = (userId, email, fullName, userType, evaluatorName, department, evaluatorId) => {
    insertRejectUserModalContent(userId, email, fullName, userType, evaluatorName, department, evaluatorId);
    showModal('RejectUserModal', 'rejectUserButton', () => rejectTableUser(userId, email, fullName, userType, evaluatorName, department, evaluatorId));
};

const showDeleteUserModal = (userId, email, fullName, userType, evaluatorName, department, evaluatorId) => {
    insertDeleteUserModalContent(userId, email, fullName, userType, evaluatorName, department, evaluatorId);
    showModal('DeleteUserModal', 'deleteUserButton', () => deleteTableUser(userId, email, fullName, userType, evaluatorName, department, evaluatorId));
};

const showAutoDeleteModal = () => {
    fetch('database/fetch_auto_delete_settings.php')
        .then(response => response.json())
        .then(settings => {
            // Filter departments based on user type
            let departmentsToShow = {};
            if (loggedInUserType === 'Admin') {
                // Only show the admin's department
                if (settings[loggedInUserDepartment]) {
                    departmentsToShow[loggedInUserDepartment] = settings[loggedInUserDepartment];
                }
            } else if (loggedInUserType === 'Super Admin') {
                // Show all departments
                departmentsToShow = settings;
            }
            const modalContent = `
                <div class="modal-content" style="padding: 15px">
                    <span class="close" id="closeAutoDeleteModalButton"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                    <br>
                    <h1 style="font-size: 50px"><i class="fa fa-user-times" aria-hidden="true"></i></h1>
                    <p style="text-align: center">Auto Delete User Due to Inactivity</p>
                    <p style="text-align: center; font-size: 13px; color: #6E6E6E">Set the number of days of inactivity before auto-deletion</p>
                    <form id="autoDeleteForm">
                        <div class="table-container">
                            <table style="font-size: 13px">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Faculty (Days)</th>
                                        <th>Staff (Days)</th>
                                        <th>Student (Days)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${Object.entries(departmentsToShow).map(([dept, data]) => `
                                        <tr>
                                            <td>${dept}</td>
                                            <td><input type="number" min="1" value="${data.faculty_days}" class="days-input" name="faculty_${dept}"></td>
                                            <td><input type="number" min="1" value="${data.staff_days}" class="days-input" name="staff_${dept}"></td>
                                            <td><input type="number" min="1" value="${data.student_days}" class="days-input" name="student_${dept}"></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                        <br>
                        <div style="text-align: right;">
                            <button type="button" id="cancelAutoDeleteButton" class="grey-button">Cancel</button>
                            <button type="submit" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Update All</b></button>
                        </div>
                    </form>
                </div>
            `;

            const modal = createModal('AutoDeleteModal', modalContent);
            modal.style.display = 'flex';

            // Add event listeners
            document.getElementById('closeAutoDeleteModalButton').onclick = () => {
                modal.style.display = 'none';
            };

            document.getElementById('cancelAutoDeleteButton').onclick = () => {
                modal.style.display = 'none';
            };

            document.getElementById('autoDeleteForm').onsubmit = (e) => {
                e.preventDefault();
                updateAllInactivityDays();
            };
        })
        .catch(error => console.error('Error fetching auto-delete settings:', error));
};

const updateAllInactivityDays = () => {
    const formData = {};
    const inputs = document.querySelectorAll('.days-input');
    let hasEmptyFields = false;

    inputs.forEach(input => {
        if (!input.value || input.value < 1) {
            hasEmptyFields = true;
            return;
        }

        const [role, dept] = input.name.split('_');
        if (!formData[dept]) {
            formData[dept] = {};
        }
        formData[dept][`${role}_days`] = parseInt(input.value);
    });

    if (hasEmptyFields) {
        alert('Please fill in all fields with valid numbers (minimum 1 day).');
        return;
    }

    fetch('database/update_auto_delete_settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ settings: formData })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('successMessageVersion2').textContent = 'Auto-delete settings updated successfully!';
            document.getElementById('AutoDeleteModal').style.display = 'none';
            document.getElementById('successMessageModalVersion2').style.display = 'flex';
        } else {
            throw new Error(data.message || 'Failed to update settings');
        }
    })
    .catch(error => {
        document.getElementById('errorTextVersion2').textContent = error.message;
        document.getElementById('AutoDeleteModal').style.display = 'none';
        document.getElementById('errorModalVersion2').style.display = 'flex';
    });
};

const showAddUserModal = (userId) => {
    const modalContent = `
        <div class="modal-content" style="padding: 15px">
            <span class="close" id="closeAddUserModalButton"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="font-size: 50px"><i class="fa fa-user-plus" aria-hidden="true"></i></h1>
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
                        <p style="font-size: 12px; color: #666; text-align: left; margin-top: 10px">Correct .csv format example:</p>
                        <div class="table-container">
                            <table style="font-size: 12px">
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
                                    <li>Staff</li>
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
                    <button type="button" id="cancelAddUserModalButton" class="grey-button">Cancel</button>
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
                            <option value="Staff">Staff</option>
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
                            <option value="Staff">Staff</option>
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
        formData.append('evaluator_name', full_name);
        formData.append('evaluator_id', user_id);
        formData.append('evaluator_type', loggedInUserType);
        formData.append('evaluator_department', loggedInUserDepartment);
        
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
        const password = Math.random().toString(36).slice(-8);

        formData.append('action', 'add_user');
        formData.append('evaluator_name', full_name);
        formData.append('evaluator_id', user_id);
        formData.append('evaluator_type', loggedInUserType);
        formData.append('evaluator_department', loggedInUserDepartment);
        formData.append('password', password);
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
                        // Send email with temporary password
                        sendTempPasswordEmail(formData.get('email'), formData.get('password'), formData.get('full_name'), formData.get('department'), full_name);
                        document.getElementById('successMessageVersion2').textContent = `User added successfully! The temporary password is sent to the user's email.`;
                        document.getElementById('AddUserModal').style.display = 'none';
                        document.getElementById('successMessageModalVersion2').style.display = 'flex';
                        console.log("Form Data: ", formData);
                    } else if (message.success == false) {
                        document.getElementById('errorTextVersion2').textContent = message.message;
                        document.getElementById('AddUserModal').style.display = 'none';
                        document.getElementById('errorModalVersion2').style.display = 'flex';
                    }
                    Ws.close();
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

                        // Send temporary password emails for each successfully added user
                        if (message.addedUsers && message.addedUsers.length > 0) {
                            message.addedUsers.forEach(user => {
                                // Add a small delay between emails to prevent overwhelming the server
                                setTimeout(() => {
                                    sendTempPasswordEmail(
                                        user.email,
                                        user.password,
                                        user.full_name,
                                        user.department,
                                        formData.get('evaluator_name')
                                    );
                                }, 500); // 500ms delay between each email
                            });
                        }
                    } else if (message.success == false){
                        document.getElementById('errorTextVersion2').innerHTML = message.message;
                        document.getElementById('AddUserModal').style.display = 'none';
                        document.getElementById('errorModalVersion2').style.display = 'flex';
                    }
                    Ws.close();
                } else if (message.action === 'edit_user' || message.action === 'user_edited') {
                    document.getElementById('successMessageVersion2').innerHTML = 'User updated successfully!';
                    document.getElementById('EditUserModal').style.display = 'none';
                    document.getElementById('successMessageModalVersion2').style.display = 'flex';
                    console.log("Form Data Email: ", formData.get('email'));
                    sendUpdatedUserEmail(formData.get('email'), formData.get('full_name'), formData.get('department'), formData.get('user_type'), full_name);
                    Ws.close();
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

const sendTempPasswordEmail = (email, password, full_name, department, evaluator_name) => {
    fetch('send_temp_password_email.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&full_name=${encodeURIComponent(full_name)}&department=${encodeURIComponent(department)}&evaluator_name=${encodeURIComponent(evaluator_name)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Temporary password email sent successfully');
        } else {
            console.error('Failed to send temporary password email:', data.message);
        }
    })
    .catch(error => {
        console.error('Error sending temporary password email:', error);
    });
};

const sendDeletedUserEmail = (email, full_name, department, evaluator_name, evaluator_message) => {
    fetch('send_deleted_user_email.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `email=${encodeURIComponent(email)}&full_name=${encodeURIComponent(full_name)}&department=${encodeURIComponent(department)}&evaluator_name=${encodeURIComponent(evaluator_name)}&evaluator_message=${encodeURIComponent(evaluator_message)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Deleted user email sent successfully');
        } else {
            console.error('Failed to send deleted user email:', data.message);
        }
    })
    .catch(error => {
        console.error('Error sending deleted user email:', error);
    });
};

const sendUpdatedUserEmail = (email, full_name, department, userType, evaluator_name) => {
    fetch('send_updated_user_email.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `email=${encodeURIComponent(email)}&full_name=${encodeURIComponent(full_name)}&department=${encodeURIComponent(department)}&user_type=${encodeURIComponent(userType)}&evaluator_name=${encodeURIComponent(evaluator_name)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Update user email sent successfully');
        } else {
            console.error('Failed to send update user email:', data.message);
        }
    })
    .catch(error => {
        console.error('Error sending update user email:', error);
    });
};

const performUserAction = (action, userId, email, fullName, userType, evaluatorName, department, evaluatorId) => {
    if (action === 'approve_user') {
        sendWebSocketMessage({
            action: action,
            user_id: userId,
            email: email,
            full_name: fullName,
            user_type: userType,
            department: department,
            evaluator_name: evaluatorName,
            evaluator_id: evaluatorId
        });
    } else {
        const evaluatorMessage = document.getElementById('evaluator_message').value;
        if (!evaluatorMessage.trim()) {
            alert('Please enter a message for the users.');
            return;
        }
        sendWebSocketMessage({
            action: action,
            user_id: userId,
            email: email,
            full_name: fullName,
            user_type: userType,
            department: department,
            evaluator_name: evaluatorName,
            evaluator_id: evaluatorId,
            evaluator_message: evaluatorMessage
        });
    }
};

const approveTableUser = (userId, email, fullName, userType, evaluatorName, department, evaluatorId) => performUserAction('approve_user', userId, email, fullName, userType, evaluatorName, department, evaluatorId);
const rejectTableUser = (userId, email, fullName, userType, evaluatorName, department, evaluatorId) => performUserAction('reject_user', userId, email, fullName, userType, evaluatorName, department, evaluatorId);
const deleteTableUser = (userId, email, fullName, userType, evaluatorName, department, evaluatorId) => performUserAction('delete_user', userId, email, fullName, userType, evaluatorName, department, evaluatorId);

/* ============== EDIT USER DETAILS ============== */
// Function to populate the edit user modal with user data
const populateEditUserModal = (userId) => {
    const modalContent = `
        <div class="modal-content" style="padding: 15px">
            <span class="close" id="closeEditUserModalButton"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="font-size: 50px"><i class="fa fa-pencil" aria-hidden="true"></i></h1>
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
                    <div id="userTypeContainerEditUser" class="floating-label-container">
                        <!-- User Type will be dynamically inserted here -->
                    </div>
                </div>
                <div id="departmentContainer" class="floating-label-container">
                    <!-- Department field will be dynamically inserted here -->
                </div>
                <br>
                <div style="text-align: right;">
                    <button type="button" id="cancelEditUserModalButton" class="grey-button">Cancel</button>
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

                // Create the department field based on the logged-in user's type
                const departmentContainer = document.getElementById('departmentContainer');
                const userTypeContainerEditUser = document.getElementById('userTypeContainerEditUser');
                if (loggedInUserType === 'Admin') {
                    userTypeContainerEditUser.innerHTML = `
                        <select name="user_type" id="edit_user_type" class="floating-label-input-text-area">
                            <option value="Admin">Admin</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Staff">Staff</option>
                            <option value="Student">Student</option>
                        </select>
                        <label for="edit_user_type" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Role</label>
                    `;
                    departmentContainer.innerHTML = `
                        <input type="text" name="department" id="edit_department" placeholder=" " class="floating-label-input-text-area" style="background: none; box-shadow: none; border: none; pointer-events: none" readonly>
                        <label for="edit_department" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Department</label>
                    `;
                    document.getElementById('edit_department').value = user.department || '';
                    document.getElementById('edit_user_type').value = user.user_type || '';
                } else if (loggedInUserType === 'Super Admin') {
                    userTypeContainerEditUser.innerHTML = `
                        <select name="user_type" id="edit_user_type" class="floating-label-input-text-area">
                            <option value="Admin">Admin</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Staff">Staff</option>
                            <option value="Student">Student</option>
                            <option value="Super Admin">Super Admin</option>
                        </select>
                        <label for="edit_user_type" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Role</label>
                    `;
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
                    document.getElementById('edit_user_type').value = user.user_type || '';
                }
            } else {
                console.error('User data is null or undefined');
            }
        })
        .catch(error => console.error('Error fetching user data:', error));
};

// Show edit user modal
const showEditUserModal = (userId, email, fullName, userType, evaluatorName, department, evaluatorId) => {
    populateEditUserModal(userId, email, fullName, userType, evaluatorName, department, evaluatorId);
    showModal('EditUserModal', 'editUserButton', () => editUser(userId, email, fullName, userType, evaluatorName, department, evaluatorId));
};

const editUser = (userId, email, fullName, userType, evaluatorName, department, evaluatorId) => {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    formData.append('user_id', userId);
    formData.append('action', 'edit_user');
    formData.append('full_name', fullName);
    formData.append('evaluator_name', evaluatorName);
    formData.append('evaluator_id', evaluatorId);

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
                    let successMessage = '';
                    switch (message.action) {
                        case 'approve_user':
                            successMessage = 'User approved successfully!';
                            document.getElementById('ApproveUserModal').style.display = 'none';
                            document.getElementById('successMessageVersion2').textContent = successMessage;
                            document.getElementById('successMessageModalVersion2').style.display = 'flex';
                            break;
                        case 'reject_user':
                            successMessage = 'User rejected successfully!';
                            document.getElementById('RejectUserModal').style.display = 'none';
                            document.getElementById('successMessageVersion2').textContent = successMessage;
                            document.getElementById('successMessageModalVersion2').style.display = 'flex';
                            break;
                        case 'delete_user':
                        case 'user_deleted':
                            successMessage = 'User deleted successfully!';
                            document.getElementById('DeleteUserModal').style.display = 'none';
                            document.getElementById('successMessageVersion2').textContent = successMessage;
                            document.getElementById('successMessageModalVersion2').style.display = 'flex';
                            sendDeletedUserEmail(message.email, message.full_name, message.department, message.evaluator_name, message.evaluator_message);
                            break;
                    }
                    refreshUserTable();
                } else if (message.success == false) {
                    let errorMessage = message.message || 'An error occurred. Please try again.';
                    document.getElementById('errorTextVersion2').textContent = errorMessage;
                    document.getElementById('errorModalVersion2').style.display = 'flex';
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
    const selectMultipleBtnMobile = document.getElementById('selectMultipleBtnMobile');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const deleteSelectedBtnMobile = document.getElementById('deleteSelectedBtnMobile');

    selectMultipleBtn.addEventListener('click', toggleMultiSelectMode);
    selectMultipleBtnMobile.addEventListener('click', toggleMultiSelectMode);
    deleteSelectedBtn.addEventListener('click', deleteSelectedUsers);
    deleteSelectedBtnMobile.addEventListener('click', deleteSelectedUsers);
});