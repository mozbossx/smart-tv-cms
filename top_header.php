

<?php
include("config_connection.php");

// Start the session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Fetch user data for the currently logged-in user
include 'get_session.php';
include 'user_theme_color.php';

// Fetch all feature types from the features_tb table
$featuresQuery = "SELECT type FROM features_tb";
$featuresResult = $conn->query($featuresQuery);
$featureTypes = [];
if ($featuresResult && $featuresResult->num_rows > 0) {
    while ($row = $featuresResult->fetch_assoc()) {
        $featureTypes[] = $row['type'];
    }
}

// Check if current page is a feature form
$is_feature_form = false;
foreach ($featureTypes as $feature) {
    if ($current_page === 'form_' . $feature . '.php') {
        $is_feature_form = true;
        break;
    }
}

// Check if the user is logged in
if (!isset($_SESSION['full_name']) || !isset($_SESSION['email'])) {
    header('location: login.php?pageid=Login');
    exit;
}

// Prepare and execute the query using prepared statements to prevent SQL injection
$query = "SELECT full_name, password, department, email, user_type FROM users_tb WHERE full_name = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $full_name);
$stmt->execute();
$result = $stmt->get_result();

// Close the statement
$stmt->close();

// Fetch notification count for the logged-in user
$stmtNotificationCount = $conn->prepare("SELECT notification_count FROM users_tb WHERE user_id = ?");
$stmtNotificationCount->bind_param("i", $user_id);
$stmtNotificationCount->execute();
$resultNotificationCount = $stmtNotificationCount->get_result();
$notificationCount = $resultNotificationCount->fetch_assoc()['notification_count'];
$stmtNotificationCount->close();

$displayTimeQuery = $conn->query("SELECT display_seconds_range_first, display_seconds_range_last, display_interval FROM display_time_tb LIMIT 1");
$displayTimeRange = $displayTimeQuery->fetch_assoc();
$minTime = $displayTimeRange['display_seconds_range_first'];
$maxTime = $displayTimeRange['display_seconds_range_last'];
$displayInterval = $displayTimeRange['display_interval'];
?>

