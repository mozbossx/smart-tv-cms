<?php
// Start the session and include the configuration
session_start();
include 'config_connection.php';

// Fetch user data for the currently logged-in user
include 'get_session.php';

// Initialize variables to store tv data
$tvId = '';
$tvHeight = '';
$tvWidth = '';
$backgroundColor = '';
$topbarColor = '';

$topbarTvNameColor = '';
$topbarTvNameFontStyle = '';
$topbarTvNameFontFamily = '';

$topbarDeviceIdColor = '';
$topbarDeviceIdFontStyle = '';
$topbarDeviceIdFontFamily = '';

$topbarTimeColor = '';
$topbarTimeFontStyle = '';
$topbarTimeFontFamily = '';

$topbarDateColor = '';
$topbarDateFontStyle = '';
$topbarDateFontFamily = '';

// Check if tvId is set in the URL
if (isset($_GET['tvId'])) {
    $tvId = $_GET['tvId'];

    // Fetch background color from the database
    $backgroundColorQuery = "SELECT background_hex_color FROM background_tv_tb WHERE tv_id = ?";
    $stmtBgColor = $conn->prepare($backgroundColorQuery);
    $stmtBgColor->bind_param("i", $tvId);
    $stmtBgColor->execute();
    $resultBgColorQuery = $stmtBgColor->get_result();

    if ($resultBgColorQuery->num_rows > 0) {
        $bgColorData = $resultBgColorQuery->fetch_assoc();
        $backgroundColor = $bgColorData['background_hex_color'];
    }

    $stmtBgColor->close();

    // Fetch topbar color from the database
    $topbarColorQuery = "SELECT * FROM topbar_tv_tb WHERE tv_id = ?";
    $stmtTopbarColor = $conn->prepare($topbarColorQuery);
    $stmtTopbarColor->bind_param("i", $tvId);
    $stmtTopbarColor->execute();
    $resultTopbarColorQuery = $stmtTopbarColor->get_result();

    if ($resultTopbarColorQuery->num_rows > 0) {
        $topbarColorData = $resultTopbarColorQuery->fetch_assoc();
        $topbarColor = $topbarColorData['topbar_hex_color'];

        $topbarTvNameColor = $topbarColorData['topbar_tvname_font_color'];
        $topbarTvNameFontStyle = $topbarColorData['topbar_tvname_font_style'];
        $topbarTvNameFontFamily = $topbarColorData['topbar_tvname_font_family'];

        $topbarDeviceIdColor = $topbarColorData['topbar_deviceid_font_color'];
        $topbarDeviceIdFontStyle = $topbarColorData['topbar_deviceid_font_style'];
        $topbarDeviceIdFontFamily = $topbarColorData['topbar_deviceid_font_family'];

        $topbarTimeColor = $topbarColorData['topbar_time_font_color'];
        $topbarTimeFontStyle = $topbarColorData['topbar_time_font_style'];
        $topbarTimeFontFamily = $topbarColorData['topbar_time_font_family'];

        $topbarDateColor = $topbarColorData['topbar_date_font_color'];
        $topbarDateFontStyle = $topbarColorData['topbar_date_font_style'];
        $topbarDateFontFamily = $topbarColorData['topbar_date_font_family'];
    }

    $stmtTopbarColor->close();

    // Fetch background colors for each container from the database
    $containersQuery = "SELECT * FROM containers_tb WHERE tv_id = ?";
    $stmtContainers = $conn->prepare($containersQuery);
    $stmtContainers->bind_param("i", $tvId);
    $stmtContainers->execute();
    $resultContainersQuery = $stmtContainers->get_result();

    // Store container details in an array
    while ($container = $resultContainersQuery->fetch_assoc()) {
        $containers[] = $container;
    }

    $stmtContainers->close();

    // Fetch TV Size for each tv from the database
    $tvQuery = "SELECT * FROM smart_tvs_tb WHERE tv_id = ?";
    $stmtTvSize = $conn->prepare($tvQuery);
    $stmtTvSize->bind_param("i", $tvId);
    $stmtTvSize->execute();
    $resultTvSizeQuery = $stmtTvSize->get_result();

    if ($resultTvSizeQuery->num_rows > 0) {
        $tvSizeData = $resultTvSizeQuery->fetch_assoc();

        $tvHeight = $tvSizeData['height_px'];
        $tvWidth = $tvSizeData['width_px'];
        $tvBrand = $tvSizeData['tv_brand'];
    }

    $stmtTvSize->close();

} else {
    die("tvId not specified.");
}

// Fetch smart TVs data
$smartTvsQuery = "SELECT tv_id, tv_name, device_id, tv_brand FROM smart_tvs_tb WHERE tv_id = ?";
$stmt = $conn->prepare($smartTvsQuery);
$stmt->bind_param("i", $tvId);
$stmt->execute();
$resultTVQuery = $stmt->get_result();

if ($resultTVQuery->num_rows == 0) {
    die("No smart TV found for tvId: " . htmlspecialchars($tvId));
}

