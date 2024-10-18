<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

include 'admin_access_only.php';

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
                                <?php 
                                    if ($user_type == 'Super Admin') {
                                        echo '<li class="breadcrumb-item"><a href="admin_options.php?pageid=AdminOptions?userId=' . $user_id . '&full_name=' . $full_name . '" style="color: #264B2B">Super Admin Options</a></li>';
                                    } else {
                                        echo '<li class="breadcrumb-item"><a href="admin_options.php?pageid=AdminOptions?userId=' . $user_id . '&full_name=' . $full_name . '" style="color: #264B2B">Admin Options</a></li>';
                                    }
                                ?>
                                <li class="breadcrumb-item active" aria-current="page">Manage Users</li>
                            </ol>
                        </nav>                        
                        <div id="user-data" data-user-id="<?php echo $user_id; ?>" data-user-type="<?php echo $user_type; ?>"></div>
                        <div style="display: flex; justify-content: flex-end;">
                            <button type="button" class="green-button" id="selectMultipleBtn" style="margin-right: 5px"><i class="fa fa-check-square" style="margin-right: 2px"></i> Select Multiple</button>
                            <button type="button" class="green-button" id="deleteSelectedBtn" style="margin-right: 5px; display: none"><i class="fa fa-user-times" style="margin-right: 2px"></i> Delete Selected</button>
                            <button type="button" class="green-button" style="margin-right: 5px;" onclick="showAddUserModal(<?php echo $user_id?>)"><i class="fa fa-user-plus" style="margin-right: 2px"></i> Add User</button>
                            <button type="button" class="green-button" style="margin-right: 0;" onclick="showUserTableSummary()"><i class="fa fa-table" style="margin-right: 2px"></i> Table Summary</button>
                        </div>
                        <div class="error-message" style="display: none; margin-top: 10px"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></div>
                        <div class="success-message" style="display: none; margin-top: 10px"><i class="fa fa-check-circle" aria-hidden="true"></i></div>
                        <form class="search-form" style="width: 300px; max-width: 100%; margin: 0; margin-bottom: 10px;">
                            <div class="floating-label-container">
                                <input type="text" id="searchInput" required placeholder=" " class="floating-label-input" onkeyup="searchUsersTable()">
                                <label for="searchInput" class="floating-label"><i class="fa fa-search" style="margin-right: 6px" aria-hidden="true"></i> Search User Table</label>
                                <button type="button" id="clearSearch" style="display: none; position: absolute; right: 12px; font-size: 16px; top: 56%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </button>
                            </div>
                        </form>
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
    <script>
        function searchUsersTable() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("usersTable");
            tr = table.getElementsByTagName("tr");

            // Show/hide clear button based on input value
            document.getElementById("clearSearch").style.display = input.value ? "block" : "none";

            for (i = 1; i < tr.length; i++) {
                var found = false;
                for (var j = 0; j < tr[i].cells.length; j++) {
                    td = tr[i].getElementsByTagName("td")[j];
                    if (td) {
                        txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                if (found) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }

        // Add event listener for clear button
        document.getElementById("clearSearch").addEventListener("click", function() {
            document.getElementById("searchInput").value = "";
            searchUsersTable();
        });

        function sortTable(columnIndex) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("usersTable");
            switching = true;
            dir = "asc";
            while (switching) {
                switching = false;
                rows = table.rows;
                for (i = 1; i < (rows.length - 1); i++) {
                    shouldSwitch = false;
                    x = rows[i].getElementsByTagName("TD")[columnIndex];
                    y = rows[i + 1].getElementsByTagName("TD")[columnIndex];
                    
                    if (dir == "asc") {
                        if (compareValues(x, y) > 0) {
                            shouldSwitch = true;
                            break;
                        }
                    } else if (dir == "desc") {
                        if (compareValues(x, y) < 0) {
                            shouldSwitch = true;
                            break;
                        }
                    }
                }
                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                } else {
                    if (switchcount == 0 && dir == "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
        }

        function compareValues(x, y) {
            const xValue = x.textContent || x.innerText;
            const yValue = y.textContent || y.innerText;

            // Check if the values are dates
            const xDate = parseDate(xValue);
            const yDate = parseDate(yValue);
            if (xDate && yDate) {
                return xDate - yDate;
            }

            // Check if the values are numbers
            const xNum = parseFloat(xValue);
            const yNum = parseFloat(yValue);
            if (!isNaN(xNum) && !isNaN(yNum)) {
                return xNum - yNum;
            }

            // Default to string comparison
            return xValue.localeCompare(yValue);
        }

        function parseDate(dateString) {
            // Assuming date format is MM/DD/YYYY | HH:MM AM/PM
            const parts = dateString.split('|');
            if (parts.length === 2) {
                const datePart = parts[0].trim();
                const timePart = parts[1].trim();
                const [month, day, year] = datePart.split('/');
                let [time, ampm] = timePart.split(' ');
                let [hours, minutes] = time.split(':');
                
                if (ampm.toUpperCase() === 'PM' && hours !== '12') {
                    hours = parseInt(hours) + 12;
                }
                if (ampm.toUpperCase() === 'AM' && hours === '12') {
                    hours = '00';
                }
                
                return new Date(year, month - 1, day, hours, minutes);
            }
            return null;
        }
    </script>
</body>
</html>