<div class="header">
    <div class="header-bar">
        <div class="logo-header-container">
            <img src="images/USC Logo Full.png" class="logo-header" id="logo" onclick="goToHome()">
            <img src="images/usc_icon.png" class="logo-header-2" id="logo-2" onclick="goToHome()">
        </div>
        <nav id='menu' style="z-index: 10000; box-shadow: 0px 5px 20px 0px rgb(0 0 0 / 18%); background-color: <?php echo $theme_color; ?>;">
            <input type='checkbox' id='responsive-menu' onclick='updatemenu()'><label for="responsive-menu"></label>
            <ul>
                <li>
                    <a href="user_home.php?pageid=UserHome?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>"
                    <?php echo (isset($_GET['action']) && $_GET['action']=='edit') || ($current_page === 'user_home.php' || $current_page === 'tv_contents.php') ? 'class="active-header-content" style="color:black"' : ''; ?>>Home</a>
                </li>
                <li>
                    <a id="dropdown-arrow" href="create_post.php?pageid=CreatePost?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>"
                        <?php echo ((!isset($_GET['action']) && ($current_page === 'create_post.php' || $current_page === 'form_announcement.php' || $current_page === 'form_event.php' || $current_page === 'form_news.php' || $current_page === 'form_promotional_material.php' || $current_page === 'form_add_new_feature.php' || $current_page === 'general_info.php' || $current_page === 'form_peo.php' || $current_page === 'form_student_outcomes.php' || $current_page === 'form_department_organizational_chart.php' || $current_page === 'form_facility.php' || $is_feature_form))) ? 'class="active-header-content" style="color:black"' : ''; ?>>
                        Create Post
                    </a>
                    <ul class='sub-menus'>
                        <li><a href="form_announcement.php?pageid=AnnouncementForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Announcement</a></li>
                        <li><a href="form_event.php?pageid=EventForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Event</a></li>
                        <li><a href="form_news.php?pageid=NewsForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">News</a></li>
                        <li><a href="form_promotional_material.php?pageid=PromotionalMaterialsForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Promotional Material</a></li>
                        <?php 
                            if ($user_type === 'Admin' || $user_type === 'Super Admin') {
                                echo '<li><a href="general_info.php?pageid=GeneralInformationForm?userId=' . $user_id . '\'\'' . $full_name . '">General Information</a></li>';
                                echo '<li><a href="form_add_new_feature.php?pageid=AddNewFeatureForm?userId=' . $user_id . '\'\'' . $full_name . '">Add New Feature</a></li>';
                            }
                        ?>
                    </ul>
                </li>
                <li style="float: right; margin-right: 5px; margin-top: 3px;">
                    <a href="profile.php?pageid=Profile?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>"
                    <?php echo $current_page === 'profile.php' ? 'class="active-header-content" style="color:black;"' : ''; ?>><?php echo $full_name; ?></a>
                </li>
                <li style="float: right; margin-right: 2px; margin-top: 23px; margin-right: 30px;">
                    <button id="notifications-button" class="notifications-button" <?php echo $current_page === 'notifications.php' ? 'class="active-header-content" style="border-radius: 20px;"' : ''; ?>" onclick="toggleNotificationsDropdown(false)"><i class="fa fa-bell"></i></button>
                    <span id="notificationCount" class="notification-count"><?php echo $notificationCount; ?></span>
                    <span id="triangle" class="triangle"></span>
                    <div id="notificationsDropdown" class="dropdown-notifications" style="display: none; position: absolute; right: -10px; top: 30px; background-color: white; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; width: 300px; height: 400px; max-height: 400px; overflow-y: auto; padding: 10px;">
                        <p style="font-weight: bold; font-size: 18px; margin-bottom: 10px;">Notifications <span class="all-notifications-count-2"></span></p>
                        <div id="notificationsContainer" class="notifications-container">
                            <!-- Notifications will be dynamically inserted here -->
                        </div>
                        <button onclick="goToNotifications()" class="green-button" style="width: 100%; margin-top: 10px; margin-right: 0; padding: 10px;"><p style="color: white; text-align: center;">View All</p></button>
                    </div>
                </li>
                <li>
                    <a id="dropdown-arrow" href="manage_contents.php?pageid=ManageContents?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>"
                        <?php echo ($current_page === 'manage_contents.php' || $current_page === 'trash.php' || $current_page === 'archives.php' || $current_page === 'view_approved_contents.php' || $current_page === 'view_rejected_contents.php' || $current_page === 'view_pending_contents.php') ? 'class="active-header-content" style="color:black"' : ''; ?>>
                        Manage Contents
                    </a>
                    <ul class='sub-menus'>
                        <li><a href="trash.php?pageid=Trash?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Trash <span id="trashContentCount" class="trash-content-count" style="color: <?php echo $light_theme_color_20_percent; ?>;"></span></a></li>
                        <li><a href="archives.php?pageid=Archives?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Archives <span id="archivesContentCount" class="archives-content-count" style="color: <?php echo $light_theme_color_20_percent; ?>;"></span></a></li>
                        <li><a href="view_approved_contents.php?pageid=ViewApprovedContents&userId=<?php echo $user_id; ?>&userType=<?php echo $user_type; ?>">Approved <span id="approvedContentCount" class="approved-content-count" style="color: <?php echo $light_theme_color_20_percent; ?>;"></span></a></li>
                        <li><a href="view_rejected_contents.php?pageid=ViewRejectedContents&userId=<?php echo $user_id; ?>&userType=<?php echo $user_type; ?>">Rejected <span id="rejectedContentCount" class="rejected-content-count" style="color: <?php echo $light_theme_color_20_percent; ?>;"></span></a></li>
                        <li><a href="view_pending_contents.php?pageid=ViewPendingContents&userId=<?php echo $user_id; ?>&userType=<?php echo $user_type; ?>">Pending <span id="pendingContentCount" class="pending-content-count" style="color: <?php echo $light_theme_color_20_percent; ?>;"></span></a></li>
                    </ul>
                </li>
                <!-- <li>
                    <a onclick="openSurveyModal()" style="cursor: pointer;">Take Survey</a>
                </li> -->
                <?php if ($user_type === 'Admin') { ?>
                <li>
                    <a id="dropdown-arrow" href="admin_options.php?pageid=AdminOptions?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>"
                        <?php echo ($current_page === 'admin_options.php' || $current_page === 'manage_users.php' || $current_page === 'manage_smart_tvs.php' || $current_page === 'manage_templates.php' || $current_page === 'edit_template.php') ? 'class="active-header-content" style="color:black"' : ''; ?>>
                        Admin Options
                    </a>
                    <ul class='sub-menus'>
                        <li><a href="manage_users.php?pageid=ManageUsers?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Manage Users</a></li>
                        <li><a href="manage_smart_tvs.php?pageid=ManageSmartTVs?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Manage Smart TVs</a></li>
                        <li><a href="manage_templates.php?pageid=ManageTemplates?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Manage Templates</a></li>
                        <li><button class="menu-button" onclick="changeDisplayTimeModal()">Change Display Time</button></li>
                    </ul>
                </li>
                <?php } ?>
                <?php if ($user_type === 'Super Admin') { ?>
                <li>
                    <a id="dropdown-arrow" href="admin_options.php?pageid=AdminOptions?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>"
                        <?php echo ($current_page === 'admin_options.php' || $current_page === 'manage_users.php' || $current_page === 'manage_smart_tvs.php' || $current_page === 'manage_templates.php' || $current_page === 'edit_template.php') ? 'class="active-header-content" style="color:black"' : ''; ?>>
                        Super Admin Options
                    </a>
                    <ul class='sub-menus'>
                        <li><a href="manage_users.php?pageid=ManageUsers?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Manage Users</a></li>
                        <li><a href="manage_smart_tvs.php?pageid=ManageSmartTVs?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Manage Smart TVs</a></li>
                        <li><a href="manage_templates.php?pageid=ManageTemplates?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Manage Templates</a></li>
                        <li><button class="menu-button" onclick="changeDisplayTimeModal()">Change Display Time</button></li>
                    </ul>
                </li>
                <?php } ?>
            </ul>
        </nav>
        <div class="notifications-button-mobile-container">
            <button id="notifications-button-mobile" class="notifications-button-mobile" <?php echo $current_page === 'notifications.php' ? 'class="active-header-content" style="border-radius: 20px;"' : ''; ?>" onclick="toggleNotificationsDropdown(true)"><i class="fa fa-bell" style="font-size: 35px; margin-top: 5px;"></i></button>
            <span id="notificationCountMobile" class="notification-count-mobile"><?php echo $notificationCount; ?></span>
            <span id="triangleMobile" class="triangle-mobile"></span>
            <div id="notificationsDropdownMobile" class="dropdown-notifications-mobile" style="display: none; position: absolute; right: 5px; top: 65px; background-color: white; box-shadow: 0px 0px 16px 0px rgba(0,0,0,0.54); z-index: 1; width: 285px; height: 400px; max-height: 400px; overflow-y: auto; padding: 10px;">
                <p style="font-weight: bold; font-size: 18px; margin-bottom: 10px;">Notifications <span class="all-notifications-count-mobile"></span></p>
                <div id="notificationsContainerMobile" class="notifications-container-mobile">
                    <!-- Notifications will be dynamically inserted here -->
                </div>
                <button onclick="goToNotifications()" class="green-button" style="width: 100%; margin-top: 10px; margin-right: 0; padding: 10px;"><p style="color: white; text-align: center;">View All</p></button>
            </div>
        </div>
        <div class="toggle">
            <div class="hamburger">
                <span></span>
            </div>
        </div>
    </div>
