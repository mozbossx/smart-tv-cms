<?php

$tvId = $_GET['tvId'] ?? $_COOKIE['tv_id'] ?? null;
$isIframe = $_GET['isIframe'] ?? null;

echo "<script>console.log('isIframe: " . $isIframe . "');</script>";

// Check if the tv_id is already stored in a cookie
if ($tvId === null || (!isset($_COOKIE['tv_id']) && !$isIframe)) {
    $tv_name = "Unknown";
    $tv_brand = "Unknown";

    $sql = "INSERT INTO smart_tvs_tb (tv_name, tv_brand) VALUES ('$tv_name', '$tv_brand')";
    if (mysqli_query($conn, $sql)) {
        // Get the ID of the newly inserted row
        $tvId = mysqli_insert_id($conn);

        // Store the tv_id in a cookie
        setcookie('tv_id', $tvId, time() + (10 * 365 * 24 * 60 * 60)); // Expire in 10 years

        // Insert default background color into background_tv_tb
        $defaultColor = '#ffffff';
        $sql_bg = "INSERT INTO background_tv_tb (tv_id, tv_name, background_hex_color) VALUES ($tvId, '$tv_name', '$defaultColor')";
        mysqli_query($conn, $sql_bg);

        $sql_topbar = "INSERT INTO topbar_tv_tb (tv_id, tv_name) VALUES ($tvId, '$tv_name')";
        mysqli_query($conn, $sql_topbar);

        $containerNameAnnouncements = 'Announcements';
        $containerNameEvents = 'Events';
        $containerNameNews = 'News';
        $containerNamePromaterials = 'Promotional Materials';
        $containerNamePEO = 'Program Educational Objectives';
        $containerNameSO = 'Student Outcomes';
        $containerNameOrgChart = 'Department Organizational Chart';
        $containerTypeAnnouncements = 'announcement';
        $containerTypeEvents = 'event';
        $containerTypeNews = 'news';
        $containerTypePromaterials = 'promaterial';
        $containerTypePEO = 'peo';
        $containerTypeSO = 'so';
        $containerTypeOrgChart = 'orgchart';
        $containerVisibleAnnouncements = 1;
        $containerVisibleEvents = 1;
        $containerVisibleNews = 1;
        $containerVisiblePromaterials = 1;
        $containerVisiblePEO = 0;
        $containerVisibleSO = 0;
        $containerVisibleOrgChart = 0;
        $containerXaxisAnnouncements = 1.0;
        $containerYaxisAnnouncements = 5.0;
        $containerXaxisEvents = 415.0;
        $containerYaxisEvents = 5.0;
        $containerXaxisNews = 826.0;
        $containerYaxisNews = 5.0;
        $containerXaxisPromaterials = 1200.0;
        $containerYaxisPromaterials = 5.0;
        

        $sql_announcement_container = "INSERT INTO containers_tb (tv_id, container_name, type, visible, xaxis, yaxis) VALUES ($tvId, '$containerNameAnnouncements', '$containerTypeAnnouncements', $containerVisibleAnnouncements, $containerXaxisAnnouncements, $containerYaxisAnnouncements)";
        mysqli_query($conn, $sql_announcement_container);
        
        $sql_event_container = "INSERT INTO containers_tb (tv_id, container_name, type, visible, xaxis, yaxis) VALUES ($tvId, '$containerNameEvents', '$containerTypeEvents', $containerVisibleEvents, $containerXaxisEvents, $containerYaxisEvents)";
        mysqli_query($conn, $sql_event_container);

        $sql_news_container = "INSERT INTO containers_tb (tv_id, container_name, type, visible, xaxis, yaxis) VALUES ($tvId, '$containerNameNews', '$containerTypeNews', $containerVisibleNews, $containerXaxisNews, $containerYaxisNews)";
        mysqli_query($conn, $sql_news_container);

        $sql_promaterials_container = "INSERT INTO containers_tb (tv_id, container_name, type, visible, xaxis, yaxis) VALUES ($tvId, '$containerNamePromaterials', '$containerTypePromaterials', $containerVisiblePromaterials, $containerXaxisPromaterials, $containerYaxisPromaterials)";
        mysqli_query($conn, $sql_promaterials_container);

        $sql_peo_container = "INSERT INTO containers_tb (tv_id, container_name, type, visible) VALUES ($tvId, '$containerNamePEO', '$containerTypePEO', $containerVisiblePEO)";
        mysqli_query($conn, $sql_peo_container);

        $sql_so_container = "INSERT INTO containers_tb (tv_id, container_name, type, visible) VALUES ($tvId, '$containerNameSO', '$containerTypeSO', $containerVisibleSO)";
        mysqli_query($conn, $sql_so_container);

        $sql_orgchart_container = "INSERT INTO containers_tb (tv_id, container_name, type, visible) VALUES ($tvId, '$containerNameOrgChart', '$containerTypeOrgChart', $containerVisibleOrgChart)";
        mysqli_query($conn, $sql_orgchart_container);

        // Redirect to the same page with tvId as query parameter
        header("Location: tv2.php?tvId=$tvId");
        exit();
    } else {
        // Handle SQL error here
        echo "Error: " . mysqli_error($conn);
        exit();
    }
} 

