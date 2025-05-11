const smartTVTableContainer = document.getElementById('smartTVTableContainer');

const displaySmartTVTable = (data) => {
    let tableHtml = `
        <table id="smartTVsTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">TV ID</th>
                    <th onclick="sortTable(1)">TV Name</th>
                    <th onclick="sortTable(2)">TV Brand</th>
                    <th onclick="sortTable(3)">TV Department</th>
                    <th>Operations</th>
                </tr>
            </thead>
            <tbody>`;

    data.forEach(row => {
        tableHtml += `<trs>
            <td style="text-align: center;">${row.tv_id}</td>
            <td style="text-align: left; column-width: 150px; overflow: auto">${row.tv_name}</td>
            <td style="text-align: left; column-width: 150px; overflow: auto">${row.tv_brand}</td>
            <td style="text-align: left;">${row.tv_department}</td>
            <td style="text-align:center;">
                <button type="button" class="green-button" style="width: 100%; margin-bottom: 5px" onclick="showEditSmartTVModal(${row.tv_id})">Edit</button>
                <button type="button" class="red-button" style="width: 100%;" onclick="showDeleteSmartTVModal(${row.tv_id})">Delete</button>
            </td>
        </tr>`;
    });

    tableHtml += `</tbody></table>`;
    smartTVTableContainer.innerHTML = tableHtml;
};

/* ============== DELETE SMART TV ============== */
// Modal content insertion
const insertDeleteSmartTVModalContent = (tvId) => {
    const modalContainer = document.getElementById('confirmDeleteSmartTVModal');
    modalContainer.innerHTML = `
        <div class="modal-content" style="padding: 15px;">
            <span class="close" id="closeDeleteSmartTVModalButton"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <h1 style="text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-trash"></i> Delete TV</h1>
            <p style="text-align: left; font-weight: 500; font-size: 16px; color: #6E6E6E;">Proceed to delete this smart TV? All of its contents will be deleted as well.</p>
            <br>
            <div style="text-align: right;">
                <button id="cancelDeleteSmartTVModalButton" class="grey-button" type="button">Cancel</button>
                <button id="deleteSmartTVButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0">Yes, I want to delete</button>
            </div>
        </div>
    `;
}

const sendWebSocketMessage = (data) => {
    let reconnectAttempts = 0;
    const reconnectDelay = 1000; // Start with 1 second delay
    let ws = null;

    const connect = () => {
        fetch('websocket_conn.php')
            .then(response => response.text())
            .then(url => {
                ws = new WebSocket(url);

                ws.onopen = () => {
                    console.log('WebSocket connection established');
                    reconnectAttempts = 0; // Reset attempts on successful connection
                    ws.send(JSON.stringify(data));
                };

                ws.onmessage = (event) => {
                    const message = JSON.parse(event.data);
                    if (message.success == true) {
                        let successMessage = '';
                        switch (message.action) {
                            case 'edit_smart_tv':
                                successMessage = 'Smart TV updated successfully!';
                                document.getElementById('editSmartTVModal').style.display = 'none';
                                showSuccessModal(successMessage);
                                break;
                            case 'delete_smart_tv':
                                successMessage = 'Smart TV deleted successfully!';
                                document.getElementById('confirmDeleteSmartTVModal').style.display = 'none';
                                showSuccessModal(successMessage);
                                break;
                        }
                        // Fetch fresh data after successful operation
                        fetch('database/fetch_smart_tvs2.php')
                            .then(response => response.json())
                            .then(tvData => {
                                displaySmartTVTable(tvData);
                            })
                            .catch(error => console.error('Error fetching updated smart TVs:', error));
                    } else if (message.success == false) {
                        let errorMessage = message.message || 'An error occurred. Please try again.';
                        showErrorModal(errorMessage);
                    }
                };

                ws.onclose = (event) => {
                    console.log('WebSocket connection closed', event.code, event.reason);
                    const timeoutDelay = Math.min(1000 * Math.pow(2, reconnectAttempts), 10000); // Exponential backoff with max 10s
                    console.log(`Attempting to reconnect in ${timeoutDelay}ms...`);
                    setTimeout(() => {
                        reconnectAttempts++;
                        connect();
                    }, timeoutDelay);
                };

                ws.onerror = (error) => {
                    console.error('WebSocket error:', error);
                    if (ws.readyState === WebSocket.CLOSED) {
                        connect();
                    }
                };
            })
            .catch(error => {
                console.error('Error fetching WebSocket URL:', error);
                setTimeout(() => {
                    reconnectAttempts++;
                    connect();
                }, reconnectDelay);
            });
    };

    connect(); // Initial connection attempt
};