</div>
<div id="confirmApproveUserModal" class="modal"></div>
<div id="confirmRejectUserModal" class="modal"></div>
<div id="confirmApproveContentModal" class="modal" style="z-index: 10000;"></div>
<div id="confirmRejectContentModal" class="modal" style="z-index: 10000;"></div>
<div id="viewContentModal" class="modal"></div>
<div id="confirmDeleteContentModal" class="modal"></div>
<div id="changeDisplayTimeModal" class="modal">
    <div class="modal-content" style="padding: 15px">
        <span class="close" onclick="document.getElementById('changeDisplayTimeModal').style.display='none'"><i class="fa fa-times-circle" aria-hidden="true"></i></span>
        <h1 style="text-align: left; font-size: 25px; padding-bottom: 15px"><i class="fa fa-clock-o"></i> Change Display TIme</h1>
        <form id="changeDisplayTimeForm" style="margin-top: 10px">
            <div style="display: flex; flex-direction: row; justify-content: space-between">
                <div class="floating-label-container">
                    <input type="number" name="display_seconds_range_first" id="display_seconds_range_first" required placeholder=" " class="floating-label-input-text-area">
                    <label for="display_seconds_range_first" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Minimum Time (seconds)</label>
                </div>
                <div style="align-content: center; padding-left: 5px; padding-right: 5px; margin-top: 7px">
                    <i class="fa fa-arrows-h" aria-hidden="true"></i>
                </div>
                <div class="floating-label-container">
                    <input type="number" name="display_seconds_range_last" id="display_seconds_range_last" required placeholder=" " class="floating-label-input-text-area">
                    <label for="display_seconds_range_last" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Maximum Time (seconds)</label>
                </div>
            </div>
            <div class="floating-label-container">
                <input type="number" name="display_interval" id="display_interval" required placeholder=" " class="floating-label-input-text-area">
                <label for="display_interval" style="width: auto; padding-top: 5px; border-radius: 0" class="floating-label-text-area">Interval</label>
            </div>
            <br>
            <div style="text-align: right;">
                <button type="button" onclick="document.getElementById('changeDisplayTimeModal').style.display='none'" class="grey-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0">Cancel</button>
                <button type="submit" id="changeDisplayTimeButton" class="green-button" style="margin-left: 7px; margin-right: 0; margin-bottom: 0">Change Display Time</button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Websocket Connection Modal -->
