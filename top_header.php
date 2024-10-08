<?php
include("config_connection.php");

// Start the session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Fetch user data for the currently logged-in user
include 'get_session.php';

// Check if the user is logged in
if (!isset($_SESSION['full_name']) || !isset($_SESSION['email'])) {
    header('location: index.php');
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

// Fetch draft counts for each content type
$draftCounts = [
    'announcement' => 0,
    'event' => 0,
    'news' => 0,
    'promaterial' => 0,
];

$contentTypes = ['announcement', 'event', 'news', 'promaterial'];
foreach ($contentTypes as $type) {
    if ($type !== 'news') {
        $query = "SELECT COUNT(*) as count FROM {$type}s_tb WHERE status = 'Draft' AND {$type}_author_id = ?";
    } else {
        $query = "SELECT COUNT(*) as count FROM {$type}_tb WHERE status = 'Draft' AND {$type}_author_id = ?";
    }
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $draftCounts[$type] = $result->fetch_assoc()['count'];  // Fetch the associative array without arguments
}

// Check if there are any drafts
$hasDrafts = array_sum($draftCounts) > 0;

// Fetch notification count for the logged-in user
$stmtNotificationCount = $conn->prepare("SELECT notification_count FROM users_tb WHERE user_id = ?");
$stmtNotificationCount->bind_param("i", $user_id);
$stmtNotificationCount->execute();
$resultNotificationCount = $stmtNotificationCount->get_result();
$notificationCount = $resultNotificationCount->fetch_assoc()['notification_count'];
$stmtNotificationCount->close();

?>

<div class="header">
    <div class="header-bar">
        <div style="padding-left: 20px; padding-top: 10px">
            <img src="images/USC Logo Full.png" class="logo-header" id="logo">
        </div>
        <nav id='menu' style="z-index: 10000">
            <input type='checkbox' id='responsive-menu' onclick='updatemenu()'><label for="responsive-menu"></label>
            <ul>
                <li>
                    <a href="user_home.php?pageid=UserHome?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>"
                    <?php echo ($current_page === 'user_home.php' || $current_page === 'tv_contents.php' || $current_page === 'edit_announcement.php' || $current_page === 'edit_event.php' || $current_page === 'edit_news.php' || $current_page === 'edit_promaterial.php' || $current_page === 'edit_peo.php') ? 'class="active-header-content" style="color:black"' : ''; ?>>Home</a>
                </li>
                <li>
                    <a id="dropdown-arrow" href="create_post.php?pageid=CreatePost?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>"
                        <?php echo ($current_page === 'create_post.php' || $current_page === 'form_announcement.php' || $current_page === 'form_event.php' || $current_page === 'form_news.php' || $current_page === 'form_promotional_material.php' || $current_page === 'form_add_new_feature.php' || $current_page === 'general_info.php' || $current_page === 'form_peo.php' || $current_page === 'form_student_outcomes.php' || $current_page === 'form_department_organizational_chart.php') ? 'class="active-header-content" style="color:black"' : ''; ?>>
                        Create Post
                    </a>
                    <ul class='sub-menus'>
                        <li><a href="form_announcement.php?pageid=AnnouncementForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Announcement</a></li>
                        <li><a href="form_event.php?pageid=EventForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Event</a></li>
                        <li><a href="form_news.php?pageid=NewsForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">News</a></li>
                        <li><a href="form_promotional_material.php?pageid=PromotionalMaterialsForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Promotional Material</a></li>
                        <li><a href="general_info.php?pageid=GeneralInformationForm?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">General Information</a></li>
                    </ul>
                </li>
                <li style="float: right; margin-right: 5px; margin-top: 3px;">
                    <a href="profile.php?pageid=Profile?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>"
                    <?php echo $current_page === 'profile.php' ? 'class="active-header-content" style="color:black;"' : ''; ?>><?php echo $full_name; ?></a>
                </li>
                <li style="float: right; margin-right: 2px; margin-top: 23px; margin-right: 30px;">
                    <button id="notifications-button" class="notifications-button" <?php echo $current_page === 'notifications.php' ? 'class="active-header-content" style="text-shadow: 0px 0px 5px black; border-radius: 20px;"' : ''; ?>" onclick="toggleNotificationsDropdown()"><i class="fa fa-bell"></i></button>
                    <span id="notificationCount" class="notification-count"><?php echo $notificationCount; ?></span>
                    <span class="triangle"></span>
                    <div id="notificationsDropdown" class="dropdown-notifications" style="display: none; position: absolute; right: -10px; top: 30px; background-color: white; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; width: 300px; max-height: 400px; overflow-y: auto; padding: 10px;">
                        <p style="font-weight: bold; font-size: 18px; margin-bottom: 10px;">Notifications</p>
                        <a href="notifications.php?pageid=Notifications?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="padding: 0; position: absolute; right: 12px; top: 9px;"><p style="color: black">View All</p></a>
                        <div id="notificationsContainer" class="notifications-container">
                            <!-- Notifications will be dynamically inserted here -->
                        </div>
                    </div>
                </li>
                <li>
                    <a href="archives.php?pageid=Archives?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>"
                    <?php echo $current_page === 'archives.php' ? 'class="active-header-content" style="color:black"' : ''; ?>>Archives</a>
                </li>
                <?php if ($user_type === 'Admin'|| $user_type === 'Super Admin') { ?>
                <li>
                    <a id="dropdown-arrow" href="admin_options.php?pageid=AdminOptions?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>"
                        <?php echo ($current_page === 'admin_options.php' || $current_page === 'manage_users.php' || $current_page === 'manage_smart_tvs.php' || $current_page === 'manage_templates.php' || $current_page === 'edit_template.php') ? 'class="active-header-content" style="color:black"' : ''; ?>>
                        Admin Options
                    </a>
                    <ul class='sub-menus'>
                        <li><a href="manage_users.php?pageid=ManageUsers?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Manage Users</a></li>
                        <li><a href="manage_smart_tvs.php?pageid=ManageSmartTVs?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Manage Smart TVs</a></li>
                        <li><a href="manage_templates.php?pageid=ManageTemplates?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Manage Templates</a></li>
                    </ul>
                </li>
                <?php } ?>
                <?php if ($hasDrafts) { ?>
                <li>
                    <!-- Display Drafts option if there are existing drafts in each content type (announcement, event, news, etc.) -->
                    <a href="drafts.php?pageid=Profile?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>"
                    <?php echo $current_page === 'drafts.php' ? 'class="active-header-content" style="color:black"' : ''; ?>>Drafts  <span style="background: crimson; padding: 2px; border: 1px solid white; border-radius: 3px; color: white;"><?php echo array_sum($draftCounts); ?></span></a>
                </li>
                <?php } ?>
            </ul>
        </nav>
        <div class="toggle">
            <div class="hamburger">
                <span></span>
            </div>
        </div>
    </div>
</div>
<div id="confirmApproveUserModal" class="modal"></div>
<div id="confirmRejectUserModal" class="modal"></div>
<div id="confirmApproveContentModal" class="modal"></div>
<div id="confirmRejectContentModal" class="modal"></div>
<div id="viewContentModal" class="modal">
    <!-- Modal content will be dynamically inserted here -->
</div>
 <script src="misc/js/capitalize_first_letter.js"></script>
<script>
    const userType = '<?php echo $user_type; ?>';
    const full_name = '<?php echo $full_name; ?>';
    const user_id = '<?php echo $user_id; ?>';
    const department = '<?php echo $department; ?>';

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
</script>
<script src="js/fetch_notifications.js"></script>
