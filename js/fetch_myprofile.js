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


function updateTheme(color) {
    // Update the color picker value
    document.getElementById('theme_color').value = color;
    
    // Remove active class from all options
    document.querySelectorAll('.color-option').forEach(option => {
        option.classList.remove('active');
    });
    
    // Add active class to selected option
    document.querySelector(`[data-color="${color}"]`).classList.add('active');
    
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
    const currentColor = document.getElementById('theme_color').value;
    const matchingOption = document.querySelector(`[data-color="${currentColor}"]`);
    if (matchingOption) {
        matchingOption.classList.add('active');
    }
});