<div id="loadingWebsocketConnectionModal" class="modal" style="z-index: 10000000000000;">
    <div class="modal-content" style="padding: 15px; text-align: center;">
        <div class="loading-spinner">
            <i class="fa fa-spinner fa-spin" style="font-size: 50px; color: <?php echo $theme_color; ?>"></i>
        </div>
        <p id="loadingWebsocketConnectionMessage"></p>
        <div style="align-items: right; text-align: right; right: 0">
            <button type="button" class="green-button" style="margin-right: 0" onclick="document.getElementById('loadingWebsocketConnectionModal').style.display='none'">Close</button>
        </div>
    </div>
</div>

<!-- Success Websocket Connection Modal -->
<div id="successWebsocketConnectionModal" class="modal" style="z-index: 10000000000000;">
    <div class="modal-content" style="padding: 15px; text-align: center;">
        <div class="green-bar-vertical">
            <h1 style="color: <?php echo $theme_color; ?>; font-size: 50px"><i class="fa fa-check-circle" aria-hidden="true"></i></h1>
            <p id="successWebsocketConnectionMessage"></p>
            <br>
            <div style="align-items: right; text-align: right; right: 0">
                <button type="button" class="green-button" style="margin-right: 0" onclick="document.getElementById('successWebsocketConnectionModal').style.display='none'">Okay</button>
            </div>
        </div>
    </div>
</div>

<!-- Take Survey Modal -->
<!-- <div id="takeSurveyModal" class="modal">
    <div class="modal-content" style="padding: 15px; text-align: center;">
        <div class="green-bar-vertical">
            <h1 style="color: <?php echo $theme_color; ?>; font-size: 50px"><i class="fa fa-wpforms" aria-hidden="true"></i></h1>
            <p id="successWebsocketConnectionMessage">Proceed to take survey?</p>
            <p style="font-size: 13px; color: <?php echo $dark_theme_color_50_percent; ?>">As a part of the evaluation process, please take the survey. This will only take a few minutes.</p>
            <br>
            <div style="text-align: right;">
                <button onclick="document.getElementById('takeSurveyModal').style.display='none'" class="grey-button" style="margin-right: 0">Cancel</button>
                <button onclick="window.open('https://docs.google.com/forms/d/e/1FAIpQLSezD3WTthXbXJYJDM2WHymNIXaQGZYHYWgY7mGieqJPcJG3eQ/viewform', '_blank')" class="green-button" style="margin-right: 0">Take Survey</button>
            </div>
        </div>
    </div>
</div> -->

