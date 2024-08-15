<?php
include 'config_connection.php';

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

// Fetch user data from the database using prepared statements to prevent SQL injection
$query = "SELECT full_name, password, department, email, user_type FROM users_tb WHERE full_name = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $_SESSION['full_name']);
$stmt->execute();
$result = $stmt->get_result();

// Check if user data is found
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    $error[] = "Error: No user data found";
}

// Close the statement
$stmt->close();

?>

<style>
    .blur-background {
        filter: blur(5px);
    }
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5); /* Semi-transparent black overlay */
        display: none;
        z-index: 1; /* Ensure the overlay is on top of the main content */
    }

    .overlay.active-overlay {
        display: block;
    }

</style>

<div class="sidebar">
    <nav class="navbar">
        <a href="user_home.php?pageid=UserHome?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo ($current_page === 'user_home.php' || $current_page === 'edit_announcement.php' || $current_page === 'edit_event.php' || $current_page === 'edit_news.php' || $current_page === 'edit_promaterial.php' || $current_page === 'edit_peo.php') ? 'class="active-sidebar-content"' : ''; ?>><i class="fa fa-home" style="padding-right: 8px"></i>Home</a>
        <a href="create_post.php?pageid=CreatePost?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo ($current_page === 'create_post.php' || $current_page === 'form_announcement.php' || $current_page === 'form_event.php' || $current_page === 'form_news.php' || $current_page === 'form_promotional_material.php' || $current_page === 'general_info.php' || $current_page === 'form_peo.php' || $current_page === 'form_student_outcomes.php' || $current_page === 'form_department_organizational_chart.php' || $current_page === 'form_facilities.php' || $current_page === 'add_new_feature.php') ? 'class="active-sidebar-content"' : ''; ?>><i class="fa fa-pencil-square" style="padding-right: 8px"></i>Create Post</a>
        <a href="notifications.php?pageid=Notifications?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo $current_page === 'notifications.php' ? 'class="active-sidebar-content"' : ''; ?>><i class="fa fa-bell" style="padding-right: 8px"></i>Notifications</a>
        <a href="archives.php?pageid=Archives?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo $current_page === 'archives.php' ? 'class="active-sidebar-content"' : ''; ?>><i class="fa fa-archive" style="padding-right: 8px"></i>Archives</a>
        <?php if ($user_type === 'Admin') { ?>
            <a href="admin_options.php?pageid=AdminOptions?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo $current_page === 'admin_options.php' || $current_page === 'manage_users.php' || $current_page === 'manage_smart_tvs.php' || $current_page === 'manage_templates.php' || $current_page === 'edit_template.php' || $current_page === 'manage_posts.php'? 'class="active-sidebar-content"' : ''; ?>><i class="fa fa-user-secret" style="padding-right: 8px"></i>Admin Options</a>
        <?php } ?>
        <a href="profile.php?pageid=Profile?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo $current_page === 'profile.php' ? 'class="active-sidebar-content"' : ''; ?>><i class="fa fa-user" style="padding-right: 8px"></i>My Profile</a>
        <a href="settings.php?pageid=Settings?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" <?php echo $current_page === 'settings.php' ? 'class="active-sidebar-content"' : ''; ?>><i class="fa fa-gear" style="padding-right: 8px"></i>Settings</a>
        <a href="logout.php" style="margin-bottom: 80px"><i class="fa fa-sign-out" style="padding-right: 8px"></i>Logout</a>
    </nav>
</div>

<div class="overlay" id="overlay"></div>

<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function () {
        const toggleButton = document.querySelector('.toggle');
        const sidebar = document.querySelector('.sidebar');
        const logo = document.getElementById('logo');
        const mainContent = document.querySelector('.main-container');
        const overlay = document.getElementById('overlay'); // Get the overlay element

        toggleButton.addEventListener('click', function () {
            toggleButton.classList.toggle('active');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('blur-background');
            overlay.classList.toggle('active-overlay'); // Toggle overlay

            // Add or remove the hide-logo class to fade out or display the logo
            if (logo.classList.contains('hide-logo')) {
                logo.classList.remove('hide-logo');
            } else {
                logo.classList.add('hide-logo');
            }
        });

        // Additional logic to close the sidebar on outside click
        overlay.addEventListener('click', function (event) {
            if (sidebar.classList.contains('active') && !sidebar.contains(event.target) && !toggleButton.contains(event.target)) {
                sidebar.classList.remove('active');
                toggleButton.classList.remove('active');
                mainContent.classList.remove('blur-background');
                overlay.classList.remove('active-overlay');
                logo.classList.remove('hide-logo');
            }
        });

        
    });

</script>
