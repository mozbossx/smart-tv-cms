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
                $query = "SELECT * FROM smart_tvs_tb";
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
                        echo '<div class="tv-frame" id="tv-frame" style="scale: 0.35">';
                            echo "<iframe id='tv-iframe' frameborder='0' src='tv2.php?tvId=$tvId' class='tv-screen' style='height: {$tvHeight}px; width: {$tvWidth}px; pointer-events: none; border: none;'></iframe>";
                            echo '<p style="text-align: center; font-size: 25px; margin-top: auto; color: white;">'. htmlspecialchars($row['tv_brand']) .'</p>';
                        echo "</div>";
                        echo '<div class="scale-buttons">';
                        echo '<button id="scale-down"><i class="fa fa-search-minus"></i></button>';
                        echo '<button id="scale-up"><i class="fa fa-search-plus"></i></button>';
                        echo '</div>';
                        echo "</div>";
                        echo "<button class='green-button' style='float: right; margin-top: auto;' onclick='window.location.href=\"tv_contents.php?tvId=$tvId&initialize=false\"'>View Contents</button>";
                        echo '<p>Device ID: ' . htmlspecialchars($row['device_id']) . '</p>'; 
                        echo '<p>TV Brand: ' . htmlspecialchars($row['tv_brand']) . '</p>'; 
                        echo '</div>';
                    }
                } else {
                    echo '<p>No TVs found.</p>';
                }
                ?>

                    <!-- <div id="announcementList" class="content-container">
                        <h1 class="content-title" style="text-align: center;"><i class="fa fa-bullhorn" style="margin-right: 6px" aria-hidden="true"></i>Announcements</h1>
                        <div class="scroll-div">
                            <div id="announcementCarouselContainer">

                            </div>
                        </div>
                    </div>
                    <div id="eventList" class="content-container">
                        <h1 class="content-title" style="text-align: center;"><i class="fa fa-calendar-check-o" style="margin-right: 6px" aria-hidden="true"></i>Upcoming Events</h1>
                        <div class="scroll-div">
                            <div id="eventCarouselContainer">

                            </div>
                        </div>
                    </div>
                    <div id="newsList" class="content-container">
                        <h1 class="content-title" style="text-align: center;"><i class="fa fa-newspaper-o" style="margin-right: 6px" aria-hidden="true"></i>News</h1>
                        <div class="scroll-div">
                            <div id="newsCarouselContainer">

                            </div>
                        </div>
                    </div>
                    <div id="promaterialList" class="content-container">
                        <h1 class="content-title" style="text-align: center;"><i class="fa fa-object-group" style="margin-right: 6px" aria-hidden="true"></i>Promotional Materials</h1>
                        <div class="scroll-div">
                            <div id="promaterialCarouselContainer">

                            </div>
                        </div>
                    </div>
                    <div id="peoList" class="content-container">
                        <h1 class="content-title" style="text-align: center;"><i class="fa fa-map" style="margin-right: 6px" aria-hidden="true"></i>Program Educational Objectives (PEO)</h1>
                        <div class="scroll-div">
                            <div id="peoCarouselContainer">

                            </div>
                        </div>
                    </div>
                    <div id="soList" class="content-container">
                        <h1 class="content-title" style="text-align: center;"><i class="fa fa-graduation-cap" style="margin-right: 6px" aria-hidden="true"></i>Student Outcomes (SO)</h1>
                        <div class="scroll-div">
                            <div id="soCarouselContainer">

                            </div>
                        </div>
                    </div>
                    <div id="departmentOrganizationalChartList" class="content-container">
                        <h1 class="content-title" style="text-align: center;"><i class="fa fa-university" style="margin-right: 6px" aria-hidden="true"></i>Department Organizational Chart</h1>
                        <div class="scroll-div">
                            <div id="departmentOrganizationalChartCarouselContainer">

                            </div>
                        </div>
                    </div>
                    <div id="facilitiesList" class="content-container">
                        <h1 class="content-title" style="text-align: center;"><i class="fa fa-building" style="margin-right: 6px" aria-hidden="true"></i>Facilities</h1>
                        <div class="scroll-div">
                            <div id="facilitiesCarouselContainer">

                            </div>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </div>
    <script>
        const userType = '<?php echo $user_type; ?>';
        const full_name = '<?php echo $full_name; ?>';
        const tvFrame = document.getElementById('tv-frame');
        const scaleUpButton = document.getElementById('scale-up');
        const scaleDownButton = document.getElementById('scale-down');
        let scale = 1;
        let isDragging = false;
        let startX, startY, scrollLeft, scrollTop;

        // Scale Up Button
        scaleUpButton.addEventListener('click', () => {
            scale += 0.1;
            tvFrame.style.transform = `scale(${scale})`;
        });

        // Scale Down Button
        scaleDownButton.addEventListener('click', () => {
            if (scale > 0.2) {  // Prevent scaling too small
                scale -= 0.1;
                tvFrame.style.transform = `scale(${scale})`;
            }
        });

        // Drag to Pan
        tvFrame.parentElement.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX - tvFrame.offsetLeft;
            startY = e.clientY - tvFrame.offsetTop;
            tvFrame.parentElement.style.cursor = 'grabbing';
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
            tvFrame.parentElement.style.cursor = 'grab';
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            const x = e.clientX - startX;
            const y = e.clientY - startY;
            tvFrame.style.left = `${x}px`;
            tvFrame.style.top = `${y}px`;
        });
    </script>
</body>
</html>