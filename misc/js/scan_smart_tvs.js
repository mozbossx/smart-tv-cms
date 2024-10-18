function scanForSmartTVs() {
    showLoadingIndicator();
    fetch('discover_tvs.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            hideLoadingIndicator();
            if (data.success) {
                showScanResults(data.tvs);
            } else {
                console.error('Error during discovery:', data.error);
                alert('Error during discovery: ' + data.error);
            }
        })
        .catch(error => {
            hideLoadingIndicator();
            console.error('Error:', error);
            alert('An error occurred during the scan: ' + error.message);
        });
}
function showLoadingIndicator() {
    // Add loading indicator to the page
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'modal';
    loadingDiv.id = 'loadingIndicator';
    loadingDiv.innerHTML = `
        <div class="modal-content" style="text-align: center;">
            <h1><i class="fa fa-tv" style="color: #334b35; font-size: 50px;"></i></h1>
            <h2>Scanning for Smart TVs...</h2>
            <div class="loader-container">
                <div class="loader"></div>
            </div>
        </div>
    `;
    document.body.appendChild(loadingDiv);
    loadingDiv.style = "display: flex; align-items: center; justify-content: center;";
}
function hideLoadingIndicator() {
    // Remove loading indicator
    const loadingDiv = document.getElementById('loadingIndicator');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}
function showScanResults(discoveredTVs) {
    const modal = document.getElementById('scanResultsModal');
    let content = '<h2>Discovered Smart TVs</h2>';
    if (discoveredTVs.length === 0) {
        content += `
            <h1><i class="fa fa-exclamation-triangle" style="font-size: 50px; color: #FFA500; margin-top: 20px;"></i></h1>
            <p>No Smart TVs found on the local network.</p>
            <button onclick="scanForSmartTVs()" class="green-button" style="background: none; color: black"><i class="fa fa-search" style="padding-right: 5px"></i>Scan Again</button>
        `;
    } else {
        content += '<ul>';
        discoveredTVs.forEach(tv => {
            content += `<li>${tv.name} (${tv.location}) <button onclick="enrollTV('${tv.name}', '${tv.location}')">Enroll</button></li>`;
        });
        content += '</ul>';
    }
    modal.innerHTML = `
    <div class="modal-content">
        <div style="height: 200px; overflow-y: auto;">
            ${content}
        </div>
        <div style="text-align: right;">
            <button onclick="closeScanResultsModal()" class="green-button">Close</button>
        </div>
    </div>
    `;
    modal.style.display = 'flex';
}
function closeScanResultsModal() {
    const modal = document.getElementById('scanResultsModal');
    modal.style.display = 'none';
}
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
            alert(`Successfully enrolled ${name}`);
            fetchSmartTVs();
        } else {
            alert(`Failed to enroll ${name}: ${data.error}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(`An error occurred while enrolling ${name}`);
    });
}