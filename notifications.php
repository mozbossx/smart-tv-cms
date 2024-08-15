<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// check if the user is not logged in, redirect to the login page (index.php)
if (!isset($_SESSION['full_name']) || !isset($_SESSION['email'])) {
    header('location: index.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// fetch user data for the currently logged-in user using prepared statements
$sql = "SELECT full_name, password, department, email, user_type FROM users_tb WHERE full_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $full_name);
$stmt->execute();
$result = $stmt->get_result();

// Check if user data is found
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    header('location: logout.php');
    exit;
}

$stmt->close();
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
    <title>Notifications</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <h1 class="content-title" style="color: black"><i class="fa fa-bell" style="padding-right: 5px"></i>Notifications</h1>
                    <div id="notificationsList" class="content-container" style="height: calc(100vh - 215px); overflow: auto; background: none; border: none">
                        <div class="scroll-div" style="height: auto; margin-bottom: 10px">
                            <div id="notificationsContainer">
                                 <!-- Latest notifications will be displayed here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="confirmApproveModal" class="modal"></div>
    <div id="confirmRejectModal" class="modal"></div>
    <div id="confirmApproveContentModal" class="modal"></div>
    <script src="js/fetch_notifications.js"></script>
    <script>
        const userType = '<?php echo $user_type; ?>';
        const full_name = '<?php echo $full_name; ?>';
        const user_id = '<?php echo $user_id; ?>';

        function closeModal(modalId) {
            var modal = document.getElementById(modalId + 'Modal');
            modal.style.display = 'none';
        }
    </script>
</body>
</html>