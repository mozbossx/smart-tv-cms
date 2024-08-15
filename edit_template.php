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
$topbarDeviceIdColor = '';
$topbarTimeColor = '';
$topbarDateColor = '';

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
        $topbarDeviceIdColor = $topbarColorData['topbar_deviceid_font_color'];
        $topbarTimeColor = $topbarColorData['topbar_time_font_color'];
        $topbarDateColor = $topbarColorData['topbar_date_font_color'];
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
                                <iframe src="tv.php?tvId=<?php echo $tvId?>" class="tv-screen" style="height: <?php echo $tvHeight?>px; width: <?php echo $tvWidth?>px"></iframe>
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
                                <div class="form-column" style="flex: 1; border: 1px black solid; border-radius: 5px">
                                    <p style="background: #264B2B; color: white; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;">Customize Background</p>
                                    <form id="editBackgroundColorForm" enctype="multipart/form-data" style="padding: 8px;">                                        
                                        <div class="option-div-color">
                                            <input type="color" id="background_color" name="background_color" value="<?php echo htmlspecialchars($backgroundColor); ?>">
                                            <label for="background_color"> TV Background Color</label>
                                        </div>
                                    </form>
                                </div>
                                <div class="form-column" style="flex: 1; border: 1px black solid; border-radius: 5px">
                                    <p style="background: #264B2B; color: white; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;">Customize Top Bar</p>
                                    <form id="editTopBarColorForm" enctype="multipart/form-data" style="padding: 8px;">
                                        <div class="option-div-color">
                                            <input type="color" id="topbar_color" name="topbar_color" value="<?php echo htmlspecialchars($topbarColor); ?>">
                                            <label for="topbar_color"> Top Bar Color</label>
                                        </div>
                                        <div class="option-div-color">
                                            <input type="color" id="topbar_tvname_color" name="topbar_tvname_color" value="<?php echo htmlspecialchars($topbarTvNameColor); ?>">
                                            <label for="topbar_tvname_color"> TV Name Font Color</label>
                                        </div>
                                        <div class="option-div-color">
                                            <input type="color" id="topbar_deviceid_color" name="topbar_deviceid_color" value="<?php echo htmlspecialchars($topbarDeviceIdColor); ?>">
                                            <label for="topbar_deviceid_color"> Device ID Font Color</label>
                                        </div>
                                        <div class="option-div-color">
                                            <input type="color" id="topbar_time_color" name="topbar_time_color" value="<?php echo htmlspecialchars($topbarTimeColor); ?>">
                                            <label for="topbar_time_color"> Time Font Color</label>
                                        </div>
                                        <div class="option-div-color">
                                            <input type="color" id="topbar_date_color" name="topbar_date_color" value="<?php echo htmlspecialchars($topbarDateColor); ?>">
                                            <label for="topbar_date_color"> Date Font Color</label>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="form-row" style="margin-top: 5px">
                                <div class="form-column" style="flex: 1; border: 1px black solid; border-radius: 5px">
                                    <p style="background: #264B2B; color: white; padding: 8px; border-top-left-radius: 5px; border-top-right-radius: 5px;">Customize Content Containers</p>
                                    <form id="editContentContainerForm" enctype="multipart/form-data" style="padding: 8px">
                                        <?php foreach ($containers as $container): ?>
                                            <div style="display: flex; flex-direction: column; border: 1px solid gray; border-radius: 5px; padding: 5px; margin-bottom: 10px">
                                                <p><?php echo $container['container_name'];?></p>
                                                <div class="option-div-color">
                                                    <input type="color" id="container_<?php echo $container['container_id']; ?>_bg_color" name="container_<?php echo $container['container_id']; ?>_bg_color" value="<?php echo htmlspecialchars($container['parent_background_color']); ?>">
                                                    <label for="container_<?php echo $container['container_id']; ?>"> Background Color</label>
                                                </div>
                                                <div class="option-div-color">
                                                    <input type="color" id="container_<?php echo $container['container_id']; ?>_font_color" name="container_<?php echo $container['container_id']; ?>_font_color" value="<?php echo htmlspecialchars($container['parent_font_color']); ?>">
                                                    <label for="container_<?php echo $container['container_id']; ?>"> Font Color</label>
                                                </div>
                                                <div class="option-div-color">
                                                    <input type="color" id="container_<?php echo $container['container_id']; ?>_card_bg_color" name="container_<?php echo $container['container_id']; ?>_card_bg_color" value="<?php echo htmlspecialchars($container['child_background_color']); ?>">
                                                    <label for="container_<?php echo $container['container_id']; ?>_card_bg_color"> Card Background Color</label>
                                                </div>
                                                <div class="option-div-color">
                                                    <input type="color" id="container_<?php echo $container['container_id']; ?>_fcard_color" name="container_<?php echo $container['container_id']; ?>_fcard_color" value="<?php echo htmlspecialchars($container['child_font_color']); ?>">
                                                    <label for="container_<?php echo $container['container_id']; ?>_fcard_color"> Card Font Color</label>
                                                </div>
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
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const backgroundColorForm = document.getElementById('editBackgroundColorForm');
            const topBarColorForm = document.getElementById('editTopBarColorForm');
            const contentContainerForm = document.getElementById('editContentContainerForm');
            const visibilityForm = document.getElementById('visibilitySettingsForm');

            // Fetch the WebSocket URL from the PHP file
            fetch('websocket_conn.php')
                .then(response => response.text())
                .then(url => {
                    const ws = new WebSocket(url);

                    ws.onopen = () => console.log("WebSocket connection established.");

                    ws.onmessage = function (event) {
                        const message = JSON.parse(event.data);
                        if (message.action === 'update_background_color') {
                            if (message.success) {
                                console.log('Background Color updated!');
                            } else {
                                // Display an error message if needed
                                console.error('Update failed:', message.message);
                            }
                        } else if (message.action === 'update_topbar_color') {
                            if (message.success) {
                                console.log('Top Bar Color updated!');
                            } else {
                                // Display an error message if needed
                                console.error('Update failed:', message.message);
                            }
                        } else if (message.action === 'update_container_colors') {
                            if (message.success) {
                                console.log('Container color updated!');
                            } else {
                                console.error('Update failed:', message.message);
                            }
                        } else if (message.action === 'show_hide_content') {
                            if (message.success) {
                                console.log('Content visibility updated!');
                            } else {
                                console.error('Update failed:', message.message);
                            }
                        }
                    };

                    ws.onerror = function (error) {
                        console.error('WebSocket Error:', error);
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

                    // Submit topbar color changes
                    const topbarFields = ['topbar_color', 'topbar_tvname_color', 'topbar_deviceid_color', 'topbar_time_color', 'topbar_date_color'];
                    topbarFields.forEach(id => {
                        topBarColorForm.querySelector(`#${id}`).addEventListener('input', () => {
                            const formData = new FormData(topBarColorForm);
                            ws.send(JSON.stringify({
                                action: 'update_topbar_color',
                                tv_id: <?php echo $tvId; ?>,
                                topbar_hex_color: formData.get('topbar_color'),
                                topbar_tvname_font_color: formData.get('topbar_tvname_color'),
                                topbar_deviceid_font_color: formData.get('topbar_deviceid_color'),
                                topbar_time_font_color: formData.get('topbar_time_color'),
                                topbar_date_font_color: formData.get('topbar_date_color')
                            }));
                        });
                    });

                    // Automatically submit the content container form when a color changes
                    contentContainerForm.querySelectorAll('input[type="color"]').forEach(input => {
                        input.addEventListener('input', () => {
                            const colorsData = {};
                            contentContainerForm.querySelectorAll('input[type="color"]').forEach(colorInput => {
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
                                }
                            });

                            const data = {
                                action: 'update_container_colors',
                                tv_id: <?php echo $tvId; ?>,
                                containers: colorsData
                            };

                            console.log("Data being sent: ", data);
                            ws.send(JSON.stringify(data));
                        });
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
                })
                .catch(error => {
                    console.error('Error fetching WebSocket URL:', error);
                });
        });
    </script>
</body>
</html>