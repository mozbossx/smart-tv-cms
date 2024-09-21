// const Ws = new WebSocket('ws://192.168.1.13:8081');

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

    // Save the active tab to localStorage
    localStorage.setItem('activeTab', tabName);
};

function setInitialActiveTab() {
    const activeTab = localStorage.getItem('activeTab') || 'MyProfile';
    const tabElement = document.querySelector(`.tablinks[onclick="openTab(event, '${activeTab}')"]`);
    if (tabElement) {
        tabElement.click();
    }
}

function confirmLogout() {
    return confirm("Are you sure you want to log out?");
}

function logout() {
    window.location.href = "logout.php";
}

function openModal(modalId, userName, userId) {
    var modal = document.getElementById(modalId + 'Modal');
    if (modal) {
        modal.style.display = 'flex';
        
        // Only set values if the inputs exist
        var userNameInput = modal.querySelector('[name="user_name"]');
        var userIdInput = modal.querySelector('[name="user_id"]');
        
        if (userNameInput) userNameInput.value = userName || '';
        if (userIdInput) userIdInput.value = userId || '';
    } else {
        console.error(`Modal with id "${modalId}Modal" not found`);
    }
}

function updatePostsUI(data, contentType) {
    const postsContainer = document.getElementById('postsContainer');
    postsContainer.innerHTML = '';
    if (data.length === 0) {
        postsContainer.innerHTML = '<p>No posts found.</p>';
    } else {
        data.forEach(post => {
            const postDiv = createPostDiv(post, contentType);
            postsContainer.appendChild(postDiv);
        });
    }
}

function openViewAllPostsModal(modalId, contentType) {
    var modal = document.getElementById(modalId + 'Modal');
    modal.style.display = 'flex';
    document.getElementById('modalTitle').textContent = contentType.charAt(0).toUpperCase() + contentType.slice(1) + ' Posts';
    fetchPosts(contentType);
}

function fetchPosts(contentType) {
    console.log('Fetching posts for ' + contentType);
    fetch('get_posts.php?type=' + contentType)
        .then(response => response.json())
        .then(data => {
            updatePostsUI(data, contentType);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('postsContainer').innerHTML = '<p>Error fetching posts.</p>';
        });
}

function createPostDiv(data, type) {
    const containerDiv = document.createElement('div');
    containerDiv.className = 'content-container-con';
    containerDiv.style = `
        background: #dffce5;
        margin-bottom: 10px;
        border-radius: 15px;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        padding: 15px;
    `;

    let contentHtml = '';

    // Add media content if available
    if (data.media_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.media_path);
        const isVideo = /\.(mp4|webm|ogg)$/i.test(data.media_path);
        const mediaContent = isImage 
            ? `<img src="servers/${type}_media/${data.media_path}" style="width: auto; height: auto; max-width: 100%; max-height: 100%; display: block; margin: 0 auto; border-radius: 5px">` 
            : isVideo 
            ? `<video width="100%" height="100%" controls style="width: 100%"><source src="servers/${type}_media/${data.media_path}" type="video/mp4"></video>` 
            : '';
        
        if (mediaContent) {
            contentHtml += `<div class="media-container" style="margin-bottom: 5px">${mediaContent}</div>`;
        }
    }

    // Add type-specific content
    switch(type) {
        case 'announcement':
        case 'event':
        case 'news':
            contentHtml += `<pre class="ann-body" style="word-break: break-word">${data[`${type}_body`]}</pre>`;
            break;
        case 'promaterial':
            break;
        case 'peo':
        case 'so':
            contentHtml += `<pre class="ann-body" style="word-break: break-word"><b>${data[`${type}_title`]}</b></pre>
                            <pre class="ann-body" style="word-break: break-word">${data[`${type}_description`]}</pre>
                            <pre class="ann-body" style="word-break: break-word">${data[`${type}_subdescription`]}</pre>`;
            break;
        default:
            // For new features, dynamically add all fields
            for (let key in data) {
                if (key !== `${type}_id` && key !== 'created_datetime' && key !== 'expiration_datetime' 
                    && key !== 'display_time' && key !== 'status' && key !== 'isCancelled' 
                    && key !== 'tv_id' && key !== `${type}_author_id` && key !== 'type' && key !== 'department' 
                    && key !== 'user_type' && key !== 'category' && key !== 'evaluated_by' && key !== 'evaluated_message' && key !== 'author_name') {
                        contentHtml += `<p>${data[key]}</p>`;
                }
            }
    }

    const formattedCreatedDateTime = formatDate(data.created_datetime);

    contentHtml += `
        <div class="line-separator"></div>
        <p class="ann-author" style="color: #6E6E6E; font-size: 13px"><small>Posted on ${formattedCreatedDateTime}</small></p>
        ${data.status ? `<p class="status" style="color: #6E6E6E; font-size: 13px"><small>Status: <b>${data.status}</b></small></p>` : ''}
    `;

    containerDiv.innerHTML = contentHtml;

    // Create button container
    const buttonContainer = document.createElement('div');
    buttonContainer.style = `
        display: flex;
        justify-content: flex-end;
        margin-top: 10px;
    `;

    // Create buttons
    const deleteButton = createButton('Delete', 'fa fa-trash', () => showDeleteModal(data[`${type}_id`], type));
    buttonContainer.appendChild(deleteButton);
    if(data.isCancelled === 0){
        const archiveButton = createButton('Archive', 'fa fa-archive', () => showArchiveModal(data[`${type}_id`], type));
        buttonContainer.appendChild(archiveButton);
    } else if (data.isCancelled === 1){
        const unarchiveButton = createButton('Unarchive', 'fa fa-archive', () => showUnarchiveModal(data[`${type}_id`], type));
        buttonContainer.appendChild(unarchiveButton);
    }
    const editButton = createButton('Edit', 'fa fa-pencil-square', () => window.location.href = `edit_${type}.php?${type}_id=${data[`${type}_id`]}&${type}_author_id=${data[`${type}_author_id`]}`);
    buttonContainer.appendChild(editButton);

    // Append button container to main container
    containerDiv.appendChild(buttonContainer);

    return containerDiv;
}

