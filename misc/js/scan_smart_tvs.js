function enrollTV(name, location) {
    fetch('enroll_tv.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `name=${encodeURIComponent(name)}&location=${encodeURIComponent(location)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.message.includes('browser opened')) {
                alert(`Successfully enrolled ${name} and opened browser`);
            } else {
                console.error('Failed to open browser:', data);
                let errorDetails = JSON.stringify(data, null, 2);
                alert(`Enrolled ${name}, but failed to open browser. Error details:\n${errorDetails}`);
            }
            fetchSmartTVs();
        } else {
            console.error('Enrollment failed:', data);
            let errorDetails = JSON.stringify(data, null, 2);
            alert(`Failed to enroll ${name}. Error details:\n${errorDetails}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(`An error occurred while enrolling ${name}: ${error.message}`);
    });
}