// Fetch the data for the specific TV
$tvData = $resultTVQuery->fetch_assoc();

$stmt->close();
$conn->close();
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
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Edit Template</title>
</head>
<body style="overflow: hidden;">
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <div class="content-form">
                        <!-- Breadcrumb Navigation -->
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="admin_options.php" style="color: #264B2B">Admin Options</a></li>
                                <li class="breadcrumb-item"><a href="manage_templates.php" style="color: #264B2B">Manage Templates</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit Template</li>
                            </ol>
                        </nav>
                        <?php include('error_message.php'); ?>
                        <!-- Content Area -->
                        <div class="tv-frame-parent" style="height: 70vh; user-select: none; -moz-user-select: none; -webkit-user-drag: none; -webkit-user-select: none; -ms-user-select: none;">
                            <!-- Display iframe based on tvId -->
                            <div class="tv-frame" id="tv-frame">
                                <iframe id="tv-iframe" frameborder="0" src="tv2.php?tvId=<?php echo $tvId?>&isIframe=true" class="tv-screen" style="height: <?php echo $tvHeight?>px; width: <?php echo $tvWidth?>px"></iframe>
                                <p style="text-align: center; font-size: 25px; margin-top: auto; color: white;"><?php echo $tvBrand?></p>
                            </div>
                            <!-- Sidebar -->
                            <div class="left-sidebar" id="leftSidebar">
                                <div class="content-container" style="margin-top: 0; position: fixed; width: 245px">
                                    <p style="color: #264B2B; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;"><b>Show/Hide Content Containers</b></p>
                                    <form id="visibilitySettingsForm" style="padding: 8px; height: 58vh; overflow-y: auto;">
                                        <?php foreach ($containers as $container): ?>
                                            <label class="option-div">
                                                <input type="checkbox" id="container_<?php echo $container['container_id']; ?>" name="container_<?php echo $container['container_id']; ?>" <?php echo $container['visible'] ? 'checked' : ''; ?> style="margin-right: 10px;">
                                                <label for="container_<?php echo $container['container_id']; ?>"><?php echo htmlspecialchars($container['container_name']); ?></label>
                                            </label>
                                        <?php endforeach; ?>
                                    </form>
                                </div>
                            </div>
                            <div class="scale-buttons-2">
                                <button type="button" id="openSidebarButton" class="open-sidebar-button open" onclick="openSidebar()"><i class="fa fa-angle-right"></i></button>
                                <button type="button" id="closeSidebarButton" class="close-sidebar-button" onclick="closeSidebar()"><i class="fa fa-angle-left"></i></button>
                                <button type="button" id="scale-down"><i class="fa fa-search-minus"></i></button>
                                <button type="button" id="scale-up"><i class="fa fa-search-plus"></i></button>
                                <button type="button" id="updateTemplateButton" style="display: none;"><i class="fa fa-floppy-o"></i></button>
                            </div>
                            <!-- Top Bar Customization Left-side panel -->
                            <div id="topbarLeftSidePanel" class="left-side-panel" >
                                <div class="content-container" style="background: #f9fffa; position: fixed; width: 245px">
                                    <div class="panel-close-btn">
                                        <button onclick="closeTopbarLeftSidePanel()"><i class="fa fa-angle-left" aria-hidden="true"></i> Collapse</button>
                                    </div>
                                    <p style="color: #264B2B; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;"><b>Customize Top Bar</b></p>
                                    <form id="editTopBarColorForm" enctype="multipart/form-data" style="height: 57vh; overflow-y: auto; overflow-x: hidden; padding-right: 10px">
                                        <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px; ">
                                            <input type="color" id="topbar_color" name="topbar_color" class="floating-label-input" style="height: 60px; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="<?php echo htmlspecialchars($topbarColor); ?>">
                                            <label for="topbar_color" class="floating-label">Top Bar Color</label>
                                        </div>
                                        <div class="line-separator" style="margin: 0px;"></div>
                                        <p style="color: #264B2B; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;"><b>TV Name</b></p>
                                        <div class="split-container" style="margin-bottom: 0">
                                            <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px; ">
                                                <input type="color" id="topbar_tvname_color" name="topbar_tvname_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="<?php echo htmlspecialchars($topbarTvNameColor); ?>">
                                                <label for="topbar_tvname_color" class="floating-label">Font Color</label>
                                            </div>
                                            <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                                                <select id="topbar_tvname_font_style" name="topbar_tvname_font_style" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                                                    <option value="normal" <?php echo $topbarTvNameFontStyle == 'normal' ? 'selected' : ''; ?>>Normal</option>
                                                    <option value="italic" <?php echo $topbarTvNameFontStyle == 'italic' ? 'selected' : ''; ?> style="font-style: italic">Italic</option>
                                                </select>
                                                <label for="topbar_tvname_font_style" class="floating-label">Font Style</label>
                                            </div>
                                        </div>
                                        <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                                            <select id="topbar_tvname_font_family" name="topbar_tvname_font_family" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                                                <option value="Questrial" <?php echo $topbarTvNameFontFamily == 'Questrial' ? 'selected' : ''; ?> style="font-family: Questrial">Questrial</option>
                                                <option value="Arial" <?php echo $topbarTvNameFontFamily == 'Arial' ? 'selected' : ''; ?> style="font-family: Arial">Arial</option>
                                                <option value="Helvetica" <?php echo $topbarTvNameFontFamily == 'Helvetica' ? 'selected' : ''; ?> style="font-family: Helvetica">Helvetica</option>
                                                <option value="Verdana" <?php echo $topbarTvNameFontFamily == 'Verdana' ? 'selected' : ''; ?> style="font-family: Verdana">Verdana</option>
                                                <option value="Times New Roman" <?php echo $topbarTvNameFontFamily == 'Times New Roman' ? 'selected' : ''; ?> style="font-family: Times New Roman">Times New Roman</option>
                                                <option value="Georgia" <?php echo $topbarTvNameFontFamily == 'Georgia' ? 'selected' : ''; ?> style="font-family: Georgia">Georgia</option>
                                                <option value="Courier New" <?php echo $topbarTvNameFontFamily == 'Courier New' ? 'selected' : ''; ?> style="font-family: Courier New">Courier New</option>
                                                <option value="Libre Baskerville" <?php echo $topbarTvNameFontFamily == 'Libre Baskerville' ? 'selected' : ''; ?> style="font-family: Libre Baskerville">Libre Baskerville</option>
                                            </select>
                                            <label for="topbar_tvname_font_family" class="floating-label">Font Family</label>
                                        </div>
                                        <div class="line-separator" style="margin: 0px;"></div>
                                        <p style="color: #264B2B; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;"><b>Device ID</b></p>
                                        <div class="split-container" style="margin-bottom: 0">
                                            <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px; ">
                                                <input type="color" id="topbar_deviceid_color" name="topbar_deviceid_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="<?php echo htmlspecialchars($topbarDeviceIdColor); ?>">
                                                <label for="topbar_deviceid_color" class="floating-label">Font Color</label>
                                            </div>
                                            <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                                                <select id="topbar_deviceid_font_style" name="topbar_deviceid_font_style" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                                                    <option value="normal" <?php echo $topbarDeviceIdFontStyle == 'normal' ? 'selected' : ''; ?>>Normal</option>
                                                    <option value="italic" <?php echo $topbarDeviceIdFontStyle == 'italic' ? 'selected' : ''; ?> style="font-style: italic">Italic</option>
                                                </select>
                                                <label for="topbar_deviceid_font_style" class="floating-label">Font Style</label>
                                            </div>
                                        </div>
                                        <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                                            <select id="topbar_deviceid_font_family" name="topbar_deviceid_font_family" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                                                <option value="Questrial" <?php echo $topbarDeviceIdFontFamily == 'Questrial' ? 'selected' : ''; ?> style="font-family: Questrial">Questrial</option>
                                                <option value="Arial" <?php echo $topbarDeviceIdFontFamily == 'Arial' ? 'selected' : ''; ?> style="font-family: Arial">Arial</option>
                                                <option value="Helvetica" <?php echo $topbarDeviceIdFontFamily == 'Helvetica' ? 'selected' : ''; ?> style="font-family: Helvetica">Helvetica</option>
                                                <option value="Verdana" <?php echo $topbarDeviceIdFontFamily == 'Verdana' ? 'selected' : ''; ?> style="font-family: Verdana">Verdana</option>
                                                <option value="Times New Roman" <?php echo $topbarDeviceIdFontFamily == 'Times New Roman' ? 'selected' : ''; ?> style="font-family: Times New Roman">Times New Roman</option>
                                                <option value="Georgia" <?php echo $topbarDeviceIdFontFamily == 'Georgia' ? 'selected' : ''; ?> style="font-family: Georgia">Georgia</option>
                                                <option value="Courier New" <?php echo $topbarDeviceIdFontFamily == 'Courier New' ? 'selected' : ''; ?> style="font-family: Courier New">Courier New</option>
                                                <option value="Libre Baskerville" <?php echo $topbarDeviceIdFontFamily == 'Libre Baskerville' ? 'selected' : ''; ?> style="font-family: Libre Baskerville">Libre Baskerville</option>
                                            </select>
                                            <label for="topbar_deviceid_font_family" class="floating-label">Device ID Font Family</label>
                                        </div>
                                        <div class="line-separator" style="margin: 0px;"></div>
                                        <p style="color: #264B2B; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;"><b>Time</b></p>
                                        <div class="split-container" style="margin-bottom: 0">
                                            <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px; ">
                                                <input type="color" id="topbar_time_color" name="topbar_time_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="<?php echo htmlspecialchars($topbarTimeColor); ?>">
                                                <label for="topbar_time_color" class="floating-label">Font Color</label>
                                            </div>
                                            <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                                                <select id="topbar_time_font_style" name="topbar_time_font_style" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                                                    <option value="normal" <?php echo $topbarTimeFontStyle == 'normal' ? 'selected' : ''; ?>>Normal</option>
                                                    <option value="italic" <?php echo $topbarTimeFontStyle == 'italic' ? 'selected' : ''; ?> style="font-style: italic">Italic</option>
                                                </select>
                                                <label for="topbar_time_font_style" class="floating-label">Font Style</label>
                                            </div>
                                        </div>
                                        <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                                            <select id="topbar_time_font_family" name="topbar_time_font_family" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                                                <option value="Questrial" <?php echo $topbarTimeFontFamily == 'Questrial' ? 'selected' : ''; ?> style="font-family: Questrial">Questrial</option>
                                                <option value="Arial" <?php echo $topbarTimeFontFamily == 'Arial' ? 'selected' : ''; ?> style="font-family: Arial">Arial</option>
                                                <option value="Helvetica" <?php echo $topbarTimeFontFamily == 'Helvetica' ? 'selected' : ''; ?> style="font-family: Helvetica">Helvetica</option>
                                                <option value="Verdana" <?php echo $topbarTimeFontFamily == 'Verdana' ? 'selected' : ''; ?> style="font-family: Verdana">Verdana</option>
                                                <option value="Times New Roman" <?php echo $topbarTimeFontFamily == 'Times New Roman' ? 'selected' : ''; ?> style="font-family: Times New Roman">Times New Roman</option>
                                                <option value="Georgia" <?php echo $topbarTimeFontFamily == 'Georgia' ? 'selected' : ''; ?> style="font-family: Georgia">Georgia</option>
                                                <option value="Courier New" <?php echo $topbarTimeFontFamily == 'Courier New' ? 'selected' : ''; ?> style="font-family: Courier New">Courier New</option>
                                                <option value="Libre Baskerville" <?php echo $topbarTimeFontFamily == 'Libre Baskerville' ? 'selected' : ''; ?> style="font-family: Libre Baskerville">Libre Baskerville</option>
                                            </select>
                                            <label for="topbar_time_font_family" class="floating-label">Time Font Family</label>
                                        </div>
                                        <div class="line-separator" style="margin: 0px;"></div>
                                        <p style="color: #264B2B; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;"><b>Date</b></p>
                                        <div class="split-container" style="margin-bottom: 0">
                                            <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px; ">
                                                <input type="color" id="topbar_date_color" name="topbar_date_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="<?php echo htmlspecialchars($topbarDateColor); ?>">
                                                <label for="topbar_date_color" class="floating-label">Font Color</label>
                                            </div>
                                            <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                                                <select id="topbar_date_font_style" name="topbar_date_font_style" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                                                    <option value="normal" <?php echo $topbarDateFontStyle == 'normal' ? 'selected' : ''; ?>>Normal</option>
                                                    <option value="italic" <?php echo $topbarDateFontStyle == 'italic' ? 'selected' : ''; ?> style="font-style: italic">Italic</option>
                                                </select>
                                                <label for="topbar_date_font_style" class="floating-label">Font Style</label>
                                            </div>
                                        </div>
                                        <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                                            <select id="topbar_date_font_family" name="topbar_date_font_family" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                                                <option value="Questrial" <?php echo $topbarDateFontFamily == 'Questrial' ? 'selected' : ''; ?> style="font-family: Questrial">Questrial</option>
                                                <option value="Arial" <?php echo $topbarDateFontFamily == 'Arial' ? 'selected' : ''; ?> style="font-family: Arial">Arial</option>
                                                <option value="Helvetica" <?php echo $topbarDateFontFamily == 'Helvetica' ? 'selected' : ''; ?> style="font-family: Helvetica">Helvetica</option>
                                                <option value="Verdana" <?php echo $topbarDateFontFamily == 'Verdana' ? 'selected' : ''; ?> style="font-family: Verdana">Verdana</option>
                                                <option value="Times New Roman" <?php echo $topbarDateFontFamily == 'Times New Roman' ? 'selected' : ''; ?> style="font-family: Times New Roman">Times New Roman</option>
                                                <option value="Georgia" <?php echo $topbarDateFontFamily == 'Georgia' ? 'selected' : ''; ?> style="font-family: Georgia">Georgia</option>
                                                <option value="Courier New" <?php echo $topbarDateFontFamily == 'Courier New' ? 'selected' : ''; ?> style="font-family: Courier New">Courier New</option>
                                                <option value="Libre Baskerville" <?php echo $topbarDateFontFamily == 'Libre Baskerville' ? 'selected' : ''; ?> style="font-family: Libre Baskerville">Libre Baskerville</option>
                                            </select>
                                            <label for="topbar_date_font_family" class="floating-label">Date Font Family</label>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <!-- Background Customization Left-side panel -->
                            <div id="backgroundLeftSidePanel" class="left-side-panel">
                                <div class="content-container" style="background: #f9fffa; position: fixed; width: 245px">
                                    <div class="panel-close-btn">
                                        <button onclick="closeBackgroundLeftSidePanel()"><i class="fa fa-angle-left" aria-hidden="true"></i> Collapse</button>
                                    </div>
                                    <p style="color: #264B2B; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;"><b>Customize Background</b></p>
                                    <form id="editBackgroundColorForm" enctype="multipart/form-data" style="height: 57vh; overflow-y: auto; overflow-x: hidden; padding-right: 10px">                                        
                                        <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px; ">
                                            <input type="color" id="background_color" name="background_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="<?php echo htmlspecialchars($backgroundColor); ?>">
                                            <label for="background_color" class="floating-label">TV Background Color</label>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <!-- Containers Customization Left-side panel -->
                            <div id="contentContainerLeftSidePanel" class="left-side-panel">
                                <div class="content-container" style="background: #f9fffa; overflow: hidden; position: fixed; width: 245px">
                                    <div class="panel-close-btn">
                                        <button onclick="closeContainerLeftSidePanel()"><i class="fa fa-angle-left" aria-hidden="true"></i> Collapse</button>
                                    </div>
                                    <p style="color: #264B2B; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;"><b>Customize Containers</b></p>
                                    <form id="editContentContainerForm" enctype="multipart/form-data" style="height: 57vh; overflow: hidden;">
                                        <!-- Dynamic content will be added here -->
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        let ws;
        var containers = <?php echo json_encode($containers); ?>;
        const contentContainerForm = document.getElementById('editContentContainerForm');

        function closeTopbarLeftSidePanel() {
            topbarLeftSidePanel.classList.remove('open');
            const scaleButtons = document.querySelector('.scale-buttons-2');
            scaleButtons.classList.remove('open');
            const openSidebarButton = document.querySelector('.open-sidebar-button');
            openSidebarButton.classList.add('open');
            const closeSidebarButton = document.querySelector('.close-sidebar-button');
            closeSidebarButton.classList.remove('open');
        }

        function closeBackgroundLeftSidePanel() {
            backgroundLeftSidePanel.classList.remove('open');
            const scaleButtons = document.querySelector('.scale-buttons-2');
            scaleButtons.classList.remove('open');
            const openSidebarButton = document.querySelector('.open-sidebar-button');
            openSidebarButton.classList.add('open');
            const closeSidebarButton = document.querySelector('.close-sidebar-button');
            closeSidebarButton.classList.remove('open');
        }

        function closeContainerLeftSidePanel() {
            contentContainerLeftSidePanel.classList.remove('open');
            const scaleButtons = document.querySelector('.scale-buttons-2');
            scaleButtons.classList.remove('open');
            const openSidebarButton = document.querySelector('.open-sidebar-button');
            openSidebarButton.classList.add('open');
            const closeSidebarButton = document.querySelector('.close-sidebar-button');
            closeSidebarButton.classList.remove('open');
        }

        function openSidebar() {
            const leftSidebar = document.getElementById('leftSidebar');
            const scaleButtons = document.querySelector('.scale-buttons-2');
            const openSidebarButton = document.getElementById('openSidebarButton');
            const closeSidebarButton = document.getElementById('closeSidebarButton');

            leftSidebar.classList.add('open');
            scaleButtons.classList.add('open');
            openSidebarButton.classList.remove('open');
            closeSidebarButton.classList.add('open');
        }

        function closeSidebar() {
            const leftSidebar = document.getElementById('leftSidebar');
            const scaleButtons = document.querySelector('.scale-buttons-2');
            const openSidebarButton = document.getElementById('openSidebarButton');
            const closeSidebarButton = document.getElementById('closeSidebarButton');

            leftSidebar.classList.remove('open');
            scaleButtons.classList.remove('open');
            openSidebarButton.classList.add('open');
            closeSidebarButton.classList.remove('open');
        }
        // Function to initialize WebSocket connection
        function initializeWebSocket() {
            fetch('websocket_conn.php')
                .then(response => response.text())
                .then(url => {
                    ws = new WebSocket(url);

                    ws.onopen = () => console.log("WebSocket connection established.");

                    ws.onmessage = function (event) {
                        const message = JSON.parse(event.data);
                    };

                    ws.onerror = function (error) {
                        console.error('WebSocket Error:', error);
                    };
                })
                .catch(error => {
                    console.error('Error fetching WebSocket URL:', error);
                });
        }
            
        initializeWebSocket();

        document.getElementById('updateTemplateButton').addEventListener('click', function() {
            const iframe = document.getElementById('tv-iframe');
            iframe.contentWindow.postMessage({action: 'updateTemplate'}, '*');
        });

        // Add an event listener for messages from the iframe
        window.addEventListener('message', function(event) {
            if (event.data.action === 'containerPositionsUpdated') {
                const positions = event.data.positions;
                updateContainerPositionsOnServer(positions);
            }
        });

        let templateChanged = false;
        const updateTemplateButton = document.getElementById('updateTemplateButton');

        function showUpdateButton() {
            updateTemplateButton.style.display = 'inline-block';
            templateChanged = true;
        }

        // Add event listener for messages from tv2.php iframe
        window.addEventListener('message', function(event) {
            if (event.data.action === 'containerMoved') {
                showUpdateButton();
            } else if (event.data.action === 'containerPositionsUpdated') {
                updateContainerPositionsOnServer(event.data.positions);
            }
        });

        // Add event listener for the Update Template button
        updateTemplateButton.addEventListener('click', function() {
            const iframe = document.getElementById('tv-iframe');
            iframe.contentWindow.postMessage({action: 'updateTemplate'}, '*');
            templateChanged = false;
            updateTemplateButton.style.display = 'none';
        });

        // Add beforeunload event listener
        window.addEventListener('beforeunload', function (e) {
            if (templateChanged) {
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        });

        function updateContainerPositionsOnServer(positions) {
            // Assuming you have a WebSocket connection established
            if (ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    action: 'update_container_positions',
                    tv_id: <?php echo $tvId; ?>,
                    positions: positions
                }));
                templateChanged = false;
                updateTemplateButton.style.display = 'none';
            } else {
                console.error('WebSocket is not open');
            }
        }

        function openContentContainerLeftSidePanel(containerId) {
            closeTopbarLeftSidePanel();  // Close the top bar panel if it's open
            closeBackgroundLeftSidePanel();  // Close the background panel if it's open
            contentContainerLeftSidePanel.classList.add('open');
            closeSidebar();
            const scaleButtons = document.querySelector('.scale-buttons-2');
            scaleButtons.classList.add('open');
            const openSidebarButton = document.getElementById('openSidebarButton');
            openSidebarButton.classList.remove('open');

            editContentContainerForm.innerHTML = '';

            // Fetch and display the specific container details
            const container = containers.find(c => c.container_id == containerId);
            if (container) {
                // Create and append the container details dynamically
                const containerDiv = document.createElement('div');
                containerDiv.innerHTML = `
                    <p style="text-align: center; color: #264B2B; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;"><b>${container.container_name}</b></p>
                    <div style="overflow-y: auto; overflow-x: hidden; height: 54vh; padding-right: 10px">
                        <p style="color: #264B2B; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;"><b>Parent Container</b></p>
                        <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px;">
                            <input type="color" id="container_${container.container_id}_bg_color" name="container_${container.container_id}_bg_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="${container.parent_background_color}">
                            <label for="container_${container.container_id}_bg_color" class="floating-label">Background Color</label>
                        </div>
                        <div class="split-container" style="margin-bottom: 0">
                            <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px;">
                                <input type="color" id="container_${container.container_id}_font_color" name="container_${container.container_id}_font_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="${container.parent_font_color}">
                                <label for="container_${container.container_id}_font_color" class="floating-label">Font Color</label>
                            </div>
                            <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px;">
                                <select id="container_${container.container_id}_fontstyle" name="container_${container.container_id}_fontstyle" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                                    <option value="normal" ${container.parent_font_style == 'normal' ? 'selected' : ''}>Normal</option>
                                    <option value="italic" ${container.parent_font_style == 'italic' ? 'selected' : ''} style="font-style: italic">Italic</option>
                                </select>
                                <label for="container_${container.container_id}_fontstyle" class="floating-label">Font Style</label>
                            </div>
                        </div>
                        <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                            <select id="container_${container.container_id}_fontfamily" name="container_${container.container_id}_fontfamily" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                                <option value="Questrial" ${container.parent_font_family == 'Questrial' ? 'selected' : ''} style="font-family: Questrial">Questrial</option>
                                <option value="Arial" ${container.parent_font_family == 'Arial' ? 'selected' : ''} style="font-family: Arial">Arial</option>
                                <option value="Helvetica" ${container.parent_font_family == 'Helvetica' ? 'selected' : ''} style="font-family: Helvetica">Helvetica</option>
                                <option value="Verdana" ${container.parent_font_family == 'Verdana' ? 'selected' : ''} style="font-family: Verdana">Verdana</option>
                                <option value="Times New Roman" ${container.parent_font_family == 'Times New Roman' ? 'selected' : ''} style="font-family: Times New Roman">Times New Roman</option>
                                <option value="Georgia" ${container.parent_font_family == 'Georgia' ? 'selected' : ''} style="font-family: Georgia">Georgia</option>
                                <option value="Courier New" ${container.parent_font_family == 'Courier New' ? 'selected' : ''} style="font-family: Courier New">Courier New</option>
                                <option value="Libre Baskerville" ${container.parent_font_family == 'Libre Baskerville' ? 'selected' : ''} style="font-family: Libre Baskerville">Libre Baskerville</option>
                            </select>
                            <label for="container_${container.container_id}_fontfamily" class="floating-label">Font Family</label>
                        </div>
                        <div class="line-separator" style="margin: 0px"></div>
                        <p style="color: #264B2B; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;"><b>Card Container</b></p>
                        <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px;">
                            <input type="color" id="container_${container.container_id}_card_bg_color" name="container_${container.container_id}_card_bg_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="${container.child_background_color}">
                            <label for="container_${container.container_id}_card_bg_color" class="floating-label">Card Background Color</label>
                        </div>                        
                        <div class="split-container" style="margin-bottom: 0">
                            <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px;">
                                <input type="color" id="container_${container.container_id}_fcard_color" name="container_${container.container_id}_fcard_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="${container.child_font_color}">
                                <label for="container_${container.container_id}_fcard_color" class="floating-label">Card Font Color</label>
                            </div>
                            <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px;">
                                <select id="container_${container.container_id}_fcardstyle" name="container_${container.container_id}_fcardstyle" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                                    <option value="normal" ${container.child_font_style == 'normal' ? 'selected' : ''}>Normal</option>
                                    <option value="italic" ${container.child_font_style == 'italic' ? 'selected' : ''} style="font-style: italic">Italic</option>
                                </select>
                                <label for="container_${container.container_id}_fcardstyle" class="floating-label">Card Font Style</label>
                            </div>
                        </div>                        
                        <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                            <select id="container_${container.container_id}_fcardfamily" name="container_${container.container_id}_fcardfamily" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                                <option value="Questrial" ${container.child_font_family == 'Questrial' ? 'selected' : ''} style="font-family: Questrial">Questrial</option>
                                <option value="Arial" ${container.child_font_family == 'Arial' ? 'selected' : ''} style="font-family: Arial">Arial</option>
                                <option value="Helvetica" ${container.child_font_family == 'Helvetica' ? 'selected' : ''} style="font-family: Helvetica">Helvetica</option>
                                <option value="Verdana" ${container.child_font_family == 'Verdana' ? 'selected' : ''} style="font-family: Verdana">Verdana</option>
                                <option value="Times New Roman" ${container.child_font_family == 'Times New Roman' ? 'selected' : ''} style="font-family: Times New Roman">Times New Roman</option>
                                <option value="Georgia" ${container.child_font_family == 'Georgia' ? 'selected' : ''} style="font-family: Georgia">Georgia</option>
                                <option value="Courier New" ${container.child_font_family == 'Courier New' ? 'selected' : ''} style="font-family: Courier New">Courier New</option>
                                <option value="Libre Baskerville" ${container.child_font_family == 'Libre Baskerville' ? 'selected' : ''} style="font-family: Libre Baskerville">Libre Baskerville</option>
                            </select>
                            <label for="container_${container.container_id}_fcardfamily" class="floating-label">Card Font Family</label>
                        </div>
                    </div>
                `;
                contentContainerForm.appendChild(containerDiv);

                // Automatically submit the content container form when a color changes
                contentContainerForm.querySelectorAll('input[type="color"], select').forEach(input => {
                    input.addEventListener('input', () => {
                        const colorsData = {};
                        contentContainerForm.querySelectorAll('input[type="color"], select').forEach(colorInput => {
                            const containerId = colorInput.id.split('_')[1];
                            const colorType = colorInput.id.split('_')[2];

                            if (!colorsData[containerId]) {
                                colorsData[containerId] = {};
                            }

                            // Use `bg_color` and `font_color` instead of `bg` and `font`
                            if (colorType === 'bg') {
                                colorsData[containerId]['bg_color'] = colorInput.value;
                            } else if (colorType === 'font') {
                                colorsData[containerId]['font_color'] = colorInput.value;
                            } else if (colorType === 'card') {
                                colorsData[containerId]['card_bg_color'] = colorInput.value;
                            } else if (colorType === 'fcard') {
                                colorsData[containerId]['fcard_color'] = colorInput.value;
                            } else if (colorType === 'fcardstyle') {
                                colorsData[containerId]['fcardstyle'] = colorInput.value;
                            } else if (colorType === 'fcardfamily') {
                                colorsData[containerId]['fcardfamily'] = colorInput.value;
                            } else if (colorType === 'fontfamily') {
                                colorsData[containerId]['fontfamily'] = colorInput.value;
                            } else if (colorType === 'fontstyle') {
                                colorsData[containerId]['fontstyle'] = colorInput.value;
                            }
                        });

                        const data = {
                            action: 'update_container_colors',
                            tv_id: <?php echo $tvId; ?>,
                            containers: colorsData
                        };
                        ws.send(JSON.stringify(data));
                    });
                });
            } else {
                console.error('Container not found for ID:', containerId); // Debugging line
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const backgroundColorForm = document.getElementById('editBackgroundColorForm');
            const topBarColorForm = document.getElementById('editTopBarColorForm');
            const visibilityForm = document.getElementById('visibilitySettingsForm');

            const iframe = document.getElementById('tv-iframe');
            const topbarLeftSidePanel = document.getElementById('topbarLeftSidePanel');
            const backgroundLeftSidePanel = document.getElementById('backgroundLeftSidePanel');
            const contentContainerLeftSidePanel = document.getElementById('contentContainerLeftSidePanel');

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

            // Function to open and close the left side panels
            function openTopbarLeftSidePanel() {
                closeBackgroundLeftSidePanel();  // Close the background panel if it's open
                closeContainerLeftSidePanel();  // Close the container panel if it's open
                closeSidebar();
                topbarLeftSidePanel.classList.add('open');
                const scaleButtons = document.querySelector('.scale-buttons-2');
                scaleButtons.classList.add('open');
                const openSidebarButton = document.querySelector('.open-sidebar-button');
                openSidebarButton.classList.remove('open');
                const closeSidebarButton = document.querySelector('.close-sidebar-button');
                closeSidebarButton.classList.remove('open');
            }

            function openBackgroundLeftSidePanel() {
                closeTopbarLeftSidePanel();  // Close the top bar panel if it's open
                closeContainerLeftSidePanel();  // Close the container panel if it's open
                closeSidebar();
                backgroundLeftSidePanel.classList.add('open');
                const scaleButtons = document.querySelector('.scale-buttons-2');
                scaleButtons.classList.add('open');
                const openSidebarButton = document.querySelector('.open-sidebar-button');
                openSidebarButton.classList.remove('open');
                const closeSidebarButton = document.querySelector('.close-sidebar-button');
                closeSidebarButton.classList.remove('open');
            }

            // Add event listeners to the iframe content
            iframe.onload = function () {
                const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;

                // Click event for the top bar
                iframeDocument.getElementById('topbar').addEventListener('dblclick', function () {
                    openTopbarLeftSidePanel();
                });

                // Click event for the background
                iframeDocument.getElementById('tvBackgroundColor').addEventListener('dblclick', function () {
                    openBackgroundLeftSidePanel();
                });

                var contentDocument = iframe.contentDocument || iframe.contentWindow.document;
                // Click event for the containers
                var containersElements = iframeDocument.querySelectorAll('.content-container');
                containersElements.forEach(function(container) {
                    container.addEventListener('click', function() {
                        const containerId = container.dataset.containerId; // Use data-container-id
                        openContentContainerLeftSidePanel(containerId);
                    });
                });
            };
            
            // Automatically submit the backgroundColorForm when the color changes
            const backgroundColorInput = document.getElementById('background_color');
            backgroundColorInput.addEventListener('input', () => {
                const formData = new FormData(backgroundColorForm);
                const data = {
                    action: 'update_background_color',
                    tv_id: <?php echo $tvId; ?>,
                    background_hex_color: formData.get('background_color')
                };

                formData.forEach((value, key) => {
                    data[key] = value;
                });

                ws.send(JSON.stringify(data));
            });

            const topbarFields = [
                'topbar_color','topbar_tvname_color', 'topbar_deviceid_color', 
                'topbar_time_color', 'topbar_date_color', 'topbar_tvname_font_style', 
                'topbar_tvname_font_family', 'topbar_deviceid_font_style', 
                'topbar_deviceid_font_family', 'topbar_time_font_style', 
                'topbar_time_font_family', 'topbar_date_font_style', 
                'topbar_date_font_family', 'topbar_position',
            ];

            // Function to send WebSocket message
            function sendTopbarUpdate() {
                const formData = new FormData(topBarColorForm);
                ws.send(JSON.stringify({
                    action: 'update_topbar_color',
                    tv_id: <?php echo $tvId; ?>,
                    topbar_hex_color: formData.get('topbar_color'),
                    topbar_tvname_font_color: formData.get('topbar_tvname_color'),
                    topbar_tvname_font_style: formData.get('topbar_tvname_font_style'),
                    topbar_tvname_font_family: formData.get('topbar_tvname_font_family'),
                    topbar_deviceid_font_color: formData.get('topbar_deviceid_color'),
                    topbar_deviceid_font_style: formData.get('topbar_deviceid_font_style'),
                    topbar_deviceid_font_family: formData.get('topbar_deviceid_font_family'),
                    topbar_time_font_color: formData.get('topbar_time_color'),
                    topbar_time_font_style: formData.get('topbar_time_font_style'),
                    topbar_time_font_family: formData.get('topbar_time_font_family'),
                    topbar_date_font_color: formData.get('topbar_date_color'),
                    topbar_date_font_style: formData.get('topbar_date_font_style'),
                    topbar_date_font_family: formData.get('topbar_date_font_family'),
                    topbar_position: formData.get('topbar_position')
                }));
            }

            // Attach event listeners to all relevant fields
            topbarFields.forEach(id => {
                const element = topBarColorForm.querySelector(`#${id}`);
                if (element) {  // Check if the element exists
                    element.addEventListener('input', sendTopbarUpdate);
                    element.addEventListener('change', sendTopbarUpdate);
                } 
            });

            // Automatically submit the visibility form when changes are made
            visibilityForm.querySelectorAll('input[type="checkbox"]').forEach(input => {
                input.addEventListener('change', () => {
                    const containersData = {};
                    visibilityForm.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                        const containerId = checkbox.id.split('_')[1];
                        containersData[containerId] = checkbox.checked ? 1 : 0;
                    });

                    const data = {
                        action: 'show_hide_content',
                        tv_id: <?php echo $tvId; ?>,
                        containers: containersData
                    };

                    ws.send(JSON.stringify(data));
                });
            });
        });
    </script>
</body>
</html>