// Helper function to create buttons
function createButton(text, iconClass, onClick) {
    const button = document.createElement('button');
    button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i> ${text}`;
    button.style = `
        background-color: #316038;
        color: #fff;
        padding: 8px 16px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s;
        margin-top: 10px;
        margin-left: 5px;
    `;
    button.onclick = onClick;
    return button;
}

function createModals(contentTypes) {
    const modalContainer = document.createElement('div');
    modalContainer.id = 'dynamicModals';

    contentTypes.forEach(type => {
        const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
        
        // Archive Modal
        const archiveModal = document.createElement('div');
        archiveModal.id = `confirmArchive${capitalizedType}Modal`;
        archiveModal.className = 'modal';
        archiveModal.innerHTML = `<div id="archive${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(archiveModal);

        // Unarchive Modal
        const unarchiveModal = document.createElement('div');
        unarchiveModal.id = `confirmUnarchive${capitalizedType}Modal`;
        unarchiveModal.className = 'modal';
        unarchiveModal.innerHTML = `<div id="unarchive${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(unarchiveModal);

        // Delete Modal
        const deleteModal = document.createElement('div');
        deleteModal.id = `confirmDelete${capitalizedType}Modal`;
        deleteModal.className = 'modal';
        deleteModal.innerHTML = `<div id="delete${capitalizedType}ModalContent"></div>`;
        modalContainer.appendChild(deleteModal);
    });

    document.body.appendChild(modalContainer);
}

// Function to show delete modal
function showDeleteModal(id, type) {
    const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
    const modal = document.getElementById(`confirmDelete${capitalizedType}Modal`);
    const modalContent = document.getElementById(`delete${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    
    modalContent.innerHTML = `        
        <div class="red-bar-vertical">
            <span class="close" onclick="document.getElementById('confirmDelete${capitalizedType}Modal').style.display='none'" style="color: rgb(126, 11, 34)"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #7E0B22; font-size: 50px"><i class="fa fa-trash" aria-hidden="true"></i></h1>
            <p id="deleteMessage" style="text-align: center">Proceed to delete?</p>
            <br>
            <div style="text-align: right;">
                <button type="button" class="red-button" style="background: #334b353b; color: black" onclick="document.getElementById('confirmDelete${capitalizedType}Modal').style.display='none'">Cancel</button>
                <button type="button" class="red-button" onclick="deleteItem('${type}', '${id}')">Yes, Delete</button>
            </div>
        </div>
    `;
    
    modal.style.display = 'flex';
}

// Function to show archive modal
function showArchiveModal(id, type) {
    const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
    const modal = document.getElementById(`confirmArchive${capitalizedType}Modal`);
    const modalContent = document.getElementById(`archive${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    
    modalContent.innerHTML = `
        <div class="yellow-bar-vertical">
            <span class="close" onclick="document.getElementById('confirmArchive${capitalizedType}Modal').style.display='none'" style="color: #dc7d09"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #dc7d09; font-size: 50px"><i class="fa fa-archive" aria-hidden="true"></i></h1>
            <p id="deleteMessage" style="text-align: center">Proceed to archive?</p>
            <br>
            <div style="text-align: right;">
                <button type="button" class="red-button" style="background: #334b353b; color: black" onclick="document.getElementById('confirmArchive${capitalizedType}Modal').style.display='none'">Cancel</button>
                <button type="button" class="yellow-button" onclick="archiveItem('${type}', '${id}')">Yes, Archive</button>
            </div>
        </div>
    `;
    
    modal.style.display = 'flex';
}

function showUnarchiveModal(id, type) {
    const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
    const modal = document.getElementById(`confirmUnarchive${capitalizedType}Modal`);
    const modalContent = document.getElementById(`unarchive${capitalizedType}ModalContent`);
    modalContent.className = 'modal-content';
    
    modalContent.innerHTML = `
        <div class="yellow-bar-vertical">
            <span class="close" onclick="document.getElementById('confirmUnarchive${capitalizedType}Modal').style.display='none'" style="color: #dc7d09"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
            <br>
            <h1 style="color: #dc7d09; font-size: 50px"><i class="fa fa-archive" aria-hidden="true"></i></h1>
            <p id="deleteMessage" style="text-align: center">Proceed to unarchive?</p>
            <br>
            <div style="text-align: right;">
                <button type="button" class="red-button" style="background: #334b353b; color: black" onclick="document.getElementById('confirmUnarchive${capitalizedType}Modal').style.display='none'">Cancel</button>
                <button type="button" class="yellow-button" onclick="unarchiveItem('${type}', '${id}')">Yes, Unarchive</button>
            </div>
        </div>
    `;
    
    modal.style.display = 'flex';
}

// Function to delete an item
function deleteItem(type, id) {
    const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
    const modal = document.getElementById(`confirmDelete${capitalizedType}Modal`);
    const data = {
        action: 'delete',
        type: type,
        [`${type}_id`]: id
    };
    modal.style.display = 'none';
    Ws.send(JSON.stringify(data));
}

// Function to archive an item
function archiveItem(type, id) {
    const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
    const modal = document.getElementById(`confirmArchive${capitalizedType}Modal`);
    const data = {
        action: 'archive',
        type: type,
        [`${type}_id`]: id
    };
    modal.style.display = 'none';
    Ws.send(JSON.stringify(data));
}

function unarchiveItem(type, id) {
    const capitalizedType = type.charAt(0).toUpperCase() + type.slice(1);
    const modal = document.getElementById(`confirmUnarchive${capitalizedType}Modal`);
    const data = {
        action: 'unarchive',
        type: type,
        [`${type}_id`]: id
    };
    modal.style.display = 'none';
    Ws.send(JSON.stringify(data));
}

// Function to format date to "MM DD YYYY"
function formatDate(dateTimeString) {
    const dateTime = new Date(dateTimeString);
    const options = {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    };
    return new Intl.DateTimeFormat('en-US', options).format(dateTime);
}

function closeModal(modalId) {
    var modal = document.getElementById(modalId + 'Modal');
    modal.style.display = 'none';

    // Clear password fields
    var passwordFields = modal.querySelectorAll("[type='password']");
    passwordFields.forEach(function(field) {
        field.value = '';
    });
}

function changeUserNameSubmit() {
    document.getElementById('updateUserNameForm').submit();
}

function changePasswordSubmit() {
    document.getElementById('changePasswordForm').submit();
}

// JavaScript to toggle password visibility and show/hide labels
const togglePassword = document.querySelector("#togglePassword");
const toggleConfirmPassword = document.querySelector("#toggleConfirmPassword");
const password1 = document.querySelector("[name='password']");
const password2 = document.querySelector("[name='confirm_password']");
const floatingInput = document.querySelector(".floating-label-input");

togglePassword.addEventListener("click", function () {
    // Toggle the type attribute
    const type = password1.getAttribute("type") === "password" ? "text" : "password";
    password1.setAttribute("type", type);
    // Toggle the text of the toggle button
    this.textContent = type === "password" ? "Show" : "Hide";
});

toggleConfirmPassword.addEventListener("click", function () {
    // Toggle the type attribute
    const type = password2.getAttribute("type") === "password" ? "text" : "password";
    password2.setAttribute("type", type);
    // Toggle the text of the toggle button
    this.textContent = type === "password" ? "Show" : "Hide";
});

Ws.addEventListener('message', function(event) {
    const data = JSON.parse(event.data);
    switch (data.action) {
        case 'archive':
        case 'unarchive':
        case 'delete':
        case 'update':
            // Refresh the posts for the affected content type
            fetchPosts(data.type);
            break;

    }

});

document.addEventListener('DOMContentLoaded', function() {
    const contentTypes = ['announcement', 'event', 'news', 'promaterial', 'peo', 'so'];
    createModals(contentTypes);

    setInitialActiveTab();

});