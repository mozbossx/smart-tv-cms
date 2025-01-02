<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

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
                                <li class="breadcrumb-item"><a href="create_post.php?pageid=CreatePost?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="color: #264B2B">Create Post</a></li>
                                <li class="breadcrumb-item"><a href="general_info.php?pageid=GeneralInformationForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="color: #264B2B">General Information</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Department Organizational Chart</li>
                            </ol>
                        </nav>
                        <form id="orgchartForm" enctype="multipart/form-data" class="main-form" style="max-width: 100%">
                            <?php include('error_message.php'); ?>
                            <input type="hidden" name="type" value="orgchart">
                            <h1 style="text-align: center">Department Organizational Chart Form</h1>
                            <div style="position: relative">
                                <div class="chart-parent-container" style="place-items: center">
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
            <h1 style="color: #264B2B; font-size: 50px"><i class="fa fa-user-plus" aria-hidden="true"></i></h1>
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
        <div class="modal-content-preview" style="padding: 15px">
            <div class="flex-preview-content">
                <?php 
                    $sqlContainers = "SELECT * FROM containers_tb";
                    $resultContainers = mysqli_query($conn, $sqlContainers);
                    $containers = [];
                    while ($row = mysqli_fetch_assoc($resultContainers)) {
                        $containers[] = $row; // Store each container in an array
                    }
                ?>
                <div style="display: flex, flex-direction: column; flex: 2; height: auto;">
                    <div id="previewContainer"></div>
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
    <?php include 'misc/php/success_modal.php' ?>
    <?php include 'misc/php/error_modal.php' ?>
    <script>
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
                if (message.success == true) {
                    document.getElementById('successMessage').textContent = "Organizational Chart was successfully processed!";                        
                } else if (message.success == false) {
                    alert('Error saving organizational chart. Please try again.');
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
                previewContent += '<p class="preview-input"><strong>Schedule Post Date & Time: </strong><br>' + (document.querySelector('[name="schedule_date"]').value ? formatDateTime(document.querySelector('[name="schedule_date"]').value, document.querySelector('[name="schedule_time"]').value) : 'Not scheduled') + '</p>';
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
        }

        function updatePreviewContent() {
            const selectedTvs = Array.from(document.querySelectorAll('[name="tv_id[]"]:checked')).map(checkbox => parseInt(checkbox.value, 10));
            const selectedType = document.querySelector('[name="type"]').value;
            const previewContainer = document.getElementById('previewContainer');

            let content = '';

            // Determine the content based on the selected type
            switch (selectedType) {
                case 'announcement':
                    content = announcementBodyQuill.root.innerHTML;
                    break;
                case 'event':
                    content = eventBodyQuill.root.innerHTML;
                    break;
                case 'news':
                    content = newsBodyQuill.root.innerHTML;
                    break;
                default:
                    content = 'Unknown content type';
                    break;
            }

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

                    carouselHTML += `
                        <div class="carousel-item ${index === 0 ? 'active' : ''}" style="display: none;" data-tv-id="${matchingContainer.tv_id}">
                            <div style="background-color: ${matchingContainer.parent_background_color}; padding: 10px; border-radius: 5px; height: ${matchingContainer.height_px}px; width: ${matchingContainer.width_px}px;">
                                <h1 style="color: ${matchingContainer.parent_font_color}; font-family: ${matchingContainer.parent_font_family}; font-style: ${matchingContainer.parent_font_style}; font-size: 2.0vh; margin-bottom: 5px">${matchingContainer.container_name}</h1>
                                <div style="background-color: ${matchingContainer.child_background_color}; color: ${matchingContainer.child_font_color}; font-style: ${matchingContainer.child_font_style}; font-family: ${matchingContainer.child_font_family}; width: auto; height: calc(100% - 6.5vh); font-size: 1.5vh; padding: 10px; border-radius: 5px">
                                    <p style="white-space: pre-wrap">${content}</p>
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
                            <p id="carouselTvName">${initialTvName}</p>
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
                    items[currentIndex].style.display = 'none'; // Hide current item
                    items[currentIndex].classList.remove('active');

                    // Update index
                    currentIndex += direction;

                    // Loop around if at the ends
                    if (currentIndex < 0) {
                        currentIndex = items.length - 1;
                    } else if (currentIndex >= items.length) {
                        currentIndex = 0;
                    }

                    items[currentIndex].style.display = 'block'; // Show new item
                    items[currentIndex].classList.add('active');

                    updateTvName(); // Update the TV name based on the new active item
                };
            } else {
                previewContainer.innerHTML = '<p>No container found for the selected TVs.</p>'; // Fallback message
            }
        }

        // Add event listener to tv_id select element
        document.addEventListener("DOMContentLoaded", function () {
            const tvCheckboxes = document.querySelectorAll('[name="tv_id[]"]');
            tvCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updatePreviewContent);
            });
        });

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
            treant.style.transformOrigin = 'top center';
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
                            <button onclick="openAddMemberModal(${member.id})" class="add-member-node-btn">
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
                                // Add a small delay to ensure the tree is fully rendered
                                setTimeout(scaleOrgChart, 100);
                            }
                        }
                    },
                    nodeStructure: rootNode
                });
            } else {
                chartContainer.innerHTML = '<p style="text-align: center; color: #264B2B; font-size: 1.2em; padding: 20px;">No members in the organizational chart. Please <a href="#" onclick="openAddMemberModal()">add</a> at least one member.</p>';
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

                orgChartData.push(newMember); // Add the new member to the org chart data
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
            addMemberForm.reset();

        }
    </script>
</body>
</html>