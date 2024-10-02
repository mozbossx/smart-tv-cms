let isFormDirty = false;

// Mark the form as dirty when any input changes
document.querySelectorAll('.main-form input, .main-form textarea, .main-form select').forEach(input => {
    input.addEventListener('input', () => {
        isFormDirty = true;
        // document.getElementById('saveDraftButton').style.display = "block";
    });
});

// Show confirmation dialog on page unload if the form is dirty
window.addEventListener('beforeunload', (event) => {
    if (isFormDirty) {
        const message = "There are unsaved changes. Proceed to go to a different page?";
        event.returnValue = message; // For most browsers
        return message; // For some older browsers
    }
});