const showSuccessModal = (message) => {
    const modalContent = `
        <div class="modal-content" style="padding: 15px;">
            <span class="close" id="closeSuccessModalButton"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <h1 style="text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-check-circle"></i> Success</h1>
            <p style="text-align: left; font-weight: 500; font-size: 16px; color: #6E6E6E;">${message}</p>
            <br>
            <div style="text-align: right;">
                <button id="okaySuccessModalButton" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0">Okay</button>
            </div>
        </div>
    `;

    const modal = createModal('successMessageModal', modalContent);
    modal.style.display = 'flex';

    document.getElementById('closeSuccessModalButton').onclick = () => modal.style.display = 'none';
    document.getElementById('okaySuccessModalButton').onclick = () => modal.style.display = 'none';
};

const showErrorModal = (message) => {
    const editSmartTVModal = document.getElementById('editSmartTVModal');
    if (editSmartTVModal) {
        editSmartTVModal.style.display = 'none';
    }
    const modalContent = `
        <div class="modal-content" style="padding: 15px">
            <span class="close" id="closeErrorModalButton"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <h1 style="text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-exclamation-triangle"></i> Error</h1>
            <p style="text-align: left; font-weight: 500; font-size: 16px; color: #6E6E6E;">${message}</p>
            <br>
            <div style="text-align: right;">
                <button id="okayErrorModalButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0">Okay</button>
            </div>
        </div>
    `;

    const modal = createModal('errorModal', modalContent);
    modal.style.display = 'flex';

    document.getElementById('closeErrorModalButton').onclick = () => modal.style.display = 'none';
    document.getElementById('okayErrorModalButton').onclick = () => modal.style.display = 'none';
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

// Show delete smart TV modal
const showDeleteSmartTVModal = (tvId) => {
    insertDeleteSmartTVModalContent(tvId);
    const modal = document.getElementById('confirmDeleteSmartTVModal');
    const deleteSmartTVButton = document.getElementById('deleteSmartTVButton');
    const cancelDeleteSmartTVModalButton = document.getElementById('cancelDeleteSmartTVModalButton');
    const closeDeleteSmartTVModalButton = document.getElementById('closeDeleteSmartTVModalButton');

    modal.style.display = 'flex';

    deleteSmartTVButton.onclick = () => {
        // Delete smart TV logic here
        deleteSmartTV(tvId);
        modal.style.display = 'none';
    };

    cancelDeleteSmartTVModalButton.onclick = () => {
        modal.style.display = 'none';
    }

    closeDeleteSmartTVModalButton.onclick = () => {
        modal.style.display = 'none';
    }
};

// Function to Delete smart TV
const deleteSmartTV = (tv_id) => {
    // Data to send to WebSocket server
    const data = {
        action: 'delete_smart_tv',
        tv_id: tv_id
    };

    sendWebSocketMessage(data);
};

/* ============== EDIT SMART TV DETAILS ============== */
// Function to populate the edit smart TV modal with smart TV data
const populateEditSmartTVModal = (tvId) => {
    const modalContainer = document.getElementById('editSmartTVModal');
    modalContainer.innerHTML = `
        <div class="modal-content" style="padding: 15px">
            <span class="close" id="closeEditSmartTVModalButton"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <h1 style="text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-pencil"></i> Edit TV</h1>
            <form id="editSmartTVForm">
                <div class="floating-label-container">
                    <input type="text" name="tv_id" id="edit_tv_id" placeholder=" " class="floating-label-input-text-area" style="background: none; box-shadow: none; border: none; pointer-events: none" readonly>
                    <label for="edit_tv_id" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Device ID</label>
                </div>
                <div class="floating-label-container">
                    <input type="text" name="tv_name" id="edit_tv_name" placeholder=" " class="floating-label-input-text-area" maxlength="20">
                    <label for="edit_tv_name" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">TV Name</label>
                </div>
                <div class="floating-label-container">
                    <input type="text" name="tv_brand" id="edit_tv_brand" placeholder=" " class="floating-label-input-text-area" maxlength="20">
                    <label for="edit_tv_brand" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">TV Brand</label>
                </div>
                <div class="floating-label-container">
                    <div class="floating-label-container" style="height: 55.18px; margin-top: 3px">
                        <select name="tv_department" id="edit_tv_department" class="floating-label-input-text-area" style="height: 100%; background: #FFFF;">
                            <option value="">~</option>
                            <option value="COMPUTER ENGINEERING">Computer Engineering</option>
                            <option value="CHEMICAL ENGINEERING">Chemical Engineering</option>
                            <option value="CIVIL ENGINEERING">Civil Engineering</option>
                            <option value="INDUSTRIAL ENGINEERING">Industrial Engineering</option>
                            <option value="ELECTRICAL ENGINEERING">Electrical Engineering</option>
                            <option value="MECHANICAL ENGINEERING">Mechanical Engineering</option>
                            <option value="ELECTRONICS ENGINEERING">Electronics Engineering</option>
                        </select>
                        <label for="edit_tv_department" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Department</label>
                    </div>
                </div>
                <br>
                <div style="text-align: right;">
                    <button type="button" id="cancelEditSmartTVModalButton" class="grey-button">Cancel</button>
                    <button type="submit" id="editSmartTVButton" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0">Update</button>
                </div>
            </form>
        </div>
    `;

    // Fetch smart TV data based on tvId
    fetch(`database/fetch_smart_tvs2.php?tvId=${tvId}`)
        .then(response => response.json())
        .then(tv => {
            if (tv) {
                const tvNameInput = document.getElementById('edit_tv_name');
                const tvBrandInput = document.getElementById('edit_tv_brand');
                const tvIdInput = document.getElementById('edit_tv_id');
                const tvDepartmentSelect = document.getElementById('edit_tv_department');

                // Set values and store original values
                tvNameInput.value = tv.tv_name || '';
                tvNameInput.defaultValue = tv.tv_name || '';

                tvBrandInput.value = tv.tv_brand || '';
                tvBrandInput.defaultValue = tv.tv_brand || '';

                tvIdInput.value = tv.tv_id || '';
                tvIdInput.defaultValue = tv.tv_id || '';

                tvDepartmentSelect.value = tv.tv_department || '';
                tvDepartmentSelect.setAttribute('data-original-value', tv.tv_department || '');
            } else {
                console.error('TV data is null or undefined');
            }
        })
        .catch(error => console.error('Error fetching TV data:', error));
};

// Show edit TV modal
const showEditSmartTVModal = (tvId) => {
    populateEditSmartTVModal(tvId);
    const modal = document.getElementById('editSmartTVModal');
    const cancelEditSmartTVModalButton = document.getElementById('cancelEditSmartTVModalButton');
    const closeEditSmartTVModalButton = document.getElementById('closeEditSmartTVModalButton');

    modal.style.display = 'flex';

    // Handle form submission
    const editSmartTVForm = document.getElementById('editSmartTVForm');
    editSmartTVForm.onsubmit = function(event) {
        event.preventDefault(); // Prevent the default form submission
        const tvName = document.getElementById('edit_tv_name').value.trim();
        const tvBrand = document.getElementById('edit_tv_brand').value.trim();
        const tvDepartment = document.getElementById('edit_tv_department').value.trim();

        // Get the original values from the form
        const originalTvName = document.getElementById('edit_tv_name').defaultValue.trim();
        const originalTvBrand = document.getElementById('edit_tv_brand').defaultValue.trim();
        const originalTvDepartment = document.getElementById('edit_tv_department').getAttribute('data-original-value') || '';

        // Check if any changes were made
        if (tvName === originalTvName && tvBrand === originalTvBrand && tvDepartment === originalTvDepartment) {
            modal.style.display = 'none';
            return;
        }

        // Validate TV Name and TV Brand
        if (tvName === '' || tvName === null || tvName === 'Unknown') {
            showErrorModal('TV Name cannot be empty or "Unknown". Please enter a valid TV name.');
            return;
        }

        if (tvBrand === '' || tvBrand === null || tvBrand === 'Unknown') {
            showErrorModal('TV Brand cannot be empty or "Unknown". Please enter a valid TV brand.');
            return;
        }

        // Validate TV Department
        if (tvDepartment === '' || tvDepartment === null) {
            showErrorModal('TV Department cannot be empty. Please select a valid department.');
            return;
        }

        editSmartTV(tvId, tvName, tvBrand, tvDepartment);
        modal.style.display = 'none';
    };
  
    cancelEditSmartTVModalButton.onclick = function() {
        modal.style.display = 'none';
    };

    closeEditSmartTVModalButton.onclick = function() {
        modal.style.display = 'none';
    };
};

const editSmartTV = (tv_id, tv_name, tv_brand, tv_department) => {
    // Data to send to WebSocket server
    const data = {
        action: 'edit_smart_tv',
        tv_id: tv_id,
        tv_name: tv_name,
        tv_brand: tv_brand,
        tv_department: tv_department
    };

    sendWebSocketMessage(data);
};

// Ws.addEventListener('message', function (event) {
//     const data = JSON.parse(event.data);
//     if (data.action === 'delete_smart_tv' && data.success) {
//         // Fetch all smart TVs and refresh the table
//         fetch('database/fetch_smart_tvs2.php')
//             .then(response => response.json())
//             .then(data => {
//                 displaySmartTVTable(data);
//             })
//             .catch(error => console.error('Error fetching updated smart TVs:', error));
//     } else if (data.action === 'edit_smart_tv' && data.success) {
//         // Fetch all smart TVs and refresh the table
//         fetch('database/fetch_smart_tvs2.php')
//             .then(response => response.json())
//             .then(data => {
//                 displaySmartTVTable(data);
//             })
//             .catch(error => console.error('Error fetching updated smart TVs:', error));
//     } 
// });

document.addEventListener('DOMContentLoaded', () => {
    fetch('database/fetch_smart_tvs2.php')
        .then(response => response.json())
        .then(data => {
            displaySmartTVTable(data);
        })
        .catch(error => console.error('Error fetching smart TVs:', error));
});

// const refreshSmartTVTable = () => {
//     fetch('database/fetch_smart_tvs2.php')
//         .then(response => response.json())
//         .then(data => {
//             displaySmartTVTable(data);
//         })
//         .catch(error => console.error('Error fetching updated smart TVs:', error));
// };