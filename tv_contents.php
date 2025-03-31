<?php
// start the session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';
include 'user_theme_color.php';

$tvId = $_GET['tvId'];

if (isset($_GET['tvId'])) {
    $tvId = $_GET['tvId'];
    $tvQuery = "SELECT tv_department FROM smart_tvs_tb WHERE tv_id = ?";
    $tvStmt = $conn->prepare($tvQuery);
    $tvStmt->bind_param("i", $tvId);
    $tvStmt->execute();
    $tvResult = $tvStmt->get_result();

    if ($tvResult->num_rows > 0) {
        $tvData = $tvResult->fetch_assoc();
        $tvDepartment = $tvData['tv_department'];

        // Check if the tv_department is not equal to the sessioned department
        if ($user_type != 'Super Admin') {
            if ($tvDepartment !== $department) {
                echo '
                    <script>
                        alert("You are not authorized to access this TV."); 
                        window.location.href = "user_home.php";
                    </script>';
                exit;
            }
        }
    } else {
        // If no TV found, redirect to user_home.php
        header('location: user_home.php');
        exit;
    }

    $tvStmt->close();
}

// Fetch data from smart_tvs_tb
$query = "SELECT * FROM smart_tvs_tb WHERE tv_id = '$tvId'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tvId = $row['tv_id'];
        $tvName = $row['tv_name'];
        $tvBrand = $row['tv_brand'];
        $tvDepartment = $row['tv_department'];
        $tvHeight = $row['height_px'];
        $tvWidth = $row['width_px'];
        $tvStatus = $row['active_status'];
    }
}

