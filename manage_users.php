<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// Fetch the current user's data
$sqlCurrentUser = "SELECT * FROM users_tb";
$resultCurrentUser = mysqli_query($conn, $sqlCurrentUser);

if (!$resultCurrentUser) {
    $error[] = "Error: " . mysqli_error($conn);
}

// Check if the user is not 'Admin', redirect to the user_home
if ($_SESSION['user_type'] !== 'Admin') {
    header('location: user_home.php');
    exit;
}

// Fetch data from the users_tb table
$sqlAllUsers = "SELECT * FROM users_tb";
$resultAllUsers = mysqli_query($conn, $sqlAllUsers);

if (!$resultAllUsers) {
    $error[] = "Error: " . mysqli_error($conn);
}

// Check if user data is found
if ($resultAllUsers->num_rows > 0) {
    // Move the loop inside the if statement
} else {
    $error[] = "No user data found";
}
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Manage Users</title>
    <style>
        th {
            cursor: pointer;
        }
    </style>
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
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="admin_options.php?pageid=AdminOptions?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="color: #264B2B">Create Post</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Manage Users</li>
                            </ol>
                        </nav>
                        <?php include('error_message.php'); ?>
                        <div id="user-data" data-user-id="<?php echo $user_id; ?>"></div>
                        <div style="display: flex; justify-content: flex-end;">
                            <button type="button" class="green-button" style="margin-right: 0;" onclick="showAddUserModal()">Add User</button>
                        </div>
                        <div class="table-container">
                            <div id="userTableContainer">
                                <!-- Latest table of users will be displayed here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript to fetch all users using WebSocket-->
    <?php include('misc/php/success_modal.php')?>
    <?php include('misc/php/error_modal.php')?>
    <script src="js/fetch_users.js"></script>
    <script src="misc/js/sort_table.js"></script>
</body>
</html>
