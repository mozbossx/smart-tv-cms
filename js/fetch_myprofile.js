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

const togglePassword = document.querySelector("#togglePassword");
const password = document.querySelector("[name='password']");
const toggleConfirmPassword = document.querySelector("#toggleConfirmPassword");
const passwordConfirm = document.querySelector("[name='confirm_password']");

togglePassword.addEventListener("click", function () {
    const type = password.getAttribute("type") === "password" ? "text" : "password";
    password.setAttribute("type", type);
    this.classList.toggle("bi-eye");
    this.classList.toggle("bi-eye-slash");
});

toggleConfirmPassword.addEventListener("click", function () {
    const type = passwordConfirm.getAttribute("type") === "password" ? "text" : "password";
    passwordConfirm.setAttribute("type", type);
    this.classList.toggle("bi-eye");
    this.classList.toggle("bi-eye-slash");
});


function updateTheme(color) {
    // Update the color picker value
    document.getElementById('theme_color').value = color;
    
    // Remove active class from all options
    document.querySelectorAll('.color-option').forEach(option => {
        option.classList.remove('active');
    });

    document.querySelectorAll('.color-option-container').forEach(optionContainer => {
        optionContainer.classList.remove('active');
    });
    
    // Add active class to selected option
    document.querySelector(`[data-color="${color}"]`).classList.add('active');
    document.querySelector(`[data-color-container="${color}"]`).classList.add('active');
    
    // Submit the form automatically
    const formData = new FormData();
    formData.append('theme_color', color);
    formData.append('user_id', document.querySelector('input[name="user_id"]').value);
    formData.append('email', document.querySelector('input[name="email"]').value);

    fetch('change_theme.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Reload the page to see the changes
        window.location.reload();
    })
    .catch(error => console.error('Error:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    // Get the current color from the hidden input
    const currentColor = document.getElementById('theme_color').value;
    console.log("Current theme color on load:", currentColor);
    
    // Find and activate the matching color option
    const colorOptions = document.querySelectorAll('.color-option');
    const colorOptionContainers = document.querySelectorAll('.color-option-container');
    let matchFound = false;
    
    // Remove active class from all options first
    colorOptions.forEach(option => {
        option.classList.remove('active');
    });
    
    colorOptionContainers.forEach(container => {
        container.classList.remove('active');
    });
    
    // Add active class to matching elements
    colorOptions.forEach(option => {
        if (option.getAttribute('data-color') === currentColor) {
            option.classList.add('active');
            matchFound = true;
            console.log("Applied active class to color-option:", currentColor);
        }
    });
    
    colorOptionContainers.forEach(container => {
        if (container.getAttribute('data-color-container') === currentColor) {
            container.classList.add('active');
            console.log("Applied active class to color-option-container:", currentColor);
        }
    });
    
    if (!matchFound) {
        console.warn("No matching color option found for:", currentColor);
    }
});