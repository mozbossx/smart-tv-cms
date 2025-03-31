<?php
// Start session
session_start();

include '../config_connection.php';

include 'tv_initialize_preview.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
    <link rel="icon" type="image/png" href="../images/usc_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../style_tv.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="../misc/js/treant-js-master/vendor/jquery.min.js"></script>
    <script src="../misc/js/treant-js-master/vendor/raphael.js"></script>
    <script src="../misc/js/treant-js-master/Treant.js"></script>
    <link rel="stylesheet" href="../misc/js/treant-js-master/Treant.css" />
    <script src="../jquery-textfill-master/dist/jquery.textfill.min.js"></script>
    <script src="../jquery-textfill-master/dist/jquery.textfill.js"></script>
    <script src="https://bumbu.me/svg-pan-zoom/dist/svg-pan-zoom.min.js"></script>
    <title><?php echo $_SESSION['tv_name'] ?></title>
</head>
<p id="screen" style="display: none; color: white; position: fixed"></p>
<body>
    <div style="<?php 
        if ($isStaticBackground) {
            echo "background: $backgroundColor;";
        } else if ($isImageBackground) {
            echo "background-image: url('../servers/tv_backgrounds/$tvId/$backgroundImagePath');";
            echo "background-size: cover;";
            echo "background-position: center;";
            echo "background-repeat: no-repeat;";
        }
        ?> cursor: pointer; width: 100%; height: calc(100% - 7vh); overflow: hidden; display: flex; flex-direction: column; /* Arrange containers vertically */ height: 100vh; overflow: hidden; /* Prevent any overflow */" id="tvBackgroundColor">
        <?php if ($topbarPosition === 'top') { ?>
            <?php include('tv_topbar_preview.php'); ?>
        <?php } ?>
        <div class="main-container" id="main-container">
        <?php foreach ($containers as $container): ?>
            <?php if ($container['type'] !== 'orgchart'):  ?>
                <?php $containerNameLower = strtolower($container['type']); ?>
                <div id="<?php echo $container['container_name']; ?>" class="content-container" data-container-id="<?php echo $container['container_id'];?>" 
                    data-x="<?php echo $container['xaxis']; ?>"
                    data-y="<?php echo $container['yaxis']; ?>"
                    style="background: <?php echo $container['parent_background_color']; ?>;
                            display: <?php echo $container['visible'] ? 'block' : 'none'; ?>;
                            height: <?php echo $container['height_px']; ?>px;
                            width: <?php echo $container['width_px']; ?>px;
                            transform: translate(<?php echo $container['xaxis']; ?>px, <?php echo $container['yaxis']; ?>px);">
                    <div>
                        <h1 id="<?php echo $containerNameLower; ?>ContainerName" class="content-title" style="color: <?php echo $container['parent_font_color']; ?>; font-style: <?php echo $container['parent_font_style']?>; font-family: <?php echo $container['parent_font_family']?>; width: 100%; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: <?php echo $container['container_name_text_align']; ?>;"><?php echo $container['container_name']; ?></h1>
                    </div>
                    <div id="<?php echo $containerNameLower; ?>CarouselContainer" class="carousel-container"
                        style="background: <?php echo $container['child_background_color']; ?>;
                                color: <?php echo $container['child_font_color']; ?>;
                                font-style: <?php echo $container['child_font_style'];?>;
                                font-family: <?php echo $container['child_font_family']; ?>">
                        <!-- Content for carousel-container will be displayed here -->
                    </div>
                    <div id="<?php echo $containerNameLower; ?>PageNumberContainer" class="page-number-container" style="color: <?php echo $container['parent_font_color']; ?>; font-style: <?php echo $container['parent_font_style']?>; font-family: <?php echo $container['parent_font_family']?>; text-align: center"></div>
                </div>        
            <?php endif; ?>
            <?php if ($container['type'] === 'orgchart'):  ?>
                <?php $containerNameLower = strtolower($container['type']); ?>
                <div id="<?php echo $container['container_name']; ?>" class="content-container" data-container-id="<?php echo $container['container_id'];?>" 
                    data-x="<?php echo $container['xaxis']; ?>"
                    data-y="<?php echo $container['yaxis']; ?>"
                    style="background: <?php echo $container['parent_background_color']; ?>;
                            display: <?php echo $container['visible'] ? 'block' : 'none'; ?>;
                            height: <?php echo $container['height_px']; ?>px;
                            width: <?php echo $container['width_px']; ?>px;
                            min-height: 450px;
                            min-width: 500px;
                            transform: translate(<?php echo $container['xaxis']; ?>px, <?php echo $container['yaxis']; ?>px);">
                    <div>
                        <h1 id="<?php echo $containerNameLower; ?>ContainerName" class="content-title" style="color: <?php echo $container['parent_font_color']; ?>; font-style: <?php echo $container['parent_font_style']?>; font-family: <?php echo $container['parent_font_family']?>; width: 100%; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: <?php echo $container['container_name_text_align']; ?>;"><?php echo $container['container_name']; ?></h1>
                    </div>
                    <div id="<?php echo $containerNameLower; ?>CarouselContainer" class="carousel-container"
                        style="background: <?php echo $container['child_background_color']; ?>;
                                color: <?php echo $container['child_font_color']; ?>;
                                font-style: <?php echo $container['child_font_style'];?>;
                                font-family: <?php echo $container['child_font_family']; ?>">
                        <!-- Content for carousel-container will be displayed here -->
                    </div>
                    <div id="<?php echo $containerNameLower; ?>PageNumberContainer" class="page-number-container" style="color: <?php echo $container['parent_font_color']; ?>; font-style: <?php echo $container['parent_font_style']?>; font-family: <?php echo $container['parent_font_family']?>; text-align: center"></div>
                </div> 
            <?php endif; ?>

            
        <?php endforeach; ?>

            
        </div>
        <?php if ($topbarPosition === 'bottom') { ?>
            <?php include('tv_topbar_preview.php'); ?>
        <?php } ?>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/web-animations/2.3.1/web-animations.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/muuri/0.5.3/muuri.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <script src="fetch_preview_content.js"></script>
    <script>
        // Function to update the clock
        function updateClock() {
            const now = new Date();
            const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sept", "Oct", "Nov", "Dec"];
            let hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12; // Convert to 12-hour format
            const dayOfWeek = daysOfWeek[now.getDay()]; // Get day of the week
            const month = months[now.getMonth()]; // Get full month name
            const day = now.getDate().toString().padStart(2, '0');
            const year = now.getFullYear();

            document.getElementById('live-clock').textContent = hours + ':' + minutes + ' ' + ampm;
            document.getElementById('live-date').textContent = dayOfWeek + ', ' + month + ' ' + day + ', ' + year;
        }

        // Update the clock every second
        setInterval(updateClock, 1000);
        updateClock(); // Initial update

        // Enhanced debug listener with media support
        window.addEventListener('message', function(event) {                        
            // Forward the message to any needed handlers
            if (event.data && event.data.action === 'updatePreview') {
                // Create a custom event to pass the data
                const previewEvent = new CustomEvent('previewUpdate', { 
                    detail: event.data 
                });
                document.dispatchEvent(previewEvent);
            }
        });

        // Fallback method for cross-origin iframe access issues
        function checkForParentMessages() {
            try {
                if (window.parent && window.parent !== window) {                    
                    // Create a handler for receiving messages via custom event
                    window.parent.postMessage({ action: 'readyForMessages', frameId: 'tv-iframe' }, '*');
                    
                    // Set up global handler for preview data
                    window.receivePreviewData = function(data) {                        
                        // Process the preview data
                        if (data && data.action === 'updatePreview') {
                            const event = new CustomEvent('previewUpdate', { detail: data });
                            document.dispatchEvent(event);
                        }
                    };
                }
            } catch (e) {
                console.error("Error checking for parent messages:", e);
            }
        }

        // Run the check when loaded
        if (document.readyState === 'complete') {
            checkForParentMessages();
        } else {
            window.addEventListener('load', checkForParentMessages);
        }
    </script>
    <script>
    // Function to check if containers are visible
    function checkContainerVisibility() {
        const containers = document.querySelectorAll('.content-container');        
        containers.forEach((container, index) => {
            const containerName = container.querySelector('.content-title')?.textContent || 'Unknown';
            const isVisible = container.style.display !== 'none';
            
            const carouselContainer = container.querySelector('.carousel-container');
            if (carouselContainer) {
                const carouselItems = carouselContainer.querySelectorAll('.carousel-item');
            }
        });
    }

    // Run visibility check after content is loaded and when preview updates occur
    document.addEventListener('previewUpdate', function() {
        setTimeout(checkContainerVisibility, 500);
    });

    // Also check on load
    document.addEventListener('DOMContentLoaded', function() {
        checkContainerVisibility();
    });
    </script>
    <style>
    /* Media container styling */
    .media-container {
        display: block !important;
        max-width: 100%;
        max-height: 70%;
        overflow: hidden;
        margin: 0 auto;
    }

    .media-container img,
    .media-container video {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        display: block;
        margin: 0 auto;
    }

    /* Preview item specific styling */
    .preview-item {
        display: block !important;
    }

    .preview-item .content-container-con {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .preview-item .content-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .preview-item {
        flex: 1;
        overflow: auto;
    }

    </style>
    <script>
    // Set up a MutationObserver to detect changes in the DOM
    const previewObserver = new MutationObserver(function(mutations) {
        for (const mutation of mutations) {
            if (mutation.type === 'childList') {
                // Check for preview signals
                const signals = document.querySelectorAll('[id^="preview-signal-"]');
                for (const signal of signals) {                    
                    // Signal was found, apply any needed updates
                    const type = signal.dataset.type;
                    const hasMedia = signal.dataset.hasMedia === 'true';
                    
                    if (type) {
                        // Check relevant container
                        const container = document.getElementById(`${type}CarouselContainer`);
                        if (container) {                            
                            // Check if preview content exists
                            const previewItems = container.querySelectorAll('.preview-item');
                            if (previewItems.length === 0) {                                
                                // Dispatch a custom event requesting re-render
                                document.dispatchEvent(new CustomEvent('requestPreviewRefresh', {
                                    detail: { type, hasMedia }
                                }));
                            }
                        }
                    }
                }
                
                // Check for media containers
                const mediaContainers = document.querySelectorAll('.media-container');
                for (const container of mediaContainers) {
                    // Make sure media elements are visible
                    const imgs = container.querySelectorAll('img');
                    const videos = container.querySelectorAll('video');
                    
                    imgs.forEach(img => {
                        img.style.display = 'block';
                        img.style.maxWidth = '100%';
                        img.style.maxHeight = '100%';
                        img.style.margin = '0 auto';
                    });
                    
                    videos.forEach(video => {
                        video.style.display = 'block';
                        video.style.maxWidth = '100%';
                        video.style.maxHeight = '100%';
                        video.style.margin = '0 auto';
                    });
                }
            }
        }
    });

    // Start observing the document body
    previewObserver.observe(document.body, { 
        childList: true, 
        subtree: true 
    });

    // Listen for refresh requests
    document.addEventListener('requestPreviewRefresh', function(e) {        
        // Ask parent for latest data
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({ 
                action: 'requestLatestPreview',
                type: e.detail.type
            }, '*');
        }
    });
    </script>
</body>
</html>