<script src="misc/js/capitalize_first_letter.js"></script>
<script>
    const userType = '<?php echo $user_type; ?>';
    const full_name = '<?php echo $full_name; ?>';
    const user_id = '<?php echo $user_id; ?>';
    const department = '<?php echo $department; ?>';
    <?php if (isset($_GET['tvId'])) { ?>
        const tvIdFromUrl = '<?php echo $_GET['tvId']; ?>';
    <?php } ?>

    function toggleDropdown() {
        var dropdownContent = document.getElementById("myDropdown");
        dropdownContent.classList.toggle("show");
    }

    // Close the dropdown if the user clicks outside of it
    window.onclick = function(event) {
        if (!event.target.matches('.dropbtn')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }

    function updatemenu() {
        if (document.getElementById('responsive-menu').checked == true) {
            document.getElementById('menu').style.borderBottomRightRadius = '0';
            document.getElementById('menu').style.borderBottomLeftRadius = '0';
        } else{
            document.getElementById('menu').style.borderRadius = '10px';
        }
    }

    function goToHome() {
        window.location.href = 'user_home.php?pageid=UserHome?userId=' + user_id + '' + full_name;
    }

    function goToNotifications() {
        window.location.href = 'notifications.php?pageid=Notifications?userId=' + user_id + '' + full_name;
    }

    // function openSurveyModal() {
    //     const takeSurveyModal = document.getElementById("takeSurveyModal");
    //     takeSurveyModal.style.display = "flex";
    // }

    function changeDisplayTimeModal() {
        const changeDisplayTimeModal = document.getElementById("changeDisplayTimeModal");
        // Pre-fill the existing values
        document.getElementById("display_seconds_range_first").value = <?php echo $minTime; ?>;
        document.getElementById("display_seconds_range_last").value = <?php echo $maxTime; ?>;
        document.getElementById("display_interval").value = <?php echo $displayInterval; ?>;

        changeDisplayTimeModal.style.display = "flex";
    }

    document.getElementById('changeDisplayTimeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const minTime = document.getElementById('display_seconds_range_first').value;
        const maxTime = document.getElementById('display_seconds_range_last').value;
        const displayInterval = document.getElementById('display_interval').value;

        // Validate input
        if (parseInt(minTime) >= parseInt(maxTime)) {
            alert('Minimum time must be less than maximum time');
            return;
        } 
        
        if (parseInt(minTime) <= 0) {
            alert('Minimum time must be more than 0');
            return;
        }

        if (parseInt(maxTime) <= 0) {
            alert('Maximum time must be more than 0');
            return;
        }

        if (parseInt(displayInterval) <= 0) {
            alert('Interval must be more than 0');
            return;
        }

        // Send AJAX request
       fetch('update_display_time.php', {
           method: 'POST',
           headers: {
               'Content-Type': 'application/json',
           },
           body: JSON.stringify({
               min_time: minTime,
               max_time: maxTime,
               display_interval: displayInterval
           })
       })
       .then(response => response.json())
       .then(data => {
           if (data.success) {
               alert('Display time updated successfully');
               document.getElementById('changeDisplayTimeModal').style.display = 'none';
               // Optionally reload the page to reflect changes
               location.reload();
           } else {
               alert('Error updating display time: ' + data.message);
           }
       })
       .catch(error => {
           console.error('Error:', error);
           alert('An error occurred while updating display time');
       });
   });
