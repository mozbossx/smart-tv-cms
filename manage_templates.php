<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

include 'admin_access_only.php';

// Fetch data from smart_tvs_tb
if($user_type == 'Super Admin') {
    $querySelectExtistingTvs = "SELECT * FROM smart_tvs_tb WHERE tv_name != 'Unknown' AND tv_brand != 'Unknown'";
} else {
    $querySelectExtistingTvs = "SELECT * FROM smart_tvs_tb WHERE tv_department = '$department' AND tv_name != 'Unknown' AND tv_brand != 'Unknown'";
}

$resultSelectExtistingTvs = mysqli_query($conn, $querySelectExtistingTvs);

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
    <title>Manage Templates</title>
    <style>
        .form-field {
            position: relative;
            box-sizing: border-box;
        }

        .form-field input[type="password"] {
            padding-right: 40px;
        }

        .form-field i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .form-field input {
            width: 100%;
            padding: 1rem 2rem 1rem 3rem;
            border: 1px solid #858585;
            background: #f4f4f4;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .form-field input:focus {
            border-color: #007638;
            outline: none;
        }
    </style>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('error_message.php'); ?>
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <div class="content-form">
                        <!-- Breadcrumb Navigation -->
                        <nav aria-label="breadcrumb" style="margin-bottom: 10px;">
                            <ol class="breadcrumb">
                                <?php 
                                    if ($user_type == 'Super Admin') {
                                        echo '<li class="breadcrumb-item"><a href="admin_options.php?pageid=AdminOptions?userId=' . $user_id . '&full_name=' . $full_name . '">Super Admin Options</a></li>';
                                    } else {
                                        echo '<li class="breadcrumb-item"><a href="admin_options.php?pageid=AdminOptions?userId=' . $user_id . '&full_name=' . $full_name . '">Admin Options</a></li>';
                                    }
                                ?>
                                <li class="breadcrumb-item active" aria-current="page">Manage Templates</li>
                            </ol>
                        </nav>
                        <?php
                        if (mysqli_num_rows($resultSelectExtistingTvs) > 0) {
                            echo '
                                <!-- search form -->
                                <form class="search-form" style="width: 550px; max-width: 100%; margin: 0 auto; margin-bottom: 10px;">
                                    <div class="form-field">
                                        <i class="bi bi-search"></i>
                                        <input type="text" id="searchInput" required placeholder="Search TVs" onkeyup="searchTvs()">
                                        <button type="button" id="clearSearch" style="display: none; position: absolute; right: 40px; font-size: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                                            <i class="fa fa-times" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </form>
                            ';
                        } else if (mysqli_num_rows($resultSelectExtistingTvs) == 0) {
                            echo '';
                        }
                        ?>

                        <?php include('error_message.php'); ?>
                        <div class="content-grid-container">
                        <?php
                
                        if (mysqli_num_rows($resultSelectExtistingTvs) > 0) {
                            while ($row = mysqli_fetch_assoc($resultSelectExtistingTvs)) {
                                $tvId = $row['tv_id'];
                                $tvBrand = $row['tv_brand'];
                                $tvName = $row['tv_name'];
                                $tvDepartment = $row['tv_department'];
                                $tvHeight = $row['height_px'];
                                $tvWidth = $row['width_px'];
                                $tvStatus = $row['active_status'];
                                // Display each TV item
                                echo '<div class="content-container" style="padding: 0; padding-bottom: 20px; margin: 10px" data-tv-name="' . strtolower(htmlspecialchars($tvName)) . '" data-tv-brand="' . strtolower(htmlspecialchars($tvBrand)) . '" data-tv-id="' . strtolower(htmlspecialchars($tvId)) . '" data-tv-department="' . strtolower(htmlspecialchars($tvDepartment)) . '">';
                                // Initial status icon color based on database value
                                $statusIcon = $tvStatus === 'active' ? 
                                '<i class="fa fa-circle" style="color: #2ECC71;"></i>' : 
                                '<i class="fa fa-circle" style="color: #E74C3C;"></i>';
                                echo '<div style="background: '.$theme_color.'; padding: 15px">';
                                echo '<h1 class="tv-title" id="tvNameUserHome_' . $tvId . '" style="text-align: left; padding-bottom: 0; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; width: 100%; font-weight: normal; color: white; font-size: 20px"><i class="fa fa-tv" style="margin-right: 6px" aria-hidden="true"></i>' . htmlspecialchars($row['tv_name']) . '<span id="activeTvStatus_' . $tvId . '" style="font-size: 18px; float: right;">' . $statusIcon . '</span></h1>';
                                echo '</div>';
                                echo '<div class="tv-frame-parent" style="margin: 15px; width: auto; height: 270px; background: none; cursor: default;">';
                                echo '<div class="tv-frame" style="display: flex; flex-direction: column; align-items: center;">';
                                    echo "<iframe frameborder='0' src='tv.php?tvId=$tvId&isIframe=true' class='tv-screen' style='height: {$tvHeight}px; width: {$tvWidth}px; pointer-events: none; border: none;'></iframe>";
                                    echo '<p id="tvBrand2UserHome_' . $tvId . '" style="text-align: center; font-size: 25px; margin-top: auto; color: white; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; width: auto; max-width: 250px;">'. htmlspecialchars($row['tv_brand']) .'</p>';
                                echo "</div>";
                                echo "</div>";
                                echo "<div class='line-separator'></div>";
                                echo "<button class='green-button' style='float: right; margin-top: 5px; margin-right: 15px' onclick='window.location.href=\"edit_template.php?tvId=$tvId&isIframe=true\"'>Edit Template <i class=\"fa fa-chevron-right\" aria-hidden=\"true\" style=\"margin-left: 7px\"></i></button>";
                                echo '</div>';
                            }
                        } 
                        ?>
                        </div>
                        <?php
                        if (mysqli_num_rows($resultSelectExtistingTvs) == 0) {
                            echo '<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">';
                                echo '<img src="images/no_tvs_found.png" alt="No TVs found" style="display: block; margin: 0 auto; width: auto; height: 200px;">';
                                echo '<p style="text-weight: bold; font-size: 25px; color: black; margin-top: 5px; text-align: center;">No TVs found</p>';
                            echo '</div>';
                        }
                        ?>
                </div>
            </div>
        </div>
    </div>
    <script src="misc/js/tv_frames.js"></script>
    <script>
        function searchTvs() {
            const searchInput = document.getElementById('searchInput');
            const clearSearchButton = document.getElementById('clearSearch');
            const tvContainers = document.querySelectorAll('.content-container');
            const searchTerm = searchInput.value.toLowerCase().trim();

            tvContainers.forEach(container => {
                const tvName = container.getAttribute('data-tv-name');
                const tvBrand = container.getAttribute('data-tv-brand');
                const tvId = container.getAttribute('data-tv-id');
                const tvDepartment = container.getAttribute('data-tv-department');
                
                if (tvName.includes(searchTerm) || tvBrand.includes(searchTerm) || tvId.includes(searchTerm) || tvDepartment.includes(searchTerm)) {
                    container.style.display = '';
                } else {
                    container.style.display = 'none';
                }
            });

            // Show or hide the clear search button based on the input value
            clearSearchButton.style.display = searchTerm ? 'block' : 'none';
        }

        // Add event listener for the clear search button
        if (document.getElementById('clearSearch')) {
            document.getElementById('clearSearch').addEventListener('click', function() {
                const searchInput = document.getElementById('searchInput');
                searchInput.value = '';
                searchTvs(); // Reset the search results
            });
        }
    </script>
</body>
</html>