// Fetch all features from the database
$pdo = new PDO("mysql:host=localhost;dbname=smart_tv_cms_db", "root", "Mozar@123456");
$stmtNewFeatures = $pdo->query("SELECT * FROM features_tb WHERE department = '$tvDepartment'");
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
    <link rel="stylesheet" href="style_facility.css">
    <script src="misc/js/treant-js-master/vendor/jquery.min.js"></script>
    <script src="misc/js/treant-js-master/vendor/raphael.js"></script>
    <script src="misc/js/treant-js-master/Treant.js"></script>
    <link rel="stylesheet" href="misc/js/treant-js-master/Treant.css" />
    <title><?php echo $tvName; ?></title>
    <style>
        .create-post-button-1:hover {
            cursor: pointer;
            background: <?php echo $dark_theme_color_50_percent; ?> !important;
        }
    </style>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <nav aria-label="breadcrumb" style="margin: 10px;">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="user_home.php?pageid=UserHome?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $tvName; ?></li>
                    </ol>
                </nav>
                <?php
                // Initial status icon color based on database value
                $statusIcon = $tvStatus === 'active' ? 
                '<i class="fa fa-circle" style="color: #2ECC71;"></i>' : 
                '<i class="fa fa-circle" style="color: #E74C3C;"></i>';
                echo '<h1 id="tvNameContent" style="text-align: center; padding-bottom: 0;"><i class="fa fa-tv" style="margin-right: 6px" aria-hidden="true"></i>' . htmlspecialchars($tvName) . '<span id="activeTvStatus_' . $tvId . '" style="font-size: 22px; margin-left: 10px">' . $statusIcon . '</span></h1>';
                echo '<div style="display: flex; justify-content: center; align-items: center;">';
                    echo '<p style="margin: 0; margin-right: 5px; color: #6E6E6E"><small>ID: ' . $tvId . '</small></p>'; 
                    echo '<p id="tvBrandUserHome_' . $tvId . '" style="margin: 0; color: #6E6E6E;"><small>| ' . $tvBrand . '</small></p>'; 
                echo '</div>';
                echo '<p id="tvDepartmentUserHome_' . $tvId . '" style="margin: 0; margin-bottom: 10px; color: #6E6E6E; text-align: center;"><small>' . $tvDepartment . '</small></p>'; 
                echo '<div class="tv-frame-parent" style="width: auto; height: 450px; background: none; cursor: default;">';
                echo '<div class="tv-frame" id="tv-frame" style="scale: 0.5; user-select: none">';
                    echo "<iframe id='tv-iframe' frameborder='0' src='tv.php?tvId=$tvId&isIframe=true' class='tv-screen' style='height: {$tvHeight}px; width: {$tvWidth}px; pointer-events: none; border: none;'></iframe>";
                    echo '<p id="tvBrand2UserHome_' . $tvId . '" style="text-align: center; font-size: 25px; margin-top: auto; color: white;">'. htmlspecialchars($tvBrand) .'</p>';
                echo "</div>";
                echo "<div class='scale-buttons' style='background: none;'>";
                if ($user_type == 'Admin' || $user_type == 'Super Admin') {
                    echo "<button class='green-button' style='padding: 8px 16px; margin-left: 20px' onclick=\"window.location.href='edit_template.php?tvId=$tvId&initialize=false'\"><i class='fa fa-window-restore' aria-hidden='true'></i></button>";
                }
                echo "</div>";
                echo "</div>";
                ?>
                <div class="content-grid-container">
                <?php
                // Array of content types
                $contentTypes = ['announcement', 'event',  'news', 'promaterial', 'peo', 'so', 'orgchart', 'facility'];
                // echo '<p>'; print_r($contentTypes); echo '</p>';

                // Add new features to the content types array
                foreach ($featuresNewFeatures as $feature) {
                    $contentTypes[] = strtolower(str_replace(' ', '_', $feature['feature_name']));
                }

                // Loop through each content type
                foreach ($contentTypes as $type) {
                    // Skip certain content types for non-admin users
                    if (($type == 'peo' || $type == 'so' || $type == 'orgchart' || $type == 'facility') && 
                        $user_type != 'Admin' && $user_type != 'Super Admin') {
                        continue;
                    }
                    
                    echo "<div id='{$type}List' class='content-container' style='padding: 0; padding-bottom: 20px; margin: 10px'>";
                    echo '<div style="background: '.$theme_color.'; display: flex; flex-direction: row; justify-content: space-between">';
                    if ($type == 'announcement') {
                        echo "<h1 class='content-title' style='padding: 15px; text-align: left; width: 75%'><i class='fa fa-bullhorn' style='margin-right: 6px' aria-hidden='true'></i>Announcements <span id='announcementCount' style='color: $light_theme_color_70_percent;'></span></h1>";
                    } else if ($type == 'event') {
                        echo "<h1 class='content-title' style='padding: 15px; text-align: left; width: 75%'><i class='fa fa-calendar-check-o' style='margin-right: 6px' aria-hidden='true'></i>Events <span id='eventCount' style='color: $light_theme_color_70_percent;'></span></h1>";
                    } else if ($type == 'news') {
                        echo "<h1 class='content-title' style='padding: 15px; text-align: left; width: 75%'><i class='fa fa-newspaper-o' style='margin-right: 6px' aria-hidden='true'></i>News <span id='newsCount' style='color: $light_theme_color_70_percent;'></span></h1>";
                    } else if ($type == 'promaterial') {
                        echo "<h1 class='content-title' style='padding: 15px; text-align: left; width: 75%'><i class='fa fa-object-group' style='margin-right: 6px' aria-hidden='true'></i>Promotional Materials <span id='promaterialCount' style='color: $light_theme_color_70_percent;'></span></h1>";
                    } else if ($type == 'peo') {
                        echo "<h1 class='content-title' style='padding: 15px; text-align: left; width: 75%'><i class='fa fa-map' style='margin-right: 6px' aria-hidden='true'></i>Program Educational Objectives (PEO) <span id='peoCount' style='color: $light_theme_color_70_percent;'></span></h1>";
                    } else if ($type == 'so') {
                        echo "<h1 class='content-title' style='padding: 15px; text-align: left; width: 75%'><i class='fa fa-graduation-cap' style='margin-right: 6px' aria-hidden='true'></i>Student Outcomes (SO) <span id='soCount' style='color: $light_theme_color_70_percent;'></span></h1>";
                    } else if ($type == 'orgchart') {
                        echo "<h1 class='content-title' style='padding: 15px; text-align: left; width: 75%'><i class='fa fa-graduation-cap' style='margin-right: 6px' aria-hidden='true'></i>Department Organizational Chart <span id='orgchartCount' style='color: $light_theme_color_70_percent;'></span></h1>";
                    } else if ($type == 'facility') {
                        echo "<h1 class='content-title' style='padding: 15px; text-align: left; width: 75%'><i class='fa fa-building' style='margin-right: 6px' aria-hidden='true'></i>Facilities <span id='facilityCount' style='color: $light_theme_color_70_percent;'></span></h1>";
                    } else {
                        // For new features
                        $feature = array_filter($featuresNewFeatures, function($f) use ($type) {
                            return strtolower(str_replace(' ', '_', $f['feature_name'])) === $type;
                        });
                        $feature = reset($feature);
                        if ($feature) {
                            echo "<h1 class='content-title' style='padding: 15px; text-align: left; width: 75%'><i class='fa {$feature['icon']}' style='margin-right: 6px' aria-hidden='true'></i>{$feature['feature_name']} <span id='{$type}Count' style='color: $light_theme_color_70_percent;'></span></h1>";
                        } else {
                            echo "<h1 class='content-title' style='padding: 15px; text-align: left; width: 75%'><i class='fa fa-{$type}' style='margin-right: 6px' aria-hidden='true'></i>" . ucfirst(str_replace('_', ' ', $type)) . " <span id='{$type}Count' style='color: $light_theme_color_70_percent;'></span></h1>";
                        }
                    }
                    echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
                    if ($type == 'promaterial') {
                        echo '<button id="createPostButton" class="create-post-button-1" style="background: '.$theme_color.'; color: white; border: none; height: 100%; padding-left: 15px; padding-right: 15px; font-size: 25px" onclick="window.location.href=\'form_promotional_material.php?pageid=PromotionalMaterialsForm&userId=' . $user_id . ' ' . urlencode($full_name) . '&tvId=' . $tvId . '\'"><i class="fa fa-plus" aria-hidden="true"></i></button>';
                    } else if ($type == 'so') {
                        echo '<button id="createPostButton" class="create-post-button-1" style="background: '.$theme_color.'; color: white; border: none; height: 100%; padding-left: 15px; padding-right: 15px; font-size: 25px" onclick="window.location.href=\'form_student_outcomes.php?pageid=StudentOutcomesForm&userId=' . $user_id . ' ' . urlencode($full_name) . '&tvId=' . $tvId . '\'"><i class="fa fa-plus" aria-hidden="true"></i></button>';
                    } else if ($type == 'orgchart') {
                        echo '<button id="createPostButton" class="create-post-button-1" style="background: '.$theme_color.'; color: white; border: none; height: 100%; padding-left: 15px; padding-right: 15px; font-size: 25px" onclick="window.location.href=\'form_department_organizational_chart.php?pageid=DepartmentOrganizationalChart&userId=' . $user_id . ' ' . urlencode($full_name) . '&tvId=' . $tvId . '\'"><i class="fa fa-plus" aria-hidden="true"></i></button>';
                    } else {
                        echo '<button id="createPostButton" class="create-post-button-1" style="background: '.$theme_color.'; color: white; border: none; height: 100%; padding-left: 15px; padding-right: 15px; font-size: 25px" onclick="window.location.href=\'form_' . $type . '.php?pageid=' . $type . 'Form&userId=' . $user_id . ' ' . urlencode($full_name) . '&tvId=' . $tvId . '\'"><i class="fa fa-plus" aria-hidden="true"></i></button>';
                    }
                    echo "</div>";
                    echo '</div>';
                    echo "<div class='scroll-div' style='margin-top: 10px; margin-left: 15px; margin-right: 15px;'>";
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
    <script src="js/fetch_content2.js"></script>
    <?php include('misc/php/success_modal.php'); ?>
    <script>
        const tvFrame = document.querySelector('.tv-frame');

        // Function to fit the tv-frame inside its parent
        function fitFrameToParent() {
            const parent = tvFrame.parentElement;
            const parentRect = parent.getBoundingClientRect();

            // Reset any existing transformations and positioning
            tvFrame.style.transform = 'none';
            
            const frameRect = tvFrame.getBoundingClientRect();

            const scaleX = parentRect.width / frameRect.width;
            const scaleY = parentRect.height / frameRect.height;
            const scale = Math.min(scaleX, scaleY, 1); // Don't scale up if already smaller

            // Apply transformations
            tvFrame.style.transform = `scale(${scale})`;
        }

        // Fit frame on load
        fitFrameToParent();

        // Refit on window resize
        // window.addEventListener('resize', fitFrameToParent);
    </script>
</body>
</html>