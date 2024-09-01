document.addEventListener('DOMContentLoaded', function() {
    const contentType = document.querySelector('[name="type"]').value;
    const form = document.getElementById(`${contentType}Form`);

    // Fetch the WebSocket URL from the PHP file
    fetch('websocket_conn.php')
        .then(response => response.text())
        .then(url => {
            const ws = new WebSocket(url);

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(form);
                const data = { 
                    action: 'post_content',
                    tv_ids: formData.getAll('tv_id[]') // Collect all tv_id[] values
                };

                // Check for file input
                const mediaInput = document.getElementById('media');
                if (mediaInput && mediaInput.files.length > 0) {
                    const mediaFile = mediaInput.files[0];
                    const reader = new FileReader();

                    reader.onload = function (e) {
                        const base64Data = e.target.result;
                        formData.set('media', base64Data);
                        formData.forEach((value, key) => {
                            data[key] = value;
                        });

                        ws.send(JSON.stringify(data));
                    };

                    reader.readAsDataURL(mediaFile);
                } else {
                    formData.forEach((value, key) => {
                        data[key] = value;
                    });

                    console.log('Form Data:', data);
                    ws.send(JSON.stringify(data));
                }

                // Listen for messages from the WebSocket server
                ws.onmessage = function(event) {
                    const message = JSON.parse(event.data);
                    if (message.success) {
                        // Redirect the user to user_home.php if success
                        // Display successMessageModal
                        // Set the success message
                        document.getElementById('successMessage').textContent = contentType + " was successfully processed!";                        
                        document.getElementById('successMessageModal').style.display = 'flex';
                    } else {
                        // Display an error modal
                        document.getElementById('errorText').textContent = "Error processing " + contentType + ". Try again later";
                        document.getElementById('errorModal').style.display = 'flex';
                    }
                };
            });
        })
        .catch(error => {
            console.error('Error fetching WebSocket URL:', error);
        });
});

function resetFormAndGoHome() {
    // Get the form by content type
    const contentType = document.querySelector('[name="type"]').value;
    const form = document.getElementById(`${contentType}Form`);

    // Reset all the form fields to null
    form.reset();

    // Navigate to the home page
    location.href = 'user_home.php?pageid=UserHome&userId=<?php echo $user_id; ?>&fullName=<?php echo $full_name; ?>';
}