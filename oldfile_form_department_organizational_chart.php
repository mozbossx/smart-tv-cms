<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// check if the user is not Super Admin or Admin
include 'admin_access_only.php';

// fetch tv data from the select options
include 'misc/php/options_tv.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
    <link rel="icon" type="image/png" href="images/usc_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="misc/js/treant-js-master/vendor/jquery.min.js"></script>
    <script src="misc/js/treant-js-master/vendor/raphael.js"></script>
    <script src="misc/js/treant-js-master/Treant.js"></script>
    <link rel="stylesheet" href="misc/js/treant-js-master/Treant.css" />

    <title>Create an Organizational Chart</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <div class="content-form">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb" style="background: none">
                                <li class="breadcrumb-item"><a href="create_post.php?pageid=CreatePost?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Create Post</a></li>
                                <li class="breadcrumb-item"><a href="general_info.php?pageid=GeneralInformationForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">General Information</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Department Organizational Chart Form</li>
                            </ol>
                        </nav>
                        <form id="orgchartForm" enctype="multipart/form-data" class="main-form" style="max-width: 100%">
                            <?php include('error_message.php'); ?>
                            <input type="hidden" name="type" value="orgchart">
                            <h1 style="text-align: center">Department Organizational Chart Form</h1>
                            <div style="position: relative">
                                <div class="chart-parent-container" style="place-items: center; background-color: none">
                                    <div id="chart-container" class="chart-container" style="width: auto;"></div>
                                </div>
                            </div>
                            <?php include('misc/php/displaytime_tvdisplay.php')?>
                            <div style="display: flex; flex-direction: row; margin-left: auto; margin-top: 10px">
                                <div>
                                    <button type="button" name="preview" id="previewButton" class="preview-button" style="margin-right: 0" onclick="validateAndOpenPreviewModal()">
                                        <i class="fa fa-eye" style="padding-right: 5px"></i> Preview 
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for Adding a Member -->
    <div class="modal" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
        <div class="modal-content" style="padding: 15px">
            <h1 style="color: <?php echo $theme_color; ?>; font-size: 50px"><i class="fa fa-user-plus" aria-hidden="true"></i></h1>
            <p>Add New Member</p>
            <form id="addMemberForm" enctype="multipart/form-data">
                <div class="floating-label-container">
                    <input type="text" id="memberName" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area">
                    <label for="memberName" style="width: auto; padding: 5px; margin-top: 2px; border-radius: 0" class="floating-label-text-area">Member Name</label>
                </div>
                <div class="floating-label-container">
                    <input type="text" id="memberTitle" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input-text-area">
                    <label for="memberTitle" style="width: auto; padding: 5px; margin-top: 2px; border-radius: 0" class="floating-label-text-area">Title</label>
                </div>
                <input type="hidden" id="parentNodeHiddenInput" value="">
                <div class="floating-label-container">
                    <input type="file" class="floating-label-input" style="padding-top: 23px; background: white" id="memberPicture" accept="image/*">
                    <label for="memberPicture" style="width: auto; padding: 5px; margin-top: 2px; border-radius: 0" class="floating-label">Upload Picture</label>
                </div>
            </form>
            <br>
            <div style="text-align: right; margin-top: 5px">
                <button type="button" class="grey-button" onclick="closeAddMemberModal()">Close</button>
                <button type="button" class="green-button" onclick="submitMemberForm()" style="margin-right: 0">Add Member</button>
            </div>
        </div>
    </div>
    <!-- Preview Modal -->
    <div id="previewModal" class="modal">
        <div class="modal-content-preview">
            <div class="flex-preview-content">
                <?php 
                    $sqlContainers = "SELECT c.*, t.width_px as tv_width_px, t.height_px as tv_height_px, t.tv_brand as tv_brand, t.tv_name as tv_name, t.tv_department as tv_department,
                        b.background_image_path, b.background_hex_color, b.isImageBackground, b.isStaticBackground,
                        tb.topbar_hex_color, tb.department_logo_path,
                        tb.topbar_tvname_font_color, tb.topbar_tvname_font_style, tb.topbar_tvname_font_family, 
                        tb.topbar_tvid_font_color, tb.topbar_tvid_font_style, tb.topbar_tvid_font_family, 
                        tb.topbar_time_font_color, tb.topbar_time_font_style, tb.topbar_time_font_family, 
                        tb.topbar_date_font_color, tb.topbar_date_font_style, tb.topbar_date_font_family
                    FROM containers_tb c 
                    JOIN smart_tvs_tb t ON c.tv_id = t.tv_id
                    LEFT JOIN background_tv_tb b ON c.tv_id = b.tv_id
                    LEFT JOIN topbar_tv_tb tb ON c.tv_id = tb.tv_id";
                    $resultContainers = mysqli_query($conn, $sqlContainers);
                    $containers = [];
                    while ($row = mysqli_fetch_assoc($resultContainers)) {
                        $containers[] = $row;
                    }
                ?>
                <div style="display: flex, flex-direction: column; flex: 2; height: auto; overflow: auto">
                    <div id="previewContainer">
                    </div>
                </div>
                <!-- The container consists of child_background_color, child_font_style, child_font_color-->
                <div class="preview-content" id="previewContent"></div>
            </div>
            <!-- Operation buttons inside the Preview modal -->
            <div class="flex-button-modal">
                <button type="button" class="grey-button" id="closeButton" style="margin-top: 0; margin-right: 5px" onclick="closePreviewModal()">Cancel</button>
                <button type="button" name="post" class="green-button" style="margin-top: 0; margin-right: 0" onclick="submitFormViaWebSocket()">Submit</button>
            </div>
        </div>
    </div>
    <?php 
        $topbarPositions = [];
        $topbarQuery = "SELECT tv_id, topbar_position FROM topbar_tv_tb";
        $resultTopbar = mysqli_query($conn, $topbarQuery);
        while ($row = mysqli_fetch_assoc($resultTopbar)) {
            $topbarPositions[$row['tv_id']] = $row['topbar_position'];
        }
    ?>
    <?php include 'misc/php/success_modal.php' ?>
    <?php include 'misc/php/error_modal.php' ?>
    <script>
        let clockInterval;
        const containers = <?php echo json_encode($containers); ?>;
        const tvNames = <?php echo !empty($tv_names) ? json_encode($tv_names) : '{}'; ?>; 
        const contentType = document.querySelector('[name="type"]').value;

        let orgChartData = []; // Initialize an empty array to store org chart data

        function openAddMemberModal(parentId) {
            const modal = document.getElementById('addMemberModal');
            modal.style.display = "flex";
            modal.classList.add('show');

            // Set the value of the hidden input
            const parentNodeHiddenInput = document.getElementById('parentNodeHiddenInput');
            parentNodeHiddenInput.value = parentId || ''; // Set to empty string if parentId is null
        }

        function closeAddMemberModal() {
            const modal = document.getElementById('addMemberModal');
            modal.style.display = "none";
            modal.classList.remove('show');
        }

        function submitFormViaWebSocket() {
            const data = {
                action: 'post_content',
                type: 'orgchart',
                orgChartData: orgChartData,
                display_time: document.querySelector('[name="display_time"]').value,
                tv_ids: Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => checkbox.value)
            };

            ws.send(JSON.stringify(data));
            console.log("Chart Data: ", data);
            console.log("OrgChartData: ", orgChartData)

            ws.onmessage = function(event) {
                const message = JSON.parse(event.data);
                if (message.action === 'post_content') {
                    if (message.success == true) {
                        const successMessageModal = document.getElementById('successMessageModal');
                        successMessageModal.style.display = 'flex';
                        document.getElementById('successMessage').textContent = "Organizational Chart was successfully processed!";
                        // Close the preview modal if it's open
                        const previewModal = document.getElementById('previewModal');
                        if (previewModal) {
                            previewModal.style.display = 'none';
                        }
                    } else if (message.success == false) {
                        alert('Error saving organizational chart. Please try again.');
                    }
                }
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById(`${contentType}Form`);

            // Fetch the WebSocket URL from the PHP file
            fetch('websocket_conn.php')
                .then(response => response.text())
                .then(url => {
                    const ws = new WebSocket(url);

                    // Attach the WebSocket to the window object for global access
                    window.ws = ws;

                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        submitFormViaWebSocket();
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

        document.getElementById('closeButton').addEventListener('click', function() {
            closePreviewModal();
        });

        // Function to close the preview modal
        function closePreviewModal() {
            var modal = document.getElementById('previewModal');
            modal.style.display = 'none';
        }

        function validateAndOpenPreviewModal() {
            const displayTime = document.querySelector('[name="display_time"]').value;
            const tvDisplays = document.querySelectorAll('[name="tv_id[]"]:checked'); // Get all checked TV displays
            
            if (displayTime === "" || tvDisplays.length === 0 || orgChartData.length === 0) {
                errorModalMessage("Please add at least one member to the org chart and select at least one TV display.");
                return;
            }

            openPreviewModal();
        }

        // Function to get the preview content
        function getPreviewContent() {
            const selectedType = document.querySelector('[name="type"]').value;
            // Function to format date and time
            function formatDateTime(dateString, timeString) {
                const dateTime = new Date(dateString + ' ' + timeString);
                const options = {
                    weekday: 'short',
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    hour12: true
                };
                return new Intl.DateTimeFormat('en-US', options).format(dateTime);
            }

            var previewContent = '';

            if (selectedType !== 'peo' && selectedType !== 'so' && selectedType !== 'orgchart') {
                previewContent += '<p class="preview-input"><strong>Display Time: </strong><br>' + document.querySelector('[name="display_time"]').value + ' seconds</p>';
                previewContent += '<p class="preview-input"><strong>Expiration Date & Time: </strong><br>' + formatDateTime(document.querySelector('[name="expiration_date"]').value, document.querySelector('[name="expiration_time"]').value) + '</p>';
            } else {
                previewContent += '<p class="preview-input"><strong>Display Time: </strong><br>' + document.querySelector('[name="display_time"]').value + ' seconds</p>';
            }

            const selectedTvs = Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => checkbox.value);
            const selectedTvNames = selectedTvs.map(tvId => tvNames[tvId]); // Map tv_id to tv_name
            previewContent += '<p class="preview-input"><strong>TV Display: </strong><br>' + (selectedTvNames.length > 0 ? selectedTvNames.join(", ") : 'None selected') + '</p>';
            
            return previewContent;
        }

        // Function to open the preview modal
        function openPreviewModal() {
            var modal = document.getElementById('previewModal');
            modal.style.display = 'flex';

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
            // Initial call to load the container when the page loads with a pre-selected tv_id
            updatePreviewContent();
            // Display the preview content in the modal
            document.getElementById('previewContent').innerHTML = getPreviewContent();

            // Clear any existing interval
            if (clockInterval) {
                clearInterval(clockInterval);
            }

            // Start clock updates
            updateClock(); // Initial update
            clockInterval = setInterval(updateClock, 1000);
            
            // Clear interval when modal is closed by clicking outside
            window.onclick = function(event) {
                if (event.target == modal) {
                    closePreviewModal();
                }
            }
        }

        function updatePreviewContent() {
            const selectedTvs = Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => parseInt(checkbox.value, 10));
            const selectedType = document.querySelector('[name="type"]').value;
            const previewContainer = document.getElementById('previewContainer');

            let content = '';

            // Clear previous content
            previewContainer.innerHTML = '';

            // Find the matching container for each selected TV
            const matchingContainers = containers.filter(container => 
                selectedTvs.includes(parseInt(container.tv_id, 10)) && container.type === selectedType
            );

            if (matchingContainers.length > 0) {
                // Create carousel structure
                let carouselHTML = '<div class="carousel">';
                matchingContainers.forEach((matchingContainer, index) => {
                    const tvName = tvNames[matchingContainer.tv_id] || 'Unknown TV';
                    const isSelectedType = matchingContainer.type === selectedType;
                    const isVisible = matchingContainer.visible === '1';
                    const topbarPositions = <?php echo json_encode($topbarPositions); ?>;

                    carouselHTML += `
                        <div class="carousel-item ${index === 0 ? 'active' : ''}" style="display: none;" data-tv-id="${matchingContainer.tv_id}">
                            <div class="tv-frame-parent" style="width: auto; height: 100%; background: none; cursor: default;">
                                <div class="tv-frame" id="tvFrame-${matchingContainer.tv_id}" style="position: relative; display: flex; flex-direction: column; align-items: center; overflow: clip;">
                                    ${topbarPositions[matchingContainer.tv_id] === 'top' ? `
                                        <div class="topbar" id="topbar" style="background: ${matchingContainer.topbar_hex_color}">
                                            <img src="${matchingContainer.department_logo_path ? 
                                            `servers/tv_department_logos/${matchingContainer.tv_id}/${matchingContainer.department_logo_path}` : 
                                            'images/soe_icon.png'}" 
                                            alt="" style="width: 5vh; height: 5vh; margin-left: 5px">
                                            <div class="tv-id" style="color: ${matchingContainer.topbar_tvid_font_color}; font-style: ${matchingContainer.topbar_tvid_font_style}; font-family: ${matchingContainer.topbar_tvid_font_family}; font-size: 2.5vh; padding-left: 10px;">
                                                <p id="tvDepartment_${matchingContainer.tv_id}">${matchingContainer.tv_department}</p>
                                                <p id="tv-id"><i class="fa fa-television" aria-hidden="true"></i>: ${matchingContainer.tv_id}</p>
                                            </div>
                                            <h1 class="tv-name" style="color: ${matchingContainer.topbar_tvname_font_color}; font-style: ${matchingContainer.topbar_tvname_font_style}; font-family: ${matchingContainer.topbar_tvname_font_family};">
                                                ${matchingContainer.tv_name}
                                            </h1>
                                            <div class="date-time">
                                                <span class="live-clock time" style="font-weight: bold; color: ${matchingContainer.topbar_time_font_color}; font-style: ${matchingContainer.topbar_time_font_style}; font-family: ${matchingContainer.topbar_time_font_family};"></span>
                                                <span class="live-date date" style="color: ${matchingContainer.topbar_date_font_color}; font-style: ${matchingContainer.topbar_date_font_style}; font-family: ${matchingContainer.topbar_date_font_family};"></span>
                                            </div>
                                        </div>
                                    ` : ''}
                                    <div class='tv-screen' style='
                                        height: ${matchingContainer.tv_height_px}px; 
                                        width: ${matchingContainer.tv_width_px}px; 
                                        border: none;
                                        border-radius: 0;
                                        ${matchingContainer.isImageBackground === '1' ? 
                                            (matchingContainer.background_image_path ? 
                                                `background-image: url(servers/tv_backgrounds/${matchingContainer.tv_id}/${matchingContainer.background_image_path}); 
                                                background-size: cover; 
                                                background-position: center;` : 
                                                'background-color: #000;'
                                            ) :
                                            (matchingContainer.isStaticBackground === '1' ? 
                                                `background-color: ${matchingContainer.background_hex_color};` : 
                                                'background-color: #000;'
                                            )
                                        }'>
                                        ${matchingContainer.visible === '1' ? 
                                            `<div class="carousel-content" 
                                                style="position: relative;
                                                        transform: translate(${matchingContainer.xaxis}px, ${matchingContainer.yaxis}px);
                                                        width: ${matchingContainer.width_px}px;
                                                        height: ${matchingContainer.height_px}px;
                                                        background-color: ${matchingContainer.parent_background_color};
                                                        padding: 5px;
                                                        border-radius: 5px;">
                                                <h1 style="color: ${matchingContainer.parent_font_color}; 
                                                        font-family: ${matchingContainer.parent_font_family}; 
                                                        font-style: ${matchingContainer.parent_font_style}; 
                                                        margin-bottom: 5px;
                                                        text-overflow: ellipsis;
                                                        overflow: hidden;
                                                        white-space: nowrap;
                                                        width: auto;
                                                        max-width: 100%;
                                                        font-size: 2.5vh">
                                                    ${matchingContainer.container_name}
                                                </h1>
                                                <div id="carouselContent-${matchingContainer.tv_id}" style="background-color: ${matchingContainer.child_background_color}; 
                                                        color: ${matchingContainer.child_font_color}; 
                                                        font-style: ${matchingContainer.child_font_style}; 
                                                        font-family: ${matchingContainer.child_font_family}; 
                                                        width: auto; 
                                                        height: calc(100% - 9.5vh); 
                                                        display: flex;
                                                        flex-direction: column;
                                                        padding: 5px; 
                                                        border-radius: 5px">
                                                    <div class="carousel-item-2 active" style="width: auto; margin-bottom: 5px; height: 100%; border-radius: 5px;">
                                                        <div style="border: none; border-top-left-radius: 5px; border-top-right-radius: 5px; text-align: justify; height: 100%; bottom: 0;">
                                                            <div class="content-container-con" style="height: 100%;">
                                                                <div id="previewOrgChartContainerParent-${matchingContainer.tv_id}" class="orgchart-container">
                                                                    <div id="previewOrgChartContainer-${matchingContainer.tv_id}"  class="orgchart-container-parent">
                                                                        <div class="orgchart-content"></div>
                                                                    </div>
                                                                </div>
                                                                <div class="content-details">
                                                                    <div style="display: flex; flex-direction: row; margin: 0">
                                                                        <div>
                                                                            <p class="author" style="margin: 0; font-size: 1.9vh;"><small>Posted on ${new Date().toLocaleString()}</small></p>
                                                                        </div>
                                                                        <div style="margin-left: auto; margin-top: auto">
                                                                            <p class="display-time" style="text-align: right; margin: 0; font-size: 1.9vh;">
                                                                                <i class="fa fa-hourglass-half" aria-hidden="true"></i> 
                                                                                <span class="time-left">${document.querySelector('[name="display_time"]').value}s</span>
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>` :
                                            `<div class="carousel-content" 
                                                style="position: relative;
                                                        transform: translate(5px, 5px);
                                                        width: 400px;
                                                        height: 400px;
                                                        background-color: ${matchingContainer.parent_background_color};
                                                        padding: 5px;
                                                        border-radius: 5px;
                                                        display: flex;
                                                        justify-content: center;
                                                        align-items: center;
                                                        flex-direction: column;">
                                                    <h1 style="font-size: 35px; color: ${matchingContainer.parent_font_color}"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></h1> 
                                                    <h1 style="color: ${matchingContainer.parent_font_color}; 
                                                        font-family: ${matchingContainer.parent_font_family}; 
                                                        font-style: ${matchingContainer.parent_font_style}; 
                                                        margin-bottom: 5px;
                                                        text-align: center;
                                                        align-content: center;">
                                                    ${selectedType.charAt(0).toUpperCase() + selectedType.slice(1)} Container not visible in the TV
                                                </h1>
                                            </div>`
                                        }
                                    </div>
                                    ${topbarPositions[matchingContainer.tv_id] === 'bottom' ? `
                                        <div class="topbar" id="topbar" style="background: ${matchingContainer.topbar_hex_color}">
                                            <img src="${matchingContainer.department_logo_path ? 
                                            `servers/tv_department_logos/${matchingContainer.tv_id}/${matchingContainer.department_logo_path}` : 
                                            'images/soe_icon.png'}" 
                                            alt="" style="width: 5vh; height: 5vh; margin-left: 5px">
                                            <div class="tv-id" style="color: ${matchingContainer.topbar_tvid_font_color}; font-style: ${matchingContainer.topbar_tvid_font_style}; font-family: ${matchingContainer.topbar_tvid_font_family}; font-size: 2.5vh; padding-left: 10px;">
                                                <p id="tvDepartment_${matchingContainer.tv_id}">${matchingContainer.tv_department}</p>
                                                <p id="tv-id"><i class="fa fa-television" aria-hidden="true"></i>: ${matchingContainer.tv_id}</p>
                                            </div>
                                            <h1 class="tv-name" style="color: ${matchingContainer.topbar_tvname_font_color}; font-style: ${matchingContainer.topbar_tvname_font_style}; font-family: ${matchingContainer.topbar_tvname_font_family};">
                                                ${matchingContainer.tv_name}
                                            </h1>
                                            <div class="date-time">
                                                <span class="live-clock time" style="font-weight: bold; color: ${matchingContainer.topbar_time_font_color}; font-style: ${matchingContainer.topbar_time_font_style}; font-family: ${matchingContainer.topbar_time_font_family};"></span>
                                                <span class="live-date date" style="color: ${matchingContainer.topbar_date_font_color}; font-style: ${matchingContainer.topbar_date_font_style}; font-family: ${matchingContainer.topbar_date_font_family};"></span>
                                            </div>
                                        </div>
                                    ` : ''}
                                    <p id="tvBrand2UserHome_${matchingContainer.tv_id}" style="text-align: center; font-size: 25px; margin-top: auto; color: white; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; width: auto; max-width: 250px;">${matchingContainer.tv_brand}</p>
                                    <div class="tv-stand"></div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                carouselHTML += '</div>';

                // Left and Right Navigation buttons
                if (matchingContainers.length > 1) {
                    const initialTvName = tvNames[matchingContainers[0].tv_id] || 'Unknown TV';
                    carouselHTML += `
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <button type="button" class="carousel-control prev" onclick="moveCarousel(-1)"><i class="fa fa-angle-left" aria-hidden="true"></i> Previous</button>
                            <p id="carouselTvName" style="text-overflow: ellipsis; overflow: hidden; white-space: nowrap; width: auto; max-width: 200px;">${initialTvName}</p>
                            <button type="button" class="carousel-control next" onclick="moveCarousel(1)">Next <i class="fa fa-angle-right" aria-hidden="true"></i></button>
                        </div>
                    `;
                }

                previewContainer.innerHTML = carouselHTML;

                // Show the first item
                const items = document.querySelectorAll('.carousel-item');
                items[0].style.display = 'block';

                // Initialize the current index
                let currentIndex = 0;

                // Function to update the TV name when navigating
                function updateTvName() {
                    const activeItem = items[currentIndex];
                    const activeTvId = activeItem.getAttribute('data-tv-id');
                    const activeTvName = tvNames[activeTvId] || 'Unknown TV';
                    document.getElementById('carouselTvName').textContent = activeTvName;
                }

                // Function to move carousel
                window.moveCarousel = function(direction) {
                    // Hide current item
                    items[currentIndex].style.display = 'none';
                    items[currentIndex].classList.remove('active');

                    // Update index
                    currentIndex += direction;

                    // Loop around if at the ends
                    if (currentIndex < 0) {
                        currentIndex = items.length - 1;
                    } else if (currentIndex >= items.length) {
                        currentIndex = 0;
                    }

                    // Reset transform on all frames before showing new one
                    items.forEach(item => {
                        const tvId = item.getAttribute('data-tv-id');
                        const frame = document.getElementById(`tvFrame-${tvId}`);
                        if (frame) {
                            frame.style.transform = 'none';
                        }
                    });

                    // Show new item
                    items[currentIndex].style.display = 'block';
                    items[currentIndex].classList.add('active');

                    // Small delay to ensure DOM updates before fitting
                    setTimeout(() => {
                        updateTvName();
                        fitFrameToParent();
                    }, 0);
                };


                const carouselContent = document.querySelector('.carousel-content');

                // Modified fit frame function to maintain aspect ratio
                function fitFrameToParent() {
                    const activeItem = document.querySelector('.carousel-item.active');
                    if (!activeItem) return;
                    
                    const tvId = activeItem.getAttribute('data-tv-id');
                    const tvFrame = document.getElementById(`tvFrame-${tvId}`);
                    if (!tvFrame) return;

                    const parent = tvFrame.parentElement;
                    const parentRect = parent.getBoundingClientRect();
                    
                    // Reset any existing transformations
                    tvFrame.style.transform = 'none';
                    
                    // Force a reflow
                    void tvFrame.offsetWidth;
                    
                    const frameRect = tvFrame.getBoundingClientRect();
                    
                    // Calculate scale while leaving room for the TV stand
                    const scaleX = (parentRect.width) / frameRect.width;
                    const scaleY = (parentRect.height - 50) / frameRect.height; // Subtract some pixels for TV stand
                    const scale = Math.min(scaleX, scaleY, 0.5);

                    // Apply transformations with adjusted position
                    tvFrame.style.transform = `scale(${scale})`;
                    tvFrame.style.transformOrigin = 'center top';
                    tvFrame.style.marginBottom = '50px'; // margin to ensure stand visibility
                }

                // Fit frame on load
                fitFrameToParent();

                // Refit on window resize
                // window.addEventListener('resize', fitFrameToParent);
            } else {
                previewContainer.innerHTML = '<p>No container found for the selected TVs.</p>'; // Fallback message
            }

            // After creating the preview structure, create the org charts
            matchingContainers.forEach((matchingContainer, index) => {
                createPreviewOrgChart(orgChartData, `previewOrgChartContainer-${matchingContainer.tv_id}`, matchingContainer.tv_id);
            });
        }

        // event listener to tv_id select element
        document.addEventListener("DOMContentLoaded", function () {
            const tvCheckboxes = document.querySelectorAll('[name="tv_id[]"]');
            tvCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updatePreviewContent);
            });
        });

        function createPreviewOrgChart(data, containerId, tvId) {
            const container = document.getElementById(containerId);
            if (!container) {
                console.error(`Container not found for orgchart ${containerId}`);
                return;
            }

            // Clear any existing chart
            const contentDiv = container.querySelector('.orgchart-content');
            if (!contentDiv) {
                console.warn("contentDiv not found");
                return;
            }
            contentDiv.innerHTML = '';

            // Create nodes map first
            const nodes = {};
            data.forEach(member => {
                nodes[member.id] = {
                    text: {
                        name: member.name,
                        title: member.title,
                    },
                    image: member.picture,
                    innerHTML: `
                        <div class="node-content">
                            ${member.picture ? 
                                `<img src="${member.picture}" class="node-image" alt="${member.name}">` : 
                                `<img src="images/profile_picture.png" class="node-image" alt="Default Profile">`
                            }
                            <div class="node-details">
                                <div class="node-name">${member.name}</div>
                                <div class="node-title">${member.title}</div>
                            </div>
                        </div>
                    `,
                    HTMLclass: "custom-node",
                    children: []
                };
            });

            // Build hierarchy
            let rootNode = null;
            data.forEach(member => {
                if (member.parent_id) {
                    if (nodes[member.parent_id]) {
                        nodes[member.parent_id].children.push(nodes[member.id]);
                    }
                } else {
                    rootNode = nodes[member.id];
                }
            });

            if (rootNode) {
                const chartConfig = {
                    chart: {
                        container: `#${containerId}`,
                        nodeAlign: "BOTTOM",
                        levelSeparation: 10,
                        siblingSeparation: 20,
                        subTeeSeparation: 20,
                        connectors: {
                            type: 'step',
                            style: {
                                'stroke-width': '2px'
                            }
                        },
                        node: {
                            HTMLclass: 'nodeExample1'
                        }
                    },
                    nodeStructure: rootNode
                };

                try {
                    new Treant(chartConfig, () => {
                        const treant = document.querySelector(`#${containerId}`).closest('.Treant');
                        const svg = treant ? treant.querySelector('svg') : null;

                        if (!treant || !svg) {
                            console.error('Treant or SVG not found');
                            return;
                        }

                        // First, make the Treant match the SVG dimensions
                        const svgWidth = svg.width.baseVal.value;
                        const svgHeight = svg.height.baseVal.value;
                        
                        // Set both width and height of Treant to match SVG
                        treant.style.width = `${svgWidth}px`;
                        treant.style.height = `${svgHeight}px`;

                        // Reset transform to get true dimensions
                        treant.style.transform = 'none';
                        
                        // Calculate scale ratios
                        const containerWidth = container.offsetWidth;
                        const containerHeight = container.offsetHeight;
                        const scale = Math.min(
                            containerWidth / svgWidth,
                            containerHeight / svgHeight,
                            1
                        ) * 0.9; // 0.9 for padding

                        // Apply the transform using zoom instead of scale
                        treant.style.zoom = `${scale}`;
                        treant.style.transformOrigin = 'center center';
                    });
                } catch (error) {
                    console.error('Error creating preview org chart:', error);
                    contentDiv.innerHTML = '<p style="text-align: center; color: <?php echo $theme_color; ?>; font-size: 1.2em; padding: 20px;">Error loading organizational chart.</p>';
                }
            }
        }

        function fetchOrgChartData() {
            return $.ajax({
                url: 'database/fetch_orgchart.php',
                dataType: 'json'
            }).done(function(data) {
                console.log("Fetched data:", data);  // Log data to check its structure
            });
        }

        function scaleOrgChart() {
            const container = document.querySelector('.chart-parent-container');
            const treant = document.querySelector('.Treant');
            const svg = treant ? treant.querySelector('svg') : null;
            
            if (!container || !treant || !svg) return;

            // First, make the Treant match the SVG dimensions
            const svgWidth = svg.width.baseVal.value;
            const svgHeight = svg.height.baseVal.value;
            
            // Set both width and height of Treant to match SVG
            treant.style.width = `${svgWidth}px`;
            treant.style.height = `${svgHeight}px`;

            // Reset transform to get true dimensions
            treant.style.transform = 'none';
            
            // Calculate scale ratios
            const containerWidth = container.offsetWidth;
            const containerHeight = container.offsetHeight;
            const scale = Math.min(
                containerWidth / svgWidth,
                containerHeight / svgHeight,
                1
            ) * 0.9;

            // Apply the transform
            treant.style.transform = `scale(${scale})`;
            treant.style.transformOrigin = 'center center';
        }

        function createOrgChart(data) {
            const chartContainer = document.getElementById('chart-container');
            chartContainer.innerHTML = ''; // Clear existing content

            // Create a map of all nodes by their ID
            const nodes = {};
            data.forEach(member => {
                const hasChildren = data.some(child => child.parent_id == member.id);
                nodes[member.id] = {
                    text: {
                        name: member.name,
                        title: member.title,
                    }, 
                    image: member.picture,
                    innerHTML: `
                        <div class="node-content">
                            ${member.picture ? 
                                `<img src="${member.picture}" class="node-image" alt="${member.name}">` : 
                                `<img src="images/profile_picture.png" class="node-image" alt="Default Profile">`
                            }
                            <div class="node-details">
                                <div class="node-name">${member.name}</div>
                                <div class="node-title">${member.title}</div>
                            </div>
                            ${!hasChildren ? 
                                `<button onclick="deleteMember(${member.id})" class="delete-member-btn">
                                    <i class="fa fa-times-circle" style="font-size: 18px"></i>
                                </button>` : 
                                ''
                            }
                        </div>
                        <div class="node-actions">
                            <button type="button" onclick="openAddMemberModal(${member.id})" class="add-member-node-btn">
                                <i class="fa fa-user-plus"></i>
                                Add Member
                            </button>
                        </div>
                    `,
                    HTMLclass: "custom-node",
                    children: []
                };
            });

            // Build hierarchy by linking nodes
            let rootNode = null;
            data.forEach(member => {
                if (member.parent_id) {
                    if (nodes[member.parent_id]) {
                        nodes[member.parent_id].children.push(nodes[member.id]);
                    }
                } else {
                    rootNode = nodes[member.id];  // This should be the root node
                }
            });

            // Check if rootNode is defined
            if (rootNode) {
                // Initialize Treant with the root node
                new Treant({
                    chart: {
                        container: "#chart-container",
                        nodeAlign: "BOTTOM",
                        levelSeparation: 50,
                        siblingSeparation: 40,
                        subTeeSeparation: 40,
                        callback: {
                            onTreeLoaded: function() {
                                // a small delay to ensure the tree is fully rendered
                                setTimeout(scaleOrgChart, 100);
                            }
                        }
                    },
                    nodeStructure: rootNode
                });
            } else {
                chartContainer.innerHTML = '<p style="text-align: center; color: <?php echo $theme_color; ?>; font-size: 1.2em; padding: 20px;">No members in the organizational chart. Please <a href="#" onclick="openAddMemberModal()">add</a> at least one member.</p>';
            }
        }

        function deleteMember(memberId) {
            // Check if the member has children
            const hasChildren = orgChartData.some(member => 
                member.parent_id == memberId
            );
            if (hasChildren) {
                alert('Cannot delete a parent node unless all its child nodes are deleted first.');
                return;
            } 

            // Remove the member from orgChartData
            orgChartData = orgChartData.filter(member => member.id !== memberId);
            createOrgChart(orgChartData); // Re-render the org chart
        }

        $(document).ready(function() {
            createOrgChart(orgChartData); // Initialize with an empty org chart
        });

        function submitMemberForm() {
            const memberName = document.getElementById('memberName').value;
            const memberTitle = document.getElementById('memberTitle').value;
            const parentNodeHiddenInput = document.getElementById('parentNodeHiddenInput').value;
            const memberPicture = document.getElementById('memberPicture').files[0];

            if (!memberName || !memberTitle) {
                alert('Name and Title are required.');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(event) {
                const pictureBase64 = event.target.result;

                const newMember = {
                    id: orgChartData.length + 1, // Generate a temporary ID
                    parent_id: parentNodeHiddenInput || null, // Set parent_id to null if no parent selected
                    name: memberName,
                    title: memberTitle,
                    picture: pictureBase64
                };

                orgChartData.push(newMember); // the new member to the org chart data
                createOrgChart(orgChartData); // Re-render the org chart
                closeAddMemberModal(); // Close the modal after adding the member
            };

            if (memberPicture) {
                reader.readAsDataURL(memberPicture);
            } else {
                const newMember = {
                    id: orgChartData.length + 1,
                    parent_id: parentNodeHiddenInput || null,
                    name: memberName,
                    title: memberTitle,
                    picture: ''
                };

                orgChartData.push(newMember);
                createOrgChart(orgChartData);
                closeAddMemberModal();
            }

            console.log("Org Chart Data:", orgChartData);
            
            const addMemberForm = document.getElementById('addMemberForm');
            // addMemberForm.reset();

        }

        // Function to update the clock
        function updateClock() {
            const clockElements = document.getElementsByClassName('live-clock');
            const dateElements = document.getElementsByClassName('live-date');
            
            if (clockElements.length > 0 && dateElements.length > 0) {
                const now = new Date();
                const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
                const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sept", "Oct", "Nov", "Dec"];
                
                let hours = now.getHours();
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12 || 12; // Convert to 12-hour format
                
                const dayOfWeek = daysOfWeek[now.getDay()];
                const month = months[now.getMonth()];
                const day = now.getDate().toString().padStart(2, '0');
                const year = now.getFullYear();

                const timeString = `${hours}:${minutes} ${ampm}`;
                const dateString = `${dayOfWeek}, ${month} ${day}, ${year}`;

                // Update all clock elements
                Array.from(clockElements).forEach(element => {
                    element.textContent = timeString;
                });

                // Update all date elements
                Array.from(dateElements).forEach(element => {
                    element.textContent = dateString;
                });
            }
        }
    </script>
</body>
</html>