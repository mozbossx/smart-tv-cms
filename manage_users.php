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
                    <h1 class="content-title" style="color: black"><i class="fa fa-users" style="padding-right: 5px"></i>Manage Users</h1>
                    <div class="content-form">
                        <div class="button-flex-space-between">
                            <div class="left-side-button">
                                <button type="button" class="back-button" onclick="javascript:history.back()"><i class="fa fa-arrow-left" style="padding-right: 5px"></i>Back</button>
                            </div>
                            <div class="right-side-button-preview">
                                <a href="create_post.php" class="green-button">Add a User<i class="fa fa-plus" style="margin-left: 5px"></i></a>
                            </div>
                        </div>
                        <div class="line-separator"></div>
                        <?php include('error_message.php'); ?>
                        <div id="user-data" data-user-id="<?php echo $user_id; ?>"></div>
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
    <div id="confirmApproveUserModal" class="modal"></div>
    <div id="confirmRejectUserModal" class="modal"></div>
    <div id="editUserModal" class="modal"></div>
    <div id="confirmDeleteUserModal" class="modal"></div>

    <!-- JavaScript to fetch all users using WebSocket-->
    <script src="js/fetch_users.js"></script>
    <script>
        function sortTable(columnIndex) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("usersTable");
            switching = true;
            // Set the sorting direction to ascending:
            dir = "asc";
            /* Make a loop that will continue until
            no switching has been done: */
            while (switching) {
                // Start by saying: no switching is done:
                switching = false;
                rows = table.rows;
                /* Loop through all table rows (except the
                first, which contains table headers): */
                for (i = 1; i < (rows.length - 1); i++) {
                    // Start by saying there should be no switching:
                    shouldSwitch = false;
                    /* Get the two elements you want to compare,
                    one from current row and one from the next: */
                    x = rows[i].getElementsByTagName("TD")[columnIndex];
                    y = rows[i + 1].getElementsByTagName("TD")[columnIndex];
                    /* Check if the two rows should switch place,
                    based on the direction, asc or desc: */
                    if (dir == "asc") {
                        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                            // If so, mark as a switch and break the loop:
                            shouldSwitch = true;
                            break;
                        }
                    } else if (dir == "desc") {
                        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                            // If so, mark as a switch and break the loop:
                            shouldSwitch = true;
                            break;
                        }
                    }
                }
                if (shouldSwitch) {
                    /* If a switch has been marked, make the switch
                    and mark that a switch has been done: */
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    // Each time a switch is done, increase this count by 1:
                    switchcount ++;
                } else {
                    /* If no switching has been done AND the direction is "asc",
                    set the direction to "desc" and run the while loop again. */
                    if (switchcount == 0 && dir == "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
        }
    </script>
</body>
</html>
