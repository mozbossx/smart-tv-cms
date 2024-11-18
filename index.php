<?php
session_start();
@include 'config_connection.php';

// include 'check_for_inactive_users.php';

// Check if the user is already logged in, redirect to user-home.php
if (isset($_SESSION['full_name'])) {
    header('location: user_home.php');
    exit;
}

// Get dynamic content from CMS/Database
$welcomeMessage = "Smart TV Content Management System";
$subheading = "Empowering Interactive Digital Displays for the University of San Carlos";
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
    <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_index.css">
    <title>Smart TV CMS</title>
</head>
<body>
    <section class="hero-section">
        <div class="nav-buttons">
            <a href="login.php?pageid=Login" class="nav-button login-btn">Login</a>
            <a href="create_account.php?pageid=CreateAccount" class="nav-button create-account-btn">Create Account</a>
        </div>
        <div class="hero-content">
            <div style="display: flex; flex-direction: row">
                <img src="images/usc_icon.png" class="usc-logo">
                <img src="images/soe_icon.png" class="soe-logo">
            </div>
            
            <h1 class="hero-title"><?php echo htmlspecialchars($welcomeMessage); ?></h1>
            <p class="hero-subtitle"><?php echo htmlspecialchars($subheading); ?></p>
        </div>
    </section>

    <section class="features-section">
        <h2 class="section-title">Key Features</h2>
        <div class="features-grid">
            <div class="feature-card">
                <h3 class="feature-title">Mobile Control</h3>
                <p class="feature-description">Control your Smart TV CMS directly from your smartphone. Post content, manage displays, and make updates on the go.</p>
            </div>
            <div class="feature-card">
                <h3 class="feature-title">Interactive Content Management</h3>
                <p class="feature-description">Create and manage announcements, events, news, promotional materials, and more with an intuitive interface.</p>
            </div>
            <div class="feature-card">
                <h3 class="feature-title">Customizable Display</h3>
                <p class="feature-description">Adjust content container sizes, fonts, colors, and backgrounds to match your department's needs.</p>
            </div>
        </div>
    </section>

    <section class="user-roles">
        <h2 class="section-title">User Roles & Capabilities</h2>
        <div class="role-cards">
            <div class="role-card">
                <h3 class="role-title">Faculty, Staff, and Students</h3>
                <ul class="role-features">
                    <li>Post content for approval</li>
                    <li>Manage personal profile</li>
                    <li>Access personal content archives</li>
                    <li>Track submission status</li>
                </ul>
            </div>
            <div class="role-card">
                <h3 class="role-title">Administrator</h3>
                <ul class="role-features">
                    <li>Direct content submission</li>
                    <li>Manage departmental users</li>
                    <li>Customize TV templates</li>
                    <li>Control display settings</li>
                    <li>User role management within department</li>
                </ul>
            </div>
            <div class="role-card">
                <h3 class="role-title">Super Administrator</h3>
                <ul class="role-features">
                    <li>Cross-department management</li>
                    <li>System-wide control</li>
                    <li>Advanced customization</li>
                    <li>User role management across departments</li>
                </ul>
            </div>
        </div>
    </section>
</body>
</html>