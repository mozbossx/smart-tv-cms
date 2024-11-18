<?php
session_start();
include 'config_connection.php';

$tvId = isset($_GET['tvId']) ? (int)$_GET['tvId'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';
$isPreview = isset($_GET['preview']) && $_GET['preview'] === 'true';

// Fetch TV dimensions
$sql = "SELECT width_px, height_px FROM smart_tvs_tb WHERE tv_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $tvId);
$stmt->execute();
$result = $stmt->get_result();
$tvDimensions = $result->fetch_assoc();

// Fetch container information
$sql = "SELECT * FROM containers_tb WHERE tv_id = ? AND type = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $tvId, $type);
$stmt->execute();
$result = $stmt->get_result();
$container = $result->fetch_assoc();

if (!$container) {
    die("Container not found");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Preview</title>
    <link rel="stylesheet" href="style_tv.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            width: <?php echo $tvDimensions['width_px']; ?>px;
            height: <?php echo $tvDimensions['height_px']; ?>px;
            overflow: hidden;
            background: transparent;
        }

        .tv-preview-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .container-not-visible {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            font-family: 'Roboto Flex', sans-serif;
            color: #666;
        }

        /* Import existing container styles */
        .content-container {
            position: absolute;
            transform: translate(<?php echo $container['xaxis']; ?>px, <?php echo $container['yaxis']; ?>px);
            width: <?php echo $container['width_px']; ?>px;
            height: <?php echo $container['height_px']; ?>px;
            background-color: <?php echo $container['parent_background_color']; ?>;
        }

        .content-title {
            color: <?php echo $container['parent_font_color']; ?>;
            font-family: <?php echo $container['parent_font_family']; ?>;
            font-style: <?php echo $container['parent_font_style']; ?>;
            padding: 10px;
            margin: 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .carousel-container {
            background-color: <?php echo $container['child_background_color']; ?>;
            color: <?php echo $container['child_font_color']; ?>;
            font-family: <?php echo $container['child_font_family']; ?>;
            font-style: <?php echo $container['child_font_style']; ?>;
            height: calc(100% - 60px);
            padding: 10px;
            overflow: auto;
        }

        .page-number-container {
            color: <?php echo $container['parent_font_color']; ?>;
            font-family: <?php echo $container['parent_font_family']; ?>;
            font-style: <?php echo $container['parent_font_style']; ?>;
            padding: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="tv-preview-wrapper">
        <?php if ($container['visible'] == 1): ?>
            <div class="content-container">
                <div>
                    <h1 class="content-title"><?php echo htmlspecialchars($container['container_name']); ?></h1>
                </div>
                <div id="<?php echo strtolower($type); ?>CarouselContainer" class="carousel-container">
                    <!-- Content will be populated by the TV display system -->
                </div>
                <div id="<?php echo strtolower($type); ?>PageNumberContainer" class="page-number-container"></div>
            </div>
        <?php else: ?>
            <div class="container-not-visible">
                <i class="fa fa-eye-slash"></i>
                Content container is not visible
            </div>
        <?php endif; ?>
    </div>

    <!-- Include necessary scripts -->
    <!-- <script src="js_tv/fetch_tv_content.js"></script> -->
</body>
</html>