</script>
<script src="js/fetch_notifications.js"></script>
<script src="js/fetch_user_session.js"></script>
<style>
    #menu ul.sub-menus {
        background: <?php echo $light_theme_color_70_percent; ?>;
        box-shadow: rgba(0, 0, 0, 0.2) 0px 8px 16px 0px;
    }
    #menu ul.sub-menus a {
        color: <?php echo $dark_theme_color_50_percent; ?>;
    }
    #menu ul.sub-menus a:hover {
        background: <?php echo $light_theme_color_90_percent; ?>;
        color: <?php echo $dark_theme_color_50_percent; ?>;
    }
    
    .menu-button {
        background: <?php echo $light_theme_color_70_percent; ?>;
        color: <?php echo $dark_theme_color_50_percent; ?>;
    }
    .menu-button:hover{
        background: <?php echo $light_theme_color_90_percent; ?>;
        color: <?php echo $dark_theme_color_50_percent; ?>;
    }
    .content-container:nth-child(odd), .content-button {
        background: <?php echo $light_theme_color_90_percent; ?>;
    }

    .content-container:nth-child(even), .content-button {
        background: <?php echo $light_theme_color_90_percent; ?>;
    }

    .content-button:hover {
        background: <?php echo $light_theme_color_70_percent; ?>;
    }

    .content-container-main:nth-child(odd) {
        background: <?php echo $light_theme_color_75_percent; ?>;
    }

    .content-container-main:nth-child(even) {
        background: <?php echo $light_theme_color_80_percent; ?>;
    }

    .green-button, .preview-button {
        background: <?php echo $theme_color; ?>;
        border: 1px solid <?php echo $theme_color; ?>;
    }
    .green-button:hover, .preview-button:hover {
        background: <?php echo $dark_theme_color_50_percent; ?>;
        border: 1px solid <?php echo $dark_theme_color_50_percent; ?>;
    }
    .red-button, .yellow-button {
        background: <?php echo $dark_theme_color_50_percent; ?>;
        border: 1px solid <?php echo $dark_theme_color_50_percent; ?>;
    }
    .red-button:hover, .yellow-button:hover {
        background: <?php echo $dark_theme_color_90_percent; ?>;
        border: 1px solid <?php echo $dark_theme_color_90_percent; ?>;
    }
    .light-green-button {
        background: <?php echo $light_theme_color_50_percent; ?>;
        border: 1px solid <?php echo $dark_theme_color_50_percent; ?>;
        color: <?php echo $dark_theme_color_50_percent; ?>;
    }
    .light-green-button:hover {
        background: <?php echo $dark_theme_color_50_percent; ?>;
        border: 1px solid <?php echo $dark_theme_color_50_percent; ?>;
        color: <?php echo $light_theme_color_90_percent; ?>;
    }
    .grey-button {
        background: <?php echo $light_theme_color_80_percent; ?>;
        border: 1px solid <?php echo $light_theme_color_80_percent; ?>;
        color: <?php echo $dark_theme_color_50_percent; ?>;
    }
    .grey-button:hover {
        background: <?php echo $dark_theme_color_50_percent; ?>;
        border: 1px solid <?php echo $dark_theme_color_50_percent; ?>;
        color: <?php echo $light_theme_color_90_percent; ?>;
    }
    *::-webkit-scrollbar-thumb {
        background-color: <?php echo $theme_color; ?>;
    }

    *::-webkit-scrollbar-thumb:hover {
        background-color: <?php echo $dark_theme_color_50_percent; ?>;
    }

    *::-webkit-scrollbar-thumb:active {
        background-color: <?php echo $theme_color; ?>;
    }
    .error-message {
        background: crimson;
        color: white;
        border: 1px solid crimson;
    }
    .success-message {
        background: <?php echo $light_theme_color_80_percent; ?>;
        color: <?php echo $dark_theme_color_50_percent; ?>;
        border: 1px solid <?php echo $light_theme_color_80_percent; ?>;
    }
    .red-bar-vertical::after {
        background: <?php echo $dark_theme_color_50_percent; ?>;
    }
    .green-bar-vertical::after {
        background: <?php echo $theme_color; ?>;
    }
    .content-container:nth-child(odd) {
        background: <?php echo $light_theme_color_90_percent; ?>;
    }

    .content-container:nth-child(even) {
        background: <?php echo $light_theme_color_90_percent; ?>;
    }

    .breadcrumb {
        background-color: <?php echo $light_theme_color_90_percent; ?>;
    }

    .breadcrumb-item a {
        color: <?php echo $theme_color; ?>;
    }

    .breadcrumb-item a:hover {
        color: <?php echo $dark_theme_color_50_percent; ?>;
    }

    .breadcrumb-item.active {
        color: <?php echo $dark_theme_color_50_percent; ?>;
    }
    .floating-label {
        color: <?php echo $theme_color; ?>;
    }
    .floating-label-input:focus + .floating-label,
    .floating-label-input:not(:placeholder-shown) + .floating-label {
        color: <?php echo $theme_color; ?>;
    }

    .floating-label-input-text-area:focus + .floating-label-text-area,
    .floating-label-input-text-area:not(:placeholder-shown) + .floating-label-text-area {
        color: <?php echo $theme_color; ?>;
    }

    .dot {
        background: <?php echo $light_theme_color_80_percent; ?>;
        border: 1px solid <?php echo $light_theme_color_80_percent; ?>;
        color: <?php echo $dark_theme_color_90_percent; ?>;
    }

    .dot:hover {
        background: <?php echo $light_theme_color_70_percent; ?>;
    }

    .dot.active {
        background: <?php echo $theme_color; ?>;
        border: 1px solid <?php echo $theme_color; ?>;
    }
    .close {
        color: <?php echo $theme_color; ?>;
    }
    .close:hover {
        color: <?php echo $dark_theme_color_50_percent; ?>;
    }
    .user-type-pill {
        background: <?php echo $dark_theme_color_50_percent; ?>;
        color: <?php echo $light_theme_color_90_percent; ?>;
    }
    .input-container-label {
        color: <?php echo $theme_color; ?>;
    }
    .notification {
        border-left: 5px solid <?php echo $dark_theme_color_30_percent; ?>;
        background-color: <?php echo $light_theme_color_90_percent; ?>;
    }
    .days-remaining-label {
        background: <?php echo $light_theme_color_90_percent; ?>;
        color: <?php echo $dark_theme_color_50_percent; ?>;
    }
    .content-type-label {
        background: <?php echo $light_theme_color_60_percent; ?>;
        color: <?php echo $dark_theme_color_70_percent; ?>;
        font-weight: bold;
    }
    .modal-content h1 {
        color: <?php echo $dark_theme_color_60_percent; ?>;
    }
    .panel-close-btn button {
        color: <?php echo $theme_color; ?>;
        font-weight: bold;
    }
    .floating-menu button {
        background: <?php echo $light_theme_color_90_percent; ?>;
        color: <?php echo $theme_color; ?>;
        font-weight: bold;
    }
    .floating-menu button:hover {
        background: <?php echo $light_theme_color_70_percent; ?>;
        color: <?php echo $theme_color; ?>;
    }
    .open-sidebar-button.open, #updateTemplateButton {
        background: <?php echo $dark_theme_color_50_percent; ?>;
        color: white;
    }
    .open-sidebar-button.open:hover, #updateTemplateButton:hover {
        background: <?php echo $dark_theme_color_60_percent; ?>;
        color: white;
    }
    .toggle {
        background: <?php echo $theme_color; ?>;
    }
    .sidebar {
        background: <?php echo $dark_theme_color_50_percent; ?>;
    }
    .active-sidebar-content {
        background: <?php echo $dark_theme_color_70_percent; ?>;
    }
    .active-sidebar-content:hover {
        background: <?php echo $dark_theme_color_70_percent; ?>;
    }
    .archives-content-count, .trash-content-count, .approved-content-count, .rejected-content-count, .pending-content-count {
        color: <?php echo $light_theme_color_50_percent; ?>;
    }
    .trash-content-button-count, .archived-content-button-count, .approved-content-button-count, .rejected-content-button-count, .pending-content-button-count {
        color: <?php echo $light_theme_color_50_percent; ?>;
        background: none;
    }
    .navbar a:hover{
        background-color: <?php echo $dark_theme_color_70_percent; ?>;
    }
    .add-member-node-btn {
        background: <?php echo $dark_theme_color_50_percent; ?>;
        color: <?php echo $light_theme_color_90_percent; ?>;
    }
    .add-member-node-btn:hover {
        background: <?php echo $dark_theme_color_70_percent; ?>;
        color: <?php echo $light_theme_color_90_percent; ?>;
    }
    .theme-container {
        background: <?php echo $light_theme_color_90_percent; ?>;
    }
    .color-option.active {
        border: 5px solid <?php echo $dark_theme_color_20_percent; ?>;
    }
    th {
        background-color: <?php echo $light_theme_color_70_percent; ?>;
    }

    tr:nth-child(even) {
        background-color: <?php echo $light_theme_color_95_percent; ?>;
    }

    tr:nth-child(odd) {
        background-color: <?php echo $light_theme_color_90_percent; ?>;
    }

    #usersTable th, #smartTVsTable th {
        background-color: <?php echo $light_theme_color_70_percent; ?>;
    }
    input[type="checkbox"] {
        accent-color: <?php echo $theme_color; ?>;
    }
    .form-title-container {
        background: <?php echo $theme_color; ?>;
    }
    .form-container {
        background: <?php echo $light_theme_color_95_percent; ?>;
        border-radius: 20px;
        padding: 20px;
    }
    .pending-content-message {
        color: <?php echo $theme_color; ?>;
    }
    .loading-spinner {
        border-top: 5px solid <?php echo $theme_color; ?>;
    }
</style>

