<?php
// start the session
session_start();
include 'config_connection.php';

// fetch user data for the currently logged-in user
include 'get_session.php';

$tvId = $_GET['tvId'];

// Fetch data from smart_tvs_tb
$query = "SELECT * FROM smart_tvs_tb WHERE tv_id = '$tvId'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tvName = $row['tv_name'];
        $tvBrand = $row['tv_brand'];
        $tvHeight = $row['height_px'];
        $tvWidth = $row['width_px'];
    }
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
    <title>Home</title>
</head>
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <nav aria-label="breadcrumb" style="margin: 10px;">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="user_home.php?pageid=UserHome?userId=<?php echo $user_id; ?>''<?php echo $full_name; ?>" style="color: #264B2B">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">View Contents</li>
                    </ol>
                </nav>
                <?php
                echo '<h1 class="content-title" style="text-align: center; padding-bottom: 0;"><i class="fa fa-tv" style="margin-right: 6px" aria-hidden="true"></i>' . htmlspecialchars($tvName) . '</h1>';
                echo '<div class="tv-frame-parent" style="width: auto; height: 450px; ">';
                echo '<div class="tv-frame" id="tv-frame" style="scale: 0.5; user-select: none">';
                    echo "<iframe id='tv-iframe' frameborder='0' src='tv2.php?tvId=$tvId&isIframe=true' class='tv-screen' style='height: {$tvHeight}px; width: {$tvWidth}px; pointer-events: none; border: none;'></iframe>";
                    echo '<p style="text-align: center; font-size: 25px; margin-top: auto; color: white;">'. htmlspecialchars($tvBrand) .'</p>';
                echo "</div>";
                echo '<div class="scale-buttons">';
                echo '<button id="scale-down"><i class="fa fa-search-minus"></i></button>';
                echo '<button id="scale-up"><i class="fa fa-search-plus"></i></button>';
                if ($user_type == 'Admin') {
                    echo "<button class='green-button' style='margin-left: 20px' onclick=\"window.location.href='edit_template.php?tvId=$tvId&initialize=false'\"><i class='fa fa-window-restore' style='margin-right: 6px' aria-hidden='true'></i>Edit Template</button>";
                }
                echo '</div>';
                echo "</div>";
                ?>
                <div class="content-grid-container">
                <?php
                // Array of content types
                $contentTypes = ['announcement', 'event',  'news', 'promaterial', 'peo', 'so'];
                // echo '<p>'; print_r($contentTypes); echo '</p>';

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
                        echo "<h1 class='content-title' style='text-align: center;'><i class='fa fa-{$type}' style='margin-right: 6px' aria-hidden='true'></i>" . ucfirst($type) . "</h1>";
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
    <!-- Archive Modals -->
    <div id="confirmArchiveAnnouncementModal" class="modal">
        <div id="archiveAnnouncementModalContent"></div>
    </div>
    <div id="confirmArchiveEventModal" class="modal">
        <div id="archiveEventModalContent"></div>
    </div>
    <div id="confirmArchiveNewsModal" class="modal">
        <div id="archiveNewsModalContent"></div>
    </div>
    <div id="confirmArchivePromaterialModal" class="modal">
        <div id="archivePromaterialModalContent"></div>
    </div>
    <div id="confirmArchivePeoModal" class="modal">
        <div id="archivePeoModalContent"></div>
    </div>
    <div id="confirmArchiveSoModal" class="modal">
        <div id="archiveSoModalContent"></div>
    </div>
    
    <!-- Delete Modals -->
    <div id="confirmDeleteAnnouncementModal" class="modal">
        <div id="deleteAnnouncementModalContent"></div>
    </div>
    <div id="confirmDeleteEventModal" class="modal">
        <div id="deleteEventModalContent"></div>
    </div>
    <div id="confirmDeleteNewsModal" class="modal">
        <div id="deleteNewsModalContent"></div>
    </div>
    <div id="confirmDeletePromaterialModal" class="modal">
        <div id="deletePromaterialModalContent"></div>
    </div>
    <div id="confirmDeletePeoModal" class="modal">
        <div id="deletePeoModalContent"></div>
    </div>
    <div id="confirmDeleteSoModal" class="modal">
        <div id="deleteSoModalContent"></div>
    </div>
    <div id="confirmDeleteNewsModal" class="modal">
        <div id="deleteModalContent"></div>
    </div>
    
    <!-- JavaScript to fetch all content using WebSocket-->
    <script src="js/fetch_content2.js"></script>
    <script>
        const userType = '<?php echo $user_type; ?>';
        const full_name = '<?php echo $full_name; ?>';
        const userId = <?php echo $user_id; ?>;
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