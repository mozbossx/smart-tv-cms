<?php
// start the session
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
    <link rel="icon" type="image/png" href="images/usc_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Home</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <!-- search form -->
                <form class="search-form" style="width: 550px; max-width: 100%; margin: 0 auto; margin-bottom: 10px;">
                    <div class="floating-label-container">
                        <input type="text" id="searchInput" required placeholder=" " class="floating-label-input">
                        <label for="searchInput" class="floating-label"><i class="fa fa-search" style="margin-right: 6px" aria-hidden="true"></i> Search for TV Name, Brand, Department, or ID</label>
                    </div>
                </form>

                <div class="content-grid-container">
                <?php
                // Fetch data from smart_tvs_tb
                if($user_type == 'Super Admin') {
                    $query = "SELECT * FROM smart_tvs_tb WHERE tv_name != 'Unknown' AND tv_brand != 'Unknown'";
                } else {
                    $query = "SELECT * FROM smart_tvs_tb WHERE tv_department = '$department' AND tv_name != 'Unknown' AND tv_brand != 'Unknown'";
                }

                $result = mysqli_query($conn, $query);
        
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $tvId = $row['tv_id'];
                        $tvBrand = $row['tv_brand'];
                        $tvName = $row['tv_name'];
                        $tvDepartment = $row['tv_department'];
                        $tvHeight = $row['height_px'];
                        $tvWidth = $row['width_px'];
                        // Display each TV item
                        echo '<div class="content-container" data-tv-name="' . strtolower(htmlspecialchars($tvName)) . '" data-tv-brand="' . strtolower(htmlspecialchars($tvBrand)) . '" data-tv-id="' . strtolower(htmlspecialchars($tvId)) . '" data-tv-department="' . strtolower(htmlspecialchars($tvDepartment)) . '">';
                        echo '<h1 class="tv-title" id="tvNameUserHome_' . $tvId . '" style="text-align: center; padding-bottom: 0;"><i class="fa fa-tv" style="margin-right: 6px" aria-hidden="true"></i>' . htmlspecialchars($row['tv_name']) . '</h1>';
                        echo '<div style="display: flex; justify-content: center; align-items: center;">';
                            echo '<p style="margin: 0; margin-right: 5px; color: #6E6E6E"><small>ID: ' . htmlspecialchars($row['tv_id']) . '</small></p>'; 
                            echo '<p id="tvBrandUserHome_' . $tvId . '" style="margin: 0; color: #6E6E6E;"><small>| ' . htmlspecialchars($row['tv_brand']) . '</small></p>'; 
                        echo '</div>';
                        echo '<p id="tvDepartmentUserHome_' . $tvId . '" style="margin: 0; margin-bottom: 10px; color: #6E6E6E; text-align: center;"><small>' . htmlspecialchars($row['tv_department']) . '</small></p>'; 
                        echo '<div class="tv-frame-parent" style="width: auto; height: 350px; background: none; cursor: default;">';
                        echo '<div class="tv-frame" style="display: flex; flex-direction: column; align-items: center;">';
                            echo "<iframe frameborder='0' src='tv2.php?tvId=$tvId&isIframe=true' class='tv-screen' style='height: {$tvHeight}px; width: {$tvWidth}px; pointer-events: none; border: none;'></iframe>";
                            echo '<p id="tvBrand2UserHome_' . $tvId . '" style="text-align: center; font-size: 25px; margin-top: auto; color: white;">'. htmlspecialchars($row['tv_brand']) .'</p>';
                            echo '<div class="tv-stand"></div>';
                        echo "</div>";
                        echo "</div>";
                        // echo '<div class="scale-control">';
                        // echo '<input type="range" min="0.1" max="2" step="0.1" value="1" class="scale-slider">';
                        // echo '</div>';
                        echo "<div class='line-separator'></div>";
                        echo "<button class='green-button' style='float: right; margin-top: auto;' onclick='window.location.href=\"tv_contents.php?tvId=$tvId&isIframe=true\"'>View Contents</button>";
                        echo '</div>';
                    }
                } else {
                    echo '<p style="font-size: 25px; color: black; margin-top: 5px;">No TVs found.</p>';
                }
                ?>
                </div>
            </div>
        </div>
    </div>
    <script src="misc/js/tv_frames.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const tvContainers = document.querySelectorAll('.content-container');

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();

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
            });
        });
    </script>
</body>
</html>