<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// Fetch smart TVs data
$smartTvsQuery = "SELECT * FROM smart_tvs_tb";
$resultTVQuery = $conn->query($smartTvsQuery);

if (!$resultTVQuery) {
    $error[] = "No smart TV data found!";
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
    <title>Manage Templates</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <h1 class="content-title" style="color: black"><i class="fa fa-window-restore" style="padding-right: 5px"></i>Manage Templates</h1>
                    <div class="content-form">
                        <div class="left-side-button">
                            <button type="button" class="back-button" onclick="javascript:history.back()"><i class="fa fa-arrow-left" style="padding-right: 5px"></i>Back</button>
                        </div>
                        <div class="line-separator"></div>
                        <?php include('error_message.php'); ?>
                        <div>
                            <!-- Display each smart tvs -->
                            <?php
                                if ($resultTVQuery->num_rows > 0) {
                                    // Output data of each row
                                    while ($row = $resultTVQuery->fetch_assoc()) {
                                        $tvId = $row['tv_id'];
                                        $tvBrand = $row['tv_brand'];
                                        $tvHeight = $row['height_px'];
                                        $tvWidth = $row['width_px'];
                                        echo "<div class='content-container' style='overflow: hidden'>";
                                        echo "<div class='tv-frame' style='user-select: none;'>";
                                        echo "<iframe src='tv.php?tvId=$tvId' class='tv-screen' style='pointer-events: none; user-select: none; height: {$tvHeight}px; width: {$tvWidth}px'></iframe>";
                                        echo "</div>";
                                        echo "<div class='line-separator'></div>";
                                        echo "<h2>" . htmlspecialchars($row['tv_name']) . "</h2>";
                                        echo "<p>Device ID: " . htmlspecialchars($row['device_id']) . "</p>";
                                        echo "<p>Brand: " . htmlspecialchars($row['tv_brand']) . "</p>";
                                        echo "<p>Screen Dimensions: " . htmlspecialchars($row['width_px']) . " x " . htmlspecialchars($row['height_px']) . "</p>";
                                        echo "<div class='line-separator'></div>";
                                        echo "<button class='green-button' onclick='window.location.href=\"edit_template.php?tvId=$tvId&initialize=false\"'>Edit Template</button>";
                                        echo "</div>";
                                    }
                                } else {
                                    echo "<p>No smart TVs found.</p>";
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>