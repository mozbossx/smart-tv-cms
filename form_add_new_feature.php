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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <title>Add New Feature</title>
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
                                <li class="breadcrumb-item active" aria-current="page">Add New Feature Form</li>
                            </ol>
                        </nav>
                        <form id="newFeatureForm" enctype="multipart/form-data" class="main-form">
                            <?php include('error_message.php'); ?>
                            <h1 style="text-align: center">Add New Feature Form</h1>
                            <div class="floating-label-container">
                                <input type="text" name="name_of_feature" id="name_of_feature" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input">
                                <label for="name_of_feature" class="floating-label">Name of Feature</label>
                            </div>
                            <div class="form-row" style="margin-bottom: 5px">
                                <div class="floating-label-container" style="flex: 1">
                                    <select id="number_of_inputs" name="number_of_inputs" class="floating-label-input" required style="background: #FFFF; padding-left: 8px">
                                        <option value="">Select Number of Inputs</option>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                    </select>
                                    <label for="number_of_inputs" class="floating-label">Number of Inputs</label>
                                </div>
                                <div class="floating-label-container" style="flex: 1">
                                    <button type="button" id="featureIconsModalButton" class="floating-label-input" style="background: #FFFF">
                                        Select Feature Icon
                                    </button>
                                    <label for="featureIconsModalButton" class="floating-label">Feature Icon</label>
                                    <input type="hidden" id="selectedIcon" name="selectedIcon" style="display: none">
                                </div>
                            </div>
                            <div id="newInput" style="display: none">
                                <!-- Inputs will be added here -->
                            </div>
                            <div id="userTypeAccess" style="display: none; background: #FFFFFF; border-radius: 5px; padding: 12px; box-shadow: rgba(0, 0, 0, 0.02) 0px 1px 3px 0px, rgba(27, 31, 35, 0.15) 0px 0px 0px 1px">
                                <p style="color: #264B2B; border-top-left-radius: 5px; border-top-right-radius: 5px;"><b>Users who can access this feature</b></p>
                                <br>
                                <?php
                                    // fetch user type data
                                    $sqlUserType = "SELECT * FROM user_types_tb WHERE user_type_id != 1 AND user_type_id != 2";
                                    $resultUserType = mysqli_query($conn, $sqlUserType);

                                    if (mysqli_num_rows($resultUserType) > 0) {
                                        // Loop through each user type
                                        while ($rowUserType = mysqli_fetch_assoc($resultUserType)) {
                                            // Use echo to output the HTML and PHP variables
                                            echo '
                                            <div id="accessFields" style="user-select: none">
                                                <label style="display: block; margin-bottom: 7px; padding: 10px; background: #f3f3f3; border-radius: 5px">
                                                    <input type="checkbox" name="user_type[]" class="userCheckbox" value="' . $rowUserType['user_type'] . '">
                                                    ' . $rowUserType['user_type'] . ' 
                                                </label>
                                            </div>';
                                        }
                                        echo '
                                        <div id="accessFields" style="user-select: none">
                                            <label style="display: block; margin-bottom: 7px; padding: 10px; background: #f3f3f3; border-radius: 5px">
                                                <input type="checkbox" id="checkAll"> All Users
                                            </label>
                                        </div>';
                                    } else {
                                        echo "No user types found.";
                                    }
                                ?>
                            </div>
                            <div class="form-row" style="margin-bottom: 5px"> 
                                <div id="contentExpirationDate" class="floating-label-container" style="display: none; flex: 1">
                                    <select id="content_has_expiration_date" name="content_has_expiration_date" class="floating-label-input" required style="background: #FFFF; padding-left: 8px">
                                        <option value="">Choose Selection</option>
                                        <option value="yes">Yes</option>
                                        <option value="no">No</option>
                                    </select>
                                    <label for="content_has_expiration_date" class="floating-label">Content Has Expiration Date?</label>
                                </div>
                            </div>
                            <div id="departmentSelection" class="floating-label-container" style="display: none">
                                <select name="department" class="floating-label-input" style="background: #FFFF; padding-left: 8px">
                                    <option value="">~</option>
                                    <option value="COMPUTER ENGINEERING">Department of Computer Engineering</option>
                                    <option value="CHEMICAL ENGINEERING">Department of Chemical Engineering</option>
                                    <option value="CIVIL ENGINEERING">Department of Civil Engineering</option>
                                    <option value="INDUSTRIAL ENGINEERING">Department of Industrial Engineering</option>
                                    <option value="ELECTRICAL ENGINEERING">Department of Electrical Engineering</option>
                                    <option value="MECHANICAL ENGINEERING">Department of Mechanical Engineering</option>
                                    <option value="ELECTRONICS ENGINEERING">Department of Electronics Engineering</option>
                                </select>
                                <label for="department" class="floating-label">Department</label>
                            </div>
                            <div style="display: flex; flex-direction: row; margin-left: auto; margin-top: 10px">
                                <div>
                                    <button type="button" name="preview" id="previewButton" class="preview-button" style="margin-right: 0" onclick="validateAndOpenNewFeaturePreviewModal()">
                                        <i class="fa fa-eye" style="padding-right: 5px"></i> Preview 
                                    </button>
                                </div>
                            </div>
                            <div id="featureIconsModal" class="modal">
                                <div class="modal-content" style="padding: 10px">
                                    <h1 style="color: #264B2B; font-size: 50px"><i class="fa fa-info-circle" aria-hidden="true"></i></h1>
                                    <p>Select Feature Icon</p>
                                    <br>
                                    <div id="featureIconsGridList" class="icon-grid" style="height: 150px; max-height: 200px; overflow: auto; text-align: left; padding-right: 10px">
                                        <!-- Grid List with Font Awesome Icons -->
                                    </div>
                                    <br>
                                    <div style="display: flex; float: right">
                                        <button type="button" id="cancelFeatureIconsModal" class="green-button" style="background: none; border: 1px solid #264B2B; color: #264B2B">Cancel</button>
                                    </div>
                                </div>
                            </div>
                            <?php include('misc/php/newfeatureform_preview_modal.php') ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('misc/php/error_modal.php') ?>
    <?php include('misc/php/success_modal.php') ?>
    <script src="misc/js/populate_font_awesome_icons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <script>
        document.getElementById('number_of_inputs').addEventListener('change', function() {
            const numberOfInputs = parseInt(this.value);
            const newInputContainer = document.getElementById('newInput');
            const userTypeAccess = document.getElementById('userTypeAccess');
            const contentExpirationDate = document.getElementById('contentExpirationDate');
            const departmentSelection = document.getElementById('departmentSelection');
            
            // Clear existing inputs
            newInputContainer.innerHTML = '';
            userTypeAccess.style.display = 'none';
            contentExpirationDate.style.display = 'none';
            departmentSelection.style.display = 'none';
            // Create and append new input sections
            for (let i = 0; i < numberOfInputs; i++) {
                const inputSection = `
                    <p style="color: #264B2B;"><b>Input #${i+1}</b></p>
                    <div class="floating-label-container">
                        <input type="text" name="name_of_input_${i+1}" id="name_of_input_${i+1}" required placeholder=" " style="background: #FFFF; width: 100%" class="floating-label-input">
                        <label for="name_of_input_${i+1}" class="floating-label">Name of Input #${i+1}</label>
                    </div>
                    <div class="form-row" style="margin-bottom: 5px">
                        <div class="floating-label-container" style="flex: 1">
                            <select id="input_type_${i+1}" name="input_type_${i+1}" class="floating-label-input" required style="background: #FFFF; padding-left: 8px">
                                <option value="">Select Input Type</option>
                                <option value="text">Text</option>
                                <option value="image">Image</option>
                            </select>
                            <label for="input_type_${i+1}" class="floating-label">Input Type</label>
                        </div>
                        <div class="floating-label-container" style="flex: 1">
                            <select id="required_field_${i+1}" name="required_field_${i+1}" class="floating-label-input" required style="background: #FFFF; padding-left: 8px">
                                <option value="">Choose Selection</option>
                                <option value="yes">Yes</option>
                                <option value="no">No</option>
                            </select>
                            <label for="required_field_${i+1}" class="floating-label">Required Field?</label>
                        </div>
                    </div>
                    <div class="line-separator"></div>
                `;
                newInputContainer.insertAdjacentHTML('beforeend', inputSection);
            }
            
            // Show the container if inputs are selected, hide otherwise
            newInputContainer.style.display = numberOfInputs > 0 ? 'block' : 'none';
            userTypeAccess.style.display = numberOfInputs > 0 ? 'block' : 'none';
            contentExpirationDate.style.display = numberOfInputs > 0 ? 'block' : 'none';
            departmentSelection.style.display = numberOfInputs > 0 ? 'block' : 'none';
        });

        // To handle 'Check All' functionality
        document.getElementById('checkAll').addEventListener('change', function() {
            // Get all user checkboxes
            var checkboxes = document.querySelectorAll('.userCheckbox');
            // Set the checked status of all checkboxes to match "All Users" checkbox
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = document.getElementById('checkAll').checked;
            });
        });
        // To handle individual checkbox changes
        document.querySelectorAll('.userCheckbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var allChecked = Array.from(document.querySelectorAll('.userCheckbox')).every(cb => cb.checked);
                document.getElementById('checkAll').checked = allChecked;
            });
        });
    </script>
</body>
</html>