<?php
// start the session
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

// Fetch all features from the database
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "");
$stmtNewFeatures = $pdo->query("SELECT * FROM features_tb");
$featuresNewFeatures = $stmtNewFeatures->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Arhives</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-grid-container">
                <?php
                // Array of content types
                $contentTypes = ['announcement', 'event',  'news', 'promaterial', 'peo', 'so'];
                // echo '<p>'; print_r($contentTypes); echo '</p>';

                // Add new features to the content types array
                foreach ($featuresNewFeatures as $feature) {
                    $contentTypes[] = strtolower(str_replace(' ', '_', $feature['feature_name']));
                }

                // Loop through each content type
                foreach ($contentTypes as $type) {
                    echo "<div id='{$type}List' class='content-container'>";
                    if ($type == 'announcement') {
                        echo "<h1 class='content-title' style='text-align: center;'><i class='fa fa-bullhorn' style='margin-right: 6px' aria-hidden='true'></i>Announcements</h1>";
                    } else if ($type == 'event') {
                        echo "<h1 class='content-title' style='text-align: center;'><i class='fa fa-calendar-check-o' style='margin-right: 6px' aria-hidden='true'></i>Events</h1>";
                    } else if ($type == 'news') {
                        echo "<h1 class='content-title' style='text-align: center;'><i class='fa fa-newspaper-o' style='margin-right: 6px' aria-hidden='true'></i>News</h1>";
                    } else if ($type == 'promaterial') {
                        echo "<h1 class='content-title' style='text-align: center;'><i class='fa fa-object-group' style='margin-right: 6px' aria-hidden='true'></i>Promotional Materials</h1>";
                    } else if ($type == 'peo') {
                        echo "<h1 class='content-title' style='text-align: center;'><i class='fa fa-map' style='margin-right: 6px' aria-hidden='true'></i>Program Educational Objectives (PEO)</h1>";
                    } else if ($type == 'so') {
                        echo "<h1 class='content-title' style='text-align: center;'><i class='fa fa-graduation-cap' style='margin-right: 6px' aria-hidden='true'></i>Student Outcomes (SO)</h1>";
                    } else {
                        // For new features
                        $feature = array_filter($featuresNewFeatures, function($f) use ($type) {
                            return strtolower(str_replace(' ', '_', $f['feature_name'])) === $type;
                        });
                        $feature = reset($feature);
                        if ($feature) {
                            echo "<h1 class='content-title' style='text-align: center;'><i class='fa {$feature['icon']}' style='margin-right: 6px' aria-hidden='true'></i>{$feature['feature_name']}</h1>";
                        } else {
                            echo "<h1 class='content-title' style='text-align: center;'><i class='fa fa-{$type}' style='margin-right: 6px' aria-hidden='true'></i>" . ucfirst(str_replace('_', ' ', $type)) . "</h1>";
                        }
                    }
                    echo "<div class='scroll-div'>";
                    echo "<div id='{$type}CarouselContainer'>";

                    echo "</div>"; // Close carousel container
                    echo "</div>"; // Close scroll div
                    echo "</div>"; // Close content container
                }
                ?>
                </div>
            </div>
        </div>
    </div>
    <!-- JavaScript to fetch all content using WebSocket-->
    <script src="js/fetch_archived_content.js"></script>
    <script src="js/fetch_user_session.js"></script>
</body>
</html>