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
$topbarPosition = '';

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
        $topbarPosition = $topbarColorData['topbar_position'];

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
<body>
    <div class="main-section" id="all-content">
        <?php include('top_header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div class="main-container">
            <div class="column1">
                <div class="content-inside-form">
                    <h1 class="content-title" style="color: black"><i class="fa fa-pencil-square" style="padding-right: 5px"></i>Edit Template</h1>
                    <div class="content-form">
                        <div class="left-side-button">
                            <button type="button" class="back-button" onclick="javascript:history.back()"><i class="fa fa-arrow-left" style="padding-right: 5px"></i>Back</button>
                        </div>
                        <div class="line-separator"></div>
                        <?php include('error_message.php'); ?>
                        <div>
                            <!-- Display iframe based on tvId -->
                            <div class="tv-frame">
                                <iframe id="tv-iframe" src="tv.php?tvId=<?php echo $tvId?>" class="tv-screen" style="height: <?php echo $tvHeight?>px; width: <?php echo $tvWidth?>px"></iframe>
                            </div>
                            <div class="line-separator"></div>
                            <div class="form-row">
                                <div class="form-column" style="flex: 1; border: 1px black solid; border-radius: 5px">
                                    <p style="background: #264B2B; color: white; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;">Show/Hide Content Containers</p>
                                    <form id="visibilitySettingsForm" style="padding: 8px;">
                                        <?php foreach ($containers as $container): ?>
                                            <div class="option-div">
                                                <input type="checkbox" id="container_<?php echo $container['container_id']; ?>" name="container_<?php echo $container['container_id']; ?>" <?php echo $container['visible'] ? 'checked' : ''; ?>>
                                                <label for="container_<?php echo $container['container_id']; ?>"> Show <?php echo htmlspecialchars($container['container_name']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Top Bar Customization Right-side panel -->
        <div id="topbarRightSidePanel" class="right-side-panel">
            <div class="panel-close-btn">
                <button onclick="closeTopbarRightSidePanel()"><i class="fa fa-times-circle-o" aria-hidden="true"></i></button>
            </div>
            <h3>Customize Top Bar</h3>
            <br>
            <form id="editTopBarColorForm" enctype="multipart/form-data" style="padding-bottom: 45px">
                <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px; ">
                    <input type="color" id="topbar_color" name="topbar_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="<?php echo htmlspecialchars($topbarColor); ?>">
                    <label for="topbar_color" class="floating-label">Top Bar Color</label>
                </div>
                <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                    <select id="topbar_position" name="topbar_position" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                        <option value="top" <?php echo $topbarPosition == 'top' ? 'selected' : ''; ?>>Top</option>
                        <option value="bottom" <?php echo $topbarPosition == 'bottom' ? 'selected' : ''; ?>>Bottom</option>
                    </select>
                    <label for="topbar_position" class="floating-label">Top Bar Position</label>
                </div>
                <div class="line-separator" style="margin-top: 0"></div>
                <div class="split-container" style="margin-bottom: 0">
                    <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px; ">
                        <input type="color" id="topbar_tvname_color" name="topbar_tvname_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="<?php echo htmlspecialchars($topbarTvNameColor); ?>">
                        <label for="topbar_tvname_color" class="floating-label">TV Name Font Color</label>
                    </div>
                    <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                        <select id="topbar_tvname_font_style" name="topbar_tvname_font_style" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                            <option value="normal" <?php echo $topbarTvNameFontStyle == 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="italic" <?php echo $topbarTvNameFontStyle == 'italic' ? 'selected' : ''; ?> style="font-style: italic">Italic</option>
                        </select>
                        <label for="topbar_tvname_font_style" class="floating-label">TV Name Font Style</label>
                    </div>
                </div>
                <div class="split-container" style="margin-bottom: 0">
                    <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px; ">
                        <input type="color" id="topbar_deviceid_color" name="topbar_deviceid_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="<?php echo htmlspecialchars($topbarDeviceIdColor); ?>">
                        <label for="topbar_deviceid_color" class="floating-label">Device ID Font Color</label>
                    </div>
                    <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                        <select id="topbar_deviceid_font_style" name="topbar_deviceid_font_style" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                            <option value="normal" <?php echo $topbarDeviceIdFontStyle == 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="italic" <?php echo $topbarDeviceIdFontStyle == 'italic' ? 'selected' : ''; ?> style="font-style: italic">Italic</option>
                        </select>
                        <label for="topbar_deviceid_font_style" class="floating-label">Device ID Font Style</label>
                    </div>
                </div>
                <div class="split-container" style="margin-bottom: 0">
                    <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px; ">
                        <input type="color" id="topbar_time_color" name="topbar_time_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="<?php echo htmlspecialchars($topbarTimeColor); ?>">
                        <label for="topbar_time_color" class="floating-label">Time Font Color</label>
                    </div>
                    <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                        <select id="topbar_time_font_style" name="topbar_time_font_style" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                            <option value="normal" <?php echo $topbarTimeFontStyle == 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="italic" <?php echo $topbarTimeFontStyle == 'italic' ? 'selected' : ''; ?> style="font-style: italic">Italic</option>
                        </select>
                        <label for="topbar_time_font_style" class="floating-label">Time Font Style</label>
                    </div>
                </div>
                <div class="split-container" style="margin-bottom: 0">
                    <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px; ">
                        <input type="color" id="topbar_date_color" name="topbar_date_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="<?php echo htmlspecialchars($topbarDateColor); ?>">
                        <label for="topbar_date_color" class="floating-label">Date Font Color</label>
                    </div>
                    <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px;">
                        <select id="topbar_date_font_style" name="topbar_date_font_style" class="floating-label-input" style="background: #f3f3f3; box-shadow: none; height: 100%;">
                            <option value="normal" <?php echo $topbarDateFontStyle == 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="italic" <?php echo $topbarDateFontStyle == 'italic' ? 'selected' : ''; ?> style="font-style: italic">Italic</option>
                        </select>
                        <label for="topbar_date_font_style" class="floating-label">Date Font Style</label>
                    </div>
                </div>
                <div class="line-separator" style="margin-top: 0"></div>
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
                    <label for="topbar_tvname_font_family" class="floating-label">TV Name Font Family</label>
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
        <!-- Background Customization Right-side panel -->
        <div id="backgroundRightSidePanel" class="right-side-panel">
            <div class="panel-close-btn">
                <button onclick="closeBackgroundRightSidePanel()"><i class="fa fa-times-circle-o" aria-hidden="true"></i></button>
            </div>
            <br>
            <h3>Customize Background</h3>
            <br>
            <form id="editBackgroundColorForm" enctype="multipart/form-data" style="padding: 8px;">                                        
                <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px; ">
                    <input type="color" id="background_color" name="background_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="<?php echo htmlspecialchars($backgroundColor); ?>">
                    <label for="background_color" class="floating-label">TV Background Color</label>
                </div>
            </form>
        </div>
        <!-- Containers Customization Right-side panel -->
        <div id="contentContainerRightSidePanel" class="right-side-panel">
            <div class="panel-close-btn">
                <button onclick="closeContainerRightSidePanel()"><i class="fa fa-times-circle-o" aria-hidden="true"></i></button>
            </div>
            <br>
            <h3>Customize Container</h3>
            <br>
            <form id="editContentContainerForm" enctype="multipart/form-data">
                <!-- Dynamic content will be added here -->
            </form>
        </div>
    </div>
    <script>
        let ws;
        var containers = <?php echo json_encode($containers); ?>;
        const contentContainerForm = document.getElementById('editContentContainerForm');

        function closeTopbarRightSidePanel() {
            topbarRightSidePanel.classList.remove('open');
        }

        function closeBackgroundRightSidePanel() {
            backgroundRightSidePanel.classList.remove('open');
        }

        function closeContainerRightSidePanel() {
            contentContainerRightSidePanel.classList.remove('open');
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

        function openContentContainerRightSidePanel(containerId) {
            closeTopbarRightSidePanel();  // Close the top bar panel if it's open
            closeBackgroundRightSidePanel();  // Close the background panel if it's open
            contentContainerRightSidePanel.classList.add('open');

            editContentContainerForm.innerHTML = '';

            // Fetch and display the specific container details
            const container = containers.find(c => c.container_id == containerId);
            if (container) {
                // Create and append the container details dynamically
                const containerDiv = document.createElement('div');
                containerDiv.className = 'option-div-container-color';
                containerDiv.innerHTML = `
                    <p style="background: #264B2B; color: white; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;">${container.container_name}</p>
                    <div style="padding: 8px;">
                    <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px;">
                        <input type="color" id="container_${container.container_id}_bg_color" name="container_${container.container_id}_bg_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="${container.parent_background_color}">
                        <label for="container_${container.container_id}_bg_color" class="floating-label">Background Color</label>
                    </div>
                    <div class="floating-label-container" style="margin-top: 0; margin-bottom: 10px; width: 100%; height: 60px; background: #f3f3f3; margin-right: 5px; border-radius: 5px;">
                        <input type="color" id="container_${container.container_id}_card_bg_color" name="container_${container.container_id}_card_bg_color" class="floating-label-input" style="height: 100%; width: 100%; background: none; box-shadow: none; padding-right: 12px" value="${container.child_background_color}">
                        <label for="container_${container.container_id}_card_bg_color" class="floating-label">Card Background Color</label>
                    </div>
                    <div class="line-separator" style="margin-top: 0; margin-left: 12px; margin-right: 12px"></div>
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
                    <div class="line-separator" style="margin-top: 0; margin-left: 12px; margin-right: 12px"></div>
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
            const topbarRightSidePanel = document.getElementById('topbarRightSidePanel');
            const backgroundRightSidePanel = document.getElementById('backgroundRightSidePanel');
            const contentContainerRightSidePanel = document.getElementById('contentContainerRightSidePanel');

            // Function to open and close the right side panels
            function openTopbarRightSidePanel() {
                closeBackgroundRightSidePanel();  // Close the background panel if it's open
                closeContainerRightSidePanel();  // Close the container panel if it's open
                topbarRightSidePanel.classList.add('open');
            }

            function openBackgroundRightSidePanel() {
                closeTopbarRightSidePanel();  // Close the top bar panel if it's open
                closeContainerRightSidePanel();  // Close the container panel if it's open
                backgroundRightSidePanel.classList.add('open');
            }

            // Add event listeners to the iframe content
            iframe.onload = function () {
                const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;

                // Click event for the top bar
                iframeDocument.getElementById('topbar').addEventListener('click', function () {
                    openTopbarRightSidePanel();
                });

                // Click event for the background
                iframeDocument.getElementById('tvBackgroundColor').addEventListener('click', function () {
                    openBackgroundRightSidePanel();
                });

                var contentDocument = iframe.contentDocument || iframe.contentWindow.document;
                // Click event for the containers
                var containersElements = iframeDocument.querySelectorAll('.content-container');
                containersElements.forEach(function(container) {
                    container.addEventListener('click', function() {
                        const containerId = container.dataset.containerId; // Use data-container-id
                        openContentContainerRightSidePanel(containerId);
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
                element.addEventListener('input', sendTopbarUpdate);
                element.addEventListener('change', sendTopbarUpdate);
            });

            // Automatically submit the visibility form when changes are made
            visibilityForm.querySelectorAll('input[type="checkbox"]').forEach(input => {
                input.addEventListener('change', () => {
                    const formData = new FormData(visibilityForm);

                    // Loop through the checkboxes to gather their states
                    const containersData = {};
                    visibilityForm.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                        const containerId = checkbox.id.split('_')[1];
                        containersData[containerId] = checkbox.checked ? 1 : 0;
                    });

                    const data = {
                        action: 'show_hide_content',
                        tv_id: <?php echo $tvId; ?>,
                        containers: containersData // Change key from "visible" to "containers"
                    };

                    ws.send(JSON.stringify(data));
                });
            });
        });
    </script>
</body>
</html>