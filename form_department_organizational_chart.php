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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                            <div class="form-title-container">
                                <h1 class="form-title"><i class="fa fa-sitemap" style="margin-right: 6px"></i>Department Organizational Chart Form</h1>
                            </div>
                            <div class="form-container">
                                <div style="position: relative">
                                    <div class="chart-parent-container" style="place-items: center; background-color: none">
                                        <div id="chart-container" class="chart-container" style="width: auto;"></div>
                                    </div>
                                </div>
                                <?php include('misc/php/displaytime_tvdisplay.php')?>
                                <div style="display: flex; flex-direction: row; margin-left: auto; margin-top: 10px; justify-content: flex-end">
                                    <div>
                                        <button type="button" name="preview" id="previewButton" class="preview-button" style="margin-right: 0" onclick="validateAndOpenPreviewModal()">
                                            Preview <i class="fa fa-chevron-right" style="padding-left: 5px"></i> 
                                        </button>
                                    </div>
                                </div>
                                <?php include('misc/php/preview_modal_new.php') ?>
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
    <?php include 'misc/php/success_modal.php' ?>
    <?php include 'misc/php/error_modal.php' ?>
    <script>
        const containers = <?php echo json_encode($containers); ?>;
        const tvNames = <?php echo !empty($tv_names) ? json_encode($tv_names) : '{}'; ?>; 
        const contentType = document.querySelector('[name="type"]').value;
        const userDepartment = <?php echo json_encode($department); ?>;

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

        function validateAndOpenPreviewModal() {
            const displayTime = document.querySelector('[name="display_time"]').value;
            const tvDisplays = document.querySelectorAll('[name="tv_id[]"]:checked');
            
            if (displayTime === "" || tvDisplays.length === 0 || orgChartData.length === 0) {
                errorModalMessage("Please add at least one member to the org chart and select at least one TV display.");
                return;
            }

            // Send the preview data to all iframes
            const iframes = document.querySelectorAll('iframe.tv-screen');
            iframes.forEach(iframe => {
                if (iframe.contentWindow) {
                    const previewData = {
                        action: 'updatePreview',
                        type: 'orgchart',
                        content: {
                            members: orgChartData // Wrap the data in a members property
                        },
                        displayTime: displayTime
                    };

                    console.log("Sending preview data:", previewData);
                    
                    // Wait for iframe to load before sending message
                    if (iframe.contentDocument.readyState === 'complete') {
                        iframe.contentWindow.postMessage(previewData, '*');
                    } else {
                        iframe.onload = () => {
                            iframe.contentWindow.postMessage(previewData, '*');
                        };
                    }
                }
            });

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
            
            // Clear interval when modal is closed by clicking outside
            window.onclick = function(event) {
                if (event.target == modal) {
                    closePreviewModal();
                }
            }
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
                            <button type="button" onclick="openAddMemberModal(${member.id})" class="add-member-node-btn">Add Member</button>
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
                        siblingSeparation: 50,
                        subTeeSeparation: 50,
                        connectors: {
                            type: 'step',
                            style: {
                                'stroke-width': '4px'
                            }
                        },
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
                chartContainer.innerHTML = '<p style="text-align: center; color: <?php echo $theme_color; ?>; font-size: 1.2em; padding: 20px;">No members in the organizational chart. Please <a href="#" onclick="openAddMemberModal()" style="font-weight: bolder; font-size: 14px; color: <?php echo $theme_color?>">add</a> at least one member.</p>';
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
            
            const addMemberForm = document.getElementById('addMemberForm');
            addMemberForm.reset();

        }
    </script>
</body>
</html>