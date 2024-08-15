<?php
$tvId = $_GET['tvId'];

// Check if the device ID is already stored in a cookie
if (!isset($_COOKIE['device_id']) && isset($_GET['initialize']) && $_GET['initialize'] === 'true') {
    // Generate a random six-digit device ID
    $tv_name = "Unknown";
    $tv_brand = "Unknown";
    $device_id = sprintf('%06d', mt_rand(0, 999999));

    // Store the device ID in a cookie
    setcookie('device_id', $device_id, time() + (10 * 365 * 24 * 60 * 60)); // Expire in 10 years

    // Store the device ID in the database
    $sql = "INSERT INTO smart_tvs_tb (tv_name, tv_brand, device_id) VALUES ('$tv_name', '$tv_brand', '$device_id')";
    if (mysqli_query($conn, $sql)) {
        // Get the ID of the newly inserted row
        $tv_id = mysqli_insert_id($conn);

        // Insert default background color into background_tv_tb
        $defaultColor = '#ffffff';
        $sql_bg = "INSERT INTO background_tv_tb (tv_id, tv_name, background_hex_color) VALUES ($tv_id, '$tv_name', '$defaultColor')";
        mysqli_query($conn, $sql_bg);
    } else {
        // Handle SQL error here
        echo "Error: " . mysqli_error($conn);
    }

    // Set the current device ID for this session
    $_COOKIE['device_id'] = $device_id;

    $sql = "SELECT * FROM smart_tvs_tb";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        // Fetch all rows and store them in an array
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
        // Find the row with the matching device_id
        foreach ($rows as $row) {
            if ($row['device_id'] == $device_id) { // line 62
                // Set session variables based on the matching row
                $_SESSION['tv_name'] = $row['tv_name'];
                $_SESSION['tv_brand'] = $row['tv_brand'];
                $_SESSION['device_id'] = $row['device_id'];
    
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

                // Fetch background colors, heights, and widths for each container from the database
                $containersQuery = "SELECT * FROM containers_tb WHERE tv_id = ? ORDER BY position_order ASC";
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
                    $topbarDeviceIdColor = $topbarColorData['topbar_deviceid_font_color'];
                    $topbarTimeColor = $topbarColorData['topbar_time_font_color'];
                    $topbarDateColor = $topbarColorData['topbar_date_font_color'];
                }
                    
                // Redirect to the same page with tvId as query parameter
                if (!isset($tvId)) {
                    // $tvId = $row['tv_id'];
                    header("Location: tv.php?tvId=$tvId?initialize=true");
                    exit();
                }
    
                break; // Break out of the loop once a match is found
            }
        }
    }
} else if (isset($_COOKIE['device_id']) || isset($_GET['initialize']) && $_GET['initialize'] === 'false') {
    $device_id = $_COOKIE['device_id'];

    $sql = "SELECT * FROM smart_tvs_tb";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        // Fetch all rows and store them in an array
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
        // Find the row with the matching device_id
        foreach ($rows as $row) {
            if ($row['device_id'] == $device_id) { // line 62
                // Set session variables based on the matching row
                $_SESSION['tv_name'] = $row['tv_name'];
                $_SESSION['tv_brand'] = $row['tv_brand'];
                $_SESSION['device_id'] = $row['device_id'];
    
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
                $containersQuery = "SELECT * FROM containers_tb WHERE tv_id = ? ORDER BY position_order ASC";
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
                    $topbarDeviceIdColor = $topbarColorData['topbar_deviceid_font_color'];
                    $topbarTimeColor = $topbarColorData['topbar_time_font_color'];
                    $topbarDateColor = $topbarColorData['topbar_date_font_color'];
                }
               
                // Redirect to the same page with tvId as query parameter
                if (!isset($tvId)) {
                    // $tvId = $row['tv_id'];
                    header("Location: tv.php?tvId=$tvId");
                    exit();
                }
    
                break; // Break out of the loop once a match is found
            }
        }
    }
} else {
    $sql = "SELECT * FROM smart_tvs_tb";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        // Fetch all rows and store them in an array
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
        // Find the row with the matching device_id
        foreach ($rows as $row) {
            if ($row['device_id']) { // line 62
                // Set session variables based on the matching row
                $_SESSION['tv_name'] = $row['tv_name'];
                $_SESSION['tv_brand'] = $row['tv_brand'];
                $_SESSION['device_id'] = $row['device_id'];
    
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
                $containersQuery = "SELECT * FROM containers_tb WHERE tv_id = ? ORDER BY position_order ASC";
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
                    $topbarDeviceIdColor = $topbarColorData['topbar_deviceid_font_color'];
                    $topbarTimeColor = $topbarColorData['topbar_time_font_color'];
                    $topbarDateColor = $topbarColorData['topbar_date_font_color'];
                }
                
                // Redirect to the same page with tvId as query parameter
                if (!isset($tvId)) {
                    // $tvId = $row['tv_id'];
                    header("Location: tv.php?tvId=$tvId");
                    exit();
                }
    
                break; // Break out of the loop once a match is found
            }
        }
    }
}



?>