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
            <td style="text-align: left;">${row.tv_name}</td>
            <td style="text-align: left;">${row.tv_brand}</td>
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
        <div class="modal-content">
            <div class="red-bar-vertical">
                <span class="close" id="closeDeleteSmartTVModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
                <br>
                <h1 style="color: rgb(126, 11, 34); font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
                <p id="deleteMessage" style="text-align: center">Proceed to delete this smart TV?</p>
                <br>
                <div style="text-align: right;">
                    <button id="cancelDeleteSmartTVModalButton" class="grey-button" type="button">Cancel</button>
                    <button id="deleteSmartTVButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Yes, I want to delete</b></button>
                </div>
            </div>
        </div>
    `;
}

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
                    displaySmartTVTable(data);
                } else {
                    let errorMessage = message.message || 'An error occurred. Please try again.';
                    showErrorModal(errorMessage);
                }
            };
            Ws.onclose = () => console.log('WebSocket connection closed');
            Ws.onerror = (error) => console.error('WebSocket error:', error);
        })
        .catch(error => console.error('Error fetching WebSocket URL:', error));
};

const showSuccessModal = (message) => {
    const modalContent = `
        <div class="modal-content" style="padding: 15px">
            <span class="close" id="closeSuccessModalButton" style="color: #334b35"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
            <p id="successMessage" style="text-align: center">${message}</p>
            <br>
            <div style="text-align: right;">
                <button id="okaySuccessModalButton" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Okay</b></button>
            </div>
        </div>
    `;

    const modal = createModal('successMessageModal', modalContent);
    modal.style.display = 'flex';

    document.getElementById('closeSuccessModalButton').onclick = () => modal.style.display = 'none';
    document.getElementById('okaySuccessModalButton').onclick = () => modal.style.display = 'none';
};

const showErrorModal = (message) => {
    const modalContent = `
        <div class="modal-content" style="padding: 15px">
            <span class="close" id="closeErrorModalButton" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: rgb(126, 11, 34); font-size: 50px"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></h1>
            <p id="errorText" style="text-align: center">${message}</p>
            <br>
            <div style="text-align: right;">
                <button id="okayErrorModalButton" class="red-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Okay</b></button>
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
            <span class="close" id="closeEditSmartTVModalButton" style="color: #334b35"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #334b35; font-size: 50px"><i class="fa fa-pencil" aria-hidden="true"></i></h1>
            <p id="editMessage" style="text-align: center">Edit Smart TV</p>
            <form id="editSmartTVForm">
                <div class="floating-label-container">
                    <input type="text" name="tv_id" id="edit_tv_id" placeholder=" " class="floating-label-input-text-area" style="background: none; box-shadow: none; border: none; pointer-events: none" readonly>
                    <label for="edit_tv_id" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Device ID</label>
                </div>
                <div class="floating-label-container">
                    <input type="text" name="tv_name" id="edit_tv_name" placeholder=" " class="floating-label-input-text-area">
                    <label for="edit_tv_name" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">TV Name</label>
                </div>
                <div class="floating-label-container">
                    <input type="text" name="tv_brand" id="edit_tv_brand" placeholder=" " class="floating-label-input-text-area">
                    <label for="edit_tv_brand" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">TV Brand</label>
                </div>
                <div class="floating-label-container">
                    <select name="tv_department" id="edit_tv_department" class="floating-label-input">
                        <option value="">~</option>
                        <option value="COMPUTER ENGINEERING">Department of Computer Engineering</option>
                        <option value="CHEMICAL ENGINEERING">Department of Chemical Engineering</option>
                        <option value="CIVIL ENGINEERING">Department of Civil Engineering</option>
                        <option value="INDUSTRIAL ENGINEERING">Department of Industrial Engineering</option>
                        <option value="ELECTRICAL ENGINEERING">Department of Electrical Engineering</option>
                        <option value="MECHANICAL ENGINEERING">Department of Mechanical Engineering</option>
                        <option value="ELECTRONICS ENGINEERING">Department of Electronics Engineering</option>
                    </select>
                    <label for="edit_tv_department" class="floating-label">Department</label>
                </div>
                <br>
                <div style="text-align: right;">
                    <button type="button" id="cancelEditSmartTVModalButton" class="grey-button">Cancel</button>
                    <button type="submit" id="editSmartTVButton" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0"><b>Update</b></button>
                </div>
            </form>
        </div>
    `;

    // Fetch smart TV data based on tvId
    fetch(`database/fetch_smart_tvs2.php?tvId=${tvId}`)
        .then(response => response.json())
        .then(tv => {
            console.log('Fetched smart TV data:', tv); // Log fetched tv data to check if it's correct
            if (tv) {
                document.getElementById('edit_tv_name').value = tv.tv_name || '';
                document.getElementById('edit_tv_brand').value = tv.tv_brand || '';
                document.getElementById('edit_tv_id').value = tv.tv_id || '';
                document.getElementById('edit_tv_department').value = tv.tv_department || '';
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
        const tvName = document.getElementById('edit_tv_name').value;
        const tvBrand = document.getElementById('edit_tv_brand').value;
        const tvDepartment = document.getElementById('edit_tv_department').value;

        editSmartTV(tvId, tvName, tvBrand, tvDepartment);
        modal.style.display = 'none';
    };
  
    cancelEditSmartTVModalButton.onclick = function() {
        modal.style.display = 'none';
    };

    closeEditSmartTVModalButton.onclick = function() {
        modal.style.display = 'none';
    };

    // Close modal when clicking outside the modal content
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
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

Ws.addEventListener('message', function (event) {
    const data = JSON.parse(event.data);
    if (data.action === 'delete_smart_tv' && data.success) {
        // Fetch all smart TVs and refresh the table
        fetch('database/fetch_smart_tvs2.php')
            .then(response => response.json())
            .then(data => {
                displaySmartTVTable(data);
            })
            .catch(error => console.error('Error fetching updated smart TVs:', error));
    } else if (data.action === 'edit_smart_tv' && data.success) {
        // Fetch all smart TVs and refresh the table
        fetch('database/fetch_smart_tvs2.php')
            .then(response => response.json())
            .then(data => {
                displaySmartTVTable(data);
            })
            .catch(error => console.error('Error fetching updated smart TVs:', error));
    } 
});

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