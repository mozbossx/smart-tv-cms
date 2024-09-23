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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                <div class="content-grid-container">
                <?php
                // Fetch data from smart_tvs_tb
                $query = "SELECT * FROM smart_tvs_tb WHERE tv_department = '$department' AND tv_name != 'Unknown' AND tv_brand != 'Unknown'";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $tvId = $row['tv_id'];
                        $tvBrand = $row['tv_brand'];
                        $tvHeight = $row['height_px'];
                        $tvWidth = $row['width_px'];
                        // Display each TV item
                        echo '<div class="content-container">';
                        echo '<h1 class="content-title" style="text-align: center; padding-bottom: 0;"><i class="fa fa-tv" style="margin-right: 6px" aria-hidden="true"></i>' . htmlspecialchars($row['tv_name']) . '</h1>';
                        echo '<div class="tv-frame-parent" style="width: auto; height: 350px; ">';
                        echo '<div class="tv-frame" style="scale: 0.35">';
                            echo "<iframe frameborder='0' src='tv2.php?tvId=$tvId&isIframe=true' class='tv-screen' style='height: {$tvHeight}px; width: {$tvWidth}px; pointer-events: none; border: none;'></iframe>";
                            echo '<p style="text-align: center; font-size: 25px; margin-top: auto; color: white;">'. htmlspecialchars($row['tv_brand']) .'</p>';
                        echo "</div>";
                        echo '<div class="scale-buttons">';
                        echo '<button class="scale-down"><i class="fa fa-search-minus"></i></button>';
                        echo '<button class="scale-up"><i class="fa fa-search-plus"></i></button>';
                        echo '</div>';
                        echo "</div>";
                        echo "<button class='green-button' style='float: right; margin-top: auto;' onclick='window.location.href=\"tv_contents.php?tvId=$tvId&initialize=false\"'>View Contents</button>";
                        echo '<p>Device ID: ' . htmlspecialchars($row['device_id']) . '</p>'; 
                        echo '<p>TV Brand: ' . htmlspecialchars($row['tv_brand']) . '</p>'; 
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
    <script src="js/fetch_user_session.js"></script>
</body>
</html>