if ((!isset($_COOKIE['tv_id']) || $_COOKIE['tv_id'] != $tvId) && !$isIframe) {
    echo "<h1 style='color: red;'>You have no permission to access this TV</h1>";
    echo "<h1 style='color: red;'>$isIframe</h1>";
    exit();
}

// If tvId is set but not in the URL, redirect to include it
if (!isset($_GET['tvId']) && isset($_COOKIE['tv_id'])) {
    header("Location: tv2.php?tvId=" . $_COOKIE['tv_id']);
    exit();
}

$sql = "SELECT * FROM smart_tvs_tb";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    // Fetch all rows and store them in an array
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Find the row with the matching tv_id
    foreach ($rows as $row) {
        if ($row['tv_id'] == $tvId) { // line 62
            // Set session variables based on the matching row
            $_SESSION['tv_name'] = $row['tv_name'];
            $_SESSION['tv_brand'] = $row['tv_brand'];
            $_SESSION['tv_department'] = $row['tv_department'];
            $_SESSION['tv_id'] = $row['tv_id'];

            // Fetch background color from the database
            $backgroundColorQuery = "SELECT background_hex_color FROM background_tv_tb WHERE tv_id = ?";
            $stmtBgColor = $conn->prepare($backgroundColorQuery);
            $stmtBgColor->bind_param("i", $tvId);
            $stmtBgColor->execute();
            $resultBgColorQuery = $stmtBgColor->get_result();

            // Fetch topbar color from the database
            $topbarColorQuery = "SELECT * FROM topbar_tv_tb WHERE tv_id = ?";
            $stmtTopbarColor = $conn->prepare($topbarColorQuery);
            $stmtTopbarColor->bind_param("i", $tvId);
            $stmtTopbarColor->execute();
            $resultTopbarColorQuery = $stmtTopbarColor->get_result();

            // Fetch background colors for each container from the database
            $containersQuery = "SELECT * FROM containers_tb WHERE tv_id = ?";
            $stmtContainers = $conn->prepare($containersQuery);
            $stmtContainers->bind_param("i", $tvId);
            $stmtContainers->execute();
            $resultContainersQuery = $stmtContainers->get_result();

            $containers = [];
            if ($resultContainersQuery->num_rows > 0) {
                while ($containerRow = $resultContainersQuery->fetch_assoc()) {
                    $containers[] = $containerRow;
                }
            }

            if ($resultBgColorQuery->num_rows > 0 || $resultTopbarColorQuery->num_rows > 0 || $resultContainersQuery->num_rows > 0) {
                $bgColorData = $resultBgColorQuery->fetch_assoc();
                $topbarColorData = $resultTopbarColorQuery->fetch_assoc();
                $containersData = $resultContainersQuery->fetch_assoc();
                $backgroundColor = $bgColorData['background_hex_color'];
                $topbarColor = $topbarColorData['topbar_hex_color'];

                $topbarTvNameColor = $topbarColorData['topbar_tvname_font_color'];
                $topbarTvNameFontStyle = $topbarColorData['topbar_tvname_font_style'];
                $topbarTvNameFontFamily = $topbarColorData['topbar_tvname_font_family'];

                $topbarTvIdColor = $topbarColorData['topbar_tvid_font_color'];
                $topbarTvIdFontStyle = $topbarColorData['topbar_tvid_font_style'];
                $topbarTvIdFontFamily = $topbarColorData['topbar_tvid_font_family'];

                $topbarTimeColor = $topbarColorData['topbar_time_font_color'];
                $topbarTimeFontStyle = $topbarColorData['topbar_time_font_style'];
                $topbarTimeFontFamily = $topbarColorData['topbar_time_font_family'];

                $topbarDateColor = $topbarColorData['topbar_date_font_color'];
                $topbarDateFontStyle = $topbarColorData['topbar_date_font_style'];
                $topbarDateFontFamily = $topbarColorData['topbar_date_font_family'];

                $topbarPosition = $topbarColorData['topbar_position'];
            }
            
            // Redirect to the same page with tvId as query parameter
            if (!isset($tvId)) {
                // $tvId = $row['tv_id'];
                header("Location: tv2.php?tvId=$tvId");
                exit();
            }

            break; // Break out of the loop once a match is found
        }
    }
}
?>