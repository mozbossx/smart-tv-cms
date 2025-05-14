<?php

$tvId = $_GET['tvId'] ?? null;
$isIframe = $_GET['isIframe'] ?? null;

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
            $backgroundColorQuery = "SELECT * FROM background_tv_tb WHERE tv_id = ?";
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

                $isStaticBackground = $bgColorData['isStaticBackground'];
                $isImageBackground = $bgColorData['isImageBackground'];
                $backgroundImagePath = $bgColorData['background_image_path'];

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
                $departmentLogoPath = $topbarColorData['department_logo_path'];
            }
            
            // Redirect to the same page with tvId as query parameter
            if (!isset($tvId)) {
                // $tvId = $row['tv_id'];
                header("Location: tv_preview.php?tvId=$tvId");
                exit();
            }

            break; // Break out of the loop once a match is found
        }
    }
}
?>