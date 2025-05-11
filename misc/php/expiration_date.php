<?php
$expirationDateTime = ($isEditMode && isset($contentData['expiration_datetime'])) 
    ? $contentData['expiration_datetime'] 
    : '';
?>

<div class="floating-label-container" style="flex: 1">
    <button type="button" id="expirationDateTimeModalButton" class="floating-label-input" style="background: #FFFF;">
        Select Expiration Date & Time
    </button>
    <label for="expiration_datetime" class="floating-label">Expiration Date & Time</label>
    <input type="hidden" 
        id="expiration_datetime" 
        name="expiration_datetime" 
        value="<?php echo htmlspecialchars($expirationDateTime); ?>"
        data-is-valid="true">
    <div id="expiration-success-message" style="display: none; margin-top: 3px; margin-left: 10px; font-size: 14px; "></div>
</div>    

<!-- Expiration Date & Time Modal -->
<style>
    /* Remove clear button from datetime-local input */
    input[type="datetime-local"]::-webkit-clear-button {
        display: none;
        -webkit-appearance: none;
    }
</style>

<div id="expirationDateTimeModal" class="modal">
    <div class="modal-content" style="padding: 15px">
        <h1 style="color: <?php echo $theme_color; ?>; text-align: left; font-size: 25px; padding-bottom: 15px">
            <i class="fa fa-calendar"></i> Expiration Date & Time
        </h1>
        <div style="margin-bottom: 5px;">
            <input type="datetime-local" 
                   id="expiration_datetime_modal" 
                   class="input-datetime"
                   style="width: 100%;"
                   value="<?php echo htmlspecialchars($expirationDateTime); ?>">
        </div>
        <div style="display: flex; float: right">
            <button type="button" id="closeExpirationDateTimeModal" class="grey-button" style="margin-top: 10px; margin-right: 5px">Cancel</button>
            <button type="button" id="saveExpirationDateTime" class="green-button" style="border: 1px solid #264B2B; margin-right: 0">Save</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const expirationDateTimeModalButton = document.getElementById("expirationDateTimeModalButton");
    const expirationDateTimeModal = document.getElementById("expirationDateTimeModal");
    const closeExpirationDateTimeModal = document.getElementById("closeExpirationDateTimeModal");
    const saveExpirationDateTime = document.getElementById("saveExpirationDateTime");
    const expirationDateTimeInput = document.getElementById("expiration_datetime");
    const expirationDateTimeModalInput = document.getElementById("expiration_datetime_modal");
    const messageDiv = document.getElementById("expiration-success-message");

    // Function to validate expiration date
    function validateExpirationDateTime(dateValue) {
        if (!dateValue) return false;
        const selectedDate = new Date(dateValue);
        const currentDate = new Date();
        return selectedDate > currentDate;
    }

    // Function to update validation state and message
    function updateValidationState(isValid, dateValue) {
        expirationDateTimeInput.dataset.isValid = isValid;
        
        if (isValid) {
            const formattedDate = new Date(dateValue).toLocaleString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            messageDiv.style.color = 'green';
            messageDiv.innerHTML = `<i class="fa fa-check-circle"></i> Expiration date and time is set to ${formattedDate}`;
        } else {
            messageDiv.style.color = 'red';
            messageDiv.innerHTML = `<i class="fa fa-exclamation-triangle"></i> Expiration date and time cannot be in the past`;
        }
        messageDiv.style.display = 'block';
    }

    // Open modal
    expirationDateTimeModalButton.addEventListener("click", function() {
        expirationDateTimeModal.style.display = "flex";
    });

    // Close modal
    closeExpirationDateTimeModal.addEventListener("click", function() {
        expirationDateTimeModal.style.display = "none";
    });

    // Close modal when clicking outside
    window.addEventListener("click", function(event) {
        if (event.target == expirationDateTimeModal) {
            expirationDateTimeModal.style.display = "none";
        }
    });

    // Save expiration date and time
    saveExpirationDateTime.addEventListener("click", function() {
        const selectedDate = new Date(expirationDateTimeModalInput.value);
        const currentDate = new Date();
        
        if (selectedDate == 'Invalid Date' || !expirationDateTimeModalInput.value) {
            messageDiv.style.color = 'red';
            messageDiv.innerHTML = `<i class="fa fa-exclamation-triangle"></i> Expiration date and time is required`;
            messageDiv.style.display = 'block';
            expirationDateTimeModal.style.display = "none";
            expirationDateTimeInput.dataset.isValid = "false";
            return;
        }

        const isValid = validateExpirationDateTime(expirationDateTimeModalInput.value);
        
        // Update hidden input and button text
        expirationDateTimeInput.value = expirationDateTimeModalInput.value;
        const formattedDate = selectedDate.toLocaleString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        expirationDateTimeModalButton.textContent = formattedDate;
        
        // Update validation state and show message
        updateValidationState(isValid, expirationDateTimeModalInput.value);
        
        // Close modal
        expirationDateTimeModal.style.display = "none";
    });

    // Initialize button text and validation state if there's a pre-selected value
    if (expirationDateTimeInput.value) {
        const initialDate = new Date(expirationDateTimeInput.value);
        const formattedInitialDate = initialDate.toLocaleString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        expirationDateTimeModalButton.textContent = formattedInitialDate;
        
        // Set initial validation state
        const isValid = validateExpirationDateTime(expirationDateTimeInput.value);
        updateValidationState(isValid, expirationDateTimeInput.value);
    }

    // validation check to the validateAndOpenPreviewModal function
    const originalValidateAndOpenPreviewModal = window.validateAndOpenPreviewModal;
    window.validateAndOpenPreviewModal = function() {
        // Check expiration date validity first
        const expirationInput = document.getElementById('expiration_datetime');
        if (expirationInput && expirationInput.value) {
            const isValid = validateExpirationDateTime(expirationInput.value);
            if (!isValid) {
                errorModalMessage("Expiration date and time cannot be in the past.");
                return;
            }
        }
        
        // If validation passes, call the original function
        if (typeof originalValidateAndOpenPreviewModal === 'function') {
            originalValidateAndOpenPreviewModal();
        }
    };

    // form submission validation
    if (document.querySelector('form')) {
        document.querySelector('form').addEventListener('submit', function(e) {
            const expirationInput = document.getElementById('expiration_datetime');
            if (expirationInput && expirationInput.value) {
                const isValid = validateExpirationDateTime(expirationInput.value);
                if (!isValid) {
                    e.preventDefault();
                    errorModalMessage("Expiration date and time cannot be in the past.");
                    return false;
                }
            }
        });
    }
});
</script>
