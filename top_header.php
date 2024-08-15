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
?>

<div class="header">
    <nav class="header-bar">
        <div style="padding-left: 20px; padding-top: 10px">
            <img src="images/USC Logo Full.png" class="logo-header" id="logo">
        </div>
        <div class="links">
            <a href="user_home.php?pageid=UserHome?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo ($current_page === 'user_home.php' || $current_page === 'edit_announcement.php' || $current_page === 'edit_event.php' || $current_page === 'edit_news.php' || $current_page === 'edit_promaterial.php' || $current_page === 'edit_peo.php') ? 'class="active-header-content" style="color:black"' : ''; ?>>Home</a>
            <a href="create_post.php?pageid=CreatePost?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo ($current_page === 'create_post.php' || $current_page === 'form_announcement.php' || $current_page === 'form_event.php' || $current_page === 'form_form_form_news.php' || $current_page === 'form_promotional_material.php' || $current_page === 'general_info.php' || $current_page === 'form_peo.php' || $current_page === 'form_student_outcomes.php' || $current_page === 'form_department_organizational_chart.php' || $current_page === 'form_facilities.php' || $current_page === 'add_new_feature.php') ? 'class="active-header-content" style="color:black"' : ''; ?>>Create Post</a>
            <a href="notifications.php?pageid=Notifications?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo $current_page === 'notifications.php' ? 'class="active-header-content" style="color:black"' : ''; ?>>Notifications <span style="background: crimson; color: white; padding: 2px; border-radius: 3px; text-align: center; margin-left: 3px; border: 1px white solid;" id="notificationCount"></span></a>
            <a href="archives.php?pageid=Archives?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo $current_page === 'archives.php' ? 'class="active-header-content" style="color:black"' : ''; ?>>Archives</a>
            <?php if ($user_type === 'Admin') { ?>
                <a href="admin_options.php?pageid=AdminOptions?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo $current_page === 'admin_options.php' || $current_page === 'manage_users.php' || $current_page === 'manage_smart_tvs.php' || $current_page === 'manage_templates.php' || $current_page === 'edit_template.php' || $current_page === 'manage_posts.php'? 'class="active-header-content" style="color:black"' : ''; ?>>Admin Options</a>
            <?php } ?>
            <a href="profile.php?pageid=Profile?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo $current_page === 'profile.php' ? 'class="active-header-content" style="color:black"' : ''; ?>>My Profile</a>
            <a href="settings.php?pageid=Settings?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo $current_page === 'settings.php' ? 'class="active-header-content" style="color:black"' : ''; ?>>Settings</a>
        </div>
    </nav>
    <div class="toggle">
        <div class="hamburger">
            <span></span>
        </div>
    </div>
</div>
<script src="js/fetch_notifications_count.js"></script>
<script>
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
</script>

